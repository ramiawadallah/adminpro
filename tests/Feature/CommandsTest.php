<?php

namespace Ramiawadallah\Adminpro\Tests\Feature;

use Ramiawadallah\Adminpro\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CommandsTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_seed_command_can_publish_new_super_admin()
    {
        $this->artisan('adminpro:seed', ['--role'=>'super']);
        $this->assertDatabaseHas('admins', ['email'=>'super@admin.com']);
    }
}
