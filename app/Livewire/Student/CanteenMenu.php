<?php

namespace App\Livewire\Student;

use App\Actions\Student\CanteenMenu\SaveAction;
use App\Actions\Student\PreOrder\MenuAction;
use App\Models\CanteenMenu as CanteenMenuModel;
use App\Models\Product;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Students → Canteen Menu: what each pre-order meal serves on each school day,
 * the same every week. Courses run down, school days across — the way a
 * caterer's menu sheet reads. Parents see it in the parent portal when they order.
 *
 * Nobody types a whole week from nothing: the quick-fill presets scaffold the
 * courses, borrow another meal's menu, repeat a dish across the week or draft
 * the empty days, and every dish field suggests the canteen's own products.
 */
class CanteenMenu extends Component
{
    public const WEEKDAYS = [7 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

    /**
     * Course scaffolds for a menu that has none yet — structure only, so the
     * school overwrites the names with its own wording straight away.
     *
     * @var array<string, array{label: string, icon: string, courses: array<int, array{0: string, 1: string}>}>
     */
    public const TEMPLATES = [
        'hot' => ['label' => 'Hot lunch', 'icon' => 'fa-cutlery', 'courses' => [['Main dish', '1 portion'], ['Side', 'Small bowl'], ['Fruit', 'Seasonal'], ['Drink', '200 ml']]],
        'box' => ['label' => 'Lunch box', 'icon' => 'fa-archive', 'courses' => [['Sandwich', '1 piece'], ['Snack', 'Small pack'], ['Fruit', 'Seasonal'], ['Drink', '200 ml']]],
        'breakfast' => ['label' => 'Breakfast', 'icon' => 'fa-coffee', 'courses' => [['Main dish', '1 portion'], ['Fruit', 'Seasonal'], ['Drink', '200 ml']]],
        'snack' => ['label' => 'Snack & drink', 'icon' => 'fa-glass', 'courses' => [['Snack', '1 pack'], ['Drink', '200 ml']]],
    ];

    /** Course names offered as chips while a course is unnamed: name => portion. */
    public const COURSE_PRESETS = [
        'Main dish' => '1 portion', 'Side' => 'Small bowl', 'Salad' => 'Small bowl', 'Soup' => '1 cup', 'Sandwich' => '1 piece',
        'Snack' => 'Small pack', 'Dessert' => '1 piece', 'Fruit' => 'Seasonal', 'Drink' => '200 ml',
    ];

    /** How many product names the dish suggestions carry to the browser. */
    private const SUGGESTION_LIMIT = 800;

    public $product_id = null;

    /** The meal whose saved menu the "copy a menu" preset brings over. */
    public $copy_from = null;

    /** @var array<int, array{name: string, note: string, dishes: array<int|string, array{name: string, description: string}>}> */
    public $courses = [];

    public function mount(): void
    {
        $this->product_id = $this->meals()->keys()->first();
        $this->loadMenu();
    }

    public function updatedProductId(): void
    {
        $this->resetErrorBag();
        $this->copy_from = null;
        $this->loadMenu();
    }

    public function addCourse(): void
    {
        if (count($this->courses) < CanteenMenuModel::MAX_COURSES) {
            $this->courses[] = $this->blankCourse();
        }
    }

    public function removeCourse(int $index): void
    {
        unset($this->courses[$index]);
        $this->courses = array_values($this->courses);
        if (! $this->courses) {
            $this->courses[] = $this->blankCourse();
        }
    }

    public function moveCourse(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (! isset($this->courses[$index], $this->courses[$target])) {
            return;
        }
        [$this->courses[$index], $this->courses[$target]] = [$this->courses[$target], $this->courses[$index]];
    }

    /* ── quick fill ──────────────────────────────────────────────────────
       Presets only shape what is on screen; nothing is written until Save. */

    /** Name the courses from a scaffold, leaving the dishes already typed alone. */
    public function applyTemplate(string $key): void
    {
        $this->guardEdit();
        $template = self::TEMPLATES[$key] ?? null;
        if (! $template) {
            return;
        }

        foreach ($template['courses'] as $index => [$name, $note]) {
            if ($index >= CanteenMenuModel::MAX_COURSES) {
                break;
            }
            $this->courses[$index] ??= $this->blankCourse();
            $this->courses[$index]['name'] = $name;
            $this->courses[$index]['note'] = $note;
        }

        $this->dispatch('success', ['message' => $template['label'].' courses ready — write the dishes, then save.']);
    }

    public function applyCoursePreset(int $index, string $name): void
    {
        $this->guardEdit();
        if (! isset($this->courses[$index]) || ! array_key_exists($name, self::COURSE_PRESETS)) {
            return;
        }
        $this->courses[$index]['name'] = $name;
        if (blank($this->courses[$index]['note'])) {
            $this->courses[$index]['note'] = self::COURSE_PRESETS[$name];
        }
    }

    /** Bring another meal's saved menu over, to edit and save as this meal's. */
    public function copyFromMeal(): void
    {
        $this->guardEdit();
        $meals = $this->meals();
        if (! $this->copy_from || ! $meals->has((int) $this->copy_from)) {
            $this->dispatch('error', ['message' => 'Choose the meal to copy from.']);

            return;
        }

        $menu = CanteenMenuModel::where('product_id', (int) $this->copy_from)->first();
        if (! $menu?->courses) {
            $this->dispatch('error', ['message' => $meals->get((int) $this->copy_from)->name.' has no menu to copy yet.']);

            return;
        }

        $this->courses = $this->normalise($menu->courses);
        $this->dispatch('success', ['message' => 'Copied from '.$meals->get((int) $this->copy_from)->name.' — nothing is saved until you press Save menu.']);
    }

    /** Write one dish into the rest of the week: the empty days, or all of them when none are empty. */
    public function repeatAcross(int $index, int $weekday): void
    {
        $this->guardEdit();
        $dish = $this->courses[$index]['dishes'][$weekday] ?? null;
        if (blank($dish['name'] ?? null)) {
            return;
        }

        $days = $this->schoolDays();
        $empty = array_filter($days, fn ($day) => blank($this->courses[$index]['dishes'][$day]['name'] ?? null));
        foreach ($empty ?: $days as $day) {
            $this->courses[$index]['dishes'][$day] = $dish;
        }
    }

    /** Copy a day's whole column into the week's other empty days. */
    public function copyDay(int $weekday): void
    {
        $this->guardEdit();
        foreach ($this->courses as $index => $course) {
            $dish = $course['dishes'][$weekday] ?? null;
            if (blank($dish['name'] ?? null)) {
                continue;
            }
            foreach ($this->schoolDays() as $day) {
                if ($day !== $weekday && blank($this->courses[$index]['dishes'][$day]['name'] ?? null)) {
                    $this->courses[$index]['dishes'][$day] = $dish;
                }
            }
        }
    }

    /**
     * Draft the empty days from the canteen's products — a starting point, not an
     * answer. One course when given an index, the whole week otherwise.
     */
    public function fillEmptyDays(?int $only = null): void
    {
        $this->guardEdit();
        $pool = $this->suggestions();
        if (! $pool) {
            $this->dispatch('error', ['message' => 'There are no canteen products to suggest yet. Add them under Products first.']);

            return;
        }

        $days = $this->schoolDays();
        $written = 0;
        foreach ($this->courses as $index => $course) {
            if ($only !== null && $index !== $only) {
                continue;
            }
            // No dish twice in one course's week, so a draft still reads like a menu.
            $taken = array_map('mb_strtolower', array_filter(array_column($course['dishes'], 'name')));
            $free = array_values(array_filter($pool, fn ($name) => ! in_array(mb_strtolower($name), $taken, true)));
            $free = $free ?: $pool;
            foreach (array_values($days) as $offset => $day) {
                if (filled($this->courses[$index]['dishes'][$day]['name'] ?? null)) {
                    continue;
                }
                $this->courses[$index]['dishes'][$day]['name'] = $free[($index + $offset) % count($free)];
                $written++;
            }
        }

        $this->dispatch($written ? 'success' : 'error', ['message' => $written ? 'Drafted '.$written.' '.str('day')->plural($written).' — change any of them, then save.' : 'Every day already has a dish.']);
    }

    public function clearDay(int $weekday): void
    {
        $this->guardEdit();
        foreach (array_keys($this->courses) as $index) {
            $this->courses[$index]['dishes'][$weekday] = ['name' => '', 'description' => ''];
        }
    }

    public function clearWeek(): void
    {
        $this->guardEdit();
        foreach (array_keys($this->courses) as $index) {
            foreach ($this->schoolDays() as $day) {
                $this->courses[$index]['dishes'][$day] = ['name' => '', 'description' => ''];
            }
        }
    }

    public function save(): void
    {
        $this->guardEdit();
        abort_unless($this->meals()->has((int) $this->product_id), 404);

        $response = (new SaveAction())->execute((int) $this->product_id, $this->courses, (int) Auth::id());
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->loadMenu();
        $this->dispatch('success', ['message' => $response['message']]);
    }

    public function render()
    {
        $settings = StudentSettings::current();

        return view('livewire.student.canteen-menu', [
            'settings' => $settings,
            'meals' => $this->meals(),
            'days' => array_intersect_key(self::WEEKDAYS, array_flip($settings->schoolDays)),
            'canEdit' => (bool) Auth::user()?->can('student menu.edit'),
            'suggestions' => $this->suggestions(),
        ]);
    }

    /**
     * The pre-order menu's products (Settings → Student Settings), keyed by id.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function meals()
    {
        $settings = StudentSettings::current();

        return $settings->preOrderCategoryIds
            ? MenuAction::query($settings)->orderBy('name')->get(['id', 'name', 'mrp'])->keyBy('id')
            : collect();
    }

    /**
     * Dish suggestions: the canteen's own selling products, minus the meals
     * themselves (those are what is being ordered, not what is served).
     *
     * @return array<int, string>
     */
    private function suggestions(): array
    {
        $settings = StudentSettings::current();
        $mealIds = $settings->preOrderCategoryIds ? MenuAction::query($settings)->pluck('id')->all() : [];

        return Product::query()
            ->where('is_selling', true)
            ->where('status', 'active')
            ->where('type', 'product')
            ->when($mealIds, fn ($query) => $query->whereNotIn('id', $mealIds))
            ->orderBy('name')
            ->limit(self::SUGGESTION_LIMIT)
            ->pluck('name')
            ->all();
    }

    /** @return array<int, int> the ISO weekdays the canteen serves */
    private function schoolDays(): array
    {
        return array_values(array_intersect(array_keys(self::WEEKDAYS), StudentSettings::current()->schoolDays));
    }

    private function guardEdit(): void
    {
        abort_unless(Auth::user()?->can('student menu.edit'), 403);
    }

    private function loadMenu(): void
    {
        $menu = $this->product_id ? CanteenMenuModel::where('product_id', $this->product_id)->first() : null;

        $this->courses = $this->normalise($menu?->courses ?? []);
    }

    /** Every course with a slot for every weekday, so the grid can bind to it. */
    private function normalise(array $courses): array
    {
        return collect($courses)
            ->map(fn (array $course) => [
                'name' => (string) ($course['name'] ?? ''),
                'note' => (string) ($course['note'] ?? ''),
                'dishes' => collect(self::WEEKDAYS)->keys()->mapWithKeys(fn ($day) => [$day => [
                    'name' => (string) ($course['dishes'][$day]['name'] ?? ''),
                    'description' => (string) ($course['dishes'][$day]['description'] ?? ''),
                ]])->all(),
            ])
            ->values()
            ->all() ?: [$this->blankCourse()];
    }

    private function blankCourse(): array
    {
        return [
            'name' => '',
            'note' => '',
            'dishes' => collect(self::WEEKDAYS)->keys()->mapWithKeys(fn ($day) => [$day => ['name' => '', 'description' => '']])->all(),
        ];
    }
}
