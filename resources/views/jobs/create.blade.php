@extends('layouts.app')

@section('title', 'Tạo Công Việc Sao Chép | Google Doc Copier')

@push('styles')
    <style>
        .folder-input {
            transition: all 0.3s ease;
        }

        .folder-option {
            transition: all 0.3s ease;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .folder-option:not(.d-none):hover {
            background-color: rgba(66, 133, 244, 0.05);
        }

        #folder_info {
            background-color: #f8f9fa;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-in-out;
        }

        #folder_info:hover {
            background-color: #f0f7ff;
        }

        .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        .option-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .step-number {
            display: inline-block;
            width: 28px;
            height: 28px;
            line-height: 28px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            text-align: center;
            margin-right: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .copy-form-steps {
            position: relative;
        }

        .form-step-connector {
            position: absolute;
            left: 14px;
            top: 28px;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
            z-index: 0;
        }

        .form-step {
            position: relative;
            z-index: 1;
            padding-bottom: 20px;
        }
    </style>
@endpush

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3>Tạo Công Việc Sao Chép Mới</h3>
                </div>
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('jobs.store') }}" method="POST" class="copy-form-steps">
                        <div class="form-step-connector"></div>
                        @csrf

                        <div class="form-step mb-4 animate-on-scroll" data-animation="fadeIn">
                            <h5>
                                <span class="step-number">1</span>
                                Thêm ID tài liệu Google nguồn
                            </h5>
                            <div class="mb-3 mt-3">
                                <textarea class="form-control @error('source_doc_ids') is-invalid @enderror" id="source_doc_ids" name="source_doc_ids"
                                    rows="4"
                                    placeholder="Nhập mỗi link hoặc ID trên một dòng. Ví dụ:&#10;https://docs.google.com/document/d/1aBcDeFgHiJkLmNoPqRsTuVwXyZ/edit&#10;2bCdEfGhIjKlMnOpQrStUvWxYz">{{ old('source_doc_ids') }}</textarea>
                                @error('source_doc_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Bạn có thể nhập link đầy đủ hoặc chỉ ID của tài liệu. ID là phần trong URL sau /d/ và
                                    trước /edit
                                </div>
                            </div>
                        </div>

                        <div class="form-step mb-4 animate-on-scroll delay-2" data-animation="fadeIn">
                            <h5>
                                <span class="step-number">2</span>
                                Chọn nơi lưu tài liệu
                            </h5>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <div class="folder-option border p-3 text-center h-100">
                                        <input class="form-check-input" type="radio" name="folder_option"
                                            id="existing_folder" value="existing"
                                            {{ old('folder_option') != 'new' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100 cursor-pointer" for="existing_folder">
                                            <div class="option-icon">
                                                <i class="fas fa-folder-open"></i>
                                            </div>
                                            <h5>Chọn thư mục hiện có</h5>
                                            <p class="text-muted small">Chọn thư mục từ Google Drive của bạn</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="folder-option border p-3 text-center h-100">
                                        <input class="form-check-input" type="radio" name="folder_option" id="new_folder"
                                            value="new" {{ old('folder_option') == 'new' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="new_folder">
                                            <div class="option-icon">
                                                <i class="fas fa-folder-plus"></i>
                                            </div>
                                            <h5>Tạo thư mục mới</h5>
                                            <p class="text-muted small">Tạo một thư mục mới trong Google Drive</p>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="folder-input mt-3 ps-4 folder-existing {{ old('folder_option') == 'new' ? 'd-none' : '' }}">
                                <button type="button" class="btn btn-outline-primary mb-2" id="browse_folders">
                                    <i class="fas fa-search me-1"></i> Duyệt Thư Mục Drive
                                </button>
                                <input type="hidden" id="selected_folder_id" name="folder_id"
                                    value="{{ old('folder_id') }}">
                                <div id="folder_info" class="p-3 border rounded {{ !old('folder_id') ? 'd-none' : '' }}">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-folder-open text-primary me-2"></i>
                                        <div>
                                            <div class="fw-bold">Thư mục đã chọn:</div>
                                            <div id="folder_name">{{ old('folder_name') ?: 'Chưa chọn thư mục' }}</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="folder_name" id="folder_name_hidden"
                                        value="{{ old('folder_name') }}">
                                </div>
                            </div>

                            <div
                                class="folder-input mt-3 ps-4 folder-new {{ old('folder_option') == 'new' ? '' : 'd-none' }}">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-folder-plus"></i>
                                    </span>
                                    <input type="text" class="form-control" id="new_folder_name" name="new_folder_name"
                                        placeholder="Nhập tên thư mục mới" value="{{ old('new_folder_name') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-step animate-on-scroll delay-3" data-animation="fadeIn">
                            <h5>
                                <span class="step-number">3</span>
                                Thiết lập tốc độ sao chép
                            </h5>
                            <div class="mb-4 mt-3">
                                <label for="interval_seconds" class="form-label">Khoảng thời gian giữa các lần sao chép
                                    (giây)</label>
                                <div class="d-flex align-items-center">
                                    <input type="range" class="form-range me-3" min="10" max="300"
                                        step="5" value="{{ old('interval_seconds', 60) }}" id="interval_range">
                                    <div class="input-group" style="width: 150px">
                                        <input type="number"
                                            class="form-control @error('interval_seconds') is-invalid @enderror"
                                            id="interval_seconds" name="interval_seconds"
                                            value="{{ old('interval_seconds', 60) }}" min="10" max="300">
                                        <span class="input-group-text">giây</span>
                                    </div>
                                </div>
                                @error('interval_seconds')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Thời gian giữa mỗi lần sao chép (tối thiểu 10 giây)
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4 animate-on-scroll delay-4" data-animation="fadeInUp">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-play-circle me-1"></i> Tạo Công Việc Sao Chép
                            </button>
                            <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Quay Lại Danh Sách
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý hiển thị/ẩn các tùy chọn thư mục
            document.querySelectorAll('input[name="folder_option"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.folder-input').forEach(function(el) {
                        el.classList.add('d-none');
                    });

                    if (this.value === 'existing') {
                        document.querySelector('.folder-existing').classList.remove('d-none');
                    } else if (this.value === 'new') {
                        document.querySelector('.folder-new').classList.remove('d-none');
                    }
                });
            });

            // Liên kết slider và input số
            const intervalRange = document.getElementById('interval_range');
            const intervalSeconds = document.getElementById('interval_seconds');

            intervalRange.addEventListener('input', function() {
                intervalSeconds.value = this.value;
            });

            intervalSeconds.addEventListener('input', function() {
                let value = parseInt(this.value);
                if (value < 10) value = 10;
                if (value > 300) value = 300;
                intervalRange.value = value;
            });

            // Nút duyệt thư mục
            document.getElementById('browse_folders').addEventListener('click', function() {
                window.open('{{ route('folders.picker') }}', 'Google Folder Picker',
                    'width=800,height=600');
            });

            // Hàm callback để nhận kết quả từ Picker
            window.setSelectedFolder = function(folderId, folderName) {
                document.getElementById('selected_folder_id').value = folderId;
                document.getElementById('folder_name').textContent = folderName;
                document.getElementById('folder_name_hidden').value = folderName;

                const folderInfo = document.getElementById('folder_info');
                folderInfo.classList.remove('d-none');

                // Add animation effect
                folderInfo.style.animation = 'none';
                folderInfo.offsetHeight; // Trigger reflow
                folderInfo.style.animation = 'bounceIn 0.5s';
            }
        });
    </script>
@endpush
