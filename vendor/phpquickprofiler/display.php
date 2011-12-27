<style type="text/css">
	#profiler { clear: both; background: #222; opacity: 0.45; padding: 0 5px; font-family: Helvetica, sans-serif; font-size: 10px !important; line-height: 12px; position: fixed; width: auto; min-width: 70em; max-width: 90%; z-index: 1000; }
	#profiler:hover { background: #101010; opacity: 1.0; }
	
	#profiler.bottom-right { bottom:0; right: 0; -webkit-border-top-left-radius: 7px; -moz-border-radius-topleft: 7px; border-top-left-radius: 7px; -webkit-box-shadow: -1px -1px 10px #222; -moz-box-shadow: -1px -1px 10px #222; box-shadow: -1px -1px 10px #222; }	
	#profiler.bottom-left { bottom:0; top: auto; -webkit-border-top-right-radius: 7px; -moz-border-radius-topright: 7px; border-top-right-radius: 7px; -webkit-box-shadow: 1px -1px 10px #222; -moz-box-shadow: 1px -1px 10px #222; box-shadow: 1px -1px 10px #222; }
	#profiler.top-left { top:0; left: 0; -webkit-border-bottom-right-radius: 7px; -moz-border-radius-bottomright: 7px; border-bottom-right-radius: 7px;-webkit-box-shadow: 1px 1px 10px #222; -moz-box-shadow: 1px 1px 10px #222; box-shadow: 1px 1px 10px #222; }	
	#profiler.top-right { top: 0; right: 0; -webkit-border-bottom-left-radius: 7px; -moz-border-radius-bottomleft: 7px; border-bottom-left-radius: 7px; -webkit-box-shadow: -1px 1px 10px #222; -moz-box-shadow: -1px 1px 10px #222; box-shadow: -1px 1px 10px #222; }	
	
	.profiler-box { padding: 10px; margin: 0 0 10px 0; max-height: 400px; overflow: auto; color: #fff; font-family: Monaco, 'Lucida Console', 'Courier New', monospace; font-size: 11px !important; }
	.profiler-box h2 { font-family: Helvetica, sans-serif; font-weight: bold; font-size: 16px !important; padding: 0; line-height: 2.0; }
	
	#profiler-menu a:link, #profiler-menu a:visited { display: inline-block; padding: 7px 0; margin: 0; color: #ccc; text-decoration: none; font-weight: lighter; cursor: pointer; text-align: center; width: 11.5%; border-bottom: 4px solid #444; }
	#profiler-menu a:hover, #profiler-menu a.current { background-color: #666; border-color: #999; }
	#profiler-menu a span { display: block; font-weight: bold; font-size: 14px !important; line-height: 1.2; }
	
	#profiler-menu-time span, #profiler-benchmarks h2 { color: #B72F09; }
	#profiler-menu-memory span, #profiler-memory h2 { color: #953FA1; }
	#profiler-menu-queries span, #profiler-queries h2 { color: #3769A0; }
	#profiler-menu-vars span, #profiler-vars h2 { color: #D28C00; }
	#profiler-menu-files span, #profiler-files h2 { color: #5a8616; }
	#profiler-menu-console span, #profiler-console h2 { color: #5a8616; }
	
	#profiler table { width: 100%; border: none; }
	#profiler table.main tr:hover { cursor: pointer;}
	#profiler table.main td { font-family: Consolas, Lucida;  padding: 5px 8px; text-align: left; vertical-align: top; color: #aaa; border-bottom: 1px dotted #444; line-height: 1.5; background: #101010 !important; font-size: 11px !important; }
	#profiler table.main tr:hover td { background: #292929 !important; }
	#profiler table.main code {  padding: 0; background: transparent; border: 0; color: #fff; }
	#profiler table.main td b { float:right;font-weight:normal;color:#e6f387}
	
	#profiler table .hilight { color: #FFFD70 !important; }
	#profiler table .faded { color: #aaa !important; }
	#profiler table .small { font-size: 10px; letter-spacing: 1px; font-weight: lighter; }
	
	#profiler-menu-exit { background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIhSURBVDjLlZPrThNRFIWJicmJz6BWiYbIkYDEG0JbBiitDQgm0PuFXqSAtKXtpE2hNuoPTXwSnwtExd6w0pl2OtPlrphKLSXhx07OZM769qy19wwAGLhM1ddC184+d18QMzoq3lfsD3LZ7Y3XbE5DL6Atzuyilc5Ciyd7IHVfgNcDYTQ2tvDr5crn6uLSvX+Av2Lk36FFpSVENDe3OxDZu8apO5rROJDLo30+Nlvj5RnTlVNAKs1aCVFr7b4BPn6Cls21AWgEQlz2+Dl1h7IdA+i97A/geP65WhbmrnZZ0GIJpr6OqZqYAd5/gJpKox4Mg7pD2YoC2b0/54rJQuJZdm6Izcgma4TW1WZ0h+y8BfbyJMwBmSxkjw+VObNanp5h/adwGhaTXF4NWbLj9gEONyCmUZmd10pGgf1/vwcgOT3tUQE0DdicwIod2EmSbwsKE1P8QoDkcHPJ5YESjgBJkYQpIEZ2KEB51Y6y3ojvY+P8XEDN7uKS0w0ltA7QGCWHCxSWWpwyaCeLy0BkA7UXyyg8fIzDoWHeBaDN4tQdSvAVdU1Aok+nsNTipIEVnkywo/FHatVkBoIhnFisOBoZxcGtQd4B0GYJNZsDSiAEadUBCkstPtN3Avs2Msa+Dt9XfxoFSNYF/Bh9gP0bOqHLAm2WUF1YQskwrVFYPWkf3h1iXwbvqGfFPSGW9Eah8HSS9fuZDnS32f71m8KFY7xs/QZyu6TH2+2+FAAAAABJRU5ErkJggg==) 0% 0% no-repeat; padding-left: 20px; position: absolute; right: 5px; top: 10px; }
</style>

<script type="text/javascript">
current = null;
currentvar = null;
currentli = null;
function show (obj, el)
{
	if (obj == current) {
		off(obj);
		current = null;
	} else {
		off(current);
		on(obj);
		remove_class(current, 'current');
		current = obj;
		//ci_profiler_bar.add_class(el, 'current');
	}
}
function on (obj){ if (document.getElementById(obj) != null) document.getElementById(obj).style.display = ''; }
function off(obj){ if (document.getElementById(obj) != null) document.getElementById(obj).style.display = 'none';}
function toggle (obj){
	if (typeof obj == 'string')	obj = document.getElementById(obj);
	if (obj) obj.style.display = obj.style.display == 'none' ? '' : 'none';
}
function close_bar () { document.getElementById('profiler').style.display = 'none';
}
function add_class(obj, clas){ alert(obj); document.getElementById(obj).className += " "+ clas; }
function remove_class(obj, clas){
	if (obj != undefined) {
		document.getElementById(obj).className = document.getElementById(obj).className.replace(/\bclass\b/, '');
	}
}
</script>

<?php
$logCount = count($output['logs']['console']);
$fileCount = count($output['files']);
$memoryUsed = $output['memoryTotals']['used'];
$queryCount = $output['queryTotals']['count'];
$speedTotal = $output['speedTotals']['total'];

$printarray = function($items, $depth, &$class, &$count) use(&$printarray)
{
	$output = '';
	foreach($items as $item => $value) {
		$count++;
		$output .="<tr>\n<td class='".$class."'>\n";
		if (is_bool($value))
		{
			$output .= "<b>".($value?"true":"false")."  </b>\n";
		}
		elseif (is_null($value))
		{
			$output .= "<b>null</b>\n";
		}
		elseif( ! is_array($value))
		{
			$output .= "<b>".$value." </b>\n";
		}
		$output .= $item . str_repeat('&rsaquo;&nbsp;', $depth)."</td>\n</tr>\n";
		if($class == '') $class = 'alt'; else $class = '';
		is_array($value) and $output .= $printarray($value, $depth + 1, $class, $count);
	}
	return $output;
};

$class = '';
$configCount = 0;
$output['configItems'] = $printarray(\Config::$items, 0, $class, $configCount);

$class = '';
$sessionCount = 0;
$output['sessionItems'] = $printarray(\Session::get(), 0, $class, $sessionCount);

$class = '';
$getCount = 0;
$output['getItems'] = $printarray(\Input::get(), 0, $class, $getCount);

$class = '';
$postCount = 0;
$output['postItems'] = $printarray(\Input::post(), 0, $class, $postCount);

?>

<div id="profiler" class="bottom-right">
	
	<div id="profiler-menu">
		
		<!-- Console -->
		<?php if (isset($output['logs'])) : ?>
			<a href="#" id="profiler-menu-console" onclick="show('profiler-console', 'profiler-menu-console');return false;">
				<span><?php echo $logCount ?></span> Console
			</a>
		<?php endif; ?>
		
		<!-- Benchmarks -->
		<?php if (isset($output['logs'])) :?>
			<a href="#" id="profiler-menu-time" onclick="show('profiler-benchmarks', 'profiler-menu-time'); return false;">
				<span><?php echo $speedTotal ?></span>
				Load Time
			</a>
			
		<?php endif; ?>
                
                <!-- Memories -->
		<?php if (isset($output['memoryTotals'])) :?>
                <a href="#" id="profiler-menu-memory" onclick="show('profiler-memory', 'profiler-menu-memory'); return false;">
				<span><?php echo $memoryUsed ?></span> Memory Used
			</a>
		<?php endif;?>
                
                <!-- Files -->
		<?php if (isset($output['files'])) : ?>
			<a href="#" id="profiler-menu-files" onclick="show('profiler-files', 'profiler-menu-files'); return false;">
				<span><?php echo $fileCount ?></span> Files
			</a>
		<?php endif; ?>
                
		<!-- Vars and Config -->
		<?php if (isset($output['files'])) : ?>
			<a href="#" id="profiler-menu-vars" onclick="show('profiler-vars', 'profiler-menu-vars'); return false;">
				<span><?php echo $configCount;?></span> Items loaded
			</a>
		<?php endif; ?>
		
		<!-- Get -->
		<?php if (isset($output['getItems'])) : ?>
			<a href="#" id="profiler-menu-gets-posts" onclick="show('profiler-gets-posts', 'profiler-menu-gets-posts'); return false;">
				<span><?php echo $getCount;?></span> Gets or Posts
			</a>
		<?php endif; ?>
		
                <!-- Queries -->
		<?php if (isset($output['queries'])) : ?>
			<a href="#" id="profiler-menu-queries" onclick="show('profiler-queries', 'profiler-menu-queries'); return false;">
				<span> <?php echo $queryCount;?></span> Queries
		<?php endif; ?>
			
		<!-- Session -->
		<?php if (isset($output['sessionItems'])) : ?>
			
			<a href="#" id="profiler-menu-session" onclick="show('profiler-session', 'profiler-menu-session'); return false;">
				<span><?php echo $sessionCount;?> </span> Session
			</a>
		<?php endif; ?>
			
		<a href="#" id="profiler-menu-exit" onclick="close_bar(); return false;" style="width: 2em"></a>
	</div>

<?php if (@count($output['logs']) > 0) : ?>
	<!-- Console -->
	<?php if (isset($output['logs'])) :?>
		<div id="profiler-console" class="profiler-box" style="display: none">
			<h2>Console</h2>
			
			<?php if ($output['logs']) : ?>
				
				<table class="main">
				<?php foreach ($output['logs']['console'] as $log) : ?>
					
					<?php if ($log['type'] == 'log') : ?>
						<tr>
							<td><?php echo $log['type'] ?></td>
							<td class="faded"><pre><?php echo $log['data'] ?></pre></td>
							<td></td>
						</tr>
					<?php elseif ($log['type'] == 'memory')  :?>
						<tr>
							<td><?php echo $log['type'] ?></td>
							<td>
								<em><?php echo $log['data_type'] ?></em>: 
								<?php echo $log['name']; ?>
							</td>
							<td class="hilight" style="width: 9em"><?php echo $log['data'] ?></td>
						</tr>
                                        <?php elseif($log['type'] == 'speed'):?>
                                                <tr>
							<td><?php echo $log['type'] ?></td>
							<td>
								<em><?php echo $log['data'] ?></em>: 
								<?php echo $log['name']; ?>
							</td>
							<td class="hilight" style="width: 9em"><?php echo $log['data'] ?></td>
						</tr>
                                        <?php elseif($log['type'] == 'error'):?>
                                                <tr>
							<td><?php echo $log['type'] ?></td>
							<td>
								<em><?php echo $log['data_type'] ?></em>: 
								<?php echo $log['name']; ?>
							</td>
							<td class="hilight" style="width: 9em"><?php echo $log['data'] ?></td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
				</table>

			<?php else : ?>

				<?php echo $output['logs']; ?>

			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<!-- Benchmarks -->
	<?php if (isset($output['logs'])) :?>
		<div id="profiler-benchmarks" class="profiler-box" style="display: none">
                        <h2>Benchmarks</h2>
			<?php if (is_array($output['logs'])) : ?>
				
				<table class="main">
				<?php foreach ($output['logs']['console'] as $log) : ?>
					
                                        <?php if($log['type'] == 'speed'):?>
                                                <tr>
							<td><?php echo $log['type'] ?></td>
							<td>
								<em><?php echo $log['data'] ?></em>: 
								<?php echo $log['name']; ?>
							</td>
							<td class="hilight" style="width: 9em"><?php echo $log['data'] ?></td>
						</tr>
					<?php endif; ?>
                                        
				<?php endforeach; ?>
				</table>

			<?php else : ?>

				<?php echo $sections['console']; ?>

			<?php endif; ?>
		</div>
	<?php endif; ?>

        <!-- Memory -->
	<?php if (isset($output['memoryTotals'])) :?>
		<div id="profiler-memory" class="profiler-box" style="display: none">
			<h2>Memory Usage</h2>
			<?php if (is_array($output['memoryTotals'])) : ?>
				
				<table class="main">
				<?php foreach ($output['memoryTotals'] as $key => $val) : ?>
					<tr><td><?php echo $key ?></td><td class="hilight"><?php echo $val ?></td></tr>
				<?php endforeach; ?>
				</table>

			<?php else : ?>

				<?php echo $sections['benchmarks']; ?>

			<?php endif; ?>
		</div>
	<?php endif; ?>
	
        <!-- Files -->
	<?php if (isset($output['files'])) :?>
		<div id="profiler-files" class="profiler-box" style="display: none">
			<h2>Loaded Files</h2>
                       
                        Total Files : <span class="faded small"><?php echo $output['fileTotals']['count'] ?></span> <br />
                        Total Size : <span class="faded small"><?php echo $output['fileTotals']['size'] ?></span> <br />
                        Largest : <span class="faded small"><?php echo $output['fileTotals']['largest'] ?></span> <br /> <br />
			<?php if (is_array($output['files'])) : ?>
				
				<table class="main">
				<?php foreach ($output['files'] as $file) : ?>
					<tr>
						<td class="hilight">
							<?php echo $file['name'] ?>
							<br/><span class="faded small"><?php echo $file['size'] ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
                                       
				</table>

			<?php else : ?>

				<?php echo $output['files']; ?>

			<?php endif; ?>
		</div>
	<?php endif; ?>
	
        <!-- Config Items -->
	<?php if (isset($output['configItems'])) :?>
		<div id="profiler-vars" class="profiler-box" style="display: none">
			<h2>Config Item</h2>
			<table class="main" cellspacing="0">
				<?php echo $output['configItems'];?>
			</table>
		</div>
	<?php endif; ?>
	
	<!-- Gets and Posts-->
	<?php if (isset($output['getItems']) or isset($output['postItems'])) :?>
		<div id="profiler-gets-posts" class="profiler-box" style="display: none">
			<h2>Gets Item</h2>
			<table class="main" cellspacing="0">
				<?php echo $output['getItems'];?>
			</table>
			
			<h2>Posts Item</h2>
			<table class="main" cellspacing="0">
				<?php echo $output['postItems'];?>
			</table>
			
		</div>
	<?php endif; ?>
	
	
	
	
	<!-- Queries -->
	<?php if (isset($output['queries'])) :?>
		<div id="profiler-queries" class="profiler-box" style="display: none">
			<h2>Queries</h2>
			
			<?php if (is_array($output['queries'])) : ?>
				
				<table class="main" cellspacing="0">
				<?php foreach ($output['queries'] as $key => $val) : ?>
					<tr><td class="hilight"><?php echo $key ?></td><td><?php echo $val ?></td></tr>
				<?php endforeach; ?>
				</table>

			<?php else : ?>

				<?php echo $output['queries']; ?>

			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<!-- Session -->
	<?php if (isset($output['sessionItems'])) :?>
		<div id="profiler-session" class="profiler-box" style="display: none">
			<h2>Session Item</h2>
			<table class="main" cellspacing="0">
				<?php echo $output['sessionItems'];?>
			</table>
			
		</div>
	<?php endif; ?>
	
	

	
<?php else: ?>

	<p class="profiler-box">No profiller profile ;)</p>

<?php endif; ?>

</div>
