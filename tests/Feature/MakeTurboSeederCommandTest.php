<?php

declare(strict_types=1);

afterEach(function () {
    foreach (['TestGeneratedSeeder', 'TestFactorySeeder'] as $class) {
        $path = database_path('seeders/'.$class.'.php');
        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('make:turbo-seeder generates a generate()-based seeder with introspected columns', function () {
    $this->artisan('make:turbo-seeder', [
        'name' => 'TestGeneratedSeeder',
        '--table' => 'test_users',
    ])->assertSuccessful();

    $path = database_path('seeders/TestGeneratedSeeder.php');

    expect(is_file($path))->toBeTrue();

    $contents = file_get_contents($path);

    expect($contents)->toContain('class TestGeneratedSeeder')
        ->and($contents)->toContain("TurboSeeder::create('test_users')")
        ->and($contents)->toContain("'email'")
        ->and($contents)->toContain('TurboData::nowOnce()');
});

test('make:turbo-seeder --factory generates a fromFactory()-based seeder', function () {
    $this->artisan('make:turbo-seeder', [
        'name' => 'TestFactorySeeder',
        '--table' => 'test_users',
        '--factory' => true,
    ])->assertSuccessful();

    $contents = file_get_contents(database_path('seeders/TestFactorySeeder.php'));

    expect($contents)->toContain('fromFactory(')
        ->and($contents)->toContain('::factory()');
});
