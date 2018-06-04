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
            $handle = self::getDbHandle($config);
            if (!isset(self::$pool[ $handle ])) {
                self::$pool[ $handle ] = new DB($config);
            }

            return self::$pool[ $handle ];
        }

        private static function getDbHandle(Config $config) {
            return implode('|', [
                $config->db_type,
                $config->host,
                $config->db_name,
                $config->user
            ]);
        }
    }