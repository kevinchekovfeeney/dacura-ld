<?php
/**
 * Simple stub to enable interoperation with json ld library
 * @author Chekov
 * @license GPL V2
 */ 
require("phplib/libs/JsonLD/JsonLD.php");
require("phplib/libs/JsonLD/RdfConstants.php");
require("phplib/libs/JsonLD/JsonLdSerializable.php");
require("phplib/libs/JsonLD/QuadSerializerInterface.php");
require("phplib/libs/JsonLD/QuadParserInterface.php");
require("phplib/libs/JsonLD/Value.php");
require("phplib/libs/JsonLD/NodeInterface.php");
require("phplib/libs/JsonLD/DocumentFactoryInterface.php");
require("phplib/libs/JsonLD/DefaultDocumentFactory.php");
require("phplib/libs/JsonLD/DocumentInterface.php");
require("phplib/libs/JsonLD/GraphInterface.php");
require("phplib/libs/JsonLD/IRI/IRI.php");
require("phplib/libs/JsonLD/Document.php");
require("phplib/libs/JsonLD/Processor.php");
require("phplib/libs/JsonLD/Graph.php");
require("phplib/libs/JsonLD/Quad.php");
require("phplib/libs/JsonLD/NQuads.php");
require("phplib/libs/JsonLD/Node.php");
require("phplib/libs/JsonLD/TypedValue.php");
require("phplib/libs/JsonLD/Exception/JsonLdException.php");
require("phplib/libs/JsonLD/Exception/InvalidQuadException.php");
require("phplib/libs/JsonLD/LanguageTaggedString.php");

/**
 * Turns a dacura json array into json-ld
 * @param array $props dacura ld structure
 * @param boolean $ns if true prefix compression will be used
 * @param boolean $ismulti is this a multi-graph object
 * @param string $cwurl the url of the object in question
 * @return json ld object
 */
function toJSONLD($props, $ns = false, $ismulti, $cwurl){
	$jsonld = propsToJSONLD($props, $ismulti, $cwurl);
	if($jsonld){
		$compressed = ML\JsonLD\JsonLD::compact($jsonld->toJsonLd(), (object) $ns);
		return $compressed;
	}
	return false;
}

/**
 * Imports a json-ld structure into a dacura json structure
 * @param array $jsonld
 * @return boolean
 */
function fromJSONLD($jsonld){
	$jsonld = json_decode(json_encode($jsonld));
	return propsFromJSONLD($jsonld);
}

/**
 * Transforms dacura object into nquads
 * @param array $props dacura prop array
 * @param string $cwurl the object's url
 * @param boolean $ismulti if true it is a multi-graph object
 * @return string nquad serialisation
 */
function toNQuads($props, $cwurl, $ismulti = false){
	$jsonld = propsToJSONLD($props, $cwurl, $ismulti);
	$quads = ML\JsonLD\JsonLD::toRdf($jsonld->toJsonLd());
	$nquads = new ML\JsonLD\NQuads();
	$ser = $nquads->serialize($quads);
	return $ser;
}

/**
 * Imports from nquads into a dacura ld array
 * @param string $text the n-quad input string 
 * @return array dacura props ld array
 */
function fromNQuads($text){
	$nquads = new ML\JsonLD\NQuads();
	try {
		$quads = $nquads->parse($text);
	}
	catch(Exception $e){
		return false;
	}
	$document = ML\JsonLD\JsonLD::fromRdf($quads);
	return propsFromJSONLDDoc($document);
}

/**
 * Transforms the built-in linked data property array into a json ld structure
 * @param array $props an ld property array
 * @param array $cwurl url of the object being json-lded
 * @param boolean $ismulti - true if the object is spread across multiple graphs
 * @return array json-ld compliant array
 */
function propsToJSONLD($props, $cwurl, $ismulti = false){
	$jsonld = new ML\JsonLD\Document($cwurl);
	if($ismulti){
		foreach($props as $gid => $ldos){
			$ng = $jsonld->createGraph($gid);
			foreach($ldos as $nid => $ldo){
				$nn = $ng->createNode($nid, true);
				addLDOToJSONLDNode($nn, $ldo, $cwurl);
			}
		}
	}
	else {
		$dg = $jsonld->getGraph();
		foreach($props as $nid => $ldo){
			$nn = $dg->createNode($nid, true);
			addLDOToJSONLDNode($nn, $ldo, $cwurl);
		}
	}
	return $jsonld;
}

/**
 * Adds a dacura linked data object to a json ld node
 * 
 * Used internally in transformations.
 * @param array $node the json ld node
 * @param array $ldo the linked data object
 * @param string $cwurl the url of the object
 */
function addLDOToJSONLDNode(&$node, $ldo, $cwurl){
	foreach($ldo as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embedded()){
			$newnode = new ML\JsonLD\Node($node->getGraph());
			addLDOToJSONLDNode($newnode, $v, $cwurl);
			$node->setProperty($p, $newnode);		
		}
		elseif($pv->objectlist()){
			foreach($v as $obj){
				$newnode = new ML\JsonLD\Node($node->getGraph());
				addLDOToJSONLDNode($newnode, $obj, $cwurl);
				$node->setProperty($p, $newnode);
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $id => $obj){
				$newnode = new ML\JsonLD\Node($node->getGraph(), $id);
				addLDOToJSONLDNode($newnode, $obj, $cwurl);
				$node->setProperty($p, $newnode);
			}
		}
		elseif($pv->objectliteral()){
			$newnode = objectLiteralToJSONLDNode($v);
			$node->setProperty($p, $newnode);
		}
		elseif($pv->objectliterallist()){
			foreach($v as $ol){
				$newnode = objectLiteralToJSONLDNode($ol);
				$node->setProperty($p, $newnode);				
			}
		}
		elseif($pv->link()){
			$newnode = new ML\JsonLD\Node($node->getGraph(), $v);
			$node->setProperty($p, $newnode);				
		}
		elseif($pv->valuelist()){
			foreach($v as $oneval){
				if(isNamespacedURL($oneval) || isURL($oneval)){
					$newnode = new ML\JsonLD\Node($node->getGraph(), $oneval);
					$node->setProperty($p, $newnode);						
				}
				else {
					$node->setProperty($p, ML\JsonLD\Value::fromJsonLd((object) array('@value' => $oneval)));
				}				
			}
						
		}
		elseif($pv->literal()){
			$node->setProperty($p, ML\JsonLD\Value::fromJsonLd((object) array('@value' => $v)));				
		}
		else {
			$node->setProperty($p, "error could not convert ".$pv->ldtype()." value to jsonld");
		}
	}
}

/**
 * Adds a dacura object literal to a jason ld node
 * @param array $lit the object literal {type: .. data: ... lang ...}
 * @return newly created json ld node
 */
function objectLiteralToJSONLDNode($lit){
	if(isset($lit['lang'])){
		$newnode = new \ML\JsonLD\LanguageTaggedString($lit['data'], $lit['lang']);
	}
	else {
		$newnode = new \ML\JsonLD\TypedValue($lit['data'], $lit['type']);
	}
	return $newnode;
}

/**
 * Imports a dacura properties array from a json ld doc
 * @param jsondoc $doc the input document
 * @return array properties array
 */
function propsFromJSONLDDoc($doc){
	$doc = ML\JsonLD\JsonLD::getDocument($doc);
	$ldprops = array();
	$gnames = $doc->getGraphNames();
	if(count($gnames) > 0){
		$ldprops['multigraph'] = true;
		foreach($gnames as $gname){
			$ldprops[$gname] = array();
			$graph = $doc->getGraph($gname);
			$ldprops[$gname] = propsFromGraph($graph);
		}	
	}
	else {
		$graph = $doc->getGraph();				
		$ldprops = propsFromGraph($graph);
		//$ldprops['multigraph'] = false;
	}
	return $ldprops;
}

/**
 * Get properties from a json ld graph
 * @param object $graph the json ld object representing the graph
 * @return array dacura linked data props array
 */
function propsFromGraph($graph){
	$ldprops = array();
	$nodes = $graph->getNodes();
	foreach ($nodes as $node) {
		//opr($node);
		$nid = $node->getId();
		//echo "<P>Processing node $nid";
		if($nid && count($node->getProperties()) > 0){
			if(!isset($ldprops[$nid])){
				$ldprops[$nid] = importJSONLDNode($node);
			}
			elseif(isAssoc($ldprops[$nid])){
				$ldprops[$nid] = array($ldprops[$nid]);
				$ldprops[$nid][] = importJSONLDNode($node);
			}
			else {
				$ldprops[$nid][] = importJSONLDNode($node);
			}
		}
	}
	return $ldprops;
}

/**
 * Imports dacura properties from json ld
 * @param object $jsonld the json ld object 
 * @return the properties array or false if failure
 */
function propsFromJSONLD($jsonld){
	$expanded = ML\JsonLD\JsonLD::expand($jsonld);//can pass in options to expansion of need be..
	if($expanded){
		return propsFromJSONLDDoc($expanded);
	}
	return false;
}

/**
 * Import a json ld node into a dacura ld proprs array
 * @param object $node the json ld node
 * @return array nodes that were imported
 */
function importJSONLDNode($node){
	$imported = array();
	$nprops = $node->getProperties();
	if(count($nprops) == 0 && $node->getId()){
		return $node->getId();
	}
	if($ntype = $node->getType()){
	 	$imported["http://www.w3.org/1999/02/22-rdf-syntax-ns#type"] = $ntype;
	}
	foreach($nprops as $prop => $values){
		if(!is_array($values)) $values = array($values);
		$imported[$prop] = array();
		foreach($values as $value){
			if(is_scalar($value)){
				$imported[$prop][] = $value;
			}
			elseif(is_object($value) && ($value instanceof ML\JsonLD\NodeInterface)){
				if(($nid = $value->getId()) && count($value->getProperties()) > 0){
					$imported[$prop][] = $nid;
				}
				else {
					$imported[$prop][] = importJSONLDNode($value);
				}
			}
			elseif(is_object($value) && ($value instanceof ML\JsonLD\LanguageTaggedString)){
				$imported[$prop][] = array("lang" => $value->getLanguage(), "data" => $value->getValue());
			}
			elseif(is_object($value) && ($value instanceof ML\JsonLD\TypedValue)){
				$imported[$prop][] = array("type" => $value->getType(), "data" => $value->getValue());				
			}
			else {
				//$imported[$prop][] = $value;
				//opr($value);
			}			
		}
		if(!isAssoc($imported[$prop]) && count($imported[$prop]) == 1){
			$imported[$prop] = $imported[$prop][0];
		}
	}
	return $imported;	
}