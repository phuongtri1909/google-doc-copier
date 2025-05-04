<!-- filepath: d:\google-doc-copier\resources\views\admin\users\show.blade.php -->
@extends('layouts.admin')

@section('title', "{$user->name} | Chi tiết người dùng - Admin Google Doc Copier")

@push('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.users.index') }}">Quản lý người dùng</a>
    </li>
    <li class="breadcrumb-item active">Chi tiết người dùng</li>
@endpush

@push('styles')
<style>
    .user-info-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }
    
    .user-avatar-lg {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .user-info-list {
        list-style: none;
        padding-left: 0;
    }
    
    .user-info-list li {
        padding: 10px 0;
        border-bottom: 1px solid #f1f1f1;
        display: flex;
    }
    
    .user-info-list li:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 500;
        min-width: 120px;
    }
    
    .tab-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .nav-tabs {
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
    }
    
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 1rem 1.5rem;
        font-weight: 500;
        border-radius: 0;
    }
    
    .nav-tabs .nav-link.active {
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        background-color: transparent;
    }
    
    .license-card {
        border-radius: 8px;
        background-color: #f9f9f9;
        transition: all 0.3s ease;
    }
    
    .license-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .license-key {
        font-family: monospace;
        font-size: 1.1rem;
        word-break: break-all;
    }
    
    .stat-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.07);
    }
    
    .stat-icon {
        font-size: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-radius: 50%;
    }
    
    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-0">Chi tiết người dùng</h2>
            <p class="text-muted">Thông tin và quản lý cho {{ $user->name }}</p>
        </div>
        <div class="col-md-4 d-flex justify-content-md-end align-items-center gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary">
                <i class="fas fa-edit me-1"></i> Chỉnh sửa
            </a>
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">
                    <i class="fas fa-trash-alt me-1"></i> Xóa
                </button>
            </form>
        </div>
    </div>
    
    @if (session('success'))
        <div class="alert alert-success animate-on-load">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger animate-on-load">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- User Profile Card -->
            <div class="card user-info-card mb-4">
                <div class="card-body text-center py-4">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="user-avatar-lg mb-3">
                    @else
                        <div class="user-initial user-avatar-lg mb-3 mx-auto d-flex align-items-center justify-content-center">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    @endif
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <span class="badge {{ $user->role == 'admin' ? 'bg-danger' : 'bg-info' }} mb-3">
                        {{ $user->role == 'admin' ? 'Admin' : 'Người dùng' }}
                    </span>
                    <div class="mb-3">
                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                            <i class="fas {{ $user->is_active ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                            {{ $user->is_active ? 'Đang hoạt động' : 'Đã vô hiệu hóa' }}
                        </span>
                    </div>
                    <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                            <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check-circle' }} me-1"></i>
                            {{ $user->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                        </button>
                    </form>
                </div>
                <div class="card-body pt-0">
                    <ul class="user-info-list">
                        <li>
                            <span class="info-label">Email:</span>
                            <span>{{ $user->email }}</span>
                        </li>
                        <li>
                            <span class="info-label">Đăng nhập qua:</span>
                            <span>
                                @if($user->google_id)
                                    <i class="fab fa-google text-danger me-1"></i> Google
                                @else
                                    <i class="fas fa-key text-secondary me-1"></i> Email/Password
                                @endif
                            </span>
                        </li>
                        <li>
                            <span class="info-label">Ngày đăng ký:</span>
                            <span>{{ $user->created_at->format('d/m/Y H:i') }}</span>
                        </li>
                        <li>
                            <span class="info-label">Cập nhật lần cuối:</span>
                            <span>{{ $user->updated_at->format('d/m/Y H:i') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Job Stats Card -->
            <div class="row">
                <div class="col-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-primary-light text-primary me-3">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <div class="stat-number mb-0">{{ $jobStats['total'] }}</div>
                                <div class="text-muted">Tổng công việc</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-success-light text-success me-3">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="stat-number mb-0">{{ $jobStats['completed'] }}</div>
                                <div class="text-muted">Hoàn thành</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-warning-light text-warning me-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="stat-number mb-0">{{ $jobStats['pending'] + $jobStats['processing'] }}</div>
                                <div class="text-muted">Đang xử lý</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-danger-light text-danger me-3">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div>
                                <div class="stat-number mb-0">{{ $jobStats['failed'] }}</div>
                                <div class="text-muted">Thất bại</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Tabs Panel -->
            <div class="card tab-card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs" id="userDetailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="licenses-tab" data-bs-toggle="tab" data-bs-target="#licenses-tab-pane" type="button" role="tab" aria-controls="licenses-tab-pane" aria-selected="true">
                                <i class="fas fa-key me-1"></i> License Keys
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs-tab-pane" type="button" role="tab" aria-controls="jobs-tab-pane" aria-selected="false">
                                <i class="fas fa-tasks me-1"></i> Công việc
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="userDetailTabsContent">
                        <!-- Licenses Tab -->
                        <div class="tab-pane fade show active" id="licenses-tab-pane" role="tabpanel" aria-labelledby="licenses-tab" tabindex="0">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">License Keys</h5>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLicenseModal">
                                    <i class="fas fa-plus-circle me-1"></i> Thêm License
                                </button>
                            </div>
                            
                            @if (session('createdKey'))
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i> License key mới đã được tạo: 
                                    <strong class="ms-1">{{ session('createdKey') }}</strong>
                                </div>
                            @endif
                            
                            @if(count($licenseKeys) > 0)
                                @foreach($licenseKeys as $index => $license)
                                    <div class="license-card p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="license-key mb-0">{{ $license->key }}</h6>
                                            <span class="badge {{ $license->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $license->is_active ? 'Đang hoạt động' : 'Đã vô hiệu hóa' }}
                                            </span>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">Ngày kích hoạt:</small>
                                                <p>{{ $license->activated_at ? $license->activated_at->format('d/m/Y H:i') : 'Chưa kích hoạt' }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">Ngày hết hạn:</small>
                                                <p>{{ $license->expires_at ? $license->expires_at->format('d/m/Y') : 'Không hết hạn' }}</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">Giới hạn tài liệu:</small>
                                                <p>{{ $license->max_documents ? "{$license->documents_used}/{$license->max_documents} tài liệu" : 'Không giới hạn' }}</p>
                                            </div>
                                            <div class="col-md-6 d-flex justify-content-end align-items-end">
                                                <form action="{{ route('admin.users.remove-license', ['user' => $user->id, 'licenseKey' => $license->id]) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn gỡ license này khỏi người dùng?');">
                                                        <i class="fas fa-unlink me-1"></i> Gỡ license
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-key text-muted mb-3" style="font-size: 3rem; opacity: 0.2;"></i>
                                    <p>Người dùng này chưa có license key nào. Hãy thêm license để người dùng có thể sử dụng dịch vụ.</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Jobs Tab -->
                        <div class="tab-pane fade" id="jobs-tab-pane" role="tabpanel" aria-labelledby="jobs-tab" tabindex="0">
                            <h5 class="mb-3">Công việc gần đây</h5>
                            
                            @if(count($jobs) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Trạng thái</th>
                                                <th>Tài liệu nguồn</th>
                                                <th>Tiến độ</th>
                                                <th>Thời gian</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($jobs as $job)
                                                <tr>
                                                    <td>{{ $job->id }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ 
                                                            $job->status == 'completed' ? 'success' : 
                                                            ($job->status == 'processing' ? 'primary' : 
                                                            ($job->status == 'failed' ? 'danger' : 'warning')) 
                                                        }}">
                                                            {{ ucfirst($job->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="https://docs.google.com/document/d/{{ $job->source_doc_id }}/edit" target="_blank" class="text-truncate d-block" style="max-width: 150px;">
                                                            {{ $job->source_doc_id }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $progress = $job->total_sentences > 0 
                                                                ? round(($job->current_position / $job->total_sentences) * 100) 
                                                                : 0;
                                                        @endphp
                                                        <div class="progress" style="height: 6px; width: 100px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress }}%" 
                                                                aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <small>{{ $progress }}%</small>
                                                    </td>
                                                    <td>
                                                        <small>{{ $job->created_at->format('d/m/Y H:i') }}</small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-clipboard-list text-muted mb-3" style="font-size: 3rem; opacity: 0.2;"></i>
                                    <p>Người dùng này chưa tạo công việc nào.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add License Modal -->
<div class="modal fade" id="addLicenseModal" tabindex="-1" aria-labelledby="addLicenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLicenseModalLabel">Tạo License Key Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.generate-license', $user) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Ngày hết hạn</label>
                        <input type="date" class="form-control" id="expires_at" name="expires_at" 
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                        <small class="text-muted">Để trống nếu license không hết hạn</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="max_documents" class="form-label">Giới hạn số tài liệu</label>
                        <input type="number" class="form-control" id="max_documents" name="max_documents" min="1">
                        <small class="text-muted">Để trống nếu không giới hạn số tài liệu</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Tạo License Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 5 seconds (except for the created key alert)
        const alerts = document.querySelectorAll('.alert:not(.alert:has(strong))');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
</script>
@endpush