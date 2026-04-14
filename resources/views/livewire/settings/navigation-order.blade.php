<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0">Navigation Order</h5>
                <p class="text-muted small mb-0 mt-1">Drag items to reorder. Toggle the eye icon to show or hide menu sections.</p>
            </div>
            <div class="d-flex gap-2">
                <button wire:click="resetToDefault"
                    wire:confirm="Reset navigation to the default order?"
                    class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-refresh me-1"></i> Reset Default
                </button>
                <button wire:click="save" class="btn btn-primary btn-sm">
                    <i class="fa fa-save me-1"></i> Save Order
                </button>
            </div>
        </div>
        <div class="card-body p-3">
            <ul id="nav-order-sortable" class="list-group list-group-flush" style="user-select: none;">
                @foreach ($items as $item)
                    <li class="list-group-item px-2 py-2 rounded mb-1 border"
                        data-id="{{ $item['id'] }}"
                        style="transition: opacity 0.2s; {{ !($item['visible'] ?? true) ? 'opacity: 0.45;' : '' }}">
                        <div class="d-flex align-items-start gap-2">
                            {{-- Drag handle --}}
                            <span class="drag-handle text-muted mt-1 flex-shrink-0" style="cursor: grab; font-size: 1rem; line-height: 1.4;">
                                <i class="fa fa-bars"></i>
                            </span>

                            {{-- Icon + label + children --}}
                            <div class="flex-fill min-w-0">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="{{ $item['icon'] }} fs-6 flex-shrink-0" style="width: 18px; text-align: center;"></i>
                                    <span class="fw-semibold">{{ $item['label'] }}</span>
                                    @if (!($item['visible'] ?? true))
                                        <span class="badge bg-secondary-subtle text-secondary small">Hidden</span>
                                    @endif
                                </div>
                                @if (!empty($item['children']))
                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                        @foreach ($item['children'] as $child)
                                            <span class="badge bg-light text-secondary border small fw-normal">{{ $child }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Visibility toggle --}}
                            <button type="button"
                                wire:click="toggleVisibility('{{ $item['id'] }}')"
                                class="btn btn-sm btn-link p-0 text-decoration-none flex-shrink-0 mt-1"
                                title="{{ ($item['visible'] ?? true) ? 'Hide from navigation' : 'Show in navigation' }}">
                                @if ($item['visible'] ?? true)
                                    <i class="fa fa-eye text-success fs-6"></i>
                                @else
                                    <i class="fa fa-eye-slash text-muted fs-6"></i>
                                @endif
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-footer text-muted small">
            <i class="fa fa-info-circle me-1"></i>
            Changes apply after saving. Reload the page to see the updated navigation.
            Hidden items are still accessible via direct URLs — they are only hidden from the sidebar.
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
        <script>
            (function () {
                let _navSortable = null;

                function initNavSortable() {
                    const el = document.getElementById('nav-order-sortable');
                    if (!el) return;

                    if (_navSortable) {
                        _navSortable.destroy();
                        _navSortable = null;
                    }

                    _navSortable = new Sortable(el, {
                        animation: 180,
                        handle: '.drag-handle',
                        ghostClass: 'bg-primary-subtle',
                        chosenClass: 'shadow-sm',
                        dragClass: 'opacity-75',
                        onEnd: function () {
                            const ids = Array.from(
                                el.querySelectorAll('[data-id]')
                            ).map(li => li.dataset.id);
                            Livewire.dispatch('nav-order-updated', { ids });
                        }
                    });
                }

                // Initialize on first load
                document.addEventListener('livewire:initialized', initNavSortable);

                // Re-initialize after each Livewire component update (visibility toggle, reset, etc.)
                document.addEventListener('livewire:updated', function () {
                    // Small delay to let the DOM settle
                    setTimeout(initNavSortable, 30);
                });
            })();
        </script>
    @endpush
</div>
