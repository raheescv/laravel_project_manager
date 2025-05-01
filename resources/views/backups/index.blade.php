<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Backup</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Backups</h1>
            <p class="lead">
                A table is an arrangement of Database Backups
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6 d-flex gap-1 align-items-center">
                        </div>
                        <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end">
                            @can('backup.create')
                                <a href='{{ route('backup::create') }}' class="btn btn-xs btn-success" title="To create a new backup">Create Backup</a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('backup_result'))
                        <div class="alert alert-info">
                            <pre>{{ session('backup_result') }}</pre>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-striped align-middle table-sm table-bordered">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-white">#</th>
                                    <th class="text-white">Date</th>
                                    <th class="text-white">Size</th>
                                    @can('backup.download')
                                        <th class="text-white">Action</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($files as $file)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ date('d-m-Y H:i:s', $file['last_modified']) }}</td>
                                        <td>{{ $file['size'] }}</td>
                                        @can('backup.download')
                                            <td> <a href="{{ route('backup::download', ['file' => $file['name']]) }}"> <i class="fa fa-2x fa-download"></i> </a> </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
