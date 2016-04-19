<?php 
/**
 * Tool page header
 * 
 * Header that appears on the top of all 'tool' pages
 * Includes tool libraries, css class, close-link, title, subtitle, breadcrumbs
 *
 * @package core/snippets
 * @author chekov
 * @copyright GPL v2
 */
?>
<script src="<?=$service->get_service_script_url("dacura.tool.js", "core")?>"></script>
<?php if($params['dt']) {?>
	<script src='<?=$service->furl("js", "jquery.dataTables.min.js")?>'></script>
	<script src='<?=$service->furl("js", "dataTables.jqueryui.js")?>'></script>
	<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->furl("css", "dataTables.jqueryui.css")?>" />
<?php } if($params['jsoned']){?>
	<script src='<?=$service->furl("js", "jquery.json-editor.js")?>'></script>
	<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->furl("css", "jquery.json-editor.css")?>" />
<?php }?>
<div class='tool-holder'>
	<div class="tool-header <?=$params['css_class']?>">
		<div class='tool-close dch'>
			<a href='<?=$params['close-link']?>' title='Close <?=$params['title']?> - <?=$params['close-link']?>'>
			<?=$params['close-msg']?></a>
		</div>
		<span class='tool-image'>
			<?php if($params['image']){
					if($params['image-link']){?>
						<a href='<?=$params['image-link']?>'><img class='tool-header-image' src="<?=$params['image']?>" title="<?=$params['title']?>" /></a>
					<?php } else { ?>
			<img class='tool-header-image' src="<?=$params['image']?>" title="<?=$params['title']?>" />
			<?php }}?>
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
