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

class PollBuilder extends BaseBuilder
{
	public function build(array $forums, array $users, array $topics): void
	{
		if (!function_exists('submit_post'))
		{
			require_once($this->board_dir . 'includes/functions_posting.' . $this->phpEx);
		}

		// Switch user context to Admin
		$admin_id = $users['val_admin']['user_id'];
		$this->switch_user($admin_id);

		// Scenario 1: Active Poll (Unvoted by current viewer)
		$this->create_poll_topic(
			$forums['lobby_forum'],
			'Poll Showcase: Favorite phpBB Feature?',
			'Please vote on your favorite feature of the forum software. This poll allows vote changes.',
			[
				'poll_title' => 'Favorite phpBB feature?',
				'poll_options' => [
					'Extension System',
					'Twig Template Engine',
					'Responsive Prosilver style',
					'Database Portability',
				],
				'poll_max_options' => 1,
				'poll_length' => 0,
				'poll_vote_change' => 1,
			]
		);

		// Scenario 2: Voted Poll (Showing results)
		$voted_topic = $this->create_poll_topic(
			$forums['lobby_forum'],
			'Poll Showcase: Voted Poll Results',
			'This topic showcases a poll that has already received votes from community members.',
			[
				'poll_title' => 'Best design aesthetic for styling?',
				'poll_options' => [
					'Dark Mode Prosilver',
					'Glassmorphism Flat',
					'Bento Grid Dashboard',
					'Standard Light Theme',
				],
				'poll_max_options' => 1,
				'poll_length' => 0,
				'poll_vote_change' => 0,
			]
		);

		// Inject votes for Scenario 2
		$this->inject_votes($voted_topic['topic_id'], $users);

		// Restore user context
		$this->restore_user();
	}

	protected function create_poll_topic(int $forum_id, string $subject, string $message, array $poll_ary): array
	{
		$db = $this->db;

		// Check if topic already exists
		$sql = 'SELECT topic_id, topic_first_post_id FROM ' . TOPICS_TABLE . " WHERE topic_title = '" . $db->sql_escape($subject) . "'";
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

		$uid = $bitfield = $options = '';
		$options = 7;
		generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

		// Get forum name
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

		// Structure poll options
		$poll_options = [];
		foreach ($poll_ary['poll_options'] as $option)
		{
			$poll_options[] = trim($option);
		}

		$poll_data = [
			'poll_title' => $poll_ary['poll_title'],
			'poll_options' => $poll_options,
			'poll_max_options' => $poll_ary['poll_max_options'],
			'poll_length' => $poll_ary['poll_length'],
			'poll_vote_change' => $poll_ary['poll_vote_change'],
			'poll_start' => time(),
		];

		submit_post('post', $subject, $this->user->data['username'], POST_NORMAL, $poll_data, $data);

		return [
			'topic_id' => (int) $data['topic_id'],
			'post_id'  => (int) $data['post_id'],
		];
	}

	protected function inject_votes(int $topic_id, array $users): void
	{
		$db = $this->db;

		// Check if votes are already injected for this topic
		$sql = 'SELECT COUNT(*) as cnt FROM ' . POLL_VOTES_TABLE . ' WHERE topic_id = ' . (int) $topic_id;
		$result = $db->sql_query($sql);
		$vote_count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		if ($vote_count > 0)
		{
			return;
		}

		// Retrieve options for this poll
		$sql = 'SELECT poll_option_id FROM ' . POLL_OPTIONS_TABLE . ' WHERE topic_id = ' . (int) $topic_id . ' ORDER BY poll_option_id ASC';
		$result = $db->sql_query($sql);
		$options = [];
		while ($row = $db->sql_fetchrow($result))
		{
			$options[] = (int) $row['poll_option_id'];
		}
		$db->sql_freeresult($result);

		if (empty($options))
		{
			return;
		}

		$totals = array_fill_keys($options, 0);

		// Distribute votes from seeded users
		$user_vote_map = [
			'val_admin' => 0,
			'val_glob_mod' => 0,
			'val_reg_user' => 1,
			'val_reg_user_2' => 1,
			'val_reg_user_3' => 2,
			'val_reg_user_4' => 3,
		];

		foreach ($user_vote_map as $user_key => $option_index)
		{
			if (isset($users[$user_key]) && isset($options[$option_index]))
			{
				$user_id = $users[$user_key]['user_id'];
				$option_id = $options[$option_index];

				$vote_ary = [
					'topic_id' => $topic_id,
					'poll_option_id' => $option_id,
					'vote_user_id' => $user_id,
					'vote_user_ip' => '127.0.0.1',
				];

				$sql = 'INSERT INTO ' . POLL_VOTES_TABLE . ' ' . $db->sql_build_array('INSERT', $vote_ary);
				$db->sql_query($sql);

				$totals[$option_id]++;
			}
		}

		// Update option totals in options table
		foreach ($totals as $opt_id => $total)
		{
			$sql = 'UPDATE ' . POLL_OPTIONS_TABLE . ' SET poll_option_total = ' . (int) $total . ' WHERE topic_id = ' . (int) $topic_id . ' AND poll_option_id = ' . (int) $opt_id;
			$db->sql_query($sql);
		}

		// Update topic table with correct poll_last_vote column
		$sql = 'UPDATE ' . TOPICS_TABLE . ' SET poll_last_vote = ' . time() . ' WHERE topic_id = ' . (int) $topic_id;
		$db->sql_query($sql);
	}
}
