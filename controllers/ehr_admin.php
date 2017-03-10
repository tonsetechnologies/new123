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
 * Portions created by the Initial Developer are Copyright (C) 2009-2011
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

/**
 * Controller Class for EHR_ADMIN
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_admin extends MY_Controller 
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
		$this->load->model('madmin_rdb');
		$this->load->model('madmin_wdb');
		//$this->load->model('mehr_wdb');
		$this->load->model('mutil_rdb');
        
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
    // === ADMIN MANAGEMENT
    // ------------------------------------------------------------------------
    function admin_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin');    
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "T H I R R A - Admin Management";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['users_list'] = $this->madmin_rdb->get_users_list();
        $data['centres_list'] = $this->madmin_rdb->get_referral_centres();
		$data['unsynched_logins'] = $this->madmin_rdb->get_unsynched_logins('ALL');
		$data['unsynched_new_patients'] = $this->madmin_rdb->get_unsynched_patients('ALL',FALSE);
		$data['unsynched_old_patients'] = $this->madmin_rdb->get_unsynched_patients('ALL',TRUE);
		$data['unsynched_antenatalinfo'] = $this->madmin_rdb->get_unsynched_antenatalinfo('ALL');
		$data['unsynched_antenatalcheckup'] = $this->madmin_rdb->get_unsynched_antenatalcheckup('ALL');
		$data['unsynched_antenataldelivery'] = $this->madmin_rdb->get_unsynched_antenataldelivery('ALL');
		$data['unsynched_closed_episodes'] = $this->madmin_rdb->get_unsynched_episodes('ALL','Closed');
		$data['unsynched_open_episodes'] = $this->madmin_rdb->get_unsynched_episodes('ALL','Open');
		$data['unsynched_historyimmunisation'] = $this->madmin_rdb->get_unsynched_historyimmunisation('ALL');
        /*
		$data['unsynched_logins'] = $this->madmin_rdb->get_unsynched_logins($data['location_id']);
		$data['unsynched_newpatients'] = $this->madmin_rdb->get_unsynched_patients($data['location_id'],TRUE);
		$data['unsynched_oldpatients'] = $this->madmin_rdb->get_unsynched_patients($data['location_id'],FALSE);
		$data['unsynched_antenatalinfo'] = $this->madmin_rdb->get_unsynched_antenatalinfo($data['location_id']);
		$data['unsynched_antenatalcheckup'] = $this->madmin_rdb->get_unsynched_antenatalcheckup($data['location_id']);
		$data['unsynched_antenataldelivery'] = $this->madmin_rdb->get_unsynched_antenataldelivery($data['location_id']);
		$data['unsynched_episodes'] = $this->madmin_rdb->get_unsynched_episodes($data['location_id']);
		$data['unsynched_historyimmunisation'] = $this->madmin_rdb->get_unsynched_historyimmunisation($data['location_id']);
        */
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_mgt_wap";
            $new_body   =   "ehr/ehr_admin_mgt_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_mgt_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_admin'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function admin_mgt($id)


    // ------------------------------------------------------------------------
    function admin_list_systemusers($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin');    
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of System Users";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['users_list'] = $this->madmin_rdb->get_users_list();
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_list_systemusers_wap";
            $new_body   =   "ehr/ehr_admin_list_systemusers_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_list_systemusers_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_admin'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function admin_list_systemusers($id)


    // ------------------------------------------------------------------------
    function edit_systemuser($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin','ehr_admin/admin_list_systemusers/username','List System Users');    
		$data['form_purpose']   = $this->uri->segment(3);
		$data['user_id']        = $this->uri->segment(4);
		$data['title'] = "Add New / Edit System User";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['users_list'] = $this->madmin_rdb->get_users_list();
        $data['systemuser_categories'] = $this->madmin_rdb->get_systemuser_categories();
        $data['staff_categories'] = $this->madmin_rdb->get_staff_categories();
		$data['clinics_list'] = $this->madmin_rdb->get_clinics_list('All');
        
        if(count($_POST)) {
            // User has posted the form
            $data['user_id']        =   $_POST['user_id'];
            //$data['category_id']    =   $_POST['category_id'];
            $data['username']       =   $_POST['username'];
            $data['init_password1'] =   $_POST['password1'];
            $data['init_password2'] =   $_POST['password2'];
            $data['staff_id']       =   $_POST['staff_id'];
            $data['expiry_date']    =   $_POST['expiry_date'];
            //$data['access_status']  =   $_POST['access_status'];
            //$data['permission']     =   $_POST['permission'];
            $data['staff_category_id']= $_POST['staff_category_id'];
            $data['staff_name']     =   $_POST['staff_name'];
            $data['staff_initials'] =   $_POST['staff_initials'];
            $data['mmc_no']         =   $_POST['mmc_no'];
            $data['specialty']      =   $_POST['specialty'];
            $data['gender']         =   $_POST['gender'];
            $data['ic_no']          =   $_POST['ic_no'];
            $data['ic_other_type']  =   $_POST['ic_other_type'];
            $data['ic_other_no']    =   $_POST['ic_other_no'];
            $data['nationality']    =   $_POST['nationality'];
            $data['date_of_birth']  =   $_POST['date_of_birth'];
            $data['race']           =   $_POST['race'];
            $data['home_clinic']    =   $_POST['home_clinic'];
            $data['address']        =   $_POST['address'];
            $data['address2']       =   $_POST['address2'];
            $data['address3']       =   $_POST['address3'];
            $data['town']           =   $_POST['town'];
            $data['state']          =   $_POST['state'];
            $data['postcode']       =   $_POST['postcode'];
            $data['country']        =   $_POST['country'];
            $data['tel_home']       =   $_POST['tel_home'];
            $data['tel_mobile']     =   $_POST['tel_mobile'];
            $data['email']          =   $_POST['email'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_systemuser") {
                // New user
		        $data['user_info']      =  array();
                $data['user_id']        =   "";
                $data['category_id']    =   "";
                $data['username']       =   "";
                $data['init_password1']      =   "";
                $data['init_password2']      =   "";
                $data['staff_id']       =   "";
                $data['expiry_date']    =   "";
                $data['access_status']  =   "";
                $data['permission']     =   "";
                $data['staff_category_id']=   "";
                $data['staff_name']     =   "";
                $data['staff_initials'] =   "";
                $data['mmc_no']         =   "";
                $data['specialty']      =   "";
                $data['gender']         =   "";
                $data['ic_no']          =   "";
                $data['ic_other_type']  =   "";
                $data['ic_other_no']    =   "";
                $data['nationality']    =   "";
                $data['date_of_birth']  =   "";
                $data['race']           =   "";
                $data['home_clinic']    =   "";
                $data['address']        =   "";
                $data['address2']       =   "";
                $data['address3']       =   "";
                $data['town']           =   "";
                $data['state']           =   "";
                $data['postcode']        =   "";
                $data['country']         =   "";
                $data['tel_home']        =   "";
                $data['tel_mobile']      =   "";
                $data['email']           =   "";
            } else {
                // Existing user
		        $data['user_info'] =  $this->madmin_rdb->get_one_systemuser($data['user_id']);
                $data['category_id']    =   $data['user_info']['category_id'];
                $data['username']       =   $data['user_info']['username'];
                $data['password']       =   $data['user_info']['password'];
                $data['init_password1'] =   "";
                $data['init_password2'] =   "";
                $data['staff_id']       =   $data['user_info']['staff_id'];
                $data['expiry_date']    =   $data['user_info']['expiry_date'];
                //$data['access_status']  =   $data['user_info']['access_status'];
                $data['permission']     =   $data['user_info']['permission'];
                $data['staff_category_id']=   $data['user_info']['staff_category_id'];
                $data['staff_name']     =   $data['user_info']['staff_name'];
                $data['staff_initials'] =   $data['user_info']['staff_initials'];
                $data['mmc_no']         =   $data['user_info']['mmc_no'];
                $data['specialty']      =   $data['user_info']['specialty'];
                $data['gender']         =   $data['user_info']['gender'];
                $data['ic_no']          =   $data['user_info']['ic_no'];
                $data['ic_other_type']  =   $data['user_info']['ic_other_type'];
                $data['ic_other_no']    =   $data['user_info']['ic_other_no'];
                $data['nationality']    =   $data['user_info']['nationality'];
                $data['date_of_birth']  =   $data['user_info']['date_of_birth'];
                $data['race']           =   $data['user_info']['race'];
                $data['address']        =   $data['user_info']['address'];
                $data['address2']       =   $data['user_info']['address2'];
                $data['address3']       =   $data['user_info']['address3'];
                $data['town']           =   $data['user_info']['town'];
                $data['state']          =   $data['user_info']['state'];
                $data['postcode']       =   $data['user_info']['postcode'];
                $data['country']        =   $data['user_info']['country'];
                $data['tel_home']       =   $data['user_info']['tel_home'];
                $data['tel_mobile']     =   $data['user_info']['tel_mobile'];
                $data['email']          =   $data['user_info']['email'];
                $data['home_clinic']          =   $data['user_info']['home_clinic'];
            } //endif ($data['form_purpose'] == "new_systemuser")
        } //endif(count($_POST))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_systemuser') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
                //$new_body   =   "ehr/emr_edit_systemuser_wap";
                $new_body   =   "ehr/ehr_admin_edit_systemuser_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_admin_edit_systemuser_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_admin'] < 100){
                $new_body   =   "ehr/ehr_access_denied_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            $this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_systemuser") {
                // Insert records
                $ins_user_array['user_id']          = $data['now_id'];
                $ins_user_array['category_id']      = $data['staff_category_id']; //$data['category_id'];
                $ins_user_array['username']         = $data['username'];
                $ins_user_array['password']         = crypt($data['init_password1']);
                $ins_user_array['expiry_date']      = $data['expiry_date'];
                $ins_user_array['access_status']    = "A";//$data['access_status'];
                $ins_user_array['permission']       = 262143;//$data['permission'];
                $ins_user_array['staff_id']         = $data['now_id'];
                $ins_user_array['staff_category_id']= $data['staff_category_id'];
                $ins_user_array['staff_name']       = $data['staff_name'];
                $ins_user_array['staff_initials']   = $data['staff_initials'];
                $ins_user_array['mmc_no']           = $data['mmc_no'];
                $ins_user_array['specialty']        = $data['specialty'];
                $ins_user_array['gender']           = $data['gender'];
                $ins_user_array['ic_no']            = $data['ic_no'];
                $ins_user_array['ic_other_type']    = $data['ic_other_type'];
                $ins_user_array['ic_other_no']      = $data['ic_other_no'];
                $ins_user_array['nationality']      = $data['nationality'];
                if(!empty($data['date_of_birth'])){
                    $ins_user_array['date_of_birth']    = $data['date_of_birth'];
                }
                $ins_user_array['race']             = $data['race'];
                $ins_user_array['staff_contact_id'] = $data['now_id'];
                $ins_user_array['staff_work_id']    = $data['now_id'];
                $ins_user_array['wage_type']        = "Monthly";//$data['wage_type'];
                $ins_user_array['location_id']      = $data['home_clinic'];
                $ins_user_array['home_clinic']      = $data['home_clinic'];
                $ins_user_array['clinic_dept_id']   = $data['home_clinic']; // TO CHANGE LATER TO USER CHOICE
                $ins_user_array['address']          = $data['address'];
                $ins_user_array['address2']         = $data['address2'];
                $ins_user_array['address3']         = $data['address3'];
                $ins_user_array['town']             = $data['town'];
                $ins_user_array['state']            = $data['state'];
                $ins_user_array['postcode']         = $data['postcode'];
                $ins_user_array['country']          = $data['country'];
                $ins_user_array['tel_home']         = $data['tel_home'];
                $ins_user_array['tel_mobile']       = $data['tel_mobile'];
                $ins_user_array['email']            = $data['email'];
                $ins_user_data =   $this->madmin_wdb->insert_new_systemuser($ins_user_array);
                $this->session->set_flashdata('data_activity', 'User added.');
            } else {
                // Update records
                $ins_user_array['user_id']          = $data['user_id'];
                $ins_user_array['category_id']      = $data['staff_category_id']; //$data['category_id'];
                $ins_user_array['username']         = $data['username'];
                if(!empty($data['init_password1'])){
                    $ins_user_array['password']         = crypt($data['init_password1']);
                }
                $ins_user_array['expiry_date']      = $data['expiry_date'];
                $ins_user_array['access_status']    = "A";//$data['access_status'];
                $ins_user_array['permission']       = 262143;//$data['permission'];
                $ins_user_array['staff_id']         = $data['staff_id'];
                $ins_user_array['staff_category_id']= $data['staff_category_id'];
                $ins_user_array['staff_name']       = $data['staff_name'];
                $ins_user_array['staff_initials']   = $data['staff_initials'];
                $ins_user_array['mmc_no']           = $data['mmc_no'];
                $ins_user_array['specialty']        = $data['specialty'];
                $ins_user_array['gender']           = $data['gender'];
                $ins_user_array['ic_no']            = $data['ic_no'];
                $ins_user_array['ic_other_type']    = $data['ic_other_type'];
                $ins_user_array['ic_other_no']      = $data['ic_other_no'];
                $ins_user_array['nationality']      = $data['nationality'];
                if(!empty($data['date_of_birth'])){
                    $ins_user_array['date_of_birth']    = $data['date_of_birth'];
                }
                $ins_user_array['race']             = $data['race'];
                $ins_user_array['staff_contact_id'] = $data['staff_id']; // assumed same as staff_id
                $ins_user_array['staff_work_id']    = $data['staff_id']; // assumed same as staff_id
                //$ins_user_array['wage_type']        = "Monthly";//$data['wage_type'];
                //$ins_user_array['location_id']      = $data['home_clinic'];
                $ins_user_array['home_clinic']      = $data['home_clinic'];
                $ins_user_array['address']          = $data['address'];
                $ins_user_array['address2']         = $data['address2'];
                $ins_user_array['address3']         = $data['address3'];
                $ins_user_array['town']             = $data['town'];
                $ins_user_array['state']            = $data['state'];
                $ins_user_array['postcode']         = $data['postcode'];
                $ins_user_array['country']          = $data['country'];
                $ins_user_array['tel_home']         = $data['tel_home'];
                $ins_user_array['tel_mobile']       = $data['tel_mobile'];
                $ins_user_array['email']            = $data['email'];
                $ins_user_data =   $this->madmin_wdb->update_system_user($ins_user_array);
                $this->session->set_flashdata('data_activity', 'User updated.');
            } //endif($data['form_purpose'] == "new_systemuser")
            $new_page = base_url()."index.php/ehr_admin/admin_list_systemusers";
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_systemuser') == FALSE)
    } // end of function edit_systemuser($id)


    // ------------------------------------------------------------------------
    function admin_list_staffcategories($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin');    
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of System Users";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['users_list'] = $this->madmin_rdb->get_staff_categories();
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_list_systemusers_wap";
            $new_body   =   "ehr/ehr_admin_list_staffcategories_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_list_staffcategories_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_admin'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function admin_list_staffcategories($id)


    // ------------------------------------------------------------------------
    function edit_staff_category($id=NULL) 
    {
        // Basis: To simplify THIRRA system, staff_category name === system_category name
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin','ehr_admin/admin_list_staffcategories','List Staff Categories');    
		$data['form_purpose']       = $this->uri->segment(3);
		$data['category_id']        = $this->uri->segment(4);
		$data['title'] = "Add New / Edit Staff Category";
        $data['now_id']             =   time();
        //$data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['now_time']           =   date("H:i",$data['now_id']);
		$data['users_list'] = $this->madmin_rdb->get_users_list();
        $data['systemuser_categories'] = $this->madmin_rdb->get_systemuser_categories();
        
        if(count($_POST)) {
            // User has posted the form
            $data['category_id']            =   $this->input->post('category_id');
            $data['category_name']          =   $this->input->post('category_name');
            $data['description']            =   $this->input->post('description');
            $data['init_access_patients']   =   $this->input->post('access_patients');
            $data['init_access_pharmacy']   =   $this->input->post('access_pharmacy');
            $data['init_access_orders']     =   $this->input->post('access_orders');
            $data['init_access_queue']      =   $this->input->post('access_queue');
            $data['init_access_reports']    =   $this->input->post('access_reports');
            $data['init_access_utilities']  =   $this->input->post('access_utilities');
            $data['init_access_admin']      =   $this->input->post('access_admin');
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_category") {
                // New user
		        $data['category_info']      =  array();
                $data['category_id']    =   "new_category";
                $data['category_name']          =   "";
                $data['description']            =   "";
                $data['init_access_patients']   =   "";
                $data['init_access_pharmacy']   =   "";
                $data['init_access_orders']     =   "";
                $data['init_access_queue']      =   "";
                $data['init_access_reports']    =   "";
                $data['init_access_utilities']  =   "";
                $data['init_access_admin']      =   "";
            } else {
                // Existing user
		        $data['category_info']  =  $this->madmin_rdb->get_one_staffcategory($data['category_id']);
                $data['category_id']    =   $data['category_info']['category_id'];
                $data['category_name']  =   $data['category_info']['category_name'];
                $data['description']    =   $data['category_info']['description'];
                $data['sys_category_id']=   $data['category_info']['sys_category_id'];
                $data['permission']     =   $data['category_info']['permission'];
                $data['access_rights']      =   $this->get_user_rights($data['permission']);
                $data['access_rights']['permission'] =   $data['permission'];
                $data['init_access_patients']   =    $data['access_rights']['section_patients'];
                $data['init_access_pharmacy']   =   $data['access_rights']['section_pharmacy'];
                $data['init_access_orders']     =   $data['access_rights']['section_orders'];
                $data['init_access_queue']      =   $data['access_rights']['section_queue'];
                $data['init_access_reports']    =   $data['access_rights']['section_reports'];
                $data['init_access_utilities']  =   $data['access_rights']['section_utilities'];
                $data['init_access_admin']      =   $data['access_rights']['section_admin'];
            } //endif ($data['form_purpose'] == "new_category")
        } //endif(count($_POST))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_staff_category') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
                //$new_body   =   "ehr/emr_edit_systemuser_wap";
                $new_body   =   "ehr/ehr_admin_edit_staff_category_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_admin_edit_staff_category_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_admin'] < 100){
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
            // Generate permission
            $data['rights_decimal']   =   0;
            if(is_numeric($data['init_access_admin'])){                    
                $data['rights_decimal']   +=   1;
            }
            if(is_numeric($data['init_access_reports'])){                    
                $data['rights_decimal']   +=   2;
            }
            if(is_numeric($data['init_access_pharmacy'])){                    
                $data['rights_decimal']   +=   4;
            }
            if(is_numeric($data['init_access_orders'])){                    
                $data['rights_decimal']   +=   8;
            }
            if(is_numeric($data['init_access_patients'])){                    
                $data['rights_decimal']   +=   16;
            }
            if(is_numeric($data['init_access_queue'])){                    
                $data['rights_decimal']   +=   32;
            }
            /* Finance and Billing 
            if(is_numeric($data['init_access_patients'])){                    
                $data['rights_decimal']   +=   64;
            }
            if(is_numeric($data['init_access_patients'])){                    
                $data['rights_decimal']   +=   128;
            }
            */
            if(is_numeric($data['init_access_utilities'])){                    
                $data['rights_decimal']   +=   256;
            }
            if($data['form_purpose'] == "new_category") {
                // Insert records
                $ins_cate_array['category_id']      = $data['now_id'];
                $ins_cate_array['category_name']    = $data['category_name'];
                $ins_cate_array['description']      = $data['description'];
                $ins_cate_array['permission']       = $data['rights_decimal'];
                //echo "data['rights_decimal']".$data['rights_decimal'];
                $ins_user_data =   $this->madmin_wdb->insert_new_staffcategory($ins_cate_array);
                $this->session->set_flashdata('data_activity', 'Staff category added.');
            } else {
                // Update records
                $upd_cate_array['category_id']      = $data['category_id'];
                $upd_cate_array['category_name']    = $data['category_name'];
                $upd_cate_array['description']      = $data['description'];
                $upd_cate_array['permission']       = $data['rights_decimal'];
                //echo "data['rights_decimal']".$data['rights_decimal'];
                $upd_user_data =   $this->madmin_wdb->update_staffcategory($upd_cate_array);
                $this->session->set_flashdata('data_activity', 'Staff category updated.');
            } //endif($data['form_purpose'] == "new_category")
            $new_page = base_url()."index.php/ehr_admin/admin_list_staffcategories";
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_staff_category') == FALSE)
    } // end of function edit_staff_category($id)


    // ------------------------------------------------------------------------
    function admin_list_clinics($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['country']		    =	$this->config->item('app_country');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin');    
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Clinics";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['clinics_list'] = $this->mthirra->get_clinics_list($data['country'],$data['sort_order']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_list_clinics_wap";
            $new_body   =   "ehr/ehr_admin_list_clinics_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_list_clinics_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_admin'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function admin_list_clinics($id)


    // ------------------------------------------------------------------------
    function admin_edit_clinic_info()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
		$this->load->model('mutil_rdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin','ehr_admin/admin_list_clinics/sort_clinic','List Clinics');    
        $data['form_purpose']   	= 	$this->uri->segment(3);
        $data['clinic_info_id']		= 	$this->uri->segment(4);
	  	
        if(count($_POST)) {
            // User has posted the form
            //$data['form_purpose']           = $_POST['form_purpose'];
            $data['init_clinic_name']       =   $this->input->post('clinic_name');
            $data['init_clinic_ref_no']     =   $this->input->post('clinic_ref_no');
            $data['init_clinic_shortname']  =   $this->input->post('clinic_shortname');
            $data['init_manager_id']        = $_POST['manager_id'];
            $data['init_owner_id']          = $_POST['owner_id'];
            $data['init_time_open']         = $_POST['time_open'];
            $data['init_time_close']        = $_POST['time_close'];
            $data['init_locale']            = $_POST['locale'];
            $data['init_address']           = $_POST['address'];
            $data['init_address2']          = $_POST['address2'];
            //$data['init_address3']          = $_POST['address3'];
            //$data['init_town']              = $_POST['town'];
            //$data['init_state']             = $_POST['state'];
            $data['init_postcode']          = $_POST['postcode'];
            //$data['init_country']           = $_POST['country'];
            $data['init_tel_no']            = $_POST['tel_no'];
            $data['init_tel_no2']           = $_POST['tel_no2'];
            $data['init_tel_no3']           = $_POST['tel_no3'];
            $data['init_fax_no']            = $_POST['fax_no'];
            $data['init_fax_no2']           = $_POST['fax_no2'];
            $data['init_email']             = $_POST['email'];
            $data['init_other']             = $_POST['other'];
            $data['init_established']       = $_POST['established'];
            $data['init_owner_type']        = $_POST['owner_type'];
            $data['init_health_department_id']= $_POST['health_department_id'];
            $data['init_remarks']           = $_POST['remarks'];
            $data['init_pcdom_ref']         = $_POST['pcdom_ref'];
            $data['init_markup_1']          = $_POST['markup_1'];
            $data['init_markup_2']          = $_POST['markup_2'];
            $data['init_markup_3']          = $_POST['markup_3'];
            $data['init_sort_clinic']       = $_POST['sort_clinic'];
            $data['init_clinic_privatekey'] = $_POST['clinic_privatekey'];
            $data['init_clinic_publickey']  = $_POST['clinic_publickey'];
            $data['init_addr_village_id']   = $_POST['addr_village_id'];
            //$data['init_addr_town_id']      = $_POST['addr_town_id'];
            //$data['init_addr_area_id']      = $_POST['addr_area_id'];
            //$data['init_addr_district_id']  = $_POST['addr_district_id'];
            //$data['init_addr_state_id']     = $_POST['addr_state_id'];
            $data['init_clinic_district_id']= $_POST['clinic_district_id'];
            $data['init_clinic_status']     = $_POST['clinic_status'];
            $data['init_clinic_gps_lat']    = $_POST['clinic_gps_lat'];
            $data['init_clinic_gps_long']   = $_POST['clinic_gps_long'];
            $data['init_clinic_type']       = $_POST['clinic_type'];
            if($data['form_purpose'] == "new_clinic"){
                $data['init_dept_name']       = $_POST['dept_name'];
                $data['init_dept_shortname']  = $_POST['dept_shortname'];
            }
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_clinic") {
                $data['init_clinic_info_id']    =   $data['clinic_info_id'];
                $data['init_clinic_name']       =   "";
                $data['init_clinic_ref_no']     =   "";
                $data['init_clinic_shortname']  =   "";
                $data['init_manager_id']        =   "";
                $data['init_owner_id']          =   "";
                $data['init_time_open']         =   "";
                $data['init_time_close']        =   "";
                $data['init_locale']            =   "";
                $data['init_address']           =   "";
                $data['init_address2']          =   "";
                $data['init_address3']          =   "";
                $data['init_town']              =   "";
                $data['init_state']             =   "";
                $data['init_postcode']          =   "";
                $data['init_country']           =   "";
                $data['init_tel_no']            =   "";
                $data['init_tel_no2']           =   "";
                $data['init_tel_no3']           =   "";
                $data['init_fax_no']            =   "";
                $data['init_fax_no2']           =   "";
                $data['init_email']             =   "";
                $data['init_other']             =   "";
                $data['init_established']       =   "";
                $data['init_owner_type']        =   "";
                $data['init_health_department_id']=   "";
                $data['init_remarks']           =   "";
                $data['init_pcdom_ref']         =   "";
                $data['init_markup_1']          =   "";
                $data['init_markup_2']          =   "";
                $data['init_markup_3']          =   "";
                $data['init_sort_clinic']       =   "";
                $data['init_clinic_privatekey'] =   "";
                $data['init_clinic_publickey']  =   "";
                $data['init_addr_village_id']   =   "";
                $data['init_addr_town_id']      =   "";
                $data['init_addr_area_id']      =   "";
                $data['init_addr_district_id']  =   "";
                $data['init_addr_state_id']     =   "";
                $data['init_clinic_district_id']=   "";
                $data['init_clinic_status']     =   "";
                $data['init_clinic_gps_lat']    =   "";
                $data['init_clinic_gps_long']   =   "";
                $data['init_clinic_type']       =   "";
                $data['init_dept_name']         =   "Outpatient Department";
                $data['init_dept_shortname']    =   "OPD";
            } elseif ($data['form_purpose'] == "edit_clinic") {
                //echo "Edit supplier";
                $data['clinic_info'] = $this->mthirra->get_clinic_info($data['clinic_info_id']);
                $data['init_clinic_name']       = $data['clinic_info']['clinic_name'];
                $data['init_clinic_ref_no']     = $data['clinic_info']['clinic_ref_no'];
                $data['init_clinic_shortname']  = $data['clinic_info']['clinic_shortname'];
                $data['init_manager_id']        = $data['clinic_info']['manager_id'];
                $data['init_owner_id']          = $data['clinic_info']['owner_id'];
                $data['init_time_open']         = $data['clinic_info']['time_open'];
                $data['init_time_close']        = $data['clinic_info']['time_close'];
                $data['init_locale']            = $data['clinic_info']['locale'];
                $data['init_address']           = $data['clinic_info']['address'];
                $data['init_address2']          = $data['clinic_info']['address2'];
                $data['init_address3']          = $data['clinic_info']['address3'];
                $data['init_town']              = $data['clinic_info']['town'];
                $data['init_state']             = $data['clinic_info']['state'];
                $data['init_postcode']          = $data['clinic_info']['postcode'];
                $data['init_country']           = $data['clinic_info']['country'];
                $data['init_tel_no']            = $data['clinic_info']['tel_no'];
                $data['init_tel_no2']           = $data['clinic_info']['tel_no2'];
                $data['init_tel_no3']           = $data['clinic_info']['tel_no3'];
                $data['init_fax_no']            = $data['clinic_info']['fax_no'];
                $data['init_fax_no2']           = $data['clinic_info']['fax_no2'];
                $data['init_email']             = $data['clinic_info']['email'];
                $data['init_other']             = $data['clinic_info']['other'];
                $data['init_established']       = $data['clinic_info']['established'];
                $data['init_owner_type']        = $data['clinic_info']['owner_type'];
                $data['init_health_department_id']= $data['clinic_info']['health_department_id'];
                $data['init_remarks']           = $data['clinic_info']['remarks'];
                $data['init_pcdom_ref']         = $data['clinic_info']['pcdom_ref'];
                $data['init_markup_1']          = $data['clinic_info']['markup_1'];
                $data['init_markup_2']          = $data['clinic_info']['markup_2'];
                $data['init_markup_3']          = $data['clinic_info']['markup_3'];
                $data['init_sort_clinic']       = $data['clinic_info']['sort_clinic'];
                $data['init_clinic_privatekey'] = $data['clinic_info']['clinic_privatekey'];
                $data['init_clinic_publickey']  = $data['clinic_info']['clinic_publickey'];
                $data['init_addr_village_id']   = $data['clinic_info']['addr_village_id'];
                $data['init_addr_town_id']      = $data['clinic_info']['addr_town_id'];
                $data['init_addr_area_id']      = $data['clinic_info']['addr_area_id'];
                $data['init_addr_district_id']  = $data['clinic_info']['addr_district_id'];
                $data['init_addr_state_id']     = $data['clinic_info']['addr_state_id'];
                $data['init_clinic_district_id']= $data['clinic_info']['clinic_district_id'];
                $data['init_clinic_status']     = $data['clinic_info']['clinic_status'];
                $data['init_clinic_gps_lat']    = $data['clinic_info']['clinic_gps_lat'];
                $data['init_clinic_gps_long']   = $data['clinic_info']['clinic_gps_long'];
                $data['init_clinic_type']       = $data['clinic_info']['clinic_type'];
            } //endif ($data['form_purpose'] == "new_clinic")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Clinic";
        $data['init_location_id']   =   $_SESSION['location_id'];
        //$data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['staff_list'] = $this->madmin_rdb->get_staff_list();
		$data['addr_village_list']	=	$this->mutil_rdb->get_addr_village_list($data['app_country'],"addr_village_sort");
		$data['village_info']	=	$this->mutil_rdb->get_addr_village_list($data['app_country'],"addr_village_sort",$data['init_addr_village_id']);
		if(count($data['village_info']) > 0){
			$data['init_addr_town_id']  = $data['village_info'][0]['addr_town_id'];
			$data['init_addr_area_id']  = $data['village_info'][0]['addr_area_id'];
			$data['init_addr_district_id']= $data['village_info'][0]['addr_district_id'];
			$data['init_addr_state_id'] = $data['village_info'][0]['addr_state_id'];
			$data['init_address3']      = $data['village_info'][0]['addr_village_name'];
			$data['init_town']          = $data['village_info'][0]['addr_town_name'];
			$data['init_state']         = $data['village_info'][0]['addr_district_state'];
			$data['init_country']       = $data['village_info'][0]['addr_district_country'];
		} else {
			$data['init_addr_town_id']      = "";
			$data['init_addr_area_id']      = "";
			$data['init_addr_district_id']  = "";
			$data['init_addr_state_id']     = "";
			$data['init_address3']          = "";
			$data['init_town']              = "";
			$data['init_state']             = "";
			$data['init_country']           = "";
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_clinic_info') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
                //$new_body   =   "ehr/ehr_admin_edit_clinic_info_wap";
                $new_body   =   "ehr/ehr_admin_edit_clinic_info_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_admin_edit_clinic_info_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_admin'] < 100){
                $new_body   =   "ehr/ehr_access_denied_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            $this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            //echo "\nValidated successfully.";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_clinic") {
                // New area record
                $ins_clinic_array   =   array();
                $ins_clinic_array['staff_id']       = $_SESSION['staff_id'];
                $ins_clinic_array['now_id']         = $data['now_id'];
                $ins_clinic_array['clinic_info_id'] = $data['now_id'];
                $ins_clinic_array['clinic_name']    = $data['init_clinic_name'];
                $ins_clinic_array['clinic_ref_no']  = $data['init_clinic_ref_no'];
                $ins_clinic_array['clinic_shortname']= $data['init_clinic_shortname'];
                $ins_clinic_array['manager_id']     = $data['init_manager_id'];
                $ins_clinic_array['owner_id']       = $data['init_owner_id'];
                //$ins_clinic_array['time_open']  = $data['init_time_open'];
                //$ins_clinic_array['time_close']  = $data['init_time_close'];
                $ins_clinic_array['locale']         = $data['init_locale'];
                $ins_clinic_array['address']        = $data['init_address'];
                $ins_clinic_array['address2']       = $data['init_address2'];
                $ins_clinic_array['address3']       = $data['init_address3'];
                $ins_clinic_array['town']           = $data['init_town'];
                $ins_clinic_array['state']          = $data['init_state'];
                $ins_clinic_array['postcode']       = $data['init_postcode'];
                $ins_clinic_array['country']        = $data['init_country'];
                $ins_clinic_array['tel_no']         = $data['init_tel_no'];
                $ins_clinic_array['tel_no2']        = $data['init_tel_no2'];
                $ins_clinic_array['tel_no3']        = $data['init_tel_no3'];
                $ins_clinic_array['fax_no']         = $data['init_fax_no'];
                $ins_clinic_array['fax_no2']        = $data['init_fax_no2'];
                $ins_clinic_array['email']          = $data['init_email'];
                $ins_clinic_array['other']          = $data['init_other'];
                //$ins_clinic_array['established']  = $data['init_established'];
                $ins_clinic_array['owner_type']     = $data['init_owner_type'];
                $ins_clinic_array['health_department_id']  = $data['init_health_department_id'];
                $ins_clinic_array['remarks']        = $data['init_remarks'];
                $ins_clinic_array['pcdom_ref']      = $data['init_pcdom_ref'];
                if(is_numeric($data['init_markup_1'])){
                    $ins_clinic_array['markup_1']= (int)$data['init_markup_1'];
                }
                //$ins_clinic_array['markup_1']  = $data['init_markup_1'];
                if(is_numeric($data['init_markup_2'])){
                    $ins_clinic_array['markup_2']= (int)$data['init_markup_2'];
                }
                //$ins_clinic_array['markup_2']  = $data['init_markup_2'];
                if(is_numeric($data['init_markup_3'])){
                    $ins_clinic_array['markup_3']= (int)$data['init_markup_3'];
                }
                //$ins_clinic_array['markup_3']  = $data['init_markup_3'];
                if(is_numeric($data['init_sort_clinic'])){
                    $ins_clinic_array['sort_clinic']= (int)$data['init_sort_clinic'];
                }
                //$ins_clinic_array['sort_clinic']  = $data['init_sort_clinic'];
                $ins_clinic_array['clinic_privatekey'] = $data['init_clinic_privatekey'];
                $ins_clinic_array['clinic_publickey'] = $data['init_clinic_publickey'];
                $ins_clinic_array['addr_village_id']= $data['init_addr_village_id'];
                $ins_clinic_array['addr_town_id']   = $data['init_addr_town_id'];
                $ins_clinic_array['addr_area_id']   = $data['init_addr_area_id'];
                $ins_clinic_array['addr_district_id'] = $data['init_addr_district_id'];
                $ins_clinic_array['addr_state_id']  = $data['init_addr_state_id'];
                $ins_clinic_array['clinic_district_id']  = $data['init_clinic_district_id'];
                $ins_clinic_array['clinic_status']  = $data['init_clinic_status'];
                $ins_clinic_array['clinic_gps_lat'] = $data['init_clinic_gps_lat'];
                $ins_clinic_array['clinic_gps_long']= $data['init_clinic_gps_long'];
                $ins_clinic_array['clinic_type']    = $data['init_clinic_type'];
                if($data['offline_mode']){
                    $ins_clinic_array['synch_out']      = $data['now_id'];
                }
                $ins_clinic_array['clinic_dept_id'] = $data['now_id'];
                $ins_clinic_array['location_id']    = $data['now_id'];
                $ins_clinic_array['dept_name']      = $data['init_dept_name'];
                $ins_clinic_array['dept_shortname'] = $data['init_dept_shortname'];
	            $ins_clinic_data       =   $this->madmin_wdb->insert_new_clinic($ins_clinic_array);
                $this->session->set_flashdata('data_activity', 'Clinic added.');
            } elseif($data['form_purpose'] == "edit_clinic") {
                // Existing supplier record
                $upd_clinic_array   =   array();
                $upd_clinic_array['staff_id']       = $_SESSION['staff_id'];
                $upd_clinic_array['clinic_info_id'] = $data['clinic_info_id'];
                $upd_clinic_array['clinic_name']    = $data['init_clinic_name'];
                $upd_clinic_array['clinic_ref_no']  = $data['init_clinic_ref_no'];
                $upd_clinic_array['clinic_shortname']= $data['init_clinic_shortname'];
                $upd_clinic_array['manager_id']     = $data['init_manager_id'];
                $upd_clinic_array['owner_id']       = $data['init_owner_id'];
                //$upd_clinic_array['time_open']  = $data['init_time_open'];
                //$upd_clinic_array['time_close']  = $data['init_time_close'];
                $upd_clinic_array['locale']         = $data['init_locale'];
                $upd_clinic_array['address']        = $data['init_address'];
                $upd_clinic_array['address2']       = $data['init_address2'];
                $upd_clinic_array['address3']       = $data['init_address3'];
                $upd_clinic_array['town']           = $data['init_town'];
                $upd_clinic_array['state']          = $data['init_state'];
                $upd_clinic_array['postcode']       = $data['init_postcode'];
                $upd_clinic_array['country']        = $data['init_country'];
                $upd_clinic_array['tel_no']         = $data['init_tel_no'];
                $upd_clinic_array['tel_no2']        = $data['init_tel_no2'];
                $upd_clinic_array['tel_no3']        = $data['init_tel_no3'];
                $upd_clinic_array['fax_no']         = $data['init_fax_no'];
                $upd_clinic_array['fax_no2']        = $data['init_fax_no2'];
                $upd_clinic_array['email']          = $data['init_email'];
                $upd_clinic_array['other']          = $data['init_other'];
                //$upd_clinic_array['established']  = $data['init_established'];
                $upd_clinic_array['owner_type']     = $data['init_owner_type'];
                $upd_clinic_array['health_department_id']  = $data['init_health_department_id'];
                $upd_clinic_array['remarks']        = $data['init_remarks'];
                $upd_clinic_array['pcdom_ref']      = $data['init_pcdom_ref'];
                if(is_numeric($data['init_markup_1'])){
                    $upd_clinic_array['markup_1']= $data['init_markup_1'];
                }
                //$upd_clinic_array['markup_1']  = $data['init_markup_1'];
                if(is_numeric($data['init_markup_2'])){
                    $upd_clinic_array['markup_2']= $data['init_markup_2'];
                }
                //$upd_clinic_array['markup_2']  = $data['init_markup_2'];
                if(is_numeric($data['init_markup_3'])){
                    $upd_clinic_array['markup_3']= $data['init_markup_3'];
                }
                //$upd_clinic_array['markup_3']  = $data['init_markup_3'];
                if(is_numeric($data['init_sort_clinic'])){
                    $upd_clinic_array['sort_clinic']= $data['init_sort_clinic'];
                }
                //$upd_clinic_array['sort_clinic']  = $data['init_sort_clinic'];
                $upd_clinic_array['clinic_privatekey']  = $data['init_clinic_privatekey'];
                $upd_clinic_array['clinic_publickey']  = $data['init_clinic_publickey'];
                $upd_clinic_array['addr_village_id']= $data['init_addr_village_id'];
                $upd_clinic_array['addr_town_id']   = $data['init_addr_town_id'];
                $upd_clinic_array['addr_area_id']   = $data['init_addr_area_id'];
                $upd_clinic_array['addr_district_id']  = $data['init_addr_district_id'];
                $upd_clinic_array['addr_state_id']  = $data['init_addr_state_id'];
                $upd_clinic_array['clinic_district_id']  = $data['init_clinic_district_id'];
                $upd_clinic_array['clinic_status']  = $data['init_clinic_status'];
                $upd_clinic_array['clinic_gps_lat'] = $data['init_clinic_gps_lat'];
                $upd_clinic_array['clinic_gps_long']= $data['init_clinic_gps_long'];
                $upd_clinic_array['clinic_type']    = $data['init_clinic_type'];
	            $upd_clinic_data       =   $this->madmin_wdb->update_clinic_info($upd_clinic_array);
                $this->session->set_flashdata('data_activity', 'Clinic updated.');
            } //endif($data['form_purpose'] == "new_clinic") 
            $new_page = base_url()."index.php/ehr_admin/admin_list_clinics/sort_clinic";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_addr_area') == FALSE)


    } // end of function admin_edit_clinic_info()


    // ------------------------------------------------------------------------
    function admin_list_depts($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['country']		    =	$this->config->item('app_country');
        $data['location_id']   =   $_SESSION['location_id'];
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin');    
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Departments";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['depts_list'] = $this->madmin_rdb->get_depts_list('All',$data['sort_order']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_list_clinics_wap";
            $new_body   =   "ehr/ehr_admin_list_depts_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_list_depts_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_admin'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function admin_list_depts($id)


    // ------------------------------------------------------------------------
    // admin_edit_clinic_dept
    function admin_edit_clinic_dept()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('mqueue_rdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin','ehr_admin/admin_list_depts/dept_sort','List Departments');    
        $data['form_purpose']   	= 	$this->uri->segment(3);
        $data['clinic_dept_id']		= 	$this->uri->segment(4);
        $data['location_id']   =   $_SESSION['location_id'];
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_location_id']     = $_POST['location_id'];
            $data['init_dept_name']       = $_POST['dept_name'];
            $data['init_dept_shortname']  = $_POST['dept_shortname'];
            $data['init_dept_code']       = $_POST['dept_code'];
            $data['init_dept_description']= $_POST['dept_description'];
            $data['init_dept_head']       = $_POST['dept_head'];
            $data['init_dept_telno']      = $_POST['dept_telno'];
            $data['init_dept_sort']       = $_POST['dept_sort'];
            $data['init_dept_remarks']    = $_POST['dept_remarks'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['referral_center_id']= $this->uri->segment(4);
            if ($data['form_purpose'] == "new_dept") {
                $data['init_location_id']   =   "";
                $data['init_dept_name']     =   "";
                $data['init_dept_shortname']   =   "";
                $data['init_dept_code']     =   "";
                $data['init_dept_description']   =   "";
                $data['init_dept_head']     =   "";
                $data['init_dept_telno']    =   "";
                $data['init_dept_sort']     =   "";
                $data['init_dept_remarks']   =   "";
            } elseif ($data['form_purpose'] == "edit_dept") {
                $data['dept_info'] = $this->madmin_rdb->get_dept_info($data['clinic_dept_id']);
                $data['init_location_id']   = $data['dept_info'][0]['location_id'];
                $data['init_dept_name']   = $data['dept_info'][0]['dept_name'];
                $data['init_dept_shortname']   = $data['dept_info'][0]['dept_shortname'];
                $data['init_dept_code']   = $data['dept_info'][0]['dept_code'];
                $data['init_dept_description']   = $data['dept_info'][0]['dept_description'];
                $data['init_dept_head']   = $data['dept_info'][0]['dept_head'];
                $data['init_dept_telno']   = $data['dept_info'][0]['dept_telno'];
                $data['init_dept_sort']   = $data['dept_info'][0]['dept_sort'];
                $data['init_dept_remarks']   = $data['dept_info'][0]['dept_remarks'];
            } //endif ($data['form_purpose'] == "new_dept")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Department";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
		$data['clinics_list']   = $this->mthirra->get_clinics_list('All');
        $data['rooms_list'] = $this->mqueue_rdb->get_rooms_list($data['location_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_clinic_dept') == FALSE){
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
                $new_body   =   "ehr/ehr_admin_edit_clinic_dept_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_admin_edit_clinic_dept_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_admin'] < 100){
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
            if($data['form_purpose'] == "new_dept") {
                // New department
                $ins_dept_array   =   array();
                $ins_dept_array['staff_id']       = $_SESSION['staff_id'];
                $ins_dept_array['now_id']         = $data['now_id'];
                $ins_dept_array['clinic_dept_id']= $data['now_id'];
                $ins_dept_array['location_id']    = $data['init_location_id'];
                $ins_dept_array['dept_name']    = $data['init_dept_name'];
                $ins_dept_array['dept_shortname']    = $data['init_dept_shortname'];
                $ins_dept_array['dept_code']    = $data['init_dept_code'];
                $ins_dept_array['dept_description']    = $data['init_dept_description'];
                $ins_dept_array['dept_head']    = $data['init_dept_head'];
                $ins_dept_array['dept_telno']    = $data['init_dept_telno'];
                if(is_numeric($data['init_dept_sort'])){
                    $ins_dept_array['dept_sort']    = $data['init_dept_sort'];
                }
                $ins_dept_array['dept_remarks']    = $data['init_dept_remarks'];
	            $ins_dept_data       =   $this->madmin_wdb->insert_new_clinic_dept($ins_dept_array);
                $this->session->set_flashdata('data_activity', 'Department added.');
            } elseif($data['form_purpose'] == "edit_dept") {
                // Existing department record
                echo "data['clinic_dept_id']=".$data['clinic_dept_id'];
                $upd_dept_array   =   array();
                $upd_dept_array['staff_id']       = $_SESSION['staff_id'];
                $upd_dept_array['clinic_dept_id']   = $data['clinic_dept_id'];
                $upd_dept_array['location_id']        = $data['init_location_id'];
                $upd_dept_array['dept_name']        = $data['init_dept_name'];
                $upd_dept_array['dept_shortname']        = $data['init_dept_shortname'];
                $upd_dept_array['dept_code']        = $data['init_dept_code'];
                $upd_dept_array['dept_description']        = $data['init_dept_description'];
                $upd_dept_array['dept_head']        = $data['init_dept_head'];
                $upd_dept_array['dept_telno']        = $data['init_dept_telno'];
                if(is_numeric($data['init_dept_sort'])){
                    $upd_dept_array['dept_sort']= $data['init_dept_sort'];
                }
                //$upd_dept_array['dept_sort']        = $data['init_dept_sort'];
                $upd_dept_array['dept_remarks']        = $data['init_dept_remarks'];
	            $upd_dept_data       =   $this->madmin_wdb->update_clinic_dept($upd_dept_array);
                $this->session->set_flashdata('data_activity', 'Department updated.');
            } //endif($data['form_purpose'] == "new_dept")
            $new_page = base_url()."index.php/ehr_admin/admin_list_depts/dept_sort";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_clinic_dept') == FALSE)


    } // end of function admin_edit_clinic_dept()


    // ------------------------------------------------------------------------
    function admin_list_referral_centres($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin');    
		$data['title'] = "T H I R R A - List of Referral Centres";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['centres_list'] = $this->madmin_rdb->get_referral_centres();
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_list_referral_centres_wap";
            $new_body   =   "ehr/ehr_admin_list_referral_centres_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_list_referral_centres_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_admin'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function admin_list_referral_centres($id)


    // ------------------------------------------------------------------------
    // admin_edit_referral_centre
    function admin_edit_referral_centre()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin','ehr_admin/admin_list_referral_centres','List Referral Centres');    
	  	$this->load->model('mthirra');
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['partner_clinic_id'])) { 
                $data['partner_clinic_id']   =   $_POST['partner_clinic_id'];
            }
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_referral_center_id']= $_POST['referral_center_id'];
            $data['referral_center_id'] = $_POST['referral_center_id'];
            $data['init_centre_name']   = $_POST['centre_name'];
            $data['init_centre_type']   = $_POST['centre_type'];
            $data['init_address']       = $_POST['address'];
            $data['init_address2']      = $_POST['address2'];
            $data['init_address3']      = $_POST['address3'];
            $data['init_town']          = $_POST['town'];
            $data['init_state']         = $_POST['state'];
            $data['init_postcode']      = $_POST['postcode'];
            $data['init_country']       = $_POST['country'];
            $data['init_tel_no']        = $_POST['tel_no'];
            $data['init_tel_no2']       = $_POST['tel_no2'];
            $data['init_tel_no3']       = $_POST['tel_no3'];
            $data['init_fax_no']        = $_POST['fax_no'];
            $data['init_fax_no2']       = $_POST['fax_no2'];
            $data['init_email']         = $_POST['email'];
            $data['init_contact_person']= $_POST['contact_person'];
            $data['init_website']       = $_POST['website'];
            $data['init_beds']          = $_POST['beds'];
            $data['init_remarks']       = $_POST['remarks'];
            $data['init_pcdom_ref']     = $_POST['pcdom_ref'];
            $data['init_thirra_url']    = $_POST['thirra_url'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['referral_center_id']= $this->uri->segment(4);
            if ($data['form_purpose'] == "new_centre") {
                $data['init_centre_name']   =   "";
                $data['init_centre_type']   =   "";
                $data['init_address']       =   "";
                $data['init_address2']      =   "";
                $data['init_address3']      =   "";
                $data['init_town']          =   "";
                $data['init_state']         =   "";
                $data['init_postcode']      =   "";
                $data['init_country']       =   "";
                $data['init_tel_no']        =   "";
                $data['init_tel_no2']       =   "";
                $data['init_tel_no3']       =   "";
                $data['init_fax_no']        =   "";
                $data['init_fax_no2']       =   "";
                $data['init_email']         =   "";
                $data['init_contact_person']=   "";
                $data['init_other']         =   "";
                $data['init_partner_clinic_id']= "";
                $data['partner_clinic_id']= "";
                $data['init_website']       =   "";
                $data['init_beds']          =   0;
                $data['init_remarks']       =   "";
                $data['init_pcdom_ref']     =   "";
                $data['init_thirra_url']    =   "";
            } elseif ($data['form_purpose'] == "edit_centre") {
                //echo "Edit diagnosis";
                $data['centre_info'] = $this->madmin_rdb->get_referral_centres($data['referral_center_id']);
                $data['init_centre_name']   = $data['centre_info'][0]['name'];
                $data['init_centre_type']   = $data['centre_info'][0]['centre_type'];
                $data['init_address']       = $data['centre_info'][0]['address'];
                $data['init_address2']      = $data['centre_info'][0]['address2'];
                $data['init_address3']      = $data['centre_info'][0]['address3'];
                $data['init_town']          = $data['centre_info'][0]['town'];
                $data['init_state']         = $data['centre_info'][0]['state'];
                $data['init_postcode']      = $data['centre_info'][0]['postcode'];
                $data['init_country']       = $data['centre_info'][0]['country'];
                $data['init_tel_no']        = $data['centre_info'][0]['tel_no'];
                $data['init_tel_no2']       = $data['centre_info'][0]['tel_no2'];
                $data['init_tel_no3']       = $data['centre_info'][0]['tel_no3'];
                $data['init_fax_no']        = $data['centre_info'][0]['fax_no'];
                $data['init_fax_no2']       = $data['centre_info'][0]['fax_no2'];
                $data['init_email']         = $data['centre_info'][0]['email'];
                $data['init_contact_person']= $data['centre_info'][0]['contact_person'];
                $data['init_other']         = $data['centre_info'][0]['other'];
                $data['init_partner_clinic_id']= $data['centre_info'][0]['other'];
                $data['partner_clinic_id']= $data['centre_info'][0]['other'];
                $data['init_website']       = $data['centre_info'][0]['website'];
                $data['init_beds']          = $data['centre_info'][0]['beds'];
                $data['init_remarks']       = $data['centre_info'][0]['remarks'];
                $data['init_pcdom_ref']     = $data['centre_info'][0]['pcdom_ref'];
                $data['init_thirra_url']    = $data['centre_info'][0]['thirra_url'];
            } //endif ($data['form_purpose'] == "new_centre")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Referral Centre";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
		$data['clinics_list'] = $this->mthirra->get_clinics_list('All');
        if(isset($data['partner_clinic_id'])){
		    $data['partner_info'] = $this->mthirra->get_clinic_info($data['partner_clinic_id']);
        } else {
            $data['partner_info'] = array();
        }
        if(isset($data['referral_center_id'])){
            $data['persons_list'] = $this->madmin_rdb->get_referral_persons($data['referral_center_id']);
        } else {
            $data['persons_list'] = array();
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_referral_centre') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
                //$new_body   =   "ehr/ehr_admin_edit_referral_centre_wap";
                $new_body   =   "ehr/ehr_admin_edit_referral_centre_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_admin_edit_referral_centre_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_admin'] < 100){
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
            if($data['form_purpose'] == "new_centre") {
                // New diagnosis record
                $ins_centre_array   =   array();
                $ins_centre_array['staff_id']       = $_SESSION['staff_id'];
                $ins_centre_array['now_id']         = $data['now_id'];
                $ins_centre_array['referral_center_id']= $data['now_id'];
                $ins_centre_array['centre_name']    = $data['init_centre_name'];
                $ins_centre_array['centre_type']    = $data['init_centre_type'];
                $ins_centre_array['address']        = $data['init_address'];
                $ins_centre_array['address2']       = $data['init_address2'];
                $ins_centre_array['address3']       = $data['init_address3'];
                $ins_centre_array['town']           = $data['init_town'];
                $ins_centre_array['state']          = $data['init_state'];
                $ins_centre_array['postcode']       = $data['init_postcode'];
                $ins_centre_array['country']        = $data['init_country'];
                $ins_centre_array['tel_no']         = $data['init_tel_no'];
                $ins_centre_array['tel_no2']        = $data['init_tel_no2'];
                $ins_centre_array['tel_no3']        = $data['init_tel_no3'];
                $ins_centre_array['fax_no']         = $data['init_fax_no'];
                $ins_centre_array['fax_no2']        = $data['init_fax_no2'];
                $ins_centre_array['email']          = $data['init_email'];
                $ins_centre_array['contact_person'] = $data['init_contact_person'];
                $ins_centre_array['other']          = $data['partner_clinic_id'];
                $ins_centre_array['website']        = $data['init_website'];
                if(is_numeric($data['init_beds'])){
                    $ins_centre_array['beds']                = $data['init_beds'];
                }
                //$ins_centre_array['beds']           = $data['init_beds'];
                $ins_centre_array['remarks']        = $data['init_remarks'];
                $ins_centre_array['pcdom_ref']      = $data['init_pcdom_ref'];
                $ins_centre_array['thirra_url']     = $data['init_thirra_url'];
	            $ins_centre_data       =   $this->madmin_wdb->insert_new_referral_centre($ins_centre_array);
                $this->session->set_flashdata('data_activity', 'Referral centre added.');
            } elseif($data['form_purpose'] == "edit_centre") {
                // Existing diagnosis record
                $upd_centre_array   =   array();
                $upd_centre_array['staff_id']       = $_SESSION['staff_id'];
                $upd_centre_array['referral_center_id']= $data['referral_center_id'];
                $upd_centre_array['centre_name']    = $data['init_centre_name'];
                $upd_centre_array['centre_type']    = $data['init_centre_type'];
                $upd_centre_array['address']        = $data['init_address'];
                $upd_centre_array['address2']       = $data['init_address2'];
                $upd_centre_array['address3']       = $data['init_address3'];
                $upd_centre_array['town']           = $data['init_town'];
                $upd_centre_array['state']          = $data['init_state'];
                $upd_centre_array['postcode']       = $data['init_postcode'];
                $upd_centre_array['country']        = $data['init_country'];
                $upd_centre_array['tel_no']         = $data['init_tel_no'];
                $upd_centre_array['tel_no2']        = $data['init_tel_no2'];
                $upd_centre_array['tel_no3']        = $data['init_tel_no3'];
                $upd_centre_array['fax_no']         = $data['init_fax_no'];
                $upd_centre_array['fax_no2']        = $data['init_fax_no2'];
                $upd_centre_array['email']          = $data['init_email'];
                $upd_centre_array['contact_person'] = $data['init_contact_person'];
                $upd_centre_array['other']          = $data['partner_clinic_id'];
                $upd_centre_array['website']        = $data['init_website'];
                if(is_numeric($data['init_beds'])){
                    $upd_centre_array['beds']                = $data['init_beds'];
                }
                $upd_centre_array['remarks']        = $data['init_remarks'];
                $upd_centre_array['pcdom_ref']      = $data['init_pcdom_ref'];
                $upd_centre_array['thirra_url']     = $data['init_thirra_url'];
	            $upd_centre_data       =   $this->madmin_wdb->update_referral_centre($upd_centre_array);
                $this->session->set_flashdata('data_activity', 'Referral centre updated.');
            } //endif($data['diagnosis_id'] == "new_imaging")
            $new_page = base_url()."index.php/ehr_admin/admin_list_referral_centres";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_referral_centre') == FALSE)


    } // end of function admin_edit_referral_centre()


    // ------------------------------------------------------------------------
    // admin_edit_referral_person
    function admin_edit_referral_person()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_admin/admin_mgt','Admin','ehr_admin/admin_list_referral_centres','List Referral Centres');    
	  	//$this->load->model('mthirra');
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            if(isset($_POST['partner_clinic_id'])) { 
                $data['partner_clinic_id']   =   $_POST['partner_clinic_id'];
            }
            $data['init_referral_doctor_id']= $_POST['referral_doctor_id'];
            $data['referral_doctor_id'] = $_POST['referral_doctor_id'];
            $data['init_referral_center_id']= $_POST['referral_center_id'];
            $data['referral_center_id'] = $_POST['referral_center_id'];
            $data['init_doctor_name']   = $_POST['doctor_name'];
            $data['init_specialty']   = $_POST['specialty'];
            $data['init_tel_no']        = $_POST['tel_no'];
            $data['init_tel_no2']       = $_POST['tel_no2'];
            $data['init_fax_no']        = $_POST['fax_no'];
            $data['init_email']         = $_POST['email'];
            $data['init_remarks']       = $_POST['remarks'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['referral_center_id']= $this->uri->segment(4);
            $data['referral_doctor_id']= $this->uri->segment(5);
            $data['partner_clinic_id']= $this->uri->segment(6);
            $data['partner_clinic_id']= (string)$data['partner_clinic_id'];
            if ($data['form_purpose'] == "new_person") {
                $data['init_referral_center_id']   =   "";
                $data['init_doctor_name']   =   "";
                $data['init_specialty']       =   "";
                $data['init_tel_no']        =   "";
                $data['init_tel_no2']       =   "";
                $data['init_fax_no']        =   "";
                $data['init_email']         =   "";
                $data['init_other']         =   "";
                $data['init_partner_staff_id']= "";
                $data['partner_staff_id']= "";
                $data['init_remarks']       =   "";
            } elseif ($data['form_purpose'] == "edit_person") {
                //echo "Edit diagnosis";
                $data['person_info'] = $this->madmin_rdb->get_referral_persons($data['referral_center_id'],$data['referral_doctor_id']);
                $data['init_doctor_name']   = $data['person_info'][0]['doctor_name'];
                $data['init_specialty']       = $data['person_info'][0]['specialty'];
                $data['init_tel_no']        = $data['person_info'][0]['tel_no'];
                $data['init_tel_no2']       = $data['person_info'][0]['tel_no2'];
                $data['init_fax_no']        = $data['person_info'][0]['fax_no'];
                $data['init_email']         = $data['person_info'][0]['email'];
                $data['init_other']         = $data['person_info'][0]['other'];
                $data['init_partner_staff_id']= $data['person_info'][0]['other'];
                $data['partner_staff_id']= $data['person_info'][0]['other'];
                $data['init_remarks']       = $data['person_info'][0]['remarks'];
            } //endif ($data['form_purpose'] == "new_imaging")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Referral Person";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        
        if(isset($data['partner_clinic_id'])){
		    $data['partner_info'] = $this->mthirra->get_clinic_info($data['partner_clinic_id']);
		    $data['staff_list'] = $this->madmin_rdb->get_staff_list($data['partner_clinic_id']);
        } else {
            $data['partner_info'] = array();
        }
        
        if(isset($data['referral_center_id'])){
            $data['persons_list'] = $this->madmin_rdb->get_referral_persons($data['referral_center_id']);
        } else {
            $data['persons_list'] = array();
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_referral_person') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
                //$new_body   =   "ehr/emr_admin_edit_referral_person_wap";
                $new_body   =   "ehr/ehr_admin_edit_referral_person_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_admin_edit_referral_person_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_admin'] < 100){
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
            if($data['form_purpose'] == "new_person") {
                // New diagnosis record
                $ins_centre_array   =   array();
                $ins_centre_array['staff_id']       = $_SESSION['staff_id'];
                $ins_centre_array['now_id']         = $data['now_id'];
                $ins_centre_array['referral_doctor_id']= $data['now_id'];
                $ins_centre_array['referral_center_id']= $data['init_referral_center_id'];
                $ins_centre_array['doctor_name']    = $data['init_doctor_name'];
                $ins_centre_array['specialty']    = $data['init_specialty'];
                $ins_centre_array['tel_no']         = $data['init_tel_no'];
                $ins_centre_array['tel_no2']        = $data['init_tel_no2'];
                $ins_centre_array['fax_no']         = $data['init_fax_no'];
                $ins_centre_array['email']          = $data['init_email'];
                $ins_centre_array['other']          = $data['partner_clinic_id'];
                $ins_centre_array['remarks']        = $data['init_remarks'];
                $ins_centre_array['doctor_active']  = "TRUE"; //$data['init_rdoctor_active'];
	            $ins_centre_data       =   $this->madmin_wdb->insert_new_referral_person($ins_centre_array);
                $this->session->set_flashdata('data_activity', 'Referral person added.');
            } elseif($data['form_purpose'] == "edit_person") {
                // Existing diagnosis record
                $upd_centre_array   =   array();
                $upd_centre_array['staff_id']       = $_SESSION['staff_id'];
                $upd_centre_array['referral_doctor_id']= $data['referral_doctor_id'];
                $upd_centre_array['referral_center_id']= $data['referral_center_id'];
                $upd_centre_array['doctor_name']        = $data['init_doctor_name'];
                $upd_centre_array['specialty']    = $data['init_specialty'];
                $upd_centre_array['tel_no']         = $data['init_tel_no'];
                $upd_centre_array['tel_no2']        = $data['init_tel_no2'];
                $upd_centre_array['fax_no']         = $data['init_fax_no'];
                $upd_centre_array['email']          = $data['init_email'];
                $upd_centre_array['other']          = $data['partner_clinic_id'];
                $upd_centre_array['remarks']        = $data['init_remarks'];
                $upd_centre_array['doctor_active']  = "TRUE"; //$data['init_doctor_active'];
	            $upd_centre_data       =   $this->madmin_wdb->update_referral_doctor($upd_centre_array);
                $this->session->set_flashdata('data_activity', 'Referral person updated.');
            } //endif($data['diagnosis_id'] == "new_imaging")
            $new_page = base_url()."index.php/ehr_admin/admin_edit_referral_centre/edit_centre/".$data['referral_center_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_referral_centre') == FALSE)


    } // end of function admin_edit_referral_person()


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
    function get_user_rights($rights){
        $data = array();
        $data['section_patients']   =   0;  
        $data['section_pharmacy']   =   0;  
        $data['section_orders']     =   0;  
        $data['section_queue']      =   0;  
        $data['section_reports']    =   0;  
        $data['section_utilities']  =   0;  
        $data['section_admin']      =   0;  
        //$data['permission']     =   261;
        $data['binary']             =   decbin($rights);
        if(strlen($data['binary']) > 9){
            $data['place10']                   =   substr($data['binary'],-10,1);
            if($data['place10'] == "1"){
                //$data['section_admin']      =   100;  
            }
        }        
        if(strlen($data['binary']) > 8){
            $data['place9']                   =   substr($data['binary'],-9,1);
            if($data['place9'] == "1"){
                $data['section_utilities']      =   100;  
            }
        }
        if(strlen($data['binary']) > 7){
            $data['place8']                   =   substr($data['binary'],-8,1);
            if($data['place8'] == "1"){
                $data['section_billing']      =   100;  
            }
        }
        if(strlen($data['binary']) > 6){
            $data['place7']                   =   substr($data['binary'],-7,1);
            if($data['place7'] == "1"){
                $data['section_finance']      =   100;  
            }
        }
        if(strlen($data['binary']) > 5){
            $data['place6']                   =   substr($data['binary'],-6,1);
            if($data['place6'] == "1"){
                $data['section_queue']      =   100;  
            }
        }
        if(strlen($data['binary']) > 4){
            $data['place5']                   =   substr($data['binary'],-5,1);
            if($data['place5'] == "1"){
                $data['section_patients']      =   100;  
            }
        }
        if(strlen($data['binary']) > 3){
            $data['place4']                   =   substr($data['binary'],-4,1);
            if($data['place4'] == "1"){
                $data['section_orders']      =   100;  
            }
        }
        if(strlen($data['binary']) > 2){
            $data['place3']                   =   substr($data['binary'],-3,1);
            if($data['place3'] == "1"){
                $data['section_pharmacy']      =   100;  
            }
        }
        if(strlen($data['binary']) > 1){
            $data['place2']                   =   substr($data['binary'],-2,1);
            if($data['place2'] == "1"){
                $data['section_reports']      =   100;  
            }
        }
        if(strlen($data['binary']) > 0){
            $data['place1']                   =   substr($data['binary'],-1,1);
            if($data['place1'] == "1"){
                $data['section_admin']      =   100;  
            }
        }
        return $data;    
    } //end of function get_user_rights($rights)


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
            $new_sidebar=   "ehr/sidebar_ehr_admin_wap";
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
        if($data['user_rights']['section_admin'] < 100){
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
