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
 * >php cli.php seshat.peg.inc
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


/* factfragment: value:datedfact | value:undatedfact */
protected $match_factfragment_typestack = array('factfragment');
function match_factfragment ($stack = array()) {
	$matchrule = "factfragment"; $result = $this->construct($matchrule, $matchrule, null);
	$_18 = NULL;
	do {
		$res_15 = $result;
		$pos_15 = $this->pos;
		$matcher = 'match_'.'datedfact'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_18 = TRUE; break;
		}
		$result = $res_15;
		$this->pos = $pos_15;
		$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_18 = TRUE; break;
		}
		$result = $res_15;
		$this->pos = $pos_15;
		$_18 = FALSE; break;
	}
	while(0);
	if( $_18 === TRUE ) { return $this->finalise($result); }
	if( $_18 === FALSE) { return FALSE; }
}


/* datedfact: (value:undatedfact SPACE ":" SPACE value:datevalue) | (value:datevalue SPACE ":" SPACE value:undatedfact)  */
protected $match_datedfact_typestack = array('datedfact');
function match_datedfact ($stack = array()) {
	$matchrule = "datedfact"; $result = $this->construct($matchrule, $matchrule, null);
	$_35 = NULL;
	do {
		$res_20 = $result;
		$pos_20 = $this->pos;
		$_26 = NULL;
		do {
			$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_26 = FALSE; break; }
			$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_26 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == ':') {
				$this->pos += 1;
				$result["text"] .= ':';
			}
			else { $_26 = FALSE; break; }
			$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_26 = FALSE; break; }
			$matcher = 'match_'.'datevalue'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_26 = FALSE; break; }
			$_26 = TRUE; break;
		}
		while(0);
		if( $_26 === TRUE ) { $_35 = TRUE; break; }
		$result = $res_20;
		$this->pos = $pos_20;
		$_33 = NULL;
		do {
			$matcher = 'match_'.'datevalue'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_33 = FALSE; break; }
			$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_33 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == ':') {
				$this->pos += 1;
				$result["text"] .= ':';
			}
			else { $_33 = FALSE; break; }
			$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_33 = FALSE; break; }
			$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_33 = FALSE; break; }
			$_33 = TRUE; break;
		}
		while(0);
		if( $_33 === TRUE ) { $_35 = TRUE; break; }
		$result = $res_20;
		$this->pos = $pos_20;
		$_35 = FALSE; break;
	}
	while(0);
	if( $_35 === TRUE ) { return $this->finalise($result); }
	if( $_35 === FALSE) { return FALSE; }
}


/* datevalue: value:daterange | value:disagreedate | value:singledate */
protected $match_datevalue_typestack = array('datevalue');
function match_datevalue ($stack = array()) {
	$matchrule = "datevalue"; $result = $this->construct($matchrule, $matchrule, null);
	$_44 = NULL;
	do {
		$res_37 = $result;
		$pos_37 = $this->pos;
		$matcher = 'match_'.'daterange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_44 = TRUE; break;
		}
		$result = $res_37;
		$this->pos = $pos_37;
		$_42 = NULL;
		do {
			$res_39 = $result;
			$pos_39 = $this->pos;
			$matcher = 'match_'.'disagreedate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_42 = TRUE; break;
			}
			$result = $res_39;
			$this->pos = $pos_39;
			$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_42 = TRUE; break;
			}
			$result = $res_39;
			$this->pos = $pos_39;
			$_42 = FALSE; break;
		}
		while(0);
		if( $_42 === TRUE ) { $_44 = TRUE; break; }
		$result = $res_37;
		$this->pos = $pos_37;
		$_44 = FALSE; break;
	}
	while(0);
	if( $_44 === TRUE ) { return $this->finalise($result); }
	if( $_44 === FALSE) { return FALSE; }
}


/* daterange: value:singledate SPACE "-" SPACE value:singledate */
protected $match_daterange_typestack = array('daterange');
function match_daterange ($stack = array()) {
	$matchrule = "daterange"; $result = $this->construct($matchrule, $matchrule, null);
	$_51 = NULL;
	do {
		$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_51 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_51 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '-') {
			$this->pos += 1;
			$result["text"] .= '-';
		}
		else { $_51 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_51 = FALSE; break; }
		$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_51 = FALSE; break; }
		$_51 = TRUE; break;
	}
	while(0);
	if( $_51 === TRUE ) { return $this->finalise($result); }
	if( $_51 === FALSE) { return FALSE; }
}


/* singledate: value:uncertaindate | value:disagreedate | value:simpledate */
protected $match_singledate_typestack = array('singledate');
function match_singledate ($stack = array()) {
	$matchrule = "singledate"; $result = $this->construct($matchrule, $matchrule, null);
	$_60 = NULL;
	do {
		$res_53 = $result;
		$pos_53 = $this->pos;
		$matcher = 'match_'.'uncertaindate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_60 = TRUE; break;
		}
		$result = $res_53;
		$this->pos = $pos_53;
		$_58 = NULL;
		do {
			$res_55 = $result;
			$pos_55 = $this->pos;
			$matcher = 'match_'.'disagreedate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_58 = TRUE; break;
			}
			$result = $res_55;
			$this->pos = $pos_55;
			$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_58 = TRUE; break;
			}
			$result = $res_55;
			$this->pos = $pos_55;
			$_58 = FALSE; break;
		}
		while(0);
		if( $_58 === TRUE ) { $_60 = TRUE; break; }
		$result = $res_53;
		$this->pos = $pos_53;
		$_60 = FALSE; break;
	}
	while(0);
	if( $_60 === TRUE ) { return $this->finalise($result); }
	if( $_60 === FALSE) { return FALSE; }
}


/* simpledaterange: value:simpledate SPACE "-" SPACE value:simpledate */
protected $match_simpledaterange_typestack = array('simpledaterange');
function match_simpledaterange ($stack = array()) {
	$matchrule = "simpledaterange"; $result = $this->construct($matchrule, $matchrule, null);
	$_67 = NULL;
	do {
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_67 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_67 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '-') {
			$this->pos += 1;
			$result["text"] .= '-';
		}
		else { $_67 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_67 = FALSE; break; }
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_67 = FALSE; break; }
		$_67 = TRUE; break;
	}
	while(0);
	if( $_67 === TRUE ) { return $this->finalise($result); }
	if( $_67 === FALSE) { return FALSE; }
}


/* disagreedatefragment: value:simpledaterange | value:simpledate */
protected $match_disagreedatefragment_typestack = array('disagreedatefragment');
function match_disagreedatefragment ($stack = array()) {
	$matchrule = "disagreedatefragment"; $result = $this->construct($matchrule, $matchrule, null);
	$_72 = NULL;
	do {
		$res_69 = $result;
		$pos_69 = $this->pos;
		$matcher = 'match_'.'simpledaterange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_72 = TRUE; break;
		}
		$result = $res_69;
		$this->pos = $pos_69;
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_72 = TRUE; break;
		}
		$result = $res_69;
		$this->pos = $pos_69;
		$_72 = FALSE; break;
	}
	while(0);
	if( $_72 === TRUE ) { return $this->finalise($result); }
	if( $_72 === FALSE) { return FALSE; }
}


/* disagreedate: "{" SPACE (value:disagreedatefragment SPACE ";" SPACE)+ value:disagreedatefragment SPACE "}" */
protected $match_disagreedate_typestack = array('disagreedate');
function match_disagreedate ($stack = array()) {
	$matchrule = "disagreedate"; $result = $this->construct($matchrule, $matchrule, null);
	$_85 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_85 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_85 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_81 = $result;
			$pos_81 = $this->pos;
			$_80 = NULL;
			do {
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
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_80 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_80 = FALSE; break; }
				$_80 = TRUE; break;
			}
			while(0);
			if( $_80 === FALSE) {
				$result = $res_81;
				$this->pos = $pos_81;
				unset( $res_81 );
				unset( $pos_81 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_85 = FALSE; break; }
		$matcher = 'match_'.'disagreedatefragment'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_85 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_85 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_85 = FALSE; break; }
		$_85 = TRUE; break;
	}
	while(0);
	if( $_85 === TRUE ) { return $this->finalise($result); }
	if( $_85 === FALSE) { return FALSE; }
}


/* uncertaindate:  "[" SPACE ((value:simpledaterange | value:simpledate) SPACE ";" SPACE)+ (value:simpledaterange | value:simpledate) SPACE "]"  */
protected $match_uncertaindate_typestack = array('uncertaindate');
function match_uncertaindate ($stack = array()) {
	$matchrule = "uncertaindate"; $result = $this->construct($matchrule, $matchrule, null);
	$_110 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_110 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_110 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_100 = $result;
			$pos_100 = $this->pos;
			$_99 = NULL;
			do {
				$_94 = NULL;
				do {
					$_92 = NULL;
					do {
						$res_89 = $result;
						$pos_89 = $this->pos;
						$matcher = 'match_'.'simpledaterange'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "value" );
							$_92 = TRUE; break;
						}
						$result = $res_89;
						$this->pos = $pos_89;
						$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "value" );
							$_92 = TRUE; break;
						}
						$result = $res_89;
						$this->pos = $pos_89;
						$_92 = FALSE; break;
					}
					while(0);
					if( $_92 === FALSE) { $_94 = FALSE; break; }
					$_94 = TRUE; break;
				}
				while(0);
				if( $_94 === FALSE) { $_99 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_99 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_99 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_99 = FALSE; break; }
				$_99 = TRUE; break;
			}
			while(0);
			if( $_99 === FALSE) {
				$result = $res_100;
				$this->pos = $pos_100;
				unset( $res_100 );
				unset( $pos_100 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_110 = FALSE; break; }
		$_106 = NULL;
		do {
			$_104 = NULL;
			do {
				$res_101 = $result;
				$pos_101 = $this->pos;
				$matcher = 'match_'.'simpledaterange'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_104 = TRUE; break;
				}
				$result = $res_101;
				$this->pos = $pos_101;
				$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_104 = TRUE; break;
				}
				$result = $res_101;
				$this->pos = $pos_101;
				$_104 = FALSE; break;
			}
			while(0);
			if( $_104 === FALSE) { $_106 = FALSE; break; }
			$_106 = TRUE; break;
		}
		while(0);
		if( $_106 === FALSE) { $_110 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_110 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_110 = FALSE; break; }
		$_110 = TRUE; break;
	}
	while(0);
	if( $_110 === TRUE ) { return $this->finalise($result); }
	if( $_110 === FALSE) { return FALSE; }
}


/* simpledate: value:year SPACE ( value:yearsuffix )? */
protected $match_simpledate_typestack = array('simpledate');
function match_simpledate ($stack = array()) {
	$matchrule = "simpledate"; $result = $this->construct($matchrule, $matchrule, null);
	$_117 = NULL;
	do {
		$matcher = 'match_'.'year'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_117 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_117 = FALSE; break; }
		$res_116 = $result;
		$pos_116 = $this->pos;
		$_115 = NULL;
		do {
			$matcher = 'match_'.'yearsuffix'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_115 = FALSE; break; }
			$_115 = TRUE; break;
		}
		while(0);
		if( $_115 === FALSE) {
			$result = $res_116;
			$this->pos = $pos_116;
			unset( $res_116 );
			unset( $pos_116 );
		}
		$_117 = TRUE; break;
	}
	while(0);
	if( $_117 === TRUE ) { return $this->finalise($result); }
	if( $_117 === FALSE) { return FALSE; }
}


/* undatedfact: value:uncertainlist | value:uncertainrange | value:disagreelist |  value:string  */
protected $match_undatedfact_typestack = array('undatedfact');
function match_undatedfact ($stack = array()) {
	$matchrule = "undatedfact"; $result = $this->construct($matchrule, $matchrule, null);
	$_130 = NULL;
	do {
		$res_119 = $result;
		$pos_119 = $this->pos;
		$matcher = 'match_'.'uncertainlist'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_130 = TRUE; break;
		}
		$result = $res_119;
		$this->pos = $pos_119;
		$_128 = NULL;
		do {
			$res_121 = $result;
			$pos_121 = $this->pos;
			$matcher = 'match_'.'uncertainrange'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_128 = TRUE; break;
			}
			$result = $res_121;
			$this->pos = $pos_121;
			$_126 = NULL;
			do {
				$res_123 = $result;
				$pos_123 = $this->pos;
				$matcher = 'match_'.'disagreelist'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_126 = TRUE; break;
				}
				$result = $res_123;
				$this->pos = $pos_123;
				$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_126 = TRUE; break;
				}
				$result = $res_123;
				$this->pos = $pos_123;
				$_126 = FALSE; break;
			}
			while(0);
			if( $_126 === TRUE ) { $_128 = TRUE; break; }
			$result = $res_121;
			$this->pos = $pos_121;
			$_128 = FALSE; break;
		}
		while(0);
		if( $_128 === TRUE ) { $_130 = TRUE; break; }
		$result = $res_119;
		$this->pos = $pos_119;
		$_130 = FALSE; break;
	}
	while(0);
	if( $_130 === TRUE ) { return $this->finalise($result); }
	if( $_130 === FALSE) { return FALSE; }
}


/* uncertainlist: "[" SPACE  (value:string SPACE ";" SPACE)+ value:string SPACE "]" */
protected $match_uncertainlist_typestack = array('uncertainlist');
function match_uncertainlist ($stack = array()) {
	$matchrule = "uncertainlist"; $result = $this->construct($matchrule, $matchrule, null);
	$_143 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_143 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_143 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_139 = $result;
			$pos_139 = $this->pos;
			$_138 = NULL;
			do {
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
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_138 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_138 = FALSE; break; }
				$_138 = TRUE; break;
			}
			while(0);
			if( $_138 === FALSE) {
				$result = $res_139;
				$this->pos = $pos_139;
				unset( $res_139 );
				unset( $pos_139 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_143 = FALSE; break; }
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_143 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_143 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_143 = FALSE; break; }
		$_143 = TRUE; break;
	}
	while(0);
	if( $_143 === TRUE ) { return $this->finalise($result); }
	if( $_143 === FALSE) { return FALSE; }
}


/* uncertainrange: "[" SPACE  (value:nodashstring SPACE "-" SPACE)+ value:nodashstring SPACE "]" */
protected $match_uncertainrange_typestack = array('uncertainrange');
function match_uncertainrange ($stack = array()) {
	$matchrule = "uncertainrange"; $result = $this->construct($matchrule, $matchrule, null);
	$_156 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_156 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_156 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_152 = $result;
			$pos_152 = $this->pos;
			$_151 = NULL;
			do {
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
				if (substr($this->string,$this->pos,1) == '-') {
					$this->pos += 1;
					$result["text"] .= '-';
				}
				else { $_151 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_151 = FALSE; break; }
				$_151 = TRUE; break;
			}
			while(0);
			if( $_151 === FALSE) {
				$result = $res_152;
				$this->pos = $pos_152;
				unset( $res_152 );
				unset( $pos_152 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_156 = FALSE; break; }
		$matcher = 'match_'.'nodashstring'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_156 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_156 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_156 = FALSE; break; }
		$_156 = TRUE; break;
	}
	while(0);
	if( $_156 === TRUE ) { return $this->finalise($result); }
	if( $_156 === FALSE) { return FALSE; }
}


/* disagreelistfragment: value:uncertainrange | value:string */
protected $match_disagreelistfragment_typestack = array('disagreelistfragment');
function match_disagreelistfragment ($stack = array()) {
	$matchrule = "disagreelistfragment"; $result = $this->construct($matchrule, $matchrule, null);
	$_161 = NULL;
	do {
		$res_158 = $result;
		$pos_158 = $this->pos;
		$matcher = 'match_'.'uncertainrange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_161 = TRUE; break;
		}
		$result = $res_158;
		$this->pos = $pos_158;
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_161 = TRUE; break;
		}
		$result = $res_158;
		$this->pos = $pos_158;
		$_161 = FALSE; break;
	}
	while(0);
	if( $_161 === TRUE ) { return $this->finalise($result); }
	if( $_161 === FALSE) { return FALSE; }
}


/* disagreelist: "{" SPACE  (value:disagreelistfragment SPACE ";" SPACE)+ value:disagreelistfragment SPACE "}" */
protected $match_disagreelist_typestack = array('disagreelist');
function match_disagreelist ($stack = array()) {
	$matchrule = "disagreelist"; $result = $this->construct($matchrule, $matchrule, null);
	$_174 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_174 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_174 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_170 = $result;
			$pos_170 = $this->pos;
			$_169 = NULL;
			do {
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
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_169 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_169 = FALSE; break; }
				$_169 = TRUE; break;
			}
			while(0);
			if( $_169 === FALSE) {
				$result = $res_170;
				$this->pos = $pos_170;
				unset( $res_170 );
				unset( $pos_170 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_174 = FALSE; break; }
		$matcher = 'match_'.'disagreelistfragment'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_174 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_174 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_174 = FALSE; break; }
		$_174 = TRUE; break;
	}
	while(0);
	if( $_174 === TRUE ) { return $this->finalise($result); }
	if( $_174 === FALSE) { return FALSE; }
}


/* string: word (SPACE word)* */
protected $match_string_typestack = array('string');
function match_string ($stack = array()) {
	$matchrule = "string"; $result = $this->construct($matchrule, $matchrule, null);
	$_181 = NULL;
	do {
		$matcher = 'match_'.'word'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_181 = FALSE; break; }
		while (true) {
			$res_180 = $result;
			$pos_180 = $this->pos;
			$_179 = NULL;
			do {
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_179 = FALSE; break; }
				$matcher = 'match_'.'word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_179 = FALSE; break; }
				$_179 = TRUE; break;
			}
			while(0);
			if( $_179 === FALSE) {
				$result = $res_180;
				$this->pos = $pos_180;
				unset( $res_180 );
				unset( $pos_180 );
				break;
			}
		}
		$_181 = TRUE; break;
	}
	while(0);
	if( $_181 === TRUE ) { return $this->finalise($result); }
	if( $_181 === FALSE) { return FALSE; }
}


/* nodashstring: nodashword (SPACE nodashword)* */
protected $match_nodashstring_typestack = array('nodashstring');
function match_nodashstring ($stack = array()) {
	$matchrule = "nodashstring"; $result = $this->construct($matchrule, $matchrule, null);
	$_188 = NULL;
	do {
		$matcher = 'match_'.'nodashword'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_188 = FALSE; break; }
		while (true) {
			$res_187 = $result;
			$pos_187 = $this->pos;
			$_186 = NULL;
			do {
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_186 = FALSE; break; }
				$matcher = 'match_'.'nodashword'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_186 = FALSE; break; }
				$_186 = TRUE; break;
			}
			while(0);
			if( $_186 === FALSE) {
				$result = $res_187;
				$this->pos = $pos_187;
				unset( $res_187 );
				unset( $pos_187 );
				break;
			}
		}
		$_188 = TRUE; break;
	}
	while(0);
	if( $_188 === TRUE ) { return $this->finalise($result); }
	if( $_188 === FALSE) { return FALSE; }
}


/* SPACE: " "* */
protected $match_SPACE_typestack = array('SPACE');
function match_SPACE ($stack = array()) {
	$matchrule = "SPACE"; $result = $this->construct($matchrule, $matchrule, null);
	while (true) {
		$res_190 = $result;
		$pos_190 = $this->pos;
		if (substr($this->string,$this->pos,1) == ' ') {
			$this->pos += 1;
			$result["text"] .= ' ';
		}
		else {
			$result = $res_190;
			$this->pos = $pos_190;
			unset( $res_190 );
			unset( $pos_190 );
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
	$_225 = NULL;
	do {
		$res_194 = $result;
		$pos_194 = $this->pos;
		if (( $subres = $this->literal( 'bce' ) ) !== FALSE) {
			$result["text"] .= $subres;
			$_225 = TRUE; break;
		}
		$result = $res_194;
		$this->pos = $pos_194;
		$_223 = NULL;
		do {
			$res_196 = $result;
			$pos_196 = $this->pos;
			if (( $subres = $this->literal( 'ce' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_223 = TRUE; break;
			}
			$result = $res_196;
			$this->pos = $pos_196;
			$_221 = NULL;
			do {
				$res_198 = $result;
				$pos_198 = $this->pos;
				if (( $subres = $this->literal( 'bc' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_221 = TRUE; break;
				}
				$result = $res_198;
				$this->pos = $pos_198;
				$_219 = NULL;
				do {
					$res_200 = $result;
					$pos_200 = $this->pos;
					if (( $subres = $this->literal( 'BCE' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_219 = TRUE; break;
					}
					$result = $res_200;
					$this->pos = $pos_200;
					$_217 = NULL;
					do {
						$res_202 = $result;
						$pos_202 = $this->pos;
						if (( $subres = $this->literal( 'CE' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_217 = TRUE; break;
						}
						$result = $res_202;
						$this->pos = $pos_202;
						$_215 = NULL;
						do {
							$res_204 = $result;
							$pos_204 = $this->pos;
							if (( $subres = $this->literal( 'BC' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_215 = TRUE; break;
							}
							$result = $res_204;
							$this->pos = $pos_204;
							$_213 = NULL;
							do {
								$res_206 = $result;
								$pos_206 = $this->pos;
								if (( $subres = $this->literal( 'Bce' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_213 = TRUE; break;
								}
								$result = $res_206;
								$this->pos = $pos_206;
								$_211 = NULL;
								do {
									$res_208 = $result;
									$pos_208 = $this->pos;
									if (( $subres = $this->literal( 'Ce' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_211 = TRUE; break;
									}
									$result = $res_208;
									$this->pos = $pos_208;
									if (( $subres = $this->literal( 'Bc' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_211 = TRUE; break;
									}
									$result = $res_208;
									$this->pos = $pos_208;
									$_211 = FALSE; break;
								}
								while(0);
								if( $_211 === TRUE ) { $_213 = TRUE; break; }
								$result = $res_206;
								$this->pos = $pos_206;
								$_213 = FALSE; break;
							}
							while(0);
							if( $_213 === TRUE ) { $_215 = TRUE; break; }
							$result = $res_204;
							$this->pos = $pos_204;
							$_215 = FALSE; break;
						}
						while(0);
						if( $_215 === TRUE ) { $_217 = TRUE; break; }
						$result = $res_202;
						$this->pos = $pos_202;
						$_217 = FALSE; break;
					}
					while(0);
					if( $_217 === TRUE ) { $_219 = TRUE; break; }
					$result = $res_200;
					$this->pos = $pos_200;
					$_219 = FALSE; break;
				}
				while(0);
				if( $_219 === TRUE ) { $_221 = TRUE; break; }
				$result = $res_198;
				$this->pos = $pos_198;
				$_221 = FALSE; break;
			}
			while(0);
			if( $_221 === TRUE ) { $_223 = TRUE; break; }
			$result = $res_196;
			$this->pos = $pos_196;
			$_223 = FALSE; break;
		}
		while(0);
		if( $_223 === TRUE ) { $_225 = TRUE; break; }
		$result = $res_194;
		$this->pos = $pos_194;
		$_225 = FALSE; break;
	}
	while(0);
	if( $_225 === TRUE ) { return $this->finalise($result); }
	if( $_225 === FALSE) { return FALSE; }
}




}