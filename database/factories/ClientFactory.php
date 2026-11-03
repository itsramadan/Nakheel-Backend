<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'name' => fake()->name(),
            'phone' => '01'.fake()->unique()->numerify('#########'),
            'city' => fake()->randomElement(['الفيوم', 'أسوان', 'المنيا', 'البحيرة', 'الشرقية']),
            'phone_verified_at' => now(),
        ];
    }
}
