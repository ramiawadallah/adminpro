<?php

namespace Ramiawadallah\Adminpro\Console\Commands;

use Illuminate\Console\Command;
use App\Model\Admin;
use App\Model\Role;

class SeedCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminpro:seed {--r|role=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed one super admin for adminpro package
                            {--role= : Give any role name to create new role}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $rolename = $this->option('role');
        $role = Role::whereName($rolename)->first();
        if (! $rolename) {
            $this->error("please provide role as --role='roleName'");

            return;
        }
        $admin = $this->createSuperAdmin($role, $rolename);

        $this->info("You have created an admin name '{$admin->name}' with role of '{$admin->roles->first()->name}' ");
        $this->info("Now log-in with {$admin->email} email and password as 'secret'");
    }

    protected function createSuperAdmin($role, $rolename)
    {
        $prefix = config('adminpro.prefix');
        $admin = factory(Admin::class)
            ->create(['email' => "super@{$prefix}.com", 'name' => 'Super '.ucfirst($prefix)]);
        if (! $role) {
            $role = factory(Role::class)->create(['name' => $rolename]);
        }
        $admin->roles()->attach($role);

        return $admin;
    }
}
