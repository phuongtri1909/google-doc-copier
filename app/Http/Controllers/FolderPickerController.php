<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Support\Facades\Session;

class FolderPickerController extends Controller
{
    private function getClient()
    {
        $client = new GoogleClient();
        $client->setAuthConfig(storage_path('app/google-credentials.json'));
        $client->addScope([
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/documents'
        ]);

        if (Session::has('google_access_token')) {
            $client->setAccessToken(Session::get('google_access_token'));

            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    Session::put('google_access_token', $client->getAccessToken());
                } else {
                    return null; // Thông báo lỗi sẽ được xử lý ở controller
                }
            }
        } else {
            return null;
        }

        return $client;
    }

    public function showPicker()
    {
        $client = $this->getClient();
        
        if (!$client) {
            return view('folders.picker-error', [
                'error' => 'Bạn cần đăng nhập Google trước khi sử dụng tính năng này.'
            ]);
        }
        
        // Lấy access token cho client-side picker
        $accessToken = $client->getAccessToken();
        
        return view('folders.picker', [
            'accessToken' => $accessToken['access_token'] ?? null
        ]);
    }
    
    public function createFolder(Request $request)
    {
        $request->validate([
            'folder_name' => 'required|string|max:255',
        ]);
        
        $client = $this->getClient();
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xác thực với Google. Vui lòng đăng nhập lại.'
            ]);
        }
        
        $driveService = new Drive($client);
        
        $folderMetadata = new Drive\DriveFile([
            'name' => $request->folder_name,
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);
        
        try {
            $folder = $driveService->files->create($folderMetadata, [
                'fields' => 'id, name'
            ]);
            
            return response()->json([
                'success' => true,
                'folder' => [
                    'id' => $folder->getId(),
                    'name' => $folder->getName()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo thư mục: ' . $e->getMessage()
            ]);
        }
    }
}