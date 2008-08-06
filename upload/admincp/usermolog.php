<?php
/*============================================================================*\
|| ########################################################################### ||
|| # Product Name: User Moderating Options                    Version: 1.1.0 # ||
|| # License Type: Custom Paid License                      $Revision$ # ||
|| # ----------------------------------------------------------------------- # ||
|| #                                                                         # ||
|| #         Copyright ©2005-2008 PHP KingDom. All Rights Reserved.          # ||
|| #   This product may not be redistributed in whole or significant part.   # ||
|| #                                                                         # ||
|| # ----------- 'User Moderating Options' IS NOT FREE SOFTWARE ------------ # ||
|| #  http://www.phpkd.org | http://www.phpkd.org/info/license/custompaid    # ||
|| ########################################################################### ||
\*============================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision$');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('logging');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_usermo_log.php');

// ############################# LOG ACTION ###############################
if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['viewlog']))
{
	print_cp_no_permission();
}

log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['phpkd_usermo_log']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'choose';
}

// ###################### Start view #######################
if ($_REQUEST['do'] == 'view')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'      => TYPE_UINT,
		'pagenumber'   => TYPE_UINT,
		'userid'       => TYPE_UINT,
		'usermoaction' => TYPE_STR,
		'orderby'      => TYPE_NOHTML,
		'startdate'    => TYPE_UNIXTIME,
		'enddate'      => TYPE_UNIXTIME,
	));

	$princids = array(
		'user_name'  => $vbphrase['user'],
	);

	$sqlconds = array();
	$hook_query_fields = $hook_query_joins = '';

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}

	if ($vbulletin->GPC['userid'] OR $vbulletin->GPC['usermoaction'])
	{
		if ($vbulletin->GPC['userid'])
		{
			$sqlconds[] = "usermolog.userid = " . $vbulletin->GPC['userid'];
		}
		if ($vbulletin->GPC['usermoaction'])
		{
			$sqlconds[] = "usermolog.action LIKE '%" . $db->escape_string_like($vbulletin->GPC['usermoaction']) . "%'";
		}
	}

	if ($vbulletin->GPC['startdate'])
	{
		$sqlconds[] = "usermolog.dateline >= " . $vbulletin->GPC['startdate'];
	}

	if ($vbulletin->GPC['enddate'])
	{
 		$sqlconds[] = "usermolog.dateline <= " . $vbulletin->GPC['enddate'];
	}

	($hook = vBulletinHook::fetch_hook('admin_usermologviewer_query')) ? eval($hook) : false;

	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "phpkd_usermolog AS usermolog
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
	");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	switch($vbulletin->GPC['orderby'])
	{
		case 'user':
			$order = 'username ASC, dateline DESC';
			break;
		case 'usermoaction':
			$order = 'action ASC, dateline DESC';
			break;
		case 'date':
		default:
			$order = 'dateline DESC';
	}

	$logs = $db->query_read("
		SELECT usermolog.*, user.username, processeduser.username AS user_name, processeduser.userid AS user_id
			$hook_query_fields
		FROM " . TABLE_PREFIX . "phpkd_usermolog AS usermolog
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = usermolog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS processeduser ON (processeduser.userid = usermolog.processedid)
		$hook_join_fields
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
		ORDER BY $order
		LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
	");

	if ($db->num_rows($logs))
	{
		$vbulletin->GPC['usermoaction'] = htmlspecialchars_uni($vbulletin->GPC['usermoaction']);

		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='usermolog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&usermoaction=" . $vbulletin->GPC['usermoaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='usermolog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&usermoaction=" . $vbulletin->GPC['usermoaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='usermolog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&usermoaction=" . $vbulletin->GPC['usermoaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='usermolog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&usermoaction=" . $vbulletin->GPC['usermoaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$totalpages'\">";
		}

		print_form_header('usermolog', 'remove');
		print_description_row(construct_link_code($vbphrase['restart'], "usermolog.php?" . $vbulletin->session->vars['sessionurl'] . ""), 0, 6, 'thead', $stylevar['right']);
		print_table_header(construct_phrase($vbphrase['phpkd_usermo_log_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 6);

		$headings = array();
		$headings[] = $vbphrase['id'];
		$headings[] = "<a href=\"usermolog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&usermoaction=" . $vbulletin->GPC['usermoaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=user&page=" . $vbulletin->GPC['pagenumber'] . "\">" . str_replace(' ', '&nbsp;', $vbphrase['username']) . "</a>";
		$headings[] = "<a href=\"usermolog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&usermoaction=" . $vbulletin->GPC['usermoaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=date&page=" . $vbulletin->GPC['pagenumber'] . "\">" . $vbphrase['date'] . "</a>";
		//$headings[] = "<a href=\"usermolog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&usermoaction=" . $vbulletin->GPC['usermoaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=usermoaction&page=" . $vbulletin->GPC['pagenumber'] . "\">" . $vbphrase['action'] . "</a>";
		$headings[] = $vbphrase['action'];
		$headings[] = $vbphrase['info'];
		$headings[] = str_replace(' ', '&nbsp;', $vbphrase['ip_address']);
		print_cells_row($headings, 1);

		while ($log = $db->fetch_array($logs))
		{
			$cell = array();
			$cell[] = $log['usermologid'];
			$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$log[userid]\"><b>$log[username]</b></a>";
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['dateline']) . '</span>';

			if ($log['type'])
			{
				$phrase = fetch_usermologactions($log['type']);

				if ($unserialized = unserialize($log['action']))
				{
					array_unshift($unserialized, $vbphrase["$phrase"]);
					$log['action'] = call_user_func_array('construct_phrase', $unserialized);
				}
				else
				{
					$log['action'] = construct_phrase($vbphrase["$phrase"], $log['action']);
				}
			}

			$cell[] = $log['action'];

			($hook = vBulletinHook::fetch_hook('admin_usermologviewer_query_loop')) ? eval($hook) : false;

			$celldata = '';
			reset($princids);
			foreach ($princids AS $sqlfield => $output)
			{
				if ($log["$sqlfield"])
				{
					if ($celldata)
					{
						$celldata .= "<br />\n";
					}
					$celldata .= "<b>$output:</b> ";
					switch($sqlfield)
					{
						case 'user_name':
							$celldata .= construct_link_code($log["$sqlfield"], "user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$log[user_id]", true);
							break;
						default:
							$handled = false;
							($hook = vBulletinHook::fetch_hook('admin_usermologviewer_query_linkfield')) ? eval($hook) : false;
							if (!$handled)
							{
								$celldata .= $log["$sqlfield"];
							}
					}
				}
			}

			$cell[] = $celldata;

			$cell[] = '<span class="smallfont">' . iif($log['ipaddress'], "<a href=\"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=gethost&ip=$log[ipaddress]\">$log[ipaddress]</a>", '&nbsp;') . '</span>';

			print_cells_row($cell, 0, 0, -4);
		}

		print_table_footer(6, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	}
	else
	{
		print_stop_message('no_results_matched_your_query');
	}
}

// ###################### Start prune log #######################
if ($_REQUEST['do'] == 'prunelog')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'daysprune'    => TYPE_UINT,
		'userid'       => TYPE_UINT,
		'usermoaction' => TYPE_STR,
	));

	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['prunelog']))
	{
		print_stop_message('phpkd_usermo_log_pruning_permission_restricted');
	}

	$datecut = TIMENOW - (86400 * $vbulletin->GPC['daysprune']);

	$sqlconds = array("dateline < $datecut");
	if ($vbulletin->GPC['userid'])
	{
		$sqlconds[] = "userid = " . $vbulletin->GPC['userid'];

	}
	if ($vbulletin->GPC['usermoaction'])
	{
		$sqlconds[] = "action LIKE '%" . $db->escape_string_like($vbulletin->GPC['usermoaction']) . "%'";
	}

	$logs = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "phpkd_usermolog
		WHERE " . (!empty($sqlconds) ? implode("\r\n\tAND ", $sqlconds) : "") . "
	");
	if ($logs['total'])
	{
		print_form_header('usermolog', 'doprunelog');
		construct_hidden_code('datecut', $datecut);
		construct_hidden_code('usermoaction', $vbulletin->GPC['usermoaction']);
		construct_hidden_code('userid', $vbulletin->GPC['userid']);
		print_table_header($vbphrase['prune_usermo_log']);
		print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_prune_x_log_entries_from_usermo_log'], vb_number_format($logs['total'])));
		print_submit_row($vbphrase['yes'], 0, 0, $vbphrase['no']);
	}
	else
	{
		print_stop_message('no_logs_matched_your_query');
	}

}

// ###################### Start do prune log #######################
if ($_POST['do'] == 'doprunelog')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'datecut'      => TYPE_UINT,
		'usermoaction' => TYPE_STR,
		'userid'       => TYPE_UINT,
	));

	if (!($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['prunelog']))
	{
		print_stop_message('phpkd_usermo_log_pruning_permission_restricted');
	}

	$sqlconds = array("dateline < " . $vbulletin->GPC['datecut']);
	if (!empty($vbulletin->GPC['usermoaction']))
	{
		$sqlconds[] = "action LIKE '%" . $db->escape_string_like($vbulletin->GPC['usermoaction']) . "%'";
	}
	if (!empty($vbulletin->GPC['userid']))
	{
		$sqlconds[] = "userid = " . $vbulletin->GPC['userid'];
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phpkd_usermolog
		WHERE " . (!empty($sqlconds) ? implode("\r\n\tAND ", $sqlconds) : "") . "
	");

	define('CP_REDIRECT', 'usermolog.php?do=choose');
	print_stop_message('pruned_usermo_log_successfully');
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'choose')
{
	$users = $db->query_read("
		SELECT DISTINCT usermolog.userid, user.username
		FROM " . TABLE_PREFIX . "phpkd_usermolog AS usermolog
		INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		ORDER BY username
	");
	$userlist = array('no_value' => $vbphrase['all_log_entries']);
	while ($user = $db->fetch_array($users))
	{
		$userlist["$user[userid]"] = $user['username'];
	}

	print_form_header('usermolog', 'view');
	print_table_header($vbphrase['phpkd_usermo_log_viewer']);
	print_input_row($vbphrase['log_entries_to_show_per_page'], 'perpage', 15);
	print_select_row($vbphrase['show_only_entries_generated_by'], 'userid', $userlist);
	print_time_row($vbphrase['start_date'], 'startdate', 0, 0);
	print_time_row($vbphrase['end_date'], 'enddate', 0, 0);
	print_select_row($vbphrase['order_by'], 'orderby', array('date' => $vbphrase['date'], 'user' => $vbphrase['username']), 'date');
	print_submit_row($vbphrase['view'], 0);

	if ($permissions['phpkdusermo'] & $vbulletin->bf_ugp_phpkdusermo['prunelog'])
	{
		print_form_header('usermolog', 'prunelog');
		print_table_header($vbphrase['prune_usermo_log']);
		print_select_row($vbphrase['remove_entries_logged_by_user'], 'userid', $userlist);
		print_input_row($vbphrase['remove_entries_older_than_days'], 'daysprune', 30);
		print_submit_row($vbphrase['prune_log_entries'], 0);
	}

}

print_cp_footer();

/*============================================================================*\
|| ########################################################################### ||
|| # Version: 1.1.0
|| # $Revision$
|| # Released: $Date$
|| ########################################################################### ||
\*============================================================================*/
?>