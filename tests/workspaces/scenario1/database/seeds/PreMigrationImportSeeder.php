<?php

namespace CodeDistortion\Adapt\Tests\Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

/**
 * Seed the pre_migration_import table (which was created by the pre-migration sql file).
 */
class PreMigrationImportSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::insert("INSERT INTO `pre_migration_import` (`name`) VALUES ('Three')");
    }
}
