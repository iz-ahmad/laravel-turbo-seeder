<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestUserModel extends Model
{
    use HasFactory;

    protected $table = 'test_users';

    protected $guarded = [];

    protected static function newFactory(): TestUserFactory
    {
        return TestUserFactory::new();
    }
}
