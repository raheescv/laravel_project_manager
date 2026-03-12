<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="demo-psi-home"></i></a></li>
                    <li class="breadcrumb-item">Properties</li>
                    <li class="breadcrumb-item">Cheque Managements</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Cheque Management</h1>
            <p class="lead">Manage cheques for rental agreements</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.cheque-management-table', ['agreementType' => 'rental'])
        </div>
    </div>
</x-app-layout>
