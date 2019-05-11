# dbreader
## About
A Php library for reading through the database structure. Useful for generating models based on a given Database structure. For relational databases.
## DB support

 - [x] PostgreSQL
 - [x] MySQL
 - [ ] SQL Server

## use
### setup
    composer require angujo/dbreader
After getting the library into your project, need to setup the database connection.
You can do a **static connection** setup by modifying the config file at `src/configs.php` 

    return [        
      'dbms' => 'postgres',  // postgres/pgsql for postgres, mysql for MySQL
      'host' => 'localhost',  
      'port' => '5432',  
      'database' => 'dvdrental',  
      'username' => 'postgres',  
      'password' => 'postgres',  
      'charset' => 'utf8mb4',  
      'options' => [],  
    ];
**On the fly** by calling the `Config` class methods;
Setup at once

    Config::dns($dns_string,$username,$password); 
    // E.g. Config::dns('mysql:dbname=test;host:localhost;...','root','...');
or you can always change configuration on the fly;

    Config::dbms($dbms);
    Config::host($host);
    Config::port($port);
    Config::database($database);
    Config::username($username);
    Config::password($password);
and always retrieve details by calling either. to get host call `Config::host();`
If you already have a PDO connection running set it up for connection as

    Connection::setPDO($pdo,'client');
### Up and running
Get current Database

 

      $db= Connection::currentDatabase(); // $db = [Database()]

You can always call any database by initiating a new database;

    $db=new Database($db_name);
Get tables;

    $tables=$db->tables; // An array|Collection of tables i.e. DBTable[]|Tightenco\Collect\Support\Collection
  
  You can also get all columns for a given database: `$db->columns; // DBColumn[]`
For a given table, you can retrieve columns using `$table->columns`
To get relations from a given table;
**one to one relations**

    $table->$foreign_keys_one_to_one; // returns ForeignKey[]
**one to many relations**

    $table->$foreign_keys_one_to_many; // returns ForeignKey[]

WILL DO MORE DOCUMENTATION WITH TIME
... If interested, check out the code for variables accessible via the respective classes...
