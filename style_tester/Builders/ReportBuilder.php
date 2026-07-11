<?php
/**
 *
 * phpBB Style Tester
 *
 * @copyright (c) Vinny (https://github.com/vinny)
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace StyleTester\Builders;
if (!defined('IN_PHPBB'))
{
	exit;
}

class ReportBuilder extends BaseBuilder
{
	public function build(array $posts, array $users): void
	{
		$db = $this->db;

		if (count($posts) < 2)
		{
			return;
		}

		// Report the second post (e.g. BBCode Reply)
		$target_post_id = $posts[1];

		// Get topic_id and post info for this post
		$sql = 'SELECT topic_id, post_text, bbcode_uid, bbcode_bitfield, enable_bbcode, enable_smilies, enable_magic_url 
			FROM ' . POSTS_TABLE . ' 
			WHERE post_id = ' . (int) $target_post_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			return;
		}

		$topic_id = (int) $row['topic_id'];

		// Check if report already exists for this post to keep it idempotent
		$sql = 'SELECT COUNT(*) as cnt FROM ' . REPORTS_TABLE . ' WHERE post_id = ' . (int) $target_post_id;
		$result = $db->sql_query($sql);
		$report_count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		if ($report_count > 0)
		{
			return;
		}

		// Query a report reason
		$sql = 'SELECT reason_id FROM ' . REPORTS_REASONS_TABLE . ' ORDER BY reason_id ASC';
		$result = $db->sql_query_limit($sql, 1);
		$reason_id = $result ? (int) $db->sql_fetchfield('reason_id') : 1;
		$db->sql_freeresult($result);

		// Reporter user ID
		$reporter_id = $users['val_reg_user']['user_id'];

		// Insert report record
		$report_row = [
			'reason_id' => $reason_id,
			'post_id' => $target_post_id,
			'pm_id' => 0,
			'user_id' => $reporter_id,
			'user_notify' => 0,
			'report_closed' => 0,
			'report_time' => time(),
			'report_text' => 'This post contains layout violations and excessive smileys.',
			'reported_post_text' => $row['post_text'],
			'reported_post_uid' => $row['bbcode_uid'],
			'reported_post_bitfield' => $row['bbcode_bitfield'],
			'reported_post_enable_bbcode' => $row['enable_bbcode'],
			'reported_post_enable_smilies' => $row['enable_smilies'],
			'reported_post_enable_magic_url' => $row['enable_magic_url'],
		];

		$sql = 'INSERT INTO ' . REPORTS_TABLE . ' ' . $db->sql_build_array('INSERT', $report_row);
		$db->sql_query($sql);

		// Update post and topic to show as reported
		$sql = 'UPDATE ' . POSTS_TABLE . ' SET post_reported = 1 WHERE post_id = ' . (int) $target_post_id;
		$db->sql_query($sql);

		$sql = 'UPDATE ' . TOPICS_TABLE . ' SET topic_reported = 1 WHERE topic_id = ' . (int) $topic_id;
		$db->sql_query($sql);
	}
}
