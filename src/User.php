<?php

namespace TheoryTest\Fleet;

class User extends \TheoryTest\Car\User{
    
    /**
     * Checks to see if the user has upgraded their account and has access to the given test/learning section
     * @param int $testID This should be the test ID you are checking if the user has access to
     * @param string $type This should be the type field to check
     * @return boolean|void If the user has access will return try else will redirect the user to the upgrade page
     */
    public function checkUserAccess($testID = 100, $type = 'fleet'){
        return true;
    }
}
