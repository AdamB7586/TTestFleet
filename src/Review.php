<?php

namespace TheoryTest\Fleet;

class Review extends \TheoryTest\Car\Review
{
    
    public $where = [];
    
    public $noOfTests = 0;
    
    /**
     * Returns the sections to be displayed within the table
     * @return array The list of sections for the table will be returned
     */
    public function getSectionTables()
    {
        return [
            ['table' => 'fleet_sections', 'name' => 'DVSA Category', 'section' => 'dvsa', 'sectionNo' => 'dsacat']
        ];
    }
    
    /**
     * Sets the tables
     */
    public function setTables()
    {
        $this->questionsTable = $this->config->table_fleet_questions;
        $this->learningProgressTable = $this->config->table_fleet_progress;
        $this->progressTable = $this->config->table_fleet_test_progress;
        $this->dvsaCatTable = $this->config->table_fleet_dvsa_sections;
    }
}
