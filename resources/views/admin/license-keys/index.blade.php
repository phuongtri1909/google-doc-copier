@extends('layouts.admin')

@section('title', 'Quản lý License Keys | Admin - Google Doc Copier')

@push('breadcrumbs')
    <li class="breadcrumb-item active">Quản lý License Keys</li>
@endpush

@push('styles')
<style>
    .license-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
    }
    
    .license-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .license-key {
        font-family: monospace;
        font-weight: 500;
        word-break: break-all;
    }
    
    .table th {
        font-weight: 500;
    }
    
    .table td {
        vertical-align: middle;
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
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .user-initial {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 500;
        color: white;
        background-color: var(--primary);
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
    
    .copy-btn {
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .copy-btn:hover {
        color: var(--primary);
    }
    
    .tooltip-inner {
        max-width: 200px;
        padding: 6px 10px;
        color: #fff;
        text-align: center;
        background-color: #333;
        border-radius: 4px;
    }
    
    .batch-action-bar {
        display: none;
        background-color: var(--primary-light);
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 15px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-1">Quản lý License Keys</h1>
            <p class="text-muted">Quản lý tất cả license keys trong hệ thống</p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end align-items-center">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLicenseModal">
                <i class="fas fa-plus-circle me-1"></i> Tạo License Key mới
            </button>
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

    <!-- Batch Actions -->
    <div class="batch-action-bar animate-on-load" id="batchActionBar">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <span id="selectedCount" class="me-3">0 license keys được chọn</span>
            </div>
            <div class="d-flex gap-2">
                <button id="batchActivate" class="btn btn-sm btn-success">
                    <i class="fas fa-check-circle me-1"></i> Kích hoạt tất cả
                </button>
                <button id="batchDeactivate" class="btn btn-sm btn-warning">
                    <i class="fas fa-ban me-1"></i> Vô hiệu hóa tất cả
                </button>
                <button id="batchDelete" class="btn btn-sm btn-danger">
                    <i class="fas fa-trash-alt me-1"></i> Xóa tất cả
                </button>
                <button id="cancelBatch" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Hủy
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filter Card -->
    <div class="card filter-card mb-4 animate-on-load">
        <div class="card-body">
            <form action="{{ route('admin.license-keys.index') }}" method="GET" class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="position-relative">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" class="form-control filter-input" name="search" 
                               placeholder="Tìm kiếm license key..." value="{{ request('search') }}">
                    </div>
                </div>
                
                <div class="col-md-2 col-sm-6">
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Vô hiệu hóa</option>
                    </select>
                </div>
                
                <div class="col-md-2 col-sm-6">
                    <select class="form-select" name="assigned">
                        <option value="">Tất cả license</option>
                        <option value="assigned" {{ request('assigned') == 'assigned' ? 'selected' : '' }}>Đã gán cho người dùng</option>
                        <option value="unassigned" {{ request('assigned') == 'unassigned' ? 'selected' : '' }}>Chưa gán</option>
                    </select>
                </div>
                
                <div class="col-md-2 col-sm-6">
                    <select class="form-select" name="expiry">
                        <option value="">Tất cả thời hạn</option>
                        <option value="expired" {{ request('expiry') == 'expired' ? 'selected' : '' }}>Đã hết hạn</option>
                        <option value="expiring_soon" {{ request('expiry') == 'expiring_soon' ? 'selected' : '' }}>Sắp hết hạn</option>
                        <option value="unlimited" {{ request('expiry') == 'unlimited' ? 'selected' : '' }}>Không giới hạn</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Lọc
                        </button>
                        <a href="{{ route('admin.license-keys.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-sync me-1"></i> Đặt lại
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- License Keys Table -->
    <div class="card shadow-sm animate-on-load">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" width="30">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                            </th>
                            <th scope="col">License Key</th>
                            <th scope="col">Người dùng</th>
                            <th scope="col">Giới hạn</th>
                            <th scope="col">Ngày kích hoạt</th>
                            <th scope="col">Ngày hết hạn</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($licenseKeys as $license)
                            <tr>
                                <td>
                                    <input class="form-check-input license-checkbox" type="checkbox" value="{{ $license->id }}">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="license-key">{{ $license->key }}</span>
                                        <i class="fas fa-copy ms-2 copy-btn" data-clipboard-text="{{ $license->key }}" 
                                           data-bs-toggle="tooltip" data-bs-title="Sao chép"></i>
                                    </div>
                                </td>
                                <td>
                                    @if($license->user)
                                        <div class="d-flex align-items-center">
                                            @if($license->user->avatar)
                                                <img src="{{ $license->user->avatar }}" class="user-avatar me-2" alt="{{ $license->user->name }}">
                                            @else
                                                <div class="user-initial me-2">{{ substr($license->user->name, 0, 1) }}</div>
                                            @endif
                                            <div>
                                                <div>{{ $license->user->name }}</div>
                                                <small class="text-muted">{{ $license->user->email }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-secondary">Chưa được gán</span>
                                    @endif
                                </td>
                                <td>
                                    @if($license->max_documents)
                                        <span class="badge bg-info">{{ $license->max_documents }} tài liệu</span>
                                        @if($license->documents_used)
                                            <small class="d-block text-muted">Đã dùng: {{ $license->documents_used }}</small>
                                        @endif
                                    @else
                                        <span class="badge bg-success">Không giới hạn</span>
                                    @endif
                                </td>
                                <td>
                                    @if($license->activated_at)
                                        {{ $license->activated_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted">Chưa kích hoạt</span>
                                    @endif
                                </td>
                                <td>
                                    @if($license->expires_at)
                                        @if($license->expires_at->isPast())
                                            <span class="text-danger">{{ $license->expires_at->format('d/m/Y') }}</span>
                                            <small class="d-block">(Đã hết hạn)</small>
                                        @elseif($license->expires_at->diffInDays(now()) <= 7)
                                            <span class="text-warning">{{ $license->expires_at->format('d/m/Y') }}</span>
                                            <small class="d-block">(Còn {{ $license->expires_at->diffInDays(now()) }} ngày)</small>
                                        @else
                                            {{ $license->expires_at->format('d/m/Y') }}
                                        @endif
                                    @else
                                        <span class="badge bg-success">Không giới hạn</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.license-keys.toggle', $license) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-link p-0 border-0 bg-transparent">
                                            <span class="status-toggle {{ $license->is_active ? 'active' : '' }}"
                                                  title="{{ $license->is_active ? 'Đang kích hoạt' : 'Đã vô hiệu hóa' }}">
                                            </span>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary action-btn view-license" 
                                                data-id="{{ $license->id }}"
                                                data-key="{{ $license->key }}"
                                                data-status="{{ $license->is_active ? 'active' : 'inactive' }}"
                                                data-expires="{{ $license->expires_at ? $license->expires_at->format('Y-m-d') : '' }}"
                                                data-max-docs="{{ $license->max_documents }}"
                                                data-used-docs="{{ $license->documents_used }}"
                                                data-created="{{ $license->created_at->format('d/m/Y H:i') }}"
                                                data-activated="{{ $license->activated_at ? $license->activated_at->format('d/m/Y H:i') : '' }}"
                                                data-notes="{{ $license->notes }}"
                                                data-bs-toggle="modal" data-bs-target="#viewLicenseModal"
                                                title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info action-btn edit-license"
                                                data-id="{{ $license->id }}"
                                                data-expires="{{ $license->expires_at ? $license->expires_at->format('Y-m-d') : '' }}"
                                                data-max-docs="{{ $license->max_documents }}" 
                                                data-notes="{{ $license->notes }}"
                                                data-bs-toggle="modal" data-bs-target="#editLicenseModal"
                                                title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.license-keys.destroy', $license) }}" method="POST"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa license key này?');">
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
                                    <i class="fas fa-key text-muted mb-3" style="font-size: 3rem;"></i>
                                    <p class="mb-0">Không tìm thấy license key nào</p>
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
                        Hiển thị {{ $licenseKeys->firstItem() ?? 0 }} đến {{ $licenseKeys->lastItem() ?? 0 }} của {{ $licenseKeys->total() }} license keys
                    </small>
                </div>
                <div>
                    {{ $licenseKeys->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create License Modal -->
<div class="modal fade" id="createLicenseModal" tabindex="-1" aria-labelledby="createLicenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createLicenseModalLabel">Tạo License Key Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.license-keys.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Số lượng license key cần tạo</label>
                        <select class="form-select" name="quantity">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Ngày hết hạn</label>
                        <input type="date" class="form-control" id="expires_at" name="expires_at" 
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}">
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

<!-- View License Modal -->
<div class="modal fade" id="viewLicenseModal" tabindex="-1" aria-labelledby="viewLicenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewLicenseModalLabel">Chi tiết License Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4 text-center">
                    <div class="license-key h5" id="viewLicenseKey"></div>
                    <button class="btn btn-sm btn-outline-secondary copy-btn" id="copyViewLicenseKey">
                        <i class="fas fa-copy me-1"></i> Sao chép
                    </button>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Trạng thái</small>
                        <div id="viewLicenseStatus"></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Giới hạn tài liệu</small>
                        <div id="viewLicenseDocs"></div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Ngày tạo</small>
                        <div id="viewLicenseCreated"></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Ngày kích hoạt</small>
                        <div id="viewLicenseActivated"></div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <small class="text-muted d-block">Ngày hết hạn</small>
                        <div id="viewLicenseExpires"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Ghi chú</small>
                    <div id="viewLicenseNotes" class="p-2 bg-light rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit License Modal -->
<div class="modal fade" id="editLicenseModal" tabindex="-1" aria-labelledby="editLicenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLicenseModalLabel">Chỉnh sửa License Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editLicenseForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_expires_at" class="form-label">Ngày hết hạn</label>
                        <input type="date" class="form-control" id="edit_expires_at" name="expires_at" 
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        <small class="text-muted">Để trống nếu license không hết hạn</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_max_documents" class="form-label">Giới hạn số tài liệu</label>
                        <input type="number" class="form-control" id="edit_max_documents" name="max_documents" min="1">
                        <small class="text-muted">Để trống nếu không giới hạn số tài liệu</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
        
        // Initialize clipboard
        const clipboard = new ClipboardJS('.copy-btn');
        clipboard.on('success', function(e) {
            const tooltip = bootstrap.Tooltip.getInstance(e.trigger);
            if (tooltip) {
                tooltip.dispose();
                e.trigger.setAttribute('data-bs-title', 'Đã sao chép!');
                new bootstrap.Tooltip(e.trigger).show();
                setTimeout(() => {
                    e.trigger.setAttribute('data-bs-title', 'Sao chép');
                }, 1000);
            }
            e.clearSelection();
        });
        
        // Copy License Key from View Modal
        document.getElementById('copyViewLicenseKey').addEventListener('click', function() {
            const licenseKey = document.getElementById('viewLicenseKey').textContent;
            navigator.clipboard.writeText(licenseKey).then(() => {
                this.innerHTML = '<i class="fas fa-check me-1"></i> Đã sao chép';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy me-1"></i> Sao chép';
                }, 1000);
            });
        });
        
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
        
        // View License Modal
        const viewLicenseModal = document.getElementById('viewLicenseModal');
        viewLicenseModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const key = button.getAttribute('data-key');
            const status = button.getAttribute('data-status');
            const expires = button.getAttribute('data-expires');
            const maxDocs = button.getAttribute('data-max-docs');
            const usedDocs = button.getAttribute('data-used-docs');
            const created = button.getAttribute('data-created');
            const activated = button.getAttribute('data-activated');
            const notes = button.getAttribute('data-notes');
            
            document.getElementById('viewLicenseKey').textContent = key;
            
            // Status
            const statusEl = document.getElementById('viewLicenseStatus');
            if (status === 'active') {
                statusEl.innerHTML = '<span class="badge bg-success">Đang hoạt động</span>';
            } else {
                statusEl.innerHTML = '<span class="badge bg-secondary">Vô hiệu hóa</span>';
            }
            
            // Docs limit
            const docsEl = document.getElementById('viewLicenseDocs');
            if (maxDocs) {
                docsEl.textContent = `${usedDocs || 0}/${maxDocs} tài liệu`;
            } else {
                docsEl.textContent = 'Không giới hạn';
            }
            
            // Dates
            document.getElementById('viewLicenseCreated').textContent = created;
            document.getElementById('viewLicenseActivated').textContent = activated || 'Chưa kích hoạt';
            
            // Expiry
            const expiresEl = document.getElementById('viewLicenseExpires');
            if (expires) {
                const expiryDate = new Date(expires);
                const today = new Date();
                const diffTime = expiryDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays < 0) {
                    expiresEl.innerHTML = `<span class="text-danger">${new Date(expires).toLocaleDateString('vi-VN')} (Đã hết hạn)</span>`;
                } else if (diffDays <= 7) {
                    expiresEl.innerHTML = `<span class="text-warning">${new Date(expires).toLocaleDateString('vi-VN')} (Còn ${diffDays} ngày)</span>`;
                } else {
                    expiresEl.textContent = new Date(expires).toLocaleDateString('vi-VN');
                }
            } else {
                expiresEl.innerHTML = '<span class="badge bg-success">Không giới hạn</span>';
            }
            
            // Notes
            document.getElementById('viewLicenseNotes').textContent = notes || 'Không có ghi chú';
        });
        
        // Edit License Modal
        const editLicenseModal = document.getElementById('editLicenseModal');
        editLicenseModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const expires = button.getAttribute('data-expires');
            const maxDocs = button.getAttribute('data-max-docs');
            const notes = button.getAttribute('data-notes');
            
            document.getElementById('edit_expires_at').value = expires;
            document.getElementById('edit_max_documents').value = maxDocs;
            document.getElementById('edit_notes').value = notes;
            
            const form = document.getElementById('editLicenseForm');
            form.action = `/admin/license-keys/${id}`;
        });
        
        // Batch selection
        const selectAllCheckbox = document.getElementById('selectAll');
        const licenseCheckboxes = document.querySelectorAll('.license-checkbox');
        const batchActionBar = document.getElementById('batchActionBar');
        const selectedCountEl = document.getElementById('selectedCount');
        
        // Select all checkboxes
        selectAllCheckbox.addEventListener('change', function() {
            licenseCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBatchActionBar();
        });
        
        // Individual checkbox change
        licenseCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // If any checkbox is unchecked, deselect "Select All"
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                }
                // If all checkboxes are checked, select "Select All"
                else if (Array.from(licenseCheckboxes).every(cb => cb.checked)) {
                    selectAllCheckbox.checked = true;
                }
                updateBatchActionBar();
            });
        });
        
        function updateBatchActionBar() {
            const selectedCount = document.querySelectorAll('.license-checkbox:checked').length;
            selectedCountEl.textContent = `${selectedCount} license keys được chọn`;
            
            if (selectedCount > 0) {
                batchActionBar.style.display = 'block';
            } else {
                batchActionBar.style.display = 'none';
            }
        }
        
        // Cancel batch selection
        document.getElementById('cancelBatch').addEventListener('click', function() {
            licenseCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
            updateBatchActionBar();
        });
        
        // Batch actions
        document.getElementById('batchActivate').addEventListener('click', function() {
            executeBatchAction('activate', 'kích hoạt');
        });
        
        document.getElementById('batchDeactivate').addEventListener('click', function() {
            executeBatchAction('deactivate', 'vô hiệu hóa');
        });
        
        document.getElementById('batchDelete').addEventListener('click', function() {
            executeBatchAction('delete', 'xóa');
        });
        
        function executeBatchAction(action, actionText) {
            const selectedLicenses = Array.from(document.querySelectorAll('.license-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            if (selectedLicenses.length === 0) return;
            
            if (confirm(`Bạn có chắc muốn ${actionText} ${selectedLicenses.length} license keys đã chọn?`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/license-keys/batch-${action}`;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                form.appendChild(csrfInput);
                
                selectedLicenses.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'licenses[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    });
</script>
@endpush