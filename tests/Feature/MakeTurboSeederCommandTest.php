<?php

declare(strict_types=1);

afterEach(function () {
    foreach (glob(database_path('seeders/Test*Seeder.php')) ?: [] as $path) {
        unlink($path);
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

    expect($contents)
        ->toContain('class TestGeneratedSeeder')
        ->and($contents)->toContain("TurboSeeder::forTable('test_users')")
        ->and($contents)->toContain("'email'")
        ->and($contents)->toContain('$email = TurboData::uniqueEmail()')
        ->and($contents)->toContain('$email($index)')
        ->and($contents)->toContain('->withTimestamps()')
        ->and($contents)->not->toContain("'created_at'")
        ->and($contents)->not->toContain("'updated_at'");
});

test('make:turbo-seeder --factory generates a fromFactory()-based seeder', function () {
    $this->artisan('make:turbo-seeder', [
        'name' => 'TestFactorySeeder',
        '--table' => 'test_users',
        '--factory' => true,
    ])->assertSuccessful();

    $contents = file_get_contents(database_path('seeders/TestFactorySeeder.php'));

    expect($contents)
        ->toContain('fromFactory(')
        ->and($contents)->toContain('\App\Models\TestUser::factory()');
});

test('make:turbo-seeder --factory=ClassName uses the provided class to infer the model', function () {
    $this->artisan('make:turbo-seeder', [
        'name' => 'TestFactoryWithModelSeeder',
        '--table' => 'test_users',
        '--factory' => 'OrderFactory',
    ])->assertSuccessful();

    $contents = file_get_contents(database_path('seeders/TestFactoryWithModelSeeder.php'));

    expect($contents)->toContain('\App\Models\Order::factory()');
});

test('make:turbo-seeder --count sets the record count in the generated seeder', function () {
    $this->artisan('make:turbo-seeder', [
        'name' => 'TestCountSeeder',
        '--table' => 'test_users',
        '--count' => '500000',
    ])->assertSuccessful();

    $contents = file_get_contents(database_path('seeders/TestCountSeeder.php'));

    expect($contents)->toContain('->count(500000)');
});

test('make:turbo-seeder omits withTimestamps for tables without timestamp columns', function () {
    $this->artisan('make:turbo-seeder', [
        'name' => 'TestNoTimestampSeeder',
        '--table' => 'test_counters',
    ])->assertSuccessful();

    $contents = file_get_contents(database_path('seeders/TestNoTimestampSeeder.php'));

    expect($contents)
        ->toContain("TurboSeeder::forTable('test_counters')")
        ->and($contents)->not->toContain('->withTimestamps()');
});

test('make:turbo-seeder --force overwrites an existing seeder file', function () {
    $path = database_path('seeders/TestForceSeeder.php');
    file_put_contents($path, '<?php // original');

    $this->artisan('make:turbo-seeder', [
        'name' => 'TestForceSeeder',
        '--table' => 'test_users',
        '--force' => true,
    ])->assertSuccessful();

    expect(file_get_contents($path))->toContain('class TestForceSeeder');
});

test('make:turbo-seeder does not overwrite an existing seeder without --force', function () {
    $path = database_path('seeders/TestNoOverwriteSeeder.php');
    file_put_contents($path, '<?php // original');

    $this->artisan('make:turbo-seeder', [
        'name' => 'TestNoOverwriteSeeder',
        '--table' => 'test_users',
    ]);

    expect(file_get_contents($path))->toBe('<?php // original');
});

test('make:turbo-seeder generates randomInt expression for _id columns', function () {
    $this->artisan('make:turbo-seeder', [
        'name' => 'TestRelationSeeder',
        '--table' => 'test_posts',
    ])->assertSuccessful();

    $contents = file_get_contents(database_path('seeders/TestRelationSeeder.php'));

    expect($contents)
        ->toContain("'user_id'")
        ->toContain('TurboData::randomInt(1, 100)');
});

test('make:turbo-seeder falls back to placeholder columns when table does not exist', function () {
    $this->artisan('make:turbo-seeder', [
        'name' => 'TestFallbackSeeder',
        '--table' => 'nonexistent_table',
    ])->assertSuccessful();

    $contents = file_get_contents(database_path('seeders/TestFallbackSeeder.php'));

    expect($contents)
        ->toContain("TurboSeeder::forTable('nonexistent_table')")
        ->and($contents)->toContain("'name'")
        ->and($contents)->toContain('->withTimestamps()');
});
