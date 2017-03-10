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
 * Breaks down ISO format date to year, month and day.
 *
 * @access	public
 * @param	string
 * @return	string
 */	
if ( ! function_exists('breadcrumbs'))
{
    function breadcrumbs($main_class,$cont_name,$cont_method=NULL,$method_name=NULL)  // Create bread crumb
    {
        $bread_crumbs   =    "<a href='".base_url()."index.php/ehr_dashboard'>Home</a> > ";
        $bread_crumbs   .=    "<a href='".base_url()."index.php/".$main_class."'>".$cont_name."</a>";
        if($cont_method){
            $bread_crumbs   .=    " > <a href='".base_url()."index.php/".$cont_method."'>".$method_name."</a>";
        }
        return $bread_crumbs;
    } // end of function breadcrumbs()

}  


/* End of file MY_breadcrumbs_helper.php */
/* Location: ./app_thirra/helpers/MY_breadcrumbs_helper.php */
