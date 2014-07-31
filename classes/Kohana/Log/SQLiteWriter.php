<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * SQLite записувач логів. Записує повідомлення та зберігає його в базі даних SQLite.
 *
 * @package    KtretyaK
 * @category   Logging
 * @author     Kohana Team, Kostya Tretyak
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Log_SQLiteWriter extends Log_Writer {
	
	/**
	 * Numeric log level to string lookup table.
	 * @var array
	 */
	protected $_log_levels = array(
		LOG_EMERG   => 'EMERGENCY',
		LOG_ALERT   => 'ALERT',
		LOG_CRIT    => 'CRITICAL',
		LOG_ERR     => 'ERROR',
		LOG_WARNING => 'WARNING',
		LOG_NOTICE  => 'NOTICE',
		LOG_INFO    => 'INFO',
		LOG_DEBUG   => 'DEBUG',
	);

	/**
	 * @var  int  Level to use for stack traces
	 */
	public static $strace_level = LOG_DEBUG;
	
	protected $config;


	/**
	 * Створення нового записувача логів. Перевіряється чи існує каталог
	 * та чи є права на запис для нього.
	 *
	 *     $writer = new Log_SQLite3($directory);
	 *
	 * @param   string  $directory  Каталог для логів
	 * @return  void
	 */
	public function __construct()
	{
		$this->config = Kohana::$config->load('logsqlite');
		
		$directory = $this->config['directory'];
		
		if ( ! is_dir($directory) OR ! is_writable($directory))
		{
			throw new Kohana_Exception('Directory :dir must be writable',
				array(':dir' => Debug::path($directory)));
		}
	}

	/**
	 * Записування кожного повідомлення в Базу Даних SQLite.
	 *
	 *     $writer->write($messages);
	 *
	 * @param   array   $messages
	 * @return  void
	 */
	public function write(array $messages)
	{
		try
		{
			if( ! class_exists('SQLite3'))
				throw new Exception('SQLite3 class does not exist (in php.ini must enable php_sqlite3)');
			
			$file_path = realpath($this->config['directory']).DIRECTORY_SEPARATOR.$this->config['filename'];
			
			$db = new SQLite3($file_path);
			
			$tablename = $this->config['tablename'];
			
			$result = $db->exec("
				create table if not exists $tablename
				(
					id integer not null primary key autoincrement
					,`time` integer
					,`level` text
					,`body` text
					,`trace` text
					,`class` text
					,`function` text
					,`ip` text
					,`url` test
				)

			");
			
			if( ! $result)
				throw new Exception('For some reason can not be created for the log table in the database SQLite');
			
			$db->busyTimeout(3000);

			$stmt = $db->prepare('
				insert into logs
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
					'.time().'
					,:level
					,:body
					,:trace
					,:class
					,:function
					,:ip
					,:url
				)
			');

			if ($stmt === false)
				throw new Exception('There was a glitch in the preparation of the request for a table logs');
			
			$message_str = '';
			foreach ($messages as $message)
				foreach($this->format_message($message) as $row)
				{
					$stmt->bindValue(':level', @$row['level'], SQLITE3_TEXT);
					$stmt->bindValue(':body', @$row['body'], SQLITE3_TEXT);
					$stmt->bindValue(':trace', @$row['trace'], SQLITE3_TEXT);
					$stmt->bindValue(':class', @$row['class'], SQLITE3_TEXT);
					$stmt->bindValue(':function', @$row['function'], SQLITE3_TEXT);
					$stmt->bindValue(':ip', @Request::$client_ip, SQLITE3_TEXT);
					$stmt->bindValue(':url', $_SERVER['REQUEST_URI'], SQLITE3_TEXT);
					$stmt->execute();
					if ($stmt === false)
						throw new Exception('Failed when inserting a new message in the logs table');
				}
		}
		catch(Exception $e)
		{
			$file_log_writer =  new Log_File(APPPATH.'logs');
			$file_log_writer->write($messages);
			
			$message = [];
			$trace = $e->getTrace();
			$message[0] =
			[
				'time'       => time(),
				'level'      => Log::ERROR,
				'body'       => $e->getMessage(),
				'trace'      => $trace,
				'file'       => isset($trace[0]['file']) ? $trace[0]['file'] : NULL,
				'line'       => isset($trace[0]['line']) ? $trace[0]['line'] : NULL,
				'class'      => isset($trace[0]['class']) ? $trace[0]['class'] : NULL,
				'function'   => isset($trace[0]['function']) ? $trace[0]['function'] : NULL,
				'additional' => NULL,
			];
			
			$file_log_writer->write($message);
			
			$this->after_catch();
		}
	}
	
	protected function after_catch(){} // Here you can send a mail, for example
	
	/**
	 * Формат запису логів.
	 *
	 * @param   array   $message
	 * @param   string  $format
	 * @return  array
	 */
	public function format_message(array $message, $format = "body \n in file:line")
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
}
