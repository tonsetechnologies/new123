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
 * Portions created by the Initial Developer are Copyright (C) 2009 - 2011
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

/**
 * Controller Class for EHR_QUEUE
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_queue extends MY_Controller 
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
		$this->load->model('mqueue_rdb');
		$this->load->model('mqueue_wdb');
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

        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-1);
        $data['pics_url']      =    $data['pics_url']."-uploads/";
        define("PICS_URL", $data['pics_url']);
    }


    // ------------------------------------------------------------------------
    // === QUEUE MANAGEMENT
    // ------------------------------------------------------------------------
    function queue_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('mqueue_rdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_queue/queue_mgt','Queue');    
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['location_id']   =   $_SESSION['location_id'];
		$data['title'] = "T H I R R A - Queue Management";
		$data['queue_info'] = $this->mqueue_rdb->get_patients_queue($data['location_id']);
		$data['all_queue'] = $this->mqueue_rdb->get_patients_queue();
		$data['postcon_info'] = $this->mqueue_rdb->get_postconsultation_queue();
        $data['rooms_list'] = $this->mqueue_rdb->get_rooms_list($data['location_id']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_queue_wap";
            //$new_body   =   "ehr/ehr_queue_mgt_wap";
            $new_body   =   "ehr/ehr_queue_mgt_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_queue_html";
            $new_body   =   "ehr/ehr_queue_mgt_html";
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
    } // end of function queue_mgt($id)


    // ------------------------------------------------------------------------
    function queue_edit_queue()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('madmin_rdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_queue/queue_mgt','Queue');    
        $data['location_id']   =   $_SESSION['location_id'];
		$data['title'] = 'Add/Edit Queue';
		$data['form_purpose']   = $this->uri->segment(3);
        $data['patient_id']     = $this->uri->segment(4);
        $data['booking_id']     = $this->uri->segment(5);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		//$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);

        if(count($_POST)) {
            // User has posted the form
            //$data['now_id']                   =   $_POST['now_id'];
            //$data['now_date']                 =   date("Y-m-d",$data['now_id']);
            $data['init_location_id']           =   $_SESSION['location_id'];
            $data['form_purpose']      	        =   $this->input->post('form_purpose');
            $data['init_booking_id']      	    =   $this->input->post('booking_id');
            $data['booking_id']                 =   $data['init_booking_id'];
            $data['init_room_id']      	        =   $this->input->post('room_id');
            $data['init_staff_id']      	    =   $this->input->post('staff_id');
            $data['init_queue_date']      	    =   $this->input->post('queue_date');
            $data['init_start_time']      	    =   $this->input->post('start_time');
            $data['init_remarks']      	        =   $this->input->post('remarks');
            $data['init_priority']      	    =   $this->input->post('priority');
            $data['init_external_ref']      	=   $this->input->post('external_ref');
            $data['init_canceled_reason']      	=   $this->input->post('canceled_reason');
           if(isset($_POST['patient_id'])) {
                $data['init_patient_id']      	    =   $this->input->post('patient_id');
            } else {
                $data['init_patient_id']          = "";
            }
            if ($data['form_purpose'] == "new_queue") {            
                $data['patients_list'] = $this->memr_rdb->get_patients_list($data['location_id']);
            }
            $data['patient_id']               =   $data['init_patient_id'];
            if ($data['booking_id'] == "new_queue"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'ADD TO QUEUE';
            } //endif ($data['booking_id'] == "new_queue")
            /*
            } else {
                // Edit form
          		$data['save_attempt']       = 'EDIT QUEUE';
                // These fields were passed through as hidden tags
                //$data['patient_id']         =   $data['init_patient_id']; //came from POST
                //$data['init_patient_id']    =   $data['patient_info']['patient_id'];
            } //endif ($data['booking_id'] == "new_queue")
            */
        } else {
            // First time form is displayed
            $data['init_clinic_name']   =   NULL;
            $data['booking_id']         = $this->uri->segment(5);
            $data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);

            if ($data['form_purpose'] == "new_queue") {
                // New vitals
          		$data['save_attempt']            =   'ADD TO QUEUE';
		        //$data['patient_info']       = array();
                //$data['init_booking_id']          =   "";
                //$data['booking_id']               =   "";
                $data['init_room_id']             =   "";
                $data['init_staff_id']            =   "";
                $data['init_reserve_date']        =   "";
                $data['init_reserve_time']        =   "";
                $data['init_jqqueue_date']          =   "10/20/2010";
                $data['init_queue_date']          =   $data['now_date'];
                $data['init_start_time']          =   $data['now_time'];
                $data['init_end_time']            =   "";
                $data['init_remarks']             =   "";
                $data['init_priority']            =   "";
                $data['init_canceled_reason']     =   "";
                $data['init_external_ref']        =   "";
                $data['init_name']                =   "";
                $data['init_gender']              =   "";
                $data['init_birth_date']          =   "";
                if($data['patient_id'] == "patient_id") {
                    $data['patient_scope'] 		= $data['location_id'];
                    $data['list_sort'] 			= "name";
                    $data['alphabet'] 			= "All";
                    $data['patients_list'] = $this->memr_rdb->get_patients_list($data['location_id'],$data['list_sort'],NULL,$data['alphabet']);
                    $data['patient_id'] = "";
                } else {
                    $data['patients_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
                    $data['init_name']         = $data['patients_info']['patient_name'];
                    $data['init_gender']       = $data['patients_info']['gender'];
                    $data['init_birth_date']   = $data['patients_info']['birth_date'];
               }
            } else {
                // Editing vitals
          		$data['save_attempt'] = 'EDIT QUEUE';
                $data['init_patient_id']   = $data['patient_id'];
                $data['queue_info'] = $this->mqueue_rdb->get_patients_queue($data['location_id'],"any",$data['booking_id']);
                $data['init_room_id']      = $data['queue_info'][0]['room_id'];
                $data['init_staff_id']     = $data['queue_info'][0]['staff_id'];
                $data['init_queue_date']   = $data['queue_info'][0]['date'];
                $data['init_start_time']   = $data['queue_info'][0]['start_time'];
                $data['init_end_time']     = $data['queue_info'][0]['end_time'];
                $data['init_remarks']      = $data['queue_info'][0]['remarks'];
                $data['init_booking_type'] = $data['queue_info'][0]['booking_type'];
                $data['init_priority']     = $data['queue_info'][0]['priority'];
                $data['init_status']       = $data['queue_info'][0]['status'];
                $data['init_canceled_reason']= $data['queue_info'][0]['canceled_reason'];
                $data['init_previous_session_id']= $data['queue_info'][0]['previous_session_id'];
                $data['init_external_ref'] = $data['queue_info'][0]['external_ref'];
                $data['init_name']         = $data['queue_info'][0]['name'];
                $data['init_gender']       = $data['queue_info'][0]['gender'];
                $data['init_birth_date']   = $data['queue_info'][0]['birth_date'];
            } //endif ($data['form_purpose'] == "new_queue")
        } //endif(count($_POST))
        $data['staff_list'] = $this->madmin_rdb->get_staff_list("doctor");
        $data['rooms_list'] = $this->mqueue_rdb->get_rooms_list($data['location_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_queue') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_queue_wap";
                //$new_body   =   "ehr/emr_queue_edit_queue_wap";
                $new_body   =   "ehr/ehr_queue_edit_queue_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_queue_html";
                $new_body   =   "ehr/ehr_queue_edit_queue_html";
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
            if($data['booking_id'] == "new_queue") {
                // New patient record
                $ins_queue_array   =   array();
                $ins_queue_array['now_id']          = $data['now_id'];
                $ins_queue_array['booking_id']      = $data['now_id'];
                $ins_queue_array['room_id']         = $data['init_room_id'];
                $ins_queue_array['staff_id']        = $data['init_staff_id'];
                $ins_queue_array['booking_staff_id']= $_SESSION['staff_id'];
                $ins_queue_array['patient_id']      = $data['init_patient_id'];
                $ins_queue_array['reserve_date']    = $data['now_date'];
                $ins_queue_array['reserve_time']    = $data['now_time'];
                $ins_queue_array['date']            = $data['init_queue_date'];
                $ins_queue_array['start_time']      = $data['init_start_time'];
                // OSM To COMPUTE end time
                $ins_queue_array['end_time']        = $data['init_start_time'];
                $ins_queue_array['remarks']         = $data['init_remarks'];
                $ins_queue_array['booking_type']    = "External";
                $ins_queue_array['priority']        = $data['init_priority'];
                $ins_queue_array['status']          = "Pending";
                $ins_queue_array['external_ref']    = $data['init_external_ref'];
	            $ins_queue_data       =   $this->mqueue_wdb->insert_new_booking($ins_queue_array);
                $this->session->set_flashdata('data_activity', 'Patient added to queue.');
            } else {
                // Edit patient queue
                $ins_queue_array   =   array();
                $ins_queue_array['now_id']          = $data['now_id'];
                $ins_queue_array['booking_id']      = $data['booking_id'];
                $ins_queue_array['room_id']         = $data['init_room_id'];
                $ins_queue_array['staff_id']        = $data['init_staff_id'];
                $ins_queue_array['booking_staff_id']= $_SESSION['staff_id'];
                $ins_queue_array['patient_id']      = $data['init_patient_id'];
                $ins_queue_array['reserve_date']    = $data['now_date'];
                $ins_queue_array['reserve_time']    = $data['now_time'];
                $ins_queue_array['date']            = $data['init_queue_date'];
                $ins_queue_array['start_time']      = $data['init_start_time'];
                // OSM To COMPUTE end time
                $ins_queue_array['end_time']        = $data['init_start_time'];
                $ins_queue_array['remarks']         = $data['init_remarks'];
                $ins_queue_array['booking_type']    = "External";
                $ins_queue_array['priority']        = $data['init_priority'];
                if(empty($data['init_canceled_reason'])){
                    $ins_queue_array['status']          = "Pending";
                } else {
                    $ins_queue_array['status']          = "Cancelled";
                    $ins_queue_array['canceled_reason']        = $data['now_date']." ".$_SESSION['username'].":".$data['init_canceled_reason'];
                } //endif(empty($data['init_canceled_reason']))
                $ins_queue_array['external_ref']    = $data['init_external_ref'];
	            $ins_queue_data       =   $this->mqueue_wdb->update_booking($ins_queue_array);
                $this->session->set_flashdata('data_activity', 'Patient queue updated.');
            } //endif($data['patient_id'] == "new_queue")
            $new_page = base_url()."index.php/ehr_queue/queue_mgt";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_vitals') == FALSE)
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function queue_edit_queue()


    // ------------------------------------------------------------------------
    function queue_edit_room($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('madmin_rdb');
		$this->load->model('mqueue_rdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_queue/queue_mgt','Queue');    
		$data['form_purpose']   = $this->uri->segment(3);
		$data['room_id']        = $this->uri->segment(4);
        $data['location_id']   =   $_SESSION['location_id'];
		$data['title'] = "Add New / Edit Room";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['rooms_list'] = $this->mqueue_rdb->get_rooms_list($data['location_id']);
        $data['dept_list'] = $this->madmin_rdb->get_depts_list($data['location_id']);
        $data['category_list'] = $this->mqueue_rdb->get_room_categories();
        
        if(count($_POST)) {
            // User has posted the form
            $data['room_id']            =   $_POST['room_id'];
            $data['init_clinic_dept_id']=   $_POST['clinic_dept_id'];
            $data['clinic_dept_id']     =   $data['init_clinic_dept_id'];
            $data['category_id']        =   $_POST['category_id'];
            $data['init_room_name']     =   $_POST['room_name'];
            $data['init_description']   =   $_POST['description'];
            $data['init_room_rate1']    =   $_POST['room_rate1'];
            $data['init_room_rate2']    =   $_POST['room_rate2'];
            $data['init_room_rate3']    =   $_POST['room_rate3'];
            $data['init_room_cost']     =   $_POST['room_cost'];
            $data['init_beds_qty']      =   $_POST['beds_qty'];
            $data['init_room_floor']    =   $_POST['room_floor'];
            $data['init_room_remarks']  =   $_POST['room_remarks'];
            $data['init_room_code']     =   $_POST['room_code'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_room") {
                // New user
		        $data['room_info']          =  array();
                $data['room_id']            =   "";
                $data['init_clinic_dept_id'] =   "";
                $data['clinic_dept_id']     =   "";
                $data['category_id']        =   "";
                $data['init_room_name']     =   "";
                $data['init_description']   =   "";
                $data['location_id']        =   "";
                $data['init_room_rate1']    =   0;
                $data['init_room_rate2']    =   0;
                $data['init_room_rate3']    =   0;
                $data['init_room_cost']     =   0;
                $data['init_beds_qty']      =   1;
                $data['init_room_floor']    =   " floor";
                $data['init_room_remarks']   =   "";
                $data['init_room_code']   =   "";
            } else {
                // Existing user
		        $data['room_info'] =  $this->mqueue_rdb->get_one_room($data['room_id']);
                $data['init_clinic_dept_id']=   $data['room_info']['clinic_dept_id'];
                $data['clinic_dept_id']   =   $data['init_clinic_dept_id'];
                $data['category_id']      =   $data['room_info']['category_id'];
                $data['init_room_name']   =   $data['room_info']['name'];
                $data['init_description'] =   $data['room_info']['description'];
                $data['init_room_rate1']  =   $data['room_info']['room_rate1'];
                $data['init_room_rate2']  =   $data['room_info']['room_rate2'];
                $data['init_room_rate3']  =   $data['room_info']['room_rate3'];
                $data['init_room_cost']   =   $data['room_info']['room_cost'];
                $data['init_beds_qty']   =   $data['room_info']['beds_qty'];
                $data['init_room_floor']   =   $data['room_info']['room_floor'];
                $data['init_room_remarks'] =   $data['room_info']['room_remarks'];
                $data['init_room_code'] =   $data['room_info']['room_code'];
            } //endif ($data['form_purpose'] == "new_room")
        } //endif(count($_POST))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_room') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_queue_wap";
                $new_body   =   "ehr/ehr_queue_edit_room_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_queue_edit_room_html";
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
            if($data['form_purpose'] == "new_room") {
                // Insert records
                $ins_room_array['room_id']      = $data['now_id'];
                $ins_room_array['clinic_dept_id']  = $data['clinic_dept_id'];
                $ins_room_array['category_id']  = $data['category_id'];
                $ins_room_array['room_name']    = $data['init_room_name'];
                $ins_room_array['description']  = $data['init_description'];
                $ins_room_array['location_id']  = $data['location_id'];
                if(is_numeric($data['init_room_rate1'])){
                    $ins_room_array['room_rate1']                = $data['init_room_rate1'];
                }
                //$ins_room_array['room_rate1']   = $data['init_room_rate1'];
                if(is_numeric($data['init_room_rate2'])){
                    $ins_room_array['room_rate2']                = $data['init_room_rate2'];
                }
                //$ins_room_array['room_rate2']   = $data['init_room_rate2'];
                if(is_numeric($data['init_room_rate3'])){
                    $ins_room_array['room_rate3']                = $data['init_room_rate3'];
                }
                //$ins_room_array['room_rate3']   = $data['init_room_rate3'];
                if(is_numeric($data['init_room_cost'])){
                    $ins_room_array['room_cost']                = $data['init_room_cost'];
                }
                //$ins_room_array['room_cost']    = $data['init_room_cost'];
                if(is_numeric($data['init_beds_qty'])){
                    $ins_room_array['beds_qty']                = $data['init_beds_qty'];
                }
                //$ins_room_array['beds_qty']     = $data['init_beds_qty'];
                $ins_room_array['room_floor']   = $data['init_room_floor'];
                $ins_room_array['room_remarks']  = $data['init_room_remarks'];
                $ins_room_array['room_code']  = $data['init_room_code'];
                $ins_room_data =   $this->mqueue_wdb->insert_new_room($ins_room_array);
                $this->session->set_flashdata('data_activity', 'Room added.');
            } else {
                // Update records
                $upd_room_array['room_id']      = $data['room_id'];
                $upd_room_array['clinic_dept_id']  = $data['clinic_dept_id'];
                $upd_room_array['category_id']  = $data['category_id'];
                $upd_room_array['room_name']    = $data['init_room_name'];
                $upd_room_array['description']  = $data['init_description'];
                $upd_room_array['location_id']  = $data['location_id'];
                if(is_numeric($data['init_room_rate1'])){
                    $upd_room_array['room_rate1']                = $data['init_room_rate1'];
                }
                //$upd_room_array['room_rate1']   = $data['init_room_rate1'];
                if(is_numeric($data['init_room_rate2'])){
                    $upd_room_array['room_rate2']                = $data['init_room_rate2'];
                }
                //$upd_room_array['room_rate2']   = $data['init_room_rate2'];
                if(is_numeric($data['init_room_rate3'])){
                    $upd_room_array['room_rate3']                = $data['init_room_rate3'];
                }
                //$upd_room_array['room_rate3']   = $data['init_room_rate3'];
                if(is_numeric($data['init_room_cost'])){
                    $upd_room_array['room_cost']                = $data['init_room_cost'];
                }
                //$upd_room_array['room_cost']    = $data['init_room_cost'];
                if(is_numeric($data['init_beds_qty'])){
                    $upd_room_array['beds_qty']                = $data['init_beds_qty'];
                }
                //$upd_room_array['beds_qty']     = $data['init_beds_qty'];
                $upd_room_array['room_floor']   = $data['init_room_floor'];
                $upd_room_array['room_remarks']  = $data['init_room_remarks'];
                $upd_room_array['room_code']  = $data['init_room_code'];
                $upd_room_data =   $this->mqueue_wdb->update_room($upd_room_array);
                $this->session->set_flashdata('data_activity', 'Room updated.');
            } //endif($data['form_purpose'] == "new_room")
            $new_page = base_url()."index.php/ehr_queue/queue_mgt";
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_room') == FALSE)
    } // end of function queue_edit_room($id)


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
    function test_email($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['smtp_host']		    =	$this->config->item('smtp_host');
        $data['smtp_user']		    =	$this->config->item('smtp_user');
        $data['smtp_pass']		    =	$this->config->item('smtp_pass');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "T H I R R A - NewPage";

        $this->load->library('email');
        //$config['protocol'] = 'sendmail';
        //$config['mailpath'] = '/usr/sbin/sendmail';
        $config['protocol'] = 'smtp';
        $config['smtp_host'] = $data['smtp_host'];
        $config['smtp_user'] = $data['smtp_user'];
        $config['smtp_pass'] = $data['smtp_pass'];
        $config['smtp_port'] = 465;
        $config['charset'] = 'iso-8859-1';
        $config['wordwrap'] = TRUE;
        $this->email->initialize($config);
        $this->email->set_newline("\r\n"); // Important
        $this->email->from('jasontn@gmail.com', 'Boon');
        $this->email->to('jasontan@mail.com', 'Jason Tan');
        //$this->email->cc('another@person.com');
        //$this->email->bcc('theboss@example.com');
        $this->email->subject('Email Test');
        $this->email->message('Testing e-mail from CodeIgniter.');
        
        
        $this->email->send();
        echo $this->email->print_debugger();

        $this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/emr_newpage_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_queue'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
        /*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);
        */
    } // end of function test_email($id)


    // ------------------------------------------------------------------------
    // === TEMPLATES
    // ------------------------------------------------------------------------
    function new_method($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "T H I R R A - NewPage";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/emr_newpage_html";
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
    } // end of function new_method($id)


}

/* End of file emr.php */
/* Location: ./app_thirra/controllers/emr.php */
