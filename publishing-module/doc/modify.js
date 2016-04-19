$(document).ready(function() {
	$(".endnote").wrap("<div></div>");
	$("hr, #toc, #introduction, h2:contains('Abstract'), hr ~ p").wrapAll("<div></div>");
	$("div").wrapAll("<div id='wrapper' class='row-fluid'></div>");
	$("#wrapper").wrapAll("<div id='wrapper2' class='span6 offset2'></div>");
	$("#wrapper2").wrapAll("<div id='wrapper3' class='row-fluid content'></div>");
	$('#wrapper3').append("<div id='logos' class='row-fluid'><img class='logolode' src='http://lode.sourceforge.net/img/LODELogo.png' id='logoLode'><a href='http://kdeg.scss.tcd.ie/'><img class='img-rounded' src='kdeg.jpg'></a><a href='http://www.tcd.ie/'><img src='trinity.jpg'></a></div>");
	$('#logos').wrap("<div id='logos2' class='span3 offset1'></div>");
	$('body').append("<div id='photo'><img src='liberty.jpg' id='head'></div>");
	$('body').append("<div id='layerfloat' class='row-fluid top'></div>");
	$('#layerfloat').append("<div class='span6 offset2'><ul class='breadcrumb'><li><a href='../index.html'>Home</a><span class='divider'>/</span></li><li class='active'>Political Violence Vocabulary</li></ul><h1 id='mytitle'>Political Violence Vocabulary</h1></div>");
	$('body').append("<div id='layerfooter' class='row-fluid footer'></div>");
	$('#layerfooter').append("<div id='layerfooter' class='span8 offset2'><p><small>© Knowledge and Data Engineering Group (KDEG), School of Computer Science and Statistics, Trinity College Dublin</small></p><p><small>Icons by Monika Ciapala, from<a href='http://www.thenounproject.com/'>The Noun Project</a></small></p></div>");

	/*to remove a thing in the html code*/
	$(".head dt").remove(":contains('Other visualisation:')");
	$(".head a").remove(":contains('Ontology source')");
	$(".head a").remove(":contains('Machester Ontology Browser')");
});
