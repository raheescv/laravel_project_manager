<x-app-layout>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('property.property.view', ['id' => $id])
        </div>
    </div>

    {{-- Reuses the same edit modal as the properties list. --}}
    <x-property.property.property-modal />
</x-app-layout>
