<?php

namespace CodeDistortion\Adapt\Tests\Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::insert("INSERT INTO `users` (`username`) VALUES ('user1')");
    }
}
