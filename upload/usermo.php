<?php
/*============================================================================*\
|| ########################################################################### ||
|| # Product Name: User Moderating Options                    Version: 1.2.0 # ||
|| # License Number: Custom License                         $Revision: 123 $ # ||
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


// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('GET_EDIT_TEMPLATES', 'editsignature,updatesignature');
define('THIS_SCRIPT', 'usermo');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('user', 'posting', 'cppermission');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'USERCP_SHELL',
	'usercp_nav_folderbit',
	'phpkd_usermo_usercp',
	'phpkd_usermo_search',
	'phpkd_usermo_results',
	'phpkd_usermo_results_bits'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'editavatar' => array(
		'phpkd_usermo_modifyavatar',
		'phpkd_usermo_modifyavatarbit',
		'phpkd_usermo_modifyavatarbit_custom',
		'phpkd_usermo_modifyavatarbit_noavatar',
		'phpkd_usermo_modifyavatar_category',
		'phpkd_usermo_help_avatars_row',
	),
	'editprofilepic' => array(
		'phpkd_usermo_modifyprofilepic',
	),
	'editsignature' => array(
		'phpkd_usermo_newpost_errormessage',
		'phpkd_usermo_newpost_preview',
		'phpkd_usermo_modifysignature',
	),
);

$actiontemplates['none'] =& $actiontemplates['usermo_editavatar'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_user.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'editavatar';
}

if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
{
	print_no_permission();
}

if (empty($vbulletin->userinfo['userid']))
{
	print_no_permission();
}

// set shell template name
$shelltemplatename = 'USERCP_SHELL';
$templatename = '';

// initialise onload event
$onload = '';

// start the navbar
$navbits = array('usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel']);

($hook = vBulletinHook::fetch_hook('profile_usermo_start')) ? eval($hook) : false;

if ($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['logactions'])
{
	require_once(DIR . '/includes/functions_usermo_log.php');
	$show['logactions'] = true;
}

$show['usermo_search'] = false;
$show['usermo_results'] = false;

$request = $_REQUEST['do'];
switch ($request)
{
	case 'editavatar':
		$title = $vbphrase['avatar'];
		break;
	case 'editprofilepic':
		$title = $vbphrase['profile_picture'];
		break;
	case 'editsignature':
		$title = $vbphrase['signature'];
		break;
}

$vbulletin->input->clean_array_gpc('r', array('userid' => TYPE_INT));
$vbulletin->input->clean_array_gpc('p', array('username' => TYPE_STR, 'exact' => TYPE_BOOL));

$userid = $vbulletin->GPC['userid'];
$username = $vbulletin->GPC['username'];
$exactchecked["{$vbulletin->GPC['exact']}"] = 'checked="checked"';

if (empty($vbulletin->GPC['userid']))
{
	$show['usermo_search'] = true;
	eval('$usermo_search .= "' . fetch_template('phpkd_usermo_search') . '";');
}
else if ($vbulletin->userinfo['userid'] == $vbulletin->GPC['userid'])
{
	exec_header_redirect('profile.php?' . $vbulletin->session->vars['sessionurl_js'] . 'do=' . $request);
}
else
{
	$userinfo = fetch_userinfo($vbulletin->GPC['userid'], FETCH_USERINFO_SIGPIC);
	if (!$userinfo)
	{
		eval(standard_error(fetch_error('invalid_user_specified')));
	}

	cache_permissions($userinfo);
	require_once(DIR . '/includes/adminfunctions.php');

	if (is_unalterable_user($userinfo['userid']))
	{
		eval(standard_error(fetch_error('user_is_protected_from_alteration_by_undeletableusers_var')));
	}

	if (!($userinfo['permissions']['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managed']))
	{
		eval(standard_error(fetch_error('phpkd_usermo_usergroup_is_protected_from_alteration')));
	}
}

if ($vbulletin->GPC['username'])
{
	if ($vbulletin->GPC['exact'])
	{
		$condition = "username = '" . $db->escape_string($vbulletin->GPC['username']) . "'";
	}
	else
	{
		$condition = "username LIKE '%" . $db->escape_string_like($vbulletin->GPC['username']) . "%'";
	}

	$users = $db->query_read("
		SELECT userid, username
		FROM " . TABLE_PREFIX . "user
		WHERE $condition
		ORDER BY username
	");

	if ($db->num_rows($users) == 1)
	{
		$user = $db->fetch_array($users);
		exec_header_redirect('usermo.php?' . $vbulletin->session->vars['sessionurl_js'] . 'do=' . $request . '&amp;u=' . $user['userid']);
	}
	else if ($db->num_rows($users) > 1)
	{
		$show['usermo_results'] = true;

		while ($user = $db->fetch_array($users))
		{
			$user['musername'] = fetch_musername($user);
			eval('$usermo_results_bits .= "' . fetch_template('phpkd_usermo_results_bits') . '";');
		}

		eval('$usermo_results = "' . fetch_template('phpkd_usermo_results') . '";');
	}
	else
	{
		eval(standard_error(fetch_error('no_matches_found')));
	}
}


// ############################################################################
// ############################### EDIT AVATAR ################################
// ############################################################################
if ($_REQUEST['do'] == 'editavatar')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'categoryid' => TYPE_UINT
	));

	if (!$vbulletin->options['avatarenabled'])
	{
		eval(standard_error(fetch_error('avatardisabled')));
	}

	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['manageavatar']) AND !can_moderate(0, 'caneditavatar'))
	{
		eval(standard_error(fetch_error('phpkd_usermo_no_permission_avatars')));
	}

	if ($vbulletin->options['phpkd_usermo_violated_avatar'])
	{
		require_once(DIR . '/includes/class_vurl.php');
		$vurl = new vB_vURL($vbulletin);
		$vurl->set_option(VURL_URL, $vbulletin->options['phpkd_usermo_violated_avatar']);
		$vurl->set_option(VURL_HEADER, true);
		$vurl->set_option(VURL_RETURNTRANSFER, true);
		$result = $vurl->exec2();
		if (file_exists($result['body_file']))
		{
			$show['usermo_violated_avatar'] = true;
		}
		else
		{
			$show['usermo_violated_avatar'] = false;
		}
	}


	($hook = vBulletinHook::fetch_hook('profile_usermo_editavatar_start')) ? eval($hook) : false;

	if (!$show['usermo_search'])
	{
		// initialise vars
		$avatarchecked["{$userinfo['avatarid']}"] = 'checked="checked"';
		$categorycache = array();
		$bbavatar = array();
		$donefirstcategory = 0;

		// variables that will become templates
		$avatarlist = '';
		$nouseavatarchecked = '';
		$categorybits = '';
		$predefined_section = '';
		$custom_section = '';

		// initialise the bg class
		$bgclass = 'alt1';

		// ############### DISPLAY USER'S AVATAR ###############
		if ($userinfo['avatarid'])
		{
			// using a predefined avatar
			$avatar = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "avatar WHERE avatarid = " . $userinfo['avatarid']);
			$avatarid =& $avatar['avatarid'];
			eval('$currentavatar = "' . fetch_template('phpkd_usermo_modifyavatarbit') . '";');
			// store avatar info in $bbavatar for later use
			$bbavatar = $avatar;
		}
		else
		{
			// not using a predefined avatar, check for custom
			if ($avatar = $db->query_first("SELECT dateline, width, height FROM " . TABLE_PREFIX . "customavatar WHERE userid=" . $userinfo['userid']))
			{
				// using a custom avatar
				if ($vbulletin->options['usefileavatar'])
				{
					$userinfo['avatarurl'] = $vbulletin->options['avatarurl'] . '/avatar' . $userinfo['userid'] . '_' . $userinfo['avatarrevision'] . '.gif';
				}
				else
				{
					$userinfo['avatarurl'] = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . "&amp;dateline=$avatar[dateline]";
				}

				if ($avatar['width'] AND $avatar['height'])
				{
					$userinfo['avatarurl'] .= "\" width=\"$avatar[width]\" height=\"$avatar[height]";
				}

				eval('$currentavatar = "' . fetch_template('phpkd_usermo_modifyavatarbit_custom') . '";');
			}
			else
			{
				// no avatar specified
				$nouseavatarchecked = 'checked="checked"';
				$avatarchecked[0] = '';
				eval('$currentavatar = "' . fetch_template('phpkd_usermo_modifyavatarbit_noavatar') . '";');
			}
		}
		// get rid of any lingering $avatar variables
		unset($avatar);

		$categorycache =& fetch_avatar_categories($userinfo);
		foreach ($categorycache AS $category)
		{
			if (!$donefirstcategory OR $category['imagecategoryid'] == $vbulletin->GPC['categoryid'])
			{
				$displaycategory = $category;
				$donefirstcategory = 1;
			}
		}

		// get the id of the avatar category we want to display
		if ($vbulletin->GPC['categoryid'] == 0)
		{
			if ($userinfo['avatarid'] != 0 AND !empty($categorycache["{$bbavatar['imagecategoryid']}"]))
			{
				$displaycategory = $bbavatar;
			}

			$vbulletin->GPC['categoryid'] = $displaycategory['imagecategoryid'];
		}

		// make the category <select> list
		$optionselected["{$vbulletin->GPC['categoryid']}"] = 'selected="selected"';
		if (count($categorycache) > 1)
		{
			$show['categories'] = true;
			foreach ($categorycache AS $category)
			{
				$thiscategoryid = $category['imagecategoryid'];
				$selected = iif($thiscategoryid == $vbulletin->GPC['categoryid'], ' selected="selected"', '');
				eval('$categorybits .= "' . fetch_template('phpkd_usermo_modifyavatar_category') . '";');
			}
		}
		else
		{
			$show['categories'] = false;
			$categorybits = '';
		}

		// ############### GET TOTAL NUMBER OF AVATARS IN THIS CATEGORY ###############
		// get the total number of avatars in this category
		$totalavatars = $categorycache["{$vbulletin->GPC['categoryid']}"]['avatars'];

		// get perpage parameters for table display
		$perpage = $vbulletin->options['numavatarsperpage'];
		sanitize_pageresults($totalavatars, $vbulletin->GPC['pagenumber'], $perpage, 100, 25);
		// get parameters for query limits
		$startat = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;

		// make variables for 'displaying avatars x to y of z' text
		$first = $startat + 1;
		$last = $startat + $perpage;
		if ($last > $totalavatars)
		{
			$last = $totalavatars;
		}

		// ############### DISPLAY PREDEFINED AVATARS ###############
		if ($totalavatars)
		{
			$pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $perpage, $totalavatars, 'usermo.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editavatar&amp;categoryid=' . $vbulletin->GPC['categoryid']);

			$avatars = $db->query_read_slave("
				SELECT avatar.*, imagecategory.title AS category
				FROM " . TABLE_PREFIX . "avatar AS avatar LEFT JOIN " . TABLE_PREFIX . "imagecategory AS imagecategory USING(imagecategoryid)
				WHERE minimumposts <= " . $userinfo['posts'] . "
				AND avatar.imagecategoryid=" . $vbulletin->GPC['categoryid'] . "
				AND avatarid <> " . $userinfo['avatarid'] . "
				ORDER BY avatar.displayorder
				LIMIT $startat,$perpage
			");
			$avatarsonthispage = $db->num_rows($avatars);

			$cols = intval($vbulletin->options['numavatarswide']);
			$cols = iif($cols, $cols, 5);
			$cols = iif($cols > $avatarsonthispage, $avatarsonthispage, $cols);

			$bits = array();
			while ($avatar = $db->fetch_array($avatars))
			{
				$categoryname = $avatar['category'];
				$avatarid =& $avatar['avatarid'];

				($hook = vBulletinHook::fetch_hook('profile_usermo_editavatar_bit')) ? eval($hook) : false;

				eval('$bits[] = "' . fetch_template('phpkd_usermo_modifyavatarbit') . '";');
				if (sizeof($bits) == $cols)
				{
					$avatarcells = implode('', $bits);
					$bits = array();
					eval('$avatarlist .= "' . fetch_template('phpkd_usermo_help_avatars_row') . '";');
					exec_switch_bg();
				}
			}

			// initialize remaining columns
			$remainingcolumns = 0;

			$remaining = sizeof($bits);
			if ($remaining)
			{
				$remainingcolumns = $cols - $remaining;
				$avatarcells = implode('', $bits);
				eval('$avatarlist .= "' . fetch_template('phpkd_usermo_help_avatars_row') . '";');
				exec_switch_bg();
			}

			$show['forumavatars'] = true;
		}
		else
		{
			$show['forumavatars'] = false;
		}
		// end code for predefined avatars

		// ############### DISPLAY CUSTOM AVATAR CONTROLS ###############
		require_once(DIR . '/includes/functions_file.php');
		$inimaxattach = fetch_max_upload_size();

		if ($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar'])
		{
			$show['customavatar'] = true;
			$show['customavatar_url'] = (ini_get('allow_url_fopen') != 0 OR function_exists('curl_init'));

			$userinfo['permissions']['avatarmaxsize'] = vb_number_format($userinfo['permissions']['avatarmaxsize'], 1, true);

			$maxnote = '';
			if ($userinfo['permissions']['avatarmaxsize'] AND ($userinfo['permissions']['avatarmaxwidth'] OR $userinfo['permissions']['avatarmaxheight']))
			{
				$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x_y_or_z'], $userinfo['username'], $userinfo['permissions']['avatarmaxwidth'], $userinfo['permissions']['avatarmaxheight'], $userinfo['permissions']['avatarmaxsize']);
			}
			else if ($userinfo['permissions']['avatarmaxsize'])
			{
				$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x'], $userinfo['username'], $userinfo['permissions']['avatarmaxsize']);
			}
			else if ($userinfo['permissions']['avatarmaxwidth'] OR $userinfo['permissions']['avatarmaxheight'])
			{
				$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x_y_pixels'], $userinfo['username'], $userinfo['permissions']['avatarmaxwidth'], $userinfo['permissions']['avatarmaxheight']);
			}

			$show['maxnote'] = (!empty($maxnote)) ? true : false;
		}
		else
		{
			$show['customavatar'] = false;
		}
	}

	// draw cp nav bar
	construct_usercp_nav('usermo_editavatar');

	$navbits[''] = construct_phrase($vbphrase['phpkd_usermo_edit_avatar'], iif($userinfo['username'], $userinfo['username'], $vbphrase['user']));
	$templatename = 'phpkd_usermo_modifyavatar';
}

// ############################### start update avatar ###############################
if ($_POST['do'] == 'updateavatar')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'avatarid'  => TYPE_INT,
		'avatarurl' => TYPE_STR,
	));

	if (!$vbulletin->options['avatarenabled'])
	{
		eval(standard_error(fetch_error('avatardisabled')));
	}

	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['manageavatar']) AND !can_moderate(0, 'caneditavatar'))
	{
		eval(standard_error(fetch_error('phpkd_usermo_no_permission_avatars')));
	}

	($hook = vBulletinHook::fetch_hook('profile_usermo_updateavatar_start')) ? eval($hook) : false;

	$useavatar = iif($vbulletin->GPC['avatarid'] == -1, 0, 1);

	if ($useavatar)
	{
		if ($vbulletin->GPC['avatarid'] == 0 AND ($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar']))
		{
			$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

			// begin custom avatar code
			require_once(DIR . '/includes/class_upload.php');
			require_once(DIR . '/includes/class_image.php');

			$upload = new vB_Upload_Userpic($vbulletin);

			$upload->data =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_STANDARD, 'userpic');
			$upload->image =& vB_Image::fetch_library($vbulletin);
			$upload->userinfo =& $userinfo;
			$upload->maxwidth = $userinfo['permissions']['avatarmaxwidth'];
			$upload->maxheight = $userinfo['permissions']['avatarmaxheight'];
			$upload->maxuploadsize = $userinfo['permissions']['avatarmaxsize'];
			$upload->allowanimation = ($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cananimateavatar']) ? true : false;

			if (!$upload->process_upload($vbulletin->GPC['avatarurl']))
			{
				eval(standard_error($upload->fetch_error()));
			}
		}
		else
		{
			// start predefined avatar code
			$vbulletin->GPC['avatarid'] = verify_id('avatar', $vbulletin->GPC['avatarid']);
			$avatarinfo = $db->query_first_slave("
				SELECT avatarid, minimumposts, imagecategoryid
				FROM " . TABLE_PREFIX . "avatar
				WHERE avatarid = " . $vbulletin->GPC['avatarid']
			);

			if ($avatarinfo['minimumposts'] > $userinfo['posts'])
			{
				// not enough posts error
				eval(standard_error(fetch_error('phpkd_usermo_avatarmoreposts', $userinfo['username'])));
			}

			$membergroups = fetch_membergroupids_array($userinfo);

			$avperms = $db->query_read_slave("
				SELECT usergroupid
				FROM " . TABLE_PREFIX . "imagecategorypermission
				WHERE imagecategoryid = $avatarinfo[imagecategoryid]
			");

			$noperms = array();
			while ($avperm = $db->fetch_array($avperms))
			{
				$noperms[] = $avperm['usergroupid'];
			}

			if (!count(array_diff($membergroups, $noperms)))
			{
				eval(standard_error(fetch_error('invalid_avatar_specified')));
			}

			$userpic =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_STANDARD, 'userpic');
			$userpic->condition = 'userid = ' . $userinfo['userid'];
			$userpic->delete();

			// end predefined avatar code
		}
	}
	else
	{
		// not using an avatar
		$vbulletin->GPC['avatarid'] = 0;
		$userpic =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$userpic->condition = 'userid = ' . $userinfo['userid'];
		$userpic->delete();
	}

	// init user data manager
	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($userinfo);

	$userdata->set('avatarid', $vbulletin->GPC['avatarid']);

	($hook = vBulletinHook::fetch_hook('profile_usermo_updateavatar_complete')) ? eval($hook) : false;

	$userdata->save();

	// LOGGING USERMO
	if ($show['logactions'])
	{
		$usermolog = array(
			'processedid' => $userinfo['userid']
		);
		log_usermo_action($usermolog, 'usermo_editavatar');
	}

	$vbulletin->url = 'usermo.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editavatar&amp;u=' . $userinfo['userid'];
	eval(print_standard_redirect('redirect_usermo_updatethanks'));
}


// ############################################################################
// ########################## EDIT PROFILE PICTURE ############################
// ############################################################################
if ($_REQUEST['do'] == 'editprofilepic')
{
	if (!$vbulletin->options['profilepicenabled'])
	{
		eval(standard_error(fetch_error('phpkd_usermo_profilepicdisabled')));
	}

	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['manageprofilepicture']) AND !can_moderate(0, 'caneditprofilepic'))
	{
		eval(standard_error(fetch_error('phpkd_usermo_no_permission_profilepicture')));
	}

	if (!empty($userinfo['userid']) AND !($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic']))
	{
		eval(standard_error(fetch_error('phpkd_usermo_user_has_no_perms', $vbphrase['profile_picture'])));
	}

	if ($vbulletin->options['phpkd_usermo_violated_profilepic'])
	{
		require_once(DIR . '/includes/class_vurl.php');
		$vurl = new vB_vURL($vbulletin);
		$vurl->set_option(VURL_URL, $vbulletin->options['phpkd_usermo_violated_profilepic']);
		$vurl->set_option(VURL_HEADER, true);
		$vurl->set_option(VURL_RETURNTRANSFER, true);
		$result = $vurl->exec2();
		if (file_exists($result['body_file']))
		{
			$show['usermo_violated_profilepic'] = true;
		}
		else
		{
			$show['usermo_violated_profilepic'] = false;
		}
	}


	($hook = vBulletinHook::fetch_hook('profile_usermo_editprofilepic')) ? eval($hook) : false;

	if (!$show['usermo_search'])
	{
		if ($profilepic = $db->query_first("
			SELECT userid, dateline, height, width
			FROM " . TABLE_PREFIX . "customprofilepic
			WHERE userid = " . $userinfo['userid']
		))
		{
			if ($vbulletin->options['usefileavatar'])
			{
				$userinfo['profileurl'] = $vbulletin->options['profilepicurl'] . '/profilepic' . $userinfo['userid'] . '_' . $userinfo['profilepicrevision'] . '.gif';
			}
			else
			{
				$userinfo['profileurl'] = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . "&amp;dateline=$profilepic[dateline]&amp;type=profile";
			}

			if ($profilepic['width'] AND $profilepic['height'])
			{
				$userinfo['profileurl'] .= "\" width=\"$profilepic[width]\" height=\"$profilepic[height]";
			}
			$show['profilepic'] = true;
		}

		$userinfo['permissions']['profilepicmaxsize'] = vb_number_format($userinfo['permissions']['profilepicmaxsize'], 1, true);

		$maxnote = '';
		if ($userinfo['permissions']['profilepicmaxsize'] AND ($userinfo['permissions']['profilepicmaxwidth'] OR $userinfo['permissions']['profilepicmaxheight']))
		{
			$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x_y_or_z'], $userinfo['username'], $userinfo['permissions']['profilepicmaxwidth'], $userinfo['permissions']['profilepicmaxheight'], $userinfo['permissions']['profilepicmaxsize']);
		}
		else if ($userinfo['permissions']['profilepicmaxsize'])
		{
			$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x'], $userinfo['username'], $userinfo['permissions']['profilepicmaxsize']);
		}
		else if ($userinfo['permissions']['profilepicmaxwidth'] OR $userinfo['permissions']['profilepicmaxheight'])
		{
			$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x_y_pixels'], $userinfo['username'], $userinfo['permissions']['profilepicmaxwidth'], $userinfo['permissions']['profilepicmaxheight']);
		}
		$show['maxnote'] = (!empty($maxnote)) ? true : false;
		$show['profilepic_url'] = (ini_get('allow_url_fopen') != 0 OR function_exists('curl_init'));
	}

	// draw cp nav bar
	construct_usercp_nav('usermo_editprofilepic');

	$navbits[''] = construct_phrase($vbphrase['phpkd_usermo_edit_profile_picture'], $userinfo['username']);
	$templatename = 'phpkd_usermo_modifyprofilepic';
}


// ############################### start update profile pic###########################
if ($_POST['do'] == 'updateprofilepic')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deleteprofilepic' => TYPE_BOOL,
		'avatarurl'        => TYPE_STR,
	));

	if (!$vbulletin->options['profilepicenabled'])
	{
		eval(standard_error(fetch_error('phpkd_usermo_profilepicdisabled')));
	}

	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['manageprofilepicture']) AND !can_moderate(0, 'caneditprofilepic'))
	{
		eval(standard_error(fetch_error('phpkd_usermo_no_permission_profilepicture')));
	}

	if (!($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic']))
	{
		eval(standard_error(fetch_error('phpkd_usermo_user_has_no_perms', $vbphrase['profile_picture'])));
	}

	($hook = vBulletinHook::fetch_hook('profile_usermo_updateprofilepic_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['deleteprofilepic'])
	{
		$userpic =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$userpic->condition = "userid = " . $userinfo['userid'];
		$userpic->delete();
	}
	else
	{
		$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

		require_once(DIR . '/includes/class_upload.php');
		require_once(DIR . '/includes/class_image.php');

		$upload = new vB_Upload_Userpic($vbulletin);

		$upload->data =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$upload->image =& vB_Image::fetch_library($vbulletin);
		$upload->userinfo =& $userinfo;
		$upload->maxwidth = $userinfo['permissions']['profilepicmaxwidth'];
		$upload->maxheight = $userinfo['permissions']['profilepicmaxheight'];
		$upload->maxuploadsize = $userinfo['permissions']['profilepicmaxsize'];
		$upload->allowanimation = ($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cananimateprofilepic']) ? true : false;

		if (!$upload->process_upload($vbulletin->GPC['avatarurl']))
		{
			eval(standard_error($upload->fetch_error()));
		}
	}

	($hook = vBulletinHook::fetch_hook('profile_usermo_updateprofilepic_complete')) ? eval($hook) : false;

	// LOGGING USERMO
	if ($show['logactions'])
	{
		$usermolog = array(
			'processedid' => $userinfo['userid']
		);
		log_usermo_action($usermolog, 'usermo_editprofilepic');
	}

	$vbulletin->url = 'usermo.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editprofilepic&amp;u=' . $userinfo['userid'];
	eval(print_standard_redirect('redirect_usermo_updatethanks'));
}

// ############################################################################
// ############################## EDIT SIGNATURE ##############################
// ############################################################################

// ########################### start update signature #########################
if ($_POST['do'] == 'updatesignature')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'wysiwyg'      => TYPE_BOOL,
		'message'      => TYPE_STR,
		'preview'      => TYPE_STR,
		'deletesigpic' => TYPE_BOOL,
		'sigpicurl'    => TYPE_STR,
	));

	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managesignature']) AND !can_moderate(0, 'caneditsigs'))
	{
		eval(standard_error(fetch_error('phpkd_usermo_no_permission_signature')));
	}

	if (!($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
	{
		eval(standard_error(fetch_error('phpkd_usermo_nosignaturepermission', $userinfo['username'])));
	}

	if ($userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic'])
	{
		$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);
	}

	require_once(DIR . '/includes/class_bbcode.php');
	require_once(DIR . '/includes/class_sigparser.php');
	require_once(DIR . '/includes/functions_misc.php');

	$errors = array();

	// DO WYSIWYG processing to get to BB code.
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/functions_wysiwyg.php');

		$signature = convert_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['allowhtml']);
	}
	else
	{
		$signature = $vbulletin->GPC['message'];
	}

	($hook = vBulletinHook::fetch_hook('profile_usermo_updatesignature_start')) ? eval($hook) : false;

	// handle image uploads
	if ($vbulletin->GPC['deletesigpic'])
	{
		if (preg_match('#\[sigpic\](.*)\[/sigpic\]#siU', $signature))
		{
			$errors[] = fetch_error('phpkd_usermo_sigpic_in_use', $userinfo['username']);
		}
		else
		{
			$userpic =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
			$userpic->condition = "userid = " . $userinfo['userid'];
			$userpic->delete();
		}

		$redirectsig = true;
	}
	else if (($vbulletin->GPC['sigpicurl'] != '' AND $vbulletin->GPC['sigpicurl'] != 'http://www.') OR $vbulletin->GPC['upload']['size'] > 0)
	{
		require_once(DIR . '/includes/class_upload.php');
		require_once(DIR . '/includes/class_image.php');

		$upload = new vB_Upload_Userpic($vbulletin);

		$upload->data =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$upload->image =& vB_Image::fetch_library($vbulletin);
		$upload->userinfo =& $userinfo;
		$upload->maxwidth = $userinfo['permissions']['sigpicmaxwidth'];
		$upload->maxheight = $userinfo['permissions']['sigpicmaxheight'];
		$upload->maxuploadsize = $userinfo['permissions']['sigpicmaxsize'];
		$upload->allowanimation = ($userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cananimatesigpic']) ? true : false;

		if (!$upload->process_upload($vbulletin->GPC['sigpicurl']))
		{
			eval(standard_error($upload->fetch_error()));
		}
		$redirectsig = true;
		$userinfo['sigpicrevision']++;
	}

	// Censored Words
	$censor_signature = fetch_censored_text($signature);

	if ($signature != $censor_signature)
	{
		$signature = $censor_signature;
		$errors[] = fetch_error('phpkd_usermo_censoredword');
		unset($censor_signature);
	}

	// Max number of images in the sig if imgs are allowed.
	if ($userinfo['permissions']['sigmaximages'])
	{
		// Parsing the signature into BB code.
		require_once(DIR . '/includes/class_bbcode_alt.php');
		$bbcode_parser =& new vB_BbCodeParser_ImgCheck($vbulletin, fetch_tag_list());
		$bbcode_parser->set_parse_userinfo($userinfo, $userinfo['permissions']);
		$parsedsig = $bbcode_parser->parse($signature, 'signature');

		$imagecount = fetch_character_count($parsedsig, '<img');

		// Count the images
		if ($imagecount > $userinfo['permissions']['sigmaximages'])
		{
			$vbulletin->GPC['preview'] = true;
			$errors[] = fetch_error('phpkd_usermo_toomanyimages', $imagecount, $userinfo['username'], $userinfo['permissions']['sigmaximages']);
		}
	}

	// Count the raw characters in the signature
	if ($userinfo['permissions']['sigmaxrawchars'] AND vbstrlen($signature) > $userinfo['permissions']['sigmaxrawchars'])
	{
		$vbulletin->GPC['preview'] = true;
		$errors[] = fetch_error('phpkd_usermo_sigtoolong_includingbbcode', $userinfo['username'], $userinfo['permissions']['sigmaxrawchars']);
	}
	// Count the characters after stripping in the signature
	else if ($userinfo['permissions']['sigmaxchars'] AND (vbstrlen(strip_bbcode($signature, false, false, false)) > $userinfo['permissions']['sigmaxchars']))
	{
		$vbulletin->GPC['preview'] = true;
		$errors[] = fetch_error('phpkd_usermo_sigtoolong_excludingbbcode', $userinfo['username'], $userinfo['permissions']['sigmaxchars']);
	}

	if ($userinfo['permissions']['sigmaxlines'] > 0)
	{
		require_once(DIR . '/includes/class_sigparser_char.php');
		$char_counter =& new vB_SignatureParser_CharCount($vbulletin, fetch_tag_list(), $userinfo['permissions'], $userinfo['userid']);
		$line_count_text = $char_counter->parse(trim($signature));

		if ($vbulletin->options['softlinebreakchars'] > 0)
		{
			// implicitly wrap after X characters without a break
			$line_count_text = preg_replace('#([^\r\n]{' . $vbulletin->options['softlinebreakchars'] . '})#', "\\1\n", $line_count_text);
		}

		// + 1, since 0 linebreaks still means 1 line
		$line_count = substr_count($line_count_text, "\n") + 1;

		if ($line_count > $userinfo['permissions']['sigmaxlines'])
		{
			$vbulletin->GPC['preview'] = true;
			$errors[] = fetch_error('phpkd_usermo_sigtoomanylines', $userinfo['username'], $userinfo['permissions']['sigmaxlines']);
		}
	}

	if ($userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['canbbcode'])
	{
		// Get the files we need
		require_once(DIR . '/includes/functions_newpost.php');

		// add # to color tags using hex if it's not there
		$signature = preg_replace('#\[color=(&quot;|"|\'|)([a-f0-9]{6})\\1]#i', '[color=\1#\2\1]', $signature);

		// Turn the text into bb code.
		$signature = convert_url_to_bbcode($signature);

		// Create the parser with the users sig permissions
		$sig_parser =& new vB_SignatureParser($vbulletin, fetch_tag_list(), $userinfo['permissions'], $userinfo['userid']);

		// Parse the signature
		$previewmessage = $sig_parser->parse($signature);

		if ($error_num = count($sig_parser->errors))
		{
			foreach ($sig_parser->errors AS $tag => $error_phrase)
			{
				$errors[] = fetch_error($error_phrase, $tag);
			}
		}

		unset($sig_parser, $tag_list, $sig_tag_token_array, $results);
	}

	// If they are previewing the signature or there were usergroup rules broken and there are $errors[]
	if (!empty($errors) OR $vbulletin->GPC['preview'] != '')
	{
		if (!empty($errors))
		{
			$errorlist = '';
			foreach ($errors AS $key => $errormessage)
			{
				eval('$errorlist .= "' . fetch_template('phpkd_usermo_newpost_errormessage') . '";');
			}
			$show['errors'] = true;
		}

		$bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());
		$bbcode_parser->set_parse_userinfo($userinfo, $userinfo['permissions']);
		$previewmessage = $bbcode_parser->parse($signature, 'signature');

		// save a conditional by just overwriting the phrase
		$vbphrase['submit_message'] =& $vbphrase['save_signature'];
		eval('$preview = "' . fetch_template('phpkd_usermo_newpost_preview') . '";');
		$_REQUEST['do'] = 'editsignature';

		$preview_error_signature = $signature;
	}
	else
	{
		// init user data manager
		$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
		$userdata->set_existing($userinfo);

		$userdata->set('signature', $signature);

		($hook = vBulletinHook::fetch_hook('profile_usermo_updatesignature_complete')) ? eval($hook) : false;

		$userdata->save();

		// LOGGING USERMO
		if ($show['logactions'])
		{
			$usermolog = array(
				'processedid' => $userinfo['userid']
			);
			log_usermo_action($usermolog, 'usermo_editsignature');
		}

		if ($redirectsig)
		{
			$vbulletin->url = 'usermo.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editsignature&amp;u=' . $userinfo['userid'] . '&amp;url=' . $vbulletin->url . '#sigpic';
		}
		eval(print_standard_redirect('redirect_usermo_updatethanks'));
	}
}

// ############################### start update signature pic ###########################
if ($_POST['do'] == 'updatesigpic')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletesigpic' => TYPE_BOOL,
		'sigpicurl'    => TYPE_STR,
	));

	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managesignature']) AND !can_moderate(0, 'caneditsigs'))
	{
		eval(standard_error(fetch_error('phpkd_usermo_no_permission_signature')));
	}

	if (!($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
	{
		eval(standard_error(fetch_error('phpkd_usermo_nosignaturepermission', $userinfo['username'])));
	}

	if (!($userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic']))
	{
		eval(standard_error(fetch_error('phpkd_usermo_user_has_no_perms_cansigpic')));
	}

	($hook = vBulletinHook::fetch_hook('profile_usermo_updatesigpic_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['deletesigpic'])
	{
		$userpic =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$userpic->condition = "userid = " . $userinfo['userid'];
		$userpic->delete();
	}
	else
	{
		$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

		require_once(DIR . '/includes/class_upload.php');
		require_once(DIR . '/includes/class_image.php');

		$upload = new vB_Upload_Userpic($vbulletin);

		$upload->data =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$upload->image =& vB_Image::fetch_library($vbulletin);
		$upload->userinfo =& $userinfo;
		$upload->maxwidth = $userinfo['permissions']['sigpicmaxwidth'];
		$upload->maxheight = $userinfo['permissions']['sigpicmaxheight'];
		$upload->maxuploadsize = $userinfo['permissions']['sigpicmaxsize'];
		$upload->allowanimation = ($userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cananimatesigpic']) ? true : false;

		if (!$upload->process_upload($vbulletin->GPC['sigpicurl']))
		{
			eval(standard_error($upload->fetch_error()));
		}
	}

	($hook = vBulletinHook::fetch_hook('profile_usermo_updatesigpic_complete')) ? eval($hook) : false;

	// LOGGING USERMO
	if ($show['logactions'])
	{
		$usermolog = array(
			'processedid' => $userinfo['userid']
		);
		log_usermo_action($usermolog, 'usermo_editsignature');
	}

	$vbulletin->url = 'usermo.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editsignature&amp;u=' . $userinfo['userid'] . '#sigpic';
	eval(print_standard_redirect('redirect_usermo_updatethanks'));
}


// ############################### start edit signature ###########################
if ($_REQUEST['do'] == 'editsignature')
{
	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managesignature']) AND !can_moderate(0, 'caneditsigs'))
	{
		eval(standard_error(fetch_error('phpkd_usermo_no_permission_signature')));
	}

	if (!empty($userinfo['userid']) AND !($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
	{
		eval(standard_error(fetch_error('phpkd_usermo_nosignaturepermission', $userinfo['username'])));
	}

	require_once(DIR . '/includes/functions_newpost.php');

	($hook = vBulletinHook::fetch_hook('profile_usermo_editsignature_start')) ? eval($hook) : false;

	if (!$show['usermo_search'])
	{
		// Build the permissions to display
		require_once(DIR . '/includes/class_bbcode.php');
		require_once(DIR . '/includes/class_sigparser.php');

		// Create the parser with the users sig permissions
		$sig_parser =& new vB_SignatureParser($vbulletin, fetch_tag_list(), $userinfo['permissions'], $userinfo['userid']);

		// Build $show variables for each signature bitfield permission
		foreach ($vbulletin->bf_ugp_signaturepermissions AS $bit_name => $bit_value)
		{
			if ($bbcode = preg_match('#canbbcode(\w+)#i', $bit_name, $matches) AND $matches[1])
			{
				$term = $matches[1] == 'link' ? 'URL' : strtoupper($matches[1]);
				$show["$bit_name"] = ($userinfo['permissions']['signaturepermissions'] & $bit_value AND $vbulletin->options['allowedbbcodes'] & @constant('ALLOW_BBCODE_' . $term))  ? true : false;
			}
			else
			{
				$show["$bit_name"] = ($userinfo['permissions']['signaturepermissions'] & $bit_value ? true : false);
			}
		}

		// Build variables for the remaining signature permissions
		$sigperms_display = array(
			'sigmaxchars'     => vb_number_format($userinfo['permissions']['maxchars']),
			'sigmaxlines'     => vb_number_format($userinfo['permissions']['maxlines']),
			'sigpicmaxwidth'  => vb_number_format($userinfo['permissions']['sigpicmaxwidth']),
			'sigpicmaxheight' => vb_number_format($userinfo['permissions']['sigpicmaxheight']),
			'sigpicmaxsize'   => vb_number_format($userinfo['permissions']['sigpicmaxsize'], 1, true)
		);

		if ($preview_error_signature)
		{
			$signature = $preview_error_signature;
		}
		else
		{
			$signature = $userinfo['signature'];
		}

		// Free the memory, unless we need it below.
		if (!$signature)
		{
			unset($sig_parser);
		}

		if ($signature)
		{
			if (!$previewmessage)
			{
				require_once(DIR . '/includes/class_bbcode.php');
				$bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());
				$bbcode_parser->set_parse_userinfo($userinfo, $userinfo['permissions']);
				$previewmessage = $bbcode_parser->parse($signature, 'signature');
			}

			// save a conditional by just overwriting the phrase
			$vbphrase['submit_message'] =& $vbphrase['save_signature'];
			eval('$preview = "' . fetch_template('phpkd_usermo_newpost_preview') . '";');
		}

		require_once(DIR . '/includes/functions_editor.php');

		// set message box width to usercp size
		$stylevar['messagewidth'] = $stylevar['messagewidth_usercp'];
		$editorid = construct_edit_toolbar(
			htmlspecialchars_uni($signature),
			0,
			'signature',
			$userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['allowsmilies']
		);

		$show['canbbcode'] = ($userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['canbbcode']) ? true : false;

		// ############### DISPLAY SIG IMAGE CONTROLS ###############
		require_once(DIR . '/includes/functions_file.php');
		$inimaxattach = fetch_max_upload_size();

		if ($userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic'])
		{
			$show['cansigpic'] = true;
			$show['sigpic_url'] = (ini_get('allow_url_fopen') != 0 OR function_exists('curl_init'));

			$maxnote = '';
			if ($userinfo['permissions']['sigpicmaxsize'] AND ($userinfo['permissions']['sigpicmaxwidth'] OR $userinfo['permissions']['sigpicmaxheight']))
			{
				$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x_y_or_z'], $userinfo['username'], $sigperms_display['sigpicmaxwidth'], $sigperms_display['sigpicmaxheight'], $sigperms_display['sigpicmaxsize']);
			}
			else if ($userinfo['permissions']['sigpicmaxsize'])
			{
				$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x'], $userinfo['username'], $sigperms_display['sigpicmaxsize']);
			}
			else if ($userinfo['permissions']['sigpicmaxwidth'] OR $userinfo['permissions']['sigpicmaxheight'])
			{
				$maxnote = construct_phrase($vbphrase['phpkd_usermo_note_maximum_size_x_y_pixels'], $userinfo['username'], $sigperms_display['sigpicmaxwidth'], $sigperms_display['sigpicmaxheight']);
			}
			$show['maxnote'] = (!empty($maxnote)) ? true : false;

			// Get the current sig image info.
			if ($sig_image = $db->query_first("SELECT dateline, filename, filedata FROM " . TABLE_PREFIX . "sigpic WHERE userid = " . $userinfo['userid']))
			{
				if ($sig_image['filedata'] != '')
				{
					// sigpic stored in the DB
					$sigpicurl = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'type=sigpic&amp;userid=' . $userinfo['userid'] . "&amp;dateline=$sig_image[dateline]";
				}
				else
				{
					// sigpic stored in the FS
					$sigpicurl = $vbulletin->options['sigpicpath'] . '/sigpic' . $userinfo['userid'] . '_' . $userinfo['sigpicrevision'] . '.gif';
				}
			}
			else // No sigpic yet
			{
				$sigpicurl = false;
			}
		}
		else
		{
			$show['cansigpic'] = false;
		}
	}

	construct_usercp_nav('usermo_editsignature');

	$navbits[''] = construct_phrase($vbphrase['phpkd_usermo_edit_signature'], $userinfo['username']);
	$templatename = 'phpkd_usermo_modifysignature';
	$url =& $vbulletin->url;
}

// #############################################################################
// spit out final HTML if we have got this far

if ($templatename != '')
{
	// make navbar
	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	($hook = vBulletinHook::fetch_hook('profile_usermo_complete')) ? eval($hook) : false;

	// shell template
	eval('$HTML = "' . fetch_template($templatename) . '";');
	eval('print_output("' . fetch_template($shelltemplatename) . '");');
}

/*============================================================================*\
|| ########################################################################### ||
|| # Version: 1.2.0
|| # $Revision: 123 $
|| # Released: $Date: 2008-07-22 07:19:36 +0300 (Tue, 22 Jul 2008) $
|| ########################################################################### ||
\*============================================================================*/
?>