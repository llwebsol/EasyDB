# EasyDB
A Simple Wrapper for PDO

To get the most out of this library, every table in your database should have a primary key called `id`.

## Getting Started
using composer

```
    composer require llwebsol/easy-db dev-master
```

## 1. Create a Config
```
    $config = new EasyDb\Core\Config([
        'db_type' => 'mysql',
        'host' => 'localhost',
        'db_name' => 'my_test_db',
        'user' => 'root',
        'password' => ''
    ]);
```

#### Accepted `db_type` options:
- mysql
- pgsql
- sqlite*
- sqlsrv*

*currently untested

#### Complete List of Options:
```
    db_type
    host
    db_name
    port
    user
    password

    // mysql specific:
    unix_socket
    charset

    // sqlsrv specific:
    app
    connection_pooling
    encrypt
    failover_partner
    login_timeout
    multiple_active_result_sets
    quoted_id
    server
    trace_file
    trace_on
    transaction_isolation
    trust_server_certificate
    wsid
```


## 2. Get a DB Instance
Use the ConnectionPool to retrieve a database instance for a given configuration.
```
$db = ConnectionPool::getDbInstance($config);
```

## 3. Querying the Database
Once you have an instance of the DB class, there are several helper methods available to you

#### - Query
accepts any database query you desire, with an optional array of bound parameters

returns a Generator for iterating through your result set

```
$query = 'SELECT * FROM users WHERE id = :user_id';
$params = [':user_id' => 7];

$results = $db->query($query,$params);
```

#### - Insert
Insert a record into a given table
Returns the last inserted id
```
$data = [
    'name' => 'Chris',
    'email' => 'chris@landlordwebsolutions.com'
];

$inserted_id = $db->insert('users', $data);
```

#### - Update
Update a record in a given table
returns the number of rows affected
```
    $data = [
        'email' => 'new.email@email.com'
    ];

    $rows_affected = $db->update('users', 76, $data);

```

#### - Save
this is just an alias for insert/update
if the $data has an 'id' field it will update, otherwise it will insert

#### - Delete
Delete a record from a given table
returns the number of rows affected or `false` if invalid
```
    // Delete the record with id=76 from 'users'
    $rows_deleted = $db->delete('users', 76);
```

#### - Delete Where
Delete records from a given table that meet the conditions of the where clause
Returns the number of rows deleted
```
// Delete all clients from Toronto or New York with a name starting with 'T'

$where = 'name LIKE :name_compare AND city_id IN (:toronto_id,:new_york_id)';
$params = [
    ':name_compare' =>  't%',
    ':toronto_id' => 5142,
    ':new_york_id' => 1432
];

$records_deleted = $db->deleteWhere('clients', $where, $params);
```

#### - Update Where
Update records from a given table that meet the conditions of the where clause
Returns the number of rows updated
```
Set Status to 'disabled' for all users with hotmail accounts

$update = [ 'status' => 'disabled' ];
$where  = 'email LIKE :email_compare';
$params = [ ':email_compare' => '%@hotmail.com' ]

$rows_updated = $db->updateWhere('users', $update, $where, $params);
```

#### - Find In
Returns a Generator with all records in table where `$column_name IN ( $in_array )`
```
    $records = $db->findIn('clients', 'city_id', [5142,1432,76,222]);
```
SQL Equivalent:
```
    SELECT *
    FROM clients
    WHERE city_id IN (5142,1432,76,222);
```

## 4. Events
You can add event listeners for any stage of a database interaction

Supported Events:
- ON_ERROR
- BEFORE_QUERY
- AFTER_QUERY
- BEFORE_UPDATE
- AFTER_UPDATE
- BEFORE_INSERT
- AFTER_INSERT
- BEFORE_DELETE
- AFTER_DELETE

Helpers:
- BEFORE_SAVE ( BEFORE_INSERT and BEFORE_UPDATE)
- AFTER_SAVE (AFTER_INSERT and AFTER_UPDATE)


### Examples

Echo the sql of every query that is performed
```
    use EasyDb\Events\Listener;

    class QueryListener implements Listener
    {

        /**
         * @param array $data            [optional]
         * @param array &$ref_parameters [optional]
         */
        public static function handleEvent(array $data = [], array &$ref_parameters = []) {
            echo $data['sql'];
        }
    }

    // Register the listener
    Listeners::register(Event::BEFORE_QUERY, QueryListener::class);
```

Add a user id to all inserted records

*Assumes all of your tables have a `created_user` column
```
 use EasyDb\Events\Listener;

    class InsertListener implements Listener
    {

        /**
         * @param array $data            [optional]
         * @param array &$ref_parameters [optional]
         */
        public static function handleEvent(array $data = [], array &$ref_parameters = []) {
            $ref_parameters['created_user'] = $_SESSION['user'];
        }
    }

    // Register the listener
    Listeners::register(Event::BEFORE_QUERY, InsertListener::class);
````

*Referenced Parameters are available for `BEFORE_INSERT` and `BEFORE_UPDATE` events only