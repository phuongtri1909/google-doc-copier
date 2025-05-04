@extends('layouts.admin')

@section('title', 'Quản lý Người dùng | Admin - Google Doc Copier')

@push('breadcrumbs')
    <li class="breadcrumb-item active">Quản lý người dùng</li>
@endpush

@push('styles')
<style>
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .user-initial {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 500;
        color: white;
        background-color: var(--primary);
    }
    
    .table th {
        font-weight: 500;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .license-badge {
        font-size: 0.8rem;
        padding: 0.3em 0.6em;
        border-radius: 30px;
    }
    
    .status-toggle {
        cursor: pointer;
        width: 42px;
        height: 22px;
        background-color: #ccc;
        display: inline-block;
        border-radius: 11px;
        position: relative;
        transition: all 0.3s;
    }
    
    .status-toggle:after {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: white;
        top: 2px;
        left: 2px;
        transition: all 0.3s;
    }
    
    .status-toggle.active {
        background-color: var(--secondary);
    }
    
    .status-toggle.active:after {
        left: calc(100% - 20px);
    }
    
    .filter-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .filter-input {
        border-radius: 20px;
        padding-left: 2.5rem;
        background-color: #f8f9fa;
        border: 1px solid #f1f3f4;
    }
    
    .filter-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .pagination {
        margin-bottom: 0;
    }
    
    .page-link {
        color: var(--primary);
        border-radius: 4px;
        margin: 0 2px;
    }
    
    .page-item.active .page-link {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    
    .animate-on-load {
        animation: fadeInUp 0.5s ease;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-1">Quản lý Người dùng</h1>
            <p class="text-muted">Quản lý tất cả người dùng trong hệ thống</p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end align-items-center">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Thêm người dùng mới
            </a>
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
    
    <div class="card filter-card mb-4 animate-on-load">
        <div class="card-body">
            <form action="{{ route('admin.users.index') }}" method="GET" class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="position-relative">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" class="form-control filter-input" name="search" 
                               placeholder="Tìm theo tên hoặc email..." value="{{ request()->search }}">
                    </div>
                </div>
                
                <div class="col-md-2 col-sm-6">
                    <select class="form-select" name="role">
                        <option value="">Tất cả vai trò</option>
                        <option value="user" {{ request()->role == 'user' ? 'selected' : '' }}>Người dùng</option>
                        <option value="admin" {{ request()->role == 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                
                <div class="col-md-2 col-sm-6">
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" {{ request()->status == '1' ? 'selected' : '' }}>Kích hoạt</option>
                        <option value="0" {{ request()->status == '0' ? 'selected' : '' }}>Vô hiệu hóa</option>
                    </select>
                </div>
                
                <div class="col-md-2 col-sm-6">
                    <select class="form-select" name="license">
                        <option value="">Tất cả license</option>
                        <option value="active" {{ request()->license == 'active' ? 'selected' : '' }}>Có license đang hoạt động</option>
                        <option value="inactive" {{ request()->license == 'inactive' ? 'selected' : '' }}>Không có license</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Lọc
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-sync me-1"></i> Đặt lại
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm animate-on-load">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Người dùng</th>
                            <th scope="col">Email</th>
                            <th scope="col">Vai trò</th>
                            <th scope="col">License</th>
                            <th scope="col">Ngày đăng ký</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($user->avatar)
                                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="user-avatar me-3">
                                        @else
                                            <div class="user-initial me-3">{{ substr($user->name, 0, 1) }}</div>
                                        @endif
                                        <div>
                                            <h6 class="mb-0">{{ $user->name }}</h6>
                                            @if($user->google_id)
                                                <small class="text-muted">
                                                    <i class="fab fa-google text-danger"></i> Google
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->role == 'admin')
                                        <span class="badge bg-danger">Admin</span>
                                    @else
                                        <span class="badge bg-info">Người dùng</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->validLicenseKeys_count > 0)
                                        <span class="license-badge bg-success">
                                            <i class="fas fa-key me-1"></i> {{ $user->validLicenseKeys_count }} license
                                        </span>
                                    @else
                                        <span class="license-badge bg-secondary">
                                            <i class="fas fa-times-circle me-1"></i> Không có license
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST"
                                          onsubmit="return confirm('Bạn có chắc muốn {{ $user->is_active ? 'vô hiệu hóa' : 'kích hoạt' }} người dùng này?')">
                                        @csrf
                                        <button type="submit" class="btn btn-link p-0 border-0 bg-transparent">
                                            <span class="status-toggle {{ $user->is_active ? 'active' : '' }}"
                                                  title="{{ $user->is_active ? 'Đang kích hoạt' : 'Đã vô hiệu hóa' }}">
                                            </span>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary action-btn" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-info action-btn" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger action-btn" title="Xóa">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
                                    <p class="mb-0">Không tìm thấy người dùng nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">
                        Hiển thị {{ $users->firstItem() ?? 0 }} đến {{ $users->lastItem() ?? 0 }} của {{ $users->total() }} người dùng
                    </small>
                </div>
                <div>
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
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