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
 * Controller Class for EHR_CONSULT_ORDERS
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_consult_orders extends MY_Controller 
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
    // Categorised lab form
    function edit_lab()
    {
		$this->load->model('morders_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['form_purpose']   = $this->uri->segment(3);
        $data['patient_id']     = $this->uri->segment(4);
        $data['summary_id']     = $this->uri->segment(5);
        $data['lab_order_id']   = $this->uri->segment(6);
        $patient_id             = $this->uri->segment(4);
	  	
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
            //$data['patient_id']     = $_POST['patient_id'];
            $data['summary_id']     = $_POST['summary_id'];
            $data['lab_order_id']   = $_POST['lab_order_id'];
            $data['sample_date']    = $_POST['sample_date'];
            $data['sample_time']    = $_POST['sample_time'];
            $data['fasting']        = $_POST['fasting'];
            $data['urgency']        = $_POST['urgency'];
            $data['sample_ref']     = $_POST['sample_ref'];
            $data['summary_result'] = $_POST['summary_result'];
            $data['remarks']        = $_POST['remarks'];
            $data['result_status']  = $_POST['result_status'];
            $data['num_of_tests']   = $_POST['num_of_tests'];
            //$data['enter_tests']    = $_POST['enter_tests'];
            if(isset($_POST['enter_tests'])) { 
                $data['enter_tests']   =   $_POST['enter_tests'];
				if($data['enter_tests'] == 'TRUE'){
					//$data['package_info']  = $this->memr_rdb->get_lab_packagetests($data['lab_package_id']);
					// Retrieve lab_result rows for editing
					$data['results_info']  = $this->memr_rdb->get_one_lab_orderresult($data['lab_package_id']);
					if(count($data['results_info']) > 0){
						// Lab_result row(s) exist
						$data['num_of_tests'] = count($data['results_info']);
						for($i=1; ($i <= $data['num_of_tests']); $i++){
							$varname_result =   "test_result_".$i;
							$varname_normal =   "test_normal_".$i;
							$varname_remark =   "test_remark_".$i;
							$data['results_info'][$i-1][$varname_result]	=	$_POST["test_result_".$i];
							$data['results_info'][$i-1][$varname_normal]	=	$_POST["test_normal_".$i];
							$data['results_info'][$i-1][$varname_remark]	=	$_POST["test_remark_".$i];
							//echo $data[$varname_result];
						} //end for($i=1; ($i <= $data['num_of_test']); $i++)
					} else {
						// Lab result row(s) doesn't exist. So create new entry boxes
						$data['package_info']  = $this->memr_rdb->get_lab_packagetests($data['lab_package_id']);
						if(count($data['package_info']) > 0){
							$data['num_of_tests'] = count($data['package_info']);
							/*
							for($i=1; ($i <= $data['num_of_tests']); $i++){
								$varname_result =   "test_result_".$i;
								$varname_normal =   "test_normal_".$i;
								$varname_remark =   "test_remark_".$i;
								$data['package_info'][$i-1][$varname_result]	=	$_POST["test_result_".$i];
								$data['package_info'][$i-1][$varname_normal]	=	$_POST["test_normal_".$i];
								$data['package_info'][$i-1][$varname_remark]	=	$_POST["test_remark_".$i];
								//echo $data[$varname_result];
							} //end for($i=1; ($i <= $data['num_of_test']); $i++)
							*/
						} //endif(count($data['package_info']) == $data['num_of_tests'])						
					} //endif(count($data['package_info']) == $data['num_of_tests'])


				} //endif($data['enter_tests'] == 'TRUE')
			} else {
				$data['enter_tests']	=	FALSE;
				$data['num_of_results']	=	0;
            } //endif(isset($_POST['enter_tests']))
			// Retrieve detailed test results
        } else {
            // First time form is displayed
            //$data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_lab") {
                //echo "new_lab";
                $data['lab_package_id'] =   "none";
                $data['supplier_id']    =   "none";
                $data['sample_date']    =   $data['now_date'];
                $data['sample_time']    =   $data['now_time'];
                $data['fasting']        =   "";
                $data['urgency']        =   "";
                $data['sample_ref']     =   "";
                $data['summary_result'] =   "Pending";
                $data['result_status']  =   "Unknown";
                $data['remarks']        =   "";
                $data['lab_order_id']   = "new_lab";
				$data['package_info'][0]['sample_required'] = "N/A";
                $data['num_of_tests']   =   0;
				$data['num_of_results'] = 	0;
                $data['enter_tests']    =   FALSE;				
            } elseif ($data['form_purpose'] == "edit_lab") {
                //echo "Edit lab order";
                $data['order_info'] = $this->memr_rdb->get_patcon_lab($data['summary_id'],$data['lab_order_id']);
                $data['lab_package_id'] =   $data['order_info'][0]['lab_package_id'];
                $data['supplier_id']    =   $data['order_info'][0]['supplier_id'];
                $data['sample_date']    =   $data['order_info'][0]['sample_date'];
                $data['sample_time']    =   $data['order_info'][0]['sample_time'];
                $data['fasting']        =   $data['order_info'][0]['fasting'];
                $data['urgency']        =   $data['order_info'][0]['urgency'];
                $data['sample_ref']     =   $data['order_info'][0]['sample_ref'];
                $data['summary_result'] =   $data['order_info'][0]['summary_result'];
                $data['result_status']  =   $data['order_info'][0]['result_status'];
                $data['remarks']        =   $data['order_info'][0]['remarks'];
                $data['package_code']   =   $data['order_info'][0]['package_code'];
                $data['package_name']   =   $data['order_info'][0]['package_name'];
                $data['supplier_name']  =   $data['order_info'][0]['supplier_name'];
                $data['acc_no']         =   $data['order_info'][0]['acc_no'];
				// Check whether detailed tests records exist
				$data['results_info']  = $this->memr_rdb->get_one_lab_orderresult($data['lab_order_id']);
				//$data['package_info']  = $this->memr_rdb->get_one_lab_package($data['lab_package_id']);
				//$data['package_info']  = $this->memr_rdb->get_lab_packagetests($data['lab_package_id']);
				$data['num_of_results'] = count($data['results_info']);
				if($data['num_of_results'] > 0){
					$data['enter_tests']    =   TRUE;
				} else {
					$data['enter_tests']    =   FALSE;
				}
            } //endif ($data['form_purpose'] == "new_lab")
        } //endif(count($_POST))
		$data['title'] = "Lab Orders";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['lab_list']       = $this->memr_rdb->get_patcon_lab($data['summary_id']);
        $j  =   0;
        foreach($data['lab_list'] as $ordered){
            $has_details  = $this->memr_rdb->get_one_lab_orderresult($ordered['lab_order_id']);
            if(count($has_details) > 0){
                // Complicated
                $data['lab_list'][$j]['details']    =   "TRUE";
            } else {
                $data['lab_list'][$j]['details']    =   "FALSE";
            }
            $j++;
        }
        
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;

        $data['packages_list']= $this->memr_rdb->get_lab_packages_list($data['init_location_id']);
		$data['supplier_list']  = $this->memr_rdb->get_supplier_by_labpackage($data['lab_package_id']);
		// Reduce number of choices to one
		if(($data['enter_tests'] == TRUE) && ($data['lab_package_id'] <> "none")){
			unset($data['packages_list']);
			unset($data['supplier_list']);
			$data['packages_list']= $this->memr_rdb->get_one_lab_package($data['lab_package_id']);
			$data['package_code']   =   $data['packages_list'][0]['package_code'];
			$data['package_name']   =   $data['packages_list'][0]['package_name'];
			$data['supplier_list']  = $this->memr_rdb->get_supplier_by_labpackage($data['lab_package_id']);
			$data['supplier_name']  =   $data['supplier_list'][0]['supplier_name'];
			$data['acc_no']        	=   $data['supplier_list'][0]['acc_no'];
		}
		//$data['package_info']  = $this->memr_rdb->get_one_lab_package($data['lab_package_id']);
		/*
		if($data['num_of_results'] > 0){
			$data['enter_tests']	=	TRUE;
		} else {
			$data['enter_tests']	=	FALSE;
		}
		*/

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_lab_order') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/ehr_edit_lab_wap";
                $new_body   =   "ehr/ehr_edit_lab_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_lab_html";
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
            if($data['form_purpose'] == "new_lab") {
                // New lab order record
                $ins_lab_array   =   array();
                $ins_lab_array['now_id']          = $data['now_id'];
                $ins_lab_array['lab_order_id']    = $data['now_id'];
                $ins_lab_array['staff_id']        = $_SESSION['staff_id'];
                $ins_lab_array['patient_id']      = $data['patient_id'];
                $ins_lab_array['session_id']      = $data['summary_id'];
                $ins_lab_array['lab_package_id']  = $data['lab_package_id'];
                //$ins_lab_array['product_id']      = $data['product_id'];//Deprecate
                $ins_lab_array['sample_ref']      = $data['sample_ref'];
                $ins_lab_array['sample_date']     = $data['sample_date'];
                $ins_lab_array['sample_time']     = $data['sample_time'];
                if(is_numeric($data['fasting'])){
                    $ins_lab_array['fasting']= $data['fasting'];
                }
                //$ins_lab_array['fasting']         = $data['fasting'];
                $ins_lab_array['urgency']         = $data['urgency'];
                $ins_lab_array['summary_result']  = $data['summary_result'];
                $ins_lab_array['result_status']   = $data['result_status'];
                $ins_lab_array['invoice_status']  = "Unknown";
                //$ins_lab_array['invoice_detail_id']= $data['invoice_detail_id']; //N/A
                $ins_lab_array['remarks']         = $data['remarks'];
                if($data['offline_mode']){
                    $ins_lab_array['synch_out']        = $data['now_id'];
                }//endif($data['offline_mode'])
	            $ins_lab_data  =   $this->mconsult_wdb->insert_new_lab_order($ins_lab_array);
				if(($data['num_of_tests'] > 0) && ($data['enter_tests'] == TRUE)){
					$upd_lab_array['lab_order_id']    = $ins_lab_array['lab_order_id'];
					$upd_lab_array['result_status']   = "Pending";
					$upd_lab_data  =   $this->morders_wdb->update_lab_order($upd_lab_array);
					for($j=1; $j <= $data['num_of_tests']; $j++){
						$varname_result =   "test_result_".$j;
						$varname_normal =   "test_normal_".$j;
						$varname_remark =   "test_remark_".$j;
						$ins_test_array['lab_result_id']=	$data['now_id'];
						$ins_test_array['lab_order_id']	=	$ins_lab_array['lab_order_id'];
						$ins_test_array['sort_test']	=	$data['package_info'][$j-1]['sort_test'];
						$ins_test_array['lab_package_test_id']	=	$data['package_info'][$j-1]['lab_package_test_id'];
						$ins_test_array['result_date']	=	$data['sample_date'];
						$ins_test_array['date_recorded']=	$data['sample_date'];
						$ins_test_array['result']		=	$data['package_info'][$j-1][$varname_result];
						$ins_test_array['loinc_num']	=	$data['package_info'][$j-1]['loinc_num'];
						$ins_test_array['normal_reading']=	$data['package_info'][$j-1][$varname_normal];
						$ins_test_array['staff_id']	=	$_SESSION['staff_id'];
						$ins_test_array['result_remarks']	=	$data['package_info'][$j-1][$varname_remark];
						//$ins_test_array['abnormal_flag']		=	$data['package_info'][$j-1]['abnormal_flag'];
						if($data['offline_mode']){
							$ins_test_array['synch_out']        = $data['now_id'];
						}//endif($data['offline_mode'])
						$ins_test_data  =   $this->mconsult_wdb->insert_new_lab_result($ins_test_array);
						$data['now_id']++;
					}
				}//endif($data['num_of_tests'] > 0)
                $this->session->set_flashdata('data_activity', 'Lab order added.');
            } elseif($data['form_purpose'] == "edit_lab") {
                // Existing lab order record
                $upd_lab_array['lab_order_id']    = $data['lab_order_id'];
                $upd_lab_array['staff_id']        = $_SESSION['staff_id'];
                $upd_lab_array['patient_id']      = $data['patient_id'];
                $upd_lab_array['session_id']      = $data['summary_id'];
                $upd_lab_array['lab_package_id']  = $data['lab_package_id'];
                //$upd_lab_array['product_id']      = $data['product_id'];//Deprecate
                $upd_lab_array['sample_ref']      = $data['sample_ref'];
                $upd_lab_array['sample_date']     = $data['sample_date'];
                $upd_lab_array['sample_time']     = $data['sample_time'];
                if(is_numeric($data['fasting'])){
                    $upd_lab_array['fasting']= $data['fasting'];
                }
                //$upd_lab_array['fasting']         = $data['fasting'];
                $upd_lab_array['urgency']         = $data['urgency'];
                $upd_lab_array['summary_result']  = $data['summary_result'];
                $upd_lab_array['result_status']   = $data['result_status'];
                $upd_lab_array['invoice_status']  = "Unknown";
                //$upd_lab_array['invoice_detail_id']= $data['invoice_detail_id']; //N/A
                $upd_lab_array['remarks']         = $data['remarks'];
                $upd_lab_data  =   $this->morders_wdb->update_lab_order($upd_lab_array);
                $this->session->set_flashdata('data_activity', 'Lab order updated.');
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_lab_order') == FALSE)


    } // end of function edit_lab()


    // ------------------------------------------------------------------------
    function consult_delete_lab($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['patient_id']         =   $this->uri->segment(3);
        $data['summary_id']         =   $this->uri->segment(4);
        $data['lab_order_id']       =   $this->uri->segment(5);
        $data['ordering_status']    =   $this->uri->segment(6);
        
        // Delete records
        $del_rec_array['lab_order_id']      = $data['lab_order_id'];
        $del_rec_array['ordering_status']   = $data['ordering_status'];
        $del_rec_data =   $this->mconsult_wdb->consult_delete_lab($del_rec_array);
        $this->session->set_flashdata('data_activity', 'Lab order deleted.');
        $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function consult_delete_lab($id)


    // ------------------------------------------------------------------------
    // Categorised imaging form
    function edit_imaging()
    {
		$this->load->model('morders_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['imaging_category'])) { 
                $data['imaging_category']   =   $_POST['imaging_category'];
            }
            if(isset($_POST['product_id'])) { 
                $data['product_id']   =   $_POST['product_id'];
            }
            if(isset($_POST['supplier_id'])) { 
                $data['supplier_id']   =   $_POST['supplier_id'];
            }
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['patient_id']     = $_POST['patient_id'];
            $data['summary_id']     = $_POST['summary_id'];
            $data['order_id']   	= $_POST['order_id'];
            $data['order_ref']      = $_POST['order_ref'];
            $data['result_status']  = $_POST['result_status'];
            $data['remarks']        = $_POST['remarks'];
            $data['result_date'] 	= $_POST['result_date'];
            $data['imaging_result'] = $_POST['imaging_result'];
            $data['result_ref'] 	= $_POST['result_ref'];
            $data['result_remarks'] = $_POST['result_remarks'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['patient_id']     = $this->uri->segment(4);
            $data['summary_id']     = $this->uri->segment(5);
            $data['order_id']       = $this->uri->segment(6);
            $patient_id             =   $this->uri->segment(4);
            $data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_imaging") {
                $data['imaging_category']   =   "";
                $data['product_id']  	=   "";
                $data['supplier_id']    =   "";
                $data['order_ref']  	=   "";
                $data['result_status']  =   "Unknown";
                $data['remarks']    	=   "";
                $data['result_date']	=   "";
                $data['imaging_result']	=   "Pending";
                $data['remarks']    	=   "";
                $data['result_date']    	=   "";
                $data['result_remarks']    	=   "";
                $data['result_reviewed_by']    	=   "";
                $data['result_reviewed_date']    	=   "";
                $data['result_ref']    	=   "";
                $data['recorded_timestamp']    	=   "";
            } elseif ($data['form_purpose'] == "edit_imaging") {
                $data['order_info'] = $this->memr_rdb->get_one_imaging_product($data['order_id']); //
                $data['imaging_category']=   $data['order_info']['class_name'];
                $data['product_id']     =   $data['order_info']['product_id'];
                $data['supplier_id']    =   $data['order_info']['supplier_id'];
                $data['order_ref']      =   $data['order_info']['supplier_ref'];
                $data['result_status']  =   $data['order_info']['result_status'];
                $data['remarks']        =   $data['order_info']['remarks'];
                $data['result_date']    =   $data['order_info']['result_date'];
                $data['imaging_result'] =   $data['order_info']['notes'];
                $data['result_remarks'] =   $data['order_info']['result_remarks'];
                $data['result_reviewed_by'] =   $data['order_info']['result_reviewed_by'];
                $data['result_reviewed_date'] =   $data['order_info']['result_reviewed_date'];
                $data['result_ref'] =   $data['order_info']['result_ref'];
                $data['recorded_timestamp'] =   $data['order_info']['recorded_timestamp'];
            } //endif ($data['form_purpose'] == "new_imaging")
        } //endif(count($_POST))
		$data['title'] = "Imaging";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['imaging_list'] = $this->memr_rdb->get_patcon_imaging($data['summary_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;
		//$data['imaging_info']  = $this->memr_rdb->get_one_imaging_product($data['order_id']);

        $data['imaging_categories'] = $this->memr_rdb->get_imaging_categories();
		$data['product_list'] = $this->memr_rdb->get_imaging_product_by_category($data['imaging_category'],$data['init_location_id']);
        if(isset($data['product_id'])){
		    $data['supplier_list'] = $this->memr_rdb->get_supplier_by_product($data['product_id']);
        } else {
            $data['supplier_list'] = array();
        }
        /*
        if(isset($data['supplier_id'])){
		    $data['dcode2ext_list'] = $this->mutil_rdb->get_dcode2ext_by_dcode1ext($data['supplier_id']);
        } else {
            $data['dcode2ext_list'] = array();
        }
        */
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_imaging_order') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/ehr_edit_imaging_wap";
                $new_body   =   "ehr/ehr_edit_imaging_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_imaging_html";
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
            if($data['form_purpose'] == "new_imaging") {
                // New diagnosis record
                $ins_imaging_array   =   array();
                $ins_imaging_array['staff_id']           = $_SESSION['staff_id'];
                $ins_imaging_array['now_id']             = $data['now_id'];
                $ins_imaging_array['order_id']           = $data['now_id'];
                $ins_imaging_array['patient_id']         = $data['patient_id'];
                $ins_imaging_array['session_id']         = $data['summary_id'];
                $ins_imaging_array['product_id']         = $data['product_id'];
                $ins_imaging_array['supplier_ref']       = $data['order_ref'];
                $ins_imaging_array['result_status']      = $data['result_status'];
                $ins_imaging_array['invoice_status']     = "Unknown";
                $ins_imaging_array['remarks']            = $data['remarks'];
                if($data['offline_mode']){
                    $ins_imaging_array['synch_out']        = $data['now_id'];
                }
                $ins_imaging_array['result_id']          = $data['now_id'];
                $ins_imaging_array['order_id']	         = $data['now_id'];
	            $ins_imaging_data       =   $this->mconsult_wdb->insert_new_imaging_order($ins_imaging_array);
				if(("Pending" <> $data['imaging_result']) && ("" <> $data['imaging_result'])){
					// There is result
					if(empty($data['result_date'])){
						$ins_imaging_array['result_date']        = $data['now_date'];
					} else {
						$ins_imaging_array['result_date']        = $data['result_date'];
					}
					$ins_imaging_array['imaging_result'] = $data['imaging_result'];					
					$ins_imaging_array['result_remarks']     = $data['result_remarks'];					
					$ins_imaging_array['result_ref']     = $data['result_ref'];					
					$ins_imaging_array['recorded_timestamp'] = $data['now_id'];					
					$ins_imaging_data       =   $this->morders_wdb->insert_new_imaging_result($ins_imaging_array);
				}
                $this->session->set_flashdata('data_activity', 'Imaging order added.');
            } elseif($data['form_purpose'] == "edit_imaging") {
                // Existing diagnosis record
                $upd_imaging_array   =   array();
                $upd_imaging_array['staff_id']           = $_SESSION['staff_id'];
                $upd_imaging_array['now_id']             = $data['now_id'];
                $upd_imaging_array['order_id']           = $data['order_id'];
                $upd_imaging_array['patient_id']         = $data['patient_id'];
                $upd_imaging_array['session_id']         = $data['summary_id'];
                $upd_imaging_array['product_id']         = $data['product_id'];
                $upd_imaging_array['supplier_ref']       = $data['order_ref'];
                $upd_imaging_array['result_status']      = $data['result_status'];
                $upd_imaging_array['invoice_status']     = "Unknown";
                $upd_imaging_array['remarks']            = $data['remarks'];
	            $upd_imaging_data       =   $this->morders_wdb->update_imaging_order($upd_imaging_array);
                $this->session->set_flashdata('data_activity', 'Imaging order updated.');
            } //endif($data['form_purpose'] == "new_imaging")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_imaging_order') == FALSE)


    } // end of function edit_imaging()


    // ------------------------------------------------------------------------
    // Categorised procedure form
    function edit_procedure()
    {
		$this->load->model('morders_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['form_purpose']   = $this->uri->segment(3);
        $data['patient_id']     = $this->uri->segment(4);
        $data['summary_id']     = $this->uri->segment(5);
        $data['lab_order_id']   = $this->uri->segment(6);
        $patient_id             = $this->uri->segment(4);
	  	
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
            //$data['patient_id']     = $_POST['patient_id'];
            $data['summary_id']     = $_POST['summary_id'];
            $data['lab_order_id']   = $_POST['lab_order_id'];
            $data['sample_date']    = $_POST['sample_date'];
            $data['sample_time']    = $_POST['sample_time'];
            $data['fasting']        = $_POST['fasting'];
            $data['urgency']        = $_POST['urgency'];
            $data['sample_ref']     = $_POST['sample_ref'];
            $data['summary_result'] = $_POST['summary_result'];
            $data['remarks']        = $_POST['remarks'];
            $data['result_status']  = $_POST['result_status'];
            $data['num_of_tests']   = $_POST['num_of_tests'];
            //$data['enter_tests']    = $_POST['enter_tests'];
            if(isset($_POST['enter_tests'])) { 
                $data['enter_tests']   =   $_POST['enter_tests'];
				if($data['enter_tests'] == 'TRUE'){
					//$data['package_info']  = $this->memr_rdb->get_lab_packagetests($data['lab_package_id']);
					// Retrieve lab_result rows for editing
					$data['results_info']  = $this->memr_rdb->get_one_lab_orderresult($data['lab_package_id']);
					if(count($data['results_info']) > 0){
						// Lab_result row(s) exist
						$data['num_of_tests'] = count($data['results_info']);
						for($i=1; ($i <= $data['num_of_tests']); $i++){
							$varname_result =   "test_result_".$i;
							$varname_normal =   "test_normal_".$i;
							$varname_remark =   "test_remark_".$i;
							$data['results_info'][$i-1][$varname_result]	=	$_POST["test_result_".$i];
							$data['results_info'][$i-1][$varname_normal]	=	$_POST["test_normal_".$i];
							$data['results_info'][$i-1][$varname_remark]	=	$_POST["test_remark_".$i];
							//echo $data[$varname_result];
						} //end for($i=1; ($i <= $data['num_of_test']); $i++)
					} else {
						// Lab result row(s) doesn't exist. So create new entry boxes
						$data['package_info']  = $this->memr_rdb->get_lab_packagetests($data['lab_package_id']);
						if(count($data['package_info']) > 0){
							$data['num_of_tests'] = count($data['package_info']);
							/*
							for($i=1; ($i <= $data['num_of_tests']); $i++){
								$varname_result =   "test_result_".$i;
								$varname_normal =   "test_normal_".$i;
								$varname_remark =   "test_remark_".$i;
								$data['package_info'][$i-1][$varname_result]	=	$_POST["test_result_".$i];
								$data['package_info'][$i-1][$varname_normal]	=	$_POST["test_normal_".$i];
								$data['package_info'][$i-1][$varname_remark]	=	$_POST["test_remark_".$i];
								//echo $data[$varname_result];
							} //end for($i=1; ($i <= $data['num_of_test']); $i++)
							*/
						} //endif(count($data['package_info']) == $data['num_of_tests'])						
					} //endif(count($data['package_info']) == $data['num_of_tests'])


				} //endif($data['enter_tests'] == 'TRUE')
			} else {
				$data['enter_tests']	=	FALSE;
				$data['num_of_results']	=	0;
            } //endif(isset($_POST['enter_tests']))
			// Retrieve detailed test results
        } else {
            // First time form is displayed
            //$data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_procedure") {
                //echo "new_lab";
                $data['lab_package_id'] =   "none";
                $data['supplier_id']    =   "none";
                $data['sample_date']    =   $data['now_date'];
                $data['sample_time']    =   $data['now_time'];
                $data['fasting']        =   "";
                $data['urgency']        =   "";
                $data['sample_ref']     =   "";
                $data['summary_result'] =   "Pending";
                $data['result_status']  =   "Unknown";
                $data['remarks']        =   "";
                $data['lab_order_id']   = "new_lab";
				$data['package_info'][0]['sample_required'] = "N/A";
                $data['num_of_tests']   =   0;
				$data['num_of_results'] = 	0;
                $data['enter_tests']    =   FALSE;				
            } elseif ($data['form_purpose'] == "edit_procedure") {
                //echo "Edit lab order";
                $data['order_info'] = $this->memr_rdb->get_patcon_lab($data['summary_id'],$data['lab_order_id']);
                $data['lab_package_id'] =   $data['order_info'][0]['lab_package_id'];
                $data['supplier_id']    =   $data['order_info'][0]['supplier_id'];
                $data['sample_date']    =   $data['order_info'][0]['sample_date'];
                $data['sample_time']    =   $data['order_info'][0]['sample_time'];
                $data['fasting']        =   $data['order_info'][0]['fasting'];
                $data['urgency']        =   $data['order_info'][0]['urgency'];
                $data['sample_ref']     =   $data['order_info'][0]['sample_ref'];
                $data['summary_result'] =   $data['order_info'][0]['summary_result'];
                $data['result_status']  =   $data['order_info'][0]['result_status'];
                $data['remarks']        =   $data['order_info'][0]['remarks'];
                $data['package_code']   =   $data['order_info'][0]['package_code'];
                $data['package_name']   =   $data['order_info'][0]['package_name'];
                $data['supplier_name']  =   $data['order_info'][0]['supplier_name'];
                $data['acc_no']         =   $data['order_info'][0]['acc_no'];
				// Check whether detailed tests records exist
				$data['results_info']  = $this->memr_rdb->get_one_lab_orderresult($data['lab_order_id']);
				//$data['package_info']  = $this->memr_rdb->get_one_lab_package($data['lab_package_id']);
				//$data['package_info']  = $this->memr_rdb->get_lab_packagetests($data['lab_package_id']);
				$data['num_of_results'] = count($data['results_info']);
				if($data['num_of_results'] > 0){
					$data['enter_tests']    =   TRUE;
				} else {
					$data['enter_tests']    =   FALSE;
				}
            } //endif ($data['form_purpose'] == "new_procedure")
        } //endif(count($_POST))
		$data['title'] = "Procedure Orders";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['lab_list']       = $this->memr_rdb->get_patcon_lab($data['summary_id']);
        $j  =   0;
        foreach($data['lab_list'] as $ordered){
            $has_details  = $this->memr_rdb->get_one_lab_orderresult($ordered['lab_order_id']);
            if(count($has_details) > 0){
                // Complicated
                $data['lab_list'][$j]['details']    =   "TRUE";
            } else {
                $data['lab_list'][$j]['details']    =   "FALSE";
            }
            $j++;
        }
        
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;

        $data['packages_list']= $this->memr_rdb->get_lab_packages_list($data['init_location_id']);
		$data['supplier_list']  = $this->memr_rdb->get_supplier_by_labpackage($data['lab_package_id']);
		// Reduce number of choices to one
		if(($data['enter_tests'] == TRUE) && ($data['lab_package_id'] <> "none")){
			unset($data['packages_list']);
			unset($data['supplier_list']);
			$data['packages_list']= $this->memr_rdb->get_one_lab_package($data['lab_package_id']);
			$data['package_code']   =   $data['packages_list'][0]['package_code'];
			$data['package_name']   =   $data['packages_list'][0]['package_name'];
			$data['supplier_list']  = $this->memr_rdb->get_supplier_by_labpackage($data['lab_package_id']);
			$data['supplier_name']  =   $data['supplier_list'][0]['supplier_name'];
			$data['acc_no']        	=   $data['supplier_list'][0]['acc_no'];
		}
		//$data['package_info']  = $this->memr_rdb->get_one_lab_package($data['lab_package_id']);
		/*
		if($data['num_of_results'] > 0){
			$data['enter_tests']	=	TRUE;
		} else {
			$data['enter_tests']	=	FALSE;
		}
		*/

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_lab_order') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/ehr_edit_lab_wap";
                $new_body   =   "ehr/ehr_edit_procedure_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_procedure_html";
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
            if($data['form_purpose'] == "new_procedure") {
                // New lab order record
                $ins_lab_array   =   array();
                $ins_lab_array['now_id']          = $data['now_id'];
                $ins_lab_array['lab_order_id']    = $data['now_id'];
                $ins_lab_array['staff_id']        = $_SESSION['staff_id'];
                $ins_lab_array['patient_id']      = $data['patient_id'];
                $ins_lab_array['session_id']      = $data['summary_id'];
                $ins_lab_array['lab_package_id']  = $data['lab_package_id'];
                //$ins_lab_array['product_id']      = $data['product_id'];//Deprecate
                $ins_lab_array['sample_ref']      = $data['sample_ref'];
                $ins_lab_array['sample_date']     = $data['sample_date'];
                $ins_lab_array['sample_time']     = $data['sample_time'];
                if(is_numeric($data['fasting'])){
                    $ins_lab_array['fasting']= $data['fasting'];
                }
                //$ins_lab_array['fasting']         = $data['fasting'];
                $ins_lab_array['urgency']         = $data['urgency'];
                $ins_lab_array['summary_result']  = $data['summary_result'];
                $ins_lab_array['result_status']   = $data['result_status'];
                $ins_lab_array['invoice_status']  = "Unknown";
                //$ins_lab_array['invoice_detail_id']= $data['invoice_detail_id']; //N/A
                $ins_lab_array['remarks']         = $data['remarks'];
                if($data['offline_mode']){
                    $ins_lab_array['synch_out']        = $data['now_id'];
                }//endif($data['offline_mode'])
	            $ins_lab_data  =   $this->mconsult_wdb->insert_new_lab_order($ins_lab_array);
				if(($data['num_of_tests'] > 0) && ($data['enter_tests'] == TRUE)){
					$upd_lab_array['lab_order_id']    = $ins_lab_array['lab_order_id'];
					$upd_lab_array['result_status']   = "Pending";
					$upd_lab_data  =   $this->morders_wdb->update_lab_order($upd_lab_array);
					for($j=1; $j <= $data['num_of_tests']; $j++){
						$varname_result =   "test_result_".$j;
						$varname_normal =   "test_normal_".$j;
						$varname_remark =   "test_remark_".$j;
						$ins_test_array['lab_result_id']=	$data['now_id'];
						$ins_test_array['lab_order_id']	=	$ins_lab_array['lab_order_id'];
						$ins_test_array['sort_test']	=	$data['package_info'][$j-1]['sort_test'];
						$ins_test_array['lab_package_test_id']	=	$data['package_info'][$j-1]['lab_package_test_id'];
						$ins_test_array['result_date']	=	$data['sample_date'];
						$ins_test_array['date_recorded']=	$data['sample_date'];
						$ins_test_array['result']		=	$data['package_info'][$j-1][$varname_result];
						$ins_test_array['loinc_num']	=	$data['package_info'][$j-1]['loinc_num'];
						$ins_test_array['normal_reading']=	$data['package_info'][$j-1][$varname_normal];
						$ins_test_array['staff_id']	=	$_SESSION['staff_id'];
						$ins_test_array['result_remarks']	=	$data['package_info'][$j-1][$varname_remark];
						//$ins_test_array['abnormal_flag']		=	$data['package_info'][$j-1]['abnormal_flag'];
						if($data['offline_mode']){
							$ins_test_array['synch_out']        = $data['now_id'];
						}//endif($data['offline_mode'])
						$ins_test_data  =   $this->mconsult_wdb->insert_new_lab_result($ins_test_array);
						$data['now_id']++;
					}
				}//endif($data['num_of_tests'] > 0)
                $this->session->set_flashdata('data_activity', 'Lab order added.');
            } elseif($data['form_purpose'] == "edit_procedure") {
                // Existing lab order record
                $upd_lab_array['lab_order_id']    = $data['lab_order_id'];
                $upd_lab_array['staff_id']        = $_SESSION['staff_id'];
                $upd_lab_array['patient_id']      = $data['patient_id'];
                $upd_lab_array['session_id']      = $data['summary_id'];
                $upd_lab_array['lab_package_id']  = $data['lab_package_id'];
                //$upd_lab_array['product_id']      = $data['product_id'];//Deprecate
                $upd_lab_array['sample_ref']      = $data['sample_ref'];
                $upd_lab_array['sample_date']     = $data['sample_date'];
                $upd_lab_array['sample_time']     = $data['sample_time'];
                if(is_numeric($data['fasting'])){
                    $upd_lab_array['fasting']= $data['fasting'];
                }
                //$upd_lab_array['fasting']         = $data['fasting'];
                $upd_lab_array['urgency']         = $data['urgency'];
                $upd_lab_array['summary_result']  = $data['summary_result'];
                $upd_lab_array['result_status']   = $data['result_status'];
                $upd_lab_array['invoice_status']  = "Unknown";
                //$upd_lab_array['invoice_detail_id']= $data['invoice_detail_id']; //N/A
                $upd_lab_array['remarks']         = $data['remarks'];
                $upd_lab_data  =   $this->morders_wdb->update_lab_order($upd_lab_array);
                $this->session->set_flashdata('data_activity', 'Lab order updated.');
            } //endif($data['diagnosis_id'] == "new_procedure")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_procedure_order') == FALSE)


    } // end of function edit_procedure()


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
