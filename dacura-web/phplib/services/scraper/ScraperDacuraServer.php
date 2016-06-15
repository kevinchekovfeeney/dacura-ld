<?php

/*
 * The service for scraping datasets from the seshat wiki
 *
 * Created By: Odhran
 * Creation Date: 20/11/2014
 * Contributors: Chekov
 * Modifications: 20/11/2014 - 07/12/2014
 * Licence: GPL v2
 */


require_once("phplib/DacuraServer.php");
require_once('files/seshat.parser.php');

//need to do logging, cookiejar, etc.

class ScraperDacuraServer extends DacuraServer {
	
	var $ch; //curl handle
	var $index = array(); //index of the structure and statistics of the wiki
	
	/*
	 * Public Functions
	 */
	function __construct($service){
		parent::__construct($service);
		$x = $this->fileman->decache("scraper", "index");
		if($x) {
			$this->index = $x;
		}
		else {
			$this->index = array();			
		}
	}
	
	function updateStatus($nga = false){
		$stats = array( "ngas" => 0, "polities" => 0, "errors" => 0, "warnings" => 0, "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0);
		if($nga == false or $nga == "false"){
			$this->write_comet_update("phase", "Rebuilding List of NGAs");
			$ngas = $this->getNGAList(true);
			if(!$ngas){
				$this->write_comet_error("Failed to load list of NGAs from wiki ".$this->errmsg, $this->errcode);
				return false;
			}
			$seen_pols = array();
			foreach($ngas as $anga){
				$stats['ngas']++;
				$this->write_comet_update("phase", "Processing NGA Pages " . $stats['ngas'] . " of " .count($ngas));
				$pols = $this->getPolities($anga, true);
				$percent_done = ($stats['ngas'] / count($ngas)) * 100;
				$this->write_comet_update("progress", $percent_done);
				if($pols){
					$nganame = $this->formatNGAName($anga);
					$this->write_comet_update("success", $nganame . "<span class='seshaturl'>$anga</span>");		
					foreach($pols as $p){
						if(!in_array($p, $seen_pols)){
							$seen_pols[] = $p;
						}
					}
				}
				else {
					$this->write_comet_update("error", "Failed to Retrieve Polity List for $anga");
				}
			}
			foreach($seen_pols as $p){
				$stats['polities']++;
				$this->write_comet_update("phase", "Processing Polity Page " . $stats['polities'] . " of " .count($seen_pols));
				//$msg = "<p class='status'>".$stats['polities'] . " of " . count($seen_pols)."</progress>";
				$facts = $this->getFactsFromURL($p, true);
				$percent_done = ($stats['polities'] / count($seen_pols)) * 100;
				$this->write_comet_update("progress", $percent_done);
				$polname = $this->formatNGAName($p);
				if($facts){
					$this->incorporateStats($stats, $facts);					
					$this->write_comet_update("success", "<p>$polname <span class='seshaturl'>$p</span></p>".$this->statsToString($facts, $stats));
				}
				else {
					$this->write_comet_update("error", "<p>$polname Failed</p>".
							$this->statsToString($this->getEmptyFactList(), $stats));
				}
			}
		}
		else {
			$stats['ngas'] = 1;
			$pols = $this->getPolities($nga, true);
			if(!is_array($pols)) {
				$this->write_comet_error("Failed to Retrieve Polity List for $nga", $this->errcode);	
				return false;			
			}
			else { 
				foreach($pols as $p){
					$stats['polities']++;
					$this->write_comet_update("phase", "Processing Polity Page " . $stats['polities'] . " of " .count($pols));
					$polname = $this->formatNGAName($p);
					$facts = $this->getFactsFromURL($p, true);
					$percent_done = ($stats['polities'] / count($pols)) * 100;
					$this->write_comet_update("progress", $percent_done);
					if($facts){
						$this->incorporateStats($stats, $facts);
						$this->write_comet_update("success", "<p>$polname <span class='seshaturl'>$p</span></p>".$this->statsToString($facts, $stats));
					}
					else {
						$this->write_comet_update("error", "<p>$polname failed</p>".
							$this->statsToString($this->getEmptyFactList(), $stats));
					}
				}		
				$this->end_comet_output("success", $this->index);				
			}
		}
		return true;
	}
	
	function getStatus($nga = false){
		return $this->index;
	}
	
	function saveIndex($updated_page = false){
		if($updated_page){
			$this->index['stats'] = array(0, 0, 0, 0, 0);
			//first update the nga stats.
			if(isset($this->index['polities'])){
				foreach($this->index['polities'] as $p => $val){
					$stat = $val[1]["stats"];
					foreach($stat as $i => $v){
						$this->index["stats"][$i] += $v;
					}
				}
			}
			if(isset($this->index['ngas'])){
				foreach($this->index['ngas'] as $nga => $ngal){
					if(in_array($updated_page, $ngal[1])){
						if(!isset($this->index["ngastats"])){
							$this->index["ngastats"] = array();
						}
						$this->index["ngastats"][$nga] = array(0, 0, 0, 0, 0);
						foreach($ngal[1] as $pageid){
							if(isset($this->index['polities'][$pageid])){
								$prec = $this->index['polities'][$pageid][1];
								foreach($prec["stats"] as $i => $v){
									$this->index["ngastats"][$nga][$i] += $v;
								}
							}
						}
					}
				}
			}
		}
		$this->fileman->cache("scraper", "index", $this->index, $this->getServiceSetting('indexcache_config'));
	}
	
	function seshatInit($action, $object=""){
		ini_set("memory_limit","512M");
		$this->init($action, $object);
		return $this->login();		
	}
	
	function includePageLink($ln){
		if(strpbrk($ln, "&#")){
			return false;
		}
		$nonpages = array("User", "Special", "Conflicts:", "File:",  "Memento", "Main_Page", "Code_book", 
				"Macrostate_Inventory", "Productivity_Template", "http", "mediawiki", "Talk");
		foreach($nonpages as $non){
			if(strpos($ln, $non) !== false) return false;
		}
		return true;
	}
		
	/*
	 * Fetches the list of NGAs from the Seshat Main page (the World-30 sample table)
	 * Returns an array of URLs
	 */
	function getNGAList($suppress_cache = false){
		if($this->getServiceSetting('use_cache') && !$suppress_cache){
			$x = $this->fileman->decache("scraper", $this->getServiceSetting('mainPage'), $this->ch);
			if($x) {
				return $x;
			}
		}
		curl_setopt($this->ch, CURLOPT_URL, $this->getServiceSetting('mainPage'));
		//curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLOPT_FILETIME, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve nga list page ".$this->getServiceSetting('mainPage'), curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "warning");
		}
		$fmod = curl_getinfo($this->ch, CURLINFO_FILETIME);
		$dom = new DOMDocument;
		$dom->loadXML($content);
		$xpath = new DOMXPath($dom);
		$links = $xpath->query('//table[@class="wikitable"][1]//a/@href');
		$ngaURLs = array();
		foreach($links as $link){
			$x = $link->value;
			$url = 'http://seshat.info'.$x;
			$ngaURLs[] = $url;
		}
		if($this->getServiceSetting('use_cache')){
			$config = $this->getServiceSetting('ngacache_config');
			$config["url"] = $this->getServiceSetting('mainPage');
			$this->fileman->cache("scraper", $this->getServiceSetting('mainPage'), $ngaURLs, $config);				
			$this->index['nga_list'] = array(time(), $ngaURLs);
			$this->saveIndex();
		}
		$this->logEvent("debug", 200, "get_ngas returned: ".count($ngaURLs)." from seshat main page");
		return $ngaURLs;
	}
	
	
	/*
	 * Fetches a list of polities from a Seshat NGA page
	 * Takes the URL of the polity page
	 * Returns an array of URLs
	 */
	function getPolities($pageURL, $suppress_cache = false ){
		if($this->getServiceSetting('use_cache') && !$suppress_cache){
			$x = $this->fileman->decache("scraper", $pageURL, $this->ch);
			if($x) return $x;
		}
		curl_setopt($this->ch, CURLOPT_URL, $pageURL);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "info");
		}
		$dom = new DOMDocument;
		if(!$dom->loadXML($content)){
			return $this->failure_result("$pageURL did not parse correctly", 503, "warning");
		}
		$xpath = new DOMXPath($dom);
		$links = $xpath->query('//a/@href');
		$polities = array();
		foreach($links as $link){
			$x = $link->value;
			if(strstr($x, "seshat.info:") or 'http://seshat.info'.$x == $pageURL or $x[0] == ":" or !$this->includePageLink($x)){
				continue;
			}
			if(!strstr($x, "seshat.info")){
				$url = 'http://seshat.info'.$x;
			}
			if(!in_array($url, $polities)){	
				$polities[] = $url;
			}
		}
		if($this->getServiceSetting('use_cache')){
			$config = $this->getServiceSetting('cache_config');
			$config["url"] = $pageURL;
			$this->fileman->cache("scraper", $pageURL, $polities, $config);				
			if(!isset($this->index['ngas'])){
				$this->index['ngas'] = array();
			}
			$this->index['ngas'][$pageURL] = array(time(), $polities);
			$this->saveIndex();
		}
		$this->logEvent("debug", 200, "get_polities returned: ".count($polities)." from $pageURL");
		return $polities;
	}
	
	/*
	 * Functions for turning a seshat page into an array of facts...
	 */
	function getFactsFromURL($pageURL, $suppress_cache = false, $fetch_remote = true){
		$facts = false;
		if($this->getServiceSetting('use_cache') && !$suppress_cache){
			$facts = $this->fileman->decache("scraper", $pageURL, $this->ch, !$fetch_remote);
		}
		if(!$facts && $fetch_remote){
			curl_setopt($this->ch, CURLOPT_URL, $pageURL);
			$content = curl_exec($this->ch);
			if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
				return $this->failure_result("Failed to retrieve url: $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "info");
			}
			$facts = $this->getFactsFromPage($content);
			if($this->getServiceSetting('use_cache')){
				$config = $this->getServiceSetting('cache_config');
				$config["url"] = $pageURL;
				$this->fileman->cache("scraper", $pageURL, $facts, $config);
				if(!isset($this->index['polities'])){
					$this->index['polities'] = array();
				}
				$page_index = array("stats" => array($facts['total_variables']-$facts['empty'], $facts['lines'], $facts['empty'], count($facts['errors']), count($facts['warnings'])));
				$this->index['polities'][$pageURL] = array(time(), $page_index);
				$this->saveIndex($pageURL);
			}
		}
		return $facts;
	}
	
	
	/**
	 * Produces a dump of the NGA / polity sets passed in
	 * @param $data associative array of NGA name -> polity URL
	 * @param $suppress_cache - turn off cache for this call
	 */
	function getDump($data, $suppress_cache = false, $on_date = false){
		$polities_retrieved = array();
		$field_sep = ($this->getServiceSetting('dump_format') == "csv") ? "," : "\t";
		$summaries = array();
		$headers = array("NGA", "Polity", "Section", "Subsection", "Variable", "Value From", "Value To",
				"Date From", "Date To", "Fact Type", "Value Note", "Date Note", "Comment");
		$error_headers = array("NGA", "Polity", "Section", "Subsection", "Variable", "Type", "Value", "Error");
		$stats = array( "ngas" => 0, "polities" => 0, "errors" => 0, "warnings" => 0, "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0);
		$polity_failures = array();
		$rows = array();
		$this->start_comet_output();
		$error_op = $this->fileman->startServiceDump("scraper", "Errors", "html", true, true);
		$this->fileman->dumpData($error_op, "<table><tr><th>".implode("</th><th>", $error_headers)."</th></tr>");
		$html_op = $this->fileman->startServiceDump("scraper", "Export", "html", true, true);
		$this->fileman->dumpData($html_op, "<table><tr><th>".implode("</th><th>", $headers)."</th></tr>");
		$tsv_op = $this->fileman->startServiceDump("scraper", "Export", $this->getServiceSetting('dump_format'), true, true);
		$this->fileman->dumpData($tsv_op, implode($field_sep, $headers)."\n");
		foreach($data as $nga => $polities){
			$stats['ngas']++;
			$summary = array("nga" => $this->formatNGAName($nga), "polities" => count($polities), "failures" => 0, "warnings" => 0,
					"total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0, "errors" => 0);
			foreach($polities as $p){
				if(!$p) continue;
				if(!isset($polities_retrieved[$p])){
					$this->logEvent("debug", 200, "retrieving facts from $p");
					$pfacts = $this->getFactsFromURL($p, $suppress_cache);
					if($pfacts){
						$polities_retrieved[$p] = $pfacts;
						//op errors
						foreach($pfacts['errors'] as $e){
							if(isset($e['variable']) && isset($e['section'])){
								$row = $this->getErrorTableRowHTML($e, $nga, $p, "error");
								$this->fileman->dumpData($error_op, $row);							
							}	
						}
						foreach($pfacts['warnings'] as $e){
							if(isset($e['variable']) && isset($e['section'])){
								$row = $this->getErrorTableRowHTML($e, $nga, $p, "warning");
								$this->fileman->dumpData($error_op, $row);							
							}	
						}
						$stats['polities']++;
						$this->incorporateStats($stats, $pfacts);
						$msg = $this->formatNGAName($p).$this->statsToString($pfacts, $stats);
						$this->write_comet_update("success", $msg);
						$this->timeEvent("Retrieved $p", "debug");
						$this->logEvent("info", 200, "Successfully retrieved $p : (".$pfacts['total_variables']." variables)");
					}
					else {
						//add to failures
						$polity_failures[] = array($nga, $p, $this->errmsg, $this->errcode);
						$msg = $this->formatNGAName($p)." error: $this->errcode $this->errmsg";
						$this->write_comet_update("error", $msg);						
					}
				}
				else {
					$pfacts = $polities_retrieved[$p];
				}
				$this->incorporateStats($summary, $pfacts);
				if(isset($polities_retrieved[$p])){
					$rows = $this->factListToRows($this->formatNGAName($nga), $this->formatNGAName($p), $polities_retrieved[$p]);
					foreach($rows as $row){
						$this->fileman->dumpData($html_op, "<tr><td>".implode("</td><td>", $row)."</td></tr>");				
						$this->fileman->dumpData($tsv_op, implode($field_sep, $row)."\n");			
					}
				}
			}
			$summaries[] = $summary;
		}
		$tab_style = "<style>th,td { padding: 2px; border: 1px solid black; }\ntable { border-collapse: collapse}</stlye>";
		$this->fileman->dumpData($html_op, "</table>$tab_style");				
		$this->fileman->dumpData($error_op, "</table>$tab_style");				
		$this->fileman->endServiceDump($error_op);
		$this->fileman->endServiceDump($html_op);
		$this->fileman->endServiceDump($tsv_op);
		//start op buffering
		ob_start();
		$this->service->renderScreen("results", array("stats" => $stats, "failures" => $polity_failures, "summary" => $summaries,
				"files" => array("errors" => $this->service->my_url("rest")."/view/".$error_op->filename("rest"), "html" => $this->service->my_url("rest")."/view/".$html_op->filename(), "tsv" => $this->service->my_url("rest")."/view/".$tsv_op->filename())));
		$page = ob_get_contents();
		ob_end_clean();
		$this->end_comet_output("success", $page);
		return true;
	}
	
	function getErrorTableRowHTML($e, $nga, $p, $t){
		$row = "<tr><td><a href='$nga'>". $this->formatNGAName($nga)."</a></td>";
		$row .= "<td><a href='$p'>". $this->formatNGAName($p)."</a></td>";
		$row .= "<td><a href='$p"."#".$this->unformatSectionName($e['section'])."'>".$e['section']."</a></td>";
		$row .= "<td><a href='$p"."#".$this->unformatSectionName($e['subsection'])."'>".$e['subsection']."</a></td>";
		$row .= "<td>".$e['variable']."</td>";
		$row .= "<td>".$t."</td>";
		$row .= "<td>".$e['value']."</td>";
		$row .= "<td>".$e['comment']."</td></tr>";
		return $row;
	}
	
	function getReport($repname){
		$fpath = $this->getSystemSetting('path_to_collections');
		if($this->cid()) $fpath .= $this->cid()."/";
		$fpath .= $this->getSystemSetting('dump_directory');
		$fname = $fpath . $repname;
		$fsize = filesize($fname);
		$path_parts = pathinfo($fname);
		$ext = strtolower($path_parts["extension"]);
		if ($fd = fopen ($fname, "r")) {
			switch ($ext) {
				case "tsv" : 
					header("Content-type: text/tab-separated-values");
					header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a file download
					header("Content-length: $fsize");
					header("Cache-control: private"); //use this to open files directly
					break;
				case "csv" :
					header("Content-type: text/comma-separated-values");
					header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a file download
					header("Content-length: $fsize");
					header("Cache-control: private"); //use this to open files directly
					break;
				case "html" :
					header("Content-Type: text/html; charset=utf-8");
					break;
				default;
				    header("Content-type: application/octet-stream");
					header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
					break;
			}
			while(!feof($fd)) {
				$buffer = fread($fd, 2048);
				echo $buffer;
			}
			fclose ($fd);
			$this->logEvent("debug", 200, ($fsize % 1024) ."kb returned for report $repname");
			return true;
		}
		else {
			return $this->failure_result("Could not open requested report file $repname", 404);
		}
	}
		
	/*
	 * Dump Statistics - showing how many variables have been retrieved
	 */
	function statsToString($fact_list, $second_fl = false){
		$html = "<table class='scraper-report scraper-update'><tr><td></td><th>Datapoints</th><th>Errors</th><th>Empty</th></tr>";
		$html .= "<tr><td><td>". $fact_list['lines']."</td><td>".count($fact_list['errors'])."</td><td>".$fact_list['empty'];
		$html .= "</td></tr>";
		if($second_fl){
			$html .= "<tr><td>Total</td><td>". $second_fl['lines']."</td><td>".$second_fl['errors']."</td><td>".$second_fl['empty'];
			$html .= "</td></tr>";
		}
		$html .= "</table>";
		return $html;
	}
	
	function incorporateStats(&$stats, $nfacts){
		$stats['total_variables'] += $nfacts['total_variables'];
		$stats['empty'] += $nfacts['empty'];
		$stats['complex'] += $nfacts['complex'];
		$stats['lines'] += $nfacts['lines'];
		$stats['errors'] += count($nfacts['errors']);
		$stats['warnings'] += count($nfacts['warnings']);
	}

	function getEmptyFactList(){
		$fact_list = array( "variables" => array(), "errors" => array(), "warnings" => array(),
				"title" => "", "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0, "sections" => array());
		return $fact_list;
	}

	/*
	 * Takes a seshat page and extracts all of the facts 
	 * Takes the URL of the page
	 * Returns a fact list object (associative array)
	 */
	function getFactsFromPage($content){
		$fact_list = $this->getEmptyFactList();
		//array( "variables" => array(), "errors" => array(), "warnings" => array(),
		//	"title" => "", "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0, "sections" => array());
		// strip out the non-content to minimise collision risk
		/*$content_start_offset = strpos($content, $this->content_start_html);
		$content_end_offset = strpos($content, $this->content_end_html);
		if($content_start_offset && $content_end_offset){
				$content = substr($content, $content_start_offset , $content_end_offset - $content_start_offset);
		}*/
		// Divide into main sections....
		$sections = explode("<h2>", $content);
		$i = 0;
		//echo count($sections) . " sections found\n";
		foreach($sections as $sect){
			++$i;
			if($i == 1){ 	//variables that appear before the first section -> page level variables 
				$sfl = $this->parseFactsFromString($sect);
				$this->updateFactStats($fact_list, $sfl);
				$fact_list['variables'] = array_merge($fact_list['variables'], $sfl['variables']);
			}
			else {
				//divide into sub-sections
				$sec_bits = explode("</span></h2>", $sect);
				if(count($sec_bits) == 2){
					$sec_head_start_pos = strpos($sec_bits[0], ">") + 1;
					$sec_head_end_pos = strpos(substr($sec_bits[0], 1), "<") + 1;
					$sec_title = substr($sec_bits[0], $sec_head_start_pos, $sec_head_end_pos - $sec_head_start_pos);
					$sec_title = str_replace(array(",", "#"), array("", ""), $sec_title );
					if(!$sec_title) $sec_title = "Unknown";
					$sec_content = $sec_bits[1];
					$subsects = explode("<h3>", $sec_content);
					$j = 0;
					foreach($subsects as $subsect){
						if(++$j == 1){ //sub-section level variables
							$sec_fact_list = $this->parseFactsFromString($subsect, $sec_title);
							$sec_fact_list['sections'] = array();
						}
						 else {
							$subsec_bits = explode("</span></h3>", $subsect);
							if(count($subsec_bits) == 2){
								$subsec_head_start_pos = strpos($subsec_bits[0], ">") + 1;
								$subsec_head_end_pos = strpos(substr($subsec_bits[0], 1), "<") + 1;
								$subsec_title = substr($subsec_bits[0], $subsec_head_start_pos, $subsec_head_end_pos - $subsec_head_start_pos);
								$subsec_title = str_replace(array(",", "#"), array("", ""), $subsec_title );
								if(!$subsec_title) $subsec_title = "Unknown";
								$subsec_content = $subsec_bits[1];
								$ssfl = $this->parseFactsFromString($subsec_content, $sec_title, $subsec_title);
								$this->updateFactStats($sec_fact_list, $ssfl);
								$k = 0;
								$nsubtitle = $subsec_title;
								while(isset($sec_fact_list['sections'][$nsubtitle])){
									$nsubtitle = $subsec_title ."_".$k++;
								}
								$sec_fact_list['sections'][$nsubtitle] = $ssfl; 
							}
							else {
								$ssfl = $this->parseFactsFromString($subsect, $sec_title);
								$this->updateFactStats($sec_fact_list, $ssfl);
								$sec_fact_list = array_merge($sec_fact_list, $ssfl['variables']);
							}								
						}
					}
					$this->updateFactStats($fact_list, $sec_fact_list);
					$k = 0;
					$nsectitle = $sec_title;
					while(isset($sec_fact_list['sections'][$nsectitle])){
						$nsectitle = $sec_title ."_".$k++;
					}
					$fact_list["sections"][$nsectitle] = $sec_fact_list;
				}
				else {  //do our best...
					$sfl = $this->parseFactsFromString($sect);
					$this->updateFactStats($fact_list, $sfl);
					$fact_list['variables'] = array_merge($fact_list['variables'], $sfl['variables']);
				}
			}
		}
		return $fact_list;
	}

	function updateFactStats(&$myfl, $newfl){
		$myfl['total_variables'] += $newfl['total_variables'];
		$myfl['empty'] += $newfl['empty'];
		$myfl['complex'] += $newfl['complex'];
		$myfl['lines'] += $newfl['lines'];
		$myfl['errors'] = array_merge($myfl['errors'], $newfl['errors']);
		$myfl['warnings'] = array_merge($myfl['warnings'], $newfl['warnings']);
	}

	/*
	 * Creates an array of name => value for variables found in the text
	 * If there are repeated keys, it appends _n to the duplicates to retain the values.
	 * Returns a FactList...
	 */
	function parseFactsFromString($str, $section = "", $subsection = ""){
		$pattern = '/\x{2660}([^\x{2660}\x{2665}\x{2663}]*)\x{2663}([^\x{2660}\x{2665}]*)\x{2665}/Uu';
		$matches = array();
		$factoids = array();
		$res = array("variables" => array(), "errors" => array(), "warnings" => array(), "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0);
		if(preg_match_all($pattern, $str, $matches)){
			for($i = 0; $i< count($matches[0]); $i++){
				$key = trim($matches[1][$i]);
				$key = str_replace(array(",", "#"), array("", ""), $key);
				$val = trim($matches[2][$i]);
				if(isset($factoids[$key])){
					$n = 1;
					while(isset($factoids[$key."_$n"])){
						$n++;
					}
					$key = $key."_$n";
				}
				$res['total_variables']++;
				$parsed = $this->parseVariableValue($val);
				if($parsed['result_code'] == "complex"){
					$res['complex']++;
					$res['lines'] += count($parsed['datapoints']);
					$factoids[$key] = $parsed['datapoints'];
				}
				elseif($parsed['result_code'] == "warning"){
					$res['complex']++;
					$res['lines'] += count($parsed['datapoints']);
					$factoids[$key] = $parsed['datapoints'];
					$e = $this->getEmptyWarning();
					$e['value'] = $parsed['value'];
					$e['variable'] = $key;
					$e['section'] = $section;
					$e['subsection'] = $subsection;
					$e['comment'] = $parsed['result_message'];
					$res['warnings'][] = $e;
									}		
				elseif($parsed['result_code'] == "empty"){
					$factoids[$key] = "";
					$res['empty']++;
				}		
				elseif($parsed['result_code'] == "error"){
					$res['complex']++;
					$e = $this->getEmptyError();
					$e['value'] = $parsed['value'];
					$e['variable'] = $key;
					$e['section'] = $section;
					$e['subsection'] = $subsection;
					$e['comment'] = $parsed['result_message'];
					$res['errors'][] = $e;
				}
				else {
					$res['lines']++;
					$factoids[$key] = $parsed['datapoints'];
				}
			}
		}
		$res["variables"] = $factoids;
		return $res;
	}
	

	/**
	 *
	 * @param string $from (date that value holds from)
	 * @param string $to (date that value holds to)
	 * @param string $dtype (date type: simple, range, disputed, uncertain)
	 * @param string $vfrom (value of variable - from value if range)
	 * @param string $vto (to value when range of variable.
	 * @param string $vtype (value type -> disputed, range, uncertain)
	 * @param string $facttype (complex | simple | error)
	 * @param string $comment
	 * @return associative array with a key for each parameter
	 */
	function createFactoid($from, $to, $dtype, $vfrom, $vto, $vtype, $facttype, $comment = ""){
		return array("date_from" => $from, "date_to" => $to, "date_type" => $dtype,
				"value_from" => $vfrom, "value_to" => $vto, "value_type" => $vtype,
				"fact_type" => $facttype, "comment" => $comment);
	}
	
	function getEmptyError(){
		return array("type" => "error", "nga" => "", "polity" => "", "section" => "", "subsection" => "", "variable" => "", 
				"value" => "", "comment" => "", "link" => "");
	}
	
	function getEmptyWarning(){
		return array("type" => "warning", "nga" => "", "polity" => "", "section" => "", "subsection" => "", "variable" => "",
			"value" => "", "comment" => "", "link" => "");
	}
	
	/*
	 * These Functions are for navigating the parse tree..
	 */
	
	/**
	 * 
	 * @param string $t the raw text value of the variable 
	 * @return associative array: 
	 *   "value" => initial_value_of_variable
	 * 	 "result_code" => error | empty | simple | complex
	 *   "result_message => "string to show to user"
	 *   "datapoints" => simple array of 'factoids' -> basic information about the fact in a table format
	 */
	function parseVariableValue($t){
		$val = strip_tags($t);
		$val = trim(html_entity_decode($val));
		//remove both # and , characters
		$val = str_replace(array(",", "#"), array("", ""), $val);
		$ret_val = array(
			"value" => $val,
			"result_code" => "",
			"result_message" => "",
			"datapoints" => array()
		);
		if($val == ""){
			$ret_val["result_code"] = "empty";
			return $ret_val;
		}
		$factoids = array();
		if(strpbrk($val, ":;[{")){
			$p = new seshatParsing($val);
			$parsedFact = $p->match_fact();
			if($parsedFact){
				if($parsedFact['text'] == $val){
					$ret_val["result_code"] = "complex";
					$fragment_contents = $parsedFact['value'];
					if(isset($fragment_contents[0])){ //array of fragments...
						$fragments = $fragment_contents;
					}
					else {
						$fragments = array($fragment_contents);
					}
					foreach($fragments as $f){
						if($f['name'] == "undatedfact"){
							$mini_factoids = $this->processUndatedFact($f['value']);
							foreach($mini_factoids as $mf){
								$factoids[] = $this->createFactoid("", "", "", $mf['value_from'], $mf['value_to'], $mf['value_type'], "complex", "");
							}
						}
						else {
							$n_factoids = $this->processDatedFact($f['value'], $val);
							if(is_array($n_factoids)){
								$factoids = array_merge($factoids, $n_factoids);
							}
							else {							
								$ret_val["result_code"] = "error";
								$ret_val["result_message"] = "The date value could not be parsed: " .$f['text'];
								break;
							}
						}
					}
				}
				else {
					$ret_val = $this->lastChanceSaloon($val);
					if($ret_val["result_code"] == "error"){
						$ret_val["result_message"] = " This fragment: \"" .$parsedFact['text'].
						"\" parsed ok. The fragment following \"".substr($val, strlen($parsedFact['text']))."\" contains the error.";
					}
					return $ret_val;
				}
			}
			else {
				return $this->lastChanceSaloon($val);
			}
		}
		else {
			$ret_val["result_code"] = "simple";
			$ret_val["result_message"] = "Simple Value.";		
			$factoids[] = $this->createFactoid("", "", "", $val, "", "simple", "simple", "");
		}
		$ret_val['datapoints'] = $factoids;
		return $ret_val;
	}

	/*
	 * Last gasp attempt to allow known common slips through, while raising a warning
	 */
	function lastChanceSaloon($txt){
		$ret_val = array("value" => $txt, "result_code" => "error");
		$factoids = false;
		//1 uncertainty list items surrounded by [] and separated by , instead of 
		$is_bad_list = false;
		if(strpos($txt, '[') === 0 && strpos($txt, ']') == (strlen($txt) -1)){
			$is_bad_list = "uncertain";
		}
		elseif(strpos($txt, '{') === 0 && strpos($txt, '}') == (strlen($txt) -1)){
			$is_bad_list = "disputed";
		}
		if($is_bad_list && strpos($txt, ",") !== false){
			$vals = explode(",", substr($txt, 1, strlen($txt)-2));
			foreach($vals as $val){
				$factoids[] = $this->createFactoid("", "", "", $val, "", $is_bad_list, "complex", "warning - Using comma (,) instead of semi-colon (;) to separate list of $is_bad_list values");
			}
			$ret_val['result_message'] = "Warning: using comma (,) instead of semi-colon (;) to separate list of $is_bad_list values";
		}
		elseif($is_bad_list && !strpbrk(substr($txt, 1, strlen($txt)-3), ":;[{,")){
			$factoids = array($this->createFactoid("", "", "", trim(substr($txt, 1, strlen($txt)-2)), "", $is_bad_list, "simple", "warning - Surrounding a simple value in $is_bad_list brackets"));				
			$ret_val['result_message'] = "Surrounding a simple value in $is_bad_list brackets";
		}
		elseif(!$is_bad_list && strpos($txt, ";") !== false && !strpbrk($txt, ":[{,")){
			$vals = explode(";", $txt);
			$factoids = array();
			foreach($vals as $val){
				if($val){
					$factoids[] = $this->createFactoid("", "", "", $val, "", "list", "complex", "");
				}
			}
			$ret_val['result_message'] = "list of variable values";
		}
		if($factoids){
			$ret_val['datapoints'] = $factoids;
			$ret_val['result_code'] = "warning";
			if($ret_val['result_message'] == "list of variable values"){ //ugly hack
				$ret_val['result_code'] = "complex";
			}
		}
		else {
			$ret_val["result_message"] = "Value $txt failed to parse. It has a formatting error. ";				
		}
		return $ret_val;
	}
	
	/*
	 * Takes a $fact (node from parse tree) representing a dated fact
	 * Returns an array of factoids. 
	 */
	function processDatedFact($fact, $orig){
		foreach($fact as $factbit){
			if($factbit['name'] == "undatedfact"){
				$factoids = $this->processUndatedFact($factbit['value']);
			}
			elseif($factbit['name'] == "datevalue"){
				$datebit = $this->processDateValue($factbit['value']);
				if(!$datebit){
					return $this->failure_result("Failed to parse date part ".$factbit['text'].$this->errmsg, $this->errcode);
				}	
			}
		}
		foreach($factoids as $i => $factoid){
			$factoids[$i]['fact_type'] = "complex";
			$factoids[$i]["date_type"] = $datebit['type'];
			if(isset($factoids[$i]['comment']) && $factoids[$i]['comment'] && isset($datebit['comment'])  && $datebit['comment']){
				$factoids[$i]['comment'] .= " - ";
			}
			$factoids[$i]["comment"] .= (isset($datebit['comment']) ? $datebit['comment'] : "");
			if($datebit["type"] == "range"){
				$factoids[$i]["date_to"] = $datebit['to'];
				$factoids[$i]["date_from"] = $datebit['from'];
			}
			else {
				$factoids[$i]["date_from"] = $datebit['value'];
			}
		}
		return $factoids;
	}
	
	/*
	 * Undated facts can be either string | complex
	 */
	function processUndatedFact($fact){
		$factoids = array();
		if($fact['name'] == 'string'){
			$factoids[] = $this->createFactoid("","", "", $fact['text'], "", "simple", "", "");
		}
		elseif($fact['name'] == 'uncertainrange') {
			$factoids[] = $this->createFactoid("","", "", $fact['value'][0]['text'], $fact['value'][1]['text'], "range", "complex", "");
		}
		elseif(($fact['name'] == 'disagreelist') ){
			foreach($fact['value'] as $i => $disagreefragment){			
				if(($disagreefragment['value']['name']) == "string"){
					$factoids[] = $this->createFactoid("","", "", $disagreefragment['value']['text'], "", "disputed", "complex", "");	
				}
				else {
					$factoids[] = $this->createFactoid("","", "", $disagreefragment['value']['value'][0]['text'], $disagreefragment['value']['value'][1]['text'], "disputed", "complex", "");	
				}
			}
		}
		else {
			foreach($fact['value'] as $i => $possibleval){
				$factoids[] = $this->createFactoid("","", "", $possibleval['text'], "", "uncertain", "complex", "The fact is uncertain");
			}
		}
		return $factoids;
	}
	
	/*
	 * For processing the date part of a factoid
	 */
	function processDateValue($fact){
		$datevals = array();
		$date_comment = "";
		if($fact['name'] == "daterange" && $fact['value'][0] && $fact['value'][1]){
			$from = $fact['value'][0];
			$to = $fact['value'][1];
			$from = $this->processDateValue($from);
			$to = $this->processDateValue($to);
			if(!$to or !$from){
				return $this->failure_result("failed to read dates in date range: ".$fact['value'][0]." " . $fact['value'].$this->errmsg, $this->errcode);
			}
			else {
				$datevals = $this->processDateRange($from, $to);
				if(!$datevals) return false;
			}
		}
		elseif($fact['name'] == "singledate" && isset($fact['value']['name']) && $fact['value']['name'] == "simpledate"){
			$normdate = $this->normaliseSuffix($fact['value']['text']);
			if(!$normdate){
				return $this->failure_result("failed to read date to find suffix ".$fact['value']['text'], 500);
			}
			$datevals = array("type" => "simple", "value" => $normdate, "comment" => "");
		}
		elseif($fact['name'] == "disagreedate" || ($fact['name'] == "singledate" && $fact['value']['name'] == "disagreedate")){
			$base = ($fact['name'] == "disagreedate")? $fact['value'] : $fact['value']['value'];
			$date_ranges = array();
			$date_units = array();
			foreach($base as $frag){
				if($frag['value']['name'] == "simpledaterange"){
					$dr = $this->getDateRangeBounds($frag['value']['value'][0]['text'], $frag['value']['value'][1]['text']);
					if(!$dr){
						return false;
					}
					$date_ranges[] = $dr;
				}
				elseif($frag['value']['name'] == "simpledate"){
					$normdate = $this->normaliseSuffix($frag['value']['text']);
					$date_units[] = $normdate;
				}
			}
			if(count($date_ranges) >= count($date_units)){
				$datevals = $this->consolidateDateRanges($date_ranges, $date_units);
			}
			else {
				$datevals = $this->consolidateDates($base, "disputed");
			}
		}
		elseif($fact['name'] == "uncertaindate" || ($fact['name'] == "singledate" && $fact['value']['name'] == "uncertaindate")){
			//just make a range from the first to the last..
			$base = ($fact['name'] == "uncertaindate")? $fact['value'] : $fact['value']['value'];
			$date_ranges = array();
			$date_units = array();
			foreach($base as $frag){
				if($frag['name'] == "simpledaterange"){
					$dr = $this->getDateRangeBounds($frag['value'][0]['text'], $frag['value'][1]['text']);
					if(!$dr){
						return false;
					}
					$date_ranges[] = $dr; 
						
				}
				elseif($frag['name'] == "simpledate"){
					$date_units[] = $frag['text'];
				}
			}
			if(count($date_ranges) >= count($date_units)){
				$datevals = $this->consolidateDateRanges($date_ranges, $date_units);
			}
			else {
				$datevals = $this->consolidateDates($base, "uncertain");
			}
		}
		else {
			return $this->failure_result("strange error parsing node: ".$fact['name'], 500);
		}
		return $datevals;
	}
	
	function normaliseSuffix($txt){
		$pattern = "/(\d{1,5})\s*(ce|bce|bc)?/i";
		$matches = array();
		if(preg_match($pattern, $txt, $matches)){
			if(isset($matches[2])){
				if(stristr($matches[2], "bc") || stristr($matches[2], "bce")){
					return $matches[1]."BCE";
				}
				else return $matches[1]."CE";
			}
			return $matches[1];
		}
		return false;
	}
	
	function consolidateDateRanges($ranges, $units){
		$from = array("type" => "", "value" => "", "comment" => "");
		$to = array("type" => "", "value" => "", "comment" => "");
		foreach($units as $unit){
			if($from['type'] == ""){
				$from['type'] = "simple";
				$from['value'] = $unit;
			}
			else {
				if($this->strToYear($from['value']) != $this->strToYear($unit)) $from['type'] = "disputed";
				if($this->strToYear($from['value']) > $this->strToYear($unit)){
					$from['value'] = $unit;
				}
			}	
			if($to['type'] == ""){
				$to['type'] = "simple";
				$to['value'] = $unit;
			}
			else {
				if($this->strToYear($to['value']) != $this->strToYear($unit)) $to['type'] = "disputed";
				if($this->strToYear($to['value']) < $this->strToYear($unit)){
					$to['value'] = $unit;
				}
			}				
		}
		foreach($ranges as $range){
			if($from['type'] == ""){
				$from['type'] = "simple";
				$from['value'] = $range[0];
			}
			else {
				if($this->strToYear($from['value']) != $this->strToYear($range[0])) $from['type'] = "disputed";
				if($this->strToYear($from['value']) > $this->strToYear($range[0])){
					$from['value'] = $range[0];
				}
			}	
			if($to['type'] == ""){
				$to['type'] = "simple";
				$to['value'] = $range[1];
			}
			else {
				if($this->strToYear($to['value']) != $this->strToYear($range[1])) $to['type'] = "disputed";
				if($this->strToYear($to['value']) < $this->strToYear($range[1])){
					$to['value'] = $range[1];
				}
			}				
		}
		$date_comment = "";
		if($from['type'] == "disputed"){
			$date_comment = "The from date is disputed";
		}		
		if($to['type'] == "disputed"){
			if($date_comment) $date_comment .= " - ";
			$date_comment .= "The to date is disputed";
		}		
		$datevals = array("from" => $from['value'], "to" => $to['value'], "type" => "range", "comment" => $date_comment);
		return $datevals;
	}
	
	function processDateRange($from, $to){
		$date_comment = "";
		if($to['type'] == 'disputed' && $from['type'] == 'disputed' ){
			$date_comment = "Both from and to dates are disputed";
		}
		elseif($from['type'] == 'disputed'){
			$date_comment = "The from date is disputed";
		}
		elseif($to['type'] == 'disputed'){
			$date_comment = "The to date is disputed";
		}
		if($from['type'] == "uncertain" && $to['type'] == 'uncertain'){
			$date_comment = "Both from and to dates are uncertain";
		}
		elseif($from['type'] == 'uncertain'){
			$str = "The from date is uncertain";
			$date_comment .= (strlen($date_comment) == 0) ? $str : " - " . $str;
		}
		elseif($to['type'] == 'uncertain'){
			$str = "The to date is uncertain";
			$date_comment .= (strlen($date_comment) == 0) ? $str : " - " . $str;
		}
		//make sure that we have no unsuffixed values
		if($from['type'] == "simple" ){
			if(!$this->containsYearSuffix($from['value'])){
				$sf = $this->getFirstYearSuffix($to['value']);
				if($sf){
					$from['value'].= $sf;
				}
			}
		}
		if($to['type'] == "simple" ){
			if(!$this->containsYearSuffix($to['value'])){
				$sf = $this->getLastYearSuffix($from['value']);
				if($sf){
					$to['value'].= $sf;
				}
			}
		}
		if(!isset($from['value']) or !isset($to['value'])){
			$msg = "Could not detect correct values in range" . ((isset($from['value'])) ? "From: ".$from['value'] : " no from detected");
			$msg .= ((isset($to['value'])) ? " To: ".$to['value'] : " no to detected");
			return $this->failure_result($msg, 400);
		}
		elseif($this->strToYear($from['value']) > $this->strToYear($to['value'])){
			$datevals = array("from" => $to['value'], "to" => $from['value'], "type" => "range", "comment" => $date_comment);				
		}
		else {
			$datevals = array("from" => $from['value'], "to" => $to['value'], "type" => "range", "comment" => $date_comment);
		}
		return $datevals;
	}
	
	function containsYearSuffix($str){
		$pattern = "/(\d{1,5})\s*(ce|bce|bc)?/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			return (isset($matches[2]) && $matches[2]);
		}
		return false;
	}
	
	function getFirstYearSuffix($str){
		$pattern = "/(ce|bce|bc)/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			if(isset($matches[1]) && $matches[1]){
				if(stristr($matches[1], "bc") || stristr($matches[1], "bce")){
					return "BCE";
				}
				return "CE";
			}
		}
		return false;
	}
	
	function getLastYearSuffix($str){
		$pattern = "/.*(ce|bce|bc)/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			if(isset($matches[1]) && $matches[1]){
				if(stristr($matches[1], "bc") || stristr($matches[1], "bce")){
					return "BCE";
				}
				return "CE";
			}
		}
		return false;
	}
	
	function strToYear($str){
		$pattern = "/(\d{1,5})\s*(ce|bce|bc)?/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			if(isset($matches[2]) && (stristr($matches[2], "bc") || stristr($matches[2], "bce"))){
				return (0 - $matches[1]);
			}
			else return $matches[1];
		}
		return false;
	}
	
	/*
	 * In either a disputed or an uncertain date - if they are all ranges, make it a range...
	 */
	function consolidateDates($fragments, $type){
		$earliest = false;
		$latest = false;
		foreach($fragments as $frag){
			$x = false;
			if($type == "disputed" && $frag['value']['name'] == "simpledate"){
				$x = $this->strToYear($frag['value']['text']);
			}
			elseif(isset($frag['name']) && $frag['name'] == "simpledate") {
				$x = $this->strToYear($frag['text']);
			}
			if($x !== false){
				if(($earliest === false) && ($latest === false)){
					$earliest = $x;
					$latest = $x;
				}
				else {
					$earliest = ($earliest < $x) ? $earliest : $x;
					$latest = ($latest > $x) ? $latest : $x;
				}
			}
			else {
				$bounds = false;
				if($type == "disputed" && $frag['value']['name'] == "simpledaterange"){
					$bounds = $this->getDateRangeBounds($frag['value']['value'][0]['text'], $frag['value']['value'][1]['text']);
				}
				elseif(isset($frag['name'] ) && $frag['name'] == "simpledaterange") {
					$bounds = $this->getDateRangeBounds($frag['value'][0]['text'], $frag['value'][1]['text']);
				}
				if($bounds){
					if(($earliest === false) && ($latest === false)){
						$earliest = $this->strToYear($bounds[0]);
						$latest = $this->strToYear($bounds[1]);
					}
					else {
						$earliest = ($earliest < $this->strToYear($bounds[0])) ? $earliest : $this->strToYear($bounds[0]);
						$latest = ($latest > $this->strToYear($bounds[1])) ? $latest : $this->strToYear($bounds[1]);
					}		
				}
				else {
					return false;	
				}
			}
		}
		if(($earliest === false) || ($latest === false)){
			return false;
		}
		$str = "";
		if($earliest < 0){
			$str = (0 - $earliest) . "BCE - ";
		}
		else {
			$str = $earliest ."CE - ";
		}
		if($latest < 0){
			$str .= (0 - $latest) . "BCE";
		}
		else {
			$str .= $latest."CE";
		}
		$date_val = "$earliest - $latest";
		return array("type" => $type, "value" => $str, "comment" => "Date is $type");
	}
	
	/*
	 * deals with the problem of missing suffixes in ranges
	 * If a suffix is missing, copy it from the other half of the range
	 * Also, ensure that left < right
	 */
	function getDateRangeBounds($dr1, $dr2){
		$bounds = array();
		$pattern = "/(\d{1,5})\s*(ce|bce|bc)?/i";
		$matches = array();
		$matches2 = array();
		if(preg_match($pattern, $dr1, $matches)){
			if(!isset($matches[2]) or !$matches[2]){
				if(preg_match($pattern, $dr2, $matches2)){
					if(isset($matches2[2])){
						if(stristr($matches2[2], "bc") || stristr($matches2[2], "bce")){
							$dr1 .= "BCE";
							$dr2 = $matches2[1]."BCE";
						}
						else {
							$dr1 .= "CE";
							$dr2 = $matches2[1]."CE";
						}
					}
					else {
						return $this->failure_result("No era suffix found on either side of date-range", 400);
					}
				}
				else {
					return $this->failure_result("From side of date range is not a valid date", 400);
				}
			}
			else {
				if(stristr($matches[2], "bc") || stristr($matches[2], "bce")){
					$dr1 = ($matches[1])."BCE";
				}
				else {
					$dr1 = ($matches[1])."CE";						
				}
				if(preg_match($pattern, $dr2, $matches2)){
					if(!isset($matches2[2]) || !$matches2[2]){
						if(stristr($matches[2], "bc") || stristr($matches[2], "bce")){
							$dr2 .= "BCE";
						}
						else {
							$dr2 .= "CE";
						}
						$dr2 .= $matches[2];
					}
					else {
						if(stristr($matches2[2], "bc") || stristr($matches2[2], "bce")){
							$dr2 = ($matches2[1])."BCE";
						}
						else {
							$dr2 = ($matches2[1])."CE";
						}
					}
				}
				else {
					return $this->failure_result("From side of date range is not a valid date", 400);
				}
			}
		}
		if($this->strToYear($dr1) > $this->strToYear($dr2)){
			return array($dr2, $dr1);
		}
		return array($dr1, $dr2);
	}
	
	function getLocator() {
		
	}
	
	function factToAPIOutput($loc, $vals){
		$fact = array(
			"locator" => $loc,
			"result_code" => $this->valuesToResultCode($vals),
			"values" => $vals	
		);
		return $fact;
	}
	
	function valuesToResultCode($vals){
		if(!is_array($vals) || count($vals) == 0){
			return "empty";
		}
		if(isset($vals['warnings']) && count($vals['warnings']) > 0){
			return "warning";
		}
		if(isset($vals['errors']) && count($vals['errors']) > 0){
			return "error";
		}
		return "correct";
	}
	
	function factListToAPIOutput($fl){
		$output = array();
		foreach($fl["variables"] as $varname => $varvals){
			$varlocator = array("property" => $varname);
			$output[] = $this->factToAPIOutput($varlocator, $varvals);	
		}
		if(isset($fl['sections'])){
			foreach($fl["sections"] as $sname => $section){
				foreach($section["variables"] as $varname => $varvals){
					$varlocator = array("property" => $varname, "section" => $sname);
					$output[] = $this->factToAPIOutput($varlocator, $varvals);
				}
				if(isset($section["sections"])){
					foreach($section["sections"] as $subsname => $subsection){
						foreach($subsection["variables"] as $varname => $varvals){
							$varlocator = array("property" => $varname, "section" => $sname, "subsection" => $subsname);
							$output[] = $this->factToAPIOutput($varlocator, $varvals);							
						}
					}
				}
			}
		}
		return $output;
	}
	
	/*
	 * Functions for converting lists of facts into tabular format...
	 */
	
	/**
	 * @param string $nga - the name of the NGA that the factlist belongs to
	 * @param url $polity - the url of the polity page that the factlist belongs to
	 * @param factlist $fl - fact list object - as returned by getFactsFromPage
	 * @param boolean  $include_empties - include variables with empty values
	 * @return array of rows -> each row is a factoid
	 */
	function factListToRows($nga, $polity, $fl, $include_empties = false){
		$rows = array();
		foreach($fl["variables"] as $varname => $varvals){
			if(is_array($varvals)){
				foreach($varvals as $val){
					$rows[] = array($nga, $polity, "", "", $varname, $val['value_from'], $val['value_to'], $val['date_from'], $val['date_to'], $val['fact_type'], $val['value_type'], $val['date_type'], $val['comment']);
				}
			}
			else {//empty
				if($include_empties){
					$rows[] = array($nga, $polity, "", "", $varname, "", "", "", "", "empty", "", "", "");
				}
			}
		}
		if(!isset($fl["sections"]) or count($fl["sections"]) == 0){
			return $rows;
		}
		foreach($fl["sections"] as $sname => $section){
			foreach($section["variables"] as $varname => $varvals){
				if(is_array($varvals)){
					foreach($varvals as $val){
						$rows[] = array($nga, $polity, $sname, "", $varname, $val['value_from'], $val['value_to'], $val['date_from'], $val['date_to'], $val['fact_type'], $val['value_type'], $val['date_type'], $val['comment']);
					}
				}
				else {//empty
					if($include_empties){
						$rows[] = array($nga, $polity, $sname, "", $varname, "", "", "", "", "empty", "", "", "");
					}
				}
			}
			if(isset($section["sections"])){
				foreach($section["sections"] as $subsname => $subsection){
					foreach($subsection["variables"] as $varname => $varvals){
						if(is_array($varvals)){
							foreach($varvals as $val){
								$rows[] = array($nga, $polity, $sname, $subsname, $varname, $val['value_from'], $val['value_to'], $val['date_from'], $val['date_to'], $val['fact_type'], $val['value_type'], $val['date_type'], $val['comment']);
							}
						}
						else {//empty
							if($include_empties){
								$rows[] = array($nga, $polity, $sname, $subsname, $varname, "", "", "", "", "empty", "", "", "");
							}
						}
					}
				}
			}
		}
		return $rows;
	}	

	

	
	/*
	 * Log into the Seshat Wiki....
	 */
	function login(){
		$this->ch = curl_init();
		//initial curl setting
		curl_setopt($this->ch, CURLOPT_URL, $this->getServiceSetting('loginUrl'));
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->getServiceSetting('cookiejar'));
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		if($prox = $this->getSystemSetting('http_proxy', "")){
			curl_setopt($this->ch, CURLOPT_PROXY, $prox);
		}
		$this->timeEvent("Start Login", "debug");
		//get token from login page
		$store = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$store){
			$this->service->logger->timeEvent("Logging in Failed");				
			return $this->failure_result("Failed to retrieve login page ".$this->getServiceSetting('loginUrl', ""), curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "warning");				
		}
		$this->timeEvent("Login Page Retrieved", "debug");				
		$loginToken = false;
		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML($store);
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query('//input');
		foreach($nodes as $node) {
			$nodename = $node->getAttribute('name');
			if($nodename == 'wpLoginToken'){
				$loginToken = $node->getAttribute('value');
			}
		}
		$this->timeEvent("Login Page Parsed", "debug");		
		if(!$loginToken){
			return $this->failure_result("Failed to find login token on login page ".$this->getServiceSetting('loginUrl'), 404, "error");				
		}
		libxml_clear_errors();
		//login
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, 'wpName='.$this->getServiceSetting('username').'&wpPassword='.$this->getServiceSetting('password').'&wpLoginAttempt=Log+in&wpLoginToken='.$loginToken);
		$store = curl_exec($this->ch);
		$http_status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		if($http_status >= 400){
			$this->service->logger->timeEvent("Login Failed");				
			return $this->failure_result("Failed to login to wiki", 400, "warning");
		}
		$this->timeEvent("Login Successful", "info");				
		return true;
	}
	

	/**
	 * Produces a monthly series of historical dumps of the data in the wiki
	 *
	 */
	function getHistory(){
		$dates_list = array();
		//$step_size = isset($date_info['step_size']) ? $date_info['step_size'] : "m";
		//$sd = isset($date_info['start_date']) ? $date_info['start_date'] : false;
		//$ed = isset($date_info['end_date']) ? $date_info['end_date'] : strtotimedate();
		$current_year = date("Y");
		$current_month = date("m");
		$dates_list[] = array(2012, 09);
		$dates_list[] = array(2012, 10);
		$dates_list[] = array(2012, 11);
		$dates_list[] = array(2012, 12);
		for($j = 2013; $j < $current_year; $j++){
			for($i = 1; $i < 13; $i++){
				$dates_list[] = array($j, $i);
			}
		}
		for($k = 1; $k < $current_month + 1; $k++){
			$dates_list[] = array($current_year, $k);
		}
		foreach($dates_list as $i => $one_date){
			$dates_list[$i][] = $this->getWikiURLforDate($this->getServiceSetting('mainPage'), $one_date[0], $one_date[1]);
		}
		return $dates_list;
	}
	
	function getWikiURLforDate($page, $y, $m){
		if($m < 10) $m = "0".$m;
		$history_url = $page . "?action=history&offset=".$y.$m."01";
		curl_setopt($this->ch, CURLOPT_URL, $history_url);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve page $page history for year $y, month $m", curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "warning");
		}
		//$matches = array();
		preg_match("/oldid\=\d+/", $content, $matches);
		if(count($matches) > 0){
			echo "got a match ";
			opr($matches);
			return $page . "?". $matches[0];
		}
		echo $history_url."<hr><p>".$content." bytes returned"."<hr><P>";
		return false;
	}
	
	/**
	 *
	 */
	function generateSchema(){
		curl_setopt($this->ch, CURLOPT_URL, $this->getServiceSetting('codeBook'));
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve url: $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "info");
		}
		echo("#Main Variables (polity-based)\n");
		$bits = explode("Main Variables (polity-based)", $content);
		$content = $bits[count($bits)-1];//ditch the early bit;
		$bits = explode("</dl>", $content);
		array_pop($bits);
		$content = implode("</dl>", $bits);
		$sections = explode("<h2>", $content);
		foreach($sections as $sect){
			$properties = array();
			$sec_bits = explode("</span></h2>", $sect);
			if(count($sec_bits) == 2){
				$sec_title = substr($sec_bits[0], strrpos($sec_bits[0], ">")+1);
				echo("#$sec_title");
				mb_regex_encoding('UTF-8');
				$pieces = mb_split('♠', $sec_bits[1]);
				foreach($pieces as $piece){
					$sub_pieces = mb_split("♣.*♥", $piece);
						
					$property_name = trim(strip_tags($sub_pieces[0]));
					if($property_name){
						$property_comment = trim(strip_tags($sub_pieces[1]));
						$property_nam = str_replace(array("(", ")", "-"), " ", $property_name);
						$names_pieces = preg_split("/\s+/", $property_nam);
						//echo count($names_pieces);
						$pname = "";
						foreach($names_pieces as $np){
							$pname .= ucfirst($np);
						}
						$domain = $this->mapVariableToDomain($pname);
						if($domain){
							$range = $this->mapVariableToRange($pname);
							$prop_assertions = array();
							$prop_assertions[] = 'seshat:'.$pname." a ".$range[0].";\n";
							$prop_assertions[] = "\trdfs:label \"$property_name\";\n";
							$prop_assertions[] = "\trdfs:domain $domain;\n";
							$prop_assertions[] = "\trdfs:range  $range[1];\n";
							if($this->allowsMultipleValues($pname) === false){
							$prop_assertions[] = "\trdfs:subClassOf [ a owl:Restriction ;\n";
									$prop_assertions[] = "\t\towl:maxCardinality 1 ;\n";
											$prop_assertions[] = "\t\towl:onProperty sghd:$pname\n";
											$prop_assertions[] = "\t\t] ;\n";
							}
							$prop_assertions[] = "\trdfs:comment \"$property_comment\" .\n\n";
							opr($prop_assertions);
							foreach($prop_assertions as $p){
								//echo $p;
							}
						}
					}
				}
				//get rid of all text up to first variable...
			}
		}
		//rdfs:subClassOf
		//[ a       owl:Restriction ;
		//owl:maxCardinality 1 ;
		//owl:onProperty :hours
		//] ;
	
		//$facts = $this->getFactsFromPage($content);
		//mb_regex_encoding('UTF-8');
		//$pieces = mb_split('♠', $content);
		//get rid of all text up to first variable...
		//array_shift($pieces);
		//opr($pieces);
		return true;
	
		//$cfacts = $this->getSchemaFromURL($this->settings['scraper']['codeBook']);
	
	}
	
	//The following 3 functions are mappings to rdf for each variable
	
	function allowsMultipleValues($property_name){
		$map = array(
				"AlternativeNames"
		);
		return in_array($property_name, $map);
	}
	
	function mapVariableToDomain($property_name, $context = ""){
		$map = array(
				"RA" => false,
				"Expert" => false,
				"Duration" => "seshat:TemporalEntity"
		);
		if(isset($map[$property_name])) {
			return $map[$property_name];
		}
		return "seshat:SocialOrganisation";
	}
	
	function mapVariableToRange($property_name){
		$map = array(
				"Duration" => array("owl:ObjectProperty", "time:Interval"),
				"UTMZone" => array("owl:ObjectProperty", "tzont:TimeZone"),
				"PeakDate" => array("owl:ObjectProperty", "time:TemporalEntity")
		);
		return (isset($map[$property_name])) ? $map[$property_name] : array("owl:DataProperty", "xsd:string");
	}
	
	

	/*
	 * Functions for making url names of pages readable
	 */
	function formatNGAName($url){
		$bits = explode("/", $url);
		$x = $bits[count($bits) - 1];
		return str_replace(array("_", ",", "#"), array(" ", "", ""), $x);
		//return str_replace("_", " ", $x);
	}
	
	function parsePolityName($url){
		$p_details = array(
			"url" => $url,
			"shorturl" => substr($url, 0,40),
			"polityname" => "");
		$bits = explode("/", $url);
		$x = $bits[count($bits) - 1];
		$p_details['polityname'] = str_replace("_", " ", $x);
		return $p_details;
	}
	
	function unformatSectionName($tit){
		return str_replace(" ", "_", $tit);
	}

}

