<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;

class PostgreSQL extends Dbms
{
    protected $functions = ['now', 'nextval', 'currval', 'setval',];

    /**
     * @return Database[]
     */
    public function getSchemas()
    {
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select schema_name from information_schema.schemata where schema_name not like \'pg_%\' and schema_name not in (\'information_schema\') and catalog_name = :db');
        $stmt->execute([':db' => $this->currentDatabase(true)]);
        // echo $stmt->_debugQuery(true), "\n";
        return array_map(function($details){ return new Database($details['schema_name'], $this->currentDatabase(true)); }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @param null|string $schema
     *
     * @return DBTable[]
     */
    public function getTables($schema = null)
    {
        $params = [':db' => $this->currentDatabase(true)];
        if (is_string($schema)) {
            $params[':ts'] = $schema;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select * from information_schema."tables" t where t.table_schema not like \'pg_%\' and t.table_schema not in (\'information_schema\') and t.table_catalog = :db'.(is_string($schema) ? ' and t.table_schema = :ts' : ''));
        $stmt->execute($params);
        //echo $stmt->_debugQuery(true),"\n";
        return $this->mapTables(array_map(function($details){ return new DBTable($details, true); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * One to one
     *
     * @param string $schema
     * @param string $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencedForeignKeys($table_name, $schema = null)
    {
        $params = [':db' => $this->currentDatabase(true),];
        $ts     = ['', ''];
        if (is_string($table_name)) {
            $params[':tn'] = $table_name;
        }
        if (is_string($schema)) {
            $params[':ts'] = $schema;
            $ts            = [' AND tc.table_schema=:ts', ' AND ccu.table_schema=:ts'];
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare("SELECT tc.table_schema, tc.constraint_name \"name\", tc.table_name, kcu.column_name, ccu.table_schema AS foreign_table_schema, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name, false unique_column FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema WHERE tc.constraint_type = 'FOREIGN KEY' {$ts[0]} and tc.table_catalog=:db ".(is_string($table_name) ? ' AND tc.table_name=:tn' : '').
                                           'union '.
                                           "select ccu.table_schema, tc.constraint_name \"name\", ccu.table_name, ccu.column_name, tc.table_schema AS foreign_table_schema, tc.table_name AS foreign_table_name, kcu.column_name AS foreign_column_name, false unique_column FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema join (select kcu.table_catalog,kcu.table_schema, kcu.column_name, kcu.table_name from information_schema.key_column_usage kcu join information_schema.table_constraints tc on tc.table_name=kcu.table_name and tc.constraint_name=kcu.constraint_name AND tc.table_schema = kcu.table_schema and tc.constraint_type='UNIQUE') t on t.table_name=tc.table_name and t.table_catalog=tc.table_catalog and t.table_schema=tc.table_schema and t.column_name=kcu.column_name WHERE tc.constraint_type = 'FOREIGN KEY' {$ts[1]} AND ccu.table_catalog=:db ".(is_string($table_name) ? ' AND ccu.table_name=:tn' : ''));
        $stmt->execute($params);
       // echo $stmt->_debugQuery(true), "\n";
        return $this->mapForeignKeys(array_map(function($details){ return new ForeignKey($details, false); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * one to many
     *
     * @param $schema
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencingForeignKeys($table_name, $schema = null)
    {
        $params = [':db' => $this->currentDatabase(true), ':tn' => $table_name];
        if (is_string($schema)) {
            $params[':ts'] = $schema;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('SELECT false unique_column, tc.table_schema AS foreign_table_schema, tc.constraint_name "name", tc.table_name AS foreign_table_name, kcu.column_name AS foreign_column_name, '.
                                           'ccu.table_schema, ccu.table_name, ccu.column_name FROM information_schema.table_constraints AS tc '.
                                           'JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema '.
                                           'JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema '.
                                           'WHERE ccu.table_catalog=:db AND tc.constraint_type = \'FOREIGN KEY\' AND ccu.table_name=:tn '.(is_string($schema) ? ' AND ccu.table_schema=:ts ' : ''));
        $stmt->execute($params);
        //echo $stmt->_debugQuery(true),"\n";
        return $this->mapForeignKeys(array_map(function($details){ return new ForeignKey($details, true); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * @param string|Database $schema
     * @param string|DBTable  $table_name
     *
     * @return DBColumn[]
     */
    public function getColumns($schema = null, $table_name = null)
    {
        $params = ['db' => $this->currentDatabase(true) ? $this->current_db->name : null];
        $query  = 'select cmt."comment", c.is_nullable=\'YES\' is_nullable, c.table_name, c.table_schema, c.column_name, c.column_default,c.character_set_name,c.data_type,c.udt_name,c.numeric_scale, t.constraint_type,  '.
            's."increment"::double precision>0 is_auto_increment from information_schema."columns" c left join (select tc.table_name,tc.table_schema,ccu.column_name,tc.constraint_type  '.
            'from information_schema.table_constraints tc join information_schema.constraint_column_usage ccu on ccu.constraint_name=tc.constraint_name and ccu.table_name=tc.table_name and '.
            'ccu.table_schema=tc.table_schema and tc.constraint_type= \'PRIMARY KEY\') t on t.table_name=c.table_name and t.column_name=c.column_name and t.table_schema=c.table_schema '.
            'left join information_schema."sequences" s on s.sequence_schema=c.table_schema and c.column_default ilike concat(\'nextval(\'\'\',s.sequence_name,\'\'\'::regclass)\') '.
            'left join (select n.nspname,t.relname,d.objsubid, d.description "comment" from pg_catalog.pg_class t JOIN pg_namespace n ON n.oid = t.relnamespace join pg_catalog.pg_description d on d.objoid=t.oid) cmt '.
            'on c.table_schema=cmt.nspname and cmt.relname=c.table_name and c.ordinal_position=cmt.objsubid '.
            'where c.table_schema not like \'pg_%\' and c.table_schema not in (\'information_schema\') and c.table_catalog = :db '.
            (is_string($schema) ? ' AND c.TABLE_SCHEMA=:ts' : '').
            (is_string($table_name) ? ' AND c.TABLE_NAME = :tn' : '').
            ' order by c.table_name,c.ordinal_position;';
        if (is_string($schema)) {
            $params[':ts'] = $schema;
        }
        if (is_string($table_name)) {
            $params[':tn'] = $table_name;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        // echo $stmt->_debugQuery(true),"\n";
        return $this->mapColumns(array_map(function($details){ return new DBColumn($this->mapColumnsData($details)); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    protected function mapColumnsData(array $data)
    {
        $_data              = $data;
        $_data['data_type'] = [];
        foreach ($data as $name => $datum) {
            if (0 === strcasecmp('table_schema', $name)) {
                $_data['schema_name'] = $datum;
            } elseif (0 === strcasecmp('column_name', $name)) {
                $_data['name'] = $datum;
            } elseif (0 === strcasecmp('column_default', $name)) {
                $_data['default'] = $this->isAFunction($datum) ? null : $datum;
            } elseif (0 === strcasecmp('character_set_name', $name)) {
                $_data['charset'] = $datum;
            } elseif (0 === strcasecmp('data_type', $name) || 0 === strcasecmp('udt_name', $name)) {
                $_data['data_type'][] = $datum;
            } elseif (0 === strcasecmp('numeric_scale', $name)) {
                $_data['decimal_places'] = $datum;
            } elseif (0 === strcasecmp('constraint_type', $name)) {
                $_data['is_primary'] = 0 === strcasecmp('PRIMARY KEY', $datum);
            }
        }
        return $_data;
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

    public function isAFunction($name)
    {
        return in_array($name, ['current_timestamp']) || preg_match('/^(\w+)(\()(.*?)?(\))$/i', $name) || preg_match('/::/i', $name);
    }
}