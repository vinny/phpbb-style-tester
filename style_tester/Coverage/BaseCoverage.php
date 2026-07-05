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

abstract class BaseCoverage
{
	protected $board_dir;
	protected $phpEx;

	public function __construct($board_dir, $phpEx)
	{
		$this->board_dir = $board_dir;
		$this->phpEx = $phpEx;
	}

	abstract public function check();
}
