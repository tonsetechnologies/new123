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
 * Controller Class for EHR_AJAX
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.12
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_ajax extends MY_Controller 
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
		$this->load->model('majax_rdb');
		$this->load->model('mthirra');

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
    }
    

	function index()
	{
        $data['patient_id'] =   "19081987230220111298434484";
		// Load view.
		$this->load->view('test_ajax', $data);
	}
 

    // ------------------------------------------------------------------------
    // Retrieve medication history
    function show_medication()
    {
        $data['patient_id']      =   $this->input->post('patient_id');
		//$data['patient_id'] = $this->uri->segment(3);
        //$this->load->library(database);
        //load the database library to connect to your database
        //$this->load->model(records);
        //inside your system/application/models folder, create a model based on
        //the procedure outlined in the CI documentation
        //$results = $this->records->get_record($record_id);
        $data['medication_list'] 	= $this->memr_rdb->get_recent_medication($data['patient_id'],999,5);
        //get the record from the database
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_indvoverview_medication");
    } //end function show_medication()


    // ------------------------------------------------------------------------
    // Retrieve immunisation history
    function show_immunisation()
    {
        $data['patient_id']      =   $this->input->post('patient_id');
		//$data['patient_id'] = $this->uri->segment(3);
        //$this->load->library(database);
        //load the database library to connect to your database
        //$this->load->model(records);
        //inside your system/application/models folder, create a model based on
        //the procedure outlined in the CI documentation
        //$results = $this->records->get_record($record_id);
        $data['vaccines_list'] 	= $this->memr_rdb->get_patient_immunisation($data['patient_id'],999,6);
        //get the record from the database
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_indvoverview_immunisation");
    }


    // ------------------------------------------------------------------------
    // Retrieve state list for selection
    function get_address_state_names()
    {
		$this->load->model('mutil_rdb');
        $data['addr_country']    =   $this->input->post('ajax_address_country');
		//$data['patient_id'] = $this->uri->segment(3);
        //$this->load->library(database);
		$data['states_list']  = $this->mutil_rdb->get_addr_state_list($data['addr_country'],"addr_state_name",NULL);
        //get the record from the database
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_address_statenames");
    } //endfunction get_address_state_names()


    // ------------------------------------------------------------------------
    // Retrieve districts list for selection
    function get_address_district_names()
    {
		$this->load->model('mutil_rdb');
        $data['addr_country']    =   $this->input->post('ajax_address_country');
        $data['addr_state_id']  =   $this->input->post('ajax_address_state');
		//$data['patient_id'] = $this->uri->segment(3);
        //$this->load->library(database);
		$data['district_list']  = $this->mutil_rdb->get_addr_district_list($data['addr_country'],"addr_district_name",$data['addr_state_id']);
        //get the record from the database
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_address_districtnames");
    } //endfunction get_address_district_names()


    // ------------------------------------------------------------------------
    // Retrieve areas list for selection
    function get_address_area_names()
    {
		$this->load->model('mutil_rdb');
        $data['addr_country']    =   $this->input->post('ajax_address_country');
        $data['addr_district_id']   =   $this->input->post('ajax_address_district');
		//$data['patient_id'] = $this->uri->segment(3);
        //$this->load->library(database);
		$data['area_list']  = $this->mutil_rdb->get_addr_area_list($data['addr_country'],"addr_area_name",NULL,$data['addr_district_id']);
        //get the record from the database
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_address_areanames");
    } //endfunction get_address_area_names()


    // ------------------------------------------------------------------------
    // Retrieve towns list for selection
    function get_address_town_names()
    {
		$this->load->model('mutil_rdb');
        $data['addr_country']    =   $this->input->post('ajax_address_country');
        $data['addr_area_id']    =   $this->input->post('ajax_address_area');
		$data['town_list']  = $this->mutil_rdb->get_addr_town_list($data['addr_country'],"addr_town_name",NULL,$data['addr_area_id']);
 		$data['village_list']  = $this->mutil_rdb->get_addr_village_list($data['addr_country'],"addr_town_name",NULL,NULL,$data['addr_area_id']);
       //get the record from the database
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_address_townnames");
    } //endfunction get_address_town_names()


    // ------------------------------------------------------------------------
    // Retrieve villages list for selection
    function get_address_village_names()
    {
		$this->load->model('mutil_rdb');
        $data['addr_country']    =   $this->input->post('ajax_address_country');
        $data['addr_town_id']    =   $this->input->post('ajax_address_town');
 		$data['village_list']  = $this->mutil_rdb->get_addr_village_list($data['addr_country'],"addr_town_name",NULL,$data['addr_town_id']);
       //get the record from the database
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_address_villagenames");
    } //endfunction get_address_town_names()


    // ------------------------------------------------------------------------
    // update_kin_details
    function update_kin_details()
    {
        if(isset($_POST['patient_id']) && $_POST['patient_id']<>""){
            $this->load->model('majax_wdb');
            $data['patient_id']             =   $this->input->post('patient_id');
            $data['ajax_outcome']           =   $this->input->post('ajax_outcome');
            $data['init_kin_ic_no']         =   $this->input->post('ajax_kin_ic_no');
            $data['init_kin_birth_date']    =   $this->input->post('ajax_kin_birth_date');
            $data['init_kin_job_function']  =   $this->input->post('ajax_kin_job_function');
            $data['init_kin_tel_mobile']    =   $this->input->post('ajax_kin_tel_mobile');
            // Retrieve contact_id of kin
            $data['init_contact_info'] 	    = $this->majax_rdb->get_kin_contact_info($data['patient_id']);
            if($data['ajax_outcome'] == "Updated"){
                // Update database
                $upd_patient_array['patient_id']        = $data['patient_id'];
                $upd_patient_array['ic_no']             = $data['init_kin_ic_no'];
                $upd_patient_array['birth_date']        = $data['init_kin_birth_date'];
                $upd_patient_array['job_function']      = $data['init_kin_job_function'];
                $upd_patient_array['contact_id']        = $data['init_contact_info']['contact_id'];        
                $upd_patient_array['tel_mobile']        = $data['init_kin_tel_mobile'];
                $upd_patient_data       =   $this->majax_wdb->update_kin_demog_info($upd_patient_array);
            }
            
            // Retrieve updated info
            $data['contact_info'] 	    = $this->majax_rdb->get_kin_contact_info($data['patient_id']);
            $data['kin_name']               =   $data['contact_info']['name'];
            $data['init_kin_ic_no']         =   $data['contact_info']['ic_no'];
            $data['init_kin_birth_date']    =   $data['contact_info']['birth_date'];
            $data['init_kin_job_function']  =   $data['contact_info']['job_function'];
            $data['init_kin_tel_mobile']    =   $data['contact_info']['tel_mobile'];
            //get the record from the database
            //echo "Well done".$data['patient_id'] ;
            $this->load->vars($data);
            $this->load->view("ehr/ajax_update_kin_details");
        } else {
        }
    } //endfunction update_kin_details()


    // ------------------------------------------------------------------------
    // Retrieve generic drugs list for selection
    function get_diagnosis_diagnosiscategory()
    {
		$this->load->model('mutil_rdb');
        $data['diagnosis_chapter']    =   $this->input->post('ajax_diagnosis_chapter');
		$data['dcode1_list'] = $this->mutil_rdb->get_dcode1_by_chapter($data['diagnosis_chapter']);
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_diagnosis_diagnosiscategory");
    } // endfunction get_diagnosis_diagnosiscategory()


    // ------------------------------------------------------------------------
    // Retrieve drug trade names list for selection
    function get_diagnosis_diagnosisext()
    {
		$this->load->model('mutil_rdb');
        $data['ajax_diagnosis_category']    =   $this->input->post('ajax_diagnosisCategory');
        $data['ajax_patient_id']            =   $this->input->post('ajax_patient_id');
        if(isset($data['ajax_diagnosis_category'])){
		    $data['dcode1ext_list'] = $this->mutil_rdb->get_dcode1ext_by_dcode1($data['ajax_diagnosis_category']);
        } else {
            $data['dcode1ext_list'] = array();
        }
        if(isset($data['diagnosis'])){
		    $data['dcode2ext_list'] = $this->mutil_rdb->get_dcode2ext_by_dcode1ext($data['diagnosis']);
        } else {
            $data['dcode2ext_list'] = array();
        }
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_diagnosis_diagnosiscode");
    } //endfunction get_diagnosis_diagnosisext()


    // ------------------------------------------------------------------------
    // Retrieve generic drugs list for selection
    function get_prescription_druggenericnames()
    {
		$this->load->model('mpharma_rdb');
        $data['drug_system']            =   $this->input->post('ajax_drug_system');
		//$data['patient_id'] = $this->uri->segment(3);
        //$this->load->library(database);
        //load the database library to connect to your database
        //$this->load->model(records);
        //inside your system/application/models folder, create a model based on
        //the procedure outlined in the CI documentation
        //$results = $this->records->get_record($record_id);
		$data['formulary_list'] = $this->mpharma_rdb->get_formulary_by_system($data['drug_system']);
        //get the record from the database
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_prescription_druggenericnames");
    } //endfunction get_prescription_druggenericnames()


    // ------------------------------------------------------------------------
    // Retrieve drug trade names list for selection
     function get_prescription_drugtradenames()
    {
		$this->load->model('mpharma_rdb');
        $data['ajax_drug_formulary_id']     =   $this->input->post('ajax_drug_formulary_id');
        $data['ajax_patient_id']            =   $this->input->post('ajax_patient_id');
        //$this->load->library(database);
        //load the database library to connect to your database
        //$this->load->model(records);
        //inside your system/application/models folder, create a model based on
        //the procedure outlined in the CI documentation
        //$results = $this->records->get_record($record_id);
		$data['allergy_list']   = $this->memr_rdb->get_drug_allergies('List',$data['ajax_patient_id']);
        $data['tradename_list'] = $this->mpharma_rdb->get_tradename_by_formulary($data['ajax_drug_formulary_id']);
        $data['formulary_chosen'] = $this->mpharma_rdb->get_one_drug_formulary($data['ajax_drug_formulary_id']);
        if(count($data['formulary_chosen'])>0){
            // Check for allergy
            if(count($data['allergy_list'])>0){
                $data['error_messages']['severity'] =   "";
                $data['error_messages']['msg']      =   "";
                foreach($data['allergy_list'] as $allergic_drug){
                    if(in_array($data['formulary_chosen']['atc_code'],$allergic_drug)){
                        $data['error_messages']['severity'] =   "DRUG ALLERGY: ";        
                        $data['error_messages']['msg']      =   "Possible reaction";        
                    }
                }
            } //endif(count($data['allergy_list'])>0)
        }
        //get the record from the database
        //echo "Well done".$data['patient_id'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_prescription_drugtradenames");
    } //endfunction get_prescription_drugtradenames()


    // ------------------------------------------------------------------------
    // Retrieve ATC drug information
    function get_atc_info()
    {
		$this->load->model('mutil_rdb');
        $data['atc_code']            =   $this->input->post('ajax_atc_code');
        $data['code_info']  = $this->mutil_rdb->get_drugatc_codes_list('data','atc_code',1,0,$data['atc_code']);
        $data['init_atc_code']          = $data['atc_code'];
        $data['init_drug_atc_id']       = $data['code_info'][0]['drug_atc_id'];
        $data['init_atc_anatomical']    = $data['code_info'][0]['atc_anatomical'];
        $data['init_atc_therapeutic']   = $data['code_info'][0]['atc_therapeutic'];
        $data['init_atc_pharmaco']      = $data['code_info'][0]['atc_pharmaco'];
        $data['init_atc_chemical']      = $data['code_info'][0]['atc_chemical'];
        $data['init_desc_anatomical']   = $data['code_info'][0]['desc_anatomical'];
        $data['init_desc_therapeutic']  = $data['code_info'][0]['desc_therapeutic'];
        $data['init_desc_pharmaco']     = $data['code_info'][0]['desc_pharmaco'];
        $data['init_desc_chemical']     = $data['code_info'][0]['desc_chemical'];
        $data['init_atc_name']          = $data['code_info'][0]['atc_name'];
        $data['init_ddd_qty']           = $data['code_info'][0]['ddd_qty'];
        $data['init_ddd_unit']          = $data['code_info'][0]['ddd_unit'];
        $data['init_admin_route']       = $data['code_info'][0]['admin_route'];
        $data['init_remarks']           = $data['code_info'][0]['remarks'];
        $data['init_drug_interaction']  = $data['code_info'][0]['drug_interaction'];
        $data['init_part_atc_code']     = substr($data['init_atc_code'],5);
        //echo "Well done".$data['atc_code'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_atc_info");
    } // endfunction get_atc_info()


    // ------------------------------------------------------------------------
    // Retrieve generic drug information
    function get_formulary_info()
    {
		$this->load->model('mutil_rdb');
        $data['drug_formulary_id']            =   $this->input->post('ajax_drug_formulary_id');
        $data['formulary_info']  = $this->mutil_rdb->get_drug_formulary_list('data','formulary_code',1,0,$data['drug_formulary_id']);
        $data['drugcode_list']  = $this->mutil_rdb->get_drug_code_list('data','trade_name','All',0,NULL,$data['drug_formulary_id']);
        //echo "Well done".$data['atc_code'] ;
		$this->load->vars($data);
        $this->load->view("ehr/ajax_get_formulary_info");
    } // endfunction get_formulary_info()


}

/* End of file Ehr_ajax.php */
/* Location: ./app_thirra/controllers/Ehr_ajax.php */
