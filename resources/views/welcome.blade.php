@extends('layouts.app')

@section('title', 'Google Doc Copier | Sao chép tài liệu Google thông minh')

@section('meta_description', 'Sao chép nội dung từ Google Doc này sang Google Doc khác theo khoảng thời gian tùy chỉnh')

@section('body-class', 'welcome-page')

@push('styles')
<style>
    .welcome-page {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 100vh;
    }
    
    .app-logo {
        width: 80px;
        height: 80px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin: 0 auto 1.5rem;
    }
    
    .logo-icon {
        color: var(--primary);
        font-size: 40px;
    }
    
    .features {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .feature {
        flex: 1 1 250px;
        max-width: 300px;
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .feature:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    
    .feature-icon {
        background: var(--primary);
        color: white;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
    }
    
    .feature h3 {
        color: var(--dark);
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }
    
    .feature p {
        color: #666;
        font-size: 0.9rem;
    }
    
    .btn-google {
        background: white;
        color: var(--dark);
        border: 1px solid #ddd;
        border-radius: 30px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s ease;
        margin: 0 auto;
        max-width: 280px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .btn-google:hover {
        background: #f8f9fa;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .btn-google i {
        font-size: 1.25rem;
    }
    
    .btn-google .google-logo {
        display: flex;
    }
    
    .btn-google .google-logo span {
        display: inline-block;
        width: 18px;
        height: 18px;
    }
    
    .google-blue { background: #4285f4; }
    .google-red { background: #ea4335; }
    .google-yellow { background: #fbbc05; }
    .google-green { background: #34a853; }
    
    .btn-dashboard {
        background: var(--secondary);
        color: white;
        border-radius: 30px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s ease;
        margin: 1rem auto 0;
        max-width: 280px;
    }
    
    .btn-dashboard:hover {
        background: #2d8f49;
        color: white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <div class="app-logo animate-on-scroll" data-animation="fadeIn">
                    <i class="fas fa-copy logo-icon"></i>
                </div>
                <h1 class="text-center animate-on-scroll" data-animation="slideDown">Google Doc Copier</h1>
            </div>
            <div class="card-body">
                <p class="lead text-center animate-on-scroll" data-animation="fadeIn">
                    Sao chép nội dung giữa các Google Docs một cách thông minh với tốc độ tự điều chỉnh, 
                    giúp việc chuyển tài liệu của bạn trở nên đơn giản và hiệu quả.
                </p>
                
                @if (session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                    </div>
                @endif
                
                <div class="features">
                    <div class="feature animate-on-scroll" data-animation="fadeInUp">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>Tùy chỉnh tốc độ</h3>
                        <p>Tự điều chỉnh thời gian giữa các lần sao chép để phù hợp với nhu cầu của bạn</p>
                    </div>
                    <div class="feature animate-on-scroll delay-2" data-animation="fadeInUp">
                        <div class="feature-icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <h3>Quản lý thư mục</h3>
                        <p>Chọn hoặc tạo thư mục trong Google Drive để lưu trữ các tài liệu đã sao chép</p>
                    </div>
                    <div class="feature animate-on-scroll delay-3" data-animation="fadeInUp">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3>Theo dõi tiến độ</h3>
                        <p>Xem tiến độ sao chép và trạng thái của từng tài liệu trong thời gian thực</p>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    @if(auth()->guest())
                    <a href="{{ route('auth.google') }}" class="btn btn-google animate-on-scroll" data-animation="bounceIn">
                        <div class="google-logo d-flex align-items-center">
                            <span class="google-blue rounded-circle"></span>
                            <span class="google-red rounded-circle"></span>
                            <span class="google-yellow rounded-circle"></span>
                            <span class="google-green rounded-circle"></span>
                        </div>
                        Đăng nhập bằng Google
                    </a>
                    @else
                    <a href="{{ route('jobs.create') }}" class="btn btn-dashboard animate-on-scroll" data-animation="fadeInUp">
                        <i class="fas fa-plus-circle"></i> Tạo công việc mới
                    </a>

                    <a href="{{ route('jobs.index') }}" class="btn btn-dashboard animate-on-scroll delay-3" data-animation="fadeInUp">
                        <i class="fas fa-tachometer-alt"></i> Truy cập bảng điều khiển
                    </a>
                    @endif
                    
                   
                </div>
            </div>
        </div>
    </div>
</div>
@endsection