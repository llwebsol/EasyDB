<?php

    namespace EasyDb\Core;

    use EasyDb\Events\Event;
    use EasyDb\Exceptions\ConnectionException;
    use EasyDb\Exceptions\QueryException;
    use Exception;
    use Generator;
    use PDO;
    use PDOStatement;


    class DB
    {

        /* @var PDO */
        protected $db_connection;

        /* @var Config */
        protected $config;

        /**
         * PdoWrapper constructor.
         *
         * @param Config $config
         *
         * @throws ConnectionException
         */
        public function __construct(Config $config) {
            $this->config = $config;
            $this->connect();
        }

        /**
         * @throws ConnectionException
         */
        private function connect() {
            if (empty($this->db_connection)) {
                try {
                    $this->db_connection = new PDO(DataSourceName::fromConfig($this->config), $this->config->user, $this->config->password);
                } catch (Exception $ex) {
                    Event::dispatch(Event::ON_ERROR, $this->getExceptionDetails($ex, 'Connect'));
                    throw new ConnectionException($ex->getMessage(), $ex->getCode(), $ex);
                }
            }
        }

        /**
         * @return bool success
         */
        public function beginTransaction() {
            return $this->db_connection->beginTransaction();
        }

        /**
         * @return bool success
         */
        public function commitTransaction() {
            return $this->db_connection->commit();
        }

        /**
         * @return bool success
         */
        public function rollbackTransaction() {
            return $this->db_connection->rollBack();
        }

        /**
         * Query the database
         *
         * @param string $query_with_placeholders
         * @param array  $named_parameters
         *
         * @return Generator
         * @throws QueryException
         */
        public function query($query_with_placeholders, $named_parameters = []) {

            Event::dispatch(Event::BEFORE_QUERY, ['sql' => $query_with_placeholders, 'parameters' => $named_parameters]);
            try {
                $stmt = $this->prepareStatement($query_with_placeholders, $named_parameters);
                $stmt->execute();
            } catch (Exception $ex) {
                Event::dispatch(Event::ON_ERROR, $this->getExceptionDetails($ex, $query_with_placeholders, $named_parameters));
                throw new QueryException($ex->getMessage(), $ex->getCode(), $ex);
            }

            Event::dispatch(Event::AFTER_QUERY, ['sql' => $query_with_placeholders, 'parameters' => $named_parameters]);

            return $this->fetch($stmt);
        }

        /**
         * @param PDOStatement $pdo_statement
         *
         * @return Generator
         */
        private function fetch(PDOStatement $pdo_statement) {
            while ($row = $pdo_statement->fetch(PDO::FETCH_ASSOC)) {
                yield $row;
            }
        }

        /**
         * Convert a string query and parameters
         * to a PDO Statement
         *
         * @param $query
         * @param $parameters
         *
         * @return PDOStatement
         */
        private function prepareStatement($query, $parameters) {
            $stmt = $this->db_connection->prepare($query);

            if (!empty($parameters)) {
                foreach ($parameters as $key => $val) {
                    $stmt->bindValue($key, $val);
                }
            }

            return $stmt;
        }

        /**
         * @param $data
         * @param $table_name
         *
         * @return bool|int|string (false | rows_affected | last_insert_id )
         * @throws QueryException
         */
        public function save($data, $table_name) {

            // Insert
            if (empty($data['id'])) {
                return $this->insert($data, $table_name);
            } // Update
            else {
                return $this->update($data, $table_name);
            }
        }

        /**
         * @param array $data
         * @param       $table_name
         *
         * @return int $inserted_id
         * @throws QueryException
         */
        public function insert(array $data, $table_name) {

            Event::dispatch(Event::BEFORE_INSERT, ['table' => $table_name], $data);

            $q = $this->config->getSystemIdentifierQuote();
            $fields = [];
            $data_values = "";
            $statement_params = [];
            $null_params = [];

            foreach ($data as $field_name => $value) {
                $fields[] = "\n$q" . $field_name . $q;
                if (strtolower($value) == 'null' || is_null($value)) {
                    $data_values .= 'NULL,';
                    $null_params[ $field_name ] = 'NULL';
                } else {
                    $data_values .= ':' . $field_name . ',';
                    $statement_params[ ':' . $field_name ] = $value;
                }
            }

            $full_param_list = array_merge($statement_params, $null_params);

            $fields = implode(',', $fields);
            $data_values = substr($data_values, 0, -1);
            $sql_query = "INSERT INTO \n$q" . $table_name . "$q ( " . $fields . " )\n VALUES (" . $data_values . ");\n";
            try {
                $stmt = $this->db_connection->prepare($sql_query);
                if ($statement_params) {
                    foreach ($statement_params as $k => $v) {
                        $stmt->bindValue($k, $v);
                    }
                }
                $stmt->execute();
            } catch (Exception $ex) {
                Event::dispatch(Event::ON_ERROR, $this->getExceptionDetails($ex, $sql_query, $full_param_list));
                throw new QueryException($ex->getMessage(), $ex->getCode(), $ex);
            }

            $insert_id = $this->db_connection->lastInsertId();
            Event::dispatch(Event::AFTER_INSERT, [
                'table'      => $table_name,
                'sql'        => $sql_query,
                'parameters' => $full_param_list,
                'result'     => $insert_id
            ]);

            return $insert_id;
        }

        /**
         * @param $data
         * @param $table_name
         *
         * @return int $rows_affected
         * @throws QueryException
         */
        private function update($data, $table_name) {

            Event::dispatch(Event::BEFORE_UPDATE, ['table' => $table_name], $data);

            $q = $this->config->getSystemIdentifierQuote();

            //update database record
            $update_id = $data['id'];
            $sql_query = "UPDATE $q" . $table_name . "$q SET ";

            $statement_params = [];
            // keep track of null params for logging output. binding null params
            // was causing foreign key violations (for nullable fields)
            $null_params = [];
            foreach ($data as $field_name => $value) {
                if ($field_name != 'id') {
                    if (strtolower($value) == 'null' || is_null($value)) {
                        $sql_query .= "\n$q" . $field_name . "$q = NULL,";
                        $null_params[ $field_name ] = 'NULL';
                    } else {
                        $sql_query .= "\n$q" . $field_name . "$q = :" . $field_name . ",";
                        $statement_params[ ':' . $field_name ] = $value;
                    }
                }
            }

            $full_param_list = array_merge(['id' => $update_id], $null_params, $statement_params);

            $sql_query = substr($sql_query, 0, -1); // remove trailing comma
            $sql_query .= "\nWHERE $q" . 'id' . "$q = :update_id";
            $sql_query .= ";";

            try {
                //Run update query, collect stats
                $stmt = $this->db_connection->prepare($sql_query);
                if ($statement_params) {
                    foreach ($statement_params as $k => $v) {
                        $stmt->bindValue($k, $v);
                    }
                }
                $stmt->bindValue(':update_id', $update_id);
                $stmt->execute();
            } catch (Exception $ex) {
                Event::dispatch(Event::ON_ERROR, $this->getExceptionDetails($ex, $sql_query, $full_param_list));

                throw new QueryException($ex->getMessage(), $ex->getCode(), $ex);
            }

            $row_count = $stmt->rowCount();
            Event::dispatch(Event::AFTER_UPDATE, [
                'table'      => $table_name,
                'sql'        => $sql_query,
                'parameters' => $full_param_list,
                'result'     => $row_count
            ]);

            return $row_count;
        }

        /**
         * @param int    $id
         * @param string $table_name
         *
         * @return bool|int rows_affected
         * @throws QueryException
         */
        public function delete($id, $table_name) {
            Event::dispatch(Event::BEFORE_DELETE, ['table' => $table_name, 'parameters' => ['id' => $id]]);
            $q = $this->config->getSystemIdentifierQuote();

            if (!$id || !$table_name) {
                return false;
            }

            $sql_query = 'DELETE FROM ' . $q . $table_name . $q . ' WHERE ' . $q . 'id' . $q . ' = :delete_id';
            try {
                //Run update query, collect stats
                $stmt = $this->db_connection->prepare($sql_query);
                $stmt->bindValue(':delete_id', $id);
                $stmt->execute();
            } catch (Exception $ex) {
                Event::dispatch(Event::ON_ERROR, $this->getExceptionDetails($ex, $sql_query, ['id' => $id]));
                throw new QueryException($ex->getMessage(), $ex->getCode(), $ex);
            }
            $rows_affected = $stmt->rowCount();

            Event::dispatch(Event::AFTER_DELETE, [
                'sql'        => $sql_query,
                'table'      => $table_name,
                'parameters' => ['id' => $id],
                'result'     => $rows_affected
            ]);

            return $rows_affected;
        }

        /**
         * Performs an SQL delete with a 'WHERE' clause
         *
         * @param string $where_with_placeholders
         * @param array  $named_parameters
         * @param string $table_name
         *
         * @return bool|int rows_affected
         * @throws QueryException
         */
        function deleteWhere($where_with_placeholders, $named_parameters, $table_name) {

            if (empty($where_with_placeholders) || !$table_name) {
                return false;
            }

            $sql_query = 'DELETE FROM ' . $table_name . ' WHERE ' . $where_with_placeholders;

            Event::dispatch(Event::BEFORE_DELETE, ['table' => $table_name, 'sql' => $sql_query, 'parameters' => $named_parameters]);

            try {
                //Run Delete query, collect stats
                $stmt = $this->db_connection->prepare($sql_query);
                if (!empty($named_parameters)) {
                    foreach ($named_parameters as $k => $v) {
                        $stmt->bindValue($k, $v);
                    }
                }

                $stmt->execute();
            } catch (Exception $ex) {
                Event::dispatch(Event::ON_ERROR, $this->getExceptionDetails($ex, $sql_query, $named_parameters));

                throw new QueryException($ex->getMessage(), $ex->getCode(), $ex);
            }

            $rows_affected = $stmt->rowCount();

            Event::dispatch(Event::AFTER_DELETE, [
                'table'      => $table_name,
                'sql'        => $sql_query,
                'parameters' => $named_parameters,
                'result'     => $rows_affected
            ]);

            return $rows_affected;

        }

        /**
         * Returns all records in table where $column_name IN ( $in_array );
         *
         * @param string $table_name
         * @param string $column_name
         * @param array  $in_array
         *
         * @return Generator
         * @throws QueryException
         */
        public function findIn($table_name, $column_name, $in_array) {
            $records = new Generator();
            $params = $this->getEnumeratedParameterList($table_name . '_' . $column_name, $in_array);
            if (count($params) > 0) {
                $query = "SELECT * FROM $table_name WHERE $column_name IN (" . $this->keyList($params) . ')';
                $records = $this->query($query, $params);
            }

            return $records;
        }

        /**
         * Returns an array of named parameters
         *
         * @param string $parameter_name
         * @param array  $param_array
         *
         * @return array
         *
         *      * ex. for $parameter_name = 'user', $param_array = [123,345,456]
         *       returns: [':user_0' => 123, ':user_1' => 345, ':user_2' => 456]
         */
        private function getEnumeratedParameterList($parameter_name, $param_array) {
            $result = [];
            if ($param_array) {
                foreach ($param_array as $k => $v) {
                    $result[ ':' . $parameter_name . '_' . $k ] = $v;
                }
            }

            return $result;
        }

        /**
         * Use this to get a comma separated list of array keys
         * from the passed in array
         *
         * @param array $array
         *
         * @return string $list
         */
        private function keyList($array) {
            return implode(',', array_keys($array));
        }

        /**
         * Performs an SQL update with a 'WHERE' clause
         *
         * @param string     $table_name
         *
         * @param array      $update_values
         *  array( $column_name => $new_value )
         *
         * @param string     $where_with_placeholders
         * @param null|array $named_parameters
         *
         * @return bool|int $rows_affected
         */
        public function updateWhere($table_name, $update_values, $where_with_placeholders, $named_parameters = null) {
            Event::dispatch(Event::BEFORE_UPDATE, [
                'table'      => $table_name,
                'where'      => $where_with_placeholders,
                'parameters' => $named_parameters
            ], $update_values);

            $q = $this->config->getSystemIdentifierQuote();
            $sql_query = 'UPDATE ' . $q . $table_name . $q . ' SET ';


            $statement_params = [];
            $null_params = [];

            foreach ($update_values as $field_name => $value) {
                if ($field_name != 'id') {
                    if (strtolower($value) == 'null' || is_null($value)) {
                        $sql_query .= "\n$q" . $field_name . "$q = NULL,";
                        $null_params[ $field_name ] = 'NULL';
                    } else {
                        $sql_query .= "\n$q" . $field_name . "$q = :" . $field_name . ",";
                        $statement_params[ ':' . $field_name ] = $value;
                    }
                }
            }

            $full_param_list = array_merge($null_params, $statement_params);

            // remove trailing comma
            $sql_query = substr($sql_query, 0, -1);

            $sql_query .= ' WHERE ' . $where_with_placeholders;

            // add extra named parameters for where clause
            if ($named_parameters) {
                foreach ($named_parameters as $param => $val) {
                    $statement_params[ $param ] = $val;
                }
            }
            try {
                // prepare the query and bind parameters
                $stmt = $this->db_connection->prepare($sql_query);
                if ($statement_params) {
                    foreach ($statement_params as $k => $v) {
                        $stmt->bindValue($k, $v);
                    }
                }

                $stmt->execute();
            } catch (Exception $ex) {
                Event::dispatch(Event::ON_ERROR, $this->getExceptionDetails($ex, $sql_query, $full_param_list));

                return false;
            }

            $rows_affected = $stmt->rowCount();

            Event::dispatch(Event::AFTER_UPDATE, ['table' => $table_name, 'sql' => $sql_query, 'parameters' => $full_param_list]);

            return $rows_affected;
        }

        private function getExceptionDetails(Exception $exception, $sql = '', $parameters = []) {
            return [
                'exception'  => $exception,
                'sql'        => $sql,
                'parameters' => $parameters
            ];
        }
    }