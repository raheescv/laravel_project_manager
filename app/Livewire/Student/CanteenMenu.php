<?php

namespace App\Livewire\Student;

use App\Actions\Student\CanteenMenu\SaveAction;
use App\Actions\Student\PreOrder\MenuAction;
use App\Models\CanteenMenu as CanteenMenuModel;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Students → Canteen Menu: what each pre-order meal serves on each school day,
 * the same every week. Courses run down, school days across — the way a
 * caterer's menu sheet reads. Parents see it in the parent portal when they order.
 */
class CanteenMenu extends Component
{
    public const WEEKDAYS = [7 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

    public $product_id = null;

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

    public function save(): void
    {
        abort_unless(Auth::user()?->can('student menu.edit'), 403);
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
        ]);
    }

    /** The pre-order menu's products (Settings → Student Settings), id => name. */
    private function meals()
    {
        $settings = StudentSettings::current();

        return $settings->preOrderCategoryIds
            ? MenuAction::query($settings)->orderBy('name')->pluck('name', 'id')
            : collect();
    }

    private function loadMenu(): void
    {
        $menu = $this->product_id ? CanteenMenuModel::where('product_id', $this->product_id)->first() : null;

        $this->courses = collect($menu?->courses ?? [])
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
