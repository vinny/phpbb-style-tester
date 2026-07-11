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

class UcpCoverage extends BaseCoverage
{
	public function check(): array
	{
		global $db;

		$results = [];

		// Check PMs exist in database
		$sql = 'SELECT COUNT(msg_id) as cnt FROM ' . PRIVMSGS_TABLE;
		$result = $db->sql_query($sql);
		$pms = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['ucp_pm_inbox_populated'] = ($pms > 0) ? 'PASSED' : 'FAILED';

		// Check folder configuration
		$sql = 'SELECT COUNT(msg_id) as cnt FROM ' . PRIVMSGS_TO_TABLE . ' WHERE folder_id = ' . PRIVMSGS_INBOX;
		$result = $db->sql_query($sql);
		$inbox = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['ucp_pm_folder_inbox'] = ($inbox > 0) ? 'PASSED' : 'FAILED';

		$sql = 'SELECT COUNT(msg_id) as cnt FROM ' . PRIVMSGS_TO_TABLE . ' WHERE folder_id = ' . PRIVMSGS_SENTBOX;
		$result = $db->sql_query($sql);
		$sent = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['ucp_pm_folder_sent'] = ($sent > 0) ? 'PASSED' : 'FAILED';

		return $results;
	}
}
