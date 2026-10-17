{{-- Add an area for work done somewhere with no checklist items of its own (a balcony, a
     corridor). Click-and-go: picking an area adds it and opens it, no confirm step.
     Expects $availableCategories. --}}
<div class="clx-add-area" x-data="{ open: false }">
    <button type="button" class="clx-add-area-toggle" x-on:click="open = ! open" x-show="! open">
        <i class="fa fa-plus-circle"></i> Add an area
    </button>
    <div class="clx-add-area-form" x-show="open" x-cloak>
        @if (count($availableCategories))
            <select class="clx-in" wire:change="addFixtureArea($event.target.value)" x-on:change="open = false">
                <option value="">Choose an area…</option>
                @foreach ($availableCategories as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </select>
        @endif
        <div class="d-flex gap-2">
            <input class="clx-in" placeholder="Or type a new area…" wire:model="newAreaCategory"
                wire:keydown.enter.prevent="addFixtureArea" x-on:keydown.enter="open = false">
            <button type="button" class="clx-btn is-primary" wire:click="addFixtureArea" x-on:click="open = false" title="Add area">
                <i class="fa fa-plus"></i>
            </button>
        </div>
        <button type="button" class="clx-add-area-toggle" x-on:click="open = false">Cancel</button>
    </div>
</div>
