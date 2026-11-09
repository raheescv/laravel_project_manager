<div class="rgx">
    @php
        $initialsOf = function (?string $name): string {
            $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [''];

            return mb_strtoupper(mb_substr($parts[0], 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
        };
    @endphp

    <header class="rgx-head">
        <div class="rgx-stats">
            <div class="rgx-stat"><b>{{ $stats['total'] }}</b><span>Parents</span></div>
            <div class="rgx-stat"><b>{{ $stats['active'] - $stats['waiting'] }}</b><span>Portal ready</span></div>
            <div class="rgx-stat"><b>{{ $stats['waiting'] }}</b><span>No password yet</span></div>
        </div>
    </header>

    <div class="rgx-filters">
        <label class="rgx-search">
            <i class="fa fa-search"></i>
            <input type="text" wire:model.live.debounce.400ms="search" autofocus placeholder="Name, mobile, email or student name…" aria-label="Search" autocomplete="off">
        </label>
        <div class="rgx-chips" role="group" aria-label="Status">
            <button type="button" @class(['rgx-chip', 'is-on' => $status === '']) wire:click="$set('status', '')">All statuses<i>{{ $stats['total'] }}</i></button>
            <button type="button" @class(['rgx-chip', 'is-on' => $status === 'active']) wire:click="$set('status', 'active')">Active<i>{{ $stats['active'] }}</i></button>
            <button type="button" @class(['rgx-chip', 'is-on' => $status === 'disabled']) wire:click="$set('status', 'disabled')">Disabled<i>{{ $stats['disabled'] }}</i></button>
        </div>
    </div>

    <div class="rgx-list rgx-guardians">
        <div class="rgx-row-head">
            <span><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="guardians.name" label="Parent" /></span>
            <span><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="guardians.mobile" label="Contact" /></span>
            <span>Students</span>
            <span><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="guardians.status" label="Status" /></span>
            <span class="text-end pe-3">Actions</span>
        </div>

        @forelse ($data as $guardian)
            <div class="rgx-row" wire:key="guardian-{{ $guardian->id }}">
                <div class="rgx-parent">
                    <span class="rgx-avatar">{{ $initialsOf($guardian->name) }}</span>
                    <div><b>{{ $guardian->name }}</b></div>
                </div>
                <div class="rgx-contact">
                    <b>{{ $guardian->mobile }}</b>
                    @if ($guardian->email)
                        <small>{{ $guardian->email }}</small>
                    @endif
                    @if ($guardian->id_no)
                        <small>QID {{ $guardian->id_no }}</small>
                    @endif
                </div>
                <div class="rgx-tags">
                    @forelse ($guardian->students as $student)
                        <a href="{{ route('student::view', $student->id) }}" class="rgx-tag rgx-tag-link" wire:key="guardian-{{ $guardian->id }}-student-{{ $student->id }}">{{ $student->name }}</a>
                    @empty
                        <span class="rgx-when">No students</span>
                    @endforelse
                </div>
                <div class="rgx-status">
                    <span @class(['rgx-pill', 'rgx-pill-brand' => $guardian->status === 'active', 'rgx-pill-off' => $guardian->status !== 'active'])>{{ ucfirst($guardian->status) }}</span>
                    @if ($guardian->isActive() && $guardian->isPendingInvite())
                        <span class="rgx-pill rgx-pill-warn"><i class="fa fa-clock-o"></i>{{ $guardian->invited_at ? 'Invited, no password yet' : 'Not invited' }}</span>
                    @elseif ($guardian->isActive())
                        <span class="rgx-pill rgx-pill-ok"><i class="fa fa-check"></i>{{ $guardian->last_login_at ? 'Signed in '.$guardian->last_login_at->diffForHumans() : 'Password set' }}</span>
                    @endif
                </div>
                <div class="rgx-actions">
                    @can('student guardian.invite')
                        <button type="button" class="rgx-btn rgx-btn-view" wire:click="invite({{ $guardian->id }})" wire:loading.attr="disabled" wire:target="invite({{ $guardian->id }})">
                            <i class="fa fa-paper-plane"></i>{{ $guardian->isPendingInvite() ? 'Send invite' : 'Reset password' }}
                        </button>
                    @endcan
                </div>
                @if ($invite_link && $invite_guardian_id === $guardian->id)
                    <div class="rgx-invite" x-data="{ copied: false }">
                        <input type="text" class="rgx-invite-link" value="{{ $invite_link }}" readonly x-ref="link-{{ $guardian->id }}" onclick="this.select()" aria-label="Set-password link">
                        <button type="button" class="rgx-btn rgx-btn-view" aria-label="Copy link" x-on:click="navigator.clipboard.writeText($refs['link-{{ $guardian->id }}'].value); copied = true; setTimeout(() => copied = false, 2000)">
                            <i class="fa" :class="copied ? 'fa-check' : 'fa-files-o'"></i>
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="rgx-empty">
                <i class="fa fa-users"></i>
                @if ($hiddenCount)
                    <div class="mb-3">No parents match these filters. {{ $hiddenCount }} {{ Str::plural('parent', $hiddenCount) }} {{ $hiddenCount === 1 ? 'is' : 'are' }} hidden by them.</div>
                    <button type="button" class="rgx-btn" wire:click="clearFilters"><i class="fa fa-filter"></i>Show all parents</button>
                @else
                    No parents yet.
                @endif
            </div>
        @endforelse
    </div>

    <div class="rgx-foot">
        <div class="rgx-pages">{{ $data->links() }}</div>
        <select wire:model.live="limit" class="rgx-limit" aria-label="Rows per page">
            <option value="25">25</option>
            <option value="100">100</option>
            <option value="500">500</option>
        </select>
    </div>
</div>
