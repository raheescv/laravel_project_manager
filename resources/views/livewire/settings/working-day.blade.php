<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-white">Working Day Configuration</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center py-3 fw-bold text-uppercase">Day</th>
                                    <th class="text-center py-3 fw-bold text-uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($days as $index => $day)
                                <tr>
                                    <td class="text-center py-3 fw-semibold">
                                        {{ $day['day_name'] }}
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="form-check form-switch d-flex justify-content-center align-items-center">
                                            <input class="form-check-input me-2" 
                                                   type="checkbox" 
                                                   wire:model="days.{{ $index }}.is_working" 
                                                   id="day_{{ $day['id'] }}"
                                                   role="switch">
                                            <label class="form-check-label fw-bold {{ $day['is_working'] ? 'text-success' : 'text-danger' }}" 
                                                   for="day_{{ $day['id'] }}">
                                                {{ $day['is_working'] ? 'ON' : 'OFF' }}
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-end py-3">
                    <button wire:click="updateSettings" class="btn btn-primary px-4">
                        <i class="fa fa-save me-2"></i>Update Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
