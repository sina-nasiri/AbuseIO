<?php

namespace Database\Factories;

use AbuseIO\Models\Contact;
use AbuseIO\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        return [
            'name'       => uniqid() . $this->faker->domainName,
            'contact_id' => Contact::all()->random()->id,
            'enabled'    => $this->faker->boolean(),
        ];
    }
}
