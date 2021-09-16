<?php

namespace DreamFactory\Core\Progress\Services;

use DreamFactory\Core\Progress\Resources\ProgressTable;
use DreamFactory\Core\SqlDb\Services\SqlDb;

/**
 * Class PostgreSqlDb
 *
 * @package DreamFactory\Core\SqlDb\Services
 */
class ProgressService extends SqlDb
{
    public static function adaptConfig(array &$config)
    {
        $config['driver'] = 'progress';
        $driverPath = env('PROGRESS_ODBC_DRIVER_PATH', '/usr/dlc/odbc/lib/pgoe27.so');
        if (isset($config['options']['driver_path'])) {
            $driverPath = $config['options']['driver_path'];
        }
        $config['dsn'] =
            "Driver={{$driverPath}};"
            . "Host={$config['host']};"
            . "Port={$config['port']};"
            . "Database={$config['database']}";
        parent::adaptConfig($config);
    }

    // Hide _schema endpoints and related parameter
    public function getApiDocInfo()
    {
        $base = parent::getApiDocInfo();
        /*$paths = (array)array_get($base, 'paths');
        foreach ($paths as $path_key => $path) {
            if (strpos($path_key, '_schema') !== false) {
                unset($paths[$path_key]);
                continue;
            }

            $paths[$path_key] = $this->removeNotGetPaths($path);
        }
        $base['paths'] = $paths;*/
        return $base;
    }

    public function getResourceHandlers()
    {
        $handlers = parent::getResourceHandlers();

        $handlers[ProgressTable::RESOURCE_NAME] = [
            'name' => ProgressTable::RESOURCE_NAME,
            'class_name' => ProgressTable::class,
            'label' => 'Table',
        ];

        return $handlers;
    }

    private function removeRelatedParameter($parameters)
    {
        foreach ($parameters as $parameter_key => $parameter) {
            if ($parameter['name'] === 'related') {
                unset($parameters[$parameter_key]);
                continue;
            }
        }
        $parameters = array_values($parameters);
        return $parameters;
    }

    private function removeNotGetPaths($path)
    {
        foreach ($path as $resource_key => $resource) {
            if ($resource_key === 'post' || $resource_key === 'patch' || $resource_key === 'put' || $resource_key === 'delete') {
                unset($path[$resource_key]);
                continue;
            }

            if (isset($resource['parameters'])) {
                $path[$resource_key]['parameters'] = $this->removeRelatedParameter($resource['parameters']);
            }
        }
        return $path;
    }
}
