<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-user me-2"></i>Visitor Details
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('visitors.index') }}">Visitors</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('visitors.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
                @if ($visitor->status === 'checked_in')
                    <form action="{{ route('visitors.checkout', $visitor) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to check out this visitor?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Check Out
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Main Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <!-- Status Banner -->
                    <div class="card-header bg-transparent border-0 py-3 {{ $visitor->status === 'checked_in' ? 'bg-success bg-opacity-10' : 'bg-secondary bg-opacity-10' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span
                                    class="badge {{ $visitor->status === 'checked_in' ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 text-{{ $visitor->status === 'checked_in' ? 'success' : 'secondary' }}">
                                    <i class="fas fa-circle me-1"></i>{{ ucfirst($visitor->status) }}
                                </span>
                                <span class="ms-2 text-muted">
                                    {{ $visitor->check_in_time->format('M d, Y h:i A') }}
                                    @if ($visitor->check_out_time)
                                        to {{ $visitor->check_out_time->format('M d, Y h:i A') }}
                                    @endif
                                </span>
                            </div>
                            <div class="text-muted">
                                <i class="fas fa-clock me-1"></i>Duration: {{ $visitor->duration }}
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <div class="card bg-light bg-opacity-50 border-0">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">
                                            <i class="fas fa-user-circle me-2 text-primary"></i>Personal Information
                                        </h5>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4 text-muted">Name</dt>
                                            <dd class="col-sm-8">{{ $visitor->name }}</dd>

                                            <dt class="col-sm-4 text-muted">Date of Birth</dt>
                                            <dd class="col-sm-8">{{ $visitor->date_of_birth?->format('M d, Y') ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4 text-muted">ID Card Number</dt>
                                            <dd class="col-sm-8">{{ $visitor->id_card_number ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4 text-muted">Address</dt>
                                            <dd class="col-sm-8">{{ $visitor->address ?? 'N/A' }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <!-- Visit Information -->
                            <div class="col-md-6">
                                <div class="card bg-light bg-opacity-50 border-0">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">
                                            <i class="fas fa-clipboard-list me-2 text-primary"></i>Visit Information
                                        </h5>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4 text-muted">Purpose</dt>
                                            <dd class="col-sm-8">{{ $visitor->purpose_of_visit }}</dd>

                                            <dt class="col-sm-4 text-muted">Host Employee</dt>
                                            <dd class="col-sm-8">
                                                @if ($visitor->hostEmployee)
                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                        {{ $visitor->hostEmployee->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </dd>

                                            <dt class="col-sm-4 text-muted">Department</dt>
                                            <dd class="col-sm-8">{{ $visitor->host_department ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4 text-muted">Branch</dt>
                                            <dd class="col-sm-8">
                                                @if ($visitor->branch)
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                        {{ $visitor->branch->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <!-- ID Card Image -->
                            @if ($visitor->id_card_image_path)
                                <div class="col-12">
                                    <div class="card bg-light bg-opacity-50 border-0">
                                        <div class="card-body">
                                            <h5 class="card-title mb-4">
                                                <i class="fas fa-id-card me-2 text-primary"></i>ID Card Image
                                            </h5>
                                            <div class="text-center">
                                                <img src="{{ Storage::url($visitor->id_card_image_path) }}" alt="ID Card" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Notes -->
                            @if ($visitor->notes)
                                <div class="col-12">
                                    <div class="card bg-light bg-opacity-50 border-0">
                                        <div class="card-body">
                                            <h5 class="card-title mb-4">
                                                <i class="fas fa-sticky-note me-2 text-primary"></i>Additional Notes
                                            </h5>
                                            <p class="card-text mb-0">{{ $visitor->notes }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('visitors.create') }}" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Register New Visitor
                            </a>
                            <a href="{{ route('visitors.index') }}" class="btn btn-light">
                                <i class="fas fa-list me-2"></i>View All Visitors
                            </a>
                            @if ($visitor->status === 'checked_in')
                                <form action="{{ route('visitors.checkout', $visitor) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to check out this visitor?')">
                                        <i class="fas fa-sign-out-alt me-2"></i>Check Out Visitor
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Visit Timeline -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-info"></i>Visit Timeline
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Check In</h6>
                                    <p class="text-muted mb-0">{{ $visitor->check_in_time->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                            @if ($visitor->check_out_time)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-secondary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Check Out</h6>
                                        <p class="text-muted mb-0">{{ $visitor->check_out_time->format('M d, Y h:i A') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .timeline {
                position: relative;
                padding-left: 3rem;
            }

            .timeline-item {
                position: relative;
                padding-bottom: 1.5rem;
            }

            .timeline-item:last-child {
                padding-bottom: 0;
            }

            .timeline-marker {
                position: absolute;
                left: -1.5rem;
                width: 1rem;
                height: 1rem;
                border-radius: 50%;
            }

            .timeline-item:not(:last-child)::before {
                content: '';
                position: absolute;
                left: -1.15rem;
                top: 1rem;
                bottom: -0.5rem;
                width: 2px;
                background-color: #e9ecef;
            }
        </style>
    @endpush
</x-app-layout>
