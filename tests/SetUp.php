<?php

namespace TheoryTest\Fleet\Tests;

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use Configuration\Config;
use Smarty;
use TheoryTest\Fleet\User;

class SetUp extends TestCase{
    protected static $db;
    protected static $config;
    protected static $user;
    protected static $template;
    
    public static function setUpBeforeClass() {
        self::$db = new Database($GLOBALS['DB_HOST'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'], $GLOBALS['DB_DBNAME']);
        if(!self::$db->isConnected()){
             $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        if(self::$db->count('users') < 1){
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/user/database/database_mysql.sql'));
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/database/database_mysql.sql'));
//            self::$db->query(file_get_contents(dirname(__FILE__).'/sample_data/data.sql'));
        }
        self::$config = new Config(self::$db);
        self::$template = new Smarty();
        self::$template->setCacheDir(dirname(__FILE__).'/cache/')->setCompileDir(dirname(__FILE__).'/cache/');
        self::$user = new User(self::$db);
    }
    
    public static function tearDownAfterClass() {
        self::$db = null;
        self::$template = null;
        self::$user = null;
    }
    
}
