<?php
/**
 * A place to put random utility functions that are useful in multiple places
 * 
 * * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */

/**
 * Prints out a php datastructure in structured way with pre tags wrapping it
 * @param mixed $s any php variable
 */
function opr($s){
	echo "<PRE>";
	print_r($s);
	echo "</PRE>";
}

/**
 * Generates a randomised ID using php's uniqid function
 * @return string id
 */
function randid(){
	return uniqid_base36();
}

/**
 * generates a base 36 encoded uniqid (for tersness)
 * @param boolean $more_entropy if set to true, a longer id, with more entropy will be returned
 * @return string
 */
function uniqid_base36($more_entropy=false) {
	$s = uniqid('', $more_entropy);
	if (!$more_entropy)
		return base_convert($s, 16, 36);
	 
	$hex = substr($s, 0, 13);
	$dec = $s[13] . substr($s, 15); // skip the dot
	return base_convert($hex, 16, 36) . base_convert($dec, 10, 36);
}

/**
 * Function to generate a randomised key of specified length
 * @param int $length length of key desired
 * @return string key
 */
function createRandomKey($length){
	$buffer = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
	$encodedBuffer = str_replace(array('+', '/'), array('_', 'X'), base64_encode($buffer));
	return substr($encodedBuffer, 0, $length);
}

/**
 * Is the passed data structure a php associative array
 * @param mixed $arr variable to be tested
 * @return boolean true if the variable is an associative array (and is not indexed 0,1,2...)
 */
function isAssoc($arr)
{
	return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * Sends an email
 * @param string $recip recipient email address
 * @param string $subj subject line
 * @param string $text body 
 * @param string $headers message headers
 * @return boolean true if sent
 */
function sendemail($recip, $subj, $text, $headers){
	if(!$headers){
		$headers = 'From: dacura@cs.tcd.ie' . "\r\n" .
			'Reply-To: dacura@cs.tcd.ie' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
	}
	if(is_array($headers)){
		$headers = implode("\r\n", $headers);
	}
	return mail( $recip, $subj, $text, $headers);
}

/**
 * creates a 'deep' recursive copy of an array
 * @param array $x the array to copy 
 * @return array the copied array
 */
function deepArrCopy(array $x){
	$vals = array();
	foreach($x as $i => $v){
		if(is_array($v)){
			$vals[$i] = deepArrCopy($v);
		}
		else {
			$vals[$i] = $v;
		}
	}
	return $vals;
}

/**
 * compares two deep arrays and returns differences. 
 * adapted from arrayRecursiveDiff in comments of http://php.net/manual/en/function.array-diff.php
 */
function arrayRecursiveCompare($aArray1, $aArray2) {
	foreach ($aArray1 as $mKey => $mValue) {
		if (array_key_exists($mKey, $aArray2)) {
			if (is_array($mValue)) {
				if(!arrayRecursiveCompare($mValue, $aArray2[$mKey])){
					return false;
				}
			} else {
				if ($mValue != $aArray2[$mKey]) {
					return false;
				}
			}
		} else {
			return false;
		}
	}
	return true;
}

/**
 * Returns true if the value is a string and is not a url
 * @param unknown $x the value to test
 * @return boolean true if it is a literal
 */
function isLiteral($x){
	return (is_string($x) && !isURL($x) && !isBlankNode($x) && !isNamespacedURL($x));
}

/**
 * Returns true if the passed string is a URL 
 * @param string $str
 * @return boolean
 */
function isURL($str){
	return (!filter_var($str, FILTER_VALIDATE_URL) === false);
}

/**
 * Returns true if the passed string is a valid email address
 * @param string $email
 * @return boolean
 */
function isValidEmail($email){
	return (!filter_var($email, FILTER_VALIDATE_EMAIL) === false);
}

/**
 * Returns true if the passed string is a namespaced url (prefix:localurl)
 * @param string $x the string to test
 * @return boolean
 */
function isNamespacedURL($x){
	if(!is_string($x)){
		return false;
	}
	else {
		$bits = explode(":", $x);
		return (!isURL($x) && count($bits) > 1 && strlen($bits[0]) <= 16 && strlen($bits[0]) >= 1 && !preg_match('/[^a-z0-9]/', $bits[0]));		
	}
}

/**
 * Returns the namespace / prefix portion of a namespaced url
 * @param string $str the string 
 * @return string the namespace portion (false if it does not exist) 
 */
function getNamespacePortion($str){
	if(is_array($str)) return false;
	$bits = explode(":", $str);
	return (count($bits) > 1) ? $bits[0]: false;
}

/**
 * Returns the local portion of a namespaced url
 * @param string $str the string
 * @return string the local id portion (false if it does not exist)
 */
function getPrefixedURLLocalID($str){
	if(is_array($str)) return false;
	if(!($x = strpos($str, ":"))) return false;
	return substr($str, $x);
}

/**
 * Tests for a string being a blank node url (_:...)
 * @param string $str the string to test
 * @return boolean
 */
function isBlankNode($str){
	return (getNamespacePortion($str) == "_");
}

/**
 * Returns the path to a particular snippet 
 * @param string $snip the name of the snippet
 * @return string the path
 */
function path_to_snippet($snip){
	return "phplib/services/core/snippets/".$snip.".php";
}

//this function exists in PHP 5.4 - this emulates it in case we are < 5.4
if (!function_exists('http_response_code')) {
/** 
 * generates correct text and codes for http responses and writes them to http
 * @param number $code the http response code desired
*/
	function http_response_code($code = NULL) {
		if ($code !== NULL) {
			switch ($code) {
				case 100: $text = 'Continue'; break;
				case 101: $text = 'Switching Protocols'; break;
				case 200: $text = 'OK'; break;
				case 201: $text = 'Created'; break;
				case 202: $text = 'Accepted'; break;
				case 203: $text = 'Non-Authoritative Information'; break;
				case 204: $text = 'No Content'; break;
				case 205: $text = 'Reset Content'; break;
				case 206: $text = 'Partial Content'; break;
				case 300: $text = 'Multiple Choices'; break;
				case 301: $text = 'Moved Permanently'; break;
				case 302: $text = 'Moved Temporarily'; break;
				case 303: $text = 'See Other'; break;
				case 304: $text = 'Not Modified'; break;
				case 305: $text = 'Use Proxy'; break;
				case 400: $text = 'Bad Request'; break;
				case 401: $text = 'Unauthorized'; break;
				case 402: $text = 'Payment Required'; break;
				case 403: $text = 'Forbidden'; break;
				case 404: $text = 'Not Found'; break;
				case 405: $text = 'Method Not Allowed'; break;
				case 406: $text = 'Not Acceptable'; break;
				case 407: $text = 'Proxy Authentication Required'; break;
				case 408: $text = 'Request Time-out'; break;
				case 409: $text = 'Conflict'; break;
				case 410: $text = 'Gone'; break;
				case 411: $text = 'Length Required'; break;
				case 412: $text = 'Precondition Failed'; break;
				case 413: $text = 'Request Entity Too Large'; break;
				case 414: $text = 'Request-URI Too Large'; break;
				case 415: $text = 'Unsupported Media Type'; break;
				case 500: $text = 'Internal Server Error'; break;
				case 501: $text = 'Not Implemented'; break;
				case 502: $text = 'Bad Gateway'; break;
				case 503: $text = 'Service Unavailable'; break;
				case 504: $text = 'Gateway Time-out'; break;
				case 505: $text = 'HTTP Version not supported'; break;
				default:
					exit('Unknown http status code "' . htmlentities($code) . '"');
					break;
			}
			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
			header($protocol . ' ' . $code . ' ' . $text);
			$GLOBALS['http_response_code'] = $code;

		} else {
			$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
		}
		return $code;
	}
}

/* 
 */
/**
 * Self-rolled tail replicator 
 * 
 * Adapted from https://gist.github.com/lorenzos/1711e81a9162320fde20
 * @param string $filepath the file to tail
 * @param number $lines number of lines to show
 * @param boolean $adaptive should it adapt to the size of the lines or just use a fixed buffer?
 * @return string last lines in file as a string
 */
function tailCustom($filepath, $lines = 1, $adaptive = true) {
	// Open file
	$f = @fopen($filepath, "rb");
	if ($f === false) return false;
	// Sets buffer size
	if (!$adaptive) $buffer = 4096;
	else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
	// Jump to last character
	fseek($f, -1, SEEK_END);
	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if (fread($f, 1) != "\n") $lines -= 1;

	// Start reading
	$output = '';
	$chunk = '';
	// While we would like more
	while (ftell($f) > 0 && $lines >= 0) {
		// Figure out how far back we should jump
		$seek = min(ftell($f), $buffer);
		// Do the jump (backwards, relative to where we are)
		fseek($f, -$seek, SEEK_CUR);
		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f, $seek)) . $output;
		// Jump back to where we started reading
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		// Decrease our line counter
		$lines -= substr_count($chunk, "\n");
	}
	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ($lines++ < 0) {
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}
	// Close file and return
	fclose($f);
	return trim($output);
}

/**
 * Trims square brackets from the start and front of string
 * @param string $ent the string to be trimmed
 * @return string
 */
function xstrim($ent){
	return trim($ent, "[]");
} 

/**
 * Sets up the default system settings by building various other settings from the ones defined in localsettings.php
 * @param array $dacura_settings - a settings array 
 */
 function default_settings(&$dacura_settings){
	if(!isset($dacura_settings['dacura_sessions'])){
		$dacura_settings['dacura_sessions'] = $dacura_settings['storage_base'].'sessions/';
	}
	if(!isset($dacura_settings['ajaxurl'])){
		$dacura_settings['ajaxurl'] = $dacura_settings['install_url'].$dacura_settings['apistr'] . "/";
	}
	if(!isset($dacura_settings['services_url'])){
		$dacura_settings['services_url'] = $dacura_settings['install_url'].$dacura_settings['path_to_services'];
	}
	if(!isset($dacura_settings['files_url'])){
		$dacura_settings['files_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_files'];
	}
	if(!isset($dacura_settings['path_to_collections'])){
		$dacura_settings['path_to_collections'] = $dacura_settings['storage_base']."collections/";
	}
	if(!isset($dacura_settings['collections_urlbase'])){
		$dacura_settings['collections_urlbase'] = $dacura_settings['install_url'] . "cfiles/";
	}
	if(!isset($dacura_settings['dacura_sessions'])){
		$dacura_settings['dacura_sessions'] = $dacura_settings['storage_base'].'sessions/';
	}
	if(!isset($dacura_settings['dqs_service'])){
		$dacura_settings['dqs_service'] = array();
	}
	if(!isset($dacura_settings['dqs_service']['instance'])){
		$dacura_settings['dqs_service']["instance"] = $dacura_settings['dqs_url']."instance";
	}
	if(!isset($dacura_settings['dqs_service']['schema'])){
		$dacura_settings['dqs_service']["schema"] = $dacura_settings['dqs_url']."schema";
	}
	if(!isset($dacura_settings['dqs_service']['schema_validate'])){
		$dacura_settings['dqs_service']["schema_validate"] = $dacura_settings['dqs_url']."schema_validate";
	}
	if(!isset($dacura_settings['dqs_service']['validate'])){
		$dacura_settings['dqs_service']["validate"] = $dacura_settings['dqs_url']."validate";
	}
	if(!isset($dacura_settings['dqs_service']['instance'])){
		$dacura_settings['dqs_service']["stub"] = $dacura_settings['dqs_url']."stub";
	}
	if(!isset($dacura_settings['dqs_service']['entity'])){
		$dacura_settings['dqs_service']["stub"] = $dacura_settings['dqs_url']."entity";
	}
	if(!isset($dacura_settings['dqs_service']['logfile'])){
		$dacura_settings['dqs_service']["logfile"] = false;
	}
	if(!isset($dacura_settings['dqs_service']['fakets'])){
		$dacura_settings['dqs_service']["fakets"] = $dacura_settings['dacura_logbase'].'fakets.json';
	}
	if(!isset($dacura_settings['dqs_service']['dumplast'])){
		$dacura_settings['dqs_service']["dumplast"] = $dacura_settings['dacura_logbase'].'lastdqs.log';
	}
}

/**
 * Performs content negotiation to figure out which is the best available mime type for the data
 * @param array $mimeTypes array of mime types that are available on the server in order of precedence
 * @param string $acceptedTypes accept string, as passed in accept http header
 * @return array|string either the mime type or the list of acceptable types for the client
 */
function getBestSupportedMimeType($mimeTypes = null, $acceptedTypes = FALSE) {
	// Values will be stored in this array
	$AcceptTypes = Array ();
	if ($acceptedTypes === FALSE){ $acceptedTypes = $_SERVER['HTTP_ACCEPT']; }
	// Accept header is case insensitive, and whitespace isn’t important
	$accept = strtolower(str_replace(' ', '', $acceptedTypes));
	// divide it into parts in the place of a ","
	$accept = explode(',', $accept);
	foreach ($accept as $a) {
		// the default quality is 1.
		$q = 1;
		// check if there is a different quality
		if (strpos($a, ';q=')) {
			// divide "mime/type;q=X" into two parts: "mime/type" i "X"
			list($a, $q) = explode(';q=', $a);
		}
		// mime-type $a is accepted with the quality $q
		// WARNING: $q == 0 means, that mime-type isn’t supported!
		$AcceptTypes[$a] = $q;
	}
	arsort($AcceptTypes);

	// if no parameter was passed, just return parsed data
	if (!$mimeTypes) return $AcceptTypes;

	$mimeTypes = array_map('strtolower', (array)$mimeTypes);

	// let’s check our supported types:
	foreach ($AcceptTypes as $mime => $q) {
		if ($q && in_array($mime, $mimeTypes)) return $mime;
	}
	// no mime-type found
	return null;
}
