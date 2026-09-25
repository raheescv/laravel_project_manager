<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Sale</li>
                    <li class="breadcrumb-item active" aria-current="page">Feedback</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Sale Feedback</h1>
            <p class="lead">
                How customers rated their visit — star ratings, compliments, suggestions and complaints left on each sale
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('report.sale.feedback-report')
        </div>
    </div>
</x-app-layout>
