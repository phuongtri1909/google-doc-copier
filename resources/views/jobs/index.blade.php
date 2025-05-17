@extends('layouts.app')

@section('title', 'Quản Lý Công Việc Sao Chép | Google Doc Copier')

@push('styles')
    <style>
        .status-badge {
            padding: 0.35em 0.65em;
            border-radius: 50px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
        }

        .job-card {
            transition: all 0.3s ease;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0.75rem;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .job-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .job-header {
            padding: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .job-body {
            padding: 0.75rem;
        }

        .job-id {
            font-weight: bold;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .job-date {
            font-size: 0.75rem;
            color: #888;
        }

        .job-footer {
            padding: 0.75rem;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
        }

        .doc-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary);
            text-decoration: none;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .doc-title {
            font-weight: 500;
            margin-bottom: 3px;
            font-size: 0.9rem;
        }

        .doc-id {
            color: #888;
            font-size: 0.75rem;
        }

        .doc-link:hover {
            text-decoration: underline;
        }

        .doc-container {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .progress {
            height: 8px;
            border-radius: 8px;
        }

        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background-color: #f8f9fa;
            border-radius: 12px;
        }

        .empty-icon {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1.5rem;
        }

        .auto-refresh-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 8px 12px;
            border-radius: 50px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
        }

        .auto-refresh-indicator .spinner {
            animation: spin 1.5s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .refresh-pause-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50px;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 5px;
        }

        .refresh-pause-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 767px) {
            .job-card {
                margin-bottom: 1rem;
            }
        }

        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.25rem;
        }

        .page-link {
            position: relative;
            display: block;
            padding: 0.5rem 0.75rem;
            margin-left: -1px;
            line-height: 1.25;
            color: #007bff;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            cursor: auto;
            background-color: #fff;
            border-color: #dee2e6;
        }
    </style>
@endpush

@section('content')
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="mb-0">Quản Lý Công Việc</h2>
            <p class="text-muted">Theo dõi và quản lý các công việc sao chép tài liệu</p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end align-items-center">
            <a href="{{ route('jobs.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Tạo Công Việc Mới
            </a>
        </div>
    </div>

    <!-- Thêm form filter vào phía trên danh sách jobs -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('jobs.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="all">Tất cả</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Đang chờ</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Đang xử lý
                        </option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành
                        </option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('jobs.index') }}" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
        </div>
    @endif

    @if (count($jobs) > 0)
        <div class="row" id="jobs-container">
            @foreach ($jobs as $index => $job)
                <div class="col-md-6 animate-on-scroll" data-animation="fadeIn"
                    style="animation-delay: {{ $index * 0.1 }}s">
                    <div class="card job-card" id="job-card-{{ $job->id }}" data-job-id="{{ $job->id }}">
                        <div class="job-header">
                            <div>
                                <span class="job-id">#{{ $job->id }}</span>
                                <span
                                    class="badge status-badge bg-{{ $job->status == 'completed'
                                        ? 'success'
                                        : ($job->status == 'processing'
                                            ? 'primary'
                                            : ($job->status == 'failed'
                                                ? 'danger'
                                                : 'warning')) }} ms-2"
                                    id="job-status-badge-{{ $job->id }}">
                                    <i
                                        class="fas fa-{{ $job->status == 'completed'
                                            ? 'check-circle'
                                            : ($job->status == 'processing'
                                                ? 'sync-alt fa-spin'
                                                : ($job->status == 'failed'
                                                    ? 'exclamation-circle'
                                                    : 'clock')) }}"></i>
                                    <span id="job-status-text-{{ $job->id }}">
                                        @if ($job->status == 'completed')
                                            Hoàn thành
                                        @elseif($job->status == 'processing')
                                            Đang xử lý
                                        @elseif($job->status == 'failed')
                                            Thất bại
                                        @elseif($job->status == 'pending')
                                            Đang chờ
                                        @else
                                            {{ ucfirst($job->status) }}
                                        @endif
                                    </span>
                                </span>
                            </div>
                            <div class="job-date">
                                <i class="far fa-calendar-alt me-1"></i> {{ $job->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="job-body">
                            <div class="doc-container">
                                <p class="mb-1 doc-title">
                                    <i class="fas fa-file-alt"></i>
                                    {{ $job->source_title ?? 'Tài liệu nguồn' }}
                                </p>
                                <a href="https://docs.google.com/document/d/{{ $job->source_doc_id }}/edit" target="_blank"
                                    class="doc-link">
                                    <span class="doc-id">{{ $job->source_doc_id }}</span>
                                </a>
                            </div>

                            {{-- <div class="doc-container">
                                <p class="mb-1 doc-title">
                                    <i class="fas fa-file-alt"></i>
                                    {{ $job->destination_title ?? 'Tài liệu đích' }}
                                </p>
                                <span id="dest-doc-container-{{ $job->id }}">
                                    @if ($job->folder_id)
                                        <a href="https://drive.google.com/drive/folders/{{ $job->folder_id }}"
                                            target="_blank" class="doc-link">
                                            <i class="fas fa-folder"></i>
                                            <span class="doc-id">Mở thư mục chứa tài liệu</span>
                                        </a>
                                    @else
                                        <span class="text-muted"><i class="fas fa-times-circle"></i> Chưa được tạo</span>
                                    @endif
                                </span>
                            </div> --}}

                            <div class="doc-container">
                                <p class="mb-1 doc-title">
                                    <i class="fas fa-folder"></i>
                                    {{ $job->destination_title ?? 'Thư mục đích' }}
                                </p>
                                <span id="dest-doc-container-{{ $job->id }}">
                                    {{-- {{ dd($job->document_folder_id) }} --}}
                                    @if (!empty($job->document_folder_id))
                                        <a href="https://drive.google.com/drive/folders/{{ $job->document_folder_id }}"
                                            target="_blank" class="doc-link">
                                            <i class="fas fa-folder"></i>
                                            <span class="doc-id">Mở thư mục tài liệu</span>
                                        </a>
                                    @else
                                        <span class="text-muted"><i class="fas fa-times-circle"></i> Chưa được tạo</span>
                                    @endif
                                </span>
                            </div>

                            <div class="mb-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small"><strong>Tiến độ:</strong></span>
                                    @php
                                        $progress =
                                            $job->total_sentences > 0
                                                ? round(($job->current_position / $job->total_sentences) * 100)
                                                : 0;
                                    @endphp
                                    <span id="progress-percent-{{ $job->id }}"
                                        class="small">{{ $progress }}%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped {{ $job->status == 'processing' ? 'progress-bar-animated' : '' }}"
                                        id="progress-bar-{{ $job->id }}" role="progressbar"
                                        style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}"
                                        aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small id="progress-count-{{ $job->id }}"
                                        style="font-size: 0.75rem;">{{ $job->current_position }} /
                                        {{ $job->total_sentences }}</small>
                                    <small style="font-size: 0.75rem;">Khoảng thời gian: <span
                                            id="interval-seconds-{{ $job->id }}">{{ $job->interval_seconds }}</span>s</small>
                                </div>
                            </div>
                        </div>
                        <div class="job-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <form action="{{ route('jobs.destroy', $job->id) }}" method="POST"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa công việc này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger action-btn">
                                        <i class="fas fa-trash-alt me-1"></i> Xóa
                                    </button>
                                </form>

                                <div class="d-flex gap-2" id="job-actions-{{ $job->id }}">
                                    @if ($job->destination_doc_id)
                                        <a href="https://docs.google.com/document/d/{{ $job->destination_doc_id }}/edit"
                                            target="_blank" class="btn btn-sm btn-outline-primary action-btn"
                                            id="view-doc-btn-{{ $job->id }}">
                                            <i class="fas fa-external-link-alt me-1"></i> Xem
                                        </a>
                                    @endif

                                    @if ($job->status != 'completed' && $job->status != 'failed')
                                        <form action="{{ route('jobs.process', $job->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary action-btn">
                                                <i class="fas fa-play me-1"></i> Xử Lý Ngay
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Live update indicator -->
        <div id="auto-refresh-indicator" class="auto-refresh-indicator" style="display: none;">
            <i class="fas fa-sync-alt spinner"></i>
            <span>Đang cập nhật tiến độ...</span>
            <button class="refresh-pause-btn" id="toggle-refresh-btn" title="Tạm dừng cập nhật">
                <i class="fas fa-pause" id="refresh-btn-icon"></i>
            </button>
        </div>
    @else
        <div class="empty-state animate-on-scroll" data-animation="fadeIn">
            <div class="empty-icon">
                <i class="far fa-clipboard"></i>
            </div>
            <h4>Chưa có công việc nào</h4>
            <p class="text-muted">Bạn chưa tạo bất kỳ công việc sao chép nào. Hãy bắt đầu tạo công việc mới.</p>
            <a href="{{ route('jobs.create') }}" class="btn btn-primary mt-3">
                <i class="fas fa-plus-circle me-1"></i> Tạo Công Việc Đầu Tiên
            </a>
        </div>
    @endif

    <!-- Sau danh sách jobs, thêm phân trang -->
    @if ($jobs->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $jobs->appends(request()->query())->links('components.paginate') }}
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hiệu ứng khi hover job cards
            const jobCards = document.querySelectorAll('.job-card');
            jobCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.15)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                });
            });

            // Animation khi scroll
            const animateOnScroll = () => {
                const elements = document.querySelectorAll('.animate-on-scroll');

                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect();
                    const viewportHeight = window.innerHeight;

                    // Kiểm tra nếu phần tử đã hiển thị trong viewport
                    if (elementPosition.top < viewportHeight * 0.9) {
                        const animation = element.dataset.animation;
                        element.classList.add(animation);
                        element.style.visibility = 'visible';
                        element.style.opacity = '1';
                    }
                });
            };

            // Thêm animation CSS cho các hiệu ứng
            const style = document.createElement('style');
            style.textContent = `
            .animate-on-scroll {
                visibility: hidden;
                opacity: 0;
                transition: all 0.5s ease;
            }
            
            .fadeIn {
                animation: fadeIn 0.5s ease forwards;
            }
            
            .fadeInUp {
                animation: fadeInUp 0.5s ease forwards;
            }
            
            .fadeInDown {
                animation: fadeInDown 0.5s ease forwards;
            }
            
            .zoomIn {
                animation: zoomIn 0.5s ease forwards;
            }
            
            @keyframes fadeIn {
                0% { opacity: 0; }
                100% { opacity: 1; }
            }
            
            @keyframes fadeInUp {
                0% { opacity: 0; transform: translateY(30px); }
                100% { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes fadeInDown {
                0% { opacity: 0; transform: translateY(-30px); }
                100% { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes zoomIn {
                0% { opacity: 0; transform: scale(0.9); }
                100% { opacity: 1; transform: scale(1); }
            }
            
            .progress-bar-animated {
                animation: progressAnimation 1s linear infinite;
            }
            
            @keyframes progressAnimation {
                0% { background-position: 1rem 0; }
                100% { background-position: 0 0; }
            }
        `;
            document.head.appendChild(style);

            // Hiệu ứng alert auto-hide
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

            // Thực hiện animation khi scroll
            window.addEventListener('scroll', animateOnScroll);

            // Thực hiện animation ngay khi trang tải xong
            animateOnScroll();

            // Hiệu ứng ripple khi click vào các nút
            const buttons = document.querySelectorAll('button, .btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const rect = button.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    const ripple = document.createElement('span');
                    ripple.className = 'ripple-effect';
                    ripple.style.cssText = `
                    position: absolute;
                    background: rgba(255, 255, 255, 0.7);
                    border-radius: 50%;
                    pointer-events: none;
                    width: 100px;
                    height: 100px;
                    top: ${y - 50}px;
                    left: ${x - 50}px;
                    transform: scale(0);
                    opacity: 1;
                    animation: ripple 0.6s linear;
                `;

                    this.style.position = this.style.position || 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Thêm animation cho ripple effect
            const rippleStyle = document.createElement('style');
            rippleStyle.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(3);
                    opacity: 0;
                }
            }
        `;
            document.head.appendChild(rippleStyle);

            // === LIVE PROGRESS UPDATES ===
            let refreshInterval;
            let refreshPaused = false;
            const refreshIndicator = document.getElementById('auto-refresh-indicator');
            const toggleRefreshBtn = document.getElementById('toggle-refresh-btn');
            const refreshBtnIcon = document.getElementById('refresh-btn-icon');

            // Tìm các công việc đang xử lý
            const processingJobs = Array.from(document.querySelectorAll('.job-card')).filter(card => {
                const jobId = card.getAttribute('data-job-id');
                const statusBadge = document.getElementById(`job-status-badge-${jobId}`);
                return statusBadge && statusBadge.classList.contains('bg-primary');
            });

            // Nếu có job đang xử lý, bắt đầu cập nhật
            if (processingJobs.length > 0 || document.querySelector('[id^="job-status-badge-"].bg-warning')) {
                startLiveUpdates();
                if (refreshIndicator) refreshIndicator.style.display = 'flex';
            }

            // Nút tạm dừng/tiếp tục cập nhật
            if (toggleRefreshBtn) {
                toggleRefreshBtn.addEventListener('click', function() {
                    if (refreshPaused) {
                        // Tiếp tục cập nhật
                        startLiveUpdates();
                        refreshPaused = false;
                        refreshBtnIcon.className = 'fas fa-pause';
                        toggleRefreshBtn.title = 'Tạm dừng cập nhật';
                    } else {
                        // Tạm dừng cập nhật
                        clearInterval(refreshInterval);
                        refreshPaused = true;
                        refreshBtnIcon.className = 'fas fa-play';
                        toggleRefreshBtn.title = 'Tiếp tục cập nhật';
                    }
                });
            }

            function startLiveUpdates() {
                // Xóa interval cũ nếu có
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }

                // Cập nhật ngay lập tức
                updateJobsProgress();

                // Sau đó cập nhật định kỳ mỗi 10 giây
                refreshInterval = setInterval(updateJobsProgress, 10000);
            }

            function updateJobsProgress() {
                // Lấy danh sách job 
                const jobCards = document.querySelectorAll('.job-card');
                const jobIds = Array.from(jobCards).map(card => card.getAttribute('data-job-id')).filter(id => id);

                if (jobIds.length === 0) return;

                // Gửi AJAX request để lấy cập nhật
                fetch('/jobs/progress?ids=' + jobIds.join(','))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {

                        let hasActiveJobs = false;

                        // Kiểm tra xem data.jobs có tồn tại không
                        if (!data.jobs) {
                            console.error('Invalid data format: missing jobs object');
                            return;
                        }

                        // Cập nhật thông tin cho từng job
                        Object.keys(data.jobs).forEach(jobId => {
                            const jobInfo = data.jobs[jobId];
                            if (!jobInfo) return;


                            const jobCard = document.getElementById(`job-card-${jobId}`);
                            if (!jobCard) {
                                console.warn(`Job card #${jobId} not found in DOM`);
                                return;
                            }

                            // Cập nhật trạng thái
                            const statusBadge = document.getElementById(`job-status-badge-${jobId}`);

                            if (statusBadge) {
                                let newClass, newIcon, newText;

                                // Xóa class cũ
                                statusBadge.classList.remove('bg-success', 'bg-primary', 'bg-danger',
                                    'bg-warning');

                                // Xác định class, icon, và text mới
                                switch (jobInfo.status) {
                                    case 'completed':
                                        newClass = 'bg-success';
                                        newIcon = 'fas fa-check-circle';
                                        newText = 'Hoàn thành';
                                        break;
                                    case 'processing':
                                        newClass = 'bg-primary';
                                        newIcon = 'fas fa-sync-alt fa-spin';
                                        newText = 'Đang xử lý';
                                        hasActiveJobs = true;
                                        break;
                                    case 'failed':
                                        newClass = 'bg-danger';
                                        newIcon = 'fas fa-exclamation-circle';
                                        newText = 'Thất bại';
                                        break;
                                    case 'pending':
                                    case 'pending_retry':
                                        newClass = 'bg-warning';
                                        newIcon = 'fas fa-clock';
                                        newText = 'Đang chờ';
                                        hasActiveJobs = true; // Công việc đang chờ cũng cần cập nhật
                                        break;
                                    default:
                                        newClass = 'bg-warning';
                                        newIcon = 'fas fa-clock';
                                        newText = jobInfo.status || 'Không xác định';
                                }

                                // Áp dụng các thay đổi
                                statusBadge.classList.add(newClass);
                                statusBadge.innerHTML =
                                    `<i class="${newIcon}"></i> <span id="job-status-text-${jobId}">${newText}</span>`;
                            }

                            // Cập nhật tiến độ nếu total_sentences > 0
                            if (jobInfo.total_sentences > 0) {
                                const progress = Math.round((jobInfo.current_position / jobInfo
                                    .total_sentences) * 100);

                                // Cập nhật thanh tiến độ
                                const progressBar = document.getElementById(`progress-bar-${jobId}`);
                                if (progressBar) {
                                    progressBar.style.width = `${progress}%`;
                                    progressBar.setAttribute('aria-valuenow', progress);

                                    if (jobInfo.status === 'processing') {
                                        progressBar.classList.add('progress-bar-animated');
                                    } else {
                                        progressBar.classList.remove('progress-bar-animated');
                                    }
                                }

                                // Cập nhật phần trăm
                                const progressPercent = document.getElementById(
                                    `progress-percent-${jobId}`);
                                if (progressPercent) {
                                    progressPercent.textContent = `${progress}%`;
                                }

                                // Cập nhật số lượng
                                const progressCount = document.getElementById(
                                    `progress-count-${jobId}`);
                                if (progressCount) {
                                    progressCount.textContent =
                                        `${jobInfo.current_position} / ${jobInfo.total_sentences}`;
                                }
                            }

                            // Cập nhật thời gian interval
                            const intervalSeconds = document.getElementById(
                                `interval-seconds-${jobId}`);
                            if (intervalSeconds && jobInfo.interval_seconds) {
                                intervalSeconds.textContent = jobInfo.interval_seconds;
                            }

                            // Cập nhật destination document folder nếu có
                            if (jobInfo.document_folder_id) {
                                const destDocContainer = document.getElementById(
                                    `dest-doc-container-${jobId}`);
                                if (destDocContainer) {
                                    const folderUrl =
                                        `https://drive.google.com/drive/folders/${jobInfo.document_folder_id}`;
                                    const currentLink = destDocContainer.querySelector('a.doc-link');

                                    // Chỉ cập nhật nếu chưa có link hoặc link thay đổi
                                    if (!currentLink || currentLink.getAttribute('href') !==
                                        folderUrl) {
                                        destDocContainer.innerHTML = `
                                            <a href="${folderUrl}" target="_blank" class="doc-link">
                                                <i class="fas fa-folder"></i>
                                                <span class="doc-id">Mở thư mục tài liệu</span>
                                            </a>
                                        `;
                                    }
                                }

                                // Cập nhật nút xem
                                const jobActions = document.getElementById(`job-actions-${jobId}`);
                                const viewBtn = document.getElementById(`view-doc-btn-${jobId}`);

                                if (jobActions && !viewBtn) {
                                    const folderUrl =
                                        `https://drive.google.com/drive/folders/${jobInfo.document_folder_id}`;
                                    const viewBtn = document.createElement('a');
                                    viewBtn.href = folderUrl;
                                    viewBtn.target = '_blank';
                                    viewBtn.id = `view-doc-btn-${jobId}`;
                                    viewBtn.className = 'btn btn-sm btn-outline-primary action-btn';
                                    viewBtn.innerHTML =
                                        '<i class="fas fa-folder-open me-1"></i> Mở thư mục';

                                    if (jobActions.firstChild) {
                                        jobActions.insertBefore(viewBtn, jobActions.firstChild);
                                    } else {
                                        jobActions.appendChild(viewBtn);
                                    }
                                }
                            }

                            // Update source document title if available
                            if (jobInfo.source_title) {
                                const sourceTitleElem = document.querySelector(
                                    `#job-card-${jobId} .doc-container:first-child .doc-title`);
                                if (sourceTitleElem) {
                                    sourceTitleElem.innerHTML =
                                        `<i class="fas fa-file-alt"></i> ${jobInfo.source_title}`;
                                }
                            }

                            // Cập nhật nút action dựa trên trạng thái
                            const jobActions = document.getElementById(`job-actions-${jobId}`);
                            if (jobActions) {
                                // Tìm form xử lý
                                const processForm = jobActions.querySelector(
                                    `form[action*="${jobId}"]`);

                                if (processForm) {
                                    // Hiển thị/ẩn nút xử lý dựa trên trạng thái
                                    if (jobInfo.status === 'completed' || jobInfo.status === 'failed') {
                                        processForm.style.display = 'none';
                                    } else {
                                        processForm.style.display = 'inline';
                                    }
                                }
                            }
                        });

                        // Ẩn indicator nếu không còn job đang chạy
                        if (!hasActiveJobs && refreshIndicator) {
                            clearInterval(refreshInterval);
                            refreshIndicator.style.display = 'none';
                        } else if (hasActiveJobs && refreshIndicator) {
                            refreshIndicator.style.display = 'flex';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching job progress:', error);
                    });
            }
        });
    </script>
@endpush
