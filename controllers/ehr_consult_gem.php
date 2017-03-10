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
 * Controller Class for EHR_CONSULT_GEM
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_consult_gem extends MY_Controller 
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
		$this->load->model('mgem_rdb');
		$this->load->model('mgem_wdb');
        
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
    function list_gem_submodules($id=NULL)  // List drug packages
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['gem_module_id']      = $this->uri->segment(3);
		$data['patient_id']         = $this->uri->segment(4);
		$data['summary_id']         = $this->uri->segment(5);
		$data['title'] = "T H I R R A - List of Submodules";
		$data['patient_info']       = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']        = $this->memr_rdb->get_patcon_details($data['patient_id']);
		$data['module_info']        = $this->memr_rdb->get_externalmod_list('episode',$data['gem_module_id']);
		$data['submodules_list']    = $this->mgem_rdb->get_submodules_list($data['gem_module_id'], $data['summary_id']);
        
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_conslt_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
            //$new_body   =   "ehr/ehr_edit_immune_select_wap";
            $new_body   =   "ehr/ehr_gem_list_submodules_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_conslt_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
            $new_body   =   "ehr/ehr_gem_list_submodules_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_orders'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function list_gem_submodules($id)


    // ------------------------------------------------------------------------
    function gem_select_agegroup($id=NULL)  // List drug packages
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['gem_module_id']      = $this->uri->segment(3);
		$data['gem_submod_id']      = $this->uri->segment(4);
		$data['patient_id']         = $this->uri->segment(5);
		$data['summary_id']         = $this->uri->segment(6);
		$data['title'] = "T H I R R A - Select Age Group";
		$data['patient_info']       = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']        = $this->memr_rdb->get_patcon_details($data['patient_id']);
		$data['module_info']        = $this->memr_rdb->get_externalmod_list('episode',$data['gem_module_id']);
		$data['submodule_info']     = $this->mgem_rdb->get_submodules_list($data['gem_module_id'], $data['summary_id'], $data['gem_submod_id']);
		$data['submodules_list']    = $this->mgem_rdb->get_submodules_list($data['gem_module_id'], $data['summary_id']);
		$data['agebands_list']      = $this->mgem_rdb->get_agebands_list($data['submodule_info'][0]['gem_ageset_id']);
        
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_conslt_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
            //$new_body   =   "ehr/ehr_edit_immune_select_wap";
            $new_body   =   "ehr/ehr_gem_select_age_group_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_conslt_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
            $new_body   =   "ehr/ehr_gem_select_age_group_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_orders'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function gem_select_agegroup($id)


    // ------------------------------------------------------------------------
    // GEM submodule form
    function gem_edit_consult()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['form_purpose']       = $this->uri->segment(3);
		$data['gem_submod_id']      = $this->uri->segment(4);
		$data['gem_agegroup_id']    = $this->uri->segment(5);
		$data['patient_id']         = $this->uri->segment(6);
		$data['summary_id']         = $this->uri->segment(7);
		$data['title'] = "GEM";
		$data['patient_info']       = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']        = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
		//$data['module_info']        = $this->memr_rdb->get_externalmod_list('episode',$data['gem_module_id']);
		$data['submodule_info']     = $this->mgem_rdb->get_submodule_info($data['gem_submod_id'], $data['summary_id']);
        $data['agegroup_info']      = $this->mgem_rdb->get_agebands_list($data['submodule_info'][0]['gem_ageset_id'],$data['gem_agegroup_id']);
		$data['form_content']       = $this->mgem_rdb->get_form_content($data['gem_submod_id'],$data['agegroup_info'][0]['gem_age_min'],$data['agegroup_info'][0]['gem_age_max'],$data['patient_info']['age_days'],$data['submodule_info'][0]['gem_session_id']);
        $num_of_questions   =   count($data['form_content']);
        //echo "num_of_questions   =".$num_of_questions;
        // Process cast types further if required
        // $i starts from 0 as array from model starts from 0.
        for($i=0; $i < $num_of_questions; $i++){
            switch($data['form_content'][$i]['gem_quest_cast']){
                case "D":
                    //echo "EHR";
                    break;
                case "E":
                    //echo "EHR";
                    switch($data['form_content'][$i]['gem_quest_gemkey']){
                        case "patient_id":
                            $data['form_content'][$i]['gem_key_value']    =   $data['patient_id'];
                            break;
                        case "summary_id":
                            $data['form_content'][$i]['gem_key_value']    =   $data['summary_id'];
                            break;
                        case "location_id":
                            $data['form_content'][$i]['gem_key_value']    =   "";
                            break;
                        case "staff_id":
                            $data['form_content'][$i]['gem_key_value']    =   "";
                            break;
                    } //endswitch($data['form_content'][$i]['gem_quest_gemkey'])
                    $data['form_content'][$i]['multiples'] = $this->mgem_rdb->get_ehr_single($data['form_content'][$i]['gem_quest_looktable'],$data['form_content'][$i]['gem_quest_lookfield'],$data['form_content'][$i]['gem_quest_lookkey'],$data['form_content'][$i]['gem_key_value']);
                    break;
                case "L":
                    //echo "Lookup";
                    $data['form_content'][$i]['multiples'] = $this->mgem_rdb->get_lookup_choices($data['form_content'][$i]['gem_quest_looktable'],$data['form_content'][$i]['gem_quest_lookfield']);
                    //echo "<pre>";
                    //print_r($data['form_content'][$i]['multiples']);
                    //echo "</pre>";
                    break;
                case "M":
                    //echo "Multiple choice";
                    $data['form_content'][$i]['multiples'] = $this->mgem_rdb->get_multiple_choices($data['form_content'][$i]['gem_question_id']);
                    //echo "<pre>";
                    //print_r($data['form_content'][$i]['multiples']);
                    //echo "</pre>";
                    break;
            }
        }
        if(count($_POST)) {
            // User has posted the form -> Move data into array to be passed to model
            $data['total_questions']      		    =   $num_of_questions; // Rely on SQL, not form $this->input->post('total_questions');
            // $j starts from 1 as html form starts from 1
            for($j=1; $j <= $data['total_questions']; $j++) {
                //echo $i;
                $data['a'][$j]['modqid']      =   $this->input->post('a'.$j.'modqid');
                $data['a'][$j]['cast']        =   $this->input->post('a'.$j.'cast');
                $data['a'][$j]['answer']      =   $this->input->post('a'.$j.'answer');
                // Additional processing for certain cast types
                switch($data['a'][$j]['cast']){
                    case "E":
                        //echo $j.". EHR ";
                        $data['a'][$j]['gem_quest_looktable']      =   $data['form_content'][$j-1]['gem_quest_looktable'];
                        $data['a'][$j]['gem_quest_lookfield']      =   $data['form_content'][$j-1]['gem_quest_lookfield'];
                        $data['a'][$j]['gem_quest_lookkey']        =   $data['form_content'][$j-1]['gem_quest_lookkey'];
                        $data['a'][$j]['gem_key_value']            =   $data['form_content'][$j-1]['gem_key_value'];
                        //echo "<pre>";
                        //print_r($data['a'][$j]);
                        //echo "</pre>";
                        break;
                } //endswitch($data['a'][$i]['cast'])
            } // endfor($i=1; $i <= $data['total_questions']; $i++)
            //echo "data['a']";
            //echo "<pre>";
            //print_r($data['a']);
            //echo "</pre>";
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_consult") {
                //echo "New form";
                $data['duration']     =   "";
            } elseif ($data['form_purpose'] == "edit_consult") {
                //echo "Edit form";
            } //endif ($data['form_purpose'] == "new_consult")
        } //endif(count($_POST))
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_consult_gem') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                $new_body   =   "ehr/ehr_gem_consult_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_gem_consult_html";
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
            if($data['form_purpose'] == "new_consult") {
                // New submodule record
                $ins_gem_array   =   array();
                $ins_gem_array['staff_id']           = $_SESSION['staff_id'];
                $ins_gem_array['now_id']             = $data['now_id'];
                $ins_gem_array['gem_session_id']     = $data['now_id'];
                $ins_gem_array['patient_id']         = $data['patient_id'];
                $ins_gem_array['summary_id']         = $data['summary_id'];
                $ins_gem_array['gem_module_id']      = $data['submodule_info'][0]['gem_module_id'];
                $ins_gem_array['gem_submod_id']      = $data['gem_submod_id'];
                $ins_gem_array['gem_agegroup_id']    = $data['gem_agegroup_id'];
                $ins_gem_array['answers']            = $data['a'];
                if($data['offline_mode']){
                    $ins_gem_array['synch_out']       = $data['now_id'];
                }
	            $ins_gem_data       =   $this->mgem_wdb->insert_new_gem_consult($ins_gem_array,$data['offline_mode']);
                $this->session->set_flashdata("data_activity", "Submodule ".$data['submodule_info'][0]['gem_submod_name']." completed.");
            } else {
            //} elseif($data['form_purpose'] == "edit_consult") {
                // Edit submodule record
                $upd_gem_array   =   array();
                $upd_gem_array['staff_id']           = $_SESSION['staff_id'];
                $upd_gem_array['now_id']             = $data['now_id'];
                $upd_gem_array['gem_session_id']     = $data['submodule_info'][0]['gem_session_id'];
                $upd_gem_array['gem_module_id']      = $data['submodule_info'][0]['gem_module_id'];
                $upd_gem_array['gem_submod_id']      = $data['gem_submod_id'];
                $upd_gem_array['gem_agegroup_id']    = $data['gem_agegroup_id'];
                $upd_gem_array['answers']            = $data['a'];
                if($data['offline_mode']){
                    $upd_gem_array['synch_out']       = $data['now_id'];
                }
	            $upd_gem_data       =   $this->mgem_wdb->update_gem_consult($upd_gem_array,$data['offline_mode']);
                $this->session->set_flashdata("data_activity", "Submodule ".$data['submodule_info'][0]['gem_submod_name']." edited.");
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_consult_gem/list_gem_submodules/".$data['submodule_info'][0]['gem_module_id']."/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_diagnosis') == FALSE)


    } // end of function gem_edit_consult()


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

/* End of file Ehr_consult_gem.php */
/* Location: ./app_thirra/controllers/Ehr_consult_gem.php */
