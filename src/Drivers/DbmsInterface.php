<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;

interface DbmsInterface
{
    /**
     * @return Database
     */
    public function currentDatabase();

    /**
     * @param string|Database $db
     * @return DBTable[]
     */
    public function getTables($db);

    /**
     * @param string|Database $schema
     * @param string|DBTable  $table_name
     *
     * @return DBColumn[]
     */
    public function getColumns($schema, $table_name);

    /**
     * Directly referenced Foreign Keys
     * Result in OneToOne relationship with foreign table
     *
     * @param $schema
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencedForeignKeys($schema, $table_name);

    /**
     * Foreign Keys referencing this column
     * Results in OneToMany relationship with foreign tables
     *
     * @param $db_name
     * @param $table_name
     * @return ForeignKey[]
     */
    public function getReferencingForeignKeys($db_name, $table_name);
}