<?php

    namespace EasyDb\Events;


    use EasyDb\Core\EventData;

    class Event
    {
        const ON_ERROR      = 1;
        const BEFORE_QUERY  = 2;
        const AFTER_QUERY   = 4;
        const BEFORE_UPDATE = 8;
        const AFTER_UPDATE  = 16;
        const BEFORE_INSERT = 32;
        const AFTER_INSERT  = 64;
        const BEFORE_SAVE   = 40;
        const AFTER_SAVE    = 80;
        const BEFORE_DELETE = 128;
        const AFTER_DELETE  = 256;

        /**
         * @param EventData $data
         * @param array     &$ref_parameters [optional]
         */
        public static function dispatch(EventData $data, &$ref_parameters = []) {
            $listeners = Listeners::getRegistered($data->getEvent());
            foreach ($listeners as $listener) {
                $listener::handleEvent($data, $ref_parameters);
            }
        }
    }