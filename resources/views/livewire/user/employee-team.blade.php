{{-- Team tab of the employee view; inherits the parent .uvx Dossier styles. --}}
<div class="row g-3">
    <div class="col-lg-7">
        <section class="uvx-panel">
            <div class="uvx-ph">
                <span class="ic"><i class="fa fa-users"></i></span>
                <div>
                    <h2>Sales Team</h2>
                    <div class="sub">Employees whose leads {{ $user->name }} can see</div>
                </div>
            </div>
            <div class="uvx-pb">
                @can('employee.edit')
                    <div class="uvx-label">Add Member</div>
                    <div class="uvx-selrow mb-3">
                        <div wire:ignore>
                            <select id="employee_team_member_select" placeholder="Search employee…"></select>
                        </div>
                        <button class="uvx-btn pri" type="button" wire:click="add" wire:loading.attr="disabled" wire:target="add">
                            <i class="fa fa-plus"></i> Add
                        </button>
                    </div>
                @endcan

                <div class="uvx-label">Members ({{ $members->count() }})</div>
                @forelse ($members as $item)
                    <div class="uvx-tgl" wire:key="employee-team-{{ $item->id }}">
                        <span class="ic"><i class="fa fa-user"></i></span>
                        <div class="tx">
                            <b>{{ $item->member?->name ?? '—' }}</b>
                            <span>
                                {{ collect([$item->member?->code, $item->member?->designation?->name, $item->member?->mobile])->filter()->implode(' · ') ?: 'Employee' }}
                            </span>
                        </div>
                        @can('employee.edit')
                            <button class="uvx-btn" type="button" title="Remove from team"
                                wire:click="remove({{ $item->id }})"
                                wire:confirm="Remove {{ $item->member?->name }} from this team?">
                                <i class="fa fa-trash-o"></i>
                            </button>
                        @endcan
                    </div>
                @empty
                    <span class="uvx-tag mute">No team members yet</span>
                @endforelse
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="uvx-panel">
            <div class="uvx-ph">
                <span class="ic"><i class="fa fa-eye"></i></span>
                <div>
                    <h2>Lead Visibility</h2>
                    <div class="sub">What this account sees under Property Leads</div>
                </div>
            </div>
            <div class="uvx-pb">
                <div class="uvx-tgl {{ $seesAllLeads ? 'on' : '' }}">
                    <span class="ic"><i class="fa {{ $seesAllLeads ? 'fa-globe' : 'fa-lock' }}"></i></span>
                    <div class="tx">
                        @if ($seesAllLeads)
                            <b>All leads</b>
                            <span>Admin, or holds the "property lead.view all" permission</span>
                        @else
                            <b>Own + team leads only</b>
                            <span>Leads assigned to {{ $user->name }} and the members on the left</span>
                        @endif
                    </div>
                </div>
                <div class="uvx-hint">
                    <i class="fa fa-info-circle"></i>
                    <span>Grant "property lead.view all" through a role on the Access tab to let this employee see every lead.</span>
                </div>
            </div>
        </section>
    </div>

    @script
        <script>
            (() => {
                const el = document.getElementById('employee_team_member_select');
                if (!el || el.tomselect) return;
                const control = new TomSelect(el, {
                    persist: false,
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name', 'mobile', 'email'],
                    load(query, callback) {
                        fetch("{{ route('users::list') }}?type=employee&query=" + encodeURIComponent(query))
                            .then((response) => response.json())
                            .then((json) => callback((json.items || []).filter((item) => String(item.id) !== '{{ $user->id }}')))
                            .catch(() => callback());
                    },
                    onFocus() {
                        this.clearOptions();
                        this.load('');
                    },
                    onChange(value) {
                        $wire.set('team_member_id', value, false);
                    },
                    render: {
                        option: (item, escape) => `<div>${escape(item.name)}${item.mobile ? ` <small class="text-muted">${escape(item.mobile)}</small>` : ''}</div>`,
                        item: (item, escape) => `<div>${escape(item.name)}</div>`,
                    },
                });
                $wire.on('EmployeeTeam-Reset-Select', () => control.clear(true));
            })()
        </script>
    @endscript
</div>
