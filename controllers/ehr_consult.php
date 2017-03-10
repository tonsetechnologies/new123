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
 * Contributor(s):
 *   Jason Tan Boon Teck <tanboonteck@gmail.com> (original author)
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

/**
 * Controller Class for EHR_CONSULT
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_consult extends MY_Controller 
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

        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-1);
        //$data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."-uploads/";
        define("PICS_URL", $data['pics_url']);
        
		$data['modules_list']   = $this->memr_rdb->get_externalmod_list('episode');
    }


    // ------------------------------------------------------------------------
    // === PATIENT CONSULTATION
    // ------------------------------------------------------------------------
    function consult_new($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	
		$data['title']          = 'Consultation Episode';
		$data['form_purpose']   = 'new_episode';
		$data['patient_id']     = $this->uri->segment(3);
		$data['summary_id']     = $this->uri->segment(4);
		$data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info'] = $this->memr_rdb->get_patcon_details($data['patient_id']);
		$data['patient_past_con'] = $this->memr_rdb->get_pastcons_list($data['patient_id']);
		$this->load->vars($data);

        if($data['patcon_info']['summary_id']=="new_summary"){
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_emr_patients_consltNoLink_wap";
                //$new_body   =   "ehr/emr_indv_startconsult_wap";
                $new_body   =   "ehr/ehr_indv_startconsult_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_body   =   "ehr/ehr_indv_startconsult_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_consltNoLink_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            $this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            //$new_body   =   "ehr/ehr_indv_consult_html";                
            //$new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['patcon_info']['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif($data['patcon_info']['summary_id']=="new_summary")
    } // end of function consult_new($id)


    // ------------------------------------------------------------------------
    function consult_episode($id=NULL)
    {
		$this->load->model('mehr_wdb');
		$this->load->model('morders_wdb');
		$this->load->model('mqueue_rdb');
		$this->load->model('mqueue_wdb');
		$this->load->model('mgem_rdb');
		$this->load->model('mantenatal_rdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	
		$data['title'] = 'Consultation Episode';
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
            $ins_episode_array['status']                =   0;
            $ins_episode_array['remarks']               =   "THIRRA";
            $ins_episode_array['now_id']                =   $data['now_id'];
            if($data['offline_mode']){
                $ins_episode_array['synch_start']       = $data['now_id'];
                $ins_episode_array['synch_out']       = $data['now_id'];
            }
            $ins_episode_data       =   $this->mconsult_wdb->insert_new_episode($ins_episode_array);
            $data['save_attempt'] = 'NEW EPISODE ADDED SUCCESSFULLY';
            $data['summary_id'] = $data['now_id'];
            $data['date_ended']     =   "";
            $data['time_ended']     =   "";
        } elseif(count($_POST) && $data['form_purpose']=="edit_episode"){
            $data['patient_id']     =   $_POST['patient_id'];
            $data['summary_id']     =   $_POST['summary_id'];            
            $data['date_started']   =   $_POST['date_started'];
            $data['time_started']   =   $_POST['time_started'];
            $data['date_ended']     =   $_POST['date_ended'];
            $data['time_ended']     =   $_POST['time_ended'];
            $data['consult_notes']  =   $this->input->post('consult_notes');
            $data['external_ref']   =   $this->input->post('external_ref');
            //$data['consult_notes']  =   $_POST['consult_notes'];
            //$data['external_ref']   =   $_POST['external_ref'];
        } else { // User arrived from a link, e.g. sidebar
			// User started new episode
            $data['patient_id'] = $this->uri->segment(3);
            $data['summary_id'] = $this->uri->segment(4);
            $data['date_ended']     =   $data['now_date'];
            $data['time_ended']     =   $data['now_time'];
       }
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
		if(isset($_POST['consult_notes'])){
			// Do nothing as already taken care of earlier
		} else {
            $data['consult_notes']  =   $data['patcon_info']['summary'];			
		}
        $data['complaints_list']= $this->memr_rdb->get_patcon_complaints($data['summary_id']);
        $data['vitals_info']    = $this->memr_rdb->get_patcon_vitals($data['summary_id']);
        $data['physical_info']  = $this->memr_rdb->get_patcon_physical($data['summary_id']);
        $data['prediagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id'],NULL,NULL,TRUE);
        $data['diagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($data['summary_id']);
        $data['lab_list']       = $this->memr_rdb->get_patcon_lab($data['summary_id']);
        $data['imaging_list']   = $this->memr_rdb->get_patcon_imaging($data['summary_id']);
        $data['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id']);
        $data['referrals_list'] = $this->memr_rdb->get_patcon_referrals($data['summary_id']);
        $data['antenatal_info']  = $this->memr_rdb->get_antenatal_list('Open',$data['patient_id']);
		$data['checkup_list']  = $this->memr_rdb->get_antenatal_followup('list',$data['patient_id'],$data['antenatal_info'][0]['antenatal_id']);
        $data['last_episode']   = $this->memr_rdb->get_last_session_reference();
        $data['postpartum_list']  = $this->mantenatal_rdb->get_antenatal_postpartum('list',$data['patient_id'],$data['antenatal_info'][0]['antenatal_id']);
		$data['submodules_list']    = $this->mgem_rdb->get_submodules_list(NULL, $data['summary_id']);
        /*
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        */
        if($data['debug_mode']){
            $this->output->enable_profiler(TRUE);  
        }
		$this->load->vars($data);

        // Run validation and close episode if successfully validated.
		if ($this->form_validation->run('edit_episode') == FALSE){
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/ehr_indv_consult_wap";
                $new_body   =   "ehr/ehr_indv_consult_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_indv_consult_html";
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
                $end_episode_array   =   array();
                $end_episode_array['now_id']             = $data['now_id'];
                $end_episode_array['patient_id']         = $data['patient_id'];
                $end_episode_array['summary_id']         = $data['summary_id'];
                $end_episode_array['session_ref']        = $data['last_episode']['max_ref']+1;
                $end_episode_array['external_ref']       = $data['external_ref'];
                $end_episode_array['adt_id']             = $data['summary_id'];
                $end_episode_array['date_started']       = $data['date_started'];
                $end_episode_array['time_started']       = $data['time_started'];
                $end_episode_array['date_ended']         = $data['date_ended'];
                $end_episode_array['time_ended']         = $data['time_ended'];
                $end_episode_array['signed_by']          = $_SESSION['staff_id'];
                $end_episode_array['consult_summary']    = $data['consult_notes'];
                $end_episode_array['location_end']       = $_SESSION['location_id'];
                $end_episode_array['status']             = 1; // Should be 10
                $end_episode_array['remarks']            = "THIRRA";//$data['remarks'];
                $end_episode_array['check_out_date']     = $data['date_ended'];
                $end_episode_array['check_out_time']     = $data['time_ended'];
                if($data['offline_mode']){
                    $end_episode_array['synch_out']        = $data['now_id'];
                }
	            $end_episode_data       =   $this->mconsult_wdb->end_episode($end_episode_array,$data['diagnosis_list']);
				
				// Vital signs
				// Change reading date of vital signs
				
				// Check whether vital signs row(s) were created.
				if(count($data['vitals_info']) > 0 && ($data['vitals_info']['vital_id']) <> "new_vitals"){
                    if($data['debug_mode']){
                        print_r($data['vitals_info']);  
                    }
                    $ins_vitals_array   =   array();
                    $ins_vitals_array['vital_id']           = $data['vitals_info']['vital_id'];
                    $ins_vitals_array['reading_date']       = $data['date_ended'];
                    if($data['vitals_info']['reading_date'] <> $data['date_ended']){
                        $ins_vitals_array['reading_time']       = $data['time_ended'];
                    }
                    $ins_vitals_data       =   $this->mconsult_wdb->update_vitals($ins_vitals_array);
                }
                
                
                // Lab results
				// Change status of lab orders
				
				// Check whether lab results row(s) were created.
				if(count($data['lab_list']) > 0 ){
					//echo "Has lab test.";
					// Was there a result?
                    if($data['debug_mode']){
                        print_r($data['lab_list']);  
                    }
					$k=0; 
					while($k < count($data['lab_list'])){
						//echo "Any result? ";
						//echo "k=".$k;
						$data['results_info'] =	array();
						$data['results_info']  = $this->memr_rdb->getsimple_one_lab_results($data['lab_list'][$k]['lab_order_id']);
						//echo "<br />Results_count=". count($data['results_info']);
						//echo "<pre>";
						//print_r($data['results_info']);
						//echo "</pre>";
						if(count($data['results_info']) > 0){
							//echo "Already created - do nothing";
							
						} else {
							//echo "Create and insert the rows";
							$ins_lab_array['result_status'] = "Pending";
							$data['package_info'] =	array();
							$data['package_info']  = $this->memr_rdb->get_lab_packagetests($data['lab_list'][$k]['lab_package_id']);
							//echo "<br />Results_count=". count($data['package_info']);
							$data['num_of_tests'] = count($data['package_info']);
							//echo "<pre>";
							//print_r($data['package_info']);
							//echo "</pre>";
							
							for($j=1; $j <= $data['num_of_tests']; $j++){
								$varname_result =   "test_result_".$j;
								$varname_normal =   "test_normal_".$j;
								$varname_remark =   "test_remark_".$j;
								$ins_test_array['lab_result_id']=	$data['now_id'];
								$ins_test_array['lab_order_id']	=	$data['lab_list'][$k]['lab_order_id'];
								$ins_test_array['sort_test']	=	$data['package_info'][$j-1]['sort_test'];
								$ins_test_array['lab_package_test_id']	=	$data['package_info'][$j-1]['lab_package_test_id'];
								$ins_test_array['result_date']	=	NULL;
								$ins_test_array['date_recorded']=	NULL;
								$ins_test_array['result']		=	NULL;
								$ins_test_array['loinc_num']	=	$data['package_info'][$j-1]['loinc_num'];
								$ins_test_array['normal_reading']=	$data['package_info'][$j-1][$varname_normal];
								$ins_test_array['staff_id']	=	$_SESSION['staff_id'];
								$ins_test_array['result_remarks']=	NULL;
								//$ins_test_array['abnormal_flag']		=	$data['package_info'][$j-1]['abnormal_flag'];
								if($data['offline_mode']){
									$ins_test_array['synch_out']        = $data['now_id'];
								}//endif($data['offline_mode'])
								$ins_test_data  =   $this->mconsult_wdb->insert_new_lab_result($ins_test_array);
								$data['now_id']++;
							} //endfor($j=1; $j <= $data['num_of_tests']; $j++)
							
						}//endif(count($data['results_info']) > 0)						
						// Change status of existing lab order record
						$upd_lab_array['lab_order_id']    = $data['lab_list'][$k]['lab_order_id'];
						$upd_lab_array['result_status']   = "Pending";
						$upd_lab_array['invoice_status']  = "Pending";
						//$upd_lab_array['invoice_detail_id']= $data['invoice_detail_id']; //N/A
                        // Change sample date to end_date if backdating clinical episode
                        if($data['lab_list'][$k]['sample_date'] > $data['date_ended']){
                            $upd_lab_array['sample_date'] =   $data['date_ended'];
                        }
						$upd_lab_data  =   $this->morders_wdb->update_lab_order($upd_lab_array);
					
						$k++;
					}//while($k < count($data['lab_list']))
					
				} //endif(count($data['lab_list']) > 0 )

				// Imaging results
				// Change status of imaging orders
				
				// Check whether imaging results row(s) were created.
				if(count($data['imaging_list']) > 0 ){
					//echo "Has imaging test.";
					// Was there a result?
					$l=0; 
					while($l < count($data['imaging_list'])){
						//echo "Any result? ";
						//echo "l=".$l;
						$data['results_info'] =	array();
						$data['results_info']  = $this->memr_rdb->getsimple_one_imaging_result($data['imaging_list'][$l+1]['order_id']);
						//echo "<br />Results_count=". count($data['results_info']);
						//echo "<pre>";
						//print_r($data['results_info']);
						//echo "</pre>";
						if(count($data['results_info']) > 0){
							//echo "Already created - do nothing";
							
						} else {
							//echo "Create and insert the rows";
							$ins_imaging_array['order_id']     = $data['imaging_list'][$l+1]['order_id'];	
							$ins_imaging_array['result_id']     = $data['imaging_list'][$l+1]['order_id'];	
							if(empty($data['result_date'])){
								$ins_imaging_array['result_date']        = $data['now_date'];
							} else {
								$ins_imaging_array['result_date']        = $data['result_date'];
							}
							//$ins_imaging_array['imaging_result']     = $data['imaging_list'][$l+1]['imaging_result'];	
							$ins_imaging_array['imaging_result']     = "Pending";	
                            if($data['offline_mode']){
                                $ins_imaging_array['synch_out']        = $data['now_id'];
                            }//endif($data['offline_mode'])
							$ins_imaging_data       =   $this->morders_wdb->insert_new_imaging_result($ins_imaging_array);							
						}//endif(count($data['results_info']) > 0)						
						
						// Change status of existing imaging order record
						$upd_imaging_array   =   array();
						$upd_imaging_array['order_id']           = $data['imaging_list'][$l+1]['order_id'];
						$upd_imaging_array['result_status']      = "Pending";
						$upd_imaging_array['invoice_status']     = "Pending";
						$upd_imaging_data       =   $this->morders_wdb->update_imaging_order($upd_imaging_array);
						$l++;
					}//while($l < count($data['imaging_list']))
					
				} //endif(count($data['imaging_list']) > 0 )
				
               // Change status of prescription records
				if(count($data['prescribe_list']) > 0 ){
					//echo "Has a prescription.";
					// Was there a result?
					$m=0; 
					while($m < count($data['prescribe_list'])){
						$upd_prescribe_array   =   array();
						$upd_prescribe_array['queue_id']     = $data['prescribe_list'][$m+1]['queue_id'];
                        $data['queue_id']       =   $upd_prescribe_array['queue_id'];
						$upd_prescribe_array['status']       = "Pending";
						$upd_prescribe_data =   $this->mconsult_wdb->update_prescription($upd_prescribe_array);
                        
                        // Insert patient_medication
                        //echo "Retrieve prescription";
                        $data['prescribe_info'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id'],$data['queue_id']);
                        $data['drug_system']        =   $data['prescribe_info'][1]['formulary_system'];
                        $data['drug_formulary_id']  =   $data['prescribe_info'][1]['drug_formulary_id'];
                        $data['generic_name']       =   $data['prescribe_info'][1]['generic_name'];
                        $data['drug_code_id']       =   $data['prescribe_info'][1]['drug_code_id'];
                        $data['drug_batch']         =   "";
                        $data['dose']               =   $data['prescribe_info'][1]['dose'];
                        $data['dose_form']          =   $data['prescribe_info'][1]['dose_form'];
                        $data['frequency']          =   $data['prescribe_info'][1]['frequency'];
                        $data['instruction']        =   $data['prescribe_info'][1]['instruction'];
                        $data['dose_duration']      =   $data['prescribe_info'][1]['dose_duration'];
                        $data['quantity']           =   $data['prescribe_info'][1]['quantity'];
                        $data['indication']         =   $data['prescribe_info'][1]['indication'];
                        $data['caution']            =   $data['prescribe_info'][1]['caution'];
                        $data['trade_name']         =   $data['prescribe_info'][1]['trade_name'];
                        
                        $ins_prescribe_array   =   array();
                        $ins_prescribe_array['medication_id']     = $data['queue_id'];
                        $ins_prescribe_array['patient_id']       = $data['patient_id'];
                        $ins_prescribe_array['staff_id']         = $_SESSION['staff_id'];
                        //$ins_prescribe_array['dispense_queue_id']= $data['dispense_queue_id'];
                        $ins_prescribe_array['prescript_queue_id']= $data['queue_id'];                        
                        $ins_prescribe_array['drug_formulary_id']= $data['drug_formulary_id'];                        
                        $ins_prescribe_array['dose']             = $data['dose'];
                        $ins_prescribe_array['dose_form']        = $data['dose_form'];
                        $ins_prescribe_array['frequency']        = $data['frequency'];
                        $ins_prescribe_array['instruction']      = $data['instruction'];
                        $ins_prescribe_array['quantity']         = $data['quantity'];
                        if(is_numeric($data['dose_duration'])){
                            $ins_prescribe_array['dose_duration']= $data['dose_duration'];
                        }
                        $ins_prescribe_array['quantity_form']    = $data['dose_form'];
                        $ins_prescribe_array['generic_drugname'] = $data['generic_name'];
                        $ins_prescribe_array['drug_tradename']   = $data['trade_name'];
                        $ins_prescribe_array['remarks']          = "";
                        $ins_prescribe_array['date_started']       = $data['date_ended']; // End of consultation
                        if($data['offline_mode']){
                            $ins_prescribe_array['synch_out']        = $data['now_id'];
                        }
                        //$ins_prescribe_array['drug_code_id']     = $data['drug_code_id']; // Will post to prescribe_queue_1 column
                        $ins_prescribe_data       =   $this->mehr_wdb->insert_new_medication_history($ins_prescribe_array);
                        
						$m++;
					}//while($l < count($data['prescribe_list']))					
				} //endif(count($data['prescribe_list']) > 0 )
				
                // Pop off from queue
                $data['queue_info'] = $this->mqueue_rdb->get_patients_queue($_SESSION['location_id'],$data['date_ended'],NULL,$data['patient_id']);
                //print_r($data['queue_info']);
                if(count($data['queue_info']) > 0){
                    //echo "Found in queue";
                    //print_r($data['queue_info']);
                    $upd_booking_array   =   array();
                    $upd_booking_array['booking_id']    =    $data['queue_info'][0]['booking_id'];
                    $upd_booking_array['status']        =    "Done";
                    $upd_booking_array['session_id']    =    $data['summary_id'];
                    $upd_upd_booking_array_data =   $this->mqueue_wdb->update_booking_post_consult($upd_booking_array);
                }

                // Copy patient_diagnosis to patient_medical_history
                // Billing
                // Set new appointment
            $new_page = base_url()."index.php/ehr_consult/close_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_episode') == FALSE)
    } // end of function consult_episode($id)


    // ------------------------------------------------------------------------
    // Categorised complaints form
    function edit_reason_encounter()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['complaintChapter'])) { 
                $data['complaintChapter']   =   $_POST['complaintChapter'];
            }
            if(isset($_POST['complaintCode'])) { 
                $data['complaintCode']   =   $_POST['complaintCode'];
            } else {
                $data['complaintCode']   =   "none";
            }
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['patient_id']     = $_POST['patient_id'];
            $data['summary_id']     = $_POST['summary_id'];
            $data['complaint_id']   = $_POST['complaint_id'];
            $data['duration']       = $_POST['duration'];
            $data['complaint_notes']= $_POST['complaint_notes'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['patient_id']     = $this->uri->segment(4);
            $data['summary_id']     = $this->uri->segment(5);
            $data['complaint_id']   = $this->uri->segment(6);
            $patient_id             =   $this->uri->segment(4);
            //$data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_complaints") {
                //echo "New diagnosis";
                $data['complaintChapter']   =   "none";
                $data['complaintCode']  =   "none";
                $data['duration']     =   "";
                $data['complaint_notes']    =   "";
                $data['complaint_id']   = "new_complaints";
            } elseif ($data['form_purpose'] == "edit_complaints") {
                //echo "Edit diagnosis";
                $data['complaints_info'] = $this->memr_rdb->get_patcon_complaints($data['summary_id'],$data['complaint_id']);
                $data['complaintChapter']   =   $data['complaints_info'][1]['complaint_chapter'];
                $data['complaintCode']      =   $data['complaints_info'][1]['icpc_code'];
                $data['duration']           =   $data['complaints_info'][1]['duration'];
                $data['complaint_notes']    =   $data['complaints_info'][1]['complaint_notes'];
            } //endif ($data['form_purpose'] == "new_diagnosis")
        } //endif(count($_POST))
		$data['title'] = "Reason for Encounter";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['complaints_list'] = $this->memr_rdb->get_patcon_complaints($data['summary_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;

        $data['ccode1_chapters'] = $this->memr_rdb->get_ccode_chapters();
		$data['ccode1_list'] = $this->memr_rdb->get_ccode1_by_chapter($data['complaintChapter']);
        $data['level3_list']    =   array();
        $data['level3_list'][0]['marker']      = "valid";  
        $data['level3_list'][0]['info']        = "N/A";  
        if(!empty($data['complaintCode']) ){
            $data['level3'] =   "valid";
        }

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_complaint') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/ehr_edit_reason_encounter_wap";
                $new_body   =   "ehr/ehr_edit_reason_encounter_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_reason_encounter_html";
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
            if($data['form_purpose'] == "new_complaints") {
                // New complaint record
                $ins_complaint_array   =   array();
                $ins_complaint_array['staff_id']           = $_SESSION['staff_id'];
                $ins_complaint_array['now_id']             = $data['now_id'];
                $ins_complaint_array['complaint_id']       = $data['now_id'];
                $ins_complaint_array['patient_id']         = $data['patient_id'];
                $ins_complaint_array['session_id']         = $data['summary_id'];
                $ins_complaint_array['adt_id']             = $data['summary_id'];
                $ins_complaint_array['icpc_code']          = $data['complaintCode'];
                $ins_complaint_array['duration']           = $data['duration'];
                $ins_complaint_array['complaint_notes']    = $data['complaint_notes'];
                $ins_complaint_array['ccode1ext_code']     = $data['complaintCode'];
                $ins_complaint_array['remarks']            = "THIRRA";//$data['remarks'];
                if($data['offline_mode']){
                    $ins_complaint_array['synch_out']        = $data['now_id'];
                }
	            $ins_complaint_data       =   $this->mconsult_wdb->insert_new_complaint($ins_complaint_array,$data['offline_mode']);
                $this->session->set_flashdata('data_activity', 'Complaint added.');
            } elseif($data['form_purpose'] == "edit_complaints") {
                // Existing complaint record
                $ins_complaint_array   =   array();
                $ins_complaint_array['staff_id']           = $_SESSION['staff_id'];
                $ins_complaint_array['now_id']             = $data['now_id'];
                $ins_complaint_array['complaint_id']       = $data['complaint_id'];
                $ins_complaint_array['patient_id']         = $data['patient_id'];
                $ins_complaint_array['session_id']         = $data['summary_id'];
                $ins_complaint_array['adt_id']             = $data['summary_id'];
                $ins_complaint_array['icpc_code']          = $data['complaintCode'];
                $ins_complaint_array['duration']           = $data['duration'];
                $ins_complaint_array['complaint_notes']    = $data['complaint_notes'];
                $ins_complaint_array['ccode1ext_code']     = $data['complaintCode'];
                $ins_complaint_array['remarks']            = "THIRRA";//$data['remarks'];
	            $ins_complaint_data       =   $this->mconsult_wdb->update_complaint($ins_complaint_array);
                $this->session->set_flashdata('data_activity', 'Complaint updated.');
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_diagnosis') == FALSE)


    } // end of function edit_reason_encounter()


    // ------------------------------------------------------------------------
    function consult_delete_complaint($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['patient_id']         =   $this->uri->segment(3);
        $data['summary_id']         =   $this->uri->segment(4);
        $data['complaint_id']       =   $this->uri->segment(5);
        
        // Delete records
        $del_rec_array['complaint_id']      = $data['complaint_id'];
        $del_rec_data =   $this->mconsult_wdb->consult_delete_complaint($del_rec_array);
        $this->session->set_flashdata('data_activity', 'Complaint deleted.');
        $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function consult_delete_complaint($id)


    // ------------------------------------------------------------------------
    function edit_vitals($summary_id = NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		//$this->load->library('form_validation');
        //$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$data['title'] = 'Vital Signs';
		$data['form_purpose']   = $this->uri->segment(3);
        //$data['patient_id']     = $this->uri->segment(4);
        //$data['summary_id']     = $this->uri->segment(5);
		//$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		//$data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
        //$data['patcon_info']  = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                   =   $_POST['now_id'];
            $data['now_date']                 =   date("Y-m-d",$data['now_id']);
            $data['init_patient_id']          =   $_POST['patient_id'];
            $data['patient_id']               =   $data['init_patient_id'];
            $data['summary_id']               =   $_POST['summary_id'];
            $data['init_vital_id']            =   $_POST['vital_id'];
            $data['vital_id']                 =   $data['init_vital_id'];
            $data['init_reading_date']        =   $_POST['reading_date'];
            $data['init_reading_time']        =   $_POST['reading_time'];
            $data['init_height']              =   trim($_POST['height']);
            $data['init_weight']              =   trim($_POST['weight']);
            $data['init_left_vision']         =   $_POST['left_vision'];
            $data['init_right_vision']        =   $_POST['right_vision'];
            $data['init_temperature']         =   trim($_POST['temperature']);
            $data['init_pulse_rate']          =   trim($_POST['pulse_rate']);
            $data['init_bmi']                 =   $_POST['bmi'];
            $data['init_waist_circumference'] =   trim($_POST['waist_circumference']);
            $data['init_bp_systolic']         =   trim($_POST['bp_systolic']);
            $data['init_bp_diastolic']        =   trim($_POST['bp_diastolic']);
            $data['init_respiration_rate']    =   trim($_POST['respiration_rate']);
            $data['init_ofc']                 =   trim($_POST['ofc']);
            $data['init_remarks']             =   $_POST['remarks'];
            
            if ($data['patient_id'] == "new_patient"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'VITAL SIGNS';
		        $data['patient_info']       = array();
            } else {
                // Edit form
          		$data['save_attempt']       = 'VITAL SIGNS';
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
            $patient_id                 =   $this->uri->segment(4);
            $data['patient_id']         =   $patient_id;
            $data['init_patient_id']    =   $patient_id;
            $data['summary_id']         = $this->uri->segment(5);
            $data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
            $data['patcon_info']  = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
            $data['vitals_info']  = $this->memr_rdb->get_patcon_vitals($data['summary_id']);

            if ($data['vitals_info']['vital_id'] == "new_vitals") {
                // New vitals
          		$data['save_attempt']            =   'ADD VITAL SIGNS';
	            //$data['vitals_info']             =   array();
                $data['init_vital_id']           =   $data['vitals_info']['vital_id'];
                $data['init_reading_date']       =   $data['now_date'];
                $data['init_reading_time']       =   $data['now_time'];
                $data['init_height']             =   NULL;
                $data['init_weight']             =   NULL;
                $data['init_left_vision']        =   NULL;
                $data['init_right_vision']       =   NULL;
                $data['init_temperature']        =   NULL;
                $data['init_pulse_rate']         =   NULL;
                $data['init_bmi']                =   NULL;
                $data['init_waist_circumference']=   NULL;
                $data['init_bp_systolic']        =   NULL;
                $data['init_bp_diastolic']       =   NULL;
                $data['init_respiration_rate']   =   NULL;
                $data['init_ofc']                =   NULL;
                $data['init_remarks']            =   NULL;
            } else {
                // Editing vitals
          		$data['save_attempt'] = 'EDIT VITAL SIGNS';
                $data['init_patient_id']         =   $data['patient_id'];
                $data['init_vital_id']           =   $data['vitals_info']['vital_id'];
                $data['init_reading_date']       =   $data['vitals_info']['reading_date'];
                $data['init_reading_time']       =   $data['vitals_info']['reading_time'];
                $data['init_height']             =   $data['vitals_info']['height'];
                $data['init_weight']             =   $data['vitals_info']['weight'];
                $data['init_left_vision']        =   $data['vitals_info']['left_vision'];
                $data['init_right_vision']       =   $data['vitals_info']['right_vision'];
                $data['init_temperature']        =   $data['vitals_info']['temperature'];
                $data['init_pulse_rate']         =   $data['vitals_info']['pulse_rate'];
                $data['init_bmi']                =   $data['vitals_info']['bmi'];
                $data['init_waist_circumference']=   $data['vitals_info']['waist_circumference'];
                $data['init_bp_systolic']        =   $data['vitals_info']['bp_systolic'];
                $data['init_bp_diastolic']       =   $data['vitals_info']['bp_diastolic'];
                $data['init_respiration_rate']   =   $data['vitals_info']['respiration_rate'];
                $data['init_ofc']                =   $data['vitals_info']['ofc'];
                $data['init_remarks']            =   $data['vitals_info']['remarks'];
            } //endif ($patient_id == "new_vitals")
        } //endif(count($_POST))

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_vitals') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/emr_edit_vitals_wap";
                $new_body   =   "ehr/ehr_edit_vitals_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_vitals_html";
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
            if($data['vital_id'] == "new_vitals") {
                // New patient vital signs
                $ins_vitals_array   =   array();
                $ins_vitals_array['staff_id']           = $_SESSION['staff_id'];
                $ins_vitals_array['now_id']             = $data['now_id'];
                $ins_vitals_array['vital_id']           = $data['now_id'];
                $ins_vitals_array['patient_id']         = $data['init_patient_id'];
                $ins_vitals_array['session_id']         = $data['summary_id'];
                $ins_vitals_array['adt_id']             = $data['summary_id'];
                $ins_vitals_array['reading_date']       = $data['init_reading_date'];
                $ins_vitals_array['reading_time']       = $data['init_reading_time'];
                if(is_numeric($data['init_height'])){
                    $ins_vitals_array['height']             = $data['init_height'];
                }
                if(is_numeric($data['init_weight'])){
                    $ins_vitals_array['weight']             = $data['init_weight'];
                }
                $ins_vitals_array['left_vision']        = $data['init_left_vision'];
                $ins_vitals_array['right_vision']       = $data['init_right_vision'];
                if(is_numeric($data['init_temperature'])){
                    $ins_vitals_array['temperature']        = $data['init_temperature'];
                }
                if(is_numeric($data['init_pulse_rate'])){
                    $ins_vitals_array['pulse_rate']         = $data['init_pulse_rate'];
                }
                if(is_numeric($data['init_bmi'])){
                    $ins_vitals_array['bmi']                = $data['init_bmi'];
                }
                if(is_numeric($data['init_waist_circumference'])){
                    $ins_vitals_array['waist_circumference']= $data['init_waist_circumference'];
                }
                if(is_numeric($data['init_bp_systolic'])){
                    $ins_vitals_array['bp_systolic']        = $data['init_bp_systolic'];
                }
                if(is_numeric($data['init_bp_diastolic'])){
                    $ins_vitals_array['bp_diastolic']       = $data['init_bp_diastolic'];
                }
                if(is_numeric($data['init_respiration_rate'])){
                    $ins_vitals_array['respiration_rate']   = $data['init_respiration_rate'];
                }
                if(is_numeric($data['init_ofc'])){
                    $ins_vitals_array['ofc']                = $data['init_ofc'];
                }
                $ins_vitals_array['remarks']            = $data['init_remarks'];
                if($data['offline_mode']){
                    $ins_vitals_array['synch_out']        = $data['now_id'];
                }
	            $ins_vitals_data       =   $this->mconsult_wdb->insert_new_vitals($ins_vitals_array);
                $this->session->set_flashdata('data_activity', 'Vital signs added.');
            } else {
                //Edit patient vital signs
                $ins_vitals_array   =   array();
                $ins_vitals_array['staff_id']           = $_SESSION['staff_id'];
                $ins_vitals_array['now_id']             = $data['now_id'];
                $ins_vitals_array['vital_id']           = $data['vital_id'];
                $ins_vitals_array['patient_id']         = $data['init_patient_id'];
                $ins_vitals_array['session_id']         = $data['summary_id'];
                $ins_vitals_array['adt_id']             = $data['summary_id'];
                $ins_vitals_array['reading_date']       = $data['init_reading_date'];
                $ins_vitals_array['reading_time']       = $data['init_reading_time'];
                if(is_numeric($data['init_height'])){
                    $ins_vitals_array['height']             = $data['init_height'];
                }
                if(is_numeric($data['init_weight'])){
                    $ins_vitals_array['weight']             = $data['init_weight'];
                }
                $ins_vitals_array['left_vision']        = $data['init_left_vision'];
                $ins_vitals_array['right_vision']       = $data['init_right_vision'];
                if(is_numeric($data['init_temperature'])){
                    $ins_vitals_array['temperature']        = $data['init_temperature'];
                }
                if(is_numeric($data['init_pulse_rate'])){
                    $ins_vitals_array['pulse_rate']         = $data['init_pulse_rate'];
                }
                if(is_numeric($data['init_bmi'])){
                    $ins_vitals_array['bmi']                = $data['init_bmi'];
                }
                if(is_numeric($data['init_waist_circumference'])){
                    $ins_vitals_array['waist_circumference']= $data['init_waist_circumference'];
                }
                if(is_numeric($data['init_bp_systolic'])){
                    $ins_vitals_array['bp_systolic']        = $data['init_bp_systolic'];
                }
                if(is_numeric($data['init_bp_diastolic'])){
                    $ins_vitals_array['bp_diastolic']       = $data['init_bp_diastolic'];
                }
                if(is_numeric($data['init_respiration_rate'])){
                    $ins_vitals_array['respiration_rate']   = $data['init_respiration_rate'];
                }
                if(is_numeric($data['init_ofc'])){
                    $ins_vitals_array['ofc']                = $data['init_ofc'];
                }
                $ins_vitals_array['remarks']            = $data['init_remarks'];
	            $ins_vitals_data       =   $this->mconsult_wdb->update_vitals($ins_vitals_array);
                $this->session->set_flashdata('data_activity', 'Vital signs updated.');
                
            } //endif($data['patient_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_vitals') == FALSE)
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_vitals()


    // ------------------------------------------------------------------------
    function edit_physical_exam($summary_id = NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		//$this->load->library('form_validation');
        //$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$data['title'] = 'Physical Examination';
		$data['form_purpose']   = $this->uri->segment(3);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['init_location_id']   =   $_SESSION['location_id'];
        $patient_id                 =   $this->uri->segment(4);
        $data['patient_id']         =   $patient_id;
        $data['init_patient_id']    =   $patient_id;
        $data['summary_id']         = $this->uri->segment(5);
        $data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']  = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
        $data['physical_info']  = $this->memr_rdb->get_patcon_physical($data['summary_id']);

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                     =   $_POST['now_id'];
            $data['now_date']                   =   date("Y-m-d",$data['now_id']);
            //$data['init_patient_id']          =   $_POST['patient_id'];
            //$data['patient_id']               =   $data['init_patient_id'];
            //$data['summary_id']               =   $_POST['summary_id'];
            $data['init_physical_exam_id']      =   $_POST['physical_exam_id'];
            $data['physical_exam_id']           =   $data['init_physical_exam_id'];
            $data['init_pulse_rate']            =   $_POST['pulse_rate'];
            if(isset($_POST['pulse_regular'])){
                $data['init_pulse_regular']         =   $_POST['pulse_regular'];
            } else {
                $data['init_pulse_regular']         =   NULL;
            }
            if(isset($_POST['pulse_regular_spec'])){
                $data['init_pulse_regular_spec']    =   $_POST['pulse_regular_spec'];
            } else {
                $data['init_pulse_regular_spec']         =   NULL;
            }
            if(isset($_POST['pulse_volume'])){
                $data['init_pulse_volume']          =   $_POST['pulse_volume'];
            } else {
                $data['init_pulse_volume']         =   NULL;
            }
            if(isset($_POST['pulse_volume_spec'])){
                $data['init_pulse_volume_spec']     =   $_POST['pulse_volume_spec'];
            } else {
                $data['init_pulse_volume_spec']         =   NULL;
            }
            if(isset($_POST['heart_rhythm'])){
                $data['init_heart_rhythm']          =   $_POST['heart_rhythm'];
            } else {
                $data['init_heart_rhythm']         =   NULL;
            }
            if(isset($_POST['heart_rhythm_spec'])){
                $data['init_heart_rhythm_spec']     =   $_POST['heart_rhythm_spec'];
            } else {
                $data['init_heart_rhythm_spec']         =   NULL;
            }
            if(isset($_POST['heart_murmur'])){
                $data['init_heart_murmur']          =   $_POST['heart_murmur'];
            } else {
                $data['init_heart_murmur']         =   NULL;
            }
            if(isset($_POST['heart_murmur_spec'])){
                $data['init_heart_murmur_spec']     =   $_POST['heart_murmur_spec'];
            } else {
                $data['init_heart_murmur_spec']         =   NULL;
            }
            if(isset($_POST['lung_clear'])){
                $data['init_lung_clear']            =   $_POST['lung_clear'];
            } else {
                $data['init_lung_clear']         =   NULL;
            }
            if(isset($_POST['lung_clear_spec'])){
                $data['init_lung_clear_spec']       =   $_POST['lung_clear_spec'];
            } else {
                $data['init_lung_clear_spec']         =   NULL;
            }
            $data['init_chest_measurement_in']  =   $_POST['chest_measurement_in'];
            $data['init_chest_measurement_out'] =   $_POST['chest_measurement_out'];
            if(isset($_POST['percussion'])){
                $data['init_percussion']            =   $_POST['percussion'];
            } else {
                $data['init_percussion']         =   NULL;
            }
            if(isset($_POST['percussion_spec'])){
                $data['init_percussion_spec']       =   $_POST['percussion_spec'];
            } else {
                $data['init_percussion_spec']         =   NULL;
            }
            $data['init_abdominal_girth']       =   $_POST['abdominal_girth'];
            if(isset($_POST['breasts'])){
                $data['init_breasts']            =   $_POST['breasts'];
            } else {
                $data['init_breasts']         =   NULL;
            }
            if(isset($_POST['breasts_spec'])){
                $data['init_breasts_spec']       =   $_POST['breasts_spec'];
            } else {
                $data['init_breasts_spec']         =   NULL;
            }
            if(isset($_POST['liver_palpable'])){
                $data['init_liver_palpable']        =   $_POST['liver_palpable'];
            } else {
                $data['init_liver_palpable']         =   NULL;
            }
            if(isset($_POST['liver_palpable_spec'])){
                $data['init_liver_palpable_spec']   =   $_POST['liver_palpable_spec'];
            } else {
                $data['init_liver_palpable_spec']         =   NULL;
            }
            if(isset($_POST['spleen_palpable'])){
                $data['init_spleen_palpable']       =   $_POST['spleen_palpable'];
            } else {
                $data['init_spleen_palpable']         =   NULL;
            }
            if(isset($_POST['spleen_palpable_spec'])){
                $data['init_spleen_palpable_spec']  =   $_POST['spleen_palpable_spec'];
            } else {
                $data['init_spleen_palpable_spec']         =   NULL;
            }
            if(isset($_POST['kidney_palpable'])){
                $data['init_kidney_palpable']       =   $_POST['kidney_palpable'];
            } else {
                $data['init_kidney_palpable']         =   NULL;
            }
            if(isset($_POST['kidney_palpable_spec'])){
                $data['init_kidney_palpable_spec']  =   $_POST['kidney_palpable_spec'];
            } else {
                $data['init_kidney_palpable_spec']         =   NULL;
            }
            if(isset($_POST['external_genitalia'])){
                $data['init_external_genitalia']    =   $_POST['external_genitalia'];
            } else {
                $data['init_external_genitalia']         =   NULL;
            }
            if(isset($_POST['external_genitalia_spec'])){
                $data['init_external_genitalia_spec']=   $_POST['external_genitalia_spec'];
            } else {
                $data['init_external_genitalia_spec']         =   NULL;
            }
            $data['init_perectal_exam']         =   $_POST['perectal_exam'];
            if(isset($_POST['hernial_orifices'])){
                $data['init_hernial_orifices']      =   $_POST['hernial_orifices'];
            } else {
                $data['init_hernial_orifices']         =   NULL;
            }
            if(isset($_POST['hernial_orifices_spec'])){
                $data['init_hernial_orifices_spec'] =   $_POST['hernial_orifices_spec'];
            } else {
                $data['init_hernial_orifices_spec']         =   NULL;
            }
            if(isset($_POST['pupils_equal'])){
                $data['init_pupils_equal']          =   $_POST['pupils_equal'];
            } else {
                $data['init_pupils_equal']         =   NULL;
            }
            if(isset($_POST['pupils_reactive'])){
                $data['init_pupils_reactive']       =   $_POST['pupils_reactive'];
            } else {
                $data['init_pupils_reactive']         =   NULL;
            }
            if(isset($_POST['reflexes'])){
                $data['init_reflexes']              =   $_POST['reflexes'];
            } else {
                $data['init_reflexes']         =   NULL;
            }
            $data['init_notes']                 =   $_POST['notes'];
            
            if ($data['form_purpose'] == "new_physical"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'ADD PHYSICAL EXAMINATION';
		        $data['patient_info']       = array();
            } else {
                // Edit form
          		$data['save_attempt']       = 'EDIT PHYSICAL EXAMINATION';
                // These fields were passed through as hidden tags
                $data['patient_id']         =   $data['init_patient_id']; //came from POST
		        $data['patient_info']       =   $this->memr_rdb->get_patient_demo($data['patient_id']);
                $data['init_patient_id']    =   $data['patient_info']['patient_id'];
                //$data['init_ic_other_no']   =   $data['patient_info']['ic_other_no'];
            } //endif ($form_purpose == "new_physical")

        } else {
            // First time form is displayed
            if ($data['physical_info']['physical_exam_id'] == "new_physical") {
                // New physical_exam
          		$data['save_attempt']               =   'ADD PHYSICAL EXAMINATION';
                $data['physical_exam_id']           =   $data['physical_info']['physical_exam_id'];
                $data['init_reading_date']          =   $data['now_date'];
                $data['init_reading_time']          =   $data['now_time'];
                $data['init_pulse_rate']            =   NULL;
                $data['init_pulse_regular']         =   NULL;
                $data['init_pulse_regular_spec']    =   NULL;
                $data['init_pulse_volume']          =   NULL;
                $data['init_pulse_volume_spec']     =   NULL;
                $data['init_heart_rhythm']          =   NULL;
                $data['init_heart_rhythm_spec']     =   NULL;
                $data['init_heart_murmur']          =   NULL;
                $data['init_heart_murmur_spec']     =   NULL;
                $data['init_lung_clear']            =   NULL;
                $data['init_lung_clear_spec']       =   NULL;
                $data['init_chest_measurement_in']  =   NULL;
                $data['init_chest_measurement_out'] =   NULL;
                $data['init_percussion']            =   NULL;
                $data['init_percussion_spec']       =   NULL;
                $data['init_abdominal_girth']       =   NULL;
                $data['init_liver_palpable']        =   NULL;
                $data['init_liver_palpable_spec']   =   NULL;
                $data['init_spleen_palpable']       =   NULL;
                $data['init_spleen_palpable_spec']  =   NULL;
                $data['init_kidney_palpable']       =   NULL;
                $data['init_kidney_palpable_spec']  =   NULL;
                $data['init_external_genitalia']    =   NULL;
                $data['init_external_genitalia_spec']=   NULL;
                $data['init_perectal_exam']         =   NULL;
                $data['init_hernial_orifices']      =   NULL;
                $data['init_hernial_orifices_spec'] =   NULL;
                $data['init_pupils_equal']          =   NULL;
                $data['init_pupils_reactive']       =   NULL;
                $data['init_reflexes']              =   NULL;
                $data['init_notes']                 =   NULL;
                $data['init_breasts']               =   NULL;
                $data['init_breasts_spec']          =   NULL;
            } else {
                // Editing physical_exam
          		$data['save_attempt'] = 'EDIT PHYSICAL EXAMINATION';
                $data['physical_exam_id']           =   $data['physical_info']['physical_exam_id'];
                $data['init_patient_id']            =   $data['patient_id'];
                $data['init_pulse_rate']            =   $data['physical_info']['pulse_rate'];
                $data['init_pulse_regular']         =   $data['physical_info']['pulse_regular'];
                $data['init_pulse_regular_spec']    =   $data['physical_info']['pulse_regular_spec'];
                $data['init_pulse_volume']          =   $data['physical_info']['pulse_volume'];
                $data['init_pulse_volume_spec']     =   $data['physical_info']['pulse_volume_spec'];
                $data['init_heart_rhythm']          =   $data['physical_info']['heart_rhythm'];
                $data['init_heart_rhythm_spec']     =   $data['physical_info']['heart_rhythm_spec'];
                $data['init_heart_murmur']          =   $data['physical_info']['heart_murmur'];
                $data['init_heart_murmur_spec']     =   $data['physical_info']['heart_murmur_spec'];
                $data['init_lung_clear']            =   $data['physical_info']['lung_clear'];
                $data['init_lung_clear_spec']       =   $data['physical_info']['lung_clear_spec'];
                $data['init_chest_measurement_in']  =   $data['physical_info']['chest_measurement_in'];
                $data['init_chest_measurement_out'] =   $data['physical_info']['chest_measurement_out'];
                $data['init_percussion']            =   $data['physical_info']['percussion'];
                $data['init_percussion_spec']       =   $data['physical_info']['percussion_spec'];
                $data['init_abdominal_girth']       =   $data['physical_info']['abdominal_girth'];
                $data['init_liver_palpable']        =   $data['physical_info']['liver_palpable'];
                $data['init_liver_palpable_spec']   =   $data['physical_info']['liver_palpable_spec'];
                $data['init_spleen_palpable']       =   $data['physical_info']['spleen_palpable'];
                $data['init_spleen_palpable_spec']  =   $data['physical_info']['spleen_palpable_spec'];
                $data['init_kidney_palpable']       =   $data['physical_info']['kidney_palpable'];
                $data['init_kidney_palpable_spec']  =   $data['physical_info']['kidney_palpable_spec'];
                $data['init_external_genitalia']    =   $data['physical_info']['external_genitalia'];
                $data['init_external_genitalia_spec']=   $data['physical_info']['external_genitalia_spec'];
                $data['init_perectal_exam']         =   $data['physical_info']['perectal_exam'];
                $data['init_hernial_orifices']      =   $data['physical_info']['hernial_orifices'];
                $data['init_hernial_orifices_spec'] =   $data['physical_info']['hernial_orifices_spec'];
                $data['init_pupils_equal']          =   $data['physical_info']['pupils_equal'];
                $data['init_pupils_reactive']       =   $data['physical_info']['pupils_reactive'];
                $data['init_reflexes']              =   $data['physical_info']['reflexes'];
                $data['init_notes']                 =   $data['physical_info']['notes'];
                $data['init_breasts']               =   $data['physical_info']['breasts'];
                $data['init_breasts_spec']          =   $data['physical_info']['breasts_spec'];
            } //endif ($patient_id == "new_physical_exam")
        } //endif(count($_POST))

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_physical_exam') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/emr_edit_vitals_wap";
                $new_body   =   "ehr/ehr_edit_physical_exam_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_physical_exam_html";
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
            if($data['physical_exam_id'] == "new_physical") {
                // New patient vital signs
                $ins_physical_array   =   array();
                $ins_physical_array['staff_id']             = $_SESSION['staff_id'];
                $ins_physical_array['now_id']               = $data['now_id'];
                $ins_physical_array['physical_exam_id']     = $data['now_id'];
                $ins_physical_array['patient_id']           = $data['init_patient_id'];
                $ins_physical_array['session_id']           = $data['summary_id'];
                $ins_physical_array['pulse_rate']           = $data['init_pulse_rate'];
                $ins_physical_array['pulse_regular']        = $data['init_pulse_regular'];
                $ins_physical_array['pulse_regular_spec']   = $data['init_pulse_regular_spec'];
                $ins_physical_array['pulse_volume']         = $data['init_pulse_volume'];
                $ins_physical_array['pulse_volume_spec']    = $data['init_pulse_volume_spec'];
                $ins_physical_array['heart_rhythm']         = $data['init_heart_rhythm'];
                $ins_physical_array['heart_rhythm_spec']    = $data['init_heart_rhythm_spec'];
                $ins_physical_array['heart_murmur']         = $data['init_heart_murmur'];
                $ins_physical_array['heart_murmur_spec']    = $data['init_heart_murmur_spec'];
                $ins_physical_array['lung_clear']           = $data['init_lung_clear'];
                $ins_physical_array['lung_clear_spec']      = $data['init_lung_clear_spec'];
                $ins_physical_array['chest_measurement_in'] = $data['init_chest_measurement_in'];
                $ins_physical_array['chest_measurement_out']= $data['init_chest_measurement_out'];
                $ins_physical_array['percussion']           = $data['init_percussion'];
                $ins_physical_array['percussion_spec']      = $data['init_percussion_spec'];
                $ins_physical_array['abdominal_girth']      = $data['init_abdominal_girth'];
                $ins_physical_array['liver_palpable']       = $data['init_liver_palpable'];
                $ins_physical_array['liver_palpable_spec']  = $data['init_liver_palpable_spec'];
                $ins_physical_array['spleen_palpable']      = $data['init_spleen_palpable'];
                $ins_physical_array['spleen_palpable_spec'] = $data['init_spleen_palpable_spec'];
                $ins_physical_array['kidney_palpable']      = $data['init_kidney_palpable'];
                $ins_physical_array['kidney_palpable_spec'] = $data['init_kidney_palpable_spec'];
                $ins_physical_array['external_genitalia']   = $data['init_external_genitalia'];
                $ins_physical_array['external_genitalia_spec']= $data['init_external_genitalia_spec'];
                $ins_physical_array['perectal_exam']        = $data['init_perectal_exam'];
                $ins_physical_array['hernial_orifices']     = $data['init_hernial_orifices'];
                $ins_physical_array['hernial_orifices_spec']= $data['init_hernial_orifices_spec'];
                $ins_physical_array['pupils_equal']         = $data['init_pupils_equal'];
                $ins_physical_array['pupils_reactive']      = $data['init_pupils_reactive'];
                $ins_physical_array['reflexes']             = $data['init_reflexes'];
                $ins_physical_array['notes']                = $data['init_notes'];
                $ins_physical_array['breasts']              = $data['init_breasts'];
                $ins_physical_array['breasts_spec']         = $data['init_breasts_spec'];
                if($data['offline_mode']){
                    $ins_physical_array['synch_out']        = $data['now_id'];
                }
	            $ins_physical_data       =   $this->mconsult_wdb->insert_new_physical_exam($ins_physical_array);
                $this->session->set_flashdata('data_activity', 'Physical examination added.');
            } else {
                //Edit patient vital signs
                $upd_physical_array   =   array();
                $upd_physical_array['staff_id']             = $_SESSION['staff_id'];
                $upd_physical_array['now_id']               = $data['now_id'];
                $upd_physical_array['physical_exam_id']     = $data['physical_exam_id'];
                $upd_physical_array['pulse_rate']           = $data['init_pulse_rate'];
                $upd_physical_array['pulse_regular']        = $data['init_pulse_regular'];
                $upd_physical_array['pulse_regular_spec']   = $data['init_pulse_regular_spec'];
                $upd_physical_array['pulse_volume']         = $data['init_pulse_volume'];
                $upd_physical_array['pulse_volume_spec']    = $data['init_pulse_volume_spec'];
                $upd_physical_array['heart_rhythm']         = $data['init_heart_rhythm'];
                $upd_physical_array['heart_rhythm_spec']    = $data['init_heart_rhythm_spec'];
                $upd_physical_array['heart_murmur']         = $data['init_heart_murmur'];
                $upd_physical_array['heart_murmur_spec']    = $data['init_heart_murmur_spec'];
                $upd_physical_array['lung_clear']           = $data['init_lung_clear'];
                $upd_physical_array['lung_clear_spec']      = $data['init_lung_clear_spec'];
                $upd_physical_array['chest_measurement_in'] = $data['init_chest_measurement_in'];
                $upd_physical_array['chest_measurement_out']= $data['init_chest_measurement_out'];
                $upd_physical_array['percussion']           = $data['init_percussion'];
                $upd_physical_array['percussion_spec']      = $data['init_percussion_spec'];
                $upd_physical_array['abdominal_girth']      = $data['init_abdominal_girth'];
                $upd_physical_array['liver_palpable']       = $data['init_liver_palpable'];
                $upd_physical_array['liver_palpable_spec']  = $data['init_liver_palpable_spec'];
                $upd_physical_array['spleen_palpable']      = $data['init_spleen_palpable'];
                $upd_physical_array['spleen_palpable_spec'] = $data['init_spleen_palpable_spec'];
                $upd_physical_array['kidney_palpable']      = $data['init_kidney_palpable'];
                $upd_physical_array['kidney_palpable_spec'] = $data['init_kidney_palpable_spec'];
                $upd_physical_array['external_genitalia']   = $data['init_external_genitalia'];
                $upd_physical_array['external_genitalia_spec']= $data['init_external_genitalia_spec'];
                $upd_physical_array['perectal_exam']        = $data['init_perectal_exam'];
                $upd_physical_array['hernial_orifices']     = $data['init_hernial_orifices'];
                $upd_physical_array['hernial_orifices_spec']= $data['init_hernial_orifices_spec'];
                $upd_physical_array['pupils_equal']         = $data['init_pupils_equal'];
                $upd_physical_array['pupils_reactive']      = $data['init_pupils_reactive'];
                $upd_physical_array['reflexes']             = $data['init_reflexes'];
                $upd_physical_array['notes']                = $data['init_notes'];
                $upd_physical_array['breasts']              = $data['init_breasts'];
                $upd_physical_array['breasts_spec']         = $data['init_breasts_spec'];
                //if(is_numeric($data['init_ofc'])){
                    //$upd_physical_array['ofc']                = $data['init_ofc'];
                //}
	            $upd_physical_data       =   $this->mconsult_wdb->update_physical_exam($upd_physical_array);
                $this->session->set_flashdata('data_activity', 'Physical examination updated.');
                
            } //endif($data['patient_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_physical_exam') == FALSE)
    } //end of function edit_physical_exam()


    // ------------------------------------------------------------------------
    // Add/Edit Referral Letter
    function edit_referral()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('madmin_rdb');
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['referral_center_id'])) { 
                $data['referral_center_id']   =   $_POST['referral_center_id'];
                $data['referral_info'] = $this->madmin_rdb->get_referral_centres($data['referral_center_id']);
            }
            if(isset($_POST['referral_doctor_id'])) { 
                $data['referral_doctor_id']   =   $_POST['referral_doctor_id'];
                $data['person_info'] = $this->madmin_rdb->get_referral_persons($data['referral_center_id'],$data['referral_doctor_id']);
            } else {
                $data['referral_doctor_id']   =   "none";
            }
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['patient_id']         = $_POST['patient_id'];
            $data['summary_id']         = $_POST['summary_id'];
            $data['referral_id']        = $_POST['referral_id'];
            $data['init_referral_date'] = $_POST['referral_date'];
            $data['init_reason']        = $_POST['reason'];
            $data['init_clinical_exam'] = $_POST['clinical_exam'];
            $data['init_referral_reference'] = $_POST['referral_reference'];
        } else {
            // First time form is displayed
            $data['form_purpose']   = $this->uri->segment(3);
            $data['patient_id']     = $this->uri->segment(4);
            $data['summary_id']     = $this->uri->segment(5);
            $data['referral_id']    = $this->uri->segment(6);
            $patient_id             =   $this->uri->segment(4);
            //$data['patient_id']     =   $patient_id;
            if ($data['form_purpose'] == "new_referral") {
                //echo "New referral";
                $data['referral_center_id'] =   "none";
                $data['referral_doctor_id'] =   "none";
                $data['init_referral_date']      =   $data['now_date'];
                $data['init_reason']      =   "";
                $data['init_clinical_exam']      =   "";
                $data['init_referral_reference']      =   "";
                $data['referral_id']       = "new_referral";
            } elseif ($data['form_purpose'] == "edit_referral") {
                //echo "Edit referral";
                $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
                $data['init_referral_center_id']=   $data['referral_info'][0]['referral_center_id'];
                $data['referral_center_id']     =   $data['init_referral_center_id'];
                $data['init_referral_doctor_id']=   $data['referral_info'][0]['referral_doctor_id'];
                $data['referral_doctor_id']     =   $data['init_referral_doctor_id'];
                $data['init_reason']            =   $data['referral_info'][0]['reason'];
                $data['init_referral_date']     =   $data['referral_info'][0]['referral_date'];
                $data['init_clinical_exam']     =   $data['referral_info'][0]['clinical_exam'];
                $data['init_referral_reference']=   $data['referral_info'][0]['referral_reference'];
                $data['person_info'] = $this->madmin_rdb->get_referral_persons($data['referral_center_id'],$data['referral_doctor_id']);
            } //endif ($data['form_purpose'] == "new_diagnosis")
        } //endif(count($_POST))
		$data['title'] = "Referral";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id']);
        $data['referrals_list'] = $this->memr_rdb->get_patcon_referrals($data['summary_id']);
        //$data['init_patient_id']    =   $patient_id;

        $data['centres_list'] = $this->madmin_rdb->get_referral_centres();
		$data['persons_list'] = $this->madmin_rdb->get_referral_persons($data['referral_center_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_referral') == FALSE){
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                //$new_body   =   "ehr/emr_edit_referral_wap";
                $new_body   =   "ehr/ehr_edit_referral_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
                $new_body   =   "ehr/ehr_edit_referral_html";
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

            if($data['form_purpose'] == "new_referral") {
                // New referral record
                $ins_referral_array   =   array();
                $ins_referral_array['staff_id']           = $_SESSION['staff_id'];
                $ins_referral_array['now_id']             = $data['now_id'];
                $ins_referral_array['referral_id']        = $data['now_id'];
                $ins_referral_array['patient_id']         = $data['patient_id'];
                $ins_referral_array['session_id']         = $data['summary_id'];
                $ins_referral_array['referral_doctor_id'] = $data['referral_doctor_id'];
                $ins_referral_array['referral_doctor_name']= $data['person_info'][0]['doctor_name'];
                $ins_referral_array['referral_specialty'] = $data['person_info'][0]['specialty'];
                $ins_referral_array['referral_centre']    = $data['person_info'][0]['centre_name'];
                $ins_referral_array['referral_date']      = $data['init_referral_date'];
                $ins_referral_array['reason']             = $data['init_reason'];
                $ins_referral_array['clinical_exam']      = $data['init_clinical_exam'];
                //$ins_referral_array['history_attached']   = $data['init_history_attached'];
                $ins_referral_array['referral_sequence']  = 0;
                $ins_referral_array['referral_reference'] = $data['init_referral_reference'];
                $ins_referral_array['remarks']            = "THIRRA";//$data['remarks'];
                if($data['offline_mode']){
                    $ins_referral_array['synch_out']        = $data['now_id'];
                }
	            $ins_referral_data       =   $this->mconsult_wdb->insert_new_referral($ins_referral_array);
                $this->session->set_flashdata('data_activity', 'Referral added.');
            } elseif($data['form_purpose'] == "edit_referral") {
                // Edit referral record
                $upd_referral_array   =   array();
                $upd_referral_array['staff_id']           = $_SESSION['staff_id'];
                $upd_referral_array['now_id']             = $data['now_id'];
                $upd_referral_array['referral_id']        = $data['referral_id'];
                $upd_referral_array['patient_id']         = $data['patient_id'];
                $upd_referral_array['session_id']         = $data['summary_id'];
                $upd_referral_array['referral_doctor_id'] = $data['referral_doctor_id'];
                $upd_referral_array['referral_doctor_name']= $data['person_info'][0]['doctor_name'];
                $upd_referral_array['referral_specialty'] = $data['person_info'][0]['specialty'];
                $upd_referral_array['referral_centre']    = $data['person_info'][0]['centre_name'];
                $upd_referral_array['referral_date']      = $data['init_referral_date'];
                $upd_referral_array['reason']             = $data['init_reason'];
                $upd_referral_array['clinical_exam']      = $data['init_clinical_exam'];
                //$ins_referral_array['history_attached']   = $data['init_history_attached'];
                $upd_referral_array['referral_sequence']  = 0;
                $upd_referral_array['referral_reference'] = $data['init_referral_reference'];
                $upd_referral_array['remarks']            = "THIRRA";//$data['remarks'];
                if($data['offline_mode']){
                    $upd_referral_array['synch_out']        = $data['now_id'];
                }
	            $upd_referral_data       =   $this->mconsult_wdb->update_consult_referral($upd_referral_array);
                $this->session->set_flashdata('data_activity', 'Referral updated.');
            } //endif($data['form_purpose'] == "new_referral")
            $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_referral') == FALSE)


    } // end of function edit_referral()


    // ------------------------------------------------------------------------
    function upload_pics_con($patient_id=NULL)
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
        $data['pics_url']           =    substr_replace($data['pics_url'],'',-1);
        $data['pics_url']           =    $data['pics_url']."-uploads/";
        $data['now_id']             =   time();
        $data['patient_info']       =   $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['upload_type']        =   "";
        $data['rotate_pic']         =   "";
        $data['new_width']          = 600; // Resize portraits
        //$new_width      = 600;
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
                $new_banner =   "ehr/banner_ehr_conslt_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
                $new_body   =   "ehr/ehr_edit_upload_con_html";
                $new_footer =   "ehr/footer_emr_wap";
		    } else {
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_conslt_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
		        //$this->load->view('bio/bio/header_xhtml1-strict');
                $new_body   =   "ehr/ehr_edit_upload_con_html";
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
            redirect('ehr_consult/upload_pics_con/new_file/'.$data['patient_id'].'/'.$data['summary_id'].'/new_referral','refresh');
            /*
                echo "<pre>";
                print_r($upload_data);
                $this->load->view('test_upload', $error);
                echo "</pre>";
            */
	    }
        // end of file upload section
           
    } // end of function upload_pics_con($patient_id=NULL)


    // ------------------------------------------------------------------------
    function con_externalmod($summary_id = NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		//$this->load->library('form_validation');
        //$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$data['title'] = 'Modules';
        $data['now_id']         =   time();
		$data['form_purpose']   = $this->uri->segment(3);
		$data['current_db']		= $this->db->database; 		
		$data['staff_id']       = $_SESSION['staff_id'];
        $data['patient_id']     = $this->uri->segment(4);
        $data['summary_id']     = $this->uri->segment(5);
		//$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
		$data['broken_birth_date'] =   $this->break_date($data['patient_id']['birth_date']);
		$data['patient_birthstamp']	= mktime(0,0,0,$data['broken_birth_date']['mm'],$data['broken_birth_date']['dd'],$data['broken_birth_date']['yyyy']);
        //$data['patcon_info']  = $this->memr_rdb->get_patcon_details($data['patient_id']);
		$data['modules_list']   = $this->memr_rdb->get_externalmod_list('episode');


		$this->load->vars($data);
        // Run validation
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
			$new_header =   "ehr/header_xhtml-mobile10";
			$new_banner =   "ehr/banner_ehr_conslt_wap";
			$new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
			$new_body   =   "ehr/ehr_indv_con_externalmod_html";
			$new_footer =   "ehr/footer_emr_wap";
		} else {
			//$new_header =   "ehr/header_xhtml1-strict";
			$new_header =   "ehr/header_xhtml1-transitional";
			$new_banner =   "ehr/banner_ehr_conslt_html";
			$new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
			$new_body   =   "ehr/ehr_indv_con_externalmod_html";
			$new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			

    } //end of function con_externalmod()


	// ------------------------------------------------------------------------
    function close_episode($id=NULL)  // last page of consultation
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['title'] = "T H I R R A - Close";
        $data['patient_id'] = $this->uri->segment(3);
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        if($data['debug_mode']){
            $this->output->enable_profiler(TRUE);  
        }
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_conslt_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
            $new_body   =   "ehr/emr_close_episode_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_conslt_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
            $new_body   =   "ehr/ehr_close_episode_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		//$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function close_episode($id)


    // ------------------------------------------------------------------------
    function consult_delete_session($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['patient_id']         =   $this->uri->segment(3);
        $data['summary_id']         =   $this->uri->segment(4);
        
        // Delete records
        $del_rec_array['summary_id']      = $data['summary_id'];
        $del_rec_data =   $this->mconsult_wdb->consult_delete_session($del_rec_array);
        $this->session->set_flashdata('data_activity', 'Prescription deleted.');
        
        echo "<br />Session deleted.<br />";
        
        $new_page = base_url()."index.php/ehr_consult/consult_episode/".$data['patient_id']."/".$data['summary_id'];
        //header("Status: 200");
        //header("Location: ".$new_page);
        
    } // end of function consult_delete_session($id)


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
