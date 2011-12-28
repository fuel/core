<?php
function output ($output)
{
/* --------------------------------------------------------
  
Title : HTML Output for Php Quick Profiler
Author : Created by Ryan Campbell
URL : http://particletree.com

Last Updated : April 22, 2009
Description : This is a horribly ugly function used to output
the PQP HTML. This is great because it will just work in your project,
but it is hard to maintain and read. See the README file for how to use
the Smarty file we provided with PQP.

This templates was modify by
Purwandi <pur@purwandi.me>
ported from Forensics CodeIgniter lonnieezell<https://github.com/lonnieezell/>

-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
$return_output = '';
$return_output .= <<<CSS
<style type="text/css">
	#profiler { clear: both; background: #222; opacity: 0.45; padding: 0 5px; font-family: Helvetica, sans-serif; font-size: 10px !important; line-height: 12px; position: fixed; width: auto; min-width: 80em; max-width: 90%; z-index: 1000; }
	#profiler:hover { background: #101010; opacity: 1.0; }
	
	#profiler.bottom-right { bottom:0; right: 0; -webkit-border-top-left-radius: 7px; -moz-border-radius-topleft: 7px; border-top-left-radius: 7px; -webkit-box-shadow: -1px -1px 10px #222; -moz-box-shadow: -1px -1px 10px #222; box-shadow: -1px -1px 10px #222; }	
	#profiler.bottom-left { bottom:0; top: auto; -webkit-border-top-right-radius: 7px; -moz-border-radius-topright: 7px; border-top-right-radius: 7px; -webkit-box-shadow: 1px -1px 10px #222; -moz-box-shadow: 1px -1px 10px #222; box-shadow: 1px -1px 10px #222; }
	#profiler.top-left { top:0; left: 0; -webkit-border-bottom-right-radius: 7px; -moz-border-radius-bottomright: 7px; border-bottom-right-radius: 7px;-webkit-box-shadow: 1px 1px 10px #222; -moz-box-shadow: 1px 1px 10px #222; box-shadow: 1px 1px 10px #222; }	
	#profiler.top-right { top: 0; right: 0; -webkit-border-bottom-left-radius: 7px; -moz-border-radius-bottomleft: 7px; border-bottom-left-radius: 7px; -webkit-box-shadow: -1px 1px 10px #222; -moz-box-shadow: -1px 1px 10px #222; box-shadow: -1px 1px 10px #222; }	
	
	.profiler-box { padding: 10px; margin: 0 0 10px 0; max-height: 400px; overflow: auto; color: #fff; font-family: Monaco, 'Lucida Console', 'Courier New', monospace; font-size: 11px !important; }
	.profiler-box h2 { font-family: Helvetica, sans-serif; font-weight: bold; font-size: 16px !important; padding: 0; line-height: 2.0; }
	
	#profiler-menu a:link, #profiler-menu a:visited { display: inline-block; padding: 7px 0; margin: 0; color: #ccc; text-decoration: none; font-weight: lighter; cursor: pointer; text-align: center; width: 10.5%; border-bottom: 4px solid #444; }
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
CSS;
$return_output .= <<<JAVASCRIPT
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
JAVASCRIPT;

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
$output['sessionItems'] = $printarray(\Session::get(null), 0, $class, $sessionCount);

$class = '';
$getCount = 0;
$output['getItems'] = $printarray(\Input::get(), 0, $class, $getCount);

$class = '';
$postCount = 0;
$output['postItems'] = $printarray(\Input::post(), 0, $class, $postCount);

$return_output .='
<div id="profiler" class="bottom-right">
	
	<div id="profiler-menu">
		
		<!-- Console -->';
		if (isset($output["logs"])):
			$return_output .= '<a href="#" id="profiler-menu-console" onclick="show(\'profiler-console\', \'profiler-menu-console\');return false;">
				<span>'.$logCount .'</span> Console
			</a>';
		endif;
		
		if (isset($output['logs'])) :
			$return_output .='<!-- Benchmarks -->
				<a href="#" id="profiler-menu-time" onclick="show(\'profiler-benchmarks\', \'profiler-menu-time\'); return false;">
				<span>'. $speedTotal.'</span>
				Load Time
			</a>';
			
		endif;
                
		if (isset($output['memoryTotals'])) :
			$return_output .='<!-- Memories -->
				<a href="#" id="profiler-menu-memory" onclick="show(\'profiler-memory\', \'profiler-menu-memory\'); return false;">
				<span>'.$memoryUsed .'</span> Memory Used
			</a>';
		endif;
                
               
		if (isset($output['files'])) :
			$return_output .= ' <!-- Files -->
			<a href="#" id="profiler-menu-files" onclick="show(\'profiler-files\', \'profiler-menu-files\'); return false;">
				<span>'. $fileCount .'</span> Files
			</a>';
		endif;
               
		if (isset($output['files'])) :
			$return_output .= '<!-- Vars and Config -->
			<a href="#" id="profiler-menu-vars" onclick="show(\'profiler-vars\', \'profiler-menu-vars\'); return false;">
				<span>'. $configCount .'</span> Items loaded
			</a>';
		endif;
		
		if (isset($output['getItems'])) :
			$return_output .= '<!-- Get -->
			<a href="#" id="profiler-menu-gets" onclick="show(\'profiler-gets\', \'profiler-menu-gets\'); return false;">
				<span>'. $getCount.'</span> Gets
			</a>';
		endif;
		
		if (isset($output['postItems'])) :
			$return_output .= '<!-- Post -->
			<a href="#" id="profiler-menu-posts" onclick="show(\'profiler-posts\', \'profiler-menu-posts\'); return false;">
				<span>'. $postCount.'</span> Posts
			</a>';
		endif;
		
		if (isset($output['queries'])) :
			$return_output .= '<!-- Queries -->
			<a href="#" id="profiler-menu-queries" onclick="show(\'profiler-queries\', \'profiler-menu-queries\'); return false;">
				<span> '. $queryCount .'</span> Queries
			</a>';
				
		endif;
			
		
		if (isset($output['sessionItems'])) :
			$return_output .='<!-- Session -->
			<a href="#" id="profiler-menu-session" onclick="show(\'profiler-session\', \'profiler-menu-session\'); return false;">
				<span>'. $sessionCount.' </span> Session
			</a>';
		endif;
			
		$return_output .=' <a href="#" id="profiler-menu-exit" onclick="close_bar(); return false;" style="width: 2em"></a>
	</div>';

if (@count($output['logs']) > 0) :
	
	if (isset($output['logs'])) :
		$return_output .=' <!-- Console -->
		<div id="profiler-console" class="profiler-box" style="display: none">
			<h2>Console</h2>';
			
			if (is_array($output['logs'])) :
				
				$return_output .='<table class="main">';
				foreach ($output['logs']['console'] as $log) :
					
					if ($log['type'] == 'log') :
						$return_output .='<tr>
							<td>'. $log['type'].'</td>
							<td class="faded"><pre>'.$log['data'] .'</pre></td>
							<td></td>
						</tr>';
					elseif ($log['type'] == 'memory')  :
						$return_output .='<tr>
							<td>'.$log['type'] .'</td>
							<td>
								<em>'.$log['data_type'] .'</em>: 
								'.$log['name'] .'
							</td>
							<td class="hilight" style="width: 9em">'.$log['data'] .'</td>
						</tr>';
                                        elseif($log['type'] == 'speed'):
                                                $return_output .='<tr>
							<td>'.$log['type'] .'</td>
							<td>
								<em>'.$log['data'] .'</em>: 
								'.$log['name'] .'
							</td>
							<td class="hilight" style="width: 9em">'.$log['data'] .'</td>
						</tr>';
                                        elseif($log['type'] == 'error'):
                                                $return_output .='<tr>
							<td>'.$log['type'] .'</td>
							<td>
								<em>'.$log['data_type'] .'</em>: 
								'.$log['name'] .'
							</td>
							<td class="hilight" style="width: 9em">'.$log['data'] .'</td>
						</tr>';
					endif;
				endforeach;
				$return_output .='</table>';

			else : 

				$return_output .= $output['logs'];

			endif;
		$return_output .='</div>';
	endif;
	
	
	if (isset($output['logs'])) :
		$return_output .='<!-- Benchmarks -->
		<div id="profiler-benchmarks" class="profiler-box" style="display: none">
                        <h2>Benchmarks</h2>';
			if (is_array($output['logs'])) :
				
				$return_output .=' <table class="main">';
				foreach ($output['logs']['console'] as $log) :
					
                                        if($log['type'] == 'speed'):
						$return_output .='
                                                <tr>
							<td>'.$log['type'] .'</td>
							<td>
								<em>'.$log['data'] .'</em>: 
								'.$log['name'] .'
							</td>
							<td class="hilight" style="width: 9em">'.$log['data'] .'</td>
						</tr>';
					endif;
                                        
				endforeach;
				$return_output .='</table>';

			else :

				$return_output .= $output['logs']['console'];

			endif;
		$return_output .= '</div>';
	endif;

	if (isset($output['memoryTotals'])) :
		$return_output .='<!-- Memory -->
		<div id="profiler-memory" class="profiler-box" style="display: none">
			<h2>Memory Usage</h2>';
			
			if (is_array($output['memoryTotals'])) :
				$return_output .='<table class="main">';
					foreach ($output['memoryTotals'] as $key => $val) :
						$return_output .='<tr><td>'.$key .'</td><td class="hilight">'.$val .'</td></tr>';
					endforeach;
				$return_output .='</table>';

			else : 

				$return_output .= $sections['benchmarks'];

			endif;
		$return_output .='</div>';
	endif;
	
        if (isset($output['files'])) :
		$return_output .='<!-- Files -->
		<div id="profiler-files" class="profiler-box" style="display: none">
			<h2>Loaded Files</h2>
                       
                        Total Files : <span class="faded small">'.$output['fileTotals']['count'] .'</span> <br />
                        Total Size : <span class="faded small">'.$output['fileTotals']['size'] .'</span> <br />
                        Largest : <span class="faded small">'.$output['fileTotals']['largest'] .'</span> <br /> <br />';
			
			if (is_array($output['files'])) :
				$return_output .='<table class="main">';
				
				foreach ($output['files'] as $file) :
					$return_output .='
					<tr>
						<td class="hilight">
							'.$file['name'] .'
							<br/><span class="faded small">'.$file['size'] .'</span>
						</td>
					</tr>';
				endforeach;
                                       
				$return_output .= '</table>';

			else :

				$return_output .= $output['files'];

			endif;
		$return_output .= '</div>';
	endif;
	
        
	if (isset($output['configItems'])) :
		$return_output .= '<!-- Config Items -->
		<div id="profiler-vars" class="profiler-box" style="display: none">
			<h2>Config Item</h2>
			<table class="main" cellspacing="0">
				'.$output['configItems'].'
			</table>
		</div>';
	endif;
	
	
	if (isset($output['getItems']) or isset($output['postItems'])) :
		$return_output .= '<!-- Gets  -->
		<div id="profiler-gets" class="profiler-box" style="display: none">
			<h2>Gets Item</h2>
			<table class="main" cellspacing="0">
				'.$output['getItems'].'
			</table>
			
		</div>';
	endif; 
	
	if (isset($output['getItems']) or isset($output['postItems'])) :
		$return_output .= '<!-- Posts -->
		<div id="profiler-posts" class="profiler-box" style="display: none">
			
			<h2>Posts Item</h2>
			<table class="main" cellspacing="0">
				'.$output['postItems'].'
			</table>
			
		</div>';
	endif; 
	
	if (isset($output['queries'])) :
		$return_output .= '<!-- Queries -->
		<div id="profiler-queries" class="profiler-box" style="display: none">
			<h2>Queries</h2>';
			
			if (is_array($output['queries'])) :
				
				$return_output .= '<table class="main" cellspacing="0">';
				
				foreach ($output['queries'] as $key => $val) :
					$return_output .= '<tr><td class="hilight">'.$key .'</td><td>'.$val .'</td></tr>';
				endforeach;
				
				$return_output .='</table>';

			else :

				$return_output .= $output['queries'];

			endif;
		$return_output .='</div>';
	endif;
	
	if (isset($output['sessionItems'])) :
		$return_output .= '<!-- Session -->
		<div id="profiler-session" class="profiler-box" style="display: none">
			<h2>Session Item</h2>
			<table class="main" cellspacing="0">
				'.$output['sessionItems'].'
			</table>
			
		</div>';
	 endif;
	
	

	
else:

	$return_output .='<p class="profiler-box">No profiller profile ;)</p>';

endif;

$return_output .='</div>';
return $return_output;
} ?>