<?php
namespace DreamFactory\Core\Progress;

use DreamFactory\Core\Compliance\Handlers\Events\EventHandler;
use DreamFactory\Core\Hadoop\Database\ODBCConnection;
use DreamFactory\Core\Hadoop\Database\ODBCConnector;
use DreamFactory\Core\Progress\Database\Schema\ProgressSchema;
use DreamFactory\Core\Progress\Http\Middleware\ExampleMiddleware;
use DreamFactory\Core\Progress\Models\ProgressConfig;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Progress\Services\ProgressService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Routing\Router;

use Route;
use Event;


class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our database drivers.
        $this->app->resolving('db', function ($db) {
            /** @var DatabaseManager $db */
            $db->extend('progress', function ($config) {
                $pdoConnection = (new ODBCConnector())->connect($config);
                return new ODBCConnection($pdoConnection, $config['database'], isset($config['prefix']) ? $config['prefix'] : '', $config);
            });
        });

        // Add our database extensions.
        $this->app->resolving('db.schema', function ($db) {
            /** @var DatabaseManager $db */
            $db->extend('progress', function ($connection) {
                dd('hi');
                return new ProgressSchema($connection);
            });
        });

        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name' => 'progress',
                    'label' => 'Progress OpenEdge',
                    'description' => 'The Progress OpenEdge database software',
                    'group' => 'Big Data',
                    'subscription_required' => LicenseLevel::GOLD,
                    'config_handler' => ProgressConfig::class,
                    'factory' => function ($config) {
                        return new ProgressService($config);
                    },
                ])
            );
        });
    }
}
