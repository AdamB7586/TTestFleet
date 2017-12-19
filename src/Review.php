<?php

namespace TheoryTest\Fleet;

class Review extends \TheoryTest\Car\Review{
    
    public $where = array();
    
    public $noOfTests = 1;
    
    protected $questionsTable = 'fleet_questions';
    protected $DSACatTable = 'fleet_sections';
    protected $progressTable = 'fleet_progress';
    protected $testProgressTable = 'fleet_test_progress';
    
    protected $testType = 'Fleet';
    
    public function getSectionTables(){
        return array(
            array('table' => 'fleet_sections', 'name' => 'DVSA Category', 'section' => 'dsa', 'sectionNo' => 'dsacat')
        );
    }
}
