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
 * Portions created by the Initial Developer are Copyright (C) 2010 - 2011
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

/**
 * Controller Class for EHR_CONSULT_DIAGNOSIS
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_consult_diagnosis extends MY_Controller 
{
    protected $_patient_id      =  "";
    protected $_offline_mode    =  FALSE;
    //protected $_offline_mode    =  TRUE;
    protected $_debug_mode      =  FALSE;
    //protected $_debug_mode      =  TRUE;


    function __construct()
    {
        parent::__construct();
        
        $this->load->helper('url');
        $this->load->helper('form');
        $data['app_language']		    =	$this->config->item('app_language');
        $this->lang->load('ehr', $data['app_language']);
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->load->model('mthirra');
		$this->load->model('memr_rdb');
		$this->load->model('mconsult_wdb');
		//$this->load->model('mehr_wdb');
        
        $this->pretend_phone	=	FALSE;
        //$this->pretend_phone	=	TRUE;  // Turn this On to overwrites actual device
        $data['debug_mode']		=	TRUE;
        if($data['debug_mode'] == TRUE) {
            // spaghetti html
        } else {
            header('Content-type: application/xhtml+xml'); 
        }

        // Redirect back to login page if not authenticated
		if ((! isset($_SESSION['user_acl'])) || ($_SESSION['user_acl'] < 1)){
            $flash_message  =   "Session Expired.";
            $new_page   =   base_url()."index.php/thirra";
            header("Status: 200");
            header("Location: ".$new_page);
        } // redirect to login page

        /*
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        define("PICS_URL", $data['pics_url']);
        */
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-1);
        //$data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."-uploads/";
        define("PICS_URL", $data['pics_url']);
    }


    // ------------------------------------------------------------------------
    // === PATIENT CONSULTATION
    // ------------------------------------------------------------------------
    // Categorised diagnosis form
    function edit_diagnosis()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('mutil_rdb');
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['diagnosisChapter'])) { 
                $data['diagnosisChapter']   =   $_POST['diagnosisChapter'];
            }
            if(isset($_POST['diagnosisCategory'])) { 
                $data['diagnosisCategory']   =   $_POST['diagnosisCategory'];
            }
            if(isset($_POST['diagnosis'])) { 
                $data['diagnosis']   =   $_POST['diagnosis'];
            }
            if(isset($_POST['diagnosis2'])) { 
                $data['diagnosis2']   =   $_POST['diagnosis2'];
            }
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['patient_id']     = $_POST['patient_id'];
            $data['summary_id']     = $_POST['summary_id'];
            $data['diagnosis_id']   = $_POST['diagnosis_id'];
            $data['diagnosis_type'] = $_POST['diagnosis_type'];
            $data['diagnosis_notes']= $_POST['diagnosis_notes'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['patient_id']     = $this->uri->segment(4);
            $data['summary_id']     = $this->uri->segment(5);
            $data['diagnosis_id']   = $this->uri->segment(6);
            $patient_id             =   $this->uri->segment(4);
            $data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_diagnosis") {
                //echo "New diagnosis";
                $data['diagnosisChapter']   =   "";
                $data['diagnosisCategory']  =   "";
                $data['diagnosis']          =   "";
                $data['diagnosis2']         =   "";
                $data['diagnosis_type']     =   "";
                $data['diagnosis_notes']    =   "";
            } elseif ($data['form_purpose'] == "edit_diagnosis") {
                //echo "Edit diagnosis";
                $data['diagnosis_info'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id'],$data['diagnosis_id']);
                $data['diagnosisChapter']   =   $data['diagnosis_info'][1]['diagnosisChapter'];
                $data['diagnosisCategory']  =   $data['diagnosis_info'][1]['diagnosisCategory'];
                $data['diagnosis']          =   $data['diagnosis_info'][1]['diagnosis'];
                $data['diagnosis2']         =   $data['diagnosis_info'][1]['diagnosis2'];
                $data['diagnosis_type']     =   $data['diagnosis_info'][1]['diagnosis_type'];
                $data['diagnosis_notes']    =   $data['diagnosis_info'][1]['diagnosis_notes'];
            } //endif ($data['form_purpose'] == "new_diagnosis")
        } //endif(count($_POST))
		$data['title'] = "Diagnosis";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['diagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;

        $data['dcode1_chapters'] = $this->mutil_rdb->get_dcode_chapters();
		$data['dcode1_list'] = $this->mutil_rdb->get_dcode1_by_chapter($data['diagnosisChapter']);
        if(isset($data['diagnosisCategory'])){
		    $data['dcode1ext_list'] = $this->mutil_rdb->get_dcode1ext_by_dcode1($data['diagnosisCategory']);
        } else {
            $data['dcode1ext_list'] = array();
        }
        if(isset($data['diagnosis'])){
		    $data['dcode2ext_list'] = $this->mutil_rdb->get_dcode2ext_by_dcode1ext($data['diagnosis']);
        } else {
            $data['dcode2ext_list'] = array();
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_diagnosis') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/emr_edit_diagnosis_wap";
                $new_body   =   "ehr/ehr_edit_diagnosis_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_diagnosis_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            $this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_diagnosis") {
                // New diagnosis record
                $ins_diagnosis_array   =   array();
                $ins_diagnosis_array['staff_id']           = $_SESSION['staff_id'];
                $ins_diagnosis_array['now_id']             = $data['now_id'];
                $ins_diagnosis_array['diagnosis_id']       = $data['now_id'];
                $ins_diagnosis_array['patient_id']         = $data['patient_id'];
                $ins_diagnosis_array['session_id']         = $data['summary_id'];
                $ins_diagnosis_array['adt_id']             = $data['summary_id'];
                $ins_diagnosis_array['diagnosis_type']     = $data['diagnosis_type'];
                $ins_diagnosis_array['diagnosis_notes']    = $data['diagnosis_notes'];
                $ins_diagnosis_array['dcode1set']          = "ICD-10";//$data['init_dcode1set'];
                $ins_diagnosis_array['dcode1ext_code']     = $data['diagnosis'];
                $ins_diagnosis_array['remarks']            = "THIRRA";//$data['remarks'];
                if($data['offline_mode']){
                    $ins_diagnosis_array['synch_out']        = $data['now_id'];
                }
	            $ins_diagnosis_data       =   $this->mconsult_wdb->insert_new_diagnosis($ins_diagnosis_array);
                $this->session->set_flashdata('data_activity', 'Diagnosis added.');
            } elseif($data['form_purpose'] == "edit_diagnosis") {
                // Existing diagnosis record
                $ins_diagnosis_array   =   array();
                $ins_diagnosis_array['staff_id']           = $_SESSION['staff_id'];
                $ins_diagnosis_array['now_id']             = $data['now_id'];
                $ins_diagnosis_array['diagnosis_id']       = $data['diagnosis_id'];
                $ins_diagnosis_array['patient_id']         = $data['patient_id'];
                $ins_diagnosis_array['session_id']         = $data['summary_id'];
                $ins_diagnosis_array['adt_id']             = $data['summary_id'];
                $ins_diagnosis_array['diagnosis_type']     = $data['diagnosis_type'];
                $ins_diagnosis_array['diagnosis_notes']    = $data['diagnosis_notes'];
                $ins_diagnosis_array['dcode1set']          = "ICD-10";//$data['init_dcode1set'];
                $ins_diagnosis_array['dcode1ext_code']     = $data['diagnosis'];
                $ins_diagnosis_array['remarks']            = "THIRRA";//$data['remarks'];
	            $ins_diagnosis_data       =   $this->mconsult_wdb->update_diagnosis($ins_diagnosis_array);
                $this->session->set_flashdata('data_activity', 'Diagnosis updated.');
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_diagnosis') == FALSE)


    } // end of function edit_diagnosis()


    // ------------------------------------------------------------------------
    // Searchable diagnosis form
    function edit_diagnoses()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']    = $_POST['form_purpose'];
            $data['form_id']         = $_POST['form_id'];
            $data['patient_id']      = $_POST['patient_id'];
            $data['summary_id']      = $_POST['summary_id'];
            $data['diagnosis_id']    = $_POST['diagnosis_id'];
            $data['diagnosis_term1'] = $_POST['diagnosis_term1'];
            $data['diagnosis_pullall']= FALSE;
            $data['diagnosis']       = $_POST['diagnosis'];
            $data['diagnosis_type']  = $_POST['diagnosis_type'];
            $data['diagnosis_notes'] = $_POST['diagnosis_notes'];
            if(isset($data['diagnosis'])){
                $data['diagnosis_chosen'] = $this->memr_rdb->get_one_diagnosis_code($data['diagnosis']);
            } //endif(isset($data['diagnosis']))
            if(($data['form_id'] == "search") && (strlen($data['diagnosis_term1'])>2)){
                $data['diagnosis_filter'] = $this->memr_rdb->get_diagnosis_list($data['diagnosis_pullall'],$data['diagnosis_term1']);
            } //endif($data['form_id'] == "search")
            // Check whether any search result was returned, if searched.
            if(! isset($data['diagnosis_filter'])){ // If none returned
                $data['diagnosis_filter']= array();
            } //endif(! isset($data['diagnosis_filter']))
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['patient_id']     = $this->uri->segment(4);
            $data['summary_id']     = $this->uri->segment(5);
            $data['diagnosis_id']   = $this->uri->segment(6);
            $data['diagnosis_term1'] = "none";
            $data['diagnosis_filter']= array();
            $data['init_patient_id']    =   $data['patient_id'];
            $data['form_id']        = "";
            if ($data['form_purpose'] == "new_diagnosis") {
                //echo "New diagnosis";
                $data['diagnosisChapter']   =   "";
                $data['diagnosisCategory']  =   "";
                $data['diagnosis']          =   "";
                $data['diagnosis2']         =   "";
                $data['diagnosis_type']     =   "";
                $data['diagnosis_notes']    =   "";
            } elseif ($data['form_purpose'] == "edit_diagnosis") {
                //echo "Edit diagnosis";
                $data['diagnosis_info'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id'],$data['diagnosis_id']);
                $data['diagnosisChapter']   =   $data['diagnosis_info'][1]['diagnosisChapter'];
                $data['diagnosisCategory']  =   $data['diagnosis_info'][1]['diagnosisCategory'];
                $data['diagnosis']          =   $data['diagnosis_info'][1]['diagnosis'];
                $data['diagnosis2']         =   $data['diagnosis_info'][1]['diagnosis2'];
                $data['diagnosis_type']     =   $data['diagnosis_info'][1]['diagnosis_type'];
            } //endif ($data['form_purpose'] == "new_diagnosis")
        } //endif(count($_POST))
		$data['title'] = "Diagnosis";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['diagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id']);
        $data['diagnosis_common'] = $this->memr_rdb->get_common_diagnosis();
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_diagnosis') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/emr_edit_diagnoses_wap";
                $new_body   =   "ehr/ehr_edit_diagnoses_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_diagnoses_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            $this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_diagnosis") {
                // New diagnosis record
                $ins_diagnosis_array   =   array();
                $ins_diagnosis_array['staff_id']           = $_SESSION['staff_id'];
                $ins_diagnosis_array['now_id']             = $data['now_id'];
                $ins_diagnosis_array['diagnosis_id']       = $data['now_id'];
                $ins_diagnosis_array['patient_id']         = $data['patient_id'];
                $ins_diagnosis_array['session_id']         = $data['summary_id'];
                $ins_diagnosis_array['adt_id']             = $data['summary_id'];
                $ins_diagnosis_array['diagnosis_type']     = $data['diagnosis_type'];
                $ins_diagnosis_array['diagnosis_notes']    = $data['diagnosis_notes'];
                $ins_diagnosis_array['dcode1set']          = "ICD-10";//$data['init_dcode1set'];
                $ins_diagnosis_array['dcode1ext_code']     = $data['diagnosis'];
                $ins_diagnosis_array['remarks']            = "THIRRA";//$data['remarks'];
                if($data['offline_mode']){
                    $ins_diagnosis_array['synch_out']        = $data['now_id'];
                }
	            $ins_diagnosis_data       =   $this->mconsult_wdb->insert_new_diagnosis($ins_diagnosis_array);
                $this->session->set_flashdata('data_activity', 'Diagnosis added.');
            } elseif($data['form_purpose'] == "edit_diagnosis") {
                // Existing diagnosis record
                $ins_diagnosis_array   =   array();
                $ins_diagnosis_array['staff_id']           = $_SESSION['staff_id'];
                $ins_diagnosis_array['now_id']             = $data['now_id'];
                $ins_diagnosis_array['diagnosis_id']       = $data['diagnosis_id'];
                $ins_diagnosis_array['patient_id']         = $data['patient_id'];
                $ins_diagnosis_array['session_id']         = $data['summary_id'];
                $ins_diagnosis_array['adt_id']             = $data['summary_id'];
                $ins_diagnosis_array['diagnosis_type']     = $data['diagnosis_type'];
                $ins_diagnosis_array['diagnosis_notes']    = $data['diagnosis_notes'];
                $ins_diagnosis_array['dcode1set']          = "ICD-10";//$data['init_dcode1set'];
                $ins_diagnosis_array['dcode1ext_code']     = $data['diagnosis'];
                $ins_diagnosis_array['remarks']            = "THIRRA";//$data['remarks'];
	            $ins_diagnosis_data       =   $this->mconsult_wdb->update_diagnosis($ins_diagnosis_array);
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_diagnosis') == FALSE)


    } // end of function edit_diagnoses()


    // ------------------------------------------------------------------------
    function consult_delete_diagnosis($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['patient_id']         =   $this->uri->segment(3);
        $data['summary_id']         =   $this->uri->segment(4);
        $data['diagnosis_id']       =   $this->uri->segment(5);
        
        // Delete records
        $del_rec_array['diagnosis_id']      = $data['diagnosis_id'];
        $del_rec_data =   $this->mconsult_wdb->consult_delete_diagnosis($del_rec_array);
        $this->session->set_flashdata('data_activity', 'Diagnosis deleted.');
        $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function consult_delete_diagnosis($id)


    // ------------------------------------------------------------------------
    // Categorised diagnosis form
    function edit_prediagnosis()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('mutil_rdb');
		$this->load->library('form_validation');
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['diagnosisChapter'])) { 
                $data['diagnosisChapter']   =   $_POST['diagnosisChapter'];
            }
            if(isset($_POST['diagnosisCategory'])) { 
                $data['diagnosisCategory']   =   $_POST['diagnosisCategory'];
            }
            if(isset($_POST['diagnosis'])) { 
                $data['diagnosis']   =   $_POST['diagnosis'];
            }
            if(isset($_POST['diagnosis2'])) { 
                $data['diagnosis2']   =   $_POST['diagnosis2'];
            }
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['patient_id']     = $_POST['patient_id'];
            $data['summary_id']     = $_POST['summary_id'];
            $data['diagnosis_id']   = $_POST['diagnosis_id'];
            $data['diagnosis_type'] = $_POST['diagnosis_type'];
            $data['diagnosis_notes']= $_POST['diagnosis_notes'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['patient_id']     = $this->uri->segment(4);
            $data['summary_id']     = $this->uri->segment(5);
            $data['diagnosis_id']   = $this->uri->segment(6);
            $patient_id             =   $this->uri->segment(4);
            $data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_diagnosis") {
                //echo "New diagnosis";
                $data['diagnosisChapter']   =   "0 - Prediagnostic observations";
                $data['diagnosisCategory']  =   "Z00";
                $data['diagnosis']          =   "";
                $data['diagnosis2']         =   "";
                $data['diagnosis_type']     =   "";
                $data['diagnosis_notes']    =   "";
            } elseif ($data['form_purpose'] == "edit_diagnosis") {
                //echo "Edit diagnosis";
                $data['diagnosis_info'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id'],$data['diagnosis_id']);
                $data['diagnosisChapter']   =   $data['diagnosis_info'][1]['diagnosisChapter'];
                $data['diagnosisCategory']  =   $data['diagnosis_info'][1]['diagnosisCategory'];
                $data['diagnosis']          =   $data['diagnosis_info'][1]['diagnosis'];
                $data['diagnosis2']         =   $data['diagnosis_info'][1]['diagnosis2'];
                $data['diagnosis_type']     =   $data['diagnosis_info'][1]['diagnosis_type'];
                $data['diagnosis_notes']    =   $data['diagnosis_info'][1]['diagnosis_notes'];
            } //endif ($data['form_purpose'] == "new_diagnosis")
        } //endif(count($_POST))
		$data['title'] = "Diagnosis";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['diagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id'],NULL,NULL,TRUE);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;

        $data['dcode1_chapters'] = $this->mutil_rdb->get_dcode_chapters();
		$data['dcode1_list'] = $this->mutil_rdb->get_dcode1_by_chapter($data['diagnosisChapter']);
        if(isset($data['diagnosisCategory'])){
		    $data['dcode1ext_list'] = $this->mutil_rdb->get_dcode1ext_by_dcode1($data['diagnosisCategory']);
        } else {
            $data['dcode1ext_list'] = array();
        }
        if(isset($data['diagnosis'])){
		    $data['dcode2ext_list'] = $this->mutil_rdb->get_dcode2ext_by_dcode1ext($data['diagnosis']);
        } else {
            $data['dcode2ext_list'] = array();
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_diagnosis') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/emr_edit_diagnosis_wap";
                $new_body   =   "ehr/ehr_edit_prediagnosis_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_prediagnosis_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            $this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_diagnosis") {
                // New diagnosis record
                $ins_diagnosis_array   =   array();
                $ins_diagnosis_array['staff_id']           = $_SESSION['staff_id'];
                $ins_diagnosis_array['now_id']             = $data['now_id'];
                $ins_diagnosis_array['diagnosis_id']       = $data['now_id'];
                $ins_diagnosis_array['patient_id']         = $data['patient_id'];
                $ins_diagnosis_array['session_id']         = $data['summary_id'];
                $ins_diagnosis_array['adt_id']             = $data['summary_id'];
                $ins_diagnosis_array['diagnosis_type']     = $data['diagnosis_type'];
                $ins_diagnosis_array['diagnosis_notes']    = $data['diagnosis_notes'];
                $ins_diagnosis_array['dcode1set']          = "ICD-10";//$data['init_dcode1set'];
                $ins_diagnosis_array['dcode1ext_code']     = $data['diagnosis'];
                $ins_diagnosis_array['remarks']            = "THIRRA";//$data['remarks'];
                if($data['offline_mode']){
                    $ins_diagnosis_array['synch_out']        = $data['now_id'];
                }
	            $ins_diagnosis_data       =   $this->mconsult_wdb->insert_new_diagnosis($ins_diagnosis_array);
                $this->session->set_flashdata('data_activity', 'Pre-diagnosis added.');
            } elseif($data['form_purpose'] == "edit_diagnosis") {
                // Existing diagnosis record
                $ins_diagnosis_array   =   array();
                $ins_diagnosis_array['staff_id']           = $_SESSION['staff_id'];
                $ins_diagnosis_array['now_id']             = $data['now_id'];
                $ins_diagnosis_array['diagnosis_id']       = $data['diagnosis_id'];
                $ins_diagnosis_array['patient_id']         = $data['patient_id'];
                $ins_diagnosis_array['session_id']         = $data['summary_id'];
                $ins_diagnosis_array['adt_id']             = $data['summary_id'];
                $ins_diagnosis_array['diagnosis_type']     = $data['diagnosis_type'];
                $ins_diagnosis_array['diagnosis_notes']    = $data['diagnosis_notes'];
                $ins_diagnosis_array['dcode1set']          = "ICD-10";//$data['init_dcode1set'];
                $ins_diagnosis_array['dcode1ext_code']     = $data['diagnosis'];
                $ins_diagnosis_array['remarks']            = "THIRRA";//$data['remarks'];
	            $ins_diagnosis_data       =   $this->mconsult_wdb->update_diagnosis($ins_diagnosis_array);
                $this->session->set_flashdata('data_activity', 'Pre-diagnosis updated.');
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_diagnosis') == FALSE)


    } // end of function edit_prediagnosis()


    // ------------------------------------------------------------------------
    function autocomplete_diagnosis() // http://www.jamipietila.fi/codeigniter-and-autocomplete-with-jquery/
    {
        $term = $this->input->post('term',TRUE);
        
        if (strlen($term) < 3) break;
        $rows = $this->memr_rdb->get_autocomplete_diagnosis($term);
        
        $keywords = array();
        foreach ($rows as $row)
            array_push($keywords, $row->dcode1ext_longname);
            
        echo json_encode($keywords);
    }


    // ------------------------------------------------------------------------
    function break_date($iso_date)  // template for new classes
    {
        $broken_date          =   array();
        $broken_date['yyyy']  =   substr($iso_date,0,4);
        $broken_date['mm']    =   substr($iso_date,5,2);
        $broken_date['dd']    =   substr($iso_date,8,2);
        return $broken_date;
    } // end of function break_date($iso_date)


    // ------------------------------------------------------------------------
    // === TEMPLATES
    // ------------------------------------------------------------------------
    function new_method($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['title'] = "T H I R R A - NewPage";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_conslt_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/sidebar_ehr_patients_conslt_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/emr_newpage_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function new_method($id)


}

/* End of file emr.php */
/* Location: ./app_thirra/controllers/emr.php */
