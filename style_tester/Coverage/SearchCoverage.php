<?php
/**
 *
 * phpBB Style Tester
 *
 * @copyright (c) Vinny (https://github.com/vinny)
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace StyleTester\Coverage;
if (!defined('IN_PHPBB'))
{
	exit;
}

class SearchCoverage extends BaseCoverage
{
	public function check(): array
	{
		global $db;

		$results = [];

		// Count words in search index
		$sql = 'SELECT COUNT(word_id) as cnt FROM ' . SEARCH_WORDLIST_TABLE;
		$result = $db->sql_query($sql);
		$words = $result ? (int) $db->sql_fetchfield('cnt') : 0;
		$db->sql_freeresult($result);
		$results['search_index_words'] = ($words > 0) ? 'PASSED' : 'FAILED';

		return $results;
	}
}
