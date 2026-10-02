<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => Ticket::STATUS_OPEN,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function inGroup(string $group): static
    {
        return $this->state(['group' => $group]);
    }
}
