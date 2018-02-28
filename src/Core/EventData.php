<?php

    namespace EasyDb\Core;

    use EasyDb\Events\Event;
    use Exception;

    class EventData
    {
        protected $event;
        protected $table;
        protected $parameters;
        protected $sql;
        protected $rows_affected;
        protected $last_insert_id;
        protected $exception;

        public function __construct($event, $table = '', $parameters = [], $sql = '', $rows_affected = null, $last_insert_id = null, $exception = null) {
            $this->event = $event;
            $this->table = $table;
            $this->parameters = $parameters;
            $this->sql = $sql;
            $this->rows_affected = $rows_affected;
            $this->last_insert_id = $last_insert_id;
            $this->exception = $exception;
        }

        public static function forBefore($event, $table = '', $parameters = [], $sql = '') {
            return new static($event, $table, $parameters, $sql);
        }

        public static function forAfter($event, $table, $parameters, $sql, $rows_affected, $last_insert_id = null) {
            return new static($event, $table, $parameters, $sql, $rows_affected, $last_insert_id);
        }

        /**
         * @param Exception $exception
         * @param string    $sql        [optional]
         * @param array     $parameters [optional]
         *
         * @return EventData
         */
        public static function forException(Exception $exception, $sql = '', $parameters = []) {
            return new static(Event::ON_ERROR, '', $parameters, $sql, null, $exception);
        }

        /**
         * @return int
         */
        public function getEvent() {
            return $this->event;
        }

        /**
         * @return string
         */
        public function getTable() {
            return $this->table;
        }

        /**
         * @return array
         */
        public function getParameters() {
            return $this->parameters;
        }

        /**
         * @return string
         */
        public function getSql() {
            return $this->sql;
        }

        /**
         * @return int
         */
        public function getLastInsertId() {
            return $this->last_insert_id;
        }

        /**
         * @return int
         */
        public function getRowsAffected() {
            return $this->rows_affected;
        }

        /**
         * @return Exception
         */
        public function getException() {
            return $this->exception;
        }
    }