<?php

/*
 * Scraper Page parser
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Licence: GPL v2
 */


require_once 'autoloader.php';
use hafriedlander\Peg\Parser;

/**
 * This parser parses Seshat data
 * Needs to be run from the command line...
 * >php cli.php seshat.peg.inc seshat.parser.php
 * @author Chekov
 */
class seshatParsing extends Parser\Basic {

/* fact: (value:datedfact SPACE ";" SPACE)* (value:datedfact | value:undatedfact) */
protected $match_fact_typestack = array('fact');
function match_fact ($stack = array()) {
	$matchrule = "fact"; $result = $this->construct($matchrule, $matchrule, null);
	$_13 = NULL;
	do {
		while (true) {
			$res_5 = $result;
			$pos_5 = $this->pos;
			$_4 = NULL;
			do {
				$matcher = 'match_'.'datedfact'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_4 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_4 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_4 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_4 = FALSE; break; }
				$_4 = TRUE; break;
			}
			while(0);
			if( $_4 === FALSE) {
				$result = $res_5;
				$this->pos = $pos_5;
				unset( $res_5 );
				unset( $pos_5 );
				break;
			}
		}
		$_11 = NULL;
		do {
			$_9 = NULL;
			do {
				$res_6 = $result;
				$pos_6 = $this->pos;
				$matcher = 'match_'.'datedfact'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_9 = TRUE; break;
				}
				$result = $res_6;
				$this->pos = $pos_6;
				$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_9 = TRUE; break;
				}
				$result = $res_6;
				$this->pos = $pos_6;
				$_9 = FALSE; break;
			}
			while(0);
			if( $_9 === FALSE) { $_11 = FALSE; break; }
			$_11 = TRUE; break;
		}
		while(0);
		if( $_11 === FALSE) { $_13 = FALSE; break; }
		$_13 = TRUE; break;
	}
	while(0);
	if( $_13 === TRUE ) { return $this->finalise($result); }
	if( $_13 === FALSE) { return FALSE; }
}


/* datedfact: (value:undatedfact SPACE ":" SPACE value:datevalue) | (value:datevalue SPACE ":" SPACE value:undatedfact)  */
protected $match_datedfact_typestack = array('datedfact');
function match_datedfact ($stack = array()) {
	$matchrule = "datedfact"; $result = $this->construct($matchrule, $matchrule, null);
	$_30 = NULL;
	do {
		$res_15 = $result;
		$pos_15 = $this->pos;
		$_21 = NULL;
		do {
			$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_21 = FALSE; break; }
			$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_21 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == ':') {
				$this->pos += 1;
				$result["text"] .= ':';
			}
			else { $_21 = FALSE; break; }
			$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_21 = FALSE; break; }
			$matcher = 'match_'.'datevalue'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_21 = FALSE; break; }
			$_21 = TRUE; break;
		}
		while(0);
		if( $_21 === TRUE ) { $_30 = TRUE; break; }
		$result = $res_15;
		$this->pos = $pos_15;
		$_28 = NULL;
		do {
			$matcher = 'match_'.'datevalue'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_28 = FALSE; break; }
			$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_28 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == ':') {
				$this->pos += 1;
				$result["text"] .= ':';
			}
			else { $_28 = FALSE; break; }
			$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_28 = FALSE; break; }
			$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_28 = FALSE; break; }
			$_28 = TRUE; break;
		}
		while(0);
		if( $_28 === TRUE ) { $_30 = TRUE; break; }
		$result = $res_15;
		$this->pos = $pos_15;
		$_30 = FALSE; break;
	}
	while(0);
	if( $_30 === TRUE ) { return $this->finalise($result); }
	if( $_30 === FALSE) { return FALSE; }
}


/* datevalue: value:daterange | value:disagreedate | value:singledate */
protected $match_datevalue_typestack = array('datevalue');
function match_datevalue ($stack = array()) {
	$matchrule = "datevalue"; $result = $this->construct($matchrule, $matchrule, null);
	$_39 = NULL;
	do {
		$res_32 = $result;
		$pos_32 = $this->pos;
		$matcher = 'match_'.'daterange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_39 = TRUE; break;
		}
		$result = $res_32;
		$this->pos = $pos_32;
		$_37 = NULL;
		do {
			$res_34 = $result;
			$pos_34 = $this->pos;
			$matcher = 'match_'.'disagreedate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_37 = TRUE; break;
			}
			$result = $res_34;
			$this->pos = $pos_34;
			$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_37 = TRUE; break;
			}
			$result = $res_34;
			$this->pos = $pos_34;
			$_37 = FALSE; break;
		}
		while(0);
		if( $_37 === TRUE ) { $_39 = TRUE; break; }
		$result = $res_32;
		$this->pos = $pos_32;
		$_39 = FALSE; break;
	}
	while(0);
	if( $_39 === TRUE ) { return $this->finalise($result); }
	if( $_39 === FALSE) { return FALSE; }
}


/* daterange: value:singledate SPACE "-" SPACE value:singledate */
protected $match_daterange_typestack = array('daterange');
function match_daterange ($stack = array()) {
	$matchrule = "daterange"; $result = $this->construct($matchrule, $matchrule, null);
	$_46 = NULL;
	do {
		$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_46 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_46 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '-') {
			$this->pos += 1;
			$result["text"] .= '-';
		}
		else { $_46 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_46 = FALSE; break; }
		$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_46 = FALSE; break; }
		$_46 = TRUE; break;
	}
	while(0);
	if( $_46 === TRUE ) { return $this->finalise($result); }
	if( $_46 === FALSE) { return FALSE; }
}


/* singledate: value:uncertaindate | value:disagreedate | value:simpledate */
protected $match_singledate_typestack = array('singledate');
function match_singledate ($stack = array()) {
	$matchrule = "singledate"; $result = $this->construct($matchrule, $matchrule, null);
	$_55 = NULL;
	do {
		$res_48 = $result;
		$pos_48 = $this->pos;
		$matcher = 'match_'.'uncertaindate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_55 = TRUE; break;
		}
		$result = $res_48;
		$this->pos = $pos_48;
		$_53 = NULL;
		do {
			$res_50 = $result;
			$pos_50 = $this->pos;
			$matcher = 'match_'.'disagreedate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_53 = TRUE; break;
			}
			$result = $res_50;
			$this->pos = $pos_50;
			$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_53 = TRUE; break;
			}
			$result = $res_50;
			$this->pos = $pos_50;
			$_53 = FALSE; break;
		}
		while(0);
		if( $_53 === TRUE ) { $_55 = TRUE; break; }
		$result = $res_48;
		$this->pos = $pos_48;
		$_55 = FALSE; break;
	}
	while(0);
	if( $_55 === TRUE ) { return $this->finalise($result); }
	if( $_55 === FALSE) { return FALSE; }
}


/* simpledaterange: value:simpledate SPACE "-" SPACE value:simpledate */
protected $match_simpledaterange_typestack = array('simpledaterange');
function match_simpledaterange ($stack = array()) {
	$matchrule = "simpledaterange"; $result = $this->construct($matchrule, $matchrule, null);
	$_62 = NULL;
	do {
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_62 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_62 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '-') {
			$this->pos += 1;
			$result["text"] .= '-';
		}
		else { $_62 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_62 = FALSE; break; }
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_62 = FALSE; break; }
		$_62 = TRUE; break;
	}
	while(0);
	if( $_62 === TRUE ) { return $this->finalise($result); }
	if( $_62 === FALSE) { return FALSE; }
}


/* disagreedatefragment: value:simpledaterange | value:simpledate */
protected $match_disagreedatefragment_typestack = array('disagreedatefragment');
function match_disagreedatefragment ($stack = array()) {
	$matchrule = "disagreedatefragment"; $result = $this->construct($matchrule, $matchrule, null);
	$_67 = NULL;
	do {
		$res_64 = $result;
		$pos_64 = $this->pos;
		$matcher = 'match_'.'simpledaterange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_67 = TRUE; break;
		}
		$result = $res_64;
		$this->pos = $pos_64;
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_67 = TRUE; break;
		}
		$result = $res_64;
		$this->pos = $pos_64;
		$_67 = FALSE; break;
	}
	while(0);
	if( $_67 === TRUE ) { return $this->finalise($result); }
	if( $_67 === FALSE) { return FALSE; }
}


/* disagreedate: "{" SPACE (value:disagreedatefragment SPACE ";" SPACE)+ value:disagreedatefragment SPACE "}" */
protected $match_disagreedate_typestack = array('disagreedate');
function match_disagreedate ($stack = array()) {
	$matchrule = "disagreedate"; $result = $this->construct($matchrule, $matchrule, null);
	$_80 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_80 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_80 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_76 = $result;
			$pos_76 = $this->pos;
			$_75 = NULL;
			do {
				$matcher = 'match_'.'disagreedatefragment'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_75 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_75 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_75 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_75 = FALSE; break; }
				$_75 = TRUE; break;
			}
			while(0);
			if( $_75 === FALSE) {
				$result = $res_76;
				$this->pos = $pos_76;
				unset( $res_76 );
				unset( $pos_76 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_80 = FALSE; break; }
		$matcher = 'match_'.'disagreedatefragment'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_80 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_80 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_80 = FALSE; break; }
		$_80 = TRUE; break;
	}
	while(0);
	if( $_80 === TRUE ) { return $this->finalise($result); }
	if( $_80 === FALSE) { return FALSE; }
}


/* uncertaindate:  "[" SPACE ((value:simpledaterange | value:simpledate) SPACE ";" SPACE)+ (value:simpledaterange | value:simpledate) SPACE "]"  */
protected $match_uncertaindate_typestack = array('uncertaindate');
function match_uncertaindate ($stack = array()) {
	$matchrule = "uncertaindate"; $result = $this->construct($matchrule, $matchrule, null);
	$_105 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_105 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_105 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_95 = $result;
			$pos_95 = $this->pos;
			$_94 = NULL;
			do {
				$_89 = NULL;
				do {
					$_87 = NULL;
					do {
						$res_84 = $result;
						$pos_84 = $this->pos;
						$matcher = 'match_'.'simpledaterange'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "value" );
							$_87 = TRUE; break;
						}
						$result = $res_84;
						$this->pos = $pos_84;
						$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "value" );
							$_87 = TRUE; break;
						}
						$result = $res_84;
						$this->pos = $pos_84;
						$_87 = FALSE; break;
					}
					while(0);
					if( $_87 === FALSE) { $_89 = FALSE; break; }
					$_89 = TRUE; break;
				}
				while(0);
				if( $_89 === FALSE) { $_94 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_94 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_94 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_94 = FALSE; break; }
				$_94 = TRUE; break;
			}
			while(0);
			if( $_94 === FALSE) {
				$result = $res_95;
				$this->pos = $pos_95;
				unset( $res_95 );
				unset( $pos_95 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_105 = FALSE; break; }
		$_101 = NULL;
		do {
			$_99 = NULL;
			do {
				$res_96 = $result;
				$pos_96 = $this->pos;
				$matcher = 'match_'.'simpledaterange'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_99 = TRUE; break;
				}
				$result = $res_96;
				$this->pos = $pos_96;
				$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_99 = TRUE; break;
				}
				$result = $res_96;
				$this->pos = $pos_96;
				$_99 = FALSE; break;
			}
			while(0);
			if( $_99 === FALSE) { $_101 = FALSE; break; }
			$_101 = TRUE; break;
		}
		while(0);
		if( $_101 === FALSE) { $_105 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_105 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_105 = FALSE; break; }
		$_105 = TRUE; break;
	}
	while(0);
	if( $_105 === TRUE ) { return $this->finalise($result); }
	if( $_105 === FALSE) { return FALSE; }
}


/* simpledate: value:year SPACE ( value:yearsuffix )? */
protected $match_simpledate_typestack = array('simpledate');
function match_simpledate ($stack = array()) {
	$matchrule = "simpledate"; $result = $this->construct($matchrule, $matchrule, null);
	$_112 = NULL;
	do {
		$matcher = 'match_'.'year'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_112 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_112 = FALSE; break; }
		$res_111 = $result;
		$pos_111 = $this->pos;
		$_110 = NULL;
		do {
			$matcher = 'match_'.'yearsuffix'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_110 = FALSE; break; }
			$_110 = TRUE; break;
		}
		while(0);
		if( $_110 === FALSE) {
			$result = $res_111;
			$this->pos = $pos_111;
			unset( $res_111 );
			unset( $pos_111 );
		}
		$_112 = TRUE; break;
	}
	while(0);
	if( $_112 === TRUE ) { return $this->finalise($result); }
	if( $_112 === FALSE) { return FALSE; }
}


/* undatedfact: value:uncertainlist | value:uncertainrange | value:disagreelist |  value:string  */
protected $match_undatedfact_typestack = array('undatedfact');
function match_undatedfact ($stack = array()) {
	$matchrule = "undatedfact"; $result = $this->construct($matchrule, $matchrule, null);
	$_125 = NULL;
	do {
		$res_114 = $result;
		$pos_114 = $this->pos;
		$matcher = 'match_'.'uncertainlist'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_125 = TRUE; break;
		}
		$result = $res_114;
		$this->pos = $pos_114;
		$_123 = NULL;
		do {
			$res_116 = $result;
			$pos_116 = $this->pos;
			$matcher = 'match_'.'uncertainrange'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_123 = TRUE; break;
			}
			$result = $res_116;
			$this->pos = $pos_116;
			$_121 = NULL;
			do {
				$res_118 = $result;
				$pos_118 = $this->pos;
				$matcher = 'match_'.'disagreelist'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_121 = TRUE; break;
				}
				$result = $res_118;
				$this->pos = $pos_118;
				$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_121 = TRUE; break;
				}
				$result = $res_118;
				$this->pos = $pos_118;
				$_121 = FALSE; break;
			}
			while(0);
			if( $_121 === TRUE ) { $_123 = TRUE; break; }
			$result = $res_116;
			$this->pos = $pos_116;
			$_123 = FALSE; break;
		}
		while(0);
		if( $_123 === TRUE ) { $_125 = TRUE; break; }
		$result = $res_114;
		$this->pos = $pos_114;
		$_125 = FALSE; break;
	}
	while(0);
	if( $_125 === TRUE ) { return $this->finalise($result); }
	if( $_125 === FALSE) { return FALSE; }
}


/* uncertainlist: "[" SPACE  (value:string SPACE ";" SPACE)+ value:string SPACE "]" */
protected $match_uncertainlist_typestack = array('uncertainlist');
function match_uncertainlist ($stack = array()) {
	$matchrule = "uncertainlist"; $result = $this->construct($matchrule, $matchrule, null);
	$_138 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_138 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_138 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_134 = $result;
			$pos_134 = $this->pos;
			$_133 = NULL;
			do {
				$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_133 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_133 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_133 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_133 = FALSE; break; }
				$_133 = TRUE; break;
			}
			while(0);
			if( $_133 === FALSE) {
				$result = $res_134;
				$this->pos = $pos_134;
				unset( $res_134 );
				unset( $pos_134 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_138 = FALSE; break; }
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_138 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_138 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_138 = FALSE; break; }
		$_138 = TRUE; break;
	}
	while(0);
	if( $_138 === TRUE ) { return $this->finalise($result); }
	if( $_138 === FALSE) { return FALSE; }
}


/* uncertainrange: "[" SPACE  (value:nodashstring SPACE "-" SPACE)+ value:nodashstring SPACE "]" */
protected $match_uncertainrange_typestack = array('uncertainrange');
function match_uncertainrange ($stack = array()) {
	$matchrule = "uncertainrange"; $result = $this->construct($matchrule, $matchrule, null);
	$_151 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_151 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_151 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_147 = $result;
			$pos_147 = $this->pos;
			$_146 = NULL;
			do {
				$matcher = 'match_'.'nodashstring'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_146 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_146 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '-') {
					$this->pos += 1;
					$result["text"] .= '-';
				}
				else { $_146 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_146 = FALSE; break; }
				$_146 = TRUE; break;
			}
			while(0);
			if( $_146 === FALSE) {
				$result = $res_147;
				$this->pos = $pos_147;
				unset( $res_147 );
				unset( $pos_147 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_151 = FALSE; break; }
		$matcher = 'match_'.'nodashstring'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_151 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_151 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_151 = FALSE; break; }
		$_151 = TRUE; break;
	}
	while(0);
	if( $_151 === TRUE ) { return $this->finalise($result); }
	if( $_151 === FALSE) { return FALSE; }
}


/* disagreelistfragment: value:uncertainrange | value:string */
protected $match_disagreelistfragment_typestack = array('disagreelistfragment');
function match_disagreelistfragment ($stack = array()) {
	$matchrule = "disagreelistfragment"; $result = $this->construct($matchrule, $matchrule, null);
	$_156 = NULL;
	do {
		$res_153 = $result;
		$pos_153 = $this->pos;
		$matcher = 'match_'.'uncertainrange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_156 = TRUE; break;
		}
		$result = $res_153;
		$this->pos = $pos_153;
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_156 = TRUE; break;
		}
		$result = $res_153;
		$this->pos = $pos_153;
		$_156 = FALSE; break;
	}
	while(0);
	if( $_156 === TRUE ) { return $this->finalise($result); }
	if( $_156 === FALSE) { return FALSE; }
}


/* disagreelist: "{" SPACE  (value:disagreelistfragment SPACE ";" SPACE)+ value:disagreelistfragment SPACE "}" */
protected $match_disagreelist_typestack = array('disagreelist');
function match_disagreelist ($stack = array()) {
	$matchrule = "disagreelist"; $result = $this->construct($matchrule, $matchrule, null);
	$_169 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_169 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_169 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_165 = $result;
			$pos_165 = $this->pos;
			$_164 = NULL;
			do {
				$matcher = 'match_'.'disagreelistfragment'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_164 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_164 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_164 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_164 = FALSE; break; }
				$_164 = TRUE; break;
			}
			while(0);
			if( $_164 === FALSE) {
				$result = $res_165;
				$this->pos = $pos_165;
				unset( $res_165 );
				unset( $pos_165 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_169 = FALSE; break; }
		$matcher = 'match_'.'disagreelistfragment'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_169 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_169 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_169 = FALSE; break; }
		$_169 = TRUE; break;
	}
	while(0);
	if( $_169 === TRUE ) { return $this->finalise($result); }
	if( $_169 === FALSE) { return FALSE; }
}


/* string: word (SPACE word)* */
protected $match_string_typestack = array('string');
function match_string ($stack = array()) {
	$matchrule = "string"; $result = $this->construct($matchrule, $matchrule, null);
	$_176 = NULL;
	do {
		$matcher = 'match_'.'word'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_176 = FALSE; break; }
		while (true) {
			$res_175 = $result;
			$pos_175 = $this->pos;
			$_174 = NULL;
			do {
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_174 = FALSE; break; }
				$matcher = 'match_'.'word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_174 = FALSE; break; }
				$_174 = TRUE; break;
			}
			while(0);
			if( $_174 === FALSE) {
				$result = $res_175;
				$this->pos = $pos_175;
				unset( $res_175 );
				unset( $pos_175 );
				break;
			}
		}
		$_176 = TRUE; break;
	}
	while(0);
	if( $_176 === TRUE ) { return $this->finalise($result); }
	if( $_176 === FALSE) { return FALSE; }
}


/* nodashstring: nodashword (SPACE nodashword)* */
protected $match_nodashstring_typestack = array('nodashstring');
function match_nodashstring ($stack = array()) {
	$matchrule = "nodashstring"; $result = $this->construct($matchrule, $matchrule, null);
	$_183 = NULL;
	do {
		$matcher = 'match_'.'nodashword'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_183 = FALSE; break; }
		while (true) {
			$res_182 = $result;
			$pos_182 = $this->pos;
			$_181 = NULL;
			do {
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_181 = FALSE; break; }
				$matcher = 'match_'.'nodashword'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_181 = FALSE; break; }
				$_181 = TRUE; break;
			}
			while(0);
			if( $_181 === FALSE) {
				$result = $res_182;
				$this->pos = $pos_182;
				unset( $res_182 );
				unset( $pos_182 );
				break;
			}
		}
		$_183 = TRUE; break;
	}
	while(0);
	if( $_183 === TRUE ) { return $this->finalise($result); }
	if( $_183 === FALSE) { return FALSE; }
}


/* SPACE: " "* */
protected $match_SPACE_typestack = array('SPACE');
function match_SPACE ($stack = array()) {
	$matchrule = "SPACE"; $result = $this->construct($matchrule, $matchrule, null);
	while (true) {
		$res_185 = $result;
		$pos_185 = $this->pos;
		if (substr($this->string,$this->pos,1) == ' ') {
			$this->pos += 1;
			$result["text"] .= ' ';
		}
		else {
			$result = $res_185;
			$this->pos = $pos_185;
			unset( $res_185 );
			unset( $pos_185 );
			break;
		}
	}
	return $this->finalise($result);
}


/* nodashword: /[^-\]\[\{\};: ]+/ */
protected $match_nodashword_typestack = array('nodashword');
function match_nodashword ($stack = array()) {
	$matchrule = "nodashword"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/[^-\]\[\{\};: ]+/' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* word: /[^\]\[\{\};: ]+/ */
protected $match_word_typestack = array('word');
function match_word ($stack = array()) {
	$matchrule = "word"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/[^\]\[\{\};: ]+/' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* year: /[0-9]{1,5}/ */
protected $match_year_typestack = array('year');
function match_year ($stack = array()) {
	$matchrule = "year"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/[0-9]{1,5}/' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* yearsuffix: "bce" | "ce" | "bc" | "BCE" | "CE" | "BC" | "Bce" | "Ce" | "Bc" */
protected $match_yearsuffix_typestack = array('yearsuffix');
function match_yearsuffix ($stack = array()) {
	$matchrule = "yearsuffix"; $result = $this->construct($matchrule, $matchrule, null);
	$_220 = NULL;
	do {
		$res_189 = $result;
		$pos_189 = $this->pos;
		if (( $subres = $this->literal( 'bce' ) ) !== FALSE) {
			$result["text"] .= $subres;
			$_220 = TRUE; break;
		}
		$result = $res_189;
		$this->pos = $pos_189;
		$_218 = NULL;
		do {
			$res_191 = $result;
			$pos_191 = $this->pos;
			if (( $subres = $this->literal( 'ce' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_218 = TRUE; break;
			}
			$result = $res_191;
			$this->pos = $pos_191;
			$_216 = NULL;
			do {
				$res_193 = $result;
				$pos_193 = $this->pos;
				if (( $subres = $this->literal( 'bc' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_216 = TRUE; break;
				}
				$result = $res_193;
				$this->pos = $pos_193;
				$_214 = NULL;
				do {
					$res_195 = $result;
					$pos_195 = $this->pos;
					if (( $subres = $this->literal( 'BCE' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_214 = TRUE; break;
					}
					$result = $res_195;
					$this->pos = $pos_195;
					$_212 = NULL;
					do {
						$res_197 = $result;
						$pos_197 = $this->pos;
						if (( $subres = $this->literal( 'CE' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_212 = TRUE; break;
						}
						$result = $res_197;
						$this->pos = $pos_197;
						$_210 = NULL;
						do {
							$res_199 = $result;
							$pos_199 = $this->pos;
							if (( $subres = $this->literal( 'BC' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_210 = TRUE; break;
							}
							$result = $res_199;
							$this->pos = $pos_199;
							$_208 = NULL;
							do {
								$res_201 = $result;
								$pos_201 = $this->pos;
								if (( $subres = $this->literal( 'Bce' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_208 = TRUE; break;
								}
								$result = $res_201;
								$this->pos = $pos_201;
								$_206 = NULL;
								do {
									$res_203 = $result;
									$pos_203 = $this->pos;
									if (( $subres = $this->literal( 'Ce' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_206 = TRUE; break;
									}
									$result = $res_203;
									$this->pos = $pos_203;
									if (( $subres = $this->literal( 'Bc' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_206 = TRUE; break;
									}
									$result = $res_203;
									$this->pos = $pos_203;
									$_206 = FALSE; break;
								}
								while(0);
								if( $_206 === TRUE ) { $_208 = TRUE; break; }
								$result = $res_201;
								$this->pos = $pos_201;
								$_208 = FALSE; break;
							}
							while(0);
							if( $_208 === TRUE ) { $_210 = TRUE; break; }
							$result = $res_199;
							$this->pos = $pos_199;
							$_210 = FALSE; break;
						}
						while(0);
						if( $_210 === TRUE ) { $_212 = TRUE; break; }
						$result = $res_197;
						$this->pos = $pos_197;
						$_212 = FALSE; break;
					}
					while(0);
					if( $_212 === TRUE ) { $_214 = TRUE; break; }
					$result = $res_195;
					$this->pos = $pos_195;
					$_214 = FALSE; break;
				}
				while(0);
				if( $_214 === TRUE ) { $_216 = TRUE; break; }
				$result = $res_193;
				$this->pos = $pos_193;
				$_216 = FALSE; break;
			}
			while(0);
			if( $_216 === TRUE ) { $_218 = TRUE; break; }
			$result = $res_191;
			$this->pos = $pos_191;
			$_218 = FALSE; break;
		}
		while(0);
		if( $_218 === TRUE ) { $_220 = TRUE; break; }
		$result = $res_189;
		$this->pos = $pos_189;
		$_220 = FALSE; break;
	}
	while(0);
	if( $_220 === TRUE ) { return $this->finalise($result); }
	if( $_220 === FALSE) { return FALSE; }
}




}