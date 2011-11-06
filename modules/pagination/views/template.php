<div class="pagination">
	<?php
	if ($current_page > 1)
	{
		$previous_page = $current_page - 1;
		$previous_page = ($previous_page <= 1) ? '' : '/'.$previous_page;
		echo \Html::anchor(rtrim($pagination_url, '/').$previous_page, '&laquo; '.__('pagination.previous'));
	}
	else
	{
		echo '&laquo; '.__('pagination.previous'); 
	}
	?>
	<span class="page-links">
		<?php
		$start = (($current_page - $num_links) > 0) ? $current_page - ($num_links - 1) : 1;
		$end   = (($current_page + $num_links) < $total_pages) ? $current_page + $num_links : $total_pages;
		for($i = $start; $i <= $end; $i++)
		{
			if ($current_page === $i)
			{
				echo '<span class="active"> '.$i.' </span>';
			}
			else
			{
				$url = ($i == 1) ? '' : '/'.$i;
				echo \Html::anchor(rtrim($pagination_url, '/').$url, $i);
			}
		}
		?>
	</span>
	<?php
	if ($current_page < $total_pages)
	{
		$next_page = $current_page + 1;
		$next_page = ($next_page > $total_pages) ? '' : '/'.$next_page;
		echo \Html::anchor(rtrim($pagination_url, '/').$next_page, __('pagination.next').' &raquo;');
	}
	else
	{
		echo __('pagination.next').' &raquo;'; 
	}
	?>
</div>