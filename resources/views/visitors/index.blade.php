<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-users me-2"></i>Visitor Management
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Visitors</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('visitors.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Register New Visitor
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-users text-primary fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-subtitle text-muted mb-1">Total Visitors Today</h6>
                                <h2 class="card-title mb-0">{{ $stats['total_visitors'] ?? 0 }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-user-check text-success fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-subtitle text-muted mb-1">Currently Checked In</h6>
                                <h2 class="card-title mb-0">{{ $stats['currently_checked_in'] ?? 0 }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-user-clock text-info fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-subtitle text-muted mb-1">Checked Out Today</h6>
                                <h2 class="card-title mb-0">{{ $stats['checked_out'] ?? 0 }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('visitors.index') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="checked_in" {{ request('status') == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                            <option value="checked_out" {{ request('status') == 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" value="{{ request('date') }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Visitors Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">Visitor</th>
                                <th class="border-0">Purpose</th>
                                <th class="border-0">Host</th>
                                <th class="border-0">Check In</th>
                                <th class="border-0">Status</th>
                                <th class="border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($visitors as $visitor)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="avatar avatar-sm bg-primary bg-opacity-10 rounded-circle">
                                                    <i class="fas fa-user text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0">{{ $visitor->name }}</h6>
                                                <small class="text-muted">{{ $visitor->id_card_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ Str::limit($visitor->purpose_of_visit, 30) }}</td>
                                    <td>
                                        @if ($visitor->hostEmployee)
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                {{ $visitor->hostEmployee->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>{{ $visitor->check_in_time->format('M d, Y') }}</span>
                                            <small class="text-muted">{{ $visitor->check_in_time->format('h:i A') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($visitor->status === 'checked_in')
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                <i class="fas fa-circle me-1"></i>Checked In
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                <i class="fas fa-circle me-1"></i>Checked Out
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('visitors.show', $visitor) }}" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if ($visitor->status === 'checked_in')
                                                <form action="{{ route('visitors.checkout', $visitor) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Check Out"
                                                        onclick="return confirm('Are you sure you want to check out this visitor?')">
                                                        <i class="fas fa-sign-out-alt"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-users fa-2x mb-3"></i>
                                            <p class="mb-0">No visitors found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($visitors->hasPages())
                <div class="card-footer bg-transparent border-0 py-3">
                    {{ $visitors->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        </script>
    @endpush
</x-app-layout>
