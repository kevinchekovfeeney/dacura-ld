//Rosa's visualisation code
$(document).ready(function(){
	sendYears();
	$(".button").click(function(startDate, endDate){
		var searchValues = new Object();
			searchValues.startDate=document.getElementById("startDate").value;
			searchValues.endDate=document.getElementById("endDate").value;
		sendYears(searchValues);
	});
})

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
		}
		//Hack to fix things if the maximum is less than five
		//Math.floor is probably not the best call here - maybe even for this simple split
		//Probably needs to be a better way of dividing up the blocks automatically.
		if(maxCount < 5){
			maxCount = 5;
		}
		var countBlockSize=Math.floor(maxCount/5);

		//updates each state colour and adds a name-based mousehover handler
		$(document).ready(function(){
			//Updates the in-page key. Hack added to deal with countBlockSize of one,
			//which would otherwise have ugly "1-1" format.
			if(countBlockSize>1){
				$("#key1").html("1-"+countBlockSize);
				$("#key2").html(((countBlockSize)+1)+"-"+(countBlockSize*2));
				$("#key3").html(((countBlockSize*2)+1)+"-"+(countBlockSize*3));
				$("#key4").html(((countBlockSize*3)+1)+"-"+(countBlockSize*4));
				$("#key5").html(((countBlockSize*4)+1)+"-"+(maxCount));
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
			});

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
