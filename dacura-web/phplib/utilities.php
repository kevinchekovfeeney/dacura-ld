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
	$encodedBuffer = str_replace(array('+', '/'), array(',', '-'), base64_encode($buffer));
	return substr($encodedBuffer, 0, $length);
}

function sendemail($recip, $subj, $text){
	$headers = 'From: feeney.kdeg@gmail.com' . "\r\n" .
			'Reply-To: feeney.kdeg@gmail.com' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
	if(mail( $recip, $subj, $text, $headers)){
		return true;
	}
	return false;
}

function isURL($str){
	return (!filter_var($str, FILTER_VALIDATE_URL) === false);
}

function deepArrCopy($x){
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

function isNamespacedURL($x){
	$bits = explode(":", $x);
	return (!isURL($x) && count($bits) > 1 && strlen($bits[0]) <= 16 && strlen($bits[0]) >= 2 && !preg_match('/[^a-z0-9]/', $bits[0]));
}

function getNamespacePortion($str){
	$bits = explode(":", $str);
	return (count($bits) > 1) ? $bits[0]: false;
}

function isBlankNode($str){
	return (getNamespacePortion($str) == "_");
}