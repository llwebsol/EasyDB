<?php

    namespace EasyDb\Core;

    class DataSourceName
    {

        /*@var Config */
        protected $config;


        /**
         * @param Config $config
         *
         * @return string
         */
        public static function fromConfig(Config $config) {
            return (new self($config))->toString();
        }


        public function __construct(Config $config) {
            $this->config = $config;
        }

        public function toString() {

            switch ($this->config->val('db_type')) {
                case 'pgsql':
                    return $this->pgsql();
                case 'sqlite':
                    return $this->sqlite();
                case 'sqlsrv':
                    return $this->sqlsrv();
                case 'mysql':
                default:
                    return $this->mysql();
            }
        }

        private function pgsql() {
            $dsn = 'pgsql:';
            $dsn .= $this->argString('host', 'host', '');
            $dsn .= $this->argString('dbname', 'db_name');
            $dsn .= $this->argString('port', 'port');

            return $dsn;
        }

        private function mysql() {
            $dsn = 'mysql:';
            $dsn .= $this->argString('host', 'host', '');
            $dsn .= $this->argString('dbname', 'db_name');
            $dsn .= $this->argString('port', 'port');
            $dsn .= $this->argString('unix_socket', 'unix_socket');
            $dsn .= $this->argString('charset', 'charset');

            return $dsn;
        }

        private function sqlite() {
            $dsn = 'sqlite:';
            if (isset($this->config['path'])) {
                $dsn .= $this->config['path'];
            } else {
                $dsn .= ':memory:';
            }

            return $dsn;
        }

        private function sqlsrv() {
            $dsn = 'sqlsrv:';
            $dsn .= $this->argString('APP', 'app', '');
            $dsn .= $this->argString('ConnectionPooling', 'connection_pooling');
            $dsn .= $this->argString('Database', 'db_name');
            $dsn .= $this->argString('Encrypt', 'encrypt');
            $dsn .= $this->argString('Failover_Partner', 'failover_partner');
            $dsn .= $this->argString('LoginTimeout', 'login_timeout');
            $dsn .= $this->argString('MultipleActiveResultSets', 'multiple_active_result_sets');
            $dsn .= $this->argString('QuotedId', 'quoted_id');
            $dsn .= $this->argString('Server', 'server');
            $dsn .= $this->argString('TraceFile', 'trace_file');
            $dsn .= $this->argString('TraceOn', 'trace_on');
            $dsn .= $this->argString('TransactionIsolation', 'transaction_isolation');
            $dsn .= $this->argString('TrustServerCertificate', 'trust_server_certificate');
            $dsn .= $this->argString('WSID', 'wsid');

            return $dsn;
        }

        private function argString($name, $config_key, $prefix = ';') {
            if ($this->config->{$config_key}) {
                return $prefix . $name . '=' . $this->config->{$config_key};
            }

            return '';
        }

    }