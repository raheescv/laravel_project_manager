<x-app-layout>
    <div class="container-fluid">
     @livewire('measurement-template-form', ['categoryModelClass' => \App\Models\MeasurementCategory::class])

    </div>
    @push('scripts')
    @endpush
</x-app-layout>
