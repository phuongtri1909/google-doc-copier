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
// Add other necessary style classes if needed, e.g., ParagraphStyle, UpdateParagraphStyleRequest
use Illuminate\Support\Facades\Log;
use Throwable; // Import Throwable for broader exception catching

class ProcessCopyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $copyJob;
    // Define how many structural elements to copy per job execution
    const ELEMENTS_PER_BATCH = 3; // Reduced batch size due to complexity

    // Maximum attempts for the job
    public $tries = 3;
    // Delay in seconds before retrying
    public $backoff = [60, 120]; // 1 min, 2 mins

    public function __construct(CopyJob $copyJob)
    {
        $this->copyJob = $copyJob;
        // Ensure total_elements (using total_sentences field) is initialized
        if ($this->copyJob->total_sentences === null) {
             $this->copyJob->total_sentences = -1; // Indicate not yet calculated
        }
    }

    public function handle()
    {
        Log::info("Starting job #{$this->copyJob->id}, attempt #{$this->attempts()}");

        try {
            // --- Setup Google Client and Authentication ---
            $client = $this->setupGoogleClient();
            $docsService = new Docs($client);

            // --- Get Source Document Content ---
            Log::info("Job #{$this->copyJob->id}: Fetching source doc {$this->copyJob->source_doc_id}");
            $sourceDoc = $docsService->documents->get($this->copyJob->source_doc_id);
            $sourceStructuralElements = $sourceDoc->getBody()->getContent();
            $totalElements = count($sourceStructuralElements);
            Log::info("Job #{$this->copyJob->id}: Source doc has {$totalElements} structural elements.");

            // --- Update Total Elements Count ---
            // Using total_sentences field to store total elements count
            if ($this->copyJob->total_sentences !== $totalElements) {
                 Log::info("Job #{$this->copyJob->id}: Updating total elements from {$this->copyJob->total_sentences} to {$totalElements}.");
                 $this->copyJob->total_sentences = $totalElements;
                 // Optional: Reset position if total changes drastically?
                 // if ($this->copyJob->current_position >= $totalElements) {
                 //     $this->copyJob->current_position = 0;
                 // }
                 $this->copyJob->save();
            }

            // --- Check if Copying is Complete ---
            if ($this->copyJob->current_position >= $totalElements) {
                Log::info("Job #{$this->copyJob->id}: All elements already processed ({$this->copyJob->current_position}/{$totalElements}). Setting to completed.");
                $this->copyJob->status = 'completed';
                $this->copyJob->error_message = null; // Clear previous errors
                $this->copyJob->save();
                return;
            }

            // --- Determine Elements for this Batch ---
            $startIndex = $this->copyJob->current_position;
            $endIndex = min($startIndex + self::ELEMENTS_PER_BATCH, $totalElements);
            $elementsToCopy = array_slice($sourceStructuralElements, $startIndex, self::ELEMENTS_PER_BATCH);

            if (empty($elementsToCopy)) {
                 Log::info("Job #{$this->copyJob->id}: No more elements to copy in this batch range ({$startIndex} to {$endIndex}). Total: {$totalElements}. Setting to completed.");
                 $this->copyJob->status = 'completed';
                 $this->copyJob->error_message = null;
                 $this->copyJob->save();
                 return;
            }
            Log::info("Job #{$this->copyJob->id}: Processing elements {$startIndex} to " . ($endIndex - 1));

            // --- Get Destination Document End Index ---
            Log::info("Job #{$this->copyJob->id}: Fetching destination doc {$this->copyJob->destination_doc_id} to find insertion point.");
            $destDoc = $docsService->documents->get($this->copyJob->destination_doc_id);
            $destContent = $destDoc->getBody()->getContent();
            $insertAtIndex = 1; // Default for empty doc or inserting at the very beginning
            if (!empty($destContent)) {
                // Find the end index of the document body's content
                $lastElement = $destContent[count($destContent) - 1];
                 // Insert *before* the final newline of the body if it exists
                 // The body content always ends with a newline character that cannot be deleted.
                 $insertAtIndex = $lastElement->getEndIndex() -1;
                 if ($insertAtIndex < 1) {
                     $insertAtIndex = 1; // Cannot insert at index 0
                 }
            }
            Log::info("Job #{$this->copyJob->id}: Determined insertion point at index {$insertAtIndex}");


            // --- Prepare Batch Update Requests ---
            $requests = [];
            $currentInsertIndex = $insertAtIndex; // Track insertion point dynamically

            foreach ($elementsToCopy as $elementIndex => $structuralElement) {
                $elementNumber = $startIndex + $elementIndex; // Actual index in source doc
                Log::debug("Job #{$this->copyJob->id}: Processing element #{$elementNumber}");

                // --- Handle Paragraph Elements ---
                if (isset($structuralElement->paragraph)) {
                    $paragraph = $structuralElement->paragraph;
                    $paragraphElements = $paragraph->getElements() ?? [];

                    // --- TODO: Apply Paragraph Styles (Complex) ---
                    // $paragraphStyle = $paragraph->getParagraphStyle();
                    // Applying paragraph styles (like headings, alignment) correctly requires
                    // knowing the exact range of the entire paragraph *after* inserting text runs.
                    // This is complex to calculate accurately during the batch creation.
                    // Skipping paragraph style application for now.
                    // Log::debug("Job #{$this->copyJob->id}: (Skipping Paragraph Style Application for element #{$elementNumber})");


                    // --- Process Text Runs and other elements within the paragraph ---
                    $paragraphIsEmpty = true;
                    foreach ($paragraphElements as $element) {
                        if (isset($element->textRun)) {
                            $textRun = $element->textRun;
                            $text = $textRun->getContent();
                            $textStyle = $textRun->getTextStyle();

                            // Don't insert if text is only whitespace/newline unless it's the only element
                            // (to preserve empty lines maybe - adjust logic if needed)
                            if (!empty($text) && trim($text) !== '' || count($paragraphElements) === 1) {
                                $paragraphIsEmpty = false;
                                $textLength = mb_strlen($text); // Use multi-byte strlen

                                if ($textLength > 0) {
                                    // 1. Insert Text
                                    Log::debug("Job #{$this->copyJob->id}: Inserting text '{$text}' at index {$currentInsertIndex}");
                                    $requests[] = new Request([
                                        'insertText' => new InsertTextRequest([
                                            'location' => new Location(['index' => $currentInsertIndex]),
                                            'text' => $text
                                        ])
                                    ]);

                                    // 2. Apply Text Style (if any)
                                    if ($textStyle && $this->hasFormatting($textStyle)) {
                                        $styleFields = $this->getStyleFields($textStyle);
                                        if (!empty($styleFields)) {
                                            Log::debug("Job #{$this->copyJob->id}: Applying style ({$styleFields}) to range [{$currentInsertIndex}, " . ($currentInsertIndex + $textLength) . "]");
                                            $requests[] = new Request([
                                                'updateTextStyle' => new UpdateTextStyleRequest([
                                                    'range' => new Range([
                                                        'startIndex' => $currentInsertIndex,
                                                        'endIndex' => $currentInsertIndex + $textLength
                                                    ]),
                                                    'textStyle' => $textStyle, // Pass the whole style object
                                                    'fields' => $styleFields // Specify which fields to update
                                                ])
                                            ]);
                                        }
                                    }
                                    // Update insertion index for the next element
                                    $currentInsertIndex += $textLength;
                                }
                            }
                        }
                        // --- TODO: Handle other paragraph elements like Inline Objects (Images) ---
                        elseif (isset($element->inlineObjectElement)) {
                             Log::warning("Job #{$this->copyJob->id}: Skipping InlineObjectElement (e.g., image) in element #{$elementNumber} - Not implemented.");
                             // Complex: Would need to fetch image bytes, upload, insert inline image request
                        }
                        // Add other element types here (HorizontalRule, PageBreak etc.) if needed
                    }

                    // Ensure a newline character is inserted after processing a paragraph's content,
                    // unless the paragraph was completely empty or only contained whitespace.
                    // Google Docs uses '\n' to signify paragraph breaks.
                    if (!$paragraphIsEmpty || count($paragraphElements) == 0) { // Add newline for empty paragraphs too
                         Log::debug("Job #{$this->copyJob->id}: Inserting paragraph break (newline) at index {$currentInsertIndex}");
                         $requests[] = new Request([
                             'insertText' => new InsertTextRequest([
                                 'location' => new Location(['index' => $currentInsertIndex]),
                                 'text' => "\n"
                             ])
                         ]);
                         $currentInsertIndex += 1; // Increment index for the newline
                    }

                }
                // --- Handle Table Elements ---
                elseif (isset($structuralElement->table)) {
                    Log::warning("Job #{$this->copyJob->id}: Skipping Table element #{$elementNumber} - Not implemented.");
                    // Complex: Would require iterating through rows/cells, creating table structure,
                    // inserting content with styles recursively.
                    // Need to insert a placeholder or estimate size to advance index? Risky.
                    // For now, we just skip and don't advance the insert index based on table content.
                }
                 // --- Handle Section Break Elements ---
                 elseif (isset($structuralElement->sectionBreak)) {
                     Log::warning("Job #{$this->copyJob->id}: Skipping SectionBreak element #{$elementNumber} - Not implemented.");
                     // Could potentially insert a page break or similar if needed.
                 }
                 // --- Handle Table of Contents ---
                 elseif (isset($structuralElement->tableOfContents)) {
                     Log::warning("Job #{$this->copyJob->id}: Skipping TableOfContents element #{$elementNumber} - Not implemented.");
                 }
                 else {
                     Log::warning("Job #{$this->copyJob->id}: Skipping unknown structural element type at index #{$elementNumber}.");
                 }
            } // End foreach ($elementsToCopy)


            // --- Execute Batch Update ---
            if (!empty($requests)) {
                Log::info("Job #{$this->copyJob->id}: Executing batch update with " . count($requests) . " requests.");
                $batchUpdateRequest = new BatchUpdateDocumentRequest(['requests' => $requests]);
                $docsService->documents->batchUpdate($this->copyJob->destination_doc_id, $batchUpdateRequest);
                Log::info("Job #{$this->copyJob->id}: Batch update successful.");
            } else {
                Log::info("Job #{$this->copyJob->id}: No requests generated for this batch (elements {$startIndex} to " . ($endIndex - 1) . "). Might be empty or unsupported elements.");
            }

            // --- Update Job State ---
            $newPosition = $endIndex; // We have processed elements up to this index
            $this->copyJob->current_position = $newPosition;
            $this->copyJob->status = ($newPosition >= $totalElements) ? 'completed' : 'processing';
            $this->copyJob->error_message = null; // Clear error on success
            $this->copyJob->save();

            Log::info("Job #{$this->copyJob->id}: Processed elements up to " . ($newPosition - 1) . ". Position now {$newPosition}/{$totalElements}. Status: {$this->copyJob->status}");

            // --- Dispatch Next Job if Needed ---
            if ($this->copyJob->status === 'processing') {
                $delay = $this->copyJob->interval_seconds ?? 5; // Use interval from job or default
                ProcessCopyJob::dispatch($this->copyJob)->delay(now()->addSeconds($delay));
                Log::info("Job #{$this->copyJob->id}: Dispatched next job with delay {$delay}s.");
            } else {
                Log::info("Job #{$this->copyJob->id} completed!");
            }

        } catch (Throwable $e) { // Catch broader Throwable
            Log::error("Error processing copy job #{$this->copyJob->id} on attempt #{$this->attempts()}: " . $e->getMessage());
            Log::error("Error Type: " . get_class($e));
            Log::error("Trace: " . $e->getTraceAsString()); // Log stack trace

            $this->copyJob->status = 'failed';
            $this->copyJob->error_message = get_class($e) . ': ' . $e->getMessage(); // Store error message

            // Specific handling for Google API errors if possible
            if ($e instanceof \Google\Service\Exception) {
                 $errorBody = json_decode($e->getMessage(), true);
                 if ($errorBody && isset($errorBody['error']['message'])) {
                     $this->copyJob->error_message = 'Google API Error: ' . $errorBody['error']['message'];
                 }
                 // Check if it's a rate limit error or server error to potentially retry
                 if ($e->getCode() == 429 || $e->getCode() >= 500) {
                     Log::warning("Job #{$this->copyJob->id}: Releasing job back to queue due to Google API error (Code: {$e->getCode()}).");
                     $this->release($this->getRetryDelay());
                     $this->copyJob->status = 'pending_retry'; // Custom status? Or keep 'processing'?
                     $this->copyJob->save();
                     return; // Stop execution after releasing
                 }
            }

             // General retry logic based on $tries and $backoff
             if ($this->attempts() < $this->tries) {
                 Log::warning("Job #{$this->copyJob->id}: Releasing job back to queue for retry.");
                 $this->release($this->getRetryDelay());
                 $this->copyJob->status = 'pending_retry';
             } else {
                 Log::error("Job #{$this->copyJob->id}: Job failed after maximum attempts.");
                 // Keep status as 'failed'
             }
             $this->copyJob->save();

             // Optional: Rethrow the exception if you want Laravel's default failed job handling
             // throw $e;
        }
    }

    /**
     * Sets up the Google Client and handles token refresh.
     * @return GoogleClient
     * @throws \Exception If token refresh fails
     */
    private function setupGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setAuthConfig(storage_path('app/google-credentials.json'));
        $client->addScope([
            'https://www.googleapis.com/auth/documents',
            'https://www.googleapis.com/auth/drive.readonly' // Keep if needed elsewhere
        ]);
        // Important for refresh tokens
        $client->setAccessType('offline');
        // $client->setPrompt('consent'); // Only needed on initial authorization usually

        $accessToken = json_decode($this->copyJob->access_token, true);
        if (!$accessToken) {
             throw new \Exception("Invalid access token format for job #{$this->copyJob->id}");
        }
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            Log::info("Access token expired for job #{$this->copyJob->id}. Attempting refresh.");
            $refreshToken = $this->copyJob->refresh_token ?? ($accessToken['refresh_token'] ?? null);

            if ($refreshToken) {
                try {
                    $client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newAccessToken = $client->getAccessToken();

                    // Persist the new access token and potentially the new refresh token
                    $this->copyJob->access_token = json_encode($newAccessToken);
                    // Google might not always return a new refresh token
                    if (isset($newAccessToken['refresh_token'])) {
                        $this->copyJob->refresh_token = $newAccessToken['refresh_token'];
                        Log::info("Obtained a new refresh token for job #{$this->copyJob->id}.");
                    }
                    $this->copyJob->save();
                    Log::info("Access token refreshed successfully for job #{$this->copyJob->id}.");
                } catch (Throwable $e) {
                    Log::error("Failed to refresh access token for job #{$this->copyJob->id}: " . $e->getMessage());
                    throw new \Exception("Access token expired and refresh failed: " . $e->getMessage());
                }
            } else {
                Log::error("Access token expired and no refresh token available for job #{$this->copyJob->id}");
                throw new \Exception("Access token expired and no refresh token available.");
            }
        }
        return $client;
    }

    /**
     * Checks if a TextStyle object contains actual formatting.
     * @param TextStyle $style
     * @return bool
     */
    private function hasFormatting(TextStyle $style): bool
    {
        // Check common formatting properties
        return $style->getBold() || $style->getItalic() || $style->getUnderline() ||
               $style->getStrikethrough() || $style->getSmallCaps() ||
               $style->getForegroundColor() || $style->getBackgroundColor() ||
               $style->getFontSize() || $style->getWeightedFontFamily();
        // Add checks for other properties like baselineOffset, link if needed
    }

    /**
     * Generates the 'fields' string for UpdateTextStyleRequest based on set properties.
     * Google requires specifying which fields are being updated.
     * @param TextStyle $style
     * @return string Comma-separated list of fields (e.g., "bold,italic,fontSize")
     */
    private function getStyleFields(TextStyle $style): string
    {
        $fields = [];
        if ($style->getBold() !== null) $fields[] = 'bold';
        if ($style->getItalic() !== null) $fields[] = 'italic';
        if ($style->getUnderline() !== null) $fields[] = 'underline';
        if ($style->getStrikethrough() !== null) $fields[] = 'strikethrough';
        if ($style->getSmallCaps() !== null) $fields[] = 'smallCaps';
        if ($style->getForegroundColor() !== null) $fields[] = 'foregroundColor';
        if ($style->getBackgroundColor() !== null) $fields[] = 'backgroundColor';
        if ($style->getFontSize() !== null) $fields[] = 'fontSize';
        if ($style->getWeightedFontFamily() !== null) $fields[] = 'weightedFontFamily';
        if ($style->getBaselineOffset() !== null) $fields[] = 'baselineOffset';
        if ($style->getLink() !== null) $fields[] = 'link';
        // Add other style properties here...

        return implode(',', array_unique($fields)); // Use unique in case a property is somehow listed twice
    }

     /**
      * Calculate the delay for the next retry attempt.
      * Uses the $backoff property if defined, otherwise a default.
      * @return int Delay in seconds
      */
     private function getRetryDelay(): int
     {
         $defaultDelay = 60; // Default delay if backoff is not configured properly
         if (isset($this->backoff[$this->attempts() - 1])) {
             return $this->backoff[$this->attempts() - 1];
         } elseif (!empty($this->backoff)) {
             // If attempts exceed defined backoff array, use the last value
             return end($this->backoff);
         }
         return $defaultDelay;
     }

}