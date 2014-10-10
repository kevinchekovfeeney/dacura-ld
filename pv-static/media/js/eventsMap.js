var categories = "";
var initYear = 1780, endYear = 2010, timelapse_step_duration = 1500, initYearLapse = initYear, endYearLapse = endYear, curInitYear, curEndYeaer;
var html_years, html_timelapse_years, togglelapse = false, timelapse_running = false, max_key_timelapse = 0;
var interval;

$(document).ready(function(){
	//Save the content of the years div on a variable
	html_years = $("#years").html();
	$("#yearsIndicator").hide();
	var filterBox = $("#filter table");
	//Load the categories from the db and adds a filter for the map
	$.getJSON("phplib/querycategories.php", function(data) {
		 $.each( data, function( key, val ) {
				filterBox.append("<tr><td id = 'filter" + key + "' style='padding: 0px;'>" + val['name'] + "</td><td><input type='checkbox' id='checkfilter' onclick='updateFilter();' value='" + val['name'] + "' checked/></td></tr>");
				categories += val['name'] + ",";
			});
			sendYears();
	});
	//Set the manual mode as the selected mode when the page loads
	$("#manualMode").css("font-weight", "bold");
	//Initialize the inputs
	$("#startDate").val(initYear);
	$("#endDate").val(endYear);
	curInitYear = $("#startDate").val();
	curEndYear = $("#endDate").val();
	//Initialize the slider
	$("#year-range").slider({ values: [ initYear, endYear ]});
	//Add the click functionality to the play button
	$(document).delegate("#playBt", "click", function(){
		var y = initYearLapse;
		//If the timelapse is not currently running, starts the timelapse
		if (!timelapse_running) {
			timelapse_running = true;
			$("#playBt").val("Stop");
			disableFilterBoxes();
			//Creates an asynchronous function that updates the map and goes through all the years on the range selected
			interval = setInterval(function() {
				var nextStep = ((y+10 > endYearLapse)?endYearLapse:y+10);
				$("#yearsIndicator").html(y + " - " + nextStep);
				$("#rangedisplay").attr("value", nextStep);
				curInitYear = y;
				curEndYear = nextStep;
				var searchValues = new Object();
				searchValues.startDate=y;
				searchValues.endDate=nextStep;
				searchValues.categories = categories.toString();
				sendYears(searchValues);
				y+= 10;
				if (y >= endYearLapse) {
					clearInterval(interval);
					timelapse_running = false;
					$("#playBt").val("Play");
					enableFilterBoxes();
				}
			}, timelapse_step_duration);
		} else { //The timelapse is running, so when press the button it stops
			clearInterval(interval);
			timelapse_running = false;
			$("#playBt").val("Play");
			enableFilterBoxes();
		}
	});
	//Manual mode tab click
	$("#manualMode").on("click", function() {
		//Checks the currently-active tab
		if (togglelapse) {
			$("#manualMode").css("font-weight", "bold");
			$("#timelapseMode").css("font-weight", "");
			$("#years").fadeOut("fast", function() {
				$("#years").html(html_years);
				addSliderFunctions();
				$("#years").fadeIn("fast");
			});
			$("#yearsIndicator").fadeOut("slow", function() { $("#yearsIndicator").html(""); });
			togglelapse = false;
		}
		//If the timelapse is running, it stops it and resets the map
		if (timelapse_running) {
			clearInterval(interval);
			timelapse_running = false;
			enableFilterBoxes();
			var searchValues = new Object();
			searchValues.startDate=$("#startDate").val();
			searchValues.endDate=$("#endDate").val();
			searchValues.categories = categories.toString();
			sendYears(searchValues);
		}
	});
	//Timelapse mode tab click
	$("#timelapseMode").on("click", function() {
		//Checks the currently-active tab
		if (!togglelapse) {
			initYearLapse = parseInt($("#startDate").val());
			endYearLapse = parseInt($("#endDate").val());
			$("#yearsIndicator").fadeIn("slow");
			$("#yearsIndicator").html($("#startDate").val() + " - " + $("#endDate").val());
			//Content for the years_lapse div when switching to timelapse mode
			html_timelapse_years = "<div style='text-align: center;'><span id='years_lapse'>" + $("#startDate").val() + " - " + $("#endDate").val() + "</span><input type='range' id='rangedisplay' min='" + $("#startDate").val() + "' max='" + $("#endDate").val() + "' value='" + $("#startDate").val() + "' disabled/><input type='button' class='btn btn-primary' id='playBt' value='Play'/></div>";
			$("#manualMode").css("font-weight", "");
			$("#timelapseMode").css("font-weight", "bold");
			$("#years").fadeOut("fast", function() {
				$("#years").html(html_timelapse_years);
				$("#years").fadeIn("fast");
			});
			togglelapse = true;
		}
	});
	
})

function disableFilterBoxes() {
	$("input[type='checkbox']").attr("disabled", true);
}

function enableFilterBoxes() {
	$("input[type='checkbox']").removeAttr("disabled");
}

//Executed every time the user interacts with the category filter
function updateFilter(box) {
	catr = [];
	var idx = 0;
	$("input[type='checkbox']").each(function(i, v) {
		if ($("input[type='checkbox']:eq(" + i + ")").prop("checked")) {
			catr[idx] = $("input[type='checkbox']:eq(" + i + ")").val() + ",";
			idx++;
		}
	});
	var searchObj = new Object();
	searchObj.categories = catr.toString();
	sendYears(searchObj);
}

//Sends the request for update the map
function sendYears(searchObject){
//Ajax call, followed by update code
	$.ajax({
		type:"GET",
		data: searchObject,
		url: "phplib/geoquery.php",
		beforeSend: function(xhr){
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		},

	}).done(function(data){
		var states = JSON.parse(data);
		//gets the block size
		var maxCount=0;
		for (i=0;i<states.length; i++) {
			if (parseInt(states[i].count)>maxCount) {
				maxCount=parseInt(states[i].count);
			}
			if (parseInt(states[i].count)>max_key_timelapse) {
				max_key_timelapse=parseInt(states[i].count);
			}
		}
		//Hack to fix things if the maximum is less than five
		//Math.floor is probably not the best call here - maybe even for this simple split
		//Probably needs to be a better way of dividing up the blocks automatically.
		if(maxCount < 5){
			maxCount = 5;
		}
		var countBlockSize;
		if (!timelapse_running) {
			countBlockSize=Math.floor(maxCount/5);
		} else {
			countBlockSize=Math.floor(max_key_timelapse/5);
		}

		//updates each state colour and adds a name-based mousehover handler
		$(document).ready(function(){
			//Updates the in-page key. Hack added to deal with countBlockSize of one,
			//which would otherwise have ugly "1-1" format.
			if(countBlockSize>1){
				$("#key1").html("1-"+countBlockSize);
				$("#key2").html(((countBlockSize)+1)+"-"+(countBlockSize*2));
				$("#key3").html(((countBlockSize*2)+1)+"-"+(countBlockSize*3));
				$("#key4").html(((countBlockSize*3)+1)+"-"+(countBlockSize*4));
				if (!timelapse_running) {
					$("#key5").html(((countBlockSize*4)+1)+"-"+(maxCount));
				} else {
					$("#key5").html(((countBlockSize*4)+1)+"-"+(max_key_timelapse));
				}
			}else{
				$("#key1").html("1");
				$("#key2").html("2");
				$("#key3").html("3");
				$("#key4").html("4");
				$("#key5").html("5");
			}

			$.each(states, function(i, v){
				var svg=document.getElementById('usamapsvg');
				var svgDoc=svg.contentDocument;
				var state=svgDoc.getElementById(states[i].name);
				var nameStates;

				for (k=0;k<usaStates.length;k++){
					for (j=0;j<usaStates[k].length;j++){
						if (usaStates[k][1]==states[i].name){
							nameStates=usaStates[k][0];
						}
					}
				}

					if (states[i].count<=maxCount && states[i].count>=((countBlockSize*4)+1)){
						$(state).css("fill", "#a50f15");
					}else if(states[i].count<=countBlockSize*4 && states[i].count>=((countBlockSize*3)+1)){
						$(state).css("fill", "#de2d26");
					}else if(states[i].count<=countBlockSize*3 && states[i].count>=((countBlockSize*2)+1)){
						$(state).css("fill", "#fb6a4a");
					}else if(states[i].count<=countBlockSize*2 && states[i].count>=((countBlockSize)+1)){
						$(state).css("fill", "#fcae91");
					}else if(states[i].count<=countBlockSize && states[i].count>= 1){
						$(state).css("fill", "#fee5d9");
					}else{
						console.log(countBlockSize);
						$(state).css("fill", "#cccccc");
					}

				$(state).bind('mouseenter', function(event){
					event.preventDefault();
					$("#description").css("display", "block");
					$("#description").html(nameStates+"<br>Number of events: "+states[i].count);
				});
				
				//Creates a bind when clicking on any state on the map that loads the table browsing page with the required values
				$(state).bind("click", function(event) {
					//Hack for states with composite names (like New York, New Mexico...)
					if (nameStates.split(" ").length > 3) {
						var loc = nameStates.split(" ")[2] + "_" + nameStates.split(" ")[3];
						location.assign("/browse-uspv.html?location=" + loc + "&startdate=" + curInitYear + "&enddate=" + curEndYear);
					} else {
						location.assign("/browse-uspv.html?location=" + nameStates.split(" ")[2] + "&startdate=" + curInitYear + "&enddate=" + curEndYear);
					}
				});
			});
			
			//Add the functionality to the slider
			addSliderFunctions();
			
			//adds mouse handler for states not in JSON
			var estado;
			var test;
			for (k=0;k<usaStates.length;k++){
				test=false;
				for (i=0;i<states.length;i++){
					estado=states[i].name;
					if (usaStates[k][1]==estado){
						test=true;
					}
				}
				if (test==false){
					var statec=usaStates[k][0];
					var svg=document.getElementById('usamapsvg');
					var svgDoc=svg.contentDocument;
					var state=svgDoc.getElementById(usaStates[k][1]);
					$(state).css("fill", "#cccccc");

					$(state).bind ("mouseenter",{msg: statec}, function(event){
						event.stopPropagation();
						$("#description").css("display", "block");
						$("#description").html(event.data.msg+"<br>Number of events: 0");
					});
				}
			}
		});
	});
}

//Add the functionality to the sliders
function addSliderFunctions() {
	$("#year-range").slider({
		range: true,
		min: initYear,
		max: endYear,
		values: [ $("#startDate").val(), $("#endDate").val() ],
		slide: function(event, ui){
			$("#startDate").val(ui.values[0]);
			$("#endDate").val(ui.values[1]);
			curInitYear = $("#startDate").val();
			curEndYear = $("#endDate").val();
			$('.ui-slider-handle:first').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + ui.values[0] + '</div></div>');
			$('.ui-slider-handle:last').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + ui.values[1] + '</div></div>');
		},
		stop: function(event, ui) {
			var searchValues = new Object();
			searchValues.startDate=document.getElementById("startDate").value;
			searchValues.endDate=document.getElementById("endDate").value;
			searchValues.categories = categories.toString();
			sendYears(searchValues);
		}
		});
	$( ".ui-slider-handle" ).mouseleave(function() {
		$('.ui-slider-handle').html("");
	});
	$( ".ui-slider-handle" ).mouseenter(function() {
		var value = $( "#year-range" ).slider( "option", "values" );
		$('.ui-slider-handle:first').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + value[0] + '</div></div>');
		$('.ui-slider-handle:last').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + value[1] + '</div></div>');
	}); 
}
