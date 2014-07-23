<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * SQLite записувач логів. Записує повідомлення та зберігає його в базі даних SQLite.
 *
 * @package    Ktretyak
 * @category   Logging
 * @author     Kohana Team, Kostya Tretyak
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Log_SQLite3 extends Log_Writer {

	/**
	 * @var  string  Каталог, де будуть зберігатись логи
	 */
	protected $_directory;
	
	/**
	 * Створення нового записувача логів. Перевіряється чи існує каталог
	 * та чи є права на запис для нього.
	 *
	 *     $writer = new Log_SQLite3($directory);
	 *
	 * @param   string  $directory  Каталог для логів
	 * @return  void
	 */
	public function __construct($directory)
	{
		if ( ! is_dir($directory) OR ! is_writable($directory))
		{
			throw new Kohana_Exception('Directory :dir must be writable',
				array(':dir' => Debug::path($directory)));
		}

		// Визначення шляху до каталогу
		$this->_directory = realpath($directory).DIRECTORY_SEPARATOR;
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
				throw new Exception('Клас SQLite3 не існує');
			
			$filename = $this->session_config = Kohana::$config->load('sqlite')->get('filename');
			$file_path = $this->_directory.$filename;			
			$db = new SQLite3($file_path);
			$result = $db->exec("
				create table if not exists logs
				(
					id integer not null primary key autoincrement
					,dateinsert integer
					,url test
					,ip text
					,time text
					,`level` text
					,`body` text
					,`file` text
					,`line` integer
					,`class` text
					,`function` text
				)

			");
			
			if( ! $result)
				throw new Exception('По якійсь причині не може створитись таблиця для логів в базі SQLite');
			
			$db->busyTimeout(3000);

			$stmt = $db->prepare('
				insert into logs
				(
					url
					,ip
					,dateinsert
					,time
					,`level`
					,`body`
					,`file`
					,`line`
					,`class`
					,`function`
				)
				values
				(
					:url
					,:ip
					,:dateinsert
					,:time
					,:level
					,:body
					,:file
					,:line
					,:class
					,:function
				)
			');

			if ($stmt === false)
				throw new Exception('Стався збій при підготовці запита для таблиці logs');
			
			$message_str = '';
			foreach ($messages as $message)
				foreach($this->format_message($message) as $row)
				{
					$stmt->bindValue(':url', $_SERVER['REQUEST_URI'], SQLITE3_TEXT);
					$stmt->bindValue(':ip', @Request::$client_ip, SQLITE3_TEXT);
					$stmt->bindValue(':dateinsert', time(), SQLITE3_INTEGER);
					$stmt->bindValue(':time', @$row['time'], SQLITE3_TEXT);
					$stmt->bindValue(':level', @$row['level'], SQLITE3_TEXT);
					$stmt->bindValue(':body', @$row['body'], SQLITE3_TEXT);
					$stmt->bindValue(':file', @$row['file'], SQLITE3_TEXT);
					$stmt->bindValue(':line', @$row['line'], SQLITE3_INTEGER);
					$stmt->bindValue(':class', @$row['class'], SQLITE3_TEXT);
					$stmt->bindValue(':function', @$row['function'], SQLITE3_TEXT);
					$stmt->execute();
					if ($stmt === false)
						throw new Exception('Стався збій при вставці нового повідомлення в таблицю logs');
				}
		}
		catch(Exception $e)
		{
			if(Kohana::$environment !== Kohana::PRODUCTION)
				throw $e; // Покищо, при збої запису логів, видається помилка; пізніше можна це змінити.
		}
	}
	
	/**
	 * Формат запису логів.
	 *
	 * @param   array   $message
	 * @param   string  $format
	 * @return  array
	 */
	public function format_message(array $message, $format = "")
	{
		$message['time'] = Date::formatted_time('@'.$message['time'], Log_Writer::$timestamp, Log_Writer::$timezone, TRUE);
		$message['level'] = $this->_log_levels[$message['level']];
		
		$rows[] = array_filter($message, 'is_scalar');

		if (isset($message['additional']['exception']))
		{
			// Re-use as much as possible, just resetting the body to the trace
			$message['body'] = $message['additional']['exception']->getTraceAsString();
			$message['level'] = $this->_log_levels[Log_Writer::$strace_level];

			$rows[] = array_filter($message, 'is_scalar');
		}

		return $rows;
	}
}
