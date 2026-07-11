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

class AttachmentBuilder extends BaseBuilder
{
	public function build(array $posts, array $users): void
	{
		$db = $this->db;

		// We need at least one post to attach files to
		if (empty($posts))
		{
			return;
		}

		$target_post_id = $posts[0]; // BBCode Showcase post

		// Get topic_id and poster_id for this post
		$sql = 'SELECT topic_id, poster_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $target_post_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			return;
		}

		$topic_id = (int) $row['topic_id'];
		$poster_id = (int) $row['poster_id'];

		// Create files/ directory if it doesn't exist
		$files_dir = $this->board_dir . 'files/';
		if (!file_exists($files_dir))
		{
			@mkdir($files_dir, 0755, true);
		}

		// Check if attachments already exist for this post to keep it idempotent
		$sql = 'SELECT COUNT(*) as cnt FROM ' . ATTACHMENTS_TABLE . ' WHERE post_msg_id = ' . (int) $target_post_id;
		$result = $db->sql_query($sql);
		$attach_count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);
		if ($attach_count > 0)
		{
			return;
		}

		// Resolve Assets directory path relative to this builder file
		$assets_dir = dirname(__DIR__) . '/Assets/';
		$image_filename = 'style_tester_image.jpg';
		
		$image_content = file_exists($assets_dir . $image_filename) ? file_get_contents($assets_dir . $image_filename) : base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSnsAAAAH0lEQVR42mNkYGD4DwUMIEwMZGBkQOeD5cEcMGl0BgA5CgQDpcX2+wAAAABJRU5ErkJggg==');
		$zip_content = file_exists($assets_dir . 'style_tester_archive.zip') ? file_get_contents($assets_dir . 'style_tester_archive.zip') : base64_decode('UEsFBgAAAAAAAAAAAAAAAAAAAAAAAA==');

		// Attachment files definition (Only JPG image and ZIP archive)
		$attachments = [
			'zip' => [
				'real_name' => 'style_tester_archive.zip',
				'comment' => 'Zip archive file.',
				'content' => $zip_content,
				'mime' => 'application/zip',
			],
			'jpg' => [
				'real_name' => $image_filename,
				'comment' => 'Visual style tester photo test.',
				'content' => $image_content,
				'mime' => 'image/jpeg',
			],
		];

		foreach ($attachments as $ext => $info)
		{
			// Generate physical filename
			$md5 = md5($info['real_name'] . time());
			$physical_name = $poster_id . '_' . $md5;

			// Write to files directory
			file_put_contents($files_dir . $physical_name, $info['content']);

			// Insert attachment DB record
			$attach_row = [
				'post_msg_id' => $target_post_id,
				'topic_id' => $topic_id,
				'in_message' => 0,
				'poster_id' => $poster_id,
				'is_orphan' => 0,
				'physical_filename' => $physical_name,
				'real_filename' => $info['real_name'],
				'download_count' => 10,
				'attach_comment' => $info['comment'],
				'extension' => $ext,
				'mimetype' => $info['mime'],
				'filesize' => strlen($info['content']),
				'filetime' => time(),
				'thumbnail' => 0,
			];

			$sql = 'INSERT INTO ' . ATTACHMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $attach_row);
			$db->sql_query($sql);
		}

		// Update post to indicate it has attachments
		$sql = 'UPDATE ' . POSTS_TABLE . ' SET post_attachment = 1 WHERE post_id = ' . (int) $target_post_id;
		$db->sql_query($sql);
	}
}
