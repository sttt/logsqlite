<?php defined('SYSPATH') or exit('No direct script access.');

class Kohana_Model_Log_SQLite
{
	protected $db;
	protected $config;
	
	/**
	 * Numeric log level to string lookup table.
	 * @var array
	 */
	protected $_log_levels = [
		LOG_EMERG   => 'EMERGENCY',
		LOG_ALERT   => 'ALERT',
		LOG_CRIT    => 'CRITICAL',
		LOG_ERR     => 'ERROR',
		LOG_WARNING => 'WARNING',
		LOG_NOTICE  => 'NOTICE',
		LOG_INFO    => 'INFO',
		LOG_DEBUG   => 'DEBUG',
	];
	
	public function __construct($config, $flag)
	{
		if( ! class_exists('SQLite3'))
			throw new Exception('SQLite3 class does not exist');

		$file_path = realpath($config['directory']).DIRECTORY_SEPARATOR.$config['filename'];

		$this->db = new SQLite3($file_path, $flag);
		$this->db->busyTimeout(3000);
		$this->config = $config;
	}
	
	public function create_table_if_not_exists()
	{
		return $this->db->exec(
			"create table if not exists ".$this->config['tablename']."
			(
				`id` integer not null primary key autoincrement
				,`time` integer
				,`level` text
				,`body` text
				,`trace` text
				,`class` text
				,`function` text
				,`ip` text
				,`url` text
			)"
		);
	}
	
	public function create_indexes_if_not_exists()
	{
		$this->db->exec("create index if not exists logs_time on logs(`time`)");
		$this->db->exec("create index if not exists logs_level on logs(`level`)");
	}
	
	public function insert($messages)
	{
		$stmt = $this->db->prepare("
			insert into ".$this->config['tablename']."
			(
				`time`
				,`level`
				,`body`
				,`trace`
				,`class`
				,`function`
				,`ip`
				,`url`
			)
			values
			(
				".time()."
				,:level
				,:body
				,:trace
				,:class
				,:function
				,:ip
				,:url
			)
		");

		if ($stmt === false)
			throw new Exception('Failed when preparation of the request for a table logs');
		
		foreach ($messages as $message)
			foreach($this->format_message($message) as $row)
			{
				$stmt->bindValue(':level', @$row['level'], SQLITE3_TEXT);
				$stmt->bindValue(':body', @$row['body'], SQLITE3_TEXT);
				$stmt->bindValue(':trace', @$row['trace'], SQLITE3_TEXT);
				$stmt->bindValue(':class', @$row['class'], SQLITE3_TEXT);
				$stmt->bindValue(':function', @$row['function'], SQLITE3_TEXT);
				$stmt->bindValue(':ip', Request::$client_ip, SQLITE3_TEXT);
				$stmt->bindValue(':url', $_SERVER['REQUEST_URI'], SQLITE3_TEXT);
				if ($stmt->execute() === false)
					throw new Exception('Failed when inserting a new message in the logs table');
			}
	}
	
	/**
	 * Формат запису логів.
	 *
	 * @param   array   $message
	 * @param   string  $format
	 * @return  array
	 */
	protected function format_message(array $message, $format = "body \n in file:line")
	{
		$message['level'] = $this->_log_levels[$message['level']];
		
		$message['body'] = strtr($format, array_filter($message, 'is_scalar'));
		
		if (isset($message['additional']['exception']))
		{
			$message['trace'] = $message['additional']['exception']->getTraceAsString();
		}
		
		$rows[] = array_filter($message, 'is_scalar');
		
		return $rows;
	}
	
	public function check_if_exitsts_table()
	{
		$res = $this->db->query("select count(*) from sqlite_master where type='table' and name = '".$this->config['tablename']."'")
			->fetchArray(SQLITE3_NUM);
		return $res[0];
	}
	
	public function select($json)
	{
		$levels = array_map([$this->db,'escapeString'], $json->levels);
		$levels = "'".implode("','", $levels)."'";
		
		$stmt = $this->db->prepare
		(
			"select
				`time`
				,`level`
				,`body`
				,`trace`
				,`class`
				,`function`
				,`ip`
				,`url`
			from ".$this->config['tablename']."
			where time between :date_fr and :date_to
				and level in($levels)
				and
				(
					body like :search_text
					or trace like :search_text
				)
			order by `time` desc
			limit :limit
			"
		);

		if ($stmt === false)
			throw new Exception('For some reason, the logs can not be prepared');

		$limit = $json->limit_fetch ?: $this->config['default_limit_fetch'];

		$stmt->bindValue(':date_fr', $json->date_fr, SQLITE3_INTEGER);
		$stmt->bindValue(':date_to', $json->date_to, SQLITE3_INTEGER);
		$stmt->bindValue(':search_text', "%{$json->search_text}%", SQLITE3_TEXT);
		$stmt->bindValue(':limit', $limit, SQLITE3_TEXT);
		if (($results = $stmt->execute()) === false)
			throw new Exception('For some reason, the logs can not be loaded');
		
		$prepare_json['logs'] = [];
		while ($row = $results->fetchArray(SQLITE3_ASSOC))
		{
			$prepare_json['logs'][] = $row;
		}
		return $prepare_json;
	}
}