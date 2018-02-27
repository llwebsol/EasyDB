<?php

    namespace EasyDb\Core;

    class ConnectionPool
    {
        protected static $pool = [];

        /**
         * @param Config $config
         *
         * @return DB
         */
        public static function getDbInstance(Config $config) {
            if (!isset(self::$pool[ $config->db_name ])) {
                self::$pool[ $config->db_name ] = new DB($config);
            }

            return self::$pool[ $config->db_name ];
        }
    }