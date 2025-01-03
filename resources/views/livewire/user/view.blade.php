<div>
    <div class="content__header content__boxed rounded-0">
        <div class="content__wrap d-md-flex align-items-start hv-outline-parent hv-outline-inherit">
            <figure class="m-0">
                <div class="d-inline-flex align-items-center position-relative pt-xl-3 mb-3">
                    <div class="flex-shrink-0">
                        <img class="hv-oc img-xl rounded-circle border" src="{{ asset('assets/img/profile-photos/3.png') }}" alt="Profile Picture" loading="lazy">
                    </div>
                    <div class="flex-grow-1 ms-4">
                        <a href="#" class="h3 btn-link text-body-emphasis">{{ $user->name }}</a>
                        <p class="m-0">Marketing manager</p>
                    </div>
                </div>
            </figure>
            <div class="d-inline-flex justify-content-end pt-xl-5 gap-2 ms-auto">
                <button class="btn btn-light text-nowrap" id="UserEdit">Edit Profile</button>
            </div>
        </div>
    </div>
    <div class="content__boxed mt-4">
        <div class="content__wrap">
            <div class="d-md-flex gap-4">
                <div class="w-md-300px flex-shrink-0">
                    <div class="text-primary">
                        <h5 class="text-primary-emphasis">About Me</h5>
                        <ul class="list-unstyled mb-3">
                            <li class="mb-2"><i class="demo-psi-map-marker-2 fs-5 me-3"></i>{{ $user->name }}</li>
                            <li class="mb-2"><i class="demo-psi-mail fs-5 me-3"></i>{{ $user->email }}</li>
                            <li class="mb-2"><i class="demo-psi-old-telephone fs-5 me-3"></i>{{ $user->mobile }}</li>
                        </ul>
                    </div>
                    <h5 class="mt-5">Roles</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#" class="btn btn-xs btn-outline-light text-nowrap">PHP Programming</a>
                        <a href="#" class="btn btn-xs btn-outline-light text-nowrap">Marketing</a>
                        <a href="#" class="btn btn-xs btn-outline-light text-nowrap">Graphic Desig</a>
                        <a href="#" class="btn btn-xs btn-outline-light text-nowrap">Sketch</a>
                        <a href="#" class="btn btn-xs btn-outline-light text-nowrap">Photography</a>
                        <a href="#" class="btn btn-xs btn-outline-light text-nowrap">HTML</a>
                        <a href="#" class="btn btn-xs btn-outline-light text-nowrap">CSS</a>
                    </div>
                </div>
                <div class="vr d-none"></div>
                <div class="flex-fill">
                    <div class="card">
                        <div class="card-header">
                            <h5>Whatsapp Settings</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">Setup Whatsapp Notification</h6>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div>
                                    <p class="text-muted mb-0">Enable Whatsapp Notification</p>
                                </div>
                                <div class="form-check form-switch p-0">
                                    {{ html()->checkbox('is_whatsapp_enabled')->value('')->checked($user->is_whatsapp_enabled)->class('m-0 form-check-input h5 position-relative')->attribute('wire:click', 'enabledWhatsapp') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="card">
                        <div class="card-header">
                            <h5>User Settings</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">Manage User Status</h6>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div>
                                    <p class="text-muted mb-0">User Status</p>
                                </div>
                                <div class="form-check form-switch p-0">
                                    {{ html()->checkbox('is_active')->value('')->checked($user->is_active)->class('m-0 form-check-input h5 position-relative')->attribute('wire:click', 'activeUser') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#UserEdit').click(function() {
                    Livewire.dispatch("User-Page-Update-Component", {
                        id: "{{ $user->id }}"
                    });
                });
                window.addEventListener('RefreshUserPage', event => {
                    Livewire.dispatch("User-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
