<?php

    namespace EasyDb\Events;

    interface Listener
    {
        /**
         * @param array $data        [optional]
         * @param array &$parameters [optional]
         */
        public function handleEvent(array $data = [], array &$parameters = []);
    }