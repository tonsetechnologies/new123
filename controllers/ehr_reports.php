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
 * Controller Class for EHR_REPORTS
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_reports extends MY_Controller 
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
        $this->lang->load('ehr', 'english');
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->load->model('memr_rdb');
		$this->load->model('mthirra');
		$this->load->model('mreport');
        
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
    // === REPORTS MANAGEMENT
    // ------------------------------------------------------------------------
    function reports_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_reports/reports_mgt','Reports');    
		$data['title'] = "T H I R R A - Reports Management";
		$data['current_db']		= $this->db->database; 		
        $data['clinic_reports_list'] = $this->mreport->get_reports_list();

		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_reports_wap";
            //$new_body   =   "ehr/ehr_reports_mgt_wap";
            $new_body   =   "ehr/ehr_reports_mgt_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_reports_html";
            $new_body   =   "ehr/ehr_reports_mgt_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_reports'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function reports_mgt($id)


    // ------------------------------------------------------------------------
    function reports_edit_reporthead($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_reports/reports_mgt','Reports');    
		$data['form_purpose']       =   $this->uri->segment(3);
        $data['report_header_id']   =   $this->uri->segment(4);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Add New / Edit Report Template";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        
        if(count($_POST)) {
            // User has posted the form
            $data['report_header_id']      			=   $this->input->post('report_header_id');
            $data['init_report_code']      			=   $this->input->post('report_code');
            $data['init_report_name']      			=   $this->input->post('report_name');
            $data['init_report_shortname']      	=   $this->input->post('report_shortname');
            $data['init_report_title1']      		=   $this->input->post('report_title1');
            $data['init_report_title2']      		=   $this->input->post('report_title2');
            $data['init_report_descr']      		=   $this->input->post('report_descr');
            $data['init_report_source']      		=   $this->input->post('report_source');
            $data['init_report_type']      			=   $this->input->post('report_type');
            $data['init_report_section']      		=   $this->input->post('report_section');
            $data['init_report_scope']      		=   $this->input->post('report_scope');
            $data['init_report_version']      		=   $this->input->post('report_version');
            $data['init_report_paper_orient']      	=   $this->input->post('report_paper_orient');
            $data['init_report_paper_size']      	=   $this->input->post('report_paper_size');
            $data['init_report_sort']      			=   $this->input->post('report_sort');
            $data['init_report_db_sort']      		=   $this->input->post('report_db_sort');
            $data['init_report_db_groupby']      	=   $this->input->post('report_db_groupby');
            $data['init_report_db_having']      	=   $this->input->post('report_db_having');
            $data['init_report_filter_sex']      	=   $this->input->post('report_filter_sex');
            $data['init_report_filter_youngerthan'] =   $this->input->post('report_filter_youngerthan');
            $data['init_report_filter_olderthan']   =   $this->input->post('report_filter_olderthan');
            $data['init_report_filter_1']      		=   $this->input->post('report_filter_1');
            $data['init_report_filter_2']      		=   $this->input->post('report_filter_2');
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_report") {
                // New user
		        $data['report_body']          =  array();
                $data['init_report_code']     =   "";
                $data['init_report_name']     =   "";
                $data['init_report_shortname']=   "";
                $data['init_report_title1']   =   "";
                $data['init_report_title2']   =   "";
                $data['init_report_descr']    =   "";
                $data['init_report_source']   =   "";
                $data['init_report_type']     =   "User Created";
                $data['init_report_section']  =   "Clinical";
                $data['init_report_scope']    =   "Server";
                $data['init_report_version']  =   "1.0";
                $data['init_report_paper_orient'] =   "Landscape";
                $data['init_report_paper_size'] =   "A4";
                $data['init_report_sort']     =   0;
                $data['init_report_db_sort']  =   "";
                $data['init_report_db_groupby'] =   "";
                $data['init_report_db_having']  =   "";
                $data['init_report_filter_sex'] =   "B";
                $data['init_report_filter_youngerthan']     =   "110";
                $data['init_report_filter_olderthan']     =   "0";
                $data['init_report_filter_1'] =   "";
                $data['init_report_filter_2'] =   "";
            } else {
                // Existing user
                $data['report_head']  = $this->mreport->get_report_header($data['report_header_id']);
                $data['report_source']          =    $data['report_head']['report_source'];
                $data['report_body']            = $this->mreport->get_report_body($data['report_header_id']);
                $data['init_report_code']       =   $data['report_head']['report_code'];
                $data['init_report_name']       =   $data['report_head']['report_name'];
                $data['init_report_shortname']  =   $data['report_head']['report_shortname'];
                $data['init_report_title1']     =   $data['report_head']['report_title1'];
                $data['init_report_title2']     =   $data['report_head']['report_title2'];
                $data['init_report_descr']      =   $data['report_head']['report_descr'];
                $data['init_report_source']     =   $data['report_head']['report_source'];
                $data['init_report_type']       =   $data['report_head']['report_type'];
                $data['init_report_section']    =   $data['report_head']['report_section'];
                $data['init_report_scope']      =   $data['report_head']['report_scope'];
                $data['init_report_version']    =   $data['report_head']['report_version'];
                $data['init_report_paper_orient'] =   $data['report_head']['report_paper_orient'];
                $data['init_report_paper_size'] =   $data['report_head']['report_paper_size'];
                $data['init_report_sort']       =   $data['report_head']['report_sort'];
                $data['init_report_db_sort']    =   $data['report_head']['report_db_sort'];
                $data['init_report_db_groupby'] =   $data['report_head']['report_db_groupby'];
                $data['init_report_db_having']  =   $data['report_head']['report_db_having'];
                $data['init_report_filter_sex'] =   $data['report_head']['report_filter_sex'];
                $data['init_report_filter_youngerthan']=   $data['report_head']['report_filter_youngerthan'];
                $data['init_report_filter_olderthan'] =   $data['report_head']['report_filter_olderthan'];
                $data['init_report_filter_1']   =   $data['report_head']['report_filter_1'];
                $data['init_report_filter_2']   =   $data['report_head']['report_filter_2'];
            } //endif ($data['form_purpose'] == "new_report")
        } //endif(count($_POST))
        
        $data['reports_scope']  = $this->mreport->get_reports_scope();
        $data['reports_sources']  = $this->mreport->get_reports_sources();
        
		$this->load->vars($data);
        
        // Run validation
		if ($this->form_validation->run('edit_report_header') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_reports_wap";
                //$new_body   =   "ehr/ehr_reports_edit_reporthead_wap";
                $new_body   =   "ehr/ehr_reports_edit_reporthead_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_reports_html";
                $new_body   =   "ehr/ehr_reports_edit_reporthead_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_reports'] < 100){
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
            if($data['form_purpose'] == "new_report") {
                // Insert records
                $ins_header_array['report_header_id']   = $data['now_id'];
                $ins_header_array['report_code']        = $data['init_report_code'];
                $ins_header_array['report_name']        = $data['init_report_name'];
                $ins_header_array['report_shortname']   = $data['init_report_shortname'];
                $ins_header_array['report_title1']      = $data['init_report_title1'];
                $ins_header_array['report_title2']      = $data['init_report_title2'];
                $ins_header_array['report_descr']       = $data['init_report_descr'];
                $ins_header_array['report_source']      = $data['init_report_source'];
                $ins_header_array['report_type']        = $data['init_report_type'];
                $ins_header_array['report_section']     = $data['init_report_section'];
                $ins_header_array['report_scope']       = $data['init_report_scope'];
                $ins_header_array['report_version']     = $data['init_report_version'];
                $ins_header_array['report_paper_orient']= $data['init_report_paper_orient'];
                $ins_header_array['report_paper_size']  = $data['init_report_paper_size'];
                //$ins_header_array['report_latest']  = $data['init_report_latest'];
                $ins_header_array['report_sort']        = $data['init_report_sort'];
                if(empty($data['init_report_db_sort'])){
                    $ins_header_array['report_db_sort']     = NULL;
                } else {
                    $ins_header_array['report_db_sort']     = $data['init_report_db_sort'];
                }
                //$ins_header_array['report_db_sort']     = $data['init_report_db_sort'];
                $ins_header_array['report_db_groupby']  = $data['init_report_db_groupby'];
                $ins_header_array['report_db_having']   = $data['init_report_db_having'];
                $ins_header_array['report_filter_sex']  = $data['init_report_filter_sex'];
                if(is_numeric($data['init_report_filter_youngerthan'])){
                    $ins_header_array['report_filter_youngerthan']= $data['init_report_filter_youngerthan'];
                }
                //$ins_header_array['report_filter_youngerthan']= $data['init_report_filter_youngerthan'];
                if(is_numeric($data['init_report_filter_olderthan'])){
                    $ins_header_array['report_filter_olderthan'] = $data['init_report_filter_olderthan'];
                }
                //$ins_header_array['report_filter_olderthan'] = $data['init_report_filter_olderthan'];
                $ins_header_array['report_filter_1']    = $data['init_report_filter_1'];
                $ins_header_array['report_filter_2']    = $data['init_report_filter_2'];
                $ins_header_data =   $this->mreport->insert_new_report_header($ins_header_array);
                $this->session->set_flashdata('data_activity', 'New report added.');
            } else {
                // Update records
                $upd_header_array['report_header_id']   = $data['report_header_id'];
                $upd_header_array['report_code']        = $data['init_report_code'];
                $upd_header_array['report_name']        = $data['init_report_name'];
                $upd_header_array['report_shortname']   = $data['init_report_shortname'];
                $upd_header_array['report_title1']      = $data['init_report_title1'];
                $upd_header_array['report_title2']      = $data['init_report_title2'];
                $upd_header_array['report_descr']       = $data['init_report_descr'];
                $upd_header_array['report_source']      = $data['init_report_source'];
                $upd_header_array['report_type']        = $data['init_report_type'];
                $upd_header_array['report_section']     = $data['init_report_section'];
                $upd_header_array['report_scope']       = $data['init_report_scope'];
                $upd_header_array['report_version']     = $data['init_report_version'];
                //$upd_header_array['report_latest']  = $data['init_report_latest'];
                $upd_header_array['report_sort']        = $data['init_report_sort'];
                $upd_header_array['report_paper_orient']= $data['init_report_paper_orient'];
                $upd_header_array['report_paper_size']  = $data['init_report_paper_size'];
                $upd_header_array['report_db_sort']     = $data['init_report_db_sort'];
                $upd_header_array['report_db_groupby']  = $data['init_report_db_groupby'];
                $upd_header_array['report_db_having']   = $data['init_report_db_having'];
                $upd_header_array['report_filter_sex']  = $data['init_report_filter_sex'];
                if(is_numeric($data['init_report_filter_youngerthan'])){
                    $upd_header_array['report_filter_youngerthan']= $data['init_report_filter_youngerthan'];
                }
                //$upd_header_array['report_filter_youngerthan']= $data['init_report_filter_youngerthan'];
                if(is_numeric($data['init_report_filter_olderthan'])){
                    $upd_header_array['report_filter_olderthan']= $data['init_report_filter_olderthan'];
                }
                //$upd_header_array['report_filter_olderthan']= $data['init_report_filter_olderthan'];
                $upd_header_array['report_filter_1']    = $data['init_report_filter_1'];
                $upd_header_array['report_filter_2']    = $data['init_report_filter_2'];
                $upd_header_data =   $this->mreport->update_report_header($upd_header_array);
                $this->session->set_flashdata('data_activity', 'Report updated.');
            } //endif($data['form_purpose'] == "new_room")
            $new_page = base_url()."index.php/ehr_reports/reports_mgt";
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_report') == FALSE)
        
    } // end of function report_edit_report($id)


    // ------------------------------------------------------------------------
    function reports_edit_reportbody($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['form_purpose']       =   $this->uri->segment(3);
        $data['report_header_id']   =   $this->uri->segment(4);
        $data['report_body_id']     =   $this->uri->segment(5);
        $crumb_url                  =   'ehr_reports/reports_edit_reporthead/'.$data['form_purpose'].'/'.$data['report_header_id'];
        $data['breadcrumbs']        =   breadcrumbs('ehr_reports/reports_mgt','Reports',$crumb_url,'Edit Report Header');    
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Add New / Edit Report Template";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['report_head']  = $this->mreport->get_report_header($data['report_header_id']);
        
        if(count($_POST)) {
            // User has posted the form
            //$data['report_header_id']      			=   $this->input->post('report_header_id');
            //$data['init_report_line']      			=   $this->input->post('report_line');
            $data['init_col_fieldname']      			=   $this->input->post('col_fieldname');
            //$data['init_col_security']      			=   $this->input->post('col_security');
            $data['init_col_sort']      			=   $this->input->post('col_sort');
            $data['init_col_title1']      			=   $this->input->post('col_title1');
            $data['init_col_title2']      			=   $this->input->post('col_title2');
            $data['init_col_format']      			=   $this->input->post('col_format');
            $data['init_col_transform']      			=   $this->input->post('col_transform');
            $data['init_col_widthmin']      			=   $this->input->post('col_widthmin');
            $data['init_col_widthmax']      			=   $this->input->post('col_widthmax');
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_body") {
                // New user
		        $data['report_body']        =  array();
                $data['init_report_line']   =   "";
                $data['init_col_fieldname'] =   "";
                $data['init_col_security']  =   "";
                $data['init_col_sort']      =   "";
                $data['init_col_title1']    =   "";
                $data['init_col_title2']    =   "";
                $data['init_col_format']    =   "";
                $data['init_col_transform'] =   "";
                $data['init_col_widthmin']  =   "";
                $data['init_col_widthmax']  =   "";
            } else {
                // Existing user
                //$data['report_head']  = $this->mreport->get_report_header($data['report_header_id']);
                $data['report_source']      =    $data['report_head']['report_source'];
                $data['body_info']          = $this->mreport->get_report_body($data['report_header_id'], $data['report_body_id']);
                $data['init_report_line']   =   $data['body_info'][0]['report_line'];
                $data['init_col_fieldname'] =   $data['body_info'][0]['col_fieldname'];
                $data['init_col_security']  =   $data['body_info'][0]['col_security'];
                $data['init_col_sort']      =   $data['body_info'][0]['col_sort'];
                $data['init_col_title1']    =   $data['body_info'][0]['col_title1'];
                $data['init_col_title2']    =   $data['body_info'][0]['col_title2'];
                $data['init_col_format']    =   $data['body_info'][0]['col_format'];
                $data['init_col_transform'] =   $data['body_info'][0]['col_transform'];
                $data['init_col_widthmin']  =   $data['body_info'][0]['col_widthmin'];
                $data['init_col_widthmax']  =   $data['body_info'][0]['col_widthmax'];
            } //endif ($data['form_purpose'] == "new_body")
        } //endif(count($_POST))
        $data['report_body']        = $this->mreport->get_report_body($data['report_header_id']);
        $table_sql  =   "get_".$data['report_head']['report_source'];
        
        $temp_param =   array();
        $temp_param['period_from']       =   "1900-01-01";
        $temp_param['period_to']         =   "2020-12-31";
        $temp_param['clinic_info_id']    =   "All";
        $temp_param['patient_id']        =   "All";
        $fields_list  = $this->mreport->$table_sql('data','pdem.name',1,$temp_param);
        // Expecting at least one record from query.
        if(count($fields_list) > 0){
            $data['fields_list'] = $fields_list[1];
        } else {
            $data['fields_list'] = NULL;
        }
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_report_body') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_reports_wap";
                //$new_body   =   "ehr/ehr_reports_edit_reportbody_wap";
                $new_body   =   "ehr/ehr_reports_edit_reportbody_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_reports_html";
                $new_body   =   "ehr/ehr_reports_edit_reportbody_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_reports'] < 100){
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
            if($data['form_purpose'] == "new_body") {
                // Insert records
                $ins_body_array['report_body_id']   = $data['now_id'];
                $ins_body_array['report_header_id'] = $data['report_header_id'];
                $ins_body_array['location_id']  = $data['location_id'];
                //$ins_body_array['report_line']  = $data['init_report_line'];
                $ins_body_array['col_fieldname']= $data['init_col_fieldname'];
                //$ins_body_array['col_security'] = $data['init_col_security'];
                $ins_body_array['col_sort']     = $data['init_col_sort'];
                $ins_body_array['col_title1']   = $data['init_col_title1'];
                $ins_body_array['col_title2']   = $data['init_col_title2'];
                $ins_body_array['col_format']   = $data['init_col_format'];
                $ins_body_array['col_transform']= $data['init_col_transform'];
                if(is_numeric($data['init_col_widthmin'])){
                    $ins_body_array['col_widthmin']                = $data['init_col_widthmin'];
                }
                //$ins_body_array['col_widthmin'] = $data['init_col_widthmin'];
                if(is_numeric($data['init_col_widthmax'])){
                    $ins_body_array['col_widthmax']                = $data['init_col_widthmax'];
                }
                //$ins_body_array['col_widthmax'] = $data['init_col_widthmax'];
                $ins_body_data =   $this->mreport->insert_new_report_body($ins_body_array);
                $this->session->set_flashdata('data_activity', 'Report column added.');
            } else {
                // Update records
                $upd_body_array['report_body_id']      = $data['report_body_id'];
                //$upd_body_array['report_line']    = $data['init_report_line'];
                $upd_body_array['col_fieldname']    = $data['init_col_fieldname'];
                //$upd_body_array['col_security']    = $data['init_col_security'];
                $upd_body_array['col_sort']    = $data['init_col_sort'];
                $upd_body_array['col_title1']    = $data['init_col_title1'];
                $upd_body_array['col_title2']    = $data['init_col_title2'];
                $upd_body_array['col_format']    = $data['init_col_format'];
                $upd_body_array['col_transform']    = $data['init_col_transform'];
                if(is_numeric($data['init_col_widthmin'])){
                    $upd_body_array['col_widthmin']                = $data['init_col_widthmin'];
                }
                //$upd_body_array['col_widthmin']    = $data['init_col_widthmin'];
                if(is_numeric($data['init_col_widthmax'])){
                    $upd_body_array['col_widthmax']                = $data['init_col_widthmax'];
                }
                //$upd_body_array['col_widthmax']    = $data['init_col_widthmax'];
                $upd_body_data =   $this->mreport->update_report_body($upd_body_array);
                $this->session->set_flashdata('data_activity', 'Report column updated.');
            } //endif($data['form_purpose'] == "new_room")
            $new_page = base_url()."index.php/ehr_reports/reports_edit_reporthead/edit_report/".$data['report_header_id'];
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_report') == FALSE)
        
    } // end of function report_edit_reportbody($id)


    // ------------------------------------------------------------------------
    function reports_delete_reportbody($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['report_header_id']   =   $this->uri->segment(3);
        $data['report_body_id']     =   $this->uri->segment(4);
        
        // Delete records
        $del_body_array['report_body_id']      = $data['report_body_id'];
        $del_body_data =   $this->mreport->delete_report_body($del_body_array);
        $new_page = base_url()."index.php/ehr_reports/reports_edit_reporthead/edit_report/".$data['report_header_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function reports_delete_reportbody($id)


    // ------------------------------------------------------------------------
    function reports_select_report($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('madmin_rdb');
		$data['form_purpose']       = $this->uri->segment(3);
		$data['report_header_id']        = $this->uri->segment(4);
        $data['location_id']   =   $_SESSION['location_id'];
		$data['title'] = "Define Report Parameters";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['report_head']  = $this->mreport->get_report_header($data['report_header_id']);
        
        if(count($_POST)) {
            // User has posted the form
            $data['output_format']          =   $this->input->post('output_format');
            $data['period_from']            =   $this->input->post('period_from');
            $data['period_to']              =   $this->input->post('period_to');
            $data['comments']               =   $this->input->post('comments');
            $data['clinic_info_id']         =   $this->input->post('clinic_info_id');
            $data['patient_id']             =   $this->input->post('patient_id');
        } else {
            // First time form is displayed
            $data['param_info']         =  array();
            $data['output_format']      =   "html";
            $data['period_from']        =   "2007-01-01";
            $data['period_to']          =   $data['now_date'];
            $data['comments']           =   "";
            $data['clinic_info_id']     =   $data['location_id'];
            $data['patient_id']         =   "";
        } //endif(count($_POST))
        
        //if($data['report_head']['report_scope'] == "Patient"){
            $data['patients_list'] = $this->memr_rdb->get_patients_list('all','name');
        //} else {
            //$data['patient_id'] =   "All";
        //}
        
        if(($data['report_head']['report_scope'] == "Server") || ($data['report_head']['report_scope'] == "Clinic")){
            $data['clinics_list'] = $this->madmin_rdb->get_clinics_list('All');
        } else {
            $data['clinic_info_id'] =   "All";
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_report_param') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_reports_wap";
                //$new_body   =   "ehr/ehr_reports_select_report_wap";
                $new_body   =   "ehr/ehr_reports_select_report_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_reports_html";
                $new_body   =   "ehr/ehr_reports_select_report_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            $this->load->view($new_header);			
            //$this->load->view($new_banner);			
            //$this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            $new_page = base_url()."index.php/ehr_reports/print_report/".$data['output_format']."/".$data['report_header_id']."/".$data['period_from']."/".$data['period_to']."/".$data['clinic_info_id']."/".$data['patient_id'];
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_room') == FALSE)
        
    } // end of function report_select_report($id)


    // ------------------------------------------------------------------------
    function print_report($id=NULL)  // Print lab result to HTML or PDF
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	//$this->load->model('memr');
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['report_header_id']   = $this->uri->segment(4);
        $data['period_from']        = $this->uri->segment(5);
        $data['period_to']          = $this->uri->segment(6);
        $data['clinic_info_id']     = $this->uri->segment(7);
        $data['patient_id']         = $this->uri->segment(8);
        $data['print_param']    =   array();
        $data['print_param']['period_from']     =   $data['period_from'];
        $data['print_param']['period_to']       =   $data['period_to'];
        $data['print_param']['clinic_info_id']  =   $data['clinic_info_id'];
        $data['print_param']['patient_id']      =   $data['patient_id'];
        $data['report_head']  = $this->mreport->get_report_header($data['report_header_id']);
		$data['title']          = "THIRRA - ".$data['report_head']['report_shortname'];
        $data['report_source']  =    $data['report_head']['report_source'];
        $data['data_sort']      =    $data['report_head']['report_db_sort'];
        $data['report_body']  = $this->mreport->get_report_body($data['report_header_id']);
        //ta['report_data']  = $this->mreport->get_report_data($data['report_source']);
        //$table_sql  =   "get_patient_demo";
        //$table_sql  =   "get_patient_vital";
        $table_sql  =   "get_".$data['report_head']['report_source'];
        $data['report_data']  = $this->mreport->$table_sql('data',$data['data_sort'],NULL,$data['print_param']);
        //$data['report_data']  = $this->mreport->get_patient_vital();
        $count_sql  =   "getc_".$data['report_head']['report_source'];
        //$data['reportc_data']  = $this->mreport->$count_sql();
        $data['reportc_data']  = $this->mreport->$table_sql('col_cnt');
        //$data['reportc_data']  = $data['reportc_data']/2;
        $data['columns_count']= count($data['report_body']);
        //$data['columnsc_count']= count($data['reportc_data']);
        $data['columnsc_count']= $data['reportc_data'];
        $data['rows_count']   = count($data['report_data']);
        if($data['clinic_info_id'] <> "All"){
            $data['scope_clinic']    =   $this->mthirra->get_clinic_info($data['clinic_info_id']);
        } else {
            $data['scope_clinic']['clinic_name']   =   "All";
        }
        
        if($data['patient_id'] <> "All"){
            $data['scope_patient']    =   $this->memr_rdb->get_patient_details($data['patient_id']);
        } else {
            $data['scope_patient']['name']   =   "All";
        }
        
        //  Build data array
        $data['table_data'] = array();
        // Each row of raw data
        for($j=1; $j <= $data['rows_count']; $j++){
            //echo "<br /><br />data_row, j =".$j;
            // Each column of raw data
            for($k=0; $k < $data['columnsc_count']; $k++){
                //echo "<br />&nbsp;raw_col, k =".$k;
                // Each column of template
                for($l=0; $l < $data['columns_count']; $l++){
                    $col_check = $k;
                    //echo "<br />&nbsp;&nbsp;&nbsp;&nbsp;template_col, l =".$l;
                    //echo "<br />&nbsp;&nbsp;&nbsp;&nbsp;<strong>tpl_colname =".$data['report_body'][$l]['col_fieldname']."</strong>";
                    //echo "<br />&nbsp;&nbsp;&nbsp;&nbsp;raw_field =".$data['report_data'][$j][$col_check]['field'];
                
                    if(trim($data['report_body'][$l]['col_fieldname']) == trim($data['report_data'][$j][$col_check]['field'])){
                        //echo "<br />-- matched";
                        //echo "<br />-- raw_val=".$data['report_data'][$j][$col_check]['val'];
                        $data['table_data'][$j][$l] = $data['report_data'][$j][$col_check]['val'];
                    }
                }
           }
        }//endfor($i=0; $i < $rows_count; $i++)
        

		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_reports_wap";
            $new_body   =   "ehr/ehr_print_report_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_print_html";
            $new_sidebar=   "ehr/sidebar_emr_reports_html";
            $new_body   =   "ehr/ehr_print_report_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		
		// Output Format
		$data['output_format'] 	= $this->uri->segment(3);
        $export_filename    = "THIRRA-".$data['report_head']['report_code']."_".$data['report_head']['report_shortname']."_".$data['now_date']."_".$data['now_time'];
        //echo "export_filename =". $export_filename;
		$this->load->vars($data);
		if($data['output_format'] == 'pdf') {
            $pdf_filename           =   $export_filename.".pdf";
            $data['filename']		=	$pdf_filename;
            $page_orientation = $data['report_head']['report_paper_size'];//'A4';
            if($data['report_head']['report_paper_orient'] == "Landscape"){
                $page_orientation .= "-L"; //'A4-L';
            }
            echo $page_orientation;
			$html = $this->load->view($new_header,'',TRUE);			
			//$html .= $this->load->view($new_banner,'',TRUE);			
			//$this->load->view($new_sidebar);			
			$html .= $this->load->view($new_body,'',TRUE);			
			//$html .= $this->load->view($new_footer,'',TRUE);		

			$this->load->library('mpdf');
			//$mpdf=new mPDF('win-1252','A4','','',20,15,5,25,10,10);
			$mpdf=new mPDF('win-1252',$page_orientation,'','',20,15,5,25,10,10);
			$mpdf->useOnlyCoreFonts = true;    // false is default
			$mpdf->SetProtection(array('print'));
			$mpdf->SetTitle("THIRRA - ".$data['report_head']['report_shortname']);
			$mpdf->SetAuthor("THIRRA");
			//$mpdf->SetWatermarkText("Paid");
			//$mpdf->showWatermarkText = true;
			//$mpdf->watermark_font = 'DejaVuSansCondensed';
			//$mpdf->watermarkTextAlpha = 0.1;
			$mpdf->SetDisplayMode('fullpage');
			$mpdf->WriteHTML($html);

			$mpdf->Output($data['filename'],'I'); exit;
		} elseif($data['output_format'] == 'csv') { // export to CSV
            $this->load->helper('download');
            //$echoOut = $patientName."--".$birthDate."--".$newIcNo."--".$sex;
            //$echoOut .= "--".$address1."--".$address2."--".$address3."--".$postcode."--".$town."--".$state;
            //$echoOut .= "--".$drugAllergy;
            //$echoOut .= "--end";
            //fwrite(STDOUT,"$echoOut\n");
            //echo $echoOut;

            //$list[0] = $echoOut;

            $writeFilePath = "/var/www/thirra-uploads/";
            $dataFile      = "export-temp.csv";
            //echo "\nWriting to " . $writeFilePath . $dataFile;
            $fp = fopen($writeFilePath . $dataFile, 'w');
            
            // ------------------------------------------------------------------------
            function usort_array($a, $b)
            {
                if ($a['col_sort'] == $b['col_sort']) {
                    return 0;
                }
                return ($a['col_sort'] < $b['col_sort']) ? -1 : 1;
            }

            usort($data['report_body'], "usort_array");

            if($data['debug_mode']){
                echo "<pre>";
                print_r($data['report_body']);
                print_r($data['table_data']);
                echo "</pre>";
            }
            // Write header row
            fwrite($fp, '"'.$data['report_body'][0]['col_title1'].'"');
            for($i=1; $i < $data['columns_count']; $i++){
                fwrite($fp, ",");
                fwrite($fp, '"'.$data['report_body'][$i]['col_title1'].'"');
            }//endfor($i=0; $i < $columns_count; $i++)
            fwrite($fp, "\n");

            // Write data rows
            foreach ($data['table_data'] as $line) {
                ksort($line);
                fputcsv($fp, $line, ',', '"');
            }

            fclose($fp);
            $temp_file       = file_get_contents("/var/www/thirra-uploads/export-temp.csv"); // Read the file's contents
            $csv_filename    = $export_filename.".csv";
            force_download($csv_filename, $temp_file); 

		} else { // display in browser
			$this->load->view($new_header);			
			//$this->load->view($new_banner);			
			//$this->load->view($new_sidebar);			
			$this->load->view($new_body);			
			//$this->load->view($new_footer);		
		} //endif($data['output_format'] == 'pdf')
		
    } // end of function print_report($id)


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
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_reports_html";
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
