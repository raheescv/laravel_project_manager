<!-- API Log Detail Modal -->
<div class="modal fade" id="apiLogDetailModal" tabindex="-1" aria-labelledby="apiLogDetailModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 py-2">
                <h6 class="modal-title fw-bold mb-0 text-white" id="apiLogDetailModalLabel">
                    <i class="fa fa-exchange me-2"></i>API Log Details
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="closeModal"></button>
            </div>

            <div class="modal-body p-3">
                @if ($apiLog)
                    @php
                        $prettyJson = function ($value) {
                            if (empty($value)) {
                                return null;
                            }
                            $decoded = json_decode($value, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                            }
                            return $value;
                        };
                        $requestPretty = $prettyJson($apiLog->request);
                        $responsePretty = $prettyJson($apiLog->response);
                    @endphp
                    {{-- Compact summary row --}}
                    <div class="bg-light border rounded p-2 mb-3">
                        <div class="row g-2 align-items-center small">
                            <div class="col-auto">
                                <span class="badge bg-secondary rounded-pill">#{{ $apiLog->id }}</span>
                            </div>
                            <div class="col-auto">
                                @php
                                    $methodColors = [
                                        'GET' => 'bg-success',
                                        'POST' => 'bg-primary',
                                        'PUT' => 'bg-warning text-dark',
                                        'PATCH' => 'bg-info text-dark',
                                        'DELETE' => 'bg-danger',
                                    ];
                                    $methodUpper = strtoupper($apiLog->method ?? '');
                                    $methodClass = $methodColors[$methodUpper] ?? 'bg-secondary';
                                @endphp
                                <span class="badge {{ $methodClass }}">{{ $methodUpper ?: '-' }}</span>
                            </div>
                            <div class="col">
                                <code class="text-primary text-break">{{ $apiLog->endpoint }}</code>
                            </div>
                            <div class="col-auto">
                                @if ($apiLog->status === 'success')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-check me-1"></i>Success</span>
                                @elseif($apiLog->status === 'failed')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa fa-times me-1"></i>Failed</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa fa-clock-o me-1"></i>Pending</span>
                                @endif
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row g-2 small text-muted">
                            <div class="col-md-4">
                                <i class="fa fa-cog me-1"></i>
                                <span class="fw-semibold">Service:</span>
                                @if ($apiLog->service_name)
                                    <span class="text-primary">{{ $apiLog->service_name }}</span>
                                @else
                                    <span>N/A</span>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <i class="fa fa-user me-1"></i>
                                <span class="fw-semibold">User:</span>
                                <span>{{ $apiLog->username ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-4">
                                <i class="fa fa-calendar me-1"></i>
                                <span class="fw-semibold">Date:</span>
                                <span>{{ $apiLog->created_at->format('M d, Y H:i:s') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Error banner (only if failed) --}}
                    @if ($apiLog->description)
                        <div class="alert alert-danger py-2 px-3 mb-3 small d-flex align-items-start">
                            <i class="fa fa-exclamation-triangle me-2 mt-1"></i>
                            <div class="flex-grow-1">{{ $apiLog->description }}</div>
                        </div>
                    @endif

                    {{-- Request + Response side by side --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold text-primary small mb-0">
                                    <i class="fa fa-paper-plane me-1"></i> Request
                                </label>
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small" onclick="copyApiLogContent('request-data')" title="Copy">
                                    <i class="fa fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="bg-dark rounded border">
                                <pre id="request-data" class="mb-0 small p-2 json-pretty" style="height: 280px; overflow: auto;"><code class="language-json">{{ $requestPretty ?: 'No request data' }}</code></pre>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold text-success small mb-0">
                                    <i class="fa fa-reply me-1"></i> Response
                                </label>
                                @if ($apiLog->response)
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small" onclick="copyApiLogContent('response-data')" title="Copy">
                                        <i class="fa fa-copy"></i> Copy
                                    </button>
                                @endif
                            </div>
                            <div class="bg-dark rounded border">
                                <pre id="response-data" class="mb-0 small p-2 json-pretty" style="height: 280px; overflow: auto;"><code class="language-json">{{ $responsePretty ?: 'No response data' }}</code></pre>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-info-circle display-4"></i>
                        <h6 class="mt-3 mb-2">No API Log Details</h6>
                        <p class="mb-0 small">The requested API log details could not be found.</p>
                    </div>
                @endif
            </div>

            <div class="modal-footer bg-light py-2">
                @if ($apiLog && $apiLog->status === 'failed')
                    <button type="button" class="btn btn-warning btn-sm" wire:click="retryApiCall({{ $apiLog->id }})"
                        wire:confirm="Are you sure you want to retry this API call?">
                        <i class="fa fa-refresh me-1"></i> Retry
                    </button>
                @endif
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" wire:click="closeModal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>

    <style>
        .json-pretty {
            font-family: 'SF Mono', Menlo, Consolas, 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.5;
            color: #e6e6e6;
            tab-size: 2;
        }
        .json-pretty .json-key { color: #79c0ff; }
        .json-pretty .json-string { color: #a5d6ff; }
        .json-pretty .json-number { color: #ffa657; }
        .json-pretty .json-boolean { color: #ff7b72; }
        .json-pretty .json-null { color: #8b949e; font-style: italic; }
        .json-pretty::-webkit-scrollbar { width: 8px; height: 8px; }
        .json-pretty::-webkit-scrollbar-track { background: #1e1e1e; }
        .json-pretty::-webkit-scrollbar-thumb { background: #4a4a4a; border-radius: 4px; }
        .json-pretty::-webkit-scrollbar-thumb:hover { background: #6a6a6a; }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('showApiLogDetail', (apiLogId) => {
                const modalElement = document.getElementById('apiLogDetailModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    setTimeout(highlightApiLogJson, 50);
                }
            });
        });

        function copyApiLogContent(elementId) {
            const el = document.getElementById(elementId);
            if (!el) return;
            navigator.clipboard.writeText(el.innerText).then(() => {
                if (window.Livewire) {
                    Livewire.dispatch('success', { message: 'Copied to clipboard' });
                }
            });
        }

        function highlightApiLogJson() {
            ['request-data', 'response-data'].forEach(id => {
                const pre = document.getElementById(id);
                if (!pre) return;
                const code = pre.querySelector('code');
                if (!code || code.dataset.highlighted === '1') return;
                const raw = code.textContent;
                try {
                    const parsed = JSON.parse(raw);
                    code.innerHTML = syntaxHighlightJson(JSON.stringify(parsed, null, 2));
                    code.dataset.highlighted = '1';
                } catch (e) {
                    // not JSON — leave as-is
                }
            });
        }

        function syntaxHighlightJson(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    cls = /:$/.test(match) ? 'json-key' : 'json-string';
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }
    </script>
</div>
