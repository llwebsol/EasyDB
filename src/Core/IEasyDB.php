<?php

    namespace EasyDb\Core;

    interface IEasyDB
    {
        public function __construct(Config $config);

        public function beginTransaction();

        public function commitTransaction();

        public function rollbackTransaction();

        public function queryOne($query_with_placeholders, $named_parameters = []);

        public function query($query_with_placeholders, $named_parameters = []);

        public function save($table_name, $data);

        public function insert($table_name, array $data);

        public function update($table_name, $id, array $data);

        public function delete($table_name, $id);

        function deleteWhere($table_name, $where_with_placeholders, $named_parameters);

        public function findIn($table_name, $column_name, array $in_array);

        public function updateWhere($table_name, $update_values, $where_with_placeholders, $named_parameters = null);
    }