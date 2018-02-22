<?php

    namespace EasyDb\Core;

    class ConnectionPool
    {
        protected static $pool = [];

        /**
         * @param Config $config
         *
         * @return DbInstance
         */
        public static function getDbInstance(Config $config) {
            if (!isset(self::$pool[ $config->db_name ])) {
                self::$pool[ $config->db_name ] = new DbInstance($config);
            }

            return self::$pool[ $config->db_name ];
        }
    }