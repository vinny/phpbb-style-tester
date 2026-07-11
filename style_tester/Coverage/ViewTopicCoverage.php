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

class ViewTopicCoverage extends BaseCoverage
{
	public function check(): array
	{
		global $db;

		$results = [];

		// Check attachment count
		$sql = 'SELECT COUNT(attach_id) as cnt FROM ' . ATTACHMENTS_TABLE;
		$result = $db->sql_query($sql);
		$attachments = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['viewtopic_attachments'] = ($attachments >= 2) ? 'PASSED' : 'FAILED';

		// Check polls exist
		$sql = 'SELECT COUNT(topic_id) as cnt FROM ' . TOPICS_TABLE . ' WHERE poll_title <> \'\'';
		$result = $db->sql_query($sql);
		$polls = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['viewtopic_polls'] = ($polls > 0) ? 'PASSED' : 'FAILED';

		// Check signatures exist
		$sql = 'SELECT COUNT(user_id) as cnt FROM ' . USERS_TABLE . ' WHERE user_sig <> \'\'';
		$result = $db->sql_query($sql);
		$sigs = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['viewtopic_signatures'] = ($sigs > 0) ? 'PASSED' : 'FAILED';

		// Check user ranks exist
		$sql = 'SELECT COUNT(user_id) as cnt FROM ' . USERS_TABLE . ' WHERE user_rank > 0';
		$result = $db->sql_query($sql);
		$ranks = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['viewtopic_user_ranks'] = ($ranks > 0) ? 'PASSED' : 'FAILED';

		// Check avatars exist
		$sql = 'SELECT COUNT(user_id) as cnt FROM ' . USERS_TABLE . ' WHERE user_avatar <> \'\'';
		$result = $db->sql_query($sql);
		$avatars = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		$results['viewtopic_avatars'] = ($avatars > 0) ? 'PASSED' : 'FAILED';

		// Check pagination topic exists (at least 15 posts)
		$sql = 'SELECT t.topic_id, COUNT(p.post_id) as posts_count FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . ' p 
			WHERE t.topic_id = p.topic_id GROUP BY t.topic_id HAVING posts_count >= 15';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$results['viewtopic_pagination'] = $row ? 'PASSED' : 'FAILED';

		return $results;
	}
}
