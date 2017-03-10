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
 * Controller Class for EHR_ADMIN  - DEPRECATED
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.8
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_admin_edi extends MY_Controller 
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
    function admin_export_patients($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['home_clinic']        =   $_SESSION['location_id'];
		$data['title'] = "Export Patients";
		$data['form_purpose']       = 	"new_export";
        $data['now_id']             =   time();
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_patients($data['home_clinic']);
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_patients($data['home_clinic'],TRUE);
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
		$data['title']              = "Exported New Patients";
        $data['now_id']             =   time();
        $export_when                =   $data['now_id'];
        $export_by                  =   $_SESSION['staff_id'];
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
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_patients>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
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
					$addr_area_id 	= $data['unsynched_list'][$selected]['patient_info']['addr_area_id'];
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
					$xmlstr .= "\n\t<addr_area_id>$addr_area_id</addr_area_id>";
					$xmlstr .= "\n\t<staff_id>$staff_id</staff_id>";
					$xmlstr .= "\n\t<synch_out>$synch_out</synch_out>";
					$xmlstr .= "\n\t<synch_remarks>$synch_remarks</synch_remarks>";
					$xmlstr .= "\n</patient_info>";
					$selected++;
				} //endif(isset($_POST['s'.$i]))
			} //endfor($i=1; $i<=$data['num_rows']; $i++)
		} //endif(count($_POST))
		$data['file_exported']		=	"patient_demo-".date("Ymd_Hi",$data['now_id']).".xml";
		$data['xmlstr']				=	$xmlstr;
		$xmlstr .= "\n</THIRRA_export_patients>";
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

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
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_antenatalinfo($data['location_id']);
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_antenatalinfo($data['location_id'],TRUE);
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
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
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
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_antenatalinfo>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
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
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_antenatalcheckup($data['location_id']);
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_antenatalcheckup($data['location_id'],TRUE);
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
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
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
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_antenatalcheckup>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
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
					$xmlstr .= "\n\t</event_info>";
					
 															
					$xmlstr .= "\n</antenatal_event>";
					$selected++;
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
		$xml = new SimpleXMLElement($xmlstr);

		//echo $xml->asXML();
		$write = $xml->asXML($data['export_path']."/".$data['file_exported']);

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
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_antenataldelivery($data['location_id']);
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_antenataldelivery($data['location_id'],TRUE);
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
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
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
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_antenataldelivery>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
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
                                        
					$synch_out 		    = $data['unsynched_list'][$selected]['delivery_info'][0]['synch_out'];
					$synch_remarks 		= $data['unsynched_list'][$selected]['delivery_info'][0]['synch_remarks'];
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
					$xmlstr .= "\n\t</event_info>";
					
 															
					$xmlstr .= "\n</antenatal_event>";
					$selected++;
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
		$data['unsynched_list'] = $this->madmin_rdb->get_unsynched_episodes($data['location_id']);
		$data['synched_list'] = $this->madmin_rdb->get_unsynched_episodes($data['location_id'],TRUE);
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
        $data['now_id']             =   time();
        $export_by 		            = $_SESSION['staff_id'];
        $export_when                = $data['now_id'];
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
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			$xmlstr = "<?xml version='1.0'?>";
			$xmlstr .= "\n<THIRRA_export_episodes>";
            $xmlstr .= "\n\t<export_info>";
            $xmlstr .= "\n\t\t<export_by>$export_by</export_by>";
            $xmlstr .= "\n\t\t<export_when>$export_when</export_when>";
            $xmlstr .= "\n\t\t<thirra_version>$app_version</thirra_version>";
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
		$xml_file			= "/var/www/thirra-uploads/imports_patient/".$data['filename'];
		$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
		// process node data
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
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			
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
			$xml_file			= "/var/www/thirra-uploads/imports_patient/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
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
					if($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id']){
						$data['unsynched_list'][$i]['final']	=	"TRUE";
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
					} else {
						//$data['unsynched_list'][$i]['final']	=	"FALSE";
						echo "FALSE";
					} //endif($data['selected_list'][$j]['patient_id'] == $data['unsynched_list'][$i]['patient_id'])
				} //endfor ($j=1; $j <= $data['total_selected']; $j++)
				$i++;
			} // endforeach ($xml->patient_info as $item)
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
		$data['title'] = "Exported New Patients";
        $data['now_id']             =   time();
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']   = $_POST['form_purpose'];
            $data['num_rows']       = $_POST['num_rows'];
			
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
			$xml_file			= "/var/www/thirra-uploads/imports_consult/".$data['filename'];
			$xml = simplexml_load_file($xml_file) or die("ERROR: Cannot create SimpleXML object");
			// process node data
			$i	=	1;
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
						$ins_episode_array['synch_in']      = $data['now_id'];
						$ins_episode_array['synch_out']      = $data['unsynched_list'][$i]['synch_out'];
						$ins_episode_data       =   $this->mconsult_wdb->insert_new_episode($ins_episode_array);
						
						// Complaints segment
						if($data['unsynched_list'][$i]['count_complaints'] > 0){
                            echo "\n<br />Importing patient complaints";
							echo "<br />i = ".$i;
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
							echo "<br />i = ".$i;
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
							echo "<br />i = ".$i;
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
							echo "<br />i = ".$i;
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
							echo "<br />i = ".$i;
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
							echo "<br />i = ".$i;
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
								$ins_prescribe_array['synch_in']        = $data['now_id'];
								$ins_prescribe_array['synch_out']        = $ins_episode_array['synch_out'];
								$ins_prescribe_data       =   $this->mconsult_wdb->insert_new_prescribe($ins_prescribe_array);
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
    } // end of function admin_import_new_episodesdone($id)
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
            $new_banner =   "ehr/banner_document_html";
            //$new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_admin_html";
            $new_body   =   "ehr/ehr_admin_import_new_refer_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_admin'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		//$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function admin_import_new_refer($id)


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
