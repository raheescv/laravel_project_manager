<div class="apx">
    <x-property-appointment.premium />

    @php
        // Tokens are PHP strings, so the braces never reach Blade's parser.
        $tokenMap = [];
        foreach ($variables as $variable) {
            $tokenMap['{{ '.$variable.' }}'] = str_replace('_', ' ', ucfirst($variable));
        }
        $typeMeta = $types[$type] ?? [];
    @endphp

    <div class="apx-panel">
        <div class="apx-panel-h flex-wrap gap-2">
            <span class="apx-ico"><i class="fa fa-envelope-o"></i></span>
            <div class="flex-grow-1">
                <h4>Email templates</h4>
                <div class="sub">One active template per event &middot; the wording is entirely yours</div>
            </div>
            <select class="form-select form-select-sm" style="max-width:190px" wire:model.live="filterModule">
                <option value="">All modules</option>
                @foreach ($modules as $key => $meta)
                    <option value="{{ $key }}">{{ $meta['label'] ?? $key }}</option>
                @endforeach
            </select>
            @can('email template.create')
                <button type="button" class="apx-btn apx-btn-soft" wire:click="createDefaults"
                    wire:confirm="Create starter templates for every event that has none? Existing templates are left untouched.">
                    <i class="fa fa-magic"></i> Create defaults
                </button>
                <button type="button" class="apx-btn apx-btn-ghost" wire:click="newTemplate">
                    <i class="fa fa-plus"></i> New template
                </button>
            @endcan
        </div>

        <div class="row g-0">
            {{-- template rail --}}
            <div class="col-lg-3" style="border-inline-end:1px solid var(--border);background:var(--surface-2)">
                @forelse ($templates as $template)
                    <div class="tpl-i {{ $templateId == $template->id ? 'on' : '' }}"
                        wire:key="tpl-{{ $template->id }}" wire:click="select({{ $template->id }})">
                        <span class="apx-ico">
                            <i class="fa {{ $modules[$template->module]['icon'] ?? 'fa-file-text-o' }}"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="nm">{{ $template->name }}</div>
                            <div class="ty">{{ $template->module }} &middot; {{ $template->type }}</div>
                        </div>
                        <span class="apx-chip {{ $template->is_active ? 'chip-completed' : 'chip-cancelled' }}">
                            <span class="dot"></span> {{ $template->is_active ? 'Active' : 'Off' }}
                        </span>
                    </div>
                @empty
                    <div class="p-3">
                        <div class="apx-hint mb-2">
                            No templates yet. Start from the built-in wording, then edit it to suit your company.
                        </div>
                        @can('email template.create')
                            <button type="button" class="apx-btn apx-btn-soft apx-btn-block" wire:click="createDefaults">
                                <i class="fa fa-magic"></i> Create default templates
                            </button>
                        @endcan
                    </div>
                @endforelse
            </div>

            {{-- editor --}}
            <div class="col-lg-9 p-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <div style="font-size:14.5px;font-weight:800;letter-spacing:-.02em">
                            {{ $name ?: 'New template' }}
                        </div>
                        <div class="apx-hint">{{ $typeMeta['description'] ?? '' }}</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="tplActive" wire:model.live="is_active">
                        <label class="form-check-label" for="tplActive" style="font-size:11.5px;font-weight:700">Active</label>
                    </div>
                </div>

                <div class="apx-alert alert-info mb-3">
                    <i class="fa fa-info-circle lead"></i>
                    <div>
                        <div class="t">One active template per event</div>
                        <div class="s">
                            Activating this stands down any other template for the same module event. If none is active,
                            sending is blocked with a clear message rather than silently doing nothing.
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:11px;font-weight:700">Module</label>
                        <select class="form-select form-select-sm" wire:model.live="module">
                            @foreach ($modules as $key => $meta)
                                <option value="{{ $key }}">{{ $meta['label'] ?? $key }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:11px;font-weight:700">Event</label>
                        <select class="form-select form-select-sm" wire:model.live="type">
                            @foreach ($types as $key => $meta)
                                <option value="{{ $key }}">{{ $meta['label'] ?? $key }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:11px;font-weight:700">Template name</label>
                        <input type="text" class="form-control form-control-sm" wire:model="name"
                            placeholder="e.g. Invitation — English">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" style="font-size:11px;font-weight:700">Lang</label>
                        <select class="form-select form-select-sm" wire:model="language">
                            <option value="en">EN</option>
                            <option value="ar">AR</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:11px;font-weight:700">Reply-to</label>
                        <input type="email" class="form-control form-control-sm" wire:model="reply_to"
                            placeholder="you@example.com">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0" style="font-size:11px;font-weight:700">Subject</label>
                        @if ($hasDefault)
                            <button type="button" class="apx-btn apx-btn-ghost" style="padding:3px 8px"
                                wire:click="useDefaultWording"
                                wire:confirm="Replace the subject and body with the built-in starter wording? Nothing is saved until you press Save.">
                                <i class="fa fa-magic"></i> Use starter wording
                            </button>
                        @endif
                    </div>
                    <input type="text" id="emailTemplateSubject" class="form-control form-control-sm mt-1" wire:model="subject">
                </div>

                <div class="mb-3">
                    <div class="apx-sect">Available variables &mdash; click to add to the subject</div>
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
                        The same variables can be dropped into the body from the editor's own Insert row.
                        Saving rejects any variable not in this list, so a typo never reaches a customer.
                    </div>
                </div>

                <x-rich-text-editor
                    wire:model="body"
                    label="Body"
                    :tokens="$tokenMap"
                    :height="260"
                    :rtl="$language === 'ar'"
                    help="Write the wording your company wants. Nothing here ships with the software." />

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-3"
                    style="border-top:1px solid var(--border)">
                    <div>
                        @if ($templateId)
                            @can('email template.delete')
                                <button type="button" class="apx-btn apx-btn-danger" wire:click="delete({{ $templateId }})"
                                    wire:confirm="Delete this template?">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            @endcan
                        @endif
                    </div>
                    @can($templateId ? 'email template.edit' : 'email template.create')
                        <button type="button" class="apx-btn apx-btn-primary" wire:click="save">
                            <i class="fa fa-check"></i> Save template
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
