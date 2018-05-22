<?php

namespace TheoryTest\Fleet;

class Review extends \TheoryTest\Car\Review{
    
    public $where = array();
    
    public $noOfTests = 1;
    
    protected $testType = 'Fleet';
    
    public function getSectionTables(){
        return array(
            array('table' => 'fleet_sections', 'name' => 'DVSA Category', 'section' => 'dsa', 'sectionNo' => 'dsacat')
        );
    }
    
    /**
     * Sets the tables
     */
    public function setTables() {
        $this->questionsTable = $this->config->table_fleet_questions;
        $this->learningProgressTable = $this->config->table_fleet_progress;
        $this->progressTable = $this->config->table_fleet_test_progress;
        $this->dvsaCatTable = $this->config->table_fleet_dvsa_sections;
    }
}
