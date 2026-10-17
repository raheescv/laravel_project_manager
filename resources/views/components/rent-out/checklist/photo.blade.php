{{-- One photo tile on the Checklist tab — item, move-out and fixture before/after photos.
     An empty tile is one big tap target that opens the camera / library; a filled one
     zooms on tap, with its own replace and remove buttons. Upload progress shows on the
     tile, and an upload the browser could not finish is toasted instead of vanishing.
     Props: url (current photo), model (wire:model for a new pick, or null for read-only),
     remove (wire:click to drop it, or null), confirm (optional wire:confirm for remove),
     label (empty-state caption), badge (corner tag, e.g. "Master"). --}}
@props(['url' => null, 'model' => null, 'remove' => null, 'confirm' => null, 'label' => 'Photo', 'badge' => null])

<div {{ $attributes->class(['clx-photo-wrap']) }} x-data="{ p: null }"
    x-on:livewire-upload-start="p = 0"
    x-on:livewire-upload-progress="p = $event.detail.progress"
    x-on:livewire-upload-finish="p = null"
    x-on:livewire-upload-cancel="p = null"
    x-on:livewire-upload-error="p = null; toastr.error('The photo did not upload. Check the connection and try again.')">
    <label @class(['clx-photo', 'has' => $url, 'ro' => ! $model]) title="{{ $url ? 'Tap to enlarge' : ($model ? 'Tap to add a photo' : '') }}">
        @if ($url)
            <img src="{{ $url }}" class="zoomable" data-img="{{ $url }}" alt="{{ $label }}" loading="lazy">
            @if ($badge)
                <span class="clx-photo-tag">{{ $badge }}</span>
            @endif
        @else
            <span class="clx-photo-empty"><i class="fa fa-camera"></i>{{ $label }}</span>
        @endif
        @if ($model)
            @if ($url)
                <span class="clx-photo-act" title="Replace photo"><i class="fa fa-camera"></i></span>
            @endif
            <input type="file" class="d-none" accept="image/*" wire:model="{{ $model }}">
        @endif
        <span class="clx-photo-prog" x-show="p !== null" x-cloak :style="'--p:' + p + '%'"><span x-text="p + '%'"></span></span>
    </label>
    @if ($url && $remove)
        <button type="button" class="clx-photo-del" title="Remove photo" wire:click="{{ $remove }}"
            @if ($confirm) wire:confirm="{{ $confirm }}" @endif>
            <i class="fa fa-times"></i>
        </button>
    @endif
</div>
