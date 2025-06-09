<div>
    <style>
        /* Using Bootstrap theme colors for consistency */
        :root {
            /* Use Bootstrap color variables */
            --primary-color: var(--bs-primary);
            --success-color: var(--bs-success);
            --warning-color: var(--bs-warning);
            --danger-color: var(--bs-danger);
            --info-color: var(--bs-info);
            --secondary-color: var(--bs-secondary);

            /* Use Bootstrap spacing and layout variables */
            --border-color: var(--bs-border-color);
            --body-bg: var(--bs-body-bg);
            --component-bg: var(--bs-component-bg);
            --text-color: var(--bs-body-color);
            --muted-color: var(--bs-secondary-color);

            /* Use Bootstrap shadows and border radius */
            --shadow-sm: var(--bs-box-shadow-sm);
            --shadow-md: var(--bs-box-shadow);
            --shadow-lg: var(--bs-box-shadow-lg);
            --radius-sm: var(--bs-border-radius-sm);
            --radius-md: var(--bs-border-radius);
            --radius-lg: var(--bs-border-radius-lg);
        }

        /* Modern gradient headers using Bootstrap colors */
        .gradient-header-primary {
            background: linear-gradient(135deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 80%, black) 100%);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .gradient-header-success {
            background: linear-gradient(135deg, var(--bs-success) 0%, color-mix(in srgb, var(--bs-success) 80%, black) 100%);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .gradient-header-danger {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        /* Modern button gradients using Bootstrap colors */
        .btn-gradient-primary {
            background: linear-gradient(135deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 80%, black) 100%);
            border: none;
            color: white;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-gradient-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-gradient-success {
            background: linear-gradient(135deg, var(--bs-success) 0%, color-mix(in srgb, var(--bs-success) 80%, black) 100%);
            border: none;
            color: white;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-gradient-success:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-gradient-danger {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            border: none;
            color: white;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(245, 101, 101, 0.2);
        }

        .btn-gradient-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(245, 101, 101, 0.3);
            color: white;
        }

        /* Clean card styles using Bootstrap colors */
        .modern-card {
            border: 1px solid var(--bs-border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            background: var(--bs-component-bg);
            overflow: hidden;
        }

        .compact-card {
            padding: 1.25rem;
            background: var(--bs-light);
        }

        .compact-spacing {
            margin-bottom: 1rem;
        }

        .small-padding {
            padding: 0.75rem;
        }

        /* Eye-friendly stat cards using Bootstrap colors */
        .stat-card {
            background: linear-gradient(135deg, var(--bs-component-bg) 0%, var(--bs-light) 100%);
            border: 1px solid var(--bs-border-color);
            border-radius: var(--radius-md);
            padding: 1rem;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Modern alerts using Bootstrap colors */
        .modern-alert {
            border: none;
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .modern-alert-success {
            background: rgba(var(--bs-success-rgb), 0.1);
            border-left-color: var(--bs-success);
            color: var(--bs-body-color);
        }

        .modern-alert-warning {
            background: rgba(var(--bs-warning-rgb), 0.1);
            border-left-color: var(--bs-warning);
            color: var(--bs-body-color);
        }

        .modern-alert-danger {
            background: rgba(var(--bs-danger-rgb), 0.1);
            border-left-color: var(--bs-danger);
            color: var(--bs-body-color);
        }

        /* Modern form controls using Bootstrap colors */
        .modern-input {
            border: 2px solid var(--bs-border-color);
            border-radius: var(--radius-md);
            padding: 0.75rem;
            transition: all 0.2s ease;
            background: var(--bs-component-bg);
        }

        .modern-input:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
            outline: none;
        }

        .modern-select {
            border: 2px solid var(--bs-border-color);
            border-radius: var(--radius-md);
            padding: 0.5rem;
            background: var(--bs-component-bg);
            transition: all 0.2s ease;
        }

        .modern-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
            outline: none;
        }

        /* Modern table styles using Bootstrap colors */
        .modern-table {
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .modern-table th {
            background: var(--bs-light);
            color: var(--bs-body-color);
            font-weight: 600;
            padding: 1rem;
            border: none;
        }

        .modern-table td {
            padding: 1rem;
            border: none;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .modern-table tbody tr:hover {
            background: var(--bs-light);
        }

        /* Modern badges using Bootstrap colors */
        .modern-badge {
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .modern-badge-primary {
            background: var(--bs-primary);
            color: white;
        }

        .modern-badge-success {
            background: var(--bs-success);
            color: white;
        }

        .modern-badge-warning {
            background: var(--bs-warning);
            color: white;
        }

        .modern-badge-light {
            background: var(--bs-light);
            color: var(--bs-body-color);
        }

        /* Icon styles using Bootstrap colors */
        .icon-circle {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .icon-circle-success {
            background: linear-gradient(135deg, var(--bs-success) 0%, color-mix(in srgb, var(--bs-success) 80%, black) 100%);
            color: white;
        }

        /* Filled stat card icons */
        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white !important;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .stat-icon-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            box-shadow: 0 2px 8px rgba(72, 187, 120, 0.2);
        }

        .stat-icon-primary {
            background: linear-gradient(135deg, #667eea 0%, #5a67d8 100%);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }

        .stat-icon-info {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            box-shadow: 0 2px 8px rgba(66, 153, 225, 0.2);
        }

        .stat-icon-warning {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            box-shadow: 0 2px 8px rgba(237, 137, 54, 0.2);
        }

        /* Smooth animations */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Clean typography using Bootstrap colors */
        .text-primary-custom {
            color: #667eea !important;
        }

        .text-success-custom {
            color: #48bb78 !important;
        }

        .text-warning-custom {
            color: #ed8936 !important;
        }

        .text-danger-custom {
            color: #f56565 !important;
        }

        .text-muted-custom {
            color: #718096 !important;
        }

        .text-body-custom {
            color: #2d3748 !important;
        }

        .text-emphasis-custom {
            color: #1a202c !important;
        }
    </style>

    <!-- Modern Header Section -->
    <div class="row compact-spacing fade-in">
        <div class="col-12">
            <div class="modern-card">
                <div class="card-header gradient-header-primary text-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-calendar me-2"></i>
                            <div>
                                <h5 class="mb-0 fw-bold text-white">Day Session Management</h5>
                                <small class="text-light opacity-90">Manage daily operations efficiently</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="modern-badge modern-badge-light">
                                <i class="fa fa-building me-1"></i>Branch
                            </span>
                            <select class="modern-select form-select-sm" wire:model="branch_id" wire:change="changeBranch($event.target.value)">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-body compact-card">
                    <!-- Modern Alert Messages -->
                    @if (session()->has('success'))
                        <div class="modern-alert modern-alert-success">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-check-circle me-2 text-success-custom"></i>
                                <span class="fw-medium">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="modern-alert modern-alert-danger">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-exclamation-circle me-2 text-danger-custom"></i>
                                <span class="fw-medium">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Modern Current Session Display -->
                    @if ($currentSession)
                        <!-- Session Status -->
                        <div class="modern-alert modern-alert-success">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-play text-success-custom me-3 fs-5"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1 text-emphasis-custom">Session Active</h6>
                                        <small class="text-muted-custom">Started {{ systemDateTime($sessionStats['opened_at']) }} by {{ $sessionStats['opened_by'] }}</small>
                                    </div>
                                </div>
                                <span class="modern-badge modern-badge-success">Session #{{ $currentSession->id }}</span>
                            </div>
                        </div>

                        <!-- Modern Session Control Panel -->
                        <div class="modern-card mt-3">
                            <div class="card-header gradient-header-danger text-white py-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 fw-bold"><i class="fa fa-lock me-2"></i>Close Session</h6>
                                    <span class="modern-badge modern-badge-light text-danger-custom">Critical Action</span>
                                </div>
                            </div>
                            <div class="card-body compact-card">
                                <!-- Modern Summary -->
                                <div class="modern-alert modern-alert-warning">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa fa-info-circle me-2 text-warning-custom"></i>
                                        <h6 class="fw-bold mb-0 text-emphasis-custom">Pre-Closing Summary</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon stat-icon-success">
                                                        <i class="fa fa-money"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-success-custom fs-5">{{ currency($sessionStats['opening_amount']) }}</div>
                                                        <small class="text-muted-custom fw-medium">Opening Amount</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon stat-icon-primary">
                                                        <i class="fa fa-shopping-cart"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-primary-custom fs-5">{{ $sessionStats['total_sales'] }}</div>
                                                        <small class="text-muted-custom fw-medium">Total Sales</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon stat-icon-info">
                                                        <i class="fa fa-line-chart"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-info fs-5">{{ currency($sessionStats['total_amount']) }}</div>
                                                        <small class="text-muted-custom fw-medium">Revenue</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon stat-icon-warning">
                                                        <i class="fa fa-bullseye"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-warning-custom fs-5">{{ currency($sessionStats['expected_amount']) }}</div>
                                                        <small class="text-muted-custom fw-medium">Expected</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form wire:submit.prevent="closeDay">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="closing_amount" class="form-label">
                                                <i class="fa fa-money me-1 text-success"></i>Closing Amount <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" step="0.01" class="form-control" id="closing_amount" wire:model="closing_amount" placeholder="0.00">
                                            @error('closing_amount')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-8">
                                            <label for="notes" class="form-label">
                                                <i class="fa fa-comment me-1 text-secondary"></i>Notes <span class="text-muted">(Optional)</span>
                                            </label>
                                            <textarea class="form-control" id="notes" wire:model="notes" rows="2" placeholder="Session notes..."></textarea>
                                            @error('notes')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                        <button type="submit" class="btn btn-gradient-danger">
                                            <i class="fa fa-lock me-1"></i>Close Session
                                        </button>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('sale::create') }}" class="btn btn-sm btn-gradient-success">
                                                <i class="fa fa-plus me-1"></i>New Sale
                                            </a>
                                            <a href="{{ route('sale::day-session', $currentSession->id) }}" class="btn btn-sm btn-gradient-primary">
                                                <i class="fa fa-eye me-1"></i>Details
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @else
                        <!-- Compact No Session State -->
                        <div class="text-center py-3">
                            <div class="mb-3">
                                <i class="fa fa-unlock-alt text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h4 class="fw-bold mb-2">Day Session Not Started</h4>
                            <p class="text-muted mb-3">Start a new session to enable sales processing and cash management.</p>

                            <div class="row justify-content-center">
                                <div class="col-lg-6">
                                    <div class="card shadow-sm">
                                        <div class="card-header gradient-header-success text-white py-2">
                                            <h6 class="mb-0 text-white"><i class="fa fa-unlock me-1"></i>Start New Session</h6>
                                        </div>
                                        <div class="card-body compact-card">
                                            <form wire:submit.prevent="openDay">
                                                <div class="mb-3">
                                                    <label for="opening_amount" class="form-label">
                                                        <i class="fa fa-money me-1 text-success"></i>Opening Cash Amount <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-success text-white">
                                                            <i class="fa fa-dollar"></i>
                                                        </span>
                                                        <input type="number" step="0.01" class="form-control" id="opening_amount" wire:model="opening_amount" placeholder="0.00">
                                                    </div>
                                                    @error('opening_amount')
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <button type="submit" class="btn btn-gradient-success w-100">
                                                    <i class="fa fa-unlock me-1"></i>Start Session
                                                </button>
                                                <div class="mt-2 p-2 rounded bg-light">
                                                    <small class="text-muted">
                                                        <i class="fa fa-info-circle me-1"></i>
                                                        Count all cash before starting
                                                    </small>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Open Sessions Table -->
    @if (count($openSessions) > 0 && (!$currentSession || $currentSession->branch_id != Auth::user()->default_branch_id))
        <div class="row mt-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header gradient-header-primary text-white py-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0"><i class="fa fa-list me-1"></i>All Open Sessions</h6>
                            <span class="badge bg-light text-primary">{{ count($openSessions) }} Active</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-2 px-3">Branch</th>
                                        <th class="py-2 px-3">Started</th>
                                        <th class="py-2 px-3">Opened By</th>
                                        <th class="py-2 px-3">Amount</th>
                                        <th class="py-2 px-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($openSessions as $session)
                                        <tr>
                                            <td class="py-2 px-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-building text-primary me-2 session-icon"></i>
                                                    <div>
                                                        <div class="fw-bold">{{ $session->branch->name }}</div>
                                                        <small class="text-success">Active</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-2 px-3">
                                                <div>{{ $session->opened_at->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $session->opened_at->format('h:i A') }}</small>
                                            </td>
                                            <td class="py-2 px-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-user text-info me-2"></i>
                                                    {{ $session->opener->name ?? 'Unknown' }}
                                                </div>
                                            </td>
                                            <td class="py-2 px-3">
                                                <span class="badge bg-warning text-dark">{{ currency($session->opening_amount) }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-sm btn-gradient-primary" wire:click="changeBranch({{ $session->branch_id }})">
                                                        <i class="fa fa-cog me-1"></i>Manage
                                                    </button>
                                                    <a href="{{ route('sale::day-session', $session->id) }}" class="btn btn-sm btn-gradient-primary">
                                                        <i class="fa fa-eye me-1"></i>Details
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
