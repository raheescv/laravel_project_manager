{{-- One yes/no setting as a switch; the value is deferred to Save like every other field. --}}
<label class="scx-toggle" for="sc-{{ $key }}">
    <span class="scx-toggle-text">
        <span class="scx-toggle-label">{{ $label }}</span>
        @isset($hint)
            <span class="scx-toggle-hint">{!! $hint !!}</span>
        @endisset
    </span>
    <span class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" role="switch" id="sc-{{ $key }}" @checked($on)
            x-on:change="$wire.set('{{ $key }}', $event.target.checked ? 'yes' : 'no', false)">
    </span>
</label>
