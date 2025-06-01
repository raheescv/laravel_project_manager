<section class="p-4 bg-light border rounded-3">
    <header class="mb-4">
        <h2 class="h4 mb-1 text-danger">
            <i class="fa fa-trash-alt me-2"></i>{{ __('Delete Account') }}
        </h2>
        <p class="text-muted small">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-user-deletion-modal">
        <i class="fa fa-user-times me-2"></i>{{ __('Delete Account') }}
    </button>

    <!-- Modal -->
    <div class="modal fade" id="confirm-user-deletion-modal" tabindex="-1" aria-labelledby="confirm-user-deletion-modal-label" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="post" action="{{ route('profile.destroy') }}" class="p-0">
                    @csrf
                    @method('delete')

                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title h5" id="confirm-user-deletion-modal-label">
                            <i class="fa fa-exclamation-triangle me-2"></i>{{ __('Are you sure you want to delete your account?') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <p class="text-muted">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </p>

                        <div class="mt-4">
                            <label for="password_delete_account" class="form-label visually-hidden">{{ __('Password') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-key"></i></span>
                                <input id="password_delete_account" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                                    placeholder="{{ __('Password') }}" required>
                            </div>
                            @error('password', 'userDeletion')
                                <div class="invalid-feedback mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>{{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger ms-2">
                            <i class="fa fa-trash-alt me-2"></i>{{ __('Delete Account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
