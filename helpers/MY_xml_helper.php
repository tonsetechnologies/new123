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
 * Convert Reserved XML characters to Entities
 *
 * @access	public
 * @param	string
 * @return	string
 */	
if ( ! function_exists('xml_export_header'))
{
	function xml_export_header($str)
	{
		$temp = '__TEMP_AMPERSANDS__';

		// Replace entities to temporary markers so that 
		// ampersands won't get messed up
		$str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);
		$str = preg_replace("/&(\w+);/",  "$temp\\1;", $str);
	
		$str = str_replace(array("&","<",">","\"", "'", "-"),
						   array("&amp;", "&lt;", "&gt;", "&quot;", "&#39;", "&#45;"),
						   $str);

		// Decode the temp markers back to entities		
		$str = preg_replace("/$temp(\d+);/","&#\\1;",$str);
		$str = preg_replace("/$temp(\w+);/","&\\1;", $str);
		
		return $str;
	}
}


// ------------------------------------------------------------------------
/**
 * Recursively remove empty leaves from an array.
 *
 * @access	public
 * @param	string
 * @return	string
 */	
if ( ! function_exists('array_filter_recursive'))
{
    function array_filter_recursive($input){
        foreach ($input as &$value){
            if (is_array($value)){
                $value = array_filter_recursive($value);
            }
        }
        return array_filter($input);
    }
}


// ------------------------------------------------------------------------
// Credits to http://ask.amoeba.co.in/convert-an-xml-to-array-in-php/
/**
 * Convert Reserved XML characters to array.
 *
 * @access	public
 * @param	string
 * @return	string
 */	
if ( ! function_exists('xml_to_array'))
{
    function xml_to_array($input, $callback = null, $recurse = false) 
    {
        $data = ((!$recurse) && is_string($input))? simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA): $input;
        if ($data instanceof SimpleXMLElement) $data = (array) $data;
        if (is_array($data)) foreach ($data as &$item) $item = xml_to_array($item, $callback, true);
        return (!is_array($data) && is_callable($callback))? call_user_func($callback, $data): $data;
    }
}


/* End of file xml_helper.php */
/* Location: ./system/helpers/xml_helper.php */
