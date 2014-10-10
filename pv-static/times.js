function loadjscssfile(filename, filetype){

		removejscssfile(filename,filetype);//remove if they exist, when you reload a bookmarklet you don't want to stack files.

        if (filetype=="js")//if filename is a external JavaScript file
        {       
                var fileref=document.createElement('script')
                fileref.setAttribute("type","text/javascript")
                fileref.setAttribute("src", filename);
        }
        else if (filetype=="css"){ //if filename is an external CSS file
                var fileref=document.createElement("link")
                fileref.setAttribute("rel", "stylesheet")
                fileref.setAttribute("type", "text/css")
                fileref.setAttribute("href", filename)
        }
        if (typeof fileref!="undefined")
        {
                document.getElementsByTagName("head")[0].appendChild(fileref)
        }
}

function loadScript(url, callback)
{
    // adding the script tag to the head as suggested before
   var head = document.getElementsByTagName('head')[0];
   var script = document.createElement('script');
   script.type = 'text/javascript';
   script.src = url;

   // then bind the event to the callback function 
   // there are several events for cross browser compatibility
   script.onreadystatechange = callback;
   script.onload = callback;

   // fire the loading
   head.appendChild(script);
}

function removejscssfile(filename, filetype){
        var targetelement= "none";
         //determine element type to create nodelist from
        if(filetype=="js")
        {
                targetelement = "script";
        }else if(filetype=="css"){
        
                targetelement = "link";
        }
        
        var targetattr = "none";
         //determine corresponding attribute to test for
        if(filetype=="js")
        {
                targetattr = "src";
        }else if(filetype=="css"){
        
                targetattr = "href";
        }
        
        var allsuspects=document.getElementsByTagName(targetelement)
        
        for (var i=allsuspects.length; i>=0; i--){ //search backwards within nodelist for matching elements to remove
                if (allsuspects[i] && allsuspects[i].getAttribute(targetattr)!=null && allsuspects[i].getAttribute(targetattr).indexOf(filename)!=-1)
                {
                        allsuspects[i].parentNode.removeChild(allsuspects[i]) //remove element by calling parentNode.removeChild()
                }
         }
}




function tidyTimesPage(){
	
}


function doTimesStuff(){
	$('#header').hide();
	$('#navbar').hide();
	var self = this;
	$('body').append("<div id='dacura-times-search'>Search Phrase: <input size=50 type='text' id='dacura-times-search-string'></input><br>" +
			"From Year: <input type='text' id='dacura-times-from' size=4> To Year: <input type='text' id='dacura-times-to' size=4> " +
			"<br><input type='submit' value='go' id='dacura-times-submit'><div id='dacura-times-message'></div></div>");
	$('#dacura-times-search').dialog({width: 600, title: "Extract Candidates"}).css('z-index', 999999);
	$('#dacura-times-submit').button().click(function(e){
		e.preventDefault();
		var txt = $('#dacura-times-search-string').val();
		var from = $('#dacura-times-from').val();
		var to = $('#dacura-times-to').val();
		if((txt == "") || (from == "") || (parseInt(from,10) < 1785 || parseInt(from,10) > 2014)){
			alert("You must enter a search term and a valid year into the from box!");
		}
		else {
			self.sendSearchQuery(txt, from, to);
		}
	});

	//tidyTimesPage();
	//sendSearchQuery();
	//var dw = new dacura_widget;
	//dw.drawEmpty({width: 500, title: "Political Violence Event Report"});
}

function loadScripts()
{
	loadScript("http://tcdfame.cs.tcd.ie/dacura/jquery-1.9.1.min.js", function(){
		loadScript("http://tcdfame.cs.tcd.ie/dacura/jquery-ui-1.10.2.custom.min.js", function(){
			loadScript("http://tcdfame.cs.tcd.ie/dacura/json2.js", function(){
				loadScript("http://tcdfame.cs.tcd.ie/dacura/widget.js", function(){
					doTimesStuff();
				});
			});
		});
	});
	loadjscssfile("http://tcdfame.cs.tcd.ie/dacura/jquery-ui-1.10.2.custom.min.css", "css");
	loadjscssfile("http://tcdfame.cs.tcd.ie/dacura/widget.css", "css");
}

var hrefs = new Array();
var proc = 0;
var dacura_api_url = "http://tcdfame.cs.tcd.ie/dacura/ajaxapi.php";
var added_articles = 0;
var duplicate_articles = 0

function parseNextPage(urlbase, offset, end){
	next_url = urlbase + offset;
	//$('#dacura-times-message').append("Getting "+ next_url + "<br>");	
	$.get(next_url).done(function(data) {
		pullHrefsFromHTML(data);
		offset = offset + 20;
	    if(offset < end){
	    	parseNextPage(urlbase, offset, end);
	    }
	    else {
			//$('#dacura-times-message').append(proc + " articles indexed<br>");
			suckUpArticles(hrefs, 0);
	    }
	});
}

function pullHrefsFromHTML(data){
	$('ul.resultsListBox', data).each(function (i, e) {
		var vals = new Object();
		vals['url'] = $('p.articleTitle a', this).attr("href") + "&scale=1.00";
		vals['title'] = $('spcitation', this).text();
		vals['section'] = $('field[name="NNG"]', this).text();
		hrefs[proc] = vals;
		proc = proc+1;
	});
}

function sendSearchQuery(t, f, to){
	var args = { 
		prodId:"TTDA",
		searchType:"AdvancedSearchForm", 
		userGroupName: "tcd",
		dummy: "dummy",
		noOfRows: 1,
		method: "doSearch",
		"inputFieldValue(0)": t,
		"inputFieldName(0)": "tx",
		fuzzyEnabled: "true",
		dateIndices: "da",
		"limiterFieldValue(CL)" : "",	
		"limiterFieldValue(IL)" : "",	
		"limiterFieldValue(SI)": "",	
		"limiterFieldValue(gs)": "", 	
		"limiterFieldValue(ng)" : "",	
		"dateLimiterValue(da).dateMode":  2, 
		"dateLimiterValue(da).fromDay": "", 
		"dateLimiterValue(da).fromMonth" : "", 
		"dateLimiterValue(da).fromYear" : f,
		"fuzzyLevel(0)": "None",
		"fuzzyLevel(1)": "None", 
		"fuzzyLevel(2)": "None",
		"fuzzyLevel(999)": "None"

	};
	if(parseInt(to,10) >= 1785 && parseInt(to,10) <= 2014){
		args['dateLimiterValue(da).dateMode'] = 4;
		args['dateLimiterValue(da).toDay']= "";
		args['dateLimiterValue(da).toMonth']= "";
		args['dateLimiterValue(da).toYear']= to;
	}
	//alert(t + " from: " + f + " to: " + to);
	$.post("http://find.galegroup.com/ttda/advancedSearch.do", args)
	.done(function(data) {
		pullHrefsFromHTML(data);
    	var x = $('ul.prevNext1 li', data).text();
    	var bits = x.split( "of");
    	var res_count = bits[1].trim();	
    	$('#dacura-times-message').html("");
    	$('#dacura-times-message').append(res_count + " results returned<br>");
    	
	    var last_page_url = $('#lastPageDivisionBottom', data).attr("href");
	    if(last_page_url){
	    	var url_bits = last_page_url.split("currentPosition");
	    	var url_base = url_bits[0];
	    	parseNextPage(url_base, 21, res_count);
	    }
	    else {
			//$('#dacura-times-message').append(proc + " articles indexed<br>");
			 suckUpArticles(hrefs, 0);
	    }
		//$('#dacura-times-message').append($('spcitation', this).text() + "<br>");
		/*var next_index = 21;
		//now go through subsequent pages...
	    var last_page_url = $('#lastPageDivisionBottom', data).attr("href");
	    if(last_page_url){
	    	var url_bits = last_page_url.split("currentPosition");
	    	var next_url = url_bits[0] + "currentPosition=" + next_index;
	    	last = false;
	    	while(last == false){
		    	//$.get(next_url).done(){
	    		$('#dacura-times-message').append("Getting "+ next_url + "<br>");	
		    	//}
		    	if(next_url == last_page_url){
		    		last = true;
		    	}
		    	if(last == false){
		    		next_index = next_index + 20;
		    		next_url = url_bits[0] + "currentPosition=" + next_index;
		    	}
	    	}
	    }
		*/

	});		
}

function createJSONFromArticleHTML(data, existing_data){
	var canonical_url = "http://find.galegroup.com/ttda/infomark.do?tabID=T003&docPage=article&type=multipage&contentSet=LTO&version=1.0&docId=";
	bits = {};
	bits['title'] = existing_data['title'];
	bits['section'] = existing_data['section'];
	bits['date'] = $('#quickSearchForm input[name="workId"]', data).attr("value");
	bits['year'] = bits['date'].substr(bits['date'].length - 4);
	bits['img'] = $('img#fascimileImg', data).attr("src");
	bits['id'] = $('#resultsForm input[name="docId"]', data).attr("value");
	bits['article_url'] = canonical_url + bits.id;
	bits['page_url'] = $('#FlexViewBrowseIssueId', data).attr("href");
	bits['issue_url'] = $('#BrowseIssueId', data).attr("href");
	bits['citation'] = $('div.ct_LtoNewspaper', data).html();
	return bits;
}


function suckUpArticles(urls, ind){
	$('#dacura-times-message').append("<div id='sucking-progress'></div>");
	var next_article = urls[ind];
	$.get(next_article.url).done(function(data) {
		$('#sucking-progress').html("Retrieving article entitled: " + next_article.title + "....");
		var json = createJSONFromArticleHTML(data, next_article);
		$('#sucking-progress').append("Parsed....submitting...");
		$.post(dacura_api_url, {"action": "add_candidate", "candidate": JSON.stringify(json)})
			.done(function(data, textStatus, jqXHR) {
				x = ind + 1;
				if(jqXHR.status == 200){
					$('#dacura-times-message').append("success: " + json.id + jqXHR.status + "<br>");
					added_articles++;
				}
				else {
					duplicate_articles++;
				}
				if(x < urls.length){
					suckUpArticles(urls, x);
				}
				else {
					$('#dacura-times-message').append("Added " + added_articles + ", " + duplicate_articles + " Duplicates");
					resetDaState();
				}
			}
		)
		.fail(function (jqXHR, textStatus) {
			$('#dacura-times-message').append("fail: " + next_article.id + " " + textStatus + " [" + jqXHR.status + "]<br>");			
			$('#dacura-times-message').append("Added " + added_articles + ", " + duplicate_articles + " Duplicates");
			resetDaState();
		});
		//$('#dacura-times-message').append("ID: " + json.id + "<br>title: " + json.title + "<br>year: " + json.year + "<br>date: " + json.date + "<br>section: " + json.section + "<p>" + JSON.stringify(json) + "</p>");
		//x = ind + 1;
		//if(x < urls.length){
		//	suckUpArticles(urls, x);
		//}
		
	});
}

function resetDaState(){
	hrefs = new Array();
	proc = 0;
	added_articles = 0;
	duplicate_articles = 0;
}


loadScripts();
