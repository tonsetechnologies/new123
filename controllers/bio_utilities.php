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
 * Controller Class for EHR_ADMIN
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.8
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Bio_utilities extends MY_Controller 
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
		$this->load->model('mutil_rdb');
		$this->load->model('mutil_wdb');
        
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
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        define("PICS_URL", $data['pics_url']);
    }


    // ------------------------------------------------------------------------
    // === UTILITIES MANAGEMENT
    // ------------------------------------------------------------------------
    function utilities_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['title'] = "T H I R R A - Utilities Management";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
            $new_body   =   "ehr/ehr_utilities_mgt_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "bio/header_xhtml1-transitional";
            $new_banner =   "bio/banner_bio_hosp";
            $new_sidebar=   "ehr/sidebar_emr_utilities_html";
            $new_body   =   "bio/bio_utilities_mgt_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function utilities_mgt($id)


    // ------------------------------------------------------------------------
    function util_list_addrvillages($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Villages";
		$data['village_list']  = $this->mutil_rdb->get_addr_village_list($data['app_country'],$data['sort_order']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
            $new_body   =   "ehr/ehr_util_list_addrvillages_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "bio/header_xhtml1-transitional";
            $new_banner =   "bio/banner_bio_hosp";
            $new_sidebar=   "ehr/sidebar_emr_utilities_html";
            $new_body   =   "bio/bio_util_list_addrvillages_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function util_list_addrvillages($id)


    // ------------------------------------------------------------------------
    function util_edit_village_info()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
        $data['form_purpose']   	= $this->uri->segment(3);
        $data['addr_village_id']	= $this->uri->segment(4);
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_addr_village_id']      = $_POST['addr_village_id'];
            $data['init_addr_town_id']         = $_POST['addr_town_id'];
            $data['init_addr_area_id']         = $_POST['addr_area_id'];
            $data['init_addr_village_name']    = $_POST['addr_village_name'];
            $data['init_addr_village_code']    = $_POST['addr_village_code'];
            $data['init_addr_village_subcode'] = $_POST['addr_village_subcode'];
            $data['init_addr_village_sort']    = $_POST['addr_village_sort'];
            $data['init_addr_village_descr']   = $_POST['addr_village_descr'];
            $data['init_addr_village_section'] = $_POST['addr_village_section'];
            $data['init_addr_village_address1']= $_POST['addr_village_address1'];
            $data['init_addr_village_address2']= $_POST['addr_village_address2'];
            $data['init_addr_village_address3']= $_POST['addr_village_address3'];
            $data['init_addr_village_postcode']= $_POST['addr_village_postcode'];
            $data['init_addr_village_town']    = $_POST['addr_village_town'];
            $data['init_addr_village_state']   = $_POST['addr_village_state'];
            $data['init_addr_village_country'] = $_POST['addr_village_country'];
            $data['init_addr_village_tel']     = $_POST['addr_village_tel'];
            $data['init_addr_village_fax']     = $_POST['addr_village_fax'];
            $data['init_addr_village_email']   = $_POST['addr_village_email'];
            $data['init_addr_village_mgr1_position']= $_POST['addr_village_mgr1_position'];
            $data['init_addr_village_mgr1_name']= $_POST['addr_village_mgr1_name'];
            $data['init_addr_village_mgr2_position']= $_POST['addr_village_mgr2_position'];
            $data['init_addr_village_mgr2_name']= $_POST['addr_village_mgr2_name'];
            $data['init_addr_village_gps_lat'] = $_POST['addr_village_gps_lat'];
            $data['init_addr_village_gps_long']= $_POST['addr_village_gps_long'];
            $data['init_addr_village_population']= $_POST['addr_village_population'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_village") {
                $data['init_addr_town_id']            =   "";
                $data['init_addr_area_id']            =   "";
                $data['init_addr_village_name']       =   "";
                $data['init_addr_village_code']       =   "";
                $data['init_addr_village_subcode']    =   "";
                $data['init_addr_village_sort']       =   "";
                $data['init_addr_village_descr']      =   "";
                $data['init_addr_village_section']    =   "";
                $data['init_addr_village_address1']   =   "";
                $data['init_addr_village_address2']   =   "";
                $data['init_addr_village_address3']   =   "";
                $data['init_addr_village_postcode']   =   "";
                $data['init_addr_village_town']       =   "";
                $data['init_addr_village_state']      =   "";
                $data['init_addr_village_country']    =   "";
                $data['init_addr_village_tel']        =   "";
                $data['init_addr_village_fax']        =   "";
                $data['init_addr_village_email']      =   "";
                $data['init_addr_village_mgr1_position']=   "";
                $data['init_addr_village_mgr1_name']  =   "";
                $data['init_addr_village_mgr2_position']=   "";
                $data['init_addr_village_mgr2_name']  =   "";
                $data['init_addr_village_gps_lat']    =   "";
                $data['init_addr_village_gps_long']   =   "";
                $data['init_addr_village_population'] =   "";
            } elseif ($data['form_purpose'] == "edit_village") {
                //echo "Edit supplier";
                $data['village_info'] = $this->mutil_rdb->get_addr_village_list($data['app_country'],'addr_village_sort',$data['addr_village_id']);
                $data['init_addr_village_id']      = $data['addr_village_id'];
                $data['init_addr_town_id']         = $data['village_info'][0]['addr_town_id'];
                $data['init_addr_area_id']         = $data['village_info'][0]['addr_area_id'];
                $data['init_addr_village_name']    = $data['village_info'][0]['addr_village_name'];
                $data['init_addr_village_code']    = $data['village_info'][0]['addr_village_code'];
                $data['init_addr_village_subcode'] = $data['village_info'][0]['addr_village_subcode'];
                $data['init_addr_village_sort']    = $data['village_info'][0]['addr_village_sort'];
                $data['init_addr_village_descr']   = $data['village_info'][0]['addr_village_descr'];
                $data['init_addr_village_section'] = $data['village_info'][0]['addr_village_section'];
                $data['init_addr_village_address1']= $data['village_info'][0]['addr_village_address1'];
                $data['init_addr_village_address2']= $data['village_info'][0]['addr_village_address2'];
                $data['init_addr_village_address3']= $data['village_info'][0]['addr_village_address3'];
                $data['init_addr_village_postcode']= $data['village_info'][0]['addr_village_postcode'];
                $data['init_addr_village_town']    = $data['village_info'][0]['addr_village_town'];
                $data['init_addr_village_state']   = $data['village_info'][0]['addr_village_state'];
                $data['init_addr_village_country'] = $data['village_info'][0]['addr_village_country'];
                $data['init_addr_village_tel']     = $data['village_info'][0]['addr_village_tel'];
                $data['init_addr_village_fax']     = $data['village_info'][0]['addr_village_fax'];
                $data['init_addr_village_email']   = $data['village_info'][0]['addr_village_email'];
                $data['init_addr_village_mgr1_position']= $data['village_info'][0]['addr_village_mgr1_position'];
                $data['init_addr_village_mgr1_name']= $data['village_info'][0]['addr_village_mgr1_name'];
                $data['init_addr_village_mgr2_position']= $data['village_info'][0]['addr_village_mgr2_position'];
                $data['init_addr_village_mgr2_name']= $data['village_info'][0]['addr_village_mgr2_name'];
                $data['init_addr_village_gps_lat'] = $data['village_info'][0]['addr_village_gps_lat'];
                $data['init_addr_village_gps_long']= $data['village_info'][0]['addr_village_gps_long'];
                $data['init_addr_village_population']= $data['village_info'][0]['addr_village_population'];
            } //endif ($data['form_purpose'] == "new_supplier")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Village";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
		$data['addr_area_id']		=	$data['init_addr_area_id'];
		$data['addr_town_list']  	= 	$this->mutil_rdb->get_addr_town_list($data['app_country'],'addr_town_sort');
		// If user chose a town
		if(!empty($data['init_addr_town_id']) && ($data['init_addr_town_id']<>"")){
			//echo "<hr />init_addr_town_id = ".$data['init_addr_town_id'];
			$data['addr_town_info']  = $this->mutil_rdb->get_addr_town_list("All",'addr_town_sort',$data['init_addr_town_id']);
			// Replace form selection of addr_area_id to addr_town_id's values.
			$data['addr_area_id']		=	$data['addr_town_info'][0]['addr_area_id'];
			$data['init_addr_area_id']		=	$data['addr_area_id'];
			$data['addr_district_id']	=	$data['addr_town_info'][0]['addr_district_id'];
		}
		$data['addr_area_list']  = $this->mutil_rdb->get_addr_area_list($data['app_country'],'addr_area_sort');
		if(isset($data['init_addr_town_id']) && empty($data['init_addr_town_id'])){
			$data['addr_area_info']  = $this->mutil_rdb->get_addr_area_list("All",'addr_area_sort',$data['addr_area_id']);
			if(count($data['addr_area_info']) > 0){
				$data['addr_district_id']	=	$data['addr_area_info'][0]['addr_district_id'];
                $data['init_addr_village_state']=$data['addr_area_info'][0]['addr_state_name'];
                $data['init_addr_village_country']=$data['addr_area_info'][0]['addr_state_country'];
			}
		}
		if(!empty($data['init_addr_town_id'])){
			$data['init_addr_village_town']=$data['addr_town_info'][0]['addr_town_name'];
			$data['init_addr_village_state']=$data['addr_town_info'][0]['addr_district_state'];
			$data['init_addr_village_country']=$data['addr_town_info'][0]['addr_district_country'];
		}

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_addr_village') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_emr_wap";
                $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
                $new_body   =   "ehr/ehr_util_edit_village_info_wap";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "ehr/sidebar_emr_utilities_html";
                $new_body   =   "bio/bio_util_edit_village_info_html";
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
            if($data['form_purpose'] == "new_village") {
                // New village record
                $ins_village_array   =   array();
                $ins_village_array['staff_id']             = $_SESSION['staff_id'];
                $ins_village_array['now_id']               = $data['now_id'];
                $ins_village_array['addr_village_id']      = $data['now_id'];
                $ins_village_array['addr_town_id']         = $data['init_addr_town_id'];
                $ins_village_array['addr_area_id']         = $data['addr_area_id'];
                $ins_village_array['addr_village_name']    = $data['init_addr_village_name'];
                $ins_village_array['addr_village_code']    = $data['init_addr_village_code'];
                $ins_village_array['addr_village_subcode'] = $data['init_addr_village_subcode'];
                if(is_numeric($data['init_addr_village_sort'])){
                    $ins_village_array['addr_village_sort']= $data['init_addr_village_sort'];
                }
                //$ins_village_array['addr_village_sort']    = $data['init_addr_village_sort'];
                $ins_village_array['addr_village_descr']   = $data['init_addr_village_descr'];
                $ins_village_array['addr_village_section'] = $data['init_addr_village_section'];
                $ins_village_array['addr_village_address1']= $data['init_addr_village_address1'];
                $ins_village_array['addr_village_address2']= $data['init_addr_village_address2'];
                $ins_village_array['addr_village_address3']= $data['init_addr_village_address3'];
                $ins_village_array['addr_village_postcode']= $data['init_addr_village_postcode'];
                $ins_village_array['addr_village_town']    = $data['init_addr_village_town'];
                $ins_village_array['addr_village_state']   = $data['init_addr_village_state'];
                $ins_village_array['addr_village_country'] = $data['init_addr_village_country'];
                $ins_village_array['addr_village_tel']     = $data['init_addr_village_tel'];
                $ins_village_array['addr_village_fax']     = $data['init_addr_village_fax'];
                $ins_village_array['addr_village_email']   = $data['init_addr_village_email'];
                $ins_village_array['addr_village_mgr1_position']= $data['init_addr_village_mgr1_position'];
                $ins_village_array['addr_village_mgr1_name']= $data['init_addr_village_mgr1_name'];
                $ins_village_array['addr_village_mgr2_position']= $data['init_addr_village_mgr2_position'];
                $ins_village_array['addr_village_mgr2_name']= $data['init_addr_village_mgr2_name'];
                $ins_village_array['addr_village_gps_lat'] = $data['init_addr_village_gps_lat'];
                $ins_village_array['addr_village_gps_long']= $data['init_addr_village_gps_long'];
                if(is_numeric($data['init_addr_village_population'])){
                    $ins_village_array['addr_village_population']= $data['init_addr_village_population'];
                }
                //$ins_village_array['addr_village_population']= $data['init_addr_village_population'];
                if($data['offline_mode']){
                    $ins_village_array['synch_out']        = $data['now_id'];
                }
	            $ins_village_data       =   $this->mutil_wdb->insert_new_village($ins_village_array);
            } elseif($data['form_purpose'] == "edit_village") {
                // Existing supplier record
                $upd_village_array   =   array();
                $upd_village_array['staff_id']       	   = $_SESSION['staff_id'];
                $upd_village_array['addr_village_id']      = $data['addr_village_id'];
                $upd_village_array['addr_town_id']         = $data['init_addr_town_id'];
                $upd_village_array['addr_area_id']         = $data['init_addr_area_id'];
                $upd_village_array['addr_village_name']    = $data['init_addr_village_name'];
                $upd_village_array['addr_village_code']    = $data['init_addr_village_code'];
                $upd_village_array['addr_village_subcode'] = $data['init_addr_village_subcode'];
                if(is_numeric($data['init_addr_village_sort'])){
                    $upd_village_array['addr_village_sort']= $data['init_addr_village_sort'];
                }
                //$upd_village_array['addr_village_sort']    = $data['init_addr_village_sort'];
                $upd_village_array['addr_village_descr']   = $data['init_addr_village_descr'];
                $upd_village_array['addr_village_section'] = $data['init_addr_village_section'];
                $upd_village_array['addr_village_address1']= $data['init_addr_village_address1'];
                $upd_village_array['addr_village_address2']= $data['init_addr_village_address2'];
                $upd_village_array['addr_village_address3']= $data['init_addr_village_address3'];
                $upd_village_array['addr_village_postcode']= $data['init_addr_village_postcode'];
                $upd_village_array['addr_village_town']    = $data['init_addr_village_town'];
                $upd_village_array['addr_village_state']   = $data['init_addr_village_state'];
                $upd_village_array['addr_village_country'] = $data['init_addr_village_country'];
                $upd_village_array['addr_village_tel']     = $data['init_addr_village_tel'];
                $upd_village_array['addr_village_fax']     = $data['init_addr_village_fax'];
                $upd_village_array['addr_village_email']   = $data['init_addr_village_email'];
                $upd_village_array['addr_village_mgr1_position']= $data['init_addr_village_mgr1_position'];
                $upd_village_array['addr_village_mgr1_name']= $data['init_addr_village_mgr1_name'];
                $upd_village_array['addr_village_mgr2_position']= $data['init_addr_village_mgr2_position'];
                $upd_village_array['addr_village_mgr2_name']= $data['init_addr_village_mgr2_name'];
                $upd_village_array['addr_village_gps_lat'] = $data['init_addr_village_gps_lat'];
                $upd_village_array['addr_village_gps_long']= $data['init_addr_village_gps_long'];
                if(is_numeric($data['init_addr_village_population'])){
                    $upd_village_array['addr_village_population']= $data['init_addr_village_population'];
                }
                //$upd_village_array['addr_village_population']= $data['init_addr_village_population'];
	            $upd_village_data       =   $this->mutil_wdb->update_village_info($upd_village_array);
            } //endif($data['diagnosis_id'] == "new_village")
            $new_page = base_url()."index.php/bio_utilities/util_list_addrvillages/addr_village_sort";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_addr_village') == FALSE)


    } // end of function util_edit_village_info()


    // ------------------------------------------------------------------------
    function util_list_addrtowns($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Towns";
		$data['town_list']  = $this->mutil_rdb->get_addr_town_list($data['app_country'],$data['sort_order']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
            $new_body   =   "ehr/ehr_util_list_addrtowns_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "bio/header_xhtml1-transitional";
            $new_banner =   "bio/banner_bio_hosp";
            $new_sidebar=   "ehr/sidebar_emr_utilities_html";
            $new_body   =   "bio/bio_util_list_addrtowns_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function util_list_addrtowns($id)


    // ------------------------------------------------------------------------
    function util_edit_town_info()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
        $data['form_purpose']   	= 	$this->uri->segment(3);
        $data['addr_town_id']		= 	$this->uri->segment(4);
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_addr_town_id']      = $_POST['addr_town_id'];
            $data['init_addr_area_id']      = $_POST['addr_area_id'];
            //$data['init_addr_district_id']  = $_POST['addr_district_id'];
            $data['init_addr_town_name']    = $_POST['addr_town_name'];
            $data['init_addr_town_code']    = $_POST['addr_town_code'];
            $data['init_addr_town_subcode'] = $_POST['addr_town_subcode'];
            $data['init_addr_town_sort']    = $_POST['addr_town_sort'];
            $data['init_addr_town_descr']   = $_POST['addr_town_descr'];
            $data['init_addr_town_section'] = $_POST['addr_town_section'];
            $data['init_addr_town_address1']= $_POST['addr_town_address1'];
            $data['init_addr_town_address2']= $_POST['addr_town_address2'];
            $data['init_addr_town_address3']= $_POST['addr_town_address3'];
            $data['init_addr_town_postcode']= $_POST['addr_town_postcode'];
            $data['init_addr_town_town']    = $_POST['addr_town_town'];
            $data['init_addr_town_state']   = $_POST['addr_town_state'];
            $data['init_addr_town_country'] = $_POST['addr_town_country'];
            $data['init_addr_town_tel']     = $_POST['addr_town_tel'];
            $data['init_addr_town_fax']     = $_POST['addr_town_fax'];
            $data['init_addr_town_email']   = $_POST['addr_town_email'];
            $data['init_addr_town_mgr1_position']= $_POST['addr_town_mgr1_position'];
            $data['init_addr_town_mgr1_name']= $_POST['addr_town_mgr1_name'];
            $data['init_addr_town_mgr2_position']= $_POST['addr_town_mgr2_position'];
            $data['init_addr_town_mgr2_name']= $_POST['addr_town_mgr2_name'];
            $data['init_addr_town_population']= $_POST['addr_town_population'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_town") {
                $data['init_addr_town_id']      = 	$data['addr_town_id'];
                $data['init_addr_area_id']      =   "";
                $data['init_addr_district_id']  =   "";
                $data['init_addr_town_name']    =   "";
                $data['init_addr_town_code']    =   "";
                $data['init_addr_town_subcode'] =   "";
                $data['init_addr_town_sort']    =   "";
                $data['init_addr_town_descr']   =   "";
                $data['init_addr_town_section'] =   "";
                $data['init_addr_town_address1']=   "";
                $data['init_addr_town_address2']=   "";
                $data['init_addr_town_address3']=   "";
                $data['init_addr_town_postcode']=   "";
                $data['init_addr_town_town']    =   "";
                $data['init_addr_town_state']   =   "";
                $data['init_addr_town_country'] =   "";
                $data['init_addr_town_tel']     =   "";
                $data['init_addr_town_fax']     =   "";
                $data['init_addr_town_email']   =   "";
                $data['init_addr_town_mgr1_position']=   "";
                $data['init_addr_town_mgr1_name']=   "";
                $data['init_addr_town_mgr2_position']=   "";
                $data['init_addr_town_mgr2_name']=   "";
                $data['init_addr_town_population']=   "";
            } elseif ($data['form_purpose'] == "edit_town") {
                //echo "Edit town";
                $data['town_info'] = $this->mutil_rdb->get_addr_town_list($data['app_country'],'addr_town_sort',$data['addr_town_id']);
                $data['init_addr_town_id']      = $data['addr_town_id'];
                $data['init_addr_area_id']      = $data['town_info'][0]['addr_area_id'];
                $data['init_addr_district_id']  = $data['town_info'][0]['addr_district_id'];
                $data['init_addr_town_name']    = $data['town_info'][0]['addr_town_name'];
                $data['init_addr_town_code']    = $data['town_info'][0]['addr_town_code'];
                $data['init_addr_town_subcode'] = $data['town_info'][0]['addr_town_subcode'];
                $data['init_addr_town_sort']    = $data['town_info'][0]['addr_town_sort'];
                $data['init_addr_town_descr']   = $data['town_info'][0]['addr_town_descr'];
                $data['init_addr_town_section'] = $data['town_info'][0]['addr_town_section'];
                $data['init_addr_town_address1']= $data['town_info'][0]['addr_town_address1'];
                $data['init_addr_town_address2']= $data['town_info'][0]['addr_town_address2'];
                $data['init_addr_town_address3']= $data['town_info'][0]['addr_town_address3'];
                $data['init_addr_town_postcode']= $data['town_info'][0]['addr_town_postcode'];
                $data['init_addr_town_town']    = $data['town_info'][0]['addr_town_town'];
                $data['init_addr_town_state']   = $data['town_info'][0]['addr_town_state'];
                $data['init_addr_town_country'] = $data['town_info'][0]['addr_town_country'];
                $data['init_addr_town_tel']     = $data['town_info'][0]['addr_town_tel'];
                $data['init_addr_town_fax']     = $data['town_info'][0]['addr_town_fax'];
                $data['init_addr_town_email']   = $data['town_info'][0]['addr_town_email'];
                $data['init_addr_town_mgr1_position']= $data['town_info'][0]['addr_town_mgr1_position'];
                $data['init_addr_town_mgr1_name']= $data['town_info'][0]['addr_town_mgr1_name'];
                $data['init_addr_town_mgr2_position']= $data['town_info'][0]['addr_town_mgr2_position'];
                $data['init_addr_town_mgr2_name']= $data['town_info'][0]['addr_town_mgr2_name'];
                $data['init_addr_town_population']= $data['town_info'][0]['addr_town_population'];
            } //endif ($data['form_purpose'] == "new_town")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Town";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
		$data['addr_town_id']		=	$data['init_addr_town_id'];
		$data['addr_area_list']  	= 	$this->mutil_rdb->get_addr_area_list($data['app_country'],'addr_area_sort');
		$data['area_info']  	= 	$this->mutil_rdb->get_addr_area_list($data['app_country'],'addr_area_sort',$data['init_addr_area_id']);
		if(count($data['area_info']) > 0){
			$data['init_addr_district_id']    = $data['area_info'][0]['addr_district_id'];
			$data['init_addr_town_state']     = $data['area_info'][0]['addr_state_name'];
			$data['init_addr_town_country']   = $data['area_info'][0]['addr_state_country'];
		}

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_addr_town') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_emr_wap";
                $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
                $new_body   =   "ehr/ehr_util_edit_town_info_wap";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "ehr/sidebar_emr_utilities_html";
                $new_body   =   "bio/bio_util_edit_town_info_html";
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
            if($data['form_purpose'] == "new_town") {
                // New town record
                $ins_town_array   =   array();
                $ins_town_array['staff_id']          = $_SESSION['staff_id'];
                $ins_town_array['now_id']            = $data['now_id'];
                $ins_town_array['addr_town_id']      = $data['now_id'];
                $ins_town_array['addr_area_id']      = $data['init_addr_area_id'];
                $ins_town_array['addr_district_id']  = $data['init_addr_district_id'];
                $ins_town_array['addr_town_name']    = $data['init_addr_town_name'];
                $ins_town_array['addr_town_code']    = $data['init_addr_town_code'];
                $ins_town_array['addr_town_subcode'] = $data['init_addr_town_subcode'];
                if(is_numeric($data['init_addr_town_sort'])){
                    $ins_town_array['addr_town_sort']= $data['init_addr_town_sort'];
                }
                //$ins_town_array['addr_town_sort']  = $data['init_addr_town_sort'];
                $ins_town_array['addr_town_descr']   = $data['init_addr_town_descr'];
                $ins_town_array['addr_town_section'] = $data['init_addr_town_section'];
                $ins_town_array['addr_town_address1']= $data['init_addr_town_address1'];
                $ins_town_array['addr_town_address2']= $data['init_addr_town_address2'];
                $ins_town_array['addr_town_address3']= $data['init_addr_town_address3'];
                $ins_town_array['addr_town_postcode']= $data['init_addr_town_postcode'];
                $ins_town_array['addr_town_town']    = $data['init_addr_town_name'];
                $ins_town_array['addr_town_state']   = $data['init_addr_town_state'];
                $ins_town_array['addr_town_country'] = $data['init_addr_town_country'];
                $ins_town_array['addr_town_tel']     = $data['init_addr_town_tel'];
                $ins_town_array['addr_town_fax']     = $data['init_addr_town_fax'];
                $ins_town_array['addr_town_email']   = $data['init_addr_town_email'];
                $ins_town_array['addr_town_mgr1_position']= $data['init_addr_town_mgr1_position'];
                $ins_town_array['addr_town_mgr1_name']= $data['init_addr_town_mgr1_name'];
                $ins_town_array['addr_town_mgr2_position']= $data['init_addr_town_mgr2_position'];
                $ins_town_array['addr_town_mgr2_name']= $data['init_addr_town_mgr2_name'];
                if(is_numeric($data['init_addr_town_population'])){
                    $ins_town_array['addr_town_population']= $data['init_addr_town_population'];
                }
                //$ins_town_array['addr_town_population']  = $data['init_addr_town_population'];
                if($data['offline_mode']){
                    $ins_town_array['synch_out']        = $data['now_id'];
                }
	            $ins_town_data       =   $this->mutil_wdb->insert_new_town($ins_town_array);
            } elseif($data['form_purpose'] == "edit_town") {
                // Existing supplier record
                $upd_town_array   =   array();
                $upd_town_array['staff_id']       	 = $_SESSION['staff_id'];
                $upd_town_array['addr_town_id']      = $data['addr_town_id'];
                $upd_town_array['addr_area_id']      = $data['init_addr_area_id'];
                $upd_town_array['addr_district_id']  = $data['init_addr_district_id'];
                $upd_town_array['addr_town_name']    = $data['init_addr_town_name'];
                $upd_town_array['addr_town_code']    = $data['init_addr_town_code'];
                $upd_town_array['addr_town_subcode'] = $data['init_addr_town_subcode'];
                if(is_numeric($data['init_addr_town_sort'])){
                    $upd_area_array['addr_town_sort']= $data['init_addr_town_sort'];
                }
                //$upd_town_array['addr_town_sort'] = $data['init_addr_town_sort'];
                $upd_town_array['addr_town_descr']   = $data['init_addr_town_descr'];
                $upd_town_array['addr_town_section'] = $data['init_addr_town_section'];
                $upd_town_array['addr_town_address1']= $data['init_addr_town_address1'];
                $upd_town_array['addr_town_address2']= $data['init_addr_town_address2'];
                $upd_town_array['addr_town_address3']= $data['init_addr_town_address3'];
                $upd_town_array['addr_town_postcode']= $data['init_addr_town_postcode'];
                $upd_town_array['addr_town_town']    = $data['init_addr_town_name'];
                $upd_town_array['addr_town_state']   = $data['init_addr_town_state'];
                $upd_town_array['addr_town_country'] = $data['init_addr_town_country'];
                $upd_town_array['addr_town_tel']     = $data['init_addr_town_tel'];
                $upd_town_array['addr_town_fax']     = $data['init_addr_town_fax'];
                $upd_town_array['addr_town_email']   = $data['init_addr_town_email'];
                $upd_town_array['addr_town_mgr1_position']= $data['init_addr_town_mgr1_position'];
                $upd_town_array['addr_town_mgr1_name']= $data['init_addr_town_mgr1_name'];
                $upd_town_array['addr_town_mgr2_position']= $data['init_addr_town_mgr2_position'];
                $upd_town_array['addr_town_mgr2_name']= $data['init_addr_town_mgr2_name'];
                if(is_numeric($data['init_addr_town_population'])){
                    $upd_area_array['addr_town_population']= $data['init_addr_town_population'];
                }
                //$upd_town_array['addr_town_population']      = $data['init_addr_town_population'];
	            $upd_town_data       =   $this->mutil_wdb->update_town_info($upd_town_array);
            } //endif($data['diagnosis_id'] == "new_town")
            $new_page = base_url()."index.php/bio_utilities/util_list_addrtowns/addr_town_sort";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_addr_town') == FALSE)


    } // end of function util_edit_town_info()


    // ------------------------------------------------------------------------
    function util_list_addrareas($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Areas";
		$data['area_list']  = $this->mutil_rdb->get_addr_area_list($data['app_country'],$data['sort_order']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
            $new_body   =   "ehr/ehr_util_list_addrareas_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "bio/header_xhtml1-transitional";
            $new_banner =   "bio/banner_bio_hosp";
            $new_sidebar=   "ehr/sidebar_emr_utilities_html";
            $new_body   =   "bio/bio_util_list_addrareas_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function util_list_addrareas($id)


    // ------------------------------------------------------------------------
    function util_edit_area_info()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
        $data['form_purpose']   	= 	$this->uri->segment(3);
        $data['addr_area_id']		= 	$this->uri->segment(4);
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_addr_area_id']        = $_POST['addr_area_id'];
            $data['init_addr_district_id']    = $_POST['addr_district_id'];
            $data['init_addr_area_name']      = $_POST['addr_area_name'];
            $data['init_addr_area_code']      = $_POST['addr_area_code'];
            $data['init_addr_area_subcode']   = $_POST['addr_area_subcode'];
            $data['init_addr_area_sort']      = $_POST['addr_area_sort'];
            $data['init_addr_area_descr']     = $_POST['addr_area_descr'];
            $data['init_addr_area_section']   = $_POST['addr_area_section'];
            $data['init_addr_area_address1']  = $_POST['addr_area_address1'];
            $data['init_addr_area_address2']  = $_POST['addr_area_address2'];
            $data['init_addr_area_address3']  = $_POST['addr_area_address3'];
            $data['init_addr_area_postcode']  = $_POST['addr_area_postcode'];
            $data['init_addr_area_town']      = $_POST['addr_area_town'];
            $data['init_addr_area_state']     = $_POST['addr_area_state'];
            $data['init_addr_area_country']   = $_POST['addr_area_country'];
            $data['init_addr_area_tel']       = $_POST['addr_area_tel'];
            $data['init_addr_area_fax']       = $_POST['addr_area_fax'];
            $data['init_addr_area_email']     = $_POST['addr_area_email'];
            $data['init_addr_area_mgr1_position']= $_POST['addr_area_mgr1_position'];
            $data['init_addr_area_mgr1_name'] = $_POST['addr_area_mgr1_name'];
            $data['init_addr_area_mgr2_position']= $_POST['addr_area_mgr2_position'];
            $data['init_addr_area_mgr2_name'] = $_POST['addr_area_mgr2_name'];
            $data['init_addr_area_population']= $_POST['addr_area_population'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_area") {
                $data['init_addr_area_id']       	 = 	$data['addr_area_id'];
                $data['init_addr_district_id']       =   "";
                $data['init_addr_area_name']         =   "";
                $data['init_addr_area_code']         =   "";
                $data['init_addr_area_subcode']      =   "";
                $data['init_addr_area_sort']         =   "";
                $data['init_addr_area_descr']        =   "";
                $data['init_addr_area_section']      =   "";
                $data['init_addr_area_address1']     =   "";
                $data['init_addr_area_address2']     =   "";
                $data['init_addr_area_address3']     =   "";
                $data['init_addr_area_postcode']     =   "";
                $data['init_addr_area_town']         =   "";
                $data['init_addr_area_state']        =   "";
                $data['init_addr_area_country']      =   "";
                $data['init_addr_area_tel']          =   "";
                $data['init_addr_area_fax']          =   "";
                $data['init_addr_area_email']        =   "";
                $data['init_addr_area_mgr1_position']=   "";
                $data['init_addr_area_mgr1_name']    =   "";
                $data['init_addr_area_mgr2_position']=   "";
                $data['init_addr_area_mgr2_name']    =   "";
                $data['init_addr_area_population']   =   "";
            } elseif ($data['form_purpose'] == "edit_area") {
                //echo "Edit supplier";
                $data['area_info'] = $this->mutil_rdb->get_addr_area_list($data['app_country'],'addr_area_sort',$data['addr_area_id']);
                $data['init_addr_area_id']        = $data['addr_area_id'];
                $data['init_addr_district_id']    = $data['area_info'][0]['addr_district_id'];
                $data['init_addr_area_name']      = $data['area_info'][0]['addr_area_name'];
                $data['init_addr_area_code']      = $data['area_info'][0]['addr_area_code'];
                $data['init_addr_area_subcode']   = $data['area_info'][0]['addr_area_subcode'];
                $data['init_addr_area_sort']      = $data['area_info'][0]['addr_area_sort'];
                $data['init_addr_area_descr']     = $data['area_info'][0]['addr_area_descr'];
                $data['init_addr_area_section']   = $data['area_info'][0]['addr_area_section'];
                $data['init_addr_area_address1']  = $data['area_info'][0]['addr_area_address1'];
                $data['init_addr_area_address2']  = $data['area_info'][0]['addr_area_address2'];
                $data['init_addr_area_address3']  = $data['area_info'][0]['addr_area_address3'];
                $data['init_addr_area_postcode']  = $data['area_info'][0]['addr_area_postcode'];
                $data['init_addr_area_town']      = $data['area_info'][0]['addr_area_town'];
                $data['init_addr_area_state']     = $data['area_info'][0]['addr_area_state'];
                $data['init_addr_area_country']   = $data['area_info'][0]['addr_area_country'];
                $data['init_addr_area_tel']       = $data['area_info'][0]['addr_area_tel'];
                $data['init_addr_area_fax']       = $data['area_info'][0]['addr_area_fax'];
                $data['init_addr_area_email']     = $data['area_info'][0]['addr_area_email'];
                $data['init_addr_area_mgr1_position']= $data['area_info'][0]['addr_area_mgr1_position'];
                $data['init_addr_area_mgr1_name'] = $data['area_info'][0]['addr_area_mgr1_name'];
                $data['init_addr_area_mgr2_position']= $data['area_info'][0]['addr_area_mgr2_position'];
                $data['init_addr_area_mgr2_name'] = $data['area_info'][0]['addr_area_mgr2_name'];
                $data['init_addr_area_population']= $data['area_info'][0]['addr_area_population'];
            } //endif ($data['form_purpose'] == "new_area")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Area";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
		$data['addr_area_id']		=	$data['init_addr_area_id'];
		$data['addr_district_list']  	= 	$this->mutil_rdb->get_addr_district_list($data['app_country'],'addr_district_sort');
		$data['district_info']  	= 	$this->mutil_rdb->get_addr_district_list($data['app_country'],'addr_district_sort',NULL,$data['init_addr_district_id']);
		if(count($data['district_info']) > 0){
			$data['init_addr_area_state']     = $data['district_info'][0]['addr_state_name'];
			$data['init_addr_area_country']   = $data['district_info'][0]['addr_state_country'];
		}

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_addr_area') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_emr_wap";
                $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
                $new_body   =   "ehr/ehr_util_edit_area_info_wap";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "ehr/sidebar_emr_utilities_html";
                $new_body   =   "bio/bio_util_edit_area_info_html";
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
            if($data['form_purpose'] == "new_area") {
                // New area record
                $ins_area_array   =   array();
                $ins_area_array['staff_id']          = $_SESSION['staff_id'];
                $ins_area_array['now_id']            = $data['now_id'];
                $ins_area_array['addr_area_id']      = $data['now_id'];
                $ins_area_array['addr_district_id']  = $data['init_addr_district_id'];
                $ins_area_array['addr_area_name']    = $data['init_addr_area_name'];
                $ins_area_array['addr_area_code']    = $data['init_addr_area_code'];
                $ins_area_array['addr_area_subcode'] = $data['init_addr_area_subcode'];
                if(is_numeric($data['init_addr_area_sort'])){
                    $ins_area_array['addr_area_sort']= $data['init_addr_area_sort'];
                }
                //$ins_area_array['addr_area_sort']      = $data['init_addr_area_sort'];
                $ins_area_array['addr_area_descr']   = $data['init_addr_area_descr'];
                $ins_area_array['addr_area_section'] = $data['init_addr_area_section'];
                $ins_area_array['addr_area_address1']= $data['init_addr_area_address1'];
                $ins_area_array['addr_area_address2']= $data['init_addr_area_address2'];
                $ins_area_array['addr_area_address3']= $data['init_addr_area_address3'];
                $ins_area_array['addr_area_postcode']= $data['init_addr_area_postcode'];
                $ins_area_array['addr_area_town']    = $data['init_addr_area_town'];
                $ins_area_array['addr_area_state']   = $data['init_addr_area_state'];
                $ins_area_array['addr_area_country'] = $data['init_addr_area_country'];
                $ins_area_array['addr_area_tel']     = $data['init_addr_area_tel'];
                $ins_area_array['addr_area_fax']     = $data['init_addr_area_fax'];
                $ins_area_array['addr_area_email']   = $data['init_addr_area_email'];
                $ins_area_array['addr_area_mgr1_position']= $data['init_addr_area_mgr1_position'];
                $ins_area_array['addr_area_mgr1_name']= $data['init_addr_area_mgr1_name'];
                $ins_area_array['addr_area_mgr2_position']= $data['init_addr_area_mgr2_position'];
                $ins_area_array['addr_area_mgr2_name']= $data['init_addr_area_mgr2_name'];
                if(is_numeric($data['init_addr_area_population'])){
                    $ins_area_array['addr_area_population']= $data['init_addr_area_population'];
                }
                //$ins_area_array['addr_area_population']      = $data['init_addr_area_population'];
                if($data['offline_mode']){
                    $ins_area_array['synch_out']      = $data['now_id'];
                }
	            $ins_area_data       =   $this->mutil_wdb->insert_new_area($ins_area_array);
            } elseif($data['form_purpose'] == "edit_area") {
                // Existing supplier record
                $upd_area_array   =   array();
                $upd_area_array['staff_id']       	 = $_SESSION['staff_id'];
                $upd_area_array['addr_area_id']      = $data['addr_area_id'];
                $upd_area_array['addr_district_id']  = $data['init_addr_district_id'];
                $upd_area_array['addr_area_name']    = $data['init_addr_area_name'];
                $upd_area_array['addr_area_code']    = $data['init_addr_area_code'];
                $upd_area_array['addr_area_subcode'] = $data['init_addr_area_subcode'];
                if(is_numeric($data['init_addr_area_sort'])){
                    $upd_area_array['addr_area_sort']= $data['init_addr_area_sort'];
                }
                //$upd_area_array['addr_area_sort']  = $data['init_addr_area_sort'];
                $upd_area_array['addr_area_descr']   = $data['init_addr_area_descr'];
                $upd_area_array['addr_area_section'] = $data['init_addr_area_section'];
                $upd_area_array['addr_area_address1']= $data['init_addr_area_address1'];
                $upd_area_array['addr_area_address2']= $data['init_addr_area_address2'];
                $upd_area_array['addr_area_address3']= $data['init_addr_area_address3'];
                $upd_area_array['addr_area_postcode']= $data['init_addr_area_postcode'];
                $upd_area_array['addr_area_town']    = $data['init_addr_area_town'];
                $upd_area_array['addr_area_state']   = $data['init_addr_area_state'];
                $upd_area_array['addr_area_country'] = $data['init_addr_area_country'];
                $upd_area_array['addr_area_tel']     = $data['init_addr_area_tel'];
                $upd_area_array['addr_area_fax']     = $data['init_addr_area_fax'];
                $upd_area_array['addr_area_email']   = $data['init_addr_area_email'];
                $upd_area_array['addr_area_mgr1_position']= $data['init_addr_area_mgr1_position'];
                $upd_area_array['addr_area_mgr1_name']= $data['init_addr_area_mgr1_name'];
                $upd_area_array['addr_area_mgr2_position']= $data['init_addr_area_mgr2_position'];
                $upd_area_array['addr_area_mgr2_name']= $data['init_addr_area_mgr2_name'];
                if(is_numeric($data['init_addr_area_population'])){
                    $upd_area_array['addr_area_population']= $data['init_addr_area_population'];
                }
                //$upd_area_array['addr_area_population']      = $data['init_addr_area_population'];
	            $upd_area_data       =   $this->mutil_wdb->update_area_info($upd_area_array);
            } //endif($data['diagnosis_id'] == "new_area")
            $new_page = base_url()."index.php/bio_utilities/util_list_addrareas/addr_area_sort";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_addr_area') == FALSE)


    } // end of function util_edit_area_info()


    // ------------------------------------------------------------------------
    function util_list_addrdistricts($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Districts";
		$data['district_list']  = $this->mutil_rdb->get_addr_district_list($data['app_country'],$data['sort_order']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
            $new_body   =   "ehr/ehr_util_list_addrdistricts_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "bio/header_xhtml1-transitional";
            $new_banner =   "bio/banner_bio_hosp";
            $new_sidebar=   "ehr/sidebar_emr_utilities_html";
            $new_body   =   "bio/bio_util_list_addrdistricts_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function util_list_addrdistricts($id)


    // ------------------------------------------------------------------------
    function util_list_addrstates($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['title'] = "T H I R R A - List of States";
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['state_list']  = $this->mutil_rdb->get_addr_state_list('All',$data['sort_order']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
            $new_body   =   "ehr/ehr_util_list_addrstates_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "bio/header_xhtml1-transitional";
            $new_banner =   "bio/banner_bio_hosp";
            $new_sidebar=   "ehr/sidebar_emr_utilities_html";
            $new_body   =   "bio/bio_util_list_addrstates_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function util_list_addrstates($id)


    // ------------------------------------------------------------------------
    function util_list_drugformulary($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
        $data['sort_order']   	    = $this->uri->segment(3);
        //$data['page_num']   	    = $this->uri->segment(4);
        $data['per_page']           = '10';
        $data['row_first']   	    = $this->uri->segment(4);//$data['page_num'] * $data['per_page'];
		$data['title'] = "T H I R R A - List of Drug Formularies";
		$data['formulary_list']  = $this->mutil_rdb->get_drug_formulary_list('data',$data['sort_order'],$data['per_page'],$data['row_first']);
		$data['count_fulllist']  = $this->mutil_rdb->get_drug_formulary_list('count',$data['sort_order'],'ALL',0);
        
        $this->load->library('pagination');

        $config['base_url'] = base_url()."index.php/ehr_utilities/util_list_drugformulary/formulary_code/";
        $config['total_rows']   = $data['count_fulllist'];
        $config['per_page']     = $data['per_page'];
        $config['num_links']    = 10;
        $config['uri_segment']  = 4;
        $this->pagination->initialize($config);

        //echo $this->pagination->create_links();

		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_utilities_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "bio/header_xhtml1-transitional";
            $new_banner =   "bio/banner_bio_hosp";
            $new_sidebar=   "ehr/sidebar_emr_utilities_html";
            $new_body   =   "ehr/ehr_util_list_drugformulary_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function util_list_drugformulary($id)


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
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "bio/header_xhtml1-transitional";
            $new_banner =   "bio/banner_bio_hosp";
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
