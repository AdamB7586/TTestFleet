<?php

namespace TheoryTest\Fleet;

/**
 * @codeCoverageIgnore
 */
class TheoryTestCertificate extends \TheoryTest\Car\TheoryTestCertificate{
    protected $testType = 'FLEET';
    
    public function generateCertificate(){
        $this->theory->getQuestions();
        $this->theory->getTestResults();
        $this->theory->getUserAnswers();
        
        $this->PDFInfo();
        if($this->theory->testresults['status'] == 'pass'){
            $this->pdf->AddPage();
            $this->pdf->Image($this->getCertificateImage(), 0, 0, 210, 297);
            $this->pdf->SetFont('Arial','B', 24);
            $this->pdf->Ln(30);
            $this->pdf->Cell(190, 15, strip_tags($this->theory->getTestName()), 0, 0, 'C');
            $this->pdf->Ln(30);
            $this->pdf->SetFont('Arial','B', 18);
            $this->pdf->Cell(10, 14, '', 0); $this->pdf->Cell(28, 14, 'Candidate', 0);
            $this->pdf->Ln(12);
            $this->certLine('Name:', $this->certUsername);
            $this->pdf->Ln(10);
            $this->pdf->SetFont('Arial','B', 18);
            $this->pdf->Cell(10, 10, '', 0); $this->pdf->Cell(14, 10, 'Test', 0);
            $this->pdf->Ln(12);
            $this->certLine('Test ID:', strtoupper($this->testType).$this->theory->testresults['id']);
            $this->certLine('Test Name:', strip_tags($this->theory->getTestName()));
            $this->certLine('Completion Date/Time:', date('d/m/Y g:i A', strtotime($this->theory->testresults['complete'])));
            $this->certLine('Score:', $this->theory->testresults['correct'].' / '.$this->theory->numQuestions());
            $this->certLine('Passmark:', $this->theory->passmark.' / '.$this->theory->numQuestions());
            $this->pdf->SetFont('Arial','B', 14);
            $this->pdf->Cell(10, 10, '', 0); $this->pdf->Cell(72, 10, 'Status:', 0);
            $this->pdf->SetTextColor(0,151,0);
            $this->pdf->Cell(92, 10, 'Passed', 0);
            $this->pdf->SetTextColor(0,0,0);
        }
        
        $this->pdf->AddPage('P', 'A4');
        $this->pdf->SetFont('Arial','B', 8);
        $detailsheader = ['Name', 'Test Name', 'Unique Test ID', 'Taken on Date/Time'];
        $details = [[$this->certUsername, strip_tags($this->theory->getTestName()), $this->theory->testresults['id'], date('d/m/Y g:i A', strtotime($this->theory->testresults['complete']))]];
        $tablewidths = [52,52,39,47];
        $this->pdf->basicTable($detailsheader, $details, $tablewidths);
        $this->pdf->Ln();
        $this->pdf->SetFont('Arial','B', 16);
        $this->pdf->Cell(92, 10, 'Theory Test Report', 0);
        $this->pdf->Ln(8);
        $this->pdf->SetFont('Arial','', 12);
        if($this->theory->testresults['status'] == 'pass'){
            $this->pdf->Cell(184, 10, "Congratulations ".$this->certUsername); $this->pdf->Ln(4);
            $this->pdf->Cell(184, 10, "You have passed this test with ".$this->theory->testresults['percent']['correct']."%."); $this->pdf->Ln(4);
            $this->pdf->Cell(184, 10, "You answered ".$this->theory->testresults['correct']." out of ".$this->theory->testresults['numquestions']." questions correctly");
        }
        else{
            $this->pdf->Cell(184, 10, "Sorry ".$this->certUsername.", but you have not passed this time."); $this->pdf->Ln(4);
            $this->pdf->Cell(184, 10, "You answered ".$this->theory->testresults['correct'].' out of '.$this->theory->testresults['numquestions']." questions correctly, the pass rate is ".$this->theory->passmark." out of ".$this->theory->testresults['numquestions']);
        }
        $this->pdf->Ln(12);
        $this->infoLine('Test:', strip_tags($this->theory->getTestName()));
        $this->infoLine('Date:', date('d/m/Y', strtotime($this->theory->testresults['complete'])));
        $this->pdf->Ln(6);
        $this->infoLine('Status:', strip_tags($this->theory->testStatus()));
        $this->infoLine('Questions:', $this->theory->testresults['numquestions']);
        $this->pdf->Ln(6);
        if($this->testType != 'free'){$this->infoLine('Candidate:', $this->certUsername);}
        $this->infoLine('Time Taken:', $this->theory->getTime());
        $this->pdf->Ln(16);
        $this->pdf->SetFont('Arial','B', 8);

        $this->overallResults();
    }
    
    protected function overallResults(){
        $header = ['Group', 'Topics in group', 'Correct', 'Incorrect', 'Total', 'Percentage', 'Status'];
        foreach($this->theory->getCategories() as $group => $data){
            $correct = (int)$this->theory->testresults['dvsa'][$data['section']]['correct'];
            $incorrect = (int)$this->theory->testresults['dvsa'][$data['section']]['incorrect'];
            $total = $correct + $incorrect;

            if($correct >= $this->theory->passmarkPerCat){$groupstatus = 'Passed';}
            else{$groupstatus = 'Failed';}
            $groupdata[] = [$data['section'], substr($data['name'], 0, 53), $correct, $incorrect, $total, number_format((($correct / $total) * 100), 0).'%', $groupstatus];
            
            $totalcorrect = $totalcorrect + $correct;
            $totalincorrect = $totalincorrect + $incorrect;
            $totalq = $totalq + $total;
        }
        $widths = [14,78,19,19,19,20,21];
        $this->pdf->basicTable($header, $groupdata, $widths, 6, 2);
        $first = true;
        $grouppercent = round(($totalcorrect / $totalq) * 100);
        
        if($this->theory->testresults['status'] == 'pass'){$status = 'Passed';}
        else{$status = 'Failed';}
        
        $overall = ['', 'Overall Status', $totalcorrect, $totalincorrect, $totalq, $grouppercent.'%', $status];
        $this->pdf->SetFont('Arial','B', 9);
        foreach($widths as $col){
            if($first == true){$first = false; $currentvalue = current($overall);}else{$currentvalue = next($overall);}
            $this->pdf->Cell($col, 6, $currentvalue, 1, 0, 'C');
        }
        
        $this->pdf->AddPage('P', 'A4');
        $this->pdf->SetFont('Arial','B', 9);
        $testheader = ['Question', 'Learning Section', 'Question No.', 'Status'];
        foreach($this->theory->questions as $question => $prim){
            if($this->theory->useranswers[$question]['status'] == '4'){$correct = 'Correct';}else{$correct = 'Incorrect';}
            $questioninfo = $this->theory->questionInfo($prim);
            $testdata[] = [$question, $questioninfo['cat'], $questioninfo['topic'], $correct];
        }
        $this->pdf->basicTable($testheader, $testdata, [22,98,30,40], 5, 2);
    }
}
