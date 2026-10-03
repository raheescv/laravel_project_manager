<div>
    <div class="row g-2 align-items-start mb-3" x-data="{
        users: @js($mentionUsers->values()),
        note: $wire.entangle('newNote'),
        open: false,
        query: '',
        start: 0,
        active: 0,
        get matches() {
            const q = this.query.toLowerCase();
            return this.users.filter(name => name.toLowerCase().includes(q));
        },
        detect(input) {
            const before = input.value.slice(0, input.selectionStart);
            const match = before.match(/(^|\s)@([^@\n]{0,30})$/);
            if (!match) { this.open = false; return; }
            this.query = match[2];
            this.start = before.length - match[2].length - 1;
            this.active = 0;
            this.open = this.matches.length > 0;
        },
        pick(name) {
            const input = this.$refs.noteInput;
            const after = this.note.slice(input.selectionStart);
            const head = this.note.slice(0, this.start) + '@' + name + ' ';
            this.note = head + after.replace(/^\S*/, '');
            this.open = false;
            this.$nextTick(() => { input.focus(); input.setSelectionRange(head.length, head.length); });
        },
        move(step) {
            if (!this.open) { return; }
            this.active = (this.active + step + this.matches.length) % this.matches.length;
            this.$nextTick(() => this.$refs.mentionList.children[this.active + 1]?.scrollIntoView({ block: 'nearest' }));
        },
        enter() {
            if (this.open && this.matches[this.active]) { this.pick(this.matches[this.active]); return; }
            $wire.addNote();
        },
    }">
        <div class="col">
            <div @click.outside="open = false">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-secondary-subtle">
                        <i class="fa fa-pencil"></i>
                    </span>
                    <input type="text" class="form-control form-control-sm border-secondary-subtle shadow-sm"
                        x-ref="noteInput" x-model="note" placeholder="Add a note... type @ to assign a user"
                        autocomplete="off" @input="detect($event.target)" @click="detect($event.target)"
                        @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                        @keydown.escape="open = false" @keydown.enter.prevent="enter()">
                </div>
                <div class="border rounded shadow-sm bg-white mt-1 py-1" x-show="open" x-cloak x-ref="mentionList"
                    style="max-height: 260px; overflow-y: auto; max-width: 320px;">
                    <template x-for="(name, index) in matches" :key="index">
                        <div class="px-3 py-1 small d-flex align-items-center" style="cursor: pointer;"
                            :class="index === active ? 'bg-primary text-white' : 'text-body'"
                            @mouseenter="active = index" @mousedown.prevent="pick(name)">
                            <i class="fa fa-user me-2"></i><span x-text="name"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center"
                style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                wire:click="addNote">
                <i class="fa fa-plus me-1"></i> Add Note
            </button>
        </div>
    </div>
    @php
        $mentionPattern = $mentionUsers->isEmpty() ? null : '/@(?:'.$mentionUsers->sortByDesc(fn ($name) => mb_strlen($name))->map(fn ($name) => preg_quote(e($name), '/'))->join('|').')(?![\\w])/iu';
    @endphp
    @forelse($rentOut->notes as $note)
        <div class="card mb-2 border shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        @php
                            $noteHtml = $mentionPattern ? preg_replace($mentionPattern, '<span class="badge bg-primary-subtle text-primary fw-semibold">$0</span>', e($note->note)) : e($note->note);
                        @endphp
                        <p class="mb-0 small">{!! $noteHtml !!}</p>
                        @if ($note->creator)
                            <small class="text-muted"><i class="fa fa-user me-1"></i>{{ $note->creator->name }}</small>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-3">
                        <small class="text-muted text-nowrap"><i
                                class="fa fa-clock-o me-1"></i>{{ $note->created_at?->format('d-m-Y H:i') }}</small>
                        <button type="button" class="btn btn-danger btn-sm text-white"
                            wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?" title="Delete"
                            data-bs-toggle="tooltip">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-4">No notes found</div>
    @endforelse
</div>
