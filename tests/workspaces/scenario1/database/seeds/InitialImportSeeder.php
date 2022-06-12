<?php

namespace CodeDistortion\Adapt\Tests\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

/**
 * Seed the initial_import table (which was created by the initial-import sql file).
 */
class InitialImportSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::insert("INSERT INTO `initial_import` (`name`) VALUES ('Three')");
    }
}
