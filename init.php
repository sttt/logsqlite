<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Помічені залежності:
 * Pagination;
 * jQuery;
 * bootstrap;
 * angularJS;
 * angularJS (+ module angular-route);
 */

Kohana::$log->attach(new Log_SQLiteWriter(APPPATH.'logs'));

Route::set('logsqlite/index', 'logsqlite(/about)')
	->defaults([
		'directory' => 'Log',
		'controller' => 'SQLiteReader',
		'action' => 'index',
	]);

// Static file serving (CSS, JS, images)
Route::set('logsqlite/media', 'media/logsqlite(/<file>)', array('file' => '.+'))
	->defaults([
		'directory' => 'Log',
		'controller' => 'SQLiteReader',
		'action'     => 'media',
		'file'       => NULL,
	]);