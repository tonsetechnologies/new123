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
 * Controller Class for EHR_REPORTS
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.8
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_mpdf extends MY_Controller 
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
        //$this->load->scaffolding('patient_demographic_info');
	  	//$this->load->model('mpatients');
        //$this->lang->load('emr', 'nepali');
        //$this->lang->load('emr', 'ceylonese');
        //$this->lang->load('emr', 'malay');
        $this->lang->load('emr', 'english');
		$this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->load->model('memr_rdb');
		$this->load->model('memr_wdb');
        
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
    // === REPORTS MANAGEMENT
    // ------------------------------------------------------------------------
    function generate_test($id=NULL)  // template for new classes
    {
        $data['offline_mode']   =	$this->_offline_mode;
        $data['debug_mode']		    =	$this->_debug_mode;
        $this->load->library('mpdf');
        $this->mpdf->WriteHTML('<p>Hello There</p>');
        $this->mpdf->Output();
    }

}

/* End of file emr.php */
/* Location: ./app_thirra/controllers/emr.php */


