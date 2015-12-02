<?php if($params['dt'] && true){?>
	<script src='https://cdn.datatables.net/1.10.10/js/jquery.dataTables.min.js'></script>
	<script src='https://cdn.datatables.net/1.10.10/js/dataTables.jqueryui.min.js'></script>
	<link rel="stylesheet" type="text/css" media="screen" href="https://cdn.datatables.net/1.10.10/css/dataTables.jqueryui.min.css" />
<?php } else if($params['dt'] && false) {?>
	<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
	<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
	<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<?php } if($params['jsoned']){?>
	<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
	<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />
<?php }?>
<div class='tool-holder'>
	<div class="tool-header <?=$params['css_class']?>">
		<div class='tool-close dch'>
			<a href='<?=$params['close-link']?>' title='Close <?=$params['title']?> - <?=$params['close-link']?>'>
			<?=$params['close-msg']?></a>
		</div>
		<span class='tool-image'>
			<?php if($params['image']){?>
			<img class='tool-header-image' src="<?=$params['image']?>" title="<?=$params['title']?>" />
			<?php }?>
		</span>
		<span class='tool-title'><?= $params['title']?></span>
		<span class='tool-subtitle'><?=$params['subtitle']?></span>
		<span class='tool-description'><?=$params['description']?></span>
	</div>
	<?php if($params['breadcrumbs']){?>
		<div class="pcbreadcrumbs"><?=$params['breadcrumbs']?></div>
	<?php }?>
	<div class='tool-contents'>
		<div class='tool-info'><?=$params['init-msg']?></div>
			<div class='tool-body'>
