var canvas, cWidth, cHeight, initialYear, endYear, parts, context, yYearLine, maxYearHeight, yearSlots, events = [], 
JSONSource, categories = [], YAxisText, XAxisText, categoryFilter = [], zoomed = false, legendStartX, legendStartY,
initialYear = 1780, endYear = 2010, yearSplit = 10, categoryColors = [], heightMultiplier, baseInitYear = initialYear, 
baseEndYear = endYear, baseYearSplit = yearSplit, canvasid, draggingLegend, startLegendXDrag, startLegendYDrag, 
legendStartX = 60, legendStartY = 30, tooltipShowTime, tooltipText, initialGap = 50, maxBarHeight = 0, highestBarTo;
$(function() { setSlider(); });

//Contains the basic setup of the graph. Can be changed by the user
function loadConfig() {
	//Basic graph configuration, change whatever you need to change//
	//The id of the canvas element on the HTML DOM where the graph will be displayed
	canvasid = "visualization";
	//The path where the JSON will be loaded from
	JSONSource = "media/json/uspv.php";
	//Name to display on the X axis
	YAxisText = "Number of Events";
	//Name to display on the Y axis
	XAxisText = "Years";
	//Define the color of the bar for each category
	categoryColors["riot"] = "#DEA345";
	categoryColors["assassination"] = "#DE7345";
	categoryColors["lynching"] = "#3CC9B6";
	categoryColors["rampage"] = "#7070EB";
	categoryColors["terrorism"] = "#BECC3B";
	categoryColors["insurrection"] = "#B6B8BA";
	//Time (in ms) that it takes for the help tooltip to appear next to the mouse when it is not moving
	tooltipShowTime = 500;
	//Define to which point the largest bar will be extended to (starting from 0 to cHeight-yYearLine)
	highestBarTo = 50;
}

//Configs the slider
function setSlider() {
$("#year-range").slider({
	range: true,
	min: baseInitYear,
	max: baseEndYear,
	step: 10,
	values: [ initialYear, endYear ],
	slide: function(event, ui){
		initialYear = ui.values[0];
		endYear = ui.values[1];
		$('.ui-slider-handle:first').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + ui.values[0] + '</div></div>');
		$('.ui-slider-handle:last').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + ui.values[1] + '</div></div>');
		initCanvas();
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

//Initializes the canvas, loading the config, setting the variables, obtaining the data (if necessary) and drawing it all on the canvas
function initCanvas() {
	//Load configuration
	loadConfig();
	canvas = document.getElementById(canvasid);
	canvas.addEventListener("mousemove", graphOnHover, false);
	canvas.addEventListener("mousedown", graphOnMouseDown, false);
	canvas.addEventListener("mouseup", graphOnMouseUp, false);
	
	//Set canvas size
	canvas.width = getWidthLeftOverPercent(2);
	canvas.height = getHeightLeftOverPercent(20);
	
	//Define canvas variables
	cWidth = canvas.width-initialGap;
	cHeight = canvas.height;
	context = canvas.getContext("2d");
	
	//Other graphic-related variables
	parts = (endYear-initialYear)/yearSplit;
	yYearLine = 70.5;
	maxYearHeight = [];
	yearSlots = [];
	draggingLegend = false;
	
	//Register the mouse movement for display the tooltip
	$(canvas).on('mousemove', function(e){
		$('#tooltip').css({
		   left:  e.pageX+15,
		   top:   e.pageY,
		   visibility:	"hidden"
		});
		$('#tooltip').html("");
	});
	var timeout;
	$(canvas).on("mousemove", function(e){
		clearTimeout(timeout);
		timeout = setTimeout(function(){if (tooltipText != null && e.pageX < cWidth-100) {$('#tooltip').html(tooltipText); $('#tooltip').css("visibility", "visible")}}, tooltipShowTime);
	});
	$(canvas).on("mouseout", function(e) {
		clearTimeout(timeout);
	});
	
	//Load the data for generate the graph
	loadData();
}

//Read the external JSON and load the data on the graph, skip if already loaded
function loadData() {
	if (events['loaded'] == null) {
		$.getJSON(JSONSource, function(data) {
			var items = [];
			$.each(data, function(key, val) {
				var count = 0;
				$.each(val['events'], function(ev, dt) {
					events[count] = [];
					events[count]['category'] = dt['title'];
					events[count]['year'] = parseInt(parseInt(dt['startdate'])/10)*10;
					events[count]['exact_year'] = parseInt(dt['startdate']);
					count++
				});
			});
			//Set a flag for avoid reloading events when resizing the graph
			events['loaded'] = true;
			//Draws the data from the JSON into the graph
			drawData();
		});
	} else {
		//Draws the data from the JSON into the graph, if here, the data wasnt reloaded because the graph was resized
		drawData();
	}
}

//Draws everything on the canvas
function drawData() {
	//Hide the slider if the graph is zoomed
	if(zoomed) { $("#year-range").hide(); } else { $("#year-range").show(); }
	//Clear the previous content of the canvas (if any)
	context.fillStyle = "white";
	context.fillRect(0, 0, cWidth+initialGap, cHeight);
	//Drawing the timeline part below//
	//Draw the lines
	context.beginPath();
	context.moveTo(initialGap, cHeight-yYearLine);
	context.lineTo(cWidth+initialGap, cHeight-yYearLine);
	context.stroke();
	//Divide the line
	for (i = 0; i <= parts; i++) {
		context.moveTo(parseInt(cWidth/parts*i+(cWidth/parts)/2)+0.5+initialGap, cHeight-yYearLine);
		context.lineTo(parseInt(cWidth/parts*i+(cWidth/parts)/2)+0.5+initialGap, cHeight-yYearLine+10);
	}
	//Draw the year numbers
	context.textAlign = "center";
	context.font = "bold "+(cWidth/140)+"px Calibri";
	context.fillStyle = "black";
	context.textBaseline = "middle";
	var xPos = 0;
	for (i = initialYear; i <= endYear; i+=yearSplit) {
		if (zoomed) {
			context.fillText(i, initialGap+cWidth/parts*xPos+((cWidth/parts)/2), cHeight-yYearLine+30);
		} else {
			context.fillText(i + "-" + (i+(yearSplit-1)), initialGap+cWidth/parts*xPos+((cWidth/parts)/2), cHeight-yYearLine+30);
		}
		maxYearHeight[i] = cHeight-yYearLine;
		yearSlots[i] = xPos;
		xPos++;
	}
	//Stroke all the previous paths
	context.lineWidth = 2;
	context.strokeStyle = "#000000";
	context.stroke();

	var ev = [], count = 0;
	//Sort the amount of categories on every year range
	for (i = 0; i < events.length; i++) {
		if (ev[events[i]['category']] == null) { 
			ev[events[i]['category']] = [];
			for (y = initialYear; y <= endYear; y+=yearSplit) {
				if (zoomed) {
					if (events[i]['exact_year'] == y) {
						ev[events[i]['category']][y.toString()] = 1;
					} else {
						ev[events[i]['category']][y.toString()] = 0;
					}
				} else {
					if (events[i]['year'] == y) {
						ev[events[i]['category']][y.toString()] = 1;
					} else {
						ev[events[i]['category']][y.toString()] = 0;
					}
				}
			}
			categories[count] = events[i]['category'];
			count++;
		} else { 
			for (y = initialYear; y <= endYear; y+=yearSplit) {
				if (zoomed) {
					if (events[i]['exact_year'] == y) {
						ev[events[i]['category']][y.toString()] += 1;
					}
				} else {
					if (events[i]['year'] == y) {
						ev[events[i]['category']][y.toString()] += 1;
					}
				}
			}
		}
	}
	
	//Reset the highest bar length (remove for avoid big bars when zooming)
	maxBarHeight = 0;
	//Get the highest bar
	for (y = initialYear; y <= endYear; y+=yearSplit) {
		var count = 0;
		for (i = 0; i < categories.length; i++) {
			count += ev[categories[i]][y.toString()];
			console.log("Year: " + y + ", category: " + categories[i] + ", count: " + ev[categories[i]][y.toString()]);
		}
		console.log("Count: " + count);
		if (count > maxBarHeight) {
			maxBarHeight = count;
		}
	}
	
	//Set the height multiplier according to the biggest bar on the graph
	heightMultiplier = (cHeight-yYearLine-highestBarTo)/maxBarHeight;
	
	//Draw the amount lines
	drawAmounts();
	
	//Draw the bars
	for (i = 0; i < categories.length; i++) {
		//Initialize the filter in case is not already initialized
		if (categoryFilter[categories[i]] == null) { categoryFilter[categories[i]] = true; }
		for (y = initialYear; y <= endYear; y+=yearSplit) {
			if (ev[categories[i]][y.toString()] > 0) {
				//Draw the bars that are enabled on the filter
				if (categoryFilter[categories[i]]) { drawBar(y, categories[i], getCatColor(categories[i]), ev[categories[i]][y.toString()]*heightMultiplier); }
			}
		}
	}
	
	//Draw axis text
	drawAxisText();
	//Draw the legend
	drawLegend();
	
}

//Obtains the color of the category requested, returns white if the category is not defined
function getCatColor(cat) {
	if (categoryColors[cat] != null) {
		return categoryColors[cat];
	}
	return "white";
}

function getWidthLeftOverPercent(per) {
	return window.innerWidth-((window.innerWidth*per)/100);
}

function getHeightLeftOverPercent(per) {
	return window.innerHeight-((window.innerHeight*per)/100);
}

//Draws the draggable legend and its components
function drawLegend() {
	//Draw the surrounding box//
	context.lineWidth = 2;
	context.setLineDash([0]);
	//Draw the shadow of the box
	context.fillStyle = "#CECECE";
	context.fillRect(legendStartX+5, legendStartY+5, cWidth/7, categories.length*26+35);
	//Draw the background and the border of the box
	context.strokeStyle = "black";
	context.fillStyle = "white";
	context.fillRect(legendStartX, legendStartY, cWidth/7, categories.length*26+35);
	context.strokeRect(legendStartX, legendStartY, cWidth/7, categories.length*26+35);
	//Draw the legend title
	context.textBaseline = "middle";
	context.textAlign = "center";
	context.fillStyle = "black";
	context.font = 'bold '+(cWidth/90)+"px Calibri";
	context.fillText("Filter by category", legendStartX+((cWidth/7)/2), legendStartY+15);
	//Draw the categories inside the box//
	for (i = 0; i < categories.length; i++) {
		//Draw the text describing the category
		context.textBaseline = "middle";
		context.textAlign = "left";
		context.fillStyle = "black";
		context.font = (cWidth/100)+"px Calibri";
		context.fillText(categories[i], legendStartX+50, legendStartY+15+25*i+30);
		//Draw the line with the color belonging to that category
		context.beginPath();
		context.moveTo(legendStartX+5, legendStartY+15+25*i+30);
		context.lineTo(legendStartX+35, legendStartY+15+25*i+30);
		context.lineWidth = 10;
		context.strokeStyle = getCatColor(categories[i]);
		context.stroke();
	}
	context.beginPath();
	//Draw the checkbox filter for each category
	for (i = 0; i < categories.length; i++) {
		//Draw the text describing the category
		context.lineWidth = 1;
		context.strokeStyle = "black";
		context.strokeRect(cWidth/7-30+legendStartX, i*26+5+legendStartY+30, 15, 15);
		//Mark or not the checkboxes depending on the state of the filter
		if (categoryFilter[categories[i]]) {
			context.moveTo(cWidth/7-30+legendStartX+3, i*26+5+legendStartY+3+30);
			context.lineTo(cWidth/7-30+legendStartX+12, i*26+5+legendStartY+12+30);
			context.moveTo(cWidth/7-30+legendStartX+12, i*26+5+legendStartY+3+30);
			context.lineTo(cWidth/7-30+legendStartX+3, i*26+5+legendStartY+12+30);
		} else {
			context.fillStyle = "white";
			context.fillRect(cWidth/7-30+legendStartX, i*26+5+legendStartY+30, 15, 15);
			context.lineWidth = 1;
			context.strokeStyle = "black";
			context.strokeRect(cWidth/7-30+legendStartX, i*26+5+legendStartY+30, 15, 15);
		}
	}
	//Stroke the X marks (if any)
	context.stroke();
	
}

//Perform click operations
function graphOnClick(data) {
	//Obtain the mouse coords
	var pos = getCursorPosition(data);
	//Check if the click was performed or not on the legend
	if (pos[0] >= legendStartX && pos[1] >= legendStartY && pos[0] <= legendStartX+cWidth/7 && pos[1] <= legendStartY+(categories.length*26+15)+100) {
		//Check on which checkbox the click took place
		if (pos[0] >= legendStartX+(cWidth/7-30) && pos[0] <= legendStartX+(cWidth/7-30+15)) {
			for (i = 0; i < categories.length; i++) {
				 if (pos[1] >= i*26+5+legendStartY+30 && pos[1] <= i*26+5+legendStartY+30+15) {
					if (categoryFilter[categories[i]]) {
						categoryFilter[categories[i]] = false;
					} else {
						categoryFilter[categories[i]] = true;
					}
					//Reload the canvas on every filter change
					drawData();
				 }
			}
		}
	} else { //Clicked anywhere else but the legend
		var xPos = 0;
		for (i = initialYear; i <= endYear; i+=yearSplit) {
			if (pos[0] > cWidth/parts*xPos+initialGap && pos[0] < cWidth/parts*xPos+cWidth/parts+initialGap) {
				//Clicked out of the zoom button, so load the timeline view
				if (pos[1] >= cWidth/80.5) {
					window.location.assign("timeline-uspv.php?year="+i);
				} else {
					//Clicked on the zoom buttons, zooming in our out depending on the current state of the zoom
					if (zoomed) {
						//Change the state of the zoom
						zoomed = false;
						//Reset the initial/end years and the year splitting
						initialYear = $("#year-range").slider("option","values")[0];
						endYear = $("#year-range").slider("option","values")[1];
						yearSplit = baseYearSplit;
						//Reload the whole canvas
						initCanvas();
						//Get out of the loop
						break;
					} else {
						//Change the state of the zoom
						zoomed = true;
						//Set the initial year to the selected one and the initial year to the last of the range selected
						initialYear = i;
						endYear = i+yearSplit;
						//Change the year splitting to individual-year
						yearSplit = 1;
						//Reload the whole canvas
						initCanvas();
						//Get out of the loop
						break;
					}
				}
			}
			xPos++;
		}
	}
}

//Perform operations while the mouse is clicked but not released (drag and drop operations mostly)
function graphOnMouseDown(data) {
	//Obtain the mouse coords
	var pos = getCursorPosition(data);
	//Check if the click was performed or not on the legend
	if (pos[0] >= legendStartX && pos[1] >= legendStartY && pos[0] <= legendStartX+cWidth/7 && pos[1] <= legendStartY+(categories.length*26+15)+100) {
		//Start drag and drop the legend
		if (!(pos[0] >= legendStartX+(cWidth/7-30) && pos[0] <= legendStartX+(cWidth/7-30+15))) {
			draggingLegend = true;
			if (startLegendXDrag == null) { 
				startLegendXDrag = pos[0]-legendStartX;
			}
			if (startLegendYDrag == null) {
				startLegendYDrag = pos[1]-legendStartY;
			}
		}
	}
}

//Perform operations after the mouse click has been released (like a normal click)
function graphOnMouseUp(data) {
	//Obtain the mouse coords
	var pos = getCursorPosition(data);
	if (draggingLegend) {
		draggingLegend = false;
		legendStartX = (startLegendXDrag + (pos[0] - startLegendXDrag))-startLegendXDrag;
		legendStartY = (startLegendYDrag + (pos[1] - startLegendYDrag))-startLegendYDrag;
		drawData();
	} else {
		graphOnClick(data);
	}
	startLegendXDrag = null;
	startLegendYDrag = null;
}

//Perform operations when moving the mouse around the graph
function graphOnHover(data) {
	//Obtain the mouse coords
	var pos = getCursorPosition(data);
	var xPos = 0;
	//Draw the line from the top of the canvas to the end of every column depending on which column the mouse is on at that moment
	//Clear the other lines by paining a blank line over all of the non-selected columns
	for (i = initialYear; i <= endYear; i+=yearSplit) {
		//Remove all the previously-drawn lines and zoom buttons
		context.beginPath();
		context.setLineDash([0]);
		context.moveTo(parseInt(cWidth/parts*xPos+((cWidth/parts)/2))+0.5+initialGap, cWidth/80);
		context.lineTo(parseInt(cWidth/parts*xPos+((cWidth/parts)/2))+0.5+initialGap, maxYearHeight[i]-1.5);
		context.lineWidth = 2;
		context.strokeStyle = "#FFFFFF";
		context.stroke();
		context.fillStyle = "#FFFFFF";
		context.fillRect(cWidth/parts*xPos+initialGap, 0, cWidth/parts, cWidth/75);
		//Draw the currently-selected line
		if (pos[0] > cWidth/parts*xPos+initialGap && pos[0] < cWidth/parts*xPos+cWidth/parts+initialGap) {
			context.lineWidth = 1;
			context.strokeStyle = "#000000";
			//Draw the zoom button
			context.beginPath();
			context.arc(parseInt(cWidth/parts*xPos+((cWidth/parts)/2))+0.5+initialGap,0,cWidth/80.5,0,Math.PI);
			context.fillStyle = (pos[1] <= cWidth/80.5)?"#CECECE":"#EFEFEF";
			context.stroke();
			context.fill();
			context.fillStyle = "#000000";
			context.textBaseline = "middle";
			context.textAlign = "center";
			context.font = (cWidth/90)+'px Calibri';
			context.fillText((zoomed)?"-":"+", parseInt(cWidth/parts*xPos+((cWidth/parts)/2))+0.5+initialGap,7)
			//Draw the selecting line			
			context.setLineDash([2]);
			context.beginPath();
			context.moveTo(parseInt(cWidth/parts*xPos+((cWidth/parts)/2))+0.5+initialGap, cWidth/80);
			context.lineTo(parseInt(cWidth/parts*xPos+((cWidth/parts)/2))+0.5+initialGap, maxYearHeight[i]-2);
			context.stroke();
		} 
		xPos++;
	}

	//Update legend's position while its being dragged
	if (draggingLegend) {
		legendStartX = (startLegendXDrag + (pos[0] - startLegendXDrag))-startLegendXDrag;
		legendStartY = (startLegendYDrag + (pos[1] - startLegendYDrag))-startLegendYDrag;
		drawData();
	} else {
		drawLegend()
	}
	//Change tooltips texts
	if (pos[0] >= legendStartX && pos[1] >= legendStartY && pos[0] <= legendStartX+cWidth/7 && pos[1] <= legendStartY+(categories.length*26+15)) {
		tooltipText = "Drag and drop the legend box for move it around</br>or click on a checkbox to hide/show each label on the graph";
	} else {
		if (pos[1] >= cWidth/80.5) {
			tooltipText = "Click to open the timeline view with the selected year/range";
		} else {
			tooltipText = "Click to zoom in/out";
		}
	}
}

//Draw the text on the X and Y axis of the graph
function drawAxisText() {
	context.textAlign = "center";
	context.textBaseline = "middle";
	context.fillStyle = "black";
	context.font = (cWidth/110)+"px Calibri";
	context.fillText(XAxisText, cWidth/2, cHeight-15);
	context.save();
	context.rotate(-0.5*Math.PI);
	context.fillText(YAxisText, -cHeight/2, 13);
	context.restore();
}

//Draws the bars on the graph
function drawBar(year, text, color, height) {
	//Set the brush color for the bar
	context.fillStyle = color;
	context.setLineDash([0]);
	context.lineWidth = 2;
	//Draw the bar itself
	context.fillRect(cWidth/parts*yearSlots[year]+initialGap+1, parseInt(((maxYearHeight[year] == null)?cHeight-yYearLine-height:maxYearHeight[year]-height))+0.5, cWidth/parts, height);
	//Draw the bar border
	context.strokeStyle = "black";
	context.strokeRect(cWidth/parts*yearSlots[year]+initialGap+1, parseInt(((maxYearHeight[year] == null)?cHeight-yYearLine-height:maxYearHeight[year]-height))+0.5, cWidth/parts, height);
	//Draw the description text on the bar if the bar is tall enough
	if (height >=15) {
		context.font = ((cWidth/100)-parts/7)+'px Calibri';
		context.fillStyle = "black";
		context.textBaseline = "middle";
		context.fillText(text, cWidth/parts*yearSlots[year]+((cWidth/parts)/2)+initialGap, ((maxYearHeight[year] == null)?cHeight-yYearLine-height:maxYearHeight[year]-height)+(height/2));
	}
	//Set the current height of the year (for stack more bars on it)
	maxYearHeight[year] = parseInt(((maxYearHeight[year] == null)?cHeight-yYearLine-height:maxYearHeight[year]-height))+0.5;
}

//Calculates and draws the amount lines (grey horizontal lines) on the graph
function drawAmounts() {
	context.beginPath();
	context.lineWidth = 1;
	context.setLineDash([2]);
	context.font = (cWidth/140)+"px Calibri";
	context.strokeStyle = "#CECECE";
	context.fillStyle = "#000000";
	var counter = 0;
	for (i = highestBarTo; i < cHeight-yYearLine; i++) {
		counter++;
	}
	var lastAmount = 0;
	var percent = 100;
	for (i = counter+highestBarTo; i > 0; i--) {
		if (parseInt((percent*counter)/100) == i) {
			if (zoomed) { percent -= 24; } else { percent -= 10; }
			if (lastAmount != parseInt((cHeight-yYearLine-i)/heightMultiplier)) {
				context.moveTo(initialGap, i+0.5);
				context.lineTo(cWidth+initialGap, i+0.5);
				context.fillText(parseInt((cHeight-yYearLine-i)/heightMultiplier), initialGap-15, i+0.5);
			}
			lastAmount = parseInt((cHeight-yYearLine-i)/heightMultiplier);
		}
	}
	context.stroke();
}

//Obtains the mouse coordinates
function getCursorPosition(e) {
    var x;
    var y;
	if (e.pageX != undefined && e.pageY != undefined) {
		x = e.pageX;
		y = e.pageY;
	} else {
		x = e.clientX + document.body.scrollLeft +
				document.documentElement.scrollLeft;
		y = e.clientY + document.body.scrollTop +
				document.documentElement.scrollTop;
	}
	x -= canvas.offsetLeft;
    y -= canvas.offsetTop;
	cell = [x, y];
    return cell;
}