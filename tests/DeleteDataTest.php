<?php

namespace TheoryTest\Fleet\Tests;

use TheoryTest\Fleet\DeleteData;

class DeleteDataTest extends SetUp{
    protected $delete;
    
    protected function setUp() {
        $this->delete = new DeleteData(self::$db, self::$config, self::$user);
    }
    
    protected function tearDown() {
        $this->delete = null;
    }
    
    public function testExample() {
        $this->markTestIncomplete();
    }
    
}
