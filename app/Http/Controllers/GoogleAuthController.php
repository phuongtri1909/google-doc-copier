<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CopyJob;
use Google\Service\Oauth2;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Nette\Utils\Random;

class GoogleAuthController extends Controller
{
    private function getClient()
    {
        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(url('/auth/google/callback'));
        $client->addScope([
            'https://www.googleapis.com/auth/drive.file', 
            'https://www.googleapis.com/auth/documents',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile'
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        return $client;
    }

    public function redirect()
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();

        \Log::info("Redirecting to Google OAuth: " . $authUrl);

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            $errorCode = $request->input('error');
    
            if ($errorCode === 'access_denied') {
                return redirect()->route('home')->with(
                    'error',
                    'Bạn đã từ chối cấp quyền truy cập. Ứng dụng cần các quyền này để hoạt động đúng cách.'
                );
            }
    
            // Handle verification error with better guidance
            \Log::error('Google OAuth error: ' . $errorCode);
            return redirect()->route('home')->with(
                'error',
                'Ứng dụng đang trong giai đoạn thử nghiệm. Khi đăng nhập Google, bạn sẽ thấy cảnh báo "Ứng dụng chưa được xác minh". 
                Vui lòng nhấn vào "Nâng cao" ở góc dưới bên trái và chọn "Tiếp tục đến [tên ứng dụng của bạn]" để tiếp tục sử dụng.'
            );
        }
    
        $client = $this->getClient();

        if ($request->has('code')) {
            try {
                $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

                // Check if token contains errors
                if (isset($token['error'])) {
                    \Log::error('Token error: ' . json_encode($token));
                    return redirect()->route('home')->with('error', 'Authentication failed: ' . ($token['error_description'] ?? $token['error']));
                }

                $client->setAccessToken($token);

                // Verify token is set before proceeding
                if (!$client->getAccessToken()) {
                    \Log::error('Access token not set after fetching');
                    return redirect()->route('home')->with('error', 'Failed to get access token');
                }

                // Get user info from Google
                $oauth2 = new Oauth2($client);
                $userInfo = $oauth2->userinfo->get();
                
                $googleId = $userInfo->getId();
                $email = $userInfo->getEmail();
                $name = $userInfo->getName();
                $avatar = $userInfo->getPicture();
                
                // Find or create user
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => bcrypt(Random::generate(16)),
                        'google_id' => $googleId,
                        'avatar' => $avatar,
                        'access_token' => json_encode($token),
                        'refresh_token' => $token['refresh_token'] ?? null,
                        'access_token_expiry' => isset($token['expires_in']) 
                            ? now()->addSeconds($token['expires_in']) 
                            : null,
                    ]
                );
                
                // Log the user in
                Auth::login($user);
                
                // Store in session for backward compatibility
                Session::put('google_access_token', $token);
                Session::put('user_email', $email);

                \Log::info('User logged in successfully: ' . $email);

                // Admin users go to admin dashboard
                if ($user->isAdmin()) {
                    return redirect()->route('admin.dashboard')->with('success', 'Đăng nhập thành công!');
                }

                // Redirect to license verification if user doesn't have a valid license
                if (!$user->hasValidLicense()) {
                    return redirect()->route('license.verify')->with('warning', 'Vui lòng kích hoạt license key để sử dụng dịch vụ.');
                }

                return redirect()->route('jobs.create')->with('success', 'Đăng nhập thành công!');
            } catch (\Exception $e) {
                \Log::error('Google API error: ' . $e->getMessage());
                \Log::error('Exception trace: ' . $e->getTraceAsString());
                return redirect()->route('home')->with('error', 'Lỗi xác thực: ' . $e->getMessage());
            }
        }

        return redirect()->route('home')->with('error', 'Xác thực thất bại');
    }
    
    public function logout()
    {
        Auth::logout();
        Session::forget(['google_access_token', 'user_email']);
        
        return redirect()->route('home')->with('success', 'Đăng xuất thành công!');
    }
}