<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class TestUserOnSecondaryConnectionModel extends Model
{
    protected $table = 'test_users';

    protected $connection = 'testing_secondary';

    protected $guarded = [];
}
