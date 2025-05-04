<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LicenseKey;
use App\Models\CopyJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Hiển thị trang dashboard cho admin
     */
    public function index()
    {
        // Tổng số người dùng
        $totalUsers = User::where('role', '!=', 'admin')->count();
        
        // Số lượng người dùng đăng ký mới trong 7 ngày qua
        $newUsers = User::where('role', '!=', 'admin')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        // Tổng số license keys
        $totalLicenses = LicenseKey::count();
        
        // Số license chưa kích hoạt
        $unusedLicenses = LicenseKey::whereNull('user_id')->count();
        
        // Số license hết hạn trong 7 ngày tới
        $expiringLicenses = LicenseKey::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>=', now())
            ->count();
        
        // Tổng số công việc
        $totalJobs = CopyJob::count();
        
        // Số công việc đang chờ xử lý
        $pendingJobs = CopyJob::where('status', 'pending')->count();
        
        // Số công việc đang xử lý
        $processingJobs = CopyJob::where('status', 'processing')->count();
        
        // Số công việc đã hoàn thành
        $completedJobs = CopyJob::where('status', 'completed')->count();
        
        // Số công việc thất bại
        $failedJobs = CopyJob::where('status', 'failed')->count();
        
        // Thống kê số lượng job theo ngày (7 ngày gần nhất)
        $jobsPerDay = CopyJob::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        $dates = [];
        $jobCounts = [];
        
        foreach ($jobsPerDay as $day) {
            $dates[] = date('d/m', strtotime($day->date));
            $jobCounts[] = $day->total;
        }
        
        // Thống kê người dùng hoạt động nhiều nhất
        $mostActiveUsers = User::select('users.id', 'users.name', 'users.email', DB::raw('count(copy_jobs.id) as job_count'))
            ->leftJoin('copy_jobs', 'users.email', '=', 'copy_jobs.email')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('job_count', 'desc')
            ->take(5)
            ->get();
        
        // License keys gần hết hạn
        $expiringLicensesList = LicenseKey::with('user')
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>=', now())
            ->orderBy('expires_at', 'asc')
            ->take(5)
            ->get();
        
        // Các công việc mới nhấtCall to undefined relationship [user] on model [App\Models\CopyJob].
        $recentJobs = CopyJob::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        return view('admin.dashboard', compact(
            'totalUsers', 
            'newUsers',
            'totalLicenses', 
            'unusedLicenses',
            'expiringLicenses',
            'totalJobs', 
            'pendingJobs',
            'processingJobs', 
            'completedJobs',
            'failedJobs',
            'dates',
            'jobCounts',
            'mostActiveUsers',
            'expiringLicensesList',
            'recentJobs'
        ));
    }
}