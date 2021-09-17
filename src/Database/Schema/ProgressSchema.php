<?php


namespace DreamFactory\Core\Progress\Database\Schema;


use DreamFactory\Core\Database\Schema\ColumnSchema;
use DreamFactory\Core\Database\Schema\TableSchema;
use DreamFactory\Core\Enums\DbResourceTypes;
use DreamFactory\Core\SqlDb\Database\Schema\MySqlSchema;
use Illuminate\Support\Facades\DB;

class ProgressSchema extends MySqlSchema
{
    const DEFAULT_SCHEMA = 'default';

    /**
     * @inheritdoc
     */
    public function getDefaultSchema()
    {
        return static::DEFAULT_SCHEMA;
    }

    public function getSupportedResourceTypes()
    {
        return [
            DbResourceTypes::TYPE_TABLE,
            DbResourceTypes::TYPE_TABLE_FIELD,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getTableNames($schema = '')
    {
        $tableSelect = $this->connection->select(DB::raw('Select * from sysprogress.systables'));
        $result = [];

        foreach ($tableSelect as $row) {
            $tableSchema = new TableSchema([]);
            $tableSchema->setName($row['OWNER'] . '.' .$row['TBL']);
            $result[] = $tableSchema;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function getViewNames($schema = '')
    {
        return [];
    }

    public function getSchemas()
    {
        $databases = $this->connection->select(DB::raw('SHOW CATALOGS ALL'));
        $result = [];

        foreach ($databases as $row) {
            foreach ($row as $key => $data) {
                if (strpos($key, "PRO_NAME") !== false)
                    $result[] = $data;
                break;
            }
        }
        return $result;
    }

    protected function loadTableColumns(TableSchema $table)
    {
        $result = $this->connection->select("DESCRIBE {$table->getName()}");
        $extendedDescribe = $this->connection->select("DESCRIBE EXTENDED {$table->getName()}");

        $constraints = array_values(array_filter($extendedDescribe, function ($value) {
            return $value['col_name'] === 'Constraints';
        }));
        $primaryKeys = [];
        if (!empty($constraints)) {
            $description = $constraints[0]['data_type'];
            preg_match('/Primary Key .+?:\[(.+?)]/', $description, $matches);
            $primaryKeys = explode(',', $matches[1]) ?? [];
        }

        foreach ($result as $columnMetadata) {
            $columnSchema = new ColumnSchema([]);
            $name = $columnMetadata['col_name'];
            $columnSchema->setName($name);
            if (in_array($name, $primaryKeys)) {
                $columnSchema->isPrimaryKey = true;
                $table->addPrimaryKey($name);
            }
            if (isset($columnMetadata['comment']) || !empty($columnMetadata['comment'])) {
                $columnSchema->comment = $columnMetadata['comment'];
            }
            $this->extractType($columnSchema, $columnMetadata['data_type']);
            $table->addColumn($columnSchema);
        }
    }

}
