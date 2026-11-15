<?php

namespace App\Actions\V1\Parent;

use App\Models\Branch;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Str;

/**
 * What the portal's public registration form offers. The schools are the
 * branches shown on the showcase; no schools means registration is closed
 * (switched off in Settings, or no branch on the showcase).
 */
class GetRegistrationFormAction
{
    /**
     * @return array{schools: list<array{value: string, name: string, group: string, place: string, initial: string}>, classes: list<string>, divisions: list<string>}
     */
    public function execute(): array
    {
        $settings = StudentSettings::current();

        return [
            'schools' => $settings->registrationOpen ? StudentSettings::registrationBranches()->map($this->schoolTile(...))->values()->all() : [],
            'classes' => $settings->registrationClasses,
            'divisions' => $settings->registrationDivisions,
        ];
    }

    /**
     * A branch as a tile: the value sent back is its id, and what the parent reads
     * is the branch's location (its name when no location is set).
     *
     * @return array{value: string, name: string, group: string, place: string, initial: string}
     */
    private function schoolTile(Branch $branch): array
    {
        $label = $branch->locationLabel();

        return [
            'value' => (string) $branch->id,
            'name' => $label,
            'group' => '',
            'place' => $label,
            'initial' => Str::upper(Str::substr($label, 0, 1)),
        ];
    }
}
