<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Initial Developer of the Original Code is
 * Primary Care Doctors Organisation Malaysia.
 * Portions created by the Initial Developer are Copyright (C) 2009
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

class Mh extends CI_Controller {
    function __construct()
    {
        parent::__construct();
        
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->scaffolding('patient_demographic_info');
	  	$this->load->model('mpatients');
        header('Content-type: application/xhtml+xml'); 
        $this->pretend_phone	=	FALSE;
        //$this->pretend_phone	=	TRUE;  // Turn this On to overwrites actual device
        $data['debug_mode']		=	TRUE;
        // Redirect back to login page if not authenticated
		if ($_SESSION['user_acl'] < 1){
            $new_page   =   base_url()."index.php/thirra";
            header("Status: 200");
            header("Location: ".$new_page);
        } // redirect to login page
    }


	function index() // Default page
    {	
		$this->load->model('mpatients');

        //---------------- Check user agent
        $this->load->library('user_agent');

        if ($this->agent->is_browser()){
            $data['agent'] = $this->agent->browser().' '.$this->agent->version();
        } elseif ($this->agent->is_robot()) {
            $data['agent'] = $this->agent->robot();
        } elseif ($this->agent->is_mobile()) {
            $data['agent'] = $this->agent->mobile();
        } else {
            $data['agent'] = 'Unidentified User Agent';
        }

	    if ("Unknown Platform" == $this->agent->platform()){
	        $device_mode = "wap";
		    $data['device_mode'] = "WAP";
	    } else {
			if ($this->pretend_phone == TRUE) {
			    $device_mode = "wap";
			    $data['device_mode'] = "WAP"; 
			} else {
		        $device_mode = "html";
			    $data['device_mode'] = "HTML";
			}
	    }			

        //----------------
		
		$data['title'] = "T H I R R A - Main Page";
		$data['main'] = 'home';
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "mh_mobile"){
			$this->load->view('mh/mh_index_wap');			
		} else {
			$this->load->view('mh/mh_index_html');			
		}
		//$this->load->view('template');
	}


	function patients_list() // Default page
    {	
		$this->load->model('mpatients');
		
		$data['title'] = "T H I R R A - Patients List";
		$data['patic']   = "12345";
		$data['patlist'] = $this->mpatients->getAllPatients();
		//	$data['mainf'] = $this->MPatients->getAllPatients();
		$data['main'] = 'home';
		$data['query'] = $this->db->get('patient_demographic_info'); 
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "mh_mobile"){
			$this->load->view('mh/mh_patients_list_wap');			
		} else {
			$this->load->view('mh/mh_patients_list_html');			
		}
		//$this->load->view('template');
	}


	function individual_overview()
    {
	  	$this->load->model('mpatients');
	  	
		$data['title'] = 'Individual Overview Sheet';
		$data['main'] = 'individual_overview';
	//	$data['query'] = $this->db->get('patient_demographic_info'); 
		$data['patient_id'] = $this->uri->segment(3);
		$data['patient_info'] = $this->mpatients->getPatientInfo($data['patient_id']);
		$data['patient_past_con'] = $this->mpatients->getPatientPastCon($data['patient_id']);
		$this->load->vars($data);
		/*
		echo '<pre>';
			print_r($data);
		echo '</pre>';
		*/
		$this->load->view('individual_overview');
	} 
	
	function past_con()
    {
	  	$this->load->model('mpatients');
	  	
		$data['title'] = "Past Consultations";
		$data['summary_id'] = $this->uri->segment(3);
		$data['patient_id'] = $this->uri->segment(4);
		$data['patient_info'] = $this->mpatients->getPatientInfo($data['patient_id']);
		$data['consultation_summary'] = $this->mpatients->getPastConDetails($data['summary_id']);
		$this->load->vars($data);
		/*
		echo '<pre>';
			print_r($data);
		echo '</pre>';
		*/
		$this->load->view('past_con');
	}


    function consult_episode($id)
    {
	  	$this->load->model('mpatients');
	  	
		$data['title'] = "Past Consultations";
		$data['summary_id'] = now();
		$data['patient_id'] = $this->uri->segment(3);
        $data['date_started'] = "2009-08-11";
        $data['date_ended'] = (isset($data['date_ended']) ? $data['date_ended'] : '');
		$data['patient_info'] = $this->mpatients->getPatientInfo($data['patient_id']);
//		$data['consultation_summary'] = $this->mpatients->getPastConDetails($data['summary_id']);
		$this->load->vars($data);
/*
		echo '<pre>';
			print_r($data);
		echo '</pre>';
*/		
		$this->load->view('consult_episode');
    }


    function save_episode()
    {
        // redirect("thirra/index");
        $data = array();
        $data['patient_id'] = $_POST['patient_id'];
        $data['summary_id'] = $_POST['summary_id'];
        $data['date_started'] = $_POST['date_started'];
        $data['date_ended'] = $_POST['date_ended'];
        $form = (isset($_POST['part_form']) ? $_POST['part_form'] : $_POST['full_form']);
        /*
        echo $form." - ";
        echo $data['patient_id']." - ";
        echo $data['summary_id']." - ";
        echo $data['date_started']." - ";
        echo $data['date_ended']." - ";
        */

        $this->db->insert('patconsum',$data);

		$data['patient_info'] = $this->mpatients->getPatientInfo($data['patient_id']);
		$data['patient_past_con'] = $this->mpatients->getPatientPastCon($data['patient_id']);
		$this->load->vars($data);
		$this->load->view('individual_overview');

    }


    function new_investigate()
    {
	  	$this->load->model('mpatients');

	  	//echo "new_investigate";
        $data = array();
        $data['patient_id'] = 'patient_id';
        $data['summary_id'] = 'summary_id';
        $data['date_started'] = 'date_started';
        $data['date_ended'] = 'date_ended';
        /*        
        $data['patient_id'] = $_POST['patient_id'];
        $data['summary_id'] = $_POST['summary_id'];
        $data['date_started'] = $_POST['date_started'];
        $data['date_ended'] = $_POST['date_ended'];
        */
        $data['title']   =   "New Investigation";
		$this->load->vars($data);
		$this->load->view('new_investigate_html');
    }


    function new_method($id)
    {
	  	$this->load->model('modelfile');
	  	echo "new_method";
		//$this->load->view('new_view');
    }


}
