<?php

    namespace EasyDb\Exceptions;

    class DatabaseException extends \Exception
    {
        public function __construct($message = "", $code = 0, $previous = null) {
            parent::__construct($message, intval($code), $previous);
        }
    }