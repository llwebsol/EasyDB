<?php

    namespace EasyDb\Core;

    class Config
    {
        public $db_type;
        public $host;
        public $db_name;
        public $port;
        public $user;
        public $password;

        // mysql specific:
        public $unix_socket;
        public $charset;

        // sqlsrv specific:
        public $app;
        public $connection_pooling;
        public $encrypt;
        public $failover_partner;
        public $login_timeout;
        public $multiple_active_result_sets;
        public $quoted_id;
        public $server;
        public $trace_file;
        public $trace_on;
        public $transaction_isolation;
        public $trust_server_certificate;
        public $wsid;

        public function __construct(array $config) {
            foreach ($config as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }

        /**
         * @return string $quote_char
         */
        public function getSystemIdentifierQuote() {
            switch ($this->db_type) {
                case 'mysql':
                    return '`';
                default:
                    return '"';
            }
        }
    }