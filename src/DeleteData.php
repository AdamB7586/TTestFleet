<?php
namespace TheoryTest\Fleet;

class DeleteData extends \TheoryTest\Car\DeleteData{
    
    public function setTables(){
        $this->learningProgressTable = $this->config->table_fleet_progress;
        $this->progressTable = $this->config->table_fleet_test_progress;
    }
}
