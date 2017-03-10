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
 * Controller Class for EHRL_REFER_CURL
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9.13
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class Ehr_refer_curl extends MY_Controller 
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
        /*
        // Redirect back to login page if not authenticated
		if ((! isset($_SESSION['user_acl'])) || ($_SESSION['user_acl'] < 1)){
            $flash_message  =   "Session Expired.";
            $new_page   =   base_url()."index.php/thirra";
            header("Status: 200");
            header("Location: ".$new_page);
        } // redirect to login page
        */
        $data['pics_url']      =    base_url();
        $data['pics_url']      =    substr_replace($data['pics_url'],'',-1);
        //$data['pics_url']      =    substr_replace($data['pics_url'],'',-7);
        $data['pics_url']      =    $data['pics_url']."-uploads/";
        define("PICS_URL", $data['pics_url']);
    }


    // ------------------------------------------------------------------------
    // === INDIVIDUAL RECORD
    // ------------------------------------------------------------------------
    /**
     * Response to a curl post by referring server. 
     *
     * Patient info is sent by referring server using curl.
     * Server responds with XML string containing lists.
     *
     * @author  Jason Tan Boon Teck
     */
	function check_patient_existence()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        
        $data['patient_name']      	    =   $this->input->post('patient_name');
        $data['birth_date']      	    =   $this->input->post('birth_date');
        $data['gender']      	        =   $this->input->post('gender');
        $data['substring_search']   =   trim($this->input->post('substring_search'));
        
        $data['name_filter']      	=   trim($this->input->post('patient_name'));
		$data['same_name'] = $this->memr_rdb->get_patients_list('all','name',$data['name_filter'],'name');
		$data['part_name'] = $this->memr_rdb->get_patients_list('all','name',$data['substring_search'],'name');
        /*
        echo "<strong>RETRIEVED</strong>";
        echo "<br />Patient Name : ".$data['patient_name'];
        echo "<br />Birth Date : ".$data['birth_date'];
        echo "<br />Sex : ".$data['gender'];
        echo "<br />substring_search : ".$data['substring_search'];
        echo "<pre>";
        echo "\n<br />same_name =";
        print_r($data['same_name']);
        echo "\n<br />part_name =";
        print_r($data['part_name']);
        echo "</pre>";
        */
        $xmlstr = "<?xml version='1.0'?>";
        //$xmlstr = "";
        $xmlstr .= "\n<THIRRA_check_existence>";
        $xmlstr .= "\n<arguments_sent>";
            $xmlstr .= "\n\t<patient_name>".$data['patient_name']."</patient_name>";
            $xmlstr .= "\n\t<birth_date>".$data['birth_date']."</birth_date>";
            $xmlstr .= "\n\t<gender>".$data['gender']."</gender>";
            $xmlstr .= "\n\t<substring_search>".$data['substring_search']."</substring_search>";
        $xmlstr .= "\n</arguments_sent>";
        $i  =   0;
        if(count($data['same_name']) > 0){
            $xmlstr .= "\n<exact_match>";
            foreach($data['same_name'] as $same_name){
                $xmlstr .= "\n\t<same-".$i.">";
                $xmlstr .= "\n\t\t<patient_id>".$same_name['patient_id']."</patient_id>";
                $xmlstr .= "\n\t\t<last_name>".$same_name['name']."</last_name>";
                $xmlstr .= "\n\t\t<name_first>".$same_name['name_first']."</name_first>";
                $xmlstr .= "\n\t\t<birth_date>".$same_name['birth_date']."</birth_date>";
                $xmlstr .= "\n\t\t<ic_no>".$same_name['ic_no']."</ic_no>";
                $xmlstr .= "\n\t</same-".$i.">";
                $i++;
            } //endforeach($data['same_name'] as $same_name)
            $xmlstr .= "\n</exact_match>";
        } else {
            $xmlstr .= "\n<exact_match />";
        } //endif(count($data['same_name']) > 0)
        $j  =   0;
        if(count($data['part_name']) > 0){
            $xmlstr .= "\n<partial_match>";
            foreach($data['part_name'] as $part_name){
                $xmlstr .= "\n\t<part-".$j.">";
                $xmlstr .= "\n\t\t<patient_id>".$part_name['patient_id']."</patient_id>";
                $xmlstr .= "\n\t\t<last_name>".$part_name['name']."</last_name>";
                $xmlstr .= "\n\t\t<name_first>".$part_name['name_first']."</name_first>";
                $xmlstr .= "\n\t\t<birth_date>".$part_name['birth_date']."</birth_date>";
                $xmlstr .= "\n\t\t<ic_no>".$part_name['ic_no']."</ic_no>";
                $xmlstr .= "\n\t</part-".$j.">";
                $j++;
            } //endforeach($data['part_name'] as $part_name)
            $xmlstr .= "\n</partial_match>";
        } else {
            $xmlstr .= "\n<partial_match />";
        } //endif(count($data['part_name']) > 0)
        $xmlstr .= "\n<check_stats>";
            $xmlstr .= "\n\t<exact_match>".$i."</exact_match>";
            $xmlstr .= "\n\t<partial_match>".$j."</partial_match>";
        $xmlstr .= "\n</check_stats>";
        $xmlstr .= "\n</THIRRA_check_existence>";
        echo $xmlstr;

        //$this->load->vars($data);
        /*
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
        */
		//$this->load->view($new_header);			
		//$this->load->view($new_banner);			
		//$this->load->view($new_sidebar);			
		//$this->load->view($new_body);			
		//$this->load->view($new_footer);		
        
	} // end of function check_patient_existence()
	

    // ------------------------------------------------------------------------
    /**
     * Response to a curl post submitting referral info
     *
     * Patient info is sent by referring server using curl.
     * Server responds with XML string containing lists.
     *
     * @author  Jason Tan Boon Teck
     */
	function receive_referral_info()
    {
        $data['offline_mode']		=	$this->config->item('offline_mode');
        $data['debug_mode']		    =	$this->config->item('debug_mode');
        
        $data['now_id']             =   time();
        $data['patient_name']      	=   $this->input->post('patient_name');
        $data['birth_date']      	=   $this->input->post('birth_date');
        $data['gender']      	    =   $this->input->post('gender');
        $data['xml_header']         =   "<?xml version='1.0'?>";
        $data['xml_refer']          =   $data['xml_header'].$_POST['xml_string'];//$this->input->post('xml_string');
        $data['string_refer']       =   $_POST['xml_string'];//$data['xml_refer'];
        $data['string_refer']       =   str_replace('<', '=', $data['xml_refer']);
        $data['xml_md5']      	    =   $this->input->post('xml_md5');
        $md5_xml_refer              =   md5($_POST['xml_string']);
        //echo "Computed md5=".$md5_xml_refer;
        
        //echo "\n<br />xml_string=".$this->input->post('xml_string');
        //echo "\n<br />xml_refer=".$data['xml_refer'];
        // Remote server returns an XML string
        $xml = simplexml_load_string($data['xml_refer']) or die("ERROR: Cannot create SimpleXML object");
        $data['xmlreply'] =   $xml;
        // Call xml_to_array() in xml helper
        $data['confirm_array'] =   xml_to_array($xml);    
        /*
        // Need to remove all empty elements of type array
        function array_filter_recursive($input){
            foreach ($input as &$value){
                if (is_array($value)){
                    $value = array_filter_recursive($value);
                }
            }
            return array_filter($input);
        }
        */
        $data['filtered_array'] = array_filter_recursive($data['confirm_array']);
        /*
		echo '<pre>';
            echo "simplexml=";
			print_r($xml);
            echo "<br />confirm_array=";
            print_r($data['confirm_array']);
            echo "<br />filtered_array=";
            print_r($data['filtered_array']);
            //print_r(array_filter($data['confirm_array']));
            //print_r(array_filter_recursive($data['confirm_array']));
		echo '</pre>';
        */
        // Insert XML string into staging table
        // Note: No sanity check is done yet.
        $ins_referin_array   =   array();
        $ins_referin_array['referin_staging_id'] = $data['now_id'];
        $ins_referin_array['staged_time']       = $data['now_id'];
        $ins_referin_array['staged_by']         = "1234567890"; // Should be Admin user
        $ins_referin_array['staged_reference']   = "1";
        $ins_referin_array['staged_sequence']    = 1;
        if(isset($data['filtered_array']['export_info']['thirra_version'])){
            $ins_referin_array['refer_version']       = $data['filtered_array']['export_info']['thirra_version'];
        }
        if(isset($data['filtered_array']['refer_info']['refer_to_person'])){
            $ins_referin_array['refer_to_person']     = $data['filtered_array']['refer_info']['refer_to_person'];
        }
        if(isset($data['filtered_array']['export_info']['refer_to_department'])){
            $ins_referin_array['refer_to_department']   = $data['filtered_array']['refer_info']['refer_to_department'];
        }
        if(isset($data['filtered_array']['export_info']['refer_to_specialty'])){
            $ins_referin_array['refer_to_specialty']    = $data['filtered_array']['refer_info']['refer_to_specialty'];
        }
        $ins_referin_array['refer_clinicname']   = $data['filtered_array']['export_info']['export_clinicname'];
        $ins_referin_array['refer_clinicid']     = $data['filtered_array']['export_info']['export_clinicid'];
        $ins_referin_array['refer_clinicref']    = $data['filtered_array']['export_info']['export_clinicref'];
        if(isset($data['filtered_array']['refer_info']['refer_by_person'])){
            $ins_referin_array['refer_by_person']       = $data['filtered_array']['refer_info']['refer_by_person'];
        }
        if(isset($data['filtered_array']['refer_info']['refer_by_position'])){
            $ins_referin_array['refer_by_position']     = $data['filtered_array']['refer_info']['refer_by_position'];
        }
        if(isset($data['filtered_array']['refer_info']['refer_by_department'])){
            $ins_referin_array['refer_by_department']   = $data['filtered_array']['refer_info']['refer_by_department'];
        }
        if(isset($data['filtered_array']['refer_info']['refer_by_specialty'])){
            $ins_referin_array['refer_by_specialty']    = $data['filtered_array']['refer_info']['refer_by_specialty'];
        }
        if(isset($data['filtered_array']['refer_info']['refer_staffno'])){
            $ins_referin_array['refer_staffno']      = $data['filtered_array']['refer_info']['refer_staffno'];
        }
        if(isset($data['filtered_array']['refer_info']['referral_reference'])){
            $ins_referin_array['refer_reference']    = $data['filtered_array']['refer_info']['referral_reference'];
        }
        if(isset($data['filtered_array']['refer_info']['refer_reason'])){
            $ins_referin_array['refer_reason']      = $data['filtered_array']['refer_info']['refer_reason'];
        }
        if(isset($data['filtered_array']['refer_info']['refer_clinical_exam'])){
            $ins_referin_array['refer_clinical_exam']      = $data['filtered_array']['refer_info']['refer_clinical_exam'];
        }
        if(isset($data['filtered_array']['refer_info']['refer_remarks'])){
            $ins_referin_array['refer_remarks']      = $data['filtered_array']['refer_info']['refer_remarks'];
        }
        $ins_referin_array['patient_id']         = $data['filtered_array']['patient_info']['rec_patient_id'];
        $ins_referin_array['patient_id_refer']   = $data['filtered_array']['patient_info']['patient_id'];
        if(isset($data['filtered_array']['patient_info']['patient_reference'])){
            $ins_referin_array['patient_reference']  = $data['confirm_array']['patient_info']['patient_reference'];
        }
        if(isset($data['filtered_array']['patient_info']['patient_pns_id'])){
            $ins_referin_array['patient_pns_id']     = $data['filtered_array']['patient_info']['patient_pns_id'];
        }
        if(isset($data['filtered_array']['patient_info']['patient_nhfa'])){
            $ins_referin_array['patient_nhfa']       = $data['filtered_array']['patient_info']['patient_nhfa'];
        }
        $ins_referin_array['patient_name']       = $data['filtered_array']['patient_info']['patient_name'];
        if(isset($data['filtered_array']['patient_info']['patient_name_first'])){
            $ins_referin_array['patient_name_first'] = $data['confirm_array']['patient_info']['patient_name_first'];
        }
        $ins_referin_array['patient_gender']     = $data['filtered_array']['patient_info']['gender'];
        if(isset($data['filtered_array']['patient_info']['patient_icno'])){
            $ins_referin_array['patient_icno']       = $data['filtered_array']['patient_info']['patient_icno'];
        }
        if(isset($data['filtered_array']['patient_info']['patient_icother_type'])){
            $ins_referin_array['patient_icother_type']= $data['filtered_array']['patient_info']['patient_icother_type'];
        }
        if(isset($data['filtered_array']['patient_info']['patient_icother_no'])){
            $ins_referin_array['patient_icother_no']= $data['filtered_array']['patient_info']['patient_icother_no'];
        }
        $ins_referin_array['patient_birthdate'] = $data['filtered_array']['patient_info']['birth_date'];
        $ins_referin_array['xmlin_string']        = $data['xml_refer'];//serialize($data['confirm_array']);
        $ins_referin_array['xmlin_mdhash']        = $data['xml_md5'];
        if($data['offline_mode']){
            $ins_referin_array['synch_out']   = $data['now_id'];
        }
        
        // Use these to test output of remote server, as it is difficult to determine the error messages.
        /*
        $ins_referin_array['testing_id'] = $data['now_id'];
        $ins_referin_array['testing1']       = $data['now_id'];
        $ins_referin_array['testing2']         = "1234567890"; // Should be Admin user
        $ins_referin_array['testing3']   = $ins_referin_array['patient_id'];
        $ins_referin_array['testing4']    = $ins_referin_array['patient_id_refer'];
        $ins_referin_array['testing5']   = $data['filtered_array']['export_info']['export_clinicname'];
        $ins_referin_data       =   $this->mrefer_wdb->insert_test_data($ins_referin_array);
        */
        $ins_referin_data       =   $this->mrefer_wdb->insert_new_referin_staging($ins_referin_array);

        // Construct XML confirmation response
        
        $xmlstr = "<?xml version='1.0'?>";
        
        //$xmlstr = "";
        $xmlstr .= "\n<THIRRA_referout_receipt>";
        $xmlstr .= "\n<info_sent>";
            $xmlstr .= "\n\t<patient_name>".$data['patient_name']."</patient_name>";
            $xmlstr .= "\n\t<birth_date>".$data['birth_date']."</birth_date>";
            $xmlstr .= "\n\t<gender>".$data['gender']."</gender>";
            $xmlstr .= "\n\t<export_clinicname>".$data['confirm_array']['export_info']['export_clinicname']."</export_clinicname>";
            $xmlstr .= "\n\t<xml_sent>".$data['string_refer']."</xml_sent>";
            $xmlstr .= "\n\t<xml_md5>".$data['xml_md5']."</xml_md5>";
            $xmlstr .= "\n\t<md5_xml_refer>".$md5_xml_refer."</md5_xml_refer>";
        $xmlstr .= "\n</info_sent>";
        $xmlstr .= "\n<edi_confirmation>";
            $xmlstr .= "\n\t<edi_no>".$ins_referin_array['referin_staging_id']."</edi_no>";
        $xmlstr .= "\n</edi_confirmation>";
        $xmlstr .= "\n</THIRRA_referout_receipt>";
        echo $xmlstr; // Send XML back to curler

        
	} // end of function receive_referral_info()
	

}

/* End of file Ehr_refer_curl.php */
/* Location: ./app_thirra/controllers/Ehr_refer_curl.php */
