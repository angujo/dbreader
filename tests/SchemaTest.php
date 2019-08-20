<?php
/**
 * Created for dbreader.
 * User: Angujo Barrack
 * Date: 2019-07-28
 * Time: 5:31 PM
 */

use Angujo\DBReader\Drivers\Connection;
use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\Schema;

class SchemaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Database
     */
    private static $db;
    private        $schemas;
    private        $tables;
    private        $fks;
    private $columns;

    public static function setUpBeforeClass()
    {
        self::$db = Connection::currentDatabase();
    }

    public function testGetSchemas()
    {
        $this->schemas = self::$db->schemas;
    }

    public function testGetTables()
    {
        $this->tables = array_map(function (Schema $sch) { return $sch->tables; }, self::$db->schemas);
    }

    public function testGetTableForeignKeys()
    {
        $tables=self::$db->getSchema('lookup')->tables;
        $table=Schema::getTable('lookup','languages');
        $this->fks = $table->foreign_keys;
    }

    public function testGetTableConstraints()
    {

    }

    public function testGetColumns()
    {
$this->columns=self::$db->getSchema('log')->columns;
    }

    public function testGetColumnForeignKeys()
    {

    }

    protected function tearDown()
    {
        //me
        $r=2+1;
    }

    public static function tearDownAfterClass()
    {
        self::$db = null;
    }
}
