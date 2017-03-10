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
 * Controller Class for EHR_INDIVIDUAL
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_individual extends MY_Controller 
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
		$this->load->model('memr_rdb');
		$this->load->model('mehr_wdb');
		$this->load->model('mthirra');

		// PanaCI
        $params = array('width' => 750, 'height' => 800, 'margin' => 10, 'backgroundColor' => '#eeeeee',);
        $this->load->library('chart', $params);

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

        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-1);
        //$data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."-uploads/";
        define("PICS_URL", $data['pics_url']);
    }


    // ------------------------------------------------------------------------
    // === INDIVIDUAL RECORD
    // ------------------------------------------------------------------------
    /**
     * Patient Overview Sheet
     *
     * @author  Jason Tan Boon Teck
     */
	function individual_overview()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['age_menarche']	    =	$this->config->item('age_menarche');
		$this->load->model('mqueue_rdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['patient_id'] = $this->uri->segment(3);
	  	
		$data['main'] = 'individual_overview';
		//$data['patient_id'] = $this->uri->segment(3);
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
		$data['title']          = "PR-".$data['patient_info']['name'];
		$data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
		$data['patient_past_con'] = $this->memr_rdb->get_pastcons_list($data['patient_id']);
		$data['drug_allergies'] = $this->memr_rdb->get_drug_allergies('List',$data['patient_id']);
        $data['queue_info']     = $this->mqueue_rdb->get_patients_queue(NULL,NULL,NULL,$data['patient_id']);
        $data['vitals_info']    = $this->memr_rdb->get_recent_vitals($data['patient_id']);
        $data['medication_info']= $this->memr_rdb->get_recent_medication($data['patient_id'],5,0);
        $data['lab_info']       = $this->memr_rdb->get_recent_lab($data['patient_id']);
        $data['imaging_info']   = $this->memr_rdb->get_recent_imaging($data['patient_id']);
        $data['prediagnoses_info']= $this->memr_rdb->get_recent_diagnoses($data['patient_id'],TRUE);
        $data['diagnoses_info'] = $this->memr_rdb->get_recent_diagnoses($data['patient_id'],FALSE);
		$data['social_history'] = $this->memr_rdb->get_history_social('List',$data['patient_id']);
        $data['vaccines_list'] 	= $this->memr_rdb->get_patient_immunisation($data['patient_id'],6,0);
		$data['history_antenatal']  = $this->memr_rdb->get_antenatal_list('list',$data['patient_id']);
		$data['antenatal_checkup']  = $this->memr_rdb->get_antenatal_followup('list',$data['patient_id']);
        $data['referrals_list'] = $this->memr_rdb->get_history_referrals('Consulted','List',$data['patient_id']);
        if($_SESSION['thirra_mode'] == "ehr_mobile") {
            $data['multicolumn']    =   FALSE;
        } else {
            $data['multicolumn']    =   TRUE;
        }
        /*
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        */
        if($data['debug_mode']){
            $this->output->enable_profiler(TRUE);  
        }
        $this->load->vars($data);
		/*
		echo '<pre>';
			print_r($data);
		echo '</pre>';
		*/
		//$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            //echo "STOP";
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            $new_body   =   "ehr/ehr_indv_overview_html";
            //$new_body   =   "ehr/ehr_indv_overview_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_overview_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_patients'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
	} // end of function individual_overview()
	

    // ------------------------------------------------------------------------
    function edit_patient($patient_id = NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
		$this->load->model('mutil_rdb');
        $data['thirra_js_vol1']     =   "thirra_js_vol1.js";
        $data['location_id']    =   $_SESSION['location_id'];
		$data['form_purpose']   = $this->uri->segment(3);
		$data['clinic_info']    = $this->mthirra->get_clinic_info($_SESSION['location_id']);
		$data['clinics_list']   = $this->mthirra->get_clinics_list('All');
		$data['addr_village_list']	=	$this->mutil_rdb->get_addr_village_list($data['app_country'],"addr_village_sort");
        //$this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']      		    =   $this->input->post('now_id');
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $data['init_patient_id']        =   $this->input->post('patient_id');
            $data['patient_id']             =   $data['init_patient_id'];

            $data['init_clinic_reference_number']=   $this->input->post('clinic_reference_number');
            $data['init_pns_pat_id']        =   $this->input->post('pns_pat_id');
            $data['init_nhfa_no']           =   $this->input->post('nhfa_no');
            $data['patient_name']      		=   trim($this->input->post('patient_name'));
            $data['name_first']      		=   trim($this->input->post('name_first'));
            $data['name_alias']      		=   $this->input->post('name_alias');
            $data['gender']      			=   $this->input->post('gender');
            $data['ic_no']      			=   trim($this->input->post('ic_no'));
            $data['init_ic_other_no']      	=   trim($this->input->post('ic_other_no'));
            $data['init_ic_other_type']     =   trim($this->input->post('ic_other_type'));
            $data['init_nationality']       =   trim($this->input->post('nationality'));
            //$data['init_birth_date']        =   $this->input->post('birth_date');
            $data['posted_birth_date']      =   $this->input->post('birth_date');
            $data['init_birth_date']        =   $data['posted_birth_date'];
            $data['init_birth_cert_no']     =   trim($this->input->post('birth_cert_no'));
            $data['init_ethnicity']         =   $this->input->post('ethnicity');
            $data['init_religion']          =   $this->input->post('religion');
            $data['init_marital_status']    =   $this->input->post('marital_status');
            //$data['init_company']           =   $this->input->post('company');
            //$data['init_employee_no']       =   $this->input->post('employee_no');
            $data['init_job_function']      =   $this->input->post('job_function');
            //$data['init_job_industry']      =   $this->input->post('job_industry');
            //$data['init_patient_employment_id']=   $this->input->post('patient_employment_id');
            $data['init_education_level']   =   $this->input->post('education_level');
            $data['init_patient_type']      =   $this->input->post('patient_type');
            $data['init_blood_group']       =   $this->input->post('blood_group');
            $data['init_blood_rhesus']   	=   $this->input->post('blood_rhesus');
            $data['init_next_of_kin_id']    =   $this->input->post('next_of_kin_id');
            $data['init_next_of_kin_name']  =   $this->input->post('next_of_kin_name');
            $data['init_next_of_kin_relationship']=   $this->input->post('next_of_kin_relationship');
            $data['init_demise_date']      	=   $this->input->post('demise_date');
            $data['init_demise_time']      	=   $this->input->post('demise_time');
            $data['init_demise_cause']      =   $this->input->post('demise_cause');
            $data['init_death_cert']      	=   $this->input->post('death_cert');
            $data['init_patient_status']    =   $this->input->post('patient_status');
            $data['init_patdemo_remarks']   =   $this->input->post('patdemo_remarks');
            $data['init_clinic_home']       =   $this->input->post('clinic_home');
            $data['init_clinic_registered']     =   $data['location_id'];
            $data['init_birth_date_estimate']   =   $this->input->post('birth_date_estimate');
            $data['init_age']      		    =   trim($this->input->post('age'));
            $data['init_synch_out']      	=   $this->input->post('synch_out');
            $data['contact_id']      		=   $this->input->post('contact_id');
            $data['init_patient_address']   =   trim($this->input->post('patient_address'));
            $data['init_patient_address2']  =   trim($this->input->post('patient_address2'));
            $data['init_patient_address3']  =   trim($this->input->post('patient_address3'));
            $data['init_patient_postcode']  =   trim($this->input->post('patient_postcode'));
            $data['init_patient_town']      =   $this->input->post('patient_town');
            $data['init_patient_state']     =   $this->input->post('patient_state');
            $data['init_patient_country']   =   $this->input->post('patient_country');
            $data['init_tel_home']          =   $this->input->post('tel_home');
            $data['init_tel_office']      	=   $this->input->post('tel_office');
            $data['init_tel_mobile']      	=   $this->input->post('tel_mobile');
            $data['init_pager_no']      	=   $this->input->post('pager_no');
            $data['init_fax_no']      		=   $this->input->post('fax_no');
            $data['init_email']      		=   $this->input->post('email');
            $data['init_contact_other']     =   $this->input->post('contact_other');
            $data['init_contact_remarks']   =   $this->input->post('contact_remarks');
            $data['init_addr_village_id']   =   $this->input->post('addr_village_id');
            $data['init_addr_town_id']      =   $this->input->post('addr_town_id');
            $data['init_addr_area_id']      =   $this->input->post('addr_area_id');
            $data['init_addr_district_id']  =   $this->input->post('addr_district_id');
            $data['init_addr_state_id']     =   $this->input->post('addr_state_id');
            $data['init_location_id']       =   $this->input->post('location_id');
            //$data['']      		=   $this->input->post('');

            if ($data['patient_id'] == "new_patient"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'ADD NEW PATIENT';
		        $data['patient_info']       = array();
                $data['patient_info']['name']       =   " ";
                $data['patient_info']['name_first'] =   " ";
                $data['patient_info']['gender']     =   " ";
                $data['patient_info']['birth_date'] =   " ";
                $data['patient_info']['age_words']     =   " ";
            } else {
                // Edit form
          		$data['save_attempt']       = 'EDIT PATIENT';
                // These fields were passed through as hidden tags
                $data['patient_id']         =   $data['init_patient_id']; //came from POST
		        $data['patient_info']       =   $this->memr_rdb->get_patient_details($data['patient_id']);
                $data['init_patient_id']    =   $data['patient_info']['patient_id'];
                //$data['init_ic_other_no']   =   $data['patient_info']['ic_other_no'];
                $data['init_clinic_registered'] = $data['patient_info']['clinic_registered'];
            } //endif ($patient_id == "new_patient")

            if(($data['init_birth_date_estimate'] == "TRUE") && ($data['init_age'] > 0)){
                //echo "Compute birth date based on age";
                $age_days                        =   "-".floor($data['init_age']*365.2425)." days";
                $data['init_birth_date']    =   date("Y-m-d", strtotime($age_days));
                //echo "<br />data['init_age']=".$data['init_age']." <br />data[init_birth_date]=".$data['init_birth_date']." <br />age_days=".$age_days;
            } elseif(($data['init_birth_date_estimate'] == "FALSE") && (!empty($data['init_birth_date']))) {
                //echo "Compute age based on birth date";
                $data['init_age']  = round((time()-strtotime($data['init_birth_date']))/(60*60*24*365.2425),2); //365.2425
                //echo "<br />data['init_age']=".$data['init_age']." <br />data[init_birth_date]=".$data['init_birth_date'];
            } else {
                //echo "E R R O R";
            }
            // Some gymnastics to push value to POST array and retrieving them back to enable set_value to recognise the new values
            $_POST['birth_date']        =   $data['init_birth_date'];
            $data['init_birth_date']    =   $_POST['birth_date'];
            $_POST['age']               =   $data['init_age'];
            $data['init_age']           =   $_POST['age'];
            $data['broken_birth_date']      =   break_date($data['init_birth_date']);
            $data['broken_now_date']        =   break_date($data['now_date']);

            
        } else {
            // First time form is displayed
            $data['init_location_id']   =   $_SESSION['location_id'];
            $data['init_end_date']      =   NULL;
            $data['init_clinic_name']   =   NULL;
            $data['now_id']             =   time();
            $data['now_date']           =   date("Y-m-d",$data['now_id']);
            $patient_id                 =   $this->uri->segment(4);
            $data['patient_id']         =   $patient_id;

            if ($data['form_purpose'] == "new_patient") {
                // New patient
	            //$data['patient_id']                 = "";
          		$data['save_attempt']               =   'NEW PATIENT';
	            $data['patient_info']               =   array();
                $data['patient_info']['name']       =   " ";
                $data['patient_info']['name_first'] =   " ";
                $data['patient_info']['gender']     =   " ";
                $data['patient_info']['birth_date'] =   " ";
                $data['patient_info']['age_words']     =   " ";
                $data['init_patient_id']            =   "new_patient";
                $data['init_clinic_reference_number']=   NULL;
                $data['init_pns_pat_id']            =   NULL;
                $data['init_nhfa_no']               =   NULL;
                $data['patient_name']               =   NULL;
                $data['name_first']                 =   NULL;
                $data['name_alias']                 =   NULL;
                $data['gender']                     =   NULL;
                $data['init_ic_other_no']           =   NULL;
                $data['init_ic_other_type']         =   NULL;
                $data['ic_no']                      =   NULL;
                $data['init_nationality']           =   $data['clinic_info']['country'];
                $data['init_birth_date']            =   NULL;
                $data['init_birth_cert_no']         =   NULL;
                if("Malaysia" == $data['app_country']){
                    $data['init_ethnicity']             =   "Orang Asli";
                } else {
                    $data['init_ethnicity']             =   NULL;
                }
                $data['init_religion']              =   NULL;
                $data['init_marital_status']        =   NULL;
                $data['init_company']               =   NULL;
                $data['init_employee_no']           =   NULL;
                $data['init_job_function']          =   NULL;
                $data['init_job_industry']          =   NULL;
                $data['init_patient_employment_id'] =   NULL;
                $data['init_education_level']       =   NULL;
                $data['init_patient_type']          =   NULL;
                $data['init_blood_group']           =   NULL;
                $data['init_blood_rhesus']          =   NULL;
                $data['init_next_of_kin_id']        =   NULL;
                $data['init_next_of_kin_name']      =   NULL;
                $data['init_next_of_kin_relationship']=   NULL;
                $data['init_demise_date']           =   NULL;
                $data['init_demise_time']           =   NULL;
                $data['init_demise_cause']          =   NULL;
                $data['init_death_cert']	        =   NULL;
                $data['init_clinic_home']           =   $data['location_id'];
                $data['init_clinic_registered']     =   $data['location_id'];
                $data['init_patient_status']        =   1;
                $data['init_patdemo_remarks']       =   NULL;
                $data['init_birth_date_estimate']   =   "FALSE";
                $data['init_age']                   =   NULL;
                $data['init_synch_out']             =   NULL;
                $data['contact_id']	                =   NULL;
                $data['init_patient_address']       =   NULL;
                $data['init_patient_address2']      =   NULL;
                $data['init_patient_address3']      =   NULL;
                $data['init_patient_postcode']      =   NULL;
                $data['init_patient_village']       =   NULL;
                $data['init_patient_town']          =   NULL;
                $data['init_patient_area']          = "";
                $data['init_patient_district']      = "";
                $data['init_patient_state']         =   $data['clinic_info']['state'];
                $data['init_patient_country']       = 	$data['clinic_info']['country'];
                $data['init_tel_home']              =   NULL;
                $data['init_tel_office']            =   NULL;
                $data['init_tel_mobile']            =   NULL;
                $data['init_pager_no']              =   NULL;
                $data['init_fax_no']                =   NULL;
                $data['init_email']                 =   NULL;
                $data['init_contact_other']         =   NULL;
                $data['init_contact_remarks']       =   NULL;
                $data['init_addr_village_id']       =   "new";
                $data['init_addr_town_id']          =   "new";
                $data['init_addr_area_id']          =   "new";
                $data['init_addr_district_id']      =   "new";
                $data['init_addr_state_id']         =   "new";
            } else {
                // Existing patient
	            $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
          		$data['save_attempt'] = 'EDIT PATIENT';
                $data['init_patient_id']        =   $data['patient_id'];
                $data['init_clinic_reference_number']=   $data['patient_info']['clinic_reference_number'];
                $data['init_pns_pat_id']        = $data['patient_info']['pns_pat_id'];
                $data['init_nhfa_no']           = $data['patient_info']['nhfa_no'];
                $data['patient_name']           = $data['patient_info']['patient_name'];
                $data['patient_info']['name']   = $data['patient_info']['patient_name'];
                $data['name_first']             = $data['patient_info']['name_first'];
                $data['name_alias']             = $data['patient_info']['name_alias'];
                $data['gender']                 = $data['patient_info']['gender'];
                $data['ic_no']                  = $data['patient_info']['ic_no'];
                $data['init_ic_other_no']       = $data['patient_info']['ic_other_no'];
                $data['init_ic_other_type']     = $data['patient_info']['ic_other_type'];
                $data['init_nationality']       = $data['patient_info']['nationality'];
                $data['init_birth_date']        = $data['patient_info']['birth_date'];
                $data['init_birth_cert_no']     = $data['patient_info']['birth_cert_no'];
                $data['init_ethnicity']         = $data['patient_info']['ethnicity'];
                $data['init_religion']          = $data['patient_info']['religion'];
                $data['init_marital_status']    = $data['patient_info']['marital_status'];
                $data['init_company']           = $data['patient_info']['company'];
                $data['init_employee_no']       = $data['patient_info']['employee_no'];
                $data['init_job_function']      = $data['patient_info']['job_function'];
                $data['init_job_industry']      = $data['patient_info']['job_industry'];
                $data['init_patient_employment_id']= $data['patient_info']['patient_employment_id'];
                $data['init_education_level']   = $data['patient_info']['education_level'];
                $data['init_patient_type']      = $data['patient_info']['patient_type'];
                $data['init_blood_group']       = $data['patient_info']['blood_group'];
                $data['init_blood_rhesus']      = $data['patient_info']['blood_rhesus'];
                $data['init_next_of_kin_id']    = $data['patient_info']['next_of_kin_id'];
                $data['init_next_of_kin_name']  = $data['patient_info']['next_of_kin_name'];
                $data['init_next_of_kin_relationship']= $data['patient_info']['next_of_kin_relationship'];
                $data['init_demise_date']       = $data['patient_info']['demise_date'];
                $data['init_demise_time']       = $data['patient_info']['demise_time'];
                $data['init_demise_cause']      = $data['patient_info']['demise_cause'];
                $data['init_death_cert']        = $data['patient_info']['death_cert'];
                $data['init_clinic_home']       = $data['patient_info']['clinic_home'];
                $data['init_clinic_registered'] = $data['patient_info']['clinic_registered'];
                $data['init_religion']          = $data['patient_info']['religion'];
                $data['init_patient_status']    = $data['patient_info']['status'];
                $data['init_patdemo_remarks']   = $data['patient_info']['patdemo_remarks'];
                if($data['patient_info']['birth_date_estimate']===TRUE){
                    $data['init_birth_date_estimate']=  "TRUE";
                } else {
                    $data['init_birth_date_estimate']=  "FALSE";
                }
                //$data['init_birth_date_estimate']= $data['patient_info']['birth_date_estimate'];
                $data['init_age']               = round((time()-strtotime($data['patient_info']['birth_date']))/(60*60*24*365.2425),1);
                $data['init_synch_out']         = $data['patient_info']['synch_out'];
                $data['contact_id']			    = $data['patient_info']['contact_id'];
                $data['init_patient_address']   = $data['patient_info']['patient_address'];
                $data['init_patient_address2']  = $data['patient_info']['patient_address2'];
                $data['init_patient_address3']  = $data['patient_info']['patient_address3'];
                $data['init_patient_postcode']  = $data['patient_info']['patient_postcode'];
                $data['init_patient_village']       =   NULL;
                $data['init_patient_town']      = $data['patient_info']['patient_town'];
                $data['init_patient_state']     = $data['patient_info']['patient_state'];
                $data['init_patient_country']   = $data['patient_info']['patient_country'];
                $data['init_tel_home']          = $data['patient_info']['tel_home'];
                $data['init_tel_office']        = $data['patient_info']['tel_office'];
                $data['init_tel_mobile']        = $data['patient_info']['tel_mobile'];
                $data['init_pager_no']        	= $data['patient_info']['pager_no'];
                $data['init_fax_no']          	= $data['patient_info']['fax_no'];
                $data['init_email']          	= $data['patient_info']['email'];
                $data['init_contact_other']    	= $data['patient_info']['contact_other'];
                $data['init_contact_remarks']  	= $data['patient_info']['contact_remarks'];
                $data['init_addr_village_id']   = $data['patient_info']['addr_village_id'];
                $data['init_addr_town_id']      = $data['patient_info']['addr_town_id'];
                $data['init_addr_area_id']      = $data['patient_info']['addr_area_id'];
                $data['init_addr_district_id']      = $data['patient_info']['addr_district_id'];
                $data['init_addr_state_id']      = $data['patient_info']['addr_state_id'];
            } //endif ($patient_id == "new_patient")
        } //endif(count($_POST))
		$data['registered_clinic']    = $this->mthirra->get_clinic_info($data['init_clinic_registered']);
        if(!empty($data['registered_clinic'])){
            $data['clinic_registered_name']     =   $data['registered_clinic']['clinic_name'];
        } else {
            $data['clinic_registered_name']     =   "";
        }
        $data['init_patient_area']       = "";
        $data['init_patient_district']   = "";
		$data['village_info']	=	$this->mutil_rdb->get_addr_village_list($data['app_country'],"addr_village_sort",$data['init_addr_village_id']);
        if(!empty($data['village_info'])){
            if(count($data['village_info']) == 1){
                $data['init_addr_town_id']       = $data['village_info'][0]['addr_town_id'];
                $data['init_addr_area_id']       = $data['village_info'][0]['addr_area_id'];
                $data['init_addr_district_id']   = $data['village_info'][0]['addr_district_id'];
                $data['init_addr_state_id']      = $data['village_info'][0]['addr_state_id'];
                $data['init_patient_town']       = $data['village_info'][0]['addr_town_name'];
                $data['init_patient_area']       = $data['village_info'][0]['addr_area_name'];
                $data['init_patient_district']   = $data['village_info'][0]['addr_district_name'];
                $data['init_patient_state']      = $data['village_info'][0]['addr_district_state'];
                $data['init_patient_country']    = $data['village_info'][0]['addr_district_country'];
            }
        } else {
            $data['init_patient_area']       = "";
            $data['init_patient_district']   = "";
        } //endif(count($data['village_info'] > 0))
        if(!empty($data['patient_name']) && ($data['form_purpose'] == "new_patient")){
            $data['duplicate_patient'] = $this->memr_rdb->get_patients_list('all','birth_date',$data['patient_name']);
        }
        $data['birth_date']  =   $data['init_birth_date'];
        $data['age']        =   $data['init_age'];
        
 		$data['title'] = "PR-".$data['patient_info']['name'];
       
		$data['education_levels_list']  = $this->mutil_rdb->get_education_levels();
		$data['addr_country_list']  = $this->mutil_rdb->get_addr_country_list('All',"addr_state_sort");
		$data['addr_state_list']  = $this->mutil_rdb->get_addr_state_list($data['init_patient_country'],"addr_state_sort");
		$data['addr_district_list']  = $this->mutil_rdb->get_addr_district_list($data['init_patient_country'],"addr_district_sort",$data['init_addr_state_id']);
		$data['addr_area_list']  = $this->mutil_rdb->get_addr_area_list($data['init_patient_country'],"addr_area_sort",NULL,$data['init_addr_district_id']);
		$data['addr_town_list']	=	$this->mutil_rdb->get_addr_town_list($data['app_country'],"addr_town_sort",NULL,$data['init_addr_area_id']);
		$data['addr_villages_list']	=	$this->mutil_rdb->get_addr_village_list($data['app_country'],"addr_village_sort",NULL,$data['init_addr_town_id'],$data['init_addr_area_id']);
		$data['family_above']  = $this->memr_rdb->get_family_relations('List','above',$data['patient_id']);
		$data['family_below']  = $this->memr_rdb->get_family_relations('List','below',$data['patient_id']);
        
		$this->load->vars($data);
        // Run validation
        if(($data['app_country'] == "Nepal") && ($data['form_purpose'] == "new_patient")){
            $validate_criteria  =   "edit_patient_unique_refno";
        } else {
            $validate_criteria  =   "edit_patient";
        } //endif($data['app_country'] == "Nepal")
		if ($this->form_validation->run($validate_criteria) == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
				if($data['patient_id'] == "new_patient"){
					$new_sidebar=   "ehr/sidebar_emr_patients_ovrvwNoLink_wap";
				} else {
					$new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
				}
                //$new_body   =   "ehr/ehr_edit_patient_wap";
                $new_body   =   "ehr/ehr_edit_patient_demog_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
				if($data['patient_id'] == "new_patient"){
					$new_sidebar=   "ehr/sidebar_emr_patients_ovrvwNoLink_html";
				} else {
					$new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
				}
                $new_body   =   "ehr/ehr_edit_patient_demog_html";
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
            if($data['patient_id'] == "new_patient") {
                // New patient record
                $ins_patient_array   =   array();
                $ins_patient_array['staff_id']           = $_SESSION['staff_id'];
                $ins_patient_array['now_id']             = $data['now_id'];
                $ins_patient_array['patient_id']         = $data['broken_birth_date']['dd']
                                                            .$data['broken_birth_date']['mm']
                                                            .$data['broken_birth_date']['yyyy']
                                                            .$data['broken_now_date']['dd']
                                                            .$data['broken_now_date']['mm']
                                                            .$data['broken_now_date']['yyyy']
                                                            .$data['now_id'];
                $ins_patient_array['clinic_reference_number']= $data['init_clinic_reference_number'];
                $ins_patient_array['pns_pat_id']         = $data['init_pns_pat_id'];
                $ins_patient_array['nhfa_no']            = $data['init_nhfa_no'];
                $ins_patient_array['patient_name']       = $data['patient_name'];
                $ins_patient_array['name_first']         = $data['name_first'];
                $ins_patient_array['name_alias']         = $data['name_alias'];
                $ins_patient_array['ic_no']              = $data['ic_no'];
                $ins_patient_array['ic_other_no']        = $data['init_ic_other_no'];
                $ins_patient_array['ic_other_type']      = $data['init_ic_other_type'];
                $ins_patient_array['nationality']        = $data['init_nationality'];
                $ins_patient_array['birth_date']         = $data['init_birth_date'];
                $ins_patient_array['birth_cert_no']      = $data['init_birth_cert_no'];
                if($data['broken_now_date']['yyyy'] < 2000){
                    $ins_patient_array['family_link']        = "Independent"; //"Head of Family";
                } else {
                    $ins_patient_array['family_link']        = "Under Head of Family";
                }
                $ins_patient_array['gender']             = $data['gender'];
                $ins_patient_array['ethnicity']          = $data['init_ethnicity'];
                $ins_patient_array['religion']           = $data['init_religion'];
                $ins_patient_array['marital_status']     = $data['init_marital_status'];
                //$ins_patient_array['company']            = $data['init_company'];
                //$ins_patient_array['employee_no']        = $data['init_employee_no'];
                $ins_patient_array['job_function']       = $data['init_job_function'];
                //$ins_patient_array['job_industry']       = $data['init_job_industry'];
                //$ins_patient_array['patient_employment_id']= $data['init_patient_employment_id'];
                $ins_patient_array['education_level']    = $data['init_education_level'];
                $ins_patient_array['patient_type']       = $data['init_patient_type'];
                $ins_patient_array['blood_group']        = $data['init_blood_group'];
                $ins_patient_array['blood_rhesus']       = $data['init_blood_rhesus'];
                if(!empty($data['init_next_of_kin_id'])){
                    $ins_patient_array['next_of_kin_id']              = $data['init_next_of_kin_id'];
                }
                //$ins_patient_array['next_of_kin_id']     = $data['init_next_of_kin_id'];
                $ins_patient_array['next_of_kin_name']   = $data['init_next_of_kin_name'];
                $ins_patient_array['next_of_kin_relationship']= $data['init_next_of_kin_relationship'];
                if($data['init_demise_date']){
                    $ins_patient_array['demise_date']              = $data['init_demise_date'];
                }
                if($data['init_demise_time']){
                    $ins_patient_array['demise_time']              = $data['init_demise_time'];
                }
                $ins_patient_array['demise_cause']       = $data['init_demise_cause'];
                $ins_patient_array['death_cert']         = $data['init_death_cert'];
                $ins_patient_array['clinic_home']        = $data['init_clinic_home'];
                $ins_patient_array['clinic_registered']  = $data['init_location_id'];
                $ins_patient_array['patient_status']     = $data['init_patient_status'];
                $ins_patient_array['location_id']        = $data['init_location_id'];
                $ins_patient_array['patdemo_remarks']    = $data['init_patdemo_remarks'];
                $ins_patient_array['contact_id']         = $data['now_id'];
                $ins_patient_array['patient_correspondence_id']  = $data['now_id'];
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
                $ins_patient_array['pager_no']           = $data['init_pager_no'];
                $ins_patient_array['fax_no']             = $data['init_fax_no'];
                $ins_patient_array['email']           	 = $data['init_email'];
                $ins_patient_array['contact_other']    	 = $data['init_contact_other'];
                $ins_patient_array['contact_remarks'] 	 = $data['init_contact_remarks'];
                $ins_patient_array['addr_village_id']    = $data['init_addr_village_id'];
                $ins_patient_array['addr_town_id']       = $data['init_addr_town_id'];
                $ins_patient_array['addr_area_id']       = $data['init_addr_area_id'];
                $ins_patient_array['addr_district_id']   = $data['init_addr_district_id'];
                $ins_patient_array['addr_state_id']      = $data['init_addr_state_id'];
                $ins_patient_array['patient_immunisation_id']  = $data['now_id'];
                if($data['offline_mode']){
                    $ins_patient_array['synch_out']        = $data['now_id'];
                }
	            $ins_patient_data       =   $this->mehr_wdb->insert_new_patient($ins_patient_array);
                $upd_patient_array['patient_id']         = $ins_patient_array['patient_id'];

            } else {
                // Edit Patient Info
                $upd_patient_array   =   array();
                $upd_patient_array['staff_id']           = $_SESSION['staff_id'];
                $upd_patient_array['now_id']             = $data['now_id'];
                $upd_patient_array['patient_id']         = $data['patient_id'];
                $upd_patient_array['clinic_reference_number']= $data['init_clinic_reference_number'];
                $upd_patient_array['pns_pat_id']         = $data['init_pns_pat_id'];
                $upd_patient_array['nhfa_no']            = $data['init_nhfa_no'];
                $upd_patient_array['patient_name']       = $data['patient_name'];
                $upd_patient_array['name_first']         = $data['name_first'];
                $upd_patient_array['name_alias']         = $data['name_alias'];
                $upd_patient_array['ic_no']              = $data['ic_no'];
                $upd_patient_array['ic_other_no']        = $data['init_ic_other_no'];
                $upd_patient_array['ic_other_type']      = $data['init_ic_other_type'];
                $upd_patient_array['nationality']        = $data['init_nationality'];
                // Not allowing updates to age
                $upd_patient_array['birth_date']         = $data['init_birth_date'];
                $upd_patient_array['birth_cert_no']      = $data['init_birth_cert_no'];
                $upd_patient_array['family_link']        = "Head of Family";
                $upd_patient_array['gender']             = $data['gender'];
                $upd_patient_array['ethnicity']          = $data['init_ethnicity'];
                $upd_patient_array['religion']           = $data['init_religion'];
                $upd_patient_array['marital_status']     = $data['init_marital_status'];
                //$upd_patient_array['company']            = $data['init_company'];
                //$upd_patient_array['employee_no']        = $data['init_employee_no'];
                $upd_patient_array['job_function']       = $data['init_job_function'];
                //$upd_patient_array['job_industry']       = $data['init_job_industry'];
                //$upd_patient_array['patient_employment_id']= $data['init_patient_employment_id'];
                $upd_patient_array['education_level']    = $data['init_education_level'];
                $upd_patient_array['patient_type']       = $data['init_patient_type'];
                $upd_patient_array['blood_group']        = $data['init_blood_group'];
                $upd_patient_array['blood_rhesus']       = $data['init_blood_rhesus'];
                if(!empty($data['init_next_of_kin_id'])){
                    $upd_patient_array['next_of_kin_id']              = $data['init_next_of_kin_id'];
                }
                //$upd_patient_array['next_of_kin_id']     = $data['init_next_of_kin_id'];
                $upd_patient_array['next_of_kin_name']   = $data['init_next_of_kin_name'];
                $upd_patient_array['next_of_kin_relationship']= $data['init_next_of_kin_relationship'];
                if($data['init_demise_date']){
                    $upd_patient_array['demise_date']              = $data['init_demise_date'];
                }
                if($data['init_demise_time']){
                    $upd_patient_array['demise_time']              = $data['init_demise_time'];
                }
                $upd_patient_array['demise_cause']       = $data['init_demise_cause'];
                $upd_patient_array['death_cert']         = $data['init_death_cert'];
                $upd_patient_array['clinic_home']        = $data['init_clinic_home'];
                $upd_patient_array['clinic_registered']  = $data['init_clinic_registered'];
                $upd_patient_array['patient_status']     = $data['init_patient_status'];
                $upd_patient_array['location_id']        = $data['init_location_id'];
                $upd_patient_array['patdemo_remarks']    = $data['init_patdemo_remarks'];
                $upd_patient_array['update_when']        = $data['now_id'];
                $upd_patient_array['update_by']          = $_SESSION['staff_id'];
                if($data['offline_mode']){
                    if(!empty($data['init_synch_out'])){
                        // New patient updated offline - do nothing
                        //$upd_patient_array['synch_out']        = $data['now_id'];
                    } else {
                        // Old patient updated offline
                        $upd_patient_array['synch_in']        = $data['now_id'];
                    }
                }
                $upd_patient_array['contact_id']         = $data['contact_id'];
                $upd_patient_array['correspondence_id']  = $data['contact_id'];
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
                $upd_patient_array['pager_no']           = $data['init_pager_no'];
                $upd_patient_array['fax_no']             = $data['init_fax_no'];
                $upd_patient_array['email']           	 = $data['init_email'];
                $upd_patient_array['contact_other']    	 = $data['init_contact_other'];
                $upd_patient_array['contact_remarks'] 	 = $data['init_contact_remarks'];
                $upd_patient_array['addr_village_id']    = $data['init_addr_village_id'];
                $upd_patient_array['addr_town_id']       = $data['init_addr_town_id'];
                $upd_patient_array['addr_area_id']       = $data['init_addr_area_id'];
                $upd_patient_array['addr_district_id']   = $data['init_addr_district_id'];
                $upd_patient_array['addr_state_id']      = $data['init_addr_state_id'];
	            $upd_patient_data       =   $this->mehr_wdb->update_patient_info($upd_patient_array);
           } //endif($data['patient_id'] == "new_patient")
            echo form_open('ehr_individual/individual_overview/'.$upd_patient_array['patient_id']);
            echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
            echo "Saved. <input type='submit' value='Click to Continue' />";
            echo "</form>";
        }
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_patient()


    // ------------------------------------------------------------------------
    function hardcopy_patient_booklet()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
	  	$this->load->model('mthirra');
        $data['location_id']    =   $_SESSION['location_id'];
	  	
		$data['title'] = 'Patient Booklet';
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['patient_id'] = $this->uri->segment(3);
		//$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
		$data['clinic_info']    = $this->mthirra->get_clinic_info($_SESSION['location_id']);
		$data['allergy_list']  = $this->memr_rdb->get_drug_allergies('List',$data['patient_id']);
		$data['social_history']  = $this->memr_rdb->get_history_social('List',$data['patient_id']);
        if(count($data['social_history']) < 1){
            $data['social_history'][0]['drugs_use']  =   "Unknown";
            $data['social_history'][0]['drugs_spec']  =   "";
            $data['social_history'][0]['alcohol_use']  =   "Unknown";
            $data['social_history'][0]['alcohol_spec']  =   "";
            $data['social_history'][0]['tobacco_use']  =   "Unknown";
            $data['social_history'][0]['tobacco_spec']  =   " ";
        }
        if($data['debug_mode']){
            $this->output->enable_profiler(TRUE);  
        }
		$this->load->vars($data);

        // Run validation and close episode if successfully validated.
		if ($this->form_validation->run('edit_episode') == FALSE){
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
                $new_sidebar=   "ehr/sidebar_emr_patients_conslt_wap";
                $new_body   =   "ehr/ehr_hardcopy_patient_booklet_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_print_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_hardcopy_patient_booklet_html";
                $new_footer =   "ehr/footer_emr_html";
            }
			// Output Format
			$data['output_format'] 	= $this->uri->segment(4);
			$data['filename']		=	"THIRRA-".$data['patient_id'].".pdf";
			if($data['output_format'] == 'pdf') {
				$html = $this->load->view($new_header,'',TRUE);			
				$html .= $this->load->view($new_banner,'',TRUE);			
				//$this->load->view($new_sidebar);			
				$html .= $this->load->view($new_body,'',TRUE);			
				//$html .= $this->load->view($new_footer,'',TRUE);		

				$this->load->library('mpdf');
				$mpdf=new mPDF('win-1252','A5','','',20,15,5,25,10,10);
				$mpdf->useOnlyCoreFonts = true;    // false is default
				$mpdf->SetProtection(array('print'));
				$mpdf->SetTitle("THIRRA - Consultation Episode");
				$mpdf->SetAuthor("THIRRA");
				//$mpdf->SetWatermarkText("Paid");
				//$mpdf->showWatermarkText = true;
				//$mpdf->watermark_font = 'DejaVuSansCondensed';
				//$mpdf->watermarkTextAlpha = 0.1;
				$mpdf->SetDisplayMode('fullpage');
				$mpdf->WriteHTML($html);

				$mpdf->Output($data['filename'],'I'); exit;
			} else { // display in browser
				$this->load->view($new_header);			
				$this->load->view($new_banner);			
				//$this->load->view($new_sidebar);			
				$this->load->view($new_body);			
				$this->load->view($new_footer);		
			} //endif($data['output_format'] == 'pdf')
			
        } else {
            echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
                
                // Copy patient_diagnosis to patient_medical_history
                // Billing
                // Set new appointment
            $new_page = base_url()."index.php/ehr_patient/close_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_episode') == FALSE)
    } // end of function hardcopy_patient_booklet()


    // ------------------------------------------------------------------------
    function past_con_details($summary_id)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_name']		    =	$this->config->item('app_name');
	  	$this->load->model('mthirra');
	  	
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        if(isset($_POST['form_purpose'])) { 
            $data['form_purpose']   =   $_POST['form_purpose'];
        }
            $data['patient_id'] = $this->uri->segment(3);
            $data['summary_id'] = $this->uri->segment(4);
            $data['date_ended']     =   $data['now_date'];
            $data['time_ended']     =   $data['now_time'];
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
 		$data['title'] = "PC-".$data['patient_info']['name'];
		$data['clinic_info']    = $this->mthirra->get_clinic_info($data['patcon_info']['location_end']);
        $data['complaints_list']= $this->memr_rdb->get_patcon_complaints($data['summary_id']);
        $data['vitals_info']    = $this->memr_rdb->get_patcon_vitals($data['summary_id']);
        $data['physical_info']  = $this->memr_rdb->get_patcon_physical($data['summary_id']);
        $data['lab_list']       = $this->memr_rdb->get_patcon_lab($data['summary_id']);
        $data['imaging_list']   = $this->memr_rdb->get_patcon_imaging($data['summary_id']);
        $data['prediagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id'],NULL,NULL,TRUE);
        $data['diagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id']);
        $data['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id']);
        $data['referrals_list'] = $this->memr_rdb->get_patcon_referrals($data['summary_id']);
		$data['social_history']  = $this->memr_rdb->get_history_social('List',$data['patient_id']);
        $data['last_episode']   = $this->memr_rdb->get_last_session_reference();
        /*
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        */
        if($data['debug_mode']){
            $this->output->enable_profiler(TRUE);  
        }
        $data['output_format'] 	= $this->uri->segment(5);
		$this->load->vars($data);

        if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_print_html";
            $new_sidebar=   "ehr/sidebar_emr_patients_conslt_wap";
            //$new_body   =   "ehr/emr_past_con_details_wap";
            $new_body   =   "ehr/ehr_reports_consult_details_html";
            $new_footer =   "ehr/footer_emr_wap";
        } else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_print_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_reports_consult_details_html";
            $new_footer =   "ehr/footer_emr_html";
        }
        // Output Format
        $patient_name   =   str_replace(" ","", $data['patient_info']['name']);
        $app_name       =   str_replace(" ","",$data['app_name']);
        $consult_start  =   str_replace("-","",$data['patcon_info']['date_started']);
        $consult_time   =   substr(str_replace(":","",$data['patcon_info']['time_started']),0,4);

        $data['filename']		=	$app_name."_CR-".$patient_name."-".$consult_start."-".$consult_time.".pdf";
        if($data['output_format'] == 'pdf') {
            $html = $this->load->view($new_header,'',TRUE);			
            $html .= $this->load->view($new_banner,'',TRUE);			
            //$this->load->view($new_sidebar);			
            $html .= $this->load->view($new_body,'',TRUE);			
            //$html .= $this->load->view($new_footer,'',TRUE);		

            $this->load->library('mpdf');
            $mpdf=new mPDF('win-1252','A4','','',20,15,5,25,10,10);
            $mpdf->useOnlyCoreFonts = true;    // false is default
            $mpdf->SetProtection(array('print'));
            $mpdf->SetTitle("THIRRA - Consultation Episode");
            $mpdf->SetAuthor("THIRRA");
            //$mpdf->SetWatermarkText("Paid");
            //$mpdf->showWatermarkText = true;
            //$mpdf->watermark_font = 'DejaVuSansCondensed';
            //$mpdf->watermarkTextAlpha = 0.1;
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->WriteHTML($html);
//$mpdf->AddPage();
//$mpdf->WriteHTML('Your Book text');

            $mpdf->Output($data['filename'],'I'); exit;
        } else { // display in browser
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            //$this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);		
        } //endif($data['output_format'] == 'pdf')
            
    } // end of function past_con_details($summary_id)


    // ------------------------------------------------------------------------
    function list_family_cluster()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['patient_id']     = $this->uri->segment(3);
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['family_link']     =   $data['patient_info']['family_link'];
        if($data['family_link'] == "Head of Family"){
            //echo "Head";
            $data['head_info'][0]  = $data['patient_info'];
            $data['family_head']  = $data['patient_id'];
            $data['family_cluster']  = $this->memr_rdb->get_family_relations('List','below',$data['patient_id']);
        } elseif($data['family_link'] == "Under Head of Family") {
            //echo "Not Head";
            // Find out who is the head
            $data['head_info']  = $this->memr_rdb->get_family_head($data['patient_id']);
            // Was family relationship created earlier?
            if(!empty($data['head_info'])){
                $data['family_head']  = $data['head_info'][0]['patient_id'];
            } else {
                $data['family_head']  = array();
            } //endif(!empty($data['head_info']))
            $data['family_cluster']  = $this->memr_rdb->get_family_relations('List','above',$data['patient_id']);
        } else {
            //echo "Independent";
            // Find out who is the head
            $data['head_info']  = $this->memr_rdb->get_family_head($data['patient_id']);
            if(!empty($data['head_info'])){
                $data['family_head']  = $data['head_info'][0]['patient_id'];
            } else {
                $data['family_head']  = array();
            } //endif(!empty($data['head_info']))
            $data['family_cluster']  = $this->memr_rdb->get_family_relations('List','above',$data['patient_id']);
        }
        $data['family_cluster']  = $this->memr_rdb->get_family_relations('List','below',$data['family_head']);
		$data['family_above']  = $this->memr_rdb->get_family_relations('List','above',$data['patient_id']);
		$data['family_below']  = $this->memr_rdb->get_family_relations('List','below',$data['patient_id']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            $new_body   =   "ehr/ehr_indv_list_family_cluster_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_list_family_cluster_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function list_family_relations()


    // ------------------------------------------------------------------------
    function edit_relationship_info($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['form_purpose']       =   $this->uri->segment(3);
		$data['patient_id']         =   $this->uri->segment(4);
		$data['relationship_id']    =   $this->uri->segment(5);
        $data['location_id']        =   $_SESSION['location_id'];
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['heads_list']  = $this->memr_rdb->get_patientby_family_type('Head of Family');
        if($data['form_purpose'] == "new_relation"){
            $data['independents_list']  = $this->memr_rdb->get_patientby_family_type('Independent');
        } else {
            $data['independents_list']  = $this->memr_rdb->get_patientby_family_type('Under Head of Family',$data['patient_id']);
        }
        
        if(count($_POST)) {
            // User has posted the form
            //$data['social_history_id']    =   $_POST['social_history_id'];
            $data['init_head_id']           =   $_POST['head_id'];
            $data['head_id']                =   $data['init_head_id'];
            $data['dbhead_id']              =   $_POST['dbhead_id'];
            $data['family_details_id']      =   $_POST['family_details_id'];
            $data['init_family_position']   =   $_POST['family_position'];
            $data['init_remarks']           =   $_POST['remarks'];
            //$data['init_generation_to_head']        =   $_POST['generation_to_head'];
            //$data['init_date_married']        =   $_POST['date_married'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_relation") {
                // New user
		        $data['relationship_info']      =  array();
                $data['init_family_position']   =   "";
                $data['init_remarks']           =   "";
                $data['init_generation_to_head']=   1;
                $data['family_details_id']      =   "";
                $data['dbhead_id']              =   "";
                //$data['init_date_married']    =   "";
            } else {
                // Existing user
                $data['relationship_info']      = $this->memr_rdb->get_relationship_info($data['patient_id'],$data['relationship_id']);
                $data['init_head_id']           =   $data['relationship_info'][0]['head_id'];
                $data['dbhead_id']              =   $data['init_head_id'];
                $data['head_id']                =   $data['init_head_id'];
                $data['family_details_id']      =   $data['relationship_info'][0]['family_details_id'];
                $data['init_family_position']   =   $data['relationship_info'][0]['family_position'];
                $data['init_remarks']           =   $data['relationship_info'][0]['remarks'];
                $data['init_generation_to_head']=   $data['relationship_info'][0]['generation_to_head'];
                //$data['init_date_married']    =   $data['relationship_info'][0]['date_married'];
           } //endif ($data['form_purpose'] == "new_relation")
        } //endif(count($_POST))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_relationship_info') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_emr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                $new_body   =   "ehr/ehr_indv_edit_relationship_info_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_indv_edit_relationship_info_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_queue'] < 100){
                $new_body   =   "ehr/ehr_access_denied_html";
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
            if(  $data['init_family_position'] == "Grandfather"
              || $data['init_family_position'] == "Grandmother"
              || $data['init_family_position'] == "Grand uncle"
              || $data['init_family_position'] == "Grand aunt"
              || $data['init_family_position'] == "Grandparent"
            ){
                $data['init_generation_to_head'] =   -2;
            }
            if(  $data['init_family_position'] == "Father"
              || $data['init_family_position'] == "Mother"
              || $data['init_family_position'] == "Uncle"
              || $data['init_family_position'] == "Aunt"
              || $data['init_family_position'] == "Parent"
              || $data['init_family_position'] == "Guardian"
            ){
                $data['init_generation_to_head'] =   -1;
            }
            if(  $data['init_family_position'] == "Husband"
              || $data['init_family_position'] == "Wife"
              || $data['init_family_position'] == "Spouse"
              || $data['init_family_position'] == "Brother"
              || $data['init_family_position'] == "Sister"
              || $data['init_family_position'] == "Sibling"
              || $data['init_family_position'] == "Cousin"
            ){
                $data['init_generation_to_head'] =   0;
            }
            if(  $data['init_family_position'] == "Son"
              || $data['init_family_position'] == "Daughter"
              || $data['init_family_position'] == "Child"
              || $data['init_family_position'] == "Nephew"
              || $data['init_family_position'] == "Niece"
              || $data['init_family_position'] == "Minor"
            ){
                $data['init_generation_to_head'] =   1;
            }
            
            if(  $data['init_family_position'] == "Grandson"
              || $data['init_family_position'] == "Granddaughter"
              || $data['init_family_position'] == "Grandchild"
              || $data['init_family_position'] == "Grand nephew"
              || $data['init_family_position'] == "Grand niece"
            ){
                $data['init_generation_to_head'] =   2;
            }
            
            if($data['form_purpose'] == "new_relation") {
                // Insert records
                $ins_relation_array['relationship_id']  = $data['now_id'];
                $ins_relation_array['patient_id']       = $data['patient_id'];
                $ins_relation_array['staff_id']         = $_SESSION['staff_id'];
                $ins_relation_array['head_id']          = $data['init_head_id'];
                $ins_relation_array['family_details_id']= $data['now_id'];
                $ins_relation_array['family_position']  = $data['init_family_position'];
                $ins_relation_array['remarks']          = $data['init_remarks'];
                $ins_relation_array['generation_to_head']= $data['init_generation_to_head'];
                //$ins_relation_array['date_married']    = $data['init_date_married'];
                $ins_relation_data =   $this->mehr_wdb->insert_new_relationship($ins_relation_array);
                $this->session->set_flashdata('data_activity', 'Relationship added.');
            } else {
                // Update records
                $upd_relation_array['family_details_id']= $data['family_details_id'];
                $upd_relation_array['family_position']  = $data['init_family_position'];
                $upd_relation_array['remarks']      = $data['init_remarks'];
                $upd_relation_array['generation_to_head']  = $data['init_generation_to_head'];
                //$upd_relation_array['date_married']  = $data['init_date_married'];
                $upd_relation_array['edit_remarks'] = $data['init_edit_remarks'];
                $upd_relation_array['edit_staff']   = $data['init_edit_staff'];
                $upd_relation_array['edit_date']    = $data['init_edit_date'];
                $upd_relation_array['relationship_id']    = $data['relationship_id'];
                $upd_relation_array['head_id']      = $data['init_head_id'];
                $upd_relation_array['dbhead_id']    = $data['dbhead_id'];
                $upd_relation_data =   $this->mehr_wdb->update_relationship_info($upd_relation_array);
                $this->session->set_flashdata('data_activity', 'Relationship updated.');
            } //endif($data['form_purpose'] == "new_relation")

            $new_page = base_url()."index.php/ehr_individual/list_family_cluster/".$data['patient_id'];
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_relation') == FALSE)
    } // end of function edit_relationship_info($id)


    // ------------------------------------------------------------------------
    function change_family_link_type($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['form_purpose']       =   $this->uri->segment(3);
        $data['patient_id']         =   $this->uri->segment(4);
        $data['relationship_type']  =   $this->uri->segment(5);

        // Update records
        switch ($data['relationship_type']){
            case "head":
			    $data['family_link'] = "Head of Family"; 
                break;			
            case "independent":
			    $data['family_link'] = "Independent";
                break;			
            case "under":
			    $data['family_link'] = "Under Head of Family";
                break;			
        } //end switch ($data['relationship_type'])
        //echo $data['relationship_type'];
        //echo $data['family_link'];
        $upd_relation_array['patient_id']       = $data['patient_id'];
        $upd_relation_array['family_link']      = $data['family_link'];
        $upd_relation_data =   $this->mehr_wdb->update_patdemo_familylink($upd_relation_array);
        $new_page = base_url()."index.php/ehr_individual/list_family_cluster/".$data['patient_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function change_family_link_type($id)


    // ------------------------------------------------------------------------
    function delete_family_relationship($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['form_purpose']       =   $this->uri->segment(3);
        $data['patient_id']         =   $this->uri->segment(4);
        $data['relationship_id']    =   $this->uri->segment(5);
        
        // Delete records
        $del_rec_array['patient_id']        = $data['patient_id'];
        $del_rec_array['relationship_id']   = $data['relationship_id'];
        $del_rec_data =   $this->mehr_wdb->delete_family_relationship($del_rec_array);
        $new_page = base_url()."index.php/ehr_individual/list_family_cluster/".$data['patient_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function delete_family_relationship($id)


    // ------------------------------------------------------------------------
    function list_family_relations()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['patient_id']     = $this->uri->segment(3);
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
		$data['title'] = "PR-".$data['patient_info']['name'];
		$data['family_above']  = $this->memr_rdb->get_family_relations('List','above',$data['patient_id']);
		$data['family_below']  = $this->memr_rdb->get_family_relations('List','below',$data['patient_id']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            $new_body   =   "ehr/ehr_indv_list_family_relations_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_list_family_relations_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function list_family_relations()


    // ------------------------------------------------------------------------
    function list_drug_allergies()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['patient_id']     = $this->uri->segment(3);
       $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
		$data['allergy_list']  = $this->memr_rdb->get_drug_allergies('List',$data['patient_id']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            $new_body   =   "ehr/ehr_indv_list_drug_allergies_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_list_drug_allergies_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function list_drug_allergies()


    // ------------------------------------------------------------------------
    // Add/Edit Drug Allergy
    function edit_drug_allergy()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('mutil_rdb');
		$this->load->model('mpharma_rdb');
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['button'] = (isset($_POST['part_form']) ? $_POST['part_form'] : $_POST['submit']);
            $data['form_purpose']    = $_POST['form_purpose'];
            $data['form_id']         = $_POST['form_id'];
            $data['patient_id']      = $_POST['patient_id'];
            $data['patient_drug_allergy_id']   = $_POST['patient_drug_allergy_id'];
            $data['formulary_term1'] = $_POST['formulary_term1'];
            $data['formulary_term2'] = $_POST['formulary_term2'];
            $data['formulary_pullall']= FALSE;
            $data['drug_formulary_id']       = $_POST['drug_formulary_id'];
            if($data['button'] == "Search"){
                if(strlen($data['formulary_term1'])>2){
                    $data['formulary_filter'] = $this->mpharma_rdb->get_formulary_list($data['formulary_pullall'],$data['formulary_term1'],$data['formulary_term2']);
                } elseif(strlen($data['formulary_term2'])>2){
                    $data['formulary_filter'] = $this->mpharma_rdb->get_formulary_list($data['formulary_pullall'],$data['formulary_term1'],$data['formulary_term2']);
                }
            } //endif($data['button'] == "Search")
            if(isset($_POST['drug_code_id'])) { 
                $data['drug_code_id']   =   $_POST['drug_code_id'];
            } else {
                $data['drug_code_id']   =   "none";
            }
            if(isset($_POST['drug_batch'])) { 
                $data['drug_batch']   =   $_POST['drug_batch'];
            } else {
                $data['drug_batch']   =   "none";
            }
            // Check whether any search result was returned, if searched.
            if(! isset($data['formulary_filter'])){ // If none returned
                $data['formulary_filter']= array();
            } //endif(! isset($data['formulary_filter']))
            $data['reaction']           = $_POST['reaction'];
            $data['added_remarks']      = $_POST['added_remarks'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['patient_id']     = $this->uri->segment(4);
            $data['patient_drug_allergy_id']     = $this->uri->segment(5);
            $data['queue_id']   = $this->uri->segment(6);
            $data['formulary_term1'] = "none";
            $data['formulary_term2'] = "none";
            $data['formulary_filter']= array();
            $data['init_patient_id']    =   $data['patient_id'];
            $data['button']          = "";
            $data['form_id']         = "";
            if ($data['form_purpose'] == "new_allergy") {
                //echo "New diagnosis";
                $data['prescribe_id']       =   $data['now_id'];
                $data['drug_formulary_id']          =   "";
                $data['drug_formulary']         =   "";
                $data['generic_name']               =   "";
                $data['reaction']          =   "";
                $data['added_remarks']          =   "";
            } elseif ($data['form_purpose'] == "edit_allergy") {
                //echo "Edit allergy";
                $data['prescribe_info'] = $this->memr_rdb->get_drug_allergies('List',$data['patient_id'],$data['patient_drug_allergy_id']);
                $data['drug_formulary']  =   $data['prescribe_info'][0]['drug_formulary'];
                $data['generic_name']       =   $data['prescribe_info'][0]['generic_name'];
                $data['reaction']        =   $data['prescribe_info'][0]['reaction'];
                $data['added_remarks']        =   $data['prescribe_info'][0]['added_remarks'];
            } //endif ($data['form_purpose'] == "new_prescribe")
        } //endif(count($_POST))
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['allergies_list'] = $this->memr_rdb->get_drug_allergies('List',$data['patient_id']);
        $data['formulary_common'] = $this->mpharma_rdb->get_common_formulary();
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        if(isset($data['drug_formulary_id'])){
		    $data['tradename_list'] = $this->mpharma_rdb->get_tradename_by_formulary($data['drug_formulary_id']);
            $data['formulary_chosen'] = $this->mpharma_rdb->get_one_drug_formulary($data['drug_formulary_id']);
        } else {
            $data['tradename_list'] = array();
        } //endif(isset($data['drug_formulary_id']))
        if(isset($data['drug_code_id'])){
            $data['drugcode_info']  = $this->mutil_rdb->get_drug_code_list('data','drug_code',1,0,$data['drug_code_id']);
        } else {
            $data['drugcode_info'] = array();
        } //endif(isset($data['c']))

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_drug_allergy') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                $new_body   =   "ehr/ehr_indv_edit_drug_allergy_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_indv_edit_drug_allergy_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            $this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            //echo "\nValidated successfully.";
            //echo "<br />Insert record";
                // New drug_allergy
                $ins_allergy_array   =   array();
                $ins_allergy_array['patient_drug_allergy_id']  = $data['now_id'];
                $ins_allergy_array['allergy_active']    = "TRUE";
                $ins_allergy_array['log_id']            = $data['now_id'];
                $ins_allergy_array['patient_id']        = $data['patient_id'];
                $ins_allergy_array['drug_code']         = $data['drugcode_info'][0]['drug_code'];
                $ins_allergy_array['drug_formulary']    = $data['drugcode_info'][0]['drug_formulary_code'];
                $ins_allergy_array['atc_code']          = $data['drugcode_info'][0]['atc_code'];
                $ins_allergy_array['reaction']          = $data['reaction'];
                $ins_allergy_array['added_remarks']     = $data['added_remarks'];
                $ins_allergy_array['added_staff']       = $_SESSION['staff_id'];
                $ins_allergy_array['added_date']        = $data['now_date'];
                $ins_allergy_array['generic_drugname']  = $data['drugcode_info'][0]['generic_name'];
                $ins_allergy_array['drug_tradename']    = $data['drugcode_info'][0]['trade_name'];
               if($data['offline_mode']){
                    $ins_allergy_array['synch_out']   = $data['now_id'];
                }
	            $ins_allergy_data       =   $this->mehr_wdb->insert_new_drugallergy($ins_allergy_array);
            $new_page = base_url()."index.php/ehr_individual/edit_drug_allergy/new_allergy/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_drug_allergy') == FALSE)

    } // end of function edit_drug_allergy()


    // ------------------------------------------------------------------------
    function edithist_immune_select($id=NULL)  // patient immunisation
	{
		$this->load->model('mconsult_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
		
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['now_id']         = $_POST['now_id'];
            $data['patient_id']     = $_POST['patient_id'];
			$data['summary_id']     = $this->uri->segment(5);
            if(isset($_POST['vaccine_id'])){
                $data['vaccine_id']	    = $_POST['vaccine_id'];
            } else {
				$data['vaccine_id'] 		= "";
            }
            $data['vaccine_date']	= $_POST['vaccine_date'];
            $data['vaccine_notes']  = $_POST['vaccine_notes'];
        } else {
			// First time form is displayed
			$data['form_purpose']   = $this->uri->segment(3);
			$data['patient_id']     = $this->uri->segment(4);
			$data['summary_id']     = $this->uri->segment(5);
			//$data['prescribe_id']   = $this->uri->segment(6);
			$patient_id             =   $this->uri->segment(4);
			$data['patient_id']     =   $patient_id;
			$data['now_id']             =   time();
			$data['now_date']           =   date("Y-m-d",$data['now_id']);
			if ($data['form_purpose'] == "new_immune") {
				//echo "New prescription";
				$data['prescribe_id']       =   $data['now_id'];
				$data['drug_system']        =   "";
				$data['drug_formulary_id']  =   "";
				$data['drug_code_id']       =   "";
				$data['drug_batch']         =   "";
				$data['dose']               =   "";
				$data['dose_form']          =   "";
				$data['frequency']          =   "";
				$data['instruction']        =   "";
				$data['quantity']           =   "";
				$data['indication']         =   "";
				$data['caution']            =   "";
				$data['vaccine_id'] 		= "";
				$data['patient_immunisation_id'] = "new_immune";
				$data['vaccine_date']       =   "";
				$data['vaccine_notes']      =   "";
			} elseif ($data['form_purpose'] == "edit_immune") {
				//echo "Edit prescription";
				$data['prescribe_info'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id'],$data['prescribe_id']);
				$data['drug_system']        =   $data['prescribe_info'][1]['formulary_system'];
				$data['drug_formulary_id']  =   $data['prescribe_info'][1]['drug_formulary_id'];
				$data['generic_name']       =   $data['prescribe_info'][1]['generic_name'];
				$data['drug_code_id']       =   "";
				$data['drug_batch']         =   "";
				$data['dose']               =   $data['prescribe_info'][1]['dose'];
				$data['dose_form']          =   $data['prescribe_info'][1]['dose_form'];
				$data['frequency']          =   $data['prescribe_info'][1]['frequency'];
				$data['instruction']        =   $data['prescribe_info'][1]['instruction'];
				$data['quantity']           =   $data['prescribe_info'][1]['quantity'];
				$data['indication']         =   $data['prescribe_info'][1]['indication'];
				$data['caution']            =   $data['prescribe_info'][1]['caution'];
			} //endif ($data['form_purpose'] == "new_prescribe")
        } //endif(count($_POST))
			
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        //$data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['vaccines_list'] 	= $this->memr_rdb->get_vaccines_list($data['patient_id'],999,0);
		// This array can be refactored to be automatic
		$data['vaccination_table']	= array(0,1,2,3,4,5,6,12,18,24);		
        //$data['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        //$data['init_patient_id']    =   $patient_id;

 		$data['title'] = "PR-".$data['patient_info']['name'];
		$this->load->vars($data);
		
        // Run validation
		if ($this->form_validation->run('edit_history_immune') == FALSE){
			if ($_SESSION['thirra_mode'] == "ehr_mobile"){
				$new_header =   "ehr/header_xhtml-mobile10";
				$new_banner =   "ehr/banner_ehr_ovrvw_wap";
				$new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
				//$new_body   =   "ehr/ehr_edithist_immune_select_wap";
				$new_body   =   "ehr/ehr_edithist_immune_select_html";
				$new_footer =   "ehr/footer_emr_wap";
			} else {
				//$new_header =   "ehr/header_xhtml1-strict";
				$new_header =   "ehr/header_xhtml1-transitional";
				$new_banner =   "ehr/banner_ehr_ovrvw_html";
				$new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
				$new_body   =   "ehr/ehr_edithist_immune_select_html";
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
            if($data['form_purpose'] == "new_immune") {
                // New prescription record
                $ins_prescribe_array   =   array();
                $ins_prescribe_array['staff_id']         = $_SESSION['staff_id'];
                $ins_prescribe_array['now_id']           = $data['now_id'];
                $ins_prescribe_array['patient_id']       = $data['patient_id'];
                $ins_prescribe_array['patient_immunisation_id']= $data['now_id'];
                $ins_prescribe_array['immunisation_id']  = $data['vaccine_id'];
                $ins_prescribe_array['vaccine_date']     = $data['vaccine_date'];
                $ins_prescribe_array['vaccine_notes']    = $data['vaccine_notes'];
                $ins_prescribe_array['status']           = "Unconfirmed";//$data['remarks'];
                if($data['offline_mode']){
                    $ins_prescribe_array['synch_out']        = $data['now_id'];
                }
	            //$ins_prescribe_prescribe       =   $this->memr_wdb->insert_new_prescribe($ins_prescribe_array);
	            $ins_prescribe_vaccine       =   $this->mconsult_wdb->insert_new_vaccine($ins_prescribe_array);
                $this->session->set_flashdata('data_activity', 'Vaccination recorded.');
            } elseif($data['form_purpose'] == "edit_immune") {
                // Existing prescription record
                $upd_prescribe_array   =   array();
                $upd_prescribe_array['staff_id']        = $_SESSION['staff_id'];
                $upd_prescribe_array['now_id']          = $data['now_id'];
                $upd_prescribe_array['queue_id']        = $data['prescribe_id'];
                $upd_prescribe_array['vaccine_id']      = $data['vaccine_id'];
                $upd_prescribe_array['patient_id']      = $data['patient_id'];
                $upd_prescribe_array['patient_immunisation_id']      = $data['now_id'];
                $upd_prescribe_array['vaccine_date']      = $data['vaccine_date'];
                $upd_prescribe_array['vaccine_notes']      = $data['vaccine_notes'];
                $upd_prescribe_array['status']          = "Unconfirmed";//$data['remarks'];
	            $upd_prescribe_data       =   $this->mconsult_wdb->update_prescription($upd_prescribe_array);
            } //endif($data['form_purpose'] == "new_prescribe")
            $new_page = base_url()."index.php/ehr_individual/edithist_immune_select/new_immune/".$data['patient_id']."/new_immune";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_prescribe') == FALSE)
    } // endfunction edithist_immune_select($id=NULL)  // patient immunisation


    // ------------------------------------------------------------------------
    function histdel_immune_confirm($id=NULL)  // patient immunisation
	{
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
		
        //$data['form_purpose']   = $this->uri->segment(3);
        $data['patient_id']             = $this->uri->segment(3);
        $data['patient_immunisation_id']= $this->uri->segment(4);
        $data['prescribe_id']   = $this->uri->segment(5);
			
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        //$data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['vaccines_list'] 	= $this->memr_rdb->get_patient_immunisation($data['patient_id'], 0);
		// This array can be refactored to be automatic
		$data['vaccination_table']	= array(0,1,2,3,4,5,6,12,18,24);		
        //$data['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        //$data['init_patient_id']    =   $patient_id;

 		$data['title'] = "PR-".$data['patient_info']['name'];
		$this->load->vars($data);
		
        if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            //$new_body   =   "ehr/ehr_histdel_immune_confirm_wap";
            $new_body   =   "ehr/ehr_histdel_immune_confirm_html";
            $new_footer =   "ehr/footer_emr_wap";
        } else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_histdel_immune_confirm_html";
            $new_footer =   "ehr/footer_emr_html";
        }
        $this->load->view($new_header);			
        $this->load->view($new_banner);			
        $this->load->view($new_sidebar);			
        $this->load->view($new_body);			
        $this->load->view($new_footer);	
            
    } // endfunction histdel_immune_confirm($id=NULL)  // patient immunisation


    // ------------------------------------------------------------------------
    function histdel_immune_exec($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['patient_id']                 =   $this->uri->segment(3);
        //$data['patient_immunisation_id']    =   $this->uri->segment(4);
        $data['patient_immunisation_id']	= $_POST['patient_immunisation_id'];
        
        // Delete records
        $del_immune_array['patient_immunisation_id']      = $data['patient_immunisation_id'];
        $del_immune_data =   $this->mehr_wdb->delete_patient_immunisation($del_immune_array);
        $this->session->set_flashdata('data_activity', 'Vaccination deleted.');
        $new_page = base_url()."index.php/ehr_individual/edithist_immune_select/new_immune/".$data['patient_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function reports_delete_reportbody($id)


    // ------------------------------------------------------------------------
    function list_patient_files() // Deprecated
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['patient_id']     = $this->uri->segment(3);
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
		$data['history_list']  = $this->memr_rdb->get_history_social('List',$data['patient_id']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            //$new_body   =   "ehr/ehr_indv_list_history_vitals_wap";
            $new_body   =   "ehr/ehr_indv_list_patient_files_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_list_patient_files_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function list_patient_files()


    // ------------------------------------------------------------------------
    function upload_pics_ovv($patient_id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	$this->load->model('mbio');
		$this->load->model('mehr_wdb');
		$data['form_purpose']       =   $this->uri->segment(3);
		$data['patient_id']         =   $this->uri->segment(4);
		$data['summary_id']         =   $this->uri->segment(5);
	    $data['allowed_types']      =   'bmp|gif|jpg|png|pdf|doc';
	    $data['max_size']	        =   '4096';
        $data['max_width']          =   '3200';
        $data['max_height']         =   '3200';
	    $data['files_list']         =   $this->memr_rdb->get_files_list($data['patient_id']);
        $data['pics_url']           =    base_url();
        /*
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/patient_pics/";
        */
        $data['pics_url']       =    substr_replace($data['pics_url'],'',-1);
        $data['pics_url']       =    $data['pics_url']."-uploads/";
        $data['now_id']         =   time();
        $data['patient_info']   =   $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['upload_type']     =   "";
        $data['rotate_pic']      =   "";
        $data['new_width']          = 600; // Resize portraits
        //$new_width                = 600;
        $thumb_width                = 100;
        if(count($_POST)) {            
            $data['upload_type']    =   $_POST['upload_type'];
            $data['rotate_pic']     =   $_POST['rotate_pic'];
            $data['file_ref']       =   $_POST['file_ref'];
            $data['file_title']     =   $_POST['file_title'];
            $data['file_descr']     =   $_POST['file_descr'];
            $data['file_sort']      =   $_POST['file_sort'];
            $data['file_remarks']   =   $_POST['file_remarks'];
        }
        $data['site_url']       =   site_url();
        $data['baseurl']        =   base_url();
        $data['exploded_baseurl']=   explode('/', $data['baseurl'], 4);
        $data['app_folder']     =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['current_url']    =   current_url();
        $data['uri_string']     =   uri_string();
	    $config['upload_path']  = $_SERVER['DOCUMENT_ROOT'].'/'.$data['app_folder'].'-uploads/patient_pics/';
	    //$config['upload_path']  = $_SERVER['DOCUMENT_ROOT'].'uploads/patient_pics/';
	    //$config['upload_path']  = $_SERVER['DOCUMENT_ROOT'].'/uploads/patient_pics/';
	    //$config['upload_path']  = '/var/www/thirra-uploads/';
        $data['upload_path']   =   $config['upload_path'];
	    //$data['patient_pics_path']    =   $_SERVER['SERVER_NAME'].$data['app_folder'].'-uploads/patient_pics/';
	    $data['patient_pics_path']    =   '/'.$data['app_folder'].'-uploads/patient_pics/';
	    $config['allowed_types']    = $data['allowed_types'];
	    $config['max_size']	        = $data['max_size'];
        $config['max_width']        = $data['max_width'];
        $config['max_height']       = $data['max_height'];
        $data['exploded_filename']  =   "";
        $data['uploaded_extension'] =   "";
        if(isset($_FILES['userfile']['name'])){
            $data['exploded_filename']  =   explode(".", $_FILES['userfile']['name']);
            $data['uploaded_extension'] =   end($data['exploded_filename']);
        }
        if($data['upload_type'] == "portrait"){
            $data['file_name']      =   $data['patient_id'];
        } else {
            $data['file_name']      =   $data['patient_id']."-".$data['now_id'];
        }
        $config['file_name']    =   $data['file_name'].".".$data['uploaded_extension'];
        if($data['debug_mode']){
            echo "<br />rotate_pic=".$data['rotate_pic'];
            echo "<br />upload_type=".$data['upload_type'];
            echo "<br />uploaded_extension=".$data['uploaded_extension'];
            echo "<br />config-file_name=".$config['file_name'];
        }
	    $config['overwrite']    = TRUE;
	    $config['max_filename'] = '50';
	    $config['remove_spaces']= TRUE;
	    $this->load->library('upload', $config);
	    if ( ! $this->upload->do_upload())
	    {
		    $error = array('error' => $this->upload->display_errors());
		    //echo "Upload error";
            //print_r($error);
		    $upload_data = array('upload_data' => $this->upload->data());
            if($data['debug_mode']){
                echo "<pre>";
                print_r($upload_data);
                $this->load->view('test_upload', $error);
                echo "</pre>";
            }
    		$this->load->vars($data);
		    if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                $new_body   =   "ehr/ehr_patients_upload_ovv_html";
                $new_footer =   "ehr/footer_emr_wap";
		    } else {
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
		        //$this->load->view('bio/bio/header_xhtml1-strict');
                $new_body   =   "ehr/ehr_patients_upload_ovv_html";
                $new_footer =   "ehr/footer_emr_html";
		    } //endif ($_SESSION['thirra_mode'] == "bio_mobile")
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            $this->load->view($new_sidebar);			
            //$this->load->view($new_body, $error);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
	    } else {
		    $upload_data = array('upload_data' => $this->upload->data());
            //print_r($upload_data);
		    //echo "Upload succeeded";
            // Image manipulation section
            // Rotate image
            $config1['image_library'] = 'netpbm';
            $config1['library_path'] = '/usr/bin/';
            $config1['source_image'] = $upload_data['upload_data']['full_path'];
            switch ($data['rotate_pic']){
                case "90a":
                    $config1['rotation_angle'] = '270';
                    //$this->image_lib->rotate();	
                    break;			
                case "90c":
                    $config1['rotation_angle'] = '90';
                    //$this->image_lib->rotate();	
                    break;			
                case "180":
                    $config1['rotation_angle'] = '180';
                    //$this->image_lib->rotate();	
                    break;			
                case "none":
                    break;			
            } // end switch
            $this->load->library('image_lib', $config1);
            if(isset($config1['rotation_angle'])){
                $this->image_lib->rotate();
                /*
                if($this->image_lib->rotate()){
                    //
                } else {
                    //echo $this->image_lib->display_errors();
                }
                */
            }
            //$this->image_lib->initialize($config1);
            /*
            if ( ! $this->image_lib->rotate())
            {
                echo $this->image_lib->display_errors();
            }
            */
            $data['final_width']   =    $upload_data['upload_data']['image_width'];
            $data['final_height']  =    $upload_data['upload_data']['image_height'];
            // Resize portraits to maximum of 600 pixels
            if(($data['upload_type']=="portrait") && ($upload_data['upload_data']['image_width'] > $data['new_width'])){
                //echo "new_width";
                $config2['image_library'] = 'gd2';
                $config2['source_image'] = $upload_data['upload_data']['full_path'];
                $data['final_width']    = $data['new_width'];//75;
                $config2['width']       = $data['new_width'];//75;
                $data['final_height'] = $upload_data['upload_data']['image_height']*($data['new_width'] / $upload_data['upload_data']['image_width']);//75;
                $config2['height']  =   $data['final_height'];
                //$config2['height']  = $upload_data['upload_data']['image_height']*($data['new_width'] / $upload_data['upload_data']['image_width']);//75;
                //$config3['maintain_ratio'] = TRUE;
                //print_r($config2);
                //$this->load->library('image_lib', $config2);
                $this->image_lib->initialize($config2); 
                $this->image_lib->resize();	
                $this->image_lib->clear();	
                /*
                if ( ! $this->image_lib->resize())
                {
                    echo $this->image_lib->display_errors();
                }
                */
            } //endif(($data['upload_type']=="portrait") && ($upload_data['upload_data']['image_width'] > $data['new_width']))
            
            // Create low quality thumbnail
            $config4['image_library'] = 'gd2';
            $config4['source_image'] = $upload_data['upload_data']['full_path'];
            $config4['thumb_marker'] = '_tnlo';
            //$config4['create_thumb'] = TRUE;
            $config4['maintain_ratio'] = TRUE;
            $config4['width']   = $thumb_width;//75;
            $config4['height'] = $data['final_height']*($thumb_width / $data['final_width']);
            //$config4['height'] = $upload_data['upload_data']['image_height']*($thumb_width / $upload_data['upload_data']['image_width']);//75;
            //$config4['new_image'] = $upload_data['upload_data']['file_path'].$data['file_name']."jpg";
            $config4['new_image'] = $upload_data['upload_data']['file_path'].$data['file_name']."_tnlo.jpg";
            $config4['quality'] = '20%';
            $this->image_lib->initialize($config4); 
            $this->image_lib->resize();	
            $this->image_lib->clear();	
            
            // Create high quality thumbnail
            $config3['image_library'] = 'gd2';
            $config3['source_image'] = $upload_data['upload_data']['full_path'];
            $config3['thumb_marker'] = '_tnhi';
            //$config3['create_thumb'] = TRUE;
            $config1['maintain_ratio'] = TRUE;
            $config3['width']   = $thumb_width;//75;
            $config3['height'] = $data['final_height']*($thumb_width / $data['final_width']);
            //$config3['height'] = $upload_data['upload_data']['image_height']*($thumb_width / $upload_data['upload_data']['image_width']);//75;
            $config3['new_image'] = $upload_data['upload_data']['file_path'].$data['file_name']."_tnhi.jpg";
            //$config2['height']  = 128;//75;
            //print_r($config3);
            $this->image_lib->initialize($config3); 
            //$this->load->library('image_lib', $config3);
            $this->image_lib->resize();	
            		
            $ins_pics_array   =   array();
            $ins_pics_array['patient_file_id']  =   $data['now_id'];
            $ins_pics_array['file_filename']    =   $data['file_name'];
            $ins_pics_array['file_origname']    =   $upload_data['upload_data']['client_name'];
            $ins_pics_array['patient_id']       =   $data['patient_id'];
            $ins_pics_array['file_category']    =   $data['upload_type'];
            $ins_pics_array['file_ref']         =   $data['file_ref'];
            $ins_pics_array['file_title']       =   $data['file_title'];
            $ins_pics_array['file_descr']       =   $data['file_descr'];
            //if(is_integer($data['file_sort'])) {
                $ins_pics_array['file_sort']     =   $data['file_sort'];
            //}
            $ins_pics_array['staff_id']         =   $_SESSION['staff_id'];
            $ins_pics_array['file_upload_time'] =   $data['now_id'];
            $ins_pics_array['file_mimetype']    =   $upload_data['upload_data']['file_type'];
            $ins_pics_array['file_extension']   =   $upload_data['upload_data']['file_ext'];
            $ins_pics_array['file_size']        =   $upload_data['upload_data']['file_size'];
            $ins_pics_array['file_path']        =   $upload_data['upload_data']['file_path'];
            $ins_pics_array['summary_id']       =   $data['summary_id'];
            $ins_pics_array['location_id']      =   $_SESSION['location_id'];
            //$ins_pics_array['ip_uploaded']        =   $data['ip_uploaded'];
            $ins_pics_array['file_remarks']     =   $data['file_remarks'];
            if($data['offline_mode']){
                $ins_pics_array['synch_out'] = $data['now_id'];
            }
	        $ins_pics_data       =   $this->mehr_wdb->insert_patient_file($ins_pics_array);
            $this->session->set_flashdata('data_activity', 'New file uploaded.');
 		    //$this->load->view('test_upload_success', $data);
            header("Status: 200");
            redirect('ehr_individual/upload_pics_ovv/new_file/'.$data['patient_id'],'refresh');
            /*
                echo "<pre>";
                print_r($upload_data);
                $this->load->view('test_upload', $error);
                echo "</pre>";
            */
	    }
        // end of file upload section
           
    } // end of function upload_pics_ovv($patient_id=NULL)


    // ------------------------------------------------------------------------
    function ovv_externalmod($summary_id = NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $data['now_id']         =   time();
		$data['form_purpose']   = $this->uri->segment(3);
		$data['current_db']		= $this->db->database; 		
		$data['staff_id']       = $_SESSION['staff_id'];
        $data['patient_id']     = $this->uri->segment(4);
        //$data['summary_id']     = $this->uri->segment(5);
		//$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
 		$data['title'] = "PR-".$data['patient_info']['name'];
		$data['broken_birth_date'] =   $this->break_date($data['patient_id']['birth_date']);
		$data['patient_birthstamp']	= mktime(0,0,0,$data['broken_birth_date']['mm'],$data['broken_birth_date']['dd'],$data['broken_birth_date']['yyyy']);
        //$data['patcon_info']  = $this->memr_rdb->get_patcon_details($data['patient_id']);
		$data['modules_list']   = $this->memr_rdb->get_externalmod_list('episode');

		$this->load->vars($data);
        // Run validation
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
			$new_body   =   "ehr/ehr_indv_ovv_externalmod_html";
			//$new_body   =   "ehr/ehr_indv_ovv_externalmod_wap";
			$new_footer =   "ehr/footer_emr_wap";
		} else {
			//$new_header =   "ehr/header_xhtml1-strict";
			$new_header =   "ehr/header_xhtml1-transitional";
			$new_banner =   "ehr/banner_ehr_ovrvw_html";
			$new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
			$new_body   =   "ehr/ehr_indv_ovv_externalmod_html";
			$new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			

    } //end of function ovv_externalmod()


    // ------------------------------------------------------------------------
    function graph_processor($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['title'] = "T H I R R A - NewPage";
		$data['graph_array']	=	array();
		$data['graph_array']['percent75'] = array(20,43,63,90,125,147,167,183,198,210,216,223);
		$data['graph_array']['percent50'] = array(18,40,60,83,110,130,148,168,183,190,195,200);
		$data['graph_array']['percent25'] = array(16,37,57,76,95,113,129,153,166,170,174,177);
		$data['graph_array']['set01'] 	  = array(19,42,62,88,118,137,159,177,184,199,209,210);
        $data['graph_array']['Labels'] = array('1m','2m','3m','4m','5m','6m','7m','8m','9m','10m','11m','Dec');        
        $data['graph_array']['Title'] = "Weights";        
		//$data['broken_birth_date'] =   
		$this->graph($data['graph_array']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_emr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/emr_newpage_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		/*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);	
		*/
		
    } // end of graph_processor($id=NULL)


	// ------------------------------------------------------------------------
    function graph($data_array) // Deprecate - PANACI
    {
		//$this->chart->loadparams($params); 
		$data_percent75 = $data_array['percent75'];
		$data_percent50 = $data_array['percent50'];
		$data_percent25 = $data_array['percent25'];
		$data_set01 = $data_array['set01'];
        $Title = $data_array['Title'];        
        $Labels = $data_array['Labels'];        

        $this->chart->setTitle($Title,"#000000",4);
        $this->chart->setLegend(SOLID, "#444444", "#ffffff", 2);
        $this->chart->setPlotArea(SOLID,"#444444", '#ffffff');
        //$this->chart->setPlotArea(SOLID,"#444444", '#dddddd');
        $this->chart->setFormat(0,',','.');
        
        $this->chart->addSeries($data_percent75,'line','75th Percentile ', SOLID,'#ff0000', '#00ffff');
        $this->chart->addSeries($data_percent50,'line','50th Percentile ', SOLID,'#ff0000', '#00ffff');
        $this->chart->addSeries($data_percent25,'line','25th Percentile ', SOLID,'#ff0000', '#00ffff');
        $this->chart->addSeries($data_set01,'dot','Height ', SOLID,'#0000ff', '#0000ff');
        //$this->chart->addSeries($data_2002,'area','Weight ', SOLID,'#ff0000', '#00ffff');
        
        $this->chart->setXAxis('#000000', SOLID, 1, "2001 - 2002");
        $this->chart->setYAxis('#000000', SOLID, 2, "m - kg");
        $this->chart->setLabels($Labels, '#000000', 1, HORIZONTAL);
        $this->chart->setGrid("#bbbbbb", DASHED, "#bbbbbb", DOTTED);	
        $this->chart->plot('./images/dynamic_chart.png');
        
        $this->load->view('graph');
		
    } //end of function graph($data_array)

	// ------------------------------------------------------------------------
    function graph_panaci()
    {
		//$this->chart->loadparams($params); 
		$data_2001 = array(43,163,56,21,0,22,0,5,73,152,123,294);
        $data_2002 = array(134,101,26,46,22,64,0,28,8,0,50,50);
        $Labels = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');        

        $this->chart->setTitle("Vital Signs","#000000",2);
        $this->chart->setLegend(SOLID, "#444444", "#ffffff", 2);
        $this->chart->setPlotArea(SOLID,"#444444", '#ffffff');
        //$this->chart->setPlotArea(SOLID,"#444444", '#dddddd');
        $this->chart->setFormat(0,',','.');
        
        $this->chart->addSeries($data_2001,'dot','Height ', SOLID,'#0000ff', '#0000ff');
        $this->chart->addSeries($data_2002,'line','Weight ', SOLID,'#ff0000', '#00ffff');
        //$this->chart->addSeries($data_2002,'area','Weight ', SOLID,'#ff0000', '#00ffff');
        
        $this->chart->setXAxis('#000000', SOLID, 1, "2001 - 2002");
        $this->chart->setYAxis('#000000', SOLID, 2, "m - kg");
        $this->chart->setLabels($Labels, '#000000', 1, HORIZONTAL);
        $this->chart->setGrid("#bbbbbb", DASHED, "#bbbbbb", DOTTED);	
        $this->chart->plot('./images/chart.png');
        
        $this->load->view('graph');
    } //end of function graph_panaci()

/*
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
    function cb_correct_date($date_string)  // Call back
    {
        $data['app_minyear']		    =	$this->config->item('app_minyear');
        $data['app_maxyear']		    =	$this->config->item('app_maxyear');
		// Check if it is YYYY-MM-DD
		if(ereg("^[1|2]{1}[9|0]{1}[0-9]{2}-[0-1]{1}[0-9]{1}-[0-3]{1}[0-9]{1}", $date_string)){
			// Check if it is a valid date
            $broken_birth_date      =   $this->break_date($date_string);			
			if(checkdate($broken_birth_date['mm'],$broken_birth_date['dd'],$broken_birth_date['yyyy'])){
				// Check if year is within limits
				if(($broken_birth_date['yyyy'] < $data['app_maxyear']) && ($broken_birth_date['yyyy'] > $data['app_minyear'])){
					return TRUE;
				} else {
					$this->form_validation->set_message('cb_correct_date', 'The %s field format is outside valid range.');
					return FALSE;
				}
			} else {
				$this->form_validation->set_message('cb_correct_date', 'The %s field format is incorrect.');
				return FALSE;
			}
		} else {
			$this->form_validation->set_message('cb_correct_date', 'The %s field format is incorrect.');
			return FALSE;
		}
    } // end of function cb_correct_date($date_string)
*/

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
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_emr_html";
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
