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
 * Portions created by the Initial Developer are Copyright (C) 2010-2011
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

/**
 * Controller Class for EHR_INDIVIDUAL_ANTENATAL
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_individual_antenatal extends MY_Controller 
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
		$this->load->model('mantenatal_wdb');
		$this->load->model('mantenatal_rdb');
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
    function list_history_antenatal()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['patient_id']     = $this->uri->segment(3);
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
		$data['history_list']  = $this->memr_rdb->get_antenatal_list('list',$data['patient_id']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            //$new_body   =   "ehr/ehr_indv_list_history_vitals_wap";
            $new_body   =   "ehr/ehr_indv_list_history_antenatal_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_list_history_antenatal_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function list_history_antenatal()


    // ------------------------------------------------------------------------
    function edit_history_antenatal($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
		$this->load->model('mqueue_rdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['patient_id']         =   $this->uri->segment(3);
		$data['form_purpose']       =   $this->uri->segment(4);
		$data['antenatal_id']  =   $this->uri->segment(5);
        $data['session_id']         = "history_id";//$data['summary_id'];
        $data['location_id']        =   $_SESSION['location_id'];
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['rooms_list'] = $this->mqueue_rdb->get_rooms_list($data['location_id']);
		$data['followup_list']  = $this->memr_rdb->get_antenatal_followup('list',$data['patient_id'],$data['antenatal_id']);
		$data['history_list']  = $this->memr_rdb->get_antenatal_list('list',$data['patient_id']);
		$data['delivery_list']  = $this->memr_rdb->get_antenatal_delivery('list',$data['patient_id'],$data['antenatal_id']);
        $data['postpartum_list']  = $this->mantenatal_rdb->get_antenatal_postpartum('list',$data['patient_id'],$data['antenatal_id']);
		$data['family_above']  = $this->memr_rdb->get_family_relations('List','above',$data['patient_id']);
		$data['family_below']  = $this->memr_rdb->get_family_relations('List','below',$data['patient_id']);
        
        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                     =   $_POST['now_id'];
            $data['now_date']                   =   date("Y-m-d",$data['now_id']);
            $data['init_patient_id']            =   $data['patient_id'];
            $data['patient_id']                 =   $data['init_patient_id'];
            //$data['summary_id']         =   $_POST['summary_id'];
            $data['init_antenatal_id']          =   $_POST['antenatal_id'];
            $data['antenatal_id']               =   $data['init_antenatal_id'];
            $data['init_antenatal_reference']   =   $_POST['antenatal_reference'];
            //$data['init_synch_out']             =   $_POST['synch_out'];
            $data['antenatal_info_id']          =   $_POST['antenatal_info_id'];
            $data['init_record_date']           =   $_POST['record_date'];
            $data['init_gravida']               =   $_POST['gravida'];
            $data['init_para']                  =   $_POST['para'];
            $data['init_method_contraception']  =   $_POST['method_contraception'];
            $data['init_abortion']              =   $_POST['abortion'];
            //$data['init_past_obstretical_history_icpc']      =   $_POST['past_obstretical_history_icpc'];
            //$data['init_past_obstretical_history_notes']      =   $_POST['past_obstretical_history_notes'];
            $data['init_num_term_deliveries']   =   $_POST['num_term_deliveries'];
            $data['init_num_preterm_deliveries']=   $_POST['num_preterm_deliveries'];
            $data['init_num_preg_lessthan_21wk']=   $_POST['num_preg_lessthan_21wk'];
            $data['init_num_live_births']       =   $_POST['num_live_births'];
            $data['init_num_caesarean_births']  =   $_POST['num_caesarean_births'];
            $data['init_num_miscarriages']      =   $_POST['num_miscarriages'];
            $data['init_three_consec_miscarriages']=   $_POST['three_consec_miscarriages'];
            $data['init_num_stillbirths']       =   $_POST['num_stillbirths'];
            $data['init_post_partum_depression']=   $_POST['post_partum_depression'];
            $data['init_present_pulmonary_tb']  =   $_POST['present_pulmonary_tb'];
            $data['init_present_heart_disease'] =   $_POST['present_heart_disease'];
            $data['init_present_diabetes']      =   $_POST['present_diabetes'];
            $data['init_present_bronchial_asthma'] =   $_POST['present_bronchial_asthma'];
            $data['init_present_goiter']        =   $_POST['present_goiter'];
            $data['init_present_hepatitis_b']   =   $_POST['present_hepatitis_b'];
            $data['init_husband_name']          =   $_POST['husband_name'];
            $data['init_husband_job']           =   $_POST['husband_job'];
            $data['init_husband_dob']           =  $_POST['husband_dob'];
            $data['init_husband_ic_no']         =   $_POST['husband_ic_no'];
            $data['init_contact_person']      	=   $this->input->post('contact_person');
            $data['antenatal_current_id']       =   $_POST['antenatal_current_id'];
            $data['init_midwife_name']          =   $_POST['midwife_name'];
            //$data['init_pregnancy_duration'] =   $_POST['pregnancy_duration'];
            $data['init_lmp']                   =   $_POST['lmp'];
            //$data['init_edd']           =   $_POST['edd'];
            $data['init_planned_place']         =   $_POST['planned_place'];
            $data['init_menstrual_cycle_length']=   $_POST['menstrual_cycle_length'];
            $data['init_lmp_edd']               =   $_POST['lmp_edd'];
            //$data['init_lmp_gestation']         =   $_POST['lmp_gestation'];
            $data['init_usscan_date']           =   $_POST['usscan_date'];
            $data['init_usscan_edd']            =   $_POST['usscan_edd'];
            $data['init_usscan_gestation']      =   $_POST['usscan_gestation'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_antenatal") {
                // New user
                $data['init_antenatal_reference']   =   "";
                $data['init_synch_out']             =   NULL;
                $data['antenatal_info_id']          =   "";
                $data['init_record_date']           =   $data['now_date'];
                $data['init_husband_name']          =   "";
                $data['init_husband_job']           =   "";
                $data['init_husband_dob']           =   "";
                $data['init_husband_ic_no']         =   "";
                $data['init_gravida']               =   "";
                $data['init_para']                  =   "";
                $data['init_method_contraception']  =   "";
                $data['init_abortion']              =   "";
                $data['init_past_obstretical_history_icpc']   =   "";
                $data['init_past_obstretical_history_notes']   =   "";
                $data['init_num_term_deliveries']   =   NULL;
                $data['init_num_preterm_deliveries']=   NULL;
                $data['init_num_preg_lessthan_21wk']=   NULL;
                $data['init_num_live_births']       =   NULL;
                $data['init_num_caesarean_births']  =   NULL;
                $data['init_num_miscarriages']      =   NULL;
                $data['init_three_consec_miscarriages'] =   NULL;
                $data['init_num_stillbirths']       =   NULL;
                $data['init_post_partum_depression']=   NULL;
                $data['init_present_pulmonary_tb']  =   NULL;
                $data['init_present_heart_disease'] =   NULL;
                $data['init_present_diabetes']      =   NULL;
                $data['init_present_bronchial_asthma'] =   NULL;
                $data['init_present_goiter']        =   NULL;
                $data['init_present_hepatitis_b']   =   NULL;
                $data['init_contact_person']        =   "";
                $data['antenatal_current_id']       =   "";
                $data['init_midwife_name']          =   "";
                $data['init_pregnancy_duration']    =   "";
                $data['init_lmp']                   =   "";
                $data['init_edd']                   =   "";
                $data['init_planned_place']         =   "";
                $data['init_menstrual_cycle_length'] =   NULL;
                $data['init_lmp_edd']               =   NULL;
                $data['init_lmp_gestation']         =   NULL;
                $data['init_usscan_date']           =   NULL;
                $data['init_usscan_edd']            =   NULL;
                $data['init_usscan_gestation']      =   NULL;
                /* Removing antenatal_delivery from form
                $data['init_antenatal_delivery_id']   =   "";
                $data['init_date_admission']   =   "";
                $data['init_time_admission']   =   "";
                $data['init_date_delivery']   =   "";
                $data['init_time_delivery']   =   "";
                $data['init_delivery_type']   =   "";
                $data['init_delivery_place']   =   "";
                $data['init_mother_condition']   =   "";
                $data['init_baby_condition']   =   "";
                $data['init_baby_weight']   =   "";
                $data['init_complication_icpc']   =   "";
                $data['init_complication_notes']   =   "";
                */
            } else {
                // Existing user
		        $data['history_info'] =  $this->memr_rdb->get_antenatal_list('one',$data['patient_id'],$data['antenatal_id']);
                $data['init_record_date']           =   $data['history_info'][0]['date'];
                $data['init_antenatal_reference']   =   $data['history_info'][0]['antenatal_reference'];
                $data['init_synch_out']             =   $data['history_info'][0]['synch_out'];
                $data['antenatal_info_id']          =   $data['history_info'][0]['antenatal_info_id'];
                $data['init_husband_name']          =   $data['history_info'][0]['husband_name'];
                $data['init_husband_job']           =   $data['history_info'][0]['husband_job'];
                $data['init_husband_dob']           =   $data['history_info'][0]['husband_dob'];
                $data['init_husband_ic_no']         =   $data['history_info'][0]['husband_ic_no'];
                $data['init_gravida']               =   $data['history_info'][0]['gravida'];
                $data['init_para']                  =   $data['history_info'][0]['para'];
                $data['init_method_contraception']  =   $data['history_info'][0]['method_contraception'];
                $data['init_abortion']              =   $data['history_info'][0]['abortion'];
                $data['init_past_obstretical_history_icpc'] =   $data['history_info'][0]['past_obstretical_history_icpc'];
                $data['init_past_obstretical_history_notes'] =   $data['history_info'][0]['past_obstretical_history_notes'];
                $data['init_num_term_deliveries']   =   $data['history_info'][0]['num_term_deliveries'];
                $data['init_num_preterm_deliveries'] =   $data['history_info'][0]['num_preterm_deliveries'];
                $data['init_num_preg_lessthan_21wk'] =   $data['history_info'][0]['num_preg_lessthan_21wk'];
                $data['init_num_live_births']       =   $data['history_info'][0]['num_live_births'];
                $data['init_num_caesarean_births']  =   $data['history_info'][0]['num_caesarean_births'];
                $data['init_num_miscarriages']      =   $data['history_info'][0]['num_miscarriages'];
                $data['init_three_consec_miscarriages'] =   $data['history_info'][0]['three_consec_miscarriages'];
                $data['init_num_stillbirths']       =   $data['history_info'][0]['num_stillbirths'];
                $data['init_post_partum_depression'] =   $data['history_info'][0]['post_partum_depression'];
                $data['init_present_pulmonary_tb']  =   $data['history_info'][0]['present_pulmonary_tb'];
                $data['init_present_heart_disease'] =   $data['history_info'][0]['present_heart_disease'];
                $data['init_present_diabetes']      =   $data['history_info'][0]['present_diabetes'];
                $data['init_present_bronchial_asthma'] =   $data['history_info'][0]['present_bronchial_asthma'];
                $data['init_present_goiter']        =   $data['history_info'][0]['present_goiter'];
                $data['init_present_hepatitis_b']   =   $data['history_info'][0]['present_hepatitis_b'];
                $data['init_antenatal_current_id']  =   $data['history_info'][0]['antenatal_current_id'];
                $data['init_contact_person']        =   $data['history_info'][0]['contact_person'];
                $data['antenatal_current_id']       =   $data['history_info'][0]['antenatal_current_id'];
                $data['init_midwife_name']          =   $data['history_info'][0]['midwife_name'];
                $data['init_pregnancy_duration']    =   $data['history_info'][0]['pregnancy_duration'];
                $data['init_lmp']                   =   $data['history_info'][0]['lmp'];
                $data['init_edd']                   =   $data['history_info'][0]['edd'];
                $data['init_planned_place']         =   $data['history_info'][0]['planned_place'];
                $data['init_menstrual_cycle_length'] =   $data['history_info'][0]['menstrual_cycle_length'];
                $data['init_lmp_edd']               =   $data['history_info'][0]['lmp_edd'];
                $data['init_lmp_gestation']         =   $data['history_info'][0]['lmp_gestation'];
                $data['init_usscan_date']           =   $data['history_info'][0]['usscan_date'];
                $data['init_usscan_edd']            =   $data['history_info'][0]['usscan_edd'];
                $data['init_usscan_gestation']      =   $data['history_info'][0]['usscan_gestation'];
                /* Removing antenatal_delivery from form
                $data['init_antenatal_delivery_id'] =   $data['history_info'][0]['antenatal_delivery_id'];
                $data['init_date_admission'] =   $data['history_info'][0]['date_admission'];
                $data['init_time_admission'] =   $data['history_info'][0]['time_admission'];
                $data['init_date_delivery'] =   $data['history_info'][0]['date_delivery'];
                $data['init_time_delivery'] =   $data['history_info'][0]['time_delivery'];
                $data['init_delivery_type'] =   $data['history_info'][0]['delivery_type'];
                $data['init_delivery_place'] =   $data['history_info'][0]['delivery_place'];
                $data['init_mother_condition'] =   $data['history_info'][0]['mother_condition'];
                $data['init_baby_condition'] =   $data['history_info'][0]['baby_condition'];
                $data['init_baby_weight'] =   $data['history_info'][0]['baby_weight'];
                $data['init_complication_icpc'] =   $data['history_info'][0]['complication_icpc'];
                $data['init_complication_notes'] =   $data['history_info'][0]['complication_notes'];
                */
           } //endif ($data['form_purpose'] == "new_room")
        } //endif(count($_POST))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_history_antenatal_info') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                $new_body   =   "ehr/ehr_indv_edit_history_antenatal_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_indv_edit_history_antenatal_html";
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
            if($data['form_purpose'] == "new_antenatal") {
                // Insert records
                // New patient antenatal info
                $ins_antenatal_array   =   array();
                $ins_antenatal_array['staff_id']           = $_SESSION['staff_id'];
                $ins_antenatal_array['now_id']             = $data['now_id'];
                $ins_antenatal_array['antenatal_id']       = $data['now_id'];
                $ins_antenatal_array['patient_id']         = $data['init_patient_id'];
                $ins_antenatal_array['event_id']            = $data['now_id'];
                $ins_antenatal_array['location_id']         = $_SESSION['location_id'];
                $ins_antenatal_array['event_description']   = "Pregnancy History EDD ".$data['init_lmp_edd']." - ".$data['patient_info']['name'];
                $ins_antenatal_array['session_id']         = "history_id";//$data['summary_id'];
                //if((strtotime($data['init_lmp_edd']) - $data['now_id']) > 0){
                    $ins_antenatal_array['status']             = 0;
                //} else {
                    //$ins_antenatal_array['status']             = 1;
                //}
                $ins_antenatal_array['antenatal_reference'] = $data['init_antenatal_reference'];
                $ins_antenatal_array['record_date']        = $data['init_record_date'];
                $ins_antenatal_array['antenatal_info_id']  = $ins_antenatal_array['antenatal_id'];
                $ins_antenatal_array['husband_name']       = $data['init_husband_name'];
                $ins_antenatal_array['husband_job']        = $data['init_husband_job'];
                if(!empty($data['init_husband_dob'])){
                    $ins_antenatal_array['husband_dob']             = $data['init_husband_dob'];
                }
                //$ins_antenatal_array['husband_dob']       = $data['init_husband_dob'];
                $ins_antenatal_array['husband_ic_no']       = $data['init_husband_ic_no'];
                $ins_antenatal_array['contact_person']      = $data['init_contact_person'];
                $ins_antenatal_array['gravida']             = $data['init_gravida'];
                $ins_antenatal_array['para']                = $data['init_para'];
                $ins_antenatal_array['method_contraception']       = $data['init_method_contraception'];
                $ins_antenatal_array['abortion']       = $data['init_abortion'];
                if(!empty($data['init_past_obstretical_history_icpc'])){
                    $ins_antenatal_array['past_obstretical_history_icpc']             = $data['init_past_obstretical_history_icpc'];
                }
                //$ins_antenatal_array['past_obstretical_history_icpc']       = $data['init_past_obstretical_history_icpc'];
                //$ins_antenatal_array['past_obstretical_history_notes']       = $data['init_past_obstretical_history_notes'];
                $ins_antenatal_array['antenatal_current_id']       = $ins_antenatal_array['antenatal_id'];
                $ins_antenatal_array['midwife_name']       = $data['init_midwife_name'];
                //$ins_antenatal_array['pregnancy_duration']       = $data['init_pregnancy_duration'];
                if(!empty($data['init_lmp'])){
                    $ins_antenatal_array['lmp']             = $data['init_lmp'];
                }
                //$ins_antenatal_array['lmp']       = $data['init_lmp'];
                //$ins_antenatal_array['edd']       = $data['init_edd'];
                $ins_antenatal_array['lmp_edd']                 = $data['init_lmp_edd'];
                //$ins_antenatal_array['lmp_gestation']           = $data['init_lmp_gestation'];
                $ins_antenatal_array['planned_place']       = $data['init_planned_place'];
                if($data['offline_mode']){
                    $ins_antenatal_array['synch_out']        = $data['now_id'];
                }
	            $ins_antenatal_data       =   $this->mantenatal_wdb->insert_new_antenatal($ins_antenatal_array);
                $this->session->set_flashdata('data_activity', 'Pregnancy added.');
            } else {
                //Edit patient antenatal info
                $upd_antenatal_array   =   array();
                $upd_antenatal_array['staff_id']           = $_SESSION['staff_id'];
                $upd_antenatal_array['now_id']              = $data['now_id'];
                $upd_antenatal_array['antenatal_id']              = $data['antenatal_id'];
                //if((strtotime($data['init_lmp_edd']) - $data['now_id']) > 0){
                    $upd_antenatal_array['status']             = 0;
                //} else {
                    //$upd_antenatal_array['status']             = 1;
                //}
                $upd_antenatal_array['antenatal_reference'] = $data['init_antenatal_reference'];
                $upd_antenatal_array['antenatal_info_id']   = $data['antenatal_info_id'];
                $upd_antenatal_array['record_date']        = $data['init_record_date'];
                $upd_antenatal_array['husband_name']        = $data['init_husband_name'];
                $upd_antenatal_array['husband_job']         = $data['init_husband_job'];
                if(!empty($data['init_husband_dob'])){
                    $upd_antenatal_array['husband_dob']          = $data['init_husband_dob'];
                }
                //$upd_antenatal_array['husband_dob']       = $data['init_husband_dob'];
                $upd_antenatal_array['husband_ic_no']       = $data['init_husband_ic_no'];
                $upd_antenatal_array['gravida']             = $data['init_gravida'];
                $upd_antenatal_array['para']                = $data['init_para'];
                $upd_antenatal_array['method_contraception']       = $data['init_method_contraception'];
                $upd_antenatal_array['abortion']            = $data['init_abortion'];
                if(!empty($data['init_past_obstretical_history_icpc'])){
                    $upd_antenatal_array['past_obstretical_history_icpc']= $data['init_past_obstretical_history_icpc'];
                }
                //$upd_antenatal_array['past_obstretical_history_icpc']       = $data['init_past_obstretical_history_icpc'];
                //$upd_antenatal_array['past_obstretical_history_notes'] = $data['init_past_obstretical_history_notes'];
                $upd_antenatal_array['num_term_deliveries']     = $data['init_num_term_deliveries'];
                $upd_antenatal_array['num_preterm_deliveries']  = $data['init_num_preterm_deliveries'];
                $upd_antenatal_array['num_preg_lessthan_21wk']  = $data['init_num_preg_lessthan_21wk'];
                $upd_antenatal_array['num_live_births']         = $data['init_num_live_births'];
                $upd_antenatal_array['num_caesarean_births']    = $data['init_num_caesarean_births'];
                $upd_antenatal_array['num_miscarriages']        = $data['init_num_miscarriages'];
                if(!empty($data['init_three_consec_miscarriages'])){
                    if($data['init_three_consec_miscarriages'] == 'TRUE') {
                        //$upd_antenatal_array['three_consec_miscarriages']                 = $data['init_three_consec_miscarriages'];
                        $upd_antenatal_array['three_consec_miscarriages'] = TRUE;
                    } elseif($data['init_three_consec_miscarriages'] == 'FALSE') {
                        $upd_antenatal_array['three_consec_miscarriages'] = FALSE;
                    } else {
                        $upd_antenatal_array['three_consec_miscarriages'] = NULL;
                    }
                } else {
                    $upd_antenatal_array['three_consec_miscarriages'] = NULL;
                }

                //$upd_antenatal_array['three_consec_miscarriages']= $data['init_three_consec_miscarriages'];
                $upd_antenatal_array['num_stillbirths']         = $data['init_num_stillbirths'];
                $upd_antenatal_array['post_partum_depression']  = $data['init_post_partum_depression'];
                $upd_antenatal_array['present_pulmonary_tb']    = $data['init_present_pulmonary_tb'];
                $upd_antenatal_array['present_heart_disease']   = $data['init_present_heart_disease'];
                $upd_antenatal_array['present_diabetes']        = $data['init_present_diabetes'];
                $upd_antenatal_array['present_bronchial_asthma']= $data['init_present_bronchial_asthma'];
                $upd_antenatal_array['present_goiter']          = $data['init_present_goiter'];
                $upd_antenatal_array['present_hepatitis_b']     = $data['init_present_hepatitis_b'];
                $upd_antenatal_array['contact_person']          = $data['init_contact_person'];
                $upd_antenatal_array['antenatal_current_id']    = $data['antenatal_current_id'];
                $upd_antenatal_array['midwife_name']            = $data['init_midwife_name'];
                //$upd_antenatal_array['pregnancy_duration']      = $data['init_pregnancy_duration'];
                if(!empty($data['init_lmp'])){
                    $upd_antenatal_array['lmp']                 = $data['init_lmp'];
                }
                //$upd_antenatal_array['lmp']       = $data['init_lmp'];
                //$upd_antenatal_array['edd']                     = $data['init_edd'];
                $upd_antenatal_array['planned_place']           = $data['init_planned_place'];
                if(is_numeric($data['init_menstrual_cycle_length'])){
                    $upd_antenatal_array['menstrual_cycle_length']             = $data['init_menstrual_cycle_length'];
                }
                //$upd_antenatal_array['menstrual_cycle_length']  = $data['init_menstrual_cycle_length'];
                $upd_antenatal_array['lmp_edd']                 = $data['init_lmp_edd'];
                //$upd_antenatal_array['lmp_gestation']           = $data['init_lmp_gestation'];
                if(!empty($data['init_usscan_date'])){
                    $upd_antenatal_array['usscan_date']         = $data['init_usscan_date'];
                }
                //$upd_antenatal_array['usscan_date']             = $data['init_usscan_date'];
                if(!empty($data['init_usscan_edd'])){
                    $upd_antenatal_array['usscan_edd']         = $data['init_usscan_edd'];
                }
                //$upd_antenatal_array['usscan_edd']              = $data['init_usscan_edd'];
                $upd_antenatal_array['usscan_gestation']        = $data['init_usscan_gestation'];
                if($data['offline_mode']){
                    if(!empty($data['init_synch_out'])){
                        // New patient updated offline - do nothing
                        //$upd_antenatal_array['synch_out']        = $data['now_id'];
                    } else {
                        // Old patient updated offline
                        $upd_antenatal_array['synch_in']        = $data['now_id'];
                    }
                }
                $upd_antenatal_array['update_when']              = $data['now_id'];
                $upd_antenatal_array['update_by']                = $_SESSION['staff_id'];
	            $upd_antenatal_data       =   $this->mantenatal_wdb->update_antenatal_info($upd_antenatal_array);
                $this->session->set_flashdata('data_activity', 'Pregnancy updated.');
            } //endif($data['form_purpose'] == "new_history")
            $new_page = base_url()."index.php/ehr_individual_antenatal/list_history_antenatal/".$data['init_patient_id'];
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_antenatal') == FALSE)
    } // end of function edit_history_antenatal($id)


    // ------------------------------------------------------------------------
    function status_history_antenatal($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['form_purpose']       =   $this->uri->segment(3);
        $data['patient_id']         =   $this->uri->segment(4);
        $data['antenatal_id']       =   $this->uri->segment(5);
        $data['antenatal_info_id']  =   $this->uri->segment(6);
        $data['antenatal_current_id']=   $this->uri->segment(7);
        $data['now_id']             =   time();
        
        // Delete records
        $upd_antenatal_array['antenatal_id']        = $data['antenatal_id'];
        $upd_antenatal_array['antenatal_info_id']   = $data['antenatal_info_id'];
        $upd_antenatal_array['antenatal_current_id']= $data['antenatal_current_id'];
        switch ($data['form_purpose']) {
            case "close":
                $upd_antenatal_array['status']              = 1;
                break;
            case "reopen":
                $upd_antenatal_array['status']              = 0;
                break;
        } //endswitch ($data['form_purpose'])
        if($data['offline_mode']){
            if(!empty($data['init_synch_out'])){
                // New patient updated offline - do nothing
                //$upd_antenatal_array['synch_out']        = $data['now_id'];
            } else {
                // Old patient updated offline
                $upd_antenatal_array['synch_in']        = $data['now_id'];
            }
        }
        $upd_antenatal_array['update_when']              = $data['now_id'];
        $upd_antenatal_array['update_by']                = $_SESSION['staff_id'];
        $upd_antenatal_data =   $this->mantenatal_wdb->update_antenatal_info($upd_antenatal_array, "update_status");
        $this->session->set_flashdata('data_activity', 'Pregnancy event status updated.');
        $new_page = base_url()."index.php/ehr_individual_antenatal/list_history_antenatal/".$data['patient_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function status_history_antenatal($id)


    // ------------------------------------------------------------------------
    function edit_history_antenatal_followup($id=NULL) 
    {
		$this->load->model('mantenatal_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['form_purpose']       =   $this->uri->segment(3);
        $data['patient_id']         =   $this->uri->segment(4);
        //$data['summary_id']       =   $this->uri->segment(5);
        $patient_id                 =   $data['patient_id'];
        $data['init_patient_id']    =   $patient_id;
        $data['antenatal_id']       =   $this->uri->segment(5);
        $data['antenatal_followup_id']= $this->uri->segment(6);
		//$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['patcon_info']  = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['antenatal_info']  = $this->memr_rdb->get_antenatal_list('List',$data['patient_id'],$data['antenatal_id']);
        $data['antenatal_followup']  = $this->memr_rdb->get_antenatal_followup('List',$data['patient_id'], $data['antenatal_id'], $data['antenatal_followup_id']);
        if(isset($data['antenatal_followup'][0]['session_id'])){
            if(($data['form_purpose'] == "new_followup") && ($data['antenatal_followup'][0]['session_id'] == $data['summary_id'])){
                $data['form_purpose'] = "edit_followup";
            }
            $data['vitals_info']    = $this->memr_rdb->get_patcon_vitals($data['antenatal_followup'][0]['session_id']);
            if($data['vitals_info']['vital_id'] == "new_vitals"){
                $data['temperature']        =   "";
                $data['bp_systolic']        =   "";
                $data['bp_diastolic']       =   "";
            } else {
                $data['temperature']        =   $data['vitals_info']['temperature']."&deg;C";
                $data['bp_systolic']        =   $data['vitals_info']['bp_systolic'];
                $data['bp_diastolic']        =   $data['vitals_info']['bp_diastolic'];
            }
        }

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                     =   $_POST['now_id'];
            $data['now_date']                   =   date("Y-m-d",$data['now_id']);
            $data['init_patient_id']            =   $_POST['patient_id'];
            $data['patient_id']                 =   $data['init_patient_id'];
            $data['summary_id']                 =   $_POST['summary_id'];
            $data['init_antenatal_id']          =   $_POST['antenatal_id'];
            $data['antenatal_id']               =   $data['init_antenatal_id'];
            $data['init_record_date']           =   $_POST['record_date'];
            $data['init_pregnancy_duration']    =   $_POST['pregnancy_duration'];
            $data['init_lie']                   =   $_POST['lie'];
            $data['init_weight']                =   $_POST['weight'];
            $data['init_fundal_height']         =   $_POST['fundal_height'];
            $data['init_hb']                    =   $_POST['hb'];
            $data['init_urine_alb']             =   $_POST['urine_alb'];
            $data['init_urine_sugar']           =   $_POST['urine_sugar'];
            $data['init_ankle_odema']           =   $_POST['ankle_odema'];
            $data['init_notes']                 =   $_POST['notes'];
            $data['init_next_followup']         =   $_POST['next_followup'];
            $data['init_glucose_tolerance_test']=   $_POST['glucose_tolerance_test'];
            $data['init_vaginal_bleeding']      =   $_POST['vaginal_bleeding'];
            $data['init_vaginal_infection']     =   $_POST['vaginal_infection'];
            $data['init_urinary_tract_infection'] =   $_POST['urinary_tract_infection'];
            $data['init_blood_pressure']        =   $_POST['blood_pressure'];
            $data['init_fever']                 =   $_POST['fever'];
            $data['init_pallor']                =   $_POST['pallor'];
            $data['init_abnormal_fundal_height']=   $_POST['abnormal_fundal_height'];
            $data['init_movements']             =   $_POST['movements'];
            $data['init_abnormal_presentation'] =   $_POST['abnormal_presentation'];
            $data['init_fetal_heart_tones']     =   $_POST['fetal_heart_tones'];
            $data['init_missing_fetal_heartbeat']=   $_POST['missing_fetal_heartbeat'];
            $data['init_supplement_iodine']     =   $this->input->post('supplement_iodine');
            $data['init_supplement_iron']       =   $this->input->post('supplement_iron');
            $data['init_supplement_vitamins']   =   $this->input->post('supplement_vitamins');
            $data['init_supplement_folate']     =   $this->input->post('supplement_folate');
            $data['init_malaria_prophylaxis']   =   $this->input->post('malaria_prophylaxis');
            $data['init_breastfeed_intention']  =   $this->input->post('breastfeed_intention');
            $data['init_advise_4_danger_signs'] =   $this->input->post('advise_4_danger_signs');
            $data['init_dental_checkup']      	=   $this->input->post('dental_checkup');
            $data['init_emergency_plans']      	=   $this->input->post('emergency_plans');
            $data['init_healthy_diet']      	=   $this->input->post('healthy_diet');
            $data['init_adequate_rest']      	=   $this->input->post('adequate_rest');
            $data['init_adequate_exercise']     =   $this->input->post('adequate_exercise');
            $data['init_thirdtrimester_issues'] =   $this->input->post('thirdtrimester_issues');
            $data['init_followup_remarks']      =   $this->input->post('followup_remarks');
            $data['init_risks']      		    =   $this->input->post('risks');
            $data['init_synch_out']             =   $_POST['synch_out'];
            
            if ($data['patient_id'] == "new_followup"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'ADD ANTENATAL CHECK-UP';
		        $data['patient_info']       = array();
            } else {
                // Edit form
          		$data['save_attempt']       = 'EDIT ANTENATAL CHECK-UP';
                // These fields were passed through as hidden tags
                $data['patient_id']         =   $data['init_patient_id']; //came from POST
		        $data['patient_info']       =   $this->memr_rdb->get_patient_demo($data['patient_id']);
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

            if ($data['form_purpose'] == "new_followup") {
                // New vitals
          		$data['save_attempt']       =   'ADD ANTENATAL CHECK-UP';
	            //$data['antenatal_info']     =   array();
                $data['antenatal_followup_id']  =   $data['antenatal_followup'][0]['antenatal_followup_id'];
                $data['summary_id']                 =   NULL;
                $data['init_record_date']           =   $data['now_date'];
                $data['init_pregnancy_duration']    =   NULL;
                $data['init_lie']                   =   NULL;
                $data['init_weight']                =   NULL;
                $data['init_fundal_height']         =   NULL;
                $data['init_hb']                    =   NULL;
                $data['init_urine_alb']             =   NULL;
                $data['init_urine_sugar']           =   NULL;
                $data['init_ankle_odema']           =   NULL;
                $data['init_notes']                 =   NULL;
                $data['init_next_followup']         =   NULL;
                $data['init_glucose_tolerance_test'] =   NULL;
                $data['init_vaginal_bleeding']      =   NULL;
                $data['init_vaginal_infection']     =   NULL;
                $data['init_urinary_tract_infection']=   NULL;
                $data['init_blood_pressure']        =   NULL;
                $data['init_fever']                 =   NULL;
                $data['init_pallor']                =   NULL;
                $data['init_abnormal_fundal_height']=   NULL;
                $data['init_movements']             =   NULL;
                $data['init_abnormal_presentation'] =   NULL;
                $data['init_fetal_heart_tones']     =   NULL;
                $data['init_missing_fetal_heartbeat'] =   NULL;
                $data['init_supplement_iodine']     =   NULL;
                $data['init_supplement_iron']       =   NULL;
                $data['init_supplement_vitamins']   =   NULL;
                $data['init_supplement_folate']     =   NULL;
                $data['init_malaria_prophylaxis']   =   NULL;
                $data['init_breastfeed_intention']  =   NULL;
                $data['init_advise_4_danger_signs'] =   NULL;
                $data['init_dental_checkup']        =   NULL;
                $data['init_emergency_plans']       =   NULL;
                $data['init_healthy_diet']          =   NULL;
                $data['init_adequate_rest']         =   NULL;
                $data['init_adequate_exercise']     =   NULL;
                $data['init_thirdtrimester_issues'] =   NULL;
                $data['init_followup_remarks']      =   NULL;
                $data['init_risks']                 =   NULL;
                $data['init_synch_out']             =   NULL;
            } else {
                // Editing followup
          		$data['save_attempt'] = 'EDIT ANTENATAL CHECK-UP';
                $data['summary_id']                 =   $data['antenatal_followup'][0]['session_id'];
                $data['init_record_date']           =   $data['antenatal_followup'][0]['date'];
                $data['init_pregnancy_duration']    =   $data['antenatal_followup'][0]['pregnancy_duration'];
                $data['init_lie']                   =   $data['antenatal_followup'][0]['lie'];
                $data['init_weight']                =   $data['antenatal_followup'][0]['weight'];
                $data['init_fundal_height']         =   $data['antenatal_followup'][0]['fundal_height'];
                $data['init_hb']                    =   $data['antenatal_followup'][0]['hb'];
                $data['init_urine_alb']             =   $data['antenatal_followup'][0]['urine_alb'];
                $data['init_urine_sugar']           =   $data['antenatal_followup'][0]['urine_sugar'];
                $data['init_ankle_odema']           =   $data['antenatal_followup'][0]['ankle_odema'];
                $data['init_notes']                 =   $data['antenatal_followup'][0]['notes'];
                $data['init_next_followup']         =   $data['antenatal_followup'][0]['next_followup'];
                $data['init_glucose_tolerance_test'] =   $data['antenatal_followup'][0]['glucose_tolerance_test'];
                $data['init_vaginal_bleeding']  =   $data['antenatal_followup'][0]['vaginal_bleeding'];
                $data['init_vaginal_infection'] =   $data['antenatal_followup'][0]['vaginal_infection'];
                $data['init_urinary_tract_infection'] =   $data['antenatal_followup'][0]['urinary_tract_infection'];
                $data['init_blood_pressure']    =   $data['antenatal_followup'][0]['blood_pressure'];
                $data['init_fever']             =   $data['antenatal_followup'][0]['fever'];
                $data['init_pallor']            =   $data['antenatal_followup'][0]['pallor'];
                $data['init_abnormal_fundal_height'] =   $data['antenatal_followup'][0]['abnormal_fundal_height'];
                $data['init_movements']         =   $data['antenatal_followup'][0]['movements'];
                $data['init_abnormal_presentation'] =   $data['antenatal_followup'][0]['abnormal_presentation'];
                $data['init_fetal_heart_tones'] =   $data['antenatal_followup'][0]['fetal_heart_tones'];
                $data['init_missing_fetal_heartbeat']=   $data['antenatal_followup'][0]['missing_fetal_heartbeat'];
                $data['init_supplement_iodine'] =   $data['antenatal_followup'][0]['supplement_iodine'];
                $data['init_supplement_iron']   =   $data['antenatal_followup'][0]['supplement_iron'];
                $data['init_supplement_vitamins'] =   $data['antenatal_followup'][0]['supplement_vitamins'];
                $data['init_supplement_folate'] =   $data['antenatal_followup'][0]['supplement_folate'];
                $data['init_malaria_prophylaxis'] =   $data['antenatal_followup'][0]['malaria_prophylaxis'];
                $data['init_breastfeed_intention']=   $data['antenatal_followup'][0]['breastfeed_intention'];
                $data['init_advise_4_danger_signs']=   $data['antenatal_followup'][0]['advise_4_danger_signs'];
                $data['init_dental_checkup']    =   $data['antenatal_followup'][0]['dental_checkup'];
                $data['init_emergency_plans']   =   $data['antenatal_followup'][0]['emergency_plans'];
                $data['init_healthy_diet']      =   $data['antenatal_followup'][0]['healthy_diet'];
                $data['init_adequate_rest']     =   $data['antenatal_followup'][0]['adequate_rest'];
                $data['init_adequate_exercise'] =   $data['antenatal_followup'][0]['adequate_exercise'];
                $data['init_thirdtrimester_issues']=   $data['antenatal_followup'][0]['thirdtrimester_issues'];
                $data['init_followup_remarks']  =   $data['antenatal_followup'][0]['followup_remarks'];
                $data['init_risks']             =   $data['antenatal_followup'][0]['risks'];
                $data['init_synch_out']         =   $data['antenatal_followup'][0]['synch_out'];
            } //endif ($patient_id == "new_followup")
        } //endif(count($_POST))
		$data['followup_list']  = $this->memr_rdb->get_antenatal_followup('list',$data['patient_id'],$data['antenatal_id']);
        $data['init_recalc_duration'] = round((strtotime($data['init_record_date'])-strtotime($data['antenatal_info'][0]['lmp']))/(60*60*24*7),2);
        $data['init_recalc_months'] = round((strtotime($data['init_record_date'])-strtotime($data['antenatal_info'][0]['lmp']))/(60*60*24*30),2);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_antenatal_followup') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                $new_body   =   "ehr/ehr_indv_edit_his_antenatal_followup_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_indv_edit_his_antenatal_followup_html";
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
            if($data['antenatal_followup_id'] == "new_followup") {
                // New patient antenatal info
                $ins_followup_array   =   array();
                $ins_followup_array['staff_id']     = $_SESSION['staff_id'];
                $ins_followup_array['now_id']       = $data['now_id'];
                $ins_followup_array['antenatal_followup_id']= $data['now_id'];
                $ins_followup_array['patient_id']   = $data['init_patient_id'];
                $ins_followup_array['antenatal_id'] = $data['antenatal_id'];
                $ins_followup_array['event_id']     = $data['antenatal_id'];
                $ins_followup_array['session_id']   = $data['summary_id'];
                $ins_followup_array['record_date']  = $data['init_record_date'];
                $ins_followup_array['pregnancy_duration']= $data['init_pregnancy_duration'];
                $ins_followup_array['lie']          = $data['init_lie'];
                $ins_followup_array['weight']       = $data['init_weight'];
                if(is_numeric($data['init_fundal_height'])){
                    $ins_followup_array['fundal_height'] = $data['init_fundal_height'];
                }
                //$ins_followup_array['fundal_height']= $data['init_fundal_height'];
                $ins_followup_array['hb']           = $data['init_hb'];
                $ins_followup_array['urine_alb']    = $data['init_urine_alb'];
                $ins_followup_array['urine_sugar']  = $data['init_urine_sugar'];
                $ins_followup_array['ankle_odema']  = $data['init_ankle_odema'];
                $ins_followup_array['notes']        = $data['init_notes'];
                if(is_numeric($data['init_fundal_height2'])){
                    $ins_followup_array['fundal_height2'] = $data['init_fundal_height2'];
                }
                $ins_followup_array['next_followup']= $data['init_next_followup'];
                if($data['offline_mode']){
                    $ins_followup_array['synch_out']  = $data['now_id'];
                }
	            $ins_followup_data       =   $this->mantenatal_wdb->insert_new_antenatal_followup($ins_followup_array);
                $this->session->set_flashdata('data_activity', 'Checkup added.');
            } else {
                //Edit patient vital signs
                $upd_followup_array   =   array();
                $upd_followup_array['staff_id']           = $_SESSION['staff_id'];
                $upd_followup_array['now_id']             = $data['now_id'];
                $upd_followup_array['antenatal_followup_id']= $data['antenatal_followup_id'];
                //$upd_followup_array['patient_id']   = $data['init_patient_id'];
                //$upd_followup_array['antenatal_id'] = $data['antenatal_id'];
                //$upd_followup_array['event_id']     = $data['antenatal_id'];
                //$upd_followup_array['session_id']   = $data['summary_id'];
                $upd_followup_array['record_date']  = $data['init_record_date'];
                $upd_followup_array['pregnancy_duration']= $data['init_pregnancy_duration'];
                $upd_followup_array['lie']          = $data['init_lie'];
                $upd_followup_array['weight']       = $data['init_weight'];
                if(is_numeric($data['init_fundal_height'])){
                    $upd_followup_array['fundal_height'] = $data['init_fundal_height'];
                }
                //$ins_followup_array['fundal_height']= $data['init_fundal_height'];
                $upd_followup_array['hb']           = $data['init_hb'];
                $upd_followup_array['urine_alb']    = $data['init_urine_alb'];
                $upd_followup_array['urine_sugar']  = $data['init_urine_sugar'];
                $upd_followup_array['ankle_odema']  = $data['init_ankle_odema'];
                $upd_followup_array['notes']        = $data['init_notes'];
                if(is_numeric($data['init_fundal_height2'])){
                    $upd_followup_array['fundal_height2'] = $data['init_fundal_height2'];
                }
                $upd_followup_array['next_followup']= $data['init_next_followup'];
                if($data['offline_mode']){
                    if(!empty($data['init_synch_out'])){
                        // New patient updated offline - do nothing
                        //$upd_followup_array['synch_out']        = $data['now_id'];
                    } else {
                        // Old patient updated offline
                        $upd_followup_array['synch_in']        = $data['now_id'];
                    }
                }
                $upd_followup_array['update_when']              = $data['now_id'];
                $upd_followup_array['update_by']                = $_SESSION['staff_id'];
	            $ins_vitals_data       =   $this->mantenatal_wdb->update_antenatal_followup($upd_followup_array);
                $this->session->set_flashdata('data_activity', 'Checkup updated.');
                
            } //endif($data['patient_id'] == "new_followup")
            $new_page = base_url()."index.php/ehr_individual_antenatal/edit_history_antenatal/".$data['patient_id']."/edit_history/".$data['antenatal_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_antenatal_followup') == FALSE)
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_history_antenatal_followup()


    // ------------------------------------------------------------------------
    function edit_history_antenatal_delivery($id=NULL) 
    {
		//$this->load->model('mantenatal_wdb');
        $this->load->helper('antenatal','date');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
		$data['form_purpose']       =   $this->uri->segment(3);
        $data['patient_id']         =   $this->uri->segment(4);
        //$data['summary_id']       = $this->uri->segment(5);
        $patient_id                 =   $data['patient_id'];
        $data['init_patient_id']    =   $patient_id;
        $data['antenatal_id']       = $this->uri->segment(5);
        $data['antenatal_delivery_id']  = $this->uri->segment(6);
		//$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['patient_info']       = $this->memr_rdb->get_patient_demo($data['patient_id']);
 		$data['title']              = "PR-".$data['patient_info']['name'];
        $data['patcon_info']        = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
        //$data['patcon_info']  = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
        $data['antenatal_info']  = $this->memr_rdb->get_antenatal_list('Any',$data['patient_id'],$data['antenatal_id']);
        $data['antenatal_delivery']  = $this->memr_rdb->get_antenatal_delivery('Any',$data['patient_id'], $data['antenatal_id'], $data['antenatal_delivery_id']);
        if($data['patient_info']['family_link'] == "Head of Family" ){
            $data['family_below']  = $this->memr_rdb->get_family_relations('List','below',$data['patient_id']);
        } elseif($data['patient_info']['family_link'] == "Under Head of Family") {
            //echo "Not Head";
            // Find out who is the head
            $data['head_info']  = $this->memr_rdb->get_family_head($data['patient_id']);
            // Was family relationship created earlier?
            if(!empty($data['head_info'])){
                $data['family_head']  = $data['head_info'][0]['patient_id'];
            } else {
                $data['family_head']  = array();
            } //endif(!empty($data['head_info']))
            $data['family_below']  = $this->memr_rdb->get_family_relations('List','below',$data['family_head']);
        }

        if(count($_POST)) {
            // User has posted the form
            $data['no_error']               =   $_POST['no_error'];
            $data['now_id']                 =   $_POST['now_id'];
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $data['init_patient_id']        =   $_POST['patient_id'];
            $data['patient_id']             =   $data['init_patient_id'];
            //$data['summary_id']         =   $_POST['summary_id'];
            $data['init_antenatal_id']      =   $_POST['antenatal_id'];
            $data['antenatal_id']           =   $data['init_antenatal_id'];
            $data['init_date_admission']    =   $_POST['date_admission'];
            $data['init_time_admission']    =   $_POST['time_admission'];
            $data['init_date_delivery']     =   $_POST['date_delivery'];
            $data['init_time_delivery']     =   $_POST['time_delivery'];
            $data['init_delivery_type']     =   $_POST['delivery_type'];
            $data['init_delivery_place']    =   $_POST['delivery_place'];
            $data['init_mother_condition']  =   $_POST['mother_condition'];
            $data['init_baby_condition']    =   $_POST['baby_condition'];
            $data['init_baby_weight']       =   $_POST['baby_weight'];
            //$data['init_complication_icpc'] =   $_POST['complication_icpc'];
            $data['init_complication_notes']=   $_POST['complication_notes'];
            $data['init_birth_attendant']   =   $_POST['birth_attendant'];
            $data['init_breastfeed_immediate']=   $_POST['breastfeed_immediate'];
            $data['init_post_partum_bleed'] =   $_POST['post_partum_bleed'];
            //$data['init_apgar_score']     =   $_POST['apgar_score'];
            $data['init_child_id']        =   $_POST['child_id'];
            $data['init_delivery_remarks']  =   $_POST['delivery_remarks'];
            $data['init_delivery_outcome']  =   $_POST['delivery_outcome'];
            //$data['init_dcode1ext_code']    =   $_POST['dcode1ext_code'];
            
            if ($data['patient_id'] == "new_delivery"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'ADD ANTENATAL DELIVERY';
		        $data['patient_info']       = array();
            } else {
                // Edit form
          		$data['save_attempt']       = 'EDIT ANTENATAL DELIVERY';
                // These fields were passed through as hidden tags
                $data['patient_id']         =   $data['init_patient_id']; //came from POST
		        $data['patient_info']       =   $this->memr_rdb->get_patient_demo($data['patient_id']);
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

            if ($data['form_purpose'] == "new_delivery") {
                // New vitals
          		$data['save_attempt']        =   'ADD ANTENATAL DELIVERY';
	            //$data['antenatal_info']       =   array();
                $data['antenatal_delivery_id']  =   $data['antenatal_delivery'][0]['antenatal_delivery_id'];
                if(($data['now_id'] - strtotime($data['antenatal_info'][0]['lmp_edd'])) > 0){
                    $data['init_date_delivery']     =   $data['antenatal_info'][0]['lmp_edd'];
                } else {
                    $data['init_date_delivery']     =   $data['now_date'];
                }
                $data['init_time_admission']    =   NULL;
                $data['init_date_admission']    =   NULL;
                $data['init_time_delivery']     =   NULL;
                $data['init_delivery_type']     =   NULL;
                $data['init_delivery_place']    =   $data['antenatal_info'][0]['planned_place'];
                $data['init_mother_condition']  =   NULL;
                $data['init_baby_condition']    =   NULL;
                $data['init_baby_weight']       =   NULL;
                $data['init_complication_icpc'] =   NULL;
                $data['init_complication_notes']=   NULL;
                $data['init_baby_alive']        =   NULL;
                $data['init_birth_attendant']   =   NULL;
                $data['init_breastfeed_immediate']=   NULL;
                $data['init_post_partum_bleed'] =   NULL;
                $data['init_apgar_score']       =   NULL;
                $data['init_delivery_remarks']  =   NULL;
                $data['init_delivery_outcome']  =   NULL;
                $data['init_dcode1ext_code']    =   NULL;
            } else {
                // Editing delivery
          		$data['save_attempt'] = 'EDIT ANTENATAL DELIVERY';
                $data['init_date_admission']    =   $data['antenatal_delivery'][0]['date_admission'];
                $data['init_time_admission']    =   $data['antenatal_delivery'][0]['time_admission'];
                $data['init_date_delivery']     =   $data['antenatal_delivery'][0]['date_delivery'];
                $data['init_time_delivery']     =   $data['antenatal_delivery'][0]['time_delivery'];
                $data['init_delivery_type']     =   $data['antenatal_delivery'][0]['delivery_type'];
                $data['init_delivery_place']    =   $data['antenatal_delivery'][0]['delivery_place'];
                $data['init_mother_condition']  =   $data['antenatal_delivery'][0]['mother_condition'];
                $data['init_baby_condition']    =   $data['antenatal_delivery'][0]['baby_condition'];
                $data['init_baby_weight']       =   $data['antenatal_delivery'][0]['baby_weight'];
                $data['init_complication_icpc'] =   $data['antenatal_delivery'][0]['complication_icpc'];
                $data['init_complication_notes']=   $data['antenatal_delivery'][0]['complication_notes'];
                $data['init_baby_alive']        =   $data['antenatal_delivery'][0]['baby_alive'];
                $data['init_birth_attendant']   =   $data['antenatal_delivery'][0]['birth_attendant'];
                $data['init_breastfeed_immediate'] =   $data['antenatal_delivery'][0]['breastfeed_immediate'];
                $data['init_post_partum_bleed'] =   $data['antenatal_delivery'][0]['post_partum_bleed'];
                $data['init_apgar_score']       =   $data['antenatal_delivery'][0]['apgar_score'];
                $data['init_event_id']          =   $data['antenatal_delivery'][0]['event_id'];
                $data['init_child_id']          =   $data['antenatal_delivery'][0]['child_id'];
                $data['init_delivery_remarks']  =   $data['antenatal_delivery'][0]['delivery_remarks'];
                $data['init_delivery_outcome']  =   $data['antenatal_delivery'][0]['delivery_outcome'];
                $data['init_dcode1ext_code']    =   $data['antenatal_delivery'][0]['dcode1ext_code'];
            } //endif ($patient_id == "new_delivery")
        } //endif(count($_POST))
		$data['followup_list']  = $this->memr_rdb->get_antenatal_followup('list',$data['patient_id'],$data['antenatal_id']);
		$data['delivery_list']  = $this->memr_rdb->get_antenatal_delivery('list',$data['patient_id'],$data['antenatal_id']);
        //$broken_lmp             =   break_date($data['antenatal_info'][0]['lmp']);
        //$broken_delivery        =   break_date($data['init_date_delivery']);
        
        // Check for rational dates
        $data['error_messages'] =   array();
        if(!empty($data['antenatal_info'][0]['lmp'])){
            $data['error_messages'] =   antenatal_gestation_period(strtotime($data['antenatal_info'][0]['lmp']),strtotime($data['init_date_delivery']));
        } else {
            $data['error_messages']['severity'] =   "";
            $data['error_messages']['msg'] =   "";
        }
        if($data['error_messages']['severity'] == "Error: "){
            $data['no_error']   =   NULL;
            //$_POST['no_error']  =   NULL;
        } else {
            $data['no_error']   =   "TRUE";
            //$_POST['no_error']  =   "TRUE";
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_antenatal_delivery') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                $new_body   =   "ehr/ehr_indv_edit_his_antenatal_delivery_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_indv_edit_his_antenatal_delivery_html";
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
            if($data['antenatal_delivery_id'] == "new_delivery") {
                // New patient antenatal info
                $ins_delivery_array   =   array();
                $ins_delivery_array['staff_id']         = $_SESSION['staff_id'];
                $ins_delivery_array['now_id']           = $data['now_id'];
                $ins_delivery_array['antenatal_delivery_id']= $data['now_id'];
                $ins_delivery_array['patient_id']       = $data['init_patient_id'];
                $ins_delivery_array['antenatal_id']     = $data['antenatal_id'];
                //$ins_delivery_array['session_id']       = $data['summary_id'];
                if(!empty($data['init_date_admission'])){
                    $ins_delivery_array['date_admission']= $data['init_date_admission'];
                }
                //$ins_delivery_array['date_admission']  = $data['init_date_admission'];
                if(!empty($data['init_time_admission'])){
                    $ins_delivery_array['time_admission'] = $data['init_time_admission'];
                }
                //$ins_delivery_array['time_admission']  = $data['init_time_admission'];
                $ins_delivery_array['date_delivery']    = $data['init_date_delivery'];
                if(!empty($data['init_time_delivery'])){
                    $ins_delivery_array['time_delivery'] = $data['init_time_delivery'];
                }
                //$ins_delivery_array['time_delivery']       = $data['init_time_delivery'];
                $ins_delivery_array['delivery_type']    = $data['init_delivery_type'];
                $ins_delivery_array['delivery_place']   = $data['init_delivery_place'];
                $ins_delivery_array['mother_condition'] = $data['init_mother_condition'];
                $ins_delivery_array['baby_condition']   = $data['init_baby_condition'];
                if(is_numeric($data['init_baby_weight'])){
                    $ins_delivery_array['baby_weight']             = $data['init_baby_weight'];
                }
                //$ins_delivery_array['baby_weight']      = $data['init_baby_weight'];
                if(!empty($data['init_complication_icpc'])){
                    $ins_delivery_array['complication_icpc'] = $data['init_complication_icpc'];
                }
                //$ins_delivery_array['complication_icpc'] = $data['init_complication_icpc'];
                $ins_delivery_array['complication_notes']  = $data['init_complication_notes'];
                if($data['init_baby_condition']=='Dead'){
                    $ins_delivery_array['baby_alive']       = FALSE;
                } else {
                    $ins_delivery_array['baby_alive']       = TRUE;
                }
                $ins_delivery_array['birth_attendant']  = $data['init_birth_attendant'];
                $ins_delivery_array['breastfeed_immediate']   = $data['init_breastfeed_immediate'];
                $ins_delivery_array['post_partum_bleed']= $data['init_post_partum_bleed'];
                //$ins_delivery_array['apgar_score']    = $data['init_apgar_score'];
                $ins_delivery_array['event_id']         = $data['antenatal_id'];
                $ins_delivery_array['child_id']         = $data['init_child_id'];
                $ins_delivery_array['delivery_remarks'] = $data['init_delivery_remarks'];
                $ins_delivery_array['delivery_outcome'] = $data['init_delivery_outcome'];
                //$ins_delivery_array['dcode1ext_code']   = $data['init_dcode1ext_code'];
                if($data['offline_mode']){
                    $ins_delivery_array['synch_out']    = $data['now_id'];
                }
                $ins_delivery_array['synch_remarks']   = NULL;
	            $ins_delivery_data       =   $this->mantenatal_wdb->insert_new_antenatal_delivery($ins_delivery_array);
                $this->session->set_flashdata('data_activity', 'Delivery added. '.$data['error_messages']['severity'].$data['error_messages']['msg']);
            } else {
                //Edit patient antenatal delivery
                $upd_delivery_array   =   array();
                $upd_delivery_array['staff_id']           = $_SESSION['staff_id'];
                $upd_delivery_array['now_id']             = $data['now_id'];
                $upd_delivery_array['antenatal_delivery_id']  = $data['antenatal_delivery_id'];
                if(!empty($data['init_date_admission'])){
                    $upd_delivery_array['date_admission']   = $data['init_date_admission'];
                }
                //$upd_delivery_array['date_admission']     = $data['init_date_admission'];
                if(!empty($data['init_time_admission'])){
                    $upd_delivery_array['time_admission']   = $data['init_time_admission'];
                }
                //$upd_delivery_array['time_admission']     = $data['init_time_admission'];
                $upd_delivery_array['date_delivery']        = $data['init_date_delivery'];
                if(!empty($data['init_time_delivery'])){
                    $upd_delivery_array['time_delivery']    = $data['init_time_delivery'];
                }
                //$upd_delivery_array['time_delivery']      = $data['init_time_delivery'];
                $upd_delivery_array['delivery_type']        = $data['init_delivery_type'];
                $upd_delivery_array['delivery_place']       = $data['init_delivery_place'];
                $upd_delivery_array['mother_condition']     = $data['init_mother_condition'];
                $upd_delivery_array['baby_condition']       = $data['init_baby_condition'];
                if(!empty($data['init_baby_weight'])){
                    $upd_delivery_array['baby_weight']    = $data['init_baby_weight'];
                }
                //$upd_delivery_array['baby_weight']          = $data['init_baby_weight'];
                if(!empty($data['init_complication_icpc'])){
                    $upd_delivery_array['complication_icpc'] = $data['init_complication_icpc'];
                }
                //$upd_delivery_array['complication_icpc']  = $data['init_complication_icpc'];
                $upd_delivery_array['complication_notes']   = $data['init_complication_notes'];
                if($data['init_baby_condition']=='Dead'){
                    $upd_delivery_array['baby_alive']       = FALSE;
                } else {
                    $upd_delivery_array['baby_alive']       = TRUE;
                }
                $upd_delivery_array['birth_attendant']      = $data['init_birth_attendant'];
                $upd_delivery_array['breastfeed_immediate'] = $data['init_breastfeed_immediate'];
                $upd_delivery_array['post_partum_bleed']    = $data['init_post_partum_bleed'];
                //$upd_delivery_array['apgar_score']        = $data['init_apgar_score'];
                $upd_delivery_array['child_id']             = $data['init_child_id'];
                $upd_delivery_array['delivery_remarks']     = $data['init_delivery_remarks'];
                $upd_delivery_array['delivery_outcome']     = $data['init_delivery_outcome'];
                $upd_delivery_array['dcode1ext_code']       = $data['init_dcode1ext_code'];
                if($data['offline_mode']){
                    $upd_delivery_array['synch_out']    = $data['now_id'];
                }
                $upd_delivery_array['synch_remarks']        = NIL;
	            $upd_delivery_data       =   $this->mantenatal_wdb->update_antenatal_delivery($upd_delivery_array);
                $this->session->set_flashdata('data_activity', 'Delivery updated. '.$data['error_messages']['severity'].$data['error_messages']['msg']);
                
            } //endif($data['patient_id'] == "new_followup")
            $new_page = base_url()."index.php/ehr_individual_antenatal/edit_history_antenatal/".$data['patient_id']."/edit_history/".$data['antenatal_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_antenatal_delivery') == FALSE)
    } //end of function edit_history_antenatal_delivery()


    // ------------------------------------------------------------------------
    function edit_history_antenatal_postpartum($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['title']              = 'Antenatal Follow-up';
		$data['form_purpose']       = $this->uri->segment(3);
        $data['patient_id']         = $this->uri->segment(4);
        $data['summary_id']         = $this->uri->segment(5);
        $patient_id                 =   $data['patient_id'];
        $data['init_patient_id']    =   $patient_id;
        $data['antenatal_id']       = $this->uri->segment(6);
        $data['antenatal_postpartum_id'] = $this->uri->segment(7);
		//$data['clinic_info']      = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['patient_info']       = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']        = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['patient_info']       = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']        = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
        $data['antenatal_info']     = $this->memr_rdb->get_antenatal_list('Open',$data['patient_id'],$data['antenatal_id']);
        $data['postpartum_info']  = $this->mantenatal_rdb->get_antenatal_postpartum('Open',$data['patient_id'],$data['antenatal_id'],$data['antenatal_postpartum_id']);
		$data['delivery_list']  = $this->memr_rdb->get_antenatal_delivery('list',$data['patient_id'],$data['antenatal_id']);

        if(count($_POST)) {
            // User has posted the form
            $data['save_attempt']           =   $_POST['save_attempt'];
            $data['now_id']                 =   $_POST['now_id'];
            $data['init_patient_id']        =   $_POST['patient_id'];
            $data['patient_id']             =   $data['init_patient_id'];
            $data['summary_id']             =   $_POST['summary_id'];
            $data['init_antenatal_id']      =   $_POST['antenatal_id'];
            $data['antenatal_id']           =   $data['init_antenatal_id'];
            //$data['init_event_id']        =   $_POST['event_id'];
            $data['init_care_date']         =   $_POST['care_date'];
            $data['init_termination_date']  =   $_POST['termination_date'];
            $data['init_breastfeed']        =   $_POST['breastfeed'];
            $data['init_want_family_planning']=   $_POST['want_family_planning'];
            $data['init_fever_38']          =   $_POST['fever_38'];
            $data['init_vaginal_discharge'] =   $_POST['vaginal_discharge'];
            $data['init_vaginal_bleeding']  =   $_POST['vaginal_bleeding'];
            $data['init_pallor']            =   $_POST['pallor'];
            $data['init_cord']              =   $_POST['cord'];
            $data['init_postpartum_remarks']=   $_POST['postpartum_remarks'];
            /*
            if ($data['patient_id'] == "new_antenatal"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'ADD ANTENATAL INFO';
		        $data['patient_info']       = array();
            } else {
                // Edit form
          		$data['save_attempt']       = 'EDIT ANTENATAL INFO';
                // These fields were passed through as hidden tags
                $data['patient_id']         =   $data['init_patient_id']; //came from POST
		        $data['patient_info']       =   $this->memr_rdb->get_patient_demo($data['patient_id']);
                $data['init_patient_id']    =   $data['patient_info']['patient_id'];
                //$data['init_ic_other_no']   =   $data['patient_info']['ic_other_no'];
            } //endif ($patient_id == "new_patient")
            */
        } else {
            // First time form is displayed
            $data['init_location_id']   =   $_SESSION['location_id'];
            $data['init_end_date']      =   NULL;
            $data['init_clinic_name']   =   NULL;
            $data['now_id']             =   time();
            $data['now_date']           =   date("Y-m-d",$data['now_id']);

            if ($data['form_purpose'] == "new_postpartum") {
                // New vitals
          		$data['save_attempt']       =   'ADD POSTPARTUM CARE';
                $data['init_care_date']             =   $data['now_date'];
                $data['init_termination_date']      =   $data['delivery_list'][0]['date_delivery'];
                $data['init_breastfeed']            =   NULL;
                $data['init_want_family_planning']  =   NULL;
                $data['init_fever_38']              =   NULL;
                $data['init_vaginal_discharge']     =   NULL;
                $data['init_vaginal_bleeding']      =   NULL;
                $data['init_pallor']                =   NULL;
                $data['init_cord']                  =   NULL;
                $data['init_postpartum_remarks']    =   NULL;
            } else {
                // Editing followup
          		$data['save_attempt']       = 'EDIT POSTPARTUM CARE';
                $data['init_care_date']             =   $data['postpartum_info'][0]['care_date'];
                $data['init_termination_date']      =   $data['postpartum_info'][0]['termination_date'];
                $data['init_breastfeed']            =   $data['postpartum_info'][0]['breastfeed'];
                $data['init_want_family_planning']  =   $data['postpartum_info'][0]['want_family_planning'];
                $data['init_fever_38']              =   $data['postpartum_info'][0]['fever_38'];
                $data['init_vaginal_discharge']     =   $data['postpartum_info'][0]['vaginal_discharge'];
                $data['init_vaginal_bleeding']      =   $data['postpartum_info'][0]['vaginal_bleeding'];
                $data['init_pallor']                =   $data['postpartum_info'][0]['pallor'];
                $data['init_cord']                  =   $data['postpartum_info'][0]['cord'];
                $data['init_postpartum_remarks']    =   $data['postpartum_info'][0]['postpartum_remarks'];
            } //endif ($patient_id == "new_followup")
        } //endif(count($_POST))
        $data['init_visit_period'] =   round((strtotime($data['init_care_date'])-strtotime($data['init_termination_date']))/(60*60*24),1);
        $data['event_id']          =   $data['antenatal_info'][0]['event_id'];
        $data['postpartum_list']  = $this->mantenatal_rdb->get_antenatal_postpartum('list',$data['patient_id'],$data['antenatal_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_antenatal_postpartum') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                $new_body   =   "ehr/ehr_indv_edit_his_antenatal_postpartum_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_indv_edit_his_antenatal_postpartum_html";
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
            if($data['antenatal_postpartum_id'] == "new_postpartum") {
                // New patient antenatal info
                $ins_postpartum_array   =   array();
                $ins_postpartum_array['staff_id']         = $_SESSION['staff_id'];
                $ins_postpartum_array['now_id']           = $data['now_id'];
                $ins_postpartum_array['antenatal_postpartum_id']= $data['now_id'];
                $ins_postpartum_array['antenatal_id']     = $data['antenatal_id'];
                $ins_postpartum_array['session_id']           = $data['summary_id'];
                $ins_postpartum_array['event_id']         = $data['antenatal_id'];
                $ins_postpartum_array['care_date']      = $data['init_care_date'];
                $ins_postpartum_array['termination_date']      = $data['init_termination_date'];
                $ins_postpartum_array['breastfeed']      = $data['init_breastfeed'];
                $ins_postpartum_array['want_family_planning']      = $data['init_want_family_planning'];
                $ins_postpartum_array['fever_38']      = $data['init_fever_38'];
                $ins_postpartum_array['vaginal_discharge']      = $data['init_vaginal_discharge'];
                $ins_postpartum_array['vaginal_bleeding']      = $data['init_vaginal_bleeding'];
                $ins_postpartum_array['pallor']      = $data['init_pallor'];
                $ins_postpartum_array['cord']      = $data['init_cord'];
                $ins_postpartum_array['postpartum_remarks']      = $data['init_postpartum_remarks'];
                if($data['offline_mode']){
                    $ins_postpartum_array['synch_out']        = $data['now_id'];
                }
	            $ins_postpartum_data       =   $this->mantenatal_wdb->insert_new_antenatal_postpartum($ins_postpartum_array);
                $this->session->set_flashdata('data_activity', 'Postpartum care added.');
            } else {
                //Edit patient vital signs
                $upd_postpartum_array   =   array();
                $upd_postpartum_array['staff_id']           = $_SESSION['staff_id'];
                $upd_postpartum_array['now_id']             = $data['now_id'];
                $upd_postpartum_array['antenatal_postpartum_id']= $data['antenatal_postpartum_id'];
                //$upd_postpartum_array['patient_id']       = $data['init_patient_id'];
                $upd_postpartum_array['care_date']          = $data['init_care_date'];
                $upd_postpartum_array['termination_date']   = $data['init_termination_date'];
                $upd_postpartum_array['breastfeed']         = $data['init_breastfeed'];
                $upd_postpartum_array['want_family_planning'] = $data['init_want_family_planning'];
                $upd_postpartum_array['fever_38']           = $data['init_fever_38'];
                $upd_postpartum_array['vaginal_discharge']  = $data['init_vaginal_discharge'];
                $upd_postpartum_array['vaginal_bleeding']   = $data['init_vaginal_bleeding'];
                $upd_postpartum_array['pallor']             = $data['init_pallor'];
                $upd_postpartum_array['cord']               = $data['init_cord'];
                $upd_postpartum_array['postpartum_remarks'] = $data['init_postpartum_remarks'];
	            $upd_postpartum_data       =   $this->mantenatal_wdb->update_antenatal_postpartum($upd_postpartum_array);
                $this->session->set_flashdata('data_activity', 'Postpartum care updated.');
                
            } //endif($data['patient_id'] == "new_followup")
            $new_page = base_url()."index.php/ehr_individual_antenatal/edit_antenatal_info/edit_antenatal/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_antenatal_followup') == FALSE)
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_history_antenatal_postpartum()


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

/* End of file ehr_individual_antenatal.php */
/* Location: ./app_thirra/controllers/ehr_individual_antenatal.php */
