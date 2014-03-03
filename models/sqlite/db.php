<?php

class Db {
    const DATABASE = '/tmp/lighting-server.db';

    private static $_db;

    public static function &get_instance() {
        if(! isset(self::$_db)) {
            self::$_db = new SQLite3(self::DATABASE);
        }

        return self::$_db;
    }
}
