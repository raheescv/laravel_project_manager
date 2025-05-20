<x-app-layout>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="row">
                @livewire('analytics.visitor-analytics')
            </div>
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
    @endpush
</x-app-layout>
