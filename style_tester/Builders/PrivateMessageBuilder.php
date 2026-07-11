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

class PrivateMessageBuilder extends BaseBuilder
{
	public function build(array $users, array $forums, array $topics): void
	{
		$db = $this->db; $user = $this->user; $auth = $this->auth;

		if (!function_exists('submit_pm'))
		{
			require_once($this->board_dir . 'includes/functions_privmsgs.' . $this->phpEx);
		}

		$admin = $users['val_admin'];
		$mod = $users['val_glob_mod'];
		$style_author = $users['val_reg_user_2'];
		$reg_user = $users['val_reg_user'];
		$founder_id = isset($users['founder']['user_id']) ? (int) $users['founder']['user_id'] : 2;

		// 1. Unread PM to User 2
		$this->switch_user($admin['user_id']);
		$this->send_pm($founder_id, 'Layout Review Request (Unread)', 'Hello, this is an unread PM to test the notification and inbox count badges in header and UCP.');

		// 2. Read PM to User 2
		$this->switch_user($mod['user_id']);
		$pm_read = $this->send_pm($founder_id, 'Style Guidelines (Read)', 'This PM is already read. Testing read layout styles in UCP.');
		$this->mark_as_read($pm_read, $founder_id);

		// 3. Reported PM to User 2
		$this->switch_user($style_author['user_id']);
		$pm_reported = $this->send_pm($founder_id, 'Spam Advertisement (Reported)', 'Buy our awesome phpBB styles now! Cheap prices, click here!');
		$this->report_pm($pm_reported, $founder_id, $users['val_reg_user']['user_id']); // Reported by user 2

		// 4. Sent PM from User 2
		$this->switch_user($founder_id);
		$this->send_pm($reg_user['user_id'], 'Re: Questions on phpBB Style Tester', 'Sure, you can find all information in the phpBB Style Tester repository.');

		// 5. Random PMs between other tester users
		$all_testers = isset($users['all_testers']) ? $users['all_testers'] : [$admin['user_id'], $mod['user_id'], $style_author['user_id']];
		if (count($all_testers) >= 10)
		{
			// Create some PMs between tester users
			for ($idx = 0; $idx < 5; $idx++)
			{
				$sender = $all_testers[$idx];
				$recipient = $all_testers[$idx + 5];
				$this->switch_user($sender);
				$this->send_pm($recipient, 'Random Tester PM ' . $idx, 'Testing private messages flow between tester users.');
			}
		}

		// Restore original user context
		$this->restore_user();

		$target_topic_id = isset($topics['lobby_normal']['topic_id']) ? (int) $topics['lobby_normal']['topic_id'] : 1;
		$target_forum_id = isset($forums['lobby_forum']) ? (int) $forums['lobby_forum'] : 2;

		// Bookmarks (Bookmark topic)
		$sql = 'SELECT COUNT(*) as cnt FROM ' . BOOKMARKS_TABLE . ' WHERE topic_id = ' . (int) $target_topic_id . ' AND user_id = ' . (int) $founder_id;
		$result = $this->execute_query($sql);
		$cnt = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($result);
		if ($cnt === 0)
		{
			$this->execute_query('INSERT INTO ' . BOOKMARKS_TABLE . ' ' . $this->db->sql_build_array('INSERT', [
				'topic_id' => $target_topic_id,
				'user_id' => $founder_id,
			]));
		}

		// Topic Subscriptions (Watch topic)
		$sql = 'SELECT COUNT(*) as cnt FROM ' . TOPICS_WATCH_TABLE . ' WHERE topic_id = ' . (int) $target_topic_id . ' AND user_id = ' . (int) $founder_id;
		$result = $this->execute_query($sql);
		$cnt = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($result);
		if ($cnt === 0)
		{
			$this->execute_query('INSERT INTO ' . TOPICS_WATCH_TABLE . ' ' . $this->db->sql_build_array('INSERT', [
				'topic_id' => $target_topic_id,
				'user_id' => $founder_id,
				'notify_status' => 0,
			]));
		}

		// Forum Subscriptions (Watch forum)
		$sql = 'SELECT COUNT(*) as cnt FROM ' . FORUMS_WATCH_TABLE . ' WHERE forum_id = ' . (int) $target_forum_id . ' AND user_id = ' . (int) $founder_id;
		$result = $this->execute_query($sql);
		$cnt = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($result);
		if ($cnt === 0)
		{
			$this->execute_query('INSERT INTO ' . FORUMS_WATCH_TABLE . ' ' . $this->db->sql_build_array('INSERT', [
				'forum_id' => $target_forum_id,
				'user_id' => $founder_id,
				'notify_status' => 0,
			]));
		}

		// Drafts (Saved drafts check)
		$sql = 'SELECT COUNT(*) as cnt FROM ' . DRAFTS_TABLE . ' WHERE user_id = ' . (int) $founder_id;
		$result = $this->execute_query($sql);
		$cnt = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($result);
		if ($cnt === 0)
		{
			$this->execute_query('INSERT INTO ' . DRAFTS_TABLE . ' ' . $this->db->sql_build_array('INSERT', [
				'user_id' => $founder_id,
				'topic_id' => 0,
				'forum_id' => $target_forum_id,
				'save_time' => time(),
				'draft_subject' => 'Draft post about visual styles',
				'draft_message' => 'This is a draft message to verify draft listings and editing view within styles.',
			]));
		}
	}

	protected function send_pm(int $recipient_id, string $subject, string $message): int
	{
		$db = $this->db;
		$user = $this->user;

		// Check if PM already exists by sender and subject to keep it idempotent
		$sql = 'SELECT msg_id FROM ' . PRIVMSGS_TABLE . ' 
			WHERE author_id = ' . (int) $user->data['user_id'] . " 
			AND message_subject = '" . $db->sql_escape($subject) . "'";
		$result = $this->execute_query($sql);
		$msg_id = (int) $db->sql_fetchfield('msg_id');
		$db->sql_freeresult($result);
		if ($msg_id)
		{
			return $msg_id;
		}

		$uid = $bitfield = $options = '';
		$options = 7;
		generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

		// Query recipient username
		$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $recipient_id;
		$result = $this->execute_query($sql);
		$recipient_name = $db->sql_fetchfield('username');
		$db->sql_freeresult($result);

		$data = [
			'address_list'      => [
				'u' => [
					$recipient_id => 'to',
				]
			],
			'from_user_id'      => $user->data['user_id'],
			'from_user_ip'      => '127.0.0.1',
			'from_username'     => $user->data['username'],
			'enable_sig'        => true,
			'enable_bbcode'     => true,
			'enable_smilies'    => true,
			'enable_urls'       => true,
			'icon_id'           => 0,
			'bbcode_uid'        => $uid,
			'bbcode_bitfield'   => $bitfield,
			'message'           => $message,
		];

		submit_pm('post', $subject, $data, true);

		return (int) $data['msg_id'];
	}

	protected function mark_as_read(int $msg_id, int $user_id): void
	{
		// Update recipients record to read/not-new using correct pm_unread column
		$sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . ' 
			SET pm_unread = 0, pm_new = 0, folder_id = ' . PRIVMSGS_INBOX . '
			WHERE msg_id = ' . (int) $msg_id . ' AND user_id = ' . (int) $user_id;
		$this->execute_query($sql);

		// Also move it out of the sender\'s outbox to sentbox since it is read!
		$sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . ' 
			SET folder_id = ' . PRIVMSGS_SENTBOX . '
			WHERE msg_id = ' . (int) $msg_id . ' AND folder_id = ' . PRIVMSGS_OUTBOX;
		$this->execute_query($sql);

		// Decrement the recipient\'s unread PM counts
		$sql = 'UPDATE ' . USERS_TABLE . '
			SET user_new_privmsg = CASE WHEN user_new_privmsg > 0 THEN user_new_privmsg - 1 ELSE 0 END,
				user_unread_privmsg = CASE WHEN user_unread_privmsg > 0 THEN user_unread_privmsg - 1 ELSE 0 END
			WHERE user_id = ' . (int) $user_id;
		$this->execute_query($sql);
	}

	protected function report_pm(int $msg_id, int $recipient_id, int $reporter_id): void
	{
		// Check if report already exists for this PM to keep it idempotent
		$sql = 'SELECT COUNT(*) as cnt FROM ' . REPORTS_TABLE . ' WHERE pm_id = ' . (int) $msg_id;
		$result = $this->execute_query($sql);
		$report_count = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($result);
		if ($report_count > 0)
		{
			return;
		}

		// Query a report reason
		$sql = 'SELECT reason_id FROM ' . REPORTS_REASONS_TABLE . ' ORDER BY reason_id ASC';
		$result = $this->db->sql_query_limit($sql, 1);
		$reason_id = $result ? (int) $this->db->sql_fetchfield('reason_id') : 1;
		$this->db->sql_freeresult($result);

		// Get PM text
		$sql = 'SELECT message_text, bbcode_uid, bbcode_bitfield, enable_bbcode, enable_smilies, enable_magic_url 
			FROM ' . PRIVMSGS_TABLE . ' 
			WHERE msg_id = ' . (int) $msg_id;
		$result = $this->execute_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return;
		}

		// Insert report record
		$report_row = [
			'reason_id' => $reason_id,
			'post_id' => 0,
			'pm_id' => $msg_id,
			'user_id' => $reporter_id,
			'user_notify' => 0,
			'report_closed' => 0,
			'report_time' => time(),
			'report_text' => 'This private message contains spam links.',
			'reported_post_text' => $row['message_text'],
			'reported_post_uid' => $row['bbcode_uid'],
			'reported_post_bitfield' => $row['bbcode_bitfield'],
			'reported_post_enable_bbcode' => $row['enable_bbcode'],
			'reported_post_enable_smilies' => $row['enable_smilies'],
			'reported_post_enable_magic_url' => $row['enable_magic_url'],
		];

		$sql = 'INSERT INTO ' . REPORTS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $report_row);
		$this->execute_query($sql);

		// Update PM to show as reported
		$sql = 'UPDATE ' . PRIVMSGS_TABLE . ' SET message_reported = 1 WHERE msg_id = ' . (int) $msg_id;
		$this->execute_query($sql);
	}

}

