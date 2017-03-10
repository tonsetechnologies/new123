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
 * Controller Class for EHR_PATIENT
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_patient extends MY_Controller 
{
    //protected $_patient_id      =  "";
    protected $_offline_mode    =  FALSE;
    //protected $_offline_mode    =  TRUE;
    protected $_debug_mode      =  FALSE;
    //protected $_debug_mode      =  TRUE;


    function __construct()
    {
        parent::__construct();
        
        $this->load->helper('url');
        $this->load->helper('form');
        //$this->load->scaffolding('patient_demographic_info');
	  	//$this->load->model('mpatients');
        $data['app_language']		    =	$this->config->item('app_language');
        $this->lang->load('ehr', $data['app_language']);
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->load->model('memr_rdb');
		$this->load->model('mehr_wdb');
        
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
            $new_page   =   base_url()."index.php/thirra/";
            header("Status: 200");
            header("Location: ".$new_page);
        } // redirect to login page

        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        define("PICS_URL", $data['pics_url']);
    }


    // ------------------------------------------------------------------------
    // === PATIENTS MANAGEMENT
    // ------------------------------------------------------------------------
    function patients_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_patient/patients_mgt','Patients');    
        $data['location_id']    	= 	$_SESSION['location_id'];
		$data['title'] = "T H I R R A - Patients Management";
		$data['pending_referrals'] = $this->memr_rdb->get_list_referrals('Consulted', $data['location_id']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_wap";
            $new_body   =   "ehr/ehr_patients_mgt_html";
            //$new_body   =   "ehr/ehr_patients_mgt_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_patients_html";
            $new_body   =   "ehr/ehr_patients_mgt_html";
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
    } // end of function patients_mgt($id)


    // ------------------------------------------------------------------------
    function search_patient()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_patient/patients_mgt','Patients');    
        $data['location_id']    	= 	$_SESSION['location_id'];
		$data['title'] = 'Search for Patient for New Notification';
        $data['name_filter']      	=   trim($this->input->post('patient_name'));
		$data['search_field']       = $this->input->post('search_field');
		$data['patlist'] = $this->memr_rdb->get_patients_list('all','name',$data['name_filter'],$data['search_field']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_wap";
            $new_body   =   "ehr/ehr_patients_searched_html";
            //$new_body   =   "ehr/emr_search_patient_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_patients_html";
            $new_body   =   "ehr/ehr_patients_searched_html";
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
    } //end of function search_patient()


    // ------------------------------------------------------------------------
	function patients_list() 
    {	
        $data['offline_mode']		= $this->config->item('offline_mode');
        $data['debug_mode']		    = $this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_patient/patients_mgt','Patients');    
        $data['location_id']    	= $_SESSION['location_id'];
		$data['patient_scope'] 		= $this->uri->segment(3);
		$data['list_sort'] 			= $this->uri->segment(4);
		$data['alphabet'] 			= $this->uri->segment(5);
/*		
        $data['sort_order']   	    = $this->uri->segment(3);
        //$data['page_num']   	    = $this->uri->segment(4);
        $data['per_page']           = '50';
        $data['row_first']   	    = $this->uri->segment(4);//$data['page_num'] * $data['per_page'];
        if(!is_numeric($data['row_first'])){
             $data['row_first'] =   0;
        }
		$data['complaints_list']  = $this->mutil_rdb->get_complaint_codes_list('data',$data['sort_order'],$data['per_page'],$data['row_first']);
		$data['count_fulllist']  = $this->mutil_rdb->get_complaint_codes_list('count',$data['sort_order'],'ALL',0);
        
        $this->load->library('pagination');

        $config['base_url'] = base_url()."index.php/ehr_utilities/util_list_complaint_codes/".$data['sort_order']."/";
        $config['total_rows']   = $data['count_fulllist'];
        $config['per_page']     = $data['per_page'];
        $config['num_links']    = 10;
        $config['uri_segment']  = 4;
        $this->pagination->initialize($config);

        //echo $this->pagination->create_links();
*/

        $data['title'] = "T H I R R A - Patients List";
        $data['patlist'] = $this->memr_rdb->get_patients_list($data['patient_scope'],$data['list_sort'],$data['alphabet']);
        //$data['patlist'] = $this->mpatients->get_all_patients();
		$data['main'] = 'home';
		//$data['query'] = $this->db->get('patient_demographic_info'); 
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_wap";
            //$new_body   =   "ehr/ehr_patients_list_html";
            $new_body   =   "ehr/ehr_patients_list_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_patients_html";
            $new_body   =   "ehr/ehr_patients_list_html";
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
	} // end of function patients_list()


    // ------------------------------------------------------------------------
    function edit_postconsult_queue($summary_id)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
	  	//$this->load->model('mthirra');
	  	
		$data['title'] = 'Past Consultation Details';
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        if(isset($_POST['form_purpose'])) { 
            $data['form_purpose']   =   $_POST['form_purpose'];
        }
        if(count($_POST) && $data['form_purpose']=="new_episode"){ // User pressed Button to Create New Episode 
            $data['patient_id'] = $this->input->post('patient_id');
            //$data['summary_id'] = $this->input->post('summary_id');
            $data['init_location_id']   =   $_SESSION['location_id'];
            $ins_episode_array   =   array();
            $ins_episode_array['staff_id']              =   $_SESSION['staff_id'];
            $ins_episode_array['adt_id']                =   $data['now_id'];
            $ins_episode_array['summary_id']            =   $data['now_id'];
            $ins_episode_array['session_type']          =   "0";
            $ins_episode_array['patient_id']            =   $data['patient_id'];
            $ins_episode_array['date_started']          =   $data['now_date']; // session start date
            $ins_episode_array['time_started']          =   $data['now_time'];
            $ins_episode_array['check_in_date']         =   $data['now_date'];
            $ins_episode_array['check_in_time']         =   $data['now_time'];
            $ins_episode_array['location_id']           =   $data['init_location_id'];
            $ins_episode_array['location_start']        =   $data['init_location_id'];
            $ins_episode_array['location_end']          =   $data['init_location_id'];
            $ins_episode_array['start_date']            =   $data['now_date']; // ambiguous
            $ins_episode_array['session_id']            =   $data['now_id'];
            $ins_episode_array['remarks']               =   "THIRRA";
            $ins_episode_array['now_id']                =   $data['now_id'];
            $ins_episode_data       =   $this->memr_wdb->insert_new_episode($ins_episode_array);
            $data['save_attempt'] = 'NEW EPISODE ADDED SUCCESSFULLY';
            $data['summary_id'] = $data['now_id'];
        } elseif(count($_POST) && $data['form_purpose']=="edit_episode"){
            $data['patient_id']     =   $_POST['patient_id'];
            $data['summary_id']     =   $_POST['summary_id'];            
            $data['date_started']   =   $_POST['date_started'];
            $data['time_started']   =   $_POST['time_started'];
            $data['date_ended']     =   $_POST['date_ended'];
            $data['time_ended']     =   $_POST['time_ended'];
            $data['consult_notes']  =   $_POST['consult_notes'];
            $data['external_ref']   =   $_POST['external_ref'];
        } else { // User arrived from a link
            $data['patient_id'] = $this->uri->segment(3);
            $data['summary_id'] = $this->uri->segment(4);
            $data['date_ended']     =   $data['now_date'];
            $data['time_ended']     =   $data['now_time'];
       }
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
		$data['clinic_info']    = $this->mthirra->get_clinic_info($data['patcon_info']['location_end']);
        $data['complaints_list']= $this->memr_rdb->get_patcon_complaints($data['summary_id']);
        $data['vitals_info']    = $this->memr_rdb->get_patcon_vitals($data['summary_id']);
        $data['lab_list']       = $this->memr_rdb->get_patcon_lab($data['summary_id']);
        $data['imaging_list']   = $this->memr_rdb->get_patcon_imaging($data['summary_id']);
        $data['diagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id']);
        $data['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id']);
        $data['referrals_list'] = $this->memr_rdb->get_patcon_referrals($data['summary_id']);
        $data['last_episode']   = $this->memr_rdb->get_last_session_reference();
        /*
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        */
		$this->load->vars($data);

        // Run validation and close episode if successfully validated.
		if ($this->form_validation->run('edit_episode') == FALSE){
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_emr_patients_conslt_wap";
                $new_body   =   "ehr/emr_edit_postconsult_queue_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_emr_print_html";
                $new_sidebar=   "ehr/sidebar_emr_patients_ovrvw_html";
                $new_body   =   "ehr/emr_edit_postconsult_queue_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_patients'] < 100){
                $new_body   =   "ehr/ehr_access_denied_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            //$this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
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
    } // end of function edit_postconsult_queue($summary_id)


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
        if($data['user_rights']['section_patients'] < 100){
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
