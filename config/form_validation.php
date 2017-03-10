<?php
$config = array(
                 'edit_case' => array(
                                    array(
                                            'field' => 'case_ref',
                                            'label' => 'Case Reference',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'case_start_date',
                                            'label' => 'Case Start Date',
                                            'rules' => 'trim|required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'case_location_isolation',
                                            'label' => 'Isolation location',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_inv' => array(
                                    array(
                                            'field' => 'inv_ref',
                                            'label' => 'Investigation Reference',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'inv_main_name',
                                            'label' => 'Interviewee',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'inv_main_contacttype',
                                            'label' => 'Contact Type',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'inv_cluster_size',
                                            'label' => 'Cluster Size',
                                            'rules' => 'trim|integer'
                                         ),
                                    array(
                                            'field' => 'inv_gps_lat',
                                            'label' => 'GPS Latitude',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'inv_gps_long',
                                            'label' => 'GPS Longitude',
                                            'rules' => 'trim|numeric'
                                         )
                                    ),
                 'edit_notify' => array(
                                    array(
                                            'field' => 'dcode1ext_code',
                                            'label' => 'Diagnosis',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'notify_date',
                                            'label' => 'Notification Date',
                                            'rules' => 'trim|required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'visit_date',
                                            'label' => 'Admission Date',
                                            'rules' => 'trim|required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'onset_date',
                                            'label' => 'Date of Onset',
                                            'rules' => 'trim|required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'notify_ref',
                                            'label' => 'MOH Notification No.',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'bht_no',
                                            'label' => 'BHT No.',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'room_id',
                                            'label' => 'Ward',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_patient' => array(
                                    array(
                                            'field' => 'patient_name',
                                            'label' => 'Patient Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'gender',
                                            'label' => 'Sex',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'birth_date',
                                            'label' => 'Birth Date',
                                            'rules' => 'trim|required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'age',
                                            'label' => 'Age',
                                            'rules' => 'trim|numeric|required'
                                         ),
                                    array(
                                            'field' => 'addr_village_id',
                                            'label' => 'Village',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         )                                    
                                    ),
                 'edit_patient_unique_refno' => array(
                                    array(
                                            'field' => 'patient_name',
                                            'label' => 'Patient Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'gender',
                                            'label' => 'Sex',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'clinic_reference_number',
                                            'label' => 'clinic_reference_number',
                                            'rules' => 'trim|required|callback_cb_unique[patient_demographic_info.clinic_reference_number]'
                                         ),
                                    array(
                                            'field' => 'birth_date',
                                            'label' => 'Birth Date',
                                            'rules' => 'trim|required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'age',
                                            'label' => 'Age',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'addr_village_id',
                                            'label' => 'Village, Town and Area',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         )                                    ),
                 'edit_relationship_info' => array(
                                    array(
                                            'field' => 'relationship_id',
                                            'label' => 'relationship_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'family_position',
                                            'label' => 'family_position',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'generation_to_head',
                                            'label' => 'generation_to_head',
                                            'rules' => 'numeric'
                                         ),
                                    array(
                                            'field' => 'head_id',
                                            'label' => 'head_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_drug_allergy' => array(
                                    array(
                                            'field' => 'patient_drug_allergy_id',
                                            'label' => 'patient_drug_allergy_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'reaction',
                                            'label' => 'Reaction',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'added_remarks',
                                            'label' => 'Remarks',
                                            'rules' => ''
                                         ),
                                    array(
                                            'field' => 'drug_code_id',
                                            'label' => 'drug_code_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_referout_response' => array(
                                    array(
                                            'field' => 'referral_id',
                                            'label' => 'referral_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'replying_doctor',
                                            'label' => 'Replying doctor',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'date_replied',
                                            'label' => 'Response date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         )
                                    ),
                 'edit_referout_email' => array(
                                    array(
                                            'field' => 'button_send',
                                            'label' => 'Clicking Send',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'referral_email',
                                            'label' => 'Email Address',
                                            'rules' => 'trim|required|valid_email'
                                         ) ,                                   
                                    array(
                                            'field' => 'cc_email',
                                            'label' => 'Email Address',
                                            'rules' => 'trim|valid_email'
                                         )                                    
                                    ),
                 'edit_history_immune' => array(
                                    array(
                                            'field' => 'vaccine_id',
                                            'label' => 'Vaccine',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'vaccine_date',
                                            'label' => 'Vaccination Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         )
                                    ),
                 'edit_history_social' => array(
                                    array(
                                            'field' => 'social_history_id',
                                            'label' => 'social_history_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'record_date',
                                            'label' => 'Record Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         )
                                    ),
                 'edit_history_antenatal_info' => array(
                                    array(
                                            'field' => 'gravida',
                                            'label' => 'Gravida',
                                            'rules' => 'required|numeric'
                                         ),
                                    array(
                                            'field' => 'para',
                                            'label' => 'Para',
                                            'rules' => 'required|numeric'
                                         ),
                                    array(
                                            'field' => 'lmp_edd',
                                            'label' => 'EDD',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'record_date',
                                            'label' => 'Record Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         )
                                    ),
                 'edit_episode' => array(
                                    array(
                                            'field' => 'summary_id',
                                            'label' => 'summary id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'date_started',
                                            'label' => 'date_started',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'time_started',
                                            'label' => 'time_started',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'date_ended',
                                            'label' => 'date_ended',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'time_ended',
                                            'label' => 'time_ended',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_complaint' => array(
                                    array(
                                            'field' => 'complaint_id',
                                            'label' => 'complaint id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'complaintCode',
                                            'label' => 'complaintCode',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'level3',
                                            'label' => 'level3',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_vitals' => array(
                                    array(
                                            'field' => 'reading_date',
                                            'label' => 'Reading Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'height',
                                            'label' => 'height',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'weight',
                                            'label' => 'weight',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'temperature',
                                            'label' => 'temperature',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'pulse_rate',
                                            'label' => 'pulse_rate',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'bmi',
                                            'label' => 'bmi',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'waist_circumference',
                                            'label' => 'waist_circumference',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'bp_systolic',
                                            'label' => 'bp_systolic',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'bp_diastolic',
                                            'label' => 'bp_diastolic',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'respiration_rate',
                                            'label' => 'respiration_rate',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'ofc',
                                            'label' => 'ofc',
                                            'rules' => 'trim|numeric'
                                         )
                                    ),
                 'edit_physical_exam' => array(
                                    array(
                                            'field' => 'physical_exam_id',
                                            'label' => 'physical_exam_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'pulse_rate',
                                            'label' => 'pulse_rate',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'chest_measurement_in',
                                            'label' => 'chest_measurement_in',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'chest_measurement_out',
                                            'label' => 'chest_measurement_out',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'abdominal_girth',
                                            'label' => 'abdominal_girth',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'notes',
                                            'label' => 'notes',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_lab_order' => array(
                                    array(
                                            'field' => 'lab_order_id',
                                            'label' => 'lab_order id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'sample_date',
                                            'label' => 'Sample Collection Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'sample_ref',
                                            'label' => 'Sample Ref.',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_imaging_order' => array(
                                    array(
                                            'field' => 'order_id',
                                            'label' => 'order id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'order_ref',
                                            'label' => 'Order Ref.',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_diagnosis' => array(
                                    array(
                                            'field' => 'diagnosis_id',
                                            'label' => 'Diagnosis id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'diagnosis',
                                            'label' => 'Diagnosis',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'diagnosis_type',
                                            'label' => 'diagnosis_type',
                                            'rules' => ''
                                         ),
                                    array(
                                            'field' => 'diagnosis_notes',
                                            'label' => 'Diagnosis Notes',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_prescribe' => array(
                                    array(
                                            'field' => 'dose',
                                            'label' => 'Dose',
                                            'rules' => 'trim|numeric|required'
                                         ),
                                    array(
                                            'field' => 'dose_form',
                                            'label' => 'dose_form',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'frequency',
                                            'label' => 'frequency',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'instruction',
                                            'label' => 'instruction',
                                            'rules' => ''
                                         ),
                                    array(
                                            'field' => 'quantity',
                                            'label' => 'Quantity',
                                            'rules' => 'trim|numeric|required'
                                         ),
                                    array(
                                            'field' => 'drug_formulary_id',
                                            'label' => 'drug_formulary_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_referral' => array(
                                    array(
                                            'field' => 'referral_id',
                                            'label' => 'referral id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'referral_date',
                                            'label' => 'Referral Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'referral_doctor_id',
                                            'label' => 'Referral Doctor',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'reason',
                                            'label' => 'Reason',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_consult_gem' => array(
                                    array(
                                            'field' => 'gem_submod_id',
                                            'label' => 'gem_submod_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'summary_id',
                                            'label' => 'summary_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_antenatal_info' => array(
                                    array(
                                            'field' => 'gravida',
                                            'label' => 'Gravida',
                                            'rules' => 'required|numeric'
                                         ),
                                    array(
                                            'field' => 'para',
                                            'label' => 'Para',
                                            'rules' => 'required|numeric'
                                         ),
                                    array(
                                            'field' => 'lmp',
                                            'label' => 'LMP',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'lmp_edd',
                                            'label' => 'EDD',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'record_date',
                                            'label' => 'Record Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         )
                                    ),
                 'edit_antenatal_followup' => array(
                                    array(
                                            'field' => 'pregnancy_duration',
                                            'label' => 'Pregnancy Duration',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'notes',
                                            'label' => 'Notes',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'record_date',
                                            'label' => 'Record Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         )
                                    ),
                 'edit_antenatal_delivery' => array(
                                    array(
                                            'field' => 'date_delivery',
                                            'label' => 'date_delivery',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'delivery_outcome',
                                            'label' => 'Delivery outcome',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'baby_condition',
                                            'label' => 'Baby condition',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'delivery_type',
                                            'label' => 'Delivery type',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'antenatal_delivery_id',
                                            'label' => 'antenatal_delivery_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_antenatal_postpartum' => array(
                                    array(
                                            'field' => 'termination_date',
                                            'label' => 'Termination date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'care_date',
                                            'label' => 'Visit Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'breastfeed',
                                            'label' => 'Breastfeed',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'fever_38',
                                            'label' => 'Fever',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'pallor',
                                            'label' => 'pallor',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_drug_supplier' => array(
                                    array(
                                            'field' => 'supplier_id',
                                            'label' => 'supplier id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'supplier_name',
                                            'label' => 'Supplier Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'credit_term',
                                            'label' => 'Credit Term',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'e-mail',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'edit_drug_product' => array(
                                    array(
                                            'field' => 'product_id',
                                            'label' => 'product id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'supplier_id',
                                            'label' => 'Supplier id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'drug_code_id',
                                            'label' => 'drug_code_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'product_name',
                                            'label' => 'Product_name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'packing',
                                            'label' => 'Packing size',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'packing_form',
                                            'label' => 'Packing form',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'e-mail',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'edit_drug_package' => array(
                                    array(
                                            'field' => 'drug_package_id',
                                            'label' => 'drug_package id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'package_name',
                                            'label' => 'Package Name',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_lab_result' => array(
                                    array(
                                            'field' => 'summary_result',
                                            'label' => 'Results Summary',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'result_date',
                                            'label' => 'Results Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         )
                                    ),
                 'edit_imag_result' => array(
                                    array(
                                            'field' => 'notes',
                                            'label' => 'Notes',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'result_date',
                                            'label' => 'Results Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         )
                                    ),
                 'edit_labsupplier' => array(
                                    array(
                                            'field' => 'supplier_id',
                                            'label' => 'supplier id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'supplier_name',
                                            'label' => 'Supplier Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'credit_term',
                                            'label' => 'Credit Term',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'e-mail',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'edit_lab_package' => array(
                                    array(
                                            'field' => 'lab_package_id',
                                            'label' => 'lab_package_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'package_name',
                                            'label' => 'Package Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'package_code',
                                            'label' => 'Package Code',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'commonly_used',
                                            'label' => 'commonly_used',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'lab_classification_id',
                                            'label' => 'Lab Classification',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'loinc_class_id',
                                            'label' => 'LOINC Class',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'lab_filter_youngerthan',
                                            'label' => 'lab_filter_youngerthan',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'lab_filter_olderthan',
                                            'label' => 'lab_filter_olderthan',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'supplier_cost',
                                            'label' => 'supplier_cost',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'retail_price_1',
                                            'label' => 'retail_price_1',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'retail_price_2',
                                            'label' => 'retail_price_2',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'retail_price_3',
                                            'label' => 'retail_price_3',
                                            'rules' => 'trim|numeric'
                                         )
                                    ),
                 'edit_lab_packagetest' => array(
                                    array(
                                            'field' => 'lab_package_test_id',
                                            'label' => 'supplier id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'sort_test',
                                            'label' => 'Sort Order',
                                            'rules' => 'trim|numeric|required'
                                         ),
                                    array(
                                            'field' => 'test_name',
                                            'label' => 'Test Name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'test_code',
                                            'label' => 'Test Code',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'loinc_num',
                                            'label' => 'LOINC',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_imagsupplier' => array(
                                    array(
                                            'field' => 'supplier_id',
                                            'label' => 'supplier id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'supplier_name',
                                            'label' => 'Supplier Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'credit_term',
                                            'label' => 'Credit Term',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'e-mail',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'edit_imag_product' => array(
                                    array(
                                            'field' => 'product_id',
                                            'label' => 'product id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'supplier_id',
                                            'label' => 'supplier id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'product_code',
                                            'label' => 'Product Code',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'description',
                                            'label' => 'Description',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'supplier_cost',
                                            'label' => 'Supplier Cost',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'retail_price',
                                            'label' => 'Retail Price',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'retail_price_2',
                                            'label' => 'Retail Price 2',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'retail_price_3',
                                            'label' => 'Retail Price 3',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'commonly_used',
                                            'label' => 'Sort Order',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'loinc_num',
                                            'label' => 'LOINC',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_queue' => array(
                                    array(
                                            'field' => 'patient_id',
                                            'label' => 'Patient',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'staff_id',
                                            'label' => 'Consultant',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'room_id',
                                            'label' => 'Room',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'bp_diastolic',
                                            'label' => 'bp_diastolic',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'queue_date',
                                            'label' => 'Queue Date',
                                            'rules' => 'required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'start_time',
                                            'label' => 'Start Time',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_room' => array(
                                    array(
                                            'field' => 'room_name',
                                            'label' => 'Room Name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'clinic_dept_id',
                                            'label' => 'Department',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'category_id',
                                            'label' => 'Room Category',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'room_rate1',
                                            'label' => 'Room Rate',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'room_rate2',
                                            'label' => 'Room Rate',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'room_rate3',
                                            'label' => 'Room Rate',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'room_cost',
                                            'label' => 'Room Cost',
                                            'rules' => 'trim|numeric'
                                         ),
                                    ),
                 'edit_report_param' => array(
                                    array(
                                            'field' => 'report_header_id',
                                            'label' => 'report_header_id',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'period_from',
                                            'label' => 'period_from',
                                            'rules' => 'trim|required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'period_to',
                                            'label' => 'period_to',
                                            'rules' => 'trim|required|callback_cb_correct_date'
                                         ),
                                    array(
                                            'field' => 'output_format',
                                            'label' => 'output_format',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_report_header' => array(
                                    array(
                                            'field' => 'report_header_id',
                                            'label' => 'report_header id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'report_name',
                                            'label' => 'Report long name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'report_shortname',
                                            'label' => 'Report short name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'report_title1',
                                            'label' => 'report_title1',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'report_source',
                                            'label' => 'report_source',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'report_sort',
                                            'label' => 'report_sort',
                                            'rules' => 'trim|numeric'
                                         )
                                    ),
                 'edit_report_body' => array(
                                    array(
                                            'field' => 'report_body_id',
                                            'label' => 'report_body id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'report_header_id',
                                            'label' => 'report_header_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'col_fieldname',
                                            'label' => 'col_fieldname',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'col_title1',
                                            'label' => 'col_title1',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'col_sort',
                                            'label' => 'col_sort',
                                            'rules' => 'trim|numeric'
                                         )
                                    ),
                 'edit_addr_village' => array(
                                    array(
                                            'field' => 'addr_village_id',
                                            'label' => 'addr_village_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_village_sort',
                                            'label' => 'Sort Order',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'addr_village_name',
                                            'label' => 'Village Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'addr_village_code',
                                            'label' => 'Village Code',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_village_email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         ),
                                    array(
                                            'field' => 'addr_area_id',
                                            'label' => 'addr_area_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_addr_town' => array(
                                    array(
                                            'field' => 'addr_town_id',
                                            'label' => 'addr_town_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_town_sort',
                                            'label' => 'Sort Order',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'addr_town_name',
                                            'label' => 'Town Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'addr_town_code',
                                            'label' => 'Town Code',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_town_email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         ),
                                    array(
                                            'field' => 'addr_area_id',
                                            'label' => 'addr_area_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_addr_area' => array(
                                    array(
                                            'field' => 'addr_area_id',
                                            'label' => 'addr_area_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_area_sort',
                                            'label' => 'Sort Order',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'addr_area_name',
                                            'label' => 'Area Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'addr_area_code',
                                            'label' => 'Area Code',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_area_email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         ),
                                    array(
                                            'field' => 'addr_district_id',
                                            'label' => 'addr_district_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_addr_district' => array(
                                    array(
                                            'field' => 'addr_district_id',
                                            'label' => 'addr_district_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_district_sort',
                                            'label' => 'Sort Order',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'addr_district_name',
                                            'label' => 'Area Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'addr_district_code',
                                            'label' => 'Area Code',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_district_email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         ),
                                    array(
                                            'field' => 'addr_state_id',
                                            'label' => 'addr_state_id',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_addr_state' => array(
                                    array(
                                            'field' => 'addr_state_id',
                                            'label' => 'addr_state_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_state_sort',
                                            'label' => 'Sort Order',
                                            'rules' => 'trim|numeric'
                                         ),
                                    array(
                                            'field' => 'addr_state_name',
                                            'label' => 'State Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'addr_state_code',
                                            'label' => 'State Code',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'addr_state_email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'edit_complaint_code' => array(
                                    array(
                                            'field' => 'icpc_code',
                                            'label' => 'ICPC-2 Code',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'full_description',
                                            'label' => 'Full description',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'short_description',
                                            'label' => 'Short description',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_diagnosis_code' => array(
                                    array(
                                            'field' => 'dcode1_id',
                                            'label' => 'dcode1_id',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'dcode1set',
                                            'label' => 'dcode1set',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'dcode1_code',
                                            'label' => 'dcode1 code',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'full_title',
                                            'label' => 'Full title',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'short_title',
                                            'label' => 'Short title',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_diagnosisext_code' => array(
                                    array(
                                            'field' => 'dcode1ext_id',
                                            'label' => 'dcode1ext_id',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'dcode1_id',
                                            'label' => 'dcode1_id',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'dcode1set',
                                            'label' => 'dcode1set',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'dcode1ext_code',
                                            'label' => 'dcode1ext_code',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'dcode1ext_longname',
                                            'label' => 'dcode1ext_longname',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'dcode1ext_shortname',
                                            'label' => 'dcode1ext_shortname',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'full_title',
                                            'label' => 'full_title',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'short_title',
                                            'label' => 'short_title',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_drug_atc' => array(
                                    array(
                                            'field' => 'part_atc_code',
                                            'label' => 'ATC code',
                                            'rules' => 'numeric|required'
                                         ),
                                    array(
                                            'field' => 'atc_code',
                                            'label' => 'ATC code',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'atc_name',
                                            'label' => 'ATC name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'atc_chemical',
                                            'label' => 'ATC chemical',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_drug_formulary' => array(
                                    array(
                                            'field' => 'drug_formulary_id',
                                            'label' => 'drug_formulary id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'part_formulary_code',
                                            'label' => 'Formulary code',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'atc_code',
                                            'label' => 'ATC code',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'generic_name',
                                            'label' => 'Generic name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'formulary_system',
                                            'label' => 'Formulary System',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_drug_code' => array(
                                    array(
                                            'field' => 'drug_code_id',
                                            'label' => 'drug_code id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'part_drug_code',
                                            'label' => 'Drug code',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'drug_formulary_id',
                                            'label' => 'Generic name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'trade_name',
                                            'label' => 'Trade name',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_immunisation_code' => array(
                                    array(
                                            'field' => 'immunisation_id',
                                            'label' => 'immunisation_id',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'vaccine_short',
                                            'label' => 'Short name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'vaccine',
                                            'label' => 'Vaccine',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'dose',
                                            'label' => 'Dose',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'immunisation_sort',
                                            'label' => 'Sort order',
                                            'rules' => 'numeric|required'
                                         )
                                    ),
                 'edit_systemuser' => array(
                                    array(
                                            'field' => 'username',
                                            'label' => 'Username',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'password1',
                                            'label' => 'Password',
                                            'rules' => 'trim|matches[password2]'
                                         ),
                                    array(
                                            'field' => 'password2',
                                            'label' => 'Password Confirmation',
                                            'rules' => 'trim'
                                         ),
                                    array(
                                            'field' => 'expiry_date',
                                            'label' => 'Access expiry date',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'staff_name',
                                            'label' => 'staff_name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'staff_initials',
                                            'label' => 'staff_initials',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'new_systemuser' => array(
                                    array(
                                            'field' => 'username',
                                            'label' => 'Username',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'password1',
                                            'label' => 'Password',
                                            'rules' => 'trim|matches[password2]'
                                         ),
                                    array(
                                            'field' => 'password2',
                                            'label' => 'Password Confirmation',
                                            'rules' => 'trim'
                                         ),
                                    array(
                                            'field' => 'expiry_date',
                                            'label' => 'Access expiry date',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'staff_name',
                                            'label' => 'staff_name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'staff_initials',
                                            'label' => 'staff_initials',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'edit_systemuser' => array(
                                    array(
                                            'field' => 'username',
                                            'label' => 'Username',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'password1',
                                            'label' => 'Password',
                                            'rules' => 'trim|matches[password2]'
                                         ),
                                    array(
                                            'field' => 'password2',
                                            'label' => 'Password Confirmation',
                                            'rules' => 'trim'
                                         ),
                                    array(
                                            'field' => 'expiry_date',
                                            'label' => 'Access expiry date',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'staff_name',
                                            'label' => 'staff_name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'staff_initials',
                                            'label' => 'staff_initials',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'edit_staff_category' => array(
                                    array(
                                            'field' => 'category_name',
                                            'label' => 'Category Name',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'description',
                                            'label' => 'Description',
                                            'rules' => 'required'
                                         )
                                    ),
                 'edit_clinic_info' => array(
                                    array(
                                            'field' => 'clinic_name',
                                            'label' => 'Clinic Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'clinic_shortname',
                                            'label' => 'Clinic shortname',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'addr_village_id',
                                            'label' => 'Village, Town, Area',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         ),
                                    array(
                                            'field' => 'clinic_ref_no',
                                            'label' => 'clinic_ref_no',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_clinic_dept' => array(
                                    array(
                                            'field' => 'dept_name',
                                            'label' => 'Dept Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'dept_shortname',
                                            'label' => 'Dept shortname',
                                            'rules' => 'trim|required'
                                         )
                                    ),
                 'edit_referral_centre' => array(
                                    array(
                                            'field' => 'centre_name',
                                            'label' => 'Centre Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'centre_type',
                                            'label' => 'Centre Type',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         ),
                                    array(
                                            'field' => 'beds',
                                            'label' => 'No. of Beds',
                                            'rules' => 'trim|numeric'
                                         )
                                    ),
                 'edit_referral_person' => array(
                                    array(
                                            'field' => 'doctor_name',
                                            'label' => 'Doctor Name',
                                            'rules' => 'trim|required'
                                         ),
                                    array(
                                            'field' => 'email',
                                            'label' => 'Email Address',
                                            'rules' => 'valid_email'
                                         )
                                    ),
                 'email' => array(
                                    array(
                                            'field' => 'emailaddress',
                                            'label' => 'EmailAddress',
                                            'rules' => 'required|valid_email'
                                         ),
                                    array(
                                            'field' => 'name',
                                            'label' => 'Name',
                                            'rules' => 'required|alpha'
                                         ),
                                    array(
                                            'field' => 'title',
                                            'label' => 'Title',
                                            'rules' => 'required'
                                         ),
                                    array(
                                            'field' => 'message',
                                            'label' => 'MessageBody',
                                            'rules' => 'required'
                                         )
                                    )                          
               );




