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


class ForumBuilder extends BaseBuilder
{
	public function build(): array
	{
		$db = $this->db;
		$auth = $this->auth;

		if (!class_exists('acp_forums'))
		{
			require_once($this->board_dir . 'includes/acp/acp_forums.' . $this->phpEx);
		}

		if (!function_exists('copy_forum_permissions'))
		{
			require_once($this->board_dir . 'includes/functions_admin.' . $this->phpEx);
		}

		if (!function_exists('validate_range'))
		{
			require_once($this->board_dir . 'includes/functions_acp.' . $this->phpEx);
		}

		$acp_forums = new \acp_forums();

		// Copy forum icon from Assets to phpBB images folder
		$source_icon = dirname(__DIR__) . '/Assets/logo_small_cosmic.png';
		$dest_icon_dir = $this->board_dir . 'images';
		if (!file_exists($dest_icon_dir))
		{
			@mkdir($dest_icon_dir, 0755, true);
		}
		$dest_icon = $dest_icon_dir . '/logo_small_cosmic.png';
		if (file_exists($source_icon))
		{
			@copy($source_icon, $dest_icon);
		}

		// Locate default category and forum (we do NOT rename them now)
		$sql = 'SELECT forum_id FROM ' . FORUMS_TABLE . ' WHERE forum_type = ' . FORUM_CAT . ' ORDER BY forum_id ASC';
		$result = $db->sql_query_limit($sql, 1);
		$row = $db->sql_fetchrow($result);
		$def_cat_id = $row ? (int) $row['forum_id'] : 1;
		$db->sql_freeresult($result);

		$sql = 'SELECT forum_id FROM ' . FORUMS_TABLE . ' WHERE forum_type = ' . FORUM_POST . ' ORDER BY forum_id ASC';
		$result = $db->sql_query_limit($sql, 1);
		$row = $db->sql_fetchrow($result);
		$def_forum_id = $row ? (int) $row['forum_id'] : 2;
		$db->sql_freeresult($result);

		$forums = [
			'news' => $def_forum_id,
		];

		// Query existing forums by name to prevent duplicates
		$sql = 'SELECT forum_id, forum_name FROM ' . FORUMS_TABLE;
		$result = $this->execute_query($sql);
		$existing_forums = [];
		while ($row = $db->sql_fetchrow($result))
		{
			$existing_forums[utf8_normalize_nfc($row['forum_name'])] = (int) $row['forum_id'];
		}
		$db->sql_freeresult($result);

		// 1. Create Lobby Forum (directly under root index_page parent_id = 0)
		$norm_lobby = utf8_normalize_nfc('Lobby Forum');
		if (isset($existing_forums[$norm_lobby]))
		{
			$lobby_id = $existing_forums[$norm_lobby];
		}
		else
		{
			$lobby_id = $this->create_forum_item([
				'parent_id' => 0,
				'forum_type' => FORUM_POST,
				'forum_name' => 'Lobby Forum',
				'forum_desc' => 'A free postable forum outside of any category on the index page.',
			], $acp_forums, $def_forum_id);
		}
		$forums['lobby_forum'] = $lobby_id;

		// 2. Define Category & Forum Hierarchies
		$structure = [
			'Test Category 1' => [
				'description' => 'First test category containing basic forums.',
				'forums' => [
					'read_forum'   => ['name' => 'Read Forum', 'desc' => 'A forum with no new posts.'],
					'unread_forum' => ['name' => 'Unread Forum', 'desc' => 'A forum containing new unread posts.'],
				]
			],
			'Test Category 2' => [
				'description' => 'Second test category containing subforums.',
				'forums' => [
					'has_subforums' => ['name' => 'Subforum Parent', 'desc' => 'Contains subforums showing nested directory layout.'],
				]
			],
			'Archive' => [
				'description' => 'Read-only archived posts.',
				'forums' => [
					'old_archives' => ['name' => 'Archived Topics', 'desc' => 'Old resolved style tester tasks.', 'status' => ITEM_LOCKED],
				]
			],
			'Special Showcase' => [
				'description' => 'Forums configured with special properties for style testing.',
				'forums' => [
					'link_forum'     => ['name' => 'phpBB Homepage Link', 'desc' => 'A redirecting link forum.', 'type' => FORUM_LINK, 'link' => 'https://www.phpbb.com'],
					'password_forum' => ['name' => 'Password Protected Forum', 'desc' => 'Access is password-gated. Password is: 123456', 'password' => '123456'],
					'private_forum'  => ['name' => 'Private Forum', 'desc' => 'Requires special group permissions to view.'],
					'empty_forum'    => ['name' => 'Empty Forum', 'desc' => 'This forum has no posts or topics.'],
					'forum_with_icon'=> ['name' => 'Forum with Icon', 'desc' => 'This forum showcases a custom forum icon.', 'image' => 'images/logo_small_cosmic.png'],
				]
			]
		];

		foreach ($structure as $cat_name => $cat_info)
		{
			$norm_cat = utf8_normalize_nfc($cat_name);
			if (isset($existing_forums[$norm_cat]))
			{
				$cat_id = $existing_forums[$norm_cat];
			}
			else
			{
				$cat_id = $this->create_forum_item([
					'parent_id' => 0,
					'forum_type' => FORUM_CAT,
					'forum_name' => $cat_name,
					'forum_desc' => $cat_info['description'],
				], $acp_forums, $def_forum_id);
			}

			foreach ($cat_info['forums'] as $key => $forum_info)
			{
				$norm_forum = utf8_normalize_nfc($forum_info['name']);
				if (isset($existing_forums[$norm_forum]))
				{
					$forum_id = $existing_forums[$norm_forum];
				}
				else
				{
					$forum_data = [
						'parent_id' => $cat_id,
						'forum_type' => isset($forum_info['type']) ? $forum_info['type'] : FORUM_POST,
						'forum_name' => $forum_info['name'],
						'forum_desc' => $forum_info['desc'],
					];

					if (isset($forum_info['link']))
					{
						$forum_data['forum_link'] = $forum_info['link'];
					}

					if (isset($forum_info['password']))
					{
						$forum_data['forum_password'] = $forum_info['password'];
						$forum_data['forum_password_confirm'] = $forum_info['password'];
					}

					if (isset($forum_info['status']))
					{
						$forum_data['forum_status'] = $forum_info['status'];
					}

					if (isset($forum_info['image']))
					{
						$forum_data['forum_image'] = $forum_info['image'];
					}

					$forum_id = $this->create_forum_item($forum_data, $acp_forums, $def_forum_id);
				}

				$forums[$key] = $forum_id;

				// If this is the parent forum, build its child subforums
				if ($key === 'has_subforums')
				{
					$norm_sub_a = utf8_normalize_nfc('Child Subforum A');
					if (isset($existing_forums[$norm_sub_a]))
					{
						$sub1 = $existing_forums[$norm_sub_a];
					}
					else
					{
						$sub1 = $this->create_forum_item([
							'parent_id' => $forum_id,
							'forum_type' => FORUM_POST,
							'forum_name' => 'Child Subforum A',
							'forum_desc' => 'Level 1 nested subforum.',
						], $acp_forums, $def_forum_id);
					}

					$norm_sub_b = utf8_normalize_nfc('Child Subforum B');
					if (isset($existing_forums[$norm_sub_b]))
					{
						$sub2 = $existing_forums[$norm_sub_b];
					}
					else
					{
						$sub2 = $this->create_forum_item([
							'parent_id' => $forum_id,
							'forum_type' => FORUM_POST,
							'forum_name' => 'Child Subforum B',
							'forum_desc' => 'Another Level 1 nested subforum.',
						], $acp_forums, $def_forum_id);
					}

					$forums['subforum_a'] = $sub1;
					$forums['subforum_b'] = $sub2;
				}
			}
		}

		// Mark the "Read Forum" as read for the founder administrator (user ID 2)
		if (isset($forums['read_forum']))
		{
			$read_forum_id = $forums['read_forum'];
			$sql = 'DELETE FROM ' . FORUMS_TRACK_TABLE . ' WHERE user_id = 2 AND forum_id = ' . (int) $read_forum_id;
			$this->execute_query($sql);

			$this->execute_query('INSERT INTO ' . FORUMS_TRACK_TABLE . ' ' . $db->sql_build_array('INSERT', [
				'user_id' => 2,
				'forum_id' => $read_forum_id,
				'mark_time' => time() + 3600, // Make it in the future so it's always read
			]));
		}


		// Update forum icon for Forum with Icon (idempotency)
		if (isset($forums['forum_with_icon']))
		{
			$sql = 'UPDATE ' . FORUMS_TABLE . " 
				SET forum_image = 'images/logo_small_cosmic.png' 
				WHERE forum_id = " . (int) $forums['forum_with_icon'];
			$this->execute_query($sql);
		}

		// Parse rules text
		$rules_text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
		$rules_uid = $rules_bitfield = '';
		$rules_options = 7;
		generate_text_for_storage($rules_text, $rules_uid, $rules_bitfield, $rules_options, true, true, true);

		// Update rules for Lobby Forum, Read Forum, and Unread Forum
		$rules_forums = ['lobby_forum', 'read_forum', 'unread_forum'];
		foreach ($rules_forums as $rf)
		{
			if (isset($forums[$rf]))
			{
				$sql = 'UPDATE ' . FORUMS_TABLE . " 
					SET forum_rules = '" . $db->sql_escape($rules_text) . "', 
						forum_rules_uid = '" . $db->sql_escape($rules_uid) . "', 
						forum_rules_bitfield = '" . $db->sql_escape($rules_bitfield) . "', 
						forum_rules_options = " . (int) $rules_options . " 
					WHERE forum_id = " . (int) $forums[$rf];
				$this->execute_query($sql);
			}
		}

		// Query the role ID for Standard Moderator
		$sql = 'SELECT role_id FROM ' . ACL_ROLES_TABLE . " WHERE role_name = 'ROLE_MOD_STANDARD'";
		$result = $this->execute_query($sql);
		$role_id = $result ? (int) $db->sql_fetchfield('role_id') : 11;
		$db->sql_freeresult($result);

		// Assign Administrators (ID 5) and Global Moderators (ID 4) as moderators of default forum in batch
		$groups_to_mod = [
			'ADMINISTRATORS' => 5,
			'GLOBAL_MODERATORS' => 4,
		];

		$acl_group_rows = [];
		foreach ($groups_to_mod as $g_name => $g_id)
		{
			// Check if already assigned to keep it idempotent
			$sql = 'SELECT COUNT(*) as cnt FROM ' . ACL_GROUPS_TABLE . ' 
				WHERE group_id = ' . (int) $g_id . ' 
					AND forum_id = ' . (int) $def_forum_id . ' 
					AND auth_role_id = ' . (int) $role_id;
			$result = $this->execute_query($sql);
			$is_mod = (int) $db->sql_fetchfield('cnt');
			$db->sql_freeresult($result);
			if (!$is_mod)
			{
				$acl_group_rows[] = [
					'group_id' => (int) $g_id,
					'forum_id' => (int) $def_forum_id,
					'auth_option_id' => 0,
					'auth_role_id' => (int) $role_id,
					'auth_setting' => 0,
				];
			}
		}

		if (!empty($acl_group_rows))
		{
			$db->sql_multi_insert(ACL_GROUPS_TABLE, $acl_group_rows);
		}

		$auth->acl_clear_prefetch();

		return $forums;
	}

	protected function create_forum_item(array $data, \acp_forums $acp_forums, int $def_forum_id): int
	{
		$forum_data = array_merge([
			'parent_id'					=> 0,
			'forum_type'				=> FORUM_POST,
			'forum_status'				=> ITEM_UNLOCKED,
			'forum_parents'				=> '',
			'forum_options'				=> 0,
			'forum_name'				=> '',
			'forum_link'				=> '',
			'forum_link_track'			=> false,
			'forum_desc'				=> '',
			'forum_desc_uid'			=> '',
			'forum_desc_options'		=> 7,
			'forum_desc_bitfield'		=> '',
			'forum_rules'				=> '',
			'forum_rules_uid'			=> '',
			'forum_rules_options'		=> 7,
			'forum_rules_bitfield'		=> '',
			'forum_rules_link'			=> '',
			'forum_image'				=> '',
			'forum_style'				=> 0,
			'forum_password'			=> '',
			'forum_password_confirm'	=> '',
			'display_subforum_list'		=> true,
			'display_on_index'			=> true,
			'forum_topics_per_page'		=> 0,
			'enable_indexing'			=> true,
			'enable_icons'				=> true,
			'enable_prune'				=> false,
			'enable_post_review'		=> true,
			'enable_quick_reply'		=> true,
			'prune_days'				=> 7,
			'prune_viewed'				=> 7,
			'prune_freq'				=> 1,
			'prune_old_polls'			=> false,
			'prune_announce'			=> false,
			'prune_sticky'				=> false,
			'forum_password_unset'		=> false,
			'show_active'				=> 1,
		], $data);

		// Parse description text
		generate_text_for_storage($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_bitfield'], $forum_data['forum_desc_options'], false, false, false);

		// Parse rules text if present
		if (!empty($forum_data['forum_rules']))
		{
			generate_text_for_storage($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options'], true, true, true);
		}

		// Run update_forum_data (which adds it)
		$errors = $acp_forums->update_forum_data($forum_data);

		if (count($errors))
		{
			trigger_error(implode('<br />', $errors), E_USER_ERROR);
		}

		// Copy permissions
		copy_forum_permissions($def_forum_id, $forum_data['forum_id']);

		return (int) $forum_data['forum_id'];
	}
}
