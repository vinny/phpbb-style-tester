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

class PostBuilder
{
	protected $board_dir;
	protected $phpEx;
	protected $db;
	protected $user;
	protected $auth;
	protected $config;

	public function __construct($board_dir, $phpEx, $db = null, $user = null, $auth = null, $config = null)
	{
		$this->board_dir = $board_dir;
		$this->phpEx = $phpEx;
		$this->db = $db ?: $GLOBALS['db'];
		$this->user = $user ?: $GLOBALS['user'];
		$this->auth = $auth ?: $GLOBALS['auth'];
		$this->config = $config ?: $GLOBALS['config'];
	}

	public function build($forums, $users, $topics)
	{
		$db = $this->db;

		if (!function_exists('submit_post'))
		{
			require_once($this->board_dir . 'includes/functions_posting.' . $this->phpEx);
		}

		$created_posts = [];
		$founder_id = isset($users['founder']['user_id']) ? (int) $users['founder']['user_id'] : 2;

		// Make val_reg_user_2 show as online
		$db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_lastvisit = ' . time() . ' WHERE user_id = ' . (int) $users['val_reg_user_2']['user_id']);

		// 1. BBCode & Smiley Showcase Topic (Authored by user ID 2 / Founder context)
		$bbcode_topic = $this->create_topic(
			$forums['lobby_forum'],
			$founder_id,
			'BBCode & Smiley Showcase',
			'Welcome to the phpBB Style Tester suite. Below is a showcase of common BBCodes, layout styling, and smileys.'
		);
		$created_posts[] = $bbcode_topic['post_id'];

		$bbcode_content = '[b]Bold Text[/b] and [i]Italic Text[/i] and [u]Underlined Text[/u].
[color=red]Red Text[/color], [color=#00AA00]Green Text[/color], [color=blue]Blue Text[/color].
[size=50]Tiny Text (50%)[/size]
[size=150]Huge Text (150%)[/size]

[quote="tester_1"]This is a quote block testing user layout.[/quote]

[quote="tester_3"][quote="tester_1"]This is nested quote level 2.[/quote]Testing deep nesting borders.[/quote]

[code]
// Code block alignment test
function testLayout() {
    console.log("Check border and padding");
}
[/code]

[list]
[*]Unordered List Item 1
[*]Unordered List Item 2
[list=a]
[*]Alphabetical sub-item 1
[*]Alphabetical sub-item 2
[/list]
[/list]

[list=1]
[*]Ordered List Item 1
[*]Ordered List Item 2
[/list]

[url=https://www.phpbb.com]External Link[/url]

Here are some standard smileys to test image alignment:
:) :D ;) :( :o :? 8-) :lol: :x :P :oops: :cry: :evil: :twisted: :roll: :!: :? :idea: :arrow: :geek: :ugeek:';

		$bbcode_reply_id = $this->create_reply(
			$bbcode_topic['topic_id'],
			$forums['lobby_forum'],
			$founder_id,
			'Re: BBCode & Smiley Showcase',
			$bbcode_content
		);
		$created_posts[] = $bbcode_reply_id;

		// 2. Nesting Quote Limits Showcase (Authored by user ID 2 / Founder context)
		$nesting_topic = $this->create_topic(
			$forums['lobby_forum'],
			$founder_id,
			'Nesting Quote Limits Showcase',
			'Initial post to test quote layout depths.'
		);
		$created_posts[] = $nesting_topic['post_id'];

		$nested_content = '[quote="tester_7"][quote="tester_1"][quote="tester_3"][quote="tester_4"]Nested quote level 4[/quote]Nested quote level 3[/quote]Nested quote level 2[/quote]Nested quote level 1[/quote]Reply content testing deep borders.';
		$nesting_reply_id = $this->create_reply(
			$nesting_topic['topic_id'],
			$forums['lobby_forum'],
			$users['val_admin']['user_id'],
			'Re: Nesting Quote Limits Showcase',
			$nested_content
		);
		$created_posts[] = $nesting_reply_id;

		// 3. Very Long Lines and Large Tables (Authored by user ID 2 / Founder context)
		$table_topic = $this->create_topic(
			$forums['lobby_forum'],
			$founder_id,
			'Very Long Lines and Code Table Overflow Showcase',
			'This thread showcases long lines and wide tables to verify horizontal scroll container styles.'
		);
		$created_posts[] = $table_topic['post_id'];

		$table_content = 'Testing extremely long single word that should be wrapped or handled gracefully:
SupercalifragilisticexpialidociousSupercalifragilisticexpialidociousSupercalifragilisticexpialidociousSupercalifragilisticexpialidocious

[code]
-----------------------------------------------------------------------------------------------------------------------------
This is an extremely wide code block line designed to force overflow-x horizontal scrollbars on responsive container elements.
-----------------------------------------------------------------------------------------------------------------------------
[/code]';
		$table_reply_id = $this->create_reply(
			$table_topic['topic_id'],
			$forums['lobby_forum'],
			$founder_id,
			'Re: Very Long Lines and Code Table Overflow Showcase',
			$table_content
		);
		$created_posts[] = $table_reply_id;

		// 4. Pagination Test Topic (Lots of replies to test page counts)
		$pagination_topic = $this->create_topic(
			$forums['lobby_forum'],
			$founder_id,
			'Pagination Test Topic - Multiple Pages',
			'This topic has multiple replies to trigger pagination buttons in topic view.'
		);
		$created_posts[] = $pagination_topic['post_id'];

		$all_testers = isset($users['all_testers']) ? $users['all_testers'] : [
			$users['val_reg_user']['user_id'],
			$users['val_reg_user_2']['user_id'],
			$users['val_reg_user_3']['user_id']
		];
		$reply_authors = array_merge([$founder_id], $all_testers);

		for ($i = 1; $i <= 15; $i++)
		{
			$author_id = $reply_authors[$i % count($reply_authors)];
			$reply_id = $this->create_reply(
				$pagination_topic['topic_id'],
				$forums['lobby_forum'],
				$author_id,
				'Re: Pagination Test Topic - Page Reply ' . $i,
				'This is reply number ' . $i . ' to check pagination element layouts.'
			);
			$created_posts[] = $reply_id;
		}

		// 5. Add Unapproved Post to pagination topic (to test moderator badges)
		$unapproved_reply_id = $this->create_reply(
			$pagination_topic['topic_id'],
			$forums['lobby_forum'],
			$users['val_reg_user_5']['user_id'],
			'Re: Pagination Test Topic - Awaiting Approval',
			'This post is currently unapproved and should only be visible to moderators and administrators.'
		);
		// Update DB to mark as unapproved
		$sql = 'UPDATE ' . POSTS_TABLE . ' SET post_visibility = ' . ITEM_UNAPPROVED . ' WHERE post_id = ' . (int) $unapproved_reply_id;
		$db->sql_query($sql);
		// Update topic to decrement approved posts count and increment unapproved posts count
		if (version_compare(PHPBB_VERSION, '3.1.0', '>='))
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . ' SET topic_posts_approved = topic_posts_approved - 1, topic_posts_unapproved = topic_posts_unapproved + 1, topic_visibility = ' . ITEM_APPROVED . ' WHERE topic_id = ' . (int) $pagination_topic['topic_id'];
			$db->sql_query($sql);
		}

		$created_posts[] = $unapproved_reply_id;

		return $created_posts;
	}

	protected function create_topic($forum_id, $user_id, $subject, $message)
	{
		global $db, $user;

		// Check if topic already exists
		$sql = 'SELECT topic_id, topic_first_post_id FROM ' . TOPICS_TABLE . ' 
			WHERE forum_id = ' . (int) $forum_id . " 
			AND topic_title = '" . $db->sql_escape($subject) . "'";
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if ($row)
		{
			return [
				'topic_id' => (int) $row['topic_id'],
				'post_id' => (int) $row['topic_first_post_id'],
			];
		}

		$this->switch_user($user_id);

		$uid = $bitfield = $options = '';
		$options = 7;
		generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

		$sql = 'SELECT forum_name FROM ' . FORUMS_TABLE . ' WHERE forum_id = ' . (int) $forum_id;
		$result = $db->sql_query($sql);
		$forum_name = $db->sql_fetchfield('forum_name');
		$db->sql_freeresult($result);

		$data = [
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
		];

		$poll = [];
		submit_post('post', $subject, $user->data['username'], POST_NORMAL, $poll, $data);

		$this->restore_user();

		return [
			'topic_id' => (int) $data['topic_id'],
			'post_id'  => (int) $data['post_id'],
		];
	}

	protected function create_reply($topic_id, $forum_id, $user_id, $subject, $message)
	{
		global $db, $user;

		// Check if reply already exists
		$sql = 'SELECT post_id FROM ' . POSTS_TABLE . ' 
			WHERE topic_id = ' . (int) $topic_id . ' 
			AND poster_id = ' . (int) $user_id . " 
			AND post_subject = '" . $db->sql_escape($subject) . "'";
		$result = $db->sql_query($sql);
		$post_id = (int) $db->sql_fetchfield('post_id');
		$db->sql_freeresult($result);
		if ($post_id)
		{
			return $post_id;
		}

		$this->switch_user($user_id);

		$uid = $bitfield = $options = '';
		$options = 7;
		generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

		$sql = 'SELECT forum_name FROM ' . FORUMS_TABLE . ' WHERE forum_id = ' . (int) $forum_id;
		$result = $db->sql_query($sql);
		$forum_name = $db->sql_fetchfield('forum_name');
		$db->sql_freeresult($result);

		$data = [
			'forum_id'             => $forum_id,
			'topic_id'             => $topic_id,
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
		];

		$poll = [];
		submit_post('reply', $subject, $user->data['username'], POST_NORMAL, $poll, $data);

		$this->restore_user();

		return (int) $data['post_id'];
	}

	protected $saved_user_data;

	protected function switch_user($user_id)
	{
		global $db, $user, $auth;

		$this->saved_user_data = $user->data;

		$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result = $db->sql_query($sql);
		$user_row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if ($user_row)
		{
			$user->data = array_merge($user->data, $user_row);
			$user->data['is_registered'] = ($user_row['user_id'] != ANONYMOUS && ($user_row['user_type'] == USER_NORMAL || $user_row['user_type'] == USER_FOUNDER)) ? true : false;
			$auth->acl($user_row);
		}
	}

	protected function restore_user()
	{
		global $user, $auth;
		$user->data = $this->saved_user_data;
		$auth->acl($user->data);
	}
}
