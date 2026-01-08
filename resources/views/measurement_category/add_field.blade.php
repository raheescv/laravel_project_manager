<x-app-layout>
    <div class="container-fluid">
       

        @livewire('measurement-template-form', [
            'categoryModelClass' => \App\Models\MeasurementCategory::class,
            'category_id' => $category->id
        ])
    </div>
</x-app-layout>
