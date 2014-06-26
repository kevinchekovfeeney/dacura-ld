<?php
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
	global $dacura_settings;
	$headers = 'From: feeney.kdeg@gmail.com' . "\r\n" .
			'Reply-To: feeney.kdeg@gmail.com' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
	if(mail( $recip, $subj, $text, $headers)){
		return true;
	}
	return false;
}