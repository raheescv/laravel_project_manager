<footer class="mt-auto">
    <div class="content__boxed">
        <div class="content__wrap py-3 py-md-1 d-flex flex-column flex-md-row align-items-md-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <p class="mb-0">© {{ date('Y') }} {{ config('app.name') }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <p class="text-end mb-0 text-muted small">
                    {{ config('database.connections.mysql.database') }}
                </p>
            </div>
        </div>
    </div>
</footer>
