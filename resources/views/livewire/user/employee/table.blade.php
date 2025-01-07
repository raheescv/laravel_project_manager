<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('employee.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="EmployeeAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                @can('category.export')
                    <button class="btn btn-icon btn-outline-light" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                @endcan
                @can('employee.delete')
                    <div class="btn-group">
                        <button class="btn btn-icon btn-outline-light" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?"><i class="demo-pli-recycling fs-5"></i>
                        </button>
                    </div>
                @endcan
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm text-capitalize">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <a href="#" wire:click.prevent="sortBy('id')">
                                ID
                                @if ($sortField === 'id')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('name')">
                                Name
                                @if ($sortField === 'name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th> Roles </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('code')">
                                Code
                                @if ($sortField === 'code')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('email')">
                                Email
                                @if ($sortField === 'email')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('mobile')">
                                mobile
                                @if ($sortField === 'mobile')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('place')">
                                place
                                @if ($sortField === 'place')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('nationality')">
                                nationality
                                @if ($sortField === 'nationality')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                                {{ $item->id }}
                            </td>
                            <td><a href="{{ route('users::employee::view', $item['id']) }}" class="btn-link">{{ $item['name'] }}</a></td>
                            <td>{{ getUserRoles($item) }}</td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->mobile }}</td>
                            <td>{{ $item->place }}</td>
                            <td>{{ $item->nationality }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("Employee-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#EmployeeAdd').click(function() {
                    Livewire.dispatch("Employee-Page-Create-Component");
                });
                window.addEventListener('RefreshEmployeeTable', event => {
                    Livewire.dispatch("Employee-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
