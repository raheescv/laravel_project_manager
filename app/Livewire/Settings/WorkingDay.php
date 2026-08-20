<?php

namespace App\Livewire\Settings;

use App\Models\WorkingDay as WorkingDayModel;
use Livewire\Component;

/**
 * The company working week: the days the business opens and the hours it keeps
 * on each of them.
 *
 * These hours are what the appointment scheduler offers for any salesman who has
 * no weekly availability of their own, so this screen — not a PHP config file —
 * is where a tenant answers "when do we work".
 */
class WorkingDay extends Component
{
    public $days = [];

    /** Module defaults, shown as the hint for any hours left unset. */
    public $defaults = [];

    public function mount()
    {
        $this->defaults = WorkingDayModel::moduleDefaults();

        $this->days = WorkingDayModel::orderBy('order_no')->get()->map(function ($day) {
            $timing = $day->timing();

            return [
                'id' => $day->id,
                'day_name' => $day->day_name,
                'is_working' => (bool) $day->is_working,
                'start_time' => $timing['start_time'],
                'end_time' => $timing['end_time'],
            ];
        })->toArray();
    }

    /**
     * Create the seven-day week for a tenant that has none.
     *
     * Without rows this screen has nothing to show and the scheduler silently
     * runs on the module defaults, which nobody can see or edit. Writing the
     * week out makes that invisible default a real, editable setting.
     */
    public function createDefaultWeek()
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        $defaults = WorkingDayModel::moduleDefaults();
        $working = array_map('intval', (array) config('property_appointment.default_availability.days', []));

        foreach (array_keys(WorkingDayModel::DAY_INDEX) as $index => $name) {
            WorkingDayModel::firstOrCreate(['day_name' => ucfirst($name)], [
                'is_working' => in_array($index, $working, true),
                'start_time' => $defaults['start_time'],
                'end_time' => $defaults['end_time'],
                'order_no' => $index,
            ]);
        }

        $this->mount();

        $this->dispatch('success', ['message' => 'The default working week was created. Adjust it to suit and save.']);
    }

    /** Spread the first working day's hours across the whole week. */
    public function applyToAll()
    {
        $source = collect($this->days)->firstWhere('is_working', true) ?? ($this->days[0] ?? null);

        if (! $source) {
            return;
        }

        foreach ($this->days as $index => $day) {
            $this->days[$index]['start_time'] = $source['start_time'];
            $this->days[$index]['end_time'] = $source['end_time'];
        }
    }

    public function updateSettings()
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        if ($message = $this->firstProblem()) {
            $this->dispatch('error', ['message' => $message]);

            return;
        }

        foreach ($this->days as $dayData) {
            WorkingDayModel::where('id', $dayData['id'])->update([
                'is_working' => $dayData['is_working'],
                // Blank hours are stored as NULL, never '' — a null column is
                // what tells the model to borrow the module default.
                'start_time' => $dayData['start_time'] ?: null,
                'end_time' => $dayData['end_time'] ?: null,
            ]);
        }

        $this->dispatch('success', ['message' => 'Settings Updated Successfully']);
    }

    /**
     * The first thing wrong with the week, or null when it is sound.
     *
     * Only working days are checked — hours left blank on a closed day are not
     * a mistake, because nothing ever reads them.
     */
    private function firstProblem(): ?string
    {
        foreach ($this->days as $day) {
            if (! $day['is_working']) {
                continue;
            }

            $name = ucfirst(strtolower((string) $day['day_name']));
            $start = (string) $day['start_time'];
            $end = (string) $day['end_time'];

            if ($start === '' || $end === '') {
                return $name.' is a working day, so it needs both an opening and a closing time.';
            }

            if (strtotime($end) <= strtotime($start)) {
                return $name."'s closing time must be after its opening time.";
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.settings.working-day');
    }
}
