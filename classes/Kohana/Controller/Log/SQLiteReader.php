<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Controller_Log_SQLiteReader extends Controller {
	
	protected $config;

	public function before()
	{
		parent::before();
		
		$this->config = Kohana::$config->load('logsqlite');
		
		if ($this->config['authentication'] and empty($_SERVER['REMOTE_USER'])) // HTTP Basic Authentication
		{
			// Web server may never pass these variables (depending on configuration);
			// in this case, authentication will not work correctly
			$username = @$_SERVER['PHP_AUTH_USER'] ?: ''; 
			$password = @$_SERVER['PHP_AUTH_PW'] ?: '';
			
			if( ! isset($this->config[$username]) or $this->config[$username] !== $password)
			{				
				throw HTTP_Exception::factory(401, 'Authentication required')->authenticate('Basic realm="Log_SQLiteReader"');
			}
		}
	}
	
	protected function action_index()
    {
		if( ! $get = $this->request->query())
			// Returns only static template
			return $this->action_media('views/logsqlite/static', 'v_index.html');
		
		$levels = json_decode($get['levels'], true); // Associative array of arrays
		
		try
		{
			if( ! class_exists('SQLite3'))
				throw new Exception('Клас SQLite3 не існує');
			
			$file_path = realpath($this->config['directory']).DIRECTORY_SEPARATOR.$this->config['filename'];
			
			$db = new SQLite3($file_path, SQLITE3_OPEN_READONLY);
			
			$db->busyTimeout(3000);

			$tablename = $this->config['tablename'];
			
			$levels = "'".implode("', '", $levels)."'";
			
			$stmt = $db->prepare
			(
				"select
					time
					,url
					,ip
					,level
					,body
					
				from $tablename
				where time > :date_fr and time < :date_to
					and level in($levels)
					and body like :search_text
				limit :limit
				"
			);
			
			if ($stmt === false)
				throw new Exception('For some reason, the logs can not be loaded');
			
			$date_to = date('Y-m-d', strtotime($get['date_to']. ' + 1 days'));
			
			$stmt->bindValue(':date_fr', $get['date_fr'], SQLITE3_TEXT);
			$stmt->bindValue(':date_to', $date_to, SQLITE3_TEXT);
			$stmt->bindValue(':search_text', "%$get[search_text]%", SQLITE3_TEXT);
			$stmt->bindValue(':limit', $this->config['limit_fetch_row'], SQLITE3_TEXT);
			
			if (($results = $stmt->execute()) === false)
				throw new Exception('For some reason, the logs can not be loaded');
			
			$logs = [];
			while ($row = $results->fetchArray(SQLITE3_ASSOC))
			{
				$logs[] = $row;
			}
		}
		catch(Exception $e)
		{
			if(Kohana::$environment !== Kohana::PRODUCTION)
				throw $e; // Покищо, при збої запису логів, видається помилка; пізніше можна це змінити.
		}
		
		$this->response->body(json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_HEX_AMP ));
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