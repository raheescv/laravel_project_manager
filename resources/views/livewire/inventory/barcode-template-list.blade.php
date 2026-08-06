<div class="bcx">
    <div class="bcx-shell">
        <div class="bcx-bar">
            <i class="fa fa-barcode" style="color:var(--bcx-brand)"></i>
            <div>
                <div class="bcx-bar__title">Barcode Design Templates</div>
                <div class="bcx-bar__sub">{{ count($templates) }} template{{ count($templates) === 1 ? '' : 's' }} · the default is what prints when no template is named</div>
            </div>

            <div class="bcx-spacer"></div>

            <input
                type="text"
                class="bcx-input"
                style="flex:0 0 240px"
                wire:model="newTemplateName"
                wire:keydown.enter="createTemplate"
                placeholder="New template name">
            <select class="bcx-input" style="flex:0 0 200px" wire:model="newTemplateType">
                @foreach ($availableTypes as $typeKey => $type)
                    <option value="{{ $typeKey }}">{{ $type['label'] }}</option>
                @endforeach
            </select>
            <button type="button" class="bcx-btn bcx-btn--primary" wire:click="createTemplate">
                <i class="fa fa-plus"></i> Create
            </button>
        </div>

        <div class="bcx-scroll">
            <table class="bcx-table">
                <thead>
                    <tr>
                        <th>Template</th>
                        <th>Type</th>
                        <th>Label size</th>
                        <th>Default print</th>
                        <th style="text-align:end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($templates as $templateKey => $template)
                        @php
                            $templateType = $template['type'] ?? 'standard';
                            $isDefault = $defaultPrintTemplateKey === $templateKey;
                        @endphp
                        <tr>
                            <td>
                                <div class="bcx-table__name">{{ $template['name'] }}</div>
                                <div class="bcx-table__meta">{{ $templateKey }}</div>
                            </td>
                            <td>
                                <span class="bcx-chip {{ $templateType === 'standard' ? '' : 'bcx-chip--brand' }}">
                                    {{ $availableTypes[$templateType]['label'] ?? $templateType }}
                                </span>
                            </td>
                            <td>
                                <span class="bcx-num">{{ $template['settings']['width'] ?? 0 }} × {{ $template['settings']['height'] ?? 0 }} mm</span>
                                @if ($templateType === 'jewellery_tag')
                                    <div class="bcx-table__meta">
                                        {{ $template['settings']['wing_width'] ?? 0 }} mm wings ·
                                        {{ $template['settings']['neck_width'] ?? 0 }} mm neck
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($isDefault)
                                    <span class="bcx-chip bcx-chip--ok"><i class="fa fa-check"></i> Default</span>
                                @else
                                    <button type="button"
                                        class="bcx-btn bcx-btn--sm"
                                        wire:click="$set('defaultPrintTemplateKey', '{{ $templateKey }}')">
                                        Set default
                                    </button>
                                @endif
                            </td>
                            <td style="text-align:end;white-space:nowrap">
                                <a href="{{ route('inventory::barcode::configuration.edit', $templateKey) }}"
                                    class="bcx-btn bcx-btn--sm">
                                    <i class="fa fa-pencil"></i> Configure
                                </a>
                                <button type="button"
                                    class="bcx-btn bcx-btn--sm bcx-btn--danger"
                                    wire:click="deleteTemplate('{{ $templateKey }}')"
                                    wire:confirm="Delete this barcode template?"
                                    @if (count($templates) === 1) disabled @endif>
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bcx-status">
            <span>TEMPLATES <b>{{ count($templates) }}</b></span>
            <span>DEFAULT <b>{{ $templates[$defaultPrintTemplateKey]['name'] ?? '—' }}</b></span>
            <span class="bcx-spacer"></span>
            <span>TYPE IS FIXED AT CREATION</span>
        </div>
    </div>
</div>
