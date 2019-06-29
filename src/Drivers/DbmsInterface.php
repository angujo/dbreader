<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;

interface DbmsInterface
{
    /**
     * @param bool $name
     *
     * @return Database
     */
    public function currentDatabase($name = false);

    /**
     * @param string $db_name
     *
     * @return DBTable[]
     */
    public function getTables($db_name);

    /**
     * @param string|Database $schema
     * @param string|DBTable  $table_name
     *
     * @return DBColumn[]
     */
    public function getColumns($schema, $table_name = null);

    /**
     * Directly referenced Foreign Keys
     * Result in OneToOne relationship with foreign table
     *
     * @param $schema
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencedForeignKeys($schema, $table_name = null);

    /**
     * Foreign Keys referencing this column
     * Results in OneToMany relationship with foreign tables
     *
     * @param $schema
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencingForeignKeys($schema, $table_name = null);
}