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

class LogManager extends DacuraObject {
	
	var $service;
	
	function __construct(&$service){
		$this->service = $service;
	}

	/*
	 * Logging function
	 */
	function log($type, $data){
		if($type == "server" || $type == "error"){
			$fpath = $this->settings['dacura_logbase']."server.log";
			return (file_put_contents($fpath, $data, FILE_APPEND)) ? $fpath : false;
		}
		else if($type == "dump" || $type == "dumperrors"){
			$fpath = $this->settings['dacura_logbase'];
			if($this->ucontext->getCollectionID()) $fpath .= $this->ucontext->getCollectionID()."/";
			if($this->ucontext->getDatasetID()) $fpath .= $this->ucontext->getDatasetID()."/";
			$fpath .= ($type == "dump") ? 'polityParse-'.date("dmY").'T'.date("His").'Z.tsv' : 'errors-'.date("dmY").'T'.date("His").'Z.html';
			return (file_put_contents($fpath, $data)) ? $fpath : false;
		}
		else if($type == "service"){
			$fpath = $this->settings['dacura_logbase']."services/".$this->ucontext->servicename.".log";
			return (file_put_contents($fpath, $data, FILE_APPEND)) ? $fpath : false;
		}
		//here we have collection dependant logging
		//finally dataset dependant logging ?
	}
	
	function cache($cname, $oname, $data, $config = false){
		$oname = $this->sanitise_file_name($oname);
		$fpath = $this->service->settings['collections_base'];
		if($this->service->getCollectionID()) $fpath .= $this->service->getCollectionID()."/";
		$fpath .= $this->service->settings['cache_directory'];
		$d_name = $fpath.$cname;
		if(!file_exists($d_name)){
			mkdir($d_name);
			if(!$config){
				$config = $this->service->settings['default_cache_config'];
			}
			//also want to create a cache config file which details when the file is stale..
		}
		$cache_config_file = $d_name."/".$oname.".config";
		if(!file_exists($cache_config_file)){
			file_put_contents($cache_config_file, json_encode($config));
		}
		$full_name = $d_name."/".$oname.".cache";
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
	
	function cacheIsStale($cfile, $config){
		return false;
		if($config['type'] == "time"){
			//check modification time of cache file
			$cached_time = time() - filemtime($cfile);
			if($cached_time > $config['value']){
				return true;
			}
			return false;
		}
		return false;
	}
	
	function decache($cname, $oname){
		$oname = $this->sanitise_file_name($oname);
		$fpath = $this->service->settings['collections_base'];
		if($this->service->getCollectionID()) $fpath .= $this->service->getCollectionID()."/";
		$fpath .= $this->service->settings['cache_directory'];
		$d_name = $fpath.$cname;
		$full_name = $d_name."/".$oname.".cache";
		if(!file_exists($full_name)){
			return $this->failure_result("Cache file for $cname / $oname does not exist", 400);
		}
		$config = false;
		$config_file = $d_name."/".$oname.".config";
		if(file_exists($config_file)){
			$config = json_decode(file_get_contents($config_file), true);
		}
		if(!$config) 
			$config = $this->service->settings['default_cache_config'];
		if(!$this->cacheIsStale($full_name, $config)){
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
		$fpath = $this->service->settings['collections_base'];
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