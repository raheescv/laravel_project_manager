{{-- Product Normal Images Component --}}
@props(['product', 'images' => null])

@php
    $productImages = $images ?? $product->normalImages()->get();
@endphp

@if($productImages->count() > 0)
    <div class="product-images-section mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa fa-images text-primary me-2"></i>
                    Product Images
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($productImages as $index => $image)
                        <div class="col-md-3 col-sm-4 col-6 mb-3">
                            <div class="image-container position-relative">
                                <img src="{{ $image->path }}"
                                     alt="Product Image {{ $index + 1 }}"
                                     class="img-fluid img-thumbnail"
                                     style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;"
                                     onclick="showImageModal('{{ $image->path }}', 'Product Image {{ $index + 1 }}')">

                                @if($loop->first)
                                    <span class="badge bg-success position-absolute top-0 start-0 m-2">
                                        <i class="fa fa-check"></i> Thumbnail
                                    </span>
                                @endif

                                <div class="image-overlay">
                                    <button class="btn btn-sm btn-light" onclick="showImageModal('{{ $image->path }}', 'Product Image {{ $index + 1 }}')">
                                        <i class="fa fa-expand"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($productImages->count() > 1)
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            <i class="fa fa-info-circle me-1"></i>
                            Click on any image to view full size
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

<style>
    .image-container {
        transition: transform 0.2s;
    }

    .image-container:hover {
        transform: scale(1.05);
    }

    .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .image-container:hover .image-overlay {
        opacity: 1;
    }
</style>

<script>
    function showImageModal(imagePath, title) {
        // Create a simple modal for viewing full-size images
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:pointer;';
        modal.innerHTML = `
            <div style="position:relative;max-width:90%;max-height:90%;">
                <button onclick="this.closest('.image-modal').remove()"
                        style="position:absolute;top:-40px;right:0;background:none;border:none;color:white;font-size:30px;cursor:pointer;">
                    &times;
                </button>
                <img src="${imagePath}"
                     alt="${title}"
                     style="max-width:100%;max-height:80vh;object-fit:contain;">
                <div style="color:white;margin-top:10px;text-align:center;">${title}</div>
            </div>
        `;

        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        };

        document.body.appendChild(modal);
    }
</script>
