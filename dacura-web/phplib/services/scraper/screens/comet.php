<div id='comet'></div>

<script type="text/javascript">
var url = "http://localhost/dacura/rest/seshat/0/scraper/comet";
//var xhr = $.ajaxSettings.xhr(); 
//xhr.multipart = true; 
//xhr.open('GET', url, true); 
//xhr.onreadystatechange = function() { 
//    if (xhr.readyState == 4) { 
 //       alert(xhr.responseText); 
 //   } 
  //  else if (xhr.readyState == 3){
//		$('#comet').html(xhr.responseText);
 //   }
//}; 
//xhr.send(null);


dacura.toolbox.modalSlowAjax(url, "testing slow ajax");

</script>
