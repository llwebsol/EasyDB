<?php

    namespace EasyDb\Exceptions;

    class RegisterNonListenerException extends DatabaseException
    {
        protected $message = 'Registered Listeners must implement EasyDb\Events\Listener interface';
    }