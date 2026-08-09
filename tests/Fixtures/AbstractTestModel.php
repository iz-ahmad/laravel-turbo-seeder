<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractTestModel extends Model
{
    protected $table = 'test_users';
}
