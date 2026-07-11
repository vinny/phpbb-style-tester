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

class NotificationBuilder extends BaseBuilder
{
	public function build(array $users, array $forums, array $topics, array $posts): void
	{
		$db = $this->db;

		// Fetch notification types
		$sql = 'SELECT notification_type_id, notification_type_name FROM ' . NOTIFICATION_TYPES_TABLE;
		$result = $db->sql_query($sql);
		$types = [];
		while ($row = $db->sql_fetchrow($result))
		{
			$types[$row['notification_type_name']] = (int) $row['notification_type_id'];
		}
		$db->sql_freeresult($result);

		// Target user for notifications: Founder (User ID 2)
		$recipient_id = isset($users['founder']['user_id']) ? (int) $users['founder']['user_id'] : 2;

		// Resolve the PM notification type ID dynamically (instead of hardcoding 3)
		$pm_type_id = isset($types['notification.type.pm']) ? $types['notification.type.pm'] : -1;

		// 1. Reply Notification (using lobby_normal instead of general_normal)
		if (isset($types['notification.type.post']) && isset($topics['lobby_normal']) && isset($forums['lobby_forum']))
		{
			$type_id = $types['notification.type.post'];
			$topic_id = $topics['lobby_normal']['topic_id'];
			$post_id = $topics['lobby_normal']['post_id']; // Target post
			$sender_id = $users['val_reg_user']['user_id'];

			$data = [
				'poster_id' => $sender_id,
				'post_username' => '',
				'post_subject' => 'Re: Standard Topic Title for Visual Testing',
				'topic_title' => 'Standard Topic Title for Visual Testing',
				'forum_id' => $forums['lobby_forum'],
			];

			$this->insert_notification($type_id, $post_id, $topic_id, $recipient_id, $data, $pm_type_id);
		}

		// 2. Quote Notification (using lobby_normal instead of general_normal)
		if (isset($types['notification.type.quote']) && isset($topics['lobby_normal']) && isset($forums['lobby_forum']))
		{
			$type_id = $types['notification.type.quote'];
			$topic_id = $topics['lobby_normal']['topic_id'];
			$post_id = $topics['lobby_normal']['post_id'];
			$sender_id = $users['val_glob_mod']['user_id'];

			$data = [
				'poster_id' => $sender_id,
				'post_subject' => 'Re: Standard Topic Title for Visual Testing',
				'topic_title' => 'Standard Topic Title for Visual Testing',
				'forum_id' => $forums['lobby_forum'],
			];

			$this->insert_notification($type_id, $post_id, $topic_id, $recipient_id, $data, $pm_type_id);
		}

		// 3. PM Notification
		if (isset($types['notification.type.pm']))
		{
			$type_id = $types['notification.type.pm'];
			$sender_id = $users['val_admin']['user_id'];

			$data = [
				'from_user_id' => $sender_id,
				'message_subject' => 'Layout Review Request',
			];

			// Query a msg_id from privmsgs
			$sql = 'SELECT msg_id FROM ' . PRIVMSGS_TABLE . ' ORDER BY msg_id DESC';
			$result = $db->sql_query_limit($sql, 1);
			$msg_id = $result ? (int) $db->sql_fetchfield('msg_id') : 1;
			$db->sql_freeresult($result);

			$this->insert_notification($type_id, $msg_id, 0, $recipient_id, $data, $pm_type_id);
		}
	}

	protected function insert_notification(int $type_id, int $item_id, int $parent_id, int $user_id, array $data, int $pm_type_id = -1): void
	{
		$db = $this->db;

		// Check if notification already exists to keep it idempotent
		$sql = 'SELECT COUNT(*) as cnt FROM ' . NOTIFICATIONS_TABLE . ' 
			WHERE notification_type_id = ' . (int) $type_id . ' 
			AND item_id = ' . (int) $item_id . ' 
			AND user_id = ' . (int) $user_id;
		$result = $db->sql_query($sql);
		$noti_count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		if ($noti_count > 0)
		{
			return;
		}

		$insert_ary = [
			'notification_type_id' => $type_id,
			'item_id' => $item_id,
			'item_parent_id' => $parent_id,
			'user_id' => $user_id,
			'notification_read' => 0, // Unread
			'notification_time' => time(),
			'notification_data' => serialize($data),
		];

		$sql = 'INSERT INTO ' . NOTIFICATIONS_TABLE . ' ' . $db->sql_build_array('INSERT', $insert_ary);
		$db->sql_query($sql);

		// Increment recipient's user table notification count (only for PM notifications)
		$sql = 'UPDATE ' . USERS_TABLE . ' 
			SET user_unread_privmsg = user_unread_privmsg + ' . ($type_id === $pm_type_id ? 1 : 0) . ' 
			WHERE user_id = ' . (int) $user_id;
		$db->sql_query($sql);
	}
}
