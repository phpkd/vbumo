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

/**
* Fetches the integer value associated with a USERMO log action string
*
* @param	string	The USERMO log action
*
* @return	integer
*/
function fetch_usermologtypes($logtype)
{
	static $usermologtypes = array(
		'usermo_editavatar'     => 1,
		'usermo_editprofilepic' => 2,
		'usermo_editsignature'  => 3,
		'usermo_editalbumpic'   => 4,
	);

	($hook = vBulletinHook::fetch_hook('fetch_usermologtypes')) ? eval($hook) : false;

	return !empty($usermologtypes["$logtype"]) ? $usermologtypes["$logtype"] : 0;
}

/**
* Fetches the string associated with a USERMO log action integer value
*
* @param	integer	The USERMO log action
*
* @return	string
*/
function fetch_usermologactions($logaction)
{
	static $usermologactions = array(
		1	=>	'usermo_editavatar',
		2	=>	'usermo_editprofilepic',
		3	=>	'usermo_editsignature',
		4	=>	'usermo_editalbumpic',
	);

	($hook = vBulletinHook::fetch_hook('fetch_usermologactions')) ? eval($hook) : false;

	return !empty($usermologactions["$logaction"]) ? $usermologactions["$logaction"] : '';
}

/**
* Logs the USERMO actions that are being performed on the forum
*
* @param	array	Array of information indicating on what data the action was performed
* @param	integer	This value corresponds to the action that was being performed
* @param	string	Other USERMO parameters
*/
function log_usermo_action($loginfo, $logtype, $action = '')
{
	global $vbulletin;

	$usermologsql = array();

	if ($result = fetch_usermologtypes($logtype))
	{
		$logtype = $result;
	}

	($hook = vBulletinHook::fetch_hook('log_usermo_action')) ? eval($hook) : false;

	if (is_array($loginfo[0]))
	{
		foreach ($loginfo AS $index => $log)
		{
			if (is_array($action))
			{
				$action = serialize($action);
			}
			$usermologsql[] = "(" . intval($logtype) . ", " . intval($log['userid']) . ", " . TIMENOW . ", " . intval($log['processedid']) . ", '" . $vbulletin->db->escape_string($action) . "', '" . $vbulletin->db->escape_string(IPADDRESS) . "')";
		}

		$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_usermolog (type, userid, dateline, processedid, action, ipaddress) VALUES " . implode(', ', $usermologsql));
	}
	else
	{
		$usermolog['userid'] =& $vbulletin->userinfo['userid'];
		$usermolog['dateline'] = TIMENOW;
		$usermolog['type'] = intval($logtype);
		$usermolog['processedid'] = intval($loginfo['processedid']);
		$usermolog['ipaddress'] = IPADDRESS;

		if (is_array($action))
		{
			$action = serialize($action);
		}
		$usermolog['action'] = $action;

		/*insert query*/
		$vbulletin->db->query_write(fetch_query_sql($usermolog, 'phpkd_usermolog'));
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