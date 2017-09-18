<?php
namespace TheoryTest\ADI;

class DeleteData extends \TheoryTest\Car\DeleteData{
    public $learningProgressTable = 'fleet_progress';
    public $progressTable = 'fleet_test_progress';
}
