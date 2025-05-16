<?php

namespace App\Http\Controllers;

use App\Models\CopyJob;
use Google\Service\Docs;
use Google\Service\Drive;
use App\Jobs\ProcessCopyJob;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CopyJobController extends Controller
{
    // Thêm constructor để áp dụng middleware
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('check.license')->except(['index']);
    }

    private function getClient()
    {
        $client = new GoogleClient();
        $client->setAuthConfig(storage_path('app/google-credentials.json'));
        $client->addScope(['https://www.googleapis.com/auth/drive', 'https://www.googleapis.com/auth/documents']);

        $user = Auth::user();

        if ($user && $user->access_token) {
            $accessToken = json_decode($user->access_token, true);
            $client->setAccessToken($accessToken);

            if ($client->isAccessTokenExpired()) {
                if ($user->refresh_token) {
                    $client->fetchAccessTokenWithRefreshToken($user->refresh_token);
                    $newToken = $client->getAccessToken();

                    // Update user's tokens
                    $user->access_token = json_encode($newToken);
                    $user->access_token_expiry = isset($newToken['expires_in'])
                        ? now()->addSeconds($newToken['expires_in'])
                        : null;
                    $user->save();

                    // Update session for backward compatibility
                    Session::put('google_access_token', $newToken);
                } else {
                    return redirect()->route('auth.google');
                }
            }
        } elseif (Session::has('google_access_token')) {
            // Backward compatibility
            $client->setAccessToken(Session::get('google_access_token'));

            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    Session::put('google_access_token', $client->getAccessToken());
                } else {
                    return redirect()->route('auth.google');
                }
            }
        }

        return $client;
    }

    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('auth.google');
        }

        $user = Auth::user();
        $query = CopyJob::where('email', $user->email);

        // Filter by date
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(10);
        $hasLicense = $user->hasValidLicense();

        return view('jobs.index', compact('jobs', 'hasLicense'));
    }

    public function create()
    {
        if (!Auth::check()) {
            return redirect()->route('auth.google');
        }

        $user = Auth::user();
        if (!$user->hasValidLicense() && !$user->isAdmin()) {
            return redirect()->route('license.verify')
                ->with('error', 'Bạn cần license key hợp lệ để sử dụng tính năng này.');
        }

        return view('jobs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_doc_ids' => 'required|string',
            'folder_option' => 'required|in:existing,new',
            'folder_id' => 'required_if:folder_option,existing',
            'new_folder_name' => 'required_if:folder_option,new|max:255',
            'interval_seconds' => 'required|integer|min:10',
        ]);

        if (!Auth::check()) {
            return redirect()->route('auth.google');
        }

        $user = Auth::user();

        // Find active license key for this user
        $licenseKey = $user->validLicenseKeys()->first();

        // Admin users bypass license check
        if (!$user->isAdmin() && !$licenseKey) {
            return redirect()->route('license.verify')
                ->with('error', 'Bạn cần license key hợp lệ để sử dụng tính năng này.');
        }

        try {
            $client = $this->getClient();
            $docsService = new Docs($client);
            $driveService = new Drive($client);

            // Xử lý thư mục đích
            $folderId = null;

            if ($validated['folder_option'] === 'existing') {
                $folderId = $validated['folder_id'];
            } else {
                // Tạo thư mục mới nếu đã chọn option "new"
                $folderMetadata = new \Google\Service\Drive\DriveFile([
                    'name' => $validated['new_folder_name'],
                    'mimeType' => 'application/vnd.google-apps.folder'
                ]);

                $folder = $driveService->files->create($folderMetadata, [
                    'fields' => 'id'
                ]);

                $folderId = $folder->getId();
            }

            // Xử lý nhiều ID tài liệu nguồn
            $sourceDocIds = preg_split('/\r\n|\r|\n/', $validated['source_doc_ids']);
            $sourceDocIds = array_map('trim', $sourceDocIds);
            $sourceDocIds = array_filter($sourceDocIds); // Loại bỏ chuỗi rỗng

            if (empty($sourceDocIds)) {
                return redirect()->back()
                    ->with('error', 'Không tìm thấy ID tài liệu hợp lệ.')
                    ->withInput();
            }

            // Nếu không phải admin và có giới hạn tài liệu, kiểm tra số lượng
            if (!$user->isAdmin() && $licenseKey && $licenseKey->max_documents !== null) {
                $remainingDocs = $licenseKey->max_documents - $licenseKey->documents_used;

                if (count($sourceDocIds) > $remainingDocs) {
                    return redirect()->back()
                        ->with('error', "License key của bạn chỉ còn cho phép sao chép {$remainingDocs} tài liệu.")
                        ->withInput();
                }
            }

            $jobsCreated = 0;
            $failedDocs = [];

            foreach ($sourceDocIds as $sourceDocId) {
                try {
                    // Lấy thông tin tài liệu nguồn
                    $sourceDoc = $docsService->documents->get($sourceDocId);
                    $sourceTitle = $sourceDoc->getTitle();
                    $content = $sourceDoc->getBody()->getContent();

                    // Đếm số câu/đoạn văn
                    $totalElements = count($content);

                    // Tạo tài liệu đích với tên giống tài liệu nguồn
                    $newDoc = new \Google\Service\Docs\Document([
                        'title' => $sourceTitle
                    ]);

                    $destinationDoc = $docsService->documents->create($newDoc);

                    // Di chuyển tài liệu đích vào thư mục đã chọn
                    $emptyFileMetadata = new \Google\Service\Drive\DriveFile();
                    $driveService->files->update(
                        $destinationDoc->getDocumentId(),
                        $emptyFileMetadata,
                        ['addParents' => $folderId, 'fields' => 'id, parents']
                    );

                    // Tạo công việc sao chép
                    $copyJob = CopyJob::create([
                        'source_doc_id' => $sourceDocId,
                        'destination_doc_id' => $destinationDoc->getDocumentId(),
                        'folder_id' => $folderId,
                        'email' => $user->email,
                        'access_token' => $user->access_token,
                        'refresh_token' => $user->refresh_token,
                        'total_sentences' => $totalElements,
                        'current_position' => 0,
                        'status' => 'pending',
                        'interval_seconds' => $validated['interval_seconds'],
                        'source_title' => $sourceTitle,
                        'destination_title' => $sourceTitle, // Same title for destination doc
                    ]);

                    // Đưa công việc vào hàng đợi
                    ProcessCopyJob::dispatch($copyJob);

                    $jobsCreated++;

                    // Cập nhật số lượng tài liệu đã sử dụng nếu có license key
                    if (!$user->isAdmin() && $licenseKey) {
                        $licenseKey->incrementDocumentsUsed();
                    }
                } catch (\Exception $docException) {
                    $failedDocs[] = [
                        'id' => $sourceDocId,
                        'error' => $docException->getMessage()
                    ];

                    // Tiếp tục với tài liệu tiếp theo
                    continue;
                }
            }

            if ($jobsCreated > 0) {
                $message = "Đã tạo {$jobsCreated} công việc sao chép thành công.";

                if (!empty($failedDocs)) {
                    $message .= " Có " . count($failedDocs) . " tài liệu không thể xử lý.";
                }

                return redirect()->route('jobs.index')->with('success', $message);
            } else {
                return redirect()->back()
                    ->with('error', 'Không thể tạo công việc sao chép. Tất cả tài liệu đều gặp lỗi.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function processJob(CopyJob $job)
    {
        // Kiểm tra nếu người dùng có quyền xử lý job này
        if (Auth::user()->email !== $job->email && !Auth::user()->isAdmin()) {
            return redirect()->route('jobs.index')
                ->with('error', 'Bạn không có quyền xử lý công việc này.');
        }

        if ($job->status === 'completed' || $job->status === 'failed') {
            return redirect()->route('jobs.index')
                ->with('error', "Công việc #{$job->id} không thể xử lý (trạng thái: {$job->status})");
        }

        // Đưa công việc vào hàng đợi
        ProcessCopyJob::dispatch($job);

        return redirect()->route('jobs.index')
            ->with('success', "Công việc #{$job->id} đã được đưa vào hàng đợi để xử lý");
    }

    public function getProgress(Request $request)
    {
        $jobIds = explode(',', $request->query('ids', ''));

        if (empty($jobIds)) {
            return response()->json(['jobs' => []]);
        }

        $jobs = CopyJob::whereIn('id', $jobIds)
            ->where(function ($query) {
                // Chỉ lấy công việc của người dùng hiện tại hoặc công việc không yêu cầu xác thực
                if (auth()->check()) {
                    $query->where('email', auth()->user()->email)
                        ->orWhereNull('email');
                } else {
                    $query->whereNull('email');
                }
            })
            ->get(['id', 'status', 'current_position', 'total_sentences', 'destination_doc_id', 'interval_seconds', 'source_title', 'destination_title']);

        $jobsArray = [];
        foreach ($jobs as $job) {
            $jobsArray[$job->id] = $job->toArray();
        }

        return response()->json(['jobs' => $jobsArray]);
    }

    public function destroy(CopyJob $job)
    {
        if (Auth::user()->email !== $job->email && !Auth::user()->isAdmin()) {
            return redirect()->route('jobs.index')
                ->with('error', 'Bạn không có quyền xóa công việc này.');
        }


        $job->delete();
        

        return redirect()->route('jobs.index')
            ->with('success', "Công việc #{$job->id} đã được xóa thành công.");
    }
}
