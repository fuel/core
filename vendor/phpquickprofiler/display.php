<?php
<<<<<<< HEAD
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
=======

/* - - - - - - - - - - - - - - - - - - - - - - - - - - -

 Title : HTML Output for Php Quick Profiler
 Author : Created by Ryan Campbell
 URL : http://particletree.com

 Last Updated : April 22, 2009

 Description : This is a horribly ugly function used to output
 the PQP HTML. This is great because it will just work in your project,
 but it is hard to maintain and read. See the README file for how to use
 the Smarty file we provided with PQP.

- - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

function displayPqp($output) {

	$css = str_replace("\n", "", <<<CSS
.pQp {width:100%;z-index:9999;text-align:center;position:fixed;bottom:0}
* html .pQp{position:absolute}
.pQp *{margin:0;padding:0;border:none}
#pQp{margin:0 auto;width:85%;min-width:960px;background-color:#222;border:12px solid #000;border-bottom:none;font-family:"Lucida Grande",Tahoma,Arial,sans-serif;-webkit-border-top-left-radius:15px;-webkit-border-top-right-radius:15px;-moz-border-radius-topleft:15px;-moz-border-radius-topright:15px}
#pQp .pqp-box h3{font-weight:normal;line-height:200px;padding:0 15px;color:#fff}
.pQp,.pQp td{color:#444}
#pqp-metrics{background:#000;width:100%}
#pqp-console,#pqp-speed,#pqp-queries,#pqp-memory,#pqp-files,#pqp-config,#pqp-session,#pqp-get,#pqp-post{background-color:#000;border-top:1px solid #ccc;height:200px;overflow:auto}
.pQp .green{color:#588e13!important}
.pQp .blue{color:#3769a0!important}
.pQp .purple{color:#953fa1!important}
.pQp .orange{color:#d28c00!important}
.pQp .red{color:#b72f09!important}
.pQp .yellow{color:#CDCF3A!important}
.pQp .cyan{color:#3EC4D3!important}
.pQp .pink{color:#FF7CAD!important}
.pQp .flesh{color:#FFA46E!important}
#pQp,#pqp-console,#pqp-speed,#pqp-queries,#pqp-memory,#pqp-files,#pqp-config,#pqp-session,#pqp-get,#pqp-post{display:none}
.pQp .console,.pQp .speed,.pQp .queries,.pQp .memory,.pQp .files,.pQp .config,.pQp .session,.pQp .get,.pQp .post{display:block!important}
.pQp .console #pqp-console,.pQp .speed #pqp-speed,.pQp .queries #pqp-queries,.pQp .memory #pqp-memory,.pQp .files #pqp-files,.pQp .config #pqp-config,.pQp .session #pqp-session,.pQp .get #pqp-get,.pQp .post #pqp-post{display:block}
.console td.green,.speed td.blue,.queries td.purple,.memory td.orange,.files td.red,.config td.yellow,.session td.cyan,.get td.pink,.post td.flesh{background:#222!important;border-bottom:6px solid #fff!important;cursor:default!important}
.tallDetails #pQp .pqp-box{height:500px}
.tallDetails #pQp .pqp-box h3{line-height:500px}
.hideDetails #pQp .pqp-box{display:none!important}
.hideDetails #pqp-footer{border-top:1px dotted #444}
.hideDetails #pQp #pqp-metrics td{height:50px;background:#000!important;border-bottom:none!important;cursor:default!important}
.hideDetails #pQp var{font-size:18px;margin:0 0 2px 0}
.hideDetails #pQp h4{font-size:10px}
.hideDetails .heightToggle{visibility:hidden}
#pqp-metrics td{height:80px;width:11%;text-align:center;cursor:pointer;border:1px solid #000;border-bottom:6px solid #444;-webkit-border-top-left-radius:10px;-moz-border-radius-topleft:10px;-webkit-border-top-right-radius:10px;-moz-border-radius-topright:10px}
#pqp-metrics td:hover{background:#222;border-bottom:6px solid #777}
#pqp-metrics .green{border-left:none}
#pqp-metrics .red{border-right:none}
#pqp-metrics h4{text-shadow:#000 1px 1px 1px}
.pqp-side var{text-shadow:#444 1px 1px 1px}
.pQp var{font-size:23px;font-weight:bold;font-style:normal;margin:0 0 3px 0;display:block}
.pQp h4{font-size:12px;color:#fff;margin:0 0 4px 0}
.pQp .main{width:80%;}
.pQp .main table{width:100%;}
*+html .pQp .main{width:78%}
* html .pQp .main{width:77%}
.pQp .main td{padding:7px 15px;text-align:left;background:#151515;border-left:1px solid #333;border-right:1px solid #333;border-bottom:1px dotted #323232;color:#FFF;}
.pQp .main td,pre{font-family:Monaco,"Consolas","Lucida Console","Courier New",monospace;font-size:11px; background: transparent}
.pQp .main td.alt{background:#111}
.pQp .main tr.alt td{background:#2e2e2e;border-top:1px dotted #4e4e4e}
.pQp .main tr.alt td.alt{background:#333}
.pQp .main td b{float:right;font-weight:normal;color:#e6f387}
.pQp .main td:hover{background:#2e2e2e}
.pQp .pqp-side{float:left;width:20%;background:#000;color:#fff;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;text-align:center}
.pQp .pqp-side td{padding:10px 0 5px 0;background-color: #000}
.pQp .pqp-side var{color:#fff;font-size:15px}
.pQp .pqp-side h4{font-weight:normal;color:#f4fcca;font-size:11px}
#pqp-console .pqp-side td{padding:12px 0}
#pqp-console .pqp-side td.alt1{background:#588e13;width:51%}
#pqp-console .pqp-side td.alt2{background-color:#b72f09}
#pqp-console .pqp-side td.alt3{background:#d28c00;border-bottom:1px solid #9c6800;border-left:1px solid #9c6800;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-console .pqp-side td.alt4{background-color:#3769a0;border-bottom:1px solid #274b74}
#pqp-console .main table{width:100%}
#pqp-console td div{width:100%;overflow:hidden}
#pqp-console td.type{font-family:"Lucida Grande",Tahoma,Arial,sans-serif;text-align:center;text-transform:uppercase;font-size:9px;padding-top:9px;color:#f4fcca;vertical-align:top;width:40px}
.pQp .log-log td.type{background:#47740d!important}
.pQp .log-error td.type{background:#9b2700!important}
.pQp .log-memory td.type{background:#d28c00!important}
.pQp .log-speed td.type{background:#2b5481!important}
.pQp .log-log pre{color:#999}
.pQp .log-log td:hover pre{color:#fff}
.pQp .log-memory em,.pQp .log-speed em{float:left;font-style:normal;display:block;color:#fff}
.pQp .log-memory pre,.pQp .log-speed pre{float:right;white-space:normal;display:block;color:#fffd70}
#pqp-speed .pqp-side td{padding:12px 0}
#pqp-speed .pqp-side{background-color:#3769a0}
#pqp-speed .pqp-side td.alt{background-color:#2b5481;border-bottom:1px solid #1e3c5c;border-left:1px solid #1e3c5c;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-queries .pqp-side{background-color:#953fa1;border-bottom:1px solid #662a6e;border-left:1px solid #662a6e}
#pqp-queries .pqp-side td.alt{background-color:#7b3384;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-queries .main b{float:none}
#pqp-queries .main em{display:block;padding:2px 0 0 0;font-style:normal;color:#aaa}
#pqp-memory .pqp-side td{padding:12px 0}
#pqp-memory .pqp-side{background-color:#c48200}
#pqp-memory .pqp-side td.alt{background-color:#ac7200;border-bottom:1px solid #865900;border-left:1px solid #865900;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-files .pqp-side{background-color:#b72f09;border-bottom:1px solid #7c1f00;border-left:1px solid #7c1f00}
#pqp-files .pqp-side td.alt{background-color:#9b2700;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-config .pqp-side{background-color:#CDCF3A;border-bottom:1px solid #CDCF3A;border-left:1px solid #CDCF3A}
#pqp-config .pqp-side td.alt{background-color:#CDCF3A;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-session .pqp-side{background-color:#3EC4D3;border-bottom:1px solid #3EC4D3;border-left:1px solid #3EC4D3}
#pqp-session .pqp-side td.alt{background-color:#3EC4D3;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-get .pqp-side{background-color:#FF7CAD;border-bottom:1px solid #FF7CAD;border-left:1px solid #FF7CAD}
#pqp-get .pqp-side td.alt{background-color:#FF7CAD;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-post .pqp-side{background-color:#FFA46E;border-bottom:1px solid #FFA46E;border-left:1px solid #FFA46E}
#pqp-post .pqp-side td.alt{background-color:#FFA46E;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px}
#pqp-footer {width:100%;background:#000;font-size:11px;border-top:1px solid #ccc}
#pqp-footer td{padding:0!important;border:none!important}
#pqp-footer strong{color:#fff}
#pqp-footer a{color:#999;padding:5px 10px;text-decoration:none}
#pqp-footer .credit{width:20%;text-align:left}
#pqp-footer .pqp-actions{width:80%;text-align:right}
#pqp-footer .pqp-actions a{float:right;width:auto}
#pqp-footer a:hover,#pqp-footer a:hover strong,#pqp-footer a:hover b{background:#fff;color:blue!important;text-decoration:underline}
#pqp-footer a:active,#pqp-footer a:active strong,#pqp-footer a:active b{background:#ecf488;color:green!important}
CSS
);

	$return_output = '';
	$return_output .=<<<JAVASCRIPT
<!-- JavaScript -->
>>>>>>> aa5276884e0263c0864dfaf6e44f57f2204a9bc6
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

<<<<<<< HEAD
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
=======
	$return_output .=<<<PQPTABS
<div id="pQp" class="console">
<table id="pqp-metrics" cellspacing="0">
<tr>
	<td class="green" onclick="changeTab('console');">
		<var>$logCount</var>
		<h4>Console</h4>
	</td>
	<td class="blue" onclick="changeTab('speed');">
		<var>$speedTotal</var>
		<h4>Load Time</h4>
	</td>
	<td class="purple" onclick="changeTab('queries');">
		<var>$queryCount Queries</var>
		<h4>Database</h4>
	</td>
	<td class="orange" onclick="changeTab('memory');">
		<var>$memoryUsed</var>
		<h4>Memory Used</h4>
	</td>
	<td class="red" onclick="changeTab('files');">
		<var>{$fileCount} Files</var>
		<h4>Included</h4>
	</td>
	<td class="yellow" onclick="changeTab('config');">
		<var>{$configCount} Config</var>
		<h4>items loaded</h4>
	</td>
	<td class="cyan" onclick="changeTab('session');">
		<var>{$sessionCount} Session</var>
		<h4>vars loaded</h4>
	</td>
	<td class="pink" onclick="changeTab('get');">
		<var>{$getCount} GET</var>
		<h4>vars loaded</h4>
	</td>
	<td class="flesh" onclick="changeTab('post');">
		<var>{$postCount} POST</var>
		<h4>vars loaded</h4>
	</td>
</tr>
</table>
PQPTABS;

	$return_output .='<div id="pqp-console" class="pqp-box">';

if($logCount ==  0) {
	$return_output .='<h3>This panel has no log items.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
		<tr>
			<td class="alt1"><var>'.$output['logs']['logCount'].'</var><h4>Logs</h4></td>
			<td class="alt2"><var>'.$output['logs']['errorCount'].'</var> <h4>Errors</h4></td>
		</tr>
		<tr>
			<td class="alt3"><var>'.$output['logs']['memoryCount'].'</var> <h4>Memory</h4></td>
			<td class="alt4"><var>'.$output['logs']['speedCount'].'</var> <h4>Speed</h4></td>
		</tr>
		</table>
		<div class="main"><table cellspacing="0">';

		$class = '';
		foreach($output['logs']['console'] as $log) {
			$return_output .='<tr class="log-'.$log['type'].'">
				<td class="type">'.$log['type'].'</td>
				<td class="'.$class.'">';
			if($log['type'] == 'log') {
				$return_output .='<div><pre>'.$log['data'].'</pre></div>';
			}
			elseif($log['type'] == 'memory') {
				$return_output .='<div><pre>'.$log['data'].'</pre> <em>'.$log['dataType'].'</em>: '.$log['name'].' </div>';
			}
			elseif($log['type'] == 'speed') {
				$return_output .='<div><pre>'.$log['data'].'</pre> <em>'.$log['name'].'</em></div>';
			}
			elseif($log['type'] == 'error') {
				$return_output .='<div><em>Line '.$log['line'].'</em> : '.$log['data'].' <pre>'.$log['file'].'</pre></div>';
			}

			$return_output .='</td></tr>';
			if($class == '') $class = 'alt';
			else $class = '';
		}

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .='<div id="pqp-speed" class="pqp-box">';

if($output['logs']['speedCount'] ==  0) {
	$return_output .='<h3>This panel has no log items.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
		  <tr><td><var>'.$output['speedTotals']['total'].'</var><h4>Load Time</h4></td></tr>
		  <tr><td class="alt"><var>'.$output['speedTotals']['allowed'].'</var> <h4>Max Execution Time</h4></td></tr>
		 </table>
		<div class="main"><table cellspacing="0">';

		$class = '';
		foreach($output['logs']['console'] as $log) {
			if($log['type'] == 'speed') {
				$return_output .='<tr class="log-'.$log['type'].'">
				<td class="'.$class.'">';
				$return_output .='<div><pre>'.$log['data'].'</pre> <em>'.$log['name'].'</em></div>';
				$return_output .='</td></tr>';
				if($class == '') $class = 'alt';
				else $class = '';
			}
		}

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .='<div id="pqp-queries" class="pqp-box">';

if($output['queryTotals']['count'] ==  0) {
	$return_output .='<h3>This panel has no log items.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
		  <tr><td><var>'.$output['queryTotals']['count'].'</var><h4>Total Queries</h4></td></tr>
		  <tr><td><var>'.$output['queryTotals']['time'].'</var> <h4>Total Time</h4></td></tr>
		  <tr><td class="alt"><var>'.$output['queryTotals']['duplicates'].'</var> <h4>Duplicates</h4></td></tr>
		 </table>
		<div class="main"><table cellspacing="0">';

		$class = '';
		foreach($output['queries'] as $query) {
			$return_output .='<tr>
				<td class="'.$class.'">'.$query['sql'];
			$return_output .='<em>';
			if(isset($query['explain'])) {
					isset($query['explain']['possible_keys']) and $return_output .='Possible keys: <b>'.$query['explain']['possible_keys'].'</b> &middot;';
					isset($query['explain']['key']) and $return_output .='Key Used: <b>'.$query['explain']['key'].'</b> &middot;';
					isset($query['explain']['type']) and $return_output .='Type: <b>'.$query['explain']['type'].'</b> &middot;';
					isset($query['explain']['type']) and $return_output .='Rows: <b>'.$query['explain']['rows'].'</b> &middot;';
			}
			$return_output .='Speed: <b>'.$query['time'].'</b>';
			$query['duplicate'] and $return_output .=' &middot; <b>DUPLICATE</b>';
			$return_output .='</em></td></tr>';
			if($class == '') $class = 'alt';
			else $class = '';
		}

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .='<div id="pqp-memory" class="pqp-box">';

if($output['logs']['memoryCount'] ==  0) {
	$return_output .='<h3>This panel has no log items.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
		  <tr><td><var>'.$output['memoryTotals']['used'].'</var><h4>Used Memory</h4></td></tr>
		  <tr><td class="alt"><var>'.$output['memoryTotals']['total'].'</var> <h4>Total Available</h4></td></tr>
		 </table>
		<div class="main"><table cellspacing="0">';

		$class = '';
		foreach($output['logs']['console'] as $log) {
			if($log['type'] == 'memory') {
				$return_output .='<tr class="log-'.$log['type'].'">';
				$return_output .='<td class="'.$class.'"><b>'.$log['data'].'</b> <em>'.$log['dataType'].'</em>: '.$log['name'].'</td>';
				$return_output .='</tr>';
				if($class == '') $class = 'alt';
				else $class = '';
			}
		}

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .='<div id="pqp-files" class="pqp-box">';

if($output['fileTotals']['count'] ==  0) {
	$return_output .='<h3>This panel has no log items.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
		  	<tr><td><var>'.$output['fileTotals']['count'].'</var><h4>Total Files</h4></td></tr>
			<tr><td><var>'.$output['fileTotals']['size'].'</var> <h4>Total Size</h4></td></tr>
			<tr><td class="alt"><var>'.$output['fileTotals']['largest'].'</var> <h4>Largest</h4></td></tr>
		 </table>
		<div class="main"><table cellspacing="0">';

		$class ='';
		foreach($output['files'] as $file) {
			$return_output .='<tr><td class="'.$class.'"><b>'.$file['size'].'</b> '.$file['name'].'</td></tr>';
			if($class == '') $class = 'alt';
			else $class = '';
		}

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .='<div id="pqp-config" class="pqp-box">';

if($configCount ==  0) {
	$return_output .='<h3>This panel has no config items.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
			<tr><td class="alt"><var>'.$configCount.'</var> <h4>Configuration items</h4></td></tr>
		 </table>
		<div class="main"><table cellspacing="0">';

		$return_output .= $output['configItems'];

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .='<div id="pqp-session" class="pqp-box">';

if($sessionCount ==  0) {
	$return_output .='<h3>This panel has no session variables.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
			<tr><td class="alt"><var>'.$sessionCount.'</var> <h4>Session variables</h4></td></tr>
		 </table>
		<div class="main"><table cellspacing="0">';

		$return_output .= $output['sessionItems'];

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .='<div id="pqp-get" class="pqp-box">';

if($getCount ==  0) {
	$return_output .='<h3>This panel has no GET variables.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
			<tr><td class="alt"><var>'.$getCount.'</var> <h4>GET variables</h4></td></tr>
		 </table>
		<div class="main"><table cellspacing="0">';

		$return_output .= $output['getItems'];

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .='<div id="pqp-post" class="pqp-box">';

if($postCount ==  0) {
	$return_output .='<h3>This panel has no POST variables.</h3>';
}
else {
	$return_output .='<table class="pqp-side" cellspacing="0">
			<tr><td class="alt"><var>'.$postCount.'</var> <h4>POST variables</h4></td></tr>
		 </table>
		<div class="main"><table cellspacing="0">';

		$return_output .= $output['postItems'];

		$return_output .='</table></div>';
}

$return_output .='</div>';

$return_output .=<<<FOOTER
	<table id="pqp-footer" cellspacing="0">
		<tr>
			<td class="credit">
				<a href="http://particletree.com" target="_blank">
				Based on
				<strong>PHP</strong>
				<b class="green">Q</b><b class="blue">u</b><b class="purple">i</b><b class="orange">c</b><b class="red">k</b>
				Profiler</a></td>
			<td class="pqp-actions">
				<a href="#" onclick="toggleDetails();return false">Details</a>
				<a class="heightToggle" href="#" onclick="toggleHeight();return false">Height</a>
				<a href="#" onclick="toggleBottom();return false">Bottom</a>
			</td>
		</tr>
	</table>
FOOTER;

	$return_output .='</div></div>';

	return $return_output;
}

?>
>>>>>>> aa5276884e0263c0864dfaf6e44f57f2204a9bc6
