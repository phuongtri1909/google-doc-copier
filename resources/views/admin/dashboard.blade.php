@extends('layouts.admin')

@section('title', 'Dashboard Admin | Google Doc Copier')

@push('styles')
<style>
    .stats-card {
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        margin-bottom: 1rem;
        border: none;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.5rem;
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .stats-title {
        font-size: 0.9rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .chart-container {
        position: relative;
        min-height: 300px;
    }
    
    .table-container {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }
    
    .table-responsive {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .dashboard-section-title {
        font-size: 1.25rem;
        margin-bottom: 1.25rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
    }
    
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease;
    }
    
    .animate-on-scroll.animated {
        opacity: 1;
        transform: translateY(0);
    }
    
    .badge-count {
        position: absolute;
        top: -5px;
        right: -5px;
        font-size: 0.7rem;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: var(--danger);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0">Dashboard</h1>
            <p class="text-muted">Tổng quan hệ thống Google Doc Copier</p>
        </div>
        <div class="col-md-4 d-flex justify-content-md-end align-items-center">
            <span class="text-muted me-3">
                <i class="far fa-calendar-alt me-1"></i> {{ now()->format('d/m/Y') }}
            </span>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <!-- Người dùng -->
        <div class="col-md-3 col-sm-6 animate-on-scroll" data-animation-delay="100">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-primary-light text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-number">{{ $totalUsers }}</div>
                        <div class="stats-title">Người dùng</div>
                    </div>
                    @if($newUsers > 0)
                        <div class="ms-auto position-relative">
                            <i class="fas fa-user-plus text-success"></i>
                            <span class="badge-count">{{ $newUsers }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- License -->
        <div class="col-md-3 col-sm-6 animate-on-scroll" data-animation-delay="200">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-success-light text-success">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-number">{{ $totalLicenses }}</div>
                        <div class="stats-title">License Keys</div>
                    </div>
                    @if($unusedLicenses > 0)
                        <div class="ms-auto position-relative">
                            <i class="fas fa-tag text-warning"></i>
                            <span class="badge-count">{{ $unusedLicenses }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tổng Jobs -->
        <div class="col-md-3 col-sm-6 animate-on-scroll" data-animation-delay="300">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-info-light text-info">
                        <i class="fas fa-copy"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-number">{{ $totalJobs }}</div>
                        <div class="stats-title">Công việc</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completion Rate -->
        <div class="col-md-3 col-sm-6 animate-on-scroll" data-animation-delay="400">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-warning-light text-warning">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-number">
                            @if($totalJobs > 0)
                                {{ round(($completedJobs / $totalJobs) * 100) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <div class="stats-title">Tỷ lệ hoàn thành</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Job Status Stats -->
    <div class="row mt-4">
        <!-- Job đang xử lý -->
        <div class="col-md-3 col-sm-6 animate-on-scroll" data-animation-delay="500">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-primary text-white">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-number">{{ $processingJobs }}</div>
                        <div class="stats-title">Đang xử lý</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job đang chờ -->
        <div class="col-md-3 col-sm-6 animate-on-scroll" data-animation-delay="600">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-warning text-white">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-number">{{ $pendingJobs }}</div>
                        <div class="stats-title">Đang chờ</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job hoàn thành -->
        <div class="col-md-3 col-sm-6 animate-on-scroll" data-animation-delay="700">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-success text-white">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-number">{{ $completedJobs }}</div>
                        <div class="stats-title">Hoàn thành</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job thất bại -->
        <div class="col-md-3 col-sm-6 animate-on-scroll" data-animation-delay="800">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-danger text-white">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-number">{{ $failedJobs }}</div>
                        <div class="stats-title">Thất bại</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Chart - Jobs per day -->
        <div class="col-lg-8 animate-on-scroll" data-animation-delay="900">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Biểu đồ công việc trong 7 ngày qua</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="jobsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- License keys sắp hết hạn -->
        <div class="col-lg-4 animate-on-scroll" data-animation-delay="1000">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">License sắp hết hạn</h5>
                    @if(count($expiringLicensesList) > 0)
                        <span class="badge bg-warning">{{ count($expiringLicensesList) }}</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if(count($expiringLicensesList) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>License Key</th>
                                        <th>Người dùng</th>
                                        <th>Hết hạn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($expiringLicensesList as $license)
                                    <tr>
                                        <td>{{ $license->key }}</td>
                                        <td>
                                            @if($license->user)
                                                {{ $license->user->name }}
                                            @else
                                                <span class="text-muted">Chưa kích hoạt</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-danger">{{ $license->expires_at->format('d/m/Y') }}</span>
                                            <small class="d-block text-muted">(còn {{ now()->diffInDays($license->expires_at) }} ngày)</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 2rem;"></i>
                            <p class="mb-0">Không có license nào sắp hết hạn</p>
                        </div>
                    @endif
                </div>
                @if(count($expiringLicensesList) > 0)
                    <div class="card-footer bg-white text-center">
                        <a href="{{ route('admin.license-keys.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt me-1"></i> Xem tất cả license
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Top active users -->
        <div class="col-lg-6 animate-on-scroll" data-animation-delay="1100">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Người dùng hoạt động nhiều nhất</h5>
                </div>
                <div class="card-body p-0">
                    @if(count($mostActiveUsers) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Người dùng</th>
                                        <th>Email</th>
                                        <th>Số công việc</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mostActiveUsers as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $user->job_count }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-link p-0">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="mb-0">Chưa có dữ liệu người dùng</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i> Xem tất cả người dùng
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent jobs -->
        <div class="col-lg-6 animate-on-scroll" data-animation-delay="1200">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Công việc gần đây</h5>
                </div>
                <div class="card-body p-0">
                    @if(count($recentJobs) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Người dùng</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentJobs as $job)
                                    <tr>
                                        <td>{{ $job->id }}</td>
                                        <td>
                                            @if($job->user)
                                                {{ $job->user->name }}
                                            @else
                                                {{ $job->email }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($job->status == 'completed')
                                                <span class="badge bg-success">Hoàn thành</span>
                                            @elseif($job->status == 'processing')
                                                <span class="badge bg-primary">Đang xử lý</span>
                                            @elseif($job->status == 'failed')
                                                <span class="badge bg-danger">Thất bại</span>
                                            @else
                                                <span class="badge bg-warning">Đang chờ</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $job->created_at->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="mb-0">Chưa có công việc nào</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation on scroll
        const animateElements = document.querySelectorAll('.animate-on-scroll');
        
        const animateOnScroll = () => {
            animateElements.forEach(element => {
                const elementPosition = element.getBoundingClientRect();
                const windowHeight = window.innerHeight;
                
                // Nếu phần tử đã hiển thị trong viewport
                if (elementPosition.top < windowHeight * 0.9) {
                    // Lấy độ trễ animation (nếu có)
                    const delay = element.dataset.animationDelay || 0;
                    
                    // Thêm class để kích hoạt animation sau độ trễ
                    setTimeout(() => {
                        element.classList.add('animated');
                    }, delay);
                }
            });
        };
        
        // Thực hiện animation khi scroll
        window.addEventListener('scroll', animateOnScroll);
        
        // Thực hiện animation ngay khi trang tải xong
        animateOnScroll();
        
        // Biểu đồ công việc
        const ctx = document.getElementById('jobsChart').getContext('2d');
        
        const dates = @json($dates);
        const jobCounts = @json($jobCounts);
        
        const jobsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Số công việc',
                    data: jobCounts,
                    backgroundColor: 'rgba(66, 133, 244, 0.6)',
                    borderColor: 'rgba(66, 133, 244, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Hover effect cho stats cards
        const statsCards = document.querySelectorAll('.stats-card');
        statsCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-5px)';
            });
        });
    });
</script>
@endpush