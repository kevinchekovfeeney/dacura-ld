<?php

require_once("SparqlBridge.php");
require_once("NSURI.php");

class Widgetizer {
	var $sparql;
	var $schema_graph_name;
	var $endpoint_url;
	var $widget_details = array();
	var $ns;
	var $pvns; 
	
	function __construct($gname, $endp){
		$this->schema_graph_name = $gname;
		$this->endpoint_url = $endp;
		$this->sparql = new SparqlBridge($endp);
		$this->ns = new NSURI();
		$this->pvns = $this->ns->getnsuri("pv");
	}

	function setWidgetDetails($ar){
		foreach($ar as $n => $v){
			$this->widget_details[] = "$n: '$v'";
		}
	}

	function getWidgetOptionString(){
		$str = '{' . implode(",\n", $this->widget_details)."}";
		return $str;
	}

	function URI2ID($uri){
		return "DCR1";
	}

	function getClassWidget($classuri, $depth = 0){
		$classes_to_render = array();
		$classes_to_render = $this->flatten_super_array($this->sparql->getSuperClasses($classuri, $this->schema_graph_name));
		$classes_to_render[] = $classuri;
		$classes_to_render = array_merge($classes_to_render, $this->flatten_sub_array($this->sparql->getSubClasses($classuri, $this->schema_graph_name)));
		$contents_str = "";
		$contents_str .= "<table class='dacura-widget'>";
		if($depth == 0){
			foreach($classes_to_render as $cls){
				//$contents_str .= "<tr><th colspan='2'>$cls</th></tr>";
				$props = $this->sparql->getEntityProperties($cls, $this->schema_graph_name);
				foreach($props as $prop){
					$contents_str .= $this->propertyListAsRowHTML($prop);
				}
			}
		}
		else {
			 
		}
		$contents_str .= "</table>";
		$contents_str .= "<div class='dacura-user-message'></div>";
		$contents_str .= $this->get_submit_buttons("dacura-submit");
		$option_str = $this->getWidgetOptionString();
		$widget_id = $this->URI2ID($classuri);
		$html = "<div class='dacura-widget' id='$widget_id'>$contents_str</div>
		<script>
		$(function() {
		$('#$widget_id').dialog($option_str);
	});
	</script>";
	return $html;
	}

	
	function get_submit_buttons($cls){
		$str = "<div class='$cls'>";
		$str .= "<input type='submit' class='dacura-submit-button' value='Add Record' id='$cls-add'>";
		$str .= "<input type='submit' class='dacura-submit-button' value='Update Record' id='$cls-update'>";
		$str .= "<input type='submit' class='dacura-submit-button' value='Delete Record' id='$cls-delete'>";
		$str .= "</div>";
		return $str;
	}
	
	function flatten_super_array($cls_tree){
	/*
	* Turns a class hierarchy into a flat list - branch by branch
	 */
		$super_flat = array();
		foreach($cls_tree as $parent => $branch){
			$super_flat[] = $parent;
			if(count($branch) > 0){
				$super_flat = array_merge($this->flatten_super_array($branch), $super_flat);
			}
		}
		return $super_flat;
	}

	function flatten_sub_array($subs){
	/*
	* Currently only return immediate sub-classes of the actual class...
		*/
		return array_keys($subs);
	}
	
	function makeSelect($sname, $vals, $ismulti = false){
		$rt = "<select id='$sname'";
		if($ismulti) $rt.=" multiple";
		$rt .= ">";
		$rt .= "<option id='$sname-none' value=''>Choose</option>";
		foreach($vals as $v){
			$rt .= "<option id='$sname-$v' value='$v'>$v</option>";
		}
		$rt .= "</select>";
		return $rt;
	}
	
	function propertyValueAsTDInput($propobj, $valobj = false){
		if($propobj->range == $this->ns->getnsuri("pv")."atTime"){
			//if($propobj->url == $this->ns->getnsuri("pv")."startDate"){
			//	return "<input type=text value='".$propobj->url."'>";
			//}
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Motivation"){
			$vals = $this->sparql->getSchemaValues($this->schema_graph_name, $propobj->range);
			return $this->makeSelect("event-motivation", $vals, true);
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Category"){
			$vals = $this->sparql->getSchemaValues($this->schema_graph_name, $propobj->range);
			return $this->makeSelect("event-category", $vals);
		}
		elseif($propobj->url == $this->pvns."description"){
			return "<textarea id='event-description'></textarea>";
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Fatalities"){
			return $this->getFatalitiesInput("event-fatalities", array());
			//return "<input type='text'></input>";
		}		
		elseif($propobj->range == $this->ns->getnsuri("pv")."Location"){
			return "<input type='text' id='event-location'></input>";
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Source"){
			return "<input type='text' id='event-source'></input>";
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Report"){
			return "<input type='text' id='event-duplicate'></input>";
		}
		//return $propobj->range;
	}
	
	function getFatalitiesInput($elid, $vals){
		$html = "<input class='fatalities-num' type='text' size='2'>";
		$html .= "<label class='fatalities-range fatalities-min'>Min</label><input class='fatalities-range fatalities-min' type='text' size='2'>";
		$html .= "<label class='fatalities-range fatalities-max'>Max</label><input class='fatalities-range fatalities-max' type='text' size='2'>";
		$types = array("Number", "Range", "Unknown");
		$html .= $this->makeSelect("$elid-types", $types);
		$html .= "</div>";
		return $html;
	}
	
	function getDateRangeChooserInput ($elid, $vals){
		$html = "";
		$types = array("Simple", "Range");
		$iptypes = array("Full", "Partial", "Unstructured");
		$html = $this->makeSelect("$elid-types", $types);
		$html .= $this->makeSelect("$elid-iptypes", $iptypes);
		$html .= "<div class='dacura-date-from'>";
		$html .= "<h4 class='dacura-date-header'>From Date</h4>";
		$html .= "<input type='text' size='24' class='dacura-date-input date-unstructured' id='$elid-from-date-unstructured'>";
		$html .= "<label class='date-d date-partial'>Day</label><input type='text' size='2' class='date-partial dacura-date-input date-d' id='$elid-from-date-day'> ";
		$html .= "<label class='date-m date-partial'>Month</label><input type='text' size='2' class='date-partial dacura-date-input date-m' id='$elid-from-date-month'> ";
		$html .= "<label class='date-y date-partial'>Year</label><input type='text' size='4' class='date-partial dacura-date-input date-y' id='$elid-from-date-year'>";
		$html .= "<input type='text' size='24' class='dacura-date-input dacura-date-from date-full' id='$elid-from-date-full'>";
		$html .= "</div><div class='dacura-date-to'>";
		$html .= "<h4 class='dacura-date-header'>To Date</h4>";
		$html .= "<input type='text' size='24' class='dacura-date-input date-unstructured' id='$elid-to-date-unstructured'>";
		$html .= "<label class='date-d date-partial'>Day</label><input type='text' size='2' class='dacura-date-input date-partial date-d' id='$elid-to-date-day'> ";
		$html .= "<label class='date-m date-partial'>Month</label><input type='text' size='2' class='dacura-date-input date-partial date-m' id='$elid-to-date-month'> ";
		$html .= "<label class='date-y date-partial'>Year</label><input type='text' size='4' class='dacura-date-input date-partial date-y' id='$elid-to-date-year'>";
		$html .= "<input type='text' size='24' class='dacura-date-input date-full' id='$elid-from-date-full'>";
		$html .= "</div>";
		return $html;
	}
	
	
	
	
	
	function propertyListAsRowHTML($propobj){
		if($propobj->url == $this->pvns."atTime"){
			$str = "<tr><td class='dacura-property-label'>Date</td><td class='dacura-property-input'>";
			$str .= $this->getDateRangeChooserInput("event-date", array());
			//$str .= $this->propertyValueAsTDInput($propobj);
			//$str .= $propobj->range;
			$str .= "</td><td><input type='checkbox'></td><td>?</td></tr>";
		}
		else {
			$str = "<tr><td class='dacura-property-label'>".ucfirst($propobj->label);
			$str .= "</td><td class='dacura-property-input'>";
			$str .= $this->propertyValueAsTDInput($propobj);
			//$str .= $propobj->range;
			$str .= "</td><td><input type='checkbox'></td><td>?</td></tr>";
		}
		return $str;
	}

}