<?php
/**
 *
 * phpBB Style Tester
 *
 * @copyright (c) Vinny (https://github.com/vinny)
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : __DIR__ . '/../';
$phpEx = (defined('PHPEX')) ? PHPEX : 'php';

// Verify CLI execution
if (PHP_SAPI !== 'cli')
{
	echo "Error: This script can only be executed via the command line.\n";
	exit(1);
}

// Bootstrap phpBB core
require($phpbb_root_path . 'common.' . $phpEx);

// Verify localhost execution environment
$server_name = isset($config['server_name']) ? $config['server_name'] : '';
$is_localhost = ($server_name === 'localhost' || $server_name === '127.0.0.1' || $server_name === '::1' || preg_match('/\.(test|local|dev|localhost)$/i', $server_name));

if (!$is_localhost)
{
	echo "Error: This script is restricted to local development environments (localhost, 127.0.0.1, or TLDs .test, .local, .dev, .localhost).\n";
	exit(1);
}

// Set up standard user session (Admin ID = 2 is default founder)
$user->session_begin();
$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = 2';
$result = $db->sql_query($sql);
$user_row = $db->sql_fetchrow($result);
$db->sql_freeresult($result);
if ($user_row)
{
	$user->data = array_merge($user->data, $user_row);
	$user->data['is_registered'] = ($user_row['user_id'] != ANONYMOUS && ($user_row['user_type'] == USER_NORMAL || $user_row['user_type'] == USER_FOUNDER)) ? true : false;
}
$auth->acl($user->data);

echo "==================================================\n";
echo "               phpBB Style Tester                 \n";
echo "==================================================\n";
echo "Running installers and builders...\n";

// Execute Style Tester Installer
require_once(__DIR__ . '/StyleTesterInstaller.php');
$installer = new \StyleTester\StyleTesterInstaller();
$installer->run($phpbb_root_path, $phpEx);

echo "Database seeding and configuration complete!\n\n";
echo "==================================================\n";
echo "       Style Template Visual Coverage Report       \n";
echo "==================================================\n";

// Load Coverage Classes
$coverages = [
	'BaseCoverage',
	'IndexCoverage',
	'ViewForumCoverage',
	'ViewTopicCoverage',
	'PostingCoverage',
	'MemberlistCoverage',
	'SearchCoverage',
	'UcpCoverage',
	'McpCoverage',
	'AcpCoverage',
];

foreach ($coverages as $cov)
{
	require_once(__DIR__ . '/Coverage/' . $cov . '.' . $phpEx);
}

// Concrete checkers
$checkers = [
	new \StyleTester\Coverage\IndexCoverage($phpbb_root_path, $phpEx),
	new \StyleTester\Coverage\ViewForumCoverage($phpbb_root_path, $phpEx),
	new \StyleTester\Coverage\ViewTopicCoverage($phpbb_root_path, $phpEx),
	new \StyleTester\Coverage\PostingCoverage($phpbb_root_path, $phpEx),
	new \StyleTester\Coverage\MemberlistCoverage($phpbb_root_path, $phpEx),
	new \StyleTester\Coverage\SearchCoverage($phpbb_root_path, $phpEx),
	new \StyleTester\Coverage\UcpCoverage($phpbb_root_path, $phpEx),
	new \StyleTester\Coverage\McpCoverage($phpbb_root_path, $phpEx),
	new \StyleTester\Coverage\AcpCoverage($phpbb_root_path, $phpEx),
];

$passed_count = 0;
$failed_count = 0;

foreach ($checkers as $checker)
{
	$class_name = (new ReflectionClass($checker))->getShortName();
	echo "\nChecking coverage for: {$class_name}\n";
	echo str_repeat("-", 40) . "\n";

	$results = $checker->check();
	foreach ($results as $item => $status)
	{
		$status_str = ($status === 'PASSED') ? "\033[32m[ PASSED ]\033[0m" : "\033[31m[ FAILED ]\033[0m";
		// Fallback without color escape codes if terminal does not support colors
		if (PHP_OS_FAMILY === 'Windows') {
			$status_str = "[ {$status} ]";
		}
		
		printf(" %-35s %s\n", $item, $status_str);

		if ($status === 'PASSED')
		{
			$passed_count++;
		}
		else
		{
			$failed_count++;
		}
	}
}

echo "\n==================================================\n";
echo "Coverage Summary: {$passed_count} Passed, {$failed_count} Failed\n";
echo "==================================================\n";

if ($failed_count > 0)
{
	echo "Warning: Some visual template scenarios did not seed completely.\n";
	exit(1);
}
else
{
	echo "Success: 100% visual template coverage seeded successfully!\n";
	exit(0);
}
