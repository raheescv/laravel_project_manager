@php
    use App\Models\Guardian;
    use App\Models\StudentDetail;

    $primary = $guardians->first();
    $initialsOf = function (string $name): string {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    };
    $initials = $initialsOf($account->name);
    $bills = (int) ($monthSales->bills ?? 0);
    $age = $account->dob ? \Illuminate\Support\Carbon::parse($account->dob)->age : null;
    $canSpend = max(0, $balance + $overdraftLimit);
    $overdraftUsed = $balance < 0 && $overdraftLimit > 0 ? min(100, abs($balance) / $overdraftLimit * 100) : 0;
    $tabs = [
        'profile' => ['fa-user', 'Profile & Parents', $guardians->count()],
        'card' => ['fa-credit-card', 'Card', null],
        'statement' => ['fa-list-alt', 'Statement', null],
        'purchases' => ['fa-shopping-cart', 'Purchases', $purchaseCount],
        'topups' => ['fa-plus-circle', 'Top-ups', $topupCount],
    ];
@endphp

<div class="svx">
    <x-student.view-premium />

    {{-- Hero: who the student is, and their card --}}
    <div class="sheet hero mb-3 rise" style="--i:0">
        <div class="row g-3 align-items-center">
            <div class="col-lg">
                <div class="d-flex gap-3 align-items-start">
                    <div class="av">
                        @if ($account->image)
                            <img src="{{ $account->image_url }}" alt="">
                        @else
                            {{ $initials }}
                        @endif
                        <span @class(['dot', 'bg-success' => $detail?->status === 'active', 'bg-secondary' => $detail?->status !== 'active'])>
                            <i class="fa {{ $detail?->status === 'active' ? 'fa-check' : 'fa-minus' }}"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <a href="{{ route('student::index') }}" class="eyebrow" title="Back to students"><i class="fa fa-graduation-cap"></i>Student @if ($detail?->admission_no)<b>· {{ $detail->admission_no }}</b>@endif</a>
                        <div class="nm">{{ $account->name }}</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span @class(['chip', 'ok' => $detail?->status === 'active', 'off' => $detail?->status !== 'active'])>
                                <i class="fa fa-circle"></i>{{ StudentDetail::STATUSES[$detail?->status] ?? $detail?->status }}
                            </span>
                            @if ($detail?->classLabel())
                                <span class="chip"><i class="fa fa-graduation-cap"></i>{{ $detail->classLabel() }}</span>
                            @endif
                            @if ($detail?->gender)
                                <span class="chip"><i class="fa {{ $detail->gender === 'male' ? 'fa-mars' : 'fa-venus' }}"></i>{{ StudentDetail::GENDERS[$detail->gender] ?? $detail->gender }}</span>
                            @endif
                            @if ($account->dob)
                                <span class="chip"><i class="fa fa-birthday-cake"></i>{{ systemDate($account->dob) }} · {{ $age }} yrs</span>
                            @endif
                        </div>
                        <div class="pline">
                            @if ($primary)
                                <i class="fa fa-users"></i>
                                <span><b>{{ $primary->name }}</b> ({{ Guardian::RELATIONS[$primary->pivot->relation] ?? $primary->pivot->relation }}{{ $primary->pivot->is_primary ? ', main contact' : '' }})</span>
                                <a href="tel:{{ $primary->mobile }}"><i class="fa fa-phone me-1"></i>{{ $primary->mobile }}</a>
                            @else
                                <i class="fa fa-users"></i><span>No parents linked yet</span>
                            @endif
                            @can('student.edit')
                                <a href="{{ route('student::edit', $account->id) }}" class="btn btn-primary btn-sm hbtn ms-auto"><i class="fa fa-pencil me-1"></i>Edit</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-auto d-flex justify-content-center justify-content-lg-end">
                <x-student.id-card :account="$account" :detail="$detail" :balance="$balance">
                    <button type="button" class="btn btn-sm btn-primary" wire:click="selectTab('card')"><i class="fa fa-link me-1"></i>Link a card</button>
                </x-student.id-card>
            </div>
        </div>
    </div>

    {{-- KPI tiles --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="sheet kpi rise" style="--i:1">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="lab">Card balance</div>
                    <div class="ic {{ $balance < 0 ? 'bg-danger-subtle text-danger-emphasis' : 'bg-success-subtle text-success-emphasis' }}"><i class="fa fa-credit-card"></i></div>
                </div>
                <div class="val"><span @class(['text-danger-emphasis' => $balance < 0])>{{ currency($balance) }}</span></div>
                <div class="ft">
                    @if ($balance < 0)
                        <span class="text-danger-emphasis fw-medium"><i class="fa fa-exclamation-triangle me-1"></i>In overdraft</span>
                    @else
                        All branches, from the ledger
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="sheet kpi rise" style="--i:2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="lab">Can spend now</div>
                    <div class="ic bg-primary-subtle text-primary-emphasis"><i class="fa fa-bolt"></i></div>
                </div>
                <div class="val">{{ currency($canSpend) }}</div>
                @if ($overdraftLimit > 0 && $canSpend > 0)
                    <div class="meter" title="Balance and overdraft allowance">
                        @if ($balance < 0)
                            <i class="bg-danger" style="width: {{ $overdraftUsed }}%"></i>
                            <i class="bg-warning opacity-50" style="width: {{ 100 - $overdraftUsed }}%"></i>
                        @else
                            <i class="bg-success" style="width: {{ $balance / $canSpend * 100 }}%"></i>
                            <i class="bg-warning opacity-50" style="width: {{ $overdraftLimit / $canSpend * 100 }}%"></i>
                        @endif
                    </div>
                @endif
                <div class="ft">
                    @if ($overdraftLimit <= 0)
                        No overdraft allowed
                    @elseif ($balance < 0)
                        Overdraft {{ round($overdraftUsed) }}% used of {{ currency($overdraftLimit) }}
                    @else
                        Includes {{ currency($overdraftLimit) }} overdraft
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="sheet kpi rise" style="--i:3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="lab">This month</div>
                    <div class="ic bg-info-subtle text-info-emphasis"><i class="fa fa-shopping-cart"></i></div>
                </div>
                <div class="val">{{ currency($monthSales->total ?? 0) }}</div>
                <div class="ft">{{ $bills }} {{ $bills === 1 ? 'purchase' : 'purchases' }}@if ($bills) · avg {{ currency($monthSales->total / $bills) }}@endif</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="sheet kpi rise" style="--i:4">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="lab">Last top-up</div>
                    <div class="ic bg-warning-subtle text-warning-emphasis"><i class="fa fa-plus-circle"></i></div>
                </div>
                @if ($lastTopup)
                    <div class="val">{{ currency($lastTopup->credit) }}</div>
                    <div class="ft">{{ systemDate($lastTopup->date) }}{{ $lastTopupBy ? ' · ' . $lastTopupBy : '' }}</div>
                @else
                    <div class="val text-body-secondary">-</div>
                    <div class="ft">No top-ups yet</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="sheet rise" style="--i:5">
        <div class="rail" role="tablist">
            @foreach ($tabs as $tab => [$icon, $label, $count])
                @continue($tab === 'topups' && $count === null)
                <button type="button" role="tab" aria-selected="{{ $selected_tab === $tab ? 'true' : 'false' }}" @class(['on' => $selected_tab === $tab]) wire:click="selectTab('{{ $tab }}')">
                    <i class="fa {{ $icon }}"></i>{{ $label }}
                    @if ($count)
                        <span class="cnt">{{ $count }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div @class(['pane', 'd-none' => $selected_tab !== 'profile'])>
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="ph">
                        <span class="pi"><i class="fa fa-user"></i></span>
                        <div>
                            <h6>Details</h6>
                            <div class="hint">School record</div>
                        </div>
                    </div>
                    @php
                        $fields = [
                            ['fa-venus-mars', 'Gender', StudentDetail::GENDERS[$detail?->gender] ?? null],
                            ['fa-birthday-cake', 'Date of birth', $account->dob ? systemDate($account->dob) : null],
                            ['fa-credit-card', 'QID', $account->id_no],
                            ['fa-globe', 'Nationality', $account->nationality],
                            ['fa-mobile', 'Mobile', $account->mobile],
                            ['fa-envelope-o', 'Email', $account->email],
                        ];
                    @endphp
                    <div class="row g-2">
                        @foreach ($fields as [$icon, $label, $value])
                            <div class="col-sm-6">
                                <div class="fld">
                                    <div class="k"><i class="fa {{ $icon }}"></i>{{ $label }}</div>
                                    <div @class(['v', 'none' => blank($value), 'mono' => $label === 'QID' && filled($value)])>{{ filled($value) ? $value : 'Not recorded' }}</div>
                                </div>
                            </div>
                        @endforeach
                        @if (filled($account->description))
                            <div class="col-12">
                                <div class="fld note">
                                    <div class="k"><i class="fa fa-exclamation-triangle"></i>Notes</div>
                                    <div class="v">{{ $account->description }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="ph">
                        <span class="pi"><i class="fa fa-users"></i></span>
                        <div>
                            <h6>Parents</h6>
                            <div class="hint">Portal access and contact</div>
                        </div>
                    </div>
                    @forelse ($guardians as $guardian)
                        @php
                            $relation = Guardian::RELATIONS[$guardian->pivot->relation] ?? $guardian->pivot->relation;
                            $guardianInitials = $initialsOf($guardian->name);
                            if ($guardian->isPendingInvite()) {
                                $steps = $guardian->invited_at ? ['done', 'now', ''] : ['now', '', ''];
                                $meta = $guardian->invited_at ? 'Invited ' . $guardian->invited_at->diffForHumans() . ', no password yet' : 'Not invited yet';
                            } else {
                                $steps = ['done', 'done', $guardian->last_login_at ? 'done' : 'now'];
                                $meta = $guardian->last_login_at ? 'Last login ' . $guardian->last_login_at->diffForHumans() : 'Has not signed in yet';
                            }
                        @endphp
                        <div class="gd" wire:key="view-guardian-{{ $guardian->id }}">
                            <div class="d-flex gap-3 align-items-start flex-wrap">
                                <div class="gav">{{ $guardianInitials }}</div>
                                <div class="flex-grow-1 min-w-0">
                                    <div>
                                        <span class="gn">{{ $guardian->name }}</span><span class="rel">{{ $relation }}</span>
                                        @if ($guardian->pivot->is_primary)
                                            <span class="pri"><i class="fa fa-star me-1"></i>Main contact</span>
                                        @endif
                                    </div>
                                    <div class="ct">
                                        <span><i class="fa fa-phone me-1"></i>{{ $guardian->mobile }}</span>
                                        @if ($guardian->email)
                                            <span><i class="fa fa-envelope-o me-1"></i>{{ $guardian->email }}</span>
                                        @endif
                                    </div>
                                    @if (!$guardian->isActive())
                                        <div class="trk off"><i class="fa fa-ban me-1"></i>Portal login disabled</div>
                                    @else
                                        <div class="trk">
                                            <span class="st {{ $steps[0] }}"><i class="fa fa-check"></i>Invited</span>
                                            <span @class(['bar', 'done' => $steps[1] === 'done'])></span>
                                            <span class="st {{ $steps[1] }}"><i class="fa fa-check"></i>Password set</span>
                                            <span @class(['bar', 'done' => $steps[2] === 'done'])></span>
                                            <span class="st {{ $steps[2] }}"><i class="fa fa-check"></i>Signed in</span>
                                            <span class="meta">{{ $meta }}</span>
                                        </div>
                                    @endif
                                </div>
                                @can('student guardian.invite')
                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="invite({{ $guardian->id }})" wire:loading.attr="disabled" wire:target="invite({{ $guardian->id }})">
                                        <i class="fa fa-paper-plane me-1"></i>{{ $guardian->isPendingInvite() ? 'Send invite' : 'Send password reset' }}
                                    </button>
                                @endcan
                            </div>
                        </div>
                        @if ($invite_link && $invite_guardian_id === $guardian->id)
                            <div class="lnk" x-data="{ copied: false }">
                                <i class="fa fa-info-circle me-1"></i>Set-password link (valid 7 days), if the parent did not receive the message:
                                <div class="input-group input-group-sm mt-2">
                                    <input type="text" class="form-control font-monospace" value="{{ $invite_link }}" readonly x-ref="link" onclick="this.select()" aria-label="Set-password link">
                                    <button type="button" class="btn btn-outline-primary bg-body" x-on:click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)">
                                        <i class="fa" :class="copied ? 'fa-check' : 'fa-files-o'"></i> <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="empty">
                            <i class="fa fa-users"></i>
                            No parents linked, so nobody can top up the card online.
                            @can('student.edit')
                                <div class="mt-2"><a href="{{ route('student::edit', $account->id) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus me-1"></i>Add a parent</a></div>
                            @endcan
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        @if (isset($loaded_tabs['card']))
            <div @class(['pane', 'd-none' => $selected_tab !== 'card'])>
                @livewire('student.card-tab', ['account_id' => $account->id], key('card-' . $account->id))
            </div>
        @endif
        @if (isset($loaded_tabs['statement']))
            <div @class(['pane', 'd-none' => $selected_tab !== 'statement'])>
                @livewire('student.statement', ['account_id' => $account->id], key('statement-' . $account->id))
            </div>
        @endif
        @if (isset($loaded_tabs['purchases']))
            <div @class(['pane', 'd-none' => $selected_tab !== 'purchases'])>
                @livewire('student.purchases', ['account_id' => $account->id], key('purchases-' . $account->id))
            </div>
        @endif
        @if (isset($loaded_tabs['topups']))
            <div @class(['pane', 'd-none' => $selected_tab !== 'topups'])>
                @livewire('student.topups', ['account_id' => $account->id], key('topups-' . $account->id))
            </div>
        @endif
    </div>
</div>
