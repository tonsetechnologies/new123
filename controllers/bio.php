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

/**
 * Controller Class for Biosurveillance
 *
 * This class is used for both narrowband and broadband Biosurveillance. 
 *
 * @version 0.5
 * @package THIRRA - Biosurveillance
 * @author  Jason Tan Boon Teck
 */
class Bio extends MY_Controller 
{
    protected $_patient_id      =  "";
    protected $_debug_mode      =  FALSE;
    //protected $_debug_mode      =  TRUE;


    function __construct()
    {
        parent::__construct();
        
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->scaffolding('patient_demographic_info');
	  	$this->load->model('mpatients');
        //$this->lang->load('emr', 'nepali');
        //$this->lang->load('emr', 'ceylonese');
        //$this->lang->load('emr', 'malay');
        $this->lang->load('bio', 'english');
        $this->pretend_phone	=	FALSE;
        //$this->pretend_phone	=	TRUE;  // Turn this On to overwrites actual device
        $data['debug_mode']		=	TRUE;
        if($data['debug_mode'] == TRUE) {
            // spaghetti html
        } else {
            header('Content-type: application/xhtml+xml'); 
        }
        // Redirect back to login page if not authenticated
		if ($_SESSION['user_acl'] < 1){
            $new_page   =   base_url()."index.php/thirra";
            header("Status: 200");
            header("Location: ".$new_page);
        } // redirect to login page
    }

    // ------------------------------------------------------------------------
	function index() // Default page
    {	
        $data['debug_mode']		    =	$this->_debug_mode;
		$this->load->model('mpatients');
		$this->load->model('mbio');

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
        } //endif ($this->agent->is_browser())

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
	    } //endif ("Unknown Platform" == $this->agent->platform())

		$data['title'] = "T H I R R A - Main Page";
		$data['patlist']    = $this->mpatients->get_all_patients();
		$data['caselist']   = $this->mbio->get_all_cases();
		$data['fresh_list'] = $this->mbio->get_disease_notified_list("fresh");
		$data['open_list']  = $this->mbio->get_disease_notified_list("open");
		$data['closed_list'] = $this->mbio->get_disease_notified_list("closed");
		$data['main'] = 'home';
		$data['query'] = $this->db->get('bio_case'); 
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_body   =   "bio/bio_index_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_body   =   "bio/bio_index_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_body   =   "bio/bio_index_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_body   =   "bio/bio_index_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        } //end switch ($_SESSION['thirra_mode'])
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		//$this->load->view('template');
	} //end function index()


    // ------------------------------------------------------------------------
	function cases_mgt() // Cases Management
    {	
        $data['debug_mode']		    =	$this->_debug_mode;
		$this->load->model('mpatients');
		$this->load->model('mbio');

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
        } //endif ($this->agent->is_browser())

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
	    } //endif ("Unknown Platform" == $this->agent->platform())

		$data['title'] = "T H I R R A - Main Page";
		$data['patlist']    = $this->mpatients->get_all_patients();
		$data['caselist']   = $this->mbio->get_all_cases();
		$data['fresh_list'] = $this->mbio->get_disease_notified_list("fresh");
		$data['open_list']  = $this->mbio->get_disease_notified_list("open");
		$data['closed_list'] = $this->mbio->get_disease_notified_list("closed");
		$data['main'] = 'home';
		$data['query'] = $this->db->get('bio_case'); 
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_body   =   "bio/bio_index_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_sidebar=   "bio/sidebar_bio_cases_html";
                $new_body   =   "bio/bio_index_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "bio/sidebar_bio_cases_hosp";
                $new_body   =   "bio/bio_cases_mgt_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_sidebar=   "bio/sidebar_bio_cases_dept";
                $new_body   =   "bio/bio_index_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        } //end switch ($_SESSION['thirra_mode'])
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		//$this->load->view('template');
	} //end function cases_mgt()


    // ------------------------------------------------------------------------
    function edit_case($bio_case_id = NULL)
    {
        $data['debug_mode']		    =	$this->_debug_mode;
        $this->load->library('user_agent');
	  	$this->load->model('mbio');
		$this->load->library('form_validation');
        if ($this->agent->is_browser()){
            $data['agent'] = $this->agent->browser().' '.$this->agent->version();
        } elseif ($this->agent->is_robot()) {
            $data['agent'] = $this->agent->robot();
        } elseif ($this->agent->is_mobile()) {
            $data['agent'] = $this->agent->mobile();
        } else {
            $data['agent'] = 'Unidentified User Agent';
        }

		$data['title'] = 'Case';
		$data['main'] = 'bio_case';
		$data['form_purpose']   = $this->uri->segment(3);
		$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
        $data['bio_inv_id']     = NULL;
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        if(count($_POST)) {
            // User has posted the form
            $data['init_case_ref']          =   $_POST['case_ref'];
            $data['init_case_findings']     =   $_POST['case_findings'];
            $data['init_case_summary']      =   $_POST['case_summary'];
            $data['init_case_start_date']   =   $_POST['case_start_date'];
            $data['init_case_end_date']     =   $_POST['case_end_date'];
            $data['init_alert_now']         =   $_POST['alert_now'];
            $data['init_gps_lat']           =   $_POST['gps_lat'];
            $data['init_gps_long']          =   $_POST['gps_long'];
            $data['init_case_comments']     =   $_POST['case_comments'];
            $data['now_id']                 =   $_POST['now_id'];
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $patient_id                     =   $this->uri->segment(4);
	        $data['notification_id']        =   $this->uri->segment(5);
            $data['bio_case_id']            =   $this->uri->segment(6);
            $data['init_patient_id']        =   $patient_id;
      		$data['save_attempt']           = 'EDIT NEW NOTIFY';
            $data['patient_id']             =   $data['init_patient_id'];
	        $data['patient_info']           =   $this->mbio->get_patient_details($data['patient_id']);
            $data['init_clinic_reference_number']=   $data['patient_info']['clinic_reference_number'];
	        $data['notify_info']            =   $this->mbio->get_disease_notify_details($data['patient_id'],$data['notification_id']);
            $data['init_notification_id']   =   $data['notification_id'];
            $data['init_summary_id']        =   $data['notify_info']['summary_id'];
            $data['init_location_id']       =   $data['notify_info']['location_id'];
            
            if($data['form_purpose'] == 'new_case') {
                // New form
		        $data['bio_case_id']        = "new_case";
          		$data['save_attempt']       = 'NEW CASE';
		        $data['bio_case_details']   = array();
		        $data['list_of_reported_visits'] = $this->mbio->get_all_cases();
            } elseif($data['form_purpose'] == 'edit_case') {
                // Edit form
          		$data['save_attempt'] = 'EDIT CASE';
                // These fields were passed through as hidden tags
                $data['init_bio_case_id']   =   $_POST['bio_case_id'];
                $data['bio_case_id']        =   $data['init_bio_case_id'];
		        $data['bio_case_details']   =   $this->mbio->get_case_details_only($data['bio_case_id']);
                $data['init_location_id']   =   $data['bio_case_details']['location_id'];
                $data['init_district_id']   =   $data['bio_case_details']['district_id'];
                $data['init_alert_max']     =   $data['bio_case_details']['alert_max'];
                $data['init_staff_start_id']=   $data['bio_case_details']['staff_start_id'];
                $data['init_start_date']    =   $data['bio_case_details']['start_date'];
            } //endif($data['form_purpose'] == 'new_case')

        } else {
            // First time form is displayed
            $data['now_id']                 =   time();
            $patient_id                     =   $this->uri->segment(4);
            $data['patient_id']             =   $patient_id;
            $data['init_patient_id']        =   $data['patient_id'];
            $data['patient_info'] = $this->mbio->get_patient_details($data['patient_id']);
	        $data['notification_id']        =   $this->uri->segment(5);
	        $data['notify_info']            =   $this->mbio->get_disease_notify_details($data['patient_id'],$data['notification_id']);
            $data['init_notification_id']   =   $data['notification_id'];
            $data['init_summary_id']        =   $data['notify_info']['summary_id'];
            if($data['form_purpose'] == 'new_case') {
            //if (is_null($bio_case_id)){
                // New form
		        $data['list_of_reported_visits']= $this->mbio->get_all_cases();
		        $data['bio_case_id']            = "new_case";
          		$data['save_attempt']           = 'NEW CASE';
		        $data['bio_case_details']       = array();
                $data['init_bio_case_id']       =   NULL;
                $data['init_case_ref']          =   NULL;
                $data['init_location_id']       =   NULL;
                $data['init_district_id']       =   NULL;
                $data['init_gps_lat']           =   NULL;
                $data['init_gps_long']          =   NULL;
                $data['init_case_findings']     =   NULL;
                $data['init_case_summary']      =   NULL;
                $data['init_alert_max']         =   NULL;
                $data['init_alert_now']         =   NULL;
                $data['init_case_start_date']   =   date("Y-m-d",$data['now_id']);
                $data['init_case_end_date']     =   NULL;
                $data['init_case_comments']     =   NULL;
                $data['init_case_remarks']      =   NULL;
                //$data['init_summary_id']    =   $data['now_id'];
            } elseif($data['form_purpose'] == 'edit_case') {
		        $data['bio_case_id']            = $this->uri->segment(6);
		        //$data['patient_info'] = $this->mbio->getPatientInfo($data['patient_id']);
		        $data['bio_case_details']       = $this->mbio->get_case_details_only($data['bio_case_id']);
          		$data['save_attempt']           = 'EDIT CASE';
                $data['init_bio_case_id']       =   $data['bio_case_details']['bio_case_id'];
                $data['init_case_ref']          =   $data['bio_case_details']['case_ref'];
                $data['init_location_id']       =   $data['bio_case_details']['location_id'];
                //$data['init_district_id']       =   $data['bio_case_details']['district_id'];
                $data['init_gps_lat']           =   $data['bio_case_details']['gps_lat'];
                $data['init_gps_long']          =   $data['bio_case_details']['gps_long'];
                $data['init_case_findings']     =   $data['bio_case_details']['case_findings'];
                $data['init_case_summary']      =   $data['bio_case_details']['case_summary'];
                $data['init_alert_max']         =   $data['bio_case_details']['alert_max'];
                $data['init_alert_now']         =   $data['bio_case_details']['alert_now'];
                $data['init_case_start_date']   =   $data['bio_case_details']['start_date'];
                $data['init_case_end_date']     =   $data['bio_case_details']['end_date'];
                $data['init_staff_start_id']    =   $data['bio_case_details']['staff_start_id'];
                $data['init_staff_close_id']    =   $data['bio_case_details']['staff_close_id'];
                $data['init_staff_close_date']  =   $data['bio_case_details']['staff_close_date'];
                $data['init_case_comments']     =   $data['bio_case_details']['case_comments'];
                $data['init_case_remarks']      =   $data['bio_case_details']['case_remarks'];
            } //endif($data['form_purpose'] == 'new_case')
        } //endif(count($_POST))

        $data['bio_inv_list'] = $this->mbio->get_investigate_details_per_biocase($data['bio_case_id']);
        
        $debug_mode		=	FALSE;
        //$debug_mode		=	TRUE;
        if($debug_mode){
            echo "<pre>";
            //print_r($data);
            echo "</pre>";
        }
        
		if ($this->form_validation->run('edit_case') == FALSE){
			//$this->load->view('myform');
    		$this->load->vars($data);
		    if ($_SESSION['thirra_mode'] == "bio_mobile"){
			    $this->load->view('bio/header_xhtml-mobile10');			
			    $this->load->view('bio/banner_bio_wap');			
			    $this->load->view('bio/bio_edit_case_wap');			
			    $this->load->view('bio/footer_bio_wap');			
		    } else {
		        //$this->load->view('bio/bio/header_xhtml1-strict');
		        $this->load->view('bio/header_xhtml1-transitional');
		        $this->load->view('bio/banner_bio_hosp');
			    $this->load->view('bio/bio_edit_case_html');			
        		$this->load->view('bio/footer_bio_hosp');
		    } //endif ($_SESSION['thirra_mode'] == "bio_mobile")

		} else {
			//$this->load->view('formsuccess');
            if($data['debug_mode']) {
                echo "\nValidated successfully.";
                echo "<pre>";
                //print_r($data);
                echo "</pre>";
            }
            if($data['save_attempt'] == "NEW CASE"){
        		$data['save_attempt'] = 'Inserted successfully';
                if($data['debug_mode']) {
                    echo "<br />Insert record";
                }
                // New patient record
    /*
      district_id character(10),
      end_date date,
      staff_end_id character(10),
      staff_close character(10),
      case_remarks character varying(255),
    */
                $ins_case_array   =   array();
                $ins_case_array['bio_case_id']           =   $data['now_id'];
                $ins_case_array['notification_id']       =   $data['init_notification_id'];
                $ins_case_array['summary_id']            =   $data['init_summary_id'];
                $ins_case_array['patient_id']            =   $data['patient_id'];
                $ins_case_array['case_ref']              =   $data['init_case_ref'];
                $ins_case_array['location_id']           =   $data['init_location_id'];
                $ins_case_array['case_findings']         =   $data['init_case_findings'];
                $ins_case_array['case_summary']          =   $data['init_case_summary'];
                $ins_case_array['gps_lat']               =   $data['init_gps_lat'];
                $ins_case_array['gps_long']              =   $data['init_gps_long'];
                if(is_int($data['init_alert_now'])) {
                    $ins_case_array['alert_now']             =   $data['init_alert_now'];
                    $ins_case_array['alert_max']             =   $data['init_alert_now'];
                }
                $ins_case_array['start_date']            =   $data['init_case_start_date'];
                if($data['init_case_end_date']){
                    $ins_case_array['end_date']              =   $data['init_case_end_date'];
                }
                $ins_case_array['staff_start_id']        =   $_SESSION['staff_id'];
                $ins_case_array['case_comments']         =   $data['init_case_comments'];
	            $ins_case_data       =   $this->mbio->insert_bio_case($ins_case_array);
                $data['form_purpose']                   = 'edit_case';  // convert purpose after insertion
            } else {
        		$data['save_attempt'] = 'Updated successfully';
                $upd_case_array   =   array();
        /*
      district_id character(10),
      alert_max integer,
      start_date date,
      end_date date,
      staff_end_id character(10),
      staff_close character(10),
      case_remarks character varying(255),
        */
                $upd_case_array['bio_case_id']          =   $data['init_bio_case_id'];
                $upd_case_array['case_ref']             =   $data['init_case_ref'];
                $upd_case_array['case_findings']        =   $data['init_case_findings'];
                $upd_case_array['case_summary']         =   $data['init_case_summary'];
                $upd_case_array['gps_lat']              =   $data['init_gps_lat'];
                $upd_case_array['gps_long']             =   $data['init_gps_long'];
                if(is_int($data['init_alert_now'])) {
                    $upd_case_array['alert_now']            =   $data['init_alert_now'];
                }
                $upd_case_array['case_comments']        =   $data['init_case_comments'];
                if($data['init_case_end_date']){
                    $upd_case_array['end_date']             =   $data['init_case_end_date'];
                }
    		    $update_data        = $this->mbio->update_case_details($upd_case_array);
            }
    		$this->load->vars($data);
		    if ($_SESSION['thirra_mode'] == "bio_mobile"){
			    $this->load->view('bio/header_xhtml-mobile10');			
			    $this->load->view('bio/banner_bio_wap');			
			    $this->load->view('bio/bio_edit_case_wap');			
			    $this->load->view('bio/footer_bio_wap');			
		    } else {
		        //$this->load->view('bio/bio/header_xhtml1-strict');
		        $this->load->view('bio/header_xhtml1-transitional');
		        $this->load->view('bio/banner_bio_hosp');
			    $this->load->view('bio/bio_edit_case_html');			
        		$this->load->view('bio/footer_bio_hosp');
		    } //endif ($_SESSION['thirra_mode'] == "bio_mobile")

		} //endif ($this->form_validation->run() == FALSE)

    } // end of function edit_case($id)


    // ------------------------------------------------------------------------
    function edit_inv($bio_inv_id = NULL)
    {
        $data['debug_mode']		    =	$this->_debug_mode;
        $this->load->library('user_agent');
	  	$this->load->model('mbio');
		$this->load->library('form_validation');
        if ($this->agent->is_browser()){
            $data['agent'] = $this->agent->browser().' '.$this->agent->version();
        } elseif ($this->agent->is_robot()) {
            $data['agent'] = $this->agent->robot();
        } elseif ($this->agent->is_mobile()) {
            $data['agent'] = $this->agent->mobile();
        } else {
            $data['agent'] = 'Unidentified User Agent';
        }

		$data['title'] = 'Investigation';
		$data['main'] = 'bio_case';
		$data['form_purpose']   = $this->uri->segment(3);
		$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
        $data['bio_inv_id']     = NULL;
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        if(count($_POST)) {
            // User has posted the form
            $data['init_inv_ref']           =   $_POST['inv_ref'];
            $data['init_inv_main_name']     =   $_POST['inv_main_name'];
            $data['init_inv_main_relship']  =   $_POST['inv_main_relship'];
            $data['init_inv_main_answer']   =   $_POST['inv_main_answer'];
            $data['init_inv_main_remarks']  =   $_POST['inv_main_remarks'];
            $data['init_inv_cluster_size']  =   $_POST['inv_cluster_size'];
            $data['init_inv_findings']      =   $_POST['inv_findings'];
            $data['init_inv_summary']       =   $_POST['inv_summary'];
            $data['init_inv_comments']      =   $_POST['inv_comments'];
            $data['init_inv_address1']      =   $_POST['inv_address1'];
            $data['init_inv_address2']      =   $_POST['inv_address2'];
            $data['init_inv_address3']      =   $_POST['inv_address3'];
            $data['init_inv_postcode']      =   $_POST['inv_postcode'];
            $data['init_inv_town']          =   $_POST['inv_town'];
            $data['init_inv_state']         =   $_POST['inv_state'];
            $data['init_inv_tel']           =   $_POST['inv_tel'];
            $data['init_inv_fax']           =   $_POST['inv_fax'];
            $data['init_inv_email']         =   $_POST['inv_email'];
            $data['init_inv_gps_lat']       =   $_POST['inv_gps_lat'];
            $data['init_inv_gps_long']      =   $_POST['inv_gps_long'];
            $data['init_inv_start_date']    =   $_POST['inv_start_date'];
            $data['init_inv_end_date']      =   $_POST['inv_end_date'];
            $data['now_id']                 =   $_POST['now_id'];
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $patient_id                     =   $this->uri->segment(4);
	        $data['notification_id']        =   $this->uri->segment(5);
            $data['bio_case_id']            =   $this->uri->segment(6);
            $data['init_patient_id']        =   $patient_id;
      		$data['save_attempt']           =   'EDIT NEW NOTIFY';
            $data['patient_id']             =   $data['init_patient_id'];
	        $data['patient_info']           =   $this->mbio->get_patient_details($data['patient_id']);
	        $data['notify_info']            =   $this->mbio->get_disease_notify_details($data['patient_id'],$data['notification_id']);
            $data['init_notification_id']   =   $data['notification_id'];
	        $data['bio_case_id']            =   $this->uri->segment(6);
		    $data['bio_case_details']       =   $this->mbio->get_case_details_only($data['bio_case_id']);
            $data['init_bio_case_id']       =   $data['bio_case_details']['bio_case_id'];
            $data['init_case_ref']          =   $data['bio_case_details']['case_ref'];
            $data['init_location_id']       =   $data['bio_case_details']['location_id'];
            //$data['init_district_id']       =   $data['bio_case_details']['district_id'];
            $data['init_gps_lat']           =   $data['bio_case_details']['gps_lat'];
            $data['init_gps_long']          =   $data['bio_case_details']['gps_long'];
            $data['init_case_findings']     =   $data['bio_case_details']['case_findings'];
            $data['init_case_summary']      =   $data['bio_case_details']['case_summary'];
            $data['init_alert_max']         =   $data['bio_case_details']['alert_max'];
            $data['init_alert_now']         =   $data['bio_case_details']['alert_now'];
            $data['init_case_start_date']   =   $data['bio_case_details']['start_date'];
            $data['init_case_end_date']     =   $data['bio_case_details']['end_date'];
            $data['init_staff_start_id']    =   $data['bio_case_details']['staff_start_id'];
            $data['init_staff_close_id']    =   $data['bio_case_details']['staff_close_id'];
            $data['init_staff_close_date']  =   $data['bio_case_details']['staff_close_date'];
            $data['init_case_comments']     =   $data['bio_case_details']['case_comments'];
            $data['init_case_remarks']      =   $data['bio_case_details']['case_remarks'];
            if(isset($_POST['bio_inv_id'])){
                $data['init_bio_inv_id']   =   $_POST['bio_inv_id'];
            }
            $data['bio_pics_list'] = $this->mbio->get_pics_list($data['bio_inv_id']);
            $data['pics_url']      =    base_url();
            $data['pics_url']      =    substr_replace($data['pics_url'],'',-1);
            $data['pics_url']      =    $data['pics_url']."uploads";
            //$data['pics_url']      =    substr_replace($data['pics_url'],'',-1);
            //$data['pics_url']      =    $data['pics_url']."-uploads/";
            
            if($data['form_purpose'] == 'new_inv') {
                // New form
		        $data['bio_inv_id']        = "new_inv";
          		$data['save_attempt']       = 'NEW INVESTIGATION';
		        $data['bio_inv_details']   = array();
		        //$data['list_of_reported_visits'] = $this->mbio->get_all_cases();
            } elseif($data['form_purpose'] == 'edit_inv') {
                // Edit form
          		$data['save_attempt'] = 'EDIT INVESTIGATION';
                // These fields were passed through as hidden tags
                $data['init_bio_inv_id']   =   $_POST['bio_inv_id'];
                $data['bio_inv_id']        =   $data['init_bio_inv_id'];
            } //endif($data['form_purpose'] == 'new_case')

        } else {
            // First time form is displayed
            $data['now_id']             =   time();
            $patient_id                 =   $this->uri->segment(4);
            $data['patient_id']         =   $patient_id;
            $data['init_patient_id']    =   $data['patient_id'];
            $data['patient_info'] = $this->mbio->get_patient_details($data['patient_id']);
	        $data['notification_id']    =   $this->uri->segment(5);
	        $data['notify_info']        =   $this->mbio->get_disease_notify_details($data['patient_id'],$data['notification_id']);
	        $data['bio_case_id']        =   $this->uri->segment(6);
		    $data['bio_case_details']   = $this->mbio->get_case_details_only($data['bio_case_id']);
            if($data['form_purpose'] == 'new_inv') {
                // New form
		        $data['list_of_reported_visits']= $this->mbio->get_all_cases();
		        $data['bio_inv_id']            = "new_inv";
          		$data['save_attempt']          = 'NEW INVESTIGATION';
		        $data['bio_inv_details']       = array();
                $data['init_bio_inv_id']       =   NULL;
                $data['init_inv_ref']          =   NULL;
                $data['init_inv_main_name']    =   NULL;
                $data['init_inv_main_relship'] =   NULL;
                $data['init_inv_main_answer']  =   NULL;
                $data['init_inv_main_remarks'] =   NULL;
                $data['init_inv_other_name']   =   NULL;
                $data['init_inv_other_relship']=   NULL;
                $data['init_inv_other_answer'] =   NULL;
                $data['init_inv_other_remarks']=   NULL;
                $data['init_inv_cluster_size'] =   NULL;
                $data['init_inv_findings']     =   NULL;
                $data['init_inv_summary']      =   NULL;
                $data['init_inv_comments']     =   NULL;
                $data['init_inv_address1']     =   NULL;
                $data['init_inv_address2']     =   NULL;
                $data['init_inv_address3']     =   NULL;
                $data['init_inv_postcode']     =   NULL;
                $data['init_inv_town']         =   NULL;
                $data['init_inv_state']        =   NULL;
                $data['init_inv_tel']          =   NULL;
                $data['init_inv_fax']          =   NULL;
                $data['init_inv_email']        =   NULL;
                $data['init_inv_gps_lat']      =   NULL;
                $data['init_inv_gps_long']     =   NULL;
                $data['init_inv_start_date']   =   date("Y-m-d",$data['now_id']);
                $data['init_inv_end_date']     =   NULL;
                $data['init_case_comments']    =   NULL;
                $data['init_case_remarks']     =   NULL;
                //$data['init_summary_id']    =   $data['now_id'];
            } elseif($data['form_purpose'] == 'edit_inv') {
		        $data['bio_inv_id']            = $this->uri->segment(7);
		        $data['bio_inv_details']       = $this->mbio->get_investigate_details_only($data['bio_inv_id']);
          		$data['save_attempt']          = 'EDIT INVESTIGATION';
                $data['init_inv_ref']          =   $data['bio_inv_details']['inv_ref'];
                $data['init_inv_main_name']    =   $data['bio_inv_details']['inv_main_name'];
                $data['init_inv_main_relship'] =   $data['bio_inv_details']['inv_main_relship'];
                $data['init_inv_main_answer']  =   $data['bio_inv_details']['inv_main_answer'];
                $data['init_inv_main_remarks'] =   $data['bio_inv_details']['inv_main_remarks'];
                $data['init_inv_other_name']   =   $data['bio_inv_details']['inv_other_name'];
                $data['init_inv_other_relship']=   $data['bio_inv_details']['inv_other_relship'];
                $data['init_inv_other_answer'] =   $data['bio_inv_details']['inv_other_answer'];
                $data['init_inv_other_remarks']=   $data['bio_inv_details']['inv_other_remarks'];
                $data['init_inv_cluster_size'] =   $data['bio_inv_details']['inv_cluster_size'];
                $data['init_inv_findings']     =   $data['bio_inv_details']['inv_findings'];
                $data['init_inv_summary']      =   $data['bio_inv_details']['inv_summary'];
                $data['init_inv_comments']     =   $data['bio_inv_details']['inv_comments'];
                $data['init_inv_address1']     =   $data['bio_inv_details']['inv_address1'];
                $data['init_inv_address2']     =   $data['bio_inv_details']['inv_address2'];
                $data['init_inv_address3']     =   $data['bio_inv_details']['inv_address3'];
                $data['init_inv_postcode']     =   $data['bio_inv_details']['inv_postcode'];
                $data['init_inv_town']         =   $data['bio_inv_details']['inv_town'];
                $data['init_inv_state']        =   $data['bio_inv_details']['inv_state'];
                $data['init_inv_tel']          =   $data['bio_inv_details']['inv_tel'];
                $data['init_inv_fax']          =   $data['bio_inv_details']['inv_fax'];
                $data['init_inv_email']        =   $data['bio_inv_details']['inv_email'];
                $data['init_inv_gps_lat']      =   $data['bio_inv_details']['inv_gps_lat'];
                $data['init_inv_gps_long']     =   $data['bio_inv_details']['inv_gps_long'];
                $data['init_inv_start_date']   =   $data['bio_inv_details']['inv_start_date'];
                $data['init_inv_end_date']     =   $data['bio_inv_details']['inv_end_date'];
                $data['init_inv_remarks']      =   $data['bio_inv_details']['inv_remarks'];
	            $data['bio_pics_list'] = $this->mbio->get_pics_list($data['bio_inv_id']);
                $data['pics_url']      =    base_url();
                $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
                $data['pics_url']      =    $data['pics_url']."uploads/case_pics/";
                //$data['pics_url']      =    substr_replace($data['pics_url'],'',-1);
                //$data['pics_url']      =    $data['pics_url']."-uploads/";
            } //endif($data['form_purpose'] == 'new_inv')
        } //endif(count($_POST))

        $debug_mode		=	FALSE;
        //$debug_mode		=	TRUE;
        if($data['debug_mode']){
            echo "<pre>";
            //print_r($data);
            echo "</pre>";
        }
        
		if ($this->form_validation->run('edit_inv') == FALSE){
			//$this->load->view('myform');
    		$this->load->vars($data);
		    if ($_SESSION['thirra_mode'] == "bio_mobile"){
			    $this->load->view('bio/header_xhtml-mobile10');			
			    $this->load->view('bio/banner_bio_wap');			
			    $this->load->view('bio/bio_edit_inv_wap');			
			    $this->load->view('bio/footer_bio_wap');			
		    } else {
		        //$this->load->view('bio/bio/header_xhtml1-strict');
		        $this->load->view('bio/header_xhtml1-transitional');
		        $this->load->view('bio/banner_bio_hosp');
			    $this->load->view('bio/bio_edit_inv_html');			
        		$this->load->view('bio/footer_bio_hosp');
		    } //endif ($_SESSION['thirra_mode'] == "bio_mobile")

		} else {
			//$this->load->view('formsuccess');
            if($data['debug_mode']) {
                echo "\nValidated successfully.";
                echo "<pre>";
                //print_r($data);
                echo "</pre>";
            }
            if($data['save_attempt'] == "NEW INVESTIGATION"){
        		$data['save_attempt'] = 'Inserted successfully';
                if($data['debug_mode']) {
                    echo "<br />Insert record";
                }
                // New patient record
    /*
      inv_other_name character varying(50),
      inv_other_relship character varying(20),
      inv_other_answer text,
      inv_other_remarks character varying(255),
      staff_end_id character(10),
      inv_remarks text,
                $ins_inv_array['']              =   $data['init_'];
    */
                $ins_inv_array   =   array();
                $ins_inv_array['bio_inv_id']            =   $data['now_id'];
                $ins_inv_array['bio_case_id']           =   $data['init_bio_case_id'];
                $ins_inv_array['inv_ref']               =   $data['init_inv_ref'];
                $ins_inv_array['inv_main_name']         =   $data['init_inv_main_name'];
                $ins_inv_array['inv_main_relship']      =   $data['init_inv_main_relship'];
                $ins_inv_array['inv_main_answer']       =   $data['init_inv_main_answer'];
                $ins_inv_array['inv_main_remarks']      =   $data['init_inv_main_remarks'];
                if(is_int($data['init_inv_cluster_size'])) {
                    $ins_inv_array['inv_cluster_size']      =   $data['init_inv_cluster_size'];
                }
                $ins_inv_array['inv_findings']          =   $data['init_inv_findings'];
                $ins_inv_array['inv_summary']           =   $data['init_inv_summary'];
                $ins_inv_array['inv_comments']          =   $data['init_inv_comments'];
                $ins_inv_array['inv_address1']          =   $data['init_inv_address1'];
                $ins_inv_array['inv_address2']          =   $data['init_inv_address2'];
                $ins_inv_array['inv_address3']          =   $data['init_inv_address3'];
                $ins_inv_array['inv_postcode']          =   $data['init_inv_postcode'];
                $ins_inv_array['inv_town']              =   $data['init_inv_town'];
                $ins_inv_array['inv_state']             =   $data['init_inv_state'];
                $ins_inv_array['inv_tel']               =   $data['init_inv_tel'];
                $ins_inv_array['inv_fax']               =   $data['init_inv_fax'];
                $ins_inv_array['inv_email']             =   $data['init_inv_email'];
                $ins_inv_array['inv_gps_lat']           =   $data['init_inv_gps_lat'];
                $ins_inv_array['inv_gps_long']          =   $data['init_inv_gps_long'];
                $ins_inv_array['inv_start_date']        =   $data['init_inv_start_date'];
                if($data['init_inv_end_date']){
                    $ins_inv_array['inv_end_date']              =   $data['init_inv_end_date'];
                }
                $ins_inv_array['staff_start_id']        =   $_SESSION['staff_id'];
	            $ins_inv_data       =   $this->mbio->insert_bio_investigate($ins_inv_array);
                $data['form_purpose']                   = 'edit_inv';  // convert purpose after insertion
            } else {
        		$data['save_attempt'] = 'Updated successfully';
                $upd_inv_array   =   array();
                $upd_inv_array['bio_inv_id']            =   $data['init_bio_inv_id'];
                $upd_inv_array['inv_ref']               =   $data['init_inv_ref'];
                $upd_inv_array['inv_main_name']         =   $data['init_inv_main_name'];
                $upd_inv_array['inv_main_relship']      =   $data['init_inv_main_relship'];
                $upd_inv_array['inv_main_answer']       =   $data['init_inv_main_answer'];
                $upd_inv_array['inv_main_remarks']      =   $data['init_inv_main_remarks'];
                $upd_inv_array['inv_cluster_size']      =   $data['init_inv_cluster_size'];
                $upd_inv_array['inv_findings']          =   $data['init_inv_findings'];
                $upd_inv_array['inv_summary']           =   $data['init_inv_summary'];
                $upd_inv_array['inv_comments']          =   $data['init_inv_comments'];
                $upd_inv_array['inv_address1']          =   $data['init_inv_address1'];
                $upd_inv_array['inv_address2']          =   $data['init_inv_address2'];
                $upd_inv_array['inv_address3']          =   $data['init_inv_address3'];
                $upd_inv_array['inv_postcode']          =   $data['init_inv_postcode'];
                $upd_inv_array['inv_town']              =   $data['init_inv_town'];
                $upd_inv_array['inv_state']             =   $data['init_inv_state'];
                $upd_inv_array['inv_tel']               =   $data['init_inv_tel'];
                $upd_inv_array['inv_fax']               =   $data['init_inv_fax'];
                $upd_inv_array['inv_email']             =   $data['init_inv_email'];
                $upd_inv_array['inv_gps_lat']           =   $data['init_inv_gps_lat'];
                $upd_inv_array['inv_gps_long']          =   $data['init_inv_gps_long'];
                if($data['init_inv_end_date']){
                    $upd_inv_array['end_date']             =   $data['init_inv_end_date'];
                }
    		    $update_data        = $this->mbio->update_investigate_details($upd_inv_array);
            }
    		$this->load->vars($data);
		    if ($_SESSION['thirra_mode'] == "bio_mobile"){
			    $this->load->view('bio/header_xhtml-mobile10');			
			    $this->load->view('bio/banner_bio_wap');			
			    $this->load->view('bio/bio_edit_inv_wap');			
			    $this->load->view('bio/footer_bio_wap');			
		    } else {
		        //$this->load->view('bio/bio/header_xhtml1-strict');
		        $this->load->view('bio/header_xhtml1-transitional');
		        $this->load->view('bio/banner_bio_hosp');
			    $this->load->view('bio/bio_edit_inv_html');			
        		$this->load->view('bio/footer_bio_hosp');
		    } //endif ($_SESSION['thirra_mode'] == "bio_mobile")

		} //endif ($this->form_validation->run() == FALSE)

    } // end of function edit_inv($id)


    // ------------------------------------------------------------------------
    function search_new_notify()
    {
        $data['debug_mode']		    =	$this->_debug_mode;
	  	$this->load->model('mbio');
	  	$this->load->model('mpatients');
		$data['title'] = 'Search for Patient for New Notification';
		$data['name_filter'] = $_POST['patient_name'];
		$data['patlist'] = $this->mbio->get_patients_list($data['name_filter']);
		$this->load->vars($data);
		//$this->load->view('bio/bio/header_xhtml1-strict');
		$this->load->view('bio/header_xhtml1-transitional');
		$this->load->view('bio/banner_bio_hosp');
		$this->load->view('bio/bio_search_new_notify_hosp');
		$this->load->view('bio/footer_bio_hosp');
    } //end of function search_new_case()


    // ------------------------------------------------------------------------
    function edit_notify($patient_id = NULL)
    {
        $data['debug_mode']		    =	$this->_debug_mode;
		$this->load->library('form_validation');
	  	$this->load->model('mbio');
	  	$this->load->model('mpatients');
		$data['form_purpose']   = $this->uri->segment(3);
		$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['diagnosis_list'] = $this->mbio->get_diagnosis_list(TRUE);
		$data['common_diagnosis'] = $this->mbio->get_diagnosis_list(TRUE,TRUE);
		$data['title'] = 'Notification';
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                 =   $_POST['now_id'];
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $data['notification_id']        =   $_POST['notification_id'];
            $data['init_patient_id']        =   $_POST['patient_id'];
            $data['patient_id']             =   $data['init_patient_id'];
            $data['init_visit_date']        =   $_POST['visit_date'];
            $data['init_started_date']      =   $_POST['started_date'];
            $data['init_notify_comments']   =   $_POST['notify_comments'];
            $data['init_notify_ref']        =   $_POST['notify_ref'];
            $data['init_bht_no']            =   $_POST['bht_no'];
            $data['init_dcode1ext_code']    =   $_POST['dcode1ext_code'];
            $data['init_diagnosis_notes']   =   $_POST['diagnosis_notes'];
            $data['init_location_id']       =   $_POST['location_id'];
            $data['init_summary_id']        =   $_POST['summary_id'];
            
      		$data['save_attempt']           = 'EDIT NEW NOTIFY';
            $data['patient_id']             =   $data['init_patient_id']; //came from POST
	        $data['patient_info']           =   $this->mbio->get_patient_details($data['patient_id']);

        } else {
            // First time form is displayed
            $data['now_id']             =   time();
            $patient_id                 =   $this->uri->segment(4);
            $data['patient_id']         =   $patient_id;
            $data['init_patient_id']        =   $data['patient_id'];
            $data['patient_info'] = $this->mbio->get_patient_details($data['patient_id']);
            if($data['form_purpose'] == 'new_notify') {
          		$data['save_attempt'] = 'NEW NOTIFICATION';
                $data['notification_id']     =   "new_notify";   
                $data['init_location_id']    =   $_SESSION['location_id'];
                $data['init_district_id']    =   NULL;
                $data['init_start_date']     =   NULL;
                $data['init_end_date']       =   NULL;
                $data['init_staff_start_id'] =   NULL;
                $data['init_diagnosis_notes']=   NULL;
                $data['init_end_date']       =   NULL;
                $data['init_clinic_name']    =   NULL;
                $data['now_date']            =   date("Y-m-d",$data['now_id']);
                $data['init_summary_id']     =   $data['now_id'];
                $data['init_visit_date']     =   date("Y-m-d",$data['init_summary_id']);
                $data['init_notify_comments']=   NULL;
                $data['init_notify_ref']     =   NULL;
                $data['init_bht_no']         =   NULL;
                $data['init_started_date']   =   $data['init_visit_date'];
            } elseif($data['form_purpose'] == 'edit_notify') {
          		$data['save_attempt']         = 'EDIT OLD NOTIFY';
		        $data['notification_id']      =   $this->uri->segment(5);
		        $data['notify_info']          =   $this->mbio->get_disease_notify_details($data['patient_id'],$data['notification_id']);
                $data['init_notification_id'] =   $data['notification_id'];
                $data['init_summary_id']      =   $data['notify_info']['summary_id'];
                $data['init_visit_date']      =   $data['notify_info']['notify_date'];
                $data['init_started_date']    =   $data['notify_info']['started_date'];
                $data['init_notify_comments'] =   $data['notify_info']['notify_comments'];
                $data['init_notify_ref']      =   $data['notify_info']['notify_ref'];
                $data['init_bht_no']          =   $data['notify_info']['bht_no'];
                $data['init_dcode1ext_code']  =   $data['notify_info']['dcode1ext_code'];
                $data['init_diagnosis_notes'] =   $data['notify_info']['diagnosis_notes'];
                $data['init_location_id']     =   $data['notify_info']['location_id'];
                
            } //endif(data['form_purpose'] == 'new_notify')
        } //endif(count($_POST))

		//$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_notify') == FALSE){
            // Resume loop

        } else {
            if($data['debug_mode']) {
                echo "\nValidated successfully.";
                echo "<pre>";
                //print_r($data);
                echo "</pre>";
            } //endif($data['debug_mode'])
            if($data['form_purpose'] == 'new_notify') {
                //echo "<br />Insert record";
                // New patient record
                $data['last_episode']   = $this->mbio->get_last_session_reference();
                $ins_notify_array   =   array();
                $ins_notify_array['staff_id']              =   $_SESSION['staff_id'];
                $ins_notify_array['adt_id']                =   $data['now_id'];
                $ins_notify_array['bht_no']                =   $data['init_bht_no'];
                $ins_notify_array['summary_id']            =   $data['now_id'];
                $ins_notify_array['session_ref']           =   $data['last_episode']['max_ref']+1;
                $ins_notify_array['session_type']          =   "1";
                $ins_notify_array['patient_id']            =   $data['init_patient_id'];
                $ins_notify_array['date_started']          =   $data['now_date']; // session start date
                $ins_notify_array['time_started']          =   "12:00:00";
                $ins_notify_array['check_in_date']         =   $data['now_date'];
                $ins_notify_array['check_in_time']         =   "12:00:00";
                $ins_notify_array['location_id']           =   $data['init_location_id'];
                $ins_notify_array['location_start']        =   $data['init_location_id'];
                $ins_notify_array['location_end']          =   $data['init_location_id'];
                $ins_notify_array['start_date']            =   $data['now_date']; // ambiguous
                $ins_notify_array['diagnosis_id']          =   $data['now_id'];
                $ins_notify_array['session_id']            =   $data['now_id'];
                $ins_notify_array['diagnosis_type']        =   "Primary";
                $ins_notify_array['dcode1set']             =   "ICPC-2ext";
                $ins_notify_array['dcode1ext_code']        =   $data['init_dcode1ext_code'];
                $ins_notify_array['diagnosis_notes']       =   $data['init_diagnosis_notes'];
                $ins_notify_array['notification_id']       =   $data['now_id'];
                $ins_notify_array['notify_date']           =   $data['now_date'];
                $ins_notify_array['started_date']          =   $data['init_started_date']; //disease start date
                $ins_notify_array['notify_comments']       =   $data['init_notify_comments'];
                $ins_notify_array['notify_ref']            =   $data['init_notify_ref'];
                $ins_notify_array['status']                =   1;
                $ins_notify_array['remarks']               =   "THIRRA";
                $ins_notify_array['now_id']                =   $data['now_id'];
	            $ins_notify_data       =   $this->mbio->insert_disease_notify($ins_notify_array);
          		$data['save_attempt'] = 'NEW NOTIFICATION ADDED SUCCESSFULLY';
            } elseif($data['form_purpose'] == 'edit_notify') {
                echo "<br />Update record";
                $update_array   =   array();
                $update_array['notification_id']        =   $data['notification_id'];
                $update_array['case_diagnosis_notes']   =   $data['init_diagnosis_notes'];
                $update_array['notify_date']            =   $data['init_visit_date'];
                $update_array['notify_started_date']    =   $data['init_started_date'];
                $update_array['notify_comments']        =   $data['init_notify_comments'];
                $update_array['notify_ref']             =   $data['init_notify_ref'];
		        $update_data       =   $this->mbio->update_disease_notify($update_array);
          		$data['save_attempt'] = 'NOTIFICATION UPDATED SUCCESSFULLY';
            }
        }
		$this->load->vars($data);
	    //$this->load->view('bio/bio/header_xhtml1-strict');
	    $this->load->view('bio/header_xhtml1-transitional');
	    $this->load->view('bio/banner_bio_hosp');
	    $this->load->view('bio/bio_edit_notify_hosp');			
		$this->load->view('bio/footer_bio_hosp');
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_notify()


    // ------------------------------------------------------------------------
    function edit_patient($patient_id = NULL)
    {
        $data['debug_mode']		    =	$this->_debug_mode;
		$this->load->library('form_validation');
	  	$this->load->model('mbio');
	  	$this->load->model('mpatients');
		$data['form_purpose']   = $this->uri->segment(3);
		$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['diagnosis_list'] = $this->mbio->get_diagnosis_list(TRUE);
		$data['title'] = 'Add New / Edit Patient';
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                 =   $this->input->post('now_id');
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $data['init_clinic_reference_number']=   $this->input->post('clinic_reference_number');
            //$data['patient_id']             =   $data['init_patient_id'];
            $data['patient_id']             =   $this->input->post('patient_id');
            $data['init_patient_id']        =   $this->input->post('patient_id');
            $data['init_patient_name']      =   $this->input->post('patient_name');
            $data['init_name_first']        =   $this->input->post('name_first');
            $data['init_name_alias']        =   $this->input->post('name_alias');
            $data['init_guardian_name']     =   $this->input->post('guardian_name');
            $data['init_guardian_relation'] =   $this->input->post('guardian_relation');
            $data['init_gender']            =   $this->input->post('gender');
            $data['init_ic_no']             =   $this->input->post('ic_no');
            $data['init_ic_other_no']       =   $this->input->post('ic_other_no');
            $data['init_nationality']       =   $this->input->post('nationality');
            $data['init_birth_date']        =   $this->input->post('birth_date');
            $data['init_ethnicity']         =   $this->input->post('ethnicity');
            $data['init_religion']          =   $this->input->post('religion');
            $data['init_marital_status']    =   $this->input->post('marital_status');
            $data['init_patient_type']      =   $this->input->post('patient_type');
            $data['init_contact_id']        =   $this->input->post('contact_id');
            $data['init_blood_group']       =   $this->input->post('blood_group');
            $data['init_blood_rhesus']      =   $this->input->post('blood_rhesus');
            $data['init_demise_date']       =   $this->input->post('demise_date');
            $data['init_demise_time']       =   $this->input->post('demise_time');
            $data['init_demise_cause']      =   $this->input->post('demise_cause');
            $data['init_patient_address']   =   $this->input->post('patient_address');
            $data['init_patient_address2']  =   $this->input->post('patient_address2');
            $data['init_patient_address3']  =   $this->input->post('patient_address3');
            $data['init_patient_postcode']  =   $this->input->post('patient_postcode');
            $data['init_patient_town']      =   $this->input->post('patient_town');
            $data['init_patient_state']     =   $this->input->post('patient_state');
            $data['init_patient_country']   =   $this->input->post('patient_country');
            $data['init_tel_home']          =   $this->input->post('tel_home');
            $data['init_tel_mobile']        =   $this->input->post('tel_mobile');
            $data['init_tel_office']        =   $this->input->post('tel_office');
            $data['init_location_id']       =   $this->input->post('location_id');
            
            if ($data['patient_id'] == "new_patient"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'ADD NEW PATIENT';
		        $data['patient_info']       = array();
            } else {
                // Edit form
          		$data['save_attempt']       = 'EDIT PATIENT';
                // These fields were passed through as hidden tags
                $data['patient_id']         =   $data['init_patient_id']; //came from POST
		        $data['patient_info']       =   $this->mbio->get_patient_details($data['patient_id']);
                $data['init_patient_id']    =   $data['patient_info']['patient_id'];
                //$data['init_ic_other_no']   =   $data['patient_info']['ic_other_no'];
            } //endif ($patient_id == "new_patient")

        } else {
            // First time form is displayed
            $data['init_location_id']   =   $_SESSION['location_id'];
            $data['init_end_date']      =   NULL;
            $data['init_clinic_name']   =   NULL;
            $data['now_id']             =   time();
            $data['now_date']           =   date("Y-m-d",$data['now_id']);
            $patient_id                 =   $this->uri->segment(4);
            $data['patient_id']         =   $patient_id;

            if ($patient_id == "new_patient") {
                // New patient
	            //$data['patient_id']                 = "";
          		$data['save_attempt']               =   'NEW PATIENT';
	            $data['patient_info']               =   array();
                $data['init_patient_id']            =   "new_patient";
                $data['init_patient_name']          =   NULL;
                $data['init_name_first']            =   NULL;
                $data['init_name_alias']            =   NULL;
                $data['init_guardian_name']  =   NULL;
                $data['init_guardian_relation']=   NULL;
                $data['init_clinic_reference_number']=   NULL;
                $data['init_nationality']           =   "Sri Lanka";
                $data['init_birth_date']            =   NULL;
                $data['init_gender']                =   NULL;
                $data['init_ic_no']                 =   NULL;
                $data['init_ic_other_no']           =   NULL;
                $data['init_ethnicity']             =   NULL;
                $data['init_religion']              =   NULL;
                $data['init_marital_status']        =   NULL;  
                $data['init_patient_type']          =   NULL;
                $data['init_contact_id']            =   NULL;
                $data['init_blood_group']           =   NULL;
                $data['init_blood_rhesus']          =   NULL;
                $data['init_demise_date']           =   NULL;
                $data['init_demise_time']           =   NULL;
                $data['init_demise_cause']          =   NULL;
                $data['init_patient_address']       =   NULL;
                $data['init_patient_address2']      =   NULL;
                $data['init_patient_address3']      =   NULL;
                $data['init_patient_postcode']      =   NULL;
                $data['init_patient_town']          =   NULL;
                $data['init_patient_state']         =   NULL;
                $data['init_patient_country']               =   "Sri Lanka";
                $data['init_tel_home']              =   NULL;
                $data['init_tel_office']            =   NULL;
                $data['init_tel_mobile']            =   NULL;
            } else {
                // Existing patient
	            $data['patient_info'] = $this->mbio->get_patient_details($data['patient_id']);
          		$data['save_attempt'] = 'EDIT PATIENT';
                $data['init_patient_id']        =   $data['patient_id'];
                $data['init_clinic_reference_number']=   $data['patient_info']['clinic_reference_number'];
                $data['init_patient_name']      =   $data['patient_info']['patient_name'];
                $data['init_name_first']        =   $data['patient_info']['name_first'];
                $data['init_name_alias']        =   $data['patient_info']['name_alias'];
                $data['init_guardian_name']     =   $data['patient_info']['guardian_name'];
                $data['init_guardian_relation'] =   $data['patient_info']['guardian_relation'];
                $data['init_nationality']       =   $data['patient_info']['nationality'];
                $data['init_birth_date']        =   $data['patient_info']['birth_date'];
                $data['init_gender']            =   $data['patient_info']['gender'];
                $data['init_ic_no']             =   $data['patient_info']['ic_no'];
                $data['init_ic_other_no']       =   $data['patient_info']['ic_other_no'];
                $data['init_ethnicity']         =   $data['patient_info']['ethnicity'];
                $data['init_religion']          =   $data['patient_info']['religion'];
                $data['init_marital_status']    =   $data['patient_info']['marital_status'];
                $data['init_patient_type']      =   $data['patient_info']['patient_type'];
                $data['init_contact_id']        =   $data['patient_info']['contact_id'];
                $data['init_blood_group']       =   $data['patient_info']['blood_group'];
                $data['init_blood_rhesus']      =   $data['patient_info']['blood_rhesus'];
                $data['init_demise_date']       =   $data['patient_info']['demise_date'];
                $data['init_demise_time']       =   $data['patient_info']['demise_time'];
                $data['init_demise_cause']      =   $data['patient_info']['demise_cause'];
                $data['init_patient_address']   =   $data['patient_info']['patient_address'];
                $data['init_patient_address2']  =   $data['patient_info']['patient_address2'];
                $data['init_patient_address3']  =   $data['patient_info']['patient_address3'];
                $data['init_patient_postcode']  =   $data['patient_info']['patient_postcode'];
                $data['init_patient_town']      =   $data['patient_info']['patient_town'];
                $data['init_patient_state']     =   $data['patient_info']['patient_state'];
                $data['init_patient_country']   =   $data['patient_info']['patient_country'];
                $data['init_tel_home']          =   $data['patient_info']['tel_home'];
                $data['init_tel_office']        =   $data['patient_info']['tel_office'];
                $data['init_tel_mobile']        =   $data['patient_info']['tel_mobile'];
            } //endif ($patient_id == "new_patient")
        } //endif(count($_POST))

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_patient') == FALSE){
		    //$this->load->view('bio/bio/header_xhtml1-strict');
		    $this->load->view('bio/header_xhtml1-transitional');
		    $this->load->view('bio/banner_bio_hosp');
		    $this->load->view('bio/bio_edit_patient_hosp');			
    		$this->load->view('bio/footer_bio_hosp');
        } else {
            if($data['debug_mode']) {
                echo "\nValidated successfully.";
                echo "<pre>";
                //print_r($data);
                echo "</pre>";
                echo "<br />Insert record";
            } // endif($data['debug_mode'])
            if($data['patient_id'] == "new_patient") {
                // New patient record
                $ins_patient_array   =   array();
                $ins_patient_array['staff_id']           = $_SESSION['staff_id'];
                $ins_patient_array['now_id']             = $data['now_id'];
                $_patient_id_yyyy                        = "";
                $ins_patient_array['patient_id']         = "1234567890123456".$data['now_id'];
                $ins_patient_array['clinic_reference_number']= $data['init_clinic_reference_number'];
                $ins_patient_array['patient_name']       = $data['init_patient_name'];
                $ins_patient_array['name_first']         = $data['init_name_first'];
                $ins_patient_array['name_alias']         = $data['init_name_alias'];
                $ins_patient_array['guardian_name']      = $data['init_guardian_name'];
                $ins_patient_array['guardian_relation']  = $data['init_guardian_relation'];
                $ins_patient_array['ic_no']              = $data['init_ic_no'];
                $ins_patient_array['ic_other_no']        = $data['init_ic_other_no'];
                $ins_patient_array['nationality']        = $data['init_nationality'];
                $ins_patient_array['birth_date']         = $data['init_birth_date'];
                $ins_patient_array['family_link']        = "Head of Family";
                $ins_patient_array['gender']             = $data['init_gender'];
                $ins_patient_array['ethnicity']          = $data['init_ethnicity'];
                $ins_patient_array['religion']           = $data['init_religion'];
                $ins_patient_array['patient_type']       = $data['init_patient_type'];
                $ins_patient_array['blood_group']        = $data['init_blood_group'];
                $ins_patient_array['blood_rhesus']       = $data['init_blood_rhesus'];
                if($data['init_demise_date']){
                    $ins_patient_array['demise_date']              =   $data['init_demise_date'];
                }
                if($data['init_demise_time']){
                    $ins_patient_array['demise_time']              =   $data['init_demise_time'];
                }
                $ins_patient_array['demise_cause']       = $data['init_demise_cause'];
                $ins_patient_array['clinic_home']        = $data['init_location_id'];
                $ins_patient_array['clinic_registered']  = $data['init_location_id'];
                $ins_patient_array['patient_status']     = 1;
                $ins_patient_array['location_id']        = $data['init_location_id'];
                $ins_patient_array['contact_id']         = $data['now_id'];
                $ins_patient_array['correspondence_id']  = $data['now_id'];
                $ins_patient_array['contact_type']       = "Residence";
                $ins_patient_array['correspondence_type']= "Correspondence";
                $ins_patient_array['start_date']         = $data['now_date'];
                $ins_patient_array['patient_address']    = $data['init_patient_address'];
                $ins_patient_array['patient_address2']   = $data['init_patient_address2'];
                $ins_patient_array['patient_address3']   = $data['init_patient_address3'];
                $ins_patient_array['patient_postcode']   = $data['init_patient_postcode'];
                $ins_patient_array['patient_town']       = $data['init_patient_town'];
                $ins_patient_array['patient_state']      = $data['init_patient_state'];
                $ins_patient_array['patient_country']    = $data['init_patient_country'];
                $ins_patient_array['tel_home']           = $data['init_tel_home'];
                $ins_patient_array['tel_office']         = $data['init_tel_office'];
                $ins_patient_array['tel_mobile']         = $data['init_tel_mobile'];
	            $ins_patient_data = $this->mbio->insert_new_patient($ins_patient_array);
            } else {
                // Edit patient record
                $upd_patient_array   =   array();
                $upd_patient_array['staff_id']           = $_SESSION['staff_id'];
                $upd_patient_array['now_id']             = $data['now_id'];
                $upd_patient_array['patient_id']         = $data['patient_id'];
                $upd_patient_array['clinic_reference_number']= $data['init_clinic_reference_number'];
                $upd_patient_array['patient_name']       = $data['init_patient_name'];
                $upd_patient_array['name_first']         = $data['init_name_first'];
                $upd_patient_array['name_alias']         = $data['init_name_alias'];
                $upd_patient_array['guardian_name']      = $data['init_guardian_name'];
                $upd_patient_array['guardian_relation']  = $data['init_guardian_relation'];
                $upd_patient_array['ic_no']              = $data['init_ic_no'];
                $upd_patient_array['ic_other_no']        = $data['init_ic_other_no'];
                $upd_patient_array['nationality']        = $data['init_nationality'];
                $upd_patient_array['birth_date']         = $data['init_birth_date'];
                $upd_patient_array['family_link']        = "Head of Family";
                $upd_patient_array['gender']             = $data['init_gender'];
                $upd_patient_array['ethnicity']          = $data['init_ethnicity'];
                $upd_patient_array['religion']           = $data['init_religion'];
                $upd_patient_array['patient_type']       = $data['init_patient_type'];
                $upd_patient_array['blood_group']        = $data['init_blood_group'];
                $upd_patient_array['blood_rhesus']       = $data['init_blood_rhesus'];
                if($data['init_demise_date']){
                    $upd_patient_array['demise_date']              =   $data['init_demise_date'];
                }
                if($data['init_demise_time']){
                    $upd_patient_array['demise_time']              =   $data['init_demise_time'];
                }
                $upd_patient_array['demise_cause']       = $data['init_demise_cause'];
                $upd_patient_array['clinic_home']        = $data['init_location_id'];
                $upd_patient_array['clinic_registered']  = $data['init_location_id'];
                $upd_patient_array['patient_status']     = 1;
                $upd_patient_array['location_id']        = $data['init_location_id'];
                $upd_patient_array['contact_id']         = $data['init_contact_id'];
                $upd_patient_array['correspondence_id']  = $data['now_id'];
                $upd_patient_array['contact_type']       = "Residence";
                $upd_patient_array['correspondence_type']= "Correspondence";
                $upd_patient_array['start_date']         = $data['now_date'];
                $upd_patient_array['patient_address']    = $data['init_patient_address'];
                $upd_patient_array['patient_address2']   = $data['init_patient_address2'];
                $upd_patient_array['patient_address3']   = $data['init_patient_address3'];
                $upd_patient_array['patient_postcode']   = $data['init_patient_postcode'];
                $upd_patient_array['patient_town']       = $data['init_patient_town'];
                $upd_patient_array['patient_state']      = $data['init_patient_state'];
                $upd_patient_array['patient_country']    = $data['init_patient_country'];
                $upd_patient_array['tel_home']           = $data['init_tel_home'];
                $upd_patient_array['tel_office']         = $data['init_tel_office'];
                $upd_patient_array['tel_mobile']         = $data['init_tel_mobile'];
	            $upd_patient_data = $this->mbio->update_patient_info($upd_patient_array);
            } //endif($data['patient_id'] == "new_patient")
            echo form_open('bio/search_new_notify');
            echo "\n<br /><input type='hidden' name='patient_name' value='".$data['init_patient_name']."' size='40' />";
            echo "Saved. <input type='submit' value='Click to Continue' />";
            echo "</form>";

        } //endif ($this->form_validation->run('edit_patient') == FALSE)
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_patient()


    // ------------------------------------------------------------------------
    function upload_pics_inv($bio_inv_id=NULL)
    {
        $data['debug_mode']		    =	$this->_debug_mode;
	  	$this->load->model('mbio');
		$data['patient_id']            =   $this->uri->segment(3);
		$data['notification_id']       =   $this->uri->segment(4);
		$data['bio_case_id']           =   $this->uri->segment(5);
		$data['bio_inv_id']            =   $this->uri->segment(6);
		$data['title'] = 'Upload Picture from Investigation';
	    $data['bio_pics_list'] = $this->mbio->get_pics_list($data['bio_inv_id']);
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/case_pics/";

        $data['now']                =   time();
        $data['bio_pics_id']    =   $data['now'];
        $data['upload_type']    =   "investigation";
        if(count($_POST)) {
            $data['bio_pic_ref']    =   $_POST['bio_pic_ref'];
            $data['bio_pic_title']  =   $_POST['bio_pic_title'];
            $data['bio_pic_descr']  =   $_POST['bio_pic_descr'];
            $data['bio_pic_sort']   =   $_POST['bio_pic_sort'];
            $data['pics_remarks']   =   $_POST['pics_remarks'];
        }
	    $config['upload_path']  = $_SERVER['DOCUMENT_ROOT'].'uploads/case_pics/';
	    //$config['upload_path']  = '/var/www/thirra-uploads/';
	    $config['allowed_types'] = 'gif|jpg|png';
	    $config['max_size']	    = '2048';
	    $config['max_width']    = '3200';
	    $config['max_height']   = '2400';
	    $config['file_name']    = "INV".$data['bio_pics_id'];
	    $this->load->library('upload', $config);
	    if ( ! $this->upload->do_upload())
	    {
		    $error = array('error' => $this->upload->display_errors());
		    //echo "Upload error";
            //print_r($error);
		    //$this->load->view('test_upload', $error);
    		$this->load->vars($data);
		    if ($_SESSION['thirra_mode'] == "bio_mobile"){
			    $this->load->view('bio/header_xhtml-mobile10');			
			    $this->load->view('bio/banner_bio_wap');			
			    $this->load->view('bio/bio_inv_upload_wap');			
			    $this->load->view('bio/footer_bio_wap');			
		    } else {
		        //$this->load->view('bio/bio/header_xhtml1-strict');
		        $this->load->view('bio/header_xhtml1-transitional');
		        $this->load->view('bio/banner_bio_hosp');
			    $this->load->view('bio/bio_inv_upload_html');			
        		$this->load->view('bio/footer_bio_hosp');
		    } //endif ($_SESSION['thirra_mode'] == "bio_mobile")
	    } else {
		    $upload_data = array('upload_data' => $this->upload->data());
		    //echo "Upload succeeded";
            // Image manipulation section
            $config['image_library'] = 'gd2';
            $config['source_image'] = $upload_data['upload_data']['full_path'];
            $config['create_thumb'] = TRUE;
            $config['maintain_ratio'] = TRUE;
            $config['width'] = 75;
            $config['height'] = 75;
            $this->load->library('image_lib', $config);
            $this->image_lib->resize();	
		
            $ins_pics_array   =   array();
            $ins_pics_array['bio_file_id']      =   $data['bio_pics_id'];
            if($data['upload_type'] == "portrait"){
                $ins_pics_array['bio_filename']           =   $data['patient_id'];
            } else {
                $ins_pics_array['bio_filename']           =   $upload_data['upload_data']['file_name'];
            }
            $ins_pics_array['bio_origname']     =   $upload_data['upload_data']['file_name'];
            $ins_pics_array['bio_inv_id']       =   $data['bio_inv_id'];
            $ins_pics_array['bio_patient_id']   =   NULL;
            $ins_pics_array['bio_file_ref']     =   $data['bio_pic_ref'];
            $ins_pics_array['bio_file_title']   =   $data['bio_pic_title'];
            $ins_pics_array['bio_file_descr']   =   $data['bio_pic_descr'];
            //if(is_integer($data['bio_pic_sort'])) {
            $ins_pics_array['bio_file_sort']    =   $data['bio_pic_sort'];
            //}
            $ins_pics_array['staff_id']         =   $_SESSION['staff_id'];
            $ins_pics_array['date_uploaded']    =   date("Y-m-d",$data['bio_pics_id']);
            $ins_pics_array['time_uploaded']    =   date("H:i:s",$data['bio_pics_id']);
            $ins_pics_array['bio_mimetype']     =   $upload_data['upload_data']['file_type'];
            $ins_pics_array['bio_fileext']      =   $upload_data['upload_data']['file_ext'];
            $ins_pics_array['bio_filesize']     =   $upload_data['upload_data']['file_size'];
            $ins_pics_array['bio_filepath']     =   $upload_data['upload_data']['file_path'];
            $ins_pics_array['bio_summary_id']   =   NULL;
            $ins_pics_array['location_id']      =   $_SESSION['location_id'];
            //$ins_pics_array['ip_uploaded']      =   $data['ip_uploaded'];
            $ins_pics_array['file_remarks']     =   $data['pics_remarks'];
	        $ins_pics_data       =   $this->mbio->insert_bio_pics($ins_pics_array);
            /*
            $ins_pics_array['bio_pics_id']           =   $data['bio_pics_id'];
            $ins_pics_array['bio_inv_id']            =   $data['bio_inv_id'];
            $ins_pics_array['bio_pic_ref']           =   $data['bio_pic_ref'];
            $ins_pics_array['bio_pic_title']         =   $data['bio_pic_title'];
            $ins_pics_array['bio_pic_descr']         =   $data['bio_pic_descr'];
            //if(is_integer($data['bio_pic_sort'])) {
            $ins_pics_array['bio_pic_sort']             =   $data['bio_pic_sort'];
            //}
            $ins_pics_array['staff_id']              =   $_SESSION['staff_id'];
            $ins_pics_array['date_uploaded']         =   date("Y-m-d",$data['bio_pics_id']);
            $ins_pics_array['time_uploaded']         =   date("H:i:s",$data['bio_pics_id']);
            $ins_pics_array['location_id']           =   $_SESSION['location_id'];
            $ins_pics_array['pics_remarks']          =   $data['pics_remarks'];
	        $ins_pics_data       =   $this->mbio->insert_bio_pics($ins_pics_array);
            */
 		    //$this->load->view('test_upload_success', $data);
            header("Status: 200");
            redirect('bio/edit_inv/edit_inv/'.$data['patient_id'].'/'.$data['notification_id'].'/'.$data['bio_case_id'].'/'.$data['bio_inv_id'],'refresh');
	    }
        // end of file upload section
           
    } // end of function upload_pics_inv($bio_inv_id=NULL)


    // ------------------------------------------------------------------------
    // === REPORTS MANAGEMENT
    // ------------------------------------------------------------------------
    function reports_mgt($id=NULL)  // template for new classes
    {
        $data['debug_mode']		    =	$this->_debug_mode;
	  	$this->load->model('memr');
		$data['title'] = "T H I R R A - Reports Management";
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_sidebar=   "bio/sidebar_bio_reports_html";
                $new_body   =   "bio/bio_reports_mgt_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_sidebar=   "bio/sidebar_bio_reports_html";
                $new_body   =   "bio/bio_reports_mgt_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "bio/sidebar_bio_reports_hosp";
                $new_body   =   "bio/bio_reports_mgt_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_sidebar=   "bio/sidebar_bio_reports_html";
                $new_body   =   "bio/bio_reports_mgt_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function reports_mgt($id)


    // ------------------------------------------------------------------------
    // === UTILITIES MANAGEMENT
    // ------------------------------------------------------------------------
    function utilities_mgt($id=NULL)  // template for new classes
    {
        $data['debug_mode']		    =	$this->_debug_mode;
	  	$this->load->model('memr');
		$data['title'] = "T H I R R A - Utilities Management";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "emr_mobile"){
            $new_header =   "emr/header_xhtml-mobile10";
            $new_banner =   "emr/banner_emr_wap";
            $new_sidebar=   "emr/sidebar_emr_utilities_wap";
            $new_body   =   "emr/emr_utilities_mgt_wap";
            $new_footer =   "emr/footer_emr_wap";
		} else {
            //$new_header =   "emr/header_xhtml1-strict";
            $new_header =   "emr/header_xhtml1-transitional";
            $new_banner =   "emr/banner_emr_html";
            $new_sidebar=   "emr/sidebar_emr_utilities_html";
            $new_body   =   "emr/emr_utilities_mgt_html";
            $new_footer =   "emr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function utilities_mgt($id)


    // ------------------------------------------------------------------------
    // === ADMIN MANAGEMENT
    // ------------------------------------------------------------------------
    function admin_mgt($id=NULL)  // template for new classes
    {
        $data['debug_mode']		    =	$this->_debug_mode;
	  	$this->load->model('memr');
		$data['title'] = "T H I R R A - Admin Management";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "emr_mobile"){
            $new_header =   "emr/header_xhtml-mobile10";
            $new_banner =   "emr/banner_emr_wap";
            $new_sidebar=   "emr/sidebar_emr_admin_wap";
            $new_body   =   "emr/emr_admin_mgt_wap";
            $new_footer =   "emr/footer_emr_wap";
		} else {
            //$new_header =   "emr/header_xhtml1-strict";
            $new_header =   "emr/header_xhtml1-transitional";
            $new_banner =   "emr/banner_emr_html";
            $new_sidebar=   "emr/sidebar_emr_admin_html";
            $new_body   =   "emr/emr_admin_mgt_html";
            $new_footer =   "emr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function admin_mgt($id)


    // ------------------------------
    // Callbacks for Forms Validation
    // ------------------------------
	function username_check($str) //Callbacks: Your own Validation Functions
	{
		if ($str == 'test'){
			$this->form_validation->set_message('username_check', 'The %s field can not be the word "test"');
			return FALSE;
		} else {
			return TRUE;
		}
	} //end of function username_check($str)


    // ------------------------------------------------------------------------
    // === TEMPLATES
    // ------------------------------------------------------------------------
    function new_method($id=NULL)  // template for new classes
    {
        $data['debug_mode']		    =	$this->_debug_mode;
	  	$this->load->model('memr');
		$data['title'] = "T H I R R A - NewPage";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "emr_mobile"){
            $new_header =   "emr/header_xhtml-mobile10";
            $new_banner =   "emr/banner_emr_wap";
            $new_body   =   "emr/emr_newpage_wap";
            $new_footer =   "emr/footer_emr_wap";
		} else {
            //$new_header =   "emr/header_xhtml1-strict";
            $new_header =   "emr/header_xhtml1-transitional";
            $new_banner =   "emr/banner_emr_html";
            $new_body   =   "emr/emr_newpage_html";
            $new_footer =   "emr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function new_method($id)



}
