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
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

/**
 * Controller Class for Biosurveillance
 *
 * This class is used for both narrowband and broadband Biosurveillance. 
 *
 * @version 0.8
 * @package THIRRA - Biosurveillance
 * @author  Jason Tan Boon Teck
 */
class Bio_hospital extends MY_Controller 
{
    protected $_patient_id      =  "";
    protected $_debug_mode      =  FALSE;
    //protected $_debug_mode      =  TRUE;


    function __construct()
    {
        parent::__construct();
        
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->scaffolding('patient_demographic_info');
	  	$this->load->model('mpatients');
        //$this->lang->load('emr', 'nepali');
        //$this->lang->load('emr', 'ceylonese');
        //$this->lang->load('emr', 'malay');
        $this->lang->load('bio', 'english');
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->load->model('mbio');
        $this->pretend_phone	=	FALSE;
        //$this->pretend_phone	=	TRUE;  // Turn this On to overwrites actual device
        $data['debug_mode']		=	TRUE;
        if($data['debug_mode'] == TRUE) {
            // spaghetti html
        } else {
            header('Content-type: application/xhtml+xml'); 
        }
        // Redirect back to login page if not authenticated
		if ($_SESSION['user_acl'] < 1){
            $new_page   =   base_url()."index.php/thirra";
            header("Status: 200");
            header("Location: ".$new_page);
        } // redirect to login page
    }

    // ------------------------------------------------------------------------
	function index() // Default page
    {	
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('mpatients');
		$this->load->model('mbio');
        $limit  =   10;
        $offset =   0;

        //---------------- Check user agent
        $this->load->library('user_agent');
        if ($this->agent->is_browser()){
            $data['agent'] = $this->agent->browser().' '.$this->agent->version();
        } elseif ($this->agent->is_robot()) {
            $data['agent'] = $this->agent->robot();
        } elseif ($this->agent->is_mobile()) {
            $data['agent'] = $this->agent->mobile();
        } else {
            $data['agent'] = 'Unidentified User Agent';
        } //endif ($this->agent->is_browser())

	    if ("Unknown Platform" == $this->agent->platform()){
	        $device_mode = "wap";
		    $data['device_mode'] = "WAP";
	    } else {
			if ($this->pretend_phone == TRUE) {
			    $device_mode = "wap";
			    $data['device_mode'] = "WAP"; 
			} else {
		        $device_mode = "html";
			    $data['device_mode'] = "HTML";
			}
	    } //endif ("Unknown Platform" == $this->agent->platform())

		$data['title'] = "T H I R R A - Main Page";
		$data['patlist']    = $this->mpatients->get_all_patients();
		$data['caselist']   = $this->mbio->get_all_cases();
		$data['fresh_list'] = $this->mbio->get_disease_notified_list("fresh",$limit,$offset);
		$data['open_list']  = $this->mbio->get_disease_notified_list("open",$limit,$offset);
		$data['closed_list'] = $this->mbio->get_disease_notified_list("closed",$limit,$offset);
		$data['now_id']     =   time();
		$data['main'] = 'home';
		$data['query'] = $this->db->get('bio_case'); 
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_body   =   "bio/bio_index_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_body   =   "bio/bio_index_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_body   =   "bio/bio_index_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_body   =   "bio/bio_index_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        } //end switch ($_SESSION['thirra_mode'])
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		//$this->load->view('template');
	} //end function index()


    // ------------------------------------------------------------------------
    function print_form544($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	//$this->load->model('memr');
		$data['title'] = "T H I R R A - Form 544";
		$data['now_id']             =   time();
        $data['patient_id']           =   $this->uri->segment(4);
		$data['notification_id']      =   $this->uri->segment(5);
		$data['notify_info']          =   $this->mbio->get_disease_notify_details($data['patient_id'],$data['notification_id']);
		$data['init_notification_id'] =   $data['notification_id'];
		$data['init_summary_id']      =   $data['notify_info']['summary_id'];
		$data['init_visit_date']      =   $data['notify_info']['notify_date'];
		$data['init_started_date']    =   $data['notify_info']['started_date'];
		$data['init_notify_comments'] =   $data['notify_info']['notify_comments'];
		$data['init_notify_ref']      =   $data['notify_info']['notify_ref'];
		$data['init_bht_no']          =   $data['notify_info']['bht_no'];
		$data['init_dcode1ext_code']  =   $data['notify_info']['dcode1ext_code'];
		$data['init_diagnosis_notes'] =   $data['notify_info']['diagnosis_notes'];
		$data['init_location_id']     =   $data['notify_info']['location_id'];
		$data['init_booking_id']      =   $data['notify_info']['booking_id'];
		$data['queue_info'] = $this->mbio->get_patients_queue($data['init_location_id'],"any",$data['init_booking_id']);
		$data['init_room_id']      		= $data['queue_info'][0]['room_id'];                
		$data['room_name']      		= $data['queue_info'][0]['room_name'];                
		$data['patient_info']           =   $this->mbio->get_patient_details($data['patient_id']);
		$broken_age						=	$this->break_date($data['patient_info']['birth_date']);
		$data['est_age']				=	($data['now_id'] - mktime(0, 0, 0, $broken_age['mm'], $broken_age['dd'], $broken_age['yyyy'])) / (60*60*24*365.25);
        $data['lab_list']       = $this->mbio->get_patcon_lab($data['init_summary_id']);
		if(count($data['lab_list'])) {
			$data['lab_package_id']	= $data['lab_list'][0]['lab_package_id'];
			$data['package_code']= $data['lab_list'][0]['package_code'];
			$data['lab_result']= $data['lab_list'][0]['summary_result'];
		} else {
			$data['lab_package_id']	= "none";			
			$data['package_code']= "N/A";
			$data['lab_result']= "N/A";
		}
		
		if ($_SESSION['thirra_mode'] == "bio_mobile"){
			$new_header =   "bio/header_xhtml-mobile10";
			$new_banner =   "bio/banner_bio_wap";
			$new_sidebar=   "bio/sidebar_bio_patients_conslt_wap";
			$new_body   =   "bio/bio_print_form544_wap";
			$new_footer =   "bio/footer_bio_wap";
		} else {
			//$new_header =   "ehr/header_xhtml1-strict";
			$new_header =   "bio/header_xhtml1-transitional";
			$new_banner =   "bio/banner_bio_print_html";
			$new_sidebar=   "bio/sidebar_bio_patients_ovrvw_html";
			$new_body   =   "bio/bio_print_form544_hosp";
			$new_footer =   "bio/footer_bio_html";
		}
		// Output Format
		$data['output_format'] 	= $this->uri->segment(3);
		$data['filename']		=	"THIRRA-Form544-".$data['init_summary_id'].".pdf";
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
		
    } // end of function print_form544($id)


    // ------------------------------------------------------------------------
	function cases_mgt() // Cases Management
    {	
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->model('mpatients');
		$this->load->model('mbio');
        $limit  =   10;
        $offset =   0;

		$data['title'] = "T H I R R A - Cases Management";
		$data['patlist']    = $this->mpatients->get_all_patients();
		$data['caselist']   = $this->mbio->get_all_cases();
		$data['fresh_list'] = $this->mbio->get_disease_notified_list("fresh",$limit,$offset);
		$data['open_list']  = $this->mbio->get_disease_notified_list("open",$limit,$offset);
		$data['closed_list'] = $this->mbio->get_disease_notified_list("closed",$limit,$offset);
		$data['main'] = 'home';
		$data['query'] = $this->db->get('bio_case'); 
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_body   =   "bio/bio_index_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_sidebar=   "bio/sidebar_bio_cases_html";
                $new_body   =   "bio/bio_index_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "bio/sidebar_bio_cases_hosp";
                $new_body   =   "bio/bio_cases_mgt_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_sidebar=   "bio/sidebar_bio_cases_dept";
                $new_body   =   "bio/bio_index_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        } //end switch ($_SESSION['thirra_mode'])
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		//$this->load->view('template');
	} //end function cases_mgt()


    // ------------------------------------------------------------------------
	function cases_fresh() // Cases Management
    {	
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $this->load->library('pagination');
		$this->load->model('mpatients');
		$this->load->model('mbio');
        $limit  =   25;
        $offset  =   $this->uri->segment(3);
        if(!is_numeric($offset)){
            $offset =   0;
        }
        $data['row_first']  =   $offset;
		$data['title'] = "T H I R R A - Fresh Cases";
		$data['total_fresh_list'] = $this->mbio->get_disease_notified_list("fresh","ALL",0);
		$data['fresh_list'] = $this->mbio->get_disease_notified_list("fresh",$limit,$offset);
		//$data['open_list']  = $this->mbio->get_disease_notified_list("open");
		//$data['closed_list'] = $this->mbio->get_disease_notified_list("closed");
		$data['main'] = 'home';
		//$data['query'] = $this->db->get('bio_case'); 

        $config['base_url'] = base_url().'/index.php/bio_hospital/cases_fresh/';
        $config['total_rows'] = count($data['total_fresh_list']);
        $config['per_page'] = $limit; 
        $config['num_links'] = 5;
        $this->pagination->initialize($config); 
        //echo $this->pagination->create_links();      
        
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_body   =   "bio/bio_index_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_sidebar=   "bio/sidebar_bio_cases_html";
                $new_body   =   "bio/bio_index_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "bio/sidebar_bio_cases_hosp";
                $new_body   =   "bio/bio_cases_fresh_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_sidebar=   "bio/sidebar_bio_cases_dept";
                $new_body   =   "bio/bio_index_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        } //end switch ($_SESSION['thirra_mode'])
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		//$this->load->view('template');
	} //end function cases_fresh()


    // ------------------------------------------------------------------------
	function cases_open() // Cases Management
    {	
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $this->load->library('pagination');
		$this->load->model('mpatients');
		$this->load->model('mbio');
        $limit  =   25;
        $offset  =   $this->uri->segment(3);
        if(!is_numeric($offset)){
            $offset =   0;
        }
        $data['row_first']  =   $offset;
		$data['title'] = "T H I R R A - Open Cases";
		//$data['fresh_list'] = $this->mbio->get_disease_notified_list("fresh");
		$data['total_open_list']  = $this->mbio->get_disease_notified_list("open","ALL",0);
		$data['open_list']  = $this->mbio->get_disease_notified_list("open",$limit,$offset);
		//$data['closed_list'] = $this->mbio->get_disease_notified_list("closed");

        $config['base_url'] = base_url().'/index.php/bio_hospital/cases_open/';
        $config['total_rows'] = count($data['total_open_list']);
        $config['per_page'] = $limit; 
        $config['num_links'] = 5;
        $this->pagination->initialize($config); 
        //echo $this->pagination->create_links();      
        
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_body   =   "bio/bio_index_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_sidebar=   "bio/sidebar_bio_cases_html";
                $new_body   =   "bio/bio_index_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "bio/sidebar_bio_cases_hosp";
                $new_body   =   "bio/bio_cases_open_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_sidebar=   "bio/sidebar_bio_cases_dept";
                $new_body   =   "bio/bio_index_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        } //end switch ($_SESSION['thirra_mode'])
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		//$this->load->view('template');
	} //end function cases_open()


    // ------------------------------------------------------------------------
	function cases_closed() // Cases Management
    {	
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $this->load->library('pagination');
		$this->load->model('mpatients');
		$this->load->model('mbio');
        $limit  =   25;
        $offset  =   $this->uri->segment(3);
        if(!is_numeric($offset)){
            $offset =   0;
        }
        $data['row_first']  =   $offset;
		$data['title'] = "T H I R R A - Closed Cases";
		//$data['fresh_list'] = $this->mbio->get_disease_notified_list("fresh");
		//$data['open_list']  = $this->mbio->get_disease_notified_list("open");
		$data['total_closed_list'] = $this->mbio->get_disease_notified_list("closed","ALL",0);
		$data['closed_list'] = $this->mbio->get_disease_notified_list("closed",$limit,$offset);
        
        $config['base_url'] = base_url().'/index.php/bio_hospital/cases_closed/';
        $config['total_rows'] = count($data['total_closed_list']);
        $config['per_page'] = $limit; 
        $config['num_links'] = 5;
        $this->pagination->initialize($config); 
        //echo $this->pagination->create_links();      
        
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_body   =   "bio/bio_index_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_sidebar=   "bio/sidebar_bio_cases_html";
                $new_body   =   "bio/bio_index_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "bio/sidebar_bio_cases_hosp";
                $new_body   =   "bio/bio_cases_closed_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_sidebar=   "bio/sidebar_bio_cases_dept";
                $new_body   =   "bio/bio_index_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        } //end switch ($_SESSION['thirra_mode'])
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
		//$this->load->view('template');
	} //end function cases_closed()


    // ------------------------------------------------------------------------
    function search_new_notify()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	$this->load->model('mbio');
	  	$this->load->model('mpatients');
		$data['title'] = 'Search for Patient for New Notification';
		$data['name_filter'] = $_POST['patient_name'];
		$data['patlist'] = $this->mbio->get_patients_list($data['name_filter']);
		$this->load->vars($data);
		//$this->load->view('bio/bio/header_xhtml1-strict');
		$this->load->view('bio/header_xhtml1-transitional');
		$this->load->view('bio/banner_bio_hosp');
		$this->load->view('bio/bio_search_new_notify_hosp');
		$this->load->view('bio/footer_bio_hosp');
    } //end of function search_new_case()


    // ------------------------------------------------------------------------
    function edit_notify544($patient_id = NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->library('form_validation');
	  	$this->load->model('mbio');
	  	$this->load->model('mpatients');
        $data['location_id']   =   $_SESSION['location_id'];
		$data['form_purpose']   = $this->uri->segment(3);
		$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['diagnosis_list'] = $this->mbio->get_diagnosis_list(TRUE);
		$data['common_diagnosis'] = $this->mbio->get_diagnosis_list(TRUE,TRUE);
		$data['title'] = 'Notification';
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                 =   $_POST['now_id'];
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $data['notification_id']        =   $_POST['notification_id'];
            $data['init_patient_id']        =   $_POST['patient_id'];
            $data['patient_id']             =   $data['init_patient_id'];
            $data['init_notify_date']      	=   $_POST['notify_date'];
            $data['init_visit_date']        =   $_POST['visit_date'];
            $data['init_onset_date']      	=   $_POST['onset_date'];
            $data['init_discharged_date']   =   $_POST['discharged_date'];
            $data['init_notify_comments']   =   $_POST['notify_comments'];
            $data['init_notify_ref']        =   $_POST['notify_ref'];
            $data['init_bht_no']            =   $_POST['bht_no'];
            $data['orig_dcode1ext_code']    =   $_POST['orig_dcode1ext_code'];
            $data['init_dcode1ext_code']    =   $_POST['dcode1ext_code'];
			$data['diagnosis_info']	= $this->mbio->get_one_diagnosis_code($data['init_dcode1ext_code']);
            $data['init_diagnosis_notes']   =   $_POST['diagnosis_notes'];
            $data['init_room_id']           =   $_POST['room_id'];
            $data['init_lab_package_id']    =   $_POST['lab_package_id'];
            $data['init_lab_result']        =   $_POST['lab_result'];
            $data['init_location_id']       =   $_POST['location_id'];
            $data['init_summary_id']        =   $_POST['summary_id'];
            $data['adt_id']        			=   $_POST['adt_id'];
            $data['diagnosis_id']        	=   $_POST['diagnosis_id'];
            
      		$data['save_attempt']           = 'EDIT NEW NOTIFY';
            $data['patient_id']             =   $data['init_patient_id']; //came from POST
	        //$data['patient_info']           =   $this->mbio->get_patient_details($data['patient_id']);

        } else {
            // First time form is displayed
            $data['now_id']             =   time();
            $patient_id                 =   $this->uri->segment(4);
            $data['patient_id']         =   $patient_id;
            $data['init_patient_id']        =   $data['patient_id'];
            //$data['patient_info'] = $this->mbio->get_patient_details($data['patient_id']);
            if($data['form_purpose'] == 'new_notify') {
          		$data['save_attempt'] = 'NEW NOTIFICATION';
                $data['notification_id']     =   "new_notify";   
                $data['init_location_id']    =   $_SESSION['location_id'];
                $data['init_district_id']    =   NULL;
                $data['init_start_date']     =   NULL;
                $data['init_end_date']       =   NULL;
                $data['init_staff_start_id'] =   NULL;
                $data['init_diagnosis_notes']=   NULL;
                $data['init_clinic_name']    =   NULL;
                $data['now_date']            =   date("Y-m-d",$data['now_id']);
                $data['init_summary_id']     =   $data['now_id'];
                $data['init_notify_date']    =   date("Y-m-d",$data['init_summary_id']); // Admission date
                $data['init_visit_date']     =   date("Y-m-d",$data['init_summary_id']); // Admission date
                $data['init_notify_comments']=   NULL;
                $data['init_notify_ref']     =   NULL;
                $data['init_bht_no']         =   NULL;
                $data['init_onset_date']   =   $data['init_visit_date'];
                $data['init_discharged_date']=   NULL;
                $data['init_room_id']        =   NULL;
				$data['init_lab_package_id'] =   NULL;
                $data['init_lab_result']     =   NULL;
            } elseif($data['form_purpose'] == 'edit_notify') {
          		$data['save_attempt']         = 'EDIT OLD NOTIFY';
		        $data['notification_id']      =   $this->uri->segment(5);
		        $data['notify_info']          =   $this->mbio->get_disease_notify_details($data['patient_id'],$data['notification_id']);
                $data['init_notification_id'] =   $data['notification_id'];
                $data['init_summary_id']      =   $data['notify_info']['summary_id'];
                $data['init_notify_date']     =   $data['notify_info']['notify_date'];
                $data['init_visit_date']      =   $data['notify_info']['check_in_date'];
                $data['init_onset_date'] 	  =   $data['notify_info']['started_date'];
                //$data['init_started_date']    =   $data['notify_info']['started_date'];
                $data['init_discharged_date'] =   $data['notify_info']['check_out_date'];
                $data['init_notify_comments'] =   $data['notify_info']['notify_comments'];
                $data['init_notify_ref']      =   $data['notify_info']['notify_ref'];
                $data['init_bht_no']          =   $data['notify_info']['bht_no'];
                $data['init_dcode1ext_code']  =   $data['notify_info']['dcode1ext_code'];
                $data['init_diagnosis_notes'] =   $data['notify_info']['diagnosis_notes'];
                $data['init_location_id']     =   $data['notify_info']['location_id'];
                $data['init_booking_id']      =   $data['notify_info']['booking_id'];
                $data['queue_info'] = $this->mbio->get_patients_queue($data['location_id'],"any",$data['init_booking_id']);
                if(count($data['queue_info']) > 0){
                    $data['init_room_id']      = $data['queue_info'][0]['room_id'];                
                } else {
                    $data['init_room_id']      = NULL;                
                }
            } //endif(data['form_purpose'] == 'new_notify')
        } //endif(count($_POST))

		$data['patient_info']           =   $this->mbio->get_patient_details($data['patient_id']);
		$broken_age						=	$this->break_date($data['patient_info']['birth_date']);
		$data['est_age']				=	($data['now_id'] - mktime(0, 0, 0, $broken_age['mm'], $broken_age['dd'], $broken_age['yyyy'])) / (60*60*24*365.25);
        $data['rooms_list'] 	= $this->mbio->get_rooms_list($data['location_id']);
        $data['lab_list']       = $this->mbio->get_patcon_lab($data['init_summary_id']);
		if(isset($data['init_lab_package_id'])) {
			$data['lab_package_id']	= $data['init_lab_package_id'];			
		} elseif(count($data['lab_list'])) {
			$data['lab_package_id']	= $data['lab_list'][0]['lab_package_id'];
			$data['init_lab_result']= $data['lab_list'][0]['summary_result'];
		} else {
			$data['lab_package_id']	= "none";			
			$data['init_lab_result']= "N/A";
		}
        $data['packages_list']	= $this->mbio->get_lab_packages_list();

		// Run validation
		if ($this->form_validation->run('edit_notify') == FALSE){
            // Resume loop

        } else {
            if($data['debug_mode']) {
                //echo "\nValidated successfully.";
                //echo "<pre>";
                //print_r($data);
                //echo "</pre>";
            } //endif($data['debug_mode'])
            if($data['form_purpose'] == 'new_notify') {
                //echo "<br />Insert record";
                // New patient record
                $data['last_episode']   = $this->mbio->get_last_session_reference();
                $ins_notify_array   =   array();
                $ins_notify_array['now_id']                =   $data['now_id'];
                $ins_notify_array['staff_id']              =   $_SESSION['staff_id'];
                $ins_notify_array['adt_id']                =   $data['now_id'];
                $ins_notify_array['bht_no']                =   $data['init_bht_no'];
                $ins_notify_array['summary_id']            =   $data['now_id'];
                $ins_notify_array['session_ref']           =   $data['last_episode']['max_ref']+1;
                $ins_notify_array['session_type']          =   "1";
                $ins_notify_array['patient_id']            =   $data['init_patient_id'];
                $ins_notify_array['date_started']          =   $data['now_date']; // session start date
                $ins_notify_array['time_started']          =   "12:00:00";
                $ins_notify_array['date_ended']            =   $data['now_date']; // session start date
                $ins_notify_array['time_ended']            =   "12:00:00";
                $ins_notify_array['check_in_date']         =   $data['init_visit_date'];
                $ins_notify_array['check_in_time']         =   "12:00:00";
				if($data['init_discharged_date']){
					$ins_notify_array['check_out_date']        =   $data['init_discharged_date'];
					$ins_notify_array['check_out_time']		   =   "12:00:00";
				}
                $ins_notify_array['location_id']           =   $data['init_location_id'];
                $ins_notify_array['location_start']        =   $data['init_location_id'];
                $ins_notify_array['location_end']          =   $data['init_location_id'];
                $ins_notify_array['start_date']            =   $data['now_date']; // ambiguous
                $ins_notify_array['diagnosis_id']          =   $data['now_id'];
                $ins_notify_array['session_id']            =   $data['now_id'];
                $ins_notify_array['diagnosis_type']        =   "Primary";
                $ins_notify_array['dcode1set']             =   $data['diagnosis_info']['dcode1set'];;
                $ins_notify_array['dcode1ext_code']        =   $data['init_dcode1ext_code'];
                $ins_notify_array['diagnosis_notes']       =   $data['init_diagnosis_notes'];
                $ins_notify_array['medical_history_id']    =   $data['now_id'];
                $ins_notify_array['history_date_accuracy'] =   "DMY";
                $ins_notify_array['history_condition']     =   $data['diagnosis_info']['dcode1ext_longname'];
                $ins_notify_array['history_status']		   =   "Unconfirmed";
                $ins_notify_array['notification_id']       =   $data['now_id'];
                $ins_notify_array['notify_date']           =   $data['init_notify_date'];
                $ins_notify_array['onset_date']            =   $data['init_onset_date']; //disease start date
                $ins_notify_array['notify_comments']       =   $data['init_notify_comments'];
                $ins_notify_array['notify_ref']            =   $data['init_notify_ref'];
                $ins_notify_array['status']                =   1;
                $ins_notify_array['remarks']               =   "THIRRA";
                $ins_notify_array['booking_id']            =   $data['now_id'];
                $ins_notify_array['room_id']               =   $data['init_room_id'];
                $ins_notify_array['booking_type']          =   "Internal";
                $ins_notify_array['booking_status']        =   "Admitted";
				if($data['init_lab_package_id'] && $data['init_lab_result']) {
					$ins_notify_array['has_lab']               =   TRUE;
					$ins_notify_array['lab_order_id']          =   $data['now_id'];
					$ins_notify_array['lab_package_id']        =   $data['init_lab_package_id'];
					$ins_notify_array['summary_result']        =   $data['init_lab_result'];
					$ins_notify_array['sample_date']           =   $data['now_date'];
					$ins_notify_array['sample_time']           =   "12:00:00";
					$ins_notify_array['result_status']         =   "Confirmed";
					$ins_notify_array['invoice_status']        =   "Unknown";
				} else {
					$ins_notify_array['has_lab']               =   FALSE;
				} //endif($data['init_lab_package_id'] && $data['init_lab_result'])
	            $ins_notify_data       =   $this->mbio->insert_disease_notify($ins_notify_array);
          		$data['save_attempt'] = 'NEW NOTIFICATION ADDED SUCCESSFULLY';
            } elseif($data['form_purpose'] == 'edit_notify') {
                echo "<br />Update record";
                $update_array   =   array();
                $update_array['staff_id']               =   $_SESSION['staff_id'];
                $update_array['notification_id']        =   $data['notification_id'];
                $update_array['notify_date']            =   $data['init_visit_date'];
                $update_array['notify_started_date']    =   $data['init_onset_date'];
                $update_array['notify_comments']        =   $data['init_notify_comments'];
                $update_array['notify_ref']             =   $data['init_notify_ref'];
                $update_array['onset_date']             =   $data['init_onset_date']; //disease start date
                $update_array['diagnosis_id']        	=   $data['diagnosis_id'];
				if($data['orig_dcode1ext_code'] <> $data['init_diagnosis_notes']){
					echo "\n<br />Diagnosis CHANGED";
					$update_array['dcode1ext_code_changed'] =	TRUE;
					$update_array['dcode1ext_code']         =   $data['init_dcode1ext_code'];
				}
                $update_array['case_diagnosis_notes']   =   $data['init_diagnosis_notes'];
                $update_array['adt_id']        			=   $data['adt_id'];
				if($data['init_discharged_date']){
					$update_array['discharged_date']        =   $data['init_discharged_date'];
					$update_array['check_out_time']		   =   "12:00:00";
				}
		        $update_data       =   $this->mbio->update_disease_notify($update_array);
          		$data['save_attempt'] = 'NOTIFICATION UPDATED SUCCESSFULLY';
            }
        }
		$this->load->vars($data);
	    //$this->load->view('bio/bio/header_xhtml1-strict');
	    $this->load->view('bio/header_xhtml1-transitional');
	    $this->load->view('bio/banner_bio_hosp');
	    $this->load->view('bio/bio_edit_notify544_hosp');			
		$this->load->view('bio/footer_bio_hosp');
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_notify544()


    // ------------------------------------------------------------------------
    function edit_notify($patient_id = NULL) // DEPRECATED in favour of edit_notify544()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$this->load->library('form_validation');
	  	$this->load->model('mbio');
	  	$this->load->model('mpatients');
        $data['location_id']   =   $_SESSION['location_id'];
		$data['form_purpose']   = $this->uri->segment(3);
		$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['diagnosis_list'] = $this->mbio->get_diagnosis_list(TRUE);
		$data['common_diagnosis'] = $this->mbio->get_diagnosis_list(TRUE,TRUE);
		$data['title'] = 'Notification';
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                 =   $_POST['now_id'];
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $data['notification_id']        =   $_POST['notification_id'];
            $data['init_patient_id']        =   $_POST['patient_id'];
            $data['patient_id']             =   $data['init_patient_id'];
            $data['init_visit_date']        =   $_POST['visit_date'];
            $data['init_started_date']      =   $_POST['started_date'];
            $data['init_notify_comments']   =   $_POST['notify_comments'];
            $data['init_notify_ref']        =   $_POST['notify_ref'];
            $data['init_bht_no']            =   $_POST['bht_no'];
            $data['init_dcode1ext_code']    =   $_POST['dcode1ext_code'];
            $data['init_diagnosis_notes']   =   $_POST['diagnosis_notes'];
            $data['init_location_id']       =   $_POST['location_id'];
            $data['init_summary_id']        =   $_POST['summary_id'];
            

      		$data['save_attempt']           = 'EDIT NEW NOTIFY';
            $data['patient_id']             =   $data['init_patient_id']; //came from POST
	        //$data['patient_info']           =   $this->mbio->get_patient_details($data['patient_id']);

        } else {
            // First time form is displayed
            $data['now_id']             =   time();
            $patient_id                 =   $this->uri->segment(4);
            $data['patient_id']         =   $patient_id;
            $data['init_patient_id']        =   $data['patient_id'];
            //$data['patient_info'] = $this->mbio->get_patient_details($data['patient_id']);
            if($data['form_purpose'] == 'new_notify') {
          		$data['save_attempt'] = 'NEW NOTIFICATION';
                $data['notification_id']     =   "new_notify";   
                $data['init_location_id']    =   $_SESSION['location_id'];
                $data['init_district_id']    =   NULL;
                $data['init_start_date']     =   NULL;
                $data['init_end_date']       =   NULL;
                $data['init_staff_start_id'] =   NULL;
                $data['init_diagnosis_notes']=   NULL;
                $data['init_end_date']       =   NULL;
                $data['init_clinic_name']    =   NULL;
                $data['now_date']            =   date("Y-m-d",$data['now_id']);
                $data['init_summary_id']     =   $data['now_id'];
                $data['init_visit_date']     =   date("Y-m-d",$data['init_summary_id']);
                $data['init_notify_comments']=   NULL;
                $data['init_notify_ref']     =   NULL;
                $data['init_bht_no']         =   NULL;
                $data['init_started_date']   =   $data['init_visit_date'];
            } elseif($data['form_purpose'] == 'edit_notify') {
          		$data['save_attempt']         = 'EDIT OLD NOTIFY';
		        $data['notification_id']      =   $this->uri->segment(5);
		        $data['notify_info']          =   $this->mbio->get_disease_notify_details($data['patient_id'],$data['notification_id']);
                $data['init_notification_id'] =   $data['notification_id'];
                $data['init_summary_id']      =   $data['notify_info']['summary_id'];
                $data['init_visit_date']      =   $data['notify_info']['notify_date'];
                $data['init_started_date']    =   $data['notify_info']['started_date'];
                $data['init_notify_comments'] =   $data['notify_info']['notify_comments'];
                $data['init_notify_ref']      =   $data['notify_info']['notify_ref'];
                $data['init_bht_no']          =   $data['notify_info']['bht_no'];
                $data['init_dcode1ext_code']  =   $data['notify_info']['dcode1ext_code'];
                $data['init_diagnosis_notes'] =   $data['notify_info']['diagnosis_notes'];
                $data['init_location_id']     =   $data['notify_info']['location_id'];
                
            } //endif(data['form_purpose'] == 'new_notify')
        } //endif(count($_POST))

		$data['patient_info']   = $this->mbio->get_patient_details($data['patient_id']);
        $data['rooms_list'] 	= $this->mbio->get_rooms_list($data['location_id']);

		// Run validation
		if ($this->form_validation->run('edit_notify') == FALSE){
            // Resume loop

        } else {
            if($data['debug_mode']) {
                echo "\nValidated successfully.";
                echo "<pre>";
                //print_r($data);
                echo "</pre>";
            } //endif($data['debug_mode'])
            if($data['form_purpose'] == 'new_notify') {
                //echo "<br />Insert record";
                // New patient record
                $data['last_episode']   = $this->mbio->get_last_session_reference();
                $ins_notify_array   =   array();
                $ins_notify_array['staff_id']              =   $_SESSION['staff_id'];
                $ins_notify_array['adt_id']                =   $data['now_id'];
                $ins_notify_array['bht_no']                =   $data['init_bht_no'];
                $ins_notify_array['summary_id']            =   $data['now_id'];
                $ins_notify_array['session_ref']           =   $data['last_episode']['max_ref']+1;
                $ins_notify_array['session_type']          =   "1";
                $ins_notify_array['patient_id']            =   $data['init_patient_id'];
                $ins_notify_array['date_started']          =   $data['now_date']; // session start date
                $ins_notify_array['time_started']          =   "12:00:00";
                $ins_notify_array['check_in_date']         =   $data['now_date'];
                $ins_notify_array['check_in_time']         =   "12:00:00";
                $ins_notify_array['location_id']           =   $data['init_location_id'];
                $ins_notify_array['location_start']        =   $data['init_location_id'];
                $ins_notify_array['location_end']          =   $data['init_location_id'];
                $ins_notify_array['start_date']            =   $data['now_date']; // ambiguous
                $ins_notify_array['diagnosis_id']          =   $data['now_id'];
                $ins_notify_array['session_id']            =   $data['now_id'];
                $ins_notify_array['diagnosis_type']        =   "Primary";
                $ins_notify_array['dcode1set']             =   "ICPC-2ext";
                $ins_notify_array['dcode1ext_code']        =   $data['init_dcode1ext_code'];
                $ins_notify_array['diagnosis_notes']       =   $data['init_diagnosis_notes'];
                $ins_notify_array['notification_id']       =   $data['now_id'];
                $ins_notify_array['notify_date']           =   $data['now_date'];
                $ins_notify_array['started_date']          =   $data['init_started_date']; //disease start date
                $ins_notify_array['notify_comments']       =   $data['init_notify_comments'];
                $ins_notify_array['notify_ref']            =   $data['init_notify_ref'];
                $ins_notify_array['status']                =   1;
                $ins_notify_array['remarks']               =   "THIRRA";
                $ins_notify_array['now_id']                =   $data['now_id'];
	            $ins_notify_data       =   $this->mbio->insert_disease_notify($ins_notify_array);
          		$data['save_attempt'] = 'NEW NOTIFICATION ADDED SUCCESSFULLY';
            } elseif($data['form_purpose'] == 'edit_notify') {
                echo "<br />Update record";
                $update_array   =   array();
                $update_array['notification_id']        =   $data['notification_id'];
                $update_array['case_diagnosis_notes']   =   $data['init_diagnosis_notes'];
                $update_array['notify_date']            =   $data['init_visit_date'];
                $update_array['notify_started_date']    =   $data['init_started_date'];
                $update_array['notify_comments']        =   $data['init_notify_comments'];
                $update_array['notify_ref']             =   $data['init_notify_ref'];
				if($data['orig_dcode1ext_code'] <> $data['init_diagnosis_notes']){
					echo "Diagnosis CHANGED";
				}
		        $update_data       =   $this->mbio->update_disease_notify($update_array);
          		$data['save_attempt'] = 'NOTIFICATION UPDATED SUCCESSFULLY';
            }
        }
		$this->load->vars($data);
	    //$this->load->view('bio/bio/header_xhtml1-strict');
	    $this->load->view('bio/header_xhtml1-transitional');
	    $this->load->view('bio/banner_bio_hosp');
	    $this->load->view('bio/bio_edit_notify_hosp');			
		$this->load->view('bio/footer_bio_hosp');
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_notify()


    // ------------------------------------------------------------------------
    function edit_patient($patient_id = NULL)
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_country']		=	$this->config->item('app_country');
		$this->load->library('form_validation');
	  	$this->load->model('mbio');
		$this->load->model('mutil_rdb');
	  	//$this->load->model('mpatients');
		$data['form_purpose']   = $this->uri->segment(3);
		$data['clinic_info']    = $this->mbio->get_clinic_info($_SESSION['location_id']);
		$data['diagnosis_list'] = $this->mbio->get_diagnosis_list(TRUE);
		$data['title'] = 'Add New / Edit Patient';
		$data['addr_village_list']	=	$this->mutil_rdb->get_addr_village_list($data['app_country']);
        //$this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        if(count($_POST)) {
            // User has posted the form
            $data['now_id']                 =   $this->input->post('now_id');
            $data['now_date']               =   date("Y-m-d",$data['now_id']);
            $data['init_clinic_reference_number']=   $this->input->post('clinic_reference_number');
            //$data['patient_id']             =   $data['init_patient_id'];
            $data['patient_id']             =   $this->input->post('patient_id');
            $data['init_patient_id']        =   $this->input->post('patient_id');
            $data['init_patient_name']      =   $this->input->post('patient_name');
            $data['init_name_first']        =   $this->input->post('name_first');
            $data['init_name_alias']        =   $this->input->post('name_alias');
            $data['init_guardian_name']     =   $this->input->post('guardian_name');
            $data['init_guardian_relation'] =   $this->input->post('guardian_relation');
            $data['init_gender']            =   $this->input->post('gender');
            $data['init_ic_no']             =   $this->input->post('ic_no');
            $data['init_ic_other_no']       =   $this->input->post('ic_other_no');
            $data['init_nationality']       =   $this->input->post('nationality');
            $data['init_birth_date']        =   $this->input->post('birth_date');
            $data['init_ethnicity']         =   $this->input->post('ethnicity');
            $data['init_religion']          =   $this->input->post('religion');
            $data['init_marital_status']    =   $this->input->post('marital_status');
            $data['init_patient_type']      =   $this->input->post('patient_type');
            $data['init_contact_id']        =   $this->input->post('contact_id');
            $data['init_blood_group']       =   $this->input->post('blood_group');
            $data['init_blood_rhesus']      =   $this->input->post('blood_rhesus');
            $data['init_demise_date']       =   $this->input->post('demise_date');
            $data['init_demise_time']       =   $this->input->post('demise_time');
            $data['init_demise_cause']      =   $this->input->post('demise_cause');
            $data['init_patient_address']   =   $this->input->post('patient_address');
            $data['init_patient_address2']  =   $this->input->post('patient_address2');
            $data['init_patient_address3']  =   $this->input->post('patient_address3');
            $data['init_patient_postcode']  =   $this->input->post('patient_postcode');
            $data['init_patient_town']      =   $this->input->post('patient_town');
            $data['init_patient_state']     =   $this->input->post('patient_state');
            $data['init_patient_country']   =   $this->input->post('patient_country');
            $data['init_tel_home']          =   $this->input->post('tel_home');
            $data['init_tel_mobile']        =   $this->input->post('tel_mobile');
            $data['init_tel_office']        =   $this->input->post('tel_office');
            $data['init_email']          	=   $this->input->post('email');
            $data['init_addr_village_id']          	=   $this->input->post('addr_village_id');
            $data['init_location_id']       =   $this->input->post('location_id');
            $data['init_age']		        =   $this->input->post('age');
            $data['init_birth_date_estimate']=   $this->input->post('birth_date_estimate');
            
            if ($data['patient_id'] == "new_patient"){
                // New form
		        //$data['patient_id']         = "";
          		$data['save_attempt']       = 'ADD NEW PATIENT';
		        $data['patient_info']       = array();
            } else {
                // Edit form
          		$data['save_attempt']       = 'EDIT PATIENT';
                // These fields were passed through as hidden tags
                $data['patient_id']         =   $data['init_patient_id']; //came from POST
		        $data['patient_info']       =   $this->mbio->get_patient_details($data['patient_id']);
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

            if ($patient_id == "new_patient") {
                // New patient
	            //$data['patient_id']                 = "";
          		$data['save_attempt']               =   'NEW PATIENT';
	            $data['patient_info']               =   array();
                $data['init_patient_id']            =   "new_patient";
                $data['init_patient_name']          =   NULL;
                $data['init_name_first']            =   NULL;
                $data['init_name_alias']            =   NULL;
                $data['init_guardian_name']  =   NULL;
                $data['init_guardian_relation']=   NULL;
                $data['init_clinic_reference_number']=   NULL;
                $data['init_nationality']           =   "Sri Lanka";
                $data['init_birth_date']            =   NULL;
                $data['init_gender']                =   NULL;
                $data['init_ic_no']                 =   NULL;
                $data['init_ic_other_no']           =   NULL;
                $data['init_ethnicity']             =   NULL;
                $data['init_religion']              =   NULL;
                $data['init_marital_status']        =   NULL;  
                $data['init_patient_type']          =   "Hospital";
                $data['init_contact_id']            =   NULL;
                $data['init_blood_group']           =   NULL;
                $data['init_blood_rhesus']          =   NULL;
                $data['init_demise_date']           =   NULL;
                $data['init_demise_time']           =   NULL;
                $data['init_demise_cause']          =   NULL;
                $data['init_patient_address']       =   NULL;
                $data['init_patient_address2']      =   NULL;
                $data['init_patient_address3']      =   NULL;
                $data['init_patient_postcode']      =   NULL;
                $data['init_patient_town']          =   NULL;
                $data['init_patient_state']         =   NULL;
                $data['init_patient_country']       =   NULL;//"Sri Lanka";
                $data['init_tel_home']              =   NULL;
                $data['init_tel_office']            =   NULL;
                $data['init_tel_mobile']            =   NULL;
                $data['init_email']		            =   NULL;
                $data['init_addr_village_id']       =   NULL;
                $data['init_addr_area_id']          =   NULL;
                $data['init_birth_date_estimate']  	=   NULL;
                $data['init_age']  		            =   NULL;
            } else {
                // Existing patient
	            $data['patient_info'] = $this->mbio->get_patient_details($data['patient_id']);
          		$data['save_attempt'] = 'EDIT PATIENT';
                $data['init_patient_id']        =   $data['patient_id'];
                $data['init_clinic_reference_number']=   $data['patient_info']['clinic_reference_number'];
                $data['init_patient_name']      =   $data['patient_info']['patient_name'];
                $data['init_name_first']        =   $data['patient_info']['name_first'];
                $data['init_name_alias']        =   $data['patient_info']['name_alias'];
                $data['init_guardian_name']     =   $data['patient_info']['guardian_name'];
                $data['init_guardian_relation'] =   $data['patient_info']['guardian_relationship'];
                $data['init_nationality']       =   $data['patient_info']['nationality'];
                $data['init_birth_date']        =   $data['patient_info']['birth_date'];
                $data['init_gender']            =   $data['patient_info']['gender'];
                $data['init_ic_no']             =   $data['patient_info']['ic_no'];
                $data['init_ic_other_no']       =   $data['patient_info']['ic_other_no'];
                $data['init_ethnicity']         =   $data['patient_info']['ethnicity'];
                $data['init_religion']          =   $data['patient_info']['religion'];
                $data['init_marital_status']    =   $data['patient_info']['marital_status'];
                $data['init_patient_type']      =   $data['patient_info']['patient_type'];
                $data['init_contact_id']        =   $data['patient_info']['contact_id'];
                $data['init_blood_group']       =   $data['patient_info']['blood_group'];
                $data['init_blood_rhesus']      =   $data['patient_info']['blood_rhesus'];
                $data['init_demise_date']       =   $data['patient_info']['demise_date'];
                $data['init_demise_time']       =   $data['patient_info']['demise_time'];
                $data['init_demise_cause']      =   $data['patient_info']['demise_cause'];
                $data['init_patient_address']   =   $data['patient_info']['patient_address'];
                $data['init_patient_address2']  =   $data['patient_info']['patient_address2'];
                $data['init_patient_address3']  =   $data['patient_info']['patient_address3'];
                $data['init_patient_postcode']  =   $data['patient_info']['patient_postcode'];
                $data['init_patient_town']      =   $data['patient_info']['patient_town'];
                $data['init_patient_state']     =   $data['patient_info']['patient_state'];
                $data['init_patient_country']   =   $data['patient_info']['patient_country'];
                $data['init_tel_home']          =   $data['patient_info']['tel_home'];
                $data['init_tel_office']        =   $data['patient_info']['tel_office'];
                $data['init_tel_mobile']        =   $data['patient_info']['tel_mobile'];
                $data['init_email']		        =   $data['patient_info']['email'];
                $data['init_addr_village_id']   =   $data['patient_info']['addr_village_id'];
                $data['init_addr_area_id']      =   $data['patient_info']['addr_area_id'];
                $data['init_birth_date_estimate']=   $data['patient_info']['birth_date_estimate'];
                $data['broken_age']	            =   $this->break_date($data['patient_info']['birth_date']);
				$data['birth_seconds']			=	mktime(0,0,0,$data['broken_age']['mm'],$data['broken_age']['dd'],$data['broken_age']['yyyy']);
				$data['init_age']				= 	($data['now_id'] - $data['birth_seconds'])/(60*60*24*365.25);
            } //endif ($patient_id == "new_patient")
        } //endif(count($_POST))
		$data['village_info']	=	$this->mutil_rdb->get_addr_village_list($data['app_country'],$data['init_addr_village_id']);
		//if(isset($data['village_info'][0])){
		if(count($data['village_info']) == 1){
			$data['init_addr_town_id']       = $data['village_info'][0]['addr_town_id'];
			$data['init_addr_area_id']       = $data['village_info'][0]['addr_area_id'];
			$data['init_addr_district_id']   = $data['village_info'][0]['addr_district_id'];
			$data['init_addr_state_id']      = $data['village_info'][0]['addr_state_id'];
			$data['init_patient_town']       = $data['village_info'][0]['addr_town_name'];
			$data['init_patient_area']       = $data['village_info'][0]['addr_area_name'];
			$data['init_patient_district']   = $data['village_info'][0]['addr_district_name'];
			$data['init_patient_state']      = $data['village_info'][0]['addr_district_state'];
			$data['init_patient_country']    = $data['village_info'][0]['addr_district_country'];
        } else {
			$data['init_patient_area']       = "";
			$data['init_patient_district']   = "";
        } //endif(count($data['village_info'] > 0))

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_patient') == FALSE){
		    //$this->load->view('bio/bio/header_xhtml1-strict');
		    $this->load->view('bio/header_xhtml1-transitional');
		    $this->load->view('bio/banner_bio_hosp');
		    $this->load->view('bio/bio_edit_patient_hosp');			
    		$this->load->view('bio/footer_bio_hosp');
        } else {
            if($data['debug_mode']) {
                echo "\nValidated successfully.";
                echo "<pre>";
                //print_r($data);
                echo "</pre>";
                //echo "<br />Insert record";
            } // endif($data['debug_mode'])
            if($data['patient_id'] == "new_patient") {
                // New patient record
                $ins_patient_array   =   array();
                $ins_patient_array['staff_id']           = $_SESSION['staff_id'];
                $ins_patient_array['now_id']             = $data['now_id'];
				if(empty($data['init_birth_date'])){
					$ins_patient_array['birth_date']         = $data['init_birth_date'];
					$est_date = new DateTime($data['now_date']);
					$est_date->modify("-".$data['init_age']." year");
					//echo $est_date->format("Y-m-d");
					$ins_patient_array['birth_date']         = $est_date->format("Y-m-d");
					$ins_patient_array['birth_date_estimate']= (boolean)TRUE;
				} else {
					$ins_patient_array['birth_date']         = $data['init_birth_date'];
				}
				$data['broken_birth_date']      =   $this->break_date($data['init_birth_date']);
				$data['broken_now_date']      	=   $this->break_date($data['now_date']);
                $ins_patient_array['patient_id']         = $data['broken_birth_date']['dd']
                                                            .$data['broken_birth_date']['mm']
                                                            .$data['broken_birth_date']['yyyy']
                                                            .$data['broken_now_date']['dd']
                                                            .$data['broken_now_date']['mm']
                                                            .$data['broken_now_date']['yyyy']
                                                            .$data['now_id'];
                $ins_patient_array['clinic_reference_number']= $data['init_clinic_reference_number'];
                $ins_patient_array['patient_name']       = $data['init_patient_name'];
                $ins_patient_array['name_first']         = $data['init_name_first'];
                $ins_patient_array['name_alias']         = $data['init_name_alias'];
                $ins_patient_array['guardian_name']      = $data['init_guardian_name'];
                $ins_patient_array['guardian_relation']  = $data['init_guardian_relation'];
                $ins_patient_array['ic_no']              = $data['init_ic_no'];
                $ins_patient_array['ic_other_no']        = $data['init_ic_other_no'];
                $ins_patient_array['nationality']        = $data['init_nationality'];
                $ins_patient_array['family_link']        = "Head of Family";
                $ins_patient_array['gender']             = $data['init_gender'];
                $ins_patient_array['ethnicity']          = $data['init_ethnicity'];
                $ins_patient_array['religion']           = $data['init_religion'];
                $ins_patient_array['patient_type']       = $data['init_patient_type'];
                $ins_patient_array['blood_group']        = $data['init_blood_group'];
                $ins_patient_array['blood_rhesus']       = $data['init_blood_rhesus'];
                if($data['init_demise_date']){
                    $ins_patient_array['demise_date']              =   $data['init_demise_date'];
                }
                if($data['init_demise_time']){
                    $ins_patient_array['demise_time']              =   $data['now_date'];
                }
                $ins_patient_array['demise_cause']       = $data['init_demise_cause'];
                $ins_patient_array['clinic_home']        = $data['init_location_id'];
                $ins_patient_array['clinic_registered']  = $data['init_location_id'];
                $ins_patient_array['patient_status']     = 1;
                $ins_patient_array['location_id']        = $data['init_location_id'];
                $ins_patient_array['contact_id']         = $data['now_id'];
                $ins_patient_array['correspondence_id']  = $data['now_id'];
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
                $ins_patient_array['email']           	 = $data['init_email'];
                $ins_patient_array['addr_village_id']    = $data['init_addr_village_id'];
                $ins_patient_array['addr_town_id']       = $data['init_addr_town_id'];
                $ins_patient_array['addr_area_id']       = $data['init_addr_area_id'];
                $ins_patient_array['addr_district_id']   = $data['init_addr_district_id'];
                $ins_patient_array['addr_state_id']      = $data['init_addr_state_id'];
	            $ins_patient_data = $this->mbio->insert_new_patient($ins_patient_array);
            } else {
                // Edit patient record
                $upd_patient_array   =   array();
                $upd_patient_array['staff_id']           = $_SESSION['staff_id'];
                $upd_patient_array['now_id']             = $data['now_id'];
                $upd_patient_array['patient_id']         = $data['patient_id'];
                $upd_patient_array['clinic_reference_number']= $data['init_clinic_reference_number'];
                $upd_patient_array['patient_name']       = $data['init_patient_name'];
                $upd_patient_array['name_first']         = $data['init_name_first'];
                $upd_patient_array['name_alias']         = $data['init_name_alias'];
                $upd_patient_array['guardian_name']      = $data['init_guardian_name'];
                $upd_patient_array['guardian_relation']  = $data['init_guardian_relation'];
                $upd_patient_array['ic_no']              = $data['init_ic_no'];
                $upd_patient_array['ic_other_no']        = $data['init_ic_other_no'];
                $upd_patient_array['nationality']        = $data['init_nationality'];
                $upd_patient_array['birth_date']         = $data['init_birth_date'];
                $upd_patient_array['family_link']        = "Head of Family";
                $upd_patient_array['gender']             = $data['init_gender'];
                $upd_patient_array['ethnicity']          = $data['init_ethnicity'];
                $upd_patient_array['religion']           = $data['init_religion'];
                $upd_patient_array['patient_type']       = $data['init_patient_type'];
                $upd_patient_array['blood_group']        = $data['init_blood_group'];
                $upd_patient_array['blood_rhesus']       = $data['init_blood_rhesus'];
                if($data['init_demise_date']){
                    $upd_patient_array['demise_date']              =   $data['init_demise_date'];
                }
                if($data['init_demise_time']){
                    $upd_patient_array['demise_time']              =   $data['init_demise_time'];
                }
                $upd_patient_array['demise_cause']       = $data['init_demise_cause'];
                $upd_patient_array['clinic_home']        = $data['init_location_id'];
                $upd_patient_array['clinic_registered']  = $data['init_location_id'];
                $upd_patient_array['patient_status']     = 1;
                $upd_patient_array['location_id']        = $data['init_location_id'];
                $upd_patient_array['contact_id']         = $data['init_contact_id'];
                $upd_patient_array['correspondence_id']  = $data['now_id'];
                $upd_patient_array['contact_type']       = "Residence";
                $upd_patient_array['correspondence_type']= "Correspondence";
                $upd_patient_array['start_date']         = $data['now_date'];
                $upd_patient_array['patient_address']    = $data['init_patient_address'];
                $upd_patient_array['patient_address2']   = $data['init_patient_address2'];
                $upd_patient_array['patient_address3']   = $data['init_patient_address3'];
                $upd_patient_array['patient_postcode']   = $data['init_patient_postcode'];
                $upd_patient_array['patient_town']       = $data['init_patient_town'];
                $upd_patient_array['patient_state']      = $data['init_patient_state'];
                $upd_patient_array['patient_country']    = $data['init_patient_country'];
                $upd_patient_array['tel_home']           = $data['init_tel_home'];
                $upd_patient_array['tel_office']         = $data['init_tel_office'];
                $upd_patient_array['tel_mobile']         = $data['init_tel_mobile'];
                $upd_patient_array['email']   	         = $data['init_email'];
                $upd_patient_array['addr_village_id']    = $data['init_addr_village_id'];
                $upd_patient_array['addr_town_id']       = $data['init_addr_town_id'];
                $upd_patient_array['addr_area_id']       = $data['init_addr_area_id'];
                $upd_patient_array['addr_district_id']   = $data['init_addr_district_id'];
                $upd_patient_array['addr_state_id']      = $data['init_addr_state_id'];
                $upd_patient_array['birth_date_estimate']= (boolean)$data['init_birth_date_estimate'];
	            $upd_patient_data = $this->mbio->update_patient_info($upd_patient_array);
            } //endif($data['patient_id'] == "new_patient")
            echo form_open('bio_hospital/search_new_notify');
            echo "\n<br /><input type='hidden' name='patient_name' value='".$data['init_patient_name']."' size='40' />";
            echo "\nSaved. <input type='submit' value='Click to Continue' />";
            echo "</form>";

        } //endif ($this->form_validation->run('edit_patient') == FALSE)
		//$this->load->view('bio/bio_new_case_hosp');
    } //end of function edit_patient()


    // ------------------------------------------------------------------------
    // === REPORTS MANAGEMENT
    // ------------------------------------------------------------------------
    function reports_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	$this->load->model('memr');
		$data['title'] = "T H I R R A - Reports Management";
		$this->load->vars($data);
        switch ($_SESSION['thirra_mode']){
            case "bio_mobile":
                $new_header =   "bio/header_xhtml-mobile10";
                $new_banner =   "bio/banner_bio_wap";
                $new_sidebar=   "bio/sidebar_bio_reports_html";
                $new_body   =   "bio/bio_reports_mgt_wap";
                $new_footer =   "bio/footer_bio_wap";
                break;			
            case "bio_broad":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_html";
                $new_sidebar=   "bio/sidebar_bio_reports_html";
                $new_body   =   "bio/bio_reports_mgt_html";
                $new_footer =   "bio/footer_bio_html";
                break;			
            case "bio_hospital":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_hosp";
                $new_sidebar=   "bio/sidebar_bio_reports_hosp";
                $new_body   =   "bio/bio_reports_mgt_hosp";
                $new_footer =   "bio/footer_bio_hosp";
                break;			
            case "bio_dept":
                //$new_header =   "bio/header_xhtml1-strict";
                $new_header =   "bio/header_xhtml1-transitional";
                $new_banner =   "bio/banner_bio_dept";
                $new_sidebar=   "bio/sidebar_bio_reports_html";
                $new_body   =   "bio/bio_reports_mgt_dept";
                $new_footer =   "bio/footer_bio_dept";
                break;			
        }
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function reports_mgt($id)


    // ------------------------------------------------------------------------
    // === UTILITIES MANAGEMENT
    // ------------------------------------------------------------------------
    function utilities_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	$this->load->model('memr');
		$data['title'] = "T H I R R A - Utilities Management";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "emr_mobile"){
            $new_header =   "emr/header_xhtml-mobile10";
            $new_banner =   "emr/banner_emr_wap";
            $new_sidebar=   "emr/sidebar_emr_utilities_wap";
            $new_body   =   "emr/emr_utilities_mgt_wap";
            $new_footer =   "emr/footer_emr_wap";
		} else {
            //$new_header =   "emr/header_xhtml1-strict";
            $new_header =   "emr/header_xhtml1-transitional";
            $new_banner =   "emr/banner_emr_html";
            $new_sidebar=   "emr/sidebar_emr_utilities_html";
            $new_body   =   "emr/emr_utilities_mgt_html";
            $new_footer =   "emr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function utilities_mgt($id)


    // ------------------------------------------------------------------------
    // === ADMIN MANAGEMENT
    // ------------------------------------------------------------------------
    function admin_mgt($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	$this->load->model('memr');
		$data['title'] = "T H I R R A - Admin Management";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "emr_mobile"){
            $new_header =   "emr/header_xhtml-mobile10";
            $new_banner =   "emr/banner_emr_wap";
            $new_sidebar=   "emr/sidebar_emr_admin_wap";
            $new_body   =   "emr/emr_admin_mgt_wap";
            $new_footer =   "emr/footer_emr_wap";
		} else {
            //$new_header =   "emr/header_xhtml1-strict";
            $new_header =   "emr/header_xhtml1-transitional";
            $new_banner =   "emr/banner_emr_html";
            $new_sidebar=   "emr/sidebar_emr_admin_html";
            $new_body   =   "emr/emr_admin_mgt_html";
            $new_footer =   "emr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function admin_mgt($id)


    // ------------------------------------------------------------------------
    function break_date($iso_date)  // template for new classes
    {
        $broken_date          =   array();
        $broken_date['yyyy']  =   substr($iso_date,0,4);
        $broken_date['mm']    =   substr($iso_date,5,2);
        $broken_date['dd']    =   substr($iso_date,8,2);
        return $broken_date;
    } // end of function break_date($iso_date)


    // ------------------------------
    // Callbacks for Forms Validation
    // ------------------------------
	function username_check($str) //Callbacks: Your own Validation Functions
	{
		if ($str == 'test'){
			$this->form_validation->set_message('username_check', 'The %s field can not be the word "test"');
			return FALSE;
		} else {
			return TRUE;
		}
	} //end of function username_check($str)


    // ------------------------------------------------------------------------
    // === TEMPLATES
    // ------------------------------------------------------------------------
    function new_method($id=NULL)  // template for new classes
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
	  	$this->load->model('memr');
		$data['title'] = "T H I R R A - NewPage";
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "emr_mobile"){
            $new_header =   "emr/header_xhtml-mobile10";
            $new_banner =   "emr/banner_emr_wap";
            $new_body   =   "emr/emr_newpage_wap";
            $new_footer =   "emr/footer_emr_wap";
		} else {
            //$new_header =   "emr/header_xhtml1-strict";
            $new_header =   "emr/header_xhtml1-transitional";
            $new_banner =   "emr/banner_emr_html";
            $new_body   =   "emr/emr_newpage_html";
            $new_footer =   "emr/footer_emr_html";
		}
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function new_method($id)



}
