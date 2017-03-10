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
 * Portions created by the Initial Developer are Copyright (C) 2011
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

/**
 * Controller Class for EHR_INDIVIDUAL_REFER
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.13
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_individual_refer extends MY_Controller 
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
        $this->load->helper('xml');
        $data['app_language']		    =	$this->config->item('app_language');
        $this->lang->load('ehr', $data['app_language']);
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->load->model('memr_rdb');
		$this->load->model('mrefer_wdb');
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
    function list_referral_out()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['patient_id']     = $this->uri->segment(3);
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['referrals_list'] = $this->memr_rdb->get_history_referrals('Consulted','List',$data['patient_id']);
        
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            $new_body   =   "ehr/ehr_indv_list_referral_out_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_list_referral_out_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);		
		
    } // end of function list_referral_out()


    // ------------------------------------------------------------------------
    /**
     * Form to record referral replies
     *
     * User can also use this page to transmit referral data to external server.
     *
     * @author  Jason Tan Boon Teck
     */
    function edit_referral_out()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		//$this->load->model('morders_wdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']       = $this->uri->segment(3);
		$data['patient_id']         = $this->uri->segment(4);
		$data['summary_id']         = $this->uri->segment(5);
		$data['referral_id']        = $this->uri->segment(6);
        $data['location_id']    	=   $_SESSION['location_id'];
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['substring_length']   =   15;
        
        if(count($_POST)) {
            // User has posted the form
            $data['init_date_replied']      =   $this->input->post('date_replied');
            $data['init_replying_doctor']   =   $this->input->post('replying_doctor');
            $data['init_replying_specialty']=   $this->input->post('replying_specialty');
            $data['init_replying_centre']   =   $this->input->post('replying_centre');
            $data['init_department']      	=   $this->input->post('department');
            $data['init_findings']      	=   $this->input->post('findings');
            $data['init_investigation']     =   $this->input->post('investigation');
            $data['init_diagnosis']      	=   $this->input->post('diagnosis');
            $data['init_treatment']      	=   $this->input->post('treatment');
            $data['init_plan']      	    =   $this->input->post('plan');
            $data['init_comments']      	=   $this->input->post('comments');
            $data['close_loop']      	    =   $this->input->post('close_loop');
			// Static information
            $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
            $data['referral_doctor_id'] 	=   $data['referral_info'][0]['referral_doctor_id'];
            $data['referral_doctor_name'] 	=   $data['referral_info'][0]['referral_doctor_name'];
            $data['referral_specialty']   	=   $data['referral_info'][0]['referral_specialty'];
            $data['referral_centre']   	    =   $data['referral_info'][0]['referral_centre'];
            $data['thirra_url'] 		    =   $data['referral_info'][0]['thirra_url'];
            $data['referral_date']   		=   $data['referral_info'][0]['referral_date'];
            $data['referral_reference'] 	=   $data['referral_info'][0]['referral_reference'];
            $data['reason'] 		        =   $data['referral_info'][0]['reason'];
            $data['clinical_exam']  	    =   $data['referral_info'][0]['clinical_exam'];
            $data['referred_by'] 		    =   $data['referral_info'][0]['staff_name'];
            if(isset($_POST['close_loop'])) { 
				$data['close_loop']  			=   $_POST['close_loop'];//TRUE;
			} else {
				$data['close_loop']  			=   "FALSE";				
			}
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_refer") {
                // New user
		        $data['room_info']          =  array();
                $data['room_id']            =   "";
                $data['category_id']        =   "";
                $data['init_room_name']     =   "";
                $data['init_description']   =   "";
            } else {
                // Existing result row
                $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
                $data['referral_doctor_id'] 	=   $data['referral_info'][0]['referral_doctor_id'];
                $data['referral_doctor_name'] 	=   $data['referral_info'][0]['referral_doctor_name'];
                $data['referral_specialty']   	=   $data['referral_info'][0]['referral_specialty'];
                $data['doctor_email']   	    =   $data['referral_info'][0]['doctor_email'];
                $data['referral_centre']   	    =   $data['referral_info'][0]['referral_centre'];
                $data['thirra_url'] 		    =   $data['referral_info'][0]['thirra_url'];
                $data['referral_date']   		=   $data['referral_info'][0]['referral_date'];
                $data['referral_reference'] 	=   $data['referral_info'][0]['referral_reference'];
                $data['reason'] 		        =   $data['referral_info'][0]['reason'];
                $data['clinical_exam']  	    =   $data['referral_info'][0]['clinical_exam'];
                $data['referred_by'] 		    =   $data['referral_info'][0]['staff_name'];
                $data['init_date_replied'] 		=   $data['referral_info'][0]['date_replied'];
                $data['init_replying_doctor'] 	=   $data['referral_info'][0]['replying_doctor'];
                $data['init_replying_specialty']=   $data['referral_info'][0]['replying_specialty'];
                if(empty($data['referral_info'][0]['replying_centre'])){
                    $data['init_replying_centre']   =   $data['referral_info'][0]['referral_centre'];
                } else {
                    $data['init_replying_centre']   =   $data['referral_info'][0]['replying_centre'];
                }
                $data['init_department'] 		=   $data['referral_info'][0]['department'];
                $data['init_findings'] 		    =   $data['referral_info'][0]['findings'];
                $data['init_investigation'] 	=   $data['referral_info'][0]['investigation'];
                $data['init_diagnosis'] 		=   $data['referral_info'][0]['diagnosis'];
                $data['init_treatment'] 		=   $data['referral_info'][0]['treatment'];
                $data['init_plan'] 		        =   $data['referral_info'][0]['plan'];
                $data['init_comments'] 		        =   $data['referral_info'][0]['comments'];
          } //endif ($data['form_purpose'] == "new_refer")
        } //endif(count($_POST))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_referout_response') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                //$new_body   =   "ehr/ehr_orders_edit_imagresult_wap";
                $new_body   =   "ehr/ehr_indv_edit_refer_out_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_indv_edit_refer_out_html";
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
            $upd_response_array   =   array();
            if($data['close_loop'] == "TRUE") {
                //echo "Change status ".$data['close_loop'];
				$upd_response_array['referral_status']  	= "Closed";
           }
			// Update records
			$upd_response_array['referral_id']      = $data['referral_id'];
			$upd_response_array['date_replied']     = $data['init_date_replied'];
			$upd_response_array['replying_doctor']  = $data['init_replying_doctor'];
			$upd_response_array['replying_specialty']= $data['init_replying_specialty'];
			$upd_response_array['replying_centre']  = $data['init_replying_centre'];
			$upd_response_array['department']  		= $data['init_department'];
			$upd_response_array['findings']  		= $data['init_findings'];
			$upd_response_array['investigation']  	= $data['init_investigation'];
			$upd_response_array['diagnosis']  		= $data['init_diagnosis'];
			$upd_response_array['treatment']  		= $data['init_treatment'];
			$upd_response_array['plan']  		    = $data['init_plan'];
			$upd_response_array['comments']  		= $data['init_comments'];
			$upd_response_array['reply_recorder']   = $_SESSION['staff_id'];
			$upd_response_array['date_recorded']  	= $data['now_date'];
            if($data['offline_mode']){
                $upd_response_array['synch_out']    = $data['now_id'];
            }
			$upd_response_data =   $this->mrefer_wdb->update_refer_out_reply($upd_response_array);
            $this->session->set_flashdata('data_activity', 'Referral reply updated.');
            $new_page = base_url()."index.php/ehr_individual_refer/list_referral_out/".$data['patient_id'];
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_referout_response') == FALSE)
    } // end of function edit_referral_out()


    // ------------------------------------------------------------------------
    // Print Referral Letter
    function print_referral_letter()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_name']		    =	$this->config->item('app_name');
		$this->load->model('madmin_rdb');
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
	  	
		// First time form is displayed
		$data['form_country']   = $this->uri->segment(3);
		$data['patient_id']     = $this->uri->segment(4);
		$data['summary_id']     = $this->uri->segment(5);
		$data['referral_id']    = $this->uri->segment(6);
        $data['output_format'] 	= $this->uri->segment(7);
		//$data['patient_id']     =   $patient_id;
		//echo "Edit referral";
		$data['title'] = "Referral";
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
        $data['referrals_list'] = $this->memr_rdb->get_patcon_referrals($data['summary_id']);
		$data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
		$data['clinic_info']    = $this->mthirra->get_clinic_info($data['patcon_info']['location_start']);
		$data['init_referral_center_id']=   $data['referral_info'][0]['referral_center_id'];
		$data['referral_center_id']     =   $data['init_referral_center_id'];
		$data['init_referral_doctor_id']=   $data['referral_info'][0]['referral_doctor_id'];
		$data['referral_doctor_id']     =   $data['init_referral_doctor_id'];
		$data['init_reason']            =   $data['referral_info'][0]['reason'];
		$data['init_referral_date']     =   $data['referral_info'][0]['referral_date'];
		$data['init_clinical_exam']     =   $data['referral_info'][0]['clinical_exam'];
		$data['init_referral_reference']=   $data['referral_info'][0]['referral_reference'];
		//$data['person_info'] = $this->madmin_rdb->get_referral_persons($data['referral_center_id'],$data['referral_doctor_id']);

        //$data['centres_list'] = $this->madmin_rdb->get_referral_centres();
		//$data['persons_list'] = $this->madmin_rdb->get_referral_persons($data['referral_center_id']);

		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
			$new_header =   "ehr/header_xhtml-mobile10";
			$new_banner =   "ehr/banner_ehr_conslt_wap";
			$new_sidebar=   "ehr/sidebar_ehr_patients_conslt_wap";
			$new_body   =   "ehr/ehr_print_referral_letter_html";
			$new_footer =   "ehr/footer_emr_wap";
		} else {
			//$new_header =   "ehr/header_xhtml1-strict";
			$new_header =   "ehr/header_xhtml1-transitional";
			$new_banner =   "ehr/banner_ehr_print_html";
			$new_sidebar=   "ehr/sidebar_ehr_patients_conslt_html";
			$new_body   =   "ehr/ehr_print_referral_letter_html";
			$new_footer =   "ehr/footer_emr_html";
		}
        // Present body of letter according to country
        switch ($data['form_country']){
            /*
            case "MY":
                $new_body   =   "ehr/ehr_print_referral_letter_MY_html";
                break;			
            case "NP":
                $new_body   =   "ehr/ehr_print_referral_letter_NP_html";
                break;		
            */
            case "PH":
                $new_banner =   "ehr/banner_ehr_print_noletterhead_html";
                $new_body   =   "ehr/ehr_print_referral_letter_PH_html";
                break;
            
        } //end switch ($_SESSION['thirra_mode'])
        
        // Output Format
        $patient_name   =   str_replace(" ","", $data['patient_info']['name']);
        $app_name       =   str_replace(" ","",$data['app_name']);
        $consult_start  =   str_replace("-","",$data['patcon_info']['date_started']);
        $consult_time   =   substr(str_replace(":","",$data['patcon_info']['time_started']),0,4);
        $data['filename']		=	$app_name."_RL-".$patient_name."-".$consult_start."-".$consult_time.".pdf";
        if($data['output_format'] == 'pdf') {
            $html = $this->load->view($new_header,'',TRUE);			
            $html .= $this->load->view($new_banner,'',TRUE);			
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
//$mpdf->AddPage();
//$mpdf->WriteHTML('Your Book text');

            $mpdf->Output($data['filename'],'I'); exit;
        } else { // display in browser
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            //$this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);		
        } //endif($data['output_format'] == 'pdf')
    } // end of function print_referral_letter()


    // ------------------------------------------------------------------------
    /**
     * Form to draft e-mail to notify referral
     *
     * User can also use this page to transmit referral notice to recipient.
     *
     * @author  Jason Tan Boon Teck
     */
    function draft_notify_email()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		//$this->load->model('morders_wdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']       = $this->uri->segment(3);
		$data['patient_id']         = $this->uri->segment(4);
		$data['summary_id']         = $this->uri->segment(5);
		$data['referral_id']        = $this->uri->segment(6);
        $data['location_id']    	=   $_SESSION['location_id'];
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_name']       = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        // Should be location_ended
		$data['clinic_info']        = $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $data['from_name'] 		        =   $data['clinic_info']['clinic_name'];
        $data['from_email'] 		    =   $data['clinic_info']['email'];
        
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose'] = "edit_draft";
            $data['button_preview']      	=   $this->input->post('button_preview');
            $data['button_send']      	    =   $this->input->post('button_send');
            $data['init_date_replied']      =   $this->input->post('date_replied');
            $data['init_replying_doctor']   =   $this->input->post('replying_doctor');
            $data['init_replying_specialty']=   $this->input->post('replying_specialty');
            $data['init_replying_centre']   =   $this->input->post('replying_centre');
            $data['init_department']      	=   $this->input->post('department');
            $data['init_findings']      	=   $this->input->post('findings');
            $data['referral_email']      	=   $this->input->post('referral_email');
            $data['cc_name']                =   $this->input->post('cc_name');
            $data['cc_email']               =   $this->input->post('cc_email');
            $data['email_subject']          =   $this->input->post('email_subject');
            $data['email_salutation']       =   $this->input->post('email_salutation');
            $data['email_paragraph1']      	=   $this->input->post('email_paragraph1');
            $data['email_paragraph2']      	=   $this->input->post('email_paragraph2');
            $data['email_conclusion']      	=   $this->input->post('email_conclusion');
            $data['close_loop']      	    =   $this->input->post('close_loop');
			// Static information
            $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
            $data['referout_doctor_name'] 	=   $data['referral_info'][0]['staff_name'];
            $data['referral_doctor_id'] 	=   $data['referral_info'][0]['referral_doctor_id'];
            $data['referral_doctor_name'] 	=   $data['referral_info'][0]['referral_doctor_name'];
            $data['referral_specialty']   	=   $data['referral_info'][0]['referral_specialty'];
            $data['referral_centre']   	    =   $data['referral_info'][0]['referral_centre'];
            $data['thirra_url'] 		    =   $data['referral_info'][0]['thirra_url'];
            $data['referral_date']   		=   $data['referral_info'][0]['referral_date'];
            $data['referral_reference'] 	=   $data['referral_info'][0]['referral_reference'];
            $data['reason'] 		        =   $data['referral_info'][0]['reason'];
            $data['clinical_exam']  	    =   $data['referral_info'][0]['clinical_exam'];
            if(isset($_POST['close_loop'])) { 
				$data['close_loop']  			=   $_POST['close_loop'];//TRUE;
			} else {
				$data['close_loop']  			=   "FALSE";				
			}
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_draft") {
                // New user
		        $data['room_info']          =  array();
                $data['room_id']            =   "";
            } else {
                // Existing result row
            } //endif ($data['form_purpose'] == "new_refer")
            $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
            $data['referout_doctor_name'] 	=   $data['referral_info'][0]['staff_name'];
            $data['referral_doctor_id'] 	=   $data['referral_info'][0]['referral_doctor_id'];
            $data['referral_doctor_name'] 	=   $data['referral_info'][0]['referral_doctor_name'];
            $data['referral_specialty']   	=   $data['referral_info'][0]['referral_specialty'];
            $data['referral_email']   	    =   $data['referral_info'][0]['doctor_email'];
            $data['referral_centre']   	    =   $data['referral_info'][0]['referral_centre'];
            $data['cc_name'] 		        =   NULL;
            $data['cc_email'] 		        =   NULL;
            $data['thirra_url'] 		    =   $data['referral_info'][0]['thirra_url'];
            $data['referral_date']   		=   $data['referral_info'][0]['referral_date'];
            $data['referral_reference'] 	=   $data['referral_info'][0]['referral_reference'];
            $data['referral_date'] 		    =   $data['referral_info'][0]['referral_date'];
            $data['reason'] 		        =   $data['referral_info'][0]['reason'];
            $data['clinical_exam']  	    =   $data['referral_info'][0]['clinical_exam'];
            $data['init_date_replied'] 		=   $data['referral_info'][0]['date_replied'];
            $data['init_replying_doctor'] 	=   $data['referral_info'][0]['replying_doctor'];
            $data['init_replying_specialty']=   $data['referral_info'][0]['replying_specialty'];
            if(empty($data['referral_info'][0]['replying_centre'])){
                $data['init_replying_centre']   =   $data['referral_info'][0]['referral_centre'];
            } else {
                $data['init_replying_centre']   =   $data['referral_info'][0]['replying_centre'];
            }
            $data['init_department'] 		=   $data['referral_info'][0]['department'];
            $data['init_findings'] 		    =   $data['referral_info'][0]['findings'];
        } //endif(count($_POST))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_referout_email') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_ovrvw_wap";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
                //$new_body   =   "ehr/ehr_orders_edit_imagresult_wap";
                $new_body   =   "ehr/ehr_indv_refer_email_draft_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_ovrvw_html";
                $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
                $new_body   =   "ehr/ehr_indv_refer_email_draft_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_orders'] < 100){
                $new_body   =   "ehr/ehr_access_denied_html";
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
            //echo "<br />Insert record";
            $upd_email_array   =   array();
            if($data['close_loop'] == "TRUE") {
                //echo "Change status ".$data['close_loop'];
				$upd_email_array['referral_status']  	= "Closed";
            }
			// Update records
			$upd_email_array['referral_id']      = $data['referral_id'];
			$upd_email_array['date_replied']     = $data['init_date_replied'];
			$upd_email_array['replying_doctor']  = $data['init_replying_doctor'];
			$upd_email_array['replying_specialty']= $data['init_replying_specialty'];
			$upd_email_array['replying_centre']  = $data['init_replying_centre'];
			$upd_email_array['department']  		= $data['init_department'];
			$upd_email_array['referral_doctor_name']  		= $data['referral_doctor_name'];
			$upd_email_array['referral_email']  = $data['referral_email'];
			$upd_email_array['findings']  		= $data['init_findings'];
			$upd_email_array['cc_name']         = $data['cc_name'];
			$upd_email_array['cc_email']        = $data['cc_email'];
			$upd_email_array['email_subject']  	= $data['email_subject'];
			$upd_email_array['email_body']      = $data['email_salutation'];
			$upd_email_array['email_body']      .= ",\n\n".$data['email_paragraph1'];
			$upd_email_array['email_body']      .= "\n\nThe details of the patient are as follows:";
			$upd_email_array['email_body']      .= "\nPatient Name\t\t:\t".$data['patient_info']['patient_name'];
			$upd_email_array['email_body']      .= "\nBirth Date\t\t:\t".$data['patient_info']['birth_date'];
			$upd_email_array['email_body']      .= "\nGender\t\t\t:\t".$data['patient_info']['gender'];
			$upd_email_array['email_body']      .= "\nReference\t\t:\t".$data['referral_reference'];
			$upd_email_array['email_body']      .= "\nReferral Date\t\t:\t".$data['referral_date'];
			$upd_email_array['email_body']      .= "\nReason\t\t\t:\t".$data['reason'];
			$upd_email_array['email_body']      .= "\nClinical Exam\t\t:\t".$data['clinical_exam'];
			$upd_email_array['email_body']      .= "\n\n".$data['email_paragraph2'];
			$upd_email_array['email_body']      .= "\n\n".$data['email_conclusion'];
			$upd_email_array['email_body']      .= "\n\n".$data['referout_doctor_name'];
			$upd_email_array['reply_recorder']   = $_SESSION['staff_id'];
			$upd_email_array['date_recorded']  	= $data['now_date'];
            if($data['offline_mode']){
                $upd_email_array['synch_out']    = $data['now_id'];
            }
			$upd_email_data =   $this->send_email($upd_email_array);
            $this->session->set_flashdata('data_activity', 'Referral reply updated.');
            // Display outcome of sending attempt
            echo "You may now close this window.<br /><br />";
            print_r($upd_email_data);
            //header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_referout_email') == FALSE)
    } // end of function draft_notify_email()


    // ------------------------------------------------------------------------
    /**
     * This method will process the data filled-in to send e-mail notification.
     *
     * User can also use this page to e-mail referral notice to recipient.
     * It makes use of Gmail's SMTP function to send mail as it is difficult to
     * configure the servers to send directly.
     * This method is called by draft_notify_email().
     *
     * @author  Jason Tan Boon Teck
     */
    function send_email($email_array)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['smtp_host']		    =	$this->config->item('smtp_host');
        $data['smtp_user']		    =	$this->config->item('smtp_user');
        $data['smtp_pass']		    =	$this->config->item('smtp_pass');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['to_email']      	    =   $email_array['referral_email'];
        $data['to_name']      	    =   $email_array['referral_doctor_name'];
        $data['cc_email']      	    =   $email_array['cc_email'];
        $data['cc_name']      	    =   $email_array['cc_name'];
        $data['email_subject']      =   $email_array['email_subject'];
        $data['email_body']      	=   $email_array['email_body'];
		$data['title'] = "T H I R R A - e-mail Referral Notification";
		$data['clinic_info']    = $this->mthirra->get_clinic_info($_SESSION['location_id']);

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
        $this->email->from($data['clinic_info']['email'], $data['clinic_info']['clinic_name']);
        $this->email->to($data['to_email'], $data['to_name']);
        $this->email->cc($data['cc_email'], $data['cc_name']);
        //$this->email->bcc('theboss@example.com');
        $this->email->subject($data['email_subject']);
        $this->email->message($data['email_body']);
        //$this->email->message("Dear Sir,
        //I would like to refer this patient to you for a second opinion.");
        
        
        $this->email->send();
        //echo $this->email->print_debugger();
        $data['sending_result'] =   $this->email->print_debugger();

        $this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/ehr_indv_refer_email_sent_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_indv_refer_email_sent_html";
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
        return $data['sending_result'];
    } // end of function test_email($id)


    // ------------------------------------------------------------------------
    /**
     * Form to check existence of patient record in external server
     *
     * Other patients with almost similar names are also retrieved. 
     * Patient info is sent to external server using curl.
     * External server responds with XML string containing lists.
     *
     * @author  Jason Tan Boon Teck
     */
    function check_remote_patient_existence()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		//$this->load->model('morders_wdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']       = $this->uri->segment(3);
		$data['patient_id']         = $this->uri->segment(4);
		$data['summary_id']         = $this->uri->segment(5);
		$data['referral_id']        = $this->uri->segment(6);
        $data['location_id']    	=   $_SESSION['location_id'];
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        
        // User has posted the form
        $data['substring_search']      	    =   $this->input->post('substring_search');
        // Static information
        $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
        $data['referral_doctor_id'] 	=   $data['referral_info'][0]['referral_doctor_id'];
        $data['referral_doctor_name'] 	=   $data['referral_info'][0]['referral_doctor_name'];
        $data['referral_specialty']   	=   $data['referral_info'][0]['referral_specialty'];
        $data['referral_centre']   	    =   $data['referral_info'][0]['referral_centre'];
        $data['referral_date']   		=   $data['referral_info'][0]['referral_date'];
        $data['referral_reference'] 	=   $data['referral_info'][0]['referral_reference'];
        $data['reason'] 		        =   $data['referral_info'][0]['reason'];
        $data['clinical_exam']  	    =   $data['referral_info'][0]['clinical_exam'];
        
        //========= EXECUTE CURL ===================
        $this->load->library('curl');  

        //$start session (also wipes existing/previous sessions)

        // NEED TO CHECK FOR TRAILING SLASH
        $data['remote_site']   =   $data['referral_info'][0]['thirra_url'];
        //$data['remote_site']   =   "http://192.168.56.101/refer/";
        //$data['remote_site']   =   "http://127.0.0.1/test";
        //$data['remote_site']   =   "http://202.9.99.47/refer/";
        //$data['remote_site']   =   "http://127.0.0.1/offline/";
        $remote_site_path       =   $data['remote_site']."index.php/ehr_refer_curl";
        //$remote_site_path   =   "http://192.168.0.21/thirra/index.php/ehr_refer_curl";
        //$this->curl->create($remote_site_path.'/curled.php');
        $this->curl->create($remote_site_path.'/check_patient_existence');
        
        // Option & Options
        $this->curl->option(CURLOPT_BUFFERSIZE, 10);
        $this->curl->options(array(CURLOPT_BUFFERSIZE => 10));

        // Post - If you do not use post, it will just run a GET request
        $posting = array();
        $posting['patient_name']    =   $data['patient_info']['patient_name'];
        //$posting['patient_name']   =   "Ang"; // Hard coded testing
        $posting['patient_name']    =   $data['patient_info']['patient_name'];
        $posting['birth_date']      =   $data['patient_info']['birth_date'];
        $posting['gender']          =   $data['patient_info']['gender'];
        $posting['substring_search']=  $data['substring_search'];
        $this->curl->post($posting);

        // Execute - returns response
        //echo $this->curl->execute();
        $data['curled']     =   $this->curl->execute();
        //echo $data['curled'];
        // Remote server returns an XML string
        $xml = simplexml_load_string($data['curled']) or die("ERROR: Cannot create SimpleXML object");
        $data['xmlstr'] =   $xml;
        // Call xml_to_array() in xml helper
        $data['xml_array'] =   xml_to_array($xml);    
        // Call array_filter_recursive() in xml helper, to remove empty arrays inside array.
        $data['xml_array'] =   array_filter_recursive($data['xml_array']);    
        // Now that some of the elements are gone, we need to recreate them with NULL values.
        if($data['xml_array']['check_stats']['exact_match'] > 0){
            for($i=0; $i < $data['xml_array']['check_stats']['exact_match']; $i++){
                $same_i =   "same-".$i;
                if(!isset($data['xml_array']['exact_match'][$same_i]['name_first'])){
                    $data['xml_array']['exact_match'][$same_i]['name_first']  =   NULL;
                }
                if(!isset($data['xml_array']['exact_match'][$same_i]['ic_no'])){
                    $data['xml_array']['exact_match'][$same_i]['ic_no']  =   NULL;
                }
            }
        }
        if($data['xml_array']['check_stats']['partial_match'] > 0){
            for($j=0; $j < $data['xml_array']['check_stats']['partial_match']; $j++){
                $part_j =   "part-".$j;
                if(!isset($data['xml_array']['partial_match'][$part_j]['name_first'])){
                    $data['xml_array']['partial_match'][$part_j]['name_first']  =   NULL;
                }
                if(!isset($data['xml_array']['partial_match'][$part_j]['ic_no'])){
                    $data['xml_array']['partial_match'][$part_j]['ic_no']  =   NULL;
                }
            }
        }
        /*
		echo '<pre>';
            echo "<br />xml =";
			print_r($xml);
            echo "<br />xml_array =";
			print_r($data['xml_array']);
		echo '</pre>';
`       */
        // Information
        $this->curl->info; // array 
        
		$this->load->vars($data);
        
        if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            //$new_body   =   "ehr/ehr_orders_edit_imagresult_wap";
            $new_body   =   "ehr/ehr_indv_refer_patient_existence_html";
            $new_footer =   "ehr/footer_emr_wap";
        } else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_refer_patient_existence_html";
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
        
    } // end of function check_remote_patient_existence()


    // ------------------------------------------------------------------------
    /**
     * Form to confirm sending referral data for selected patient in external server
     *
     * This form is required in case user selected the wrong patient name to match.
     *
     * @author  Jason Tan Boon Teck
     */
    function send_referral_out2server_confirm()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		//$this->load->model('morders_wdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']       = $this->uri->segment(3);
		$data['patient_id']         = $this->uri->segment(4);
		$data['summary_id']         = $this->uri->segment(5);
		$data['referral_id']        = $this->uri->segment(6);
        $data['location_id']    	=   $_SESSION['location_id'];
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        
        // User has posted the form
        $data['remote_site']      	    =   $this->input->post('remote_site');
        $data['match_type']      	    =   $this->input->post('match_type');
        $data['rec_patient_id']      	=   $this->input->post('rec_patient_id');
        $data['rec_name_last']      	=   $this->input->post('rec_name_last');
        $data['rec_name_first']      	=   $this->input->post('rec_name_first');
        $data['rec_birth_date']      	=   $this->input->post('rec_birth_date');
        $data['rec_ic_no']      	    =   $this->input->post('rec_ic_no');
        // Static information
        $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
        $data['referral_doctor_id'] 	=   $data['referral_info'][0]['referral_doctor_id'];
        $data['referral_doctor_name'] 	=   $data['referral_info'][0]['referral_doctor_name'];
        $data['referral_specialty']   	=   $data['referral_info'][0]['referral_specialty'];
        $data['referral_centre']   	    =   $data['referral_info'][0]['referral_centre'];
        $data['referral_date']   		=   $data['referral_info'][0]['referral_date'];
        $data['referral_reference'] 	=   $data['referral_info'][0]['referral_reference'];
        $data['reason'] 		        =   $data['referral_info'][0]['reason'];
        $data['clinical_exam']  	    =   $data['referral_info'][0]['clinical_exam'];
                        
		$this->load->vars($data);
        
        if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            //$new_body   =   "ehr/ehr_orders_edit_imagresult_wap";
            $new_body   =   "ehr/ehr_indv_refer_out2server_confirm_html";
            $new_footer =   "ehr/footer_emr_wap";
        } else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_refer_out2server_confirm_html";
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
        
    } // end of function send_referral_out2server_confirm()


    // ------------------------------------------------------------------------
    /**
     * Form to send referral info to external server
     *
     * Patient info is sent to external server using curl.
     * External server responds with XML string containing lists.
     *
     * @author  Jason Tan Boon Teck
     */
    function send_referral_out2server_done()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		//$this->load->model('morders_wdb');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
        $data['form_purpose']       = $this->uri->segment(3);
		$data['patient_id']         = $this->uri->segment(4);
		$data['summary_id']         = $this->uri->segment(5);
		$data['referral_id']        = $this->uri->segment(6);
        $data['location_id']    	=   $_SESSION['location_id'];
        $data['patient_info'] = $this->memr_rdb->get_patient_details($data['patient_id']);
        $data['patient_info']['name']   = $data['patient_info']['patient_name'];
 		$data['title'] = "PR-".$data['patient_info']['name'];
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $export_by 		= $_SESSION['staff_id'];
        $export_when    = $data['now_id'];
        $data['baseurl']        =   base_url();
        $data['exploded_baseurl']=   explode('/', $data['baseurl'], 4);
        $data['app_folder']     =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']      =    $data['app_path']."-uploads/exports_refer";
        
        $version_file_path = $data['app_path']."/app_thirra/version.txt"; //"/var/www/thirra/app_thirra/version.txt";
        $handle = fopen($version_file_path, "r");
        $app_version = fread($handle, filesize($version_file_path));
        fclose($handle);
        
        $data['clinic_info'] = $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $clinic_name    =   $data['clinic_info']['clinic_name'];
        $clinic_ref_no  =   $data['clinic_info']['clinic_ref_no'];
        $pcdom_ref      =   $data['clinic_info']['pcdom_ref'];
        
        // User has posted the form
        $data['rec_patient_id']      	    =   $this->input->post('rec_patient_id');
        $data['export_reference']      	    =   "9876";//$this->input->post('export_reference');
        $data['export_remarks']      	    =   "Rem";//$this->input->post('export_remarks');
        // Static information
        $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
        $data['referral_doctor_id'] 	=   $data['referral_info'][0]['referral_doctor_id'];
        $data['referral_doctor_name'] 	=   $data['referral_info'][0]['referral_doctor_name'];
        $data['referral_specialty']   	=   $data['referral_info'][0]['referral_specialty'];
        $data['referral_centre']   	    =   $data['referral_info'][0]['referral_centre'];
        $data['referral_date']   		=   $data['referral_info'][0]['referral_date'];
        $data['referral_reference'] 	=   $data['referral_info'][0]['referral_reference'];
        $data['reason'] 		        =   $data['referral_info'][0]['reason'];
        $data['clinical_exam']  	    =   $data['referral_info'][0]['clinical_exam'];
        
        /*$xmlstr = "<?xml version='1.0'?>";*/
        $xmlstr = "";
        $xmlstr .= "\n<THIRRA_refer_out>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$clinic_name."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$clinic_ref_no."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
        $xmlstr .= "\n<patient_info>";
            $xmlstr .= "\n\t<rec_patient_id>".$data['rec_patient_id']."</rec_patient_id>";
            $xmlstr .= "\n\t<patient_id>".$data['patient_id']."</patient_id>";
            $xmlstr .= "\n\t<patient_reference>".$data['patient_info']['clinic_reference_number']."</patient_reference>";
            $xmlstr .= "\n\t<patient_pns_id>".$data['patient_info']['pns_pat_id']."</patient_pns_id>";
            $xmlstr .= "\n\t<patient_nhfa>".$data['patient_info']['nhfa_no']."</patient_nhfa>";
            $xmlstr .= "\n\t<patient_name>".$data['patient_info']['name']."</patient_name>";
            $xmlstr .= "\n\t<patient_name_first>".$data['patient_info']['name_first']."</patient_name_first>";
            $xmlstr .= "\n\t<birth_date>".$data['patient_info']['birth_date']."</birth_date>";
            $xmlstr .= "\n\t<gender>".$data['patient_info']['gender']."</gender>";
            $xmlstr .= "\n\t<patient_icno>".$data['patient_info']['ic_no']."</patient_icno>";
            $xmlstr .= "\n\t<patient_icother_type>".$data['patient_info']['ic_other_type']."</patient_icother_type>";
            $xmlstr .= "\n\t<patient_icother_no>".$data['patient_info']['ic_other_no']."</patient_icother_no>";
        $xmlstr .= "\n</patient_info>";
        $xmlstr .= "\n<refer_info>";
             $xmlstr .= "\n\t<referral_id>".$data['referral_id']."</referral_id>";
             $xmlstr .= "\n\t<refer_to_person>".$data['referral_doctor_name']."</refer_to_person>";
             //$xmlstr .= "\n\t<refer_to_department>".$data['referral_doctor_name']."</refer_to_department>";
             $xmlstr .= "\n\t<refer_to_specialty>".$data['referral_specialty']."</refer_to_specialty>";
             $xmlstr .= "\n\t<refer_by_person>".$data['referral_doctor_name']."</refer_by_person>";
             $xmlstr .= "\n\t<refer_by_specialty>".$data['referral_specialty']."</refer_by_specialty>";
             $xmlstr .= "\n\t<referral_centre>".$data['referral_centre']."</referral_centre>";
             $xmlstr .= "\n\t<referral_date>".$data['referral_date']."</referral_date>";
             $xmlstr .= "\n\t<referral_reference>".$data['referral_reference']."</referral_reference>";
             $xmlstr .= "\n\t<refer_reason>".$data['reason']."</refer_reason>";
             $xmlstr .= "\n\t<refer_clinical_exam>".$data['clinical_exam']."</refer_clinical_exam>";
        $xmlstr .= "\n</refer_info>";
        $xmlstr .= "\n<empty_group />";
        $xmlstr .= "\n</THIRRA_refer_out>";
        //echo "ehr_individual_refer->xmlstr=".$xmlstr;
        //$xmlstr         =   str_replace('\t', '', $xmlstr);
        //$xmlstr         =   str_replace('\n', '', $xmlstr);
        $xml_md5        =   md5($xmlstr);
        
        //========= EXECUTE CURL ===================
        $this->load->library('curl');  

        //$start session (also wipes existing/previous sessions)
        
        $data['remote_site']   =   $data['referral_info'][0]['thirra_url'];
        //$data['remote_site']   =   "http://192.168.56.101/refer/";
        //$data['remote_site']   =   "http://127.0.0.1/test";
        //$data['remote_site']   =   "http://202.9.99.47/refer/";
        //$data['remote_site']   =   "http://127.0.0.1/offline/";
        $remote_site_path   =   $data['remote_site']."index.php/ehr_refer_curl";
        //$remote_site_path   =   "http://192.168.0.21/thirra/index.php/ehr_refer_curl";
        //$this->curl->create($remote_site_path.'/curled.php');
        $this->curl->create($remote_site_path.'/receive_referral_info');

        // Option & Options
        $this->curl->option(CURLOPT_BUFFERSIZE, 10);
        $this->curl->options(array(CURLOPT_BUFFERSIZE => 10));

        // Post - If you do not use post, it will just run a GET request
        $posting = array();
        $posting['patient_name']    =   $data['patient_info']['patient_name'];
        //$posting['patient_name']   =   "Ang"; // Hard coded testing
        $posting['patient_name']    =   $data['patient_info']['patient_name'];
        $posting['birth_date']      =   $data['patient_info']['birth_date'];
        $posting['gender']          =   $data['patient_info']['gender'];
        $posting['xml_string']      =   $xmlstr;
        $posting['xml_md5']         =   $xml_md5;
        //print_r($posting);
        $this->curl->post($posting);

        // Execute - returns response
        //echo $this->curl->execute();
        $data['curled']     =   $this->curl->execute();
        //echo "data['curled']=".$data['curled'];
        $xml = simplexml_load_string($data['curled']) or die("ERROR: Cannot create SimpleXML object");
        
		//echo '<pre>';
			//print_r($xml);
		//echo '</pre>';
        
        $data['xmlstr'] =   $xml;
        // Call xml_to_array() in xml helper
        $data['xml_array'] =   xml_to_array($xml);    
        // Information
        $this->curl->info; // array 
        
		$this->load->vars($data);
        
        if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_wap";
            //$new_body   =   "ehr/ehr_orders_edit_imagresult_wap";
            $new_body   =   "ehr/ehr_indv_refer_out2server_done_html";
            $new_footer =   "ehr/footer_emr_wap";
        } else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_ehr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_indv_refer_out2server_done_html";
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
        
    } // end of function send_referral_out2server_done()


    // ------------------------------------------------------------------------
	function refer_select_details()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['age_menarche']	    =	$this->config->item('age_menarche');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']   = $this->uri->segment(3);
		$data['patient_id']     = $this->uri->segment(4);
		$data['summary_id']     = $this->uri->segment(5);
		$data['referral_id']     = $this->uri->segment(6);
	  	
        $data['referral_info'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
		$data['main'] = 'individual_overview';
		$data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
		$data['title'] = "Refer-".$data['patient_info']['name'];
		$data['patcon_info'] = $this->memr_rdb->get_patcon_details($data['patient_id']);
		$data['patient_past_con'] = $this->memr_rdb->get_pastcons_list($data['patient_id']);
		$data['drug_allergies']  = $this->memr_rdb->get_drug_allergies('List',$data['patient_id']);
        $data['vitals_info']  = $this->memr_rdb->get_recent_vitals($data['patient_id']);
        $data['medication_info']  = $this->memr_rdb->get_recent_medication($data['patient_id']);
        $data['lab_info']       = $this->memr_rdb->get_recent_lab($data['patient_id']);
        $data['imaging_info']   = $this->memr_rdb->get_recent_imaging($data['patient_id']);
        $data['diagnoses_info']  = $this->memr_rdb->get_recent_diagnoses($data['patient_id']);
		$data['social_history']  = $this->memr_rdb->get_history_social('List',$data['patient_id']);
        $data['vaccines_list'] 	= $this->memr_rdb->get_vaccines_list($data['patient_id']);
		$data['history_antenatal']  = $this->memr_rdb->get_antenatal_list('list',$data['patient_id']);
        $data['referrals_list'] = $this->memr_rdb->get_history_referrals('Consulted','List',$data['patient_id']);
        if($_SESSION['thirra_mode'] == "ehr_mobile") {
            $data['multicolumn']    =   FALSE;
        } else {
            $data['multicolumn']    =   TRUE;
        }
            $data['multicolumn']    =   FALSE;

        if($data['debug_mode']){
            $this->output->enable_profiler(TRUE);  
        }
        $this->load->vars($data);
		/*
		echo '<pre>';
			print_r($data);
		echo '</pre>';
		*/
		//$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            //echo "STOP";
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_ovrvw_wap";
            $new_sidebar=   "ehr/sidebar_emr_patients_ovrvw_wap";
            $new_body   =   "ehr/ehr_refer_select_details_html";
            //$new_body   =   "ehr/ehr_indv_overview_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_emr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_refer_select_details_html";
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
        
	} // end of function refer_select_details()
	

    // ------------------------------------------------------------------------
    function refer_export_detailsdone($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
        $export_by 		= $_SESSION['staff_id'];
        $export_when    = $data['now_id'];
        $data['baseurl']        =   base_url();
        $data['exploded_baseurl']=   explode('/', $data['baseurl'], 4);
        $data['app_folder']     =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']      =    $data['app_path']."-uploads/exports_refer";
        
        $version_file_path = $data['app_path']."/app_thirra/version.txt"; //"/var/www/thirra/app_thirra/version.txt";
        $handle = fopen($version_file_path, "r");
        $app_version = fread($handle, filesize($version_file_path));
        fclose($handle);
        
        $data['clinic_info'] = $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $clinic_name    =   $data['clinic_info']['clinic_name'];
        $clinic_ref_no  =   $data['clinic_info']['clinic_ref_no'];
        $pcdom_ref      =   $data['clinic_info']['pcdom_ref'];

        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['patient_id']         = $_POST['patient_id'];
            $data['summary_id']         = $_POST['summary_id'];
            $data['referral_id']        = $_POST['referral_id'];
            $data['export_reference']   =   $_POST['reference'];
            $data['export_remarks']     =   $_POST['remarks'];
            $summary_id     =   $data['summary_id'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_referral>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$clinic_name."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$clinic_ref_no."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
            /*
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<clinic_name>$clinic_name</clinic_name>";
            $xmlstr .= "\n\t\t<clinic_ref_no>$clinic_ref_no</clinic_ref_no>";
            $xmlstr .= "\n\t\t<pcdom_ref>$pcdom_ref</pcdom_ref>";
            */
			$selected		=	1;
			//for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				//if(isset($_POST['s'.$i])){
					//$data['unsynched_list'][$selected]['number']	= $i;
					//$data['unsynched_list'][$selected]['value']	= $_POST['s'.$i];
					//list($summary_id, $patient_id) = explode("-.-", $data['unsynched_list'][$selected]['value']);					//$data['unsynched_list'][$selected]['patient_info'] 
					//	= $this->memr_rdb->get_patient_details($data['unsynched_list'][$selected]['value']);
					//$data['unsynched_list'][$selected]['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
					$data['unsynched_list'][$selected]['patient_info']   = $this->memr_rdb->get_patient_details($data['patient_id']);
					$data['unsynched_list'][$selected]['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
					$data['unsynched_list'][$selected]['complaints_list']= $this->memr_rdb->get_patcon_complaints($data['summary_id']);
					$data['unsynched_list'][$selected]['vitals_info']    = $this->memr_rdb->get_patcon_vitals($data['summary_id']);
					$data['unsynched_list'][$selected]['lab_list']       = $this->memr_rdb->get_recent_lab($data['patient_id']);
					$data['unsynched_list'][$selected]['imaging_list']   = $this->memr_rdb->get_recent_imaging($data['patient_id']);
					$data['unsynched_list'][$selected]['diagnosis_list'] = $this->memr_rdb->get_recent_diagnoses($data['patient_id']);
					$data['unsynched_list'][$selected]['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id']);
					$data['unsynched_list'][$selected]['referrals_list'] = $this->memr_rdb->get_patcon_referrals($data['summary_id'],$data['referral_id']);
					if($data['debug_mode']){
						echo "<pre>";
						echo "\n<br />print_r(patient_info)=<br />";
						print_r($data['unsynched_list'][$selected]['patient_info']);
						echo "\n<br />print_r(patcon_info)=<br />";
						print_r($data['unsynched_list'][$selected]['patcon_info']);
						echo "\n<br />print_r(complaints_list)=<br />";
						print_r($data['unsynched_list'][$selected]['complaints_list']);						
						echo "\n<br />print_r(vitals_info)=<br />";
						print_r($data['unsynched_list'][$selected]['vitals_info']);						
						echo "\n<br />print_r(lab_list)=<br />";
						print_r($data['unsynched_list'][$selected]['lab_list']);						
						echo "\n<br />print_r(imaging_list)=<br />";
						print_r($data['unsynched_list'][$selected]['imaging_list']);						
						echo "\n<br />print_r(diagnosis_list)=<br />";
						print_r($data['unsynched_list'][$selected]['diagnosis_list']);						
						echo "\n<br />print_r(prescribe_list)=<br />";
						print_r($data['unsynched_list'][$selected]['prescribe_list']);						
						echo "\n<br />print_r(referrals_list)=<br />";
						print_r($data['unsynched_list'][$selected]['referrals_list']);						
						echo "</pre>";
					}
					$patient_id 	= $data['unsynched_list'][$selected]['patient_info']['patient_id'];
					$clinic_reference_number 	= $data['unsynched_list'][$selected]['patient_info']['clinic_reference_number'];
					$pns_pat_id 	= $data['unsynched_list'][$selected]['patient_info']['pns_pat_id'];
					$nhfa_no 	= $data['unsynched_list'][$selected]['patient_info']['nhfa_no'];
					$patient_name 	= $data['unsynched_list'][$selected]['patient_info']['name'];
					$name_first 	= $data['unsynched_list'][$selected]['patient_info']['name_first'];
					$gender 	    = $data['unsynched_list'][$selected]['patient_info']['gender'];
					$ic_no 	    = $data['unsynched_list'][$selected]['patient_info']['ic_no'];
					$ic_other_type 	    = $data['unsynched_list'][$selected]['patient_info']['ic_other_type'];
					$ic_other_no 	    = $data['unsynched_list'][$selected]['patient_info']['ic_other_no'];
					$nationality 	    = $data['unsynched_list'][$selected]['patient_info']['nationality'];
					$birth_date 	= $data['unsynched_list'][$selected]['patient_info']['birth_date'];
					$ethnicity 	    = $data['unsynched_list'][$selected]['patient_info']['ethnicity'];
					$religion 	    = $data['unsynched_list'][$selected]['patient_info']['religion'];
					$marital_status 	    = $data['unsynched_list'][$selected]['patient_info']['marital_status'];
					$blood_group 	    = $data['unsynched_list'][$selected]['patient_info']['blood_group'];
					$blood_rhesus 	    = $data['unsynched_list'][$selected]['patient_info']['blood_rhesus'];
					$contact_id 	    = $data['unsynched_list'][$selected]['patient_info']['contact_id'];
					$patient_address 	    = $data['unsynched_list'][$selected]['patient_info']['patient_address'];
					$patient_address2 	    = $data['unsynched_list'][$selected]['patient_info']['patient_address2'];
					$patient_address3 	    = $data['unsynched_list'][$selected]['patient_info']['patient_address3'];
					$patient_town 	    = $data['unsynched_list'][$selected]['patient_info']['patient_town'];
					$patient_state 	    = $data['unsynched_list'][$selected]['patient_info']['patient_state'];
					$patient_postcode 	    = $data['unsynched_list'][$selected]['patient_info']['patient_postcode'];
					$patient_country 	    = $data['unsynched_list'][$selected]['patient_info']['patient_country'];
					$tel_home 	    = $data['unsynched_list'][$selected]['patient_info']['tel_home'];
					$tel_office 	    = $data['unsynched_list'][$selected]['patient_info']['tel_office'];
					$tel_mobile 	    = $data['unsynched_list'][$selected]['patient_info']['tel_mobile'];
					$fax_no 	    = $data['unsynched_list'][$selected]['patient_info']['fax_no'];
					$email 	    = $data['unsynched_list'][$selected]['patient_info']['email'];
					$other 	    = $data['unsynched_list'][$selected]['patient_info']['other'];
					$staff_id 		= $data['unsynched_list'][$selected]['patcon_info']['staff_id'];
					$adt_id 		= $data['unsynched_list'][$selected]['patcon_info']['adt_id'];
					//$location_id	= $data['unsynched_list'][$selected]['patcon_info']['location_id'];
					$location_id	= $data['unsynched_list'][$selected]['patcon_info']['location_start'];
					$session_type 	= $data['unsynched_list'][$selected]['patcon_info']['session_type'];
					$date_started 	= $data['unsynched_list'][$selected]['patcon_info']['date_started'];
					$time_started 	= $data['unsynched_list'][$selected]['patcon_info']['time_started'];
					$date_ended 	= $data['unsynched_list'][$selected]['patcon_info']['date_ended'];
					$time_ended 	= $data['unsynched_list'][$selected]['patcon_info']['time_ended'];
					$signed_by 	    = $data['unsynched_list'][$selected]['patcon_info']['signed_by'];
					$check_in_date 	= $date_started;
					$check_in_time 	= $time_started;
					//$check_in_date 	= $data['unsynched_list'][$selected]['patcon_info']['check_in_date'];
					//$check_in_time 	= $data['unsynched_list'][$selected]['patcon_info']['check_in_time'];
					$location_start = $data['unsynched_list'][$selected]['patcon_info']['location_start'];
					$location_end 	= $data['unsynched_list'][$selected]['patcon_info']['location_end'];
					$episode_summary 	= $data['unsynched_list'][$selected]['patcon_info']['summary'];
					$episode_status = $data['unsynched_list'][$selected]['patcon_info']['status'];
					$episode_remarks = $data['unsynched_list'][$selected]['patcon_info']['remarks'];
					$synch_start 	= $data['unsynched_list'][$selected]['patcon_info']['synch_start'];
					$synch_out 		= $data['unsynched_list'][$selected]['patcon_info']['synch_out'];
					$count_complaints	= count($data['unsynched_list'][$selected]['complaints_list']);
					$count_vitals		= (count($data['unsynched_list'][$selected]['vitals_info'])/34);
					// vitals_info query returns one row with 34 columns
					$count_lab			= count($data['unsynched_list'][$selected]['lab_list']);
					$count_imaging		= count($data['unsynched_list'][$selected]['imaging_list']);
					$count_procedures	= 0;
					$count_diagnosis	= count($data['unsynched_list'][$selected]['diagnosis_list']);
					$count_prescribe	= count($data['unsynched_list'][$selected]['prescribe_list']);
					$count_referrals	= count($data['unsynched_list'][$selected]['referrals_list']);
					$count_others		= 0;
                    /*		
                        $ins_episode_array['start_date']            =   $data['now_date']; // ambiguous
                        $ins_episode_array['session_id']            =   $data['now_id'];
                        $ins_episode_array['now_id']                =   $data['now_id'];
                    */
					$xmlstr .= "\n<clinical_episode summary_id='$summary_id'>";
					$xmlstr .= "\n\t<patient_info>";
					$xmlstr .= "\n\t\t<patient_id>$patient_id</patient_id>";
					$xmlstr .= "\n\t\t<clinic_reference_number>$clinic_reference_number</clinic_reference_number>";
					$xmlstr .= "\n\t\t<pns_pat_id>$pns_pat_id</pns_pat_id>";
					$xmlstr .= "\n\t\t<nhfa_no>$nhfa_no</nhfa_no>";
					$xmlstr .= "\n\t\t<patient_name>$patient_name</patient_name>";
					$xmlstr .= "\n\t\t<name_first>$name_first</name_first>";
					$xmlstr .= "\n\t\t<gender>$gender</gender>";
					$xmlstr .= "\n\t\t<ic_no>$ic_no</ic_no>";
					$xmlstr .= "\n\t\t<ic_other_type>$ic_other_type</ic_other_type>";
					$xmlstr .= "\n\t\t<ic_other_no>$ic_other_no</ic_other_no>";
					$xmlstr .= "\n\t\t<nationality>$nationality</nationality>";
					$xmlstr .= "\n\t\t<birth_date>$birth_date</birth_date>";
					$xmlstr .= "\n\t\t<ethnicity>$ethnicity</ethnicity>";
					$xmlstr .= "\n\t\t<religion>$religion</religion>";
					$xmlstr .= "\n\t\t<marital_status>$marital_status</marital_status>";
					$xmlstr .= "\n\t\t<blood_group>$blood_group</blood_group>";
					$xmlstr .= "\n\t\t<blood_rhesus>$blood_rhesus</blood_rhesus>";
					$xmlstr .= "\n\t\t<contact_id>$contact_id</contact_id>";
					$xmlstr .= "\n\t\t<patient_address>$patient_address</patient_address>";
					$xmlstr .= "\n\t\t<patient_address2>$patient_address2</patient_address2>";
					$xmlstr .= "\n\t\t<patient_address3>$patient_address3</patient_address3>";
					$xmlstr .= "\n\t\t<patient_town>$patient_town</patient_town>";
					$xmlstr .= "\n\t\t<patient_state>$patient_state</patient_state>";
					$xmlstr .= "\n\t\t<patient_postcode>$patient_postcode</patient_postcode>";
					$xmlstr .= "\n\t\t<patient_country>$patient_country</patient_country>";
					$xmlstr .= "\n\t\t<tel_home>$tel_home</tel_home>";
					$xmlstr .= "\n\t\t<tel_office>$tel_office</tel_office>";
					$xmlstr .= "\n\t\t<tel_mobile>$tel_mobile</tel_mobile>";
					$xmlstr .= "\n\t\t<fax_no>$fax_no</fax_no>";
					$xmlstr .= "\n\t\t<email>$email</email>";
					$xmlstr .= "\n\t\t<other>$other</other>";
					$xmlstr .= "\n\t</patient_info>";
					$xmlstr .= "\n\t<episode_info>";
					$xmlstr .= "\n\t\t<summary_id>$summary_id</summary_id>";
					$xmlstr .= "\n\t\t<staff_id>$staff_id</staff_id>";
					$xmlstr .= "\n\t\t<adt_id>$adt_id</adt_id>";
					$xmlstr .= "\n\t\t<location_id>$location_id</location_id>";
					$xmlstr .= "\n\t\t<session_type>$session_type</session_type>";
					$xmlstr .= "\n\t\t<date_started>$date_started</date_started>";
					$xmlstr .= "\n\t\t<time_started>$time_started</time_started>";
					$xmlstr .= "\n\t\t<check_in_date>$check_in_date</check_in_date>";
					$xmlstr .= "\n\t\t<check_in_time>$check_in_time</check_in_time>";
					$xmlstr .= "\n\t\t<date_ended>$date_ended</date_ended>";
					$xmlstr .= "\n\t\t<time_ended>$time_ended</time_ended>";
					$xmlstr .= "\n\t\t<signed_by>$signed_by</signed_by>";
					$xmlstr .= "\n\t\t<location_start>$location_start</location_start>";
					$xmlstr .= "\n\t\t<location_end>$location_end</location_end>";
					$xmlstr .= "\n\t\t<episode_summary>$episode_summary</episode_summary>";
					$xmlstr .= "\n\t\t<episode_status>$episode_status</episode_status>";
					$xmlstr .= "\n\t\t<episode_remarks>$episode_remarks</episode_remarks>";
					$xmlstr .= "\n\t\t<synch_start>$synch_start</synch_start>";
					$xmlstr .= "\n\t\t<synch_out>$synch_out</synch_out>";
					$xmlstr .= "\n\t\t<count_complaints>$count_complaints</count_complaints>";
					$xmlstr .= "\n\t\t<count_vitals>$count_vitals</count_vitals>";
					$xmlstr .= "\n\t\t<count_lab>$count_lab</count_lab>";
					$xmlstr .= "\n\t\t<count_imaging>$count_imaging</count_imaging>";
					$xmlstr .= "\n\t\t<count_procedures>$count_procedures</count_procedures>";
					$xmlstr .= "\n\t\t<count_diagnosis>$count_diagnosis</count_diagnosis>";
					$xmlstr .= "\n\t\t<count_prescribe>$count_prescribe</count_prescribe>";
					$xmlstr .= "\n\t\t<count_referrals>$count_referrals</count_referrals>";
					$xmlstr .= "\n\t\t<count_others>$count_others</count_others>";
					$xmlstr .= "\n\t</episode_info>";
					
					// Complaints Segment
					if($count_complaints > 0) {
						$k	= 1;
                        if($data['debug_mode']){
                            echo "\nExporting patient complaints.";
                        }
						foreach($data['unsynched_list'][$selected]['complaints_list'] as $complaint) {
							$xmlstr .= "\n\t<complaints_info recno='$k'>";
							$xmlstr .= "\n\t\t<complaint_id>".$complaint['complaint_id']."</complaint_id>";
							$xmlstr .= "\n\t\t<icpc_code>".$complaint['icpc_code']."</icpc_code>";
							$xmlstr .= "\n\t\t<duration>".$complaint['duration']."</duration>";
							$xmlstr .= "\n\t\t<complaint_notes>".$complaint['complaint_notes']."</complaint_notes>";
							//$xmlstr .= "\n\t\t<ccode1ext_code>".$complaint['ccode1ext_code']."</ccode1ext_code>";
							//$xmlstr .= "\n\t\t<complaint_rank>".$complaint['complaint_rank']."</complaint_rank>";
							$xmlstr .= "\n\t\t<remarks>".$complaint['remarks']."</remarks>";
							$xmlstr .= "\n\t</complaints_info>";
							$k++;
						}
					} //endif($count_complaints > 0)
 					
					// Vitals Segment
					if($count_vitals > 0) {
							if($data['debug_mode']) {
								echo "<pre>['vitals_info']";
								print_r($data['unsynched_list'][$selected]['vitals_info']);
								echo "</pre>";
							}
						$k	= 1;
                        if($data['debug_mode']){
                            echo "\nExportng vital signs";
                        }
						$vitals = $data['unsynched_list'][$selected]['vitals_info'];
                        if(isset($vitals['reading_date'])){
						//foreach($data['unsynched_list'][$selected]['vitals_info'] as $vitals) {
							$xmlstr .= "\n\t<vitals_info recno='$k'>";
							$xmlstr .= "\n\t\t<vital_id>".$vitals['vital_id']."</vital_id>";
							$xmlstr .= "\n\t\t<reading_date>".$vitals['reading_date']."</reading_date>";
							$xmlstr .= "\n\t\t<reading_time>".$vitals['reading_time']."</reading_time>";
							$xmlstr .= "\n\t\t<height>".$vitals['height']."</height>";
							$xmlstr .= "\n\t\t<weight>".$vitals['weight']."</weight>";
							$xmlstr .= "\n\t\t<left_vision>".$vitals['left_vision']."</left_vision>";
							$xmlstr .= "\n\t\t<right_vision>".$vitals['right_vision']."</right_vision>";
							$xmlstr .= "\n\t\t<temperature>".$vitals['temperature']."</temperature>";
							$xmlstr .= "\n\t\t<pulse_rate>".$vitals['pulse_rate']."</pulse_rate>";
							$xmlstr .= "\n\t\t<bmi>".$vitals['bmi']."</bmi>";
							$xmlstr .= "\n\t\t<waist_circumference>".$vitals['waist_circumference']."</waist_circumference>";
							$xmlstr .= "\n\t\t<bp_systolic>".$vitals['bp_systolic']."</bp_systolic>";
							$xmlstr .= "\n\t\t<bp_diastolic>".$vitals['bp_diastolic']."</bp_diastolic>";
							$xmlstr .= "\n\t\t<respiration_rate>".$vitals['respiration_rate']."</respiration_rate>";
							$xmlstr .= "\n\t\t<ofc>".$vitals['ofc']."</ofc>";
							$xmlstr .= "\n\t\t<remarks>".$vitals['remarks']."</remarks>";
							$xmlstr .= "\n\t</vitals_info>";
							$k++;
						//}
                        } //endif(isset($vitals['reading_date']))
					} //endif($count_vitals > 0)
					
					// Lab Orders Segment
					if($count_lab > 0) {
						$k	= 1;
                        if($data['debug_mode']){
                            echo "\nExporting lab orders";
                        }
						foreach($data['unsynched_list'][$selected]['lab_list'] as $lab) {
							$xmlstr .= "\n\t<lab_info recno='$k'>";
							$xmlstr .= "\n\t\t<lab_order_id>".$lab['lab_order_id']."</lab_order_id>";
							$xmlstr .= "\n\t\t<lab_package_id>".$lab['lab_package_id']."</lab_package_id>";
							$xmlstr .= "\n\t\t<sample_ref>".$lab['sample_ref']."</sample_ref>";
							$xmlstr .= "\n\t\t<sample_date>".$lab['sample_date']."</sample_date>";
							$xmlstr .= "\n\t\t<sample_time>".$lab['sample_time']."</sample_time>";
							$xmlstr .= "\n\t\t<fasting>".$lab['fasting']."</fasting>";
							$xmlstr .= "\n\t\t<urgency>".$lab['urgency']."</urgency>";
							$xmlstr .= "\n\t\t<summary_result>".$lab['summary_result']."</summary_result>";
							$xmlstr .= "\n\t\t<result_status>".$lab['result_status']."</result_status>";
							$xmlstr .= "\n\t\t<invoice_status>".$lab['invoice_status']."</invoice_status>";
							$xmlstr .= "\n\t\t<invoice_detail_id>".$lab['invoice_detail_id']."</invoice_detail_id>";
							$xmlstr .= "\n\t\t<remarks>".$lab['remarks']."</remarks>";
							$xmlstr .= "\n\t\t<result_reviewed_by>".$lab['result_reviewed_by']."</result_reviewed_by>";
							$xmlstr .= "\n\t\t<result_reviewed_date>".$lab['result_reviewed_date']."</result_reviewed_date>";
							$xmlstr .= "\n\t\t<package_code>".$lab['package_code']."</package_code>";
							$xmlstr .= "\n\t\t<package_name>".$lab['package_name']."</package_name>";
							$xmlstr .= "\n\t\t<supplier_name>".$lab['supplier_name']."</supplier_name>";
							$xmlstr .= "\n\t</lab_info>";
							$k++;
						}
					} //endif($count_lab > 0)
					
					// Imaging Orders Segment
					if($count_imaging > 0) {
						$k	= 1;
                        if($data['debug_mode']){
                            echo "\nExporting imaging orders";
                        }
						foreach($data['unsynched_list'][$selected]['imaging_list'] as $imaging) {
							$xmlstr .= "\n\t<imaging_info recno='$k'>";
							$xmlstr .= "\n\t\t<imaging_order_id>".$imaging['order_id']."</imaging_order_id>";
							$xmlstr .= "\n\t\t<supplier_ref>".$imaging['supplier_ref']."</supplier_ref>";
							$xmlstr .= "\n\t\t<product_id>".$imaging['product_id']."</product_id>";
							$xmlstr .= "\n\t\t<result_status>".$imaging['result_status']."</result_status>";
							$xmlstr .= "\n\t\t<invoice_status>".$imaging['invoice_status']."</invoice_status>";
							$xmlstr .= "\n\t\t<order_remarks>".$imaging['remarks']."</order_remarks>";
							$xmlstr .= "\n\t\t<product_code>".$imaging['product_code']."</product_code>";
							$xmlstr .= "\n\t\t<loinc_num>".$imaging['loinc_num']."</loinc_num>";
							$xmlstr .= "\n\t\t<description>".$imaging['description']."</description>";
							$xmlstr .= "\n\t\t<supplier_name>".$imaging['supplier_name']."</supplier_name>";
							$xmlstr .= "\n\t</imaging_info>";
							$k++;
						}
					} //endif($count_imaging > 0)
					
					// Diagnosis Segment
					if($count_diagnosis > 0) {
						$k	= 1;
                        if($data['debug_mode']){
                            echo "\nExporting medical history";
                        }
						foreach($data['unsynched_list'][$selected]['diagnosis_list'] as $diagnosis) {
							$xmlstr .= "\n\t<diagnosis_info recno='$k'>";
							$xmlstr .= "\n\t\t<diagnosis_id>".$diagnosis['diagnosis_id']."</diagnosis_id>";
							$xmlstr .= "\n\t\t<diagnosis_type>".$diagnosis['diagnosis_type']."</diagnosis_type>";
							$xmlstr .= "\n\t\t<diagnosis_notes>".$diagnosis['diagnosis_notes']."</diagnosis_notes>";
							$xmlstr .= "\n\t\t<dcode1set>".$diagnosis['dcode1set']."</dcode1set>";
							$xmlstr .= "\n\t\t<dcode1ext_code>".$diagnosis['dcode1ext_code']."</dcode1ext_code>";
							$xmlstr .= "\n\t\t<dcode2set>".$diagnosis['dcode2set']."</dcode2set>";
							$xmlstr .= "\n\t\t<dcode2ext_code>".$diagnosis['dcode2ext_code']."</dcode2ext_code>";
							$xmlstr .= "\n\t\t<remarks>".$diagnosis['remarks']."</remarks>";
							$xmlstr .= "\n\t</diagnosis_info>";
							$k++;
						}
					} //endif($count_diagnosis > 0)
					
					// Prescriptions Segment
					if($count_prescribe > 0) {
						$k	= 1;
                        if($data['debug_mode']){
                            echo "\nExporting medication history";
                        }
						foreach($data['unsynched_list'][$selected]['prescribe_list'] as $prescribe) {
							$xmlstr .= "\n\t<prescribe_info recno='$k'>";
							$xmlstr .= "\n\t\t<queue_id>".$prescribe['queue_id']."</queue_id>";
							$xmlstr .= "\n\t\t<drug_formulary_id>".$prescribe['drug_formulary_id']."</drug_formulary_id>";
							$xmlstr .= "\n\t\t<dose>".$prescribe['dose']."</dose>";
							$xmlstr .= "\n\t\t<dose_form>".$prescribe['dose_form']."</dose_form>";
							$xmlstr .= "\n\t\t<frequency>".$prescribe['frequency']."</frequency>";
							$xmlstr .= "\n\t\t<instruction>".$prescribe['instruction']."</instruction>";
							$xmlstr .= "\n\t\t<quantity>".$prescribe['quantity']."</quantity>";
							$xmlstr .= "\n\t\t<quantity_form>".$prescribe['quantity_form']."</quantity_form>";
							$xmlstr .= "\n\t\t<indication>".$prescribe['indication']."</indication>";
							$xmlstr .= "\n\t\t<caution>".$prescribe['caution']."</caution>";
							$xmlstr .= "\n\t\t<status>".$prescribe['status']."</status>";
							$xmlstr .= "\n\t\t<formulary_code>".$prescribe['formulary_code']."</formulary_code>";
							$xmlstr .= "\n\t\t<generic_name>".$prescribe['generic_name']."</generic_name>";
							$xmlstr .= "\n\t\t<formulary_system>".$prescribe['formulary_system']."</formulary_system>";
							$xmlstr .= "\n\t</prescribe_info>";
							$k++;
						}
					} //endif($count_prescribe > 0)
					
					$xmlstr .= "\n</clinical_episode>";
					$selected++;
				//} //endif(isset($_POST['s'.$i]))
			//} //endfor($i=1; $i<=$data['num_rows']; $i++)
		} //endif(count($_POST))
		$data['title'] = "Exported New Patients";
        $data['now_id']             =   time();
		$data['file_exported']		=	"patient_refer-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		//$address1 = $data['unsynched_list'][1]['patient_info']['patient_address'];
		//$xmlstr .= "\n\t<address1>$address1</address1>";
		$xmlstr .= "\n</THIRRA_export_referral>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);
		//$write = $xml->asXML("/var/www/thirra-uploads/exports_refer/".$data['file_exported']);

		$data['patient_info'] = $this->memr_rdb->get_patient_demo($data['patient_id']);
		$data['title'] = "Refer-".$data['patient_info']['name'];
		//echo $xml->patient_info[1]->patient_name;
		// ========
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_ovrvw_html";
            $new_sidebar=   "ehr/sidebar_emr_patients_ovrvw_html";
            $new_body   =   "ehr/ehr_refer_export_detailsdone_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_admin'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		//$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function refer_export_detailsdone($id)


    // ------------------------------------------------------------------------
    function admin_import_new_refer_review($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['filename']   = $this->uri->segment(4);
        $data['now_id']             =   time();
        // User has posted the form
        $data['form_purpose']   = $this->uri->segment(3); //$_POST['form_purpose'];
        $data['num_rows']       = $_POST['num_rows'];
        $data = $this->admin_import_new_referview($data['filename'],$data);
		$data['title'] = "View New Incoming Referral";

		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_refer_import_review_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		
		$this->load->view($new_header);			
		//$this->load->view($new_banner);			
		//$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		
    } // end of function admin_import_new_refer_review($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES

    // ------------------------------------------------------------------------
    function admin_import_new_refer_addpatient($id=NULL)  // DEPRECATED
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']       = $this->uri->segment(3);
        $data['filename']           = $this->uri->segment(4);
        $data['num_rows']           =   0;
        $data['now_id']             =   time();
        $data = $this->admin_import_new_referview($data['filename'],$data);
		$data['title'] = "Add New Patient for Incoming Referral";

		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_refer_import_review_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		//$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		
    } // end of function admin_import_new_refer_addpatient($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES

    // ------------------------------------------------------------------------
    function admin_import_new_refer_insertpatient($id=NULL)  // template for new classes
    {
		$this->load->model('madmin_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']       = $this->uri->segment(3);
        $data['filename']           = $this->uri->segment(4);
        $data['num_rows']           =   0;
        $data['now_id']             =   time();
        $data = $this->admin_import_new_referview($data['filename'],$data);
		$data['title'] = "Add New Patient for Incoming Referral";

        if(count($_POST)) {
            // User has posted the form
            //$data['now_id']      		=   $this->input->post('now_id');
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $data['export_username']      	=   $this->input->post('export_username');
            $data['export_when']      		=   $this->input->post('export_when');
            $data['thirra_version']      	=   $this->input->post('thirra_version');
            $data['export_clinicname']      =   $this->input->post('export_clinicname');
            $data['export_clinicref']      	=   $this->input->post('export_clinicref');
            $data['export_reference']      	=   $this->input->post('export_reference');
            $data['import_remarks']      	=   $this->input->post('import_remarks');
            $data['import_reference']      	=   $this->input->post('import_reference');
            $data['init_patient_id']      	=   $this->input->post('patient_id');
            $data['patient_id']             =   $data['init_patient_id'];
            $data['init_clinic_reference_number']=   $this->input->post('clinic_reference_number');
            $data['init_pns_pat_id']        =   $this->input->post('pns_pat_id');
            $data['init_nhfa_no']           =   $this->input->post('nhfa_no');
            $data['patient_name']      		=   $this->input->post('patient_name');
            $data['name_first']      		=   $this->input->post('name_first');
            $data['name_alias']      		=   $this->input->post('name_alias');
            $data['gender']      			=   $this->input->post('gender');
            $data['ic_no']      			=   $this->input->post('ic_no');
            $data['init_ic_other_no']      	=   $this->input->post('ic_other_no');
            $data['init_ic_other_type']     =   $this->input->post('ic_other_type');
            $data['init_nationality']       =   $this->input->post('nationality');
            //$data['init_birth_date']        =   $this->input->post('birth_date');
            $data['posted_birth_date']        =   $this->input->post('birth_date');
            $data['init_birth_date']        =   $data['posted_birth_date'];
            $data['init_birth_cert_no']     =   $this->input->post('birth_cert_no');
            $data['init_ethnicity']         =   $this->input->post('ethnicity');
            $data['init_religion']          =   $this->input->post('religion');
            $data['init_marital_status']    =   $this->input->post('marital_status');
            $data['init_patient_type']      =   $this->input->post('patient_type');
            $data['init_blood_group']       =   $this->input->post('blood_group');
            $data['init_blood_rhesus']   	=   $this->input->post('blood_rhesus');
            $data['init_demise_date']      	=   $this->input->post('demise_date');
            $data['init_demise_time']      	=   $this->input->post('demise_time');
            $data['init_demise_cause']      =   $this->input->post('demise_cause');
            $data['init_death_cert']      	=   $this->input->post('death_cert');
            $data['init_patient_status']    =   $this->input->post('patient_status');
            $data['init_patdemo_remarks']   =   "Imported referral"; //$this->input->post('patdemo_remarks');
            $data['init_clinic_home']       =   $_SESSION['location_id'];//$this->input->post('clinic_home');
            $data['init_clinic_registered']     =   $_SESSION['location_id'];
            $data['init_birth_date_estimate']   =   $this->input->post('birth_date_estimate');
            $data['init_age']      		=   $this->input->post('age');
            $data['contact_id']      		=   $this->input->post('contact_id');
            $data['init_patient_address']   =   $this->input->post('patient_address');
            $data['init_patient_address2']  =   $this->input->post('patient_address2');
            $data['init_patient_address3']  =   $this->input->post('patient_address3');
            $data['init_patient_postcode']  =   $this->input->post('patient_postcode');
            $data['init_patient_town']      =   $this->input->post('patient_town');
            $data['init_patient_state']     =   $this->input->post('patient_state');
            $data['init_patient_country']   =   $this->input->post('patient_country');
            $data['init_tel_home']          =   $this->input->post('tel_home');
            $data['init_tel_office']      	=   $this->input->post('tel_office');
            $data['init_tel_mobile']      	=   $this->input->post('tel_mobile');
            $data['init_pager_no']      	=   $this->input->post('pager_no');
            $data['init_fax_no']      		=   $this->input->post('fax_no');
            $data['init_email']      		=   $this->input->post('email');
            $data['init_contact_other']     =   $this->input->post('contact_other');
            $data['init_contact_remarks']   =   $this->input->post('contact_remarks');
            $data['init_addr_village_id']   =   $this->input->post('addr_village_id');
            $data['init_location_id']       =   $_SESSION['location_id'];
            //$data['init_addr_area_id']    =   $_POST['addr_area_id'];
            $data['broken_birth_date']      =   $this->break_date($data['init_birth_date']);
            $data['broken_now_date']        =   $this->break_date($data['now_date']);
            
            // Insert New patient record
            $ins_patient_array   =   array();
            $ins_patient_array['staff_id']           = $_SESSION['staff_id'];
            $ins_patient_array['now_id']             = $data['now_id'];
            $ins_patient_array['patient_id']         = $data['patient_id']; //$data['broken_birth_date']['dd']
                                                        //.$data['broken_birth_date']['mm']
                                                        //.$data['broken_birth_date']['yyyy']
                                                        //.$data['broken_now_date']['dd']
                                                        //.$data['broken_now_date']['mm']
                                                        //.$data['broken_now_date']['yyyy']
                                                        //.$data['now_id'];
            $ins_patient_array['clinic_reference_number']= $data['init_clinic_reference_number'];
            $ins_patient_array['pns_pat_id']         = $data['init_pns_pat_id'];
            $ins_patient_array['nhfa_no']            = $data['init_nhfa_no'];
            $ins_patient_array['patient_name']       = $data['patient_name'];
            $ins_patient_array['name_first']         = $data['name_first'];
            $ins_patient_array['name_alias']         = $data['name_alias'];
            $ins_patient_array['ic_no']              = $data['ic_no'];
            $ins_patient_array['ic_other_no']        = $data['init_ic_other_no'];
            $ins_patient_array['ic_other_type']      = $data['init_ic_other_type'];
            $ins_patient_array['nationality']        = $data['init_nationality'];
            $ins_patient_array['birth_date']         = $data['init_birth_date'];
            $ins_patient_array['birth_cert_no']      = $data['init_birth_cert_no'];
            if($data['broken_now_date']['yyyy'] < 2000){
                $ins_patient_array['family_link']        = "Independent"; //"Head of Family";
            } else {
                $ins_patient_array['family_link']        = "Under Head of Family";
            }
            $ins_patient_array['gender']             = $data['gender'];
            $ins_patient_array['ethnicity']          = $data['init_ethnicity'];
            $ins_patient_array['religion']           = $data['init_religion'];
            $ins_patient_array['marital_status']     = $data['init_marital_status'];
            $ins_patient_array['patient_type']       = $data['init_patient_type'];
            $ins_patient_array['blood_group']        = $data['init_blood_group'];
            $ins_patient_array['blood_rhesus']       = $data['init_blood_rhesus'];
            if($data['init_demise_date']){
                $ins_patient_array['demise_date']              = $data['init_demise_date'];
            }
            if($data['init_demise_time']){
                $ins_patient_array['demise_time']              = $data['init_demise_time'];
            }
            $ins_patient_array['demise_cause']       = $data['init_demise_cause'];
            $ins_patient_array['death_cert']         = $data['init_death_cert'];
            $ins_patient_array['clinic_home']        = $data['init_clinic_home'];
            $ins_patient_array['clinic_registered']  = $data['init_location_id'];
            $ins_patient_array['patient_status']     = 1; // $data['init_patient_status'];
            $ins_patient_array['location_id']        = $data['init_location_id'];
            $ins_patient_array['patdemo_remarks']    = $data['init_patdemo_remarks'];
            $ins_patient_array['contact_id']         = $data['contact_id'];
            $ins_patient_array['patient_correspondence_id']  = $data['contact_id'];
            $ins_patient_array['contact_type']       = "Residence";
            $ins_patient_array['correspondence_type']= "Correspondence";
            $ins_patient_array['start_date']         = $data['now_date'];
            $ins_patient_array['patient_address']    = $data['init_patient_address'];
            $ins_patient_array['patient_address2']   = $data['init_patient_address2'];
            $ins_patient_array['patient_address3']   = $data['init_patient_address3'];
            $ins_patient_array['patient_postcode']   = $data['init_patient_postcode'];
            $ins_patient_array['patient_town']       = $data['init_patient_town'];
            $ins_patient_array['patient_state']      = $data['init_patient_state'];
            $ins_patient_array['patient_country']    = $data['init_patient_country'];
            $ins_patient_array['tel_home']           = $data['init_tel_home'];
            $ins_patient_array['tel_office']         = $data['init_tel_office'];
            $ins_patient_array['tel_mobile']         = $data['init_tel_mobile'];
            $ins_patient_array['pager_no']           = $data['init_pager_no'];
            $ins_patient_array['fax_no']             = $data['init_fax_no'];
            $ins_patient_array['email']           	 = $data['init_email'];
            $ins_patient_array['contact_other']    	 = $data['init_contact_other'];
            $ins_patient_array['contact_remarks'] 	 = $data['init_contact_remarks'];
            $ins_patient_array['addr_village_id']    = $data['init_addr_village_id'];
            $ins_patient_array['addr_town_id']       = NULL;//$data['init_addr_town_id'];
            $ins_patient_array['addr_area_id']       = NULL;//$data['init_addr_area_id'];
            $ins_patient_array['addr_district_id']   = NULL;//$data['init_addr_district_id'];
            $ins_patient_array['addr_state_id']      = NULL;//$data['init_addr_state_id'];
            $ins_patient_array['patient_immunisation_id']  = $data['contact_id'];//$data['now_id'];
            if($data['offline_mode']){
                $ins_patient_array['synch_out']        = $data['now_id'];
            }
            $ins_patient_data       =   $this->mehr_wdb->insert_new_patient($ins_patient_array);
        
            // New log record
            $ins_log_array   =   array();
            $ins_log_array['data_synch_log_id'] = $data['now_id'];
            $ins_log_array['export_by']         = $data['export_username'];
            $ins_log_array['export_when']       = $data['export_when'];
            $ins_log_array['thirra_version']    = $data['thirra_version'];
            $ins_log_array['export_clinicname'] = $data['export_clinicname'];
            $ins_log_array['export_clinicref']  = $data['export_clinicref'];
            $ins_log_array['export_reference']  = $data['export_reference'];
            $ins_log_array['import_by']         = $_SESSION['staff_id'];
            $ins_log_array['import_when']       = $data['now_id'];
            $ins_log_array['data_filename']     = $data['filename'];
            $ins_log_array['import_remarks']    = $data['import_remarks'];
            $ins_log_array['import_reference']  = $data['import_reference'];
            $ins_log_array['import_number']     = 1;//$data['import_number'];
            $ins_log_array['import_outcome']    = "Success";
            $ins_log_array['count_inserted']    = 1;//$data['count_inserted'];
            $ins_log_array['count_declined']    = 0;//$data['num_rows'] - $data['count_inserted'];
            $ins_log_array['count_rejected']    = 0;//$data['count_rejected'];
            $ins_log_array['entities_inserted'] = 1;//$data['entities_inserted'];
            //$ins_log_array['entities_declined'] = $data['entities_declined'];
            //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
            //$ins_log_array['declined_list']     = $data['declined_list'];
            //$ins_log_array['rejected_list']     = $data['rejected_list'];
            $ins_log_array['outcome_remarks']   = "No problems encountered.";
            $ins_log_array['sync_type']         = "Manual EDI - Referrals Data";
            $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

            echo form_open('ehr_individual/individual_overview/'.$ins_patient_array['patient_id']);
            echo "\n<br /><input type='hidden' name='patient_id' value='".$ins_patient_array['patient_id']."' size='40' />";
            echo "Registered imported patient.<br /> <input type='submit' value='Click to Continue' />";
            echo "</form>";
        } //endif(count($_POST))
        /*
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_refer_import_review_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		//$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);	
        */
		
    } // end of function admin_import_new_refer_insertpatient($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES

    // ------------------------------------------------------------------------
    function admin_import_new_referview($filename,$callerdata)  // called by other methods
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['filename']   = $filename; //$this->uri->segment(3);
		//$data['title'] = "Exported New Patients";
        $data['now_id']             =   time();
        $data['baseurl']        =   base_url();
        $data['exploded_baseurl']=   explode('/', $data['baseurl'], 4);
        $data['app_folder']     =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['import_path']        =    $data['app_path']."-uploads/imports_refer";
        
        if($_SESSION['thirra_mode'] == "ehr_mobile") {
            $data['multicolumn']    =   FALSE;
        } else {
            $data['multicolumn']    =   TRUE;
        }
        //if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $callerdata['form_purpose']; //$_POST['form_purpose'];
            $data['num_rows']       = $callerdata['num_rows']; //$_POST['num_rows'];
			
			// Retrieve what user selected
			$selected		=	0;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$selected++;
					//$data['selected_list'][$selected]['number']	= $i;
					$data['selected_list'][$selected]['summary_id']	= $_POST['s'.$i];
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
			$data['total_selected'] = $selected;
            
            
			// Retrieve all records from XML file
			$xml_file			= $data['import_path']."/".$data['filename'];
			//$xml_file			= "/var/www/thirra-uploads/imports_refer/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
            //print_r($xml);
            
			$i	=	1;
            $data['unsynched_list']['export_reference']	=	$xml->export_info->export_reference;
            $data['unsynched_list']['referring_clinic']	=	$xml->export_info->export_clinicname;
            $data['unsynched_list']['export_clinicref']	=	$xml->export_info->export_clinicref;
            $data['unsynched_list']['export_clinicid']	=	$xml->export_info->export_clinicid;
            $data['unsynched_list']['export_remarks']	=	$xml->export_info->export_remarks;
            $data['unsynched_list']['export_username']	=	$xml->export_info->export_username;
            $data['unsynched_list']['export_by']	    =	$xml->export_info->export_by;
            $data['unsynched_list']['export_when']	    =	$xml->export_info->export_when;
            $data['unsynched_list']['thirra_version']	=	$xml->export_info->thirra_version;
            $data['unsynched_list']['export_db']	    =	$xml->export_info->current_db;
			foreach ($xml->clinical_episode as $item) {
				$data['unsynched_list'][$i]['patient_id']	=	$item->patient_info->patient_id;
				$data['unsynched_list'][$i]['clinic_reference_number']	=	$item->patient_info->clinic_reference_number;
				$data['unsynched_list'][$i]['pns_pat_id']	=	$item->patient_info->pns_pat_id;
				$data['unsynched_list'][$i]['nhfa_no']	=	$item->patient_info->nhfa_no;
				$data['unsynched_list'][$i]['patient_name']	=	$item->patient_info->patient_name;
				$data['unsynched_list'][$i]['name_first']	=	$item->patient_info->name_first;
				$data['unsynched_list'][$i]['gender']	=	$item->patient_info->gender;
				$data['unsynched_list'][$i]['ic_no']	=	$item->patient_info->ic_no;
				$data['unsynched_list'][$i]['ic_other_type']	=	$item->patient_info->ic_other_type;
				$data['unsynched_list'][$i]['ic_other_no']	=	$item->patient_info->ic_other_no;
				$data['unsynched_list'][$i]['nationality']	=	$item->patient_info->nationality;
				$data['unsynched_list'][$i]['birth_date']	=	$item->patient_info->birth_date;
				$data['unsynched_list'][$i]['ethnicity']	=	$item->patient_info->ethnicity;
				$data['unsynched_list'][$i]['religion']	=	$item->patient_info->religion;
				$data['unsynched_list'][$i]['marital_status']	=	$item->patient_info->marital_status;
				$data['unsynched_list'][$i]['blood_group']	=	$item->patient_info->blood_group;
				$data['unsynched_list'][$i]['blood_rhesus']	=	$item->patient_info->blood_rhesus;
				$data['unsynched_list'][$i]['contact_id']	=	$item->patient_info->contact_id;
				$data['unsynched_list'][$i]['patient_address']	=	$item->patient_info->patient_address;
				$data['unsynched_list'][$i]['patient_address2']	=	$item->patient_info->patient_address2;
				$data['unsynched_list'][$i]['patient_address3']	=	$item->patient_info->patient_address3;
				$data['unsynched_list'][$i]['patient_town']	=	$item->patient_info->patient_town;
				$data['unsynched_list'][$i]['patient_state']	=	$item->patient_info->patient_state;
				$data['unsynched_list'][$i]['patient_postcode']	=	$item->patient_info->patient_postcode;
				$data['unsynched_list'][$i]['patient_country']	=	$item->patient_info->patient_country;
				$data['unsynched_list'][$i]['tel_home']	=	$item->patient_info->tel_home;
				$data['unsynched_list'][$i]['tel_office']	=	$item->patient_info->tel_office;
				$data['unsynched_list'][$i]['tel_mobile']	=	$item->patient_info->tel_mobile;
				$data['unsynched_list'][$i]['fax_no']	=	$item->patient_info->fax_no;
				$data['unsynched_list'][$i]['email']	=	$item->patient_info->email;
				$data['unsynched_list'][$i]['other']	=	$item->patient_info->other;
				$data['unsynched_list'][$i]['summary_id']	=	$item->episode_info->summary_id;
				$data['unsynched_list'][$i]['final']		=	"FALSE"; // Initialise

                // Check for existing patient
                $data['duplicate_patient'] = $this->memr_rdb->get_patients_list('all','birth_date',$data['unsynched_list'][$i]['patient_name']);
                $data['same_patient_id'] = $this->memr_rdb->get_patients_listby_anything('All','name','patient_id',$data['unsynched_list'][$i]['patient_id']);
                $data['same_birthdate'] = $this->memr_rdb->get_patients_listby_anything('All','name','birth_date',$data['unsynched_list'][$i]['birth_date']);
                if(!empty($data['unsynched_list'][$i]['ic_no'])){
                    $data['same_ic_no'] = $this->memr_rdb->get_patients_listby_anything('All','name','ic_no',$data['unsynched_list'][$i]['ic_no']);
                }
                if(isset($data['unsynched_list'][$i]['pns_pat_id'])){
                    $data['same_pns_pat_id'] = $this->memr_rdb->get_patients_listby_anything('All','name','pns_pat_id',$data['unsynched_list'][$i]['pns_pat_id']);
                }
                if(!empty($data['unsynched_list'][$i]['nhfa_no'])){
                    $data['same_nhfa_no'] = $this->memr_rdb->get_patients_listby_anything('All','name','nhfa_no',$data['unsynched_list'][$i]['nhfa_no']);
                }
				// Compare array against selected list and Flag as selected
				for ($j=1; $j <= $data['total_selected']; $j++) {
					if($data['debug_mode']){
						echo "<br />j = ".$j; 
						echo "<br />selected_list = ";
						echo $data['selected_list'][$j]['summary_id'];
						echo "<br />unsynched_list = ";
						echo $data['unsynched_list'][$i]['summary_id'];
					}
					if($data['selected_list'][$j]['summary_id'] == $data['unsynched_list'][$i]['summary_id']){
						// User selected this for importing
						$data['unsynched_list'][$i]['final']	=	"TRUE";
						$data['unsynched_list'][$i]['staff_id']	=	$item->episode_info->staff_id;
						$data['unsynched_list'][$i]['adt_id']	=	$item->episode_info->adt_id;
						$data['unsynched_list'][$i]['location_id']	=	$item->episode_info->location_id;
						$data['unsynched_list'][$i]['session_type']	=	$item->episode_info->session_type;
						$data['unsynched_list'][$i]['date_started']	=	(string)$item->episode_info->date_started;
						$data['unsynched_list'][$i]['time_started']	=	(string)$item->episode_info->time_started;
						$data['unsynched_list'][$i]['date_ended']	=	(string)$item->episode_info->date_ended;
						$data['unsynched_list'][$i]['time_ended']	=	(string)$item->episode_info->time_ended;
						$data['unsynched_list'][$i]['signed_by']	=	(string)$item->episode_info->signed_by;
						$data['unsynched_list'][$i]['check_in_date']	=	$data['unsynched_list'][$i]['date_started'];
						$data['unsynched_list'][$i]['check_in_time']	=	$data['unsynched_list'][$i]['time_started'];
						//$data['unsynched_list'][$i]['check_in_date']	=	(string)$item->episode_info->check_in_date;
						//$data['unsynched_list'][$i]['check_in_time']	=	(string)$item->episode_info->check_in_time;
						$data['unsynched_list'][$i]['location_start']	=	$item->episode_info->location_start;
						$data['unsynched_list'][$i]['location_end']	=	$item->episode_info->location_end;
						$data['unsynched_list'][$i]['episode_summary']	=	(string)$item->episode_info->episode_summary;
						$data['unsynched_list'][$i]['episode_status']	=	$item->episode_info->episode_status;
						$data['unsynched_list'][$i]['episode_remarks']	=	(string)$item->episode_info->episode_remarks;
						$data['unsynched_list'][$i]['synch_start']	=	(string)$item->episode_info->synch_start;
						$data['unsynched_list'][$i]['synch_out']	=	(string)$item->episode_info->synch_out;
						$data['unsynched_list'][$i]['count_complaints']	=	$item->episode_info->count_complaints;
						$data['unsynched_list'][$i]['count_vitals']	=	$item->episode_info->count_vitals;
						$data['unsynched_list'][$i]['count_lab']	=	$item->episode_info->count_lab;
						$data['unsynched_list'][$i]['count_imaging']	=	$item->episode_info->count_imaging;
						$data['unsynched_list'][$i]['count_procedures']	=	$item->episode_info->count_procedures;
						$data['unsynched_list'][$i]['count_diagnosis']	=	$item->episode_info->count_diagnosis;
						$data['unsynched_list'][$i]['count_prescribe']	=	$item->episode_info->count_prescribe;
						$data['unsynched_list'][$i]['count_referrals']	=	$item->episode_info->count_referrals;
						$data['unsynched_list'][$i]['count_others']	=	$item->episode_info->count_others;
						// Write to DB
						$ins_episode_array   =   array();
						$ins_episode_array['staff_id']              =   $data['unsynched_list'][$i]['staff_id'];
						$ins_episode_array['adt_id']                =   $data['unsynched_list'][$i]['adt_id'];
						$ins_episode_array['location_id']                =   $data['unsynched_list'][$i]['location_id'];
						$ins_episode_array['summary_id']            =   $data['unsynched_list'][$i]['summary_id'];
						$ins_episode_array['session_type']          =   $data['unsynched_list'][$i]['session_type'];
						$ins_episode_array['patient_id']            =   $data['unsynched_list'][$i]['patient_id'];
						$ins_episode_array['date_started']          =   $data['unsynched_list'][$i]['date_started']	; // session start date
						$ins_episode_array['time_started']          =   $data['unsynched_list'][$i]['time_started'];
						$ins_episode_array['date_ended']          =   $data['unsynched_list'][$i]['date_ended'];
						$ins_episode_array['time_ended']          =   $data['unsynched_list'][$i]['time_ended'];
						$ins_episode_array['signed_by']          =   $data['unsynched_list'][$i]['signed_by'];
						$ins_episode_array['check_in_date']         =   $data['unsynched_list'][$i]['check_in_date'];
						$ins_episode_array['check_in_time']         =   $data['unsynched_list'][$i]['check_in_time'];
						//$ins_episode_array['location_id']           =   $data['init_location_id'];
						$ins_episode_array['location_start']        =   $data['unsynched_list'][$i]['location_start'];
						$ins_episode_array['location_end']          =   $data['unsynched_list'][$i]['location_end'];
						$ins_episode_array['summary']          =   $data['unsynched_list'][$i]['episode_summary'];
						$ins_episode_array['start_date']            =   $ins_episode_array['date_started']; // ambiguous
						$ins_episode_array['session_id']            =   $data['now_id'];
						$ins_episode_array['status']                =   $data['unsynched_list'][$i]['episode_status'];
						$ins_episode_array['remarks']               =   $data['unsynched_list'][$i]['episode_remarks'];
						$ins_episode_array['now_id']                =   $data['now_id'];
						$ins_episode_array['synch_start']       = $data['unsynched_list'][$i]['synch_start'];
						$ins_episode_array['synch_out']      = $data['unsynched_list'][$i]['synch_out'];
						//$ins_episode_data       =   $this->memr_wdb->insert_new_episode($ins_episode_array);
						
						// Complaints segment
						if($data['unsynched_list'][$i]['count_complaints'] > 0){
                            if($data['debug_mode']){
                                echo "\n<br />Importing patient complaints";
                                echo "<br />i = ".$i;
                            }
							$k = $i-1; // Since i starts with 1 and not 0
							//foreach ($xml->clinical_episode->complaints_info as $complaint) {
							for($l=0; $l <= ($data['unsynched_list'][$i]['count_complaints'] - 1); $l++){
								$data['unsynched_list'][$i]['complaints_info'][$l]['recno']	=	(string)$xml->clinical_episode[$k]->complaints_info[$l]->recno;
								$data['unsynched_list'][$i]['complaints_info'][$l]['complaint_id']	=	(string)$xml->clinical_episode[$k]->complaints_info[$l]->complaint_id;
								$data['unsynched_list'][$i]['complaints_info'][$l]['icpc_code']	=	(string)$xml->clinical_episode[$k]->complaints_info[$l]->icpc_code;
								$data['unsynched_list'][$i]['complaints_info'][$l]['duration']	=	(string)$xml->clinical_episode[$k]->complaints_info[$l]->duration;
								$data['unsynched_list'][$i]['complaints_info'][$l]['complaint_notes']	=	(string)$xml->clinical_episode[$k]->complaints_info[$l]->complaint_notes;
								//$data['unsynched_list'][$i]['complaints_info'][$l]['ccode1ext_code']	=	(string)$xml->clinical_episode[$k]->complaints_info[$l]->ccode1ext_code;
								//$data['unsynched_list'][$i]['complaints_info'][$l]['complaint_rank']	=	(string)$xml->clinical_episode[$k]->complaints_info[$l]->complaint_rank;
								$data['unsynched_list'][$i]['complaints_info'][$l]['remarks']	=	(string)$xml->clinical_episode[$k]->complaints_info[$l]->remarks;
								//$k++;
								// New complaint record
								$ins_complaint_array   =   array();
								$ins_complaint_array['staff_id']           = $ins_episode_array['staff_id'];
								$ins_complaint_array['now_id']             = $data['now_id'];
								$ins_complaint_array['complaint_id']       = $data['unsynched_list'][$i]['complaints_info'][$l]['complaint_id'];
								$ins_complaint_array['patient_id']         = $ins_episode_array['patient_id'];
								$ins_complaint_array['session_id']         = $ins_episode_array['summary_id'];
								$ins_complaint_array['icpc_code']          = $data['unsynched_list'][$i]['complaints_info'][$l]['icpc_code'];
								$ins_complaint_array['duration']           = $data['unsynched_list'][$i]['complaints_info'][$l]['duration'];
								$ins_complaint_array['complaint_notes']    = $data['unsynched_list'][$i]['complaints_info'][$l]['complaint_notes'];
								//$ins_complaint_array['ccode1ext_code']     = $data['unsynched_list'][$i]['complaints_info'][$l]['ccode1ext_code'];
								$ins_complaint_array['remarks']            = $data['unsynched_list'][$i]['complaints_info'][$l]['remarks'];
								$ins_complaint_array['synch_out']          = $ins_episode_array['synch_out'];// Which sync_out?
								//$ins_complaint_data       =   $this->memr_wdb->insert_new_complaint($ins_complaint_array,$data['offline_mode']);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_complaints'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['complaints_info']";
								print_r($data['unsynched_list'][$i]['complaints_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_complaints'] > 0)

						// Vitals segment
						if($data['unsynched_list'][$i]['count_vitals'] > 0){
                            if($data['debug_mode']){
                                echo "\n<br />Importing vital signs";
                                echo "<br />i = ".$i;
                            }
							$k = $i-1; // Since i starts with 1 and not 0
							for($l=0; $l <= ($data['unsynched_list'][$i]['count_vitals'] - 1); $l++){
								$data['unsynched_list'][$i]['vitals_info'][$l]['recno']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->recno;
								$data['unsynched_list'][$i]['vitals_info'][$l]['vital_id']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->vital_id;
								$data['unsynched_list'][$i]['vitals_info'][$l]['reading_date']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->reading_date;
								$data['unsynched_list'][$i]['vitals_info'][$l]['reading_time']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->reading_time;
								$data['unsynched_list'][$i]['vitals_info'][$l]['height']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->height;
								$data['unsynched_list'][$i]['vitals_info'][$l]['weight']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->weight;
								$data['unsynched_list'][$i]['vitals_info'][$l]['left_vision']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->left_vision;
								$data['unsynched_list'][$i]['vitals_info'][$l]['right_vision']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->right_vision;
								$data['unsynched_list'][$i]['vitals_info'][$l]['temperature']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->temperature;
								$data['unsynched_list'][$i]['vitals_info'][$l]['pulse_rate']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->pulse_rate;
								$data['unsynched_list'][$i]['vitals_info'][$l]['bmi']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->bmi;
								$data['unsynched_list'][$i]['vitals_info'][$l]['waist_circumference']	=	$xml->clinical_episode[$k]->vitals_info[$l]->waist_circumference;
								$data['unsynched_list'][$i]['vitals_info'][$l]['bp_systolic']	=	$xml->clinical_episode[$k]->vitals_info[$l]->bp_systolic;
								$data['unsynched_list'][$i]['vitals_info'][$l]['bp_diastolic']	=	$xml->clinical_episode[$k]->vitals_info[$l]->bp_diastolic;
								$data['unsynched_list'][$i]['vitals_info'][$l]['respiration_rate']	=	$xml->clinical_episode[$k]->vitals_info[$l]->respiration_rate;
								$data['unsynched_list'][$i]['vitals_info'][$l]['ofc']	=	$xml->clinical_episode[$k]->vitals_info[$l]->ofc;
								$data['unsynched_list'][$i]['vitals_info'][$l]['remarks']	=	(string)$xml->clinical_episode[$k]->vitals_info[$l]->remarks;
								//$k++;
								// New patient vital signs
								$ins_vitals_array   =   array();
								$ins_vitals_array['staff_id']           = $ins_episode_array['staff_id'];
								$ins_vitals_array['now_id']             = $data['now_id'];
								$ins_vitals_array['vital_id']           = $data['unsynched_list'][$i]['vitals_info'][$l]['vital_id'];
								$ins_vitals_array['patient_id']         = $ins_episode_array['patient_id'];
								$ins_vitals_array['session_id']         = $ins_episode_array['summary_id'];
								//$ins_vitals_array['adt_id']             = $data['summary_id'];
								$ins_vitals_array['reading_date']       = $data['unsynched_list'][$i]['vitals_info'][$l]['reading_date'];
								$ins_vitals_array['reading_time']       = $data['unsynched_list'][$i]['vitals_info'][$l]['reading_time'];
								$ins_vitals_array['height']             = $data['unsynched_list'][$i]['vitals_info'][$l]['height'];
								$ins_vitals_array['weight']             = $data['unsynched_list'][$i]['vitals_info'][$l]['weight'];
								$ins_vitals_array['left_vision']        = $data['unsynched_list'][$i]['vitals_info'][$l]['left_vision'];
								$ins_vitals_array['right_vision']       = $data['unsynched_list'][$i]['vitals_info'][$l]['right_vision'];
								$ins_vitals_array['temperature']        = $data['unsynched_list'][$i]['vitals_info'][$l]['temperature'];
								$ins_vitals_array['pulse_rate']         = $data['unsynched_list'][$i]['vitals_info'][$l]['pulse_rate'];
								$ins_vitals_array['bmi']                = $data['unsynched_list'][$i]['vitals_info'][$l]['bmi'];
								if(is_numeric($data['unsynched_list'][$i]['vitals_info'][$l]['waist_circumference'])){
								//if($data['unsynched_list'][$i]['vitals_info'][$l]['waist_circumference'] > 0){
									$ins_vitals_array['waist_circumference']= $data['unsynched_list'][$i]['vitals_info'][$l]['waist_circumference'];
								}
								if(is_numeric($data['unsynched_list'][$i]['vitals_info'][$l]['bp_systolic'])){
									$ins_vitals_array['bp_systolic']        = $data['unsynched_list'][$i]['vitals_info'][$l]['bp_systolic'];
								}
								if(is_numeric($data['unsynched_list'][$i]['vitals_info'][$l]['bp_diastolic'])){
									$ins_vitals_array['bp_diastolic']       = $data['unsynched_list'][$i]['vitals_info'][$l]['bp_diastolic'];
								}
								if(is_numeric($data['unsynched_list'][$i]['vitals_info'][$l]['respiration_rate'])){
									$ins_vitals_array['respiration_rate']   = $data['unsynched_list'][$i]['vitals_info'][$l]['respiration_rate'];
								}
								if(is_numeric($data['unsynched_list'][$i]['vitals_info'][$l]['ofc'])){
									$ins_vitals_array['ofc']                = $data['unsynched_list'][$i]['vitals_info'][$l]['ofc'];
								}
								$ins_vitals_array['remarks']            = $data['unsynched_list'][$i]['vitals_info'][$l]['remarks'];
								$ins_vitals_array['synch_out']         = $ins_episode_array['synch_out'];
								//$ins_vitals_data       =   $this->memr_wdb->insert_new_vitals($ins_vitals_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_vitals'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['vitals_info']";
								print_r($data['unsynched_list'][$i]['vitals_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_vitals'] > 0)
						
						// Lab Orders segment
						if($data['unsynched_list'][$i]['count_lab'] > 0){
                            if($data['debug_mode']){
                                echo "\n<br />Importing lab orders";
                                echo "<br />i = ".$i;
                            }
							$k = $i-1; // Since i starts with 1 and not 0
							for($l=0; $l <= ($data['unsynched_list'][$i]['count_lab'] - 1); $l++){
								$data['unsynched_list'][$i]['lab_info'][$l]['recno']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->recno;
								$data['unsynched_list'][$i]['lab_info'][$l]['lab_order_id']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->lab_order_id;
								$data['unsynched_list'][$i]['lab_info'][$l]['lab_package_id']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->lab_package_id;
								$data['unsynched_list'][$i]['lab_info'][$l]['sample_ref']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->sample_ref;
								$data['unsynched_list'][$i]['lab_info'][$l]['sample_date']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->sample_date;
								$data['unsynched_list'][$i]['lab_info'][$l]['sample_time']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->sample_time;
								$data['unsynched_list'][$i]['lab_info'][$l]['fasting']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->fasting;
								$data['unsynched_list'][$i]['lab_info'][$l]['urgency']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->urgency;
								$data['unsynched_list'][$i]['lab_info'][$l]['summary_result']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->summary_result;
								$data['unsynched_list'][$i]['lab_info'][$l]['result_status']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->result_status;
								$data['unsynched_list'][$i]['lab_info'][$l]['invoice_status']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->invoice_status;
								$data['unsynched_list'][$i]['lab_info'][$l]['invoice_detail_id']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->invoice_detail_id;
								$data['unsynched_list'][$i]['lab_info'][$l]['remarks']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->remarks;
								$data['unsynched_list'][$i]['lab_info'][$l]['result_reviewed_by']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->result_reviewed_by;
								$data['unsynched_list'][$i]['lab_info'][$l]['result_reviewed_date']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->result_reviewed_date;
								$data['unsynched_list'][$i]['lab_info'][$l]['package_code']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->package_code;
								$data['unsynched_list'][$i]['lab_info'][$l]['package_name']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->package_name;
								$data['unsynched_list'][$i]['lab_info'][$l]['supplier_name']	=	(string)$xml->clinical_episode[$k]->lab_info[$l]->supplier_name;
								//$k++;
								// New lab order record
								$ins_lab_array   =   array();
								$ins_lab_array['now_id']          = $data['now_id'];
								$ins_lab_array['lab_order_id']    = $data['unsynched_list'][$i]['lab_info'][$l]['lab_order_id'];
								$ins_lab_array['staff_id']        = $ins_episode_array['staff_id'];
								$ins_lab_array['patient_id']      = $ins_episode_array['patient_id'];
								$ins_lab_array['session_id']      = $ins_episode_array['summary_id'];
								$ins_lab_array['lab_package_id']  = $data['unsynched_list'][$i]['lab_info'][$l]['lab_package_id'];
								//$ins_lab_array['product_id']      = $data['product_id'];//Deprecate
								$ins_lab_array['sample_ref']      = $data['unsynched_list'][$i]['lab_info'][$l]['sample_ref'];
								$ins_lab_array['sample_date']     = $data['unsynched_list'][$i]['lab_info'][$l]['sample_date'];
								$ins_lab_array['sample_time']     = $data['unsynched_list'][$i]['lab_info'][$l]['sample_time'];
								$ins_lab_array['fasting']         = $data['unsynched_list'][$i]['lab_info'][$l]['fasting'];
								$ins_lab_array['urgency']         = $data['unsynched_list'][$i]['lab_info'][$l]['urgency'];
								$ins_lab_array['result_status']   = $data['unsynched_list'][$i]['lab_info'][$l]['result_status'];
								$ins_lab_array['invoice_status']  = $data['unsynched_list'][$i]['lab_info'][$l]['invoice_status'];
								//$ins_lab_array['invoice_detail_id']= $data['invoice_detail_id']; //N/A
								$ins_lab_array['remarks']         = $data['unsynched_list'][$i]['lab_info'][$l]['remarks'];
								$ins_lab_array['synch_out']       = $ins_episode_array['synch_out'];
								//$ins_lab_data  =   $this->memr_wdb->insert_new_lab_order($ins_lab_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_diagnosis'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['lab_info']";
								print_r($data['unsynched_list'][$i]['lab_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_lab'] > 0)
						
						// Imaging Orders segment
						if($data['unsynched_list'][$i]['count_imaging'] > 0){
                            if($data['debug_mode']){
                                echo "\n<br />Importing imaging orders";
                                echo "<br />i = ".$i;
                            }
							$k = $i-1; // Since i starts with 1 and not 0
							for($l=0; $l <= ($data['unsynched_list'][$i]['count_imaging'] - 1); $l++){
								$data['unsynched_list'][$i]['imaging_info'][$l]['recno']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->recno;
								$data['unsynched_list'][$i]['imaging_info'][$l]['imaging_order_id']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->imaging_order_id;
								$data['unsynched_list'][$i]['imaging_info'][$l]['supplier_ref']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->supplier_ref;
								$data['unsynched_list'][$i]['imaging_info'][$l]['product_id']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->product_id;
								$data['unsynched_list'][$i]['imaging_info'][$l]['result_status']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->result_status;
								$data['unsynched_list'][$i]['imaging_info'][$l]['invoice_status']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->invoice_status;
								$data['unsynched_list'][$i]['imaging_info'][$l]['order_remarks']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->order_remarks;
								$data['unsynched_list'][$i]['imaging_info'][$l]['product_code']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->product_code;
								$data['unsynched_list'][$i]['imaging_info'][$l]['loinc_num']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->loinc_num;
								$data['unsynched_list'][$i]['imaging_info'][$l]['description']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->description;
								$data['unsynched_list'][$i]['imaging_info'][$l]['supplier_name']	=	(string)$xml->clinical_episode[$k]->imaging_info[$l]->supplier_name;
								//$k++;
								// New imaging order record
								$ins_imaging_array   =   array();
								$ins_imaging_array['now_id']          = $data['now_id'];
								$ins_imaging_array['order_id']        = $data['unsynched_list'][$i]['imaging_info'][$l]['imaging_order_id'];
								$ins_imaging_array['staff_id']        = $ins_episode_array['staff_id'];
								$ins_imaging_array['patient_id']      = $ins_episode_array['patient_id'];
								$ins_imaging_array['session_id']      = $ins_episode_array['summary_id'];
								$ins_imaging_array['product_id']        = $data['unsynched_list'][$i]['imaging_info'][$l]['product_id'];
								$ins_imaging_array['supplier_ref']      = $data['unsynched_list'][$i]['imaging_info'][$l]['supplier_ref'];
								$ins_imaging_array['result_status']     = $data['unsynched_list'][$i]['imaging_info'][$l]['result_status'];
								$ins_imaging_array['invoice_status']     = $data['unsynched_list'][$i]['imaging_info'][$l]['invoice_status'];
								$ins_imaging_array['remarks']         = $data['unsynched_list'][$i]['imaging_info'][$l]['order_remarks'];
								$ins_imaging_array['synch_out']       = $ins_episode_array['synch_out'];
								//$ins_imaging_data  =   $this->memr_wdb->insert_new_imaging_order($ins_imaging_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_imaging'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['imaging_info']";
								print_r($data['unsynched_list'][$i]['imaging_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_imaging'] > 0)
						
						// Diagnosis segment
						if($data['unsynched_list'][$i]['count_diagnosis'] > 0){
                            if($data['debug_mode']){
                                echo "\n<br />Importing diagnoses";
                                echo "<br />i = ".$i;
                            }
							$k = $i-1; // Since i starts with 1 and not 0
							//foreach ($xml->clinical_episode->complaints_info as $complaint) {
							for($l=0; $l <= ($data['unsynched_list'][$i]['count_diagnosis'] - 1); $l++){
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['recno']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->recno;
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['diagnosis_id']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->diagnosis_id;
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['diagnosis_type']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->diagnosis_type;
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['diagnosis_notes']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->diagnosis_notes;
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['dcode1set']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->dcode1set;
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['dcode1ext_code']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->dcode1ext_code;
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['dcode2set']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->dcode2set;
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['dcode2ext_code']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->dcode2ext_code;
								$data['unsynched_list'][$i]['diagnosis_info'][$l]['remarks']	=	(string)$xml->clinical_episode[$k]->diagnosis_info[$l]->remarks;
								//$k++;
								// New diagnosis record
								$ins_diagnosis_array   =   array();
								$ins_diagnosis_array['staff_id']           = $ins_episode_array['staff_id'];
								$ins_diagnosis_array['now_id']             = $data['now_id'];
								$ins_diagnosis_array['diagnosis_id']       = $data['unsynched_list'][$i]['diagnosis_info'][$l]['diagnosis_id'];
								$ins_diagnosis_array['patient_id']         = $ins_episode_array['patient_id'];
								$ins_diagnosis_array['session_id']         = $ins_episode_array['summary_id'];
								$ins_diagnosis_array['diagnosis_type']     = $data['unsynched_list'][$i]['diagnosis_info'][$l]['diagnosis_type'];
								$ins_diagnosis_array['diagnosis_notes']    = $data['unsynched_list'][$i]['diagnosis_info'][$l]['diagnosis_notes'];
								$ins_diagnosis_array['dcode1set']          = $data['unsynched_list'][$i]['diagnosis_info'][$l]['dcode1set'];
								$ins_diagnosis_array['dcode1ext_code']     = $data['unsynched_list'][$i]['diagnosis_info'][$l]['dcode1ext_code'];
								$ins_diagnosis_array['remarks']            = $data['unsynched_list'][$i]['diagnosis_info'][$l]['remarks'];
								$ins_diagnosis_array['synch_out']          = $ins_episode_array['synch_out'];
								//$ins_diagnosis_data       =   $this->memr_wdb->insert_new_diagnosis($ins_diagnosis_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_diagnosis'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['diagnosis_info']";
								print_r($data['unsynched_list'][$i]['diagnosis_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_diagnosis'] > 0)
						
						// Prescription segment
						if($data['unsynched_list'][$i]['count_prescribe'] > 0){
                            if($data['debug_mode']){
                                echo "\n<br />Importing prescriptions";
                                echo "<br />i = ".$i;
                            }
							$k = $i-1; // Since i starts with 1 and not 0
							for($l=0; $l <= ($data['unsynched_list'][$i]['count_prescribe'] - 1); $l++){
								$data['unsynched_list'][$i]['prescribe_info'][$l]['recno']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->recno;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['queue_id']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->queue_id;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['drug_formulary_id']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->drug_formulary_id;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['dose']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->dose;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['dose_form']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->dose_form;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['frequency']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->frequency;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['instruction']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->instruction;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['quantity']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->quantity;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['quantity_form']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->quantity_form;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['indication']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->indication;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['caution']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->caution;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['status']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->status;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['formulary_code']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->formulary_code;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['generic_name']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->generic_name;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['formulary_system']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->formulary_system;
								//$k++;
								// New prescription record
								$ins_prescribe_array   =   array();
								$ins_prescribe_array['staff_id']         = $ins_episode_array['staff_id'];
								$ins_prescribe_array['now_id']           = $data['now_id'];
								$ins_prescribe_array['prescribe_id']     = $data['unsynched_list'][$i]['prescribe_info'][$l]['queue_id'];
								$ins_prescribe_array['patient_id']       = $ins_episode_array['patient_id'];
								$ins_prescribe_array['session_id']       = $ins_episode_array['summary_id'];
								$ins_prescribe_array['drug_formulary_id']= $data['unsynched_list'][$i]['prescribe_info'][$l]['drug_formulary_id'];
								$ins_prescribe_array['dose']             = $data['unsynched_list'][$i]['prescribe_info'][$l]['dose'];
								$ins_prescribe_array['dose_form']        = $data['unsynched_list'][$i]['prescribe_info'][$l]['dose_form'];
								$ins_prescribe_array['frequency']        = $data['unsynched_list'][$i]['prescribe_info'][$l]['frequency'];
								$ins_prescribe_array['instruction']      = $data['unsynched_list'][$i]['prescribe_info'][$l]['instruction'];
								$ins_prescribe_array['quantity']         = $data['unsynched_list'][$i]['prescribe_info'][$l]['quantity'];
								$ins_prescribe_array['quantity_form']    = $data['unsynched_list'][$i]['prescribe_info'][$l]['quantity_form'];
								$ins_prescribe_array['indication']       = $data['unsynched_list'][$i]['prescribe_info'][$l]['indication'];
								$ins_prescribe_array['caution']          = $data['unsynched_list'][$i]['prescribe_info'][$l]['caution'];
								$ins_prescribe_array['status']           = $data['unsynched_list'][$i]['prescribe_info'][$l]['status'];
								$ins_prescribe_array['synch_out']        = $ins_episode_array['synch_out'];
								//$ins_prescribe_data       =   $this->memr_wdb->insert_new_prescribe($ins_prescribe_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_diagnosis'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['prescribe_info']";
								print_r($data['unsynched_list'][$i]['prescribe_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_prescribe'] > 0)
						
					} else {
						//$data['unsynched_list'][$i]['final']	=	"FALSE";
						echo "FALSE";
					} //endif($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id'])
				} //endfor ($j=1; $j <= $data['total_selected']; $j++)
				$i++;
			} // endforeach ($xml->patient_info as $item)
			//echo form_open('ehr_admin/admin_mgt');
			//echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
			//echo "Saved. <input type='submit' value='Click to Continue' />";
			echo "</form>";
			
		//} //endif(count($_POST))
        return $data;
/*
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/emr_newpage_wap";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_refer_importview_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		//$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
*/
		
    } // end of function admin_import_new_referview($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES



/*
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
    function cb_correct_date($date_string)  // Call back
    {
        $data['app_minyear']		    =	$this->config->item('app_minyear');
        $data['app_maxyear']		    =	$this->config->item('app_maxyear');
		// Check if it is YYYY-MM-DD
		if(ereg("^[1|2]{1}[9|0]{1}[0-9]{2}-[0-1]{1}[0-9]{1}-[0-3]{1}[0-9]{1}", $date_string)){
			// Check if it is a valid date
            $broken_birth_date      =   $this->break_date($date_string);			
			if(checkdate($broken_birth_date['mm'],$broken_birth_date['dd'],$broken_birth_date['yyyy'])){
				// Check if year is within limits
				if(($broken_birth_date['yyyy'] < $data['app_maxyear']) && ($broken_birth_date['yyyy'] > $data['app_minyear'])){
					return TRUE;
				} else {
					$this->form_validation->set_message('cb_correct_date', 'The %s field format is outside valid range.');
					return FALSE;
				}
			} else {
				$this->form_validation->set_message('cb_correct_date', 'The %s field format is incorrect.');
				return FALSE;
			}
		} else {
			$this->form_validation->set_message('cb_correct_date', 'The %s field format is incorrect.');
			return FALSE;
		}
    } // end of function cb_correct_date($date_string)
*/


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

/* End of file ehr_individual_refer.php */
/* Location: ./app_thirra/controllers/ehr_individual_refer.php */
