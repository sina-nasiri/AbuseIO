<?php

namespace Database\Factories;

use AbuseIO\Models\Note;
use AbuseIO\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        $ticket = Ticket::all()->random() ?? Ticket::factory()->create();

        return [
            'ticket_id' => $ticket->id,
            'submitter' => $this->faker->userName,
            'text'      => $this->faker->sentence($this->faker->numberBetween(5, 10)),
            'hidden'    => $this->faker->boolean(),
            'viewed'    => $this->faker->boolean(),
        ];
    }
}
