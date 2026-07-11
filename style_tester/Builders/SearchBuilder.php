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

class SearchBuilder extends BaseBuilder
{
	public function build(array $posts = []): void
	{
		$db = $this->db;
		$config = $this->config;
		global $phpbb_container;

		// Since submit_post() updates the search index automatically,
		// our posts are already indexed.
		// We verify the index table has matches.
		$sql = 'SELECT COUNT(*) as cnt FROM ' . SEARCH_WORDMATCH_TABLE;
		$result = $db->sql_query($sql);
		$count = $result ? (int) $db->sql_fetchfield('cnt') : 0;
		$db->sql_freeresult($result);

		// Fallback: If no matches are indexed, we can manually trigger indexing
		// using the active search backend from the container.
		if ($count === 0 && !empty($posts))
		{
			$search_type = isset($config['search_type']) ? $config['search_type'] : 'search.type.fulltext_native';
			if ($phpbb_container && $phpbb_container->has($search_type))
			{
				try {
					$search = $phpbb_container->get($search_type);
					$sql = 'SELECT post_id, post_subject, post_text, poster_id, forum_id FROM ' . POSTS_TABLE;
					$result = $db->sql_query($sql);
					while ($row = $db->sql_fetchrow($result))
					{
						$search->index('post', $row['post_id'], $row['post_text'], $row['post_subject'], $row['poster_id'], $row['forum_id']);
					}
					$db->sql_freeresult($result);
				} catch (\Exception $e) {
					// Fallback silently if service container is not compiled
				}
			}
		}
	}
}
