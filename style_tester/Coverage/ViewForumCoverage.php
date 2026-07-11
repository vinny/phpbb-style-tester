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

class ViewForumCoverage extends BaseCoverage
{
	public function check(): array
	{
		global $db;

		$results = [];

		// Check topic types
		$types = [
			'POST_NORMAL' => POST_NORMAL,
			'POST_STICKY' => POST_STICKY,
			'POST_ANNOUNCEMENT' => POST_ANNOUNCE,
			'POST_GLOBAL' => POST_GLOBAL,
		];

		foreach ($types as $name => $value)
		{
			$sql = 'SELECT COUNT(topic_id) as cnt FROM ' . TOPICS_TABLE . ' WHERE topic_type = ' . (int) $value;
			$result = $db->sql_query($sql);
			$count = (int) $db->sql_fetchfield('cnt');
			$db->sql_freeresult($result);
			$results['viewforum_type_' . strtolower($name)] = ($count > 0) ? 'PASSED' : 'FAILED';
		}

		// Check locked topics
		$sql = 'SELECT COUNT(topic_id) as cnt FROM ' . TOPICS_TABLE . ' WHERE topic_status = ' . ITEM_LOCKED;
		$result = $db->sql_query($sql);
		$locked = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['viewforum_locked_topic'] = ($locked > 0) ? 'PASSED' : 'FAILED';

		// Check moved redirect topics
		$sql = 'SELECT COUNT(topic_id) as cnt FROM ' . TOPICS_TABLE . ' WHERE topic_status = ' . ITEM_MOVED;
		$result = $db->sql_query($sql);
		$moved = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['viewforum_moved_redirect'] = ($moved > 0) ? 'PASSED' : 'FAILED';

		// Check reported topics
		$sql = 'SELECT COUNT(topic_id) as cnt FROM ' . TOPICS_TABLE . ' WHERE topic_reported = 1';
		$result = $db->sql_query($sql);
		$reported = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['viewforum_reported_topic'] = ($reported > 0) ? 'PASSED' : 'FAILED';

		return $results;
	}
}
