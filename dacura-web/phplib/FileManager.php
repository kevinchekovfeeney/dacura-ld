<?php
/**
 * Class the control's Dacura's logging, caching and file-dumping 
 *
 * * Creation Date: 25/12/2014
 * @author Chekov
 * @license GPL v2
 */
class FileManager extends DacuraController {
	
	function fetchFileFromURL($url){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($proxy = $this->getSystemSetting('http_proxy', false)){
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
		$content = curl_exec($ch);
		if(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve url: ".htmlspecialchars($url), curl_getinfo($ch, CURLINFO_HTTP_CODE), "info");
		}
		return $content;
	}
	
	
	/**
	 * Caches data for later reuse
	 * @param string $cname cache name
	 * @param string $oname object name (to be cached)
	 * @param string $data the data to be inserted into the cache
	 * @param array $config configuration settings for the cache
	 * @return number number of bytes written
	 */
	function cache($cname, $oname, $data, $config = false){
		$oname = $this->sanitise_file_name($oname);
		$fpath = $this->getSystemSetting('path_to_collections');
		if($this->cid()) $fpath .= $this->cid()."/";
		$fpath .= $this->getSystemSetting('cache_directory');
		$d_name = $fpath.$cname;
		if(!file_exists($d_name)){
			mkdir($d_name);
		}
		if(!$config){
			$config = $this->getSystemSetting('default_cache_config');
		}
		$cache_config_file = $d_name."/".$oname.".config";
		if(!file_exists($cache_config_file)){
			file_put_contents($cache_config_file, json_encode($config));
		}
		$full_name = $d_name."/".$oname.".cache";
		$this->logEvent("debug", 200, "Cached $oname");
		return (file_put_contents($full_name, json_encode($data)));
	}
	/**
	 * Is the cache stale (i.e. we must refetch the data)
	 * @param string $cfile cache file name
	 * @param array $config cache configuration settings 
	 * @param mixed $ch Curl handle for connection to check data
	 * @return boolean true if the cache is stale
	 */
	function cacheIsStale($cfile, $config, $ch = false){
		if($config['type'] == "time"){
			//check modification time of cache file
			$cached_time = time() - filemtime($cfile);
			if($cached_time > $config['value']){
				return true;
			}
			return false;
		}
		elseif($config['type'] == "url_modified_time"){
			$ch = ($ch) ? $ch : curl_init();
			curl_setopt($ch, CURLOPT_URL, $config['url']);
			// Only header
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_FILETIME, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			// Get info
			$info = curl_getinfo($ch);
			$modtime = $info['filetime'];
			if($modtime == $config['value']) return false;
			
		}
		elseif($config['type'] == "constant"){
			return false;
		}
		return true;
	}
	
	/**
	 * Fetches data from the cache
	 * @param string $cname the cache name
	 * @param string $oname the cached object's name
	 * @param mixed $ch a curl channel
	 * @param boolean $return_stale set to true if you want to return data from cache even when it is stale
	 * @return boolean|mixed either the cached data (array) or false for failure
	 */
	function decache($cname, $oname, $ch=false, $return_stale = false){
		$oname = $this->sanitise_file_name($oname);
		$fpath = $this->getSystemSetting('path_to_collections');
		if($this->cid()) $fpath .= $this->service->cid()."/";
		$fpath .= $this->getSystemSetting('cache_directory');
		$d_name = $fpath.$cname;
		$full_name = $d_name."/".$oname.".cache";
		if(!file_exists($full_name)){
			return $this->failure_result("Cache file for $cname / $oname does not exist", 400);
		}
		if($return_stale){
			return json_decode(file_get_contents($full_name), true);
		}
		$config = false;
		$config_file = $d_name."/".$oname.".config";
		if(file_exists($config_file)){
			$config = json_decode(file_get_contents($config_file), true);
		}
		if(!$config) $config = $this->getSystemSetting('default_cache_config');
		if(!$this->cacheIsStale($full_name, $config, $ch)){
			$this->logEvent("debug", 200, "$oname retrieved from cache");
			return json_decode(file_get_contents($full_name), true);
		}
		return $this->failure_result("Cache file for $cname / $oname is stale", 400);
	}
	
	/**
	 * Get the URL that corresponds to a particular log file (for web access)
	 * @param string $fpath the local filesystem path of the fil
	 * @return string the url of the file
	 */
	function getURLofLogfile($fpath){
		$f_ext = substr($fpath, strlen($this->getSystemSetting('dacura_logbase')));
		$url = $this->getSystemSetting('log_url').$f_ext;
		return $url;
	}
	
	/**
	 * Starts a service dump into a particular file
	 * @param string $sname service name
	 * @param string $dname the name of the dump file
	 * @param string $extension the file extnesion of the dump file
	 * @param boolean $avoid_overwrite if this is true, if the dump exists, it will not be overwritten
	 * @param boolean $prepend_date if true, the date will be prepended to the dump file name
	 * @return boolean|FileChannel
	 */
	function startServiceDump($sname, $dname, $extension = "txt", $avoid_overwrite = true, $prepend_date = false){
		$fpath = $this->getSystemSetting('path_to_collections');
		if($this->service->getCollectionID()) $fpath .= $this->cid()."/";
		$fpath .= $this->getSystemSetting('dump_directory');
		if($prepend_date){
			$dname .= date("Ymd")."-".$dname;
		}
		$oname = $dname;
		$full_name = $fpath."$dname.$extension";
		if($avoid_overwrite){
			$i = 0;
			while(file_exists($full_name)){
				$i++;
				$dname = $oname . "_".$i;
				$full_name = $fpath.$dname.".$extension";
			}
		}
		$fp = fopen($full_name, "xb");
		if(!$fp){
			return $this->failure_result('Creation of Dump file $full_name failed', 500);
		}
		if (flock($fp, LOCK_EX)) {  // acquire an exclusive lock
			return new FileChannel($fpath, $dname, $extension, $fp);
		}
		return $this->failure_result("Failed to create lock on dump file $full_nam", 400);
	}
	/**
	 * Dump data to the service dump
	 * @param FileChannel $fc a file channel for writing the data
	 * @param string $data the data to be written to file
	 * @return number the number of bytes written
	 */
	function dumpData(FileChannel $fc, $data){
		return fwrite($fc->fhandle, $data);
	}
	/**
	 * 
	 * @param FileChannel $fc
	 */
	function endServiceDump(FileChannel $fc){
		fflush($fc->fhandle);            // flush output before releasing the lock
		flock($fc->fhandle, LOCK_UN);    // release the lock
		fclose($fc->fhandle);
	}
	
	/**
	 * Ensures a simple filename by removing special characters whitespace, etc
	 * @param string $filename
	 * @return string the sanitized version with special chars removed
	 */
	function sanitise_file_name( $filename ) {
		$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
		//$special_chars = apply_filters('sanitize_file_name_chars', $special_chars, $filename_raw);
		$filename = str_replace($special_chars, '', $filename);
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = trim($filename, '.-_');
		return $filename;
	}
}

/**
 * Class which serves as a simple file-channel wrapper
 * @author chekov
 *
 */
class FileChannel {
	/** @var string the directory path to the file */
	var $path;
	/** @var string the name of the file */
	var $name;
	/** @var string the file extension */
	var $extension;
	/** @var resource the file handle itself */
	var $fhandle;
	
	/**
	 * 
	 * @param string $p path to file
	 * @param string $n name of file
	 * @param string $e extension of file
	 * @param resource $f file handle
	 */
	function __construct($p, $n, $e, $f){
		$this->path = $p;
		$this->name = $n;
		$this->extension = $e;
		$this->fhandle = $f;
	}
	
	/**
	 * Return the filename
	 * @return string 
	 */
	function filename(){
		return $this->name.".".$this->extension;
	}
}