<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Examples;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Traits\UsesTurboSeeder;

/**
 * Example seeder class demonstrating various ways to use the TurboSeeder.
 *
 * @see \IzAhmad\TurboSeeder\Traits\UsesTurboSeeder
 */
class ExampleSeeder extends Seeder
{
    use UsesTurboSeeder;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // example 1: Basic fluent API usage

        $uniqueEmail = $this->uniqueEmail('user'); // method comes from the trait UsesTurboSeeder

        TurboSeeder::create('users')
            ->columns(['name', 'email', 'password', 'remember_token', 'created_at'])
            ->generate(fn ($index) => [
                'name' => "User {$index}",
                'email' => $uniqueEmail($index),
                'password' => Hash::make("password{$index}"),
                'remember_token' => Str::random(10),
                'created_at' => now(),
            ])
            ->count(10000)
            ->run();

        // example 2: Using CSV strategy for maximum speed
        TurboSeeder::create('posts')
            ->columns(['user_id', 'title', 'content', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => ($index % 10000) + 1,
                'title' => "Post Title {$index}",
                'content' => "This is the content for post {$index}",
                'created_at' => now(),
            ])
            ->count(100000)
            ->useCsvStrategy()
            ->run();

        // example 3: Custom configuration
        TurboSeeder::create('orders')
            ->columns(['user_id', 'total', 'status', 'payment_method', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => ($index % 10000) + 1,
                'total' => random_int(1000, 99999) / 100,
                'status' => ['pending', 'completed', 'cancelled'][random_int(0, 2)],
                'payment_method' => ['paypal', 'bank_transfer'][random_int(0, 1)],
                'created_at' => now(),
            ])
            ->count(50000)
            ->chunkSize(2000)
            ->withProgressTracking()
            ->disableForeignKeyChecks()
            ->run();

        // example 4: Conditional execution
        TurboSeeder::create('products')
            ->columns(['name', 'price', 'stock', 'description', 'created_at'])
            ->generate(fn ($index) => [
                'name' => "Product {$index}",
                'price' => random_int(100, 9999) / 100,
                'stock' => random_int(0, 1000),
                'description' => "This is the description for product {$index}",
                'created_at' => now(),
            ])
            ->count(5000)
            ->when(
                config('app.env') === 'production',
                fn ($builder) => $builder->withoutProgressTracking()
            )
            ->run();

        // example 5: Using the trait helper
        $this->quickSeed(
            'categories',
            ['name', 'slug', 'description', 'created_at'],
            fn ($index) => [
                'name' => "Category {$index}",
                'slug' => "category-{$index}",
                'description' => "This is the description for category {$index}",
                'created_at' => now(),
            ],
            1000
        );

        // example 6: Using unique value generators for tables with unique constraints
        // You can clear table before seeding to avoid duplicate entry errors
        DB::table('users')->delete();

        // generate unique value generators
        $uniqueEmail = $this->uniqueEmail('user');
        $uniqueUsername = $this->uniqueValue('username');
        $uniqueCode = $this->uniqueUuid('code_');

        TurboSeeder::create('users')
            ->columns(['name', 'email', 'username', 'verification_code', 'created_at'])
            ->generate(fn ($index) => [
                'name' => "User {$index}",
                'email' => $uniqueEmail($index),
                'username' => $uniqueUsername($index),
                'verification_code' => $uniqueCode(),
                'created_at' => now(),
            ])
            ->count(50000)
            ->withProgressTracking()
            ->run();
    }
}
