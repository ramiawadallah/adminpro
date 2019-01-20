<?php

namespace Ramiawadallah\Adminpro\Tests;

use App\Model\Admin;
use App\Model\Role;
use Ramiawadallah\Adminpro\AdminproServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setup()
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->artisan('migrate', ['--database' => 'testing']);
        $this->loadLaravelMigrations(['--database' => 'testing']);
        $this->withFactories(__DIR__.'/../src/factories');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [AdminproServiceProvider::class];
    }

    public function logInAdmin($args = [])
    {
        $admin = $this->createAdmin($args);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function createAdmin($args = [])
    {
        return factory(Admin::class)->create($args);
    }

    public function loginSuperAdmin($args = [])
    {
        $super = factory(Admin::class)->create($args);
        $role = factory(Role::class)->create();
        $super->roles()->attach($role);
        $this->actingAs($super, 'admin');

        return $super;
    }
}
