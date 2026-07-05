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

class AcpCoverage extends BaseCoverage
{
	public function check()
	{
		// Administration control panel relies on standard phpBB installations.
		// We verify config parameters for styling and style tester settings are correct.
		global $config;

		$results = [];

		$results['acp_style_default'] = (isset($config['default_style'])) ? 'PASSED' : 'FAILED';
		$results['acp_default_lang'] = (isset($config['default_lang'])) ? 'PASSED' : 'FAILED';

		return $results;
	}
}
