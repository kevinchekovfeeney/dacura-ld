<?php

require_once '/autoloader.php';
use hafriedlander\Peg\Parser;

/**
 * This parser parses Seshat data
 * NOTE: The uncertainfactstatement and disagreefactstatement definitions do not work.
 * @author Odhran Gavin
 */
class seshatParsing extends Parser\Basic {

/* factcontainer: value:factstatement | value:disagreefactstatement | value:uncertainfactstatement */
protected $match_factcontainer_typestack = array('factcontainer');
function match_factcontainer ($stack = array()) {
	$matchrule = "factcontainer"; $result = $this->construct($matchrule, $matchrule, null);
	$_7 = NULL;
	do {
		$res_0 = $result;
		$pos_0 = $this->pos;
		$matcher = 'match_'.'factstatement'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_7 = TRUE; break;
		}
		$result = $res_0;
		$this->pos = $pos_0;
		$_5 = NULL;
		do {
			$res_2 = $result;
			$pos_2 = $this->pos;
			$matcher = 'match_'.'disagreefactstatement'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_5 = TRUE; break;
			}
			$result = $res_2;
			$this->pos = $pos_2;
			$matcher = 'match_'.'uncertainfactstatement'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
				$_5 = TRUE; break;
			}
			$result = $res_2;
			$this->pos = $pos_2;
			$_5 = FALSE; break;
		}
		while(0);
		if( $_5 === TRUE ) { $_7 = TRUE; break; }
		$result = $res_0;
		$this->pos = $pos_0;
		$_7 = FALSE; break;
	}
	while(0);
	if( $_7 === TRUE ) { return $this->finalise($result); }
	if( $_7 === FALSE) { return FALSE; }
}


/* uncertainfactstatement: "[" ((value:fact ";" SPACE)* value:fact) "]" */
protected $match_uncertainfactstatement_typestack = array('uncertainfactstatement');
function match_uncertainfactstatement ($stack = array()) {
	$matchrule = "uncertainfactstatement"; $result = $this->construct($matchrule, $matchrule, null);
	$_19 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_19 = FALSE; break; }
		$_16 = NULL;
		do {
			while (true) {
				$res_14 = $result;
				$pos_14 = $this->pos;
				$_13 = NULL;
				do {
					$matcher = 'match_'.'fact'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "value" );
					}
					else { $_13 = FALSE; break; }
					if (substr($this->string,$this->pos,1) == ';') {
						$this->pos += 1;
						$result["text"] .= ';';
					}
					else { $_13 = FALSE; break; }
					$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_13 = FALSE; break; }
					$_13 = TRUE; break;
				}
				while(0);
				if( $_13 === FALSE) {
					$result = $res_14;
					$this->pos = $pos_14;
					unset( $res_14 );
					unset( $pos_14 );
					break;
				}
			}
			$matcher = 'match_'.'fact'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_16 = FALSE; break; }
			$_16 = TRUE; break;
		}
		while(0);
		if( $_16 === FALSE) { $_19 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_19 = FALSE; break; }
		$_19 = TRUE; break;
	}
	while(0);
	if( $_19 === TRUE ) { return $this->finalise($result); }
	if( $_19 === FALSE) { return FALSE; }
}


/* disagreefactstatement: "{" ((value:fact ";" SPACE)+ value:fact) "}" */
protected $match_disagreefactstatement_typestack = array('disagreefactstatement');
function match_disagreefactstatement ($stack = array()) {
	$matchrule = "disagreefactstatement"; $result = $this->construct($matchrule, $matchrule, null);
	$_31 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_31 = FALSE; break; }
		$_28 = NULL;
		do {
			$count = 0;
			while (true) {
				$res_26 = $result;
				$pos_26 = $this->pos;
				$_25 = NULL;
				do {
					$matcher = 'match_'.'fact'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "value" );
					}
					else { $_25 = FALSE; break; }
					if (substr($this->string,$this->pos,1) == ';') {
						$this->pos += 1;
						$result["text"] .= ';';
					}
					else { $_25 = FALSE; break; }
					$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_25 = FALSE; break; }
					$_25 = TRUE; break;
				}
				while(0);
				if( $_25 === FALSE) {
					$result = $res_26;
					$this->pos = $pos_26;
					unset( $res_26 );
					unset( $pos_26 );
					break;
				}
				$count++;
			}
			if ($count >= 1) {  }
			else { $_28 = FALSE; break; }
			$matcher = 'match_'.'fact'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "value" );
			}
			else { $_28 = FALSE; break; }
			$_28 = TRUE; break;
		}
		while(0);
		if( $_28 === FALSE) { $_31 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_31 = FALSE; break; }
		$_31 = TRUE; break;
	}
	while(0);
	if( $_31 === TRUE ) { return $this->finalise($result); }
	if( $_31 === FALSE) { return FALSE; }
}


/* factstatement: ((value:fact ";" SPACE)* value:fact) */
protected $match_factstatement_typestack = array('factstatement');
function match_factstatement ($stack = array()) {
	$matchrule = "factstatement"; $result = $this->construct($matchrule, $matchrule, null);
	$_39 = NULL;
	do {
		while (true) {
			$res_37 = $result;
			$pos_37 = $this->pos;
			$_36 = NULL;
			do {
				$matcher = 'match_'.'fact'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_36 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_36 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_36 = FALSE; break; }
				$_36 = TRUE; break;
			}
			while(0);
			if( $_36 === FALSE) {
				$result = $res_37;
				$this->pos = $pos_37;
				unset( $res_37 );
				unset( $pos_37 );
				break;
			}
		}
		$matcher = 'match_'.'fact'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_39 = FALSE; break; }
		$_39 = TRUE; break;
	}
	while(0);
	if( $_39 === TRUE ) { return $this->finalise($result); }
	if( $_39 === FALSE) { return FALSE; }
}


/* fact: value:basefact | value:uncertainbasefact */
protected $match_fact_typestack = array('fact');
function match_fact ($stack = array()) {
	$matchrule = "fact"; $result = $this->construct($matchrule, $matchrule, null);
	$_44 = NULL;
	do {
		$res_41 = $result;
		$pos_41 = $this->pos;
		$matcher = 'match_'.'basefact'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_44 = TRUE; break;
		}
		$result = $res_41;
		$this->pos = $pos_41;
		$matcher = 'match_'.'uncertainbasefact'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_44 = TRUE; break;
		}
		$result = $res_41;
		$this->pos = $pos_41;
		$_44 = FALSE; break;
	}
	while(0);
	if( $_44 === TRUE ) { return $this->finalise($result); }
	if( $_44 === FALSE) { return FALSE; }
}


/* uncertainbasefact: "[" (value:keyvalue | value:singleton) "]" */
protected $match_uncertainbasefact_typestack = array('uncertainbasefact');
function match_uncertainbasefact ($stack = array()) {
	$matchrule = "uncertainbasefact"; $result = $this->construct($matchrule, $matchrule, null);
	$_55 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_55 = FALSE; break; }
		$_52 = NULL;
		do {
			$_50 = NULL;
			do {
				$res_47 = $result;
				$pos_47 = $this->pos;
				$matcher = 'match_'.'keyvalue'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_50 = TRUE; break;
				}
				$result = $res_47;
				$this->pos = $pos_47;
				$matcher = 'match_'.'singleton'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_50 = TRUE; break;
				}
				$result = $res_47;
				$this->pos = $pos_47;
				$_50 = FALSE; break;
			}
			while(0);
			if( $_50 === FALSE) { $_52 = FALSE; break; }
			$_52 = TRUE; break;
		}
		while(0);
		if( $_52 === FALSE) { $_55 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_55 = FALSE; break; }
		$_55 = TRUE; break;
	}
	while(0);
	if( $_55 === TRUE ) { return $this->finalise($result); }
	if( $_55 === FALSE) { return FALSE; }
}


/* basefact: value:keyvalue | value:singleton */
protected $match_basefact_typestack = array('basefact');
function match_basefact ($stack = array()) {
	$matchrule = "basefact"; $result = $this->construct($matchrule, $matchrule, null);
	$_60 = NULL;
	do {
		$res_57 = $result;
		$pos_57 = $this->pos;
		$matcher = 'match_'.'keyvalue'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_60 = TRUE; break;
		}
		$result = $res_57;
		$this->pos = $pos_57;
		$matcher = 'match_'.'singleton'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
			$_60 = TRUE; break;
		}
		$result = $res_57;
		$this->pos = $pos_57;
		$_60 = FALSE; break;
	}
	while(0);
	if( $_60 === TRUE ) { return $this->finalise($result); }
	if( $_60 === FALSE) { return FALSE; }
}


/* keyvalue: value:statement SPACE ":" SPACE value:statement */
protected $match_keyvalue_typestack = array('keyvalue');
function match_keyvalue ($stack = array()) {
	$matchrule = "keyvalue"; $result = $this->construct($matchrule, $matchrule, null);
	$_67 = NULL;
	do {
		$matcher = 'match_'.'statement'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_67 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_67 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ':') {
			$this->pos += 1;
			$result["text"] .= ':';
		}
		else { $_67 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_67 = FALSE; break; }
		$matcher = 'match_'.'statement'; $key = $matcher; $pos = $this->pos;
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


/* singleton: value:statement */
protected $match_singleton_typestack = array('singleton');
function match_singleton ($stack = array()) {
	$matchrule = "singleton"; $result = $this->construct($matchrule, $matchrule, null);
	$matcher = 'match_'.'statement'; $key = $matcher; $pos = $this->pos;
	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
	if ($subres !== FALSE) {
		$this->store( $result, $subres, "value" );
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* statement: (value:disagreement|value:uncertainty|value:range|value:string SPACE)+ */
protected $match_statement_typestack = array('statement');
function match_statement ($stack = array()) {
	$matchrule = "statement"; $result = $this->construct($matchrule, $matchrule, null);
	$count = 0;
	while (true) {
		$res_87 = $result;
		$pos_87 = $this->pos;
		$_86 = NULL;
		do {
			$_84 = NULL;
			do {
				$res_70 = $result;
				$pos_70 = $this->pos;
				$matcher = 'match_'.'disagreement'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
					$_84 = TRUE; break;
				}
				$result = $res_70;
				$this->pos = $pos_70;
				$_82 = NULL;
				do {
					$res_72 = $result;
					$pos_72 = $this->pos;
					$matcher = 'match_'.'uncertainty'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "value" );
						$_82 = TRUE; break;
					}
					$result = $res_72;
					$this->pos = $pos_72;
					$_80 = NULL;
					do {
						$res_74 = $result;
						$pos_74 = $this->pos;
						$matcher = 'match_'.'range'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "value" );
							$_80 = TRUE; break;
						}
						$result = $res_74;
						$this->pos = $pos_74;
						$_78 = NULL;
						do {
							$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "value" );
							}
							else { $_78 = FALSE; break; }
							$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
							}
							else { $_78 = FALSE; break; }
							$_78 = TRUE; break;
						}
						while(0);
						if( $_78 === TRUE ) { $_80 = TRUE; break; }
						$result = $res_74;
						$this->pos = $pos_74;
						$_80 = FALSE; break;
					}
					while(0);
					if( $_80 === TRUE ) { $_82 = TRUE; break; }
					$result = $res_72;
					$this->pos = $pos_72;
					$_82 = FALSE; break;
				}
				while(0);
				if( $_82 === TRUE ) { $_84 = TRUE; break; }
				$result = $res_70;
				$this->pos = $pos_70;
				$_84 = FALSE; break;
			}
			while(0);
			if( $_84 === FALSE) { $_86 = FALSE; break; }
			$_86 = TRUE; break;
		}
		while(0);
		if( $_86 === FALSE) {
			$result = $res_87;
			$this->pos = $pos_87;
			unset( $res_87 );
			unset( $pos_87 );
			break;
		}
		$count++;
	}
	if ($count >= 1) { return $this->finalise($result); }
	else { return FALSE; }
}


/* disagreement: "{" (value:statement ";" SPACE)+ value:statement "}" */
protected $match_disagreement_typestack = array('disagreement');
function match_disagreement ($stack = array()) {
	$matchrule = "disagreement"; $result = $this->construct($matchrule, $matchrule, null);
	$_96 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_96 = FALSE; break; }
		$count = 0;
		while (true) {
			$res_93 = $result;
			$pos_93 = $this->pos;
			$_92 = NULL;
			do {
				$matcher = 'match_'.'statement'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_92 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_92 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_92 = FALSE; break; }
				$_92 = TRUE; break;
			}
			while(0);
			if( $_92 === FALSE) {
				$result = $res_93;
				$this->pos = $pos_93;
				unset( $res_93 );
				unset( $pos_93 );
				break;
			}
			$count++;
		}
		if ($count >= 1) {  }
		else { $_96 = FALSE; break; }
		$matcher = 'match_'.'statement'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_96 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_96 = FALSE; break; }
		$_96 = TRUE; break;
	}
	while(0);
	if( $_96 === TRUE ) { return $this->finalise($result); }
	if( $_96 === FALSE) { return FALSE; }
}


/* uncertainty: "[" value:string SPACE (";" SPACE value:string)* "]" */
protected $match_uncertainty_typestack = array('uncertainty');
function match_uncertainty ($stack = array()) {
	$matchrule = "uncertainty"; $result = $this->construct($matchrule, $matchrule, null);
	$_107 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_107 = FALSE; break; }
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_107 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_107 = FALSE; break; }
		while (true) {
			$res_105 = $result;
			$pos_105 = $this->pos;
			$_104 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_104 = FALSE; break; }
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_104 = FALSE; break; }
				$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "value" );
				}
				else { $_104 = FALSE; break; }
				$_104 = TRUE; break;
			}
			while(0);
			if( $_104 === FALSE) {
				$result = $res_105;
				$this->pos = $pos_105;
				unset( $res_105 );
				unset( $pos_105 );
				break;
			}
		}
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_107 = FALSE; break; }
		$_107 = TRUE; break;
	}
	while(0);
	if( $_107 === TRUE ) { return $this->finalise($result); }
	if( $_107 === FALSE) { return FALSE; }
}


/* range: value:string SPACE "-" SPACE value:string */
protected $match_range_typestack = array('range');
function match_range ($stack = array()) {
	$matchrule = "range"; $result = $this->construct($matchrule, $matchrule, null);
	$_114 = NULL;
	do {
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_114 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_114 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '-') {
			$this->pos += 1;
			$result["text"] .= '-';
		}
		else { $_114 = FALSE; break; }
		$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_114 = FALSE; break; }
		$matcher = 'match_'.'string'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_114 = FALSE; break; }
		$_114 = TRUE; break;
	}
	while(0);
	if( $_114 === TRUE ) { return $this->finalise($result); }
	if( $_114 === FALSE) { return FALSE; }
}


/* string: word (SPACE word)* */
protected $match_string_typestack = array('string');
function match_string ($stack = array()) {
	$matchrule = "string"; $result = $this->construct($matchrule, $matchrule, null);
	$_121 = NULL;
	do {
		$matcher = 'match_'.'word'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_121 = FALSE; break; }
		while (true) {
			$res_120 = $result;
			$pos_120 = $this->pos;
			$_119 = NULL;
			do {
				$matcher = 'match_'.'SPACE'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_119 = FALSE; break; }
				$matcher = 'match_'.'word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_119 = FALSE; break; }
				$_119 = TRUE; break;
			}
			while(0);
			if( $_119 === FALSE) {
				$result = $res_120;
				$this->pos = $pos_120;
				unset( $res_120 );
				unset( $pos_120 );
				break;
			}
		}
		$_121 = TRUE; break;
	}
	while(0);
	if( $_121 === TRUE ) { return $this->finalise($result); }
	if( $_121 === FALSE) { return FALSE; }
}


/* SPACE: " "* */
protected $match_SPACE_typestack = array('SPACE');
function match_SPACE ($stack = array()) {
	$matchrule = "SPACE"; $result = $this->construct($matchrule, $matchrule, null);
	while (true) {
		$res_123 = $result;
		$pos_123 = $this->pos;
		if (substr($this->string,$this->pos,1) == ' ') {
			$this->pos += 1;
			$result["text"] .= ' ';
		}
		else {
			$result = $res_123;
			$this->pos = $pos_123;
			unset( $res_123 );
			unset( $pos_123 );
			break;
		}
	}
	return $this->finalise($result);
}


/* word: (wordpart ((","|".") wordpart)+)|wordpart */
protected $match_word_typestack = array('word');
function match_word ($stack = array()) {
	$matchrule = "word"; $result = $this->construct($matchrule, $matchrule, null);
	$_139 = NULL;
	do {
		$res_124 = $result;
		$pos_124 = $this->pos;
		$_136 = NULL;
		do {
			$matcher = 'match_'.'wordpart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_136 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_135 = $result;
				$pos_135 = $this->pos;
				$_134 = NULL;
				do {
					$_131 = NULL;
					do {
						$_129 = NULL;
						do {
							$res_126 = $result;
							$pos_126 = $this->pos;
							if (substr($this->string,$this->pos,1) == ',') {
								$this->pos += 1;
								$result["text"] .= ',';
								$_129 = TRUE; break;
							}
							$result = $res_126;
							$this->pos = $pos_126;
							if (substr($this->string,$this->pos,1) == '.') {
								$this->pos += 1;
								$result["text"] .= '.';
								$_129 = TRUE; break;
							}
							$result = $res_126;
							$this->pos = $pos_126;
							$_129 = FALSE; break;
						}
						while(0);
						if( $_129 === FALSE) { $_131 = FALSE; break; }
						$_131 = TRUE; break;
					}
					while(0);
					if( $_131 === FALSE) { $_134 = FALSE; break; }
					$matcher = 'match_'.'wordpart'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_134 = FALSE; break; }
					$_134 = TRUE; break;
				}
				while(0);
				if( $_134 === FALSE) {
					$result = $res_135;
					$this->pos = $pos_135;
					unset( $res_135 );
					unset( $pos_135 );
					break;
				}
				$count++;
			}
			if ($count >= 1) {  }
			else { $_136 = FALSE; break; }
			$_136 = TRUE; break;
		}
		while(0);
		if( $_136 === TRUE ) { $_139 = TRUE; break; }
		$result = $res_124;
		$this->pos = $pos_124;
		$matcher = 'match_'.'wordpart'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres );
			$_139 = TRUE; break;
		}
		$result = $res_124;
		$this->pos = $pos_124;
		$_139 = FALSE; break;
	}
	while(0);
	if( $_139 === TRUE ) { return $this->finalise($result); }
	if( $_139 === FALSE) { return FALSE; }
}


/* wordpart: /[a-zA-Z0-9()<>]+/ */
protected $match_wordpart_typestack = array('wordpart');
function match_wordpart ($stack = array()) {
	$matchrule = "wordpart"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/[a-zA-Z0-9()<>]+/' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}




}