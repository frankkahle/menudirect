<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // restaurant_* models use the 'menudirect' connection while User uses the
        // default 'mysql' connection; in tests both point at menudirect_test. Share a
        // single PDO so writes across both connections live in ONE transaction —
        // visible to assertions and rolled back together by RefreshDatabase.
        $default = DB::connection();
        DB::connection('menudirect')->setPdo($default->getPdo());
        DB::connection('menudirect')->setReadPdo($default->getReadPdo());
    }
}
