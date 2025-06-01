<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold">
            <i class="fa fa-user-edit me-2"></i>{{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="card shadow-sm mb-4">
                    @include('profile.partials.update-password-form')
                </div>
                @if (false)
                    <div class="card shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
