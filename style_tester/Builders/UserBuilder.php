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

class UserBuilder extends BaseBuilder
{
	public function build(): array
	{
		$db = $this->db;
		$config = $this->config;
		global $passwords_manager;

		if (!function_exists('user_add'))
		{
			require_once($this->board_dir . 'includes/functions_user.' . $this->phpEx);
		}

		if (!$passwords_manager)
		{
			global $phpbb_container;
			$passwords_manager = $phpbb_container->get('passwords.manager');
		}

		$hashed_password = $passwords_manager->hash('123456');

		// Fetch standard group IDs
		$sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE . " 
			WHERE group_name IN ('REGISTERED', 'GLOBAL_MODERATORS', 'ADMINISTRATORS')";
		$result = $this->execute_query($sql);
		$groups = [];
		while ($row = $db->sql_fetchrow($result))
		{
			$groups[$row['group_name']] = (int) $row['group_id'];
		}
		$db->sql_freeresult($result);

		// Check how many tester users currently exist
		$sql = "SELECT user_id, username FROM " . USERS_TABLE . " WHERE username LIKE 'tester_%'";
		$result = $this->execute_query($sql);
		$existing_testers = [];
		while ($row = $db->sql_fetchrow($result))
		{
			$existing_testers[(int) $row['user_id']] = $row['username'];
		}
		$db->sql_freeresult($result);

		// If we have fewer than 25, create the missing ones
		$needed_new = 25 - count($existing_testers);
		for ($i = 0; $i < $needed_new; $i++)
		{
			// Create user with temp name
			$temp_username = 'tester_temp_' . time() . '_' . $i;
			$user_row = [
				'username' => $temp_username,
				'user_email' => 'tester_temp_' . $i . '@example.com',
				'user_password' => $hashed_password,
				'group_id' => $groups['REGISTERED'],
				'user_type' => USER_NORMAL,
				'user_colour' => '',
				'user_avatar' => '',
				'user_avatar_type' => '',
				'user_avatar_width' => 0,
				'user_avatar_height' => 0,
				'user_sig' => '',
			];

			$user_id = user_add($user_row);
			if ($user_id)
			{
				$username = 'tester_' . $user_id;
				$sql = "UPDATE " . USERS_TABLE . " SET username = '" . $db->sql_escape($username) . "', username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "' WHERE user_id = " . (int) $user_id;
				$this->execute_query($sql);
				$existing_testers[$user_id] = $username;
			}
		}

		// Sort tester user IDs to ensure deterministic mapping
		ksort($existing_testers);
		$tester_ids = array_keys($existing_testers);

		// Prepare avatar directories
		$avatar_salt = isset($config['avatar_salt']) ? $config['avatar_salt'] : '';
		$avatar_path = isset($config['avatar_path']) ? $config['avatar_path'] : 'images/avatars/upload';
		$destination_dir = $this->board_dir . $avatar_path;

		// Ensure target directory exists
		if (!file_exists($destination_dir))
		{
			@mkdir($destination_dir, 0755, true);
		}

		// Loop through all tester users to set default groups, add to lists, and update avatars/signatures
		for ($idx = 0; $idx < count($tester_ids); $idx++)
		{
			$uid = $tester_ids[$idx];
			$uname = $existing_testers[$uid];

			// Determine standard target group
			if ($idx < 2)
			{
				$target_group = $groups['ADMINISTRATORS'];
			}
			else if ($idx < 4)
			{
				$target_group = $groups['GLOBAL_MODERATORS'];
			}
			else
			{
				$target_group = $groups['REGISTERED'];
			}

			// Clean up target group membership
			if ($target_group !== $groups['ADMINISTRATORS'])
			{
				$sql = 'DELETE FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $uid . ' AND group_id = ' . (int) $groups['ADMINISTRATORS'];
				$this->execute_query($sql);
			}
			if ($target_group !== $groups['GLOBAL_MODERATORS'])
			{
				$sql = 'DELETE FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $uid . ' AND group_id = ' . (int) $groups['GLOBAL_MODERATORS'];
				$this->execute_query($sql);
			}

			// Assign to primary group
			$sql = 'UPDATE ' . USERS_TABLE . ' SET group_id = ' . (int) $target_group . ' WHERE user_id = ' . (int) $uid;
			$this->execute_query($sql);

			// Add to standard group table
			$sql = 'SELECT COUNT(*) as cnt FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $uid . ' AND group_id = ' . (int) $target_group;
			$result = $this->execute_query($sql);
			$in_group = (int) $db->sql_fetchfield('cnt');
			$db->sql_freeresult($result);
			if (!$in_group)
			{
				group_user_add($target_group, array($uid), false, false, true);
			}

			// Avatars: Copy pre-downloaded avatar from Assets folder to the installation's upload folder
			$filename_on_disk = $destination_dir . '/' . $avatar_salt . '_' . $uid . '.png';
			$source_filename = dirname(__DIR__) . '/Assets/avatars/upload/2ada37b10af7c3d1de58e76ee24ac3a5_' . (58 + $idx) . '.png';
			$avatar_copied = false;

			if (file_exists($source_filename))
			{
				$avatar_copied = @copy($source_filename, $filename_on_disk);
			}

			// Fallback: A simple 80x80 PNG placeholder with a unique background color based on user ID
			if (!$avatar_copied)
			{
				$im = @imagecreate(80, 80);
				if ($im)
				{
					$r = ($uid * 37) % 256;
					$g = ($uid * 59) % 256;
					$b = ($uid * 71) % 256;
					imagecolorallocate($im, $r, $g, $b);
					ob_start();
					imagepng($im);
					$avatar_data = ob_get_clean();
					imagedestroy($im);
					@file_put_contents($filename_on_disk, $avatar_data);
					$avatar_copied = true;
				}
			}

			if ($avatar_copied)
			{
				$avatar_db_val = $uid . '_' . time() . '.png';
				$sql = 'UPDATE ' . USERS_TABLE . " 
					SET user_avatar = '" . $db->sql_escape($avatar_db_val) . "', 
						user_avatar_type = 'avatar.driver.upload',
						user_avatar_width = 80,
						user_avatar_height = 80
					WHERE user_id = " . (int) $uid;
				$this->execute_query($sql);
			}

			// Signatures: Every tester user gets a unique, parsed signature
			$sig = "[b]Signature of " . $uname . "[/b]. phpBB Style Tester checklist [i]in progress[/i].";
			$sig_uid = $sig_bitfield = '';
			$sig_options = 7;
			generate_text_for_storage($sig, $sig_uid, $sig_bitfield, $sig_options, true, true, true);

			$sql = 'UPDATE ' . USERS_TABLE . " 
				SET user_sig = '" . $db->sql_escape($sig) . "', 
					user_sig_bbcode_uid = '" . $db->sql_escape($sig_uid) . "', 
					user_sig_bbcode_bitfield = '" . $db->sql_escape($sig_bitfield) . "'
				WHERE user_id = " . (int) $uid;
			$this->execute_query($sql);
		}

		// Also assign a custom avatar to user ID 2 (founder) using Assets
		$founder_filename = $destination_dir . '/' . $avatar_salt . '_2.png';
		$founder_source = dirname(__DIR__) . '/Assets/avatars/upload/2ada37b10af7c3d1de58e76ee24ac3a5_2.png';
		$founder_copied = false;
		if (file_exists($founder_source))
		{
			$founder_copied = @copy($founder_source, $founder_filename);
		}
		if (!$founder_copied)
		{
			$im = @imagecreate(80, 80);
			if ($im)
			{
				imagecolorallocate($im, 70, 130, 180);
				ob_start();
				imagepng($im);
				$founder_avatar_data = ob_get_clean();
				imagedestroy($im);
				@file_put_contents($founder_filename, $founder_avatar_data);
				$founder_copied = true;
			}
		}
		if ($founder_copied)
		{
			$founder_avatar_db = '2_' . time() . '.png';
			$sql = 'UPDATE ' . USERS_TABLE . " 
				SET user_avatar = '" . $db->sql_escape($founder_avatar_db) . "', 
					user_avatar_type = 'avatar.driver.upload',
					user_avatar_width = 80,
					user_avatar_height = 80
				WHERE user_id = 2";
			$this->execute_query($sql);
		}

		// Add warnings for a couple of test users to populate MCP Warnings tab
		$warned_users = [$tester_ids[3], $tester_ids[4]];
		foreach ($warned_users as $w_uid)
		{
			// Check if warning already exists for this user to keep it idempotent
			$sql = 'SELECT COUNT(*) as cnt FROM ' . WARNINGS_TABLE . ' WHERE user_id = ' . (int) $w_uid;
			$result = $this->execute_query($sql);
			$has_warning = (int) $db->sql_fetchfield('cnt');
			$db->sql_freeresult($result);
			if (!$has_warning)
			{
				$warning_row = [
					'user_id' => (int) $w_uid,
					'post_id' => 0,
					'log_id' => 0,
					'warning_time' => time(),
				];
				$sql = 'INSERT INTO ' . WARNINGS_TABLE . ' ' . $db->sql_build_array('INSERT', $warning_row);
				$this->execute_query($sql);

				$sql = 'UPDATE ' . USERS_TABLE . ' 
					SET user_warnings = 1, 
						user_last_warning = ' . time() . ' 
					WHERE user_id = ' . (int) $w_uid;
				$this->execute_query($sql);
			}
		}

		// Map generated users to keys that downstream builders expect
		$users = [
			'val_admin'        => ['user_id' => $tester_ids[0], 'username' => $existing_testers[$tester_ids[0]], 'user_colour' => ''],
			'val_glob_mod'     => ['user_id' => $tester_ids[2], 'username' => $existing_testers[$tester_ids[2]], 'user_colour' => ''],
			'val_reg_user'     => ['user_id' => $tester_ids[3], 'username' => $existing_testers[$tester_ids[3]], 'user_colour' => ''],
			'val_reg_user_2'   => ['user_id' => $tester_ids[4], 'username' => $existing_testers[$tester_ids[4]], 'user_colour' => ''],
			'val_reg_user_3'   => ['user_id' => $tester_ids[5], 'username' => $existing_testers[$tester_ids[5]], 'user_colour' => ''],
			'val_reg_user_4'   => ['user_id' => $tester_ids[6], 'username' => $existing_testers[$tester_ids[6]], 'user_colour' => ''],
			'val_reg_user_5'   => ['user_id' => $tester_ids[7], 'username' => $existing_testers[$tester_ids[7]], 'user_colour' => ''],
			'val_inactive'     => ['user_id' => $tester_ids[8], 'username' => $existing_testers[$tester_ids[8]], 'user_colour' => ''],
			'val_banned'       => ['user_id' => $tester_ids[9], 'username' => $existing_testers[$tester_ids[9]], 'user_colour' => ''],
			'all_testers'      => $tester_ids,
		];

		// Fetch and append the default founder (ID = 2) to the returned users map
		$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = 2';
		$result = $this->execute_query($sql);
		$founder_row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if ($founder_row)
		{
			$users['founder'] = $founder_row;
		}

		return $users;
	}
}
