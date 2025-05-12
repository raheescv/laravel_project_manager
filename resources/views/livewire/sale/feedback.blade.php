<div>
    <form wire:submit="save">
        <div class="card shadow-lg">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h1 class="h3">Your Feedback</h1>
                    <p class="text-muted">We value your opinion! Please rate your experience.</p>
                </div>
                <div class="feedback-container">
                    <div class="star-rating mb-4">
                        <div class="stars d-flex justify-content-center">
                            @for ($i = 5; $i >= 1; $i--)
                                <input type="radio" id="star{{ $i }}" name="rating" value="{{ $i }}" wire:model.live="sales.rating" class="d-none">
                                <label for="star{{ $i }}" class="star-label mx-1" title="{{ $i }} stars">
                                    <i class="fa fa-star fs-3 star-icon {{ $sales['rating'] >= $i ? 'active' : '' }}"></i>
                                </label>
                            @endfor
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="feedback_type" class="form-label fw-bold">Feedback Type</label>
                        {{ html()->select('feedback_type', feedbackTypes())->attribute('wire:model', 'sales.feedback_type')->class('form-select')->placeholder('Select Type')->id('feedback_type') }}
                    </div>
                    <div class="form-group mb-4">
                        <label for="comment" class="form-label fw-bold">Your Comments</label>
                        <textarea class="form-control" wire:model="sales.feedback" id="comment" rows="4" placeholder="Please share your thoughts..."></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit Feedback</button>
                </div>
            </div>
        </div>
    </form>
    @push('styles')
        <style>
            .star-rating {
                position: relative;
            }

            .star-icon {
                color: #e4e4e4;
                cursor: pointer;
                transition: all 0.2s ease-in-out;
                filter: drop-shadow(0 0 1px rgba(0, 0, 0, 0.1));
            }

            .stars {
                display: flex;
                flex-direction: row-reverse;
                justify-content: center;
            }

            .star-label {
                cursor: pointer;
                padding: 0 2px;
            }

            .star-label:hover .star-icon,
            .star-label:hover~.star-label .star-icon {
                color: #ffd700;
                transform: scale(1.1);
                filter: drop-shadow(0 0 4px rgba(255, 215, 0, 0.4));
            }

            .star-icon.active {
                color: #ffd700;
                filter: drop-shadow(0 0 4px rgba(255, 215, 0, 0.4));
            }
        </style>
    @endpush
</div>
