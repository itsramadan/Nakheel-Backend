<?php

namespace Database\Factories;

use App\Enums\SubscriptionCycle;
use App\Models\Client;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Farm>
 */
class FarmFactory extends Factory
{
    protected $model = Farm::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
            'name' => 'مزرعة '.fake()->word(),
            'city' => fake()->randomElement(['الفيوم', 'أسوان', 'المنيا', 'البحيرة', 'الشرقية']),
            'palm_count' => fake()->numberBetween(50, 400),
            'area' => fake()->randomFloat(2, 10, 60),
            'location' => fake()->streetName(),
            'subscription_cycle' => SubscriptionCycle::Monthly->value,
            'subscription_next_payment_date' => now()->addDays(15),
        ];
    }
}
