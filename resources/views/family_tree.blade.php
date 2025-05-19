<x-app-layout>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="row">
                @livewire('family-tree')
            </div>
        </div>
    </div>
    @push('scripts')
        <script src="https://balkan.app/js/FamilyTree.js"></script>
        <script>
            $('#root').attr('class', 'root mn--push');
        </script>
    @endpush
</x-app-layout>
