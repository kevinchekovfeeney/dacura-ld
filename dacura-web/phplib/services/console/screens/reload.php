<script>
var nparams = <?=json_encode($params)?>;
for(var i in nparams){
	dacura.params[i] = nparams[i];
}
</script>