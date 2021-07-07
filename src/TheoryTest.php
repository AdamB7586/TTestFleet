<?php

namespace TheoryTest\Fleet;

use DBAL\Database;
use Configuration\Config;
use Smarty;

class TheoryTest extends \TheoryTest\Car\TheoryTest
{
    protected $testNo = 1;
    protected $seconds = 5400;
    protected $section = 'aditheory';
    
    protected $testName = 'Fleet Theory Test';
    
    public $passmark = 85;
    public $passmarkPerCat = 20;
    
    protected $testType = 'fleet';
    
    protected $scriptVar = 'fleet';
    
    protected $uniqueTestID;
    
    /**
     * Set up all of the components needed to create a Theory Test
     * @param Database $db This needs to be an instance of the database class
     * @param Config $config This needs to be an instance of the Configuration class
     * @param Smarty $layout This needs to be an instance of the Smarty Template class
     * @param object $user This should be the user class used
     * @param int|false $userID If you want to emulate a user set the user ID here
     * @param string|false $templateDir If you want to change the template location set this location here
     * @param string $theme This is the template folder to look at currently either 'bootstrap' or 'bootstrap4'
     */
    public function __construct(Database $db, Config $config, Smarty $layout, $user, $userID = false, $templateDir = false, $theme = 'bootstrap')
    {
        parent::__construct($db, $config, $layout, $user, $userID, $templateDir, $theme);
        $this->layout->addTemplateDir(($templateDir === false ? str_replace(basename(__DIR__), '', dirname(__FILE__)).'templates'.DS.$theme : $templateDir), 'fleettheory');
        $this->setImagePath(DS.'images'.DS.'fleet'.DS);
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
    
    /**
     * Set the unique testID
     * @param int $testID This should be the test ID that you want to set as the current test
     * @return $this
     */
    public function setTestID($testID)
    {
        if (is_numeric($testID)) {
            $this->uniqueTestID = intval($testID);
            unset($this->useranswers);
            unset($this->questions);
            $this->getUserAnswers();
            $this->getQuestions();
            $this->getTestResults();
        }
        return $this;
    }
    
    /**
     * Returns the unique test ID for the test information you are retrieving
     * @return int This is the unique test ID of the test
     */
    public function getTestID()
    {
        if (is_int($this->uniqueTestID)) {
            return $this->uniqueTestID;
        }
        $testID = $this->db->fetchColumn($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $this->getTest()], ['id'], 0, ['started' => 'DESC'], false);
        if (is_numeric($testID)) {
            $this->setTestID($testID);
        }
        return intval($this->uniqueTestID);
    }

    /**
     * Create a new Random Fleet Theory Test for the test number given
     * @param int $test Should be the test number
     */
    public function createNewTest($test = 1)
    {
        $this->clearSettings();
        if (method_exists($this->user, 'checkUserAccess')) {
            $this->user->checkUserAccess(100, 'fleet');
        }
        if ($this->anyExisting() === false) {
            $this->chooseQuestions(1);
            $this->setTest($test);
        }
        return $this->buildTest();
    }
    
    /**
     * Creates the test report HTML if the test has been completed
     * @param int $theorytest The test number you wish to view the report for
     * @return string Returns the HTML for the test report for the given test ID
     */
    public function createTestReport($theorytest = 1)
    {
        if ($this->getTestResults()) {
            $this->setTestName($this->testName);
            return $this->buildReport(false);
        }
        return $this->layout->fetch('report'.DIRECTORY_SEPARATOR.'report-unavail.tpl');
    }
    
    /**
     * Deletes the existing test for the current user if they wish to start again
     * @return boolean If existing tests are deleted will return true else will return false
     */
    public function startNewTest()
    {
        return json_encode($this->db->delete($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'status' => 0]));
    }
    
    /**
     * Choose some random questions from each of the categories and insert them into the progress database
     * @param int $testNo This should be the test number you which to get the questions for
     * @return boolean
     */
    protected function chooseQuestions($testNo)
    {
        $this->db->delete($this->progressTable, ['user_id' => $this->getUserID(), 'status' => 0]);
        $questions = $this->db->query("(SELECT `prim` FROM `{$this->questionsTable}` WHERE `dsacat` = '1' ORDER BY RAND() LIMIT 25)
UNION (SELECT `prim` FROM `{$this->questionsTable}` WHERE `dsacat` = '2' ORDER BY RAND() LIMIT 25)
UNION (SELECT `prim` FROM `{$this->questionsTable}` WHERE `dsacat` = '3' ORDER BY RAND() LIMIT 25)
UNION (SELECT `prim` FROM `{$this->questionsTable}` WHERE `dsacat` = '4' ORDER BY RAND() LIMIT 25) ORDER BY RAND();", [], false);
        unset($_SESSION['test'.$this->getTest()]);
        unset($_SESSION['question_no']);
        foreach ($questions as $q => $question) {
            $this->questions[($q + 1)] = $question['prim'];
        }
        return $this->db->insert($this->progressTable, ['user_id' => $this->getUserID(), 'questions' => serialize($this->questions), 'answers' => serialize([]), 'test_id' => $this->testNo, 'started' => date('Y-m-d H:i:s'), 'status' => 0]);
    }
    
    /**
     * Checks to see if their is currently a test which is not complete or a test which has already been passed
     * @return string|boolean
     */
    protected function anyExisting()
    {
        $existing = $this->db->select($this->progressTable, ['user_id' => $this->getUserID(), 'status' => ['<=', 1]], '*', [], false);
        if ($existing) {
            $this->exists = true;
            if ($existing['status'] == 1) {
                return 'passed';
            } else {
                return 'exists';
            }
        }
        return false;
    }
    
    /**
     * Gets the questions array from the database if $this->questions is not set
     * @return array|false Returns the questions array if exists else returns false
     */
    public function getQuestions()
    {
        if (!isset($this->questions)) {
            $questions = $this->db->fetchColumn($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'id' => $this->getTestID()], ['questions']);
            if ($questions) {
                $this->questions = unserialize($questions);
                return $this->questions;
            }
            return false;
        }
        return $this->questions;
    }
    
    /**
     * Returns the current users answers for the current test
     * @return array|false Returns the current users answers for the current test if exists else returns false
     */
    public function getUserAnswers()
    {
        if (!isset($this->useranswers)) {
            $answers = $this->db->select($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'id' => $this->getTestID()], ['id', 'answers', 'question_no'], [], false);
            if ($answers) {
                $this->useranswers = unserialize($answers['answers']);
                if (!isset($_SESSION['test'.$this->getTest()])) {
                    $_SESSION['test'.$this->getTest()] = $this->useranswers;
                }
                if (!isset($_SESSION['question_no']['test'.$this->getTest()])) {
                    $_SESSION['question_no']['test'.$this->getTest()] = $answers['question_no'];
                }
                $this->testID = $answers['id'];
                return $this->useranswers;
            }
            return false;
        }
        return $this->useranswers;
    }
    
    /**
     * Updates the useranswers field in the progress table in the database
     * @return boolean If updated successfully returns true else returns false
     */
    protected function updateAnswers()
    {
        return json_encode($this->db->update($this->progressTable, ['answers' => serialize($_SESSION['test'.$this->getTest()]), 'time_remaining' => $_SESSION['time_remaining']['test'.$this->getTest()], 'question_no' => $_SESSION['question_no']['test'.$this->getTest()]], ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'id' => $this->getTestID()]));
    }
    
    /**
     * Returns the question data for the given prim number
     * @param int $prim Should be the question prim number
     * @return array|boolean Returns question data as array if data exists else returns false
     */
    protected function getQuestionData($prim)
    {
        return $this->db->select($this->questionsTable, ['prim' => $prim]);
    }
   
    /**
     * Returns the correct HTML for the DVSA explanation in the review section
     * @param string $explanation Should be the DVSA explanation for the particular question
     * @param int $prim Should be the prim number of the current question
     * @return string|boolean Returns the HTML string if in the review section else returns false
     */
    public function dsaExplanation($explanation, $prim)
    {
        return false;
    }
    
    /**
     * Returns the flag hint button if it should be displayed
     * @param int $prim The prim number of the question
     * @return string Returns the flag hint button if it should be displayed else will return blank spacer
     */
    protected function flagHintButton($prim)
    {
        if ($this->review !== 'answers') {
            return ['text' => 'Flag Question', 'class' => 'flag'.($this->questionFlagged($prim) ? ' flagged' : ''), 'icon' => 'flag'];
        }
        return false;
    }
    
    /**
     * Marks the current test
     * @return void nothing is returned
     */
    protected function markTest($time = false)
    {
        $this->getQuestions();
        foreach ($this->questions as $prim) {
            if ($_SESSION['test'.$this->getTest()][$this->questionNo($prim)]['status'] == 4) {
                $type = 'correct';
            } else {
                $type = 'incorrect';
            }
             
             $dvsa = $this->getDSACat($prim);
             $this->testresults['dvsa'][$dvsa][$type] = (int)$this->testresults['dvsa'][$dvsa][$type] + 1;
        }
        
        $pass = true;
        foreach ($this->testresults['dvsa'] as $category => $value) {
            if ($pass !== false) {
                if ($value['correct'] < $this->passmarkPerCat) {
                    $pass = false;
                }
            }
        }
        
        $this->testresults['correct'] = $this->numCorrect();
        $this->testresults['incorrect'] = ($this->numQuestions() - $this->numCorrect());
        $this->testresults['incomplete'] = $this->numIncomplete();
        $this->testresults['flagged'] = $this->numFlagged();
        $this->testresults['numquestions'] = $this->numQuestions();
        $this->testresults['percent']['correct'] = round(($this->testresults['correct'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['incorrect'] = round(($this->testresults['incorrect'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['flagged'] = round(($this->testresults['flagged'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['incomplete'] = round(($this->testresults['incomplete'] / $this->testresults['numquestions']) * 100);
        if ($this->numCorrect() >= $this->passmark && $pass === true) {
            $this->testresults['status'] = 'pass';
            $status = 1;
        } else {
            $this->testresults['status'] = 'fail';
            $status = 2;
        }
        
        if ($time !== false && preg_match('~[0-9]+~', $time)) {
            list($mins, $secs) = explode(':', $time);
            $newtime = gmdate('i:s', ($this->getStartSeconds() - (($mins * 60) + $secs)));
            $this->userProgress['time_taken'] = $newtime;
        }
        $this->db->update($this->progressTable, array_merge(['status' => $status, 'results' => serialize($this->testresults), 'complete' => date('Y-m-d H:i:s'), 'totalscore' => $this->numCorrect()], ($time !== false ? ['time_taken' => $newtime] : [])), ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'status' => 0]);
    }
    
    /**
     * Produces the amount of time the user has spent on the test
     * @param int $time This should be the amount of seconds remaining for the current test
     */
    public function setTime($time, $type = 'taken')
    {
        if ($time) {
            if ($type == 'taken') {
                list($hours, $mins, $secs) = explode(':', $time);
                $time = gmdate('H:i:s', ($this->seconds - (($hours * 60 * 60) + ($mins * 60) + $secs)));
                $this->db->update($this->progressTable, ['time_'.$type => $time], ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'status' => 0, 'id' => $this->getTestID()]);
            } else {
                $_SESSION['time_'.$type]['test'.$this->getTest()] = $time;
            }
        }
    }
    
    /**
     * Returns the number of seconds remaining for the current test
     * @return int Returns the number of seconds remaining for the current test
     */
    protected function getSeconds()
    {
        $time = $this->getTime('remaining');
        list($hours, $minutes, $seconds) = explode(':', $time);
        return (($hours * 3600) + ($minutes * 60) + $seconds);
    }
    
    /**
     * Returns the print certificate/results button to display on the page
     * @return string Returns either the print certificate of results button depending on how the user did on the test
     */
    protected function printCertif()
    {
        return false;
    }
    
    /**
     * Returns the test results for the current test
     * @return boolean|array If the test has been completed the test results will be returned as an array else will return false
     */
    public function getTestResults()
    {
        $results = $this->db->select($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'status' => ['>', 0], 'id' => $this->getTestID()], ['id', 'test_id', 'results', 'started', 'complete', 'time_taken', 'status'], [], false);
        if ($results) {
            $this->testresults = unserialize($results['results']);
            $this->testresults['id'] = $results['id'];
            $this->testresults['test_id'] = $results['test_id'];
            $this->testresults['complete'] = $results['complete'];
            return $this->testresults;
        }
        return false;
    }
    
    /**
     * Gets the Time taken or 'remaining for the current test
     * @param string $type This should be either set to 'taken' or 'remaining' depending on which you wish to get 'taken' by default
     * @return string Returns the time from the database
     */
    public function getTime($type = 'taken')
    {
        return $this->db->fetchColumn($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'id' => $this->getTestID()], ['time_'.$type], 0, [], false);
    }
}
