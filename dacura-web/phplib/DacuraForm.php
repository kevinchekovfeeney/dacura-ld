<?php 

class DacuraForm extends DacuraObject {
	var $elements = array();
	var $settings = array();
	var $type; //update, view, create
	var $toplevel = true;
	
	function __construct($type, $settings = array(), $is_contained = false){
		$this->type = $type;
		$this->settings = $settings;
		if($is_contained){
			$this->toplevel = false;
		}
	}
	
	function addElements($rows){
		foreach($rows as $row){
			$dfe = new DacuraFormElement();
			if($dfe->load($row, $this->type)){
				$this->elements[] = $dfe;
			}
			else {
				return $this->failure_result($dfe->errmsg, $dfe->errcode);
			}
		}
		return true;
	}
	
	function html($jdid){
		$html = "<table class='dacura-property-table dacura-".$this->type."-table' id='$jdid'>";
		foreach($this->elements as $el){
			$html .= $el->tr($this->type);			
		}
		$html .="</table>";		
		return $html;
	}
}

class DacuraFormElement extends DacuraObject {
	var $id;
	var $type; //text, url, image, password, choice, composite, section
	var $input_type; //input, textarea, checkbox, radio, password a.n.other
	var $element_size; //short, regular, long, 
	var $display_type;//
	var $update_disabled;
	var $hidden;
	var $label;
	var $submittable_alone;
	var $default_value;
	var $value;
	var $options; //array of options for choice type
	var $help;
	var $subfields;
	
	function load($row, $type = false){
		if(!isset($row['id'])){
			return $this->failure_result("Dacura form elements must have a form id associated", 400);
		}
		$this->label = isset($row['label']) ? $row['label'] : false;
		$this->hidden = isset($row['hidden']) ? $row['hidden'] : false;
		$this->element_size = isset($row['length']) ? $row['length'] : 'regular';
		$this->default_value = isset($row['default_value']) ? $row['default_value'] : "";
		$this->value = isset($row['value'])  ? $row['value'] : $this->default_value;
		
		$this->submittable_alone = isset($row['submit']) ? $row['submit'] : false;
		$this->update_disabled = isset($row['disabled']) ? $row['disabled'] : false;
		$this->help = isset($row['help']) ? $row['help'] : "";
		$this->options = isset($row['options']) ? $row['options'] : array();
		$this->id = $row['id'];
		$this->type = isset($row['type']) ? $row['type'] : 'text';
		if(isset($row['input_type'])){
			$this->input_type = $row['input_type'];
		}
		elseif(is_array($this->value)){
			$this->input_type = "textarea";
		}
		elseif($this->type == 'password'){
			$this->input_type = "password";
		}
		elseif($this->type == 'status'){
			$this->options = DacuraObject::$statuses;
			$this->input_type = "select";
		}
		elseif($this->type == 'choice'){
			$this->input_type = "select";
		}
		else {
			$this->input_type = "input";
		}
		if($this->type == "section"){
			$this->input_type = "section";
			$this->subfields = new DacuraForm($type, array(), true);
			$this->subfields->addElements($row['fields']);
		}
		return true;
	}
	
	function isSection(){
		return $this->type == "section";
	}
	
	function getValueTable($type){
		$html = "<table class='dacura-property-value-bundle'><tr><td class='dacura-property-input'>";
		if($type == "view"){
			$html .= $this->getDisplayElementHTML();
		}
		else {
			$html .= $this->getInputElementHTML();
		}
		$html .= "</td>";
		if($this->submittable_alone){
			$html .= "<td class='dacura-property-submit'>".$this->submittable_alone."</td>";
		}
		if($this->help){
			$html .= "<td class='dacura-property-help'>".$this->help."</td>";
		}
		$html .= "</tr></table>";
		return $html;
	}
	
	
	function tr($formtype){
		//if($this->isSection()){
		//	$html = "<tr class='dacura-property-section' id='section-".$this->id."'>";
		//	$html .= "<td colspan='2' class='dacura-section-label'>".$this->label."</td>";
		//	$html .= "</tr>";
		//}
		//else {
			$html = "<tr class='dacura-property' id='row-".$this->id."'>";
			$html .= "<td class='dacura-property-label'>".$this->label."</td>";
			$html .= "<td class='dacura-property-value'>";
			$html .= $this->getValueTable($formtype);
			$html .= "</td></tr>";
		//}
		return $html;
	}

	function getDisplayElementHTML(){
		if($this->isSection()){
			$html = $this->subfields->html($this->id."-sub-property-table");
		}
		elseif(is_array($this->value)){
			$html = "<div class='dacura-display-json'>" . json_encode($this->value, JSON_PRETTY_PRINT)."</div>";
		}
		else {
			$v = $this->value;
			if($v === true) $v = "true";
			if($v === false) $v = "false";
				
			$html = "<span class='dacura-display-value'>$this->value</span>";				
		}
		return $html;
	}
	
	function getInputElementHTML(){
		$cls = 'dacura-'.$this->element_size.'-input';
		$disabled = $this->update_disabled ? "disabled" : "";
		if($this->input_type == "input"){
			$html = "<input id='$this->id' class='$cls' $disabled type='text' value='$this->value'>";				
		}
		elseif($this->input_type == "textarea"){
			if(is_array($this->value)){
				$val = json_encode($this->value, JSON_PRETTY_PRINT);
				$cls .= " dacura-display-json";
			}
			else {
				$val = $this->value;
			}			
			$html = "<textarea id='$this->id' class='$cls' $disabled >$val</textarea>";
		}
		elseif ($this->input_type == "password"){
			$html = "<input id='$this->id' class='$cls' $disabled type='password' value='$this->value'/>";
		}
		elseif($this->input_type == "select"){
			$html = "<select id='$this->id' class='dacura-select $cls' $disabled>";
			if(isAssoc($this->options)){
				foreach($this->options as $v => $title){
					$selected = ($this->value && $this->value == $v) ? " selected" : "";
					$html .= "<option value='$v' $selected>$title</option>";
				}
			}
			else {
				foreach($this->options as $v ){
					$selected = ($this->value && $this->value == $v) ? " selected" : "";
					$html .= "<option value='$v' $selected>$v</option>";
				}
			}
			$html .= "</select>";
		}
		else {
			$html = "<span class='dacura-error'>Value: $this->value</span>";
		}
		return $html;
	}

}




