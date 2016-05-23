<?php 
/**
 * Set of classes which implement the RVO ontology as of April 2016
 * @author chekov
 *
 */

class RVO {
	/** @var This is a general property which can be used to attach the original error message provided by a reasoner. */
	var $message;
	/** @var This is a generic property which allows publishing additional context to a message. */
	var $info;
	/** @var The subject of a triple involved in a violation. SchemaBlankNode, EdgeOrphanInstance, InstanceProperty, InstanceBlankNode */
	var $subject;
	/** @var The predicate of a triple involved in a violation. SchemaBlankNode, EdgeOrphanInstance, InstanceProperty, InstanceBlankNode */
	var $predicate;
	/** @var The object of a triple involved in a violation. SchemaBlankNode, EdgeOrphanInstance, InstanceProperty, InstanceBlankNode */
	var $object;
	/** @var A property which assignes the property that produced a violation to the respective violation class.*/
	var $property;
	/** @var A property which assignes the element that was involved in a violation to the respective element violation class. */
	var $element;
	/** @var Type of constraint on a violation - could be openworld or closedworld. */
	var $constraintType;
	/** @var This is a generic property which indicates whether a violation is considered only best practice or more serious. */
	var $best_practice;
	/** @var The set of supported violation classes in the ontology */
	var $munge;
	var $class;
	static $violation_classes = array(
		"NoImmediateClass", "noImmediateDomain", "noImmediateRange", "OrphanClass", "ClassCycle", "NotDomainClass", "NotUniqueClassLabel", "NotUniqueClassName", 
		"NotSuperClassOfClass", "NotSubClassofClass", "NotIntersectionOfClass", "NotUnionOfClass", "NotUniquePropertyName", 
		"PropertyRange", "NoExplicitRange", "InvalidRange", "RangeNotSubsumed", "PropertyAnnotationOverload", "PropertyDomain",
		"NoExplicitDomain", "InvalidDomain", "DomainNotSubsumed", "OrphanProperty", "NotSubpropertyOfProperty","PropertyTypeOverload",
		"PropertyCycle","SchemaBlankNode", "InstanceProperty", "NoPropertyDomain", "NoPropertyRange","InvalidEdge", 
		"NotFunctionalProperty", "NotInverseFunctionalProperty", "LocalOrphanProperty", "NotAnElement", "ObjectInvalidAtClass",
		"NotRestrictionElement","EdgeOrphanInstance", "DataInvalidAtDatatype", "NotBaseTypeElement", "InstanceBlankNode",
		"Dependency", "MissingDependency", "IllegalPredicate", "IncorrectURL", "OntologyHijack", "annotationOverload"		
	);
	
	/**
	 * Loads the details of the violation from the passed property array if present
	 * @param string $props initialisation properties
	 */
	function __construct($props = false){
		$this->type = get_class();
		if($props){
			if(isset($props['property'])){
				$this->property = $props['property'];
			}
			if(isset($props['subject'])){
				$this->subject = $props['subject'];
			}
			if(isset($props['predicate'])){
				$this->predicate = $props['predicate'];
			}
			if(isset($props['object'])){
				$this->object = $props['object'];
			}
			if(isset($props['element'])){
				$this->element = $props['element'];
			}
			if(isset($props['message'])){
				$this->message = $props['message'];
			}
			if(isset($props['info'])){
				$this->info = $props['info'];
			}	
			if(isset($props['constraintType'])){
				$this->constraintType = $props['constraintType'];
			}
			if(isset($props['class'])){
				$this->class = $props['class'];
			}
			$this->munge = json_encode($props);	
		}
	}

	/**
	 * Get / set the info field
	 * @param string $m
	 * @return string
	 */
	function info($m = false){
		if($m !== false) $this->info = $m;
		return $this->info;
	}
	
	/**
	 * Get / set the message field
	 * @param string $m message
	 * @return string message
	 */
	function msg($m = false){
		if($m !== false) $this->message = $m;
		return $this->message;
	}
	
	/**
	 * Get / set the element field
	 * @param string $elem
	 */
	function element($elem=false){
		if($elem) $this->element = $elem;
		return $this->$element;
	}
	
	/**
	 * Get / set the constraintType field
	 * @param string $t constraint type
	 * @return string constraint type
	 */
	function constraintType($t = false){
		if($t) $this->constraintType = $t;
		return $this->constraintType;		
	}
	
	/**
	 * get set the property field
	 * @param string $prop
	 * @return string property
	 */
	function property($prop=false){
		if($prop) $this->property = $prop;
		return $this->property;
	}
	
	/**
	 * is this a schema violation?
	 * @return boolean
	 */
	function schema(){
		return false;		
	}
	
	/**
	 * Is this an instance violation
	 * @return boolean
	 */
	function instance(){
		return false;
	}
	
	/**
	 * Is this a dqs violation
	 * @return boolean
	 */
	function dqs(){
		return true;
	}
	
	/**
	 * is it a best practice violation
	 */
	function bp(){
		return $this->best_practice;
	}
	
	/**
	 * Returns a list of all the available DQS Schema tests
	 * @param boolean $include_bp if false best practice tests will not be included
	 * @return array - an aray of the tests
	 */
	static function getSchemaTests($include_bp = true){
		$alltests = RVO::getViolations();
		$itests = array();
		foreach($alltests as $id => $rvo){
			if($rvo->schema() && $rvo->dqs() && ($include_bp || !$rvo->bp())){
				$itests[lcfirst($id)."SC"] = $rvo;
			}				
		}
		return $itests;
	}
	
	/**
	 * Returns a list of all the available DQS instance tests
	 * @param boolean $include_bp include best practice tests
	 * @return array - an array of tests
	 */
	static function getInstanceTests($include_bp = true){
		$alltests = RVO::getViolations();
		$itests = array();
		foreach($alltests as $id => $rvo){
			if($rvo->instance() && ($include_bp || !$rvo->bp())){
				$itests[lcfirst($id)."IC"] = $rvo;
			}
		}
		return $itests;
	}
	
	/**
	 * Creates a new violation from the passed details
	 * @param string $cls the class of violation
	 * @param array $details property initialisation array
	 * @return RVO the violation object
	 */
	static function loadViolation($cls, $details){
		try {
			if($cls == "noImmediateDomain" || $cls == "noImmediateRange"){
				$cls.= "Violation";
			}
			$y = substr($cls, 0, strlen($cls) - strlen("Violation"));
			if(in_array($y, RVO::$violation_classes)){
				//echo "<P>creating new $clis";
				$x = new $cls($details);
				return $x;				
			}
			else {
				$details['message'] = $cls . " is not a known type of violation";
				$x = new UnknownViolation($details);
				return $x;
			}
		}
		catch(Exception $e){
			opr($e);
		}
		echo "Failed to create violation $cls";
		return false;
		
	}
	
	/**
	 * Gets a an array of all RVO violation types with no properties set
	 * @return array
	 */
	static function getViolations(){
		$x = RVO::$violation_classes;
		$viols = array();
		foreach($x as $v){
			$cls = $v."Violation";
			$viols[$v] = new $cls();
		}
		return $viols;
	}	
}
/* The schema part of the class hierarchy */

class SchemaViolation extends RVO {
	function schema(){
		return true;
	}
}
/* class violations can have associated parent and child nodes
 */
class ClassViolation extends SchemaViolation {
	/** @var Involved parent class */
	var $parent;
	/** @var Involved child class */
	var $child;
	
	function __construct($props = false){
		parent::__construct($props);
		if($props){
			if(isset($props['parent'])){
				$this->parent = $props['parent'];
			}
			if(isset($props['child'])){
				$this->child = $props['child'];
			}
		}	
	}

}

class OrphanClassViolation extends ClassViolation {
	var $label = "Orphan Class Violation";
	var $comment = "The class is not a subclass, intersection, or union of a valid class.";
	
}

/* class cycles have associated paths */
class ClassCycleViolation extends ClassViolation {
	var $label = "Class Cycle Violation";
	var $comment = "The class has a class cycle.";
	/** @var Defines the path of the cycle violation. */
	var $path;
	
	function __construct($props = false){
		parent::__construct($props);
		if($props && isset($props['path'])){
			$this->path = $props['path'];
		}
	}
}

class NotDomainClassViolation extends ClassViolation {
	var $label = "Not Domain Class Violation";
	var $comment = "The domain defined for the property is not a well defined class";
}

class NotUniqueClassLabelViolation extends ClassViolation {
	var $label = "Not Unique Class Label Violation"; 
	var $comment = "Class does not have exactly one label.";
	var $best_practice = true;
}

class NotUniqueClassNameViolation extends ClassViolation {
	var $label = "Not Unique Class Name Violation"; 
	var $comment = "The class or restriction is not unique (i.e. there is another existing class with the same identifier)." ;
	var $best_practice = true;
}

class noImmediateClassViolation extends ClassViolation {
	var $label = "No Immediate Class Violation";
	var $comment = "An undefined class is used as domain for a property or the class is defined but the superclass is not or the class is not a subclass of a defined class or the class is an intersection of a defined class but not a defined class or the class is not an intersection of a defined class or the class is not a union of a defined class or the class is a union but not a defined class.";
	var $best_practice = true;
}

class NotSuperClassOfClassViolation extends ClassViolation {
	var $label = "Not Super Class Violation"; 
	var $comment = "The class is not a superclass of a defined class.";
}

class NotSubClassofClassViolation extends ClassViolation {
	var $label = "Not Sub Class Violation";
	var $comment = "The class is not a subclass of a defined class.";
}

class NotIntersectionOfClassViolation extends ClassViolation {
	var $label = "Not Intersection of Class Violation";
	var $comment = "The class is an intersection of a defined class, but not a defined class or the class is not an intersection of a defined class. <p>Example: The class A is not an intersection of a valid class B.";
}

class NotUnionOfClassViolation extends ClassViolation {
	var $label = "Not Union of Class Violation";
	var $comment = "The class is not a union of a defined class or is a union of a defined class but not defined itself.";
}

/* Property Violations */

/**
 * Property violations can have parent and child properties
 */
class PropertyViolation extends SchemaViolation {
	/** @var Involved parent property. */
	var $parent;
	/** @var Involved child property */
	var $child;
	
	function __construct($props = false){
		parent::__construct($props);
		if($props){
			if(isset($props['parent'])){
				$this->parent = $props['parent'];
			}
			if(isset($props['child'])){
				$this->child = $props['child'];
			}
		}
	}
}

class NotUniquePropertyNameViolation extends PropertyViolation {
	var $label = "Not Unique Property Name Violation";
	var $comment = "Another property exists with the same identifier. <p>Example: A is not a unique property name, some property with this name already exists.";
	var $best_practice = true;
	
}

class PropertyRangeViolation extends PropertyViolation {
	var $label = "Property Range Violation";
	var $comment = "Property has no well defined range. <p>Example: Object property A has no specified range.";
	/** @var The intended range class of a property range violation. */
	var $range;
	
	function __construct($props = false){
		parent::__construct($props);
		if($props){
			if(isset($props['range'])){
				$this->range = $props['range'];
			}
		}
	}	
	
}

class noImmediateRangeViolation extends PropertyRangeViolation {
	var $label = "No Immediate Range Violation";
	var $comment = "Property has no immediate range (a super property may define its range).";
	var $best_practice = true;	
}

class NoExplicitRangeViolation extends PropertyRangeViolation {
	var $label = "No Explicit Range Violation";
	var $comment = "Property has no explicit range.";	
}

class InvalidRangeViolation extends PropertyRangeViolation {
	var $label = "Invalid Range Violation";
	var $comment = "The property has an invalid or unimplemented range. <p>Example: ObjectProperty Range class A is not a valid range for property A.";
}

class RangeNotSubsumedViolation extends PropertyRangeViolation {
	var $label = "Range Not Subsumed Violation";
	var $comment = "Invalid range on a property has been caused by failure of range subsumption. <p>Example: Invalid range on property A, due to failure of range subsumption.";
	/** @var Marks the parent property for range and domain not subsumed violations. */
	var $parentProperty;
	/** @var Parent range of a range not subsumed violation. */
	var $parentRange;
	
	function __construct($props = false){
		parent::__construct($props);
		if($props){
			if(isset($props['parentProperty'])){
				$this->parentProperty = $props['parentProperty'];
			}
			if(isset($props['parentRange'])){
				$this->parentRange = $props['parentRange'];
			}
		}
	}
}

class PropertyAnnotationOverloadViolation extends PropertyViolation {
	var $label = "Property Annotation Overload Violation";  
	var $comment = "The property is defined as a both a property and as an annotation property.";
	var $best_practice = true;
}

class PropertyDomainViolation extends PropertyViolation {
	var $label = "Property Domain Violation";
	var $comment = "Property has no well defined domain.";
	/** @var The intended domain class of a property domain violation. */
	var $domain;
	function __construct($props = false){
		parent::__construct($props);
		if($props){
			if(isset($props['domain'])){
				$this->domain = $props['domain'];
			}
		}
	}
}

class noImmediateDomainViolation extends PropertyDomainViolation {
	var $label = "No Immediate Domain Violation";
	var $comment = "Property has no immediate domain (a super property may define its domain).";
	var $best_practice = true;
}

class NoExplicitDomainViolation extends PropertyDomainViolation {
	var $label = "No Explicit Domain Violation";
	var $comment = "Property has no explicit domain.";
}

class InvalidDomainViolation extends PropertyDomainViolation {
	var $label = "Invalid Domain Violation";
	var $comment = "The property has an invalid or unimplemented domain. <p>Example: ObjectProperty Domain class A is not a valid domain for property A.";
}

class DomainNotSubsumedViolation extends PropertyDomainViolation {
	var $label = "Domain Not Subsumed Violation";
	var $comment = "Invalid domain on a property has been caused by failure of domain subsumption.";
	/** @var Marks the parent property for range and domain not subsumed violations. */
	var $parentProperty;
	/** @var Parent domain of a domain not subsumed violation. */
	var $parentDomain;
	function __construct($props = false){
		parent::__construct($props);
		if($props){
			if(isset($props['parentProperty'])){
				$this->parentProperty = $props['parentProperty'];
			}
			if(isset($props['parentDomain'])){
				$this->parentDomain = $props['parentDomain'];
			}
		}
	}
}

class NotSubpropertyOfPropertyViolation extends PropertyViolation {
	var $label = "Not Subproperty Of Property Violation";
	var $comment = "The property is not a subproperty of a valid property. <p>Example: Property A is not a sub-property of a valid property B.";
}

class OrphanPropertyViolation extends PropertyViolation {
	var $label =  "Orphan Property Violation";
	var $comment = "The property is not a sub-property of a valid property.";
}

class PropertyTypeOverloadViolation extends PropertyViolation {
	var $label =  "Property Type Overload Violation";
	var $comment = "The property is an object property and a datatype property.";
}

class PropertyCycleViolation extends PropertyViolation {
	var $label = "Property Cycle Violation";
	var $comment = "The property inheritance hierarcchy contains a property cycle.";
	/** @var Defines the path of the cycle violation. */
	var $path;
	function __construct($props = false){
		parent::__construct($props);
		if($props && isset($props['path'])){
			$this->path = $props['path'];
		}
	}
}

class SchemaBlankNodeViolation extends SchemaViolation {
	var $label = "Schema Blank Node Violation";
	var $comment = "Subject, predicate, or object is a blank node.";
	var $best_practice = true;
}

/* Instance Violations */

class InstanceViolation extends RVO {
	function instance(){
		return true;
	}
}

class InstancePropertyViolation extends InstanceViolation {
	var $label = "Instance Property Violation";
	var $comment = "No property class associated with property.";
}

class NoPropertyDomainViolation extends InstanceViolation {
	var $label = "No Property Domain Violation";
	var $comment = "Property has no well defined domain. <p>Example: Object property A has no specified domain.";
}

class NoPropertyRangeViolation extends InstanceViolation {
	var $label =  "No Property Range Violation";
	var $comment = "Property has no well defined range.";
}

class InvalidEdgeViolation extends InstancePropertyViolation {
	var $label =  "Invalid Edge Violation";
	var $comment = "Range/domain cardinality of deleted predicates not respected.";
}

class NotFunctionalPropertyViolation extends InstancePropertyViolation {
	var $label =  "Not Functional Property Violation";
	var $comment = "Property declared as functional is not functional.";
}

class NotInverseFunctionalPropertyViolation extends InstancePropertyViolation {
	var $label = "Not Inverse Functional Property Violation";
	var $comment = "Property declared as inverse functional is not inverse functional.";
}

class LocalOrphanPropertyViolation extends InstancePropertyViolation {
	var $label = "Local Orphan Property Violation";
	var $comment = "No property class associated with property.";
}

class NotAnElementViolation extends InstanceViolation {
	var $label = "Not an Element Violation";
	var $comment = "Not an element of enumeration, intersection, or union.";
	/** @var Defines the cardinality of a not an element violation. */
	var $cardinality;
	/** @var The value of an element involved in the not an element violation. */
	var $value;
	/** @var the relation between a not an elment violation and the class the element was assigned to.*/
	var $qualifiedOn;
	
	function __construct($props = false){
		parent::__construct($props);
		if($props){
			if(isset($props['cardinality'])){
				$this->cardinality = $props['cardinality'];
			}
			if(isset($props['value'])){
				$this->value = $props['value'];
			}
			if(isset($props['qualifiedOn'])){
				$this->qualifiedOn = $props['qualifiedOn'];
			}
		}
	}
}

class ObjectInvalidAtClassViolation extends NotAnElementViolation {
	var $label = "Object Invalid at Class Violation"; 
	var $comment = "Not an element of enumeration or more than one branch of disjoint union is valid or element is not valid at any class of union or complement is valid.";
}

class NotRestrictionElementViolation extends NotAnElementViolation {
	var $label = "Not Restriction Element Violation";
	var $comment = "No values from restriction class or some values not from restriction class or cardinality too small on restriction class or cardinality too large on restriction class or cardinality unequal on restriction class or qualified cardinality too small on restriction class or qualified cardinality too large on restriction class or qualified cardinality unequal on restriction class or hasValue constraint violated.";
}

class EdgeOrphanInstanceViolation extends NotAnElementViolation {
	var $label = "Edge Orphan Instance Violation";
	var $comment = "The instance has no class or an invalid domain class.";
}

class DataInvalidAtDatatypeViolation extends NotAnElementViolation {
	var $label = "Data Invalid at Datatype Violation";
	var $comment = "Not an element of enumeration or not an element of intersection or not an element of union or literal cannot be an object.";
}

class NotBaseTypeElementViolation extends DataInvalidAtDatatypeViolation {
	var $label = "Not Base Type Element Violation";
	var $comment = "The value is not element of the specified datatype.";
}

class InstanceBlankNodeViolation extends InstanceViolation {
	var $label = "Instance Blank Node Violation";
	var $comment = "Subject, preidcate, or object is a blank node.";
	var $best_practice = true;
}

class annotationOverloadViolation extends RVO {
	var $label = "Annotation Overload Violation";
	var $comment = "Property declared as both an annotation property and an object class";
	var $best_practice = true;
}

/* Added Dacura (non-DQS) dependency violations */

class DependencyViolation extends SchemaViolation {
	var $label = "Dependency Violation";
	var $comment = "The schema's dependencies are in an incosistent state";

	function dqs(){
		return false;
	}
}

class MissingDependencyViolation extends DependencyViolation {
	var $label = "Missing Dependency Violation";
	var $comment = "Ontology has a structural dependency on an ontology that is not present";
}

class IllegalPredicateViolation extends DependencyViolation {
	var $label = "Illegal Predicate Violation";
	var $comment = "Use of an illegal predicate";
}

class IncorrectURLViolation extends DependencyViolation {
	var $label = "Incorrect URL Violation";
	var $comment = "Use of an incorrect URL to identify a linked data object";
	var $best_practice = true;
}

class OntologyHijackViolation extends DependencyViolation {
	var $label = "Ontology Hijack Violation";
	var $comment = "Changing the definition of an entity defined in a third party ontology";
	var $best_practice = true;
}

class SystemRVO extends RVO {
	var $label = "System Violation";
	var $comment = "A violation was encountered while processing the request";

	function __construct($action, $title, $msg){
		$this->message = $title;
		$this->info = $msg;
		$this->comment .= " in ".$action;
	}
}


class SystemViolation extends SystemRVO {
	var $label = "Dacura System Error";
	var $comment = "An error was encountered while processing the request";	
}

class GraphTestFailure extends SystemViolation {
	var $label = "Graph Test Failure";
}


class SystemWarning extends SystemRVO {
	var $label = "Dacura System Warning";
	var $comment = "A warning condition was encountered while processing the request";
}

class RequestIDRefusalWarning extends SystemWarning {
	var $label = "Request ID refused Warning";
	var $comment = "A requested ID  was not allocated";
}

class UnknownViolation extends RVO {
	var $label = "Unknown Error";
	var $comment = "weird shit is happening";
}