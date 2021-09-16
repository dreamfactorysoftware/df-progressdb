<?php

namespace DreamFactory\Core\Progress\Models;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\SqlDb\Models\BaseSqlDbConfig;

/**
 * Write your model
 *
 * Write your methods, properties or override ones from the parent
 *
 */
class ProgressConfig extends BaseSqlDbConfig
{
    protected $appends = ['host', 'port', 'database', 'username', 'password', 'schema'];

    protected $encrypted = ['username', 'password'];

    protected $protected = ['password'];

    protected function getConnectionFields()
    {
        return ['host', 'port', 'database', 'username', 'password', 'schema'];
    }

    public static function getDefaultConnectionInfo()
    {
        return [
            [
                'name'        => 'host',
                'label'       => 'Host',
                'type'        => 'string',
                'description' => 'The name of the database host, i.e. localhost, 192.168.1.1, etc.'
            ],
            [
                'name'        => 'port',
                'label'       => 'Port Number',
                'type'        => 'integer',
                'description' => 'The number of the database host port, i.e. ' . static::getDefaultPort()
            ],
            [
                'name'        => 'database',
                'label'       => 'Database',
                'type'        => 'string',
                'description' =>
                    'The name of the database to connect to on the given server. This can be a lookup key.'
            ],
            [
                'name'        => 'username',
                'label'       => 'Username',
                'type'        => 'string',
                'description' => 'The name of the database user. This can be a lookup key.'
            ],
            [
                'name'        => 'password',
                'label'       => 'Password',
                'type'        => 'password',
                'description' => 'The password for the database user. This can be a lookup key.'
            ],
        ];
    }

    public function validate($data, $throwException = true)
    {
        $connection = $this->getAttribute('connection');
        if (empty(array_get($connection, 'host')) || empty(array_get($connection, 'database'))) {
            throw new BadRequestException("Database connection information must contain at least host and database name.");
        }

        return parent::validate($data, $throwException);
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'options':
                $schema['label'] = 'Driver Options';
                $schema['type'] = 'object';
                $schema['object'] =
                    [
                        'key'   => ['label' => 'Name', 'type' => 'string'],
                        'value' => ['label' => 'Value', 'type' => 'string']
                    ];
                $schema['description'] = 'A key-value array of driver-specific connection options.';
                break;
            case 'attributes':
                $schema['label'] = 'Driver Attributes';
                $schema['type'] = 'object';
                $schema['object'] =
                    [
                        'key'   => ['label' => 'Name', 'type' => 'string'],
                        'value' => ['label' => 'Value', 'type' => 'string']
                    ];
                $schema['description'] =
                    'A key-value array of attributes to be set after connection.' .
                    ' For further information, see http://php.net/manual/en/pdo.setattribute.php';
                break;
            case 'statements':
                $schema['label'] = 'Additional SQL Statements';
                $schema['type'] = 'array';
                $schema['items'] = 'string';
                $schema['description'] = 'An array of SQL statements to run during connection initialization.';
                break;
        }
    }

    /** {@inheritdoc} */
    public static function getConfigSchema()
    {
        $schema = parent::getConfigSchema();
        $cacheTtl = array_pop($schema);
        $cacheEnabled = array_pop($schema);
        $maxRecords = array_pop($schema);
        $upserts = array_pop($schema);
        array_pop($schema);                 // Remove statement
        array_pop($schema);                 // Remove attributes
        array_pop($schema);                 // Remove options
        array_push($schema, $upserts);      // Restore upsert
        array_push($schema, $maxRecords);   // Restore max_records
        array_push($schema, $cacheEnabled); // Restore cache enabled
        array_push($schema, $cacheTtl);     // Restore cache TTL

        return $schema;
    }
}