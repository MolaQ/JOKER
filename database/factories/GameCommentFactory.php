<?php

namespace Database\Factories;

use App\Models\GameComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameComment>
 */
class GameCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->randomElement([
                'Brawo chłopaki, tak trzymać!',
                'Świetny mecz, emocje do ostatniej piłki.',
                'Gratulacje dla całej drużyny i trenerów!',
                'Byliśmy na hali całą rodziną - super atmosfera.',
                'Ten blok w trzecim secie to majstersztyk.',
                'Do zobaczenia na kolejnym meczu, będziemy kibicować!',
                'Widać postęp z meczu na mecz. Oby tak dalej.',
                'Dziękujemy za relację i zdjęcia!',
            ]),
            'is_approved' => fake()->boolean(90), // 90% szans na zatwierdzenie
        ];
    }
}
