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
 * Controller Class for EHR_ADMIN
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.9
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_admin_edi_import extends MY_Controller 
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
    // === ADMIN EDI IMPORT
    // ------------------------------------------------------------------------
    function admin_list_synchlogs($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['sort_order']   	    = $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Synch Logs";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['exports_list'] = $this->madmin_rdb->get_synch_logs("Export");
		$data['imports_list'] = $this->madmin_rdb->get_synch_logs("Import");
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_list_systemusers_wap";
            $new_body   =   "ehr/ehr_admin_list_synchlogs_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_list_synchlogs_html";
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
		
    } // end of function admin_list_synchlogs($id)


    // ------------------------------------------------------------------------
    function admin_import_logins($id=NULL)  
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Import Antenatal Info";
		$data['form_purpose']       = 	"new_import";
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['app_path']       =   $data['DOCUMENT_ROOT'].$data['app_folder'];
        $data['import_path']    =    $data['app_path']."-uploads/imports_system";
		$data['directory']      = $data['import_path'];
		// get directory contents as an array
		$data['fileList'] = scandir($data['directory']) or die ("Not a directory");
		// print file names and sizes
		//$data['unsynched_list'] =	array('0' => array('filename' => 'patient_demo.xml','export_date' => '2010-01-20'));
		//$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients();
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
            $new_body   =   "ehr/ehr_admin_import_logins_html";
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
    } // end of function admin_import_logins($id)


    // ------------------------------------------------------------------------
    function admin_import_new_logins($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		// User has posted the form
		$data['filename']   = $this->uri->segment(3);
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_system";
		$data['directory'] = $data['import_path'];
		$xml_file			= $data['directory']."/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
        $data['thirra_version']     =   $xml->export_info->thirra_version;
        $data['export_clinicname']  =   $xml->export_info->export_clinicname;
        $data['export_clinicref']   =   $xml->export_info->export_clinicref;
        $data['export_clinicid']    =   $xml->export_info->export_clinicid;
        $data['export_reference']   =   $xml->export_info->export_reference;
        $data['export_username']    =   $xml->export_info->export_username;
		$i	=	1;
		foreach ($xml->history_login as $item) {
			$data['unsynched_list'][$i]['log_id']	=	$item->log_id;
			$data['unsynched_list'][$i]['log_date']	=	$item->log_date;
			$data['unsynched_list'][$i]['login_time']	=	$item->login_time;
			$data['unsynched_list'][$i]['username']	=	$item->username;
			$data['unsynched_list'][$i]['immunisation_id']	=	$item->immunisation_info->immunisation_id;
			$data['unsynched_list'][$i]['vaccine_short']	=	$item->immunisation_info->vaccine_short;
			$data['unsynched_list'][$i]['immunisation_date']	=	$item->immunisation_info->immunisation_date;
			$data['unsynched_list'][$i]['synch_out']	=	(int)$item->synch_out;
			$i++;
		} // endforeach ($xml->patient_info as $item)
		$data['title'] = "Import New Immunisation Histories";
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
            $new_body   =   "ehr/ehr_admin_import_new_logins_html";
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
    } // end of function admin_import_new_logins($id)


    // ------------------------------------------------------------------------
    function admin_import_new_loginsdone($id=NULL)  // template for new classes
    {
		$this->load->model('mconsult_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Imported New Immunisation Histories";
        $data['now_id']             =   time();
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_system";
		$data['directory'] = $data['import_path'];
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']           = $_POST['num_rows'];
            $data['import_reference']   =   $_POST['reference'];
            $data['import_remarks']     =   $_POST['remarks'];
			
			// Retrieve what user selected
			$selected		=	0;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$selected++;
					//$data['selected_list'][$selected]['number']	= $i;
					$data['selected_list'][$selected]['patient_immunisation_id']	= $_POST['s'.$i];
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
			$data['total_selected'] = $selected;
			// Retrieve all records from XML file
			$data['filename']   = $this->uri->segment(3);
			$xml_file			= $data['directory']."/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
            $data['thirra_version']     =   $xml->export_info->thirra_version;
            $data['export_clinicname']  =   $xml->export_info->export_clinicname;
            $data['export_clinicref']   =   $xml->export_info->export_clinicref;
            $data['export_clinicid']    =   $xml->export_info->export_clinicid;
            $data['export_reference']   =   $xml->export_info->export_reference;
            $data['export_username']    =   $xml->export_info->export_username;
            $data['export_when']        =   $xml->export_info->export_when;
            $data['count_inserted']     =   0;
            $data['count_rejected']     =   0;
			foreach ($xml->history_login as $item) {
				$data['unsynched_list']['log_id']	    =	(string)$item->log_id;
				$data['unsynched_list']['log_date']     =	(string)$item->log_date;
				$data['unsynched_list']['user_id']      =	(string)$item->user_id;
				$data['unsynched_list']['login_time']   =	(string)$item->login_time;
				$data['unsynched_list']['logout_time']  =	(string)$item->logout_time;
				$data['unsynched_list']['login_location']=	(string)$item->login_location;
				$data['unsynched_list']['login_ip']     =	(string)$item->login_ip;
				$data['unsynched_list']['webbrowser']   =	(string)$item->webbrowser;
				$data['unsynched_list']['log_outcome']  =	(string)$item->log_outcome;
				$data['unsynched_list']['synch_out']    =	(string)$item->synch_out;
				$data['unsynched_list']['synch_remarks']=	(string)$item->synch_remarks;
				$data['unsynched_list']['final']		=	"FALSE"; // Initialise

                if($data['debug_mode']){
                    echo "<br />unsynched_list = ";
                    echo $data['unsynched_list']['log_id'];
                }
                $data['unsynched_list'][$i]['final']	=	"TRUE";
                // Write to DB
                $ins_history_array   =   array();
                $ins_history_array['log_id']        = $data['unsynched_list']['log_id'];
                $ins_history_array['date']          = $data['unsynched_list']['log_date'];
                $ins_history_array['user_id']       = $data['unsynched_list']['user_id'];
                $ins_history_array['login_time']    = $data['unsynched_list']['login_time'];
                $ins_history_array['logout_time']   = $data['unsynched_list']['logout_time'];
                $ins_history_array['login_location']= $data['unsynched_list']['login_location'];
                $ins_history_array['login_ip']      = $data['unsynched_list']['login_ip'];
                $ins_history_array['webbrowser']    = $data['unsynched_list']['webbrowser'];
                $ins_history_array['log_outcome']   = $data['unsynched_list']['log_outcome'];
                $ins_history_array['synch_out']     = $data['unsynched_list']['synch_out'];
                $ins_history_array['synch_in']      = $data['now_id'];
                $ins_history_data       =   $this->mthirra->insert_new_login($ins_history_array);
                
                //Log patient_immunisation_id's
                if(isset($data['entities_inserted'])){
                    $data['entities_inserted'] = $data['entities_inserted'].",";
                } else {
                    $data['entities_inserted'] = "";
                }
                $data['entities_inserted']  =   $data['entities_inserted'].$ins_history_array['log_id'];
                
                $data['count_inserted'] = $data['count_inserted'] + 1;
			} // endforeach ($xml->patient_info as $item)
            
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
            $ins_log_array['count_inserted']    = $data['count_inserted'];
            $ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
            $ins_log_array['count_rejected']    = $data['count_rejected'];
            $ins_log_array['entities_inserted'] = $data['entities_inserted'];
            //$ins_log_array['entities_declined'] = $data['entities_declined'];
            //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
            //$ins_log_array['declined_list']     = $data['declined_list'];
            //$ins_log_array['rejected_list']     = $data['rejected_list'];
            $ins_log_array['outcome_remarks']   = "No problems encountered.";
            $ins_log_array['sync_type']         = "Manual EDI - Logins Data";
            $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

			echo form_open('ehr_admin/admin_mgt');
			//echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
			echo "Done. <input type='submit' value='Click to Continue' />";
			echo "</form>";
			
		} //endif(count($_POST))
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
            $new_body   =   "ehr/ehr_admin_import_new_patientsdone_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		/*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		*/
    } // end of function admin_import_new_loginsdone($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES


    // ------------------------------------------------------------------------
    function admin_import_patients($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title']              = "Import Patients";
		$data['form_purpose']       = 	"new_export";
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
		// define directory path
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_patient";
		$data['directory'] = $data['import_path'];
		//$data['directory'] = '/var/www/thirra-uploads/imports_patient';
                
		// get directory contents as an array
		$data['fileList'] = scandir($data['import_path']) or die ("Not a directory");
		// print file names and sizes
		//$data['unsynched_list'] =	array('0' => array('filename' => 'patient_demo.xml','export_date' => '2010-01-20'));
		//$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients();
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_patients(TRUE);
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
            $new_body   =   "ehr/ehr_admin_import_patients_html";
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
    } // end of function admin_import_patients($id)


    // ------------------------------------------------------------------------
    function admin_import_new_patients($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		// User has posted the form
		$data['filename']   = $this->uri->segment(3);
        
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_patient";
		$data['directory'] = $data['import_path'];
        $xml_file			= $data['directory']."/".$data['filename'];
		//$xml_file			= "/var/www/thirra-uploads/imports_patient/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
        $data['thirra_version']     =   $xml->export_info->thirra_version;
        $data['export_clinicname']  =   $xml->export_info->export_clinicname;
        $data['export_clinicref']   =   $xml->export_info->export_clinicref;
        $data['export_clinicid']    =   $xml->export_info->export_clinicid;
        $data['export_reference']   =   $xml->export_info->export_reference;
        $data['export_username']    =   $xml->export_info->export_username;
		$i	=	1;
		foreach ($xml->patient_info as $item) {
			$data['unsynched_list'][$i]['patient_id']	=	$item->patient_id;
			$data['unsynched_list'][$i]['patient_name']	=	$item->patient_name;
			$data['unsynched_list'][$i]['name_first']	=	$item->name_first;
			$data['unsynched_list'][$i]['birth_date']	=	$item->birth_date;
			$data['unsynched_list'][$i]['gender']		=	$item->gender;
			$data['unsynched_list'][$i]['synch_out']	=	(int)$item->synch_out;
			$i++;
		} // endforeach ($xml->patient_info as $item)
		$data['title'] = "Import New Patients";
        $data['now_id']             =   time();
		$data['imported_before'] = $this->madmin_rdb->get_synch_logs("Import",NULL,$data['filename']);
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
            $new_body   =   "ehr/ehr_admin_import_new_patients_html";
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
    } // end of function admin_import_new_patients($id)


    // ------------------------------------------------------------------------
    function admin_import_new_patientsdone($id=NULL)  // template for new classes
    {
		$this->load->model('mehr_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Imported New Patients";
        $data['now_id']             =   time();
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_patient";
		$data['directory'] = $data['import_path'];
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']           = $_POST['num_rows'];
            $data['import_reference']   =   $_POST['reference'];
            $data['import_remarks']     =   $_POST['remarks'];
			
			// Retrieve what user selected
			$selected		=	0;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$selected++;
					//$data['selected_list'][$selected]['number']	= $i;
					$data['selected_list'][$selected]['patient_id']	= $_POST['s'.$i];
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
			$data['total_selected'] = $selected;
			// Retrieve all records from XML file
			$data['filename']   = $this->uri->segment(3);
			$xml_file			= $data['directory']."/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
            $data['thirra_version']     =   $xml->export_info->thirra_version;
            $data['export_clinicname']  =   $xml->export_info->export_clinicname;
            $data['export_clinicref']   =   $xml->export_info->export_clinicref;
            $data['export_clinicid']    =   $xml->export_info->export_clinicid;
            $data['export_reference']   =   $xml->export_info->export_reference;
            $data['export_username']    =   $xml->export_info->export_username;
            $data['export_when']        =   $xml->export_info->export_when;
            $data['count_inserted']     =   0;
            $data['count_rejected']     =   0;
			foreach ($xml->patient_info as $item) {
				$data['unsynched_list'][$i]['patient_id']	=	(string)$item->patient_id;
				$data['unsynched_list'][$i]['patient_name']	=	$item->patient_name;
				$data['unsynched_list'][$i]['name_first']	=	$item->name_first;
				$data['unsynched_list'][$i]['name_alias']	=	$item->name_alias;
				$data['unsynched_list'][$i]['gender']		=	$item->gender;
				$data['unsynched_list'][$i]['ic_no']		=	$item->ic_no;
				$data['unsynched_list'][$i]['ic_other_no']	=	$item->ic_other_no;
				$data['unsynched_list'][$i]['nationality']	=	$item->nationality;
				$data['unsynched_list'][$i]['birth_date']	=	$item->birth_date;
				$data['unsynched_list'][$i]['clinic_reference_number']=$item->clinic_reference_number;
				$data['unsynched_list'][$i]['ethnicity']	=	$item->ethnicity;
				$data['unsynched_list'][$i]['religion']	=	$item->religion;
				$data['unsynched_list'][$i]['marital_status']	=	$item->marital_status;
				$data['unsynched_list'][$i]['patient_type']	=	$item->patient_type;
				$data['unsynched_list'][$i]['blood_group']	=	$item->blood_group;
				$data['unsynched_list'][$i]['blood_rhesus']	=	$item->blood_rhesus;
				$data['unsynched_list'][$i]['demise_date']	=	$item->demise_date;
				$data['unsynched_list'][$i]['demise_time']	=	$item->demise_time;
				$data['unsynched_list'][$i]['demise_cause']	=	$item->demise_cause;
				$data['unsynched_list'][$i]['clinic_home']		=	$item->clinic_home;
				$data['unsynched_list'][$i]['clinic_registered']		=	$item->clinic_registered;
				$data['unsynched_list'][$i]['patient_status']		=	$item->status;
				$data['unsynched_list'][$i]['contact_id']		=	$item->contact_id;
				$data['unsynched_list'][$i]['start_date']		=	$item->start_date;
				$data['unsynched_list'][$i]['patient_address']	=	$item->patient_address;
				$data['unsynched_list'][$i]['patient_address2']	=	$item->patient_address2;
				$data['unsynched_list'][$i]['patient_address3']	=	$item->patient_address3;
				$data['unsynched_list'][$i]['patient_town']		=	$item->patient_town;
				$data['unsynched_list'][$i]['patient_postcode']	=	$item->patient_postcode;
				$data['unsynched_list'][$i]['patient_state']	=	$item->patient_state;
				$data['unsynched_list'][$i]['patient_country']	=	$item->patient_country;
				$data['unsynched_list'][$i]['tel_home']		=	$item->tel_home;
				$data['unsynched_list'][$i]['tel_office']		=	$item->tel_office;
				$data['unsynched_list'][$i]['tel_mobile']		=	$item->tel_mobile;
				$data['unsynched_list'][$i]['fax_no']		=	$item->fax_no;
				$data['unsynched_list'][$i]['email']		=	$item->email;
				$data['unsynched_list'][$i]['staff_id']		=	$item->staff_id;
				$data['unsynched_list'][$i]['synch_out']	=	(int)$item->synch_out;
				$data['unsynched_list'][$i]['final']		=	"FALSE"; // Initialise

				// Compare array against selected list and Flag as selected
				for ($j=1; $j <= $data['total_selected']; $j++) {
					if($data['debug_mode']){
						echo "<br />j = ".$j; 
						echo "<br />selected_list = ";
						echo $data['selected_list'][$j]['patient_id'];
						echo "<br />unsynched_list = ";
						echo $data['unsynched_list'][$i]['patient_id'];
					}
					if($data['selected_list'][$j]['patient_id'] === $data['unsynched_list'][$i]['patient_id']){
						$data['unsynched_list'][$i]['final']	=	"TRUE";
                        if($data['debug_mode']){
                            echo $data['unsynched_list'][$i]['final'];
                        }
						// Write to DB
						$ins_patient_array   =   array();
						$ins_patient_array['staff_id']           = $data['unsynched_list'][$i]['staff_id'];
						$ins_patient_array['now_id']             = $data['now_id'];
						$ins_patient_array['patient_id']         = (string)$data['unsynched_list'][$i]['patient_id'];
						$ins_patient_array['clinic_reference_number']= $data['unsynched_list'][$i]['clinic_reference_number'];
						$ins_patient_array['patient_name']       = $data['unsynched_list'][$i]['patient_name'];
						$ins_patient_array['name_first']         = $data['unsynched_list'][$i]['name_first'];
						$ins_patient_array['name_alias']         = $data['unsynched_list'][$i]['name_alias'];
						$ins_patient_array['ic_no']              = $data['unsynched_list'][$i]['ic_no'];;
						$ins_patient_array['ic_other_no']        = $data['unsynched_list'][$i]['ic_other_no'];
						$ins_patient_array['nationality']        = $data['unsynched_list'][$i]['nationality'];
						$ins_patient_array['birth_date']         = $data['unsynched_list'][$i]['birth_date'];
						$ins_patient_array['family_link']        = "Head of Family";
						$ins_patient_array['gender']             = $data['unsynched_list'][$i]['gender'];
						$ins_patient_array['ethnicity']          = $data['unsynched_list'][$i]['ethnicity'];
						$ins_patient_array['religion']           = $data['unsynched_list'][$i]['religion'];
						$ins_patient_array['marital_status']           = $data['unsynched_list'][$i]['marital_status'];
						$ins_patient_array['patient_type']       = $data['unsynched_list'][$i]['patient_type'];
						$ins_patient_array['blood_group']        = $data['unsynched_list'][$i]['blood_group'];
						$ins_patient_array['blood_rhesus']       = $data['unsynched_list'][$i]['blood_rhesus'];
						//if(empty($data['unsynched_list'][$i]['demise_date'])){
						if(strlen($data['unsynched_list'][$i]['demise_date']) > 0){
							$ins_patient_array['demise_date']    = $data['unsynched_list'][$i]['demise_date'];
						}
						if(strlen($data['unsynched_list'][$i]['demise_time']) > 0){
							$ins_patient_array['demise_time']    = $data['unsynched_list'][$i]['demise_time'];
						}
						$ins_patient_array['demise_cause']       = $data['unsynched_list'][$i]['demise_cause'];
						$ins_patient_array['clinic_home']        = $data['unsynched_list'][$i]['clinic_home'];
						$ins_patient_array['clinic_registered']  = $data['unsynched_list'][$i]['clinic_registered'];
						$ins_patient_array['patient_status']     = $data['unsynched_list'][$i]['patient_status'];
						//$ins_patient_array['location_id']        = $data['unsynched_list'][$i]['location_id'];
						$ins_patient_array['contact_id']         = $data['unsynched_list'][$i]['contact_id'];
						$ins_patient_array['patient_correspondence_id']  = $data['unsynched_list'][$i]['contact_id'];
						$ins_patient_array['contact_type']       = "Residence";
						$ins_patient_array['correspondence_type']= "Correspondence";
						$ins_patient_array['start_date']         = $data['unsynched_list'][$i]['start_date'];
						$ins_patient_array['patient_address']    = $data['unsynched_list'][$i]['patient_address'];
						$ins_patient_array['patient_address2']   = $data['unsynched_list'][$i]['patient_address2'];
						$ins_patient_array['patient_address3']   = $data['unsynched_list'][$i]['patient_address3'];
						$ins_patient_array['patient_postcode']   = $data['unsynched_list'][$i]['patient_postcode'];
						$ins_patient_array['patient_town']       = $data['unsynched_list'][$i]['patient_town'];
						$ins_patient_array['patient_state']      = $data['unsynched_list'][$i]['patient_state'];
						$ins_patient_array['tel_home']          = $data['unsynched_list'][$i]['tel_home'];
						$ins_patient_array['tel_office']        = $data['unsynched_list'][$i]['tel_office'];
						$ins_patient_array['tel_mobile']        = $data['unsynched_list'][$i]['tel_mobile'];
						$ins_patient_array['fax_no']            = $data['unsynched_list'][$i]['fax_no'];
						$ins_patient_array['email']             = $data['unsynched_list'][$i]['email'];
						$ins_patient_array['patient_immunisation_id']      = $data['unsynched_list'][$i]['contact_id'];
						$ins_patient_array['staff_id']          = $data['unsynched_list'][$i]['staff_id'];
						$ins_patient_array['synch_out']         = $data['unsynched_list'][$i]['synch_out'];
						$ins_patient_array['synch_in']          = $data['now_id'];
						$ins_patient_data       =   $this->mehr_wdb->insert_new_patient($ins_patient_array);
                        //Log patient_id's
                        if(isset($data['entities_inserted'])){
                            $data['entities_inserted'] = $data['entities_inserted'].",";
                        } else {
                            $data['entities_inserted'] = "";
                        }
                        $data['entities_inserted']  =   $data['entities_inserted'].$ins_patient_array['patient_id'];
                        
                        $data['count_inserted'] = $data['count_inserted'] + 1;
					} else {
						//$data['unsynched_list'][$i]['final']	=	"FALSE";
						//echo "FALSE";
					} //endif($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id'])
				} //endfor ($j=1; $j <= $data['total_selected']; $j++)
				$i++;
			} // endforeach ($xml->patient_info as $item)
            
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
            $ins_log_array['count_inserted']    = $data['count_inserted'];
            $ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
            $ins_log_array['count_rejected']    = $data['count_rejected'];
            $ins_log_array['entities_inserted'] = $data['entities_inserted'];
            //$ins_log_array['entities_declined'] = $data['entities_declined'];
            //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
            //$ins_log_array['declined_list']     = $data['declined_list'];
            //$ins_log_array['rejected_list']     = $data['rejected_list'];
            $ins_log_array['outcome_remarks']   = "No problems encountered.";
            $ins_log_array['sync_type']         = "Manual EDI - Patients Data";
            $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

            // Display conclusion
            echo "\n<br /><br />Count_inserted    = ".$data['count_inserted'];
            echo "\n<br />Count_declined    = ".($data['num_rows'] - $data['count_inserted']);
            echo "\n<br />Count_rejected    = ".$data['count_rejected'];
			echo form_open('ehr_admin/admin_mgt');
			//echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
			echo "<br />Done. <input type='submit' value='Click to Continue' />";
			echo "</form>";
			
		} //endif(count($_POST))
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
            $new_body   =   "ehr/ehr_admin_import_new_patientsdone_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		/*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		*/
    } // end of function admin_import_new_patientsdone($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES


    // ------------------------------------------------------------------------
    function admin_import_episodes($id=NULL)  
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Import Episodes";
		$data['form_purpose']       = 	"new_export";
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_consult";
		$data['directory'] = $data['import_path'];
		// get directory contents as an array
		$data['fileList'] = scandir($data['directory']) or die ("Not a directory");
		// print file names and sizes
		//$data['unsynched_list'] =	array('0' => array('filename' => 'patient_demo.xml','export_date' => '2010-01-20'));
		//$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients();
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_patients(TRUE);
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
            $new_body   =   "ehr/ehr_admin_import_episodes_html";
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
    } // end of function admin_import_episodes($id)


    // ------------------------------------------------------------------------
    function admin_import_new_episodes($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		// User has posted the form
		$data['filename']   = $this->uri->segment(3);
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_consult";
		$data['directory'] = $data['import_path'];
		$xml_file			= $data['directory']."/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
        $data['thirra_version']     =   $xml->export_info->thirra_version;
        $data['export_clinicname']  =   $xml->export_info->export_clinicname;
        $data['export_clinicref']   =   $xml->export_info->export_clinicref;
        $data['export_clinicid']    =   $xml->export_info->export_clinicid;
        $data['export_reference']   =   $xml->export_info->export_reference;
        $data['export_username']    =   $xml->export_info->export_username;
		$i	=	1;
		foreach ($xml->clinical_episode as $item) {
			$data['unsynched_list'][$i]['patient_id']	=	$item->patient_info->patient_id;
			$data['unsynched_list'][$i]['patient_name']	=	$item->patient_info->patient_name;
			$data['unsynched_list'][$i]['name_first']	=	$item->patient_info->name_first;
			$data['unsynched_list'][$i]['summary_id']	=	$item->episode_info->summary_id;
			$data['unsynched_list'][$i]['date_started']	=	$item->episode_info->date_started;
			$data['unsynched_list'][$i]['time_started']	=	$item->episode_info->time_started;
			$data['unsynched_list'][$i]['synch_out']	=	(int)$item->episode_info->synch_out;
			$i++;
		} // endforeach ($xml->patient_info as $item)
		$data['title'] = "Import New Episodes";
        $data['now_id']             =   time();
		$data['imported_before'] = $this->madmin_rdb->get_synch_logs("Import",NULL,$data['filename']);
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
            $new_body   =   "ehr/ehr_admin_import_new_episodes_html";
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
    } // end of function admin_import_new_episodes($id)


    // ------------------------------------------------------------------------
    function admin_import_new_episodesdone($id=NULL)  // template for new classes
    {
		$this->load->model('mehr_wdb');
		$this->load->model('mconsult_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Imported New Episodes";
        $data['now_id']             =   time();
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_consult";
		$data['directory'] = $data['import_path'];
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
            $data['import_reference']   =   $_POST['reference'];
            $data['import_remarks']     =   $_POST['remarks'];
			
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
			$data['filename']   = $this->uri->segment(3);
			$xml_file			= $data['directory']."/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
            $data['thirra_version']     =   $xml->export_info->thirra_version;
            $data['export_clinicname']  =   $xml->export_info->export_clinicname;
            $data['export_clinicref']   =   $xml->export_info->export_clinicref;
            $data['export_clinicid']    =   $xml->export_info->export_clinicid;
            $data['export_reference']   =   $xml->export_info->export_reference;
            $data['export_username']    =   $xml->export_info->export_username;
            $data['export_when']        =   $xml->export_info->export_when;
            $data['count_inserted']     =   0;
            $data['count_rejected']     =   0;
			foreach ($xml->clinical_episode as $item) {
				$data['unsynched_list'][$i]['patient_id']	=	$item->patient_info->patient_id;
				$data['unsynched_list'][$i]['patient_name']	=	$item->patient_info->patient_name;
				$data['unsynched_list'][$i]['name_first']	=	$item->patient_info->name_first;
				$data['unsynched_list'][$i]['summary_id']	=	$item->episode_info->summary_id;
				$data['unsynched_list'][$i]['final']		=	"FALSE"; // Initialise

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
                        // Display to user_error
                        echo "\n<br />Patient : ".$data['unsynched_list'][$i]['patient_name'];
                        echo "\n<br />Session : ".$data['unsynched_list'][$i]['date_ended'];
						// Write to DB
						$ins_episode_array   =   array();
						$ins_episode_array['staff_id']              =   $data['unsynched_list'][$i]['staff_id'];
						$ins_episode_array['adt_id']                =   $data['unsynched_list'][$i]['adt_id'];
						$ins_episode_array['location_id']           =   $data['unsynched_list'][$i]['location_id'];
						$ins_episode_array['summary_id']            =   $data['unsynched_list'][$i]['summary_id'];
						$ins_episode_array['session_type']          =   $data['unsynched_list'][$i]['session_type'];
						$ins_episode_array['patient_id']            =   $data['unsynched_list'][$i]['patient_id'];
						$ins_episode_array['date_started']          =   $data['unsynched_list'][$i]['date_started']	; // session start date
						$ins_episode_array['time_started']          =   $data['unsynched_list'][$i]['time_started'];
						$ins_episode_array['date_ended']            =   $data['unsynched_list'][$i]['date_ended'];
						$ins_episode_array['time_ended']            =   $data['unsynched_list'][$i]['time_ended'];
						$ins_episode_array['signed_by']             =   $data['unsynched_list'][$i]['signed_by'];
						$ins_episode_array['check_in_date']         =   $data['unsynched_list'][$i]['check_in_date'];
						$ins_episode_array['check_in_time']         =   $data['unsynched_list'][$i]['check_in_time'];
						//$ins_episode_array['location_id']           =   $data['init_location_id'];
						$ins_episode_array['location_start']        =   $data['unsynched_list'][$i]['location_start'];
						$ins_episode_array['location_end']          =   $data['unsynched_list'][$i]['location_end'];
						$ins_episode_array['summary']               =   $data['unsynched_list'][$i]['episode_summary'];
						$ins_episode_array['start_date']            =   $ins_episode_array['date_started']; // ambiguous
						$ins_episode_array['session_id']            =   $data['now_id'];
						$ins_episode_array['status']                =   $data['unsynched_list'][$i]['episode_status'];
						$ins_episode_array['remarks']               =   $data['unsynched_list'][$i]['episode_remarks'];
						$ins_episode_array['now_id']                =   $data['now_id'];
						$ins_episode_array['synch_start']           = $data['unsynched_list'][$i]['synch_start'];
						$ins_episode_array['synch_in']              = $data['now_id'];
						$ins_episode_array['synch_out']             = $data['unsynched_list'][$i]['synch_out'];
						$ins_episode_data       =   $this->mconsult_wdb->insert_new_episode($ins_episode_array);
						
                        //Log session_id's
                        if(isset($data['entities_inserted'])){
                            $data['entities_inserted'] = $data['entities_inserted'].",";
                        } else {
                            $data['entities_inserted'] = "";
                        }
                        $data['entities_inserted']  =   $data['entities_inserted'].$ins_episode_array['summary_id'];
                        
						// Complaints segment
						if($data['unsynched_list'][$i]['count_complaints'] > 0){
                            echo "\n<br />Importing patient complaints";
                            if($data['debug_mode']){
                                echo "<br />i = ".$i;
                            } // endif($data['debug_mode'])
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
								$ins_complaint_array['synch_in']          = $data['now_id'];
								$ins_complaint_array['synch_out']          = $ins_episode_array['synch_out'];// Which sync_out?
								$ins_complaint_data       =   $this->mconsult_wdb->insert_new_complaint($ins_complaint_array,$data['offline_mode']);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_complaints'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['complaints_info']";
								print_r($data['unsynched_list'][$i]['complaints_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_complaints'] > 0)

						// Vitals segment
						if($data['unsynched_list'][$i]['count_vitals'] > 0){
                            echo "\n<br />Importing vital signs";
                            if($data['debug_mode']){
                                echo "<br />i = ".$i;
                            } // endif($data['debug_mode'])
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
								$ins_vitals_array['synch_in']         = $data['now_id'];
								$ins_vitals_array['synch_out']         = $ins_episode_array['synch_out'];
								$ins_vitals_data       =   $this->mconsult_wdb->insert_new_vitals($ins_vitals_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_vitals'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['vitals_info']";
								print_r($data['unsynched_list'][$i]['vitals_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_vitals'] > 0)
						
						// Lab Orders segment
						if($data['unsynched_list'][$i]['count_lab'] > 0){
                            echo "\n<br />Importing lab orders";
                            if($data['debug_mode']){
                                echo "<br />i = ".$i;
                            } // endif($data['debug_mode'])
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
								$ins_lab_array['synch_in']       = $data['now_id'];
								$ins_lab_array['synch_out']       = $ins_episode_array['synch_out'];
								$ins_lab_data  =   $this->mconsult_wdb->insert_new_lab_order($ins_lab_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_diagnosis'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['lab_info']";
								print_r($data['unsynched_list'][$i]['lab_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_lab'] > 0)
						
						// Imaging Orders segment
						if($data['unsynched_list'][$i]['count_imaging'] > 0){
                            echo "\n<br />Importing imaging orders";
                            if($data['debug_mode']){
                                echo "<br />i = ".$i;
                            } // endif($data['debug_mode'])
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
								$ins_imaging_array['synch_in']       = $data['now_id'];
								$ins_imaging_array['synch_out']       = $ins_episode_array['synch_out'];
								$ins_imaging_data  =   $this->mconsult_wdb->insert_new_imaging_order($ins_imaging_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_imaging'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['imaging_info']";
								print_r($data['unsynched_list'][$i]['imaging_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_imaging'] > 0)
						
						// Diagnosis segment
						if($data['unsynched_list'][$i]['count_diagnosis'] > 0){
                            echo "\n<br />Importing diagnoses";
                            if($data['debug_mode']){
                                echo "<br />i = ".$i;
                            } // endif($data['debug_mode'])
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
								$ins_diagnosis_array['synch_in']          = $data['now_id'];
								$ins_diagnosis_array['synch_out']          = $ins_episode_array['synch_out'];
								$ins_diagnosis_data       =   $this->mconsult_wdb->insert_new_diagnosis($ins_diagnosis_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_diagnosis'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['diagnosis_info']";
								print_r($data['unsynched_list'][$i]['diagnosis_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_diagnosis'] > 0)
						
						// Prescription segment
						if($data['unsynched_list'][$i]['count_prescribe'] > 0){
                            echo "\n<br />Importing prescriptions";
                            if($data['debug_mode']){
                                echo "<br />i = ".$i;
                            } // endif($data['debug_mode'])
							$k = $i-1; // Since i starts with 1 and not 0
							for($l=0; $l <= ($data['unsynched_list'][$i]['count_prescribe'] - 1); $l++){
								$data['unsynched_list'][$i]['prescribe_info'][$l]['recno']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->recno;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['queue_id']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->queue_id;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['drug_formulary_id']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->drug_formulary_id;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['drug_code_id']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->drug_code_id;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['dose']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->dose;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['dose_form']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->dose_form;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['frequency']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->frequency;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['instruction']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->instruction;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['dose_duration']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->dose_duration;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['quantity']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->quantity;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['quantity_form']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->quantity_form;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['indication']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->indication;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['caution']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->caution;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['status']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->status;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['formulary_code']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->formulary_code;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['generic_drugname']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->generic_name;
								$data['unsynched_list'][$i]['prescribe_info'][$l]['drug_tradename']	=	(string)$xml->clinical_episode[$k]->prescribe_info[$l]->drug_tradename;
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
								$ins_prescribe_array['drug_code_id']    = $data['unsynched_list'][$i]['prescribe_info'][$l]['drug_code_id'];
								$ins_prescribe_array['dose']             = $data['unsynched_list'][$i]['prescribe_info'][$l]['dose'];
								$ins_prescribe_array['dose_form']        = $data['unsynched_list'][$i]['prescribe_info'][$l]['dose_form'];
								$ins_prescribe_array['frequency']        = $data['unsynched_list'][$i]['prescribe_info'][$l]['frequency'];
								$ins_prescribe_array['instruction']      = $data['unsynched_list'][$i]['prescribe_info'][$l]['instruction'];
                                if(is_numeric($data['unsynched_list'][$i]['prescribe_info'][$l]['dose_duration'])){
                                    $ins_prescribe_array['dose_duration']= $data['unsynched_list'][$i]['prescribe_info'][$l]['dose_duration'];
                                }
								//$ins_prescribe_array['dose_duration']         = $data['unsynched_list'][$i]['prescribe_info'][$l]['dose_duration'];
								$ins_prescribe_array['quantity']         = $data['unsynched_list'][$i]['prescribe_info'][$l]['quantity'];
								$ins_prescribe_array['quantity_form']    = $data['unsynched_list'][$i]['prescribe_info'][$l]['quantity_form'];
								$ins_prescribe_array['indication']       = $data['unsynched_list'][$i]['prescribe_info'][$l]['indication'];
								$ins_prescribe_array['caution']          = $data['unsynched_list'][$i]['prescribe_info'][$l]['caution'];
								$ins_prescribe_array['status']           = $data['unsynched_list'][$i]['prescribe_info'][$l]['status'];
								$ins_prescribe_array['synch_in']        = $data['now_id'];
								$ins_prescribe_array['synch_out']        = $ins_episode_array['synch_out'];
								$ins_prescribe_array['status']           = $data['unsynched_list'][$i]['prescribe_info'][$l]['status'];
								$ins_prescribe_array['generic_drugname']           = $data['unsynched_list'][$i]['prescribe_info'][$l]['generic_drugname'];
								$ins_prescribe_array['drug_tradename']           = $data['unsynched_list'][$i]['prescribe_info'][$l]['drug_tradename'];
								$ins_prescribe_data       =   $this->mconsult_wdb->insert_new_prescribe($ins_prescribe_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_diagnosis'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['prescribe_info']";
								print_r($data['unsynched_list'][$i]['prescribe_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_prescribe'] > 0)
						
						// Referral segment
						if($data['unsynched_list'][$i]['count_referrals'] > 0){
                            echo "\n<br />Importing referrals";
                            if($data['debug_mode']){
                                echo "<br />i = ".$i;
                            } // endif($data['debug_mode'])
							$k = $i-1; // Since i starts with 1 and not 0
							//foreach ($xml->clinical_episode->complaints_info as $complaint) {
							for($l=0; $l <= ($data['unsynched_list'][$i]['count_referrals'] - 1); $l++){
								$data['unsynched_list'][$i]['referral_info'][$l]['recno']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->recno;
								$data['unsynched_list'][$i]['referral_info'][$l]['referral_id']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->referral_id;
								$data['unsynched_list'][$i]['referral_info'][$l]['referral_doctor_id']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->referral_doctor_id;
								$data['unsynched_list'][$i]['referral_info'][$l]['referral_doctor_name']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->referral_doctor_name;
								$data['unsynched_list'][$i]['referral_info'][$l]['referral_specialty']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->referral_specialty;
								$data['unsynched_list'][$i]['referral_info'][$l]['referral_centre']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->referral_centre;
								$data['unsynched_list'][$i]['referral_info'][$l]['referral_date']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->referral_date;
								$data['unsynched_list'][$i]['referral_info'][$l]['reason']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->reason;
								$data['unsynched_list'][$i]['referral_info'][$l]['clinical_exam']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->clinical_exam;
								$data['unsynched_list'][$i]['referral_info'][$l]['history_attached']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->history_attached;
								$data['unsynched_list'][$i]['referral_info'][$l]['referral_sequence']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->referral_sequence;
								$data['unsynched_list'][$i]['referral_info'][$l]['referral_reference']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->referral_reference;
								$data['unsynched_list'][$i]['referral_info'][$l]['date_replied']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->date_replied;
								$data['unsynched_list'][$i]['referral_info'][$l]['replying_doctor']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->replying_doctor;
								$data['unsynched_list'][$i]['referral_info'][$l]['replying_specialty']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->replying_specialty;
								$data['unsynched_list'][$i]['referral_info'][$l]['replying_centre']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->replying_centre;
								$data['unsynched_list'][$i]['referral_info'][$l]['department']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->department;
								$data['unsynched_list'][$i]['referral_info'][$l]['findings']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->findings;
								$data['unsynched_list'][$i]['referral_info'][$l]['investigation']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->investigation;
								$data['unsynched_list'][$i]['referral_info'][$l]['diagnosis']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->diagnosis;
								$data['unsynched_list'][$i]['referral_info'][$l]['treatment']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->treatment;
								$data['unsynched_list'][$i]['referral_info'][$l]['plan']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->plan;
								$data['unsynched_list'][$i]['referral_info'][$l]['comments']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->comments;
								$data['unsynched_list'][$i]['referral_info'][$l]['reply_recorder']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->reply_recorder;
								$data['unsynched_list'][$i]['referral_info'][$l]['date_recorded']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->date_recorded;
								$data['unsynched_list'][$i]['referral_info'][$l]['synch_out']	=	(string)$xml->clinical_episode[$k]->referral_info[$l]->synch_out;
								//$k++;
								// New referral record
								$ins_referral_array   =   array();
								$ins_referral_array['referral_id']      = $data['unsynched_list'][$i]['referral_info'][$l]['referral_id'];
								$ins_referral_array['patient_id']       = $ins_episode_array['patient_id'];
								$ins_referral_array['session_id']       = $ins_episode_array['summary_id'];
								$ins_referral_array['referral_doctor_id']= $data['unsynched_list'][$i]['referral_info'][$l]['referral_doctor_id'];
								$ins_referral_array['referral_doctor_name']= $data['unsynched_list'][$i]['referral_info'][$l]['referral_doctor_name'];
								$ins_referral_array['referral_specialty']= $data['unsynched_list'][$i]['referral_info'][$l]['referral_specialty'];
								$ins_referral_array['referral_centre']  = $data['unsynched_list'][$i]['referral_info'][$l]['referral_centre'];
								$ins_referral_array['referral_date']    = $data['unsynched_list'][$i]['referral_info'][$l]['referral_date'];
								$ins_referral_array['reason']           = $data['unsynched_list'][$i]['referral_info'][$l]['reason'];
								$ins_referral_array['clinical_exam']    = $data['unsynched_list'][$i]['referral_info'][$l]['clinical_exam'];
								$ins_referral_array['history_attached'] = $data['unsynched_list'][$i]['referral_info'][$l]['history_attached'];
								$ins_referral_array['referral_sequence']= $data['unsynched_list'][$i]['referral_info'][$l]['referral_sequence'];
								$ins_referral_array['referral_reference']= $data['unsynched_list'][$i]['referral_info'][$l]['referral_reference'];
                                if(!empty($data['unsynched_list'][$i]['referral_info'][$l]['date_replied'])){
                                    $ins_referral_array['date_replied']     = $data['unsynched_list'][$i]['referral_info'][$l]['date_replied'];
                                }
								$ins_referral_array['replying_doctor']  = $data['unsynched_list'][$i]['referral_info'][$l]['replying_doctor'];
								$ins_referral_array['replying_specialty']= $data['unsynched_list'][$i]['referral_info'][$l]['replying_specialty'];
								$ins_referral_array['replying_centre']  = $data['unsynched_list'][$i]['referral_info'][$l]['replying_centre'];
								$ins_referral_array['department']       = $data['unsynched_list'][$i]['referral_info'][$l]['department'];
								$ins_referral_array['findings']         = $data['unsynched_list'][$i]['referral_info'][$l]['findings'];
								$ins_referral_array['investigation']    = $data['unsynched_list'][$i]['referral_info'][$l]['investigation'];
								$ins_referral_array['diagnosis']        = $data['unsynched_list'][$i]['referral_info'][$l]['diagnosis'];
								$ins_referral_array['treatment']        = $data['unsynched_list'][$i]['referral_info'][$l]['treatment'];
								$ins_referral_array['plan']             = $data['unsynched_list'][$i]['referral_info'][$l]['plan'];
								$ins_referral_array['comments']         = $data['unsynched_list'][$i]['referral_info'][$l]['comments'];
								$ins_referral_array['reply_recorder']   = $data['unsynched_list'][$i]['referral_info'][$l]['reply_recorder'];
                                if(!empty($data['unsynched_list'][$i]['referral_info'][$l]['date_recorded'])){
                                    $ins_referral_array['date_recorded']    = $data['unsynched_list'][$i]['referral_info'][$l]['date_recorded'];
                                }
								$ins_referral_array['synch_in']         = $data['now_id'];
								$ins_referral_array['synch_out']        = $ins_episode_array['synch_out'];
								$ins_referral_data       =   $this->mconsult_wdb->insert_new_referral($ins_referral_array);
							} //endfor($l=0; $l <= ($data['unsynched_list'][$i]['count_diagnosis'] - 1); $l++)
							if($data['debug_mode']) {
								echo "<pre>['referral_info']";
								print_r($data['unsynched_list'][$i]['referral_info']);
								echo "</pre>";
							}
						} //endif($data['unsynched_list'][$i]['count_referrals'] > 0)
						
                        $data['count_inserted'] = $data['count_inserted'] + 1;
                        } else {
						//$data['unsynched_list'][$i]['final']	=	"FALSE";
                        if($data['debug_mode']) {
                            echo "FALSE";
                        }
					} //endif($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id'])
				} //endfor ($j=1; $j <= $data['total_selected']; $j++)
				$i++;
			} // endforeach ($xml->patient_info as $item)
            
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
            $ins_log_array['count_inserted']    = $data['count_inserted'];
            $ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
            $ins_log_array['count_rejected']    = $data['count_rejected'];
            $ins_log_array['entities_inserted'] = $data['entities_inserted'];
            //$ins_log_array['entities_declined'] = $data['entities_declined'];
            //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
            //$ins_log_array['declined_list']     = $data['declined_list'];
            //$ins_log_array['rejected_list']     = $data['rejected_list'];
            $ins_log_array['outcome_remarks']   = "No problems encountered.";
            $ins_log_array['sync_type']         = "Manual EDI - Episodes Data";
            $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

            // Display conclusion
            echo "\n<br /><br />Count_inserted    = ".$data['count_inserted'];
            echo "\n<br />Count_declined    = ".($data['num_rows'] - $data['count_inserted']);
            echo "\n<br />Count_rejected    = ".$data['count_rejected'];
			echo form_open('ehr_admin/admin_mgt');
			//echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
			echo "<br />Done. <input type='submit' value='Click to Continue' />";
			echo "</form>";
			
		} //endif(count($_POST))
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
            $new_body   =   "ehr/ehr_admin_import_new_patientsdone_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		/*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		*/
    } // end of function admin_import_new_episodesdone($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES


    // ------------------------------------------------------------------------
    function admin_import_antenatal_info($id=NULL)  
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Import Antenatal Info";
		$data['form_purpose']       = 	"new_import";
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
		// get directory contents as an array
		$data['fileList'] = scandir($data['directory']) or die ("Not a directory");
		// print file names and sizes
		//$data['unsynched_list'] =	array('0' => array('filename' => 'patient_demo.xml','export_date' => '2010-01-20'));
		//$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients();
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
            $new_body   =   "ehr/ehr_admin_import_antenatalinfo_html";
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
    } // end of function admin_import_antenatal_info($id)


    // ------------------------------------------------------------------------
    function admin_import_new_antenatalinfo($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		// User has posted the form
		$data['filename']   = $this->uri->segment(3);
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
		$xml_file			= $data['directory']."/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
        $data['thirra_version']     =   $xml->export_info->thirra_version;
        $data['export_clinicname']  =   $xml->export_info->export_clinicname;
        $data['export_clinicref']   =   $xml->export_info->export_clinicref;
        $data['export_clinicid']    =   $xml->export_info->export_clinicid;
        $data['export_reference']   =   $xml->export_info->export_reference;
        $data['export_username']    =   $xml->export_info->export_username;
		$i	=	1;
		foreach ($xml->antenatal_event as $item) {
			$data['unsynched_list'][$i]['patient_id']	=	$item->patient_info->patient_id;
			$data['unsynched_list'][$i]['patient_name']	=	$item->patient_info->patient_name;
			$data['unsynched_list'][$i]['name_first']	=	$item->patient_info->name_first;
			$data['unsynched_list'][$i]['event_id']	=	$item->event_info->event_id;
			$data['unsynched_list'][$i]['gravida']	=	$item->event_info->gravida;
			$data['unsynched_list'][$i]['lmp']	=	$item->event_info->lmp;
			$data['unsynched_list'][$i]['lmp_edd']	=	$item->event_info->lmp_edd;
			$data['unsynched_list'][$i]['synch_out']	=	(int)$item->event_info->synch_out;
			$i++;
		} // endforeach ($xml->patient_info as $item)
		$data['title'] = "Import New Antenatal Info";
        $data['now_id']             =   time();
		$data['imported_before'] = $this->madmin_rdb->get_synch_logs("Import",NULL,$data['filename']);
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
            $new_body   =   "ehr/ehr_admin_import_new_antenatalinfo_html";
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
    } // end of function admin_import_new_antenatalinfo($id)


    // ------------------------------------------------------------------------
    function admin_import_new_antenatalinfodone($id=NULL)  // template for new classes
    {
		$this->load->model('mantenatal_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Imported New Patients";
        $data['now_id']             =   time();
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']           = $_POST['num_rows'];
            $data['import_reference']   =   $_POST['reference'];
            $data['import_remarks']     =   $_POST['remarks'];
			
			// Retrieve what user selected
			$selected		=	0;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$selected++;
					//$data['selected_list'][$selected]['number']	= $i;
					$data['selected_list'][$selected]['event_id']	= $_POST['s'.$i];
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
			$data['total_selected'] = $selected;
			// Retrieve all records from XML file
			$data['filename']   = $this->uri->segment(3);
			$xml_file			= $data['directory']."/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
            $data['thirra_version']     =   $xml->export_info->thirra_version;
            $data['export_clinicname']  =   $xml->export_info->export_clinicname;
            $data['export_clinicref']   =   $xml->export_info->export_clinicref;
            $data['export_clinicid']    =   $xml->export_info->export_clinicid;
            $data['export_reference']   =   $xml->export_info->export_reference;
            $data['export_username']    =   $xml->export_info->export_username;
            $data['export_when']        =   $xml->export_info->export_when;
            $data['count_inserted']     =   0;
            $data['count_declined']     =   0;
            $data['count_rejected']     =   0;
			foreach ($xml->antenatal_event as $item) {
				$data['unsynched_list'][$i]['patient_id']	        =	(string)$item->patient_info->patient_id;
				$data['unsynched_list'][$i]['event_id']	            =	(string)$item->event_info->event_id;
				$data['unsynched_list'][$i]['event_tabletop']	    =	$item->event_info->event_tabletop;
				$data['unsynched_list'][$i]['event_key']	        =	$item->event_info->event_key;
				$data['unsynched_list'][$i]['event_name']	        =	$item->event_info->event_name;
				$data['unsynched_list'][$i]['location_id']	        =	(string)$item->event_info->location_id;
				$data['unsynched_list'][$i]['staff_id']	            =	(string)$item->event_info->staff_id;
				$data['unsynched_list'][$i]['event_description']    =	(string)$item->event_info->event_description;
				$data['unsynched_list'][$i]['event_remarks']	    =	$item->event_info->event_remarks;
				$data['unsynched_list'][$i]['antenatal_id']	        =	(string)$item->event_info->antenatal_id;
				$data['unsynched_list'][$i]['session_id']	        =	(string)$item->event_info->session_id;
				$data['unsynched_list'][$i]['antenatal_status']	    =	$item->event_info->antenatal_status;
				$data['unsynched_list'][$i]['antenatal_reference']	=	(string)$item->event_info->antenatal_reference;
				$data['unsynched_list'][$i]['antenatal_current_id']	=	(string)$item->event_info->antenatal_current_id;
				$data['unsynched_list'][$i]['midwife_name']	        =	(string)$item->event_info->midwife_name;
				$data['unsynched_list'][$i]['pregnancy_duration']	=	(string)$item->event_info->pregnancy_duration;
				$data['unsynched_list'][$i]['lmp']	                =	(string)$item->event_info->lmp;
				$data['unsynched_list'][$i]['planned_place']	    =	(string)$item->event_info->planned_place;
				$data['unsynched_list'][$i]['menstrual_cycle_length']=	$item->event_info->menstrual_cycle_length;
				$data['unsynched_list'][$i]['lmp_edd']	            =	(string)$item->event_info->lmp_edd;
				$data['unsynched_list'][$i]['lmp_gestation']	    =	(string)$item->event_info->lmp_gestation;
				$data['unsynched_list'][$i]['usscan_date']	        =	(string)$item->event_info->usscan_date;
				$data['unsynched_list'][$i]['usscan_edd']	        =	(string)$item->event_info->usscan_edd;
				$data['unsynched_list'][$i]['usscan_gestation']	    =	(string)$item->event_info->usscan_gestation;
				$data['unsynched_list'][$i]['antenatal_info_id']    =	(string)$item->event_info->antenatal_info_id;
				$data['unsynched_list'][$i]['record_date']	        =	(string)$item->event_info->record_date;
				$data['unsynched_list'][$i]['husband_name']	        =	(string)$item->event_info->husband_name;
				$data['unsynched_list'][$i]['husband_job']	        =	(string)$item->event_info->husband_job;
				$data['unsynched_list'][$i]['husband_dob']	        =	(string)$item->event_info->husband_dob;
				$data['unsynched_list'][$i]['husband_ic_no']	    =	(string)$item->event_info->husband_ic_no;
				$data['unsynched_list'][$i]['gravida']	            =	$item->event_info->gravida;
				$data['unsynched_list'][$i]['para']	                =	$item->event_info->para;
				$data['unsynched_list'][$i]['method_contraception']	=	(string)$item->event_info->method_contraception;
				$data['unsynched_list'][$i]['abortion']	            =	(string)$item->event_info->abortion;
				$data['unsynched_list'][$i]['past_obstretical_history_icpc']=	(string)$item->event_info->past_obstretical_history_icpc;
				$data['unsynched_list'][$i]['past_obstretical_history_notes']=	(string)$item->event_info->past_obstretical_history_notes;
				$data['unsynched_list'][$i]['num_term_deliveries']	=	(string)$item->event_info->num_term_deliveries;
				$data['unsynched_list'][$i]['num_preterm_deliveries']=	(string)$item->event_info->num_preterm_deliveries;
				$data['unsynched_list'][$i]['num_preg_lessthan_21wk']=	(string)$item->event_info->num_preg_lessthan_21wk;
				$data['unsynched_list'][$i]['num_live_births']	    =	(string)$item->event_info->num_live_births;
				$data['unsynched_list'][$i]['num_caesarean_births']	=	(string)$item->event_info->num_caesarean_births;
				$data['unsynched_list'][$i]['num_miscarriages']	    =	(string)$item->event_info->num_miscarriages;
				$data['unsynched_list'][$i]['three_consec_miscarriages']=	(string)$item->event_info->three_consec_miscarriages;
				$data['unsynched_list'][$i]['num_stillbirths']	    =	(string)$item->event_info->num_stillbirths;
				$data['unsynched_list'][$i]['post_partum_depression']=	(string)$item->event_info->post_partum_depression;
				$data['unsynched_list'][$i]['present_pulmonary_tb']	=	(string)$item->event_info->present_pulmonary_tb;
				$data['unsynched_list'][$i]['present_heart_disease']=	(string)$item->event_info->present_heart_disease;
				$data['unsynched_list'][$i]['present_diabetes']	    =	(string)$item->event_info->present_diabetes;
				$data['unsynched_list'][$i]['present_bronchial_asthma']	=	(string)$item->event_info->present_bronchial_asthma;
				$data['unsynched_list'][$i]['present_goiter']	    =	(string)$item->event_info->present_goiter;
				$data['unsynched_list'][$i]['present_hepatitis_b']	=	(string)$item->event_info->present_hepatitis_b;
				$data['unsynched_list'][$i]['antenatal_remarks']	=	(string)$item->event_info->antenatal_remarks;
				$data['unsynched_list'][$i]['contact_person']	    =	(string)$item->event_info->contact_person;
				$data['unsynched_list'][$i]['synch_out']	        =	(string)$item->event_info->synch_out;
				$data['unsynched_list'][$i]['synch_remarks']	    =	$item->event_info->synch_remarks;
				$data['unsynched_list'][$i]['final']		        =	"FALSE"; // Initialise

				// Compare array against selected list and Flag as selected
				for ($j=1; $j <= $data['total_selected']; $j++) {
					if($data['debug_mode']){
						echo "<br />j = ".$j; 
						echo "<br />selected_list = ";
						echo $data['selected_list'][$j]['patient_id'];
						echo "<br />unsynched_list = ";
						echo $data['unsynched_list'][$i]['patient_id'];
					}
					if($data['selected_list'][$j]['event_id'] == $data['unsynched_list'][$i]['event_id']){
						$data['unsynched_list'][$i]['final']	=	"TRUE";
                        // Display to user_error
                        echo "\n<br />Patient : ".(string)$item->patient_info->patient_name;
                        echo ", ".(string)$item->patient_info->name_first;
						// Write to DB
						$ins_antenatal_array   =   array();
						$ins_antenatal_array['staff_id']                = $data['unsynched_list'][$i]['staff_id'];
						$ins_antenatal_array['now_id']                  = $data['now_id'];
						$ins_antenatal_array['antenatal_id']            = (string)$data['unsynched_list'][$i]['antenatal_id'];
						$ins_antenatal_array['event_id']                = (string)$data['unsynched_list'][$i]['event_id'];
						$ins_antenatal_array['patient_id']              = (string)$data['unsynched_list'][$i]['patient_id'];
						$ins_antenatal_array['location_id']             = $data['unsynched_list'][$i]['location_id'];
						$ins_antenatal_array['event_description']       = $data['unsynched_list'][$i]['event_description'];
						$ins_antenatal_array['session_id']              = $data['unsynched_list'][$i]['session_id'];
						$ins_antenatal_array['status']                  = $data['unsynched_list'][$i]['antenatal_status'];
						$ins_antenatal_array['antenatal_reference']     = $data['unsynched_list'][$i]['antenatal_reference'];
						$ins_antenatal_array['record_date']             = $data['unsynched_list'][$i]['record_date'];
						$ins_antenatal_array['antenatal_info_id']       = (string)$data['unsynched_list'][$i]['antenatal_info_id'];
						$ins_antenatal_array['husband_job']             = $data['unsynched_list'][$i]['husband_job'];
						if(strlen($data['unsynched_list'][$i]['husband_dob']) > 0){
							$ins_antenatal_array['husband_dob']         = $data['unsynched_list'][$i]['husband_dob'];
						}
						$ins_antenatal_array['husband_ic_no']           = $data['unsynched_list'][$i]['husband_ic_no'];
						$ins_antenatal_array['gravida']                 = $data['unsynched_list'][$i]['gravida'];
						$ins_antenatal_array['para']                    = $data['unsynched_list'][$i]['para'];
						$ins_antenatal_array['method_contraception']    = $data['unsynched_list'][$i]['method_contraception'];
						$ins_antenatal_array['abortion']                = $data['unsynched_list'][$i]['abortion'];
						$ins_antenatal_array['past_obstretical_history_icpc']= $data['unsynched_list'][$i]['past_obstretical_history_icpc'];
						$ins_antenatal_array['past_obstretical_history_notes'] = $data['unsynched_list'][$i]['past_obstretical_history_notes'];
						$ins_antenatal_array['num_term_deliveries']     = $data['unsynched_list'][$i]['num_term_deliveries'];
						$ins_antenatal_array['num_preterm_deliveries']  = $data['unsynched_list'][$i]['num_preterm_deliveries'];
						$ins_antenatal_array['num_preg_lessthan_21wk']  = $data['unsynched_list'][$i]['num_preg_lessthan_21wk'];
						$ins_antenatal_array['num_live_births']         = $data['unsynched_list'][$i]['num_live_births'];
						$ins_antenatal_array['num_caesarean_births']    = $data['unsynched_list'][$i]['num_caesarean_births'];
						$ins_antenatal_array['num_miscarriages']        = $data['unsynched_list'][$i]['num_miscarriages'];
						if(strlen($data['unsynched_list'][$i]['three_consec_miscarriages']) > 0){
							$ins_antenatal_array['three_consec_miscarriages'] = $data['unsynched_list'][$i]['three_consec_miscarriages'];
						}
						$ins_antenatal_array['num_stillbirths']         = $data['unsynched_list'][$i]['num_stillbirths'];
						$ins_antenatal_array['post_partum_depression']  = $data['unsynched_list'][$i]['post_partum_depression'];
						$ins_antenatal_array['present_pulmonary_tb']    = $data['unsynched_list'][$i]['present_pulmonary_tb'];
						$ins_antenatal_array['present_heart_disease']   = $data['unsynched_list'][$i]['present_heart_disease'];
						$ins_antenatal_array['present_diabetes']        = $data['unsynched_list'][$i]['present_diabetes'];
						$ins_antenatal_array['present_bronchial_asthma']= $data['unsynched_list'][$i]['present_bronchial_asthma'];
						$ins_antenatal_array['present_goiter']          = $data['unsynched_list'][$i]['present_goiter'];
						$ins_antenatal_array['present_hepatitis_b']     = $data['unsynched_list'][$i]['present_hepatitis_b'];
						$ins_antenatal_array['antenatal_current_id']    = (string)$data['unsynched_list'][$i]['antenatal_current_id'];
						$ins_antenatal_array['midwife_name']            = $data['unsynched_list'][$i]['midwife_name'];
						$ins_antenatal_array['pregnancy_duration']      = $data['unsynched_list'][$i]['pregnancy_duration'];
						if(strlen($data['unsynched_list'][$i]['lmp']) > 0){
							$ins_antenatal_array['lmp']                 = $data['unsynched_list'][$i]['lmp'];
						}
						$ins_antenatal_array['planned_place']           = $data['unsynched_list'][$i]['planned_place'];
						$ins_antenatal_array['menstrual_cycle_length']  = (int)$data['unsynched_list'][$i]['menstrual_cycle_length'];
						if(strlen($data['unsynched_list'][$i]['lmp_edd']) > 0){
							$ins_antenatal_array['lmp_edd']             = $data['unsynched_list'][$i]['lmp_edd'];
						}
						$ins_antenatal_array['lmp_gestation']           = $data['unsynched_list'][$i]['lmp_gestation'];
						if(strlen($data['unsynched_list'][$i]['usscan_date']) > 0){
							$ins_antenatal_array['usscan_date']         = $data['unsynched_list'][$i]['usscan_date'];
						}
						if(strlen($data['unsynched_list'][$i]['usscan_edd']) > 0){
							$ins_antenatal_array['usscan_edd']          = $data['unsynched_list'][$i]['usscan_edd'];
						}
						$ins_antenatal_array['usscan_gestation']        = $data['unsynched_list'][$i]['usscan_gestation'];
						$ins_antenatal_array['synch_out']               = $data['unsynched_list'][$i]['synch_out'];
						$ins_antenatal_array['synch_in']                = $data['now_id'];
						$ins_antenatal_data       =   $this->mantenatal_wdb->insert_new_antenatal($ins_antenatal_array);
                        //Log antenatal_id's
                        if(isset($data['entities_inserted'])){
                            $data['entities_inserted'] = $data['entities_inserted'].",";
                        } else {
                            $data['entities_inserted'] = "";
                        }
                        $data['entities_inserted']  =   $data['entities_inserted'].$ins_antenatal_array['antenatal_id'];
                        
                        $data['count_inserted'] = $data['count_inserted'] + 1;
					} else {
						//$data['unsynched_list'][$i]['final']	=	"FALSE";
                        if($data['debug_mode']){
                            echo "FALSE";
                        } //endif($data['debug_mode'])
                            //Log antenatal_id's
                        if(isset($data['entities_declined'])){
                            $data['entities_declined'] = $data['entities_declined'].",";
                        } else {
                            $data['entities_declined'] = "";
                        }
						$ins_antenatal_array['antenatal_id']            = (string)$data['unsynched_list'][$i]['antenatal_id'];
                        $data['entities_declined']  =   $data['entities_declined'].$ins_antenatal_array['antenatal_id'];
                        
                        $data['count_declined'] = $data['count_declined'] + 1;
					} //endif($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id'])
				} //endfor ($j=1; $j <= $data['total_selected']; $j++)
				$i++;
			} // endforeach ($xml->patient_info as $item)
            
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
            $ins_log_array['count_inserted']    = $data['count_inserted'];
            $ins_log_array['count_declined']    = $data['count_declined'];//$data['num_rows'] - $data['count_inserted'];
            $ins_log_array['count_rejected']    = $data['count_rejected'];
            $ins_log_array['entities_inserted'] = $data['entities_inserted'];
            $ins_log_array['entities_declined'] = $data['entities_declined'];
            //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
            //$ins_log_array['declined_list']     = $data['declined_list'];
            //$ins_log_array['rejected_list']     = $data['rejected_list'];
            $ins_log_array['outcome_remarks']   = "No problems encountered.";
            $ins_log_array['sync_type']         = "Manual EDI - Antenatal Info Data";
            $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

            // Display conclusion
            echo "\n<br /><br />Count_inserted    = ".$data['count_inserted'];
            echo "\n<br />Count_declined    = ".($data['num_rows'] - $data['count_inserted']);
            echo "\n<br />Count_rejected    = ".$data['count_rejected'];
			echo form_open('ehr_admin/admin_mgt');
			//echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
			echo "Saved. <input type='submit' value='Click to Continue' />";
			echo "</form>";
			
		} //endif(count($_POST))
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
            $new_body   =   "ehr/ehr_admin_import_new_patientsdone_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		/*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		*/
    } // end of function admin_import_new_antenatalinfodone($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES


    // ------------------------------------------------------------------------
    function admin_import_antenatal_checkup($id=NULL)  
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Import Antenatal Info";
		$data['form_purpose']       = 	"new_import";
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
		// get directory contents as an array
		$data['fileList'] = scandir($data['directory']) or die ("Not a directory");
		// print file names and sizes
		//$data['unsynched_list'] =	array('0' => array('filename' => 'patient_demo.xml','export_date' => '2010-01-20'));
		//$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients();
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
            $new_body   =   "ehr/ehr_admin_import_antenatalcheckup_html";
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
    } // end of function admin_import_antenatal_checkup($id)


    // ------------------------------------------------------------------------
    function admin_import_new_antenatalcheckup($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		// User has posted the form
		$data['filename']   = $this->uri->segment(3);
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
		$xml_file			= $data['directory']."/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
        $data['thirra_version']     =   $xml->export_info->thirra_version;
        $data['export_clinicname']  =   $xml->export_info->export_clinicname;
        $data['export_clinicref']   =   $xml->export_info->export_clinicref;
        $data['export_clinicid']    =   $xml->export_info->export_clinicid;
        $data['export_reference']   =   $xml->export_info->export_reference;
        $data['export_username']    =   $xml->export_info->export_username;
		$i	=	1;
		foreach ($xml->antenatal_checkup as $item) {
			$data['unsynched_list'][$i]['patient_id']	=	$item->patient_info->patient_id;
			$data['unsynched_list'][$i]['patient_name']	=	$item->patient_info->patient_name;
			$data['unsynched_list'][$i]['name_first']	=	$item->patient_info->name_first;
			$data['unsynched_list'][$i]['antenatal_followup_id']	=	$item->checkup_info->antenatal_followup_id;
			$data['unsynched_list'][$i]['event_id']	=	$item->checkup_info->event_id;
			$data['unsynched_list'][$i]['gravida']	=	$item->checkup_info->gravida;
			$data['unsynched_list'][$i]['checkup_date']	=	$item->checkup_info->checkup_date;
			$data['unsynched_list'][$i]['synch_out']	=	(int)$item->checkup_info->synch_out;
			$i++;
		} // endforeach ($xml->patient_info as $item)
		$data['title'] = "Import New Antenatal Checkups";
        $data['now_id']             =   time();
		$data['imported_before'] = $this->madmin_rdb->get_synch_logs("Import",NULL,$data['filename']);
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
            $new_body   =   "ehr/ehr_admin_import_new_antenatalcheckup_html";
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
    } // end of function admin_import_new_antenatalcheckup($id)


    // ------------------------------------------------------------------------
    function admin_import_new_antenatalcheckupdone($id=NULL)  // template for new classes
    {
		$this->load->model('mantenatal_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Imported New Check-ups";
        $data['now_id']             =   time();
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
            $data['import_reference']   =   $_POST['reference'];
            $data['import_remarks']     =   $_POST['remarks'];
			
			// Retrieve what user selected
			$selected		=	0;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$selected++;
					//$data['selected_list'][$selected]['number']	= $i;
					$data['selected_list'][$selected]['antenatal_followup_id']	= $_POST['s'.$i];
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
			$data['total_selected'] = $selected;
			// Retrieve all records from XML file
			$data['filename']   = $this->uri->segment(3);
			$xml_file			= $data['directory']."/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
            $data['thirra_version']     =   $xml->export_info->thirra_version;
            $data['export_clinicname']  =   $xml->export_info->export_clinicname;
            $data['export_clinicref']   =   $xml->export_info->export_clinicref;
            $data['export_clinicid']    =   $xml->export_info->export_clinicid;
            $data['export_reference']   =   $xml->export_info->export_reference;
            $data['export_username']    =   $xml->export_info->export_username;
            $data['export_when']        =   $xml->export_info->export_when;
            $data['count_inserted']     =   0;
            $data['count_rejected']     =   0;
			foreach ($xml->antenatal_checkup as $item) {
				$data['unsynched_list'][$i]['patient_id']	    =	(string)$item->patient_info->patient_id;
				$data['unsynched_list'][$i]['antenatal_followup_id']=	(string)$item->checkup_info->antenatal_followup_id;
				$data['unsynched_list'][$i]['antenatal_id']	    =	(string)$item->checkup_info->antenatal_id;
				$data['unsynched_list'][$i]['record_date']	    =	(string)$item->checkup_info->checkup_date;
				$data['unsynched_list'][$i]['pregnancy_duration']=	(string)$item->checkup_info->pregnancy_duration;
				$data['unsynched_list'][$i]['lie']	            =	(string)$item->checkup_info->lie;
				$data['unsynched_list'][$i]['weight']	        =	(string)$item->checkup_info->weight;
				$data['unsynched_list'][$i]['fundal_height']	=	$item->checkup_info->fundal_height;
				$data['unsynched_list'][$i]['hb']	            =	(string)$item->checkup_info->hb;
				$data['unsynched_list'][$i]['urine_alb']	    =	(string)$item->checkup_info->urine_alb;
				$data['unsynched_list'][$i]['urine_sugar']	    =	(string)$item->checkup_info->urine_sugar;
				$data['unsynched_list'][$i]['ankle_odema']	    =	(string)$item->checkup_info->ankle_odema;
				$data['unsynched_list'][$i]['notes']	        =	(string)$item->checkup_info->notes;
				$data['unsynched_list'][$i]['next_followup']	=	(string)$item->checkup_info->next_followup;
				$data['unsynched_list'][$i]['fundal_height2']	=	$item->checkup_info->fundal_height2;
				$data['unsynched_list'][$i]['session_id']	    =	(string)$item->checkup_info->session_id;
				$data['unsynched_list'][$i]['event_id']	        =	(string)$item->checkup_info->event_id;
				$data['unsynched_list'][$i]['synch_out']        =	(string)$item->checkup_info->synch_out;
				$data['unsynched_list'][$i]['synch_remarks']	=	(string)$item->checkup_info->synch_remarks;
				$data['unsynched_list'][$i]['final']		    =	"FALSE"; // Initialise

				// Compare array against selected list and Flag as selected
				for ($j=1; $j <= $data['total_selected']; $j++) {
					if($data['debug_mode']){
						echo "<br />j = ".$j; 
						echo "<br />selected_list = ";
						echo $data['selected_list'][$j]['patient_id'];
						echo "<br />unsynched_list = ";
						echo $data['unsynched_list'][$i]['patient_id'];
					}
					if($data['selected_list'][$j]['antenatal_followup_id'] == $data['unsynched_list'][$i]['antenatal_followup_id']){
						$data['unsynched_list'][$i]['final']	=	"TRUE";
                        // Display to user_error
                        echo "\n<br />Patient : ".(string)$item->patient_info->patient_name;
                        echo ", ".(string)$item->patient_info->name_first;
                        echo "\n<br />Date : ".$data['unsynched_list'][$i]['record_date'];
						// Write to DB
						$ins_antenatal_array   =   array();
						$ins_antenatal_array['antenatal_followup_id']= $data['unsynched_list'][$i]['antenatal_followup_id'];
						$ins_antenatal_array['antenatal_id']        = $data['unsynched_list'][$i]['antenatal_id'];
						$ins_antenatal_array['record_date']         = $data['unsynched_list'][$i]['record_date'];
						$ins_antenatal_array['pregnancy_duration']  = $data['unsynched_list'][$i]['pregnancy_duration'];
						$ins_antenatal_array['lie']                 = $data['unsynched_list'][$i]['lie'];
						$ins_antenatal_array['weight']              = $data['unsynched_list'][$i]['weight'];
						$ins_antenatal_array['fundal_height']       = (int)$data['unsynched_list'][$i]['fundal_height'];
						$ins_antenatal_array['hb']                  = $data['unsynched_list'][$i]['hb'];
						$ins_antenatal_array['urine_alb']           = $data['unsynched_list'][$i]['urine_alb'];
						$ins_antenatal_array['urine_sugar']         = $data['unsynched_list'][$i]['urine_sugar'];
						$ins_antenatal_array['ankle_odema']         = $data['unsynched_list'][$i]['ankle_odema'];
						$ins_antenatal_array['notes']               = $data['unsynched_list'][$i]['notes'];
						if(strlen($data['unsynched_list'][$i]['next_followup']) > 0){
							$ins_antenatal_array['next_followup']      = $data['unsynched_list'][$i]['next_followup'];
						}
						$ins_antenatal_array['fundal_height2']      = (int)$data['unsynched_list'][$i]['fundal_height2'];
						$ins_antenatal_array['session_id']          = $data['unsynched_list'][$i]['session_id'];
						$ins_antenatal_array['event_id']            = $data['unsynched_list'][$i]['event_id'];
						$ins_antenatal_array['synch_out']           = $data['unsynched_list'][$i]['synch_out'];
						$ins_antenatal_array['synch_in']            = $data['now_id'];
						$ins_antenatal_data       =   $this->mantenatal_wdb->insert_new_antenatal_followup($ins_antenatal_array);
                        
                        //Log antenatal_followup_id's
                        if(isset($data['entities_inserted'])){
                            $data['entities_inserted'] = $data['entities_inserted'].",";
                        } else {
                            $data['entities_inserted'] = "";
                        }
                        $data['entities_inserted']  =   $data['entities_inserted'].$ins_antenatal_array['antenatal_followup_id'];
                        
                        $data['count_inserted'] = $data['count_inserted'] + 1;
					} else {
						//$data['unsynched_list'][$i]['final']	=	"FALSE";
                        if($data['debug_mode']){
                            echo "FALSE";
                        } //endif($data['debug_mode'])
					} //endif($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id'])
				} //endfor ($j=1; $j <= $data['total_selected']; $j++)
				$i++;
			} // endforeach ($xml->patient_info as $item)
            
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
            $ins_log_array['count_inserted']    = $data['count_inserted'];
            $ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
            $ins_log_array['count_rejected']    = $data['count_rejected'];
            $ins_log_array['entities_inserted'] = $data['entities_inserted'];
            //$ins_log_array['entities_declined'] = $data['entities_declined'];
            //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
            //$ins_log_array['declined_list']     = $data['declined_list'];
            //$ins_log_array['rejected_list']     = $data['rejected_list'];
            $ins_log_array['outcome_remarks']   = "No problems encountered.";
            $ins_log_array['sync_type']         = "Manual EDI - Antenatal Checkup Data";
            $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

            // Display conclusion
            echo "\n<br /><br />Count_inserted    = ".$data['count_inserted'];
            echo "\n<br />Count_declined    = ".($data['num_rows'] - $data['count_inserted']);
            echo "\n<br />Count_rejected    = ".$data['count_rejected'];
			echo form_open('ehr_admin/admin_mgt');
			//echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
			echo "Saved. <input type='submit' value='Click to Continue' />";
			echo "</form>";
			
		} //endif(count($_POST))
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
            $new_body   =   "ehr/ehr_admin_import_new_patientsdone_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		/*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		*/
    } // end of function admin_import_new_antenatalcheckupdone($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES


    // ------------------------------------------------------------------------
    function admin_import_antenatal_delivery($id=NULL)  
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Import Antenatal Info";
		$data['form_purpose']       = 	"new_import";
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
		// get directory contents as an array
		$data['fileList'] = scandir($data['directory']) or die ("Not a directory");
		// print file names and sizes
		//$data['unsynched_list'] =	array('0' => array('filename' => 'patient_demo.xml','export_date' => '2010-01-20'));
		//$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients();
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
            $new_body   =   "ehr/ehr_admin_import_antenataldelivery_html";
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
    } // end of function admin_import_antenatal_delivery($id)


    // ------------------------------------------------------------------------
    function admin_import_new_antenataldelivery($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		// User has posted the form
		$data['filename']   = $this->uri->segment(3);
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
		$xml_file			= $data['directory']."/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
        $data['thirra_version']     =   $xml->export_info->thirra_version;
        $data['export_clinicname']  =   $xml->export_info->export_clinicname;
        $data['export_clinicref']   =   $xml->export_info->export_clinicref;
        $data['export_clinicid']    =   $xml->export_info->export_clinicid;
        $data['export_reference']   =   $xml->export_info->export_reference;
        $data['export_username']    =   $xml->export_info->export_username;
		$i	=	1;
		foreach ($xml->antenatal_delivery as $item) {
			$data['unsynched_list'][$i]['patient_id']	=	$item->patient_info->patient_id;
			$data['unsynched_list'][$i]['patient_name']	=	$item->patient_info->patient_name;
			$data['unsynched_list'][$i]['name_first']	=	$item->patient_info->name_first;
			$data['unsynched_list'][$i]['antenatal_delivery_id']	=	$item->delivery_info->antenatal_delivery_id;
			$data['unsynched_list'][$i]['event_id']	=	$item->delivery_info->event_id;
			$data['unsynched_list'][$i]['gravida']	=	$item->delivery_info->gravida;
			$data['unsynched_list'][$i]['para']	=	$item->delivery_info->para;
			$data['unsynched_list'][$i]['date_delivery']	=	$item->delivery_info->date_delivery;
			$data['unsynched_list'][$i]['synch_out']	=	(int)$item->delivery_info->synch_out;
			$i++;
		} // endforeach ($xml->patient_info as $item)
		$data['title'] = "Import New Antenatal Deliveries";
        $data['now_id']             =   time();
		$data['imported_before'] = $this->madmin_rdb->get_synch_logs("Import",NULL,$data['filename']);
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
            $new_body   =   "ehr/ehr_admin_import_new_antenataldelivery_html";
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
    } // end of function admin_import_new_antenataldelivery($id)


    // ------------------------------------------------------------------------
    function admin_import_new_antenataldeliverydone($id=NULL)  // template for new classes
    {
		$this->load->model('mantenatal_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Imported New Deliveries";
        $data['now_id']             =   time();
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_antenatal";
		$data['directory'] = $data['import_path'];
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
            $data['import_reference']   =   $_POST['reference'];
            $data['import_remarks']     =   $_POST['remarks'];
			
			// Retrieve what user selected
			$selected		=	0;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$selected++;
					//$data['selected_list'][$selected]['number']	= $i;
					$data['selected_list'][$selected]['antenatal_delivery_id']	= $_POST['s'.$i];
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
			$data['total_selected'] = $selected;
			// Retrieve all records from XML file
			$data['filename']   = $this->uri->segment(3);
			$xml_file			= $data['directory']."/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
            $data['thirra_version']     =   $xml->export_info->thirra_version;
            $data['export_clinicname']  =   $xml->export_info->export_clinicname;
            $data['export_clinicref']   =   $xml->export_info->export_clinicref;
            $data['export_clinicid']    =   $xml->export_info->export_clinicid;
            $data['export_reference']   =   $xml->export_info->export_reference;
            $data['export_username']    =   $xml->export_info->export_username;
            $data['export_when']        =   $xml->export_info->export_when;
            $data['count_inserted']     =   0;
            $data['count_rejected']     =   0;
			foreach ($xml->antenatal_delivery as $item) {
				$data['unsynched_list'][$i]['patient_id']	    =	(string)$item->patient_info->patient_id;
				$data['unsynched_list'][$i]['antenatal_id']	    =	(string)$item->delivery_info->antenatal_id;
				$data['unsynched_list'][$i]['antenatal_delivery_id']=	(string)$item->delivery_info->antenatal_delivery_id;
				$data['unsynched_list'][$i]['date_admission']   =	(string)$item->delivery_info->date_admission;
				$data['unsynched_list'][$i]['time_admission']   =	(string)$item->delivery_info->time_admission;
				$data['unsynched_list'][$i]['date_delivery']    =	(string)$item->delivery_info->date_delivery;
				$data['unsynched_list'][$i]['time_delivery']    =	(string)$item->delivery_info->time_delivery;
				$data['unsynched_list'][$i]['delivery_type']    =	(string)$item->delivery_info->delivery_type;
				$data['unsynched_list'][$i]['delivery_place']   =	(string)$item->delivery_info->delivery_place;
				$data['unsynched_list'][$i]['mother_condition'] =	(string)$item->delivery_info->mother_condition;
				$data['unsynched_list'][$i]['baby_condition']   =	(string)$item->delivery_info->baby_condition;
				$data['unsynched_list'][$i]['baby_weight']      =	(string)$item->delivery_info->baby_weight;
				$data['unsynched_list'][$i]['complication_icpc']=	(string)$item->delivery_info->complication_icpc;
				$data['unsynched_list'][$i]['complication_notes']=	(string)$item->delivery_info->complication_notes;
				$data['unsynched_list'][$i]['baby_alive']       =	(string)$item->delivery_info->baby_alive;
				$data['unsynched_list'][$i]['birth_attendant']  =	(string)$item->delivery_info->birth_attendant;
				$data['unsynched_list'][$i]['breastfeed_immediate']=	(string)$item->delivery_info->breastfeed_immediate;
				$data['unsynched_list'][$i]['post_partum_bleed']=	(string)$item->delivery_info->post_partum_bleed;
				$data['unsynched_list'][$i]['apgar_score']      =	(string)$item->delivery_info->apgar_score;
				$data['unsynched_list'][$i]['child_id']         =	(string)$item->delivery_info->child_id;
				$data['unsynched_list'][$i]['delivery_remarks'] =	(string)$item->delivery_info->delivery_remarks;
				$data['unsynched_list'][$i]['delivery_outcome'] =	(string)$item->delivery_info->delivery_outcome;
				$data['unsynched_list'][$i]['dcode1ext_code']   =	(string)$item->delivery_info->dcode1ext_code;
				$data['unsynched_list'][$i]['event_id']	        =	(string)$item->delivery_info->event_id;
				$data['unsynched_list'][$i]['synch_out']        =	(string)$item->delivery_info->synch_out;
				$data['unsynched_list'][$i]['synch_remarks']	=	(string)$item->delivery_info->synch_remarks;
				$data['unsynched_list'][$i]['final']		    =	"FALSE"; // Initialise

				// Compare array against selected list and Flag as selected
				for ($j=1; $j <= $data['total_selected']; $j++) {
					if($data['debug_mode']){
						echo "<br />j = ".$j; 
						echo "<br />selected_list = ";
						echo $data['selected_list'][$j]['patient_id'];
						echo "<br />unsynched_list = ";
						echo $data['unsynched_list'][$i]['patient_id'];
					}
					if($data['selected_list'][$j]['antenatal_delivery_id'] == $data['unsynched_list'][$i]['antenatal_delivery_id']){
						$data['unsynched_list'][$i]['final']	=	"TRUE";
                        // Display to user_error
                        echo "\n<br />Patient : ".(string)$item->patient_info->patient_name;
                        echo ", ".(string)$item->patient_info->name_first;
                        echo "\n<br />Date : ".$data['unsynched_list'][$i]['date_delivery'];
						// Write to DB
						$ins_antenatal_array   =   array();
						$ins_antenatal_array['antenatal_delivery_id']= $data['unsynched_list'][$i]['antenatal_delivery_id'];
						$ins_antenatal_array['antenatal_id']        = $data['unsynched_list'][$i]['antenatal_id'];
						if(strlen($data['unsynched_list'][$i]['date_admission']) > 0){
							$ins_antenatal_array['date_admission']      = $data['unsynched_list'][$i]['date_admission'];
						}
						if(strlen($data['unsynched_list'][$i]['time_admission']) > 0){
							$ins_antenatal_array['time_admission']      = $data['unsynched_list'][$i]['time_admission'];
						}
						if(strlen($data['unsynched_list'][$i]['date_delivery']) > 0){
							$ins_antenatal_array['date_delivery']      = $data['unsynched_list'][$i]['date_delivery'];
						}
						if(strlen($data['unsynched_list'][$i]['time_delivery']) > 0){
							$ins_antenatal_array['time_delivery']      = $data['unsynched_list'][$i]['time_delivery'];
						}
						$ins_antenatal_array['delivery_type']       = $data['unsynched_list'][$i]['delivery_type'];
						$ins_antenatal_array['delivery_place']      = $data['unsynched_list'][$i]['delivery_place'];
						$ins_antenatal_array['mother_condition']    = $data['unsynched_list'][$i]['mother_condition'];
						$ins_antenatal_array['baby_condition']      = $data['unsynched_list'][$i]['baby_condition'];
						if(strlen($data['unsynched_list'][$i]['baby_weight']) > 0){
							$ins_antenatal_array['baby_weight']      = $data['unsynched_list'][$i]['baby_weight'];
						}
						if(strlen($data['unsynched_list'][$i]['complication_icpc']) > 0){
							$ins_antenatal_array['complication_icpc']      = $data['unsynched_list'][$i]['complication_icpc'];
						}
						$ins_antenatal_array['complication_notes']  = $data['unsynched_list'][$i]['complication_notes'];
						if(strlen($data['unsynched_list'][$i]['baby_alive']) > 0){
							$ins_antenatal_array['baby_alive']      = $data['unsynched_list'][$i]['baby_alive'];
						}
						$ins_antenatal_array['birth_attendant']     = $data['unsynched_list'][$i]['birth_attendant'];
						$ins_antenatal_array['breastfeed_immediate']= $data['unsynched_list'][$i]['breastfeed_immediate'];
						$ins_antenatal_array['post_partum_bleed']   = $data['unsynched_list'][$i]['post_partum_bleed'];
						if(strlen($data['unsynched_list'][$i]['apgar_score']) > 0){
							$ins_antenatal_array['apgar_score']      = $data['unsynched_list'][$i]['apgar_score'];
						}
						$ins_antenatal_array['event_id']            = $data['unsynched_list'][$i]['event_id'];
						$ins_antenatal_array['child_id']            = $data['unsynched_list'][$i]['child_id'];
						$ins_antenatal_array['delivery_remarks']    = $data['unsynched_list'][$i]['delivery_remarks'];
						$ins_antenatal_array['delivery_outcome']    = $data['unsynched_list'][$i]['delivery_outcome'];
						$ins_antenatal_array['dcode1ext_code']      = $data['unsynched_list'][$i]['dcode1ext_code'];
						$ins_antenatal_array['synch_out']           = $data['unsynched_list'][$i]['synch_out'];
						$ins_antenatal_array['synch_in']            = $data['now_id'];
						$ins_antenatal_data       =   $this->mantenatal_wdb->insert_new_antenatal_delivery($ins_antenatal_array);
                        
                        //Log patient_id's
                        if(isset($data['entities_inserted'])){
                            $data['entities_inserted'] = $data['entities_inserted'].",";
                        } else {
                            $data['entities_inserted'] = "";
                        }
                        $data['entities_inserted']  =   $data['entities_inserted'].$ins_antenatal_array['antenatal_delivery_id'];
                        
                        $data['count_inserted'] = $data['count_inserted'] + 1;
					} else {
						//$data['unsynched_list'][$i]['final']	=	"FALSE";
                        if($data['debug_mode']){
                            echo "FALSE";
                        } //endif($data['debug_mode'])
					} //endif($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id'])
				} //endfor ($j=1; $j <= $data['total_selected']; $j++)
				$i++;
			} // endforeach ($xml->patient_info as $item)
            
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
            $ins_log_array['count_inserted']    = $data['count_inserted'];
            $ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
            $ins_log_array['count_rejected']    = $data['count_rejected'];
            $ins_log_array['entities_inserted'] = $data['entities_inserted'];
            //$ins_log_array['entities_declined'] = $data['entities_declined'];
            //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
            //$ins_log_array['declined_list']     = $data['declined_list'];
            //$ins_log_array['rejected_list']     = $data['rejected_list'];
            $ins_log_array['outcome_remarks']   = "No problems encountered.";
            $ins_log_array['sync_type']         = "Manual EDI - Antenatal Delivery Data";
            $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

            // Display conclusion
            echo "\n<br /><br />Count_inserted    = ".$data['count_inserted'];
            echo "\n<br />Count_declined    = ".($data['num_rows'] - $data['count_inserted']);
            echo "\n<br />Count_rejected    = ".$data['count_rejected'];
			echo form_open('ehr_admin/admin_mgt');
			//echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
			echo "Saved. <input type='submit' value='Click to Continue' />";
			echo "</form>";
			
		} //endif(count($_POST))
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
            $new_body   =   "ehr/ehr_admin_import_new_patientsdone_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		/*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		*/
    } // end of function admin_import_new_antenataldeliverydone($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES


    // ------------------------------------------------------------------------
    function admin_import_history_immunisation($id=NULL)  
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Import Antenatal Info";
		$data['form_purpose']       = 	"new_import";
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_history";
		$data['directory'] = $data['import_path'];
		// get directory contents as an array
		$data['fileList'] = scandir($data['directory']) or die ("Not a directory");
		// print file names and sizes
		//$data['unsynched_list'] =	array('0' => array('filename' => 'patient_demo.xml','export_date' => '2010-01-20'));
		//$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients();
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
            $new_body   =   "ehr/ehr_admin_import_historyimmunisation_html";
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
    } // end of function admin_import_history_immunisation($id)


    // ------------------------------------------------------------------------
    function admin_import_new_historyimmunisation($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		// User has posted the form
		$data['filename']   = $this->uri->segment(3);
		// define directory path
		//$data['directory'] = '/var/www/thirra-uploads/imports_consult';
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_history";
		$data['directory'] = $data['import_path'];
		$xml_file			= $data['directory']."/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
        $data['thirra_version']     =   $xml->export_info->thirra_version;
        $data['export_clinicname']  =   $xml->export_info->export_clinicname;
        $data['export_clinicref']   =   $xml->export_info->export_clinicref;
        $data['export_clinicid']    =   $xml->export_info->export_clinicid;
        $data['export_reference']   =   $xml->export_info->export_reference;
        $data['export_username']    =   $xml->export_info->export_username;
		$i	=	1;
		foreach ($xml->history_immunisation as $item) {
			$data['unsynched_list'][$i]['patient_id']	=	$item->patient_info->patient_id;
			$data['unsynched_list'][$i]['patient_name']	=	$item->patient_info->patient_name;
			$data['unsynched_list'][$i]['name_first']	=	$item->patient_info->name_first;
			$data['unsynched_list'][$i]['patient_immunisation_id']	=	$item->immunisation_info->patient_immunisation_id;
			$data['unsynched_list'][$i]['immunisation_id']	=	$item->immunisation_info->immunisation_id;
			$data['unsynched_list'][$i]['vaccine_short']	=	$item->immunisation_info->vaccine_short;
			$data['unsynched_list'][$i]['immunisation_date']	=	$item->immunisation_info->immunisation_date;
			$data['unsynched_list'][$i]['synch_out']	=	(int)$item->immunisation_info->synch_out;
			$i++;
		} // endforeach ($xml->patient_info as $item)
		$data['title'] = "Import New Immunisation Histories";
        $data['now_id']             =   time();
		$data['imported_before'] = $this->madmin_rdb->get_synch_logs("Import",NULL,$data['filename']);
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
            $new_body   =   "ehr/ehr_admin_import_new_historyimmunisation_html";
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
    } // end of function admin_import_new_historyimmunisation($id)


    // ------------------------------------------------------------------------
    function admin_import_new_historyimmunisationdone($id=NULL)  // template for new classes
    {
		$this->load->model('mconsult_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Imported New Immunisation Histories";
        $data['now_id']             =   time();
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_history";
		$data['directory'] = $data['import_path'];
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['num_rows']           = $_POST['num_rows'];
            $data['import_reference']   =   $_POST['reference'];
            $data['import_remarks']     =   $_POST['remarks'];
			
			// Retrieve what user selected
			$selected		=	0;
			for($i=1; $i<=$data['num_rows']; $i++){
				// Only retrieve if selected by user
				if(isset($_POST['s'.$i])){
					$selected++;
					//$data['selected_list'][$selected]['number']	= $i;
					$data['selected_list'][$selected]['patient_immunisation_id']	= $_POST['s'.$i];
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
			$data['total_selected'] = $selected;
			// Retrieve all records from XML file
			$data['filename']   = $this->uri->segment(3);
			$xml_file			= $data['directory']."/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
            $data['thirra_version']     =   $xml->export_info->thirra_version;
            $data['export_clinicname']  =   $xml->export_info->export_clinicname;
            $data['export_clinicref']   =   $xml->export_info->export_clinicref;
            $data['export_clinicid']    =   $xml->export_info->export_clinicid;
            $data['export_reference']   =   $xml->export_info->export_reference;
            $data['export_username']    =   $xml->export_info->export_username;
            $data['export_when']        =   $xml->export_info->export_when;
            $data['count_inserted']     =   0;
            $data['count_rejected']     =   0;
			foreach ($xml->history_immunisation as $item) {
				$data['unsynched_list'][$i]['patient_id']	    =	(string)$item->patient_info->patient_id;
				$data['unsynched_list'][$i]['patient_immunisation_id']=	(string)$item->immunisation_info->patient_immunisation_id;
				$data['unsynched_list'][$i]['staff_id']	    =	(string)$item->immunisation_info->staff_id;
				$data['unsynched_list'][$i]['record_date']	    =	(string)$item->immunisation_info->checkup_date;
				$data['unsynched_list'][$i]['session_id']=	(string)$item->immunisation_info->session_id;
				$data['unsynched_list'][$i]['immunisation_date']=	(string)$item->immunisation_info->immunisation_date;
				$data['unsynched_list'][$i]['immunisation_id']=	(string)$item->immunisation_info->immunisation_id;
				$data['unsynched_list'][$i]['dispense_queue_id']=	(string)$item->immunisation_info->dispense_queue_id;
				$data['unsynched_list'][$i]['prescript_queue_id']=	(string)$item->immunisation_info->prescript_queue_id;
				$data['unsynched_list'][$i]['notes']=	(string)$item->immunisation_info->notes;
				$data['unsynched_list'][$i]['synch_out']        =	(string)$item->immunisation_info->synch_out;
				$data['unsynched_list'][$i]['synch_remarks']	=	(string)$item->immunisation_info->synch_remarks;
				$data['unsynched_list'][$i]['final']		    =	"FALSE"; // Initialise

				// Compare array against selected list and Flag as selected
				for ($j=1; $j <= $data['total_selected']; $j++) {
					if($data['debug_mode']){
						echo "<br />j = ".$j; 
						echo "<br />selected_list = ";
						echo $data['selected_list'][$j]['patient_id'];
						echo "<br />unsynched_list = ";
						echo $data['unsynched_list'][$i]['patient_id'];
					}
					if($data['selected_list'][$j]['patient_immunisation_id'] == $data['unsynched_list'][$i]['patient_immunisation_id']){
						$data['unsynched_list'][$i]['final']	=	"TRUE";
                        // Display to user_error
                        echo "\n<br />Patient : ".(string)$item->patient_info->patient_name;
                        echo ", ".(string)$item->patient_info->name_first;
                        echo "\n<br />Date : ".$data['unsynched_list'][$i]['immunisation_date'];
						// Write to DB
						$ins_history_array   =   array();
						$ins_history_array['patient_immunisation_id']= $data['unsynched_list'][$i]['patient_immunisation_id'];
						$ins_history_array['patient_id']        = $data['unsynched_list'][$i]['patient_id'];
						$ins_history_array['staff_id']          = $data['unsynched_list'][$i]['staff_id'];
						$ins_history_array['session_id']        = $data['unsynched_list'][$i]['session_id'];
						$ins_history_array['vaccine_date']      = $data['unsynched_list'][$i]['immunisation_date'];
						$ins_history_array['immunisation_id']   = $data['unsynched_list'][$i]['immunisation_id'];
						$ins_history_array['dispense_queue_id'] = $data['unsynched_list'][$i]['dispense_queue_id'];
						$ins_history_array['prescript_queue_id']= $data['unsynched_list'][$i]['prescript_queue_id'];
						$ins_history_array['notes']             = $data['unsynched_list'][$i]['notes'];
						$ins_history_array['synch_out']           = $data['unsynched_list'][$i]['synch_out'];
						$ins_history_array['synch_in']            = $data['now_id'];
						$ins_history_data       =   $this->mconsult_wdb->insert_new_vaccine($ins_history_array);
                        
                        //Log patient_immunisation_id's
                        if(isset($data['entities_inserted'])){
                            $data['entities_inserted'] = $data['entities_inserted'].",";
                        } else {
                            $data['entities_inserted'] = "";
                        }
                        $data['entities_inserted']  =   $data['entities_inserted'].$ins_history_array['patient_immunisation_id'];
                        
                        $data['count_inserted'] = $data['count_inserted'] + 1;
					} else {
						//$data['unsynched_list'][$i]['final']	=	"FALSE";
                        if($data['debug_mode']){
                            echo "FALSE";
                        } //endif($data['debug_mode'])
					} //endif($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id'])
				} //endfor ($j=1; $j <= $data['total_selected']; $j++)
				$i++;
			} // endforeach ($xml->patient_info as $item)
            
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
            $ins_log_array['count_inserted']    = $data['count_inserted'];
            $ins_log_array['count_declined']    = $data['num_rows'] - $data['count_inserted'];
            $ins_log_array['count_rejected']    = $data['count_rejected'];
            $ins_log_array['entities_inserted'] = $data['entities_inserted'];
            //$ins_log_array['entities_declined'] = $data['entities_declined'];
            //$ins_log_array['entities_rejected'] = $data['entities_rejected'];
            //$ins_log_array['declined_list']     = $data['declined_list'];
            //$ins_log_array['rejected_list']     = $data['rejected_list'];
            $ins_log_array['outcome_remarks']   = "No problems encountered.";
            $ins_log_array['sync_type']         = "Manual EDI - Immunisation Data";
            $ins_log_data       =   $this->madmin_wdb->insert_new_synch_log($ins_log_array);

            // Display conclusion
            echo "\n<br /><br />Count_inserted    = ".$data['count_inserted'];
            echo "\n<br />Count_declined    = ".($data['num_rows'] - $data['count_inserted']);
            echo "\n<br />Count_rejected    = ".$data['count_rejected'];
			echo form_open('ehr_admin/admin_mgt');
			//echo "\n<br /><input type='hidden' name='patient_id' value='".$data['init_patient_id']."' size='40' />";
			echo "Saved. <input type='submit' value='Click to Continue' />";
			echo "</form>";
			
		} //endif(count($_POST))
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
            $new_body   =   "ehr/ehr_admin_import_new_patientsdone_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		/*
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		*/
    } // end of function admin_import_new_historyimmunisationdone($id)
	// *** NEED TO MOVE XML FILE FROM CURRENT DIRECTORY TO ARCHIVES


    // ------------------------------------------------------------------------
    function admin_import_refer($id=NULL)  
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['title'] = "Import Episodes";
		$data['form_purpose']       = 	"new_export";
		$data['current_db']			=	$this->db->database; 		
        $data['now_id']             =   time();
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
        $data['import_path']      =    $data['app_path']."-uploads/imports_refer";
        
		$data['directory'] = $data['import_path'];
		// get directory contents as an array
		$data['fileList'] = scandir($data['import_path']) or die ("Not a directory");
		// print file names and sizes
		//$data['unsynched_list'] =	array('0' => array('filename' => 'patient_demo.xml','export_date' => '2010-01-20'));
		//$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients();
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_patients(TRUE);
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
            $new_body   =   "ehr/ehr_admin_import_refer_html";
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
    } // end of function admin_import_refer($id)


    // ------------------------------------------------------------------------
    function admin_import_new_refer($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		// User has posted the form
		$data['filename']   = $this->uri->segment(3);
        
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
        $data['import_path']        =    $data['app_path']."-uploads/imports_refer";
		$xml_file			= $data['import_path']."/".$data['filename'];
		//$xml_file			= "/var/www/thirra-uploads/imports_refer/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
		$i	=	1;
		foreach ($xml->clinical_episode as $item) {
			$data['unsynched_list'][$i]['patient_id']	=	$item->patient_info->patient_id;
			$data['unsynched_list'][$i]['patient_name']	=	$item->patient_info->patient_name;
			$data['unsynched_list'][$i]['name_first']	=	$item->patient_info->name_first;
			$data['unsynched_list'][$i]['summary_id']	=	$item->episode_info->summary_id;
			$data['unsynched_list'][$i]['date_started']	=	$item->episode_info->date_started;
			$data['unsynched_list'][$i]['time_started']	=	$item->episode_info->time_started;
			$data['unsynched_list'][$i]['synch_out']	=	(int)$item->episode_info->synch_out;
			$i++;
		} // endforeach ($xml->patient_info as $item)
		$data['title'] = "Import New Episodes";
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
            $new_body   =   "ehr/ehr_admin_import_new_refer_html";
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
    } // end of function admin_import_new_refer($id)


    // ------------------------------------------------------------------------
    function admin_reset_synchflag($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "T H I R R A - Reset Synch Flags";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['unsynched_logins'] = $this->madmin_rdb->get_unsynched_logins($data['location_id']);
		$data['unsynched_patients'] = $this->madmin_rdb->get_unsynched_patients($data['location_id']);
		$data['unsynched_antenatalinfo'] = $this->madmin_rdb->get_unsynched_antenatalinfo($data['location_id']);
		$data['unsynched_antenatalcheckup'] = $this->madmin_rdb->get_unsynched_antenatalcheckup($data['location_id']);
		$data['unsynched_antenataldelivery'] = $this->madmin_rdb->get_unsynched_antenataldelivery($data['location_id']);
		$data['unsynched_episodes'] = $this->madmin_rdb->get_unsynched_episodes($data['location_id']);
		$data['unsynched_historyimmunisation'] = $this->madmin_rdb->get_unsynched_historyimmunisation($data['location_id']);
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_mgt_wap";
            $new_body   =   "ehr/ehr_admin_reset_synchflags_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_reset_synchflags_html";
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
    } // end of function admin_reset_synchflag($id)


    // ------------------------------------------------------------------------
    function admin_reset_synchflagsdone($id=NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['location_id']        =   $_SESSION['location_id'];
		$data['title'] = "T H I R R A - Reset synch flags";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
		$data['unsynched_logins'] = $this->madmin_rdb->get_unsynched_logins($data['location_id']);
		$data['unsynched_patients'] = $this->madmin_rdb->get_unsynched_patients($data['location_id']);
		$data['unsynched_antenatalinfo'] = $this->madmin_rdb->get_unsynched_antenatalinfo($data['location_id']);
		$data['unsynched_antenatalcheckup'] = $this->madmin_rdb->get_unsynched_antenatalcheckup($data['location_id']);
		$data['unsynched_antenataldelivery'] = $this->madmin_rdb->get_unsynched_antenataldelivery($data['location_id']);
		$data['unsynched_episodes'] = $this->madmin_rdb->get_unsynched_episodes($data['location_id']);
		$data['unsynched_historyimmunisation'] = $this->madmin_rdb->get_unsynched_historyimmunisation($data['location_id']);
        $data['reset_flags']       =   $this->madmin_wdb->reset_synch_flags();
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_emr_wap";
            $new_sidebar=   "ehr/sidebar_emr_admin_wap";
            //$new_body   =   "ehr/ehr_admin_list_referral_centres_wap";
            $new_body   =   "ehr/ehr_admin_reset_synchflagsdone_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_reset_synchflagsdone_html";
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
		
    } // end of function admin_reset_synchflagdone($id)


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
