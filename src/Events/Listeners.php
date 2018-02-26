<?php

    namespace EasyDb\Events;

    use EasyDb\Exceptions\RegisterNonListenerException;

    class Listeners
    {
        protected static $listeners = [
            Event::BEFORE_QUERY  => [],
            Event::AFTER_QUERY   => [],
            Event::BEFORE_UPDATE => [],
            Event::AFTER_UPDATE  => [],
            Event::BEFORE_INSERT => [],
            Event::AFTER_INSERT  => [],
            Event::BEFORE_DELETE => [],
            Event::AFTER_DELETE  => []
        ];

        /**
         * @param int      $event
         * @param Listener $listener
         *
         * @throws RegisterNonListenerException
         */
        public static function register($event, $listener) {
            if (!is_a($listener, Listener::class)) {
                throw new RegisterNonListenerException();
            }

            foreach (self::$listeners as $ev => $registered_listeners) {
                if ($ev & $event && !in_array($listener, $registered_listeners)) {
                    self::$listeners[ $ev ][] = $listener;
                }
            }
        }

        /**
         * @param $event
         *
         * @return Listener[]
         */
        public static function getRegistered($event) {
            $listeners = [];
            foreach (self::$listeners as $ev => $registered_listeners) {
                if ($ev & $event) {
                    $listeners = array_merge($listeners, $registered_listeners);
                }
            }

            return $listeners;
        }
    }