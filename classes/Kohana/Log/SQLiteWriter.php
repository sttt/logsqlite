<?php defined('SYSPATH') or exit('No direct script access.');
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
	
	protected $config; // Config for DB SQLite


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
		
		if ( ! is_dir($directory) or ! is_writable($directory))
		{
			throw new Kohana_Exception
			(
				'Directory :dir must be writable',
				[':dir' => Debug::path($directory)]
			);
		}
	}
	
	protected function init_model()
	{
		$model = new Model_Log_SQLite($this->config, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
			
		if( ! $model->create_table_if_not_exists() )
			throw new Exception('For some reason the table in SQLite database can not be created');
		
		$model->create_indexes_if_not_exists();
		return $model;
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
			$this->init_model()
				->insert($messages);
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
				'body'       => 'Log_SQLiteWriter failed: '.$e->getMessage(),
				'trace'      => $trace,
				'file'       => isset($trace[0]['file']) ? $trace[0]['file'] : NULL,
				'line'       => isset($trace[0]['line']) ? $trace[0]['line'] : NULL,
				'class'      => isset($trace[0]['class']) ? $trace[0]['class'] : NULL,
				'function'   => isset($trace[0]['function']) ? $trace[0]['function'] : NULL,
				'additional' => ['exception' => $e],
			];
			
			$file_log_writer->write($message);
			
			$this->after_catch();
		}
	}
	
	protected function after_catch(){} // Here you can send a email, for example
}
