<?php
/*
 * A place to put random utility functions that you might want to use again
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified: 
 * Licence: GPL v2
 */


function opr($s){
	echo "<PRE>";
	print_r($s);
	echo "</PRE>";
}

function randid(){
	return uniqid_base36();
}

function uniqid_base36($more_entropy=false) {
	$s = uniqid('', $more_entropy);
	if (!$more_entropy)
		return base_convert($s, 16, 36);
	 
	$hex = substr($s, 0, 13);
	$dec = $s[13] . substr($s, 15); // skip the dot
	return base_convert($hex, 16, 36) . base_convert($dec, 10, 36);
}

function isAssoc($arr)
{
	return array_keys($arr) !== range(0, count($arr) - 1);
}

function createRandomKey($length)
{
	$buffer = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
	$encodedBuffer = str_replace(array('+', '/'), array('_', 'X'), base64_encode($buffer));
	return substr($encodedBuffer, 0, $length);
}

function sendemail($headers, $recip, $subj, $text){
	if(!$headers){
		$headers = 'From: dacura@cs.tcd.ie' . "\r\n" .
			'Reply-To: dacura@cs.tcd.ie' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
	}
	if(is_array($headers)){
		
	}
	
	if(mail( $recip, $subj, $text, $headers)){
		return true;
	}
	return false;
}

function deepArrCopy($x){
	$vals = array();
	if(is_array($x)){
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
	return $x;
}

function isLiteral($x){
	return (is_string($x) && !isURL($x) && !isBlankNode($x) && !isNamespacedURL($x));
}

function isURL($str){
	return (!filter_var($str, FILTER_VALIDATE_URL) === false);
}

function isValidEmail($email){
	return (!filter_var($email, FILTER_VALIDATE_EMAIL) === false);
}

function isNamespacedURL($x){
	if(is_array($x)){
		//pr($x);
		return false;
	}
	else {
		$bits = explode(":", $x);
		return (!isURL($x) && count($bits) > 1 && strlen($bits[0]) <= 16 && strlen($bits[0]) >= 1 && !preg_match('/[^a-z0-9]/', $bits[0]));		
	}
}

function getNamespacePortion($str){
	if(is_array($str)) return false;
	$bits = explode(":", $str);
	return (count($bits) > 1) ? $bits[0]: false;
}

function isBlankNode($str){
	return (getNamespacePortion($str) == "_");
}

function isServiceName($servicename, $settings){
	return file_exists($settings['path_to_services'].$servicename);
}

function path_to_snippet($snip){
	return "phplib/services/core/snippets/".$snip.".php";
}

/*
 * generates correct text and codes for http responses
*
* Created By: Chekov
* Creation Date: 20/11/2014
* Contributors:
* Modified:
* Licence: GPL v2
*/


if (!function_exists('http_response_code')) {
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
 * Adapted from https://gist.github.com/lorenzos/1711e81a9162320fde20
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

