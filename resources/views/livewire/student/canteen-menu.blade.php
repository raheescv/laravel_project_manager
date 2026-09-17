@php
    use App\Livewire\Student\CanteenMenu as CanteenMenuComponent;
    use App\Models\CanteenMenu as CanteenMenuModel;

    $meal = $product_id ? $meals->get((int) $product_id) : null;
    // Alpine needs the school days in school order (Sunday first), which an
    // integer-keyed object would resort numerically.
    $dayList = collect($days)->map(fn ($label, $iso) => ['iso' => (int) $iso, 'label' => $label])->values()->all();
@endphp

{{-- Students → Canteen Menu. Design system: <x-student.canteen-menu-premium /> (scope .cmx .cma).
     Courses down, school days across. The counters and the parent preview are read from the
     fields themselves, so they follow what is typed without a request; the quick-fill presets
     and the per-day tools go through the component. --}}
<div class="cmx cma" x-data="cmxMenu(@js($dayList))">
    <x-student.canteen-menu-premium />

    @if (!$settings->preOrdersEnabled || $meals->isEmpty())
        <div class="sheet rise">
            <div class="secline"><span class="n"><i class="fa fa-cutlery"></i></span>
                <div><h5>Set up pre-orders first</h5><p>The canteen menu is written for the meals parents can pre-order.</p></div>
            </div>
            <div class="p-3 pt-2">
                <p class="text-body-secondary mb-3" style="font-size: 13px; max-width: 640px;">
                    Switch on <strong>Canteen pre-orders</strong> and choose the categories on the pre-order menu in <strong>Settings → Student Cards</strong>.
                    The selling products in those categories then show here, each with its own weekly menu.
                </p>
                @can('student settings.edit')
                    <a href="{{ route('settings::index') }}" class="btn btn-primary btn-sm"><i class="fa fa-cog me-1"></i> Open Settings</a>
                @endcan
            </div>
        </div>
    @else
        {{-- Hero: the meal being written, and how far the week has got --}}
        <div class="sheet top mb-3 rise" style="--i:0">
            <div class="min-w-0">
                <a href="{{ route('student::index') }}" class="eyebrow" title="Back to students"><i class="fa fa-graduation-cap"></i>Students · Canteen menu</a>
                <h4>{{ $meal?->name }} — what it serves each week</h4>
                <p>The same menu every week. Leave a day empty and the meal is not served that day.</p>
            </div>
            <div class="stat">
                <div><b>{{ count($courses) }}</b><span>{{ str('Course')->plural(count($courses)) }}</span></div>
                <div><b x-text="served + '/' + days.length">&nbsp;</b><span>Days served</span></div>
                <div><b x-text="filled + '/' + total">&nbsp;</b><span>Dishes written</span></div>
            </div>
        </div>

        {{-- 1 · which meal --}}
        <div class="sheet mb-3 rise" style="--i:1">
            <div class="secline"><span class="n">1</span>
                <div><h5>Which meal</h5><p>Every meal parents can pre-order has its own weekly menu.</p></div>
            </div>
            <div class="p-3 pt-2">
                <div class="meals">
                    @foreach ($meals as $id => $item)
                        <input type="radio" class="btn-check" name="cm_meal" id="cm_meal_{{ $id }}" value="{{ $id }}" wire:model.live="product_id" autocomplete="off">
                        <label class="mealc" for="cm_meal_{{ $id }}">
                            <span class="mi"><i class="fa fa-cutlery"></i></span>
                            <span><b>{{ $item->name }}</b><small>{{ currency($item->mrp) }}</small></span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 2 · quick fill: the presets that write the week for them --}}
        @if ($canEdit)
            <div class="sheet mb-3 rise" style="--i:2">
                <div class="secline"><span class="n">2</span>
                    <div><h5>Quick fill</h5><p>Start from a ready set of courses, borrow another meal's menu, or let the canteen's own items fill the gaps.</p></div>
                </div>
                <div class="qf">
                    <div class="qfrow">
                        <span class="ttl"><i class="fa fa-th-list"></i>Course template</span>
                        <div class="chips">
                            @foreach (CanteenMenuComponent::TEMPLATES as $key => $template)
                                <button type="button" class="chip tmpl" wire:click="applyTemplate('{{ $key }}')" wire:loading.attr="disabled">
                                    <i class="fa {{ $template['icon'] }}"></i>{{ $template['label'] }}
                                </button>
                            @endforeach
                        </div>
                        <span class="hint">Sets the course names and portions — the dishes already typed stay.</span>
                    </div>
                    @if ($meals->count() > 1)
                        <div class="qfrow">
                            <span class="ttl"><i class="fa fa-clone"></i>Copy a menu</span>
                            <select class="sel" wire:model="copy_from" aria-label="Meal to copy the menu from">
                                <option value="">Choose a meal…</option>
                                @foreach ($meals as $id => $item)
                                    @if ((int) $id !== (int) $product_id)
                                        <option value="{{ $id }}">{{ $item->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="button" class="chip tmpl" wire:click="copyFromMeal" wire:loading.attr="disabled">
                                <i class="fa fa-arrow-down"></i>Copy into {{ $meal?->name }}
                            </button>
                            <span class="hint">Brings its courses and dishes over, then edit what differs.</span>
                        </div>
                    @endif
                    <div class="qfrow">
                        <span class="ttl"><i class="fa fa-magic"></i>Fill the gaps</span>
                        <button type="button" class="chip tmpl" wire:click="fillEmptyDays" wire:loading.attr="disabled">
                            <i class="fa fa-bolt"></i>Draft the empty days
                        </button>
                        <button type="button" class="chip tmpl" wire:click="clearWeek" wire:confirm="Clear every dish in this week?">
                            <i class="fa fa-eraser"></i>Clear the week
                        </button>
                        <span class="hint">Writes a draft from the canteen's products — change any of them, then save.</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- 3 · the week --}}
        <form wire:submit="save">
            <div class="sheet mb-3 rise" style="--i:3">
                <div class="secline"><span class="n">3</span>
                    <div><h5>The week</h5><p>Courses down, school days across. An empty cell is a day that course is not served.</p></div>
                    @if ($canEdit && count($courses) < CanteenMenuModel::MAX_COURSES)
                        <div class="r"><button type="button" class="chip" wire:click="addCourse"><i class="fa fa-plus"></i>Add course</button></div>
                    @endif
                </div>
                <div class="pt-2"></div>

                <div class="grid">
                    <table>
                        <thead>
                            <tr>
                                <th class="cx"><span class="lbl">Course</span></th>
                                @foreach ($days as $iso => $label)
                                    <th style="min-width: 192px">
                                        <div class="dayh">
                                            <div>
                                                <div class="dn">{{ $label }}</div>
                                                <div class="dc" x-text="dayLabel({{ $iso }})">&nbsp;</div>
                                            </div>
                                            @if ($canEdit)
                                                <div class="dt">
                                                    <button type="button" class="tool" title="Copy {{ $label }} into the week's empty days" wire:click="copyDay({{ $iso }})"><i class="fa fa-clone"></i></button>
                                                    <button type="button" class="tool danger" title="Clear {{ $label }}" wire:click="clearDay({{ $iso }})" wire:confirm="Clear every dish on {{ $label }}?"><i class="fa fa-eraser"></i></button>
                                                </div>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $index => $course)
                                <tr wire:key="course-{{ $index }}">
                                    <td class="cx">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="idx">{{ $index + 1 }}</span>
                                            <input type="text" class="form-control fw-semibold" wire:model="courses.{{ $index }}.name" data-course-name data-c="{{ $index }}" maxlength="60"
                                                placeholder="e.g. Main dish" aria-label="Course {{ $index + 1 }} name" x-on:input="sync()" @disabled(!$canEdit)>
                                        </div>
                                        <input type="text" class="form-control mt-1" wire:model="courses.{{ $index }}.note" data-course-note data-c="{{ $index }}" maxlength="80"
                                            placeholder="Portion (optional)" aria-label="Course {{ $index + 1 }} portion" x-on:input="sync()" @disabled(!$canEdit)>
                                        @if ($canEdit)
                                            @if (blank($course['name']))
                                                <div class="chips mt-1">
                                                    @foreach (CanteenMenuComponent::COURSE_PRESETS as $name => $note)
                                                        <button type="button" class="chip" title="Name it {{ $name }} ({{ $note }})" wire:click="applyCoursePreset({{ $index }}, '{{ $name }}')">{{ $name }}</button>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="ctools">
                                                <button type="button" class="tool" title="Draft this course's empty days" wire:click="fillEmptyDays({{ $index }})"><i class="fa fa-magic"></i></button>
                                                <button type="button" class="tool" title="Move up" wire:click="moveCourse({{ $index }}, -1)" @disabled($index === 0)><i class="fa fa-arrow-up"></i></button>
                                                <button type="button" class="tool" title="Move down" wire:click="moveCourse({{ $index }}, 1)" @disabled($index === count($courses) - 1)><i class="fa fa-arrow-down"></i></button>
                                                <button type="button" class="tool danger" title="Remove course" wire:click="removeCourse({{ $index }})" wire:confirm="Remove this course and its dishes?"><i class="fa fa-trash"></i></button>
                                            </div>
                                        @endif
                                    </td>
                                    @foreach ($days as $iso => $label)
                                        <td>
                                            <div class="cell" data-c="{{ $index }}" data-d="{{ $iso }}" :class="{ empty: !isFilled({{ $index }}, {{ $iso }}), focused: isFocus({{ $index }}, {{ $iso }}) }">
                                                <input type="text" class="form-control dishn mb-1" wire:model="courses.{{ $index }}.dishes.{{ $iso }}.name" data-cell-name data-c="{{ $index }}" data-d="{{ $iso }}"
                                                    maxlength="80" placeholder="Dish" aria-label="{{ $label }} dish" autocomplete="off"
                                                    x-on:focus="focusCell({{ $index }}, {{ $iso }})" x-on:input="sync()" @disabled(!$canEdit)>
                                                <textarea class="form-control dishd" rows="2" wire:model="courses.{{ $index }}.dishes.{{ $iso }}.description" data-cell-desc data-c="{{ $index }}" data-d="{{ $iso }}"
                                                    maxlength="200" placeholder="Short description (optional)" aria-label="{{ $label }} description"
                                                    x-on:focus="focusCell({{ $index }}, {{ $iso }})" x-on:input="sync()" @disabled(!$canEdit)></textarea>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- the suggestion strip: the canteen's products for whichever cell is being written --}}
                @if ($canEdit)
                    <div class="sugbar" x-show="focus" x-cloak>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <span class="who"><i class="fa fa-lightbulb-o me-1"></i><span x-text="focusLabel()"></span></span>
                            <input type="search" class="find ms-auto" placeholder="Search canteen items…" aria-label="Search canteen items" x-model="q">
                            <button type="button" class="tool" title="Hide suggestions" x-on:click="focus = null"><i class="fa fa-times"></i></button>
                        </div>
                        <div class="chips">
                            <span class="chipgrp">Canteen items</span>
                            <template x-for="name in matches()" :key="name">
                                <button type="button" class="chip" x-on:click="pick(name)"><i class="fa fa-tag"></i><span x-text="name"></span></button>
                            </template>
                            <span class="hint" style="font-size: 11px; color: var(--mut)" x-show="!matches().length"
                                x-text="dishes.length ? 'Nothing matches — type the dish instead' : 'No canteen products yet. Add them under Products.'"></span>
                        </div>
                        <div class="chips mt-2">
                            <button type="button" class="chip" x-on:click="$wire.repeatAcross(focus.c, focus.d)"><i class="fa fa-arrows-h"></i>Repeat all week</button>
                            <button type="button" class="chip" x-on:click="clearCell()"><i class="fa fa-eraser"></i>Clear this dish</button>
                        </div>
                    </div>
                @endif

                <div class="foot">
                    <span class="note"><i class="fa fa-info-circle me-1"></i>An empty day means the meal is not served that day. Parents see the change as soon as you save.</span>
                    @if ($canEdit)
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save menu</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    @endif
                </div>
            </div>
        </form>

        {{-- 4 · the same week, as the parent portal shows it --}}
        <div class="sheet mb-3 rise" style="--i:4">
            <div class="secline"><span class="n"><i class="fa fa-mobile"></i></span>
                <div><h5>What parents see</h5><p>{{ $meal?->name }} in the parent portal, day by day.</p></div>
                <div class="r">
                    <span class="pill" :class="served ? 'ok' : 'warn'"
                        x-text="served ? 'Served on ' + served + ' of ' + days.length + ' school days' : 'Not on the menu yet'">&nbsp;</span>
                </div>
            </div>
            <div class="p-3 pt-2">
                <div class="phones">
                    <template x-for="day in week" :key="day.iso">
                        <div class="phone" :class="{ dim: !day.rows.length }">
                            <div class="ph">
                                <i class="fa fa-cutlery" style="color: var(--acc)"></i>
                                <div><b x-text="day.label"></b><small class="d-block">order before {{ $settings->preOrderCutoff }}</small></div>
                            </div>
                            <template x-for="(row, i) in day.rows" :key="i">
                                <div class="prow">
                                    <div class="pc" x-text="row.course + (row.note ? ' · ' + row.note : '')"></div>
                                    <div class="pn" x-text="row.name"></div>
                                    <div class="pd" x-show="row.description" x-text="row.description"></div>
                                </div>
                            </template>
                            <div class="pempty" x-show="!day.rows.length">
                                <i class="fa fa-moon-o d-block mb-1" style="font-size: 18px"></i>Not served
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    @endif

    @once
        @push('scripts')
            <script>
                window.cmxDishes = @js($suggestions);

                document.addEventListener('alpine:init', () => {
                    Alpine.data('cmxMenu', (days) => ({
                        days,
                        dishes: window.cmxDishes || [],
                        week: [],
                        keys: new Set(),
                        courseCount: 0,
                        filled: 0,
                        served: 0,
                        total: 0,
                        focus: null,
                        q: '',

                        init() {
                            this.sync();
                            // The presets and the day tools run on the server; re-read the
                            // fields once their answer has been morphed in.
                            window.addEventListener('cmx-refresh', () => this.sync());
                        },

                        cell(course, day, kind) {
                            return this.$root.querySelector('[data-cell-' + kind + '][data-c="' + course + '"][data-d="' + day + '"]');
                        },

                        /* The fields are the truth: counters and the parent preview are read
                           straight from them, so typing needs no request. */
                        sync() {
                            const root = this.$root;
                            const courses = Array.from(root.querySelectorAll('[data-course-name]')).map((el) => ({
                                index: Number(el.dataset.c),
                                name: el.value.trim(),
                                note: (root.querySelector('[data-course-note][data-c="' + el.dataset.c + '"]') || {}).value || '',
                            }));
                            const keys = new Set();

                            this.week = this.days.map((day) => {
                                const rows = [];
                                courses.forEach((course) => {
                                    const name = ((this.cell(course.index, day.iso, 'name') || {}).value || '').trim();
                                    if (!name) return;
                                    keys.add(course.index + ':' + day.iso);
                                    rows.push({
                                        course: course.name || 'Course ' + (course.index + 1),
                                        note: course.note.trim(),
                                        name: name,
                                        description: ((this.cell(course.index, day.iso, 'desc') || {}).value || '').trim(),
                                    });
                                });
                                return { iso: day.iso, label: day.label, rows: rows };
                            });

                            this.keys = keys;
                            this.courseCount = courses.length;
                            this.filled = keys.size;
                            this.served = this.week.filter((day) => day.rows.length).length;
                            this.total = courses.length * this.days.length;
                        },

                        isFilled(course, day) { return this.keys.has(course + ':' + day); },
                        isFocus(course, day) { return !!this.focus && this.focus.c === course && this.focus.d === day; },

                        dayLabel(iso) {
                            const day = this.week.find((entry) => entry.iso === iso);
                            const written = day ? day.rows.length : 0;
                            return written ? written + ' of ' + this.courseCount + ' written' : 'not served';
                        },

                        focusCell(course, day) { this.focus = { c: course, d: day }; this.q = ''; },

                        focusLabel() {
                            if (!this.focus) return '';
                            const course = this.$root.querySelector('[data-course-name][data-c="' + this.focus.c + '"]');
                            const day = this.days.find((entry) => entry.iso === this.focus.d);
                            return ((course && course.value.trim()) || 'Course ' + (this.focus.c + 1)) + ' · ' + (day ? day.label : '');
                        },

                        matches() {
                            const query = this.q.trim().toLowerCase();
                            const list = query ? this.dishes.filter((name) => name.toLowerCase().includes(query)) : this.dishes;
                            return list.slice(0, 12);
                        },

                        /* Writing a suggestion into the field and into Livewire's copy of it,
                           without a request: the value rides along with the next one. */
                        write(value) {
                            if (!this.focus) return;
                            const field = this.cell(this.focus.c, this.focus.d, 'name');
                            if (!field) return;
                            field.value = value;
                            this.$wire.set('courses.' + this.focus.c + '.dishes.' + this.focus.d + '.name', value, false);
                            field.focus();
                            this.sync();
                        },

                        pick(name) { this.write(name); },

                        clearCell() {
                            if (!this.focus) return;
                            const description = this.cell(this.focus.c, this.focus.d, 'desc');
                            if (description) {
                                description.value = '';
                                this.$wire.set('courses.' + this.focus.c + '.dishes.' + this.focus.d + '.description', '', false);
                            }
                            this.write('');
                        },
                    }));
                });

                document.addEventListener('livewire:init', () => {
                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => setTimeout(() => window.dispatchEvent(new CustomEvent('cmx-refresh')), 0));
                    });
                });
            </script>
        @endpush
    @endonce
</div>
