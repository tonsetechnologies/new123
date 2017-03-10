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
 * Portions created by the Initial Developer are Copyright (C) 2009 - 2011
 * the Initial Developer and IDRC. All Rights Reserved.
 *
 * Contributor(s):
 *   Jason Tan Boon Teck <tanboonteck@gmail.com> (original author)
 *
 * ***** END LICENSE BLOCK ***** */

/*
 * <view file name><element>
 */

//======== HOME PAGE ==================================================
$lang['10150-100_index_html_title']		                      = "EHR Dashboard";
$lang['10150-200_index_html_div-patientqueue_title']		  = "Today's Patient Queue";
$lang['10150-210_index_html_div-patientqueue_th-time']		  = "Time";
$lang['10150-220_index_html_div-patientqueue_th-patientname'] = "Patient Name (DOB)";
$lang['10150-240_index_html_div-patientqueue_th-clinicrefno'] = "Ref.";
$lang['10150-230_index_html_div-patientqueue_th-consultant']  = "Consultant";
$lang['10150-240_index_html_div-patientqueue_th-priority']    = "Priority";
$lang['10150-250_index_html_div-patientqueue_th-remarks']     = "Remarks";
$lang['10150-250_index_html_div-patientqueue_th-room']        = "Room";
$lang['10150-300_index_html_div-postconqueue_title'] 		  = "Post Consultation Queue";
$lang['10150-400_index_html_div-laborders_title'] 		      = "Pending Lab Orders";
$lang['10150-800_index_html_div-opensession_title'] 		  = "Open Sessions";

$lang['ehr_access_denied_html_title']		            = "ACCESS DENIED";
//======== PATIENTS MANAGEMENT ========================================
$lang['10200-100_patients_mgt_html_title']		        = "PATIENTS MANAGEMENT";

$lang['patients_list_html_title']		                = "PATIENTS LIST";

$lang['patients_search_patient_html_title']		        = "SEARCH FOR PATIENT";

$lang['12000-100_patients_indv_overview_html_title']	= "PATIENT OVERVIEW";

$lang['12100-100_patients_edit_patient_html_title']	    = "DEMOGRAPHIC INFORMATION";

$lang['12900-100_patients_con_details_html_title']	    = "CONSULTATION DETAILS";

$lang['patients_ovv_list_family_cluster_html_title']	= "FAMILY CLUSTER";

$lang['patients_ovv_list_family_relations_html_title']	= "LIST OF FAMILY RELATIONSHIPS";

$lang['patients_edit_relationship_info_html_title']	    = "ADD/EDIT FAMILY RELATIONSHIP INFO";

$lang['patients_ovv_drug_allergies_html_title']	        = "DRUG ALLERGIES";

$lang['patients_ovv_history_vitals_html_title']	        = "VITAL SIGNS HISTORY";

$lang['patients_edit_history_vitals_html_title']	    = "ADD VITAL SIGNS HISTORY";

$lang['patients_ovv_history_diagnosis_html_title']	    = "MEDICAL HISTORY";

$lang['patients_ovv_history_medication_html_title']	    = "MEDICATION HISTORY";

$lang['patients_ovv_history_lab_html_title']	        = "LAB TESTS HISTORY";

$lang['patients_ovv_history_imaging_html_title']	    = "IMAGING TESTS HISTORY";

$lang['patients_ovv_history_social_html_title']	        = "SOCIAL HISTORY";

$lang['patients_edit_history_social_html_title']	    = "ADD/EDIT SOCIAL HISTORY";

$lang['patients_ovv_history_antenatal_html_title']	    = "ANTENATAL HISTORY";

$lang['patients_edit_history_antenatal_html_title']	    = "ADD/EDIT ANTENATAL HISTORY";

$lang['patients_edit_his_antenatal_followup_html_title']= "ADD/EDIT ANTENATAL CHECK-UP";

$lang['patients_edit_his_antenatal_delivery_html_title']= "ADD/EDIT ANTENATAL DELIVERY";

$lang['12800-100_patients_upload_ovv_html_title']	    = "UPLOAD FILE";

$lang['patients_ovv_patient_files_html_title']	        = "UPLOADED FILES";

$lang['patients_ovv_refer_out_html_title']	            = "OUTGOING REFERRALS";

$lang['patients_edit_refer_out_html_title']	            = "OUTGOING REFERRAL";

$lang['patients_draft_refer_out_email_html_title']	    = "DRAFT E-MAIL - OUTGOING REFERRAL";

$lang['patients_refer_patient_existence_html_title']	= "CHECK PATIENT'S EXISTENCE IN REMOTE SERVER";

$lang['patients_refer_out2server_confirm_html_title']	= "CONFIRM PATIENT TO REFER TO REMOTE SERVER";

$lang['patients_refer_out2server_done_html_title']	    = "SEND REFERRAL INFO TO REMOTE SERVER";

$lang['patients_ovv_externalmod_html_title']	        = "MODULES";

$lang['patients_ovv_gem_list_submodules_html_title']	= "SUBMODULES HISTORY";

$lang['patients_ovv_gem_history_submodule_html_title']	= "SUBMODULE HISTORY";

$lang['patients_refer_select_details_html_title']	    = "SELECT REFERRAL DETAILS FOR EXPORT";

//======== CLINICAL CONSULTATION ========================================
$lang['patients_indv_startconsult_html_title']	        = "NEW CONSULTATION";

$lang['patients_sidebar_consult_html_progress']	        = "PROGRESS";

$lang['patients_indv_consult_html_title']	            = "CONSULTATION - PROGRESS";

$lang['patients_edit_reason_encounter_html_title']      = "CONSULTATION - COMPLAINTS";

$lang['patients_edit_vitals_html_title']	            = "CONSULTATION - VITAL SIGNS";

$lang['patients_edit_physical_exam_html_title']	        = "CONSULTATION - PHYSICAL EXAMINATION";

$lang['patients_edit_lab_html_title']	                = "CONSULTATION - LAB ORDERS";

$lang['patients_edit_imaging_html_title']	            = "CONSULTATION - IMAGING ORDERS";

$lang['patients_edit_procedure_html_title']	            = "CONSULTATION - PROCEDURE ORDERS";

$lang['patients_edit_prediagnosis_html_title']	        = "PRE-DIAGNOSTIC OBSERVATIONS";

$lang['patients_edit_diagnosis_html_title']	            = "CONSULTATION - DIAGNOSIS";

$lang['patients_edit_diagnoses_html_title']	            = "CONSULTATION - DIAGNOSIS";

$lang['patients_edit_prescription_html_title']	        = "CONSULTATION - PRESCRIPTION";

$lang['patients_edit_prescribe_html_title']	            = "CONSULTATION - PRESCRIPTION";

$lang['patients_list_drug_packages_html_title']	        = "CONSULTATION - LIST DRUGS PACKAGE";

$lang['patients_edit_drug_package_html_title']	        = "CONSULTATION - PRESCRIBE DRUGS PACKAGE";

$lang['patients_edit_immune_prescribe_html_title']	    = "CONSULTATION - PRESCRIBE IMMUNISATION";

$lang['patients_edit_referral_html_title']	            = "CONSULTATION - REFERRAL";

$lang['patients_edit_upload_file_html_title']	        = "CONSULTATION - UPLOAD FILE";

$lang['patients_con_externalmod_html_title']	        = "CONSULTATION - MODULES";

$lang['patients_list_submodules_html_title']	        = "CONSULTATION - SUBMODULES";

$lang['patients_select_age_groups_html_title']	        = "CONSULTATION - SELECT AGE GROUP";

$lang['patients_edit_gem_consult_html_title']	        = "CONSULTATION - SUBMODULE";

$lang['patients_edit_antenatal_info_html_title']        = "CONSULTATION - ANTENATAL INFO";

$lang['patients_edit_antenatal_followup_html_title']    = "CONSULTATION - ANTENATAL FOLLOW-UP";

$lang['patients_edit_antenatal_delivery_html_title']    = "CONSULTATION - ANTENATAL DELIVERY";

$lang['patients_edit_antenatal_postpartum_html_title']  = "CONSULTATION - POSTPARTUM CARE";

$lang['patients_close_episode_html_title']	            = "CONSULTATION - END EPISODE";

//======== PHARMACY MANAGEMENT ========================================
$lang['pharmacy_mgt_html_title']		                = "PHARMACY MANAGEMENT";

$lang['phar_list_closed_prescriptions_html_title']	    = "CLOSED PRESCRIPTIONS";

$lang['phar_list_drugsuppliers_html_title']		        = "LIST OF DRUG SUPPLIERS";

$lang['phar_edit_drugsupplier_info_html_title']	        = "ADD/EDIT DRUG SUPPLIER";

$lang['phar_edit_drug_product_html_title']	            = "ADD/EDIT DRUG PRODUCT";

$lang['phar_list_drugsupplier_invoices_html_title']		= "LIST OF DRUG SUPPLIER INVOICES";

$lang['phar_edit_drugsupplier_invoice_html_title']		= "DRUG SUPPLIER INVOICE";

$lang['phar_list_drugs_packages_html_title']		    = "LIST OF DRUG PACKAGES";

$lang['phar_edit_drugs_package_html_title']		        = "ADD/EDIT DRUG PACKAGE";

$lang['phar_edit_package_drug_html_title']		        = "ADD/EDIT DRUG TO PACKAGE";

//======== ORDERS MANAGEMENT ==========================================
$lang['orders_mgt_html_title']			                = "ORDERS MANAGEMENT";

$lang['orders_edit_lab_results_html_title']	            = "RECORD LAB RESULTS";

$lang['orders_edit_imag_results_html_title']	        = "RECORD IMAGING RESULTS";

$lang['orders_list_closed_lab_results_html_title']	    = "CLOSED LAB RESULTS";

$lang['orders_print_lab_results_html_title']	        = "LAB REPORT";

$lang['orders_list_closed_imag_results_html_title']	    = "CLOSED IMAGING RESULTS";

$lang['orders_print_imag_results_html_title']	        = "IMAGING RESULTS";

$lang['orders_list_labsuppliers_html_title']		    = "LIST OF LAB SUPPLIERS";

$lang['orders_edit_labsupplier_info_html_title']	    = "ADD/EDIT LAB SUPPLIER";

$lang['orders_edit_lab_package_html_title']	            = "ADD/EDIT LAB PACKAGE";

$lang['orders_edit_lab_packagetest_html_title']	        = "ADD/EDIT LAB PACKAGE TEST";

$lang['orders_list_imagsuppliers_html_title']		    = "LIST OF IMAGING SUPPLIERS";

$lang['orders_edit_imagsupplier_info_html_title']	    = "ADD/EDIT IMAGING SUPPLIER";

$lang['orders_edit_imag_product_html_title']	        = "ADD/EDIT IMAGING PRODUCT";

//======== QUEUE MANAGEMENT ===========================================
$lang['queue_mgt_html_title']			                = "QUEUE MANAGEMENT";

$lang['queue_edit_queue_html_title']			        = "ADD/EDIT QUEUE";

$lang['queue_edit_room_html_title']			            = "ADD/EDIT ROOM";

//======== REPORTS MANAGEMENT =========================================
$lang['reports_mgt_html_title']			                = "REPORTS MANAGEMENT";

$lang['reports_edit_reporthead_html_title']		        = "ADD/EDIT REPORT TEMPLATE HEADER";

$lang['reports_edit_reportbody_html_title']		        = "ADD/EDIT REPORT TEMPLATE BODY";

$lang['reports_select_report_html_title']		        = "DEFINE REPORT PARAMETERS";

$lang['reports_print_report_html_title']		        = "THIRRA Report";

//======== UTILITIES MANAGEMENT =======================================
$lang['utilities_mgt_html_title']		                = "UTILITIES MANAGEMENT";

$lang['util_list_addrvillages_html_title']		        = "LIST OF VILLAGES";

$lang['util_edit_village_info_html_title']		        = "ADD/EDIT VILLAGE";

$lang['util_list_addrtowns_html_title']		            = "LIST OF TOWNS";

$lang['util_edit_town_info_html_title']		            = "ADD/EDIT TOWN";

$lang['util_list_addrareas_html_title']		            = "LIST OF AREAS";

$lang['util_edit_area_info_html_title']		            = "ADD/EDIT AREA";

$lang['util_list_addrdistricts_html_title']		        = "LIST OF DISTRICTS";

$lang['util_edit_district_info_html_title']		        = "ADD/EDIT DISTRICT";

$lang['util_list_addrstates_html_title']		        = "LIST OF STATES";

$lang['util_edit_state_info_html_title']		        = "ADD/EDIT STATE";

$lang['util_list_complaint_codes_html_title']		    = "LIST OF ICPC-2 COMPLAINT CODES";

$lang['util_edit_complaint_code_html_title']		    = "EDIT ICPC-2 COMPLAINT CODE";

$lang['util_list_diagnosiscodes_html_title']		    = "LIST OF DIAGNOSIS CODES";

$lang['util_edit_diagnosis_info_html_title']            = "ADD/EDIT DIAGNOSIS CODE";

$lang['util_list_diagnosisext_codes_html_title']        = "LIST OF DIAGNOSIS CODES (EXTENDED)";

$lang['util_edit_diagnosisext_info_html_title']         = "ADD/EDIT DIAGNOSIS CODE (EXTENDED)";

$lang['util_list_drugatc_html_title']		            = "LIST OF DRUGS ATC CODES";

$lang['util_edit_drugatc_html_title']		            = "ADD/EDIT ATC DRUG CODES";

$lang['util_list_drugformulary_html_title']		        = "LIST OF DRUG FORMULARIES";

$lang['util_edit_drugformulary_html_title']		        = "ADD/EDIT DRUG FORMULARY";

$lang['util_list_drugcodes_html_title']		            = "LIST OF DRUG CODES";

$lang['util_edit_drugcode_html_title']		            = "ADD/EDIT DRUG CODE";

$lang['util_list_immunisation_codes_html_title']	    = "LIST OF IMMUNISATION CODES";

$lang['util_edit_immunisation_code_html_title']		    = "EDIT IMMUNISATION CODE";

//======== ADMIN MANAGEMENT ===========================================
$lang['admin_mgt_html_title']			                = "ADMIN MANAGEMENT";

$lang['admin_list_systemusers_html_title']			    = "LIST OF SYSTEM USERS";

$lang['admin_edit_systemuser_html_title']			    = "ADD/EDIT SYSTEM USER";

$lang['admin_list_staffcategories_html_title']		    = "LIST OF STAFF CATEGORIES";

$lang['admin_edit_staff_category_html_title']	        = "ADD/EDIT STAFF CATEGORY";

$lang['admin_list_referral_centres_html_title']		    = "LIST OF REFERRAL CENTRES";

$lang['admin_list_clinics_html_title']		            = "LIST OF CLINICS";

$lang['admin_edit_clinic_info_html_title']		        = "ADD/EDIT CLINICS";

$lang['admin_list_depts_html_title']		            = "LIST OF DEPARTMENTS";

$lang['admin_edit_clinic_dept_html_title']		        = "ADD/EDIT DEPARTMENT";

$lang['admin_edit_referral_centre_html_title']		    = "ADD/EDIT REFERRAL CENTRE";

$lang['admin_edit_referral_person_html_title']		    = "ADD/EDIT REFERRAL PERSON";

$lang['admin_export_logins_html_title']			        = "EXPORT LOGINS DATA";

$lang['admin_export_new_logins_done_html_title']	    = "EXPORTED NEW LOGINS DATA";

$lang['admin_export_patients_html_title']			    = "EXPORT PATIENTS DATA";

$lang['admin_export_new_patients_html_title']		    = "EXPORT NEW PATIENTS DATA";

$lang['admin_export_new_patientsdone_html_title']	    = "EXPORTED NEW PATIENTS DATA";

$lang['admin_list_open_episodes_html_title']			= "LIST OF OPEN EPISODES";

$lang['admin_export_episodes_html_title']			    = "EXPORT EPISODES DATA";

$lang['admin_export_new_episodes_html_title']		    = "EXPORT NEW EPISODES DATA";

$lang['admin_export_new_episodesdone_html_title']	    = "EXPORTED NEW EPISODES DATA";

$lang['admin_export_antenatalinfo_html_title']			= "EXPORT ANTENATAL INFO DATA";

$lang['admin_export_new_antenatalinfo_html_title']		= "EXPORT NEW ANTENATAL INFO DATA";

$lang['admin_export_new_antenatalinfo_done_html_title']	= "EXPORTED NEW ANTENATAL INFO DATA";

$lang['admin_export_antenatalcheckup_html_title']		= "EXPORT ANTENATAL CHECKUP DATA";

$lang['admin_export_new_antenatalcheckup_html_title']	= "EXPORT NEW ANTENATAL CHECKUP DATA";

$lang['admin_export_new_antenatalcheckup_done_html_title']	= "EXPORTED NEW ANTENATAL CHECKUP DATA";

$lang['admin_export_historyimmunisation_html_title']		= "EXPORT IMMUNISATION HISTORIES DATA";

$lang['admin_export_new_historyimmunisation_html_title']	= "EXPORT NEW IMMUNISATION HISTORIES DATA";

$lang['admin_export_new_historyimmunisation_done_html_title']	= "EXPORTED NEW IMMUNISATION HISTORIES DATA";

$lang['admin_export_antenataldelivery_html_title']		= "EXPORT ANTENATAL DELIVERY DATA";

$lang['admin_export_new_antenataldelivery_html_title']	= "EXPORT NEW ANTENATAL DELIVERY DATA";

$lang['admin_export_new_antenataldelivery_done_html_title']	= "EXPORTED NEW ANTENATAL DELIVERY DATA";

$lang['admin_import_logins_html_title']			        = "IMPORT LOGINS DATA";

$lang['admin_import_new_logins_html_title']		        = "IMPORT NEW LOGINS DATA";

$lang['admin_import_patients_html_title']			    = "IMPORT PATIENTS DATA";

$lang['admin_import_new_patients_html_title']		    = "IMPORT NEW PATIENTS DATA";

$lang['admin_import_episodes_html_title']			    = "IMPORT EPISODES DATA";

$lang['admin_import_new_episodes_html_title']		    = "IMPORT NEW EPISODES DATA";

$lang['admin_import_antenatalinfo_html_title']			= "IMPORT ANTENATAL INFO DATA";

$lang['admin_import_new_antenatalinfo_html_title']		= "IMPORT NEW ANTENATAL INFO DATA";

$lang['admin_import_antenatalcheckup_html_title']	    = "IMPORT ANTENATAL CHECKUP DATA";

$lang['admin_import_new_antenatalcheckup_html_title']	= "IMPORT NEW ANTENATAL CHECKUP DATA";

$lang['admin_import_antenataldelivery_html_title']		= "IMPORT ANTENATAL DELIVERY DATA";

$lang['admin_import_new_antenataldelivery_html_title']	= "IMPORT NEW ANTENATAL DELIVERY DATA";

$lang['admin_import_historyimmunisation_html_title']	= "IMPORT IMMUNISATION HISTORIES DATA";

$lang['admin_import_new_historyimmunisation_html_title']= "IMPORT NEW IMMUNISATION HISTORIES DATA";

$lang['admin_import_refer_html_title']			        = "IMPORT REFERRAL DATA";

$lang['admin_import_new_refer_html_title']		        = "IMPORT NEW REFERRAL DATA";

$lang['admin_import_new_referreview_html_title']		= "REVIEW NEW REFERRAL DATA";

$lang['admin_reset_synchflags_html_title']			    = "RESET SYNCH FLAGS";

$lang['admin_reset_synchflagsdone_html_title']			= "COMPLETED RESET SYNCH FLAGS";

$lang['admin_list_synchlogs_html_title']			    = "LIST OF SYNCH LOGS";

/* End of file ehr_lang.php */
/* Location: ./app_thirra/language/english/ehr_lang.php */
