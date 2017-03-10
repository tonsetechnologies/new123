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
class Ehr_pharmacy_supplier extends MY_Controller 
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
		//$this->load->model('memr_rdb');
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
    function phar_list_drug_suppliers($id=NULL)  // List suppliers for drugs
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy');    
		$data['supplier_type']   	= $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Suppliers for ".$data['supplier_type'];
		$data['supplier_list']  = $this->mpharma_rdb->get_supplier_list_drug();
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_pharmacy_wap";
            //$new_body   =   "ehr/ehr_orders_list_imagsuppliers_wap";
            $new_body   =   "ehr/ehr_phar_list_drugsuppliers_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_pharmacy_html";
            $new_body   =   "ehr/ehr_phar_list_drugsuppliers_html";
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
    } // end of function phar_list_drug_suppliers($id)


    // ------------------------------------------------------------------------
    function phar_edit_drugsupplier_info()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_currency']		=	$this->config->item('app_currency');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy','ehr_pharmacy_supplier/phar_list_drug_suppliers/drug','List Drug Suppliers');    
        $data['form_purpose']   = $this->uri->segment(3);
        $data['supplier_type']	= $this->uri->segment(4);
        $data['supplier_id']	= $this->uri->segment(5);
        $data['packages_list']= $this->mpharma_rdb->get_drug_product_bysupplier($data['supplier_id']);
	  	
        if(count($_POST)) {
            // User has posted the form
            $data['form_purpose']       = $_POST['form_purpose'];
            $data['init_supplier_id']	= $_POST['supplier_id'];
            $data['init_supplier_name'] = $_POST['supplier_name'];
            $data['init_registration_no']= $_POST['registration_no'];
            $data['init_acc_no']        = $_POST['acc_no'];
            $data['init_credit_term']   = $_POST['credit_term'];
            $data['init_supplier_remarks']= $_POST['supplier_remarks'];
            $data['contact_id']          = $_POST['contact_id'];
            $data['init_address']       = $_POST['address'];
            $data['init_address2']      = $_POST['address2'];
            $data['init_address3']      = $_POST['address3'];
            $data['init_town']          = $_POST['town'];
            $data['init_state']         = $_POST['state'];
            $data['init_postcode']      = $_POST['postcode'];
            $data['init_country']       = $_POST['country'];
            $data['init_tel_no']        = $_POST['tel_no'];
            $data['init_tel2_no']       = $_POST['tel2_no'];
            $data['init_tel3_no']       = $_POST['tel3_no'];
            $data['init_fax_no']        = $_POST['fax_no'];
            $data['init_fax2_no']       = $_POST['fax2_no'];
            $data['init_email']         = $_POST['email'];
            $data['init_contact_person']= $_POST['contact_person'];
            $data['init_contact_other'] = $_POST['contact_other'];
            $data['init_website']       = $_POST['website'];
            $data['init_contact_remarks'] = $_POST['contact_remarks'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_supplier") {
                $data['init_supplier_name'] =   "";
                $data['init_registration_no']=   "";
                $data['init_acc_no']        =   "";
                $data['init_credit_term']   =   0;
                $data['init_supplier_remarks']=   "";
                $data['init_address']       =   "";
                $data['init_address2']      =   "";
                $data['init_address3']      =   "";
                $data['init_town']          =   "";
                $data['init_state']         =   "";
                $data['init_postcode']      =   "";
                $data['init_country']       =   "";
                $data['init_tel_no']        =   "";
                $data['init_tel2_no']       =   "";
                $data['init_tel3_no']       =   "";
                $data['init_fax_no']        =   "";
                $data['init_fax2_no']       =   "";
                $data['init_email']         =   "";
                $data['init_contact_person']=   "";
                $data['init_contact_other'] =   "";
                $data['init_website']       =   "";
                $data['init_contact_remarks']=   "";
            } elseif ($data['form_purpose'] == "edit_supplier") {
                //echo "Edit supplier";
                $data['supplier_info'] = $this->mpharma_rdb->get_supplier_list_drug($data['supplier_id']);
                $data['init_supplier_name'] = $data['supplier_info'][0]['supplier_name'];
                $data['init_registration_no']= $data['supplier_info'][0]['registration_no'];
                $data['contact_id']        = $data['supplier_info'][0]['contact_id'];
                $data['customer_info_id']        = $data['supplier_info'][0]['customer_info_id'];
                $data['init_acc_no']        = $data['supplier_info'][0]['acc_no'];
                $data['init_credit_term']   = $data['supplier_info'][0]['credit_term'];
                $data['init_supplier_remarks']= $data['supplier_info'][0]['remarks'];
                $data['init_address']       = $data['supplier_info'][0]['address'];
                $data['init_address2']      = $data['supplier_info'][0]['address2'];
                $data['init_address3']      = $data['supplier_info'][0]['address3'];
                $data['init_town']          = $data['supplier_info'][0]['town'];
                $data['init_state']         = $data['supplier_info'][0]['state'];
                $data['init_postcode']      = $data['supplier_info'][0]['postcode'];
                $data['init_country']       = $data['supplier_info'][0]['country'];
                $data['init_tel_no']        = $data['supplier_info'][0]['tel_no'];
                $data['init_tel2_no']       = $data['supplier_info'][0]['tel2_no'];
                $data['init_tel3_no']       = $data['supplier_info'][0]['tel2_no'];
                $data['init_fax_no']        = $data['supplier_info'][0]['fax_no'];
                $data['init_fax2_no']       = $data['supplier_info'][0]['fax2_no'];
                $data['init_email']         = $data['supplier_info'][0]['email'];
                $data['init_contact_person']= $data['supplier_info'][0]['contact_person'];
                $data['init_contact_other'] = $data['supplier_info'][0]['contact_other'];
                $data['init_website']       = $data['supplier_info'][0]['website'];
                $data['init_contact_remarks'] = $data['supplier_info'][0]['contact_remarks'];
            } //endif ($data['form_purpose'] == "new_supplier")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Supplier";
        $data['init_location_id']   =   $_SESSION['location_id'];
        $data['init_clinic_name']   =   NULL;
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_drug_supplier') == FALSE){
		    //$this->load->view('emr/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_pharmacy_wap";
                //$new_body   =   "ehr/ehr_orders_edit_imagsupplier_info_wap";
                $new_body   =   "ehr/ehr_phar_edit_drugsupplier_info_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_pharmacy_html";
                $new_body   =   "ehr/ehr_phar_edit_drugsupplier_info_html";
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
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_supplier") {
                // New supplier record
                $ins_supplier_array   =   array();
                $ins_supplier_array['staff_id']       = $_SESSION['staff_id'];
                $ins_supplier_array['now_id']         = $data['now_id'];
                $ins_supplier_array['supplier_id']	  = $data['now_id'];
                $ins_supplier_array['supplier_name']  = $data['init_supplier_name'];
                $ins_supplier_array['registration_no']= $data['init_registration_no'];
                $ins_supplier_array['contact_id']     = $data['now_id'];
                $ins_supplier_array['customer_info_id']= $data['now_id'];
                $ins_supplier_array['acc_no']         = $data['init_acc_no'];
                //$ins_supplier_array['account_id']   = $data['init_town'];
                if(is_numeric($data['init_credit_term'])){
                    $ins_supplier_array['credit_term']                = $data['init_credit_term'];
                }
                //$ins_supplier_array['credit_term']    = $data['init_credit_term'];
                $ins_supplier_array['supplier_remarks']= $data['init_supplier_remarks'];
                $ins_supplier_array['address']        = $data['init_address'];
                $ins_supplier_array['address2']       = $data['init_address2'];
                $ins_supplier_array['address3']       = $data['init_address3'];
                $ins_supplier_array['town']           = $data['init_town'];
                $ins_supplier_array['state']          = $data['init_state'];
                $ins_supplier_array['postcode']       = $data['init_postcode'];
                $ins_supplier_array['country']        = $data['init_country'];
                $ins_supplier_array['tel_no']         = $data['init_tel_no'];
                $ins_supplier_array['tel_no2']        = $data['init_tel2_no'];
                $ins_supplier_array['tel_no3']        = $data['init_tel3_no'];
                $ins_supplier_array['fax_no']         = $data['init_fax_no'];
                $ins_supplier_array['fax_no2']        = $data['init_fax2_no'];
                $ins_supplier_array['email']          = $data['init_email'];
                $ins_supplier_array['contact_person'] = $data['init_contact_person'];
                $ins_supplier_array['other']          = $data['init_contact_other'];
                $ins_supplier_array['website']        = $data['init_website'];
                $ins_supplier_array['contact_remarks']        = $data['init_contact_remarks'];
                if($data['offline_mode']){
                    $ins_supplier_array['synch_out']        = $data['now_id'];
                }
	            $ins_supplier_data       =   $this->mpharma_wdb->insert_new_drugsupplier($ins_supplier_array);
                $this->session->set_flashdata('data_activity', 'Drug supplier added.');
            } elseif($data['form_purpose'] == "edit_supplier") {
                // Existing supplier record
                $upd_supplier_array   =   array();
                $upd_supplier_array['staff_id']       = $_SESSION['staff_id'];
                $upd_supplier_array['supplier_name']  = $data['init_supplier_name'];
                $upd_supplier_array['registration_no']= $data['init_registration_no'];
                $upd_supplier_array['contact_id']     = $data['contact_id'];
                $upd_supplier_array['customer_info_id']= $data['customer_info_id'];
                $upd_supplier_array['acc_no']         = $data['init_acc_no'];
                //$upd_supplier_array['account_id']   = $data['init_town'];
                if(is_numeric($data['init_credit_term'])){
                    $upd_supplier_array['credit_term']                = $data['init_credit_term'];
                }
                //$upd_supplier_array['credit_term']    = $data['init_credit_term'];
                $upd_supplier_array['supplier_remarks']= $data['init_supplier_remarks'];
                $upd_supplier_array['supplier_id']= $data['supplier_id'];
                $upd_supplier_array['supplier_name']    = $data['init_supplier_name'];
                $upd_supplier_array['address']        = $data['init_address'];
                $upd_supplier_array['address2']       = $data['init_address2'];
                $upd_supplier_array['address3']       = $data['init_address3'];
                $upd_supplier_array['town']           = $data['init_town'];
                $upd_supplier_array['state']          = $data['init_state'];
                $upd_supplier_array['postcode']       = $data['init_postcode'];
                $upd_supplier_array['country']        = $data['init_country'];
                $upd_supplier_array['tel_no']         = $data['init_tel_no'];
                $upd_supplier_array['tel_no2']        = $data['init_tel2_no'];
                $upd_supplier_array['tel_no3']        = $data['init_tel3_no'];
                $upd_supplier_array['fax_no']         = $data['init_fax_no'];
                $upd_supplier_array['fax_no2']        = $data['init_fax2_no'];
                $upd_supplier_array['email']          = $data['init_email'];
                $upd_supplier_array['contact_person'] = $data['init_contact_person'];
                $upd_supplier_array['other']          = $data['init_contact_other'];
                $upd_supplier_array['website']        = $data['init_website'];
                $upd_supplier_array['contact_remarks']        = $data['init_contact_remarks'];
	            $upd_supplier_data       =   $this->mpharma_wdb->update_drug_supplier($upd_supplier_array);
                $this->session->set_flashdata('data_activity', 'Drug supplier updated.');
            } //endif($data['diagnosis_id'] == "new_supplier")
            $new_page = base_url()."index.php/ehr_pharmacy/phar_list_drug_suppliers";
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_drug_supplier') == FALSE)


    } // end of function phar_edit_drugsupplier_info()


    // ------------------------------------------------------------------------
    // Add/Edit drug product based on drug code
    function phar_edit_drug_product()
    {
		$this->load->model('mutil_rdb');
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        $data['app_currency']		=	$this->config->item('app_currency');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy','ehr_pharmacy_supplier/phar_list_drug_suppliers/drug','List Drug Suppliers');    
        $data['form_purpose']   	=   $this->uri->segment(3);
        $data['supplier_id']	    =   $this->uri->segment(5);
        $data['product_id']	        =   $this->uri->segment(6);
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
	  	
        if(count($_POST)) {
            // User has posted the form
            if(isset($_POST['loinc_class_name'])) { 
                $data['init_loinc_class_name']   =   $_POST['loinc_class_name'];
            } else {
                $data['init_loinc_class_name']   =   "none";
            }
            if(isset($_POST['loinc_num'])) { 
                $data['init_loinc_num']   =   $_POST['loinc_num'];
            }
            //$data['init_drug_official_code']= $_POST['drug_official_code'];
            $data['init_drug_code_id']  = $_POST['drug_code_id'];
            //$data['init_formulary_id']  = $_POST['formulary_id'];
            $data['init_supplier_id']   = $_POST['supplier_id'];
            $data['init_product_name']  = $_POST['product_name'];
            $data['init_seller_code']   = $_POST['seller_code'];
            $data['init_pbkd_no']       = $_POST['pbkd_no'];
            $data['init_packing']       = $_POST['packing'];
            $data['init_packing_form']  = $_POST['packing_form'];
            //$data['init_bulk_packing']  = $_POST['bulk_packing'];
            //$data['init_bulk_form']     = $_POST['bulk_form'];
            $data['init_wholesale_price']= $_POST['wholesale_price'];
            //$data['init_bonus_base']    = $_POST['bonus_base'];
            //$data['init_bonus_extra']   = $_POST['bonus_extra'];
            $data['init_retail_price']  = $_POST['retail_price'];
            $data['init_retail_price_2']= $_POST['retail_price_2'];
            $data['init_retail_price_3']= $_POST['retail_price_3'];
            $data['init_ucost_std']     = $_POST['ucost_std'];
            $data['init_quantity']      = $_POST['quantity'];
            $data['init_commonly_used'] = $_POST['commonly_used'];
            //$data['init_reorder_level'] = $_POST['reorder_level'];
            //$data['init_reorder_qty']   = $_POST['reorder_qty'];
            //$data['init_eoq']           = $_POST['eoq'];
            $data['init_remarks']       = $_POST['remarks'];
            $data['init_location_id']   = $_POST['location_id'];
            $data['init_drug_type']     = $_POST['drug_type'];
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_product") {
                //echo "new drug product";
                $data['product_info'] = $this->mpharma_rdb->get_drug_product_bysupplier($data['supplier_id']);
                $data['init_drug_code_id']  =   "";
                $data['init_product_name']  =   "";
                $data['init_seller_code']   =   "";
                $data['init_pbkd_no']       =   "";
                $data['init_packing']       =   "";
                $data['init_packing_form']  =   "";
                $data['init_wholesale_price']=   0;
                $data['init_retail_price']  =   0;
                $data['init_retail_price_2']=   0;
                $data['init_retail_price_3']=   0;
                $data['init_ucost_std']     =   0;
                $data['init_quantity']      =   100000;
                $data['init_commonly_used'] =   "";
                $data['init_remarks']       =   "";
                $data['init_location_id']   =   $_SESSION['location_id'];
                $data['init_drug_type']     =   "Conventional medicine";
           } elseif ($data['form_purpose'] == "edit_product") {
                //echo "Edit drug product";
                $data['product_info'] = $this->mpharma_rdb->get_drug_product_bysupplier($data['supplier_id'],$data['product_id']);
                $data['init_drug_code_id']     	=   $data['product_info'][0]['drug_code_id'];
                $data['init_product_name']     	=   $data['product_info'][0]['product_name'];
                $data['init_seller_code']     	=   $data['product_info'][0]['seller_code'];
                $data['init_pbkd_no'] 	        =   $data['product_info'][0]['pbkd_no'];
                $data['init_packing'] 	        =   $data['product_info'][0]['packing'];
                $data['init_packing_form'] 	    =   $data['product_info'][0]['packing_form'];
                $data['init_wholesale_price'] 	=   $data['product_info'][0]['wholesale_price'];
                $data['init_retail_price'] 		=   $data['product_info'][0]['retail_price'];
                $data['init_retail_price_2'] 	=   $data['product_info'][0]['retail_price_2'];
                $data['init_retail_price_3'] 	=   $data['product_info'][0]['retail_price_3'];
                $data['init_ucost_std'] 	    =   $data['product_info'][0]['ucost_std'];
                $data['init_quantity'] 	        =   $data['product_info'][0]['quantity'];
                $data['init_commonly_used']     =   $data['product_info'][0]['commonly_used'];
                $data['init_remarks'] 	        =   $data['product_info'][0]['remarks'];
                $data['init_location_id'] 	    =   $data['product_info'][0]['location_id'];
                $data['init_drug_type'] 	    =   $data['product_info'][0]['drug_type'];
            } //endif ($data['form_purpose'] == "new_product")
        } //endif(count($_POST))
		$data['title'] = "Add/Edit Drug Product";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        //$data['init_patient_id']    =   $patient_id;

        $data['supplier_info'] = $this->mpharma_rdb->get_supplier_list_drug($data['supplier_id']);
		$data['drugcode_list']  = $this->mutil_rdb->get_drug_code_list('data','trade_name','ALL',0);
        $data['drugcode_info']  = $this->mutil_rdb->get_drug_code_list('data','drug_code',1,0,$data['init_drug_code_id']);
        if(empty($data['drugcode_info'])){
            $data['drugcode_info']  =   array();
            $data['drugcode_info'][0]['drug_formulary_id']   =   "";
            $data['drugcode_info'][0]['drug_code']   =   "";
            $data['drugcode_info'][0]['trade_name']   =   "";
        }
        $data['formulary_info']  = $this->mutil_rdb->get_drug_formulary_list('data','formulary_code',1,0,$data['drugcode_info'][0]['drug_formulary_id']);
		$data['package_forms']  = $this->mutil_rdb->get_package_forms();
        if($data['init_product_name'] == ""){
            ($data['init_product_name'] = $data['drugcode_info'][0]['trade_name']);
        }
        $data['init_drug_official_code']    = $data['drugcode_info'][0]['drug_code'];
        $data['init_formulary_id']          = $data['drugcode_info'][0]['drug_formulary_id'];
        $data['level3_list']    =   array();
        $data['level3_list'][0]['marker']      = "valid";  
        $data['level3_list'][0]['info']        = "N/A";  
        if(!empty($data['init_loinc_num']) ){
            $data['level3'] =   "valid";
        }
		$data['drug_stocks']  = $this->mpharma_rdb->get_drug_product_quantity($data['product_id']);

		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_drug_product') == FALSE){
            //echo "Validation failed";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
		    //$this->load->view('ehr_patient/emr_edit_patient_html');			
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_ehr_pharmacy_wap";
                //$new_body   =   "ehr/ehr_orders_edit_lab_packagetest_wap";
                $new_body   =   "ehr/ehr_phar_edit_drug_product_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_ehr_html";
                $new_sidebar=   "ehr/sidebar_emr_pharmacy_html";
                $new_body   =   "ehr/ehr_phar_edit_drug_product_html";
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
            if($data['form_purpose'] == "new_product") {
                // New lab order record
                $ins_drug_array   =   array();
                $ins_drug_array['now_id']           = $data['now_id'];
				$ins_drug_array['product_id']	    = $data['now_id'];
				$ins_drug_array['drug_official_code']= $data['init_drug_official_code'];
				$ins_drug_array['drug_code_id']		= $data['init_drug_code_id'];
				$ins_drug_array['formulary_id']		= $data['init_formulary_id'];
				//$ins_drug_array['manufacturer_id']= $data['init_manufacturer_id'];
				$ins_drug_array['supplier_id']		= $data['init_supplier_id'];
				$ins_drug_array['product_name']		= $data['init_product_name'];
				$ins_drug_array['seller_code']		= $data['init_seller_code'];
				$ins_drug_array['pbkd_no']		    = $data['init_pbkd_no'];
				$ins_drug_array['packing']		    = $data['init_packing'];
				$ins_drug_array['packing_form']		= $data['init_packing_form'];
				//$ins_drug_array['bulk_packing']	= $data['init_bulk_packing'];
				//$ins_drug_array['bulk_form']		= $data['init_bulk_form'];
				$ins_drug_array['wholesale_price']	= $data['init_wholesale_price'];
				//$ins_drug_array['bonus_base']		= $data['init_bonus_base'];
				//$ins_drug_array['bonus_extra']	= $data['init_bonus_extra'];
				$ins_drug_array['retail_price']		= $data['init_retail_price'];
				$ins_drug_array['retail_price_2']	= $data['init_retail_price_2'];
				$ins_drug_array['retail_price_3']	= $data['init_retail_price_3'];
				$ins_drug_array['ucost_std']		= $data['init_ucost_std'];
				//$ins_drug_array['ucost_fifo']		= $data['init_ucost_fifo'];
				//$ins_drug_array['ucost_wac']		= $data['init_ucost_wac'];
				$ins_drug_array['quantity']		    = $data['init_quantity'];
                if(is_numeric($data['init_commonly_used'])){
                    $ins_drug_array['commonly_used']  = $data['init_commonly_used'];
                }
				//$ins_drug_array['reorder_level']	= $data['init_reorder_level'];
				//$ins_drug_array['reorder_qty']	= $data['init_reorder_qty'];
				//$ins_drug_array['eoq']		    = $data['init_eoq'];
				$ins_drug_array['remarks']		    = $data['init_remarks'];
				$ins_drug_array['location_id']		= $data['init_location_id'];
				$ins_drug_array['drug_type']		= $data['init_drug_type'];
				$ins_drug_array['added_remarks']	= "THIRRA"; //$data['init_added_remarks'];
				$ins_drug_array['added_staff']		= $_SESSION['staff_id'];
				$ins_drug_array['added_date']		= $data['now_date'];
				//$ins_imag_array['commonly_used']	= $data['commonly_used'];
                if($data['offline_mode']){
                    $ins_imag_array['synch_out']    = $data['now_id'];
                }//endif($data['offline_mode'])
	            $ins_drug_data  =   $this->mpharma_wdb->insert_new_drug_product($ins_drug_array);
                $this->session->set_flashdata('data_activity', 'Drug product added.');
            } elseif($data['form_purpose'] == "edit_product") {
                // Existing drug product record
                $upd_drug_array['product_id']       = $data['product_id'];
                $upd_drug_array['drug_official_code']= $data['init_drug_official_code'];
                $upd_drug_array['drug_code_id']     = $data['init_drug_code_id'];
                $upd_drug_array['formulary_id']     = $data['init_formulary_id'];
                //$upd_drug_array['manufacturer_id']  = $data['init_manufacturer_id'];
                $upd_drug_array['supplier_id']      = $data['init_supplier_id'];
                $upd_drug_array['product_name']     = $data['init_product_name'];
                $upd_drug_array['seller_code']      = $data['init_seller_code'];
                $upd_drug_array['pbkd_no']          = $data['init_pbkd_no'];
                $upd_drug_array['packing']          = $data['init_packing'];
                $upd_drug_array['packing_form']     = $data['init_packing_form'];
                //$upd_drug_array['bulk_packing']     = $data['init_bulk_packing'];
                //$upd_drug_array['bulk_form']        = $data['init_bulk_form'];
                $upd_drug_array['wholesale_price']  = $data['init_wholesale_price'];
                //$upd_drug_array['bonus_base']       = $data['init_bonus_base'];
                //$upd_drug_array['bonus_extra']      = $data['init_bonus_extra'];
                $upd_drug_array['retail_price']     = $data['init_retail_price'];
                $upd_drug_array['retail_price_2']   = $data['init_retail_price_2'];
                $upd_drug_array['retail_price_3']   = $data['init_retail_price_3'];
                $upd_drug_array['ucost_std']        = $data['init_ucost_std'];
                //$upd_drug_array['ucost_fifo']       = $data['init_ucost_fifo'];
                //$upd_drug_array['ucost_wac']        = $data['init_ucost_wac'];
                $upd_drug_array['quantity']         = $data['init_quantity'];
                if(is_numeric($data['init_commonly_used'])){
                    $upd_drug_array['commonly_used'] = $data['init_commonly_used'];
                }
                $upd_drug_array['reorder_level']      = $data['init_reorder_level'];
                $upd_drug_array['reorder_qty']      = $data['init_reorder_qty'];
                $upd_drug_array['eoq']              = $data['init_eoq'];
                $upd_drug_array['remarks']          = $data['init_remarks'];
                $upd_drug_array['location_id']      = $data['init_location_id'];
                $upd_drug_array['drug_type']        = $data['init_drug_type'];
                $upd_drug_array['edit_remarks']     = $data['init_edit_remarks'];
                $upd_drug_array['edit_staff']       = $_SESSION['staff_id'];
                $upd_drug_array['edit_date']        = $data['now_date'];
	            $upd_drug_data  =   $this->mpharma_wdb->update_drug_product($upd_drug_array);
                $this->session->set_flashdata('data_activity', 'Drug product updated.');
            } //endif($data['diagnosis_id'] == "new_patient")
            $new_page = base_url()."index.php/ehr_pharmacy/phar_edit_drugsupplier_info/edit_supplier/drug/".$data['init_supplier_id'];
            header("Status: 200");
            header("Location: ".$new_page);

        } // endif ($this->form_validation->run('edit_lab_order') == FALSE)


    } // end of function phar_edit_drug_product()


    // ------------------------------------------------------------------------
    function phar_list_drug_supplierinvoices($id=NULL)  // List suppliers for imaging
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
        $data['breadcrumbs']        =   breadcrumbs('ehr_pharmacy/pharmacy_mgt','Pharmacy','ehr_pharmacy_supplier/phar_list_drug_suppliers/drug','List Drug Suppliers');    
		$data['supplier_id']   	    =   $this->uri->segment(3);
		$data['title'] = "T H I R R A - List of Supplier Invoices";
        $data['supplier_info'] = $this->mpharma_rdb->get_supplier_list_drug($data['supplier_id']);
        $data['invoices_list'] = $this->mpharma_rdb->get_drugsupplier_list_invoices($data['supplier_id']);
        
		$this->load->vars($data);
		if ($_SESSION['thirra_mode'] == "ehr_mobile"){
            $new_header =   "ehr/header_xhtml-mobile10";
            $new_banner =   "ehr/banner_ehr_wap";
            $new_sidebar=   "ehr/sidebar_ehr_pharmacy_wap";
            //$new_body   =   "ehr/ehr_orders_list_imagsuppliers_wap";
            $new_body   =   "ehr/ehr_phar_list_drugsupplier_invoices_html";
            $new_footer =   "ehr/footer_emr_wap";
		} else {
            //$new_header =   "ehr/header_xhtml1-strict";
            $new_header =   "ehr/header_xhtml1-transitional";
            $new_banner =   "ehr/banner_ehr_html";
            $new_sidebar=   "ehr/sidebar_emr_pharmacy_html";
            $new_body   =   "ehr/ehr_phar_list_drugsupplier_invoices_html";
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
    } // end of function phar_list_drug_supplierinvoices($id)


    // ------------------------------------------------------------------------
    function phar_edit_drug_supplierinvoice($id=NULL) 
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
		$data['user_rights']        =   $this->mthirra->get_user_rights($_SESSION['username']);
		$data['form_purpose']       =   $this->uri->segment(3);
		$data['supplier_id']   	    =   $this->uri->segment(4);
		$data['supplier_invoice_id']=   $this->uri->segment(5);
		$data['title'] = "Add New / Edit Supplier Invoice";
        $data['now_id']             =   time();
        $data['now_date']           =   date("Y-m-d",$data['now_id']);
        $data['now_time']           =   date("H:i",$data['now_id']);
        $data['supplier_info'] = $this->mpharma_rdb->get_supplier_list_drug($data['supplier_id']);
        $data['doc_status'] =   "open";
        
        if(count($_POST)) {
            // User has posted the form
            $data['init_invoice_no']      		=   $this->input->post('invoice_no');
            $data['init_invoice_date']      	=   $this->input->post('invoice_date');
            $data['init_remarks']      			=   $this->input->post('remarks');
            $data['init_with_po']      			=   $this->input->post('with_po');
        } else {
            // First time form is displayed
            if ($data['form_purpose'] == "new_invoice") {
                // New invoice
		        $data['report_body']        =   array();
                $data['init_supplier_type'] =   "D";
                $data['init_invoice_no']    =   "";
                $data['init_invoice_date']  =   "";
                $data['init_transaction_id']=   "";
                $data['init_total_cost']    =   0;
                $data['init_location_id']   =   $_SESSION['location_id'];
                $data['init_status']        =   "Open";
                $data['init_remarks']       =   "";
                $data['init_with_po']       =   FALSE;
                $data['init_posted_by']     =   "";
                $data['init_posted_date']   =   "";
                $data['init_posted_time']   =   "";
            } else {
                // Existing user
                //$data['report_head']  = $this->mreport->get_report_header($data['report_header_id']);
                $data['invoice_header'] = $this->mpharma_rdb->get_drugsupplier_list_invoices($data['supplier_id'], $data['supplier_invoice_id']);
                $data['init_supplier_type'] =   $data['invoice_header'][0]['supplier_type'];
                $data['init_invoice_no']    =   $data['invoice_header'][0]['invoice_no'];
                $data['init_invoice_date']  =   $data['invoice_header'][0]['invoice_date'];
                $data['init_transaction_id']=   $data['invoice_header'][0]['transaction_id'];
                $data['init_total_cost']    =   $data['invoice_header'][0]['total_cost'];
                $data['init_location_id']   =   $data['invoice_header'][0]['location_id'];
                $data['init_status']        =   $data['invoice_header'][0]['status'];
                $data['init_remarks']       =   $data['invoice_header'][0]['remarks'];
                $data['init_with_po']       =   $data['invoice_header'][0]['with_po'];
                $data['init_posted_by']     =   $data['invoice_header'][0]['posted_by'];
                $data['init_posted_date']   =   $data['invoice_header'][0]['posted_date'];
                $data['init_posted_time']   =   $data['invoice_header'][0]['posted_time'];
                if($data['invoice_header'][0]['status'] == "Complete"){
                    $data['doc_status'] =   "closed";
                }
            } //endif ($data['form_purpose'] == "new_invoice")
        } //endif(count($_POST))
        $data['clinic_info'] = $this->mthirra->get_clinic_info($data['init_location_id']);
        $data['details_withpo'] = $this->mpharma_rdb->get_drugsinvoice_details_withpo($data['supplier_invoice_id']);
        $data['details_nopo'] = $this->mpharma_rdb->get_drugsinvoice_details_nopo($data['supplier_invoice_id']);
        echo "init=".$data['init_with_po'];
        if($data['init_with_po'] == 't'){
            $data['invoice_details'] = $this->mpharma_rdb->get_drugsinvoice_details_withpo($data['supplier_invoice_id']);
        } else {
            $data['invoice_details'] = $this->mpharma_rdb->get_drugsinvoice_details_nopo($data['supplier_invoice_id']);
            echo "STOP";
        }
		$this->load->vars($data);
        // Run validation
		if ($this->form_validation->run('edit_report_body') == FALSE){
            // Return to incomplete form
            if ($_SESSION['thirra_mode'] == "ehr_mobile"){
                $new_header =   "ehr/header_xhtml-mobile10";
                $new_banner =   "ehr/banner_ehr_wap";
                $new_sidebar=   "ehr/sidebar_emr_reports_wap";
                //$new_body   =   "ehr/ehr_reports_edit_reportbody_wap";
                $new_body   =   "ehr/ehr_phar_edit_drugsupplier_invoice_html";
                $new_footer =   "ehr/footer_emr_wap";
            } else {
                //$new_header =   "ehr/header_xhtml1-strict";
                $new_header =   "ehr/header_xhtml1-transitional";
                $new_banner =   "ehr/banner_document_html";
                $new_sidebar=   "ehr/sidebar_emr_reports_html";
                $new_body   =   "ehr/ehr_phar_edit_drugsupplier_invoice_html";
                $new_footer =   "ehr/footer_emr_html";
            }
            if($data['user_rights']['section_reports'] < 100){
                $new_body   =   "ehr/ehr_access_denied_html";
            }
            $this->load->view($new_header);			
            $this->load->view($new_banner);			
            //$this->load->view($new_sidebar);			
            $this->load->view($new_body);			
            $this->load->view($new_footer);			
        } else {
            //echo "\nValidated successfully.";
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo "<br />Insert record";
            if($data['form_purpose'] == "new_invoice") {
                // Insert records
                $ins_body_array['report_body_id']   = $data['now_id'];
                $ins_body_array['report_header_id'] = $data['report_header_id'];
                $ins_body_array['location_id']  = $data['location_id'];
                //$ins_body_array['report_line']  = $data['init_report_line'];
                $ins_body_array['col_fieldname']= $data['init_col_fieldname'];
                //$ins_body_array['col_security'] = $data['init_col_security'];
                $ins_body_array['col_sort']     = $data['init_col_sort'];
                $ins_body_array['col_title1']   = $data['init_col_title1'];
                $ins_body_array['col_title2']   = $data['init_col_title2'];
                $ins_body_array['col_format']   = $data['init_col_format'];
                $ins_body_array['col_transform']= $data['init_col_transform'];
                if(is_numeric($data['init_col_widthmin'])){
                    $ins_body_array['col_widthmin']                = $data['init_col_widthmin'];
                }
                //$ins_body_array['col_widthmin'] = $data['init_col_widthmin'];
                if(is_numeric($data['init_col_widthmax'])){
                    $ins_body_array['col_widthmax']                = $data['init_col_widthmax'];
                }
                //$ins_body_array['col_widthmax'] = $data['init_col_widthmax'];
                $ins_body_data =   $this->mreport->insert_new_report_body($ins_body_array);
                $this->session->set_flashdata('data_activity', 'Report column added.');
            } else {
                // Update records
                $upd_body_array['report_body_id']      = $data['report_body_id'];
                //$upd_body_array['report_line']    = $data['init_report_line'];
                $upd_body_array['col_fieldname']    = $data['init_col_fieldname'];
                //$upd_body_array['col_security']    = $data['init_col_security'];
                $upd_body_array['col_sort']    = $data['init_col_sort'];
                $upd_body_array['col_title1']    = $data['init_col_title1'];
                $upd_body_array['col_title2']    = $data['init_col_title2'];
                $upd_body_array['col_format']    = $data['init_col_format'];
                $upd_body_array['col_transform']    = $data['init_col_transform'];
                if(is_numeric($data['init_col_widthmin'])){
                    $upd_body_array['col_widthmin']                = $data['init_col_widthmin'];
                }
                //$upd_body_array['col_widthmin']    = $data['init_col_widthmin'];
                if(is_numeric($data['init_col_widthmax'])){
                    $upd_body_array['col_widthmax']                = $data['init_col_widthmax'];
                }
                //$upd_body_array['col_widthmax']    = $data['init_col_widthmax'];
                $upd_body_data =   $this->mreport->update_report_body($upd_body_array);
                $this->session->set_flashdata('data_activity', 'Report column updated.');
            } //endif($data['form_purpose'] == "new_invoice")
            $new_page = base_url()."index.php/ehr_reports/reports_edit_reporthead/edit_report/".$data['report_header_id'];
            header("Status: 200");
            header("Location: ".$new_page);
        } //endif ($this->form_validation->run('edit_report') == FALSE)
        
    } // end of function phar_edit_drug_supplierinvoice($id)


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

/* End of file Ehr_pharmacy_supplier.php */
/* Location: ./app_thirra/controllers/Ehr_pharmacy_supplier.php */
