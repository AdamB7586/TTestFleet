<?php

namespace TheoryTest\Fleet;

use DBAL\Database;
use Smarty;
use UserAuth\User;

class LearnTest extends \TheoryTest\Car\LearnTest{
    public $questionsTable = 'fleet_questions';
    public $progressTable = 'fleet_progress';
    public $dsaCategoriesTable = 'fleet_sections';
    
    /**
     * Set up all of the components needed to create a Theory Test
     * @param Database $db This should be an instance of Database
     * @param Smarty $layout This needs to be an instance of Smarty Templating
     * @param User $user This should be and instance if the User Class
     * @param false|int $userID If you wish to emulate a user set this value to the users ID else set to false
     */
    public function __construct(Database $db, Smarty $layout, User $user, $userID = false) {
        parent::__construct($db, $layout, $user, $userID);
        $this->setImagePath(ROOT.DS.'images'.DS.'fleet'.DS);
    }
    
    /**
     * Creates a new test for the 
     * @param int $sectionNo This should be the section number for the test
     */
    public function createNewTest($sectionNo = '1'){
        $this->clearSettings();
        $this->chooseQuestions($sectionNo);
        $this->setTest($sectionNo);
        $learnName = self::$db->select('fleet_sections', array('section' => $sectionNo), array('name', 'free'));
        if($learnName['free'] == 0){self::$user->checkUserAccess(NULL, 'fleet');}
        $this->setTestName($learnName['name']);
        return $this->buildTest();
    }
    
    /**
     * Gets the questions for the current section test
     * @param int $sectionNo This should be the section number for the test
     */
    protected function chooseQuestions($sectionNo) {
        $this->testInfo['section'] = $sectionNo;
        setcookie('testinfo', serialize($this->testInfo), time() + 31536000, '/');
    }
    
    /**
     * Returns the question data for the given prim number
     * @param int $prim Should be the question prim number
     * @return array|boolean Returns question data as array if data exists else returns false
     */
    protected function getQuestionData($prim){
        return self::$db->select($this->questionsTable, array('prim' => $prim), array('prim', 'question', 'mark', 'option1', 'option2', 'option3', 'option4', 'answerletters', 'format', 'dsaimageid'));
    }
    
    /**
     * Make sure the audio doesn't appear as no audio currently exists for the fleet questions
     * @return boolean Returns false as no fleet audio exists
     */
    protected function audioButton(){
        return false;
    }
    
    /**
     * Returns the HTML5 audio information as a string
     * @param int $prim This should be the question prim number
     * @param string $letter This should be the letter of the question or answer
     * @return boolean Returns false as no audio exists for fleet
     */
    protected function addAudio($prim, $letter){
        return false;
    }
    
    /**
     * Returns the number of questions in the current section
     * @return int This should be the number of questions for the section
     */
    public function numQuestions(){
        return count(self::$db->selectAll($this->questionsTable, array('dsacat' => $this->testInfo['section']), array('prim')));
    }
    
    /**
     * Returns the current question number
     * @param int $prim This should be the current questions unique prim number
     * @return int Returns the current question number
     */
    protected function currentQuestion(){
        if(!isset($this->current)){
            $currentnum = self::$db->select($this->questionsTable, array('prim' => $this->currentPrim, 'dsacat' => $this->testInfo['section']), array('dsaqposition'));
            $this->current = $currentnum['dsaqposition'];
        }
        return $this->current;
    }
    
    /**
     * Returns the Previous question HTML for the current question
     * @return string Returns the previous question HTML with the correct prim number for the previous question
     */
    protected function prevQuestion(){
        if($_COOKIE['skipCorrect'] == 1){$prim = $this->getIncomplete('prev');}
        elseif($this->currentQuestion() != 1){
            $prim = self::$db->select($this->questionsTable, array('dsaqposition' => array('<', $this->currentQuestion()), 'dsacat' => $this->testInfo['section']), array('prim'), 0, array('dsaqposition' => 'DESC'));
        }
        else{$prim = $this->getLastQuestion();}
        return '<div class="prevquestion btn btn-theory" id="'.$prim.'"><span class="fa fa-angle-left fa-fw"></span><span class="hidden-xs"> Previous</span></div>';
    }
    
    /**
     * Returns the Next question HTML for the current question
     * @return string Returns the next question HTML with the correct prim number for the next question
     */
    protected function nextQuestion(){
        if($_COOKIE['skipCorrect'] == 1){$prim = $this->getIncomplete();}
        elseif($this->currentQuestion() < $this->numQuestions()){
            $prim = self::$db->fetchColumn($this->questionsTable, array('dsaqposition' => array('>', $this->currentQuestion()), 'dsacat' => $this->testInfo['section']), array('prim'), 0, array('dsaqposition' => 'ASC'));
        }
        else{$prim = $this->getFirstQuestion();}
        return '<div class="nextquestion btn btn-theory" id="'.$prim.'"><span class="hidden-xs">Next </span><span class="fa fa-angle-right fa-fw"></span></div>';
    }
    
    /**
     * Returns the prim number for the next or previous incomplete question
     * @param string $nextOrPrev Should be either next of previous for which way you want the next question to be
     * @return int|string Returns the prim number for the next or previous question or none if no more incomplete questions exist
     */
    protected function getIncomplete($nextOrPrev = 'next'){
        if(strtolower($nextOrPrev) == 'next'){$dir = '>'; $sort = 'ASC'; $start = '0';}
        else{$dir = '<'; $sort = 'DESC'; $start = '100000';}
        
        $questions = self::$db->selectAll($this->questionsTable, array('dsaqposition' => array($dir, $this->currentQuestion()), 'dsacat' => $this->testInfo['section']), array('prim'), array('dsaqposition' => $sort));
        foreach($questions as $question){
            if($this->useranswers[$question['prim']]['status'] <= 1){
                return $question['prim'];
            }
        }
        
        $questions = self::$db->selectAll($this->questionsTable, array('dsaqposition' => array($dir, $start), 'dsacat' => $this->testInfo['section']), array('prim'), array('dsaqposition' => $sort));
        foreach($questions as $question){
            if($this->useranswers[$question['prim']]['status'] <= 1){
                return $question['prim'];
            }
        }
        return 'none';
    }
    
    /**
     * Returns the first questions prim number for the current section
     * @return int Returns the prim number of the first question in the current section
     */
    protected function getFirstQuestion(){
        return self::$db->fetchColumn($this->questionsTable, array('dsaqposition' => '1', 'dsacat' => $this->testInfo['section']), array('prim'));
    }
    
    /**
     * Returns the prim number for the last question
     * @return int Returns the prim number for the last question
     */
    protected function getLastQuestion(){
        return self::$db->fetchColumn($this->questionsTable, array('dsacat' => $this->testInfo['section']), array('prim'), array('dsaqposition' => 'DESC'));
    }
    
    /**
     * Returns any extra content to be displayed on the page
     * @return string
     */
    protected function extraContent(){
        if($_COOKIE['skipCorrect'] == 1){$skipcorrect = ' flagged';}
        return '</div></div><div class="row"><div><div class="col-xs-12 skipcorrectclear"><div class="skipcorrect btn btn-theory'.$skipcorrect.'">Skip Correct</div></div>';
    }
    
    /**
     * Returns the correct button for the learning test section
     * @return string Returns the button HTML
     */
    protected function flagHintButton(){
        return false;
    }
    
    /**
     * Returns any related information about the current question
     * @param string $explanation This should be the DSA explanation for the database as it has already been retrieved
     * @param int $prim This should be the questions unique prim number
     * @return boolean Should only return false as no fleet explanations exist
     */
    public function dsaExplanation($explanation, $prim){
        return false;
    }
}