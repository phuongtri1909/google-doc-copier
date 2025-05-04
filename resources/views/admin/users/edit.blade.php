<!-- filepath: d:\google-doc-copier\resources\views\admin\users\edit.blade.php -->
@extends('layouts.admin')

@section('title', "Chỉnh sửa {$user->name} | Admin - Google Doc Copier")

@push('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.users.index') }}">Quản lý người dùng</a>
    </li>
    <li class="breadcrumb-item active">Chỉnh sửa người dùng</li>
@endpush

@push('styles')
<style>
    .form-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }
    
    .form-label {
        font-weight: 500;
    }
    
    .required::after {
        content: '*';
        color: var(--danger);
        margin-left: 3px;
    }
    
    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem rgba(66, 133, 244, 0.15);
    }
    
    .user-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .user-initial {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        background-color: var(--primary);
        margin: 0 auto;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card form-card animate-on-load">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Chỉnh sửa người dùng</h5>
                </div>
                <div class="card-body">
                    <!-- User Avatar -->
                    <div class="text-center mb-4">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="user-avatar mb-3">
                        @else
                            <div class="user-initial mb-3">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif
                        <h5>{{ $user->email }}</h5>
                        @if($user->google_id)
                            <span class="badge bg-primary">
                                <i class="fab fa-google"></i> Google Account
                            </span>
                        @endif
                    </div>
                    
                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label required">Họ tên</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password">
                            <small class="text-muted">Để trống nếu không muốn thay đổi mật khẩu</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label required">Vai trò</label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="user" {{ (old('role', $user->role) == 'user') ? 'selected' : '' }}>Người dùng</option>
                                <option value="admin" {{ (old('role', $user->role) == 'admin') ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="is_active" 
                                       name="is_active" {{ $user->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Kích hoạt tài khoản
                                </label>
                            </div>
                            <small class="text-muted">Người dùng bị vô hiệu hóa sẽ không thể đăng nhập hoặc sử dụng dịch vụ</small>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Quay lại
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Cập nhật người dùng
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection