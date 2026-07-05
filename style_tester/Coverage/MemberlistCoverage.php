<?php
/**
 *
 * phpBB Style Tester
 *
 * @copyright (c) Vinny (https://github.com/vinny)
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace StyleTester\Coverage;
if (!defined('IN_PHPBB'))
{
	exit;
}

class MemberlistCoverage extends BaseCoverage
{
	public function check()
	{
		global $db;

		$results = [];

		// Count members
		$sql = 'SELECT COUNT(user_id) as cnt FROM ' . USERS_TABLE . ' WHERE user_type <> ' . USER_IGNORE;
		$result = $db->sql_query($sql);
		$count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['memberlist_users_count'] = ($count >= 5) ? 'PASSED' : 'FAILED';

		// Count special groups
		$sql = 'SELECT COUNT(group_id) as cnt FROM ' . GROUPS_TABLE . ' WHERE group_type = ' . GROUP_SPECIAL;
		$result = $db->sql_query($sql);
		$groups = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['memberlist_groups_count'] = ($groups >= 3) ? 'PASSED' : 'FAILED';

		return $results;
	}
}
