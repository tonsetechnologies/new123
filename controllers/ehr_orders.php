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
 * Controller Class for EHR_ORDERS
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_orders extends MY_Controller 
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
		$this->load->model('morders_rdb');
		$this->load->model('morders_wdb');
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
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        define("PICS_URL", $data['pics_url']);
    }


    // ------------------------------------------------------------------------
    // === ORDERS MANAGEMENT
    // ------------------------------------------------------------------------
    function orders_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders');    
        $data['location_id']    =   $_SESSION['location_id'];
		$data['title'] = "T H I R R A - Orders Management";
		$data['pending_labresults'] = $this->morders_rdb->get_list_labresult('Pending','data','sample_date',100,0,$data['location_id']);
		$data['pending_imaging'] = $this->morders_rdb->get_list_imaging_result("Pending");
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
            //$new_body   =   "ehr/ehr_orders_mgt_wap";
            $new_body   =   "ehr/ehr_orders_mgt_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_orders_mgt_html";
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
    } // end of function orders_mgt($id)


    // ------------------------------------------------------------------------
    // Lab Results Form
    function edit_labresults()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders');    
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['lab_package_id'])) { 
                $data['lab_package_id']   =   $_POST['lab_package_id'];
            }
            if(isset($_POST['supplier_id'])) { 
                $data['supplier_id']   =   $_POST['supplier_id'];
            } else {
                $data['supplier_id']   =   "none";
            }
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['patient_id']     = $_POST['patient_id'];
            $data['summary_id']     = $_POST['summary_id'];
            $data['lab_order_id']   = $_POST['lab_order_id'];
            $data['order_info'] = $this->memr_rdb->get_patcon_lab($data['summary_id'],$data['lab_order_id']);
            $data['lab_package_id'] =   $data['order_info'][0]['lab_package_id'];
            $data['package_name']   =   $data['order_info'][0]['package_name'];
            $data['supplier_id']    =   $data['order_info'][0]['supplier_id'];
            $data['supplier_name']  =   $data['order_info'][0]['supplier_name'];
            $data['sample_required']=   $data['order_info'][0]['sample_required'];
            $data['sample_date']    =   $data['order_info'][0]['sample_date'];
            $data['sample_time']    =   $data['order_info'][0]['sample_time'];
            $data['fasting']        =   $data['order_info'][0]['fasting'];
            $data['urgency']        =   $data['order_info'][0]['urgency'];
            $data['sample_ref']     =   $data['order_info'][0]['sample_ref'];
            $data['remarks']        =   $data['order_info'][0]['remarks'];
            $data['summary_result'] = $_POST['summary_result'];
            $data['result_date']    = $_POST['result_date'];
            $data['result_ref']     =   $_POST['result_ref'];
            $data['num_of_tests']   = $_POST['num_of_tests'];
            $data['package_info']  = $this->memr_rdb->get_one_lab_results($data['lab_order_id']);
			if(count($data['package_info']) > 0){
				for($i=1; ($i <= $data['num_of_tests']); $i++){
					$varname_result =   "test_result_".$i;
					$varname_normal =   "test_normal_".$i;
					$varname_remark =   "test_remark_".$i;
					$data['package_info'][$i-1][$varname_result]	=	$_POST["test_result_".$i];
					$data['package_info'][$i-1][$varname_normal]	=	$_POST["test_normal_".$i];
					$data['package_info'][$i-1][$varname_remark]	=	$_POST["test_remark_".$i];
					//echo $data[$varname_result];
				} //end for($i=1; ($i <= $data['num_of_test']); $i++)
				$data['num_of_tests'] = count($data['package_info']);
			} //endif(count($data['package_info']) == $data['num_of_tests'])
            if(isset($_POST['close_order'])) { 
				$data['close_order']  			=   $_POST['close_order'];//TRUE;
			} else {
				$data['close_order']  			=   "FALSE";				
			}
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['patient_id']     = $this->uri->segment(4);
            $data['summary_id']     = $this->uri->segment(5);
            $data['lab_order_id']   = $this->uri->segment(6);
            $patient_id             =   $this->uri->segment(4);
            //$data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_labresults") {
                //echo "new_lab";
                $data['lab_package_id'] =   "none";
                $data['supplier_id']    =   "none";
                $data['sample_date']    =   $data['now_date'];
                $data['sample_time']    =   $data['now_time'];
                $data['fasting']        =   "";
                $data['urgency']        =   "";
                $data['sample_ref']     =   "";
                $data['summary_result'] =   "Pending";
                $data['remarks']        =   "";
                $data['lab_order_id']   = "new_lab";
				$data['package_info'][0]['sample_required'] = "N/A";
                $data['num_of_tests']       =   0;
                $data['result_ref']        =   "";
            } elseif ($data['form_purpose'] == "edit_labresults") {
                //echo "Edit diagnosis";
                $data['order_info'] = $this->memr_rdb->get_patcon_lab($data['summary_id'],$data['lab_order_id']);
                $data['lab_package_id'] =   $data['order_info'][0]['lab_package_id'];
                $data['package_name']   =   $data['order_info'][0]['package_name'];
                $data['supplier_id']    =   $data['order_info'][0]['supplier_id'];
                $data['supplier_name']  =   $data['order_info'][0]['supplier_name'];
                $data['sample_required']=   $data['order_info'][0]['sample_required'];
                $data['sample_date']    =   $data['order_info'][0]['sample_date'];
                $data['sample_time']    =   $data['order_info'][0]['sample_time'];
                $data['fasting']        =   $data['order_info'][0]['fasting'];
                $data['urgency']        =   $data['order_info'][0]['urgency'];
                $data['sample_ref']     =   $data['order_info'][0]['sample_ref'];
                $data['summary_result'] =   $data['order_info'][0]['summary_result'];
                $data['remarks']        =   $data['order_info'][0]['remarks'];
                $data['result_date']    =   $data['order_info'][0]['result_date'];
                $data['result_ref']     =   $data['order_info'][0]['result_ref'];
				$data['package_info']  = $this->memr_rdb->get_one_lab_results($data['lab_order_id']);
				$data['num_of_tests']   = count($data['package_info']);
				if($data['fasting'] == 1){
					$data['fasting'] 	=	"Yes";
				} else {
					$data['fasting'] 	=	"No";
				}
				if($data['urgency'] == 1){
					$data['urgency'] 	=	"Yes";
				} else {
					$data['urgency'] 	=	"No";
				}
            } //endif ($data['form_purpose'] == "new_lab")
        } //endif(count($_POST))
		$data['title'] = "Edit Lab Results";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_lab_result') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
                $new_body   =   "ehr/ehr_orders_edit_labresults_wap";
                $new_body   =   "ehr/ehr_orders_edit_labresults_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_orders_html";
                $new_body   =   "ehr/ehr_orders_edit_labresults_html";
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
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            // Existing lab order record
            $upd_lab_array['lab_order_id']    = $data['lab_order_id'];
            $upd_lab_array['summary_result']  = $data['summary_result'];
            $upd_lab_array['result_status']   = "Pending";
            if($data['summary_result'] <> "Pending") {
            //if($data['close_order'] == "TRUE") {
                //echo "Change status ".$data['close_order'];
                $upd_lab_array['result_status']  	= "Received";
            }
            $upd_lab_array['invoice_status']  = "Unknown";
            $upd_lab_array['recorded_timestamp']   = $data['now_id'];
            $upd_lab_array['reply_method']    = "THIRRA";
            //$ins_lab_array['invoice_detail_id']= $data['invoice_detail_id']; //N/A
            $upd_lab_array['result_date']     = $data['result_date'];
            $upd_lab_array['result_ref']     = $data['result_ref'];
            $upd_lab_data  =   $this->morders_wdb->update_lab_order($upd_lab_array);
            if($data['num_of_tests'] > 0){
                for($j=1; $j <= $data['num_of_tests']; $j++){
                    $varname_result =   "test_result_".$j;
                    $varname_normal =   "test_normal_".$j;
                    $varname_remark =   "test_remark_".$j;
                    $upd_test_array['lab_result_id']	=	$data['package_info'][$j-1]['lab_result_id'];
                    $upd_test_array['lab_order_id']		=	$upd_lab_array['lab_order_id'];
                    //$upd_test_array['sort_test']		=	$data['package_info'][$j-1]['sort_test'];
                    //$upd_test_array['lab_package_test_id']	=	$data['lab_package_id'];
                    $upd_test_array['result_date']		=	$data['result_date'];
                    $upd_test_array['date_recorded']	=	$data['now_date'];
                    $upd_test_array['result']		    =	$data['package_info'][$j-1][$varname_result];
                    //$upd_test_array['loinc_num']		=	$data['package_info'][$j-1]['loinc_num'];
                    $upd_test_array['normal_reading']	=	$data['package_info'][$j-1][$varname_normal];
                    $upd_test_array['staff_id']	        =	$_SESSION['staff_id'];
                    $upd_test_array['result_remarks']	=	$data['package_info'][$j-1][$varname_remark];
                    //$ins_test_array['abnormal_flag']		=	$data['package_info'][$j-1]['abnormal_flag'];
                    $upd_test_array['result_ref']       = $data['result_ref'];
                    $upd_test_array['recorded_timestamp'] = $data['now_id'];
                    if($data['offline_mode']){
                        $upd_test_array['synch_out']    = $data['now_id'];
                    }//endif($data['offline_mode'])
                    $upd_test_data  =   $this->morders_wdb->update_lab_result($upd_test_array);
                    $data['now_id']++;
                }
            }//endif($data['num_of_tests'] > 0)
            $this->session->set_flashdata('data_activity', 'Lab result for '.$data['patient_info']['name'].' updated.');
            $new_page = base_url()."index.php/ehr_orders/orders_mgt";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_lab_order') == FALSE)


    } // end of function edit_labresults()


    // ------------------------------------------------------------------------
    function orders_edit_imagresult($result_id) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders');    
		$data['form_purpose']       =   $this->uri->segment(3);
		$data['order_id']           =   $this->uri->segment(4);
        $data['location_id']    	=   $_SESSION['location_id'];
		$data['title'] 				=   "Record Imaging Result";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        
        if(count($_POST)) {
            // User has posted the form
            $data['order_id']        	=   $_POST['order_id'];
            $data['init_notes']     	=   $_POST['notes'];
            $data['init_result_date']   =   $_POST['result_date'];
            $data['init_result_remarks']=   $_POST['result_remarks'];
            $data['init_result_ref']    =   $_POST['result_ref'];
			// Static information
			$data['order_info'] = $this->memr_rdb->get_one_imaging_result($data['order_id']);
			$data['session_id']     	=   $data['order_info'][0]['session_id'];
			$data['name'] 				=   $data['order_info'][0]['name'];
			$data['birth_date'] 		=   $data['order_info'][0]['birth_date'];
			$data['supplier_name'] 		=   $data['order_info'][0]['supplier_name'];
			$data['product_id']   		=   $data['order_info'][0]['product_id'];
			$data['product_code']   	=   $data['order_info'][0]['product_code'];
			$data['description']   		=   $data['order_info'][0]['description'];
			$data['supplier_ref'] 		=   $data['order_info'][0]['supplier_ref'];
			$data['result_status']  	=   $data['order_info'][0]['result_status'];
			$data['remarks']  			=   $data['order_info'][0]['remarks'];
			$data['result_id']  		=   $data['order_info'][0]['result_id'];
			$data['result_remarks']  			=   $data['order_info'][0]['result_remarks'];
			$data['result_ref']  			=   $data['order_info'][0]['result_ref'];
            if(isset($_POST['close_order'])) { 
				$data['close_order']  			=   $_POST['close_order'];//TRUE;
			} else {
				$data['close_order']  			=   "FALSE";				
			}
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_result") {
                // New user
		        $data['room_info']          =  array();
                $data['room_id']            =   "";
                $data['category_id']        =   "";
                $data['init_room_name']     =   "";
                $data['init_description']   =   "";
            } else {
                // Existing result row
				$data['order_info'] = $this->memr_rdb->get_one_imaging_result($data['order_id']);
                $data['session_id']      	=   $data['order_info'][0]['session_id'];
                $data['name'] 				=   $data['order_info'][0]['name'];
                $data['birth_date'] 		=   $data['order_info'][0]['birth_date'];
                $data['supplier_name'] 		=   $data['order_info'][0]['supplier_name'];
                $data['product_id']   		=   $data['order_info'][0]['product_id'];
                $data['product_code']   	=   $data['order_info'][0]['product_code'];
                $data['description']   		=   $data['order_info'][0]['description'];
                $data['supplier_ref'] 		=   $data['order_info'][0]['supplier_ref'];
                $data['result_status']  	=   $data['order_info'][0]['result_status'];
                $data['remarks']  			=   $data['order_info'][0]['remarks'];
                $data['result_id']  		=   $data['order_info'][0]['result_id'];
                $data['init_result_date']   =   $data['order_info'][0]['result_date'];
                $data['init_notes']   		=   $data['order_info'][0]['notes'];
                $data['image_path']   		=   $data['order_info'][0]['image_path'];
                $data['result_staff_id']   	=   $data['order_info'][0]['staff_id'];
                $data['date_ended'] 		=   $data['order_info'][0]['date_ended'];
                $data['init_result_remarks']   		=   $data['order_info'][0]['result_remarks'];
                $data['init_result_ref']   		=   $data['order_info'][0]['result_ref'];
           } //endif ($data['form_purpose'] == "new_result")
        } //endif(count($_POST))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_imag_result') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
                //$new_body   =   "ehr/ehr_orders_edit_imagresult_wap";
                $new_body   =   "ehr/ehr_orders_edit_imagresult_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_orders_html";
                $new_body   =   "ehr/ehr_orders_edit_imagresult_html";
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
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo "<br />Insert record";
            if($data['close_order'] == "TRUE") {
                //echo "Change status ".$data['close_order'];
				$upd_order_array['order_id']  		= $data['order_id'];
				$upd_order_array['result_status']  	= "Received";
                if($data['offline_mode']){
                    $upd_order_array['synch_out']        = $data['now_id'];
                }
				$upd_order_data =   $this->morders_wdb->update_imaging_order($upd_order_array);
            }
			// Update records
			$upd_result_array['result_id']      = $data['result_id'];
			$upd_result_array['staff_id']       = $_SESSION['staff_id'];
			$upd_result_array['result_date']    = $data['init_result_date'];
			$upd_result_array['notes']  		= $data['init_notes'];
            if($data['offline_mode']){
                $upd_result_array['synch_out']        = $data['now_id'];
            }
			$upd_result_data =   $this->morders_wdb->update_imaging_result($upd_result_array);
            $new_page = base_url()."index.php/ehr_orders/orders_mgt";
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_imag_result') == FALSE)
    } // end of function orders_edit_imagresult($result_id)


    // ------------------------------------------------------------------------
    function orders_listclosed_labresults($id=NULL)  // List suppliers for labs
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders' );    
		//$data['supplier_type']   	= $this->uri->segment(3);
        
        $data['sort_order']   	    = $this->uri->segment(3);
        //$data['page_num']   	    = $this->uri->segment(4);
        $data['per_page']           = '25';
        $data['row_first']   	    = $this->uri->segment(4);//$data['page_num'] * $data['per_page'];
        if(!is_numeric($data['row_first'])){
             $data['row_first'] =   0;
        }
        
		$data['title'] = "T H I R R A - Closed Lab Results";
		$data['pending_labresults'] = $this->morders_rdb->get_list_labresult('Received','data',$data['sort_order'],$data['per_page'],$data['row_first']);
		//$data['complaints_list']  = $this->mutil_rdb->get_complaint_codes_list('data',$data['sort_order'],$data['per_page'],$data['row_first']);
		$data['count_fulllist']  = $this->morders_rdb->get_list_labresult('Received','count',$data['sort_order'],'ALL',0);
        
        $this->load->library('pagination');

        $config['base_url'] = base_url()."index.php/ehr_orders/orders_listclosed_labresults/".$data['sort_order']."/";
        $config['total_rows']   = $data['count_fulllist'];
        $config['per_page']     = $data['per_page'];
        $config['num_links']    = 10;
        $config['uri_segment']  = 4;
        $this->pagination->initialize($config);

        $this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
            //$new_body   =   "ehr/ehr_orders_listclosed_labresults_wap";
            $new_body   =   "ehr/ehr_orders_listclosed_labresults_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_orders_listclosed_labresults_html";
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
    } // end of function orders_listclosed_labresults($id)


    // ------------------------------------------------------------------------
    function orders_listclosed_imagresults($id=NULL)  // List suppliers for labs
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders' );    
		$data['supplier_type']   	= $this->uri->segment(3);
		$data['title'] = "T H I R R A - Closed Lab Results";
		$data['pending_imaging'] = $this->morders_rdb->get_list_imaging_result('Received');
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
            $new_body   =   "ehr/ehr_orders_listclosed_imagresults_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_orders_listclosed_imagresults_html";
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
    } // end of function orders_listclosed_imagresults($id)


    // ------------------------------------------------------------------------
    function print_lab_result($id=NULL)  // Print lab result to HTML or PDF
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	//$this->load->model('memr');
		$data['title'] = "T H I R R A - Lab Results";
		$data['now_id']             =   time();
        $data['patient_id']     = $this->uri->segment(4);
        $data['summary_id']     = $this->uri->segment(5);
        $data['lab_order_id']   = $this->uri->segment(6);
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
        $data['order_info'] = $this->memr_rdb->get_patcon_lab($data['summary_id'],$data['lab_order_id']);
        $data['package_info']  = $this->memr_rdb->get_one_lab_orderresult($data['lab_order_id']);
		
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
            //$new_body   =   "ehr/ehr_print_lab_result_html";
            $new_body   =   "ehr/ehr_print_lab_result_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_print_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_print_lab_result_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		
		// Output Format
		$data['output_format'] 	= $this->uri->segment(3);
		$data['filename']		=	"THIRRA-LabResult-".$data['summary_id'].".pdf";
		$this->load->vars($data);
		if($data['output_format'] == 'pdf') {
			$html = $this->load->view($new_header,'',TRUE);			
			//$html .= $this->load->view($new_banner,'',TRUE);			
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

			$mpdf->Output($data['filename'],'I'); exit;
		} else { // display in browser
			$this->load->view($new_header);			
			//$this->load->view($new_banner);			
			//$this->load->view($new_sidebar);			
			$this->load->view($new_body);			
			$this->load->view($new_footer);		
		} //endif($data['output_format'] == 'pdf')
		
    } // end of function print_lab_result($id)


    // ------------------------------------------------------------------------
    function print_imag_result($id=NULL)  // Print lab result to HTML or PDF
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	//$this->load->model('memr');
		$data['title'] = "T H I R R A - Print Imaging Results";
		$data['now_id']             =   time();
        $data['patient_id']         =   $this->uri->segment(4);
		$data['order_id']      	  =   $this->uri->segment(5);
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['order_info']     = $this->memr_rdb->get_one_imaging_result($data['order_id']);
		$data['summary_id']     =   $data['order_info'][0]['session_id'];
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
		
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
            $new_body   =   "ehr/ehr_print_imag_result_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_print_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_print_imag_result_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		
		// Output Format
		$data['output_format'] 	= $this->uri->segment(3);
		$data['filename']		=	"THIRRA-ImagingResult-".$data['summary_id'].".pdf";
		$this->load->vars($data);
		if($data['output_format'] == 'pdf') {
			$html = $this->load->view($new_header,'',TRUE);			
			//$html .= $this->load->view($new_banner,'',TRUE);			
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

			$mpdf->Output($data['filename'],'I'); exit;
		} else { // display in browser
			$this->load->view($new_header);			
			//$this->load->view($new_banner);			
			//$this->load->view($new_sidebar);			
			$this->load->view($new_body);			
			$this->load->view($new_footer);		
		} //endif($data['output_format'] == 'pdf')
		
    } // end of function print_imag_result($id)


    // ------------------------------------------------------------------------
    function ehr_orders_list_labsuppliers($id=NULL)  // List suppliers for labs
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders' );    
		$data['supplier_type']   	= $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Suppliers for ".$data['supplier_type'];
		$data['supplier_list']  = $this->morders_rdb->get_supplier_list_lab();
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
            //$new_body   =   "ehr/ehr_orders_list_labsuppliers_wap";
            $new_body   =   "ehr/ehr_orders_list_labsuppliers_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_orders_list_labsuppliers_html";
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
    } // end of function ehr_orders_list_labsuppliers($id)


    // ------------------------------------------------------------------------
    function orders_edit_labsupplier_info()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_currency']		=	$this->config->item('app_currency');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders','ehr_orders/ehr_orders_list_labsuppliers/lab','List Lab Suppliers');    
        $data['location_id']    =   $_SESSION['location_id'];
        $data['form_purpose']   = $this->uri->segment(3);
        $data['supplier_type']	= $this->uri->segment(4);
        $data['supplier_id']	= $this->uri->segment(5);
        $data['packages_list']= $this->morders_rdb->get_lab_packages_bysupplier($data['supplier_id']);
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_supplier_id']	= $_POST['supplier_id'];
            $data['init_supplier_name'] = $_POST['supplier_name'];
            $data['init_registration_no']= $_POST['registration_no'];
            $data['init_contact_id']          = $_POST['contact_id'];
            $data['init_customer_info_id']          = $_POST['customer_info_id'];
            $data['init_acc_no']        = $_POST['acc_no'];
            $data['init_credit_term']   = $_POST['credit_term'];
            $data['init_supplier_remarks']= $_POST['supplier_remarks'];
            $data['init_address']       = $_POST['address'];
            $data['init_address2']      = $_POST['address2'];
            $data['init_address3']      = $_POST['address3'];
            $data['init_town']          = $_POST['town'];
            $data['init_state']         = $_POST['state'];
            $data['init_postcode']      = $_POST['postcode'];
            $data['init_country']       = $_POST['country'];
            $data['init_tel_no']        = $_POST['tel_no'];
            $data['init_tel2_no']       = $_POST['tel2_no'];
            $data['init_tel3_no']       = $_POST['tel3_no'];
            $data['init_fax_no']        = $_POST['fax_no'];
            $data['init_fax2_no']       = $_POST['fax2_no'];
            $data['init_email']         = $_POST['email'];
            $data['init_contact_person']= $_POST['contact_person'];
            $data['init_contact_other'] = $_POST['contact_other'];
            $data['init_website']       = $_POST['website'];
            $data['init_contact_remarks'] = $_POST['contact_remarks'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_supplier") {
                $data['init_supplier_name'] =   "";
                $data['init_registration_no']=   "";
                $data['init_contact_id']          =   "";
                $data['init_customer_info_id']          =   "";
                $data['init_acc_no']        =   "";
                $data['init_credit_term']   =   0;
                $data['init_supplier_remarks']=   "";
                $data['init_address']       =   "";
                $data['init_address2']      =   "";
                $data['init_address3']      =   "";
                $data['init_town']          =   "";
                $data['init_state']         =   "";
                $data['init_postcode']      =   "";
                $data['init_country']       =   "";
                $data['init_tel_no']        =   "";
                $data['init_tel2_no']       =   "";
                $data['init_tel3_no']       =   "";
                $data['init_fax_no']        =   "";
                $data['init_fax2_no']       =   "";
                $data['init_email']         =   "";
                $data['init_contact_person']=   "";
                $data['init_contact_other'] =   "";
                $data['init_website']       =   "";
                $data['init_contact_remarks']=   "";
            } elseif ($data['form_purpose'] == "edit_supplier") {
                //echo "Edit supplier";
                $data['supplier_info'] = $this->morders_rdb->get_supplier_list_lab($data['supplier_id']);
                $data['init_supplier_name'] = $data['supplier_info'][0]['supplier_name'];
                $data['init_registration_no']= $data['supplier_info'][0]['registration_no'];
                $data['init_contact_id'] = $data['supplier_info'][0]['contact_id'];
                $data['init_customer_info_id'] = $data['supplier_info'][0]['customer_info_id'];
                $data['init_acc_no']        = $data['supplier_info'][0]['acc_no'];
                $data['init_credit_term']   = $data['supplier_info'][0]['credit_term'];
                $data['init_supplier_remarks']= $data['supplier_info'][0]['supplier_remarks'];
                $data['init_address']       = $data['supplier_info'][0]['address'];
                $data['init_address2']      = $data['supplier_info'][0]['address2'];
                $data['init_address3']      = $data['supplier_info'][0]['address3'];
                $data['init_town']          = $data['supplier_info'][0]['town'];
                $data['init_state']         = $data['supplier_info'][0]['state'];
                $data['init_postcode']      = $data['supplier_info'][0]['postcode'];
                $data['init_country']       = $data['supplier_info'][0]['country'];
                $data['init_tel_no']        = $data['supplier_info'][0]['tel_no'];
                $data['init_tel2_no']       = $data['supplier_info'][0]['tel2_no'];
                $data['init_tel3_no']       = $data['supplier_info'][0]['tel2_no'];
                $data['init_fax_no']        = $data['supplier_info'][0]['fax_no'];
                $data['init_fax2_no']       = $data['supplier_info'][0]['fax2_no'];
                $data['init_email']         = $data['supplier_info'][0]['email'];
                $data['init_contact_person']= $data['supplier_info'][0]['contact_person'];
                $data['init_contact_other'] = $data['supplier_info'][0]['contact_other'];
                $data['init_website']       = $data['supplier_info'][0]['website'];
                $data['init_contact_remarks'] = $data['supplier_info'][0]['contact_remarks'];
            } //endif ($data['form_purpose'] == "new_supplier")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Supplier";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_labsupplier') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
                //$new_body   =   "ehr/ehr_orders_edit_labsupplier_info_wap";
                $new_body   =   "ehr/ehr_orders_edit_labsupplier_info_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_orders_html";
                $new_body   =   "ehr/ehr_orders_edit_labsupplier_info_html";
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
        } else {
            //echo "\nValidated successfully.";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_supplier") {
                // New supplier record
                $ins_supplier_array   =   array();
                $ins_supplier_array['staff_id']       = $_SESSION['staff_id'];
                $ins_supplier_array['now_id']         = $data['now_id'];
                $ins_supplier_array['supplier_id']	  = $data['now_id'];
                $ins_supplier_array['supplier_name']  = $data['init_supplier_name'];
                $ins_supplier_array['registration_no']= $data['init_registration_no'];
                $ins_supplier_array['contact_id']     = $data['now_id'];
                $ins_supplier_array['customer_info_id']= $data['now_id'];
                $ins_supplier_array['acc_no']         = $data['init_acc_no'];
                //$ins_supplier_array['account_id']   = $data['init_town'];
                if(is_numeric($data['init_credit_term'])){
                    $ins_supplier_array['credit_term']    = $data['init_credit_term'];
                }
                //$ins_supplier_array['credit_term']    = $data['init_credit_term'];
                $ins_supplier_array['supplier_remarks']= $data['init_supplier_remarks'];
                $ins_supplier_array['address']        = $data['init_address'];
                $ins_supplier_array['address2']       = $data['init_address2'];
                $ins_supplier_array['address3']       = $data['init_address3'];
                $ins_supplier_array['town']           = $data['init_town'];
                $ins_supplier_array['state']          = $data['init_state'];
                $ins_supplier_array['postcode']       = $data['init_postcode'];
                $ins_supplier_array['country']        = $data['init_country'];
                $ins_supplier_array['tel_no']         = $data['init_tel_no'];
                $ins_supplier_array['tel_no2']        = $data['init_tel2_no'];
                $ins_supplier_array['tel_no3']        = $data['init_tel3_no'];
                $ins_supplier_array['fax_no']         = $data['init_fax_no'];
                $ins_supplier_array['fax_no2']        = $data['init_fax2_no'];
                $ins_supplier_array['email']          = $data['init_email'];
                $ins_supplier_array['contact_person'] = $data['init_contact_person'];
                //$ins_supplier_array['other']          = $data['partner_clinic_id'];
                $ins_supplier_array['website']        = $data['init_website'];
                $ins_supplier_array['contact_remarks']        = $data['init_contact_remarks'];
                if($data['offline_mode']){
                    $ins_supplier_array['synch_out']        = $data['now_id'];
                }
	            $ins_supplier_data       =   $this->morders_wdb->insert_new_labsupplier($ins_supplier_array);
            } elseif($data['form_purpose'] == "edit_supplier") {
                // Existing supplier record
                $upd_supplier_array   =   array();
                $upd_supplier_array['staff_id']       = $_SESSION['staff_id'];
                $upd_supplier_array['supplier_id']= $data['supplier_id'];
                $upd_supplier_array['supplier_name']    = $data['init_supplier_name'];
                $upd_supplier_array['registration_no']= $data['init_registration_no'];
                $upd_supplier_array['contact_id']     = $data['init_contact_id'];
                $upd_supplier_array['customer_info_id']= $data['init_customer_info_id'];
                $upd_supplier_array['acc_no']         = $data['init_acc_no'];
                if(is_numeric($data['init_credit_term'])){
                    $upd_supplier_array['credit_term']                = $data['init_credit_term'];
                }
                //$upd_supplier_array['credit_term']    = $data['init_credit_term'];
                $upd_supplier_array['supplier_remarks']= $data['init_supplier_remarks'];
                $upd_supplier_array['address']        = $data['init_address'];
                $upd_supplier_array['address2']       = $data['init_address2'];
                $upd_supplier_array['address3']       = $data['init_address3'];
                $upd_supplier_array['town']           = $data['init_town'];
                $upd_supplier_array['state']          = $data['init_state'];
                $upd_supplier_array['postcode']       = $data['init_postcode'];
                $upd_supplier_array['country']        = $data['init_country'];
                $upd_supplier_array['tel_no']         = $data['init_tel_no'];
                $upd_supplier_array['tel_no2']        = $data['init_tel2_no'];
                $upd_supplier_array['tel_no3']        = $data['init_tel3_no'];
                $upd_supplier_array['fax_no']         = $data['init_fax_no'];
                $upd_supplier_array['fax_no2']        = $data['init_fax2_no'];
                $upd_supplier_array['email']          = $data['init_email'];
                $upd_supplier_array['contact_person'] = $data['init_contact_person'];
                //$upd_supplier_array['other']          = $data['partner_clinic_id'];
                $upd_supplier_array['website']        = $data['init_website'];
                $upd_supplier_array['contact_remarks']        = $data['init_contact_remarks'];
	            $upd_supplier_data       =   $this->morders_wdb->update_lab_supplier($upd_supplier_array);
            } //endif($data['diagnosis_id'] == "new_supplier")
            $new_page = base_url()."index.php/ehr_orders/ehr_orders_list_labsuppliers/lab";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_referral_centre') == FALSE)


    } // end of function orders_edit_labsupplier_info()


    // ------------------------------------------------------------------------
    function orders_edit_lab_package()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_currency']		=	$this->config->item('app_currency');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']   = $this->uri->segment(3);
        $data['supplier_id']	= $this->uri->segment(4);
        $data['lab_package_id']	= $this->uri->segment(5);
        $crumbs3                    =   'ehr_orders/orders_edit_labsupplier_info/edit_supplier/lab/'.$data['supplier_id'];
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders','ehr_orders/ehr_orders_list_labsuppliers/lab','List Lab Suppliers',$crumbs3,'Edit Lab Supplier');    
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_package_location_id']= $_POST['package_location_id'];
            $data['init_clinic_shortname']		= $_POST['clinic_shortname'];
            $data['init_lab_classification_id']= $_POST['lab_classification_id'];
            $data['init_loinc_class_id']	= $_POST['loinc_class_id'];
            $data['init_supplier_id']		= $_POST['supplier_id'];
            $data['init_package_name']		= $_POST['package_name'];
            $data['init_package_code']		= $_POST['package_code'];
            $data['init_description']		= $_POST['description'];
            $data['init_sample_required']	= $_POST['sample_required'];
            $data['init_commonly_used']		= $_POST['commonly_used'];
            $data['init_package_active']	= $_POST['package_active'];
            $data['init_lab_filter_sex']	= $_POST['lab_filter_sex'];
            $data['init_lab_filter_olderthan']	= $_POST['lab_filter_olderthan'];
            $data['init_lab_filter_youngerthan']= $_POST['lab_filter_youngerthan'];
            $data['init_supplier_cost']		= $_POST['supplier_cost'];
            $data['init_retail_price_1']	= $_POST['retail_price_1'];
            $data['init_retail_price_2']	= $_POST['retail_price_2'];
            $data['init_retail_price_3']	= $_POST['retail_price_3'];
            $data['init_package_remarks']	= $_POST['package_remarks'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_package") {
                $data['init_lab_package_id']   =   "";
                $data['init_clinic_shortname']       =   $_SESSION['clinic_shortname'];
                $data['init_package_location_id']=   "";
                $data['init_lab_classification_id']=   "";
                $data['init_loinc_class_id']   =   "";
                $data['loinc_class_id']   =   "";
                $data['init_supplier_id']      =   "";
                $data['init_package_code']     =   "";
                $data['init_sample_required']  =   "";
                $data['init_package_cost_std'] =   "";
                $data['init_package_name']     =   "";
                $data['init_description']      =   "";
                $data['init_commonly_used']    =   "";
                $data['init_package_active']   =   "";
                $data['init_lab_filter_sex']   =   "";
                $data['init_lab_filter_youngerthan']=   "";
                $data['init_lab_filter_olderthan']=   "";
                $data['init_supplier_cost']    =   "";
                $data['init_retail_price_1']   =   "";
                $data['init_retail_price_2']   =   "";
                $data['init_retail_price_3']   =   "";
                $data['init_package_remarks']  =   "";
            } elseif ($data['form_purpose'] == "edit_package") {
                //echo "Edit package";
				$data['package_info']= $this->morders_rdb->get_lab_packages_bysupplier($data['supplier_id'],$data['lab_package_id']);
                $data['init_clinic_shortname']     = $data['package_info'][0]['clinic_shortname'];
                $data['init_package_location_id'] = $data['package_info'][0]['location_id'];
                $data['init_lab_classification_id']= $data['package_info'][0]['lab_classification_id'];
                $data['init_loinc_class_id']  = $data['package_info'][0]['loinc_class_id'];
                $data['init_supplier_id']     = $data['package_info'][0]['supplier_id'];
                $data['init_package_code']    = $data['package_info'][0]['package_code'];
                $data['init_sample_required'] = $data['package_info'][0]['sample_required'];
                $data['init_package_cost_std']= $data['package_info'][0]['package_cost_std'];
                $data['init_package_name']    = $data['package_info'][0]['package_name'];
                $data['init_description']     = $data['package_info'][0]['description'];
                $data['init_commonly_used']   = $data['package_info'][0]['commonly_used'];
                $data['init_package_active']  = $data['package_info'][0]['package_active'];
                $data['init_lab_filter_sex']  = $data['package_info'][0]['lab_filter_sex'];
                $data['init_lab_filter_youngerthan']= $data['package_info'][0]['lab_filter_youngerthan'];
                $data['init_lab_filter_olderthan']= $data['package_info'][0]['lab_filter_olderthan'];
                $data['init_supplier_cost']   = $data['package_info'][0]['supplier_cost'];
                $data['init_retail_price_1']  = $data['package_info'][0]['retail_price_1'];
                $data['init_retail_price_2']  = $data['package_info'][0]['retail_price_2'];
                $data['init_retail_price_3']  = $data['package_info'][0]['retail_price_3'];
                $data['init_package_remarks'] = $data['package_info'][0]['package_remarks'];
                $data['init_class_name']      = $data['package_info'][0]['class_name'];
                $data['init_class_group']     = $data['package_info'][0]['class_group'];
				$data['tests_info']         = $this->memr_rdb->get_one_lab_package_test($data['lab_package_id']);
				$data['packages_ordered']   = $this->morders_rdb->get_lab_package_ordered($data['lab_package_id']);
            } //endif ($data['form_purpose'] == "new_package")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Lab Package";
        if(empty($data['init_package_location_id'])){
            $data['init_package_location_id']   =   $_SESSION['location_id'];
        }
		$data['clinics_list']       =   $this->mthirra->get_clinics_list('All');
		$data['supplier_info'] = $this->morders_rdb->get_supplier_list_lab($data['supplier_id']);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['loinc_class']= $this->morders_rdb->get_loinc_class_lab();
        $data['lab_classifications']= $this->morders_rdb->get_lab_classification();

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_lab_package') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
                //$new_body   =   "ehr/ehr_orders_edit_lab_package_wap";
                $new_body   =   "ehr/ehr_orders_edit_lab_package_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_orders_html";
                $new_body   =   "ehr/ehr_orders_edit_lab_package_html";
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
        } else {
            //echo "\nValidated successfully.";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_package") {
                // New supplier record
                $ins_package_array   =   array();
                $ins_package_array['staff_id']        = $_SESSION['staff_id'];
                $ins_package_array['now_id']          = $data['now_id'];
                $ins_package_array['lab_package_id']  = $data['now_id'];
                $ins_package_array['location_id']     = $data['init_package_location_id'];
                $ins_package_array['lab_classification_id']  = $data['init_lab_classification_id'];
                $ins_package_array['loinc_class_id']  = $data['init_loinc_class_id'];
                $ins_package_array['supplier_id']	  = $data['init_supplier_id'];
                $ins_package_array['package_code']    = $data['init_package_code'];
                $ins_package_array['sample_required'] = $data['init_sample_required'];
                //$ins_package_array['package_cost_std']= $data['init_package_cost_std'];
                $ins_package_array['package_name']    = $data['init_package_name'];
                $ins_package_array['description']     = $data['init_description'];
                //$ins_package_array['commonly_used']   = $data['init_commonly_used'];
                if(is_numeric($data['init_commonly_used'])){
                    $ins_package_array['commonly_used']         = $data['init_commonly_used'];
                }
                $ins_package_array['package_active']  = $data['init_package_active'];
                $ins_package_array['lab_filter_sex']  = $data['init_lab_filter_sex'];
                //$ins_package_array['lab_filter_youngerthan']  = $data['init_lab_filter_youngerthan'];
                if(is_numeric($data['init_lab_filter_youngerthan'])){
                    $ins_package_array['lab_filter_youngerthan']         = $data['init_lab_filter_youngerthan'];
                }
                //$ins_package_array['lab_filter_olderthan']  = $data['init_lab_filter_olderthan'];
                if(is_numeric($data['init_lab_filter_olderthan'])){
                    $ins_package_array['lab_filter_olderthan']         = $data['init_lab_filter_olderthan'];
                }
                //$ins_package_array['supplier_cost']   = $data['init_supplier_cost'];
                if(is_numeric($data['init_supplier_cost'])){
                    $ins_package_array['supplier_cost']         = $data['init_supplier_cost'];
                }
                //$ins_package_array['retail_price_1']  = $data['init_retail_price_1'];
                if(is_numeric($data['init_retail_price_1'])){
                    $ins_package_array['retail_price_1']         = $data['init_retail_price_1'];
                }
                //$ins_package_array['retail_price_2']  = $data['init_retail_price_2'];
                if(is_numeric($data['init_retail_price_2'])){
                    $ins_package_array['retail_price_2']         = $data['init_retail_price_2'];
                }
                //$ins_package_array['retail_price_3']  = $data['init_retail_price_3'];
                if(is_numeric($data['init_retail_price_3'])){
                    $ins_package_array['retail_price_3']         = $data['init_retail_price_3'];
                }
                $ins_package_array['package_remarks'] = $data['init_package_remarks'];
                if($data['offline_mode']){
                    $ins_package_array['synch_out']        = $data['now_id'];
                }
	            $ins_package_data       =   $this->morders_wdb->insert_new_lab_package($ins_package_array);
                $this->session->set_flashdata('data_activity', 'Lab package added.');
            } elseif($data['form_purpose'] == "edit_package") {
                // Existing supplier record
                $upd_package_array   =   array();
                $upd_package_array['staff_id']       = $_SESSION['staff_id'];
                $upd_package_array['lab_package_id']= $data['lab_package_id'];
                $upd_package_array['location_id']     = $data['init_package_location_id'];
                $upd_package_array['lab_classification_id']           = $data['init_lab_classification_id'];
                $upd_package_array['loinc_class_id']           = $data['init_loinc_class_id'];
                $upd_package_array['package_code']           = $data['init_package_code'];
                $upd_package_array['sample_required']           = $data['init_sample_required'];
                //$upd_package_array['price']           = $data['init_price'];
                //$upd_package_array['package_cost_std']           = $data['init_package_cost_std'];
                $upd_package_array['package_name']           = $data['init_package_name'];
                $upd_package_array['description']           = $data['init_description'];
                if(is_numeric($data['init_commonly_used'])){
                    $upd_package_array['commonly_used']         = $data['init_commonly_used'];
                }
                $upd_package_array['package_active']           = $data['init_package_active'];
                $upd_package_array['lab_filter_sex']           = $data['init_lab_filter_sex'];
                if(is_numeric($data['init_lab_filter_youngerthan'])){
                    $upd_package_array['lab_filter_youngerthan']         = $data['init_lab_filter_youngerthan'];
                }
                if(is_numeric($data['init_lab_filter_olderthan'])){
                    $upd_package_array['lab_filter_olderthan']         = $data['init_lab_filter_olderthan'];
                }
                if(is_numeric($data['init_supplier_cost'])){
                    $upd_package_array['supplier_cost']         = $data['init_supplier_cost'];
                }
                if(is_numeric($data['init_retail_price_1'])){
                    $upd_package_array['retail_price_1']         = $data['init_retail_price_1'];
                }
                if(is_numeric($data['init_retail_price_2'])){
                    $upd_package_array['retail_price_2']         = $data['init_retail_price_2'];
                }
                if(is_numeric($data['init_retail_price_3'])){
                    $upd_package_array['retail_price_3']         = $data['init_retail_price_3'];
                }
                $upd_package_array['package_remarks']           = $data['init_package_remarks'];
	            $upd_package_data       =   $this->morders_wdb->update_lab_package($upd_package_array);
                $this->session->set_flashdata('data_activity', 'Lab package updated.');
            } //endif($data['diagnosis_id'] == "new_supplier")
            $new_page = base_url()."index.php/ehr_orders/orders_edit_labsupplier_info/edit_supplier/lab/".$data['supplier_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_referral_centre') == FALSE)


    } // end of function orders_edit_lab_package()


    // ------------------------------------------------------------------------
    function orders_delete_lab_packagetest($id=NULL) 
    {
		$this->load->model('mconsult_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders','ehr_orders/ehr_orders_list_labsuppliers/lab','List Lab Suppliers');    
        $data['supplier_id']        =   $this->uri->segment(3);
        $data['lab_package_id']     =   $this->uri->segment(4);
        $data['lab_package_test_id'] =   $this->uri->segment(5);
        
        // Delete records
        $del_rec_array['lab_package_test_id']      = $data['lab_package_test_id'];
        $del_rec_data =   $this->mconsult_wdb->delete_lab_packagetest($del_rec_array);
        $this->session->set_flashdata('data_activity', 'Lab package test deleted.');
        $new_page = base_url()."index.php/ehr_orders/orders_edit_lab_package/edit_package/".$data['supplier_id']."/".$data['lab_package_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function orders_delete_lab_packagetest($id)


    // ------------------------------------------------------------------------
    // Add/Edit lab package test based on LOINC
    function orders_edit_lab_packagetest()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders','ehr_orders/ehr_orders_list_labsuppliers/lab','List Lab Suppliers');    
        $data['form_purpose']   	=   $this->uri->segment(3);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['loinc_class_name'])) { 
                $data['loinc_class_name']   =   $_POST['loinc_class_name'];
            } else {
                $data['loinc_class_name']   =   "none";
            }
            if(isset($_POST['loinc_num'])) { 
                $data['loinc_num']   =   $_POST['loinc_num'];
            }
            //$data['form_purpose']   = $_POST['form_purpose'];
            $data['supplier_id']   		= $_POST['supplier_id'];
            $data['lab_package_id']   	= $_POST['lab_package_id'];
            $data['lab_package_test_id']= $_POST['lab_package_test_id'];
            $data['sort_test']     		= $_POST['sort_test'];
            $data['test_name']     		= $_POST['test_name'];
            $data['test_code'] 			= $_POST['test_code'];
            $data['normal_adult']       = $_POST['normal_adult'];
            $data['normal_child']       = $_POST['normal_child'];
            $data['normal_infant']      = $_POST['normal_infant'];
            $data['test_remarks']       = $_POST['test_remarks'];
            $data['num_of_tests']    	= $_POST['num_of_tests'];
			$data['package_info']  = $this->memr_rdb->get_one_lab_package($data['lab_package_id']);
			if(count($data['package_info']) > 0){
				for($i=1; ($i <= $data['num_of_tests']); $i++){
					$varname_result =   "test_result_".$i;
					$varname_normal =   "test_normal_".$i;
					$varname_remark =   "test_remark_".$i;
					//$data['package_info'][$i-1][$varname_result]	=	$_POST["test_result_".$i];
					//$data['package_info'][$i-1][$varname_normal]	=	$_POST["test_normal_".$i];
					//$data['package_info'][$i-1][$varname_remark]	=	$_POST["test_remark_".$i];
					//echo $data[$varname_result];
				} //end for($i=1; ($i <= $data['num_of_test']); $i++)
				$data['num_of_tests'] = count($data['package_info']);
			} //endif(count($data['package_info']) == $data['num_of_tests'])
        } else {
            // First time form is displayed
            //$data['form_purpose']   = $this->uri->segment(3);
            $data['lab_package_id'] 	= $this->uri->segment(4);
            $data['lab_package_test_id'] = $this->uri->segment(5);
            $data['lab_order_id']   	= $this->uri->segment(6);
            //$data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_packagetest") {
                //echo "new_lab";
                $data['loinc_class_name'] =   "none";
                $data['sort_test']        =   "";
                $data['test_name']        =   "";
                $data['test_code']        =   "";
                $data['loinc_num']        =   "";
                $data['normal_adult']     =   "";
                $data['normal_child']     =   "";
                $data['normal_infant']    =   "";
                $data['test_remarks']     =   "";
                $data['test_minlegal']    =   "";
                $data['test_minnormal']   =   "";
                $data['test_maxnormal']   =   "";
                $data['test_maxlegal']    =   "";
				$data['package_info'][0]['sample_required'] = "N/A";
                $data['num_of_tests']     =   0;
            } elseif ($data['form_purpose'] == "edit_packagetest") {
                //echo "Edit lab order";
                $data['packagetest_info'] = $this->memr_rdb->get_lab_packagetests($data['lab_package_id'],$data['lab_package_test_id']);
                //$data['lab_package_id']  =   $data['order_info'][0]['lab_package_id'];
                $data['sort_test']     	=   $data['packagetest_info'][0]['sort_test'];
                $data['test_name']     	=   $data['packagetest_info'][0]['test_name'];
                $data['test_code'] 		=   $data['packagetest_info'][0]['test_code'];
                $data['loinc_num'] 		=   $data['packagetest_info'][0]['loinc_num'];
                $data['normal_adult'] 	=   $data['packagetest_info'][0]['normal_adult'];
                $data['normal_child'] 	=   $data['packagetest_info'][0]['normal_child'];
                $data['normal_infant'] 	=   $data['packagetest_info'][0]['normal_infant'];
                $data['test_remarks']   =   $data['packagetest_info'][0]['test_remarks'];
                //$data['test_minlegal'] 	=   $data['packagetest_info'][0]['test_minlegal'];
                //$data['test_minnormal'] =   $data['packagetest_info'][0]['test_minnormal'];
                //$data['test_maxnormal'] =   $data['packagetest_info'][0]['test_maxnormal'];
                //$data['test_maxlegal'] 	=   $data['packagetest_info'][0]['test_maxlegal'];
                $data['loinc_class_name'] =   $data['packagetest_info'][0]['class_name'];
            } //endif ($data['form_purpose'] == "new_lab")
        } //endif(count($_POST))
		$data['title'] = "Lab Package Test";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;

		$data['package_info']  = $this->memr_rdb->get_one_lab_package($data['lab_package_id']);
        $data['loinc_class_name'] =   $data['package_info'][0]['class_name'];
		$data['tests_list']  = $this->memr_rdb->get_lab_packagetests($data['lab_package_id']);
		$data['num_of_tests'] = count($data['tests_list']);
        $data['loinc_class']= $this->morders_rdb->get_loinc_class_lab();
        $data['loinc_list']= $this->morders_rdb->get_loinc($data['loinc_class_name']);
        if($data['debug_mode']){
			echo "\n<hr />";
			echo "<br />data['form_purpose']=".$data['form_purpose'];
			echo "<br />data['lab_package_id']=".$data['lab_package_id'];
			echo "<br />data['lab_package_test_id']=".$data['lab_package_test_id'];
			echo "<br />data['num_of_tests']=".$data['num_of_tests'];
			echo "\n<hr />";
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_lab_packagetest') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
                //$new_body   =   "ehr/ehr_orders_edit_lab_packagetest_wap";
                $new_body   =   "ehr/ehr_orders_edit_lab_packagetest_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_orders_html";
                $new_body   =   "ehr/ehr_orders_edit_lab_packagetest_html";
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
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_packagetest") {
                // New lab order record
                $ins_test_array   =   array();
                $ins_test_array['now_id']          = $data['now_id'];
                $ins_test_array['staff_id']        = $_SESSION['staff_id'];
				$ins_test_array['lab_package_test_id']	=	$data['now_id'];
                $ins_test_array['lab_package_id']  	= $data['lab_package_id'];
                $ins_test_array['location_id']      = $_SESSION['location_id'];
				$ins_test_array['sort_test']		= $data['sort_test'];
				$ins_test_array['test_name']		= $data['test_name'];
				$ins_test_array['test_code']		= $data['test_code'];
				$ins_test_array['loinc_num']		= $data['loinc_num'];
				//$ins_test_array['sample_required']	= $data['sample_required'];
				$ins_test_array['normal_adult']		= $data['normal_adult'];
				$ins_test_array['normal_child']		= $data['normal_child'];
				$ins_test_array['normal_infant']	= $data['normal_infant'];
				$ins_test_array['test_remarks']		= $data['test_remarks'];
                if($data['offline_mode']){
                    $ins_test_array['synch_out']        = $data['now_id'];
                }//endif($data['offline_mode'])
	            $ins_test_data  =   $this->morders_wdb->insert_new_lab_packagetest($ins_test_array);
                $this->session->set_flashdata('data_activity', 'Lab package test added.');
            } elseif($data['form_purpose'] == "edit_packagetest") {
                // Existing lab order record
               $ins_lab_array['lab_package_test_id']    = $data['lab_package_test_id'];
                $ins_lab_array['staff_id']        = $_SESSION['staff_id'];
                $ins_lab_array['lab_package_id']  = $data['lab_package_id'];
                //$ins_lab_array['location_id']  = $_SESSION['location_id'];
                $ins_lab_array['sort_test']  = $data['sort_test'];
                $ins_lab_array['test_name']  = $data['test_name'];
                $ins_lab_array['test_code']  = $data['test_code'];
                $ins_lab_array['loinc_num']  = $data['loinc_num'];
                //$ins_lab_array['sample_required']  = $data['sample_required'];
                $ins_lab_array['normal_adult']  = $data['normal_adult'];
                $ins_lab_array['normal_child']  = $data['normal_child'];
                $ins_lab_array['normal_infant']  = $data['normal_infant'];
                $ins_lab_array['test_remarks']  = $data['test_remarks'];
	            $ins_lab_data  =   $this->morders_wdb->update_lab_packagetest($ins_lab_array);
                $this->session->set_flashdata('data_activity', 'Lab package test updated.');
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_orders/orders_edit_lab_package/edit_package/".$data['supplier_id']."/".$data['lab_package_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_lab_order') == FALSE)


    } // end of function orders_edit_lab_packagetest()


    // ------------------------------------------------------------------------
    function ehr_orders_list_imagsuppliers($id=NULL)  // List suppliers for imaging
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders');    
		$data['supplier_type']   	= $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Suppliers for ".$data['supplier_type'];
		$data['supplier_list']  = $this->morders_rdb->get_supplier_list_imag();
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
            //$new_body   =   "ehr/ehr_orders_list_imagsuppliers_wap";
            $new_body   =   "ehr/ehr_orders_list_imagsuppliers_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_orders_list_imagsuppliers_html";
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
    } // end of function ehr_orders_list_imagsuppliers($id)


    // ------------------------------------------------------------------------
    function orders_edit_imagsupplier_info()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_currency']		=	$this->config->item('app_currency');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders','ehr_orders/ehr_orders_list_imagsuppliers/imaging','List Imaging Suppliers');    
        $data['form_purpose']   = $this->uri->segment(3);
        $data['supplier_type']	= $this->uri->segment(4);
        $data['supplier_id']	= $this->uri->segment(5);
        $data['packages_list']= $this->morders_rdb->get_imag_product_bysupplier($data['supplier_id']);
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_supplier_id']	= $_POST['supplier_id'];
            $data['init_supplier_name'] = $_POST['supplier_name'];
            $data['init_registration_no']= $_POST['registration_no'];
            $data['init_acc_no']        = $_POST['acc_no'];
            $data['init_credit_term']   = $_POST['credit_term'];
            $data['init_supplier_remarks']= $_POST['supplier_remarks'];
            $data['init_address']       = $_POST['address'];
            $data['init_address2']      = $_POST['address2'];
            $data['init_address3']      = $_POST['address3'];
            $data['init_town']          = $_POST['town'];
            $data['init_state']         = $_POST['state'];
            $data['init_postcode']      = $_POST['postcode'];
            $data['init_country']       = $_POST['country'];
            $data['init_tel_no']        = $_POST['tel_no'];
            $data['init_tel2_no']       = $_POST['tel2_no'];
            $data['init_tel3_no']       = $_POST['tel3_no'];
            $data['init_fax_no']        = $_POST['fax_no'];
            $data['init_fax2_no']       = $_POST['fax2_no'];
            $data['init_email']         = $_POST['email'];
            $data['init_contact_person']= $_POST['contact_person'];
            $data['init_contact_other'] = $_POST['contact_other'];
            $data['init_website']       = $_POST['website'];
            $data['init_contact_remarks'] = $_POST['contact_remarks'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_supplier") {
                $data['init_supplier_name'] =   "";
                $data['init_registration_no']=   "";
                $data['init_acc_no']        =   "";
                $data['init_credit_term']   =   0;
                $data['init_supplier_remarks']=   "";
                $data['init_address']       =   "";
                $data['init_address2']      =   "";
                $data['init_address3']      =   "";
                $data['init_town']          =   "";
                $data['init_state']         =   "";
                $data['init_postcode']      =   "";
                $data['init_country']       =   "";
                $data['init_tel_no']        =   "";
                $data['init_tel2_no']       =   "";
                $data['init_tel3_no']       =   "";
                $data['init_fax_no']        =   "";
                $data['init_fax2_no']       =   "";
                $data['init_email']         =   "";
                $data['init_contact_person']=   "";
                $data['init_contact_other'] =   "";
                $data['init_website']       =   "";
                $data['init_contact_remarks']=   "";
            } elseif ($data['form_purpose'] == "edit_supplier") {
                //echo "Edit supplier";
                $data['supplier_info'] = $this->morders_rdb->get_supplier_list_imag($data['supplier_id']);
                $data['init_supplier_name'] = $data['supplier_info'][0]['supplier_name'];
                $data['init_registration_no']= $data['supplier_info'][0]['registration_no'];
                $data['contact_id']        = $data['supplier_info'][0]['contact_id'];
                $data['customer_info_id']        = $data['supplier_info'][0]['customer_info_id'];
                $data['init_acc_no']        = $data['supplier_info'][0]['acc_no'];
                $data['init_credit_term']   = $data['supplier_info'][0]['credit_term'];
                $data['init_supplier_remarks']= $data['supplier_info'][0]['supplier_remarks'];
                $data['init_address']       = $data['supplier_info'][0]['address'];
                $data['init_address2']      = $data['supplier_info'][0]['address2'];
                $data['init_address3']      = $data['supplier_info'][0]['address3'];
                $data['init_town']          = $data['supplier_info'][0]['town'];
                $data['init_state']         = $data['supplier_info'][0]['state'];
                $data['init_postcode']      = $data['supplier_info'][0]['postcode'];
                $data['init_country']       = $data['supplier_info'][0]['country'];
                $data['init_tel_no']        = $data['supplier_info'][0]['tel_no'];
                $data['init_tel2_no']       = $data['supplier_info'][0]['tel2_no'];
                $data['init_tel3_no']       = $data['supplier_info'][0]['tel2_no'];
                $data['init_fax_no']        = $data['supplier_info'][0]['fax_no'];
                $data['init_fax2_no']       = $data['supplier_info'][0]['fax2_no'];
                $data['init_email']         = $data['supplier_info'][0]['email'];
                $data['init_contact_person']= $data['supplier_info'][0]['contact_person'];
                $data['init_contact_other'] = $data['supplier_info'][0]['contact_other'];
                $data['init_website']       = $data['supplier_info'][0]['website'];
                $data['init_contact_remarks'] = $data['supplier_info'][0]['contact_remarks'];
            } //endif ($data['form_purpose'] == "new_supplier")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Supplier";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
		$data['clinics_list']       =   $this->mthirra->get_clinics_list('All');
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_imagsupplier') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
                //$new_body   =   "ehr/ehr_orders_edit_imagsupplier_info_wap";
                $new_body   =   "ehr/ehr_orders_edit_imagsupplier_info_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_orders_html";
                $new_body   =   "ehr/ehr_orders_edit_imagsupplier_info_html";
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
        } else {
            //echo "\nValidated successfully.";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_supplier") {
                // New supplier record
                $ins_supplier_array   =   array();
                $ins_supplier_array['staff_id']       = $_SESSION['staff_id'];
                $ins_supplier_array['now_id']         = $data['now_id'];
                $ins_supplier_array['supplier_id']	  = $data['now_id'];
                $ins_supplier_array['supplier_name']  = $data['init_supplier_name'];
                $ins_supplier_array['registration_no']= $data['init_registration_no'];
                $ins_supplier_array['contact_id']     = $data['now_id'];
                $ins_supplier_array['customer_info_id']= $data['now_id'];
                $ins_supplier_array['acc_no']         = $data['init_acc_no'];
                //$ins_supplier_array['account_id']   = $data['init_town'];
                if(is_numeric($data['init_credit_term'])){
                    $ins_supplier_array['credit_term']                = $data['init_credit_term'];
                }
                //$ins_supplier_array['credit_term']    = $data['init_credit_term'];
                $ins_supplier_array['supplier_remarks']= $data['init_supplier_remarks'];
                $ins_supplier_array['address']        = $data['init_address'];
                $ins_supplier_array['address2']       = $data['init_address2'];
                $ins_supplier_array['address3']       = $data['init_address3'];
                $ins_supplier_array['town']           = $data['init_town'];
                $ins_supplier_array['state']          = $data['init_state'];
                $ins_supplier_array['postcode']       = $data['init_postcode'];
                $ins_supplier_array['country']        = $data['init_country'];
                $ins_supplier_array['tel_no']         = $data['init_tel_no'];
                $ins_supplier_array['tel_no2']        = $data['init_tel_no2'];
                $ins_supplier_array['tel_no3']        = $data['init_tel_no3'];
                $ins_supplier_array['fax_no']         = $data['init_fax_no'];
                $ins_supplier_array['fax_no2']        = $data['init_fax_no2'];
                $ins_supplier_array['email']          = $data['init_email'];
                $ins_supplier_array['contact_person'] = $data['init_contact_person'];
                $ins_supplier_array['other']          = $data['partner_clinic_id'];
                $ins_supplier_array['website']        = $data['init_website'];
                $ins_supplier_array['contact_remarks']        = $data['init_contact_remarks'];
                if($data['offline_mode']){
                    $ins_supplier_array['synch_out']        = $data['now_id'];
                }
	            $ins_supplier_data       =   $this->morders_wdb->insert_new_imagsupplier($ins_supplier_array);
                $this->session->set_flashdata('data_activity', 'Imaging supplier added.');
            } elseif($data['form_purpose'] == "edit_supplier") {
                // Existing supplier record
                $upd_supplier_array   =   array();
                $upd_supplier_array['staff_id']       = $_SESSION['staff_id'];
                $upd_supplier_array['supplier_name']  = $data['init_supplier_name'];
                $upd_supplier_array['registration_no']= $data['init_registration_no'];
                $upd_supplier_array['contact_id']     = $data['contact_id'];
                $upd_supplier_array['customer_info_id']= $data['customer_info_id'];
                $upd_supplier_array['acc_no']         = $data['init_acc_no'];
                //$upd_supplier_array['account_id']   = $data['init_town'];
                if(is_numeric($data['init_credit_term'])){
                    $upd_supplier_array['credit_term']                = $data['init_credit_term'];
                }
                //$upd_supplier_array['credit_term']    = $data['init_credit_term'];
                $upd_supplier_array['supplier_remarks']= $data['init_supplier_remarks'];
                $upd_supplier_array['supplier_id']= $data['supplier_id'];
                $upd_supplier_array['supplier_name']    = $data['init_supplier_name'];
                $upd_supplier_array['address']        = $data['init_address'];
                $upd_supplier_array['address2']       = $data['init_address2'];
                $upd_supplier_array['address3']       = $data['init_address3'];
                $upd_supplier_array['town']           = $data['init_town'];
                $upd_supplier_array['state']          = $data['init_state'];
                $upd_supplier_array['postcode']       = $data['init_postcode'];
                $upd_supplier_array['country']        = $data['init_country'];
                $upd_supplier_array['tel_no']         = $data['init_tel_no'];
                $upd_supplier_array['tel_no2']        = $data['init_tel_no2'];
                $upd_supplier_array['tel_no3']        = $data['init_tel_no3'];
                $upd_supplier_array['fax_no']         = $data['init_fax_no'];
                $upd_supplier_array['fax_no2']        = $data['init_fax_no2'];
                $upd_supplier_array['email']          = $data['init_email'];
                $upd_supplier_array['contact_person'] = $data['init_contact_person'];
                $upd_supplier_array['other']          = $data['partner_clinic_id'];
                $upd_supplier_array['website']        = $data['init_website'];
                $upd_supplier_array['contact_remarks']        = $data['init_contact_remarks'];
	            $upd_supplier_data       =   $this->morders_wdb->update_imaging_supplier($upd_supplier_array);
                $this->session->set_flashdata('data_activity', 'Imaging supplier updated.');
            } //endif($data['diagnosis_id'] == "new_supplier")
            $new_page = base_url()."index.php/ehr_orders/ehr_orders_list_imagsuppliers";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_imagupplier') == FALSE)


    } // end of function orders_edit_imagsupplier_info()


    // ------------------------------------------------------------------------
    // Add/Edit imaging product based on LOINC
    function orders_edit_imag_product()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_currency']		=	$this->config->item('app_currency');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_orders/orders_mgt','Orders','ehr_orders/ehr_orders_list_imagsuppliers/imaging','List Imaging Suppliers');    
        $data['form_purpose']   	=   $this->uri->segment(3);
        $data['supplier_id']	    =   $this->uri->segment(5);
        $data['product_id']	        =   $this->uri->segment(6);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['packages_ordered']   =   array();
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['loinc_class_name'])) { 
                $data['init_loinc_class_name']   =   $_POST['loinc_class_name'];
            } else {
                $data['init_loinc_class_name']   =   "none";
            }
            if(isset($_POST['loinc_num'])) { 
                $data['init_loinc_num']   =   $_POST['loinc_num'];
            }
            //$data['form_purpose']   = $_POST['form_purpose'];
            $data['supplier_id']   		    = $_POST['supplier_id'];
            $data['init_product_code']      = $_POST['product_code'];
            $data['init_supplier_cost']   	= $_POST['supplier_cost'];
            $data['init_retail_price']      = $_POST['retail_price'];
            $data['init_retail_price_2']    = $_POST['retail_price_2'];
            $data['init_retail_price_3']    = $_POST['retail_price_3'];
            $data['init_description'] 		= $_POST['description'];
            $data['init_commonly_used']     = $_POST['commonly_used'];
            $data['init_remarks'] 		    = $_POST['remarks'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_product") {
                //echo "new_product";
                $data['init_clinic_shortname']       =   $_SESSION['clinic_shortname'];
                $data['init_loinc_class_name']  =   "none";
                $data['init_loinc_num']         =   "";
                $data['init_product_code']      =   "";
                $data['init_supplier_cost']     =   0;
                $data['init_retail_price']      =   0;
                $data['init_retail_price_2']    =   0;
                $data['init_retail_price_3']    =   0;
                $data['init_description']       =   "";
                $data['init_imaging_enhanced_id'] =   "";
                $data['init_commonly_used']     =   "";
                $data['init_remarks']           =   "";
                $data['init_package_location_id'] =   "";
                $data['init_component']         =   "";
            } elseif ($data['form_purpose'] == "edit_product") {
                //echo "Edit lab order";
                $data['product_info'] = $this->morders_rdb->get_imag_product_bysupplier($data['supplier_id'],$data['product_id']);
                $data['init_clinic_shortname']  =   $data['product_info'][0]['clinic_shortname'];
                $data['init_loinc_num']     	=   $data['product_info'][0]['loinc_num'];
                $data['init_product_code']     	=   $data['product_info'][0]['product_code'];
                $data['init_supplier_cost'] 	=   $data['product_info'][0]['supplier_cost'];
                $data['init_retail_price'] 		=   $data['product_info'][0]['retail_price'];
                $data['init_retail_price_2'] 	=   $data['product_info'][0]['retail_price_2'];
                $data['init_retail_price_3'] 	=   $data['product_info'][0]['retail_price_3'];
                $data['init_description'] 	    =   $data['product_info'][0]['description'];
                $data['init_imaging_enhanced_id']=   $data['product_info'][0]['imaging_enhanced_id'];
                $data['init_commonly_used']     =   $data['product_info'][0]['commonly_used'];
                $data['init_remarks']           =   $data['product_info'][0]['remarks'];
                $data['init_package_location_id'] = $data['product_info'][0]['location_id'];
                $data['init_component']         =   $data['product_info'][0]['component'];
                $data['init_loinc_class_name']  =   $data['product_info'][0]['class_name'];
				$data['packages_ordered']   = $this->morders_rdb->get_imag_product_ordered($data['product_id']);
            } //endif ($data['form_purpose'] == "new_product")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Imaging Product";
        $data['init_location_id']   =   $_SESSION['location_id'];
        //$data['init_clinic_name']   =   NULL;
        if(empty($data['init_package_location_id'])){
            $data['init_package_location_id']   =   $_SESSION['location_id'];
        }
		$data['clinics_list']       =   $this->mthirra->get_clinics_list('All');
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;
        $data['supplier_info'] = $this->morders_rdb->get_supplier_list_imag($data['supplier_id']);

        $data['loinc_class']= $this->morders_rdb->get_loinc_class_imag();
        $data['loinc_list']= $this->morders_rdb->get_loinc($data['init_loinc_class_name']);
        $data['level3_list']    =   array();
        $data['level3_list'][0]['marker']      = "valid";  
        $data['level3_list'][0]['info']        = "N/A";  
        if(!empty($data['init_loinc_num']) ){
            $data['level3'] =   "valid";
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_imag_product') == FALSE){
            //echo "Validation failed";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_orders_wap";
                //$new_body   =   "ehr/ehr_orders_edit_lab_packagetest_wap";
                $new_body   =   "ehr/ehr_orders_edit_imag_product_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_orders_html";
                $new_body   =   "ehr/ehr_orders_edit_imag_product_html";
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
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_product") {
                // New lab order record
                $ins_imag_array   =   array();
                $ins_imag_array['now_id']           = $data['now_id'];
                $ins_imag_array['staff_id']         = $_SESSION['staff_id'];
				$ins_imag_array['product_id']	    = $data['now_id'];
                $ins_imag_array['location_id']      = $data['init_package_location_id']; // Unused
				$ins_imag_array['loinc_num']		= $data['init_loinc_num'];
				$ins_imag_array['supplier_id']		= $data['supplier_id'];
				$ins_imag_array['product_code']		= $data['init_product_code'];
				$ins_imag_array['supplier_cost']	= $data['init_supplier_cost'];
				$ins_imag_array['retail_price']	    = $data['init_retail_price'];
				$ins_imag_array['retail_price_2']	= $data['init_retail_price_2'];
				$ins_imag_array['retail_price_3']	= $data['init_retail_price_3'];
				$ins_imag_array['description']	    = $data['init_description'];
                if(is_numeric($data['init_commonly_used'])){
                    $ins_imag_array['commonly_used']                = $data['init_commonly_used'];
                }
				//$ins_imag_array['commonly_used']		= $data['commonly_used'];
				$ins_imag_array['remarks']	        = $data['init_remarks'];
                if($data['offline_mode']){
                    $ins_imag_array['synch_out']        = $data['now_id'];
                }//endif($data['offline_mode'])
	            $ins_imag_data  =   $this->morders_wdb->insert_new_imag_product($ins_imag_array);
                $this->session->set_flashdata('data_activity', 'Imaging product added.');
            } elseif($data['form_purpose'] == "edit_product") {
                // Existing lab order record
                $upd_imag_array['product_id']       = $data['product_id'];
                $upd_imag_array['staff_id']         = $_SESSION['staff_id'];
                $upd_imag_array['location_id']      = $data['init_package_location_id']; // Unused
                $upd_imag_array['loinc_num']        = $data['init_loinc_num'];
                $upd_imag_array['supplier_id']      = $data['supplier_id'];
                $upd_imag_array['product_code']     = $data['init_product_code'];
                $upd_imag_array['supplier_cost']    = $data['init_supplier_cost'];
                $upd_imag_array['retail_price']     = $data['init_retail_price'];
                $upd_imag_array['retail_price_2']   = $data['init_retail_price_2'];
                $upd_imag_array['retail_price_3']   = $data['init_retail_price_3'];
                $upd_imag_array['description']      = $data['init_description'];
                if(is_numeric($data['init_commonly_used'])){
                    $upd_imag_array['commonly_used'] = $data['init_commonly_used'];
                }
				$upd_imag_array['remarks']	        = $data['init_remarks'];
	            $upd_imag_data  =   $this->morders_wdb->update_imag_product($upd_imag_array);
                $this->session->set_flashdata('data_activity', 'Imaging product updated.');
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_orders/orders_edit_imagsupplier_info/edit_supplier/imag/".$data['supplier_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_lab_order') == FALSE)


    } // end of function orders_edit_imag_product()


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
