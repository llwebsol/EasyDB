<?php

    namespace EasyDb\Events;

    use EasyDb\Core\EventData;

    interface Listener
    {
        /**
         * @param EventData $data
         * @param array     &$ref_parameters [optional]
         */
        public static function handleEvent(EventData $data, array &$ref_parameters = []);
    }