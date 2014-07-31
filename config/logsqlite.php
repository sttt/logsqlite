<?php defined('SYSPATH') or die('No direct script access.');
return [
	
	'directory' => APPPATH . 'logs', // Directory with SQLite Database
	'filename' => 'db.sqlite', // SQLite Database file name
	'tablename' => 'logs', // table name in the SQLite Database
	'default_limit_fetch' => 100, // default_limit_fetch_rows
	'authentication' => false,
	'users' => [
		'admin' => '123', // pair:  username => password
	],
];