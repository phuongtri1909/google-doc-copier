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
        //Log::info("Starting job #{$this->copyJob->id}, attempt #{$this->attempts()}");

        try {
            // --- Setup Google Client and Authentication ---
            $client = $this->setupGoogleClient();
            $docsService = new Docs($client);

            // --- Get Source Document Content ---
            //Log::info("Job #{$this->copyJob->id}: Fetching source doc {$this->copyJob->source_doc_id}");
            $sourceDoc = $docsService->documents->get($this->copyJob->source_doc_id);
            $sourceStructuralElements = $sourceDoc->getBody()->getContent();
            $totalElements = count($sourceStructuralElements);
            //Log::info("Job #{$this->copyJob->id}: Source doc has {$totalElements} structural elements.");

            // --- Update Total Elements Count ---
            // Using total_sentences field to store total elements count
            if ($this->copyJob->total_sentences !== $totalElements) {
                // Log::info("Job #{$this->copyJob->id}: Updating total elements from {$this->copyJob->total_sentences} to {$totalElements}.");
                 $this->copyJob->total_sentences = $totalElements;
                 // Optional: Reset position if total changes drastically?
                 // if ($this->copyJob->current_position >= $totalElements) {
                 //     $this->copyJob->current_position = 0;
                 // }
                 $this->copyJob->save();
            }

            // --- Check if Copying is Complete ---
            if ($this->copyJob->current_position >= $totalElements) {
               // Log::info("Job #{$this->copyJob->id}: All elements already processed ({$this->copyJob->current_position}/{$totalElements}). Setting to completed.");
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
                // Log::info("Job #{$this->copyJob->id}: No more elements to copy in this batch range ({$startIndex} to {$endIndex}). Total: {$totalElements}. Setting to completed.");
                 $this->copyJob->status = 'completed';
                 $this->copyJob->error_message = null;
                 $this->copyJob->save();
                 return;
            }
           // Log::info("Job #{$this->copyJob->id}: Processing elements {$startIndex} to " . ($endIndex - 1));

            // --- Get Destination Document End Index ---
           // Log::info("Job #{$this->copyJob->id}: Fetching destination doc {$this->copyJob->destination_doc_id} to find insertion point.");
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
           // Log::info("Job #{$this->copyJob->id}: Determined insertion point at index {$insertAtIndex}");


            // --- Prepare Batch Update Requests ---
            $requests = [];
            $currentInsertIndex = $insertAtIndex; // Track insertion point dynamically

            foreach ($elementsToCopy as $elementIndex => $structuralElement) {
                $elementNumber = $startIndex + $elementIndex; // Actual index in source doc
               // Log::debug("Job #{$this->copyJob->id}: Processing element #{$elementNumber}");

                // --- Handle Paragraph Elements ---
                if (isset($structuralElement->paragraph)) {
                    $paragraph = $structuralElement->paragraph;
                    $paragraphElements = $paragraph->getElements() ?? [];

                    // --- Xử lý Paragraph Styles (đặc biệt là Heading) ---
                    $paragraphStyle = $paragraph->getParagraphStyle();
                    $paragraphStyleChanged = false;
                    $namedStyleType = $paragraphStyle ? $paragraphStyle->getNamedStyleType() : null;
                    $paragraphStartIndex = $currentInsertIndex; // Lưu vị trí bắt đầu của paragraph để áp dụng style sau

                    // Kiểm tra xem paragraph có phải là heading không
                    if ($namedStyleType && $namedStyleType !== 'NORMAL_TEXT') {
                      //  Log::debug("Job #{$this->copyJob->id}: Đoạn văn #{$elementNumber} có kiểu đặt tên: {$namedStyleType}");
                        $paragraphStyleChanged = true;
                    }

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
                                  //  Log::debug("Job #{$this->copyJob->id}: Inserting text '{$text}' at index {$currentInsertIndex}");
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
                                         //  Log::debug("Job #{$this->copyJob->id}: Applying style ({$styleFields}) to range [{$currentInsertIndex}, " . ($currentInsertIndex + $textLength) . "]");
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
                            // Log::warning("Job #{$this->copyJob->id}: Skipping InlineObjectElement (e.g., image) in element #{$elementNumber} - Not implemented.");
                             // Complex: Would need to fetch image bytes, upload, insert inline image request
                        }
                        // Add other element types here (HorizontalRule, PageBreak etc.) if needed
                    }

                    // Cải tiến đoạn xử lý empty paragraphs (đoạn trống) ở dòng 125-130
                    // --- Xử lý đặc biệt cho đoạn trống ---
                    // Đoạn trống thường được sử dụng để tạo khoảng cách trong tài liệu
                    if (count($paragraphElements) == 0 || $paragraphIsEmpty) {
                        // Lưu vị trí bắt đầu của đoạn trống
                        $emptyParagraphStart = $currentInsertIndex;
                        
                        // Chèn một ký tự xuống dòng (cần thiết để áp dụng style cho đoạn trống)
                        $requests[] = new Request([
                            'insertText' => new InsertTextRequest([
                                'location' => new Location(['index' => $currentInsertIndex]),
                                'text' => "\n"
                            ])
                        ]);
                        $currentInsertIndex += 1; // Tăng index sau khi chèn xuống dòng
                        
                        // Áp dụng định dạng khoảng cách cho đoạn trống nếu có
                        if ($paragraphStyle && ($paragraphStyle->getSpaceAbove() !== null || $paragraphStyle->getSpaceBelow() !== null)) {
                            $emptySpacingObj = new ParagraphStyle();
                            $emptySpacingFields = [];
                            
                            if ($paragraphStyle->getSpaceAbove() !== null) {
                                $emptySpacingObj->setSpaceAbove($paragraphStyle->getSpaceAbove());
                                $emptySpacingFields[] = 'spaceAbove';
                            }
                            
                            if ($paragraphStyle->getSpaceBelow() !== null) {
                                $emptySpacingObj->setSpaceBelow($paragraphStyle->getSpaceBelow());
                                $emptySpacingFields[] = 'spaceBelow';
                            }
                            
                            if (!empty($emptySpacingFields)) {
                              //  Log::debug("Job #{$this->copyJob->id}: Áp dụng khoảng cách cho đoạn trống");
                                $requests[] = new Request([
                                    'updateParagraphStyle' => new UpdateParagraphStyleRequest([
                                        'range' => new Range([
                                            'startIndex' => $emptyParagraphStart,
                                            'endIndex' => $currentInsertIndex
                                        ]),
                                        'paragraphStyle' => $emptySpacingObj,
                                        'fields' => implode(',', $emptySpacingFields)
                                    ])
                                ]);
                            }
                        }
                    }

                    // Thay thế phần chèn ngắt đoạn (khoảng dòng 162-173) với đoạn code sau:
                    // Đầu tiên, kiểm tra xem đoạn trước có phải là đoạn trống không
                    $isEmptyParagraph = count($paragraphElements) == 0 || $paragraphIsEmpty;

                    // Chèn ngắt đoạn (xuống dòng) chỉ khi cần thiết
                    if (!$isEmptyParagraph || $paragraphStyleChanged) { // Luôn thêm xuống dòng cho heading và đoạn có nội dung
                      //  Log::debug("Job #{$this->copyJob->id}: Chèn ngắt đoạn (xuống dòng) tại vị trí {$currentInsertIndex}");
                        $requests[] = new Request([
                            'insertText' => new InsertTextRequest([
                                'location' => new Location(['index' => $currentInsertIndex]),
                                'text' => "\n"
                            ])
                        ]);
                        $currentInsertIndex += 1; // Tăng vị trí chèn sau khi thêm xuống dòng
                    }

                    // --- Áp dụng paragraph style (đặc biệt là heading) nếu có ---
                    if ($paragraphStyleChanged && $paragraphStartIndex < $currentInsertIndex) {
                       // Log::debug("Job #{$this->copyJob->id}: Áp dụng kiểu định dạng {$namedStyleType} cho đoạn từ [{$paragraphStartIndex} đến {$currentInsertIndex}]");
                        
                        // Tạo request để áp dụng named style (như HEADING_1, HEADING_2, v.v.)
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
                        
                        // Nếu có thêm các thuộc tính paragraph style khác cần áp dụng
                        if ($paragraphStyle->getAlignment() !== null || 
                            $paragraphStyle->getIndentFirstLine() !== null ||
                            $paragraphStyle->getIndentStart() !== null) {
                            
                            $fields = [];
                            $styleObj = new ParagraphStyle();
                            
                            if ($paragraphStyle->getAlignment() !== null) {
                                $styleObj->setAlignment($paragraphStyle->getAlignment());
                                $fields[] = 'alignment';
                            }
                            
                            if ($paragraphStyle->getIndentFirstLine() !== null) {
                                $styleObj->setIndentFirstLine($paragraphStyle->getIndentFirstLine());
                                $fields[] = 'indentFirstLine';
                            }
                            
                            if ($paragraphStyle->getIndentStart() !== null) {
                                $styleObj->setIndentStart($paragraphStyle->getIndentStart());
                                $fields[] = 'indentStart';
                            }
                            
                            if (!empty($fields)) {
                              //  Log::debug("Job #{$this->copyJob->id}: Áp dụng style bổ sung cho đoạn: " . implode(',', $fields));
                                $requests[] = new Request([
                                    'updateParagraphStyle' => new UpdateParagraphStyleRequest([
                                        'range' => new Range([
                                            'startIndex' => $paragraphStartIndex,
                                            'endIndex' => $currentInsertIndex
                                        ]),
                                        'paragraphStyle' => $styleObj,
                                        'fields' => implode(',', $fields)
                                    ])
                                ]);
                            }
                        }
                    }

                    // Sửa đổi phần xử lý các thuộc tính khoảng cách (dòng 209-224) và đảm bảo nó được ưu tiên
                    // Cho phần khoảng cách đoạn lên đầu tiên sau khi áp dụng namedStyleType
                    if ($paragraphStyleChanged && $paragraphStartIndex < $currentInsertIndex) {
                        // ... giữ nguyên phần áp dụng named style (HEADING_1, HEADING_2,...)
                        
                        // Ưu tiên xử lý khoảng cách trên/dưới ngay sau khi áp dụng named style
                        if ($paragraphStyle->getSpaceAbove() !== null || 
                            $paragraphStyle->getSpaceBelow() !== null || 
                            $paragraphStyle->getLineSpacing() !== null) {
                            
                            $spacingFields = [];
                            $spacingObj = new ParagraphStyle();
                            
                            if ($paragraphStyle->getSpaceAbove() !== null) {
                                $spacingObj->setSpaceAbove($paragraphStyle->getSpaceAbove());
                                $spacingFields[] = 'spaceAbove';
                                
                                // Log chi tiết để debug
                                $magnitude = $paragraphStyle->getSpaceAbove()->getMagnitude();
                                $unit = $paragraphStyle->getSpaceAbove()->getUnit();
                              //  Log::debug("Job #{$this->copyJob->id}: Áp dụng khoảng cách trên: {$magnitude} {$unit}");
                            }
                            
                            if ($paragraphStyle->getSpaceBelow() !== null) {
                                $spacingObj->setSpaceBelow($paragraphStyle->getSpaceBelow());
                                $spacingFields[] = 'spaceBelow';
                                
                                // Log chi tiết để debug
                                $magnitude = $paragraphStyle->getSpaceBelow()->getMagnitude();
                                $unit = $paragraphStyle->getSpaceBelow()->getUnit();
                               // Log::debug("Job #{$this->copyJob->id}: Áp dụng khoảng cách dưới: {$magnitude} {$unit}");
                            }
                            
                            if ($paragraphStyle->getLineSpacing() !== null) {
                                $spacingObj->setLineSpacing($paragraphStyle->getLineSpacing());
                                $spacingFields[] = 'lineSpacing';
                              //  Log::debug("Job #{$this->copyJob->id}: Áp dụng khoảng cách dòng: {$paragraphStyle->getLineSpacing()}");
                            }
                            
                            if (!empty($spacingFields)) {
                              //  Log::debug("Job #{$this->copyJob->id}: Áp dụng khoảng cách cho đoạn: " . implode(',', $spacingFields));
                                $requests[] = new Request([
                                    'updateParagraphStyle' => new UpdateParagraphStyleRequest([
                                        'range' => new Range([
                                            'startIndex' => $paragraphStartIndex,
                                            'endIndex' => $currentInsertIndex
                                        ]),
                                        'paragraphStyle' => $spacingObj,
                                        'fields' => implode(',', $spacingFields)
                                    ])
                                ]);
                            }
                        }
                        
                        // Các phần áp dụng thuộc tính khác của paragraph style giữ nguyên...
                    }

                    // Thêm vào sau dòng 197 (sau phần kiểm tra các thuộc tính paragraph)
                    // Kiểm tra và áp dụng các thuộc tính khác như khoảng cách dòng, khoảng cách đoạn
                    if ($paragraphStyle->getSpaceAbove() !== null || 
                        $paragraphStyle->getSpaceBelow() !== null || 
                        $paragraphStyle->getLineSpacing() !== null ||
                        $paragraphStyle->getDirection() !== null) {
                        
                        $additionalFields = [];
                        $additionalStyleObj = new ParagraphStyle();
                        
                        if ($paragraphStyle->getSpaceAbove() !== null) {
                            $additionalStyleObj->setSpaceAbove($paragraphStyle->getSpaceAbove());
                            $additionalFields[] = 'spaceAbove';
                        }
                        
                        if ($paragraphStyle->getSpaceBelow() !== null) {
                            $additionalStyleObj->setSpaceBelow($paragraphStyle->getSpaceBelow());
                            $additionalFields[] = 'spaceBelow';
                        }
                        
                        if ($paragraphStyle->getLineSpacing() !== null) {
                            $additionalStyleObj->setLineSpacing($paragraphStyle->getLineSpacing());
                            $additionalFields[] = 'lineSpacing';
                        }
                        
                        if ($paragraphStyle->getDirection() !== null) {
                            $additionalStyleObj->setDirection($paragraphStyle->getDirection());
                            $additionalFields[] = 'direction';
                        }
                        
                        if (!empty($additionalFields)) {
                           // Log::debug("Job #{$this->copyJob->id}: Áp dụng định dạng đoạn bổ sung: " . implode(',', $additionalFields));
                            $requests[] = new Request([
                                'updateParagraphStyle' => new UpdateParagraphStyleRequest([
                                    'range' => new Range([
                                        'startIndex' => $paragraphStartIndex,
                                        'endIndex' => $currentInsertIndex
                                    ]),
                                    'paragraphStyle' => $additionalStyleObj,
                                    'fields' => implode(',', $additionalFields)
                                ])
                            ]);
                        }
                    }

                    // --- Lấy và áp dụng text style cho toàn bộ heading ---
                    if ($paragraphStyleChanged && $paragraphStartIndex < $currentInsertIndex) {
                        // Lấy style từ heading nguồn - ưu tiên style chung cho toàn bộ paragraph trước
                        $headingTextStyle = new TextStyle();
                        $styleFields = [];
                        
                        // 1. Kiểm tra nếu paragraph có style chung
                        if ($paragraph->getParagraphStyle() && $paragraph->getParagraphStyle()->getHeadingId()) {
                            // Thêm xử lý đặc biệt cho heading dựa trên headingId nếu cần
                        }
                        
                        // 2. Ưu tiên lấy style từ text run đầu tiên vì đó thường là style chính của heading
                        if (!empty($paragraphElements)) {
                            foreach ($paragraphElements as $element) {
                                if (isset($element->textRun) && $element->textRun->getTextStyle()) {
                                    $sourceStyle = $element->textRun->getTextStyle();
                                    
                                    // Sao chép các thuộc tính định dạng quan trọng
                                    if ($sourceStyle->getBold() !== null) {
                                        $headingTextStyle->setBold($sourceStyle->getBold());
                                        $styleFields[] = 'bold';
                                    }
                                    
                                    if ($sourceStyle->getItalic() !== null) {
                                        $headingTextStyle->setItalic($sourceStyle->getItalic());
                                        $styleFields[] = 'italic';
                                    }
                                    
                                    if ($sourceStyle->getUnderline() !== null) {
                                        $headingTextStyle->setUnderline($sourceStyle->getUnderline());
                                        $styleFields[] = 'underline';
                                    }
                                    
                                    if ($sourceStyle->getFontSize() !== null) {
                                        $headingTextStyle->setFontSize($sourceStyle->getFontSize());
                                        $styleFields[] = 'fontSize';
                                    }
                                    
                                    if ($sourceStyle->getWeightedFontFamily() !== null) {
                                        $headingTextStyle->setWeightedFontFamily($sourceStyle->getWeightedFontFamily());
                                        $styleFields[] = 'weightedFontFamily';
                                    }
                                    
                                    if ($sourceStyle->getForegroundColor() !== null) {
                                        $headingTextStyle->setForegroundColor($sourceStyle->getForegroundColor());
                                        $styleFields[] = 'foregroundColor';
                                    }
                                    
                                    // Lấy được style từ text run đầu tiên là đủ
                                    break;
                                }
                            }
                        }
                        
                        // Thêm ngay sau khi lấy được style từ text run đầu tiên (trong vòng lặp foreach $paragraphElements)
                        if ($namedStyleType && $namedStyleType !== 'NORMAL_TEXT') {
                            // Khi tìm thấy text run đầu tiên trong heading, debug chi tiết
                           // Log::debug("Job #{$this->copyJob->id}: Đã tìm thấy text run cho heading {$namedStyleType}");
                            $this->debugTextStyle($sourceStyle, "heading {$namedStyleType}");
                        }
                        
                        // 3. Áp dụng text style nếu có các thuộc tính cần thiết
                        if (!empty($styleFields)) {
                            $styleFieldsStr = implode(',', array_unique($styleFields));
                           // Log::debug("Job #{$this->copyJob->id}: Áp dụng định dạng văn bản rõ ràng cho heading: {$styleFieldsStr}");
                            
                            $requests[] = new Request([
                                'updateTextStyle' => new UpdateTextStyleRequest([
                                    'range' => new Range([
                                        'startIndex' => $paragraphStartIndex,
                                        'endIndex' => $currentInsertIndex - 1  // -1 để không bao gồm ký tự xuống dòng
                                    ]),
                                    'textStyle' => $headingTextStyle,
                                    'fields' => $styleFieldsStr
                                ])
                            ]);
                            
                            // 4. Ghi log chi tiết để debug
                            if ($headingTextStyle->getBold() !== null) {
                              //  Log::debug("Job #{$this->copyJob->id}: Heading sẽ được áp dụng Bold = " . 
                                      //    ($headingTextStyle->getBold() ? "true" : "false"));
                            }
                            
                            if ($headingTextStyle->getFontSize() !== null) {
                                $fontSize = $headingTextStyle->getFontSize()->getMagnitude() . 
                                           $headingTextStyle->getFontSize()->getUnit();
                             //   Log::debug("Job #{$this->copyJob->id}: Heading sẽ được áp dụng font size = {$fontSize}");
                            }
                        }
                    }

                }
                // --- Handle Table Elements ---
                elseif (isset($structuralElement->table)) {
                  //  Log::warning("Job #{$this->copyJob->id}: Skipping Table element #{$elementNumber} - Not implemented.");
                    // Complex: Would require iterating through rows/cells, creating table structure,
                    // inserting content with styles recursively.
                    // Need to insert a placeholder or estimate size to advance index? Risky.
                    // For now, we just skip and don't advance the insert index based on table content.
                }
                 // --- Handle Section Break Elements ---
                 elseif (isset($structuralElement->sectionBreak)) {
                   //  Log::warning("Job #{$this->copyJob->id}: Skipping SectionBreak element #{$elementNumber} - Not implemented.");
                     // Could potentially insert a page break or similar if needed.
                 }
                 // --- Handle Table of Contents ---
                 elseif (isset($structuralElement->tableOfContents)) {
                   //  Log::warning("Job #{$this->copyJob->id}: Skipping TableOfContents element #{$elementNumber} - Not implemented.");
                 }
                 else {
                  //   Log::warning("Job #{$this->copyJob->id}: Skipping unknown structural element type at index #{$elementNumber}.");
                 }
            } // End foreach ($elementsToCopy)


            // --- Execute Batch Update ---
            if (!empty($requests)) {
               // Log::info("Job #{$this->copyJob->id}: Executing batch update with " . count($requests) . " requests.");
                $batchUpdateRequest = new BatchUpdateDocumentRequest(['requests' => $requests]);
                $docsService->documents->batchUpdate($this->copyJob->destination_doc_id, $batchUpdateRequest);
              //  Log::info("Job #{$this->copyJob->id}: Batch update successful.");
            } else {
               // Log::info("Job #{$this->copyJob->id}: No requests generated for this batch (elements {$startIndex} to " . ($endIndex - 1) . "). Might be empty or unsupported elements.");
            }

            // --- Update Job State ---
            $newPosition = $endIndex; // We have processed elements up to this index
            $this->copyJob->current_position = $newPosition;
            $this->copyJob->status = ($newPosition >= $totalElements) ? 'completed' : 'processing';
            $this->copyJob->error_message = null; // Clear error on success
            $this->copyJob->save();

           // Log::info("Job #{$this->copyJob->id}: Processed elements up to " . ($newPosition - 1) . ". Position now {$newPosition}/{$totalElements}. Status: {$this->copyJob->status}");

            // --- Dispatch Next Job if Needed ---
            if ($this->copyJob->status === 'processing') {
                $delay = $this->copyJob->interval_seconds ?? 5; // Use interval from job or default
                ProcessCopyJob::dispatch($this->copyJob)->delay(now()->addSeconds($delay));
               // Log::info("Job #{$this->copyJob->id}: Dispatched next job with delay {$delay}s.");
            } else {
              //  Log::info("Job #{$this->copyJob->id} completed!");
            }

        } catch (Throwable $e) { // Catch broader Throwable
          //  Log::error("Error processing copy job #{$this->copyJob->id} on attempt #{$this->attempts()}: " . $e->getMessage());
          //  Log::error("Error Type: " . get_class($e));
           // Log::error("Trace: " . $e->getTraceAsString()); // Log stack trace

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
                  //   Log::warning("Job #{$this->copyJob->id}: Releasing job back to queue due to Google API error (Code: {$e->getCode()}).");
                     $this->release($this->getRetryDelay());
                     $this->copyJob->status = 'pending_retry'; // Custom status? Or keep 'processing'?
                     $this->copyJob->save();
                     return; // Stop execution after releasing
                 }
            }

             // General retry logic based on $tries and $backoff
             if ($this->attempts() < $this->tries) {
              //   Log::warning("Job #{$this->copyJob->id}: Releasing job back to queue for retry.");
                 $this->release($this->getRetryDelay());
                 $this->copyJob->status = 'pending_retry';
             } else {
              //   Log::error("Job #{$this->copyJob->id}: Job failed after maximum attempts.");
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
          //  Log::info("Access token expired for job #{$this->copyJob->id}. Attempting refresh.");
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
                     //   Log::info("Obtained a new refresh token for job #{$this->copyJob->id}.");
                    }
                    $this->copyJob->save();
                 //   Log::info("Access token refreshed successfully for job #{$this->copyJob->id}.");
                } catch (Throwable $e) {
                 //   Log::error("Failed to refresh access token for job #{$this->copyJob->id}: " . $e->getMessage());
                    throw new \Exception("Access token expired and refresh failed: " . $e->getMessage());
                }
            } else {
              //  Log::error("Access token expired and no refresh token available for job #{$this->copyJob->id}");
                throw new \Exception("Access token expired and no refresh token available.");
            }
        }
        return $client;
    }

    /**
     * Kiểm tra xem TextStyle có chứa định dạng thực sự không.
     * @param TextStyle $style
     * @return bool
     */
    private function hasFormatting(TextStyle $style): bool
    {
        // Kiểm tra đặc biệt cho font size vì đây là object phức tạp
        $hasFontSize = false;
        if ($style->getFontSize() !== null) {
            $fontSize = $style->getFontSize();
            // Chỉ coi là có font size nếu có magnitude (kích thước) thực sự
            if ($fontSize->getMagnitude() !== null && $fontSize->getMagnitude() > 0) {
                $hasFontSize = true;
            }
        }
        
        // Kiểm tra các thuộc tính định dạng phổ biến - cẩn thận hơn với các giá trị boolean
        return 
            // Bold, italic, underline có thể là true/false nhưng vẫn là định dạng
            $style->getBold() === true || 
            $style->getItalic() === true || 
            $style->getUnderline() === true ||
            $style->getStrikethrough() === true || 
            $style->getSmallCaps() === true ||
            $hasFontSize || 
            $style->getWeightedFontFamily() !== null ||
            $style->getForegroundColor() !== null || 
            $style->getBackgroundColor() !== null ||
            $style->getBaselineOffset() !== null;
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

    /**
     * Phân tích và ghi log chi tiết về text style để debug
     * @param TextStyle $style
     * @param string $elementDesc Mô tả phần tử đang xử lý
     */
    private function debugTextStyle(TextStyle $style, string $elementDesc): void
    {
     //   Log::debug("Phân tích style cho {$elementDesc}:");
        
        if ($style->getBold() !== null) {
          //  Log::debug("- Bold: " . ($style->getBold() ? "true" : "false"));
        }
        
        if ($style->getItalic() !== null) {
         //   Log::debug("- Italic: " . ($style->getItalic() ? "true" : "false"));
        }
        
        if ($style->getUnderline() !== null) {
          //  Log::debug("- Underline: " . ($style->getUnderline() ? "true" : "false"));
        }
        
        if ($style->getFontSize() !== null) {
            $fontSize = $style->getFontSize()->getMagnitude() . 
                       ($style->getFontSize()->getUnit() ?? 'PT');
          //  Log::debug("- FontSize: {$fontSize}");
        }
        
        if ($style->getWeightedFontFamily() !== null) {
            $fontFamily = $style->getWeightedFontFamily()->getFontFamily();
            $weight = $style->getWeightedFontFamily()->getWeight();
        //    Log::debug("- Font: {$fontFamily}, Weight: {$weight}");
        }
    }
}