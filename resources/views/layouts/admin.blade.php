<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Admin - Google Doc Copier')</title>
    <link rel="icon" href="{{ asset('assets/img/favicon.ico') }}" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4285F4;
            --primary-dark: #3367d6;
            --primary-light: #e8f0fe;
            --secondary: #34A853;
            --secondary-light: #e6f4ea;
            --warning: #FBBC05;
            --warning-light: #fef7e0;
            --danger: #EA4335;
            --danger-light: #fce8e6;
            --info: #5f6368;
            --info-light: #f1f3f4;
            --light: #f8f9fa;
            --dark: #202124;
            --shadow: rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            overflow-x: hidden;
        }
        
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 999;
            transition: all 0.3s ease;
        }
        
        .sidebar-collapsed {
            width: 70px;
        }
        
        .sidebar-logo {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .sidebar-logo img {
            width: 40px;
            height: 40px;
        }
        
        .sidebar-logo-text {
            margin-left: 10px;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
            white-space: nowrap;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-collapsed .sidebar-logo-text {
            opacity: 0;
            width: 0;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .nav-link {
            padding: 0.75rem 1.5rem;
            color: var(--info);
            display: flex;
            align-items: center;
            border-radius: 0;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover {
            background-color: var(--light);
            color: var(--primary);
        }
        
        .nav-link.active {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 500;
        }
        
        .nav-icon {
            font-size: 1.25rem;
            width: 30px;
            text-align: center;
        }
        
        .nav-text {
            margin-left: 10px;
            white-space: nowrap;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-collapsed .nav-text {
            opacity: 0;
            width: 0;
        }
        
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.5rem;
        }
        
        .navbar-toggler {
            border: none;
            padding: 0;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .dropdown-menu {
            min-width: 14rem;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        
        .dropdown-item {
            padding: 0.75rem 1rem;
        }
        
        .main-content {
            margin-left: 280px;
            transition: all 0.3s ease;
        }
        
        .main-content-expanded {
            margin-left: 70px;
        }
        
        .bg-primary-light { background-color: var(--primary-light); }
        .bg-success-light { background-color: var(--secondary-light); }
        .bg-warning-light { background-color: var(--warning-light); }
        .bg-danger-light { background-color: var(--danger-light); }
        .bg-info-light { background-color: var(--info-light); }
        
        .text-primary { color: var(--primary) !important; }
        .text-success { color: var(--secondary) !important; }
        .text-warning { color: var(--warning) !important; }
        .text-danger { color: var(--danger) !important; }
        .text-info { color: var(--info) !important; }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        /* Tailwind-style utilities */
        .rounded-lg { border-radius: 0.5rem; }
        .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .shadow { box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); }
        .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" onerror="this.src='https://via.placeholder.com/40?text=GDC'">
            <span class="sidebar-logo-text">Admin Panel</span>
        </div>
        
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-users"></i></span>
                        <span class="nav-text">Quản lý người dùng</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="{{ route('admin.license-keys.index') }}" class="nav-link {{ request()->routeIs('admin.license-keys.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-key"></i></span>
                        <span class="nav-text">Quản lý License</span>
                    </a>
                </li>
                
                <li class="nav-item mt-4">
                    <a href="{{ route('jobs.index') }}" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-home"></i></span>
                        <span class="nav-text">Về trang chính</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-content" id="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg sticky-top">
            <div class="container-fluid">
                <button class="navbar-toggler me-2" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    </li>
                    @stack('breadcrumbs')
                </ol>
                
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <a class="dropdown-toggle d-flex align-items-center" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            @if(Auth::user()->avatar)
                                <img src="{{ Auth::user()->avatar }}" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                            @else
                                <div class="rounded-circle me-2 bg-primary d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px;">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                            @endif
                            <span>{{ Auth::user()->name }}</span>
                        </a>
                        
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ route('jobs.index') }}">
                                    <i class="fas fa-home me-2"></i> Trang chủ
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('sidebar-collapsed');
                    mainContent.classList.toggle('main-content-expanded');
                }
            });
            
            // Hide sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 992) {
                    if (!sidebar.contains(event.target) && 
                        !sidebarToggle.contains(event.target) && 
                        sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                    }
                }
            });
            
            // Respond to window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove('show');
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>