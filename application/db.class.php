<?php
class Db {
    private static $instance = NULL;

    private function __construct() {}

    private function __clone() {}

    public static function getInstance($config = array('host'=>DB_SERVER,'db_name'=>DB_DATABASE,'db_username'=>DB_USERNAME,'db_password'=>DB_PASSWORD)) {
      if (!isset(self::$instance)) {
        //$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        self::$instance = new PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['db_name'], $config['db_username'], $config['db_password']);
        self::$instance->exec("SET CHARACTER SET utf8");
      }
    }
    public static function dbConnection(){
      return self::$instance;
    }

} 