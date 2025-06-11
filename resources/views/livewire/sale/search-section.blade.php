<div class="search-section-wrapper">
    @push('styles')
        <style>
            .search-section-wrapper {
                margin-bottom: 0.75rem;
                position: relative;
                padding: 0.25rem;
                background: #f1f5f9;
            }

            .search-container {
                position: relative;
                z-index: 1;
                display: flex;
                gap: 0.5rem;
                align-items: stretch;
            }

            .search-box {
                position: relative;
                flex: 1;
            }

            .search-box .input-group {
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                border: 2px solid transparent;
                transition: all 0.2s ease;
                overflow: hidden;
            }

            .search-box .input-group:focus-within {
                border-color: #0ea5e9;
                background: #f0f9ff;
            }

            .search-box .input-group-text {
                background: transparent;
                border: none;
                color: #0ea5e9;
                font-size: 1rem;
                padding: 0.5rem 0.75rem;
                border-right: 1px solid rgba(0, 0, 0, 0.1);
            }

            .search-box .form-control {
                border: none;
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
                background: transparent;
                color: #1e293b;
                height: auto;
            }

            .search-box .form-control::placeholder {
                color: #94a3b8;
            }

            .search-box .form-control:focus {
                box-shadow: none;
                background: transparent;
            }

            .search-box.barcode-box {
                flex: 0.5;
            }

            .search-box.barcode-box .input-group-text {
                color: #0f766e;
            }

            .view-draft-btn {
                background: #0ea5e9;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                font-weight: 600;
                font-size: 0.875rem;
                color: white;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.375rem;
                min-width: 120px;
                height: 100%;
            }

            .view-draft-btn:hover {
                background: #0284c7;
                transform: translateY(-1px);
            }

            .view-draft-btn:active {
                transform: translateY(0);
                background: #0369a1;
            }

            .view-draft-btn i {
                font-size: 1rem;
            }

            /* Loading animation */
            .search-box.loading .input-group::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(90deg,
                        transparent,
                        rgba(255, 255, 255, 0.4),
                        transparent);
                animation: shimmer 1.5s infinite;
                z-index: 2;
            }

            @keyframes shimmer {
                0% {
                    transform: translateX(-100%);
                }

                100% {
                    transform: translateX(100%);
                }
            }

            /* Responsive Design */
            @media (max-width: 991.98px) {
                .search-container {
                    flex-wrap: wrap;
                }

                .search-box {
                    flex: 1 1 100%;
                }

                .view-draft-btn {
                    flex: 1 1 auto;
                    width: 100%;
                }
            }

            @media (max-width: 575.98px) {
                .search-section-wrapper {
                    padding: 0.25rem;
                    margin-bottom: 0.5rem;
                }

                .search-box .input-group {
                    border-radius: 0.375rem;
                }

                .search-box .input-group-text {
                    padding: 0.375rem 0.5rem;
                    font-size: 0.875rem;
                }

                .search-box .form-control {
                    padding: 0.375rem 0.5rem;
                    font-size: 0.875rem;
                }

                .view-draft-btn {
                    padding: 0.375rem 0.75rem;
                    font-size: 0.875rem;
                }
            }
        </style>
    @endpush

    <div class="search-container">
        <div class="search-box barcode-box" wire:loading.class="loading">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa fa-barcode"></i>
                </span>
                <input type="search" class="form-control" wire:model.live="barcode_key" placeholder="Scan Barcode" autocomplete="off">
            </div>
        </div>

        <div class="search-box" wire:loading.class="loading">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa fa-search"></i>
                </span>
                <input type="search" class="form-control" wire:model.live="product_key" placeholder="Search Products" autocomplete="off">
            </div>
        </div>

        <button type="button" id="viewDraftedSales" class="view-draft-btn">
            <i class="fa fa-file-alt"></i>
            <span>View Draft</span>
        </button>
    </div>
</div>
