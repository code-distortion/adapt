<?php

namespace CodeDistortion\Adapt\Tests\Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class LogSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::insert(
            "INSERT INTO `logs` (`event`, `occurred_at`) VALUES ('event1', '2020-01-01 00:00:00')"
        );
    }
}
