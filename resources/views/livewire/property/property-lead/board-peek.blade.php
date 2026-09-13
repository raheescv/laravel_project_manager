<aside class="lbx-peek" aria-label="Lead details" wire:loading.class="is-loading">
    @if (! $lead)
        <div class="pk-empty">
            <i class="fa fa-hand-o-up"></i>
            <b>No lead selected</b>
            <span>Click a card to see contact details, notes and history.</span>
        </div>
    @else
        @php
            $stageKey = \App\Support\LeadPipeline::stageOf($status);
            $dial = preg_replace('/[^\d+]/', '', (string) $lead->mobile);
            $digits = preg_replace('/\D/', '', (string) $lead->mobile);
        @endphp
        <div class="pk-h tn tn-{{ \App\Support\LeadPipeline::tone($status) }}">
            <button type="button" class="icon-btn pk-close" wire:click="close" title="Close"><i class="fa fa-times"></i></button>
            <div class="pk-id">
                <span class="av pk-av" style="--h: {{ \App\Support\LeadPipeline::hue($lead->name) }}">{{ \App\Support\LeadPipeline::initials($lead->name) }}</span>
                <div class="pk-who">
                    <div class="pk-name">{{ $lead->name }}</div>
                    <div class="pk-sub">
                        <span>#{{ $lead->id }}</span>
                        <span class="ty {{ $lead->type === 'Rentout' ? 'ty-rent' : 'ty-sales' }}">{{ $lead->type === 'Rentout' ? 'Rent out' : 'Sales' }}</span>
                        @if ($lead->company_name)
                            <span>{{ $lead->company_name }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="pk-cur">
                <span class="dot"></span>
                @if ($status === \App\Support\LeadPipeline::UNMAPPED)
                    Unknown status: {{ $lead->status !== '' ? $lead->status : '(blank)' }}
                @else
                    {{ $status }}
                @endif
                @if ($stageKey)
                    <small>· {{ $stages[$stageKey]['name'] }}</small>
                @endif
            </div>
            <div class="pk-acts">
                @if ($dial)
                    <a class="pk-act" href="tel:{{ $dial }}"><i class="fa fa-phone"></i>Call</a>
                    <a class="pk-act wa" href="https://wa.me/{{ $digits }}" target="_blank" rel="noopener"><i class="fa fa-whatsapp"></i>WhatsApp</a>
                @else
                    <span class="pk-act is-off" title="No mobile number"><i class="fa fa-phone"></i>Call</span>
                    <span class="pk-act is-off" title="No mobile number"><i class="fa fa-whatsapp"></i>WhatsApp</span>
                @endif
                @if ($lead->email)
                    <a class="pk-act" href="mailto:{{ $lead->email }}"><i class="fa fa-envelope"></i>Email</a>
                @else
                    <span class="pk-act is-off" title="No email address"><i class="fa fa-envelope"></i>Email</span>
                @endif
                <a class="pk-act" href="{{ route('property::lead::edit', $lead->id) }}"><i class="fa fa-external-link"></i>Open</a>
            </div>
        </div>

        <div class="pk-body">
            @if ($canEdit)
                <div class="pk-sec">
                    <div class="pk-lbl">Move to <span>Saves on tap</span></div>
                    <div class="stpick">
                        @foreach ($stages as $stage)
                            <div class="stpick-row">
                                <small>{{ $stage['name'] }}</small>
                                @foreach ($stage['statuses'] as $option)
                                    @if ($option === $status)
                                        <span class="stc on tn tn-{{ \App\Support\LeadPipeline::tone($option) }}"><span class="dot"></span>{{ $option }}</span>
                                    @else
                                        <button type="button" class="stc tn tn-{{ \App\Support\LeadPipeline::tone($option) }}" wire:click="$parent.moveLead({{ $lead->id }}, @js($option))">
                                            <span class="dot"></span>{{ $option }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="pk-sec">
                <div class="pk-lbl">Details</div>
                <dl class="kv">
                    <dt>Mobile</dt>
                    <dd>{{ $lead->mobile ?: '—' }}</dd>
                    <dt>Email</dt>
                    <dd title="{{ $lead->email }}">{{ $lead->email ?: '—' }}</dd>
                    <dt>Project</dt>
                    <dd>{{ $lead->group?->name ?? '—' }}</dd>
                    <dt>Source</dt>
                    <dd>
                        @if ($lead->source)
                            <i class="fa {{ \App\Support\LeadPipeline::sourceIcon($lead->source) }}"></i>{{ $lead->source }}
                        @else
                            —
                        @endif
                    </dd>
                    <dt>Assigned to</dt>
                    <dd>{{ $lead->assignee?->name ?? 'Unassigned' }}{{ $lead->assign_date ? ' · '.$lead->assign_date->format('M j') : '' }}</dd>
                    <dt>Meeting</dt>
                    <dd>
                        @if ($lead->meeting_date)
                            {{ $lead->meeting_date->format('M j, Y') }}{{ $lead->meeting_time ? ' · '.\Carbon\Carbon::parse($lead->meeting_time)->format('g:i A') : '' }}{{ $lead->location ? ' · '.$lead->location : '' }}
                        @else
                            <span class="muted">Not scheduled</span>
                        @endif
                    </dd>
                    <dt>Nationality</dt>
                    <dd>{{ $lead->country?->name ?? ($lead->nationality ?: '—') }}</dd>
                    <dt>Created</dt>
                    <dd>{{ $lead->created_at?->format('M j, Y') }}</dd>
                </dl>
            </div>

            <div class="pk-sec">
                <div class="pk-lbl">Notes <span class="badge">{{ count($notes) }}</span></div>
                @if ($canEdit)
                    <form class="addnote" wire:submit="addNote">
                        <textarea rows="2" wire:model="note" placeholder="Add a note, e.g. called, wants a 2BR sea view"></textarea>
                        <div class="addnote-f">
                            <input type="date" wire:model="noteDate" aria-label="Note date">
                            <button type="submit" class="btn-acc" wire:loading.attr="disabled" wire:target="addNote"><i class="fa fa-plus"></i> Add note</button>
                        </div>
                    </form>
                @endif
                @if (count($notes))
                    <div class="notes">
                        @foreach ($notes as $item)
                            <div class="note">
                                <time>{{ rescue(fn () => \Carbon\Carbon::parse($item['date'] ?? '')->format('M j, Y'), $item['date'] ?? '', false) }}{{ ! empty($item['user']) ? ' · '.$item['user'] : '' }}</time>
                                <p>{{ $item['note'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="muted">No notes yet.</div>
                @endif
            </div>

            @if (count($activity))
                <div class="pk-sec">
                    <div class="pk-lbl">Activity</div>
                    <ul class="log">
                        @foreach ($activity as $entry)
                            <li>
                                <i class="fa {{ $entry['icon'] }}"></i>
                                <div>
                                    {{ $entry['label'] }}
                                    @if ($entry['to'])
                                        <b>{{ $entry['from'] ?: '—' }}</b> → <b>{{ $entry['to'] }}</b>
                                    @endif
                                    <small>{{ $entry['user'] }} · {{ $entry['at'] }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</aside>
