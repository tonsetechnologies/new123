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
 * Controller Class for EHR_PHARMACY
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_pharmacy extends MY_Controller 
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
		$this->load->model('mpharma_rdb');
		$this->load->model('mpharma_wdb');
		//$this->load->model('memr_wdb');
        
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
    // === PHARMACY MANAGEMENT
    // ------------------------------------------------------------------------
    function pharmacy_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy');    
		$data['title'] = "T H I R R A - Pharmacy Management";

        $data['dispensing_info']  = $this->mpharma_rdb->get_pending_dispensings();
        $data['prescription_info']  = $this->mpharma_rdb->get_pending_prescriptions();
        $this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_pharmacy_wap";
            //$new_body   =   "ehr/ehr_pharmacy_mgt_wap";
            $new_body   =   "ehr/ehr_pharmacy_mgt_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_pharmacy_html";
            $new_body   =   "ehr/ehr_pharmacy_mgt_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_pharmacy'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function pharmacy_mgt($id)


    // ------------------------------------------------------------------------
    function print_prescription($id=NULL)  // Print prescription to HTML or PDF
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	//$this->load->model('memr');
		$data['title'] = "T H I R R A - Prescription";
		$data['now_id']         =   time();
        $data['patient_id']     =   $this->uri->segment(3);
		$data['summary_id']     =   $this->uri->segment(4);
        $data['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id']);
		$data['patient_info']   = $this->memr_rdb->get_patient_demo($data['patient_id']);
        $data['patcon_info']    = $this->memr_rdb->get_patcon_details($data['patient_id'],$data['summary_id']);
		//$data['patient_info']     =   $this->mbio->get_patient_details($data['patient_id']);
		$data['clinic_info']    = $this->mthirra->get_clinic_info($data['patcon_info']['location_end']);
		$broken_age				=	$this->break_date($data['patient_info']['birth_date']);
		$data['est_age']		=	($data['now_id'] - mktime(0, 0, 0, $broken_age['mm'], $broken_age['dd'], $broken_age['yyyy'])) / (60*60*24*365.25);
		
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_print_html";
            $new_sidebar=   "ehr/sidebar_ehr_pharmacy_wap";
            $new_body   =   "ehr/ehr_print_prescription_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_print_html";
            $new_sidebar=   "ehr/sidebar_emr_pharmacy_html";
            $new_body   =   "ehr/ehr_print_prescription_html";
            $new_footer =   "ehr/footer_emr_html";
		}
		
		// Output Format
		$data['output_format'] 	= $this->uri->segment(3);
		$data['filename']		=	"THIRRA-Prescription-".$data['summary_id'].".pdf";
		$this->load->vars($data);
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

			$mpdf->Output($data['filename'],'I'); exit;
		} else { // display in browser
			$this->load->view($new_header);			
			$this->load->view($new_banner);			
			//$this->load->view($new_sidebar);			
			$this->load->view($new_body);			
			$this->load->view($new_footer);		
		} //endif($data['output_format'] == 'pdf')
		
    } // end of function print_prescription($id)


    // ------------------------------------------------------------------------
    function phar_close_prescription($id=NULL) 
    {
		$this->load->model('mconsult_wdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['patient_id']         =   $this->uri->segment(3);
        $data['summary_id']         =   $this->uri->segment(4);
        
        $data['prescribe_list'] = $this->memr_rdb->get_patcon_prescribe($data['summary_id']);
        $m=0; 
        while($m < count($data['prescribe_list'])){
            $upd_prescribe_array   =   array();
            $upd_prescribe_array['queue_id']     = $data['prescribe_list'][$m+1]['queue_id'];
            $data['queue_id']       =   $upd_prescribe_array['queue_id'];
            $upd_prescribe_array['status']       = "Received"; // This is used for compatibility with PCDOM PrimaCare 4
            $upd_prescribe_data =   $this->mconsult_wdb->update_prescription($upd_prescribe_array);
            $m++;
        }//while($l < count($data['prescribe_list']))			
		
        $new_page = base_url()."index.php/ehr_pharmacy/pharmacy_mgt";
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function phar_close_prescription($id)


    // ------------------------------------------------------------------------
    function phar_listclosed_prescriptions($id=NULL)  // List suppliers for labs
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy');    
		$data['supplier_type']   	= $this->uri->segment(3);
		$data['title'] = "T H I R R A - Closed Lab Results";
        $data['prescription_info']  = $this->mpharma_rdb->get_pending_prescriptions('Received');
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_emr_orders_wap";
            //$new_body   =   "ehr/ehr_phar_listclosed_prescriptions_wap";
            $new_body   =   "ehr/ehr_phar_listclosed_prescriptions_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_phar_listclosed_prescriptions_html";
            $new_footer =   "ehr/footer_emr_html";
		}
        if($data['user_rights']['section_pharmacy'] < 100){
            $new_body   =   "ehr/ehr_access_denied_html";
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function phar_listclosed_prescriptions($id)


    // ------------------------------------------------------------------------
    function phar_list_drug_packages($id=NULL)  // List drug packages
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy');    
		$data['title'] = "T H I R R A - List of Drug Packages";
        $data['packages_list']      = $this->mpharma_rdb->get_drug_package_list();
        
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_emr_orders_wap";
            //$new_body   =   "ehr/ehr_orders_list_imagsuppliers_wap";
            $new_body   =   "ehr/ehr_phar_list_drug_packages_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_orders_html";
            $new_body   =   "ehr/ehr_phar_list_drug_packages_html";
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
    } // end of function phar_list_drug_packages($id)


    // ------------------------------------------------------------------------
    // phar_edit_drug_package
    function phar_edit_drug_package()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy','ehr_pharmacy/phar_list_drug_packages','List Drug Packages');    
        $data['form_purpose']       = $this->uri->segment(3);
        $data['drug_package_id']    = $this->uri->segment(4);
	  	
        if(count($_POST)) {
            // User has posted the form
            //$data['drug_package_id']        =   $this->input->post('drug_package_id');
            $data['init_package_name']      =   $this->input->post('package_name');
            $data['init_description']       =   $this->input->post('description');
            $data['init_package_code']      =   $this->input->post('package_code');
            $data['init_package_remarks']   =   $this->input->post('package_remarks');
            $data['init_package_sort']      =   $this->input->post('package_sort');
            $data['init_package_active']    =   $this->input->post('package_active');
            $data['init_location_id']       =   $this->input->post('location_id');
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_package") {
                $data['init_package_name']      =   "";
                $data['init_description']       =   "";
                $data['init_package_code']      =   "";
                $data['init_package_remarks']   =   "";
                $data['init_package_sort']      =   "";
                $data['init_package_active']    =   TRUE;
                $data['init_location_id']       =   $_SESSION['location_id'];
            } elseif ($data['form_purpose'] == "edit_package") {
                //echo "Edit package";
                $data['package_info'] = $this->mpharma_rdb->get_drug_package_list($data['drug_package_id']);
                $data['init_package_name']      = $data['package_info'][0]['package_name'];
                $data['init_description']       = $data['package_info'][0]['description'];
                $data['init_package_code']      = $data['package_info'][0]['package_code'];
                $data['init_package_remarks']   = $data['package_info'][0]['package_remarks'];
                $data['init_package_sort']      = $data['package_info'][0]['package_sort'];
                $data['init_package_active']    = $data['package_info'][0]['package_active'];
                $data['init_location_id']       = $data['package_info'][0]['location_id'];
            } //endif ($data['form_purpose'] == "new_package")
        } //endif(count($_POST))
        $data['drugs_list']      = $this->mpharma_rdb->get_drug_package_list();
		$data['title'] = "Add/Edit Drug Package";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
 		$data['clinics_list'] = $this->mthirra->get_clinics_list('All');
        $data['contents_list']  = $this->mpharma_rdb->get_drug_package_contents($data['drug_package_id']);
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_drug_package') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_emr_admin_wap";
                //$new_body   =   "ehr/emr_admin_edit_referral_person_wap";
                $new_body   =   "ehr/ehr_phar_edit_drug_package_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_phar_edit_drug_package_html";
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
            if($data['form_purpose'] == "new_package") {
                // New package record
                $ins_drug_package   =   array();
                $ins_drug_package['staff_id']       = $_SESSION['staff_id'];
                $ins_drug_package['now_id']         = $data['now_id'];
                $ins_drug_package['drug_package_id']= $data['now_id'];
                $ins_drug_package['package_name']   = $data['init_package_name'];
                $ins_drug_package['description']    = $data['init_description'];
                $ins_drug_package['package_code']   = $data['init_package_code'];
                $ins_drug_package['package_remarks']= $data['init_package_remarks'];
                if(is_numeric($data['init_package_sort'])){
                    $ins_drug_package['package_sort']= $data['init_package_sort'];
                }
                $ins_drug_package['package_active'] = $data['init_package_active'];
                $ins_drug_package['location_id']    = $data['init_location_id'];
	            $drug_package_data       =   $this->mpharma_wdb->insert_new_drug_package($ins_drug_package);
                $this->session->set_flashdata('data_activity', 'Drug package added.');
            } elseif($data['form_purpose'] == "edit_package") {
                // Existing package record
                $upd_drug_package   =   array();
                $upd_drug_package['staff_id']       = $_SESSION['staff_id'];
                $upd_drug_package['now_id']         = $data['now_id'];
                $upd_drug_package['drug_package_id']= $data['drug_package_id'];
                $upd_drug_package['package_name']   = $data['init_package_name'];
                $upd_drug_package['description']    = $data['init_description'];
                $upd_drug_package['package_code']   = $data['init_package_code'];
                $upd_drug_package['package_remarks']= $data['init_package_remarks'];
                if(is_numeric($data['init_package_sort'])){
                    $upd_drug_package['package_sort']= $data['init_package_sort'];
                }
                $upd_drug_package['package_active'] = $data['init_package_active'];
                $upd_drug_package['location_id']    = $data['init_location_id'];
	            $drug_package_data       =   $this->mpharma_wdb->update_drug_package($upd_drug_package);
                $this->session->set_flashdata('data_activity', 'Drug package updated.');
            } //endif($data['diagnosis_id'] == "new_package")
            $new_page = base_url()."index.php/ehr_pharmacy/phar_list_drug_packages";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_referral_centre') == FALSE)


    } // end of function phar_edit_drug_package()


    // ------------------------------------------------------------------------
    // phar_edit package contents
    function phar_edit_package_drug()
    {
		$this->load->model('mutil_rdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['form_purpose']       = $this->uri->segment(3);
        $data['content_id']         = $this->uri->segment(4);
        $data['drug_package_id']    = $this->uri->segment(5);
        $crumbs3                    =   'ehr_pharmacy/phar_edit_drug_package/edit_package/'.$data['drug_package_id'];
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy','ehr_pharmacy/phar_list_drug_packages','List Drug Packages',$crumbs3,'Edit Drug Package');    
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['init_drug_formulary_id'] =   $this->input->post('drug_formulary_id');
            //$data['init_atc_code']          =   $this->input->post('atc_code');
            $data['init_dose']              =   $this->input->post('dose');
            $data['init_dose_form']         =   $this->input->post('dose_form');
            $data['init_frequency']         =   $this->input->post('frequency');
            $data['init_instruction']       =   $this->input->post('instruction');
            $data['init_instruction_other'] =   $this->input->post('instruction_other');
            $data['init_quantity']          =   $this->input->post('quantity');
            $data['init_quantity_form']     =   $this->input->post('quantity_form');
            $data['init_indication']        =   $this->input->post('indication');
            $data['init_caution']           =   $this->input->post('caution');
            $data['init_drug_code_id']      =   $this->input->post('drug_code_id');
            $data['init_drug_remarks']      =   $this->input->post('drug_remarks');
            $data['init_dose_duration']     =   $this->input->post('dose_duration');
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_drug") {
                $data['init_atc_code']          =   "";
                $data['init_dose']              =   "";
                $data['init_dose_form']         =   "";
                $data['init_frequency']         =   "";
                $data['init_instruction']       =   "";
                $data['init_quantity']          =   "";
                $data['init_quantity_form']     =   "";
                $data['init_indication']        =   "";
                $data['init_caution']           =   "";
                $data['init_drug_formulary_id'] =   "";
                $data['init_drug_code_id']      =   "";
                $data['init_drug_remarks']      =   "";
                $data['init_dose_duration']     =   "";
            } elseif ($data['form_purpose'] == "edit_drug") {
                //echo "Edit drug";
                $data['content_info']  = $this->mpharma_rdb->get_drug_package_contents($data['drug_package_id'],$data['content_id']);
                $data['init_drug_formulary_id']      = $data['content_info'][0]['drug_formulary_id'];
                $data['init_atc_code']      = $data['content_info'][0]['atc_code'];
                $data['init_dose']      = $data['content_info'][0]['dose'];
                $data['init_dose_form']      = $data['content_info'][0]['dose_form'];
                $data['init_frequency']      = $data['content_info'][0]['frequency'];
                $data['init_instruction']      = $data['content_info'][0]['instruction'];
                $data['init_quantity']      = $data['content_info'][0]['quantity'];
                $data['init_quantity_form']      = $data['content_info'][0]['quantity_form'];
                $data['init_indication']      = $data['content_info'][0]['indication'];
                $data['init_caution']      = $data['content_info'][0]['caution'];
                $data['init_drug_code_id']      = $data['content_info'][0]['drug_code_id'];
                $data['init_drug_remarks']      = $data['content_info'][0]['drug_remarks'];
                $data['init_dose_duration']      = $data['content_info'][0]['dose_duration'];
            } //endif ($data['form_purpose'] == "new_drug")
        } //endif(count($_POST))
        $data['package_info']   = $this->mpharma_rdb->get_drug_package_list($data['drug_package_id']);
        $data['contents_list']  = $this->mpharma_rdb->get_drug_package_contents($data['drug_package_id']);
        $data['drugs_list']     = $this->mpharma_rdb->get_drug_package_list();
		$data['title']          = "Add/Edit Drug to Package";
        $data['now_id']         =   time();
        $data['now_date']       =   date("Y-m-d",$data['now_id']);
		$data['formulary_list'] = $this->mpharma_rdb->get_formulary_by_system("All");
		$data['dose_forms']     = $this->mutil_rdb->get_package_forms();
		$data['dose_frequency'] = $this->mutil_rdb->get_drug_frequency();
        if(isset($data['init_drug_formulary_id'])){
		    $data['tradename_list'] = $this->mpharma_rdb->get_tradename_by_formulary($data['init_drug_formulary_id']);
            $data['formulary_chosen'] = $this->mpharma_rdb->get_one_drug_formulary($data['init_drug_formulary_id']);
            if(count($data['formulary_chosen']) > 0){
                $data['init_atc_code']    = $data['formulary_chosen']['atc_code'];
            }
        } else {
            $data['tradename_list'] = array();
        } //endif(isset($data['drug_formulary_id']))
        
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_prescribe') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_emr_admin_wap";
                //$new_body   =   "ehr/emr_admin_edit_referral_person_wap";
                $new_body   =   "ehr/ehr_phar_edit_package_drug_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_admin_html";
                $new_body   =   "ehr/ehr_phar_edit_package_drug_html";
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
            if($data['form_purpose'] == "new_drug") {
                // New package record
                $ins_drug_array   =   array();
                $ins_drug_array['content_id']       = $data['now_id'];
                $ins_drug_array['drug_package_id']  = $data['drug_package_id'];
                $ins_drug_array['drug_formulary_id']= $data['init_drug_formulary_id'];
                $ins_drug_array['atc_code']         = $data['init_atc_code'];
                $ins_drug_array['dose']             = $data['init_dose'];
                $ins_drug_array['dose_form']        = $data['init_dose_form'];
                $ins_drug_array['frequency']        = $data['init_frequency'];
                $ins_drug_array['instruction']      = $data['init_instruction'];
                $ins_drug_array['quantity']         = $data['init_quantity'];
                $ins_drug_array['quantity_form']    = $data['init_dose_form'];
                $ins_drug_array['indication']       = $data['init_indication'];
                $ins_drug_array['caution']          = $data['init_caution'];
                $ins_drug_array['drug_code_id']     = $data['init_drug_code_id'];
                $ins_drug_array['drug_remarks']     = $data['init_drug_remarks'];
                if(is_numeric($data['init_dose_duration'])){
                    $ins_drug_array['dose_duration']= $data['init_dose_duration'];
                }
	            $drug_drug_data       =   $this->mpharma_wdb->insert_new_package_drug($ins_drug_array);
                $this->session->set_flashdata('data_activity', 'Package content added.');
            } elseif($data['form_purpose'] == "edit_drug") {
                // Existing package record
                $upd_drug_array   =   array();
                $upd_drug_array['content_id']       = $data['content_id'];
                $upd_drug_array['drug_package_id']  = $data['drug_package_id'];
                $upd_drug_array['drug_formulary_id']= $data['init_drug_formulary_id'];
                $upd_drug_array['atc_code']         = $data['init_atc_code'];
                $upd_drug_array['dose']             = $data['init_dose'];
                $upd_drug_array['dose_form']        = $data['init_dose_form'];
                $upd_drug_array['frequency']        = $data['init_frequency'];
                $upd_drug_array['instruction']      = $data['init_instruction'];
                $upd_drug_array['quantity']         = $data['init_quantity'];
                $upd_drug_array['quantity_form']    = $data['init_dose_form'];
                $upd_drug_array['indication']       = $data['init_indication'];
                $upd_drug_array['caution']          = $data['init_caution'];
                $upd_drug_array['drug_code_id']     = $data['init_drug_code_id'];
                $upd_drug_array['drug_remarks']     = $data['init_drug_remarks'];
                if(is_numeric($data['init_dose_duration'])){
                    $upd_drug_array['dose_duration']= $data['init_dose_duration'];
                }
	            $drug_drug_data       =   $this->mpharma_wdb->update_package_drug($upd_drug_array);
                $this->session->set_flashdata('data_activity', 'Package content updated.');
            } //endif($data['diagnosis_id'] == "new_package")
            $new_page = base_url()."index.php/ehr_pharmacy/phar_edit_drug_package/edit_package/".$data['drug_package_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_referral_centre') == FALSE)


    } // end of function phar_edit_package_drug()


    // ------------------------------------------------------------------------
    function phar_delete_package_drug($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['drug_package_id']    =   $this->uri->segment(3);
        $data['content_id']         =   $this->uri->segment(4);
        
        // Delete records
        $del_rec_array['content_id']      = $data['content_id'];
        $del_rec_data =   $this->mpharma_wdb->phar_delete_packagedrug($del_rec_array);
        $this->session->set_flashdata('data_activity', 'Drug deleted.');
        $new_page = base_url()."index.php/ehr_pharmacy/phar_edit_drug_package/".$data['drug_package_id'];
        header("Status: 200");
        header("Location: ".$new_page);
        
    } // end of function phar_delete_package_drug($id)


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
