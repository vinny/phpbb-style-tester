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

abstract class BaseBuilder
{
	/** @var string */
	protected $board_dir;

	/** @var string */
	protected $phpEx;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	public function __construct(string $board_dir, string $phpEx, $db = null, $user = null, $auth = null, $config = null)
	{
		$this->board_dir = $board_dir;
		$this->phpEx = $phpEx;
		$this->db = $db ?: $GLOBALS['db'];
		$this->user = $user ?: $GLOBALS['user'];
		$this->auth = $auth ?: $GLOBALS['auth'];
		$this->config = $config ?: $GLOBALS['config'];
	}

	/**
	 * Execute a database query with basic logging and error handling.
	 *
	 * @param string $sql
	 * @return mixed
	 * @throws \Exception
	 */
	protected function execute_query(string $sql)
	{
		$result = $this->db->sql_query($sql);
		if ($result === false)
		{
			$error = $this->db->sql_error();
			$message = isset($error['message']) ? $error['message'] : 'Unknown error';
			$this->log_error('SQL Error: ' . $message . ' in query: ' . $sql);
			throw new \Exception('Database query failed: ' . $message);
		}
		return $result;
	}

	/**
	 * Begin a database transaction.
	 *
	 * @return void
	 */
	protected function transaction_begin(): void
	{
		$this->db->sql_transaction('begin');
	}

	/**
	 * Commit a database transaction.
	 *
	 * @return void
	 */
	protected function transaction_commit(): void
	{
		$this->db->sql_transaction('commit');
	}

	/**
	 * Rollback a database transaction.
	 *
	 * @return void
	 */
	protected function transaction_rollback(): void
	{
		$this->db->sql_transaction('rollback');
	}

	/**
	 * Log an error.
	 *
	 * @param string $message
	 * @return void
	 */
	protected function log_error(string $message): void
	{
		$userId = (isset($this->user->data['user_id'])) ? (int) $this->user->data['user_id'] : 2;
		$userIp = (isset($this->user->ip)) ? $this->user->ip : '127.0.0.1';

		if (isset($GLOBALS['phpbb_log']) && is_object($GLOBALS['phpbb_log']))
		{
			$GLOBALS['phpbb_log']->add('admin', $userId, $userIp, 'LOG_ADMIN_AUTH_FAIL', time(), [$message]);
		}
		else
		{
			error_log($message);
		}
	}
}
