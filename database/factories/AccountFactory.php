<?php

namespace Database\Factories;

use AbuseIO\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name'          => $this->faker->name,
            'description'   => $this->faker->sentence(rand(6, 10)),
            'disabled'      => rand(0, 1),
            'token'         => generateApiToken(),
            'systemaccount' => 0,
            'brand_id'      => 1,
        ];
    }
}
