<?php
/**
 *
 * phpBB Style Tester
 *
 * @copyright (c) Vinny (https://github.com/vinny)
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace {
	if (!function_exists('user_add'))
	{
		function user_add($user_row) { return 58; }
	}
	if (!function_exists('group_user_add'))
	{
		function group_user_add($group_id, $user_id_ary, $username_ary = false, $group_name = false, $default = false) { return true; }
	}
	if (!function_exists('generate_text_for_storage'))
	{
		function generate_text_for_storage(&$text, &$uid, &$bitfield, &$flags, $allow_bbcode, $allow_urls, $allow_smilies)
		{
			$uid = 'abcde';
			$bitfield = 'gQ==';
		}
	}
	if (!function_exists('utf8_normalize_nfc'))
	{
		function utf8_normalize_nfc($str) { return $str; }
	}
	if (!function_exists('utf8_clean_string'))
	{
		function utf8_clean_string($str) { return strtolower($str); }
	}
	if (!function_exists('utf8_encode_ucr'))
	{
		function utf8_encode_ucr($str) { return $str; }
	}
	if (!function_exists('utf8_strlen'))
	{
		function utf8_strlen($str) { return strlen($str); }
	}
	if (!function_exists('truncate_string'))
	{
		// simple mock that returns original subject
		function truncate_string($string, $max_length = 60, $max_bytes = 255, $allow_reply = true, $append = '') { return $string; }
	}
	if (!class_exists('acp_forums'))
	{
		class acp_forums
		{
			public function update_forum_data(&$forum_data)
			{
				if (!isset($forum_data['forum_id']))
				{
					$forum_data['forum_id'] = 12;
				}
				return [];
			}
		}
	}
}

namespace StyleTester\Tests
{

use PHPUnit\Framework\TestCase;

// Define IN_PHPBB so class files can be required safely
if (!defined('IN_PHPBB'))
{
	define('IN_PHPBB', true);
}

// Define tables constants if not loaded
if (!defined('USERS_TABLE')) { define('USERS_TABLE', 'phpbb_users'); }
if (!defined('USER_GROUP_TABLE')) { define('USER_GROUP_TABLE', 'phpbb_user_group'); }
if (!defined('GROUPS_TABLE')) { define('GROUPS_TABLE', 'phpbb_groups'); }
if (!defined('FORUMS_TABLE')) { define('FORUMS_TABLE', 'phpbb_forums'); }
if (!defined('TOPICS_TABLE')) { define('TOPICS_TABLE', 'phpbb_topics'); }
if (!defined('POSTS_TABLE')) { define('POSTS_TABLE', 'phpbb_posts'); }
if (!defined('PRIVMSGS_TABLE')) { define('PRIVMSGS_TABLE', 'phpbb_privmsgs'); }
if (!defined('PRIVMSGS_TO_TABLE')) { define('PRIVMSGS_TO_TABLE', 'phpbb_privmsgs_to'); }
if (!defined('REPORTS_TABLE')) { define('REPORTS_TABLE', 'phpbb_reports'); }
if (!defined('REPORTS_REASONS_TABLE')) { define('REPORTS_REASONS_TABLE', 'phpbb_reports_reasons'); }
if (!defined('BOOKMARKS_TABLE')) { define('BOOKMARKS_TABLE', 'phpbb_bookmarks'); }
if (!defined('TOPICS_WATCH_TABLE')) { define('TOPICS_WATCH_TABLE', 'phpbb_topics_watch'); }
if (!defined('FORUMS_WATCH_TABLE')) { define('FORUMS_WATCH_TABLE', 'phpbb_forums_watch'); }
if (!defined('FORUMS_TRACK_TABLE')) { define('FORUMS_TRACK_TABLE', 'phpbb_forums_track'); }
if (!defined('DRAFTS_TABLE')) { define('DRAFTS_TABLE', 'phpbb_drafts'); }

// Define phpBB core constants if not loaded
if (!defined('FORUM_CAT')) { define('FORUM_CAT', 0); }
if (!defined('FORUM_POST')) { define('FORUM_POST', 1); }
if (!defined('FORUM_LINK')) { define('FORUM_LINK', 2); }
if (!defined('FORUM_FLAG_ACTIVE_TOPICS')) { define('FORUM_FLAG_ACTIVE_TOPICS', 1); }
if (!defined('FORUM_FLAG_QUICK_REPLY')) { define('FORUM_FLAG_QUICK_REPLY', 32); }
if (!defined('FORUM_FLAG_POST_REVIEW')) { define('FORUM_FLAG_POST_REVIEW', 64); }
if (!defined('ITEM_UNLOCKED')) { define('ITEM_UNLOCKED', 0); }
if (!defined('ITEM_LOCKED')) { define('ITEM_LOCKED', 1); }
if (!defined('ANONYMOUS')) { define('ANONYMOUS', 1); }
if (!defined('USER_NORMAL')) { define('USER_NORMAL', 0); }
if (!defined('USER_FOUNDER')) { define('USER_FOUNDER', 3); }
if (!defined('PRIVMSGS_INBOX')) { define('PRIVMSGS_INBOX', 0); }
if (!defined('PRIVMSGS_OUTBOX')) { define('PRIVMSGS_OUTBOX', -1); }
if (!defined('PRIVMSGS_SENTBOX')) { define('PRIVMSGS_SENTBOX', -2); }
if (!defined('PRIVMSGS_NO_BOX')) { define('PRIVMSGS_NO_BOX', -3); }
if (!defined('PRIVMSGS_HOLD_BOX')) { define('PRIVMSGS_HOLD_BOX', -4); }

// Load the builders
require_once __DIR__ . '/../Builders/BaseBuilder.php';
require_once __DIR__ . '/../Builders/UserBuilder.php';
require_once __DIR__ . '/../Builders/ForumBuilder.php';
require_once __DIR__ . '/../Builders/TopicBuilder.php';
require_once __DIR__ . '/../Builders/PrivateMessageBuilder.php';

/**
 * Mock database driver class for capturing executed queries and dynamically resolving results.
 */
class DatabaseMock
{
	public $queries = [];
	protected $current_result_set = [];

	public function sql_query($sql)
	{
		$this->queries[] = $sql;
		$this->current_result_set = $this->resolve_result($sql);
		return true;
	}

	public function sql_query_limit($sql, $limit, $offset = 0)
	{
		$this->queries[] = $sql . " LIMIT " . (int) $limit;
		$this->current_result_set = $this->resolve_result($sql);
		return true;
	}

	protected function resolve_result($sql)
	{
		if (strpos($sql, 'group_name IN') !== false)
		{
			return [
				['group_id' => 5, 'group_name' => 'ADMINISTRATORS'],
				['group_id' => 4, 'group_name' => 'GLOBAL_MODERATORS'],
				['group_id' => 2, 'group_name' => 'REGISTERED'],
			];
		}
		if (strpos($sql, "username LIKE 'tester_%'") !== false)
		{
			// return 25 pre-existing users to avoid loop overhead
			$users = [];
			for ($i = 58; $i < 58 + 25; $i++)
			{
				$users[] = ['user_id' => $i, 'username' => 'tester_' . $i];
			}
			return $users;
		}
		if (strpos($sql, 'phpbb_user_group') !== false)
		{
			return [['cnt' => 1]];
		}
		if (strpos($sql, 'phpbb_users') !== false && strpos($sql, 'user_id =') !== false)
		{
			preg_match('/user_id\s*=\s*(\d+)/i', $sql, $matches);
			$uid = isset($matches[1]) ? (int) $matches[1] : 2;
			return [['user_id' => $uid, 'username' => 'tester_' . $uid, 'user_type' => USER_NORMAL]];
		}
		if (strpos($sql, 'phpbb_reports_reasons') !== false)
		{
			return [['reason_id' => 1]];
		}
		if (strpos($sql, 'phpbb_privmsgs') !== false && strpos($sql, 'msg_id =') !== false)
		{
			return [['message_text' => 'Spam content', 'bbcode_uid' => 'xxx', 'bbcode_bitfield' => 'yyy', 'enable_bbcode' => 1, 'enable_smilies' => 1, 'enable_magic_url' => 1]];
		}
		if (strpos($sql, 'phpbb_forums') !== false && strpos($sql, 'forum_id =') !== false)
		{
			preg_match('/forum_id\s*=\s*(\d+)/i', $sql, $matches);
			$fid = isset($matches[1]) ? (int) $matches[1] : 1;
			return [['forum_id' => $fid, 'forum_name' => 'Test Forum', 'parent_id' => 0, 'left_id' => 1, 'right_id' => 2, 'forum_type' => 0]];
		}
		if (strpos($sql, 'MAX(right_id)') !== false)
		{
			return [['right_id' => 1]];
		}
		// Default count check mock
		if (strpos($sql, 'COUNT(*)') !== false || strpos($sql, 'COUNT(') !== false)
		{
			return [['cnt' => 0]];
		}
		return [];
	}

	public function sql_fetchrow($result)
	{
		return array_shift($this->current_result_set) ?: false;
	}

	public function sql_fetchfield($field)
	{
		$row = reset($this->current_result_set);
		$this->current_result_set = [];
		return isset($row[$field]) ? $row[$field] : 0;
	}

	public function sql_freeresult($result)
	{
		$this->current_result_set = [];
		return true;
	}

	public function sql_escape($str)
	{
		return addslashes($str);
	}

	public function sql_build_array($type, $array)
	{
		return '() VALUES ()';
	}

	public function sql_transaction($status = 'begin')
	{
		return true;
	}

	public function sql_nextid()
	{
		return 99;
	}

	public function sql_multi_insert($table, &$sql_ary)
	{
		return true;
	}

	public function sql_in_set($field, $array, $negate = false, $allow_empty_set = false)
	{
		return $field . ($negate ? ' NOT IN ' : ' IN ') . '(' . implode(', ', $array) . ')';
	}
}

/**
 * Mock authentication class.
 */
class AuthMock
{
	public $acl_data = [];
	public function acl($user_data)
	{
		return true;
	}
	public function acl_get($option)
	{
		return true;
	}
	public function acl_clear_prefetch($user_id = 0)
	{
		return true;
	}
}

/**
 * Mock request class.
 */
class RequestMock
{
	public function variable($var_name, $default, $multiline = false, $cookie = false)
	{
		return $default;
	}
}

/**
 * Mock logging class.
 */
class LogMock
{
	public function add()
	{
		return true;
	}
}

/**
 * Mock dispatcher class.
 */
class DispatcherMock
{
	public function trigger_event($name, $data = [])
	{
		return $data;
	}
}

/**
 * Mock notification manager.
 */
class NotificationManagerMock
{
	public function add_notifications($type, $data)
	{
		return true;
	}
}

/**
 * Mock container class.
 */
class ContainerMock
{
	public function get($service)
	{
		if ($service === 'passwords.manager')
		{
			return new PasswordsManagerMock();
		}
		if ($service === 'notification_manager' || $service === 'notification.manager')
		{
			return new NotificationManagerMock();
		}
		return new \stdClass();
	}
}



/**
 * Mock passwords manager for UserBuilder test.
 */
class PasswordsManagerMock
{
	public function hash($password)
	{
		return md5($password);
	}
}

/**
 * StyleTesterTest PHPUnit test class
 */
class StyleTesterTest extends TestCase
{
	protected $db_mock;
	protected $user_mock;
	protected $auth_mock;
	protected $config_mock;
	protected $board_dir;
	protected $phpEx;

	protected function setUp(): void
	{
		$this->db_mock = new DatabaseMock();
		$this->user_mock = new \stdClass();
		$this->user_mock->data = ['user_id' => 2, 'username' => 'admin'];
		$this->user_mock->ip = '127.0.0.1';
		$this->auth_mock = new AuthMock();
		$this->config_mock = ['avatar_salt' => '2ada37b10af7c3d1de58e76ee24ac3a5', 'avatar_path' => 'images/avatars/upload'];
		$this->board_dir = __DIR__ . '/../../';
		$this->phpEx = 'php';

		// Set global mocks for builders referencing globals
		$GLOBALS['db'] = $this->db_mock;
		$GLOBALS['user'] = $this->user_mock;
		$GLOBALS['auth'] = $this->auth_mock;
		$GLOBALS['phpbb_dispatcher'] = new DispatcherMock();
		$GLOBALS['request'] = new RequestMock();
		$GLOBALS['phpbb_log'] = new LogMock();
		$GLOBALS['phpbb_container'] = new ContainerMock();
	}

	public function testUserBuilderQueriesAndInsertions()
	{
		// Mock passwords manager globally
		$GLOBALS['passwords_manager'] = new PasswordsManagerMock();

		$builder = new \StyleTester\Builders\UserBuilder(
			$this->board_dir,
			$this->phpEx,
			$this->db_mock,
			$this->user_mock,
			$this->auth_mock,
			$this->config_mock
		);

		// Run builder
		$users = $builder->build();

		// Assert that the config maps administrators, global moderators, and registered groups correctly
		$this->assertNotEmpty($users);
		$this->assertArrayHasKey('val_admin', $users);
		$this->assertArrayHasKey('val_glob_mod', $users);

		// Verify executed queries contain table names
		$queries_str = implode("\n", $this->db_mock->queries);
		$this->assertStringContainsString(USERS_TABLE, $queries_str);
		$this->assertStringContainsString(GROUPS_TABLE, $queries_str);
	}

	public function testForumBuilderQueriesAndInsertions()
	{
		// Mock functions needed by ForumBuilder
		if (!function_exists('copy_forum_permissions'))
		{
			function copy_forum_permissions($src_id, $dest_id) { return true; }
		}

		$builder = new \StyleTester\Builders\ForumBuilder(
			$this->board_dir,
			$this->phpEx,
			$this->db_mock,
			$this->user_mock,
			$this->auth_mock,
			$this->config_mock
		);

		// Run builder
		$forums = $builder->build();

		$this->assertNotEmpty($forums);
		$queries_str = implode("\n", $this->db_mock->queries);
		$this->assertStringContainsString(FORUMS_TABLE, $queries_str);
	}

	public function testPrivateMessageBuilderVerification()
	{
		// Set mock dependencies
		$users_map = [
			'val_admin' => ['user_id' => 58, 'username' => 'tester_58'],
			'val_glob_mod' => ['user_id' => 60, 'username' => 'tester_60'],
			'val_reg_user' => ['user_id' => 64, 'username' => 'tester_64'],
			'val_reg_user_2' => ['user_id' => 62, 'username' => 'tester_62'],
			'founder' => ['user_id' => 2, 'username' => 'admin'],
			'all_testers' => range(58, 82),
		];
		$forums_map = ['lobby_forum' => 12];
		$topics_map = ['lobby_normal' => ['topic_id' => 45]];

		// Mock functions needed by PrivateMessageBuilder
		if (!function_exists('submit_pm'))
		{
			function submit_pm($mode, $subject, &$data, $put_in_outbox = true)
			{
				$data['msg_id'] = 99;
				return true;
			}
		}

		// Mock auth acl call
		$this->auth_mock->acl_data = [];
		if (!method_exists($this->auth_mock, 'acl'))
		{
			$this->auth_mock->acl = function($data) {};
		}

		$builder = new \StyleTester\Builders\PrivateMessageBuilder(
			$this->board_dir,
			$this->phpEx,
			$this->db_mock,
			$this->user_mock,
			$this->auth_mock,
			$this->config_mock
		);

		// Run builder
		$builder->build($users_map, $forums_map, $topics_map);

		// Assert queries were generated to save PMs and watches
		$queries_str = implode("\n", $this->db_mock->queries);
		$this->assertStringContainsString(PRIVMSGS_TABLE, $queries_str);
		$this->assertStringContainsString(PRIVMSGS_TO_TABLE, $queries_str);
		$this->assertStringContainsString(REPORTS_TABLE, $queries_str);
		$this->assertStringContainsString(BOOKMARKS_TABLE, $queries_str);
	}
}

}
