<!-- filepath: d:\google-doc-copier\resources\views\admin\users\create.blade.php -->
@extends('layouts.admin')

@section('title', 'Thêm người dùng mới | Admin - Google Doc Copier')

@push('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.users.index') }}">Quản lý người dùng</a>
    </li>
    <li class="breadcrumb-item active">Thêm mới</li>
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
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card form-card animate-on-load">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Thêm người dùng mới</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label required">Họ tên</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label required">Mật khẩu</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label required">Xác nhận mật khẩu</label>
                            <input type="password" class="form-control" 
                                   id="password_confirmation" name="password_confirmation" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label required">Vai trò</label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>Người dùng</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="is_active" 
                                       name="is_active" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Kích hoạt tài khoản
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i> Quay lại
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Tạo người dùng
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection