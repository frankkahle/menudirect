@props([
    'name',
    'aspectRatio' => 1,
    'current' => null,
    'maxSizeMb' => config('images.max_upload_mb', 15),
    'previewClass' => 'h-24 w-full object-cover rounded-lg border',
    'helpText' => null,
    'id' => null,
    'autoSubmit' => false,
])

@php
    $uid = $id ?? 'cropper_' . \Illuminate\Support\Str::random(8);
@endphp

@once
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.css">
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.js"></script>

    <style>
        .image-dropzone {
            border: 2px dashed #d1d5db;
            border-radius: 0.5rem;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.2s;
            background: #f9fafb;
            cursor: pointer;
        }
        .image-dropzone:hover { border-color: #6366f1; background: #eef2ff; }
        .image-dropzone.dragging { border-color: #4f46e5; background: #eef2ff; border-style: solid; }
        .image-dropzone-error { border-color: #ef4444; background: #fef2f2; }
    </style>

    <div id="image-cropper-modal-root" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/60" style="display:none;">
        <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4 overflow-hidden">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-900">Adjust crop</h3>
                <button type="button" data-cropper-cancel class="text-gray-400 hover:text-gray-600" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4 bg-gray-900" style="max-height:70vh; overflow:hidden;">
                <img id="image-cropper-modal-img" src="" alt="" style="max-width:100%; display:block;">
            </div>
            <div class="px-4 py-3 border-t flex justify-end gap-2 bg-gray-50">
                <button type="button" data-cropper-cancel class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
                <button type="button" data-cropper-confirm class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700">Use this crop</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        if (window.__imageCropperInit) return;
        window.__imageCropperInit = true;

        const modal = document.getElementById('image-cropper-modal-root');
        const modalImg = document.getElementById('image-cropper-modal-img');
        let activeCropper = null;
        let activeInput = null;
        let activePreviewEl = null;
        let activeOriginalName = 'image.jpg';
        let activePostConfig = null;
        let activeWrapper = null;

        function showError(wrapper, msg) {
            const errEl = wrapper.querySelector('[data-cropper-error]');
            if (errEl) {
                errEl.textContent = msg;
                errEl.classList.remove('hidden');
            }
            const dz = wrapper.querySelector('.image-dropzone');
            if (dz) dz.classList.add('image-dropzone-error');
        }
        function clearError(wrapper) {
            const errEl = wrapper.querySelector('[data-cropper-error]');
            if (errEl) errEl.classList.add('hidden');
            const dz = wrapper.querySelector('.image-dropzone');
            if (dz) dz.classList.remove('image-dropzone-error');
        }

        function openModal(dataUrl, aspect) {
            modalImg.src = dataUrl;
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            if (activeCropper) { activeCropper.destroy(); activeCropper = null; }
            modalImg.onload = function () {
                activeCropper = new Cropper(modalImg, {
                    aspectRatio: aspect,
                    viewMode: 1,
                    autoCropArea: 1,
                    movable: true,
                    zoomable: true,
                    scalable: false,
                    rotatable: false,
                });
            };
        }

        function closeModal() {
            modal.style.display = 'none';
            modal.classList.add('hidden');
            if (activeCropper) { activeCropper.destroy(); activeCropper = null; }
            activeInput = null;
            activePreviewEl = null;
            activePostConfig = null;
            activeWrapper = null;
            modalImg.src = '';
        }

        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
        modal.querySelectorAll('[data-cropper-cancel]').forEach(b => b.addEventListener('click', closeModal));

        modal.querySelector('[data-cropper-confirm]').addEventListener('click', function () {
            if (!activeCropper) return closeModal();
            if (!activeInput && !activePostConfig) return closeModal();
            const canvas = activeCropper.getCroppedCanvas({
                maxWidth: 2000,
                maxHeight: 2000,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            if (!canvas) return closeModal();
            canvas.toBlob(function (blob) {
                if (!blob) return closeModal();
                const fileName = activeOriginalName.replace(/\.[^.]+$/, '') + '.jpg';
                const file = new File([blob], fileName, { type: 'image/jpeg' });

                // Mode 1: URL-initiated re-crop — POST via fetch with error handling
                if (activePostConfig) {
                    const cfg = activePostConfig;
                    const fd = new FormData();
                    fd.append(cfg.fieldName || 'image', file);
                    if ((cfg.method || 'POST').toUpperCase() !== 'POST') {
                        fd.append('_method', (cfg.method || 'POST').toUpperCase());
                    }
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const confirmBtn = modal.querySelector('[data-cropper-confirm]');
                    confirmBtn.disabled = true;
                    confirmBtn.textContent = 'Saving…';
                    fetch(cfg.postUrl, {
                        method: 'POST',
                        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } : { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: fd,
                        credentials: 'same-origin',
                    }).then(function (resp) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Use this crop';
                        if (resp.ok || resp.redirected) {
                            closeModal();
                            window.location.reload();
                        } else if (resp.status === 413) {
                            alert('That image is too large — please use one under 20MB.');
                        } else if (resp.status === 422) {
                            resp.json().then(data => {
                                const err = data?.errors ? Object.values(data.errors).flat().join('\n') : (data?.message || 'Validation failed');
                                alert('Upload rejected: ' + err);
                            }).catch(() => alert('Upload rejected (HTTP 422).'));
                        } else {
                            alert('Failed to save crop — HTTP ' + resp.status + '. Please try again.');
                        }
                    }).catch(function (err) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Use this crop';
                        alert('Network error saving crop: ' + err.message);
                    });
                    return;
                }

                // Mode 2: File-picker — stuff blob into the file input for form submit
                const dt = new DataTransfer();
                dt.items.add(file);
                activeInput.files = dt.files;
                if (activePreviewEl) {
                    activePreviewEl.src = URL.createObjectURL(blob);
                    activePreviewEl.classList.remove('hidden');
                }
                // Show filename in dropzone
                if (activeWrapper) {
                    const nameEl = activeWrapper.querySelector('[data-cropper-filename]');
                    if (nameEl) {
                        const sizeKb = Math.round(file.size / 1024);
                        nameEl.textContent = file.name + ' · ' + (sizeKb > 1024 ? (sizeKb/1024).toFixed(1) + ' MB' : sizeKb + ' KB') + ' · ready to upload';
                        nameEl.classList.remove('hidden');
                    }
                    const placeholder = activeWrapper.querySelector('[data-cropper-placeholder]');
                    if (placeholder) placeholder.classList.add('hidden');
                }
                const finishInput = activeInput;
                const shouldSubmit = finishInput && finishInput.dataset.cropperAutoSubmit === '1';
                closeModal();
                if (shouldSubmit && finishInput.form) {
                    finishInput.form.submit();
                }
            }, 'image/jpeg', 0.9);
        });

        window.recropImageFromUrl = function (config) {
            if (typeof Cropper === 'undefined') {
                alert('Image editor not loaded yet — please try again in a moment.');
                return;
            }
            if (!config || !config.url || !config.postUrl) return;
            activeInput = null;
            activePreviewEl = null;
            activePostConfig = config;
            activeWrapper = null;
            activeOriginalName = (config.url.split('/').pop() || 'image.jpg').split('?')[0];
            fetch(config.url, { credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) {
                    if (!r.ok) throw new Error('Failed to load image (HTTP ' + r.status + ')');
                    return r.blob();
                })
                .then(function (blob) {
                    const reader = new FileReader();
                    reader.onload = function (ev) { openModal(ev.target.result, config.aspect || 1); };
                    reader.readAsDataURL(blob);
                })
                .catch(function (err) {
                    alert('Could not load image for cropping: ' + err.message);
                });
        };

        function handleFile(wrapper, file, aspect, maxSizeMb) {
            clearError(wrapper);

            // Validate type
            if (!file.type.startsWith('image/')) {
                showError(wrapper, 'That file is not an image. Please choose a JPG, PNG, GIF, or WebP.');
                return;
            }

            // Validate size
            if (file.size > maxSizeMb * 1024 * 1024) {
                const actualMb = (file.size / 1024 / 1024).toFixed(1);
                showError(wrapper, 'Image is ' + actualMb + ' MB — please choose one under ' + maxSizeMb + ' MB.');
                return;
            }

            const input = wrapper.querySelector('input[type="file"]');
            const preview = wrapper.querySelector('[data-cropper-preview]');

            if (typeof Cropper === 'undefined') {
                // CDN blocked — accept file as-is
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                if (preview) {
                    preview.src = URL.createObjectURL(file);
                    preview.classList.remove('hidden');
                }
                return;
            }

            activeInput = input;
            activePreviewEl = preview;
            activeWrapper = wrapper;
            activeOriginalName = file.name || 'image.jpg';
            const reader = new FileReader();
            reader.onload = function (ev) { openModal(ev.target.result, aspect); };
            reader.readAsDataURL(file);
        }

        window.initImageCropper = function (wrapperId, aspect, maxSizeMb) {
            const wrapper = document.getElementById(wrapperId);
            if (!wrapper) return;
            const input = wrapper.querySelector('input[type="file"]');
            const dropzone = wrapper.querySelector('.image-dropzone');
            if (!input || !dropzone) return;

            // File picker change
            input.addEventListener('change', function () {
                const file = input.files && input.files[0];
                if (!file) return;
                handleFile(wrapper, file, aspect, maxSizeMb);
            });

            // Click anywhere on dropzone opens file picker
            dropzone.addEventListener('click', function (e) {
                if (e.target.tagName === 'BUTTON') return;
                input.click();
            });

            // Drag and drop
            ['dragenter', 'dragover'].forEach(evt => {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('dragging');
                });
            });
            ['dragleave', 'drop'].forEach(evt => {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('dragging');
                });
            });
            dropzone.addEventListener('drop', function (e) {
                const file = e.dataTransfer?.files?.[0];
                if (!file) return;
                handleFile(wrapper, file, aspect, maxSizeMb);
            });

            // Clear button
            const clearBtn = wrapper.querySelector('[data-cropper-clear]');
            if (clearBtn) {
                clearBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    input.value = '';
                    const nameEl = wrapper.querySelector('[data-cropper-filename]');
                    const placeholder = wrapper.querySelector('[data-cropper-placeholder]');
                    const preview = wrapper.querySelector('[data-cropper-preview]');
                    if (nameEl) nameEl.classList.add('hidden');
                    if (placeholder) placeholder.classList.remove('hidden');
                    if (preview && !preview.dataset.keepCurrent) {
                        preview.src = '';
                        preview.classList.add('hidden');
                    }
                    clearError(wrapper);
                });
            }
        };
    })();
    </script>
@endonce

<div id="{{ $uid }}" class="image-cropper-wrapper">
    @if($current)
        <img data-cropper-preview data-keep-current="1" src="{{ $current }}" alt="Current image" class="{{ $previewClass }} mb-2">
    @else
        <img data-cropper-preview src="" alt="" class="{{ $previewClass }} mb-2 hidden">
    @endif

    <div class="image-dropzone">
        <div data-cropper-placeholder class="flex flex-col items-center gap-2">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <div class="text-sm text-gray-700">
                <span class="font-medium text-indigo-600">Click to upload</span> or drag and drop
            </div>
            <div class="text-xs text-gray-500">JPG, PNG, GIF, or WebP &middot; up to {{ $maxSizeMb }}MB</div>
        </div>
        <div data-cropper-filename class="hidden flex items-center justify-center gap-2 text-sm text-emerald-700 font-medium">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        </div>
    </div>

    <input type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
           @if($autoSubmit) data-cropper-auto-submit="1" @endif
           class="sr-only">

    <div class="flex items-center justify-between mt-2">
        @if($helpText)
            <p class="text-xs text-gray-500">{{ $helpText }}</p>
        @else
            <p class="text-xs text-gray-500">Recommended: high-quality photo for best results.</p>
        @endif
        <button type="button" data-cropper-clear class="text-xs text-gray-500 hover:text-red-600 hidden" onclick="this.classList.add('hidden')">Clear</button>
    </div>

    <p data-cropper-error class="hidden text-sm text-red-600 mt-2 flex items-start gap-1.5">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </p>

    <script>
        (function () {
            function go() {
                window.initImageCropper({!! json_encode($uid) !!}, {{ $aspectRatio }}, {{ $maxSizeMb }});
                // Show clear button when file chosen
                const w = document.getElementById({!! json_encode($uid) !!});
                const inp = w?.querySelector('input[type="file"]');
                const clearBtn = w?.querySelector('[data-cropper-clear]');
                inp?.addEventListener('change', () => {
                    if (inp.files?.length) clearBtn?.classList.remove('hidden');
                });
            }
            if (document.readyState !== 'loading') go(); else document.addEventListener('DOMContentLoaded', go);
        })();
    </script>
</div>
