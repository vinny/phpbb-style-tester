<?php
/**
 *
 * phpBB Style Tester
 *
 * @copyright (c) Vinny (https://github.com/vinny)
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace StyleTester;
if (!defined('IN_PHPBB'))
{
	exit;
}

class StyleTesterInstaller
{
	/** @var \phpbb\db\driver\driver_interface|null */
	protected $db;

	/** @var \phpbb\config\config|null */
	protected $config;

	public function run(string $board_dir, string $phpEx): void
	{
		global $db, $cache, $config;

		$this->board_dir = $board_dir;
		$this->phpEx = $phpEx;

		// Load dependencies via autoloader or manual fallback
		$this->setup_autoloading();

		// Get standard dependency instances (DIP) with null coalescing
		$db_instance = $GLOBALS['db'] ?? null;
		$user_instance = $GLOBALS['user'] ?? null;
		$auth_instance = $GLOBALS['auth'] ?? null;
		$config_instance = $GLOBALS['config'] ?? null;

		$this->db = $db_instance;
		$this->config = $config_instance;

		// 2. Users
		$userBuilder = new Builders\UserBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$users = $userBuilder->build();

		// 3. Forums
		$forumBuilder = new Builders\ForumBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$forums = $forumBuilder->build();

		// 4. Topics
		$topicBuilder = new Builders\TopicBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$topics = $topicBuilder->build($forums, $users);

		// 5. Polls
		$pollBuilder = new Builders\PollBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$pollBuilder->build($forums, $users, $topics);

		// 6. Posts
		$postBuilder = new Builders\PostBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$posts = $postBuilder->build($forums, $users, $topics);

		// 7. Attachments
		$attachmentBuilder = new Builders\AttachmentBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$attachmentBuilder->build($posts, $users);

		// 8. Reports
		$reportBuilder = new Builders\ReportBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$reportBuilder->build($posts, $users);

		// 9. Private Messages
		$pmBuilder = new Builders\PrivateMessageBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$pmBuilder->build($users, $forums, $topics);

		// 10. Notifications
		$notificationBuilder = new Builders\NotificationBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$notificationBuilder->build($users, $forums, $topics, $posts);

		// 11. Search
		$searchBuilder = new Builders\SearchBuilder($this->board_dir, $this->phpEx, $db_instance, $user_instance, $auth_instance, $config_instance);
		$searchBuilder->build($posts);

		// Finalize
		$this->finalize();
	}

	protected function setup_autoloading(): void
	{
		global $phpbb_class_loader;

		if (isset($phpbb_class_loader) && method_exists($phpbb_class_loader, 'set_path'))
		{
			$phpbb_class_loader->set_path('StyleTester\\', $this->board_dir . 'style_tester/');
		}
		else
		{
			$this->load_dependencies();
		}
	}

	protected function load_dependencies(): void
	{
		$builders = [
			'BaseBuilder',
			'UserBuilder',
			'ForumBuilder',
			'TopicBuilder',
			'PollBuilder',
			'PostBuilder',
			'AttachmentBuilder',
			'ReportBuilder',
			'PrivateMessageBuilder',
			'NotificationBuilder',
			'SearchBuilder',
		];

		foreach ($builders as $builder) {
			require_once($this->board_dir . 'style_tester/Builders/' . $builder . '.' . $this->phpEx);
		}
	}

	protected function finalize(): void
	{
		global $cache, $db, $config;

		// Re-sync forum stats
		if (!function_exists('sync'))
		{
			require_once($this->board_dir . 'includes/functions_admin.' . $this->phpEx);
		}
		sync('forum', '', '', false, true);
		sync('topic');

		$cache->purge();

		// Set user 2's lastmark/lastvisit to the past (1 day ago) so new posts/topics appear unread
		$sql = 'UPDATE ' . USERS_TABLE . ' 
			SET user_lastmark = ' . (time() - 86400) . ', 
				user_lastvisit = ' . (time() - 86400) . ' 
			WHERE user_id = 2';
		$db->sql_query($sql);

		// Users count
		$sql = 'SELECT COUNT(user_id) AS cnt FROM ' . USERS_TABLE . ' WHERE user_type <> ' . USER_IGNORE;
		$result = $db->sql_query($sql);
		$num_users = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		// Posts count
		$sql = 'SELECT COUNT(post_id) AS cnt FROM ' . POSTS_TABLE;
		$result = $db->sql_query($sql);
		$num_posts = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		// Topics count
		$sql = 'SELECT COUNT(topic_id) AS cnt FROM ' . TOPICS_TABLE;
		$result = $db->sql_query($sql);
		$num_topics = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		// Newest user
		$sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . ' WHERE user_type <> ' . USER_IGNORE . ' ORDER BY user_id DESC';
		$result = $db->sql_query_limit($sql, 1);
		$newest_user = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		$config->set('num_users', $num_users);
		$config->set('num_posts', $num_posts);
		$config->set('num_topics', $num_topics);

		if ($newest_user)
		{
			$config->set('newest_user_id', $newest_user['user_id']);
			$config->set('newest_username', $newest_user['username']);
			$config->set('newest_user_colour', $newest_user['user_colour']);
		}
	}
}
