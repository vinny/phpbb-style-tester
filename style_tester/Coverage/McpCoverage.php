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

class McpCoverage extends BaseCoverage
{
	public function check(): array
	{
		global $db;

		$results = [];

		// Count open reports
		$sql = 'SELECT COUNT(report_id) as cnt FROM ' . REPORTS_TABLE . ' WHERE report_closed = 0';
		$result = $db->sql_query($sql);
		$reports = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['mcp_open_reports'] = ($reports > 0) ? 'PASSED' : 'FAILED';

		// Count unapproved posts
		$sql = 'SELECT COUNT(post_id) as cnt FROM ' . POSTS_TABLE . ' WHERE post_visibility = ' . ITEM_UNAPPROVED;
		$result = $db->sql_query($sql);
		$unapproved = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['mcp_unapproved_posts'] = ($unapproved > 0) ? 'PASSED' : 'FAILED';

		return $results;
	}
}
