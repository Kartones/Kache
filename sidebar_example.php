<?php

$kache = Kache::GetInstance();
$recentPosts = $kache->Get(Kache::CACHE_KEY_SIDEBAR);

if (!$recentPosts)
{
	$recentPosts = wp_get_archives('type=postbypost&limit=8&echo=0');
	$kache->Set(Kache::CACHE_KEY_SIDEBAR, $recentPosts);
}

?>

<div id="sidebar">
	<ul>
		<li>
			<h2>Recent Posts</h2>
			<ul class="latest_post">
				<?php echo $recentPosts ?>
			</ul>
		</li>
	</ul>
</div>