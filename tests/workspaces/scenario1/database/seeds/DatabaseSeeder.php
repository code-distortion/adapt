<?php

namespace CodeDistortion\Adapt\Tests\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed the database.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(LogSeeder::class);
    }
}
