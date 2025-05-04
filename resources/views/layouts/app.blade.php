<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Google Doc Copier - Sao chép nội dung Google Docs một cách thông minh')">
    <title>@yield('title', 'Google Doc Copier')</title>
    <link rel="icon" href="{{ asset('assets/img/favicon.ico') }}" type="image/x-icon">
    <!-- Bootstrap và Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- CSS Chung -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">


    
    @stack('styles')
</head>
<body class="@yield('body-class')">
    <!-- Background shapes -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    
    <!-- Navbar -->
    @if(Auth::check())
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <div class="nav-logo-icon">
                    <i class="fas fa-copy"></i>
                </div>
                <span class="ms-2">Google Doc Copier</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('jobs.create') ? 'active' : '' }}" href="{{ route('jobs.create') }}">
                            <i class="fas fa-plus-circle"></i> Tạo công việc
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('jobs.index') ? 'active' : '' }}" href="{{ route('jobs.index') }}">
                            <i class="fas fa-tasks"></i> Công việc của tôi
                        </a>
                    </li>
                    @if(Auth::user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-user-shield"></i> Quản trị
                        </a>
                    </li>
                    @endif
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-dropdown" href="#" role="button" data-bs-toggle="dropdown">
                            @if(Auth::user()->avatar)
                                <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}" class="avatar-img">
                            @else
                                <i class="fas fa-user-circle"></i>
                            @endif
                            <span class="ms-2">{{ Auth::user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end animated fadeIn">
                            <li>
                                <a class="dropdown-item" href="{{ route('license.verify') }}">
                                    <i class="fas fa-key"></i> License Key
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="dropdown-item-form">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @endif

    <div class="container py-4">
        @yield('content')
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="text-center">
                <p>&copy; {{ date('Y') }} Google Doc Copier | Mọi thông tin tài liệu được bảo mật tuyệt đối</p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    
    @stack('scripts')
</body>
</html>