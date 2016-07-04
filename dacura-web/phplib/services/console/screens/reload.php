<script>
var nparams = <?=json_encode($params)?>;
for(var i in nparams){
	params[i] = nparams[i];
}
</script>