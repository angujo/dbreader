<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBConstraint;
use Angujo\DBReader\Models\DBIndex;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;
use Angujo\DBReader\Models\Schema;

class PostgreSQL extends Dbms
{
    private $_schema;
    private static $tables_query = 'SELECT t.schemaname schema_name, t.tablename "name", true is_table, false is_view FROM pg_catalog.pg_tables t';
    private static $views_query = 'SELECT t.schemaname schema_name, t.viewname "name", false is_table, true is_view FROM pg_catalog.pg_views t';
    private static $columns_query = 'select n.nspname schema_name,t.relname table_name, ty.typname data_type, c.attname "name", c.attlen "length", '.
    'c.attnotnull = false is_nullable, c.attnum ordinal, cmt.description "comment", '.
    'pg_get_expr(d.adbin, d.adrelid)::information_schema.character_data "default", st.oid is not null is_auto_increment '.
    'from pg_catalog.pg_attribute c '.
    'join pg_catalog.pg_class t on t.oid=c.attrelid join pg_catalog.pg_namespace n on n.oid=t.relnamespace '.
    'join pg_catalog.pg_type ty on ty.oid=c.atttypid left join pg_catalog.pg_attrdef d on d.adnum=c.attnum and d.adrelid=t.oid '.
    'left join pg_catalog.pg_description cmt on cmt.objoid=t.oid and cmt.objsubid=c.attnum '.
    'left join pg_catalog.pg_type st on pg_get_serial_sequence(t.relname,c.attname)=n.nspname||\'.\'||st.typname '.
    'where not c.attisdropped and NOT pg_is_other_temp_schema(n.oid) AND c.attnum > 0 AND '.
    '(t.relkind = ANY (ARRAY[\'r\'::"char", \'v\'::"char", \'f\'::"char", \'p\'::"char"]))';
    private static $constraints_query = 'select n.nspname schema_name, t.relname table_name, a.attname column_name, c.conname "name", c.consrc check_source, '.
    '\'f\'::character=c.contype is_foreign_key, \'u\'::character=c.contype is_unique_key, \'p\'::character=c.contype is_primary_key ,\'c\'::character=c.contype is_check'.
    'from pg_catalog.pg_constraint c join pg_catalog.pg_namespace n on n.oid=c.connamespace join pg_catalog.pg_class t on t.oid=conrelid '.
    'join pg_catalog.pg_attribute a on a.attrelid=t.oid and (a.attnum = any (c.conkey)) '.
    'where (c.contype = any (array[\'p\'::character, \'u\'::character, \'c\'::character]))';
    private static $toone_foreign_queries = 'select a.attnum ordinal, n.nspname schema_name, t.relname table_name, a.attname column_name, c.conname "name", rn.nspname foreign_schema_name, rt.relname foreign_table_name, '.
    'ra.attname foreign_column_name '.
    'from pg_catalog.pg_constraint c join pg_catalog.pg_namespace n on n.oid=c.connamespace join pg_catalog.pg_class t on t.oid=c.conrelid '.
    'join pg_catalog.pg_attribute a on a.attrelid=t.oid and a.attnum = any (c.conkey) join pg_catalog.pg_class rt on rt.oid=c.confrelid '.
    'join pg_catalog.pg_namespace rn on rn.oid=rt.relnamespace join pg_catalog.pg_attribute ra on ra.attrelid=rt.oid and ra.attnum = any (c.confkey) '.
    'left join pg_catalog.pg_constraint uc on n.oid=uc.connamespace and a.attnum = any (uc.conkey) and t.oid=uc.conrelid and not uc.contype = any (array[\'u\'::character,\'p\'::character]) '.
    'where (c.contype =\'f\'::character)';
    private static $tomany_foreign_queries = 'select distinct ra.attnum ordinal, rn.nspname schema_name, rt.relname table_name, ra.attname column_name, c.conname "name", '.
    'n.nspname foreign_schema_name, t.relname foreign_table_name, a.attname foreign_column_name '.
    'from pg_catalog.pg_constraint c '.
    'join pg_catalog.pg_namespace n on n.oid=c.connamespace join pg_catalog.pg_class t on t.oid=c.conrelid join pg_catalog.pg_attribute a on a.attrelid=t.oid and a.attnum = any (c.conkey) '.
    'join pg_catalog.pg_class rt on rt.oid=c.confrelid join pg_catalog.pg_namespace rn on rn.oid=rt.relnamespace '.
    'join pg_catalog.pg_attribute ra on ra.attrelid=rt.oid and ra.attnum = any (c.confkey) '.
    'join pg_catalog.pg_constraint uc on rn.oid=uc.connamespace and ra.attnum = any (uc.conkey) and rt.oid=uc.conrelid and uc.contype = any (array[\'u\'::character,\'p\'::character]) '.
    'where (c.contype =\'f\'::character)';
    private static $indices_query = 'select n.nspname schema_name,  t.relname as table_name,  i.relname as "name",  a.attname as column_name,  ix.indisprimary is_primary,  ix.indisunique is_unique  '.
    'from  pg_catalog.pg_class t,  pg_catalog.pg_namespace as n,  pg_class i,  pg_index ix,  pg_attribute a  '.
    'where  t.relnamespace=n.oid  and t.oid = ix.indrelid  and i.oid = ix.indexrelid  and a.attrelid = t.oid  and a.attnum = ANY(ix.indkey)  and t.relkind = \'r\' ';

    /**
     * One to One
     *
     * @param      $schema
     * @param null $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencedForeignKeys($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableToOneForeignKeys($schema, $table_name) : $this->schemaToOneForeignKeys($schema);
        usort($data, function($f, $s){ return $f['ordinal'] <=> $s['ordinal']; });
        return array_map(function($d){ return new ForeignKey($d, false); }, $data);
    }

    private function tableToOneForeignKeys($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$toone_foreign_queries, 'AND n.nspname = :ts', 'AND t.relname = :tn',]));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name,]);
        //echo $stmt->_debugQuery(true), "\n";
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function schemaToOneForeignKeys($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$toone_foreign_queries, 'AND n.nspname = :ts',]));
        $stmt->execute([':ts' => $schema,]);
        //echo $stmt->_debugQuery(true), "\n";
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * One to Many
     *
     * @param      $schema
     * @param null $table_name
     *
     * @return ForeignKey[]|array
     */
    public function getReferencingForeignKeys($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableToManyForeignKeys($schema, $table_name) : $this->schemaToManyForeignKeys($schema);
        usort($data, function($f, $s){ return $f['ordinal'] <=> $s['ordinal']; });
        return array_map(function($d){ return new ForeignKey($d, true); }, $data);
    }

    private function tableToManyForeignKeys($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$tomany_foreign_queries, 'AND rn.nspname = :ts', 'AND rt.relname = :tn',]));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name,]);
       // $fq=$stmt->_debugQuery(true);
       // echo "$fq\n";
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function schemaToManyForeignKeys($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$tomany_foreign_queries, 'AND rn.nspname = :ts',]));
        $stmt->execute([':ts' => $schema,]);
       // $fq=$stmt->_debugQuery(true);
       // echo "$fq\n";
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param      $schema
     * @param null $table_name
     *
     * @return DBConstraint[]
     */
    public function getConstraints($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableConstraints($schema, $table_name) : $this->schemaConstraints($schema);
        return array_map(function($d){ return new DBConstraint($d); }, $data);
    }

    private function schemaConstraints($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$constraints_query, 'AND n.nspname = :ts',]));
        $stmt->execute([':ts' => $schema,]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function tableConstraints($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$constraints_query, 'AND n.nspname = :ts', 'AND t.relname = :tn']));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getIndices($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableIndices($schema, $table_name) : $this->schemaIndices($schema);
        return array_map(function($d){ return new DBIndex($d); }, $data);
    }

    private function schemaIndices($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$indices_query, 'AND n.nspname = :ts',]));
        $stmt->execute([':ts' => $schema,]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function tableIndices($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$indices_query, 'AND n.nspname = :ts', 'AND t.relname = :tn']));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return Schema[]
     */
    public function getSchemas()
    {
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select schema_name from information_schema.schemata where schema_name not like \'pg_%\' and schema_name not in (\'information_schema\') and catalog_name = :db');
        $stmt->execute([':db' => $this->currentDatabase(true)]);
        // echo $stmt->_debugQuery(true), "\n";
        return array_map(function($details){ return new Schema($details['schema_name'], $this->currentDatabase(true)); }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @param null|string $schema
     *
     * @return DBTable[]
     */
    public function getTables($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$tables_query, 'WHERE schemaname = :ts']));
        $stmt->execute([':ts' => $schema]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //$fq= $stmt->_debugQuery(true);
        return $this->mapTables(array_map(function($details){
            return new DBTable(array_merge(['db_name' => $this->currentDatabase(true)], $details), true);
        }, $data));
    }

    public function getViews($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$views_query, 'WHERE schemaname = :ts']));
        $stmt->execute([':ts' => $schema]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //echo $stmt->_debugQuery(true),"\n";
        return $this->mapTables(array_map(function($details){
            return new DBTable(array_merge(['db_name' => $this->currentDatabase(true)], $details), true);
        }, $data));
    }

    /**
     * @param string|Database $schema
     * @param string|DBTable  $table_name
     *
     * @return DBColumn[]
     */
    public function getColumns($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableColumns($schema, $table_name) : $this->schemaColumns($schema);
        usort($data, function($f, $s){ return $f['ordinal'] <=> $s['ordinal']; });
        return $this->mapColumns(array_map(function($details){ return new DBColumn($details); }, $data));
    }

    /**
     * @param string $schema
     *
     * @return array
     */
    protected function schemaColumns($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$columns_query, 'AND n.nspname = :ts']));
        $stmt->execute([':ts' => $schema]);
        //  $q=$stmt->_debugQuery(true);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $schema
     * @param string $table_name
     *
     * @return array
     */
    protected function tableColumns($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$columns_query, 'AND n.nspname = :ts', 'AND t.relname = :tn']));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param bool $name
     *
     * @return Database|string
     */
    public function currentDatabase($name = false)
    {
        $this->current_db = $this->current_db ?: new Database($this->connection->query('SELECT current_database();')->fetchColumn());
        return true === $name && $this->current_db ? $this->current_db->name : $this->current_db;
    }

    protected function switchSchema($schema)
    {
        if (empty($schema) || !is_string($schema) || 0 === strcasecmp($this->_schema, $schema)) {
            return;
        }
        $this->connection->exec("SET search_path TO \"$schema\"");
    }
}