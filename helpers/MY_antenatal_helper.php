<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2010, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter XML Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/xml_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Determine whether the period of gestation is reasonable.
 *
 * @access	public
 * @param	string
 * @return	string
 */	
if ( ! function_exists('antenatal_gestation_period'))
{
	function antenatal_gestation_period($lmp_timestamp,$delivery_timestamp)
	{
        $verdict                    =   array();
        $legal_gestation_period     =   7*40;
        $gestation_period           =   ($delivery_timestamp - $lmp_timestamp)/(60*60*24);
        $verdict['weeks']            =   (int)($gestation_period / 7);
        switch ($gestation_period){
            case ($gestation_period < 0):
                $verdict['severity']=   "Error: ";
                $verdict['msg']     =   "Gestation period was ".$verdict['weeks']." weeks - Error dates";
                break;
            case ($gestation_period < ($legal_gestation_period*0.75)):
                $verdict['severity']=   "Warning: ";
                $verdict['msg']     =   "Gestation period was ".$verdict['weeks']." weeks - Was it an abortion?";
                break;
            case ($gestation_period < ($legal_gestation_period*0.90)):
                $verdict['severity']=   "Warning: ";
                $verdict['msg']     =   "Gestation period was ".$verdict['weeks']." weeks - Was it a premature baby?";
                break;
            case ($gestation_period < ($legal_gestation_period*1.10)):
                $verdict['severity']=   "";
                $verdict['msg']     =   "";//"Gestation period was ".$verdict['weeks']." weeks - Normal pregnancy";
                break;
            case ($gestation_period >= ($legal_gestation_period*1.10)):
                $verdict['severity']=   "Error: ";
                $verdict['msg']     =   "Gestation period was ".$verdict['weeks']." weeks - Abnormal pregnancy";
                break;
        }
		return $verdict;
	} //endfunction antenatal_gestation_period($str)
}  


/* End of file MY_antenatal_helper.php */
/* Location: ./app_thirra/helpers/MY_antenatal_helper.php */
