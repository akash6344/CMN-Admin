<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $user = User::firstOrNew(['email' => 'admin@cmnhousing.test']);
        $user->name = 'Admin User';
        $user->mobile = '9988776655';
        $user->password = 'password';
        $user->admin_role = 'admin';
        $user->status = 'Active';
        $user->save();
    }
}
