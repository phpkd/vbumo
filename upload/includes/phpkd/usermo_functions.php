<?php
/*============================================================================*\
|| ########################################################################### ||
|| # Product Name: User Moderating Options                    Version: 1.2.0 # ||
|| # License Number: Custom License                         $Revision$ # ||
|| # ----------------------------------------------------------------------- # ||
|| #                                                                         # ||
|| #         Copyright ©2005-2008 PHP KingDom. Some Rights Reserved.         # ||
|| #   This product may be redistributed in whole or significant part under  # ||
|| #      "Creative Commons - Attribution-Noncommercial-Share Alike 3.0"     # ||
|| #                                                                         # ||
|| # ------------- 'User Moderating Options' IS FREE SOFTWARE -------------- # ||
|| #        http://www.phpkd.net | http://go.phpkd.net/license/custom/       # ||
|| ########################################################################### ||
\*============================================================================*/

class vBulletinHook_phpkd_usermo extends vBulletinHook
{
	var $last_called = '';

	function vBulletinHook_phpkd_usermo(&$pluginlist, &$hookusage)
	{
		$this->pluginlist =& $pluginlist;
		$this->hookusage =& $hookusage;
	}

	function &fetch_hook_object($hookname)
	{
		$this->last_called = $hookname;
		return parent::fetch_hook_object($hookname);
	}
}

/*============================================================================*\
|| ########################################################################### ||
|| # Version: 1.2.0
|| # $Revision$
|| # Released: $Date$
|| ########################################################################### ||
\*============================================================================*/
?>