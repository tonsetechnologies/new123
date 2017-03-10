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
 * Portions created by the Initial Developer are Copyright (C) 2009
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * ***** END LICENSE BLOCK ***** */

session_start();

/**
 * Controller Class for EHR_ADMIN
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.8
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_cli extends MY_Controller 
{
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
        //$this->load->scaffolding('patient_demographic_info');
	  	//$this->load->model('mpatients');
        //$this->lang->load('emr', 'nepali');
        //$this->lang->load('emr', 'ceylonese');
        //$this->lang->load('emr', 'malay');
        $this->lang->load('ehr', $data['app_language']);
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->load->model('memr_rdb');
		$this->load->model('memr_wdb');
        
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."uploads/";
        define("PICS_URL", $data['pics_url']);
    }


    // ------------------------------------------------------------------------
    // === DASHBOARDS
    // ------------------------------------------------------------------------
	function index() // Dashboard
    {	
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');

        //----------------
		$data['title'] = "T H I R R A - Main Page";
		$data['main'] = 'home';
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        if($data['debug_mode']){
            $this->output->enable_profiler(TRUE);  
        }
		$this->load->vars($data);
        
            $writeFilePath = "/var/www/thirra-uploads/exports/";
            $dataFile      = "cli-temp.txt";
            //echo "\nWriting to " . $writeFilePath . $dataFile;
            $contents   =   "Testing 1234";
            $fp = fopen($writeFilePath . $dataFile, 'w');
            fwrite($fp, $contents);

            fclose($fp);
        
	} // end of function index()


    // ------------------------------------------------------------------------
	function write2file() // Dashboard
    {	
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');

        //----------------
		$data['title'] = "T H I R R A - CLI";
		$data['main'] = 'home';
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        if($data['debug_mode']){
            $this->output->enable_profiler(TRUE);  
        }
		$this->load->vars($data);
		//$this->load->view($new_header);			
		//$this->load->view($new_banner);			
		//$this->load->view($new_body);			
		//$this->load->view($new_footer);			
        
            $writeFilePath = "/var/www/thirra-uploads/exports/";
            $dataFile      = "cli-temp.txt";
            //echo "\nWriting to " . $writeFilePath . $dataFile;
            $contents   =   "Testing A123";
            $fp = fopen($writeFilePath . $dataFile, 'w');
            fwrite($fp, $contents);


            fclose($fp);
        
	} // end of function write2file()


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
		if ($_SESSION['thirra_mode'] == "emr_mobile"){
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
		$this->load->view($new_header);			
		$this->load->view($new_banner);			
		$this->load->view($new_sidebar);			
		$this->load->view($new_body);			
		$this->load->view($new_footer);			
    } // end of function new_method($id)


}

/* End of file emr.php */
/* Location: ./app_thirra/controllers/emr.php */
