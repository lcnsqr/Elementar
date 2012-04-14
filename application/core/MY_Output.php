<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * My Output Class
 *
 * Responsible for sending final output to browser
 * write & display function modified to preserve 
 * content type header
 *
 * @author		Luciano Siqueira lcnsqr@gmail.com
 */
class MY_Output extends CI_Output {

	function set_output_json($response)
	{
		// execution time
		$CI =& get_instance();
		$elapsed = array('elapsed_time' => $CI->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end'));
		$response = array_merge($response, $elapsed);
		
		// Try to prevent reponse caching
		$this->set_header("Expires: " . gmdate("D, d M Y H:i:s", time() - 3600)." GMT");
		$this->set_header("Cache-Control: no-cache, no-store");
		$this->set_header("Pragma: no-cache");
		$this->set_header("Content-type: application/json");
		
		// Render JSON response
		$data = json_encode($response);
		$this->set_output($data);
	}

	/**
	 * Write a Cache File
	 *
	 * @access	public
	 * @param 	string
	 * @return	void
	 */
	function _write_cache($output)
	{
		$CI =& get_instance();
		$path = $CI->config->item('cache_path');

		$cache_path = ($path == '') ? APPPATH.'cache/' : $path;

		if ( ! is_dir($cache_path) OR ! is_really_writable($cache_path))
		{
			log_message('error', "Unable to write cache file: ".$cache_path);
			return;
		}

		$uri =	$CI->config->item('base_url').
				$CI->config->item('index_page').
				$CI->uri->uri_string();
		
		/*
		 * Sometimes Get params are used
		 */
		if ( ! empty($_GET) )
		{
			$uri .= '?' . http_build_query($_GET);
		}

		$cache_path .= md5($uri);

		if ( ! $fp = @fopen($cache_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			log_message('error', "Unable to write cache file: ".$cache_path);
			return;
		}

		$expire = time() + ($this->cache_expiration * 60);

		if (flock($fp, LOCK_EX))
		{
			fwrite($fp, json_encode(array($this->headers, $expire, $output)));
			flock($fp, LOCK_UN);
		}
		else
		{
			log_message('error', "Unable to secure a file lock for file at: ".$cache_path);
			return;
		}
		fclose($fp);
		@chmod($cache_path, FILE_WRITE_MODE);

		log_message('debug', "Cache file written: ".$cache_path);
	}

	// --------------------------------------------------------------------

	/**
	 * Update/serve a cached file
	 *
	 * @access	public
	 * @param 	object	config class
	 * @param 	object	uri class
	 * @return	void
	 */
	function _display_cache(&$CFG, &$URI)
	{
		// Check for active session before sending the cached page.
		// Web service method is used because many necessary CodeIgniter
		// dependencies for session library are not loaded up to this point
		if ( array_key_exists('elementar_session', $_COOKIE) )
		{
			$session = @unserialize(stripslashes($_COOKIE['elementar_session']));
			if (is_array($session))
			{
				$response = file_get_contents('http://' . $_SERVER['SERVER_NAME'] . '/identified/session_id/' . $session['session_id']);
				if ( $response == 'true' )
				{
					// Authenticated session, skip cache
					return FALSE;
				}
			}
		}

		$cache_path = ($CFG->item('cache_path') == '') ? APPPATH.'cache/' : $CFG->item('cache_path');

		// Build the file path.  The file name is an MD5 hash of the full URI
		$uri =	$CFG->item('base_url').
				$CFG->item('index_page').
				$URI->uri_string;

		/*
		 * Sometimes Get params are used
		 */
		if ( ! empty($_GET) )
		{
			$uri .= '?' . http_build_query($_GET);
		}

		$filepath = $cache_path.md5($uri);

		if ( ! @file_exists($filepath))
		{
			return FALSE;
		}

		if ( ! $fp = @fopen($filepath, FOPEN_READ))
		{
			return FALSE;
		}

		flock($fp, LOCK_SH);

		$cache = '';
		if (filesize($filepath) > 0)
		{
			//$cache = fread($fp, filesize($filepath));
			$file = fread($fp, filesize($filepath));
		}

		flock($fp, LOCK_UN);
		fclose($fp);
		
		list($headers, $expire, $cache) = json_decode($file, TRUE);

		// ETag field check
		if ( array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER) )
		{
			foreach($headers as $header)
			{
				if ( strtolower(substr($header[0], 0, 4)) == 'etag')
				{
					if ( $_SERVER['HTTP_IF_NONE_MATCH'] == substr($header[0], -32) )
					{
						// ETag match, send not modified code and finish
						$this->set_status_header(304);
						return TRUE;
					}
				}
			}
		}

		// Has the file expired? If so we'll delete it.
		if (time() >= intval($expire) )
		{
			if (is_really_writable($cache_path))
			{
				@unlink($filepath);
				log_message('debug', "Cache file has expired. File deleted");
				return FALSE;
			}
		}
		
		// Recover the headers
		$this->headers = $headers;

		// Display the cache
		$this->_display($cache);
		log_message('debug', "Cache file is current. Sending it to browser.");
		return TRUE;
	}

}
