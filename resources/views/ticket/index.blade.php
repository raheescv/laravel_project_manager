<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tickets</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Support Tickets</h1>
            <p class="lead">
                Manage tickets with status board, filters, file uploads, and comments
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('ticket.table')
            </div>
            @include('components.ticket.ticket-modal')
        </div>
    </div>
</x-app-layout>
