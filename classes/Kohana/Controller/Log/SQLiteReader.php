<?php defined('SYSPATH') or exit('No direct script access.');
/**
 * SQLite зчитувач логів. Зчитує повідомлення, що збережені в базі даних SQLite.
 *
 * @package    KtretyaK
 * @category   Logging
 * @author     Kohana Team, Kostya Tretyak
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Controller_Log_SQLiteReader extends Controller {
	
	protected $config;
	protected $dir = 'views/logsqlite/static'; // Dir with views
	protected $template = 'v_index.html'; // Template view

	public function before()
	{
		parent::before();
		
		$this->config = Kohana::$config->load('logsqlite');
		
		if($this->config['authentication'])
			$this->check_auth();
	}
	
	protected function check_auth()
  {
		if (empty($_SERVER['REMOTE_USER'])) // HTTP Basic Authentication
		{
			// Web server may never pass these variables (depending on configuration);
			// in this case, authentication will not work correctly
			$username = @$_SERVER['PHP_AUTH_USER'] ?: ''; 
			$password = @$_SERVER['PHP_AUTH_PW'] ?: '';
			
			if( ! isset($this->config['users'][$username]) or $this->config['users'][$username] !== $password)
			{				
				throw HTTP_Exception::factory(401, 'Authentication required')->authenticate('Basic realm="Log_SQLiteReader"');
			}
		}
	}
	
	protected function action_index()
  {
		try
		{
			if( ! $post = $this->request->post())
				// Returns only static template
				return $this->action_media($this->dir, $this->template);

			$json = json_decode($post['json']);

			$file_path = realpath($this->config['directory']).DIRECTORY_SEPARATOR.$this->config['filename'];

			if( !file_exists($file_path))
				return $this->send_json_msg(
					'success',
					"<strong>SQLite3:</strong> database not exists in <i>$file_path</i>.<br><br>"
					. "(It is created automatically if required)"
				);

			$model = new Model_Log_SQLite($this->config, SQLITE3_OPEN_READONLY);

			if( ! $model->check_if_exitsts_table())
				return $this->send_json_msg(
					'success',
					"<strong>SQLite3:</strong> Table <i>".$this->config['tablename']."</i> not exists in database <i>$file_path</i><br><br>"
					. "(It is created automatically if required)"
				);
			
			$this->response
				->headers('Content-Type', 'application/json; charset=utf-8')
				->body( json_encode($model->select($json), JSON_HEX_AMP) );
		}
		catch(Exception $e)
		{
			if( ! $this->request->post())
				throw $e;
			
			$this->send_json_msg(
				'danger',
				"<strong>PHP Exception:</strong> {$e->getMessage()}<br>in {$e->getFile()}:{$e->getLine()}<br><br>"
				. "<strong>Debug:</strong><br>"
				. "<span style=\"white-space: pre-line;\">{$e->getTraceAsString()}</span>"
			);
		}
  }

	protected function send_json_msg($class = 'warning', $hrml = 'Something wrong')
	{
		$this->response
		->headers('Content-Type', 'application/json; charset=utf-8')
		->body(json_encode([
			'msg' =>
				[
					'class' => $class,
					'html' => $hrml
				]
		]));
  }

	public function action_media($dir = 'media/logsqlite', $file = '')
	{
		if( ! $file)
			// Get the file path from the request
			$file = $this->request->param('file');

		// Find the file extension
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		// Remove the extension from the filename
		$file = substr($file, 0, -(strlen($ext) + 1));

		if ($file = Kohana::find_file($dir, $file, $ext))
		{
			// Check if the browser sent an "if-none-match: <etag>" header, and tell if the file hasn't changed
			$this->check_cache(sha1($this->request->uri()).filemtime($file));
			
			// Send the file content as the response
			$this->response->body(file_get_contents($file));

			// Set the proper headers to allow caching
			$this->response->headers('content-type',  File::mime_by_ext($ext));
			$this->response->headers('last-modified', date('r', filemtime($file)));
		}
		else
		{
			// Return a 404 status
			$this->response->status(404);
		}
	}
}
