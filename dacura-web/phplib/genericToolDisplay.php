<?php
class genericToolDisplay {
	var $id;
	var $errmsg;
	var $wizer;
	var $jscripts = array('generic');
	
	function __construct($s){
		$this->wizer = $s;
	}
	
	function getHTML(){
		return "Generic tool display";
	}
	
	function getToolScripts(){
		$html = "<script>";
		foreach($this->jscripts as $js){
			if(file_exists("phplib/tools/js/$js.js")){
				$html .= file_get_contents("phplib/tools/js/$js.js");
			}
		}
		return $html . "</script>";
		
	}

	function getWidgetSubsection($id, $title, $contents, $is_displayed = true){
		$display_css = $is_displayed ? "dc-section-displayed" : "dc-section-hidden";
		$html = "<div id='$id' class='dc-widget-section'><div id='$id-header' class='dc-widget-section-header $display_css'>$title</div>";
		$html .= "<div class='dc-widget-section-contents $display_css' id='$id-contents'>$contents</div>";
		$html .= "</div>";
		return $html;
	}

	function getWidgetCollapseScript(){
		$str = "<script>
				$('div.dc-widget-section-header').click(function(e){
				$(this).toggleClass('dc-section-hidden');
				$(this).toggleClass('dc-section-displayed');
				$(this).next().toggle();
			});
		</script>";
		return $str;
	}

	function makeSelect($sname, $vals, $ismulti = false, $add_default = true){
		$rt = "<select class='dc-input-text' id='$sname'";
		if($ismulti) $rt.=" multiple";
		$rt .= ">";
		if($add_default) $rt .= "<option id='$sname-none' value=''>Choose</option>";
		foreach($vals as $i => $v){
			if($i === "" or $i == "none"){
				$rt .= "<option id='$sname-$i' class='dc-default-value'	value='$i'>$v</option>";
			}
			else {
				$rt .= "<option id='$sname-$i' value='$i'>$v</option>";
			}
		}
		$rt .= "</select>";
		return $rt;
	}

	function getDateTimePrecisionSelect($prefix){
		$vals = array("on" => "On Date", "between" => "Between Dates");
		$sel_str = $this->makeSelect($prefix, $vals, false, false);
		return $sel_str;
	}

	function getDMYBoxes($id, $mandyear = false){
		$html = $this->getTextBoxWithInstructions($id.'-dd', 'dd', 2);
		$html .= $this->getTextBoxWithInstructions($id.'-mm', 'mm', 2);
		$html .= $this->getTextBoxWithInstructions($id.'-yy', 'yyyy', 4, $mandyear);
		return $html;
	}

	function getTextBoxWithInstructions($id, $instr, $sz = 32, $ismand = false){
		if($ismand){
			$cls = 'dc-input-text dc-default-value dc-mandatory-field';
		}
		else {
			$cls = 'dc-input-text dc-default-value';
		}
		$html = "<input size='$sz' class='$cls' type='text' id='$id' value='$instr'>";
		if($ismand){
			$html .= "<span class='dc-select-mandatory'>*</span>";
		}
		$html .= "<script>
		$('#$id').focusin(function(){
		if($(this).val() == '$instr'){
		$(this).val('');
		$(this).removeClass('dc-default-value');
	}
	});
	$('#$id').focusout(function(){
		if($(this).val() == ''){
			$(this).val('$instr');
			$(this).addClass('dc-default-value');
		}
		else {
			$(this).removeClass('dc-default-value');
		}
	});
	</script>";
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
	
	

}
