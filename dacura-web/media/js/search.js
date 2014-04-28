//Code for handling the display elements of browse-uspv.html

var catValue = "any";
var motValue = "any";
var startDateValue = 0;
var endDateValue = 0;
var locationValue = "";
var search = false;

//Error handling for AJAX
function handleAjaxError(xhr, textStatus, error){
	if(textStatus==="timeout"){
		errortext = "The server has timed out. Please try again later.";
	}else if(textStatus==="parsererror"){
		errortext = "The server's response cannot be understood. Please try again later."
	}else{
		errortext = "There is a problem with the server. Please try again later";
	}

	errorbox = "<div id='errorbox'><p>" + errortext + "</p></div>";
	$("#result-table_wrapper").replaceWith(errorbox);
}

//Main datatables code
$.fn.dataTableExt.sErrMode = 'throw';
$(document).ready(function() {
	$('#results').show(1000);
	$('#result-table').dataTable({
		"bProcessing": true,	//turns on the "processing" indicator while loading data
		"bFilter": false,		//disables filtering
		"bServerSide": true,	//enables server-side processing
		"bAutoWidth": false,	//disables automatic cell width
		"sAjaxSource": "phplib/search-uspv.php",	//source for server-side processing
		"fnServerData": function(sSource, data, fnCallback){	//data to send to the server for search function
			data.push(
				{"name": "search", "value": search},
				{"name": "category", "value": catValue},
				{"name": "motivation", "value": motValue},
				{"name": "startDate", "value": startDateValue},
				{"name": "endDate", "value": endDateValue},
				{"name": "location", "value": locationValue}
			);
		    $.ajax({	//the AJAX call the table makes
		        "dataType": 'json',
		        "type": "GET",
		        "url": sSource,
		        "data": data,
		        "success": fnCallback,	//handles what to do on success
		        "timeout": 15000,
		        "error": handleAjaxError
		    });
		},
		"aoColumns": [	//columns for the table
			{ "sTitle": "", "mData": null, "bSortable": false, "sWidth": "2%", "sClass": "expand" },
			{ "sTitle": "Date", "mData": "date", "sWidth": "8%" },
			{ "sTitle": "Category", "mData": "category", "sWidth": "14%" },
			{ "sTitle": "Motivation", "mData": "motivation", "sWidth": "16%" },
			{ "sTitle": "Location", "mData": "location", "sWidth": "14%" },
			{ "sTitle": "Fatalities", "mData": "fatalities", "sWidth": "14%", "sClass": "fatalities" },
			{ "sTitle": "Source", "mData": "source", "sWidth": "0%", "sClass": "source" },
			{ "sTitle": "Description", "mData": "description", "sWidth": "0%", "sClass": "description" }
		],
		"fnDrawCallback": function(oSettings, json) {
			//Handles what happens when the table is drawn/redrawn - adds an expand/contract toggle,
			//and inserts rows based on source and description.
			$('td.expand').append('<img src="media/images/details_open.png" class="expander">');
			$(".source").hide();
			$(".description").hide();
			//on clicking the expand button: if there isn't a row there, gets the info from the hidden fields and makes one
			//otherwise, just a toggle
			$('.expander').bind('click', function(){
				parentRow = $(this).parent().parent();
				source = $(this).parent().nextAll(".source").html();
				description = $(this).parent().nextAll(".description").html();
				if(!$(parentRow).next().hasClass("expandedRow")){
					//the HTML and source text is written here
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

	//When clicking the search button, grabs relevant values to be passed to search
	$('#search-button').bind('click', function(){
		catValue = document.getElementById('category').value;
		motValue = document.getElementById('motivation').value;
		startDateValue = document.getElementById('start-date').value;
		endDateValue = document.getElementById('end-date').value;
		locationValue = document.getElementById('location').value;
		search = true;
		//Redrawing the table on search
		var test = $('#result-table').dataTable( {
			"bDestroy": true,
			"bRetrieve": true
		} );
		test.fnDraw();
	});

	$('#reset-button').bind('click', function(){
		this.form.reset();
		document.getElementById('end-date').disabled = true;
	});

	$('#yearSelector').bind('click', function(){
		document.getElementById('end-date').disabled = true;
		document.getElementById('end-date').value = "";
	});

	$('#rangeSelector').bind('click', function(){
		document.getElementById('end-date').disabled = false;
	});
} );