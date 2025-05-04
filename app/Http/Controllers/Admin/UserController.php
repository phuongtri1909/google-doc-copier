<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LicenseKey;
use App\Models\CopyJob;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $query = User::withCount(['licenseKeys', 'validLicenseKeys']);
        
        // Filter by search (name or email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }
        
        // Filter by license
        if ($request->filled('license')) {
            if ($request->license === 'active') {
                // Có license đang hoạt động
                $query->whereHas('validLicenseKeys');
            } else if ($request->license === 'inactive') {
                // Không có license hoặc chỉ có license không hoạt động
                $query->whereDoesntHave('validLicenseKeys');
            }
        }
        
        $users = $query->orderBy('role', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString(); // Giữ lại query string khi phân trang
        
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Người dùng đã được tạo thành công.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Các license keys của user
        $licenseKeys = $user->licenseKeys()->get();
        
        // Các công việc của user
        $jobs = CopyJob::where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Thống kê trạng thái công việc
        $jobStats = [
            'total' => CopyJob::where('email', $user->email)->count(),
            'completed' => CopyJob::where('email', $user->email)->where('status', 'completed')->count(),
            'pending' => CopyJob::where('email', $user->email)->where('status', 'pending')->count(),
            'processing' => CopyJob::where('email', $user->email)->where('status', 'processing')->count(),
            'failed' => CopyJob::where('email', $user->email)->where('status', 'failed')->count(),
        ];
        
        return view('admin.users.show', compact('user', 'licenseKeys', 'jobs', 'jobStats'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
            'is_active' => 'boolean',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->is_active = $request->has('is_active') ? 1 : 0;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'Thông tin người dùng đã được cập nhật.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Kiểm tra không cho phép xóa admin cuối cùng
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Không thể xóa admin cuối cùng của hệ thống.');
        }
        
        // Cập nhật license keys để không liên kết với user này nữa
        LicenseKey::where('user_id', $user->id)
            ->update(['user_id' => null, 'activated_at' => null]);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Người dùng đã được xóa thành công.');
    }
    
    /**
     * Toggle the active status of the user.
     */
    public function toggleStatus(User $user)
    {
        // Kiểm tra không cho phép vô hiệu hóa admin cuối cùng
        if ($user->isAdmin() && User::where('role', 'admin')->where('is_active', 1)->count() <= 1) {
            return back()->with('error', 'Không thể vô hiệu hóa admin cuối cùng của hệ thống.');
        }
        
        $user->is_active = !$user->is_active;
        $user->save();
        
        $status = $user->is_active ? 'kích hoạt' : 'vô hiệu hóa';
        
        return redirect()->back()
            ->with('success', "Người dùng {$user->name} đã được {$status} thành công.");
    }
    
    /**
     * Generate a new license key for the user.
     */
    public function generateLicense(Request $request, User $user)
    {
        $request->validate([
            'expires_at' => 'nullable|date|after:today',
            'max_documents' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);
        
        $licenseKey = new LicenseKey([
            'key' => LicenseKey::generateKey(),
            'user_id' => $user->id,
            'created_by' => auth()->id(),
            'expires_at' => $request->expires_at,
            'max_documents' => $request->max_documents,
            'notes' => $request->notes,
            'activated_at' => now(),
        ]);
        
        $licenseKey->save();
        
        return redirect()->route('admin.users.show', $user)
            ->with('success', "License key mới đã được tạo và gán cho người dùng {$user->name}.")
            ->with('createdKey', $licenseKey->key);
    }
    
    /**
     * Remove the license key from the user.
     */
    public function removeLicense(User $user, LicenseKey $licenseKey)
    {
        if ($licenseKey->user_id != $user->id) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'License key không thuộc về người dùng này.');
        }
        
        $licenseKey->user_id = null;
        $licenseKey->activated_at = null;
        $licenseKey->save();
        
        return redirect()->route('admin.users.show', $user)
            ->with('success', "License key đã được gỡ bỏ khỏi người dùng {$user->name}.");
    }
}