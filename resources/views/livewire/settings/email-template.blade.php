<div class="apx etx">
    <x-property-appointment.premium />
    <x-email-template.premium />

    @php
        // Tokens are PHP strings, so the braces never reach Blade's parser.
        $tokenMap = [];
        foreach ($variables as $variable) {
            $tokenMap['{{ '.$variable.' }}'] = str_replace('_', ' ', ucfirst($variable));
        }
        $typeMeta = $types[$type] ?? [];

        // Branding for the preview shell — the same sources the real
        // Editorial wrapper (mail/appointment/template) resolves at send time.
        $companyName = tenant_cache('company_name', '') ?: config('app.name');
        $companyLogo = tenant_cache('logo', '') ?: '';
        $companyPhone = tenant_cache('mobile', '') ?: '';
        $companyEmail = tenant_cache('email', '') ?: '';
        $accent = \App\Services\EmailTemplateRenderer::accent();
    @endphp

    <div class="etx-shell" x-data="etxConsole()">

        {{-- Sample data for the client-side merge. An attribute (not a script
             tag) so Livewire reliably morphs it when the module/type changes. --}}
        <div hidden data-etx-payload="{{ json_encode(['samples' => $samples, 'variables' => array_values($variables), 'raw' => $rawHtmlVariables]) }}"></div>

        {{-- ── console bar ─────────────────────────────────────────── --}}
        <div class="etx-bar">
            <a href="{{ route('settings::index') }}" class="apx-btn apx-btn-ghost" title="Back to Settings"
                style="padding:7px 9px"><i class="fa fa-arrow-left"></i></a>
            <span class="apx-ico"><i class="fa fa-envelope-o"></i></span>
            <div>
                <h4>Email templates</h4>
                <div class="sub">One active template per event &middot; edit in the inspector, the customer's inbox updates live</div>
            </div>
            <div class="etx-sp"></div>
            @can('email template.create')
                <button type="button" class="apx-btn apx-btn-ghost" wire:click="newTemplate">
                    <i class="fa fa-plus"></i> New
                </button>
            @endcan
            @can($templateId ? 'email template.edit' : 'email template.create')
                <button type="button" class="apx-btn apx-btn-primary" wire:click="save">
                    <i class="fa fa-check"></i> Save
                </button>
            @endcan
        </div>

        <div class="etx-grid">

            {{-- ── template rail ───────────────────────────────────── --}}
            <div class="etx-rail" x-data="{ q: '' }">
                <div class="etx-rail-search">
                    <i class="fa fa-search"></i>
                    <input type="text" placeholder="Find a template…" x-model="q">
                </div>
                <div class="etx-rail-list">
                    @forelse ($templates->groupBy('module') as $moduleKey => $group)
                        <div class="etx-rail-g" wire:key="tplg-{{ $moduleKey }}">
                            <i class="fa {{ $modules[$moduleKey]['icon'] ?? 'fa-file-text-o' }}"></i>
                            {{ \App\Models\EmailTemplate::moduleLabel($moduleKey) }}
                        </div>
                        @foreach ($group as $template)
                            <button type="button" class="etx-tpl {{ $templateId == $template->id ? 'on' : '' }}"
                                wire:key="tpl-{{ $template->id }}" wire:click="select({{ $template->id }})"
                                data-hay="{{ strtolower($template->name.' '.$template->type.' '.$template->language) }}"
                                x-show="q === '' || ($el.dataset.hay || '').indexOf(q.toLowerCase()) !== -1">
                                <span class="fg">
                                    <span class="nm d-block">{{ $template->name }}</span>
                                    <span class="ty d-block">{{ $template->typeLabel() }} &middot; {{ strtoupper($template->language) }}</span>
                                </span>
                                <span class="apx-chip {{ $template->is_active ? 'chip-completed' : 'chip-cancelled' }}">
                                    <span class="dot"></span> {{ $template->is_active ? 'Active' : 'Off' }}
                                </span>
                            </button>
                        @endforeach
                    @empty
                        <div class="etx-rail-empty">
                            <div class="apx-hint" style="margin:0">
                                No templates yet. Start from the built-in wording below, then edit it to
                                suit your company — nothing is sent until a template is active.
                            </div>
                        </div>
                    @endforelse
                </div>
                @can('email template.create')
                    <div class="etx-rail-foot">
                        <button type="button" class="apx-btn apx-btn-soft" wire:click="createDefaults"
                            wire:confirm="Create starter templates for every event that has none? Existing templates are left untouched.">
                            <i class="fa fa-magic"></i> Create defaults
                        </button>
                    </div>
                @endcan
            </div>

            {{-- ── live preview stage ──────────────────────────────── --}}
            <div class="etx-pv">
                <div class="etx-pv-bar">
                    <span class="t"><span class="live"></span> Live preview &middot; sample data</span>
                    <div class="etx-pv-seg">
                        <button type="button" :class="device === 'desk' && 'on'" title="Desktop width"
                            x-on:click="device = 'desk'"><i class="fa fa-desktop"></i></button>
                        <button type="button" :class="device === 'mob' && 'on'" title="Phone width"
                            x-on:click="device = 'mob'"><i class="fa fa-mobile"></i></button>
                    </div>
                </div>
                <div class="etx-pv-meta">
                    <div class="s" x-ref="pvSubject">&nbsp;</div>
                    <div>
                        From <b>{{ $companyName }}</b>
                        <span x-show="$wire.reply_to" x-cloak> &middot; reply-to <span x-text="$wire.reply_to"></span></span>
                        &middot; to sample.customer@email.com
                    </div>
                </div>
                <div class="etx-pv-canvas" :class="device === 'mob' && 'is-mob'">
                    <div class="etx-mail" style="--em-acc: {{ $accent }}">
                        <div class="etx-mail-hd">
                            @if (filled($companyLogo))
                                <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
                            @else
                                <span class="lg">{{ \Illuminate\Support\Str::of($companyName)->substr(0, 1)->upper() }}</span>
                            @endif
                            <div class="co">{{ $companyName }}</div>
                            <div class="rule"></div>
                        </div>
                        <div class="etx-mail-bd" x-ref="pvBody" :dir="$wire.language === 'ar' ? 'rtl' : 'ltr'"></div>
                        <div class="etx-mail-ft">
                            <b>{{ $companyName }}</b>
                            @if (filled($companyPhone)) &middot; {{ $companyPhone }} @endif
                            @if (filled($companyEmail)) &middot; {{ $companyEmail }} @endif
                            <br>You received this because you enquired about a property with us.
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── inspector ───────────────────────────────────────── --}}
            <div class="etx-insp">
                <div class="etx-insp-hd">
                    <i class="fa fa-sliders"></i>
                    <div class="t">{{ $name ?: 'New template' }}</div>
                    <label class="etx-sw {{ $is_active ? 'on' : '' }}"
                        title="One active template per event — activating this stands down any other for the same event.">
                        <input type="checkbox" wire:model.live="is_active">
                        <span class="tr"></span> Active
                    </label>
                </div>

                <div class="etx-acc" x-data="{ open: true }">
                    <button type="button" class="ah" x-on:click="open = ! open">
                        Identity <i class="fa" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <div class="ab" x-show="open">
                        <div class="row g-2">
                            <div class="col-7">
                                <label class="form-label" style="font-size:11px;font-weight:700">Module</label>
                                <select class="form-select form-select-sm" wire:model.live="module">
                                    @foreach ($modules as $key => $meta)
                                        <option value="{{ $key }}">{{ $meta['label'] ?? $key }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-5">
                                <label class="form-label" style="font-size:11px;font-weight:700">Language</label>
                                <select class="form-select form-select-sm" wire:model.live="language">
                                    <option value="en">English</option>
                                    <option value="ar">Arabic</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" style="font-size:11px;font-weight:700">Event</label>
                                <select class="form-select form-select-sm" wire:model.live="type">
                                    @foreach ($types as $key => $meta)
                                        <option value="{{ $key }}">{{ $meta['label'] ?? $key }}</option>
                                    @endforeach
                                </select>
                                @if (! empty($typeMeta['description']))
                                    <div class="apx-hint">{{ $typeMeta['description'] }}</div>
                                @endif
                            </div>
                            <div class="col-7">
                                <label class="form-label" style="font-size:11px;font-weight:700">Template name</label>
                                <input type="text" class="form-control form-control-sm" wire:model="name"
                                    placeholder="e.g. Invitation — English">
                            </div>
                            <div class="col-5">
                                <label class="form-label" style="font-size:11px;font-weight:700">Reply-to</label>
                                <input type="email" class="form-control form-control-sm" wire:model="reply_to"
                                    placeholder="you@example.com">
                            </div>
                        </div>
                        <div class="apx-hint">
                            One active template per event. If none is active, sending is blocked with a clear
                            message rather than silently doing nothing.
                        </div>
                    </div>
                </div>

                <div class="etx-acc" x-data="{ open: true }">
                    <button type="button" class="ah" x-on:click="open = ! open">
                        Subject <i class="fa" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <div class="ab" x-show="open">
                        <input type="text" id="emailTemplateSubject" class="form-control form-control-sm"
                            wire:model="subject" placeholder="e.g. Book your appointment for Unit @{{ unit_number }}">
                    </div>
                </div>

                <div class="etx-acc" x-data="{ open: true }">
                    <button type="button" class="ah" x-on:click="open = ! open">
                        Body <i class="fa" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <div class="ab" x-show="open">
                        {{-- Keyed so switching module/event rebuilds the editor with
                             that event's Insert tokens (the editor is wire:ignore). --}}
                        <div wire:key="rte-{{ $module }}-{{ $type }}">
                            <x-rich-text-editor wire:model="body" :tokens="$tokenMap" :height="260"
                                :rtl="$language === 'ar'"
                                placeholder="Write the wording your company wants. Nothing here ships with the software." />
                        </div>
                    </div>
                </div>

                <div class="etx-acc" x-data="{ open: true }">
                    <button type="button" class="ah" x-on:click="open = ! open">
                        Variables <i class="fa" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <div class="ab" x-show="open">
                        <div class="d-flex gap-1 flex-wrap">
                            @foreach (array_keys($tokenMap) as $token)
                                <span class="apx-var" role="button" title="Add to the subject line"
                                    x-on:click="
                                        const el = document.getElementById('emailTemplateSubject');
                                        el.value = (el.value ? el.value + ' ' : '') + @js($token);
                                        el.dispatchEvent(new Event('input'));
                                        el.focus();
                                    ">{{ $token }}</span>
                            @endforeach
                        </div>
                        <div class="apx-hint">
                            Click to add to the subject; the body has its own Insert row in the editor.
                            Saving rejects any variable not in this list, so a typo never reaches a customer.
                        </div>
                    </div>
                </div>

                <div class="etx-insp-ft">
                    @if ($templateId)
                        @can('email template.delete')
                            <button type="button" class="apx-btn apx-btn-danger" wire:click="delete({{ $templateId }})"
                                wire:confirm="Delete this template?">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        @endcan
                    @endif
                    <div class="etx-sp"></div>
                    @if ($hasDefault)
                        <button type="button" class="apx-btn apx-btn-ghost" wire:click="useDefaultWording"
                            wire:confirm="Replace the subject and body with the built-in starter wording? Nothing is saved until you press Save.">
                            <i class="fa fa-magic"></i> Starter wording
                        </button>
                    @endif
                    @can($templateId ? 'email template.edit' : 'email template.create')
                        <button type="button" class="apx-btn apx-btn-primary" wire:click="save">
                            <i class="fa fa-check"></i> Save
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        {{-- ── status bar ──────────────────────────────────────────── --}}
        <div class="etx-status">
            <span>module <b>{{ $module }}</b></span>
            <span>event <b>{{ $type }}</b></span>
            <span>lang <b>{{ strtoupper($language) }}</b></span>
            <span>{{ $templateId ? '#'.$templateId : 'unsaved draft' }}</span>
            <div class="etx-sp"></div>
            <span class="bad" x-show="unknown.length" x-cloak>
                <i class="fa fa-exclamation-triangle"></i> unknown: <span x-text="unknown.join(', ')"></span>
            </span>
            <span class="ok" x-show="! unknown.length"><i class="fa fa-check"></i> all variables valid</span>
        </div>
    </div>

    @push('scripts')
        <script>
            /**
             * Client side of the live preview: merges @{{ variable }} tokens in the
             * subject/body with the sample values the server provides, entirely in the
             * browser so typing never fires a request. Mirrors EmailTemplateRenderer:
             * values are HTML-escaped except the keys listed in `raw`, and anything
             * referencing an unknown variable is flagged in the status bar — the same
             * check that would reject the save.
             */
            window.etxConsole = function () {
                return {
                    device: 'desk',
                    unknown: [],

                    init() {
                        this.$wire.$watch('subject', () => this.paint());
                        this.$wire.$watch('body', () => this.paint());

                        // Fired by the server after select/new/starter-wording — repaint
                        // once the morph has landed. Also repaints the wire:ignore editor's
                        // deferred commits that $watch already catches.
                        window.addEventListener('rich-text:refresh', () => this.$nextTick(() => this.paint()));

                        // $refs are not registered until the tree has been walked.
                        this.$nextTick(() => this.paint());
                    },

                    payload() {
                        try {
                            return JSON.parse(this.$root.querySelector('[data-etx-payload]').dataset.etxPayload || '{}');
                        } catch (e) {
                            return {};
                        }
                    },

                    esc(value) {
                        const holder = document.createElement('div');
                        holder.textContent = value;
                        return holder.innerHTML;
                    },

                    merge(text, escape) {
                        const data = this.payload(), samples = data.samples || {}, raw = data.raw || [];
                        return String(text ?? '').replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/gi, (match, key) => {
                            if (! (key in samples)) return match;
                            const value = String(samples[key]);
                            return escape && ! raw.includes(key) ? this.esc(value) : value;
                        });
                    },

                    paint() {
                        if (! this.$refs.pvSubject) return;
                        const subject = this.$wire.subject ?? '', body = this.$wire.body ?? '';

                        this.$refs.pvSubject.textContent =
                            this.merge(subject, false).replace(/<[^>]*>/g, '') || '(no subject yet)';
                        this.$refs.pvBody.innerHTML = this.merge(body, true)
                            || '<p class="etx-mail-empty">Start writing in the inspector — the customer\'s email appears here.</p>';

                        const known = this.payload().variables || [];
                        const used = [...(subject + ' ' + body).matchAll(/\{\{\s*([a-z0-9_]+)\s*\}\}/gi)].map((m) => m[1]);
                        this.unknown = [...new Set(used.filter((key) => ! known.includes(key)))];
                    },
                };
            };
        </script>
    @endpush
</div>
