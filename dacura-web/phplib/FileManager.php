<?php
/*
 * Class representing access to Dacura's logging, caching and file-dumping 
 *
 * Created By: Chekov
 * Creation Date: 25/12/2014
 * Contributors:
 * Modified: 
 * Licence: GPL v2
 */

class FileManager extends DacuraObject {
	
	var $service;
	
	function __construct(&$service){
		$this->service = $service;
	}

	function logEvent($a, $b, $c){
		$this->service->logger->logEvent($a, $b, $c);
	}
	
	function cache($cname, $oname, $data, $config = false){
		$oname = $this->sanitise_file_name($oname);
		$fpath = $this->service->settings['path_to_collections'];
		if($this->service->getCollectionID()) $fpath .= $this->service->getCollectionID()."/";
		$fpath .= $this->service->settings['cache_directory'];
		$d_name = $fpath.$cname;
		if(!file_exists($d_name)){
			mkdir($d_name);
		}
		if(!$config){
			$config = $this->service->settings['default_cache_config'];
		}
		$cache_config_file = $d_name."/".$oname.".config";
		if(!file_exists($cache_config_file)){
			file_put_contents($cache_config_file, json_encode($config));
		}
		$full_name = $d_name."/".$oname.".cache";
		$this->logEvent("debug", 200, "Cached $oname");
		return (file_put_contents($full_name, json_encode($data)));
	
	}
	
	function sanitise_file_name( $filename ) {
		$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
		//$special_chars = apply_filters('sanitize_file_name_chars', $special_chars, $filename_raw);
		$filename = str_replace($special_chars, '', $filename);
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = trim($filename, '.-_');
		return $filename;
	}
	
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
	
	function decache($cname, $oname, $ch=false, $return_stale = false){
		$oname = $this->sanitise_file_name($oname);
		$fpath = $this->service->settings['path_to_collections'];
		if($this->service->getCollectionID()) $fpath .= $this->service->getCollectionID()."/";
		$fpath .= $this->service->settings['cache_directory'];
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
		if(!$config) 
			$config = $this->service->settings['default_cache_config'];
		if(!$this->cacheIsStale($full_name, $config, $ch)){
			$this->logEvent("debug", 200, "$oname retrieved from cache");
			return json_decode(file_get_contents($full_name), true);
		}
		return $this->failure_result("Cache file for $cname / $oname is stale", 400);
	}
	
	function getURLofLogfile($fpath){
		$f_ext = substr($fpath, strlen($this->service->settings['dacura_logbase']));
		$url = $this->service->settings['log_url'].$f_ext;
		return $url;
	}
	
	function startServiceDump($sname, $dname, $extension = "txt", $avoid_overwrite = true, $prepend_date = false){
		$fpath = $this->service->settings['path_to_collections'];
		if($this->service->getCollectionID()) $fpath .= $this->service->getCollectionID()."/";
		$fpath .= $this->service->settings['dump_directory'];
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
	
	function dumpData($fc, $data){
		return fwrite($fc->fhandle, $data);
	}
	
	function endServiceDump($fc){
		fflush($fc->fhandle);            // flush output before releasing the lock
		flock($fc->fhandle, LOCK_UN);    // release the lock
		fclose($fc->fhandle);
	}
}

class FileChannel {
	var $path;
	var $name;
	var $extenstion;
	var $fhandle;
	
	function __construct($p, $n, $e, $f){
		$this->path = $p;
		$this->name = $n;
		$this->extension = $e;
		$this->fhandle = $f;
	}
	
	function filename(){
		return $this->name.".".$this->extension;
	}
}