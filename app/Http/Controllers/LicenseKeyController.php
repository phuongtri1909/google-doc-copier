<?php

namespace App\Http\Controllers;

use App\Models\LicenseKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicenseKeyController extends Controller
{
    /**
     * Show the license verification page.
     */
    public function verify()
    {
        $user = Auth::user();
        $activeKeys = $user->validLicenseKeys;
        
        return view('license.verify', compact('activeKeys'));
    }
    
    /**
     * Verify a license key.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);
        
        $licenseKey = LicenseKey::where('key', $request->license_key)->first();
        
        if (!$licenseKey) {
            return back()->with('error', 'License key không hợp lệ.');
        }
        
        if ($licenseKey->user_id) {
            return back()->with('error', 'License key đã được kích hoạt bởi người dùng khác.');
        }
        
        if (!$licenseKey->is_active) {
            return back()->with('error', 'License key đã bị vô hiệu hóa.');
        }
        
        if ($licenseKey->expires_at && $licenseKey->expires_at < now()) {
            return back()->with('error', 'License key đã hết hạn.');
        }
        
        $user = Auth::user();
        $licenseKey->user_id = $user->id;
        $licenseKey->activated_at = now();
        $licenseKey->save();
        
        return redirect()->route('jobs.create')->with('success', 'License key đã được kích hoạt thành công!');
    }
}