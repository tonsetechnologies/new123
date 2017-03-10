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
 * Controller Class for EHR_ADMIN
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_admin_edi_export extends MY_Controller 
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
    // === ADMIN EDI MANAGEMENT
    // ------------------------------------------------------------------------
    function admin_export_logins($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Export Logins";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_logins('ALL');
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_logins('ALL',TRUE);
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
            $new_body   =   "ehr/ehr_admin_export_logins_html";
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
    } // end of function admin_export_logins($id)


    // ------------------------------------------------------------------------
    function admin_export_new_logins_done($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
        $data['export_clinic']    =   $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $data['baseurl']            =   base_url();
        $data['exploded_baseurl']   =   explode('/', $data['baseurl'], 4);
        $data['app_folder']         =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']        =    $data['app_path']."-uploads/exports_system";
        $version_file_path          = $data['app_path']."/app_thirra/version.txt";
        $handle = fopen($version_file_path, "r");
        $app_version = fread($handle, filesize($version_file_path));
        fclose($handle);

        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
            $data['export_reference']   =   $_POST['reference'];
            $data['export_remarks']     =   $_POST['remarks'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_logins>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$data['export_clinic']['clinic_name']."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$data['export_clinic']['clinic_ref_no']."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
			$selected		=	1;
            
					//$data['unsynched_list'][$selected]['number']	= $i;
					//$data['unsynched_list'][$selected]['value']	= $_POST['s'.$i];
					//list($patient_immunisation_id, $immunisation_id, $patient_id) = explode("-.-", $data['unsynched_list'][$selected]['value']);					//$data['unsynched_list'][$selected]['patient_info']   = $this->memr_rdb->get_patient_demo($patient_id);
					//$data['unsynched_list'][$selected]['immunisation_info'] = $this->memr_rdb->get_vaccines_list($patient_id,$immunisation_id);
            $data['unsynched_list'] = $this->madmin_rdb->get_unsynched_logins('ALL');
                if($data['debug_mode']){
                    echo "<pre>";
                    echo "\n<br />print_r(unsynched_logins)=<br />";
                    print_r($data['unsynched_list']);
                    echo "</pre>";
                }
            foreach($data['unsynched_list'] as $login){
                $log_id 	        = $login['log_id'];
                $log_date 	        = $login['date'];
                $user_id 	        = $login['user_id'];
                $login_time 	    = $login['login_time'];
                $logout_time 	    = $login['logout_time'];
                $login_location 	= $login['login_location'];
                $login_ip 	        = $login['login_ip'];
                $webbrowser 	    = $login['webbrowser'];
                $synch_out 		    = $login['synch_out'];
                $synch_remarks 		= $login['synch_remarks'];
                $log_outcome 	    = $login['log_outcome'];
                $username 	        = $login['username'];
                $count_others		= 0;
                
                $xmlstr .= "\n<history_login log_id='$log_id'>";
                
                $xmlstr .= "\n\t\t<log_id>$log_id</log_id>";
                $xmlstr .= "\n\t\t<log_date>$log_date</log_date>";
                $xmlstr .= "\n\t\t<user_id>$user_id</user_id>";
                $xmlstr .= "\n\t\t<login_time>$login_time</login_time>";
                $xmlstr .= "\n\t\t<logout_time>$logout_time</logout_time>";
                $xmlstr .= "\n\t\t<login_location>$login_location</login_location>";
                $xmlstr .= "\n\t\t<login_ip>$login_ip</login_ip>";
                $xmlstr .= "\n\t\t<webbrowser>$webbrowser</webbrowser>";
                $xmlstr .= "\n\t\t<log_outcome>$log_outcome</log_outcome>";
                $xmlstr .= "\n\t\t<synch_out>$synch_out</synch_out>";
                $xmlstr .= "\n\t\t<synch_remarks>$synch_remarks</synch_remarks>";
                $xmlstr .= "\n\t\t<username>$username</username>";
                                                                            
                $xmlstr .= "\n</history_login>";
                $selected++;
                
                //Log patient_id's
                if(isset($data['entities_inserted'])){
                    $data['entities_inserted'] = $data['entities_inserted'].",";
                } else {
                    $data['entities_inserted'] = "";
                }
                $data['entities_inserted']  =   $data['entities_inserted'].$log_id;
            } //endforeach($data['unsynched_list'] as $login)
		} //endif(count($_POST))
		$data['title'] = "Exported New Logins";
        $data['now_id']             =   time();
		$data['file_exported']		=	"users_logins-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		//$address1 = $data['unsynched_list'][1]['patient_info']['patient_address'];
		//$xmlstr .= "\n\t<address1>$address1</address1>";
		$xmlstr .= "\n</THIRRA_export_logins>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

        // New log record
        $ins_log_array   =   array();
        $ins_log_array['data_synch_log_id'] = $data['now_id'];
        $ins_log_array['export_by']         = $_SESSION['staff_id'];
        $ins_log_array['export_when']       = $data['now_id'];
        $ins_log_array['thirra_version']    = $app_version;
        $ins_log_array['export_clinicname'] = $data['export_clinic']['clinic_name'];
        $ins_log_array['export_clinicref']  = $data['export_clinic']['clinic_ref_no'];
        $ins_log_array['export_reference']  = $data['export_reference'];
        //$ins_log_array['import_by']         = $_SESSION['staff_id'];
        //$ins_log_array['import_when']       = $data['now_id'];
        $ins_log_array['data_filename']     = $data['file_exported'];
        //$ins_log_array['import_remarks']    = $data['import_remarks'];
        //$ins_log_array['import_reference']  = $data['import_reference'];
        //$ins_log_array['import_number']     = $data['import_number'];
        //$ins_log_array['import_outcome']    = $data['import_outcome'];
        $ins_log_array['count_inserted']    = $selected - 1;
        //$ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
        //$ins_log_array['count_rejected']    = $data['count_rejected'];
        $ins_log_array['entities_inserted'] = $data['entities_inserted'];
        //$ins_log_array['entities_declined'] = $data['entities_declined'];
        //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
        //$ins_log_array['declined_list']     = $data['declined_list'];
        //$ins_log_array['rejected_list']     = $data['rejected_list'];
        $ins_log_array['outcome_remarks']   = "Success";
        $ins_log_array['sync_type']         = "Manual EDI - Logins Data";
        $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

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
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_export_new_logins_done_html";
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
    } // end of function admin_export_new_logins_done($id)


    // ------------------------------------------------------------------------
    function admin_export_patients($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['home_clinic']        =   $_SESSION['location_id'];
		$data['title'] = "Export Patients";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients('ALL');
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_patients('ALL',TRUE);
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
            $new_body   =   "ehr/ehr_admin_export_patients_html";
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
    } // end of function admin_export_patients($id)


    // ------------------------------------------------------------------------
    function admin_export_new_patients($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			$selected_count		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				if(isset($_POST['s'.$i])){
				// Only retrieve if selected by user
					$data['unsynched_list'][$selected_count]["number"]	= $i;
					$data['unsynched_list'][$selected_count]["value"]	= $_POST['s'.$i];
					list(
						$data['unsynched_list'][$selected_count]["patient_id"],
						$data['unsynched_list'][$selected_count]["name"],
						$data['unsynched_list'][$selected_count]["name_first"],
						$data['unsynched_list'][$selected_count]["birth_date"],
						$data['unsynched_list'][$selected_count]["gender"],
						$data['unsynched_list'][$selected_count]["synch_out"]
					)= explode("-.-", $data['unsynched_list'][$selected_count]["value"]);
					$selected_count++;
				}
			}
		} //endif(count($_POST))
		$data['title'] = "Export New Patients";
        $data['now_id']             =   time();
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
            $new_body   =   "ehr/ehr_admin_export_new_patients_html";
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
    } // end of function admin_export_new_patients($id)


    // ------------------------------------------------------------------------
    function admin_export_new_patientsdone($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
		$data['title']              = "Exported New Patients";
        $data['now_id']             =   time();
        $export_when                =   $data['now_id'];
        $export_by                  =   $_SESSION['staff_id'];
        $data['export_clinic']    =   $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $data['baseurl']            =   base_url();
        $data['exploded_baseurl']   =   explode('/', $data['baseurl'], 4);
        $data['app_folder']         =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']    =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']        =    $data['app_path']."-uploads/exports_patient";
        
        $version_file_path  = $data['app_path']."/app_thirra/version.txt";
        $handle             = fopen($version_file_path, "r");
        $app_version        = fread($handle, filesize($version_file_path));
        fclose($handle);

        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']           = $_POST['num_rows'];
            $data['export_reference']   =   $_POST['reference'];
            $data['export_remarks']     =   $_POST['remarks'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_patients>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$data['export_clinic']['clinic_name']."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$data['export_clinic']['clinic_ref_no']."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
			$selected		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$data['unsynched_list'][$selected]['number']	= $i;
					$data['unsynched_list'][$selected]['value']	= $_POST['s'.$i];
					$data['unsynched_list'][$selected]['patient_info'] 
						= $this->memr_rdb->get_patient_details($data['unsynched_list'][$selected]['value']);
					$patient_id 	= $data['unsynched_list'][$selected]['patient_info']['patient_id'];
					$patient_name 	= $data['unsynched_list'][$selected]['patient_info']['patient_name'];
					$name_first 	= $data['unsynched_list'][$selected]['patient_info']['name_first'];
					$name_alias 	= $data['unsynched_list'][$selected]['patient_info']['name_alias'];
					$gender 		= $data['unsynched_list'][$selected]['patient_info']['gender'];
					$ic_no 			= $data['unsynched_list'][$selected]['patient_info']['ic_no'];
					$ic_other_type  = $data['unsynched_list'][$selected]['patient_info']['ic_other_type'];
					$ic_other_no 	= $data['unsynched_list'][$selected]['patient_info']['ic_other_no'];
					$nationality 	= $data['unsynched_list'][$selected]['patient_info']['nationality'];
					$birth_date 	= $data['unsynched_list'][$selected]['patient_info']['birth_date'];
					$clinic_reference_number = $data['unsynched_list'][$selected]['patient_info']['clinic_reference_number'];
					$pns_pat_id 	= $data['unsynched_list'][$selected]['patient_info']['pns_pat_id'];
					$nhfa_no 		= $data['unsynched_list'][$selected]['patient_info']['nhfa_no'];
					$ethnicity 		= $data['unsynched_list'][$selected]['patient_info']['ethnicity'];
					$religion 		= $data['unsynched_list'][$selected]['patient_info']['religion'];
					$marital_status = $data['unsynched_list'][$selected]['patient_info']['marital_status'];
					$patient_type 	= $data['unsynched_list'][$selected]['patient_info']['patient_type'];
					$blood_group 	= $data['unsynched_list'][$selected]['patient_info']['blood_group'];
					$blood_rhesus 	= $data['unsynched_list'][$selected]['patient_info']['blood_rhesus'];
					$demise_date 	= $data['unsynched_list'][$selected]['patient_info']['demise_date'];
					$demise_time 	= $data['unsynched_list'][$selected]['patient_info']['demise_time'];
					$demise_cause 	= $data['unsynched_list'][$selected]['patient_info']['demise_cause'];
					$clinic_home 	= $data['unsynched_list'][$selected]['patient_info']['clinic_home'];
					$clinic_registered = $data['unsynched_list'][$selected]['patient_info']['clinic_registered'];
					$status = $data['unsynched_list'][$selected]['patient_info']['status'];
					$contact_id 	= $data['unsynched_list'][$selected]['patient_info']['contact_id'];
					$start_date 	= $data['unsynched_list'][$selected]['patient_info']['start_date'];
					$patient_address = $data['unsynched_list'][$selected]['patient_info']['patient_address'];
					$patient_address2 = $data['unsynched_list'][$selected]['patient_info']['patient_address2'];
					$patient_address3 = $data['unsynched_list'][$selected]['patient_info']['patient_address3'];
					$patient_town 	= $data['unsynched_list'][$selected]['patient_info']['patient_town'];
					$patient_postcode = $data['unsynched_list'][$selected]['patient_info']['patient_postcode'];
					$patient_state 	= $data['unsynched_list'][$selected]['patient_info']['patient_state'];
					$patient_country = $data['unsynched_list'][$selected]['patient_info']['patient_country'];
					$tel_home 		= $data['unsynched_list'][$selected]['patient_info']['tel_home'];
					$tel_office 	= $data['unsynched_list'][$selected]['patient_info']['tel_office'];
					$tel_mobile 	= $data['unsynched_list'][$selected]['patient_info']['tel_mobile'];
					$fax_no 		= $data['unsynched_list'][$selected]['patient_info']['fax_no'];
					$email 			= $data['unsynched_list'][$selected]['patient_info']['email'];
					$addr_village_id 	= $data['unsynched_list'][$selected]['patient_info']['addr_village_id'];
					//$addr_town_id 			= $data['unsynched_list'][$selected]['patient_info']['addr_town_id'];
					$addr_area_id 	= $data['unsynched_list'][$selected]['patient_info']['addr_area_id'];
					//$addr_district_id 			= $data['unsynched_list'][$selected]['patient_info']['addr_district_id'];
					//$addr_state_id 			= $data['unsynched_list'][$selected]['patient_info']['addr_state_id'];
					$staff_id 		= $data['unsynched_list'][$selected]['patient_info']['staff_id'];
					$synch_out 		= $data['unsynched_list'][$selected]['patient_info']['synch_out'];
					$synch_remarks 		= $data['unsynched_list'][$selected]['patient_info']['synch_remarks'];
					$xmlstr .= "\n<patient_info patient_id='$patient_id'>";
					$xmlstr .= "\n\t<patient_id>$patient_id</patient_id>";
					$xmlstr .= "\n\t<patient_name>$patient_name</patient_name>";
					$xmlstr .= "\n\t<name_first>$name_first</name_first>";
					$xmlstr .= "\n\t<name_alias>$name_alias</name_alias>";
					$xmlstr .= "\n\t<gender>$gender</gender>";
					$xmlstr .= "\n\t<ic_no>$ic_no</ic_no>";
					$xmlstr .= "\n\t<ic_other_type>$ic_other_type</ic_other_type>";
					$xmlstr .= "\n\t<ic_other_no>$ic_other_no</ic_other_no>";
					$xmlstr .= "\n\t<nationality>$nationality</nationality>";
					$xmlstr .= "\n\t<birth_date>$birth_date</birth_date>";
					$xmlstr .= "\n\t<clinic_reference_number>$clinic_reference_number</clinic_reference_number>";
					$xmlstr .= "\n\t<pns_pat_id>$pns_pat_id</pns_pat_id>";
					$xmlstr .= "\n\t<nhfa_no>$nhfa_no</nhfa_no>";
					$xmlstr .= "\n\t<ethnicity>$ethnicity</ethnicity>";
					$xmlstr .= "\n\t<religion>$religion</religion>";
					$xmlstr .= "\n\t<marital_status>$marital_status</marital_status>";
					$xmlstr .= "\n\t<patient_type>$patient_type</patient_type>";
					$xmlstr .= "\n\t<blood_group>$blood_group</blood_group>";
					$xmlstr .= "\n\t<blood_rhesus>$blood_rhesus</blood_rhesus>";
					$xmlstr .= "\n\t<demise_date>$demise_date</demise_date>";
					$xmlstr .= "\n\t<demise_time>$demise_time</demise_time>";
					$xmlstr .= "\n\t<demise_cause>$demise_cause</demise_cause>";
					$xmlstr .= "\n\t<clinic_home>$clinic_home</clinic_home>";
					$xmlstr .= "\n\t<clinic_registered>$clinic_registered</clinic_registered>";
					$xmlstr .= "\n\t<status>$status</status>";
					$xmlstr .= "\n\t<contact_id>$contact_id</contact_id>";
					$xmlstr .= "\n\t<start_date>$start_date</start_date>";
					$xmlstr .= "\n\t<patient_address>$patient_address</patient_address>";
					$xmlstr .= "\n\t<patient_address2>$patient_address2</patient_address2>";
					$xmlstr .= "\n\t<patient_address3>$patient_address3</patient_address3>";
					$xmlstr .= "\n\t<patient_town>$patient_town</patient_town>";
					$xmlstr .= "\n\t<patient_postcode>$patient_postcode</patient_postcode>";
					$xmlstr .= "\n\t<patient_state>$patient_state</patient_state>";
					$xmlstr .= "\n\t<patient_country>$patient_country</patient_country>";
					$xmlstr .= "\n\t<tel_home>$tel_home</tel_home>";
					$xmlstr .= "\n\t<tel_office>$tel_office</tel_office>";
					$xmlstr .= "\n\t<tel_mobile>$tel_mobile</tel_mobile>";
					$xmlstr .= "\n\t<fax_no>$fax_no</fax_no>";
					$xmlstr .= "\n\t<email>$email</email>";
					$xmlstr .= "\n\t<addr_village_id>$addr_village_id</addr_village_id>";
					//$xmlstr .= "\n\t<addr_town_id>$addr_town_id</addr_town_id>";
					$xmlstr .= "\n\t<addr_area_id>$addr_area_id</addr_area_id>";
					//$xmlstr .= "\n\t<addr_district_id>$addr_district_id</addr_district_id>";
					//$xmlstr .= "\n\t<addr_state_id>$addr_state_id</addr_state_id>";
					$xmlstr .= "\n\t<staff_id>$staff_id</staff_id>";
					$xmlstr .= "\n\t<synch_out>$synch_out</synch_out>";
					$xmlstr .= "\n\t<synch_remarks>$synch_remarks</synch_remarks>";
					$xmlstr .= "\n</patient_info>";
					$selected++;
                    
                    //Log patient_id's
                    if(isset($data['entities_inserted'])){
                        $data['entities_inserted'] = $data['entities_inserted'].",";
                    } else {
                        $data['entities_inserted'] = "";
                    }
                    $data['entities_inserted']  =   $data['entities_inserted'].$patient_id;
                        
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
		} //endif(count($_POST))
		$data['file_exported']		=	"patient_demo-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		$xmlstr .= "\n</THIRRA_export_patients>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

        // New log record
        $ins_log_array   =   array();
        $ins_log_array['data_synch_log_id'] = $data['now_id'];
        $ins_log_array['export_by']         = $_SESSION['staff_id'];
        $ins_log_array['export_when']       = $data['now_id'];
        $ins_log_array['thirra_version']    = $app_version;
        $ins_log_array['export_clinicname'] = $data['export_clinic']['clinic_name'];
        $ins_log_array['export_clinicref']  = $data['export_clinic']['clinic_ref_no'];
        $ins_log_array['export_reference']  = $data['export_reference'];
        //$ins_log_array['import_by']         = $_SESSION['staff_id'];
        //$ins_log_array['import_when']       = $data['now_id'];
        $ins_log_array['data_filename']     = $data['file_exported'];
        //$ins_log_array['import_remarks']    = $data['import_remarks'];
        //$ins_log_array['import_reference']  = $data['import_reference'];
        //$ins_log_array['import_number']     = $data['import_number'];
        //$ins_log_array['import_outcome']    = $data['import_outcome'];
        $ins_log_array['count_inserted']    = $selected - 1;
        //$ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
        //$ins_log_array['count_rejected']    = $data['count_rejected'];
        $ins_log_array['entities_inserted'] = $data['entities_inserted'];
        //$ins_log_array['entities_declined'] = $data['entities_declined'];
        //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
        //$ins_log_array['declined_list']     = $data['declined_list'];
        //$ins_log_array['rejected_list']     = $data['rejected_list'];
        $ins_log_array['outcome_remarks']   = "Success";
        $ins_log_array['sync_type']         = "Manual EDI - Patients Data";
        $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

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
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_export_new_patientsdone_html";
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
    } // end of function admin_export_new_patientsdone($id)


    // ------------------------------------------------------------------------
    function admin_export_antenatal_info($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Export Antenatal Event";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_antenatalinfo('ALL');
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_antenatalinfo('ALL',TRUE);
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
            $new_body   =   "ehr/ehr_admin_export_antenatalinfo_html";
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
    } // end of function admin_export_antenatal_info($id)


    // ------------------------------------------------------------------------
    function admin_export_new_antenatalinfo($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			$selected_count		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				if(isset($_POST['s'.$i])){
				// Only retrieve if selected by user
					$data['unsynched_list'][$selected_count]["number"]	= $i;
					$data['unsynched_list'][$selected_count]["value"]	= $_POST['s'.$i];
					list(
						$data['unsynched_list'][$selected_count]["antenatal_id"],
						$data['unsynched_list'][$selected_count]["patient_id"],
						$data['unsynched_list'][$selected_count]["name"],
						$data['unsynched_list'][$selected_count]["name_first"],
						$data['unsynched_list'][$selected_count]["birth_date"],
						$data['unsynched_list'][$selected_count]["gender"],
						$data['unsynched_list'][$selected_count]["date"],
						$data['unsynched_list'][$selected_count]["synch_out"]
					)= explode("-.-", $data['unsynched_list'][$selected_count]["value"]);
					$selected_count++;
				}
			}
		} //endif(count($_POST))
		$data['title'] = "Export New Antenatal Events";
        $data['now_id']             =   time();
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
            $new_body   =   "ehr/ehr_admin_export_new_antenatalinfo_html";
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
    } // end of function admin_export_new_antenatalinfo($id)


    // ------------------------------------------------------------------------
    function admin_export_new_antenatalinfo_done($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
        $data['export_clinic']    =   $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $data['baseurl']            =   base_url();
        $data['exploded_baseurl']   =   explode('/', $data['baseurl'], 4);
        $data['app_folder']         =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']        =    $data['app_path']."-uploads/exports_antenatal";
        $version_file_path          = $data['app_path']."/app_thirra/version.txt";
        $handle = fopen($version_file_path, "r");
        $app_version = fread($handle, filesize($version_file_path));
        fclose($handle);

        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']           = $_POST['num_rows'];
            $data['export_reference']   =   $_POST['reference'];
            $data['export_remarks']     =   $_POST['remarks'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_antenatalinfo>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$data['export_clinic']['clinic_name']."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$data['export_clinic']['clinic_ref_no']."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
			$selected		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$data['unsynched_list'][$selected]['number']	= $i;
					$data['unsynched_list'][$selected]['value']	= $_POST['s'.$i];
					list($antenatal_id, $patient_id) = explode("-.-", $data['unsynched_list'][$selected]['value']);					//$data['unsynched_list'][$selected]['patient_info'] 
					//	= $this->memr_rdb->get_patient_details($data['unsynched_list'][$selected]['value']);
					$data['unsynched_list'][$selected]['patient_info']   = $this->memr_rdb->get_patient_demo($patient_id);
					$data['unsynched_list'][$selected]['antenatal_info'] = $this->memr_rdb->get_antenatal_list('one',$patient_id,$antenatal_id);
					if($data['debug_mode']){
						echo "<pre>";
						echo "\n<br />print_r(patient_info)=<br />";
						print_r($data['unsynched_list'][$selected]['patient_info']);
						echo "\n<br />print_r(antenatal_info)=<br />";
						print_r($data['unsynched_list'][$selected]['antenatal_info']);
						echo "</pre>";
					}
					$patient_id 	    = $data['unsynched_list'][$selected]['patient_info']['patient_id'];
					$patient_name 	    = $data['unsynched_list'][$selected]['patient_info']['name'];
					$name_first 	    = $data['unsynched_list'][$selected]['patient_info']['name_first'];
					$event_id 		    = $data['unsynched_list'][$selected]['antenatal_info'][0]['event_id'];
					$event_tabletop 	= $data['unsynched_list'][$selected]['antenatal_info'][0]['event_tabletop'];
					$event_key 	        = $data['unsynched_list'][$selected]['antenatal_info'][0]['event_key'];
					$event_name 	    = $data['unsynched_list'][$selected]['antenatal_info'][0]['event_name'];
					$location_id 	    = $data['unsynched_list'][$selected]['antenatal_info'][0]['location_id'];
					$staff_id 	        = $data['unsynched_list'][$selected]['antenatal_info'][0]['staff_id'];
					$event_description 	= $data['unsynched_list'][$selected]['antenatal_info'][0]['event_description'];
					$event_remarks 	    = $data['unsynched_list'][$selected]['antenatal_info'][0]['event_remarks'];
                    
					$antenatal_id       = $data['unsynched_list'][$selected]['antenatal_info'][0]['antenatal_id'];
					$session_id         = $data['unsynched_list'][$selected]['antenatal_info'][0]['session_id'];
					$antenatal_status   = $data['unsynched_list'][$selected]['antenatal_info'][0]['status'];
					$antenatal_reference = $data['unsynched_list'][$selected]['antenatal_info'][0]['antenatal_reference'];
                    
					$antenatal_current_id= $data['unsynched_list'][$selected]['antenatal_info'][0]['antenatal_current_id'];
					$midwife_name       = $data['unsynched_list'][$selected]['antenatal_info'][0]['midwife_name'];
					$pregnancy_duration = $data['unsynched_list'][$selected]['antenatal_info'][0]['pregnancy_duration'];
					$lmp                = $data['unsynched_list'][$selected]['antenatal_info'][0]['lmp'];
					$planned_place      = $data['unsynched_list'][$selected]['antenatal_info'][0]['planned_place'];
					$menstrual_cycle_length = $data['unsynched_list'][$selected]['antenatal_info'][0]['menstrual_cycle_length'];
					$lmp_edd            = $data['unsynched_list'][$selected]['antenatal_info'][0]['lmp_edd'];
					$lmp_gestation      = $data['unsynched_list'][$selected]['antenatal_info'][0]['lmp_gestation'];
					$usscan_date        = $data['unsynched_list'][$selected]['antenatal_info'][0]['usscan_date'];
					$usscan_edd         = $data['unsynched_list'][$selected]['antenatal_info'][0]['usscan_edd'];
					$usscan_gestation   = $data['unsynched_list'][$selected]['antenatal_info'][0]['usscan_gestation'];
  
					$antenatal_info_id	= $data['unsynched_list'][$selected]['antenatal_info'][0]['antenatal_info_id'];
					$record_date        = $data['unsynched_list'][$selected]['antenatal_info'][0]['date'];
					$husband_name       = $data['unsynched_list'][$selected]['antenatal_info'][0]['husband_name'];
					$husband_job        = $data['unsynched_list'][$selected]['antenatal_info'][0]['husband_job'];
					$husband_dob        = $data['unsynched_list'][$selected]['antenatal_info'][0]['husband_dob'];
					$husband_ic_no      = $data['unsynched_list'][$selected]['antenatal_info'][0]['husband_ic_no'];
					$gravida            = $data['unsynched_list'][$selected]['antenatal_info'][0]['gravida'];
					$para               = $data['unsynched_list'][$selected]['antenatal_info'][0]['para'];
					$method_contraception = $data['unsynched_list'][$selected]['antenatal_info'][0]['method_contraception'];
					$abortion           = $data['unsynched_list'][$selected]['antenatal_info'][0]['abortion'];
					$past_obstretical_history_icpc     = $data['unsynched_list'][$selected]['antenatal_info'][0]['past_obstretical_history_icpc'];
					$past_obstretical_history_notes = $data['unsynched_list'][$selected]['antenatal_info'][0]['past_obstretical_history_notes'];
					$num_term_deliveries = $data['unsynched_list'][$selected]['antenatal_info'][0]['num_term_deliveries'];
					$num_preterm_deliveries = $data['unsynched_list'][$selected]['antenatal_info'][0]['num_preterm_deliveries'];
					$num_preg_lessthan_21wk = $data['unsynched_list'][$selected]['antenatal_info'][0]['num_preg_lessthan_21wk'];
					$num_live_births    = $data['unsynched_list'][$selected]['antenatal_info'][0]['num_live_births'];
					$num_caesarean_births = $data['unsynched_list'][$selected]['antenatal_info'][0]['num_caesarean_births'];
					$num_miscarriages   = $data['unsynched_list'][$selected]['antenatal_info'][0]['num_miscarriages'];
					$three_consec_miscarriages = $data['unsynched_list'][$selected]['antenatal_info'][0]['three_consec_miscarriages'];
					$num_stillbirths    = $data['unsynched_list'][$selected]['antenatal_info'][0]['num_stillbirths'];
					$post_partum_depression = $data['unsynched_list'][$selected]['antenatal_info'][0]['post_partum_depression'];
					$present_pulmonary_tb = $data['unsynched_list'][$selected]['antenatal_info'][0]['present_pulmonary_tb'];
					$present_heart_disease = $data['unsynched_list'][$selected]['antenatal_info'][0]['present_heart_disease'];
					$present_diabetes   = $data['unsynched_list'][$selected]['antenatal_info'][0]['present_diabetes'];
					$present_bronchial_asthma = $data['unsynched_list'][$selected]['antenatal_info'][0]['present_bronchial_asthma'];
					$present_goiter     = $data['unsynched_list'][$selected]['antenatal_info'][0]['present_goiter'];
					$present_hepatitis_b = $data['unsynched_list'][$selected]['antenatal_info'][0]['present_hepatitis_b'];
					$antenatal_remarks  = $data['unsynched_list'][$selected]['antenatal_info'][0]['antenatal_remarks'];
					$contact_person     = $data['unsynched_list'][$selected]['antenatal_info'][0]['contact_person'];
                    
					$episode_status = $data['unsynched_list'][$selected]['antenatal_info'][0]['status'];
					$episode_status = $data['unsynched_list'][$selected]['antenatal_info'][0]['status'];
					$episode_status = $data['unsynched_list'][$selected]['antenatal_info'][0]['status'];
					$episode_status = $data['unsynched_list'][$selected]['antenatal_info'][0]['status'];
                    
					$synch_out 		= $data['unsynched_list'][$selected]['antenatal_info'][0]['synch_out'];
					$synch_remarks 		= $data['unsynched_list'][$selected]['antenatal_info'][0]['synch_remarks'];
					$count_procedures	= 0;
					$count_others		= 0;
                    
					$xmlstr .= "\n<antenatal_event event_id='$event_id'>";
                    
					$xmlstr .= "\n\t<patient_info>";
					$xmlstr .= "\n\t\t<patient_id>$patient_id</patient_id>";
					$xmlstr .= "\n\t\t<patient_name>$patient_name</patient_name>";
					$xmlstr .= "\n\t\t<name_first>$name_first</name_first>";
					$xmlstr .= "\n\t</patient_info>";
                    
					$xmlstr .= "\n\t<event_info>";
					$xmlstr .= "\n\t\t<event_id>$event_id</event_id>";
					$xmlstr .= "\n\t\t<event_tabletop>$event_tabletop</event_tabletop>";
					$xmlstr .= "\n\t\t<event_key>$event_key</event_key>";
					$xmlstr .= "\n\t\t<event_name>$event_name</event_name>";
					$xmlstr .= "\n\t\t<patient_id>$patient_id</patient_id>";
					$xmlstr .= "\n\t\t<location_id>$location_id</location_id>";
					$xmlstr .= "\n\t\t<staff_id>$staff_id</staff_id>";
					$xmlstr .= "\n\t\t<event_description>$event_description</event_description>";
					$xmlstr .= "\n\t\t<event_remarks>$event_remarks</event_remarks>";
					$xmlstr .= "\n\t\t<synch_out>$synch_out</synch_out>";

                    $xmlstr .= "\n\t\t<antenatal_id>$antenatal_id</antenatal_id>";
					$xmlstr .= "\n\t\t<session_id>$session_id</session_id>";
					$xmlstr .= "\n\t\t<antenatal_status>$antenatal_status</antenatal_status>";
					$xmlstr .= "\n\t\t<antenatal_reference>$antenatal_reference</antenatal_reference>";
                    
					$xmlstr .= "\n\t\t<antenatal_current_id>$antenatal_current_id</antenatal_current_id>";
					$xmlstr .= "\n\t\t<midwife_name>$midwife_name</midwife_name>";
					$xmlstr .= "\n\t\t<pregnancy_duration>$pregnancy_duration</pregnancy_duration>";
					$xmlstr .= "\n\t\t<lmp>$lmp</lmp>";
					$xmlstr .= "\n\t\t<planned_place>$planned_place</planned_place>";
					$xmlstr .= "\n\t\t<menstrual_cycle_length>$menstrual_cycle_length</menstrual_cycle_length>";
					$xmlstr .= "\n\t\t<lmp_edd>$lmp_edd</lmp_edd>";
					$xmlstr .= "\n\t\t<lmp_gestation>$lmp_gestation</lmp_gestation>";
					$xmlstr .= "\n\t\t<usscan_date>$usscan_date</usscan_date>";
					$xmlstr .= "\n\t\t<usscan_edd>$usscan_edd</usscan_edd>";
					$xmlstr .= "\n\t\t<usscan_gestation>$usscan_gestation</usscan_gestation>";
                    
					$xmlstr .= "\n\t\t<antenatal_info_id>$antenatal_info_id</antenatal_info_id>";
					$xmlstr .= "\n\t\t<record_date>$record_date</record_date>";
					$xmlstr .= "\n\t\t<husband_name>$husband_name</husband_name>";
					$xmlstr .= "\n\t\t<husband_job>$husband_job</husband_job>";
					$xmlstr .= "\n\t\t<husband_dob>$husband_dob</husband_dob>";
					$xmlstr .= "\n\t\t<husband_ic_no>$husband_ic_no</husband_ic_no>";
					$xmlstr .= "\n\t\t<gravida>$gravida</gravida>";
					$xmlstr .= "\n\t\t<para>$para</para>";
					$xmlstr .= "\n\t\t<method_contraception>$method_contraception</method_contraception>";
					$xmlstr .= "\n\t\t<abortion>$abortion</abortion>";
					$xmlstr .= "\n\t\t<past_obstretical_history_icpc>$past_obstretical_history_icpc</past_obstretical_history_icpc>";
					$xmlstr .= "\n\t\t<past_obstretical_history_notes>$past_obstretical_history_notes</past_obstretical_history_notes>";
					$xmlstr .= "\n\t\t<num_term_deliveries>$num_term_deliveries</num_term_deliveries>";
					$xmlstr .= "\n\t\t<num_preterm_deliveries>$num_preterm_deliveries</num_preterm_deliveries>";
					$xmlstr .= "\n\t\t<num_preg_lessthan_21wk>$num_preg_lessthan_21wk</num_preg_lessthan_21wk>";
					$xmlstr .= "\n\t\t<num_live_births>$num_live_births</num_live_births>";
					$xmlstr .= "\n\t\t<num_caesarean_births>$num_caesarean_births</num_caesarean_births>";
					$xmlstr .= "\n\t\t<num_miscarriages>$num_miscarriages</num_miscarriages>";
					$xmlstr .= "\n\t\t<three_consec_miscarriages>$three_consec_miscarriages</three_consec_miscarriages>";
					$xmlstr .= "\n\t\t<num_stillbirths>$num_stillbirths</num_stillbirths>";
					$xmlstr .= "\n\t\t<post_partum_depression>$post_partum_depression</post_partum_depression>";
					$xmlstr .= "\n\t\t<present_pulmonary_tb>$present_pulmonary_tb</present_pulmonary_tb>";
					$xmlstr .= "\n\t\t<present_heart_disease>$present_heart_disease</present_heart_disease>";
					$xmlstr .= "\n\t\t<present_diabetes>$present_diabetes</present_diabetes>";
					$xmlstr .= "\n\t\t<present_bronchial_asthma>$present_bronchial_asthma</present_bronchial_asthma>";
					$xmlstr .= "\n\t\t<present_goiter>$present_goiter</present_goiter>";
					$xmlstr .= "\n\t\t<present_hepatitis_b>$present_hepatitis_b</present_hepatitis_b>";
					$xmlstr .= "\n\t\t<antenatal_remarks>$antenatal_remarks</antenatal_remarks>";
					$xmlstr .= "\n\t\t<contact_person>$contact_person</contact_person>";
                    
					$xmlstr .= "\n\t\t<synch_out>$synch_out</synch_out>";
					$xmlstr .= "\n\t\t<synch_remarks>$synch_remarks</synch_remarks>";
					$xmlstr .= "\n\t</event_info>";
					 															
					$xmlstr .= "\n</antenatal_event>";
					$selected++;
                    
                    //Log session_id's
                    if(isset($data['entities_inserted'])){
                        $data['entities_inserted'] = $data['entities_inserted'].",";
                    } else {
                        $data['entities_inserted'] = "";
                    }
                    $data['entities_inserted']  =   $data['entities_inserted'].$antenatal_id;
                        
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
		} //endif(count($_POST))
		$data['title'] = "Exported New Antenatal";
        $data['now_id']             =   time();
		$data['file_exported']		=	"patient_antenatalinfo-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		//$address1 = $data['unsynched_list'][1]['patient_info']['patient_address'];
		//$xmlstr .= "\n\t<address1>$address1</address1>";
		$xmlstr .= "\n</THIRRA_export_antenatalinfo>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

        // New log record
        $ins_log_array   =   array();
        $ins_log_array['data_synch_log_id'] = $data['now_id'];
        $ins_log_array['export_by']         = $_SESSION['staff_id'];
        $ins_log_array['export_when']       = $data['now_id'];
        $ins_log_array['thirra_version']    = $app_version;
        $ins_log_array['export_clinicname'] = $data['export_clinic']['clinic_name'];
        $ins_log_array['export_clinicref']  = $data['export_clinic']['clinic_ref_no'];
        $ins_log_array['export_reference']  = $data['export_reference'];
        //$ins_log_array['import_by']         = $_SESSION['staff_id'];
        //$ins_log_array['import_when']       = $data['now_id'];
        $ins_log_array['data_filename']     = $data['file_exported'];
        //$ins_log_array['import_remarks']    = $data['import_remarks'];
        //$ins_log_array['import_reference']  = $data['import_reference'];
        //$ins_log_array['import_number']     = $data['import_number'];
        //$ins_log_array['import_outcome']    = $data['import_outcome'];
        $ins_log_array['count_inserted']    = $selected - 1;
        //$ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
        //$ins_log_array['count_rejected']    = $data['count_rejected'];
        $ins_log_array['entities_inserted'] = $data['entities_inserted'];
        //$ins_log_array['entities_declined'] = $data['entities_declined'];
        //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
        //$ins_log_array['declined_list']     = $data['declined_list'];
        //$ins_log_array['rejected_list']     = $data['rejected_list'];
        $ins_log_array['outcome_remarks']   = "Success";
        $ins_log_array['sync_type']         = "Manual EDI - Antenatal Info Data";
        $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

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
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_export_new_antenatalinfo_done_html";
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
    } // end of function admin_export_new_antenatalinfo_done($id)


    // ------------------------------------------------------------------------
    function admin_export_antenatal_checkup($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Export Antenatal Checkups";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_antenatalcheckup('ALL');
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_antenatalcheckup('ALL',TRUE);
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
            $new_body   =   "ehr/ehr_admin_export_antenatalcheckup_html";
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
    } // end of function admin_export_antenatal_checkup($id)


    // ------------------------------------------------------------------------
    function admin_export_new_antenatalcheckup($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			$selected_count		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				if(isset($_POST['s'.$i])){
				// Only retrieve if selected by user
					$data['unsynched_list'][$selected_count]["number"]	= $i;
					$data['unsynched_list'][$selected_count]["value"]	= $_POST['s'.$i];
					list(
						$data['unsynched_list'][$selected_count]["antenatal_followup_id"],
						$data['unsynched_list'][$selected_count]["antenatal_id"],
						$data['unsynched_list'][$selected_count]["patient_id"],
						$data['unsynched_list'][$selected_count]["name"],
						$data['unsynched_list'][$selected_count]["name_first"],
						$data['unsynched_list'][$selected_count]["birth_date"],
						$data['unsynched_list'][$selected_count]["gender"],
						$data['unsynched_list'][$selected_count]["date"],
						$data['unsynched_list'][$selected_count]["synch_out"]
					)= explode("-.-", $data['unsynched_list'][$selected_count]["value"]);
					$selected_count++;
				}
			}
		} //endif(count($_POST))
		$data['title'] = "Export New Antenatal Checkups";
        $data['now_id']             =   time();
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
            $new_body   =   "ehr/ehr_admin_export_new_antenatalcheckup_html";
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
    } // end of function admin_export_new_antenatalcheckup($id)


    // ------------------------------------------------------------------------
    function admin_export_new_antenatalcheckup_done($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
        $data['export_clinic']    =   $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $data['baseurl']            =   base_url();
        $data['exploded_baseurl']   =   explode('/', $data['baseurl'], 4);
        $data['app_folder']         =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']        =    $data['app_path']."-uploads/exports_antenatal";
        $version_file_path          = $data['app_path']."/app_thirra/version.txt";
        $handle = fopen($version_file_path, "r");
        $app_version = fread($handle, filesize($version_file_path));
        fclose($handle);

        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
            $data['export_reference']   =   $_POST['reference'];
            $data['export_remarks']     =   $_POST['remarks'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_antenatalcheckup>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$data['export_clinic']['clinic_name']."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$data['export_clinic']['clinic_ref_no']."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
			$selected		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$data['unsynched_list'][$selected]['number']	= $i;
					$data['unsynched_list'][$selected]['value']	= $_POST['s'.$i];
					list($antenatal_followup_id, $antenatal_id, $patient_id) = explode("-.-", $data['unsynched_list'][$selected]['value']);					//$data['unsynched_list'][$selected]['patient_info'] 
					//	= $this->memr_rdb->get_patient_details($data['unsynched_list'][$selected]['value']);
					$data['unsynched_list'][$selected]['patient_info']   = $this->memr_rdb->get_patient_demo($patient_id);
					$data['unsynched_list'][$selected]['antenatal_info'] = $this->memr_rdb->get_antenatal_list('one',$patient_id,$antenatal_id);
					$data['unsynched_list'][$selected]['checkup_info'] = $this->memr_rdb->get_antenatal_followup('one',$patient_id, $antenatal_id, $antenatal_followup_id);
					if($data['debug_mode']){
						echo "<pre>";
						echo "\n<br />print_r(patient_info)=<br />";
						print_r($data['unsynched_list'][$selected]['patient_info']);
						echo "\n<br />print_r(antenatal_info)=<br />";
						print_r($data['unsynched_list'][$selected]['antenatal_info']);
						echo "</pre>";
					}
					$patient_id 	    = $data['unsynched_list'][$selected]['patient_info']['patient_id'];
					$patient_name 	    = $data['unsynched_list'][$selected]['patient_info']['name'];
					$name_first 	    = $data['unsynched_list'][$selected]['patient_info']['name_first'];
					$event_id 		    = $data['unsynched_list'][$selected]['antenatal_info'][0]['event_id'];
					$location_id 	    = $data['unsynched_list'][$selected]['antenatal_info'][0]['location_id'];
                    
					$antenatal_id       = $data['unsynched_list'][$selected]['antenatal_info'][0]['antenatal_id'];
					$session_id         = $data['unsynched_list'][$selected]['antenatal_info'][0]['session_id'];
                                       
					$antenatal_followup_id = $data['unsynched_list'][$selected]['checkup_info'][0]['antenatal_followup_id'];
					$checkup_date       = $data['unsynched_list'][$selected]['checkup_info'][0]['date'];
					$pregnancy_duration = $data['unsynched_list'][$selected]['checkup_info'][0]['pregnancy_duration'];
					$lie                = $data['unsynched_list'][$selected]['checkup_info'][0]['lie'];
					$weight             = $data['unsynched_list'][$selected]['checkup_info'][0]['weight'];
					$fundal_height      = $data['unsynched_list'][$selected]['checkup_info'][0]['fundal_height'];
					$hb                 = $data['unsynched_list'][$selected]['checkup_info'][0]['hb'];
					$urine_alb          = $data['unsynched_list'][$selected]['checkup_info'][0]['urine_alb'];
					$urine_sugar        = $data['unsynched_list'][$selected]['checkup_info'][0]['urine_sugar'];
					$ankle_odema        = $data['unsynched_list'][$selected]['checkup_info'][0]['ankle_odema'];
					$notes              = $data['unsynched_list'][$selected]['checkup_info'][0]['notes'];
					$next_followup      = $data['unsynched_list'][$selected]['checkup_info'][0]['next_followup'];
					$fundal_height2     = $data['unsynched_list'][$selected]['checkup_info'][0]['fundal_height2'];
					$session_id         = $data['unsynched_list'][$selected]['checkup_info'][0]['session_id'];
                                        
					$synch_out 		    = $data['unsynched_list'][$selected]['checkup_info'][0]['synch_out'];
					$synch_remarks 		= $data['unsynched_list'][$selected]['checkup_info'][0]['synch_remarks'];
					$count_procedures	= 0;
					$count_others		= 0;
                    
					$xmlstr .= "\n<antenatal_checkup antenatal_followup_id='$antenatal_followup_id'>";
                    
					$xmlstr .= "\n\t<patient_info>";
					$xmlstr .= "\n\t\t<patient_id>$patient_id</patient_id>";
					$xmlstr .= "\n\t\t<patient_name>$patient_name</patient_name>";
					$xmlstr .= "\n\t\t<name_first>$name_first</name_first>";
					$xmlstr .= "\n\t</patient_info>";
                    
					$xmlstr .= "\n\t<checkup_info>";
					$xmlstr .= "\n\t\t<event_id>$event_id</event_id>";
					$xmlstr .= "\n\t\t<location_id>$location_id</location_id>";
                    $xmlstr .= "\n\t\t<antenatal_id>$antenatal_id</antenatal_id>";
					$xmlstr .= "\n\t\t<session_id>$session_id</session_id>";
                    
					$xmlstr .= "\n\t\t<antenatal_followup_id>$antenatal_followup_id</antenatal_followup_id>";
					$xmlstr .= "\n\t\t<checkup_date>$checkup_date</checkup_date>";
					$xmlstr .= "\n\t\t<pregnancy_duration>$pregnancy_duration</pregnancy_duration>";
					$xmlstr .= "\n\t\t<lie>$lie</lie>";
					$xmlstr .= "\n\t\t<weight>$weight</weight>";
					$xmlstr .= "\n\t\t<fundal_height>$fundal_height</fundal_height>";
					$xmlstr .= "\n\t\t<hb>$hb</hb>";
					$xmlstr .= "\n\t\t<urine_alb>$urine_alb</urine_alb>";
					$xmlstr .= "\n\t\t<urine_sugar>$urine_sugar</urine_sugar>";
					$xmlstr .= "\n\t\t<ankle_odema>$ankle_odema</ankle_odema>";
					$xmlstr .= "\n\t\t<notes>$notes</notes>";
					$xmlstr .= "\n\t\t<next_followup>$next_followup</next_followup>";
					$xmlstr .= "\n\t\t<fundal_height2>$fundal_height2</fundal_height2>";
					$xmlstr .= "\n\t\t<session_id>$session_id</session_id>";
                                        
					$xmlstr .= "\n\t\t<synch_out>$synch_out</synch_out>";
					$xmlstr .= "\n\t\t<synch_remarks>$synch_remarks</synch_remarks>";
					$xmlstr .= "\n\t</checkup_info>";					
                                                    
					$xmlstr .= "\n</antenatal_checkup>";
					$selected++;
                    
                    //Log patient_id's
                    if(isset($data['entities_inserted'])){
                        $data['entities_inserted'] = $data['entities_inserted'].",";
                    } else {
                        $data['entities_inserted'] = "";
                    }
                    $data['entities_inserted']  =   $data['entities_inserted'].$antenatal_followup_id;
                        
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
		} //endif(count($_POST))
		$data['title'] = "Exported New Antenatal Checkup";
        $data['now_id']             =   time();
		$data['file_exported']		=	"patient_antenatalcheckup-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		//$address1 = $data['unsynched_list'][1]['patient_info']['patient_address'];
		//$xmlstr .= "\n\t<address1>$address1</address1>";
		$xmlstr .= "\n</THIRRA_export_antenatalcheckup>";
        //echo "<pre>";
		//echo $xmlstr;
        //echo "</pre>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

        // New log record
        $ins_log_array   =   array();
        $ins_log_array['data_synch_log_id'] = $data['now_id'];
        $ins_log_array['export_by']         = $_SESSION['staff_id'];
        $ins_log_array['export_when']       = $data['now_id'];
        $ins_log_array['thirra_version']    = $app_version;
        $ins_log_array['export_clinicname'] = $data['export_clinic']['clinic_name'];
        $ins_log_array['export_clinicref']  = $data['export_clinic']['clinic_ref_no'];
        $ins_log_array['export_reference']  = $data['export_reference'];
        //$ins_log_array['import_by']         = $_SESSION['staff_id'];
        //$ins_log_array['import_when']       = $data['now_id'];
        $ins_log_array['data_filename']     = $data['file_exported'];
        //$ins_log_array['import_remarks']    = $data['import_remarks'];
        //$ins_log_array['import_reference']  = $data['import_reference'];
        //$ins_log_array['import_number']     = $data['import_number'];
        //$ins_log_array['import_outcome']    = $data['import_outcome'];
        $ins_log_array['count_inserted']    = $selected - 1;
        //$ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
        //$ins_log_array['count_rejected']    = $data['count_rejected'];
        $ins_log_array['entities_inserted'] = $data['entities_inserted'];
        //$ins_log_array['entities_declined'] = $data['entities_declined'];
        //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
        //$ins_log_array['declined_list']     = $data['declined_list'];
        //$ins_log_array['rejected_list']     = $data['rejected_list'];
        $ins_log_array['outcome_remarks']   = "Success";
        $ins_log_array['sync_type']         = "Manual EDI - Antenatal Checkup Data";
        $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

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
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_export_new_antenatalcheckup_done_html";
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
    } // end of function admin_export_new_antenatalcheckup_done($id)


    // ------------------------------------------------------------------------
    function admin_export_antenatal_delivery($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Export Antenatal Deliveries";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_antenataldelivery('ALL');
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_antenataldelivery('ALL',TRUE);
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
            $new_body   =   "ehr/ehr_admin_export_antenataldelivery_html";
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
    } // end of function admin_export_antenatal_delivery($id)


    // ------------------------------------------------------------------------
    function admin_export_new_antenataldelivery($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			$selected_count		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				if(isset($_POST['s'.$i])){
				// Only retrieve if selected by user
					$data['unsynched_list'][$selected_count]["number"]	= $i;
					$data['unsynched_list'][$selected_count]["value"]	= $_POST['s'.$i];
					list(
						$data['unsynched_list'][$selected_count]["antenatal_delivery_id"],
						$data['unsynched_list'][$selected_count]["antenatal_id"],
						$data['unsynched_list'][$selected_count]["patient_id"],
						$data['unsynched_list'][$selected_count]["name"],
						$data['unsynched_list'][$selected_count]["name_first"],
						$data['unsynched_list'][$selected_count]["birth_date"],
						$data['unsynched_list'][$selected_count]["gender"],
						$data['unsynched_list'][$selected_count]["date"],
						$data['unsynched_list'][$selected_count]["synch_out"]
					)= explode("-.-", $data['unsynched_list'][$selected_count]["value"]);
					$selected_count++;
				}
			}
		} //endif(count($_POST))
		$data['title'] = "Export New Antenatal Deliveries";
        $data['now_id']             =   time();
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
            $new_body   =   "ehr/ehr_admin_export_new_antenataldelivery_html";
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
    } // end of function admin_export_new_antenataldelivery($id)


    // ------------------------------------------------------------------------
    function admin_export_new_antenataldelivery_done($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
        $data['export_clinic']    =   $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $data['baseurl']            =   base_url();
        $data['exploded_baseurl']   =   explode('/', $data['baseurl'], 4);
        $data['app_folder']         =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']        =    $data['app_path']."-uploads/exports_antenatal";
        $version_file_path          = $data['app_path']."/app_thirra/version.txt";
        $handle = fopen($version_file_path, "r");
        $app_version = fread($handle, filesize($version_file_path));
        fclose($handle);

        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
            $data['export_reference']   =   $_POST['reference'];
            $data['export_remarks']     =   $_POST['remarks'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_antenataldelivery>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$data['export_clinic']['clinic_name']."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$data['export_clinic']['clinic_ref_no']."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
			$selected		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$data['unsynched_list'][$selected]['number']	= $i;
					$data['unsynched_list'][$selected]['value']	= $_POST['s'.$i];
					list($antenatal_delivery_id, $antenatal_id, $patient_id) = explode("-.-", $data['unsynched_list'][$selected]['value']);					//$data['unsynched_list'][$selected]['patient_info'] 
					//	= $this->memr_rdb->get_patient_details($data['unsynched_list'][$selected]['value']);
					$data['unsynched_list'][$selected]['patient_info']   = $this->memr_rdb->get_patient_demo($patient_id);
					$data['unsynched_list'][$selected]['antenatal_info'] = $this->memr_rdb->get_antenatal_list('one',$patient_id,$antenatal_id);
					$data['unsynched_list'][$selected]['delivery_info'] = $this->memr_rdb->get_antenatal_delivery('one',$patient_id, $antenatal_id, $antenatal_delivery_id);
					if($data['debug_mode']){
						echo "<pre>";
						echo "\n<br />print_r(patient_info)=<br />";
						print_r($data['unsynched_list'][$selected]['patient_info']);
						echo "\n<br />print_r(antenatal_info)=<br />";
						print_r($data['unsynched_list'][$selected]['antenatal_info']);
						echo "</pre>";
					}
					$patient_id 	    = $data['unsynched_list'][$selected]['patient_info']['patient_id'];
					$patient_name 	    = $data['unsynched_list'][$selected]['patient_info']['name'];
					$name_first 	    = $data['unsynched_list'][$selected]['patient_info']['name_first'];
					$event_id 		    = $data['unsynched_list'][$selected]['antenatal_info'][0]['event_id'];
					$location_id 	    = $data['unsynched_list'][$selected]['antenatal_info'][0]['location_id'];
					$staff_id 	        = $data['unsynched_list'][$selected]['antenatal_info'][0]['staff_id'];
                    
					$antenatal_id       = $data['unsynched_list'][$selected]['antenatal_info'][0]['antenatal_id'];
					$session_id         = $data['unsynched_list'][$selected]['antenatal_info'][0]['session_id'];
					$antenatal_status   = $data['unsynched_list'][$selected]['antenatal_info'][0]['status'];
					$antenatal_reference = $data['unsynched_list'][$selected]['antenatal_info'][0]['antenatal_reference'];
					$gravida            = $data['unsynched_list'][$selected]['antenatal_info'][0]['gravida'];
					$para               = $data['unsynched_list'][$selected]['antenatal_info'][0]['para'];
                                       
					$antenatal_delivery_id = $data['unsynched_list'][$selected]['delivery_info'][0]['antenatal_delivery_id'];
					$date_admission     = $data['unsynched_list'][$selected]['delivery_info'][0]['date_admission'];
					$time_admission     = $data['unsynched_list'][$selected]['delivery_info'][0]['time_admission'];
					$date_delivery      = $data['unsynched_list'][$selected]['delivery_info'][0]['date_delivery'];
					$time_delivery      = $data['unsynched_list'][$selected]['delivery_info'][0]['time_delivery'];
					$delivery_type      = $data['unsynched_list'][$selected]['delivery_info'][0]['delivery_type'];
					$delivery_place     = $data['unsynched_list'][$selected]['delivery_info'][0]['delivery_place'];
					$mother_condition   = $data['unsynched_list'][$selected]['delivery_info'][0]['mother_condition'];
					$baby_condition     = $data['unsynched_list'][$selected]['delivery_info'][0]['baby_condition'];
					$baby_weight        = $data['unsynched_list'][$selected]['delivery_info'][0]['baby_weight'];
					$complication_icpc  = $data['unsynched_list'][$selected]['delivery_info'][0]['complication_icpc'];
					$complication_notes = $data['unsynched_list'][$selected]['delivery_info'][0]['complication_notes'];
					$baby_alive         = $data['unsynched_list'][$selected]['delivery_info'][0]['baby_alive'];
					$birth_attendant    = $data['unsynched_list'][$selected]['delivery_info'][0]['birth_attendant'];
					$breastfeed_immediate = $data['unsynched_list'][$selected]['delivery_info'][0]['breastfeed_immediate'];
					$post_partum_bleed  = $data['unsynched_list'][$selected]['delivery_info'][0]['post_partum_bleed'];
					$apgar_score        = $data['unsynched_list'][$selected]['delivery_info'][0]['apgar_score'];
					$child_id           = $data['unsynched_list'][$selected]['delivery_info'][0]['child_id'];
					$delivery_remarks   = $data['unsynched_list'][$selected]['delivery_info'][0]['delivery_remarks'];
					$delivery_outcome   = $data['unsynched_list'][$selected]['delivery_info'][0]['delivery_outcome'];
					$dcode1ext_code     = $data['unsynched_list'][$selected]['delivery_info'][0]['dcode1ext_code'];
                                        
					$synch_out 		    = $data['unsynched_list'][$selected]['delivery_info'][0]['synch_out'];
					$synch_remarks 		= $data['unsynched_list'][$selected]['delivery_info'][0]['synch_remarks'];
					$count_procedures	= 0;
					$count_others		= 0;
                    
					$xmlstr .= "\n<antenatal_delivery antenatal_delivery_id='$antenatal_delivery_id'>";
                    
					$xmlstr .= "\n\t<patient_info>";
					$xmlstr .= "\n\t\t<patient_id>$patient_id</patient_id>";
					$xmlstr .= "\n\t\t<patient_name>$patient_name</patient_name>";
					$xmlstr .= "\n\t\t<name_first>$name_first</name_first>";
					$xmlstr .= "\n\t</patient_info>";
                    
					$xmlstr .= "\n\t<delivery_info>";
					$xmlstr .= "\n\t\t<event_id>$event_id</event_id>";
					$xmlstr .= "\n\t\t<location_id>$location_id</location_id>";
					$xmlstr .= "\n\t\t<staff_id>$staff_id</staff_id>";

                    $xmlstr .= "\n\t\t<antenatal_id>$antenatal_id</antenatal_id>";
					$xmlstr .= "\n\t\t<session_id>$session_id</session_id>";
					$xmlstr .= "\n\t\t<antenatal_status>$antenatal_status</antenatal_status>";
					$xmlstr .= "\n\t\t<antenatal_reference>$antenatal_reference</antenatal_reference>";
					$xmlstr .= "\n\t\t<gravida>$gravida</gravida>";
					$xmlstr .= "\n\t\t<para>$para</para>";
                    
					$xmlstr .= "\n\t\t<antenatal_delivery_id>$antenatal_delivery_id</antenatal_delivery_id>";
					$xmlstr .= "\n\t\t<date_admission>$date_admission</date_admission>";
					$xmlstr .= "\n\t\t<time_admission>$time_admission</time_admission>";
					$xmlstr .= "\n\t\t<date_delivery>$date_delivery</date_delivery>";
					$xmlstr .= "\n\t\t<time_delivery>$time_delivery</time_delivery>";
					$xmlstr .= "\n\t\t<delivery_type>$delivery_type</delivery_type>";
					$xmlstr .= "\n\t\t<delivery_place>$delivery_place</delivery_place>";
					$xmlstr .= "\n\t\t<mother_condition>$mother_condition</mother_condition>";
					$xmlstr .= "\n\t\t<baby_condition>$baby_condition</baby_condition>";
					$xmlstr .= "\n\t\t<baby_weight>$baby_weight</baby_weight>";
					$xmlstr .= "\n\t\t<complication_icpc>$complication_icpc</complication_icpc>";
					$xmlstr .= "\n\t\t<complication_notes>$complication_notes</complication_notes>";
					$xmlstr .= "\n\t\t<baby_alive>$baby_alive</baby_alive>";
					$xmlstr .= "\n\t\t<birth_attendant>$birth_attendant</birth_attendant>";
					$xmlstr .= "\n\t\t<breastfeed_immediate>$breastfeed_immediate</breastfeed_immediate>";
					$xmlstr .= "\n\t\t<post_partum_bleed>$post_partum_bleed</post_partum_bleed>";
					$xmlstr .= "\n\t\t<apgar_score>$apgar_score</apgar_score>";
					$xmlstr .= "\n\t\t<child_id>$child_id</child_id>";
					$xmlstr .= "\n\t\t<delivery_remarks>$delivery_remarks</delivery_remarks>";
                                        
					$xmlstr .= "\n\t\t<synch_out>$synch_out</synch_out>";
					$xmlstr .= "\n\t\t<synch_remarks>$synch_remarks</synch_remarks>";
					$xmlstr .= "\n\t</delivery_info>";
					
 															
					$xmlstr .= "\n</antenatal_delivery>";
					$selected++;
                    
                    //Log patient_id's
                    if(isset($data['entities_inserted'])){
                        $data['entities_inserted'] = $data['entities_inserted'].",";
                    } else {
                        $data['entities_inserted'] = "";
                    }
                    $data['entities_inserted']  =   $data['entities_inserted'].$antenatal_delivery_id;
                        
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
		} //endif(count($_POST))
		$data['title'] = "Exported New Antenatal Deliveries";
        $data['now_id']             =   time();
		$data['file_exported']		=	"patient_antenataldelivery-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		//$address1 = $data['unsynched_list'][1]['patient_info']['patient_address'];
		//$xmlstr .= "\n\t<address1>$address1</address1>";
		$xmlstr .= "\n</THIRRA_export_antenataldelivery>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

        // New log record
        $ins_log_array   =   array();
        $ins_log_array['data_synch_log_id'] = $data['now_id'];
        $ins_log_array['export_by']         = $_SESSION['staff_id'];
        $ins_log_array['export_when']       = $data['now_id'];
        $ins_log_array['thirra_version']    = $app_version;
        $ins_log_array['export_clinicname'] = $data['export_clinic']['clinic_name'];
        $ins_log_array['export_clinicref']  = $data['export_clinic']['clinic_ref_no'];
        $ins_log_array['export_reference']  = $data['export_reference'];
        //$ins_log_array['import_by']         = $_SESSION['staff_id'];
        //$ins_log_array['import_when']       = $data['now_id'];
        $ins_log_array['data_filename']     = $data['file_exported'];
        //$ins_log_array['import_remarks']    = $data['import_remarks'];
        //$ins_log_array['import_reference']  = $data['import_reference'];
        //$ins_log_array['import_number']     = $data['import_number'];
        //$ins_log_array['import_outcome']    = $data['import_outcome'];
        $ins_log_array['count_inserted']    = $selected - 1;
        //$ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
        //$ins_log_array['count_rejected']    = $data['count_rejected'];
        $ins_log_array['entities_inserted'] = $data['entities_inserted'];
        //$ins_log_array['entities_declined'] = $data['entities_declined'];
        //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
        //$ins_log_array['declined_list']     = $data['declined_list'];
        //$ins_log_array['rejected_list']     = $data['rejected_list'];
        $ins_log_array['outcome_remarks']   = "Success";
        $ins_log_array['sync_type']         = "Manual EDI - Antenatal Delivery Data";
        $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

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
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_export_new_antenataldelivery_done_html";
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
    } // end of function admin_export_new_antenataldelivery_done($id)


    // ------------------------------------------------------------------------
    function admin_export_episodes($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Export Episodes";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_episodes('ALL');
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_episodes('ALL',TRUE);
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
            $new_body   =   "ehr/ehr_admin_export_episodes_html";
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
    } // end of function admin_export_episodes($id)


    // ------------------------------------------------------------------------
    function admin_export_new_episodes($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			$selected_count		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				if(isset($_POST['s'.$i])){
				// Only retrieve if selected by user
					$data['unsynched_list'][$selected_count]["number"]	= $i;
					$data['unsynched_list'][$selected_count]["value"]	= $_POST['s'.$i];
					list(
						$data['unsynched_list'][$selected_count]["summary_id"],
						$data['unsynched_list'][$selected_count]["patient_id"],
						$data['unsynched_list'][$selected_count]["name"],
						$data['unsynched_list'][$selected_count]["name_first"],
						$data['unsynched_list'][$selected_count]["birth_date"],
						$data['unsynched_list'][$selected_count]["gender"],
						$data['unsynched_list'][$selected_count]["date_started"],
						$data['unsynched_list'][$selected_count]["time_started"],
						$data['unsynched_list'][$selected_count]["synch_out"]
					)= explode("-.-", $data['unsynched_list'][$selected_count]["value"]);
					$selected_count++;
				}
			}
		} //endif(count($_POST))
		$data['title'] = "Export New Episodes";
        $data['now_id']             =   time();
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
            $new_body   =   "ehr/ehr_admin_export_new_episodes_html";
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
    } // end of function admin_export_new_episodes($id)


    // ------------------------------------------------------------------------
    function admin_export_new_episodesdone($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
        $export_by 		            =   $_SESSION['staff_id'];
        $export_when                =   $data['now_id'];
        $data['export_clinic']    =   $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $data['baseurl']            =   base_url();
        $data['exploded_baseurl']   =   explode('/', $data['baseurl'], 4);
        $data['app_folder']         =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']        =    $data['app_path']."-uploads/exports_consult";
        $version_file_path          = $data['app_path']."/app_thirra/version.txt";
        $handle = fopen($version_file_path, "r");
        $app_version = fread($handle, filesize($version_file_path));
        fclose($handle);

        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']           = $_POST['num_rows'];
            $data['export_reference']   =   $_POST['reference'];
            $data['export_remarks']     =   $_POST['remarks'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_episodes>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$data['export_clinic']['clinic_name']."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$data['export_clinic']['clinic_ref_no']."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
			$selected		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$data['unsynched_list'][$selected]['number']	= $i;
					$data['unsynched_list'][$selected]['value']	= $_POST['s'.$i];
					list($summary_id, $patient_id) = explode("-.-", $data['unsynched_list'][$selected]['value']);					//$data['unsynched_list'][$selected]['patient_info'] 
					//	= $this->memr_rdb->get_patient_details($data['unsynched_list'][$selected]['value']);
					$data['unsynched_list'][$selected]['patient_info']   = $this->memr_rdb->get_patient_demo($patient_id);
					$data['unsynched_list'][$selected]['patcon_info']    = $this->memr_rdb->get_patcon_details($patient_id,$summary_id);
					$data['unsynched_list'][$selected]['complaints_list']= $this->memr_rdb->get_patcon_complaints($summary_id);
					$data['unsynched_list'][$selected]['vitals_info']    = $this->memr_rdb->get_patcon_vitals($summary_id);
					$data['unsynched_list'][$selected]['lab_list']       = $this->memr_rdb->get_patcon_lab($summary_id);
					$data['unsynched_list'][$selected]['imaging_list']   = $this->memr_rdb->get_patcon_imaging($summary_id);
					$data['unsynched_list'][$selected]['diagnosis_list'] = $this->memr_rdb->get_patcon_diagnosis($summary_id);
					$data['unsynched_list'][$selected]['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($summary_id);
					$data['unsynched_list'][$selected]['referrals_list'] = $this->memr_rdb->get_patcon_referrals($summary_id);
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
					$patient_name 	= $data['unsynched_list'][$selected]['patient_info']['name'];
					$name_first 	= $data['unsynched_list'][$selected]['patient_info']['name_first'];
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
					$synch_remarks 		= $data['unsynched_list'][$selected]['patcon_info']['synch_remarks'];
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
					$xmlstr .= "\n\t\t<patient_name>$patient_name</patient_name>";
					$xmlstr .= "\n\t\t<name_first>$name_first</name_first>";
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
					$xmlstr .= "\n\t\t<synch_remarks>$synch_remarks</synch_remarks>";
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
                    
                    //Log session_id's
                    if(isset($data['entities_inserted'])){
                        $data['entities_inserted'] = $data['entities_inserted'].",";
                    } else {
                        $data['entities_inserted'] = "";
                    }
                    $data['entities_inserted']  =   $data['entities_inserted'].$summary_id;
                        
					// Complaints Segment
					if($count_complaints > 0) {
						$k	= 1;
						foreach($data['unsynched_list'][$selected]['complaints_list'] as $complaint) {
							$xmlstr .= "\n\t<complaints_info recno='$k'>";
							$xmlstr .= "\n\t\t<complaint_id>".$complaint['complaint_id']."</complaint_id>";
							$xmlstr .= "\n\t\t<icpc_code>".$complaint['icpc_code']."</icpc_code>";
							$xmlstr .= "\n\t\t<duration>".$complaint['duration']."</duration>";
							$xmlstr .= "\n\t\t<complaint_notes>".$complaint['complaint_notes']."</complaint_notes>";
							$xmlstr .= "\n\t\t<ccode1ext_code>".$complaint['ccode1ext_code']."</ccode1ext_code>";
							$xmlstr .= "\n\t\t<complaint_rank>".$complaint['complaint_rank']."</complaint_rank>";
							$xmlstr .= "\n\t\t<remarks>".$complaint['remarks']."</remarks>";
							$xmlstr .= "\n\t\t<synch_out>".$complaint['synch_out']."</synch_out>";
							$xmlstr .= "\n\t\t<synch_remarks>".$complaint['synch_remarks']."</synch_remarks>";
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
							$xmlstr .= "\n\t\t<vital1_var>".$vitals['vital1_var']."</vital1_var>";
							$xmlstr .= "\n\t\t<vital1_value>".$vitals['vital1_value']."</vital1_value>";
							$xmlstr .= "\n\t\t<vital1_uom>".$vitals['vital1_uom']."</vital1_uom>";
							$xmlstr .= "\n\t\t<vital2_var>".$vitals['vital2_var']."</vital2_var>";
							$xmlstr .= "\n\t\t<vital2_value>".$vitals['vital2_value']."</vital2_value>";
							$xmlstr .= "\n\t\t<vital2_uom>".$vitals['vital2_uom']."</vital2_uom>";
							$xmlstr .= "\n\t\t<synch_out>".$vitals['synch_out']."</synch_out>";
							$xmlstr .= "\n\t\t<synch_remarks>".$vitals['synch_remarks']."</synch_remarks>";
							$xmlstr .= "\n\t</vitals_info>";
							$k++;
						//}
                        }
					} //endif($count_vitals > 0)
					
					// Lab Orders Segment
					if($count_lab > 0) {
						$k	= 1;
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
							$xmlstr .= "\n\t\t<synch_out>".$lab['synch_out']."</synch_out>";
							$xmlstr .= "\n\t\t<synch_remarks>".$lab['synch_remarks']."</synch_remarks>";
							$xmlstr .= "\n\t\t<result_date>".$lab['result_date']."</result_date>";
							$xmlstr .= "\n\t\t<result_ref>".$lab['result_ref']."</result_ref>";
							$xmlstr .= "\n\t\t<recorded_timestamp>".$lab['recorded_timestamp']."</recorded_timestamp>";
							$xmlstr .= "\n\t\t<package_code>".$lab['package_code']."</package_code>";
							$xmlstr .= "\n\t\t<package_name>".$lab['package_name']."</package_name>";
							$xmlstr .= "\n\t\t<supplier_name>".$lab['supplier_name']."</supplier_name>";
                            $l	= 1;
                            $data['unsynched_list'][$selected]['lab_result']    =   array();
                            $data['unsynched_list'][$selected]['lab_result']       = $this->memr_rdb->get_one_lab_results($lab['lab_order_id']);
                            foreach($data['unsynched_list'][$selected]['lab_result'] as $result) {
                                $xmlstr .= "\n\t<lab_test recno='$l'>";
                               $xmlstr .= "\n\t\t<lab_result_id>".$result['lab_result_id']."</lab_result_id>";
                                $xmlstr .= "\n\t\t<sort_test>".$result['sort_test']."</sort_test>";
                                $xmlstr .= "\n\t\t<lab_package_test_id>".$result['lab_package_test_id']."</lab_package_test_id>";
                                $xmlstr .= "\n\t\t<result_date>".$result['result_date']."</result_date>";
                                $xmlstr .= "\n\t\t<date_recorded>".$result['date_recorded']."</date_recorded>";
                                $xmlstr .= "\n\t\t<reply_method>".$result['reply_method']."</reply_method>";
                                $xmlstr .= "\n\t\t<reply_ack_date>".$result['reply_ack_date']."</reply_ack_date>";
                                $xmlstr .= "\n\t\t<result>".$result['result']."</result>";
                                $xmlstr .= "\n\t\t<loinc_num>".$result['loinc_num']."</loinc_num>";
                                $xmlstr .= "\n\t\t<normal_reading>".$result['normal_reading']."</normal_reading>";
                                $xmlstr .= "\n\t\t<abnormal_flag>".$result['abnormal_flag']."</abnormal_flag>";
                                $xmlstr .= "\n\t\t<staff_id>".$result['staff_id']."</staff_id>";
                                $xmlstr .= "\n\t\t<result_remarks>".$result['result_remarks']."</result_remarks>";
                                $xmlstr .= "\n\t\t<result_reviewed_by>".$result['result_reviewed_by']."</result_reviewed_by>";
                                $xmlstr .= "\n\t\t<result_reviewed_date>".$result['result_reviewed_date']."</result_reviewed_date>";
                                $xmlstr .= "\n\t\t<synch_out>".$result['synch_out']."</synch_out>";
                                $xmlstr .= "\n\t\t<synch_remarks>".$result['synch_remarks']."</synch_remarks>";
                                $xmlstr .= "\n\t\t<lab_enhanced_id>".$result['lab_enhanced_id']."</lab_enhanced_id>";
                                $xmlstr .= "\n\t\t<result_ref>".$result['result_ref']."</result_ref>";
                                $xmlstr .= "\n\t\t<recorded_timestamp>".$result['recorded_timestamp']."</recorded_timestamp>";
                                $xmlstr .= "\n\t</lab_test>";
                                $l++;
                                }
							$xmlstr .= "\n\t</lab_info>";
							$k++;
						}
					} //endif($count_lab > 0)
					
					// Imaging Orders Segment
					if($count_imaging > 0) {
						$k	= 1;
						foreach($data['unsynched_list'][$selected]['imaging_list'] as $imaging) {
							$xmlstr .= "\n\t<imaging_info recno='$k'>";
							$xmlstr .= "\n\t\t<imaging_order_id>".$imaging['order_id']."</imaging_order_id>";
							$xmlstr .= "\n\t\t<supplier_ref>".$imaging['supplier_ref']."</supplier_ref>";
							$xmlstr .= "\n\t\t<product_id>".$imaging['product_id']."</product_id>";
							$xmlstr .= "\n\t\t<result_status>".$imaging['result_status']."</result_status>";
							$xmlstr .= "\n\t\t<invoice_status>".$imaging['invoice_status']."</invoice_status>";
							$xmlstr .= "\n\t\t<order_remarks>".$imaging['remarks']."</order_remarks>";
							$xmlstr .= "\n\t\t<synch_out>".$imaging['synch_out']."</synch_out>";
							$xmlstr .= "\n\t\t<synch_remarks>".$imaging['synch_remarks']."</synch_remarks>";
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
							$xmlstr .= "\n\t\t<edit_remarks>".$diagnosis['edit_remarks']."</edit_remarks>";
							$xmlstr .= "\n\t\t<edit_staff>".$diagnosis['edit_staff']."</edit_staff>";
							$xmlstr .= "\n\t\t<edit_date>".$diagnosis['edit_date']."</edit_date>";
							$xmlstr .= "\n\t\t<confirm_dcode1ext>".$diagnosis['confirm_dcode1ext']."</confirm_dcode1ext>";
							$xmlstr .= "\n\t\t<confirm_remarks>".$diagnosis['confirm_remarks']."</confirm_remarks>";
							$xmlstr .= "\n\t\t<confirm_staff>".$diagnosis['confirm_staff']."</confirm_staff>";
							$xmlstr .= "\n\t\t<confirm_date>".$diagnosis['confirm_date']."</confirm_date>";
							$xmlstr .= "\n\t\t<synch_out>".$diagnosis['synch_out']."</synch_out>";
							$xmlstr .= "\n\t\t<synch_remarks>".$diagnosis['synch_remarks']."</synch_remarks>";
							$xmlstr .= "\n\t</diagnosis_info>";
							$k++;
						}
					} //endif($count_diagnosis > 0)
					
					// Prescriptions Segment
					if($count_prescribe > 0) {
						$k	= 1;
						foreach($data['unsynched_list'][$selected]['prescribe_list'] as $prescribe) {
							$xmlstr .= "\n\t<prescribe_info recno='$k'>";
							$xmlstr .= "\n\t\t<queue_id>".$prescribe['queue_id']."</queue_id>";
							$xmlstr .= "\n\t\t<drug_formulary_id>".$prescribe['drug_formulary_id']."</drug_formulary_id>";
							$xmlstr .= "\n\t\t<drug_code_id>".$prescribe['drug_code_id']."</drug_code_id>";
							$xmlstr .= "\n\t\t<dose>".$prescribe['dose']."</dose>";
							$xmlstr .= "\n\t\t<dose_form>".$prescribe['dose_form']."</dose_form>";
							$xmlstr .= "\n\t\t<frequency>".$prescribe['frequency']."</frequency>";
							$xmlstr .= "\n\t\t<instruction>".$prescribe['instruction']."</instruction>";
							$xmlstr .= "\n\t\t<quantity>".$prescribe['quantity']."</quantity>";
							$xmlstr .= "\n\t\t<quantity_form>".$prescribe['quantity_form']."</quantity_form>";
							$xmlstr .= "\n\t\t<indication>".$prescribe['indication']."</indication>";
							$xmlstr .= "\n\t\t<caution>".$prescribe['caution']."</caution>";
							$xmlstr .= "\n\t\t<status>".$prescribe['status']."</status>";
							$xmlstr .= "\n\t\t<dose_duration>".$prescribe['dose_duration']."</dose_duration>";
							$xmlstr .= "\n\t\t<synch_out>".$prescribe['synch_out']."</synch_out>";
							$xmlstr .= "\n\t\t<synch_remarks>".$prescribe['synch_remarks']."</synch_remarks>";
							$xmlstr .= "\n\t\t<formulary_code>".$prescribe['formulary_code']."</formulary_code>";
							$xmlstr .= "\n\t\t<generic_name>".$prescribe['generic_name']."</generic_name>";
							$xmlstr .= "\n\t\t<drug_tradename>".$prescribe['trade_name']."</drug_tradename>";
							$xmlstr .= "\n\t\t<formulary_system>".$prescribe['formulary_system']."</formulary_system>";
							$xmlstr .= "\n\t</prescribe_info>";
							$k++;
						}
					} //endif($count_prescribe > 0)
					
					// Referrals Segment
					if($count_referrals > 0) {
						$k	= 1;
						foreach($data['unsynched_list'][$selected]['referrals_list'] as $referral) {
							$xmlstr .= "\n\t<referral_info recno='$k'>";
							$xmlstr .= "\n\t\t<referral_id>".$referral['referral_id']."</referral_id>";
							$xmlstr .= "\n\t\t<referral_doctor_id>".$referral['referral_doctor_id']."</referral_doctor_id>";
							$xmlstr .= "\n\t\t<referral_doctor_name>".$referral['referral_doctor_name']."</referral_doctor_name>";
							$xmlstr .= "\n\t\t<referral_specialty>".$referral['referral_specialty']."</referral_specialty>";
							$xmlstr .= "\n\t\t<referral_centre>".$referral['referral_centre']."</referral_centre>";
							$xmlstr .= "\n\t\t<referral_date>".$referral['referral_date']."</referral_date>";
							$xmlstr .= "\n\t\t<reason>".$referral['reason']."</reason>";
							$xmlstr .= "\n\t\t<clinical_exam>".$referral['clinical_exam']."</clinical_exam>";
							$xmlstr .= "\n\t\t<history_attached>".$referral['history_attached']."</history_attached>";
							$xmlstr .= "\n\t\t<referral_sequence>".$referral['referral_sequence']."</referral_sequence>";
							$xmlstr .= "\n\t\t<referral_reference>".$referral['referral_reference']."</referral_reference>";
							$xmlstr .= "\n\t\t<date_replied>".$referral['date_replied']."</date_replied>";
							$xmlstr .= "\n\t\t<replying_doctor>".$referral['replying_doctor']."</replying_doctor>";
							$xmlstr .= "\n\t\t<replying_specialty>".$referral['replying_specialty']."</replying_specialty>";
							$xmlstr .= "\n\t\t<replying_centre>".$referral['replying_centre']."</replying_centre>";
							$xmlstr .= "\n\t\t<department>".$referral['department']."</department>";
							$xmlstr .= "\n\t\t<findings>".$referral['findings']."</findings>";
							$xmlstr .= "\n\t\t<investigation>".$referral['investigation']."</investigation>";
							$xmlstr .= "\n\t\t<diagnosis>".$referral['diagnosis']."</diagnosis>";
							$xmlstr .= "\n\t\t<treatment>".$referral['treatment']."</treatment>";
							$xmlstr .= "\n\t\t<plan>".$referral['plan']."</plan>";
							$xmlstr .= "\n\t\t<comments>".$referral['comments']."</comments>";
							$xmlstr .= "\n\t\t<reply_recorder>".$referral['reply_recorder']."</reply_recorder>";
							$xmlstr .= "\n\t\t<date_recorded>".$referral['date_recorded']."</date_recorded>";
							$xmlstr .= "\n\t\t<synch_out>".$referral['synch_out']."</synch_out>";
							$xmlstr .= "\n\t\t<synch_remarks>".$referral['synch_remarks']."</synch_remarks>";
							$xmlstr .= "\n\t</referral_info>";
							$k++;
						}
					} //endif($count_referrals > 0)
					
					$xmlstr .= "\n</clinical_episode>";
					$selected++;
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
		} //endif(count($_POST))
		$data['title'] = "Exported New Episodes";
        $data['now_id']             =   time();
		$data['file_exported']		=	"patient_episode-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		//$address1 = $data['unsynched_list'][1]['patient_info']['patient_address'];
		//$xmlstr .= "\n\t<address1>$address1</address1>";
		$xmlstr .= "\n</THIRRA_export_episodes>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

        // New log record
        $ins_log_array   =   array();
        $ins_log_array['data_synch_log_id'] = $data['now_id'];
        $ins_log_array['export_by']         = $_SESSION['staff_id'];
        $ins_log_array['export_when']       = $data['now_id'];
        $ins_log_array['thirra_version']    = $app_version;
        $ins_log_array['export_clinicname'] = $data['export_clinic']['clinic_name'];
        $ins_log_array['export_clinicref']  = $data['export_clinic']['clinic_ref_no'];
        $ins_log_array['export_reference']  = $data['export_reference'];
        //$ins_log_array['import_by']         = $_SESSION['staff_id'];
        //$ins_log_array['import_when']       = $data['now_id'];
        $ins_log_array['data_filename']     = $data['file_exported'];
        //$ins_log_array['import_remarks']    = $data['import_remarks'];
        //$ins_log_array['import_reference']  = $data['import_reference'];
        //$ins_log_array['import_number']     = $data['import_number'];
        //$ins_log_array['import_outcome']    = $data['import_outcome'];
        $ins_log_array['count_inserted']    = $selected - 1;
        //$ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
        //$ins_log_array['count_rejected']    = $data['count_rejected'];
        $ins_log_array['entities_inserted'] = $data['entities_inserted'];
        //$ins_log_array['entities_declined'] = $data['entities_declined'];
        //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
        //$ins_log_array['declined_list']     = $data['declined_list'];
        //$ins_log_array['rejected_list']     = $data['rejected_list'];
        $ins_log_array['outcome_remarks']   = "Success";
        $ins_log_array['sync_type']         = "Manual EDI - Episodes Data";
        $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

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
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_export_new_episodesdone_html";
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
    } // end of function admin_export_new_episodesdone($id)


    // ------------------------------------------------------------------------
    function admin_list_open_episodes($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Export Episodes";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_episodes('ALL', 'Open');
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_episodes('ALL',TRUE);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            $new_body   =   "ehr/ehr_admin_list_open_episodes_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_list_open_episodes_html";
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
    } // end of function admin_list_open_episodes($id)


    // ------------------------------------------------------------------------
    function admin_export_history_immunisation($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "Export Immunisation Histories";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_historyimmunisation('ALL');
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_historyimmunisation('ALL',TRUE);
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
            $new_body   =   "ehr/ehr_admin_export_historyimmunisation_html";
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
    } // end of function admin_export_history_immunisation($id)


    // ------------------------------------------------------------------------
    function admin_export_new_historyimmunisation($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			$selected_count		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				if(isset($_POST['s'.$i])){
				// Only retrieve if selected by user
					$data['unsynched_list'][$selected_count]["number"]	= $i;
					$data['unsynched_list'][$selected_count]["value"]	= $_POST['s'.$i];
					list(
						$data['unsynched_list'][$selected_count]["patient_immunisation_id"],
						$data['unsynched_list'][$selected_count]["vaccine_short"],
						$data['unsynched_list'][$selected_count]["patient_id"],
						$data['unsynched_list'][$selected_count]["name"],
						$data['unsynched_list'][$selected_count]["name_first"],
						$data['unsynched_list'][$selected_count]["birth_date"],
						$data['unsynched_list'][$selected_count]["gender"],
						$data['unsynched_list'][$selected_count]["immunisation_id"],
						$data['unsynched_list'][$selected_count]["synch_out"]
					)= explode("-.-", $data['unsynched_list'][$selected_count]["value"]);
					$selected_count++;
				}
			}
		} //endif(count($_POST))
		$data['title'] = "Export New Immunisation Histories";
        $data['now_id']             =   time();
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
            $new_body   =   "ehr/ehr_admin_export_new_historyimmunisation_html";
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
    } // end of function admin_export_new_historyimmunisation($id)


    // ------------------------------------------------------------------------
    function admin_export_new_historyimmunisation_done($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
        $data['export_clinic']    =   $this->mthirra->get_clinic_info($_SESSION['location_id']);
        $data['baseurl']            =   base_url();
        $data['exploded_baseurl']   =   explode('/', $data['baseurl'], 4);
        $data['app_folder']         =   substr($data['exploded_baseurl'][3], 0, -1);
        $data['DOCUMENT_ROOT']      =   $_SERVER['DOCUMENT_ROOT'];
        if(substr($data['DOCUMENT_ROOT'],-1) === "/"){
            // Do nothing
        } else {
            // Add a slash
            $data['DOCUMENT_ROOT']  =   $data['DOCUMENT_ROOT'].'/';
        }
        $data['app_path']           =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['export_path']        =    $data['app_path']."-uploads/exports_history";
        $version_file_path          = $data['app_path']."/app_thirra/version.txt";
        $handle = fopen($version_file_path, "r");
        $app_version = fread($handle, filesize($version_file_path));
        fclose($handle);

        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
            $data['export_reference']   =   $_POST['reference'];
            $data['export_remarks']     =   $_POST['remarks'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_historyimmunisation>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_reference>".$data['export_reference']."</export_reference>";
            $xmlstr .= "\n\t\t<export_clinicname>".$data['export_clinic']['clinic_name']."</export_clinicname>";
            $xmlstr .= "\n\t\t<export_clinicref>".$data['export_clinic']['clinic_ref_no']."</export_clinicref>";
            $xmlstr .= "\n\t\t<export_clinicid>".$_SESSION['location_id']."</export_clinicid>";
            $xmlstr .= "\n\t\t<export_remarks>".$data['export_remarks']."</export_remarks>";
            $xmlstr .= "\n\t\t<export_username>".$_SESSION['username']."</export_username>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
            $xmlstr .= "\n\t\t<current_db>".$data['current_db']."</current_db>";
            $xmlstr .= "\n\t</export_info>";
			$selected		=	1;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$data['unsynched_list'][$selected]['number']	= $i;
					$data['unsynched_list'][$selected]['value']	= $_POST['s'.$i];
					list($patient_immunisation_id, $immunisation_id, $patient_id) = explode("-.-", $data['unsynched_list'][$selected]['value']);					$data['unsynched_list'][$selected]['patient_info']   = $this->memr_rdb->get_patient_demo($patient_id);
					$data['unsynched_list'][$selected]['immunisation_info'] = $this->memr_rdb->get_vaccines_list($patient_id,999, 0, $immunisation_id);
					//$data['unsynched_list'][$selected]['checkup_info'] = $this->memr_rdb->get_antenatal_followup('one',$patient_id, $antenatal_id, $antenatal_followup_id);
					if($data['debug_mode']){
						echo "<pre>";
						echo "\n<br />print_r(patient_info)=<br />";
						print_r($data['unsynched_list'][$selected]['patient_info']);
						echo "\n<br />print_r(antenatal_info)=<br />";
						print_r($data['unsynched_list'][$selected]['antenatal_info']);
						echo "</pre>";
					}
					$patient_id 	    = $data['unsynched_list'][$selected]['patient_info']['patient_id'];
					$patient_name 	    = $data['unsynched_list'][$selected]['patient_info']['name'];
					$name_first 	    = $data['unsynched_list'][$selected]['patient_info']['name_first'];
                    
					$patient_immunisation_id       = $data['unsynched_list'][$selected]['immunisation_info'][0]['patient_immunisation_id'];
					$staff_id       = $data['unsynched_list'][$selected]['immunisation_info'][0]['staff_id'];
					$session_id         = $data['unsynched_list'][$selected]['immunisation_info'][0]['session_id'];
					$immunisation_date       = $data['unsynched_list'][$selected]['immunisation_info'][0]['date'];
					$dispense_queue_id       = $data['unsynched_list'][$selected]['immunisation_info'][0]['dispense_queue_id'];
					$prescript_queue_id       = $data['unsynched_list'][$selected]['immunisation_info'][0]['prescript_queue_id'];
					$notes       = $data['unsynched_list'][$selected]['immunisation_info'][0]['notes'];
                    
					$immunisation_id       = $data['unsynched_list'][$selected]['immunisation_info'][0]['immunisation_id'];
					$vaccine_short       = $data['unsynched_list'][$selected]['immunisation_info'][0]['vaccine_short'];
					$vaccine       = $data['unsynched_list'][$selected]['immunisation_info'][0]['vaccine'];
					$dose       = $data['unsynched_list'][$selected]['immunisation_info'][0]['dose'];
					$immunisation_code       = $data['unsynched_list'][$selected]['immunisation_info'][0]['immunisation_code'];
                                       
					$synch_out 		    = $data['unsynched_list'][$selected]['immunisation_info'][0]['synch_out'];
					$synch_remarks 		= $data['unsynched_list'][$selected]['immunisation_info'][0]['synch_remarks'];
					$count_others		= 0;
                    
					$xmlstr .= "\n<history_immunisation patient_immunisation_id='$patient_immunisation_id'>";
                    
					$xmlstr .= "\n\t<patient_info>";
					$xmlstr .= "\n\t\t<patient_id>$patient_id</patient_id>";
					$xmlstr .= "\n\t\t<patient_name>$patient_name</patient_name>";
					$xmlstr .= "\n\t\t<name_first>$name_first</name_first>";
					$xmlstr .= "\n\t</patient_info>";
                    
					$xmlstr .= "\n\t<immunisation_info>";
					$xmlstr .= "\n\t\t<patient_immunisation_id>$patient_immunisation_id</patient_immunisation_id>";
					$xmlstr .= "\n\t\t<staff_id>$staff_id</staff_id>";
					$xmlstr .= "\n\t\t<session_id>$session_id</session_id>";
                    $xmlstr .= "\n\t\t<immunisation_date>$immunisation_date</immunisation_date>";
					$xmlstr .= "\n\t\t<dispense_queue_id>$dispense_queue_id</dispense_queue_id>";
					$xmlstr .= "\n\t\t<prescript_queue_id>$prescript_queue_id</prescript_queue_id>";
					$xmlstr .= "\n\t\t<notes>$notes</notes>";
					$xmlstr .= "\n\t\t<immunisation_id>$immunisation_id</immunisation_id>";
					$xmlstr .= "\n\t\t<vaccine_short>$vaccine_short</vaccine_short>";
					$xmlstr .= "\n\t\t<vaccine>$vaccine</vaccine>";
					$xmlstr .= "\n\t\t<dose>$dose</dose>";
					$xmlstr .= "\n\t\t<immunisation_code>$immunisation_code</immunisation_code>";
                                        
					$xmlstr .= "\n\t\t<synch_out>$synch_out</synch_out>";
					$xmlstr .= "\n\t\t<synch_remarks>$synch_remarks</synch_remarks>";
					$xmlstr .= "\n\t</immunisation_info>";
					 															
					$xmlstr .= "\n</history_immunisation>";
					$selected++;
                    
                    //Log patient_id's
                    if(isset($data['entities_inserted'])){
                        $data['entities_inserted'] = $data['entities_inserted'].",";
                    } else {
                        $data['entities_inserted'] = "";
                    }
                    $data['entities_inserted']  =   $data['entities_inserted'].$patient_immunisation_id;
                        
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
		} //endif(count($_POST))
		$data['title'] = "Exported New Immunisation Histories";
        $data['now_id']             =   time();
		$data['file_exported']		=	"patient_historyimmunisation-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		//$address1 = $data['unsynched_list'][1]['patient_info']['patient_address'];
		//$xmlstr .= "\n\t<address1>$address1</address1>";
		$xmlstr .= "\n</THIRRA_export_historyimmunisation>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

        // New log record
        $ins_log_array   =   array();
        $ins_log_array['data_synch_log_id'] = $data['now_id'];
        $ins_log_array['export_by']         = $_SESSION['staff_id'];
        $ins_log_array['export_when']       = $data['now_id'];
        $ins_log_array['thirra_version']    = $app_version;
        $ins_log_array['export_clinicname'] = $data['export_clinic']['clinic_name'];
        $ins_log_array['export_clinicref']  = $data['export_clinic']['clinic_ref_no'];
        $ins_log_array['export_reference']  = $data['export_reference'];
        //$ins_log_array['import_by']         = $_SESSION['staff_id'];
        //$ins_log_array['import_when']       = $data['now_id'];
        $ins_log_array['data_filename']     = $data['file_exported'];
        //$ins_log_array['import_remarks']    = $data['import_remarks'];
        //$ins_log_array['import_reference']  = $data['import_reference'];
        //$ins_log_array['import_number']     = $data['import_number'];
        //$ins_log_array['import_outcome']    = $data['import_outcome'];
        $ins_log_array['count_inserted']    = $selected - 1;
        //$ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
        //$ins_log_array['count_rejected']    = $data['count_rejected'];
        $ins_log_array['entities_inserted'] = $data['entities_inserted'];
        //$ins_log_array['entities_declined'] = $data['entities_declined'];
        //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
        //$ins_log_array['declined_list']     = $data['declined_list'];
        //$ins_log_array['rejected_list']     = $data['rejected_list'];
        $ins_log_array['outcome_remarks']   = "Success";
        $ins_log_array['sync_type']         = "Manual EDI - Immunisation Data";
        $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

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
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_export_new_historyimmunisation_done_html";
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
    } // end of function admin_export_new_historyimmunisation_done($id)


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
            $new_banner =   "ehr/banner_emr_wap";
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

/* End of file ehr_admin_edi.php */
/* Location: ./app_thirra/controllers/ehr_admin_edi.php */

