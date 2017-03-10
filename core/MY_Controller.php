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

/**
 * Controller Class for EHR_INDIVIDUAL
 *
 * This class is used for both narrowband and broadband EHR. 
 *
 * @version 0.9
 * @package THIRRA - EHR
 * @author  Jason Tan Boon Teck
 */
class MY_Controller extends CI_Controller 
{

	// ------------------------------------------------------------------------
    function break_date($iso_date) 
    // Chops yyyy-mm-dd into an array
    {
        $broken_date          =   array();
        $broken_date['yyyy']  =   substr($iso_date,0,4);
        $broken_date['mm']    =   substr($iso_date,5,2);
        $broken_date['dd']    =   substr($iso_date,8,2);
        return $broken_date;
    } // end of function break_date($iso_date)


    // ------------------------------------------------------------------------
    function cb_correct_date($date_string)  
    // Call back function to validate date format
    {
        $data['app_minyear']		    =	$this->config->item('app_minyear');
        $data['app_maxyear']		    =	$this->config->item('app_maxyear');
		// Check if it is YYYY-MM-DD
		//if(ereg("^[1|2]{1}[9|0]{1}[0-9]{2}-[0-1]{1}[0-9]{1}-[0-3]{1}[0-9]{1}", $date_string)){
		if(preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date_string)){
			// Check if it is a valid date
            $broken_birth_date      =   $this->break_date($date_string);			
			if(checkdate($broken_birth_date['mm'],$broken_birth_date['dd'],$broken_birth_date['yyyy'])){
				// Check if year is within limits
				if(($broken_birth_date['yyyy'] < $data['app_maxyear']) && ($broken_birth_date['yyyy'] > $data['app_minyear'])){
					return TRUE;
				} else {
					$this->form_validation->set_message('cb_correct_date', 'The %s field format is outside valid year range.');
					return FALSE;
				}
			} else {
				$this->form_validation->set_message('cb_correct_date', 'The %s field format is incorrect.');
				return FALSE;
			}
		} else {
			$this->form_validation->set_message('cb_correct_date', 'The %s field format is incorrect.');
			return FALSE;
		}
    } // end of function cb_correct_date($date_string)


    // ------------------------------------------------------------------------
    function cb_unique($value, $params)
    // Call back function to validate unique key
    {
        list($table, $field) = explode(".", $params, 2);

        if ( ! empty($table) && ! empty( $field ) )
        {
            $CI =& get_instance();
            $CI->db->select($field);
            $CI->db->from($table);
            $CI->db->where($field, $value);
            $CI->db->limit(1);
            $query = $CI->db->get();

            if ($query->row())
            {
                return FALSE;
            }
            else
            {
                return TRUE;
            }
        }
        else
        {
            show_error('Call to Form_validation::unique() failed, parameter not in "table.column" notation');
        }
    }  
}

/* End of file MY_Controller.php */
/* Location: ./app_thirra/libraries/MY_Controller.php */
