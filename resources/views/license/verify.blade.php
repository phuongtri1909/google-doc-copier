@extends('layouts.app')

@section('title', 'Kích hoạt License Key | Google Doc Copier')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Kích hoạt License Key</h3>
            </div>
            <div class="card-body">
                @if (session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i> {{ session('warning') }}
                    </div>
                @endif

                @if(count($activeKeys) > 0)
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body bg-light rounded">
                            <h5 class="card-title d-flex align-items-center mb-3">
                                <i class="fas fa-key me-2 text-primary"></i> License Keys hiện tại của bạn:
                            </h5>
                            <ul class="list-group">
                                @foreach($activeKeys as $key)
                                    <li class="list-group-item d-flex justify-content-between align-items-center border rounded mb-2 animated fadeIn">
                                        <div>
                                            <span class="fw-bold">{{ $key->key }}</span>
                                            @if($key->expires_at)
                                                <span class="text-muted ms-3">
                                                    <i class="far fa-calendar-alt me-1"></i> Hết hạn: {{ $key->expires_at->format('d/m/Y') }}
                                                </span>
                                            @else
                                                <span class="text-muted ms-3">
                                                    <i class="fas fa-infinity me-1"></i> Không hết hạn
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if($key->max_documents)
                                            <span class="badge bg-primary">
                                                <i class="far fa-file-alt me-1"></i> {{ $key->documents_used }}/{{ $key->max_documents }} tài liệu
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="fas fa-infinity"></i> Không giới hạn
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-3 d-grid gap-2 col-md-6 mx-auto">
                                <a href="{{ route('jobs.create') }}" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-1"></i> Tiếp tục sử dụng dịch vụ
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('license.activate') }}" method="POST" class="mt-4 animate-on-scroll" data-animation="fadeInUp">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="license_key" class="form-label">
                            <i class="fas fa-key me-2 text-primary"></i> Nhập License Key mới
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg @error('license_key') is-invalid @enderror" 
                                id="license_key" name="license_key" placeholder="Ví dụ: ABCD-EFGH-IJKL-MNOP"
                                value="{{ old('license_key') }}" autocomplete="off">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i> Kích Hoạt
                            </button>
                        </div>
                        @error('license_key')
                            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-1"></i> Quay Lại Trang Chủ
                        </a>
                    </div>
                </form>

                <div class="mt-5 text-center p-3 bg-light rounded animate-on-scroll" data-animation="fadeIn">
                    <p class="mb-0">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Bạn chưa có License Key? Vui lòng liên hệ admin để được cấp.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format license key as user types (XXXX-XXXX-XXXX-XXXX)
        const licenseKeyInput = document.getElementById('license_key');
        if (licenseKeyInput) {
            licenseKeyInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0 && i < 16) {
                        formattedValue += '-';
                    }
                    if (i < 16) { // Limit to 16 chars (4 groups of 4)
                        formattedValue += value[i];
                    }
                }
                
                e.target.value = formattedValue;
            });
        }
    });
</script>
@endpush