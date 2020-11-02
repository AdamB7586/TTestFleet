<?php

namespace TheoryTest\Fleet;

use DBAL\Database;
use Configuration\Config;
use Smarty;

class LearnTest extends \TheoryTest\Car\LearnTest
{
    protected $scriptVar = 'fleetlearn';
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
     * Creates a new test for the
     * @param int $sectionNo This should be the section number for the test
     * @param mixed $type Added to make compatible with parent - not currently used
     */
    public function createNewTest($sectionNo = '1', $type = false)
    {
        $this->clearSettings();
        $this->chooseStudyQuestions($sectionNo);
        $this->setTest($sectionNo);
        $learnName = $this->getSectionInfo($this->dvsaCatTable, $sectionNo);
        if (isset($learnName['free']) && $learnName['free'] == 0 && method_exists($this->user, 'checkUserAccess')) {
            $this->user->checkUserAccess(null, 'fleet');
        }
        $this->setTestName($learnName['name']);
        return $this->buildTest();
    }
    
    /**
     * Gets the questions for the current section test
     * @param int $sectionNo This should be the section number for the test
     * @param string $type Required for compatibility with parent class
     */
    protected function chooseStudyQuestions($sectionNo, $type = '')
    {
        $this->testInfo['section'] = $sectionNo;
        setcookie('testinfo', serialize($this->testInfo), time() + 31536000, '/');
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
     * Returns the number of questions in the current section
     * @return int This should be the number of questions for the section
     */
    public function numQuestions()
    {
        return count($this->db->selectAll($this->questionsTable, ['dsacat' => $this->testInfo['section']], ['prim']));
    }
    
    /**
     * Returns the current question number
     * @return int Returns the current question number
     */
    protected function currentQuestion()
    {
        if (!isset($this->current)) {
            $this->current = $this->db->select($this->questionsTable, ['prim' => $this->currentPrim, 'dsacat' => $this->testInfo['section']], ['dsaqposition'])['dsaqposition'];
        }
        return $this->current;
    }
    
    /**
     * Returns the Previous question HTML for the current question
     * @return string Returns the previous question HTML with the correct prim number for the previous question
     */
    protected function prevQuestion()
    {
        if (isset($_COOKIE['skipCorrect']) && $_COOKIE['skipCorrect'] == 1) {
            $prim = $this->getIncomplete('prev');
        } elseif ($this->currentQuestion() != 1) {
            $prim = $this->db->fetchColumn($this->questionsTable, ['dsaqposition' => ['<', $this->currentQuestion()], 'dsacat' => $this->testInfo['section']], ['prim'], 0, ['dsaqposition' => 'DESC']);
        } else {
            $prim = $this->getLastQuestion();
        }
        return ['id' => $prim, 'text' => 'Previous', 'icon' => 'angle-left'];
    }
    
    /**
     * Returns the Next question HTML for the current question
     * @return string Returns the next question HTML with the correct prim number for the next question
     */
    protected function nextQuestion()
    {
        if (isset($_COOKIE['skipCorrect']) && $_COOKIE['skipCorrect'] == 1) {
            $prim = $this->getIncomplete();
        } elseif ($this->currentQuestion() < $this->numQuestions()) {
            $prim = $this->db->fetchColumn($this->questionsTable, ['dsaqposition' => ['>', $this->currentQuestion()], 'dsacat' => $this->testInfo['section']], ['prim'], 0, ['dsaqposition' => 'ASC']);
        } else {
            $prim = $this->getFirstQuestion();
        }
        return ['id' => $prim, 'text' => 'Next', 'icon' => 'angle-right'];
    }
    
    /**
     * Returns the prim number for the next or previous incomplete question
     * @param string $nextOrPrev Should be either next of previous for which way you want the next question to be
     * @return int|string Returns the prim number for the next or previous question or none if no more incomplete questions exist
     */
    protected function getIncomplete($nextOrPrev = 'next')
    {
        if (strtolower($nextOrPrev) == 'next') {
            $dir = '>';
            $sort = 'ASC';
            $start = '0';
        } else {
            $dir = '<';
            $sort = 'DESC';
            $start = '100000';
        }
        
        $searchCurrentQuestion = $this->findNextQuestion($dir, $this->currentQuestion(), $sort);
        if ($searchCurrentQuestion !== false) {
            return $searchCurrentQuestion;
        }
        $searchStart = $this->findNextQuestion($dir, $start, $sort);
        if ($searchStart !== false) {
            return $searchStart;
        }
        return 'none';
    }
    
    /**
     * Finds the next question from the given parameters
     * @param string $dir This should be the direction to search for the next question '>' or '<'
     * @param int $start The start number to search for the next question
     * @param string $sort The sort order for the next question ASC or DESC
     * @return int|false Will return the prim number for the next question
     */
    protected function findNextQuestion($dir, $start, $sort)
    {
        foreach ($this->db->selectAll($this->questionsTable, ['dsaqposition' => [$dir, $start], 'dsacat' => $this->testInfo['section']], ['prim'], ['dsaqposition' => $sort]) as $question) {
            if ($this->useranswers[$question['prim']]['status'] <= 1) {
                return $question['prim'];
            }
        }
        return false;
    }
    
    /**
     * Returns the first questions prim number for the current section
     * @return int Returns the prim number of the first question in the current section
     */
    protected function getFirstQuestion()
    {
        return $this->db->fetchColumn($this->questionsTable, ['dsaqposition' => '1', 'dsacat' => $this->testInfo['section']], ['prim']);
    }
    
    /**
     * Returns the prim number for the last question
     * @return int Returns the prim number for the last question
     */
    protected function getLastQuestion()
    {
        return $this->db->fetchColumn($this->questionsTable, ['dsacat' => $this->testInfo['section']], ['prim'], 0, ['dsaqposition' => 'DESC']);
    }
    
    /**
     * Returns any extra content to be displayed on the page
     * @return string
     */
    protected function extraContent()
    {
        return '</div></div><div class="row"><div><div class="col-xs-12 skipcorrectclear"><div class="skipcorrect btn btn-theory'.(isset($_COOKIE['skipCorrect']) && $_COOKIE['skipCorrect'] == 1 ? ' flagged' : '').'">Skip Correct</div></div>';
    }
    
    /**
     * Returns the correct button for the learning test section
     * @param boolean $prim Added for compatibility with parent class
     * @return string Returns the button HTML
     */
    protected function flagHintButton($prim = false)
    {
        return false;
    }
    
    /**
     * Returns any related information about the current question
     * @param string $explanation This should be the DVSA explanation for the database as it has already been retrieved
     * @param int $prim This should be the questions unique prim number
     * @return boolean Should only return false as no fleet explanations exist
     */
    public function dsaExplanation($explanation, $prim)
    {
        return false;
    }
}
