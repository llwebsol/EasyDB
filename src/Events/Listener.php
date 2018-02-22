<?php

    namespace EasyDb\Events;

    interface Listener
    {
        /**
         * @param array $data            [optional]
         * @param array &$ref_parameters [optional]
         */
        public function handleEvent(array $data = [], array &$ref_parameters = []);
    }