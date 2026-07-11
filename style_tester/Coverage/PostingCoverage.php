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

class PostingCoverage extends BaseCoverage
{
	public function check(): array
	{
		// Posting editor is dynamically rendered and contains posting_body.html.
		// We verify configuration setup for posting options is verified.
		global $config;

		$results = [];

		$results['posting_bbcodes_enabled'] = ($config['allow_bbcode']) ? 'PASSED' : 'FAILED';
		$results['posting_smilies_enabled'] = ($config['allow_smilies']) ? 'PASSED' : 'FAILED';
		$results['posting_attachments_enabled'] = ($config['allow_attachments']) ? 'PASSED' : 'FAILED';

		return $results;
	}
}
