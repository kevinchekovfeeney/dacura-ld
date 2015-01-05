<h1>Results</h1>

<div style="margin: 10px" class="results">
<table class='scraper-report'><tr><th>NGAs</th><th>Polities</th><th>Failures</th><th>Variables</th>
<th>Simple</th><th>Complex</th><th>Empty</th><th>Errors</th><th>Datapoints</th></tr>
<tr>
<td><?=$params['stats']['ngas']?></td>
<td><?=$params['stats']['polities']?></td>
<td><?=count($params['failures'])?></td>
<td><?=$params['stats']['total_variables']?></td>
<td><?=$params['stats']['total_variables'] - ($params['stats']['complex'] + $params['stats']['empty'])?></td>
<td><?=$params['stats']['complex']?></td>
<td><?=$params['stats']['empty']?></td>
<td><?=$params['stats']['errors']?></td>
<td><?=$params['stats']['lines']?></td>
</tr></table>

<?php if(count($params['failures']) > 0) {?>
	<h4>The following pages could not be scraped:</h4>
	<table class='scraper-report'><tr><th>NGA</th><th>Page</th><th>Failure</th></tr>
	<?php
	foreach($params['failures'] as $pf){
		echo "<tr><td>" . $pf[0] . "</td><td>" . $pf[1] . "</td><td>" . $pf[2] .":".$pf[3] ."</td></tr>";
	}
	echo "</table>";
}
if($params['stats']['ngas'] > 1){
?> 

	
	<h4>Results Summary</h4>
	
	<table class='scraper-report'><tr><th>NGA</th><th>Polities</th><th>Variables</th>
	<th>Simple</th><th>Complex</th><th>Empty</th><th>Parse Success</th><th>Parse Failure</th><th>Datapoints</th></tr>
	<?php
	
	foreach($params['summary'] as $pf){
		echo "<tr><td>" . $pf["nga"] . "</td><td>" . $pf["polities"] . "</td><td>".	$pf['total_variables']."</td><td>".
		($pf['total_variables'] - ($pf['complex'] + $pf['empty'])) . "</td><td>".
		$pf['complex']."</td><td>".$pf['empty']."</td><td>".($pf['complex'] - $pf['errors']).
		 "</td><td>".$pf['errors']."</td><td>".$pf['lines']."</td></tr>";
	}
	?> 
	</table>
<?php } ?>
<div class='data-buttons'>
	<a id="gettsv" class='data-button' href="<?=$params['files']['tsv']?>">Download the results</a> |
	<a id="gethtml" class='data-button' href="<?=$params['files']['html']?>">View the results</a> |
	<a id="geterrs" class='data-button' href="<?=$params['files']['errors']?>">View the error report</a>
</div>

<script>
	$("#gethtml").click(function(e){
		e.preventDefault();
		alert($(this).attr("href"));
	}};
	
</script>