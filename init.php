<?php defined('SYSPATH') or exit('No direct script access.');

Kohana::$log->attach(new Log_SQLiteWriter);

Route::set('logsqlite/index', 'logsqlite')
	->defaults([
		'directory' => 'Log',
		'controller' => 'SQLiteReader',
		'action' => 'index',
	]);

Route::set('logsqlite/media', 'media/logsqlite(/<file>)', ['file' => '.+'])
	->defaults([
		'directory' => 'Log',
		'controller' => 'SQLiteReader',
		'action'     => 'media',
		'file'       => NULL,
	]);