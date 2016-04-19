var catValue = "any";
var motValue = "any";
var startDateValue = 0;
var endDateValue = 0;
var locationValue = "";
var search = false;
//Download limit for avoid timeout issues
var downloadLimit = 800;
var initialYear = 1780, endYear = 2010;
var sparql_query = "";

function handleAjaxError(xhr, textStatus, error){
	if(textStatus==="timeout"){
		errortext = "<div class='alert alert-danger' style='width: 40%;'>The server has timed out. Please try again later.<br/><br/><a href='#' class='alert-link' onclick='location.reload();'>Reload</a></div>";
	}else if(textStatus==="parsererror"){
		errortext = "<div class='alert alert-danger' style='width: 40%;'>The server's response cannot be understood. Please try again later.<br/><br/><a href='#' class='alert-link' onclick='location.reload();'>Reload</a></div>"
	}else{
		errortext = "<div class='alert alert-danger' style='width: 40%;'>There is a problem with the server. Please try again later.<br/><br/><a href='#' class='alert-link' onclick='location.reload();'>Reload</a></div>";
	}

	errorbox = "<div id='errorbox'><p>" + errortext + "</p></div>";
	$("#result-table_wrapper").replaceWith(errorbox);
}

//This should be generalisable in a php file - do the same thing to get IDs to not-IDs as in x.php
$.fn.dataTableExt.sErrMode = 'throw';
$(document).ready(function() {
	$("#sparql_query").hide();
	readUrlParams();
	$('#results').show(1000);
	try{
		$('#result-table').dataTable({
			"bProcessing": true,
			"bFilter": false,
			"bServerSide": true,
			"bAutoWidth": false,
			"sAjaxSource": "phplib/search-uspv.php",
			"fnServerData": function(sSource, data, fnCallback){
				data.push(
					{"name": "search", "value": search},
					{"name": "category", "value": catValue},
					{"name": "motivation", "value": motValue},
					{"name": "startDate", "value": startDateValue},
					{"name": "endDate", "value": endDateValue},
					{"name": "location", "value": locationValue} 
				);
				
			    $.ajax({
			        "dataType": 'json',
			        "type": "GET",
			        "url": sSource,
			        "data": data,
			        "success": fnCallback,
			        "error": handleAjaxError 	        
			    });
			},
			"aoColumns": [
				{ "sTitle": "", "mData": null, "bSortable": false, "sWidth": "2%", "sClass": "expand" },
				{ "sTitle": "Date", "mData": "date", "sWidth": "8%" },
				{ "sTitle": "Category", "mData": "category", "sWidth": "14%" },
				{ "sTitle": "Motivation", "mData": "motivation", "sWidth": "16%" },
				{ "sTitle": "Location", "mData": "location", "sWidth": "14%" },
				{ "sTitle": "Fatalities", "mData": "fatalities", "sWidth": "14%", "sClass": "fatalities" },
				{ "sTitle": "", "mData": "id", "bSortable": false, "sWidth": "2%", "sClass": "pubby" },
				{ "sTitle": "Source", "mData": "source", "sWidth": "0%", "sClass": "source" },
				{ "sTitle": "Description", "mData": "description", "sWidth": "0%", "sClass": "description" }
			],
			"fnDrawCallback": function(oSettings, json) {
				$('td.expand').append('<img src="media/images/details_open.png" class="expander"/>');
				//Iterate through each row, creating the link to the event and removing the plain text
				$('td.pubby').each(function() {
					pubbylink = $(this).html();
					$(this).html("");
					$(this).append('<a target="_blank" href=' + pubbylink + '><img src="media/images/pubby_link.png" class="pubby_link"/></a>');
					
				});
				
				$(".source").hide();
				$(".description").hide();
				//on clicking the expand button: if there isn't a row there, gets the info from the hidden fields and makes one
				//otherwise, just a toggle
				$('.expander').bind('click', function(){
					parentRow = $(this).parent().parent();
					source = $(this).parent().nextAll(".source").html();
					description = $(this).parent().nextAll(".description").html();
					if(!$(parentRow).next().hasClass("expandedRow")){
						//get HTML
						rowHTML = '<td colspan="6"><table>';
							rowHTML += '<tr>';
								rowHTML += '<td>Description</td>';
								rowHTML += '<td colspan="5">';
									rowHTML += description;
								rowHTML += '</td>';
							rowHTML += '</tr>';
							rowHTML += '<tr>';
								rowHTML += '<td>Source</td>';
								rowHTML += '<td colspan="5">';
									rowHTML += source; 
								rowHTML += '</td>';
							rowHTML += '</tr>';
						rowHTML += '</table></td>';
						$(parentRow).after("<tr></tr>");
						$(parentRow).next().addClass("expandedRow");
						$(parentRow).next().html(rowHTML);
						this.src="media/images/details_close.png";
					}else{
						$(parentRow).next(".expandedRow").toggle();
						if($(this).attr("src")=="media/images/details_close.png"){
							this.src="media/images/details_open.png";
						}else{
							this.src="media/images/details_close.png";
						}
					}
				});
			}
		} );
	}catch(e){
		console.log(e);
	}
	//When clicking the search button, grabs relevant values to be passed to search
	$('#search-button').bind('click', function(){
		catValue = document.getElementById('category').value;
		motValue = document.getElementById('motivation').value;
		startDateValue = document.getElementById('start-date').value;
		endDateValue = document.getElementById('end-date').value;
		locationValue = document.getElementById('location').value;
		//Fix for states with space on its name, replacing it for an underscore
		locationValue = locationValue.replace(/ /g, "_");
		search = true;
		var test = $('#result-table').dataTable( {
			"bDestroy": true,
			"bRetrieve": true
		} );
		test.fnDraw();
	});
	
	$('#reset-button').bind('click', function(){
		this.form.reset();
		document.getElementById('end-date').disabled = true;
		$("#year-range").hide();
		$("#year-slider").show();
		$("#year-slider").slider("value", initialYear);
		$("#year-range").slider({ values: [ $('#start-date').val(), $('#end-date').val() ]});
	});
	
	$('#yearSelector').bind('click', function(){
		$("#year-range").hide();
		$("#year-slider").show();
		document.getElementById('end-date').disabled = true;
		document.getElementById('end-date').value = "";
		$("#year-slider").slider("value", $('#start-date').val());
		$("#year-range").slider({ values: [ $('#start-date').val(), $('#end-date').val() ]});
	});

	$('#rangeSelector').bind('click', function(){
		$("#year-range").show();
		$("#year-slider").hide();
		document.getElementById('end-date').disabled = false;
		document.getElementById('end-date').value = endYear;
		$("#year-range").slider({ values: [ $('#start-date').val(), $('#end-date').val() ]});
	});
	
	//Generate and download the CSV file
	$('#exportCSV').bind('click', function() {
		//Initially 1 to get the total count
		var amount = 1;
		var fData = "sEcho=1&iColumns=9&iDisplayStart=0&iDisplayLength=" + amount + "&iSortCol_0=0&sSortDir_0=asc&category=" + catValue + "&motivation=" + motValue + "&startDate=" + startDateValue + "&endDate=" + endDateValue + "&location=" + locationValue;
		$.ajax({
			"dataType": 'json',
			"type": "GET",
			"url": "phplib/search-uspv.php",
			"data": fData,
			"success": confirmDownload,
			"error": handleAjaxError 	        
		});
	});
	//Obtain the SPARQL query
	$('#showSPARQLQuery').bind('click', function() {
		//1 to get the query without a long load time
		var amount = 1;
		var fData = "sEcho=1&iColumns=9&iDisplayStart=0&iDisplayLength=" + amount + "&iSortCol_0=0&sSortDir_0=asc&category=" + catValue + "&motivation=" + motValue + "&startDate=" + startDateValue + "&endDate=" + endDateValue + "&location=" + locationValue;
		$.ajax({
			"dataType": 'json',
			"type": "GET",
			"url": "phplib/search-uspv.php",
			"data": fData,
			"success": showQuery,
			"error": handleAjaxError 	        
		});
	});
	
	//Keeps the search div visible when scrolling down on the table
	//Restores the position when reaching the top of the page
	$(window).scroll(function(e){ 
	  $search = $('#search');
	  $results = $('#results');
	  if (window.innerHeight > 530) {
		  if ($(this).scrollTop() > 112 && $search.css('position') != 'fixed'){ 
			$search.css({'position': 'fixed', 'top': '0px'}); 
			$results.css({'margin-left': '17%'});
		  }
		  if ($(this).scrollTop() < 112 && $search.css('position') == 'fixed'){ 
			$search.css({'position': 'relative'}); 
		  }
	  }
	});
	//Year slider
	$("#year-slider").slider({
		min: initialYear,
		max: endYear,
		value: $("#start-date").val(),
		slide: function(event, ui){
			$("#start-date").val(ui.value);
			$('#year-slider .ui-slider-handle').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + ui.value + '</div></div>');
		}});
		$( "#year-slider .ui-slider-handle" ).mouseleave(function() {
			$('#year-slider .ui-slider-handle').html("");
		});
		$( "#year-slider .ui-slider-handle" ).mouseenter(function() {
			var value = $( "#year-slider" ).slider( "option", "value" );
			$('#year-slider .ui-slider-handle:first').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + value + '</div></div>');
		}); 
		
	//Year range slider
	$("#year-range").slider({
		range: true,
		min: initialYear,
		max: endYear,
		values: [ $("#start-date").val(), $("#end-date").val() ],
		slide: function(event, ui){
				$("#start-date").val(ui.values[0]);
				$("#end-date").val(ui.values[1]);
				$('#year-range .ui-slider-handle:first').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + ui.values[0] + '</div></div>');
				$('#year-range .ui-slider-handle:last').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + ui.values[1] + '</div></div>');
			}
		});
		$( "#year-range .ui-slider-handle" ).mouseleave(function() {
			$('#year-range .ui-slider-handle').html("");
		});
		$( "#year-range .ui-slider-handle" ).mouseenter(function() {
			var value = $( "#year-range" ).slider( "option", "values" );
			$('#year-range .ui-slider-handle:first').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + value[0] + '</div></div>');
			$('#year-range .ui-slider-handle:last').html('<div class="tooltip top slider-tip"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + value[1] + '</div></div>');
		}); 
});

//Display the SPARQL query
function showQuery(data) {
	var results = JSON.parse(JSON.stringify(data));
	document.getElementById("sparql_query").innerHTML = results.search;
	$("#sparql_query").fadeIn("fast");
}

//Executed after counting the amount of entries, it is used for confirm that the user want to download the specified amount of entries
function confirmDownload(data) {
	var results = JSON.parse(JSON.stringify(data));
	var response;
	if (results.iTotalRecords > downloadLimit) {
		response = confirm("A total of " + results.iTotalRecords + " records should be downloaded\nbut currently we cannot provide more than " + downloadLimit + " records.\nOnly " + downloadLimit + " records will be downloaded.\nDo you want to continue anyway?");
		results.iTotalRecords = downloadLimit;
	} else {
		response = confirm("A total of " + results.iTotalRecords + " records will be downloaded.\nThis might take several minutes.\nDo you want to continue?");
	}
	
	if (response) {
		$("#exportCSV").button('loading');
		$("#downloadingBox").dialog({dialogClass: "no-close"});
		var amount = results.iTotalRecords;
		//Prepare the post parameters
		var fData = "sEcho=1&iColumns=9&iDisplayStart=0&iDisplayLength=" + amount + "&iSortCol_0=0&sSortDir_0=asc&category=" + catValue + "&motivation=" + motValue + "&startDate=" + startDateValue + "&endDate=" + endDateValue + "&location=" + locationValue;
		//Send the ajax request to obtain the data
		$.ajax({
			"timeout": 600000,
			"dataType": 'json',
			"type": "GET",
			"url": "phplib/search-uspv.php",
			"data": fData,
			"success": gatherCSVData,
			"error": function(e, t){$("#exportCSV").button('reset'); $("#downloadingBox").html("An error occurred while generating the data, please reload the page and try again<br>Error: " + t);} 	        
		});
}

//Executed after the data has been gathered from the JSON. Sorts the data and generates the CSV file format and content, then sends it to the download.php for the user to download it
function gatherCSVData(data) {
		//Starting the CSV file variables
		var csvContent = ""
		var filename = "uspv_" + (new Date().getTime()) + ".csv";
		var results = JSON.parse(JSON.stringify(data));
		//Obtain the keys from the JSON generated
		var keys = Object.keys(results.aaData[0]);
		//Save the keys on the first line of the file as the headers
		for (k = 0; k < keys.length; k++) {
			csvContent += keys[k];
			if (k < keys.length-1) {
				csvContent += ",";
			}
		}
		csvContent += "\n";
		//Iterate through all the results and place them on the proper columns
		for (i = 0; i < results.aaData.length; i++) {
			for (k = 0; k < keys.length; k++) {
				//Save the entry on the CSV file variable, replacing invalid characters for the CSV format
				csvContent += '"' + results.aaData[i][keys[k]].replace(/"/g, "'") + '"';
				if (k < keys.length-1) {
					csvContent += ",";
				}
			}
			csvContent += "\n";
		}
		//Save the results on a hidden form and submit the form to the download.php page so it prompts the user to download the file
		$("#name").val(filename);
		$("#content").val(csvContent);
		//hide the generating dialog
		$("#downloadingBox").dialog("close");
		$("#exportCSV").button('reset');
		$("#downloadHiddenForm").submit();
	}
}

//Read the parameters on the URL and fill the search fields accordingly to them
function readUrlParams() {
	$("#start-date").val(initialYear); 
	//Obtain the parameters from the URL
	params = urlObject({'url': document.URL});
	//Fill the fields of search data before sending the AJAX request
	if (params.parameters.startdate != undefined) { 
		startDateValue = params.parameters.startdate; 
		$("#start-date").val(params.parameters.startdate); 
	}
	if (params.parameters.enddate != undefined) { 
		endDateValue = params.parameters.enddate;
		$("#year-range").show(); 
		$("#year-slider").hide();
		$("#end-date").val(params.parameters.enddate); 
		$("#rangeSelector").prop("checked", true); 
		document.getElementById('end-date').disabled = false; 
	} else { $("#year-range").hide(); }
	if (params.parameters.location != undefined) { 
		locationValue = params.parameters.location; 
		$('#location option').filter(function () { return $(this).html() == params.parameters.location; }).prop('selected', true)
	}
	if ($("#end-date").val() != "") {
		$("#year-slider").hide();
		$("#year-range").show();
	}
}

//FROM: http://www.thecodeship.com/web-development/javascript-url-object/
function urlObject(options) {
    "use strict";
    /*global window, document*/

    var url_search_arr,
        option_key,
        i,
        urlObj,
        get_param,
        key,
        val,
        url_query,
        url_get_params = {},
        a = document.createElement('a'),
        default_options = {
            'url': window.location.href,
            'unescape': true,
            'convert_num': true
        };

    if (typeof options !== "object") {
        options = default_options;
    } else {
        for (option_key in default_options) {
            if (default_options.hasOwnProperty(option_key)) {
                if (options[option_key] === undefined) {
                    options[option_key] = default_options[option_key];
                }
            }
        }
    }

    a.href = options.url;
    url_query = a.search.substring(1);
    url_search_arr = url_query.split('&');

    if (url_search_arr[0].length > 1) {
        for (i = 0; i < url_search_arr.length; i += 1) {
            get_param = url_search_arr[i].split("=");

            if (options.unescape) {
                key = decodeURI(get_param[0]);
                val = decodeURI(get_param[1]);
            } else {
                key = get_param[0];
                val = get_param[1];
            }

            if (options.convert_num) {
                if (val.match(/^\d+$/)) {
                    val = parseInt(val, 10);
                } else if (val.match(/^\d+\.\d+$/)) {
                    val = parseFloat(val);
                }
            }

            if (url_get_params[key] === undefined) {
                url_get_params[key] = val;
            } else if (typeof url_get_params[key] === "string") {
                url_get_params[key] = [url_get_params[key], val];
            } else {
                url_get_params[key].push(val);
            }

            get_param = [];
        }
    }

    urlObj = {
        protocol: a.protocol,
        hostname: a.hostname,
        host: a.host,
        port: a.port,
        hash: a.hash.substr(1),
        pathname: a.pathname,
        search: a.search,
        parameters: url_get_params
    };

    return urlObj;
}