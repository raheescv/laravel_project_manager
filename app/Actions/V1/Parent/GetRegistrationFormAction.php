<?php

namespace App\Actions\V1\Parent;

use App\Support\Student\StudentSettings;
use Illuminate\Support\Str;

/**
 * What the portal's public registration form offers. No schools means the
 * school has not opened registration.
 */
class GetRegistrationFormAction
{
    /**
     * @return array{schools: list<array{value: string, group: string, place: string, initial: string}>, classes: list<string>, divisions: list<string>}
     */
    public function execute(): array
    {
        $settings = StudentSettings::current();

        return [
            'schools' => array_map($this->schoolTile(...), $settings->registrationSchools),
            'classes' => $settings->registrationClasses,
            'divisions' => $settings->registrationDivisions,
        ];
    }

    /**
     * "AIA - Doha" becomes a small "AIA" above a large "Doha".
     *
     * @return array{value: string, group: string, place: string, initial: string}
     */
    private function schoolTile(string $school): array
    {
        $hasGroup = str_contains($school, '-');
        $place = $hasGroup ? trim(Str::after($school, '-')) : $school;

        return [
            'value' => $school,
            'group' => $hasGroup ? trim(Str::before($school, '-')) : '',
            'place' => $place,
            'initial' => Str::upper(Str::substr($place, 0, 1)),
        ];
    }
}
