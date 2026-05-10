<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('purchase::index') }}">Purchase</a></li>
                    <li class="breadcrumb-item active" aria-current="view">View</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('purchase.view', ['table_id' => $id])
        </div>
    </div>
    @push('styles')
        <style>
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

            .icon-box {
                width: 40px;
                height: 40px;
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

            .avatar {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
            }
        </style>
    @endpush
</x-app-layout>
