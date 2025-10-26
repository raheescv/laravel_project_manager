@props(['product', 'images' => null])

@php
    $angleImages = $images ?? $product->angleImages()->orderedByAngle()->get();
@endphp

@if($angleImages->count() > 0)
    <div class="product-360-section mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa fa-globe text-primary me-2"></i>
                    360° Product View
                </h5>
            </div>
            <div class="card-body">
                <div id="product-360-viewer"
                     data-images="{{ json_encode($angleImages->map(function($img) {
                         return [
                             'path' => $img->path,
                             'url' => $img->url,
                             'degree' => $img->degree,
                             'alt_text' => $img->alt_text ?? '360° Product View'
                         ];
                     })) }}"
                     data-auto-rotate-speed="150"
                     data-enable-drag="true">

                    <!-- Fallback for when Vue component isn't loaded -->
                    <div class="fallback-viewer">
                        <div class="row">
                            @foreach($angleImages as $index => $image)
                                <div class="col-md-2 col-sm-3 col-4 mb-2">
                                    <div class="text-center">
                                        <img src="{{ $image->path }}"
                                             alt="{{ $image->alt_text ?? '360° Image' }}"
                                             class="img-thumbnail"
                                             style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;"
                                             onclick="showImageModal('{{ $image->path }}', '{{ $image->degree }}°')">
                                        <div class="small text-muted mt-1">{{ $image->degree }}°</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fa fa-info-circle me-1"></i>
                                Click on any image to view full size
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">360° Product View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="360° Product View" class="img-fluid">
                    <div class="mt-2">
                        <span id="modalAngle" class="badge bg-primary"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showImageModal(imagePath, angle) {
            document.getElementById('modalImage').src = imagePath;
            document.getElementById('modalAngle').textContent = angle;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        // Initialize Vue component if available
        document.addEventListener('DOMContentLoaded', function() {
            const viewerElement = document.getElementById('product-360-viewer');
            if (viewerElement && typeof Vue !== 'undefined') {
                const images = JSON.parse(viewerElement.dataset.images);
                const autoRotateSpeed = parseInt(viewerElement.dataset.autoRotateSpeed) || 150;
                const enableDrag = viewerElement.dataset.enableDrag === 'true';

                // Create Vue app for 360 viewer
                const { createApp } = Vue;
                createApp({
                    components: {
                        Product360Viewer: {
                            template: `
                                <div class="product-360-viewer" v-if="images.length > 0">
                                    <div class="viewer-container">
                                        <div class="image-container">
                                            <img
                                                :src="currentImage"
                                                :alt="currentImageAlt"
                                                class="main-image"
                                                @mousedown="startDrag"
                                                @mousemove="drag"
                                                @mouseup="endDrag"
                                                @mouseleave="endDrag"
                                                @touchstart="startTouch"
                                                @touchmove="touchMove"
                                                @touchend="endTouch"
                                            />
                                            <div class="loading-overlay" v-if="isLoading">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="controls">
                                            <div class="angle-indicator">
                                                <span class="current-angle">{{ currentAngle }}°</span>
                                            </div>

                                            <div class="navigation-controls">
                                                <button
                                                    class="btn btn-outline-primary btn-sm"
                                                    @click="previousImage"
                                                    :disabled="isLoading"
                                                >
                                                    <i class="fa fa-chevron-left"></i>
                                                </button>

                                                <div class="progress-container">
                                                    <div class="progress" style="height: 6px;">
                                                        <div
                                                            class="progress-bar"
                                                            :style="{ width: progressPercentage + '%' }"
                                                        ></div>
                                                    </div>
                                                </div>

                                                <button
                                                    class="btn btn-outline-primary btn-sm"
                                                    @click="nextImage"
                                                    :disabled="isLoading"
                                                >
                                                    <i class="fa fa-chevron-right"></i>
                                                </button>
                                            </div>

                                            <div class="play-controls">
                                                <button
                                                    class="btn btn-outline-secondary btn-sm"
                                                    @click="toggleAutoRotate"
                                                    :class="{ 'btn-primary': isAutoRotating }"
                                                >
                                                    <i class="fa" :class="isAutoRotating ? 'fa-pause' : 'fa-play'"></i>
                                                    {{ isAutoRotating ? 'Pause' : 'Auto' }}
                                                </button>

                                                <button
                                                    class="btn btn-outline-secondary btn-sm"
                                                    @click="resetView"
                                                >
                                                    <i class="fa fa-refresh"></i>
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `,
                            props: {
                                images: Array,
                                autoRotateSpeed: Number,
                                enableDrag: Boolean
                            },
                            data() {
                                return {
                                    currentIndex: 0,
                                    isLoading: false,
                                    isAutoRotating: false,
                                    autoRotateInterval: null,
                                    isDragging: false,
                                    dragStartX: 0,
                                    dragStartIndex: 0,
                                    touchStartX: 0,
                                    touchStartIndex: 0
                                }
                            },
                            computed: {
                                currentImage() {
                                    if (this.images.length === 0) return '';
                                    return this.images[this.currentIndex]?.path || this.images[this.currentIndex]?.url || '';
                                },
                                currentImageAlt() {
                                    if (this.images.length === 0) return '';
                                    return this.images[this.currentIndex]?.alt_text || `360° view at ${this.currentAngle}°`;
                                },
                                currentAngle() {
                                    if (this.images.length === 0) return 0;
                                    return this.images[this.currentIndex]?.degree || (this.currentIndex * (360 / this.images.length));
                                },
                                progressPercentage() {
                                    if (this.images.length === 0) return 0;
                                    return ((this.currentIndex + 1) / this.images.length) * 100;
                                }
                            },
                            methods: {
                                nextImage() {
                                    if (this.isLoading) return;
                                    this.isLoading = true;
                                    this.currentIndex = (this.currentIndex + 1) % this.images.length;
                                    setTimeout(() => { this.isLoading = false; }, 100);
                                },
                                previousImage() {
                                    if (this.isLoading) return;
                                    this.isLoading = true;
                                    this.currentIndex = this.currentIndex === 0 ? this.images.length - 1 : this.currentIndex - 1;
                                    setTimeout(() => { this.isLoading = false; }, 100);
                                },
                                toggleAutoRotate() {
                                    if (this.isAutoRotating) {
                                        this.stopAutoRotate();
                                    } else {
                                        this.startAutoRotate();
                                    }
                                },
                                startAutoRotate() {
                                    this.isAutoRotating = true;
                                    this.autoRotateInterval = setInterval(() => {
                                        this.nextImage();
                                    }, this.autoRotateSpeed);
                                },
                                stopAutoRotate() {
                                    this.isAutoRotating = false;
                                    if (this.autoRotateInterval) {
                                        clearInterval(this.autoRotateInterval);
                                        this.autoRotateInterval = null;
                                    }
                                },
                                resetView() {
                                    this.stopAutoRotate();
                                    this.currentIndex = 0;
                                    this.isLoading = false;
                                },
                                startDrag(event) {
                                    if (!this.enableDrag) return;
                                    this.isDragging = true;
                                    this.dragStartX = event.clientX;
                                    this.dragStartIndex = this.currentIndex;
                                    event.preventDefault();
                                },
                                drag(event) {
                                    if (!this.isDragging || !this.enableDrag) return;

                                    const deltaX = event.clientX - this.dragStartX;
                                    const sensitivity = 50;
                                    const imageChange = Math.floor(deltaX / sensitivity);

                                    if (imageChange !== 0) {
                                        const newIndex = (this.dragStartIndex + imageChange) % this.images.length;
                                        if (newIndex < 0) {
                                            this.currentIndex = this.images.length + newIndex;
                                        } else {
                                            this.currentIndex = newIndex;
                                        }
                                        this.dragStartIndex = this.currentIndex;
                                        this.dragStartX = event.clientX;
                                    }
                                },
                                endDrag() {
                                    this.isDragging = false;
                                },
                                startTouch(event) {
                                    if (!this.enableDrag) return;
                                    this.touchStartX = event.touches[0].clientX;
                                    this.touchStartIndex = this.currentIndex;
                                },
                                touchMove(event) {
                                    if (!this.enableDrag) return;

                                    const deltaX = event.touches[0].clientX - this.touchStartX;
                                    const sensitivity = 50;
                                    const imageChange = Math.floor(deltaX / sensitivity);

                                    if (imageChange !== 0) {
                                        const newIndex = (this.touchStartIndex + imageChange) % this.images.length;
                                        if (newIndex < 0) {
                                            this.currentIndex = this.images.length + newIndex;
                                        } else {
                                            this.currentIndex = newIndex;
                                        }
                                        this.touchStartIndex = this.currentIndex;
                                        this.touchStartX = event.touches[0].clientX;
                                    }
                                },
                                endTouch() {
                                    // Touch ended
                                }
                            },
                            mounted() {
                                if (this.images.length > 0 && this.images[0].degree !== undefined) {
                                    this.images.sort((a, b) => (a.degree || 0) - (b.degree || 0));
                                }
                            },
                            beforeUnmount() {
                                this.stopAutoRotate();
                            }
                        }
                    },
                    data() {
                        return {
                            images: images,
                            autoRotateSpeed: autoRotateSpeed,
                            enableDrag: enableDrag
                        }
                    }
                }).mount('#product-360-viewer');
            }
        });
    </script>

    <style>
        .product-360-viewer {
            max-width: 600px;
            margin: 0 auto;
        }

        .viewer-container {
            position: relative;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }

        .image-container {
            position: relative;
            width: 100%;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%),
                        linear-gradient(-45deg, #f0f0f0 25%, transparent 25%),
                        linear-gradient(45deg, transparent 75%, #f0f0f0 75%),
                        linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        }

        .main-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            cursor: grab;
            transition: transform 0.1s ease;
            user-select: none;
        }

        .main-image:active {
            cursor: grabbing;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .controls {
            padding: 15px;
            background: white;
            border-top: 1px solid #dee2e6;
        }

        .angle-indicator {
            text-align: center;
            margin-bottom: 15px;
        }

        .current-angle {
            font-size: 1.2em;
            font-weight: bold;
            color: #007bff;
        }

        .navigation-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .progress-container {
            flex: 1;
        }

        .play-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            border-radius: 20px;
        }

        .progress {
            border-radius: 10px;
        }

        .progress-bar {
            background: linear-gradient(90deg, #007bff, #0056b3);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .fallback-viewer {
            text-align: center;
        }

        @media (max-width: 768px) {
            .image-container {
                height: 300px;
            }

            .controls {
                padding: 10px;
            }

            .navigation-controls {
                flex-direction: column;
                gap: 8px;
            }

            .play-controls {
                flex-wrap: wrap;
            }
        }
    </style>
@endif
