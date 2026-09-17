<div>
    @if (!$settings->preOrdersEnabled || $meals->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-2"><i class="fa fa-cutlery me-1"></i> Set up pre-orders first</h5>
                <p class="text-body-secondary mb-3">
                    The canteen menu is written for the meals parents can pre-order. Switch on <strong>Canteen pre-orders</strong> and choose the categories on the
                    pre-order menu in <strong>Settings → Student Cards</strong>. The selling products in those categories then show here.
                </p>
                @can('student settings.edit')
                    <a href="{{ route('settings::index') }}" class="btn btn-primary btn-sm"><i class="fa fa-cog me-1"></i> Open Settings</a>
                @endcan
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <span class="form-label fw-medium small mb-2 d-block">Meal</span>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($meals as $id => $name)
                        <input type="radio" class="btn-check" name="cm_meal" id="cm_meal_{{ $id }}" value="{{ $id }}" wire:model.live="product_id" autocomplete="off">
                        <label class="btn btn-outline-primary" for="cm_meal_{{ $id }}">{{ $name }}</label>
                    @endforeach
                </div>
                <div class="form-text">
                    Parents order the meal for a day and the till sells it at its price. The dishes below are what they see for each day. A day with no dishes means the meal is not
                    served that day.
                </div>
            </div>
        </div>

        <form wire:submit="save">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                    <h5 class="mb-0"><i class="fa fa-th me-1"></i> Weekly menu <span class="text-body-secondary fw-normal small">· the same every week</span></h5>
                    @if ($canEdit && count($courses) < \App\Models\CanteenMenu::MAX_COURSES)
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addCourse"><i class="fa fa-plus me-1"></i> Add course</button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-top mb-0" style="min-width: {{ 220 + count($days) * 220 }}px">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 220px">Course</th>
                                    @foreach ($days as $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $index => $course)
                                    <tr wire:key="course-{{ $index }}">
                                        <td class="bg-body-tertiary">
                                            <input type="text" class="form-control form-control-sm fw-semibold mb-1" wire:model="courses.{{ $index }}.name" maxlength="60" placeholder="e.g. Main dish" @disabled(!$canEdit)>
                                            <input type="text" class="form-control form-control-sm mb-2" wire:model="courses.{{ $index }}.note" maxlength="80" placeholder="Portion (optional)" @disabled(!$canEdit)>
                                            @if ($canEdit)
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-light" title="Move up" wire:click="moveCourse({{ $index }}, -1)" @disabled($index === 0)><i class="fa fa-arrow-up"></i></button>
                                                    <button type="button" class="btn btn-light" title="Move down" wire:click="moveCourse({{ $index }}, 1)" @disabled($index === count($courses) - 1)><i class="fa fa-arrow-down"></i></button>
                                                    <button type="button" class="btn btn-light text-danger" title="Remove course" wire:click="removeCourse({{ $index }})" wire:confirm="Remove this course and its dishes?"><i class="fa fa-trash"></i></button>
                                                </div>
                                            @endif
                                        </td>
                                        @foreach ($days as $day => $label)
                                            <td>
                                                <input type="text" class="form-control form-control-sm fw-semibold mb-1" wire:model="courses.{{ $index }}.dishes.{{ $day }}.name" maxlength="80" placeholder="Dish"
                                                    aria-label="{{ $label }} dish" @disabled(!$canEdit)>
                                                <textarea class="form-control form-control-sm" rows="3" wire:model="courses.{{ $index }}.dishes.{{ $day }}.description" maxlength="200" placeholder="Short description (optional)"
                                                    aria-label="{{ $label }} description" @disabled(!$canEdit)></textarea>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($canEdit)
                    <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="small text-body-secondary">Leave a cell empty when there is no dish that day. Changes show to parents straight away.</span>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save menu</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    </div>
                @endif
            </div>
        </form>
    @endif
</div>
