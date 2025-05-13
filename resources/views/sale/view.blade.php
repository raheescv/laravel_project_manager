<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale::index') }}">Sale</a></li>
                    <li class="breadcrumb-item active" aria-current="view">View</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('sale.view', ['table_id' => $id])
        </div>
    </div>
    @push('styles')
        <style>
            .backdrop-blur-card {
                border: 1px solid rgba(255, 255, 255, 0.1);
                background: rgba(255, 255, 255, 0.05);
            }

            .glass-card {
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(10px);
                border-radius: 0.75rem;
                border: 1px solid rgba(255, 255, 255, 0.2);
                box-shadow: 0 2px 12px -1px rgba(0, 0, 0, 0.1);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .glass-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 15px -2px rgba(0, 0, 0, 0.15);
            }

            .text-primary-gradient {
                background: linear-gradient(45deg, #0d6efd, #0dcaf0);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .text-success-gradient {
                background: linear-gradient(45deg, #198754, #20c997);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .hover-lift {
                transition: transform 0.2s ease;
            }

            .hover-lift:hover {
                transform: translateY(-2px);
            }

            .stats-icon-container {
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                background: rgba(13, 110, 253, 0.1);
            }

            .customer-info-item,
            .contact-info-item {
                transition: all 0.2s ease;
            }

            .customer-info-item:hover,
            .contact-info-item:hover {
                transform: translateX(5px);
            }

            .icon-box {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .icon-sm {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .info-item {
                transition: all 0.2s ease;
            }

            .info-item:hover {
                background: var(--bs-light) !important;
                transform: translateY(-1px);
            }

            .customer-info,
            .contact-info {
                height: 100%;
            }
        </style>
    @endpush
</x-app-layout>
