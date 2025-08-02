<div>
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Moq Solutions API Configuration</h5>
                </div>
                <div class="card-body">
                    <div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="moq_username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="moq_username" wire:model="config.username" readonly required>
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" wire:model="config.password" readonly placeholder="Leave blank to keep current password">
                            </div>

                            <div class="col-md-12">
                                <label for="token" class="form-label">Token</label>
                                <input type="text" class="form-control" id="token" wire:model="config.token" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Sandbox Endpoint</label>
                                <input type="text" class="form-control" wire:model="config.endpoint_sandbox" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Endpoint</label>
                                <input type="text" class="form-control" wire:model="config.endpoint" readonly>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-outline-info ms-2" wire:click="testConnection">
                                <i class="demo-psi-refresh me-2"></i>
                                Test Connection
                            </button>
                        </div>
                    </div>
                    <div class="card shadow-sm mt-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Connection Status</h5>
                        </div>
                        <div class="card-body">
                            @if ($connectionStatus === 'success')
                                <div class="text-success">
                                    <i class="demo-psi-check"></i> {{ $connectionMessage }}
                                </div>
                            @elseif($connectionStatus === 'failed')
                                <div class="text-danger">
                                    <i class="demo-psi-close"></i> {{ $connectionMessage }}
                                </div>
                            @else
                                <div class="text-muted">Click "Test Connection" to check status</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Test API Call</h5>
                </div>
                <div class="card-body">
                    <form wire:submit="syncDayClose">
                        <div class="mb-3">
                            <label for="test_amount" class="form-label">DayClose Amount</label>
                            <input type="number" class="form-control" id="test_amount" wire:model="test_amount" step="0.01" min="0">
                            @error('test_amount')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="test_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="test_date" wire:model="test_date">
                            @error('test_date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="test_outlet" class="form-label">Outlet</label>
                            <input type="text" class="form-control" id="test_outlet" wire:model="test_outlet" placeholder="Outlet">
                            @error('test_outlet')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="demo-psi-send me-2"></i>
                            Sync Day Close Amount
                        </button>
                    </form>
                </div>
            </div>

            @if ($syncResult)
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Sync Result</h5>
                    </div>
                    <div class="card-body">
                        @if ($syncResult === 'success')
                            <div class="text-success">
                                <i class="demo-psi-check"></i> {{ $syncMessage }}
                            </div>
                        @elseif($syncResult === 'failed')
                            <div class="text-danger">
                                <i class="demo-psi-close"></i> {{ $syncMessage }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
