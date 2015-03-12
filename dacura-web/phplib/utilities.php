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

function getNamespacePortion($str){
	$bits = explode(":", $str);
	return (count($bits) > 1) ? $bits[0]: false;
}

function isBlankNode($str){
	return (getNamespacePortion($str) == "_");
}