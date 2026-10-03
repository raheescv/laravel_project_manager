<div class="card shadow-sm border-0">
    <style>
        .lps-tile { border: 1.5px solid var(--bs-border-color); border-radius: 12px; background: var(--bs-body-bg); padding: .6rem; text-align: start; width: 100%; height: 100%; transition: border-color .15s, box-shadow .15s; }
        .lps-tile:hover { border-color: var(--bs-primary); }
        .lps-tile.active { border-color: var(--bs-primary); box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .15); }
        .lps-thumb { position: relative; height: 92px; border-radius: 8px; overflow: hidden; background: #01275C; margin-bottom: .55rem; }
        .lps-thumb svg { position: absolute; inset: 0; width: 100%; height: 100%; }
        .lps-check { position: absolute; top: 6px; right: 6px; width: 20px; height: 20px; border-radius: 50%; background: var(--bs-primary); color: #fff; font-size: 11px; display: none; align-items: center; justify-content: center; }
        .lps-tile.active .lps-check { display: flex; }
    </style>

    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 text-white"><i class="fa fa-sign-in me-1"></i> Login Page</h5>
        <a href="{{ route('settings::login_preview', ['layout' => $layout, 'background' => $background]) }}" target="_blank" class="btn btn-light btn-sm">
            <i class="fa fa-external-link me-1"></i> Preview
        </a>
    </div>

    <div class="card-body p-3">
        <p class="small text-body-secondary mb-3">
            How the sign-in screen looks for everyone in this workspace. Tap an option to apply it.
            <strong>Random</strong> picks a new one on every visit.
        </p>

        <h6 class="fw-semibold mb-2">Layout</h6>
        <div class="row g-2 mb-4">
            @foreach ([...$layouts, 'random' => 'Random'] as $key => $label)
                <div class="col-6 col-md-4" wire:key="layout-{{ $key }}">
                    <button type="button" class="lps-tile {{ $layout === $key ? 'active' : '' }}" wire:click="setLayout('{{ $key }}')">
                        <div class="lps-thumb">
                            <span class="lps-check"><i class="fa fa-check"></i></span>
                            @if ($key === 'split')
                                <svg viewBox="0 0 160 92" preserveAspectRatio="none">
                                    <rect x="80" width="80" height="92" fill="#fff" />
                                    <rect x="12" y="38" width="44" height="6" rx="2" fill="#fff" opacity=".85" />
                                    <rect x="12" y="48" width="30" height="5" rx="2" fill="#9ED8FF" />
                                    <rect x="98" y="30" width="44" height="7" rx="2" fill="#E4E8F0" />
                                    <rect x="98" y="42" width="44" height="7" rx="2" fill="#E4E8F0" />
                                    <rect x="98" y="56" width="44" height="8" rx="3" fill="#0A62C8" />
                                </svg>
                            @elseif ($key === 'frosted')
                                <svg viewBox="0 0 160 92" preserveAspectRatio="none">
                                    <rect x="52" y="12" width="56" height="68" rx="7" fill="#fff" opacity=".92" />
                                    <rect x="74" y="20" width="12" height="12" rx="3" fill="#0BA8FA" />
                                    <rect x="60" y="40" width="40" height="6" rx="2" fill="#E4E8F0" />
                                    <rect x="60" y="51" width="40" height="6" rx="2" fill="#E4E8F0" />
                                    <rect x="60" y="64" width="40" height="7" rx="3" fill="#0A62C8" />
                                </svg>
                            @else
                                <div class="d-flex h-100 align-items-center justify-content-center text-white fs-3"><i class="fa fa-random"></i></div>
                            @endif
                        </div>
                        <div class="fw-semibold small">{{ $label }}</div>
                    </button>
                </div>
            @endforeach
        </div>

        <h6 class="fw-semibold mb-2">Live background</h6>
        <div class="row g-2">
            @foreach ([...$backgrounds, 'random' => 'Random'] as $key => $label)
                <div class="col-6 col-md-3" wire:key="background-{{ $key }}">
                    <button type="button" class="lps-tile {{ $background === $key ? 'active' : '' }}" wire:click="setBackground('{{ $key }}')">
                        <div class="lps-thumb">
                            <span class="lps-check"><i class="fa fa-check"></i></span>
                            @if ($key === 'globe')
                                <svg viewBox="0 0 160 92">
                                    <defs><pattern id="lps-dots" width="5" height="5" patternUnits="userSpaceOnUse"><circle cx="2.5" cy="2.5" r=".9" fill="#9ED8FF" /></pattern></defs>
                                    <circle cx="80" cy="46" r="36" fill="#0BA8FA" opacity=".18" />
                                    <circle cx="80" cy="46" r="30" fill="url(#lps-dots)" />
                                    <path d="M60 36 Q80 6 102 40" stroke="#0BA8FA" stroke-width="1.6" fill="none" />
                                    <circle cx="102" cy="40" r="2.2" fill="#fff" />
                                </svg>
                            @elseif ($key === 'network')
                                <svg viewBox="0 0 160 92" stroke="#9ED8FF" stroke-width=".8" stroke-opacity=".6">
                                    <path d="M18 20 L50 34 L40 64 L78 52 L50 34 M78 52 L112 26 L142 44 L118 72 L78 52 M112 26 L90 12" fill="none" />
                                    <g fill="#9ED8FF" stroke="none">
                                        <circle cx="18" cy="20" r="2" /><circle cx="50" cy="34" r="2.4" /><circle cx="40" cy="64" r="2" />
                                        <circle cx="78" cy="52" r="2.6" /><circle cx="112" cy="26" r="2.2" /><circle cx="142" cy="44" r="2" />
                                        <circle cx="118" cy="72" r="2" /><circle cx="90" cy="12" r="1.8" />
                                    </g>
                                    <circle cx="96" cy="39" r="3" fill="#0BA8FA" stroke="none" />
                                </svg>
                            @elseif ($key === 'grid')
                                <svg viewBox="0 0 160 92" stroke="#9ED8FF" stroke-opacity=".45" stroke-width=".7">
                                    <ellipse cx="80" cy="42" rx="60" ry="14" fill="#0BA8FA" opacity=".25" stroke="none" />
                                    @foreach ([-120, -80, -50, -25, 0, 25, 50, 80, 120] as $dx)
                                        <line x1="80" y1="42" x2="{{ 80 + $dx }}" y2="92" />
                                    @endforeach
                                    @foreach ([46, 52, 61, 74, 90] as $y)
                                        <line x1="0" y1="{{ $y }}" x2="160" y2="{{ $y }}" />
                                    @endforeach
                                    <line x1="80" y1="42" x2="92" y2="80" stroke="#0BA8FA" stroke-opacity="1" stroke-width="1.8" />
                                </svg>
                            @else
                                <div class="d-flex h-100 align-items-center justify-content-center text-white fs-3"><i class="fa fa-random"></i></div>
                            @endif
                        </div>
                        <div class="fw-semibold small">{{ $label }}</div>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</div>
