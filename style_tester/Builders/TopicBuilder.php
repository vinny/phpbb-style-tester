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

class TopicBuilder extends BaseBuilder
{
	public function build(array $forums, array $users): array
	{
		$db = $this->db; $user = $this->user; $auth = $this->auth;

		if (!function_exists('submit_post'))
		{
			require_once($this->board_dir . 'includes/functions_posting.' . $this->phpEx);
		}

		$topics = [];

		// Let's get a default icon using correct icons_id column
		$sql = 'SELECT icons_id FROM ' . ICONS_TABLE . ' ORDER BY icons_id ASC';
		$result = $db->sql_query_limit($sql, 1);
		$icon_id = $result ? (int) $db->sql_fetchfield('icons_id') : 0;
		$db->sql_freeresult($result);

		// Switch context to Admin and Founder for posting
		$admin_id = $users['val_admin']['user_id'];
		$founder_id = isset($users['founder']['user_id']) ? (int) $users['founder']['user_id'] : 2;

		// Query existing topics to make it idempotent (keyed by forum_id and title)
		$sql = 'SELECT topic_id, forum_id, topic_title, topic_first_post_id FROM ' . TOPICS_TABLE;
		$result = $this->execute_query($sql);
		$existing_topics = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$fid = (int) $row['forum_id'];
			$existing_topics[$fid][utf8_normalize_nfc($row['topic_title'])] = [
				'topic_id' => (int) $row['topic_id'],
				'post_id' => (int) $row['topic_first_post_id'],
			];
		}
		$db->sql_freeresult($result);

		// Topic scenarios
		// 1. News - Announcement (Admin / Founder context)
		$this->switch_user($founder_id);
		$topics['news_announcement'] = $this->create_topic(
			$forums['news'],
			'phpBB Style Tester Suite Initialized!',
			'We are happy to announce the phpBB Style Tester suite is live. This forum represents a comprehensive layout coverage setup.',
			POST_ANNOUNCE,
			[],
			$existing_topics
		);

		// 2. News - Sticky (ValAdmin context)
		$this->switch_user($admin_id);
		$topics['news_sticky'] = $this->create_topic(
			$forums['news'],
			'Read First: Guidelines and Best Practices',
			'Please stick to the style tester guides when submitting extensions or styles.',
			POST_STICKY,
			[],
			$existing_topics
		);

		// 3. Lobby - Normal Topic (Founder context)
		$this->switch_user($founder_id);
		$topics['lobby_normal'] = $this->create_topic(
			$forums['lobby_forum'],
			'Standard Topic Title for Visual Testing',
			'This topic represents a standard discussion topic with a few standard posts and replies.',
			POST_NORMAL,
			[],
			$existing_topics
		);

		// 4. Lobby - Locked Topic (ValAdmin context)
		$this->switch_user($admin_id);
		$topics['lobby_locked'] = $this->create_topic(
			$forums['lobby_forum'],
			'Locked Thread: Layout Archive Feedback',
			'This thread is locked. Users should only be able to view and not reply.',
			POST_NORMAL,
			['topic_status' => ITEM_LOCKED],
			$existing_topics
		);

		// Write moderation log for locking
		if (!function_exists('add_log'))
		{
			require_once($this->board_dir . 'includes/functions.' . $this->phpEx);
		}
		add_log('mod', $forums['lobby_forum'], $topics['lobby_locked']['topic_id'], 'LOG_LOCK', 'Locked Thread: Layout Archive Feedback');

		// 5. Lobby - Topic with Icon (ValRegUser2 context)
		$this->switch_user($users['val_reg_user_2']['user_id']);
		$topics['lobby_icon'] = $this->create_topic(
			$forums['lobby_forum'],
			'Topic with Icon for List Item Alignment Check',
			'This topic has an icon assigned to check padding and visual alignment in viewforum.',
			POST_NORMAL,
			['icon_id' => $icon_id],
			$existing_topics
		);

		// 6. Lobby - Global Announcement (ValGlobMod context)
		$this->switch_user($users['val_glob_mod']['user_id']);
		$topics['support_global'] = $this->create_topic(
			$forums['lobby_forum'],
			'CRITICAL: Standard phpBB 3.3.x Style Rules',
			'This is a global announcement visible in all forums. Make sure your designs are fully responsive.',
			POST_GLOBAL,
			[],
			$existing_topics
		);

		// 7. Lobby - Moved Topic (Topic redirect) (ValRegUser context)
		$this->switch_user($users['val_reg_user']['user_id']);
		$topics['moved_target'] = $this->create_topic(
			$forums['lobby_forum'],
			'Moved Topic: phpBB Style Tester Guidelines',
			'This topic was moved from the Support forum to General Discussion.',
			POST_NORMAL,
			[],
			$existing_topics
		);
		$topics['moved_redirect'] = $this->create_moved_redirect($forums['read_forum'], $topics['moved_target'], 'Moved Topic: phpBB Style Tester Guidelines');

		// Ensure ALL postable forums have content (no 0 content forums)
		$user_index = 0;
		$all_testers = isset($users['all_testers']) ? $users['all_testers'] : [$admin_id];
		foreach ($forums as $key => $forum_id)
		{
			if ($key === 'link_forum' || $key === 'empty_forum')
			{
				continue;
			}

			// Seed multiple verification topics in each postable forum
			for ($j = 1; $j <= 3; $j++)
			{
				$current_poster = $all_testers[$user_index % count($all_testers)];
				$user_index++;
				$this->switch_user($current_poster);

				$this->create_topic(
					$forum_id,
					"Visual Verification Topic {$j}",
					"This is topic {$j} in this forum, designed for checking style alignment, layouts, and formatting.",
					POST_NORMAL,
					[],
					$existing_topics
				);
			}
		}

		// Restore original user context
		$this->restore_user();

		return $topics;
	}

	protected function create_topic(int $forum_id, string $subject, string $message, int $topic_type = POST_NORMAL, array $additional_data = [], array &$existing_topics = []): array
	{
		$db = $this->db; $user = $this->user; $auth = $this->auth;

		$norm_subject = utf8_normalize_nfc($subject);
		if (isset($existing_topics[$forum_id][$norm_subject]))
		{
			return $existing_topics[$forum_id][$norm_subject];
		}

		$uid = $bitfield = $options = '';
		$options = 7; // enable BBCode, Smilies, Urls
		generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

		// Get forum name
		$sql = 'SELECT forum_name FROM ' . FORUMS_TABLE . ' WHERE forum_id = ' . (int) $forum_id;
		$result = $this->execute_query($sql);
		$forum_name = $this->db->sql_fetchfield('forum_name');
		$this->db->sql_freeresult($result);

		$data = array_merge([
			'forum_id'             => $forum_id,
			'topic_id'             => 0,
			'icon_id'              => 0,
			'enable_bbcode'        => true,
			'enable_smilies'       => true,
			'enable_urls'          => true,
			'enable_sig'           => true,
			'message'              => $message,
			'message_md5'          => md5($message),
			'bbcode_bitfield'      => $bitfield,
			'bbcode_uid'           => $uid,
			'post_edit_locked'     => 0,
			'topic_title'          => $subject,
			'notify_set'           => false,
			'notify'               => false,
			'post_time'            => time(),
			'forum_name'           => $forum_name,
			'enable_indexing'      => true,
			'force_approved_state' => true,
			'topic_time_limit'     => 0,
		], $additional_data);

		$poll = [];

		submit_post('post', $subject, $user->data['username'], $topic_type, $poll, $data);

		// If topic_status is locked, update it manually since submit_post creates it unlocked by default
		if (isset($additional_data['topic_status']) && $additional_data['topic_status'] == ITEM_LOCKED)
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . ' SET topic_status = ' . ITEM_LOCKED . ' WHERE topic_id = ' . (int) $data['topic_id'];
			$this->execute_query($sql);
		}

		return [
			'topic_id' => (int) $data['topic_id'],
			'post_id'  => (int) $data['post_id'],
		];
	}

	protected function create_moved_redirect(int $forum_id, array $target_topic, string $subject): int
	{
		$db = $this->db;
		$user = $this->user;

		$topic_id = $target_topic['topic_id'];

		// Check if redirect already exists
		$sql = 'SELECT topic_id FROM ' . TOPICS_TABLE . ' 
			WHERE forum_id = ' . (int) $forum_id . ' 
			AND topic_status = ' . ITEM_MOVED . ' 
			AND topic_moved_id = ' . (int) $topic_id;
		$result = $this->execute_query($sql);
		$moved_topic_id = (int) $db->sql_fetchfield('topic_id');
		$db->sql_freeresult($result);

		if ($moved_topic_id)
		{
			return $moved_topic_id;
		}

		$moved_data = [
			'forum_id' => $forum_id,
			'icon_id' => 0,
			'topic_title' => $subject,
			'topic_poster' => $user->data['user_id'],
			'topic_time' => time(),
			'topic_status' => ITEM_MOVED,
			'topic_type' => POST_NORMAL,
			'topic_moved_id' => $topic_id,
			'topic_visibility' => 1,
		];

		$sql = 'INSERT INTO ' . TOPICS_TABLE . ' ' . $db->sql_build_array('INSERT', $moved_data);
		$this->execute_query($sql);
		$moved_topic_id = $db->sql_nextid();

		// Get forum names for logging
		$sql = 'SELECT forum_name FROM ' . FORUMS_TABLE . ' WHERE forum_id = ' . (int) $forum_id;
		$result = $this->execute_query($sql);
		$old_forum_name = $db->sql_fetchfield('forum_name');
		$db->sql_freeresult($result);

		$sql = 'SELECT forum_id FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . (int) $topic_id;
		$result = $this->execute_query($sql);
		$target_forum_id = (int) $db->sql_fetchfield('forum_id');
		$db->sql_freeresult($result);

		$sql = 'SELECT forum_name FROM ' . FORUMS_TABLE . ' WHERE forum_id = ' . (int) $target_forum_id;
		$result = $this->execute_query($sql);
		$new_forum_name = $db->sql_fetchfield('forum_name');
		$db->sql_freeresult($result);

		// Log the move action
		if (!function_exists('add_log'))
		{
			require_once($this->board_dir . 'includes/functions.' . $this->phpEx);
		}
		add_log('mod', $forum_id, $topic_id, 'LOG_MOVE', $old_forum_name, $new_forum_name);

		return $moved_topic_id;
	}

}

