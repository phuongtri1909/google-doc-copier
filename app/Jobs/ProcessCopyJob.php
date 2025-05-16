<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CopyJob;
use Google\Client as GoogleClient;
use Google\Service\Docs;
use Google\Service\Docs\BatchUpdateDocumentRequest;
use Google\Service\Docs\InsertTextRequest;
use Google\Service\Docs\Location;
use Google\Service\Docs\Request;
use Google\Service\Docs\Range;
use Google\Service\Docs\UpdateTextStyleRequest;
use Google\Service\Docs\TextStyle;
use Google\Service\Docs\UpdateParagraphStyleRequest;
use Google\Service\Docs\ParagraphStyle;
use Throwable;

class ProcessCopyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $copyJob;
    const ELEMENTS_PER_BATCH = 5;
    public $tries = 3;
    public $backoff = [60, 120];

    public function __construct(CopyJob $copyJob)
    {
        $this->copyJob = $copyJob;
        if ($this->copyJob->total_sentences === null) {
            $this->copyJob->total_sentences = -1;
        }
    }

    public function handle()
    {
        try {
            $client = $this->setupGoogleClient();
            $docsService = new Docs($client);

            $sourceDoc = $docsService->documents->get($this->copyJob->source_doc_id);
            $sourceStructuralElements = $sourceDoc->getBody()->getContent();
            $totalElements = count($sourceStructuralElements);

            if ($this->copyJob->total_sentences !== $totalElements) {
                $this->copyJob->total_sentences = $totalElements;
                $this->copyJob->save();
            }

            if ($this->copyJob->current_position >= $totalElements) {
                $this->copyJob->status = 'completed';
                $this->copyJob->error_message = null;
                $this->copyJob->save();
                return;
            }

            $startIndex = $this->copyJob->current_position;
            $endIndex = min($startIndex + self::ELEMENTS_PER_BATCH, $totalElements);
            $elementsToCopy = array_slice($sourceStructuralElements, $startIndex, self::ELEMENTS_PER_BATCH);

            if (empty($elementsToCopy)) {
                $this->copyJob->status = 'completed';
                $this->copyJob->error_message = null;
                $this->copyJob->save();
                return;
            }

            $destDoc = $docsService->documents->get($this->copyJob->destination_doc_id);
            $destContent = $destDoc->getBody()->getContent();
            $insertAtIndex = 1;
            if (!empty($destContent)) {
                $lastElement = $destContent[count($destContent) - 1];
                $insertAtIndex = $lastElement->getEndIndex() - 1;
                if ($insertAtIndex < 1) {
                    $insertAtIndex = 1;
                }
            }

            $requests = [];
            $currentInsertIndex = $insertAtIndex;

            foreach ($elementsToCopy as $elementIndex => $structuralElement) {
                $elementNumber = $startIndex + $elementIndex;

                if (isset($structuralElement->paragraph)) {
                    $paragraph = $structuralElement->paragraph;
                    $paragraphElements = $paragraph->getElements() ?? [];

                    $paragraphStyle = $paragraph->getParagraphStyle();
                    $namedStyleType = $paragraphStyle ? $paragraphStyle->getNamedStyleType() : null;
                    $paragraphStartIndex = $currentInsertIndex;
                    $isHeading = $namedStyleType && $namedStyleType !== 'NORMAL_TEXT';
                    $paragraphTextStartIndex = $currentInsertIndex; // Track text start for bold formatting

                    $paragraphIsEmpty = true;
                    foreach ($paragraphElements as $element) {
                        if (isset($element->textRun)) {
                            $textRun = $element->textRun;
                            $text = $textRun->getContent();

                            if (!empty($text) && trim($text) !== '' || count($paragraphElements) === 1) {
                                $paragraphIsEmpty = false;
                                $textLength = mb_strlen($text);

                                if ($textLength > 0) {
                                    // Insert the text content
                                    $requests[] = new Request([
                                        'insertText' => new InsertTextRequest([
                                            'location' => new Location(['index' => $currentInsertIndex]),
                                            'text' => $text
                                        ])
                                    ]);
                                    
                                    $currentInsertIndex += $textLength;
                                }
                            }
                        }
                    }

                    // Handle empty paragraphs
                    if (count($paragraphElements) == 0 || $paragraphIsEmpty) {
                        $requests[] = new Request([
                            'insertText' => new InsertTextRequest([
                                'location' => new Location(['index' => $currentInsertIndex]),
                                'text' => "\n"
                            ])
                        ]);
                        $currentInsertIndex += 1;
                    }

                    // Insert line break for all paragraphs
                    $requests[] = new Request([
                        'insertText' => new InsertTextRequest([
                            'location' => new Location(['index' => $currentInsertIndex]),
                            'text' => "\n"
                        ])
                    ]);
                    $currentInsertIndex += 1;

                    // Apply heading style if this is a heading
                    if ($isHeading && $paragraphStartIndex < $currentInsertIndex) {
                        // 1. First apply the named style type (HEADING_1, HEADING_2, etc.)
                        $requests[] = new Request([
                            'updateParagraphStyle' => new UpdateParagraphStyleRequest([
                                'range' => new Range([
                                    'startIndex' => $paragraphStartIndex,
                                    'endIndex' => $currentInsertIndex
                                ]),
                                'paragraphStyle' => new ParagraphStyle([
                                    'namedStyleType' => $namedStyleType
                                ]),
                                'fields' => 'namedStyleType'
                            ])
                        ]);
                        
                        // 2. For all headings, explicitly make them bold
                        // Note: The Range excludes the newline character
                        $headingTextLength = $currentInsertIndex - $paragraphStartIndex - 1;
                        if ($headingTextLength > 0) {
                            $requests[] = new Request([
                                'updateTextStyle' => new UpdateTextStyleRequest([
                                    'range' => new Range([
                                        'startIndex' => $paragraphStartIndex,
                                        'endIndex' => $currentInsertIndex - 1 // Exclude the newline
                                    ]),
                                    'textStyle' => new TextStyle([
                                        'bold' => true
                                    ]),
                                    'fields' => 'bold'
                                ])
                            ]);
                        }
                    }
                }
            }

            if (!empty($requests)) {
                $batchUpdateRequest = new BatchUpdateDocumentRequest(['requests' => $requests]);
                $docsService->documents->batchUpdate($this->copyJob->destination_doc_id, $batchUpdateRequest);
            }

            $newPosition = $endIndex;
            $this->copyJob->current_position = $newPosition;
            $this->copyJob->status = ($newPosition >= $totalElements) ? 'completed' : 'processing';
            $this->copyJob->error_message = null;
            $this->copyJob->save();

            if ($this->copyJob->status === 'processing') {
                $delay = $this->copyJob->interval_seconds ?? 5;
                ProcessCopyJob::dispatch($this->copyJob)->delay(now()->addSeconds($delay));
            }

        } catch (Throwable $e) {
            $this->copyJob->status = 'failed';
            $this->copyJob->error_message = get_class($e) . ': ' . $e->getMessage();

            if ($e instanceof \Google\Service\Exception) {
                $errorBody = json_decode($e->getMessage(), true);
                if ($errorBody && isset($errorBody['error']['message'])) {
                    $this->copyJob->error_message = 'Google API Error: ' . $errorBody['error']['message'];
                }
                if ($e->getCode() == 429 || $e->getCode() >= 500) {
                    $this->release($this->getRetryDelay());
                    $this->copyJob->status = 'pending_retry';
                    $this->copyJob->save();
                    return;
                }
            }

            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());
                $this->copyJob->status = 'pending_retry';
            }
            $this->copyJob->save();
        }
    }

    private function setupGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->addScope([
            'https://www.googleapis.com/auth/documents',
            'https://www.googleapis.com/auth/drive.readonly'
        ]);
        $client->setAccessType('offline');

        $accessToken = json_decode($this->copyJob->access_token, true);
        if (!$accessToken) {
            throw new \Exception("Invalid access token format for job #{$this->copyJob->id}");
        }
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $this->copyJob->refresh_token ?? ($accessToken['refresh_token'] ?? null);

            if ($refreshToken) {
                try {
                    $client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newAccessToken = $client->getAccessToken();

                    $this->copyJob->access_token = json_encode($newAccessToken);
                    if (isset($newAccessToken['refresh_token'])) {
                        $this->copyJob->refresh_token = $newAccessToken['refresh_token'];
                    }
                    $this->copyJob->save();
                } catch (Throwable $e) {
                    throw new \Exception("Access token expired and refresh failed: " . $e->getMessage());
                }
            } else {
                throw new \Exception("Access token expired and no refresh token available.");
            }
        }
        return $client;
    }

    private function getRetryDelay(): int
    {
        $defaultDelay = 60;
        if (isset($this->backoff[$this->attempts() - 1])) {
            return $this->backoff[$this->attempts() - 1];
        } elseif (!empty($this->backoff)) {
            return end($this->backoff);
        }
        return $defaultDelay;
    }
}