<?php

namespace Modules\Library\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Library\Models\Book;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            // Faker's isbn13() may not exist in this Faker version — fake an
            // ISBN-shaped string instead.
            'isbn' => fake()->numerify('978-##-####-###-#'),
            'category' => fake()->randomElement(['Fiksi', 'Non-Fiksi', 'Sains', 'Teknologi', 'Sejarah', 'Ekonomi']),
            'stock' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
