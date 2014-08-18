<?php



class ToolDisplay extends genericToolDisplay {

	var $jscripts = array('getset', 'confirmscreen', 'overrides');


	function getHTML(){
		$str = $this->getWidgetSubsection("dc-details-section", "details", $this->getLocationChunk() .  $this->getDetailsChunk());
		$str .= $this->getWidgetSubsection("dc-motivations-section", "motivations", $this->getMotivationChunk());
		$str .= $this->getWidgetSubsection("dc-actors-section", "actors", $this->getActorsChunk());
		$str .= $this->getWidgetSubsection("dc-description-section", "description", "<textarea id='event-description-input'></textarea>");
		$str .= $this->getWidgetSubsection("dc-citation-section", "citation", $this->getCitationChunk());
		return ($str . $this->getWidgetCollapseScript().$this->getToolScripts());
	}



	function getDetailsChunk(){
		$vals = $this->wizer->sparql->getSchemaValues($this->wizer->schema_graph_name, $this->wizer->ns->getnsuri("pv")."Category");
		$nvals = array("" => "Event Type");
		if(!$vals){
			$html = "<div class='dc-widget-line'>No category data from SPARQL</div>";
			return $html;
		}
		foreach($vals as $i => $v){
			$nvals[$v] = $v;
		}
		asort($nvals);
		$html = "<div class='dc-widget-line'>". $this->makeSelect("event-category", $nvals, false, false);
		$html .= " <span class='dc-select-mandatory'>*</span> ";
		//.$this->getFatalitiesTypeSelect('event-fatalities-precision')
		$html .= " <span class='dacura-property-label'>Fatalities:</span> <span class='event-fatalities-number-wrapper'>";
		$html .= $this->getTextBoxWithInstructions('event-fatalities-number', 'unknown', 8);
		$html .= " <a class='more-less-widget-link' href='javascript:showFatalitiesRange()'>range</a></span>";
		$html .= "<span class='event-fatalities-range'>";
		$html .= " <span class='event-fatalities-from'>".$this->getTextBoxWithInstructions('event-fatalities-from', 'min', 3)."</span>";		
		$html .= "<span class='event-fatalities-to'> - ".$this->getTextBoxWithInstructions('event-fatalities-to', 'max', 3)."</span>";
		$html .= " <a class='more-less-widget-link' href='javascript:showFatalitiesNumber()'>number</a></span>";
		$html .= "</div>";
		$html .= "<script>
				function showFatalitiesRange(){
					$('.event-fatalities-number-wrapper').hide();
					$('.event-fatalities-range').show();
				}
				function showFatalitiesNumber(){
					$('.event-fatalities-range').hide();
					$('.event-fatalities-number-wrapper').show();
				}
				$('.event-fatalities-range').hide();
				</script>";
		return $html;

	}


	function getLocationChunk(){
		$html = "<div class='dc-widget-line'>".$this->getLocationCountrySelect('event-location-country');
		$html .= " ".$this->getIrishCountySelect('event-location-irishcounty')." ";
		$html .= $this->getTextBoxWithInstructions('event-location-place', 'enter place name', 32)."</div>";
		$html .= "<div class='dc-widget-line'>".$this->getDateTimePrecisionSelect('event-datetime-precision')." ";
		$html .= $this->getDMYBoxes('event-datetime-from', true);
		$html .= " <input type='hidden' id='event-dmy-from-hidden'> ";
		$html .= "<span class='event-datetime-range'> &amp; ".$this->getDMYBoxes('event-datetime-to');
		$html .= " <input type='hidden' id='event-dmy-to-hidden'> </span></div>";
		$html .= "<script>
				 $( '#event-dmy-from-hidden').datepicker({
						minDate: new Date(1785, 0, 0),
						showOn: 'button',
						buttonImage: 'http://jqueryui.com/resources/demos/datepicker/images/calendar.gif',
						buttonImageOnly: true,
				 		changeMonth: true,
						changeYear: true
				});
				 $( '#event-dmy-to-hidden').datepicker({
						minDate: new Date(1785, 0, 0),
						showOn: 'button',
						buttonImage: 'http://jqueryui.com/resources/demos/datepicker/images/calendar.gif',
						buttonImageOnly: true,
				 		changeMonth: true,
						changeYear: true
				});
				$('#event-dmy-from-hidden').change(function() {
					var ds = $('#event-dmy-from-hidden').val();
					if(ds != ''){
						var dsbits = ds.split('/');
						if(dsbits.length == 3){
							$('#event-datetime-from-dd').val(dsbits[1]).focusout();
							$('#event-datetime-from-mm').val(dsbits[0]).focusout();
							$('#event-datetime-from-yy').val(dsbits[2]).focusout();
						}
					}					
				});
				$('#event-dmy-to-hidden').change(function() {
					var ds = $('#event-dmy-to-hidden').val();
					if(ds != ''){
						var dsbits = ds.split('/');
						if(dsbits.length == 3){
							$('#event-datetime-to-dd').val(dsbits[1]).focusout();
							$('#event-datetime-to-mm').val(dsbits[0]).focusout();
							$('#event-datetime-to-yy').val(dsbits[2]).focusout();
						}
					}
					
				});
				
				var changeFromDateRipple = function(){
					var d = $('#event-datetime-from-dd').val();
					var m = $('#event-datetime-from-mm').val();
					var y = $('#event-datetime-from-yy').val();
					var ds = $('#event-dmy-from-hidden').val();
					if(ds != ''){
						var dsbits = ds.split('/');
						if(dsbits.length == 3){
							var hd = dsbits[1];
							var hm = dsbits[0];
							var hy = dsbits[2];
						}
					}
					if(y != '' && y != 'yyyy'){
						var newds = ((m && m!='mm') ? m : '01') + '/';
						newds += ((d && d != 'dd') ? d : '01') + '/';
						newds += y;
						$('#event-dmy-from-hidden').val(newds);
					}
				}
				var changeToDateRipple = function(){
					var d = $('#event-datetime-to-dd').val();
					var m = $('#event-datetime-to-mm').val();
					var y = $('#event-datetime-to-yy').val();
					var ds = $('#event-dmy-to-hidden').val();
					if(ds != ''){
						var dsbits = ds.split('/');
						if(dsbits.length == 3){
							var hd = dsbits[1];
							var hm = dsbits[0];
							var hy = dsbits[2];
						}
					}
					if(y != '' && y != 'yyyy'){
						var newds = ((m && m!='mm') ? m : '01') + '/';
						newds += ((d && d != 'dd') ? d : '01') + '/';
						newds += y;
						$('#event-dmy-to-hidden').val(newds);
					}
				}
				$('#event-datetime-from-dd').change(changeFromDateRipple);
				$('#event-datetime-from-mm').change(changeFromDateRipple);
				$('#event-datetime-from-yy').change(changeFromDateRipple);
				$('#event-datetime-to-dd').change(changeToDateRipple);
				$('#event-datetime-to-mm').change(changeToDateRipple);
				$('#event-datetime-to-yy').change(changeToDateRipple);
				$('#event-location-irishcounty').hide();
				$('.event-datetime-range').hide();
				$('#event-datetime-precision').change(function() {
					if($(this).val() == 'between'){
						$('.event-datetime-range').show();
					}
					else {
						$('.event-datetime-range').hide();
					}
				});
				$('#event-location-country').change(function() {
					if($(this).val() == 'irl'){
						$('#event-location-irishcounty').show();
					}
					else {
						$('#event-location-irishcounty').hide();
					}
				});
				</script>";
		return $html;

	}

	function getLocationCountrySelect($prefix){
		$vals = array("" => "Country", "irl" => "Ireland", "eng" => "England", "sco" => "Scotland", "wal" => "Wales");
		$sel_str = $this->makeSelect($prefix, $vals, false, false);
		return $sel_str;
	}

	function getIrishCountySelect($prefix){
		$vals = array("" => "County", "antrim" => "Antrim", "armagh" => "Armagh", "carlow" => "Carlow", "cavan" => "Cavan",
				"carlow" => "Carlow", "clare" => "Clare", "cork" => "Cork", "derry" => "Derry", "donegal" => "Donegal", "down" => "Down", "dublin" => "Dublin",
				"fermanagh" => "Fermanagh", "galway" => "Galway", "kerry" => "Kerry", "kildare" => "Kildare", "kilkenny" => "Kilkenny",
				"laois" => "Laois (Queens)", "leitrim" => "Leitrim", "limerick" => "Limerick", "longford" => "Longford", "louth" => "Louth", "mayo" => "Mayo",
				"meath" => "Meath", "monaghan" => "Monaghan", "offaly" => "Offaly (Kings)", "roscommon" => "Roscommon", "sligo" => "Sligo",
				"tipperary" => "Tipperary", "tyrone" => "Tyrone", "waterford" => "Waterford", "westmeath" => "Westmeath", "wexford" => "Wexford", "wicklow" => "Wicklow"
		);
		$sel_str = $this->makeSelect($prefix, $vals, false, false);
		return $sel_str;
	}


	function makeCheckboxes($cls, $vals){
		$str = "<div id='$cls'>";
		asort($vals);
		$defaults = array("economic", "land", "labor", "political", "race", "religion", "unknown");
		$extras = array("nativist", "sex", "personal", "prison", "education", "insane", "ethnic", "work", "criminal", "exralegal", "other", "family", "shopping", "revenge");
		$surplus = array("indian", "section");
		foreach($vals as $i => $v){
			if(in_array($v, $defaults)){
				$str .= "<input class='dc-motivation-checkboxes dc-motivations-defaults' value='$v' type='checkbox' id='$cls-$v' /><label for='$cls-$v'>$v</label>";
			}
			elseif(in_array($v, $extras)){
				$str .= "<input class='dc-motivation-checkboxes dc-motivations-extras' value='$v' type='checkbox' id='$cls-$v' /><label for='$cls-$v'>$v</label>";
			}
		}
		$str .= "<div class='dc-motivations-quench'>more</div>";
		$str .= "</div>";
		return $str;
	}


	function getMotivationChunk(){
		$id = "event-motivation";
		$vals = $this->wizer->sparql->getSchemaValues($this->wizer->schema_graph_name, $this->wizer->ns->getnsuri("pv")."Motivation");
		if(!$vals){
			$html = "<div class='dc-widget-line'>No Motivation data from SPARQL</div>";
			return $html;
		}
		
		$str = "<div id='$id'>";
		asort($vals);
		$defaults = array("economic", "land", "labor", "political", "race", "religion", "unknown");
		$extras = array("nativist", "sex", "personal", "prison", "education", "insane", "ethnic", "work", "criminal", "exralegal", "other", "family", "shopping", "revenge");
		$surplus = array("indian", "section");
		foreach($vals as $i => $v){
			if(in_array($v, $defaults)){
				$str .= "<input class='dc-motivations-checkboxes dc-motivations-defaults' value='$v' type='checkbox' id='$id-$v' /><label class='dc-motivations-defaults' for='$id-$v'>$v</label>";
			}
			elseif(in_array($v, $extras)){
				$str .= "<input class='dc-motivations-checkboxes dc-motivations-extras' value='$v' type='checkbox' id='$id-$v' /><label class='dc-motivations-extras' for='$id-$v'>$v</label>";
			}
		}
		$str .= "<div id='dc-motivations-quench'>more</div>";
		$str .= "</div>";
		$str .= "<script>
		$(function() {
			$('input.dc-motivations-checkboxes').button();
			$('.dc-motivations-extras').hide();
			$('#dc-motivations-quench').click(function(){
				$('.dc-motivations-extras').toggle();
				if($('#dc-motivations-quench').html() == 'more'){
					$('#dc-motivations-quench').html('less');
				}
				else {
					$('#dc-motivations-quench').html('more');
				}
			});
		});
		</script>";
		return $str;
	}

	function getCitationChunk(){
		$html = "<table class='dacura-widget'>";
		$html .= "<tr><td class='dacura-property-label'>Publication</td><td class='dacura-property-input'><div class='dc-widget-line'>";
		$html .= $this->getTextBoxWithInstructions('event-citation-publication-title', 'enter publication title', 24). "  ";
		$html .= $this->getTextBoxWithInstructions('event-citation-publication-url', 'enter publication url', 24)."</div>";
		$html .= "</td></tr>";
		$html .= "<tr><td class='dacura-property-label'>Issue</td><td class='dacura-property-input'><div class='dc-widget-line'>";
		$html .= $this->getTextBoxWithInstructions('event-citation-issue-title', 'enter issue id', 24). "  ";
		$html .= $this->getTextBoxWithInstructions('event-citation-issue-url', 'enter issue url', 24)."</div>";
		$html .= "<div class='dc-widget-line'>".$this->getDMYBoxes("event-citation-issue-date");
		$html .= "</td></tr>";
		$html .= "<tr><td class='dacura-property-label'>Section</td><td class='dacura-property-input'><div class='dc-widget-line'>";
		$html .= $this->getTextBoxWithInstructions('event-citation-section-title', 'enter section title', 24). "  ";
		$html .= $this->getTextBoxWithInstructions('event-citation-section-url', 'enter section url', 24)."</div>";
		$html .= "</td></tr>";
		$html .= "<tr><td class='dacura-property-label'>Article</td><td class='dacura-property-input'><div class='dc-widget-line'>";
		$html .= $this->getTextBoxWithInstructions('event-citation-article-title', 'enter article title', 24). "  ";
		$html .= $this->getTextBoxWithInstructions('event-citation-article-url', 'enter article url', 24)."</div>";
		$html .= "<div class='dc-widget-line'>";
		$html .= $this->getTextBoxWithInstructions('event-citation-article-id', 'enter article id', 24). "  ";
		$html .= $this->getTextBoxWithInstructions('event-citation-article-image-url', 'enter article image url', 24)."</div>";
		$html .= "</td></tr>";
		$html .= "<tr><td class='dacura-property-label'>Pages</td><td class='dacura-property-input'><div class='dc-widget-line'>";
		$html .= $this->getTextBoxWithInstructions('event-citation-article-pagesfrom', '0', 3);
		$html .= " - ".$this->getTextBoxWithInstructions('event-citation-article-pagesto', '0', 3)."</div>  ";
		$html .= "</td></tr>";
		$html .= "</table>";
		//publicaton url
		//page url
		//file
		//publication title
		//publication issue
		//publication date
		//article title
		//section
		//page #
		return $html;

	}



	function getActorsChunk(){
		$html = "<table class='dacura-event-actors'>";
		$html .= "<tr><th>Actor ";
		$html .= "<span id='event-actors-add'>(more)</span>";
		$html .="</th><th>Size</th><th>Dead</th><th>Representing</th></tr>";
		$html.= "<tr id='dc-actor-1' class='dc-actor-odd'><td class='dacura-actor'>";
		$html .= $this->getActorChunk(1);
		$html .= "</td></tr>";
		$html .= "<tr id='dc-actor-2' class='dc-actor-even'><td class='dacura-actor'>";
		$html .= $this->getActorChunk(2);
		$html .= "</td></tr>";
		$html .= "<tr id='dc-actor-3' class='dc-actor-odd'><td class='dacura-actor'>";
		$html .= $this->getActorChunk(3);
		$html .= "</td></tr>";
		$html .= "<tr id='dc-actor-4' class='dc-actor-even'><td class='dacura-actor'>";
		$html .= $this->getActorChunk(4);
		$html .= "</td></tr></table>";
		$html .= "<script>
				dc_actornum = 2;
				$('#dc-actor-4').hide();
				$('#dc-actor-3').hide();
				$('.actor-fatalities-range').hide();
				$('.actor-count-range').hide();
				function showActorFatalitiesNumber(i){
					$('.actor-' + i + '-fatalities-number-wrapper').show();
					$('.actor-' + i + '-fatalities-range').hide();
				}
				function showActorFatalitiesRange(i){
					$('.actor-' + i + '-fatalities-number-wrapper').hide();
					$('.actor-' + i + '-fatalities-range').show();
				}
				function showActorCountNumber(i){
					$('.actor-' + i + '-count-number-wrapper').show();
					$('.actor-' + i + '-count-range').hide();
				}
				function showActorCountRange(i){
					$('.actor-' + i + '-count-number-wrapper').hide();
					$('.actor-' + i + '-count-range').show();
				}
				$('#event-actors-add').click(function(){
					if(dc_actornum < 4){
						dc_actornum++;
						$('#dc-actor-' + dc_actornum).show();
					}
					if(dc_actornum >= 4){
						$(this).addClass('dc-disabled');
					}
	
				});
				</script>";
		return $html;
	}
	
	
	function getActorChunk($i){
		$vals1 = array("" => "choose actor", "individual" => "Individual(s)", "broadgroup" => "Broad Group", "organisedgroup" => "Organised Group");
		$vals2 = array("" => "representing", "organisedgroup" => "Organised Group", "broadgroup" => "Social Group", "state" => "State");
		$html = $this->makeSelect("event-actor-type-$i", $vals1, false, false);
		$html .= "<div class='dc-widget-line'>";
		$html .= " <span id='event-actor-group-$i'>";
		$html .=  $this->getTextBoxWithInstructions("event-actor-groupname-$i", 'name/description', 20)."</div>";
		$html .= "</span></div></td>";
		$html .= "<td class='dacura-actor-count'>";
		$html .= $this->getActorCountChunk($i);
		$html .= "</td>";
		$html .= "<td class='dacura-actor-fatalities'>";
		$html .= $this->getActorFatalityChunk($i);
		$html .= "</td>";
		$html .= "<td class='dacura-actor-represents'>";
		$html .= $this->makeSelect("event-actor-represents-$i", $vals2, false, false);
		$html .= " <span class='event-actor-represents-group-$i'>";
		$html .= $this->getTextBoxWithInstructions("event-actor-represents-groupname-$i", 'name/description', 20);
		$html .= "</span>";
		$html .= "<script>
		$('#event-actor-group-$i').hide();
		$('.dc-actor-hide-$i').hide();
		$('.event-actor-represents-group-$i').hide();
		$('#event-actor-type-$i').change(function() {
			if($(this).val() != ''){
				$('.dc-actor-hide-$i').show();
			}
			else {
				$('.dc-actor-hide-$i').hide();
			}
			if($(this).val() != ''){
				$('#event-actor-group-$i').show();
			}
			else {
				$('#event-actor-group-$i').hide();
			}
		});
		$('#event-actor-represents-$i').change(function() {
			if($(this).val() == 'organisedgroup' || $(this).val() == 'broadgroup'){
				$('.event-actor-represents-group-$i').show();
			}
			else {
				$('.event-actor-represents-group-$i').hide();
			}
		});
		</script>";
		return $html;
	}
	
	function getActorFatalityChunk($i){
		$html = "<span class='actor-fatalities-number actor-$i-fatalities-number-wrapper'>";
		$html .= $this->getTextBoxWithInstructions('actor-'.$i.'-fatalities-number', '??', 3);
		$html .= " <a class='more-less-widget-link' id='actor-$i-more-less-widget-link' href='javascript:showActorFatalitiesRange(\"$i\")'>range</a></span>";
		$html .= "<span class='actor-fatalities-range actor-$i-fatalities-range'>";
		$html .= " <span class='actor-$i-fatalities-from'>".$this->getTextBoxWithInstructions('actor-'.$i.'-fatalities-from', 'min', 3)."</span>";
		$html .= "<span class='actor-$i-fatalities-to'> - ".$this->getTextBoxWithInstructions('actor-'.$i.'-fatalities-to', 'max', 3)."</span>";
		$html .= " <a class='more-less-widget-link' id='actor-$i-more-less-widget-link' href='javascript:showActorFatalitiesNumber(\"$i\")'>number</a></span>";
		return $html;
	}

	function getActorCountChunk($i){
		$html = "<span class='actor-count-number actor-$i-count-number-wrapper'>";
		$html .= $this->getTextBoxWithInstructions('actor-'.$i.'-count-number', '??', 3);
		$html .= " <a class='more-less-widget-link' id='actorcount-$i-more-less-widget-link' href='javascript:showActorCountRange(\"$i\")'>range</a></span>";
		$html .= "<span class='actor-count-range actor-$i-count-range'>";
		$html .= " <span class='actor-$i-count-from'>".$this->getTextBoxWithInstructions('actor-'.$i.'-count-from', 'min', 3)."</span>";
		$html .= "<span class='actor-$i-count-to'> - ".$this->getTextBoxWithInstructions('actor-'.$i.'-count-to', 'max', 3)."</span>";
		$html .= " <a class='more-less-widget-link' id='actorcount-$i-more-less-widget-link' href='javascript:showActorCountNumber(\"$i\")'>number</a></span>";
		return $html;
	}
	

	function getActorTypeSelect($i){
		$vals = array("individual" => "Individual", "organisedgroup" => "Organised Group", "broadgroup" => "Broad Group", "state" => "State");
		$sel_str = $this->makeSelect($i, $vals, false, false);
		return $sel_str;
	}
	



}