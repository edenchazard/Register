<?php
require_once "register.class.php";
require_once "user.class.php";
require_once "FPDF/fpdf.php";

class Report {
	private $pdf 	= null;
	private $db		= null;

	public function __construct(){
		$this->pdf = new FPDF();
		$this->db = App::$db;
	}
	
	public function generate_report($options){
		// Page
		$this->pdf->SetFont('Arial', '', 12);
		$this->pdf->AddPage();
		$this->pdf->SetTitle($options['title']);

		// content
		switch($options['type']){
			// todo test for user id
			case "USER ACTIVITY":
				$this->report_type_user_activity($options['userid'], $options['account']);
				break;
			case "LIST":
				$this->report_type_list($options['account'], $options['siteid']);
				break;
		}

		$this->pdf->Ln();
		$this->pdf->Output();
	}
	
	// Creates a table in the PDF document
	private function produce_table($header, $data, $size){
		$z = count($header);

		// Header
		for($i=0; $i < $z; $i++){
			$this->pdf->Cell($size[$i], 7, $header[$i], 1, 0, 'C');
		}
		$this->pdf->Ln();

		// Data
		foreach($data as $row){
			for($i=0; $i < $z; $i++){
				$this->pdf->Cell($size[$i], 6, $row[$i], 1);
			}
			$this->pdf->Ln();
		}

		// Closing line
		$this->pdf->Cell(array_sum($size), 0, '', 'T');
	}
	
	private function write_summary($text){
		$dt = date("Y-m-d H:i:s");
		$this->pdf->Write(12, "{$text} (Generated: {$dt})");
		$this->pdf->Ln();
	}

	private function report_type_user_activity($userid, $accountid){
		$user = new User($userid);
		
		// prevent users outside of the account holder
		// from making a report on someone else
		if(!$user->belongs_to_account($accountid)){
			throw new Exception("");
		}

		// Summary
		$this->write_summary("REPORT FOR: {$user->name}");

		// Column headings
		$header = array('In', "Out", "Site", "Notes");

		// Data
		$data = $user->get_activity();

		// Make the table
		$this->produce_table($header, $data, array(60, 60, 30, 40));
	}
	
	private function report_type_list($account, $siteid){
		$register = new RegisterAccount($account);

		// Summary
		if($siteid > 0){
			$site = $register->get_site($siteid);
			$summary_text = "REPORT FOR: {$register->domain}::{$site['name']}";
		}
		else{
			$summary_text = "REPORT FOR: {$register->domain}::(All)";
		}

		// Summary
		$this->write_summary($summary_text);

		// Column headings
		$header = array('Name', 'Date/Time');

		// Data 
		$data = $register->get_whos_signed_in($siteid);

		// Make the table
		$this->produce_table($header, $data, array(80, 60));
	}
}
?>