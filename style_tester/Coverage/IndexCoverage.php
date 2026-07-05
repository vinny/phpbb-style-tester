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

class IndexCoverage extends BaseCoverage
{
	public function check()
	{
		global $db;

		$results = [];

		// Check categories exist
		$sql = 'SELECT COUNT(forum_id) as cnt FROM ' . FORUMS_TABLE . ' WHERE forum_type = ' . FORUM_CAT;
		$result = $db->sql_query($sql);
		$cat_count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['index_categories'] = ($cat_count > 0) ? 'PASSED' : 'FAILED';

		// Check forums exist
		$sql = 'SELECT COUNT(forum_id) as cnt FROM ' . FORUMS_TABLE . ' WHERE forum_type = ' . FORUM_POST;
		$result = $db->sql_query($sql);
		$forum_count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['index_forums'] = ($forum_count > 0) ? 'PASSED' : 'FAILED';

		// Check link forums exist
		$sql = 'SELECT COUNT(forum_id) as cnt FROM ' . FORUMS_TABLE . ' WHERE forum_type = ' . FORUM_LINK;
		$result = $db->sql_query($sql);
		$link_count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['index_link_forums'] = ($link_count > 0) ? 'PASSED' : 'FAILED';

		return $results;
	}
}
