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


if (!defined('VB_AREA'))
{
	exit;
}

$hookobj =& vBulletinHook::init();
require_once(DIR . '/includes/phpkd/usermo_functions.php');

switch (strval($hookobj->last_called))
{
	case 'album_picture_complete':
		{
			if ($show['usermo_editalbum'] AND ($userinfo['permissions']['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managed']))
			{
				if ($show['moderation'])
				{
					$vbulletin->templatecache['album_pictureview'] = str_replace('<div class=\"smallfont normal\" style=\"float: $stylevar[right]\">', '<div class=\"smallfont normal\" style=\"float: $stylevar[right]\"><div><a href=\"album.php?$session[sessionurl]do=approvepic&amp;albumid=$albuminfo[albumid]&amp;pictureid=$pictureinfo[pictureid]\">$vbphrase[phpkd_usermo_approve_this_picture]</a></div>', $vbulletin->templatecache['album_pictureview']);
				}
				else
				{
					$vbulletin->templatecache['album_pictureview'] = str_replace('<div class=\"smallfont normal\" style=\"float: $stylevar[right]\">', '<div class=\"smallfont normal\" style=\"float: $stylevar[right]\"><div><a href=\"album.php?$session[sessionurl]do=moderatepic&amp;albumid=$albuminfo[albumid]&amp;pictureid=$pictureinfo[pictureid]\">$vbphrase[phpkd_usermo_moderate_this_picture]</a></div>', $vbulletin->templatecache['album_pictureview']);
				}
			}
		}
		break;
	case 'album_album_query':
		{
			$usermo_done = false;
		}
		break;
	case 'album_album_picturebit':
		{
			if ($show['usermo_editalbum'] AND ($userinfo['permissions']['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managed']) AND !$usermo_done)
			{
				$vbulletin->templatecache['album_picturebit'] = str_replace('</td>', fetch_template('phpkd_usermo_album_picturebit'), $vbulletin->templatecache['album_picturebit']);
				$usermo_done = true;
			}
		}
		break;
	case 'album_start_postcheck':
		{
			// #######################################################################
			if ($_REQUEST['do'] == 'moderatepic' OR $_REQUEST['do'] == 'approvepic')
			{
				if (!function_exists('is_unalterable_user'))
				{
					require_once(DIR . '/includes/adminfunctions.php');
				}

				if ($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['logactions'])
				{
					require_once(DIR . '/includes/functions_usermo_log.php');
					$show['logactions'] = true;
				}

				if (is_unalterable_user($userinfo['userid']))
				{
					eval(standard_error(fetch_error('user_is_protected_from_alteration_by_undeletableusers_var')));
				}

				if (!($userinfo['permissions']['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managed']))
				{
					eval(standard_error(fetch_error('phpkd_usermo_usergroup_is_protected_from_alteration')));
				}

				if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managealbum']) AND !can_moderate(0, 'canmoderatepictures'))
				{
					eval(standard_error(fetch_error('phpkd_usermo_no_permission_albumpic')));
				}
			}

			// #######################################################################
			if ($_REQUEST['do'] == 'moderatepic')
			{
				$picturedata =& datamanager_init(fetch_picture_dm_name(), $vbulletin, ERRTYPE_SILENT, 'picture');
				$picturedata->set_existing($pictureinfo);
				$picturedata->set('state', 'moderation');
				$picturedata->save();

				$albumdata =& datamanager_init('Album', $vbulletin, ERRTYPE_ARRAY);
				$albumdata->set_existing($albuminfo);
				$albumdata->rebuild_counts();
				$albumdata->save();


				// LOGGING USERMO
				if ($show['logactions'])
				{
					$usermolog = array(
						'processedid' => $pictureinfo['pictureid']
					);
					log_usermo_action($usermolog, 'usermo_editalbumpic');
				}

				$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]";
	
				eval(print_standard_redirect('pictures_updated'));
			}

			// #######################################################################
			if ($_REQUEST['do'] == 'approvepic')
			{
				$picturedata =& datamanager_init(fetch_picture_dm_name(), $vbulletin, ERRTYPE_SILENT, 'picture');
				$picturedata->set_existing($pictureinfo);
				$picturedata->set('state', 'visible');
				$picturedata->save();

				$albumdata =& datamanager_init('Album', $vbulletin, ERRTYPE_ARRAY);
				$albumdata->set_existing($albuminfo);
				$albumdata->rebuild_counts();
				$albumdata->save();


				// LOGGING USERMO
				if ($show['logactions'])
				{
					$usermolog = array(
						'processedid' => $pictureinfo['pictureid']
					);
					log_usermo_action($usermolog, 'usermo_editalbumpic');
				}

				$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]";
	
				eval(print_standard_redirect('pictures_updated'));
			}
		}
		break;
	case 'cache_templates':
		{
			if ($vbulletin->options['avatarenabled'] AND (($vbulletin->userinfo['permissions']['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['manageavatar']) OR can_moderate(0, 'caneditavatar')))
			{
				$show['usermo_editavatar'] = true;
			}
			else
			{
				$show['usermo_editavatar'] = false;
			}

			if ($vbulletin->options['profilepicenabled'] AND (($vbulletin->userinfo['permissions']['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['manageprofilepicture']) OR can_moderate(0, 'caneditprofilepic')))
			{
				$show['usermo_editprofilepic'] = true;
			}
			else
			{
				$show['usermo_editprofilepic'] = false;
			}

			if ((($vbulletin->userinfo['permissions']['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managesignature']) OR can_moderate(0, 'caneditsigs')))
			{
				$show['usermo_editsignature'] = true;
			}
			else
			{
				$show['usermo_editsignature'] = false;
			}

			if (($vbulletin->userinfo['permissions']['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managealbum']) OR can_moderate(0, 'canmoderatepictures'))
			{
				$show['usermo_editalbum'] = true;
			}
			else
			{
				$show['usermo_editalbum'] = false;
			}


			if ($show['usermo_editavatar'] OR $show['usermo_editprofilepic'] OR $show['usermo_editsignature'])
			{
				$globaltemplates[] = "phpkd_usermo_postbit";
				$globaltemplates[] = "phpkd_usermo_profile";

				// Prepare Debiugging Data!
				if (!$usermo AND $usermo_data = unserialize($vbulletin->options['phpkd_custompaid37_data']))
				{
					$usermo =& $usermo_data['phpkd_usermo'];
				}
			}
		}
		break;
	case 'member_execute_start':
		{
			if (!function_exists('is_unalterable_user'))
			{
				require_once(DIR . '/includes/adminfunctions.php');
			}

			if ($vbulletin->userinfo['userid'] != $vbulletin->GPC['userid'] AND $vbulletin->GPC['userid'] != 0 AND !is_unalterable_user($vbulletin->GPC['userid']) AND ($userperms['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['managed']))
			{
				if (!($userperms['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic']))
				{
					$show['usermo_editprofilepic'] = false;
				}

				if (!($userperms['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
				{
					$show['usermo_editsignature'] = false;
				}


				if ($show['usermo_editavatar'] OR $show['usermo_editprofilepic'] OR $show['usermo_editsignature'])
				{
					eval('$usermo_options .= " ' . fetch_template('phpkd_usermo_profile') . '";');
					$vbulletin->templatecache['MEMBERINFO'] = str_replace(array('<ul class=\"thead block_row block_title list_no_decoration floatcontainer\">', '<!-- / link bar -->'), array('<ul class=\"thead block_row block_title list_no_decoration floatcontainer\"><li class=\"thead\" id=\"usermo\"><a href=\"#usermo\">$vbphrase[phpkd_usermo_nav]</a> <script type=\"text/javascript\">vBmenu.register(\"usermo\");</script></li>', '<!-- / link bar -->$usermo_options'), $vbulletin->templatecache['MEMBERINFO']);
				}
			}
		}
		break;
	case 'showthread_postbit_create':
		{
			if (!function_exists('is_unalterable_user'))
			{
				require_once(DIR . '/includes/adminfunctions.php');
			}
		}
		break;
	case 'postbit_display_complete':
		{
			if ($this->registry->userinfo['userid'] != $this->post['userid'] AND $this->post['userid'] != 0 AND !is_unalterable_user($this->post['userid']) AND ($this->cache['perms'][$this->post['userid']]['phpkdusermo'] & $this->registry->bf_ugp_phpkdusermo['managed']))
			{
				if (!($this->cache['perms'][$this->post['userid']]['genericpermissions'] & $this->registry->bf_ugp_genericpermissions['canprofilepic']))
				{
					$show['usermo_editprofilepic'] = false;
				}

				if (!($this->cache['perms'][$this->post['userid']]['genericpermissions'] & $this->registry->bf_ugp_genericpermissions['canusesignature']))
				{
					$show['usermo_editsignature'] = false;
				}


				if ($show['usermo_editavatar'] OR $show['usermo_editprofilepic'] OR $show['usermo_editsignature'])
				{
					eval('$template_hook[postbit_user_popup] .= " ' . fetch_template('phpkd_usermo_postbit') . '";');
				}
			}
		}
		break;
	case 'template_groups':
		{
			$only['phpkd_usermo_'] = $vbphrase['phpkd_usermo_templates'];
		}
		break;
	case 'usercp_nav_complete':
		{
			if ($show['usermo_editavatar'] OR $show['usermo_editprofilepic'] OR $show['usermo_editsignature'])
			{
				eval('$template_hook[usercp_navbar_bottom] .= " ' . fetch_template('phpkd_usermo_usercp') . '";');
			}
		}
		break;
	case 'usercp_nav_start':
		{
			if ($show['usermo_editavatar'] OR $show['usermo_editprofilepic'] OR $show['usermo_editsignature'])
			{
				$usermo_nav = array();
				if ($show['usermo_editavatar'])
				{
					$usermo_nav[] = "usermo_editavatar";
				}

				if ($show['usermo_editprofilepic'])
				{
					$usermo_nav[] = "usermo_editprofilepic";
				}

				if ($show['usermo_editsignature'])
				{
					$usermo_nav[] = "usermo_editsignature";
				}


				if (is_array($usermo_nav) AND !empty($usermo_nav))
				{
					$cells = array_merge($cells, $usermo_nav);
				}
			}
		}
		break;
	default:
		{
			$hookobj = new vBulletinHook_phpkd_usermo($hookobj->pluginlist, $hookobj->hookusage);
		}
		break;
}


/*============================================================================*\
|| ########################################################################### ||
|| # Version: 1.2.0
|| # $Revision: 123 $
|| # Released: $Date: 2008-07-22 07:19:36 +0300 (Tue, 22 Jul 2008) $
|| ########################################################################### ||
\*============================================================================*/
?>