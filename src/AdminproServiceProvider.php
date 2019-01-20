<?php

namespace Ramiawadallah\Adminpro;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Ramiawadallah\Adminpro\Console\Commands\RoleCmd;
use Ramiawadallah\Adminpro\Console\Commands\SeedCmd;
use Ramiawadallah\Adminpro\Exception\AdminProHandler;
use Ramiawadallah\Adminpro\Console\Commands\MakeAdminProCommand;
use Ramiawadallah\Adminpro\Console\Commands\RollbackAdminProCommand;
use Ramiawadallah\Adminpro\Http\Middleware\redirectIfAuthenticatedAdmin;
use Ramiawadallah\Adminpro\Http\Middleware\redirectIfNotWithRoleOfAdmin;

class AdminproServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->canHaveAdminBackend()) {
            $this->loadViewsFrom(__DIR__.'/views', 'adminpro');
            $this->loadMigrationsFrom(__DIR__.'/database/migrations');
            $this->loadRoutesFrom(__DIR__.'/routes/routes.php');
            $this->publisheThings();
            $this->mergeAuthFileFrom(__DIR__.'/../config/auth.php', 'auth');
            $this->mergeConfigFrom(__DIR__.'/../config/adminpro.php', 'adminpro');
            $this->loadBladeSyntax();
            $this->loadAdminCommands();
        }
        $this->loadCommands();
    }

    public function register()
    {
        if ($this->canHaveAdminBackend()) {
            $this->loadFactories();
            $this->loadMiddleware();
            $this->registerExceptionHandler();
        }
    }

    protected function loadFactories()
    {
        $appFactories = scandir(database_path('/factories'));
        $factoryPath = ! in_array('AdminFactory.php', $appFactories) ? __DIR__.'/factories' : database_path('/factories');

        $this->app->make(Factory::class)->load($factoryPath);
    }

    protected function loadRoutesFrom($path)
    {
        $prefix = config('adminpro.prefix', 'admin');
        $routeDir = base_path('routes');
        if (file_exists($routeDir)) {
            $appRouteDir = scandir($routeDir);
            if (! $this->app->routesAreCached()) {
                require in_array("{$prefix}.php", $appRouteDir) ? base_path("routes/{$prefix}.php") : $path;
            }
        }
        require $path;
    }

    protected function loadMiddleware()
    {
        app('router')->aliasMiddleware('admin', redirectIfAuthenticatedAdmin::class);
        app('router')->aliasMiddleware('role', redirectIfNotWithRoleOfAdmin::class);
    }

    protected function registerExceptionHandler()
    {
        \App::singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            AdminProHandler::class
        );
    }

    protected function mergeAuthFileFrom($path, $key)
    {
        $original = $this->app['config']->get($key, []);
        $this->app['config']->set($key, $this->multi_array_merge(require $path, $original));
    }

    protected function multi_array_merge($toMerge, $original)
    {
        $auth = [];
        foreach ($original as $key => $value) {
            if (isset($toMerge[$key])) {
                $auth[$key] = array_merge($value, $toMerge[$key]);
            } else {
                $auth[$key] = $value;
            }
        }

        return $auth;
    }

    protected function publisheThings()
    {
        $prefix = config('adminpro.prefix', 'admin');
        $this->publishes([
            __DIR__.'/database/migrations/' => database_path('migrations'),
        ], 'migrations');
        $this->publishes([
            __DIR__.'/views' => resource_path('views/admin'),
        ], 'views');
        $this->publishes([
            __DIR__.'/factories' => database_path('factories'),
        ], 'factories');
        $this->publishes([
            __DIR__.'/../config/adminpro.php' => config_path('adminpro.php'),
        ], 'config');
        $this->publishes([
            __DIR__.'/routes/routes.php' => base_path("routes/{$prefix}.php"),
        ], 'routes');
        $this->publishes([
            __DIR__.'/Http/Controllers' => base_path("app/http/controllers/{$prefix}"),
        ], 'Controllers');
        $this->publishes([
            __DIR__.'/Model' => base_path("app/Model"),
        ], 'Model');
    }

    protected function loadBladeSyntax()
    {
        Blade::if('admin', function ($role) {
            if (! auth('admin')->check()) {
                return  false;
            }
            $role = explode(',', $role);
            $role[] = 'super';
            $roles = auth('admin')->user()->/* @scrutinizer ignore-call */ roles()->pluck('name');
            $match = count(array_intersect($role, $roles->toArray()));

            return (bool) $match;
        });
    }

    protected function loadAdminCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedCmd::class,
                RoleCmd::class,
            ]);
        }
    }

    protected function loadCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAdminProCommand::class,
                RollbackAdminProCommand::class,
            ]);
        }
    }

    protected function canHaveAdminBackend()
    {
        return config('adminpro.admin_active', true);
    }
}
