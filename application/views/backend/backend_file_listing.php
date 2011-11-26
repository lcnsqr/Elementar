<?php if ( is_array($listing) ) : ?>
<ul>
<?php foreach($listing as $item): ?>
<li>
	<a style="background-image: url('<?php echo $item['icon']; ?>');" class="item block <?php echo $item['class']; ?>" href="<?php echo $item['path']; ?>" title="<?php echo $item['name']; ?>">
	<p class="label"><?php echo $item['label']; ?></p>
	</a>
	<div class="item_details">
		<hr />
		<div style="background-image: url('<?php echo $item['icon']; ?>');" class="current_file_icon"><span class="icon"><?php echo $item['icon']; ?></span></div>
		<p class="current_file_title"><?php echo $item['name']; ?></p>
		<ul>
			<?php if ( $item['class'] != 'directory' ) : ?>
			<li><strong>Tipo</strong>: <span class="mime"><?php echo $item['mime']; ?></span></li>
			<li><strong>Tamanho</strong>: <span class="size"><?php echo $item['size']; ?></span></li>
			<?php
			switch ( $item['mime'] )
			{
				case 'image/png' :
				case 'image/jpeg' :
				case 'image/gif' :
				echo '<li><strong>Dimensões</strong>: <span class="width">' . $item['width'] . '</span>&times;<span class="height">' . $item['height'] . '</span></li>';
				break;
			}
			?>
			<?php endif; ?>
			<li><a href="<?php echo $item['path']; ?>" class="current_item_rename">Renomear</a></li>
			<li><a href="<?php echo $item['path']; ?>" class="current_item_erase">Apagar</a></li>
		</ul>
		<?php if ( $item['class'] != 'directory' ) : ?>
		<hr />
		<ul>
		<?php if ( $parent == 'tinymce' ): ?>
			<li><a href="<?php echo $item['path']; ?>" title="<?php echo $item['name']; ?>" class="insert">Inserir link</a><span class="action_insert"><a href="<?php echo $item['path']; ?>" alt="<?php echo $item['name']; ?>" title="<?php echo $item['name']; ?>"><?php echo $item['name']; ?></a></span></li>
			<?php
			switch ( $item['mime'] )
			{
				case 'image/png' :
				case 'image/jpeg' :
				case 'image/gif' :
				echo '<li><a href="' . $item['path'] . '" title="' . $item['name'] . '" class="insert">Inserir como imagem</a><span class="action_insert">' . $item['img']. '</span></li>';
				break;
			}
			?>
		<?php else: ?>
			<li><a href="<?php echo $item['path']; ?>" title="<?php echo $item['name']; ?>" class="insert">Inserir</a><span class="action_insert"><?php echo $item['path']; ?></span></li>
		<?php endif; ?>
		</ul>
		<?php endif; ?>
	</div>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
