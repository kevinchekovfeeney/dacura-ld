<?php 
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



function toJSONLD($props, $ns = false, $rules){
	$jsonld = propsToJSONLD($props, $rules);
	$compressed = ML\JsonLD\JsonLD::compact($jsonld->toJsonLd(), (object) $ns);
	return $compressed;
}

function fromJSONLD($jsonld, $rules){
	$jsonld = json_decode(json_encode($jsonld));
	return propsFromJSONLD($jsonld, $rules);
}

function toNQuads($props, $rules = array()){
	$rules['multigraph'] = true;
	$jsonld = propsToJSONLD($props, $rules);
	$quads = ML\JsonLD\JsonLD::toRdf($jsonld->toJsonLd());
	$nquads = new ML\JsonLD\NQuads();
	$ser = $nquads->serialize($quads);
	return $ser;
}

function fromNQuads($text, $rules){
	$nquads = new ML\JsonLD\NQuads();
	try {
		$quads = $nquads->parse($text);
	}
	catch(Exception $e){
		return false;
	}
	$document = ML\JsonLD\JsonLD::fromRdf($quads);
	return propsFromJSONLDDoc($document, $rules);
}



/**
 * Transforms the built-in linked data property array into a json ld structure
 * @param array $props an ld property array
 * @param array $rules settings governing the transformation 
 * @return array json-ld compliant array
 */
function propsToJSONLD($props, $rules){
	$jsonld = new ML\JsonLD\Document($rules['cwurl']);
	if(isset($rules['multigraph']) && $rules['multigraph']){
		foreach($props as $gid => $ldos){
			$ng = $jsonld->createGraph($gid);
			foreach($ldos as $nid => $ldo){
				$nn = $ng->createNode($nid, true);
				addLDOToJSONLDNode($nn, $ldo, $rules);
			}
		}
	}
	else {
		$dg = $jsonld->getGraph();
		foreach($props as $nid => $ldo){
			$nn = $dg->createNode($nid, true);
			addLDOToJSONLDNode($nn, $ldo, $rules);
		}
	}
	return $jsonld;
}

function addLDOToJSONLDNode(&$node, $ldo, $rules){
	foreach($ldo as $p => $v){
		$pv = new LDPropertyValue($v, $rules['cwurl']);
		if($pv->embedded()){
			$newnode = new ML\JsonLD\Node($node->getGraph());
			addLDOToJSONLDNode($newnode, $v, $rules);
			$node->setProperty($p, $newnode);		
		}
		elseif($pv->objectlist()){
			foreach($v as $obj){
				$newnode = new ML\JsonLD\Node($node->getGraph());
				addLDOToJSONLDNode($newnode, $obj, $rules);
				$node->setProperty($p, $newnode);
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $id => $obj){
				$newnode = new ML\JsonLD\Node($node->getGraph(), $id);
				addLDOToJSONLDNode($newnode, $obj, $rules);
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

function objectLiteralToJSONLDNode($lit){
	if(isset($lit['lang'])){
		$newnode = new \ML\JsonLD\LanguageTaggedString($lit['data'], $lit['lang']);
	}
	else {
		//opr($lit);
		$newnode = new \ML\JsonLD\TypedValue($lit['data'], $lit['type']);
	}
	return $newnode;
}

function propsFromJSONLDDoc($doc, $rules){
	$doc = ML\JsonLD\JsonLD::getDocument($doc);
	$ldprops = array();
	$gnames = $doc->getGraphNames();
	if(count($gnames) > 1){
		$ldprops['multigraph'] = true;
		foreach($gnames as $gname){
			$ldprops[$gname] = array();
			$graph = $doc->getGraph($gname);
			$ldprops[$gname] = propsFromGraph($graph);
		}	
	}
	else {
		if(count($gnames) == 1){
			$graph = $doc->getGraph($gnames[0]);				
		}
		else {
			$graph = $doc->getGraph();				
		}
		$ldprops = propsFromGraph($graph);
		$ldprops['multigraph'] = false;
	}
	return $ldprops;
}

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

function propsFromJSONLD($jsonld, $rules){
	$expanded = ML\JsonLD\JsonLD::expand($jsonld);//can pass in options to expansion of need be..
	if($expanded){
		return propsFromJSONLDDoc($expanded, $rules);
	}
	return false;
}

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