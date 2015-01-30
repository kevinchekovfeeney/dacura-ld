<?php

require_once 'autoloader.php';
use hafriedlander\Peg\Parser;

/**
 * This parser parses Seshat data
 * NOTE: The disagreefactstatement definition does not work.
 * @author Odhran Gavin
 */
class seshat2Parsing extends Parser\Basic {

/* fact: (value:factfragment SPACE ";" SPACE)* value:factfragment  */
protected $match_fact_typestack = array('fact');
function match_fact ($stack = array()) {
	$matchrule = "fact"; $result = $this->construct($matchrule, $matchrule, null);
	$_7 = NULL;
	do {
		while (true) {
			$res_5 = $result;
			$pos_5 = $this->pos;
			$_4 = NULL;
			do {
				$matcher = 'match_'.'factfragment'; $key = $matcher; $pos = $this->pos;
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
		$matcher = 'match_'.'factfragment'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_7 = FALSE; break; }
		$_7 = TRUE; break;
	}
	while(0);
	if( $_7 === TRUE ) { return $this->finalise($result); }
	if( $_7 === FALSE) { return FALSE; }
}


/* factfragment: value:datedfact | value:reversedatefact | value:undatedfact */
protected $match_factfragment_typestack = array('factfragment');
function match_factfragment ($stack = array()) {
	$matchrule = "factfragment"; $result = $this->construct($matchrule, $matchrule, null);
	$_16 = NULL;
	do {
		$res_9 = $result;
		$pos_9 = $this->pos;
		$matcher = 'match_'.'datedfact'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_16 = TRUE; break;
		}
		$result = $res_9;
		$this->pos = $pos_9;
		$_14 = NULL;
		do {
			$res_11 = $result;
			$pos_11 = $this->pos;
			$matcher = 'match_'.'reversedatefact'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_14 = TRUE; break;
			}
			$result = $res_11;
			$this->pos = $pos_11;
			$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_14 = TRUE; break;
			}
			$result = $res_11;
			$this->pos = $pos_11;
			$_14 = FALSE; break;
		}
		while(0);
		if( $_14 === TRUE ) { $_16 = TRUE; break; }
		$result = $res_9;
		$this->pos = $pos_9;
		$_16 = FALSE; break;
	}
	while(0);
	if( $_16 === TRUE ) { return $this->finalise($result); }
	if( $_16 === FALSE) { return FALSE; }
}


/* reversedatefact: value:datevalue SPACE ":" SPACE value:undatedfact */
protected $match_reversedatefact_typestack = array('reversedatefact');
function match_reversedatefact ($stack = array()) {
	$matchrule = "reversedatefact"; $result = $this->construct($matchrule, $matchrule, null);
	$_23 = NULL;
	do {
		$matcher = 'match_'.'datevalue'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_23 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_23 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ':') {
			$this->pos += 1;
			$result["text"] .= ':';
		}
		else { $_23 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_23 = FALSE; break; }
		$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_23 = FALSE; break; }
		$_23 = TRUE; break;
	}
	while(0);
	if( $_23 === TRUE ) { return $this->finalise($result); }
	if( $_23 === FALSE) { return FALSE; }
}


/* datedfact: value:undatedfact SPACE ":" SPACE value:datevalue  */
protected $match_datedfact_typestack = array('datedfact');
function match_datedfact ($stack = array()) {
	$matchrule = "datedfact"; $result = $this->construct($matchrule, $matchrule, null);
	$_30 = NULL;
	do {
		$matcher = 'match_'.'undatedfact'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_30 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_30 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ':') {
			$this->pos += 1;
			$result["text"] .= ':';
		}
		else { $_30 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_30 = FALSE; break; }
		$matcher = 'match_'.'datevalue'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_30 = FALSE; break; }
		$_30 = TRUE; break;
	}
	while(0);
	if( $_30 === TRUE ) { return $this->finalise($result); }
	if( $_30 === FALSE) { return FALSE; }
}


/* datevalue: value:daterange | value:singledate */
protected $match_datevalue_typestack = array('datevalue');
function match_datevalue ($stack = array()) {
	$matchrule = "datevalue"; $result = $this->construct($matchrule, $matchrule, null);
	$_35 = NULL;
	do {
		$res_32 = $result;
		$pos_32 = $this->pos;
		$matcher = 'match_'.'daterange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_35 = TRUE; break;
		}
		$result = $res_32;
		$this->pos = $pos_32;
		$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_35 = TRUE; break;
		}
		$result = $res_32;
		$this->pos = $pos_32;
		$_35 = FALSE; break;
	}
	while(0);
	if( $_35 === TRUE ) { return $this->finalise($result); }
	if( $_35 === FALSE) { return FALSE; }
}


/* daterange: value:singledate SPACE "-" SPACE value:singledate */
protected $match_daterange_typestack = array('daterange');
function match_daterange ($stack = array()) {
	$matchrule = "daterange"; $result = $this->construct($matchrule, $matchrule, null);
	$_42 = NULL;
	do {
		$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_42 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_42 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '-') {
			$this->pos += 1;
			$result["text"] .= '-';
		}
		else { $_42 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_42 = FALSE; break; }
		$matcher = 'match_'.'singledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_42 = FALSE; break; }
		$_42 = TRUE; break;
	}
	while(0);
	if( $_42 === TRUE ) { return $this->finalise($result); }
	if( $_42 === FALSE) { return FALSE; }
}


/* singledate: value:uncertaindate | value:disagreedate | value:simpledate */
protected $match_singledate_typestack = array('singledate');
function match_singledate ($stack = array()) {
	$matchrule = "singledate"; $result = $this->construct($matchrule, $matchrule, null);
	$_51 = NULL;
	do {
		$res_44 = $result;
		$pos_44 = $this->pos;
		$matcher = 'match_'.'uncertaindate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_51 = TRUE; break;
		}
		$result = $res_44;
		$this->pos = $pos_44;
		$_49 = NULL;
		do {
			$res_46 = $result;
			$pos_46 = $this->pos;
			$matcher = 'match_'.'disagreedate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_49 = TRUE; break;
			}
			$result = $res_46;
			$this->pos = $pos_46;
			$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_49 = TRUE; break;
			}
			$result = $res_46;
			$this->pos = $pos_46;
			$_49 = FALSE; break;
		}
		while(0);
		if( $_49 === TRUE ) { $_51 = TRUE; break; }
		$result = $res_44;
		$this->pos = $pos_44;
		$_51 = FALSE; break;
	}
	while(0);
	if( $_51 === TRUE ) { return $this->finalise($result); }
	if( $_51 === FALSE) { return FALSE; }
}


/* simpledaterange: value:simpledate SPACE "-" SPACE value:simpledate */
protected $match_simpledaterange_typestack = array('simpledaterange');
function match_simpledaterange ($stack = array()) {
	$matchrule = "simpledaterange"; $result = $this->construct($matchrule, $matchrule, null);
	$_58 = NULL;
	do {
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_58 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_58 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '-') {
			$this->pos += 1;
			$result["text"] .= '-';
		}
		else { $_58 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_58 = FALSE; break; }
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_58 = FALSE; break; }
		$_58 = TRUE; break;
	}
	while(0);
	if( $_58 === TRUE ) { return $this->finalise($result); }
	if( $_58 === FALSE) { return FALSE; }
}


/* disagreedatefragment: value:simpledaterange | value:simpledate */
protected $match_disagreedatefragment_typestack = array('disagreedatefragment');
function match_disagreedatefragment ($stack = array()) {
	$matchrule = "disagreedatefragment"; $result = $this->construct($matchrule, $matchrule, null);
	$_63 = NULL;
	do {
		$res_60 = $result;
		$pos_60 = $this->pos;
		$matcher = 'match_'.'simpledaterange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_63 = TRUE; break;
		}
		$result = $res_60;
		$this->pos = $pos_60;
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_63 = TRUE; break;
		}
		$result = $res_60;
		$this->pos = $pos_60;
		$_63 = FALSE; break;
	}
	while(0);
	if( $_63 === TRUE ) { return $this->finalise($result); }
	if( $_63 === FALSE) { return FALSE; }
}


/* disagreedate: "{" SPACE (value:disagreedatefragment SPACE ";" SPACE)+ value:disagreedatefragment SPACE "}" */
protected $match_disagreedate_typestack = array('disagreedate');
function match_disagreedate ($stack = array()) {
	$matchrule = "disagreedate"; $result = $this->construct($matchrule, $matchrule, null);
	$_76 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_76 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_76 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_72 = $result;
			$pos_72 = $this->pos;
			$_71 = NULL;
			do {
				$matcher = 'match_'.'disagreedatefragment'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_71 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_71 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_71 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_71 = FALSE; break; }
				$_71 = TRUE; break;
			}
			while(0);
			if( $_71 === FALSE) {
				$result = $res_72;
				$this->pos = $pos_72;
				unset( $res_72 );
				unset( $pos_72 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_76 = FALSE; break; }
		$matcher = 'match_'.'disagreedatefragment'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_76 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_76 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_76 = FALSE; break; }
		$_76 = TRUE; break;
	}
	while(0);
	if( $_76 === TRUE ) { return $this->finalise($result); }
	if( $_76 === FALSE) { return FALSE; }
}


/* uncertaindate:  "[" SPACE (value:simpledate SPACE ";" SPACE)+ value:simpledate SPACE "]" */
protected $match_uncertaindate_typestack = array('uncertaindate');
function match_uncertaindate ($stack = array()) {
	$matchrule = "uncertaindate"; $result = $this->construct($matchrule, $matchrule, null);
	$_89 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_89 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_89 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_85 = $result;
			$pos_85 = $this->pos;
			$_84 = NULL;
			do {
				$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_84 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_84 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_84 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_84 = FALSE; break; }
				$_84 = TRUE; break;
			}
			while(0);
			if( $_84 === FALSE) {
				$result = $res_85;
				$this->pos = $pos_85;
				unset( $res_85 );
				unset( $pos_85 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_89 = FALSE; break; }
		$matcher = 'match_'.'simpledate'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_89 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_89 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_89 = FALSE; break; }
		$_89 = TRUE; break;
	}
	while(0);
	if( $_89 === TRUE ) { return $this->finalise($result); }
	if( $_89 === FALSE) { return FALSE; }
}


/* simpledate: value:year SPACE ( value:yearsuffix )? */
protected $match_simpledate_typestack = array('simpledate');
function match_simpledate ($stack = array()) {
	$matchrule = "simpledate"; $result = $this->construct($matchrule, $matchrule, null);
	$_96 = NULL;
	do {
		$matcher = 'match_'.'year'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_96 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_96 = FALSE; break; }
		$res_95 = $result;
		$pos_95 = $this->pos;
		$_94 = NULL;
		do {
			$matcher = 'match_'.'yearsuffix'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_94 = FALSE; break; }
			$_94 = TRUE; break;
		}
		while(0);
		if( $_94 === FALSE) {
			$result = $res_95;
			$this->pos = $pos_95;
			unset( $res_95 );
			unset( $pos_95 );
		}
		$_96 = TRUE; break;
	}
	while(0);
	if( $_96 === TRUE ) { return $this->finalise($result); }
	if( $_96 === FALSE) { return FALSE; }
}


/* undatedfact: value:uncertainlist | value:uncertainrange | value:disagreelist |  value:string  */
protected $match_undatedfact_typestack = array('undatedfact');
function match_undatedfact ($stack = array()) {
	$matchrule = "undatedfact"; $result = $this->construct($matchrule, $matchrule, null);
	$_109 = NULL;
	do {
		$res_98 = $result;
		$pos_98 = $this->pos;
		$matcher = 'match_'.'uncertainlist'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_109 = TRUE; break;
		}
		$result = $res_98;
		$this->pos = $pos_98;
		$_107 = NULL;
		do {
			$res_100 = $result;
			$pos_100 = $this->pos;
			$matcher = 'match_'.'uncertainrange'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_107 = TRUE; break;
			}
			$result = $res_100;
			$this->pos = $pos_100;
			$_105 = NULL;
			do {
				$res_102 = $result;
				$pos_102 = $this->pos;
				$matcher = 'match_'.'disagreelist'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_105 = TRUE; break;
				}
				$result = $res_102;
				$this->pos = $pos_102;
				$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_105 = TRUE; break;
				}
				$result = $res_102;
				$this->pos = $pos_102;
				$_105 = FALSE; break;
			}
			while(0);
			if( $_105 === TRUE ) { $_107 = TRUE; break; }
			$result = $res_100;
			$this->pos = $pos_100;
			$_107 = FALSE; break;
		}
		while(0);
		if( $_107 === TRUE ) { $_109 = TRUE; break; }
		$result = $res_98;
		$this->pos = $pos_98;
		$_109 = FALSE; break;
	}
	while(0);
	if( $_109 === TRUE ) { return $this->finalise($result); }
	if( $_109 === FALSE) { return FALSE; }
}


/* uncertainlist: "[" SPACE  (value:string SPACE ";" SPACE)+ value:string SPACE "]" */
protected $match_uncertainlist_typestack = array('uncertainlist');
function match_uncertainlist ($stack = array()) {
	$matchrule = "uncertainlist"; $result = $this->construct($matchrule, $matchrule, null);
	$_122 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_122 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_122 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_118 = $result;
			$pos_118 = $this->pos;
			$_117 = NULL;
			do {
				$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_117 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_117 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_117 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_117 = FALSE; break; }
				$_117 = TRUE; break;
			}
			while(0);
			if( $_117 === FALSE) {
				$result = $res_118;
				$this->pos = $pos_118;
				unset( $res_118 );
				unset( $pos_118 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_122 = FALSE; break; }
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_122 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_122 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_122 = FALSE; break; }
		$_122 = TRUE; break;
	}
	while(0);
	if( $_122 === TRUE ) { return $this->finalise($result); }
	if( $_122 === FALSE) { return FALSE; }
}


/* uncertainrange: "[" SPACE  (value:nodashstring SPACE "-" SPACE)+ value:nodashstring SPACE "]" */
protected $match_uncertainrange_typestack = array('uncertainrange');
function match_uncertainrange ($stack = array()) {
	$matchrule = "uncertainrange"; $result = $this->construct($matchrule, $matchrule, null);
	$_135 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_135 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_135 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_131 = $result;
			$pos_131 = $this->pos;
			$_130 = NULL;
			do {
				$matcher = 'match_'.'nodashstring'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_130 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_130 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '-') {
					$this->pos += 1;
					$result["text"] .= '-';
				}
				else { $_130 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_130 = FALSE; break; }
				$_130 = TRUE; break;
			}
			while(0);
			if( $_130 === FALSE) {
				$result = $res_131;
				$this->pos = $pos_131;
				unset( $res_131 );
				unset( $pos_131 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_135 = FALSE; break; }
		$matcher = 'match_'.'nodashstring'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_135 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_135 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_135 = FALSE; break; }
		$_135 = TRUE; break;
	}
	while(0);
	if( $_135 === TRUE ) { return $this->finalise($result); }
	if( $_135 === FALSE) { return FALSE; }
}


/* disagreelistfragment: value:uncertainrange | value:string */
protected $match_disagreelistfragment_typestack = array('disagreelistfragment');
function match_disagreelistfragment ($stack = array()) {
	$matchrule = "disagreelistfragment"; $result = $this->construct($matchrule, $matchrule, null);
	$_140 = NULL;
	do {
		$res_137 = $result;
		$pos_137 = $this->pos;
		$matcher = 'match_'.'uncertainrange'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_140 = TRUE; break;
		}
		$result = $res_137;
		$this->pos = $pos_137;
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_140 = TRUE; break;
		}
		$result = $res_137;
		$this->pos = $pos_137;
		$_140 = FALSE; break;
	}
	while(0);
	if( $_140 === TRUE ) { return $this->finalise($result); }
	if( $_140 === FALSE) { return FALSE; }
}


/* disagreelist: "{" SPACE  (value:disagreelistfragment SPACE ";" SPACE)+ value:disagreelistfragment SPACE "}" */
protected $match_disagreelist_typestack = array('disagreelist');
function match_disagreelist ($stack = array()) {
	$matchrule = "disagreelist"; $result = $this->construct($matchrule, $matchrule, null);
	$_153 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_153 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_153 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_149 = $result;
			$pos_149 = $this->pos;
			$_148 = NULL;
			do {
				$matcher = 'match_'.'disagreelistfragment'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_148 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_148 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_148 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_148 = FALSE; break; }
				$_148 = TRUE; break;
			}
			while(0);
			if( $_148 === FALSE) {
				$result = $res_149;
				$this->pos = $pos_149;
				unset( $res_149 );
				unset( $pos_149 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_153 = FALSE; break; }
		$matcher = 'match_'.'disagreelistfragment'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_153 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_153 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_153 = FALSE; break; }
		$_153 = TRUE; break;
	}
	while(0);
	if( $_153 === TRUE ) { return $this->finalise($result); }
	if( $_153 === FALSE) { return FALSE; }
}


/* string: word (SPACE word)* */
protected $match_string_typestack = array('string');
function match_string ($stack = array()) {
	$matchrule = "string"; $result = $this->construct($matchrule, $matchrule, null);
	$_160 = NULL;
	do {
		$matcher = 'match_'.'word'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_160 = FALSE; break; }
		while (true) {
			$res_159 = $result;
			$pos_159 = $this->pos;
			$_158 = NULL;
			do {
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_158 = FALSE; break; }
				$matcher = 'match_'.'word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_158 = FALSE; break; }
				$_158 = TRUE; break;
			}
			while(0);
			if( $_158 === FALSE) {
				$result = $res_159;
				$this->pos = $pos_159;
				unset( $res_159 );
				unset( $pos_159 );
				break;
			}
		}
		$_160 = TRUE; break;
	}
	while(0);
	if( $_160 === TRUE ) { return $this->finalise($result); }
	if( $_160 === FALSE) { return FALSE; }
}


/* nodashstring: nodashword (SPACE nodashword)* */
protected $match_nodashstring_typestack = array('nodashstring');
function match_nodashstring ($stack = array()) {
	$matchrule = "nodashstring"; $result = $this->construct($matchrule, $matchrule, null);
	$_167 = NULL;
	do {
		$matcher = 'match_'.'nodashword'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_167 = FALSE; break; }
		while (true) {
			$res_166 = $result;
			$pos_166 = $this->pos;
			$_165 = NULL;
			do {
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_165 = FALSE; break; }
				$matcher = 'match_'.'nodashword'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_165 = FALSE; break; }
				$_165 = TRUE; break;
			}
			while(0);
			if( $_165 === FALSE) {
				$result = $res_166;
				$this->pos = $pos_166;
				unset( $res_166 );
				unset( $pos_166 );
				break;
			}
		}
		$_167 = TRUE; break;
	}
	while(0);
	if( $_167 === TRUE ) { return $this->finalise($result); }
	if( $_167 === FALSE) { return FALSE; }
}


/* SPACE: " "* */
protected $match_SPACE_typestack = array('SPACE');
function match_SPACE ($stack = array()) {
	$matchrule = "SPACE"; $result = $this->construct($matchrule, $matchrule, null);
	while (true) {
		$res_169 = $result;
		$pos_169 = $this->pos;
		if (substr($this->string,$this->pos,1) == ' ') {
			$this->pos += 1;
			$result["text"] .= ' ';
		}
		else {
			$result = $res_169;
			$this->pos = $pos_169;
			unset( $res_169 );
			unset( $pos_169 );
			break;
		}
	}
	return $this->finalise($result);
}


/* word: (wordpart ((","|".") wordpart)+)|wordpart */
protected $match_word_typestack = array('word');
function match_word ($stack = array()) {
	$matchrule = "word"; $result = $this->construct($matchrule, $matchrule, null);
	$_185 = NULL;
	do {
		$res_170 = $result;
		$pos_170 = $this->pos;
		$_182 = NULL;
		do {
			$matcher = 'match_'.'wordpart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_182 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_181 = $result;
				$pos_181 = $this->pos;
				$_180 = NULL;
				do {
					$_177 = NULL;
					do {
						$_175 = NULL;
						do {
							$res_172 = $result;
							$pos_172 = $this->pos;
							if (substr($this->string,$this->pos,1) == ',') {
								$this->pos += 1;
								$result["text"] .= ',';
								$_175 = TRUE; break;
							}
							$result = $res_172;
							$this->pos = $pos_172;
							if (substr($this->string,$this->pos,1) == '.') {
								$this->pos += 1;
								$result["text"] .= '.';
								$_175 = TRUE; break;
							}
							$result = $res_172;
							$this->pos = $pos_172;
							$_175 = FALSE; break;
						}
						while(0);
						if( $_175 === FALSE) { $_177 = FALSE; break; }
						$_177 = TRUE; break;
					}
					while(0);
					if( $_177 === FALSE) { $_180 = FALSE; break; }
					$matcher = 'match_'.'wordpart'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_180 = FALSE; break; }
					$_180 = TRUE; break;
				}
				while(0);
				if( $_180 === FALSE) {
					$result = $res_181;
					$this->pos = $pos_181;
					unset( $res_181 );
					unset( $pos_181 );
					break;
				}
				$count++;
			}
			if ($count >= 1) {  }
			else { $_182 = FALSE; break; }
			$_182 = TRUE; break;
		}
		while(0);
		if( $_182 === TRUE ) { $_185 = TRUE; break; }
		$result = $res_170;
		$this->pos = $pos_170;
		$matcher = 'match_'.'wordpart'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres );
			$_185 = TRUE; break;
		}
		$result = $res_170;
		$this->pos = $pos_170;
		$_185 = FALSE; break;
	}
	while(0);
	if( $_185 === TRUE ) { return $this->finalise($result); }
	if( $_185 === FALSE) { return FALSE; }
}


/* nodashword: (nodashwordpart ((","|".") nodashwordpart)+)|nodashwordpart */
protected $match_nodashword_typestack = array('nodashword');
function match_nodashword ($stack = array()) {
	$matchrule = "nodashword"; $result = $this->construct($matchrule, $matchrule, null);
	$_202 = NULL;
	do {
		$res_187 = $result;
		$pos_187 = $this->pos;
		$_199 = NULL;
		do {
			$matcher = 'match_'.'nodashwordpart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_199 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_198 = $result;
				$pos_198 = $this->pos;
				$_197 = NULL;
				do {
					$_194 = NULL;
					do {
						$_192 = NULL;
						do {
							$res_189 = $result;
							$pos_189 = $this->pos;
							if (substr($this->string,$this->pos,1) == ',') {
								$this->pos += 1;
								$result["text"] .= ',';
								$_192 = TRUE; break;
							}
							$result = $res_189;
							$this->pos = $pos_189;
							if (substr($this->string,$this->pos,1) == '.') {
								$this->pos += 1;
								$result["text"] .= '.';
								$_192 = TRUE; break;
							}
							$result = $res_189;
							$this->pos = $pos_189;
							$_192 = FALSE; break;
						}
						while(0);
						if( $_192 === FALSE) { $_194 = FALSE; break; }
						$_194 = TRUE; break;
					}
					while(0);
					if( $_194 === FALSE) { $_197 = FALSE; break; }
					$matcher = 'match_'.'nodashwordpart'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_197 = FALSE; break; }
					$_197 = TRUE; break;
				}
				while(0);
				if( $_197 === FALSE) {
					$result = $res_198;
					$this->pos = $pos_198;
					unset( $res_198 );
					unset( $pos_198 );
					break;
				}
				$count++;
			}
			if ($count >= 1) {  }
			else { $_199 = FALSE; break; }
			$_199 = TRUE; break;
		}
		while(0);
		if( $_199 === TRUE ) { $_202 = TRUE; break; }
		$result = $res_187;
		$this->pos = $pos_187;
		$matcher = 'match_'.'nodashwordpart'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres );
			$_202 = TRUE; break;
		}
		$result = $res_187;
		$this->pos = $pos_187;
		$_202 = FALSE; break;
	}
	while(0);
	if( $_202 === TRUE ) { return $this->finalise($result); }
	if( $_202 === FALSE) { return FALSE; }
}


/* nodashwordpart: /[^-\]\[\{\};:]+/ */
protected $match_nodashwordpart_typestack = array('nodashwordpart');
function match_nodashwordpart ($stack = array()) {
	$matchrule = "nodashwordpart"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/[^-\]\[\{\};:]+/' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* wordpart: /[^\]\[\{\};:]+/ */
protected $match_wordpart_typestack = array('wordpart');
function match_wordpart ($stack = array()) {
	$matchrule = "wordpart"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/[^\]\[\{\};:]+/' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* year: /[0-9]+/ */
protected $match_year_typestack = array('year');
function match_year ($stack = array()) {
	$matchrule = "year"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/[0-9]+/' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* yearsuffix: "bce" | "ce" | "bc" | "BCE" | "CE" | "BC" | "Bce" | "Ce" | "Bc" */
protected $match_yearsuffix_typestack = array('yearsuffix');
function match_yearsuffix ($stack = array()) {
	$matchrule = "yearsuffix"; $result = $this->construct($matchrule, $matchrule, null);
	$_238 = NULL;
	do {
		$res_207 = $result;
		$pos_207 = $this->pos;
		if (( $subres = $this->literal( 'bce' ) ) !== FALSE) {
			$result["text"] .= $subres;
			$_238 = TRUE; break;
		}
		$result = $res_207;
		$this->pos = $pos_207;
		$_236 = NULL;
		do {
			$res_209 = $result;
			$pos_209 = $this->pos;
			if (( $subres = $this->literal( 'ce' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_236 = TRUE; break;
			}
			$result = $res_209;
			$this->pos = $pos_209;
			$_234 = NULL;
			do {
				$res_211 = $result;
				$pos_211 = $this->pos;
				if (( $subres = $this->literal( 'bc' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_234 = TRUE; break;
				}
				$result = $res_211;
				$this->pos = $pos_211;
				$_232 = NULL;
				do {
					$res_213 = $result;
					$pos_213 = $this->pos;
					if (( $subres = $this->literal( 'BCE' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_232 = TRUE; break;
					}
					$result = $res_213;
					$this->pos = $pos_213;
					$_230 = NULL;
					do {
						$res_215 = $result;
						$pos_215 = $this->pos;
						if (( $subres = $this->literal( 'CE' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_230 = TRUE; break;
						}
						$result = $res_215;
						$this->pos = $pos_215;
						$_228 = NULL;
						do {
							$res_217 = $result;
							$pos_217 = $this->pos;
							if (( $subres = $this->literal( 'BC' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_228 = TRUE; break;
							}
							$result = $res_217;
							$this->pos = $pos_217;
							$_226 = NULL;
							do {
								$res_219 = $result;
								$pos_219 = $this->pos;
								if (( $subres = $this->literal( 'Bce' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_226 = TRUE; break;
								}
								$result = $res_219;
								$this->pos = $pos_219;
								$_224 = NULL;
								do {
									$res_221 = $result;
									$pos_221 = $this->pos;
									if (( $subres = $this->literal( 'Ce' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_224 = TRUE; break;
									}
									$result = $res_221;
									$this->pos = $pos_221;
									if (( $subres = $this->literal( 'Bc' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_224 = TRUE; break;
									}
									$result = $res_221;
									$this->pos = $pos_221;
									$_224 = FALSE; break;
								}
								while(0);
								if( $_224 === TRUE ) { $_226 = TRUE; break; }
								$result = $res_219;
								$this->pos = $pos_219;
								$_226 = FALSE; break;
							}
							while(0);
							if( $_226 === TRUE ) { $_228 = TRUE; break; }
							$result = $res_217;
							$this->pos = $pos_217;
							$_228 = FALSE; break;
						}
						while(0);
						if( $_228 === TRUE ) { $_230 = TRUE; break; }
						$result = $res_215;
						$this->pos = $pos_215;
						$_230 = FALSE; break;
					}
					while(0);
					if( $_230 === TRUE ) { $_232 = TRUE; break; }
					$result = $res_213;
					$this->pos = $pos_213;
					$_232 = FALSE; break;
				}
				while(0);
				if( $_232 === TRUE ) { $_234 = TRUE; break; }
				$result = $res_211;
				$this->pos = $pos_211;
				$_234 = FALSE; break;
			}
			while(0);
			if( $_234 === TRUE ) { $_236 = TRUE; break; }
			$result = $res_209;
			$this->pos = $pos_209;
			$_236 = FALSE; break;
		}
		while(0);
		if( $_236 === TRUE ) { $_238 = TRUE; break; }
		$result = $res_207;
		$this->pos = $pos_207;
		$_238 = FALSE; break;
	}
	while(0);
	if( $_238 === TRUE ) { return $this->finalise($result); }
	if( $_238 === FALSE) { return FALSE; }
}




}