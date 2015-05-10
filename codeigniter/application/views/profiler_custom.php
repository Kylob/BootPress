<?php

$this->load->helper('language');
$profiler_logo = '<img width="22" height="22" alt="Profiler" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADDAAAAwwBArw9FwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAJeSURBVDiNnZU5aFVBFIa/eXlxAZcsgiagwUYEUSyMoAlamEYEQS0sJIWlYGPpEgyKoK0LtomKWqhgIVgbIZWFIC4QRG2MSzZRojHms7jzZLxvhODf3Dnn/Oe/Z86cewcSqCfVKbWfeULtVyfVE/8ibFXn1JtqTym2XN2rrs7k9ai3Ym5nTviCOpDxd6mjFphRz6ghwxtUz9fsShJbAwyVyAG4CqwEhoFxoA84mNn0ENBRM6pJoAWYLJG7gGvAS+AXsApYCjSqIYRgwp2IGnUVB2CuJPwTWA+MAR+AL9HXCywrceeiRp3w4lhViuPAEqAT2A6sBd4DI8CRjPCimpG2YkXcTorLQDPwFNgXhZ8Dz4AXJe4E0FozAoDaDIwCbSGE8VpQHY4vPEdxcOMULTgGjIYQehNuTaM9hDBWUavAWeBBKhpxDLgTEw7EindSHOarlBhCmAAeAKfVahX4FLfRTT2aKHr/NYo1AYPAOsAM/yjwGOitZIJpFQ8penwdGKAYvU3x2ZJL+bNSq+oV9W5OXO1Wl5V829QtGe499VJsL6gt6g81V8W8oDZHjVaIcxwP7TWwIZPQpe6ah/ZGYCSEMAZ/z/Fnin6WsRtoVxsppqMf+BZCOFTiNVN8oZSFp4GGUrUNFIf1iGKGZ+P6U6aACvA9Nf7olGyAhRSf8BtgCmiM/gbqUSEZwbTicYo5TTEDPAF64rqD4g/3MSPcEjXqKn4H7EiZIYRZiu0fBu4DF4HNIYQbGeFu4G2dV+2M18utzNXUpu5X96gLSrEe9fY/r6ZI6lOn/+MynVZPpf7fUrZQiTNc5+cAAAAASUVORK5CYII=">';
$profiler_color = '#D51B20';
// red		'#D51B20';
// blue		'#3769A0';
 
?>

<style type="text/css">
	#ci-profiler-menu-open{background-color:#141722;position:fixed;right:0;bottom:0;padding:5px 7px;}
	#ci-profiler-menu-open img{display:block;}
	#ci-profiler-menu-open:hover{background-color:<?php echo $profiler_color; ?>;}
	#codeigniter-profiler{height:400px;background-color:#141722;clear:both;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;position:fixed;bottom:0;left:0;width:100%;z-index:1000;}
	#profiler-panel{background-color:#1f2332;padding:0 5px;}
	#profiler-panel .main{font-family:Monaco,'Lucida Console','Courier New',monospace;}
	#profiler-panel a:hover,#profiler-panel a:focus{text-decoration:none;}
	.ci-profiler-box{height:340px;padding:0 10px 10px 10px;margin:0 0 10px 0;overflow:auto;color:#fff;font-size:11px!important;}
	.ci-profiler-box h2{color:#fff;font-weight:bolder;font-size:16px!important;padding:0 0 0 5px;line-height:2.0;}
	#ci-profiler-menu a{display:inline-block;font-size:13px;font-weight:200;line-height:25px;padding:3px 10px;color:#fff;text-decoration:none;cursor:pointer;}
	#ci-profiler-menu a:first-child img{vertical-align:middle;margin-bottom:2px;cursor:default;}
	#ci-profiler-menu a:first-child:hover,#ci-profiler-menu a:last-child:hover{background-color:transparent!important;}
	#ci-profiler-menu a span{background-color:#fff;border-radius:4px;font-size:11px;font-weight:600;padding:2px 5px;vertical-align:bottom;}
	#ci-profiler-console .hilight{color:<?php echo $profiler_color; ?>!important;}
	#ci-profiler-menu-console:hover,#ci-profiler-menu-console:focus,#ci-profiler-menu-console.current,#ci-profiler-console h2{background-color:<?php echo $profiler_color; ?>;}
	#ci-profiler-menu-console span{color:<?php echo $profiler_color; ?>;}
	#ci-profiler-loaded .hilight{color:<?php echo $profiler_color; ?>!important;}
	#ci-profiler-menu-loaded:hover,#ci-profiler-menu-loaded:focus,#ci-profiler-menu-loaded.current,#ci-profiler-loaded h2{background-color:<?php echo $profiler_color; ?>;}
	#ci-profiler-menu-loaded span{color:<?php echo $profiler_color; ?>;}
	#ci-profiler-variables .hilight{color:<?php echo $profiler_color; ?>!important;}
	#ci-profiler-menu-variables:hover,#ci-profiler-menu-variables:focus,#ci-profiler-menu-variables.current,#ci-profiler-variables h2{background-color:<?php echo $profiler_color; ?>;}
	#ci-profiler-menu-variables span{color:<?php echo $profiler_color; ?>;}
	#ci-profiler-databases .hilight{color:<?php echo $profiler_color; ?>!important;}
	#ci-profiler-menu-databases:hover,#ci-profiler-menu-databases:focus,#ci-profiler-menu-databases.current,#ci-profiler-databases h2{background-color:<?php echo $profiler_color; ?>;}
	#ci-profiler-menu-databases span{color:<?php echo $profiler_color; ?>;}
	#ci-profiler-smarty .hilight{color:<?php echo $profiler_color; ?>!important;}
	#ci-profiler-menu-smarty:hover,#ci-profiler-menu-smarty:focus,#ci-profiler-menu-smarty.current,#ci-profiler-smarty h2{background-color:<?php echo $profiler_color; ?>;}
	#ci-profiler-menu-smarty span{color:<?php echo $profiler_color; ?>;}
	#ci-profiler-files .hilight{color:<?php echo $profiler_color; ?>!important;}
	#ci-profiler-menu-files:hover,#ci-profiler-menu-files:focus,#ci-profiler-menu-files.current,#ci-profiler-files h2{background-color:<?php echo $profiler_color; ?>;}
	#ci-profiler-menu-files span{color:<?php echo $profiler_color; ?>;}
	#codeigniter-profiler pre{background-color:#fff!important;}
	#codeigniter-profiler table{width:100%;}
	#codeigniter-profiler table.main td{padding:7px 15px;text-align:left;vertical-align:top;color:#000;line-height:1.5;background-color:#fff!important;font-size:12px!important;}
	#codeigniter-profiler table.main td.hilight{white-space:nowrap;}
	#codeigniter-profiler table.main tr:hover td{background-color:#fcfcfc!important;}
	#codeigniter-profiler table.main code{padding:0;background:transparent;border:0;color:#fff;}
	#codeigniter-profiler table .faded{color:#aaa!important;}
	#codeigniter-profiler table .small{font-size:10px;letter-spacing:1px;font-weight:lighter;}
	#ci-profiler-menu-exit{background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAPCAYAAAA71pVKAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkQwQUYxNjIzQkNGOTExRTM4OTY3QzU4NjQ2QzdDQkMzIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkQwQUYxNjI0QkNGOTExRTM4OTY3QzU4NjQ2QzdDQkMzIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RDBBRjE2MjFCQ0Y5MTFFMzg5NjdDNTg2NDZDN0NCQzMiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6RDBBRjE2MjJCQ0Y5MTFFMzg5NjdDNTg2NDZDN0NCQzMiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6BRWmSAAABa0lEQVR42lzTzyuEURTG8TtjzDSKjEayUBbU/AWymZ0s/KghG2xkwcZCrKSklGzskGJrR6MsZEGRiIUNC0tRo8mGIUS8vrd5bl3vrU/dc885t/ed804kCIKEMSaKH3yZ8upBB+rwjCPsKhdHBX4NzUnEYfddeMQHbnCOa7zjCTnVVdo+4zVOB+W1ibTOnBRWlZ91F7hkTomBUFM8FHerbtjG7tCuDe1rsYaM4mbFDYqXVR81uiXwbj9QfIt2XCk+9WrsGrebHVzqMIJeFIL/q4g+5W3dCfbtiNIoemPYQ7/5v4aQR0JxAfW2+RU1OvxGCxZCzXPIeN9BCiX7CFOaq3ufQz1qCWOar10XXs0L5uymSslJJdpwjE7FWb1jVvGo6lPuphkdtIbmmgjFTapbdHOOKbGlxEiowRlUPq845r5tN4J5FdxrhOvYxp3Ol7zLkhHvX/UJnsU0YgJZVOMNZ1jBg37tpP1X/QkwAM/DSXbJEwZhAAAAAElFTkSuQmCC) 0% 0% no-repeat;position:absolute;right:5px;top:9px;height:2.1em;width:2em;}
	.ci-profiler-box .hilight{width:9em;}
	div.ci-profiler-box{display:none;}
</style>

<script type="text/javascript">

	var ci_profiler_bar = {

		menu: null,	/** Either 'open' or 'closed' */

		href: null,	/** Current menu link href (obj) open */

		id: null,	/** Current menu link id (el) open */

		table: null,	/** Latest panel table that was toggled open */

		currentClass: 'current',	/** The class name used for the active section */

		openIconId: 'ci-profiler-menu-open',	/** The id of the icon used to open the profiler */

		profilerId: 'codeigniter-profiler',	/** The id of the profiler itself */

		show: function(obj, el) {	/** Toggle a menu section */
			this.off(this.href, this.id);
			this.href = obj;
			this.id = el;
			this.on(this.href, this.id);
			this.set_cookie();
		},

		on: function(obj, el) {	/** Turn an element on */
			if (document.getElementById(obj) != null) {
				document.getElementById(obj).style.display = 'block';
				document.getElementById(obj).className += " " + this.currentClass;
				document.getElementById(el).className += " " + this.currentClass;
			}
		},

		off: function(obj, el) {	/** Turn an element off */
			if (document.getElementById(obj) != null) {
				document.getElementById(obj).style.display = 'none';
				document.getElementById(obj).className = document.getElementById(obj).className.replace(this.currentClass, '');
				document.getElementById(el).className = document.getElementById(el).className.replace(this.currentClass, '');
			}
		},

		toggle: function(obj) {	/** Toggle an element */
			if (typeof obj == 'string') {
				obj = document.getElementById(obj);
			}
			if (obj) obj.style.display = obj.style.display == 'none' ? 'block' : 'none';
		},

		open: function() {	/** Open the menu */
			document.getElementById(this.openIconId).style.display = 'none';
			document.getElementById(this.profilerId).style.display = 'block';
			this.menu = 'open';
			this.set_cookie();
		},

		close : function() {	/** Close the menu */
			document.getElementById(this.profilerId).style.display = 'none';
			document.getElementById(this.openIconId).style.display = 'block';
			this.menu = 'closed';
			this.set_cookie();
		},

		read_cookie: function() {	/** Read the cookie */
			var nameEQ = "Profiler=",
				ca = document.cookie.split(';'),
				i = 0,
				c;
			for (i = 0; i < ca.length; i++) {
				c = ca[i];
				while (c.charAt(0) == ' ') {
					c = c.substring(1, c.length);
				}
				if (c.indexOf(nameEQ) == 0) {
					var value = c.substring(nameEQ.length, c.length).split(',');
					for (var k in value) if (value[k] == '') value[k] = null;
					return value;
				}
			}
			return ['open', null, null, null];
		},

		set_cookie: function() {	/** Set the cookie. */
			var value = [this.menu, this.href, this.id, this.table],
				expires = "; expires=",
				date = new Date();
			date.setTime(date.getTime() + (365*24*60*60*1000));
			expires += date.toGMTString();
			document.cookie = "Profiler=" + value.join(',') + expires + "; path=/";
		},

		set_load_state: function() {	/** Set the load state */
			var cookie = this.read_cookie();
			if (cookie[0] == 'open') {
				this.open();
			} else {
				this.close();
			}
			if (cookie[1]) this.show(cookie[1], cookie[2]);
			if (cookie[3]) this.toggle_data_table(cookie[3]);
		},

		toggle_data_table: function(obj, save) {	/** Toggle a data table */
			if (typeof obj == 'string') {
				var string = obj;
				if (this.table && obj != this.table) this.toggle_data_table(this.table, false);
				obj = document.getElementById(obj + '_table');
			}
			if (obj) {
				obj.style.display = (obj.style.display == 'none') ? '' : 'none';
				if (save !== false) {
					this.table = (obj.style.display == 'none') ? null : string;
					this.set_cookie();
				}
			}
		}
		
	};

	window.onload = function(){ ci_profiler_bar.set_load_state(); }

</script>

<?php

$html = '<a href="#codeigniter-profiler" id="ci-profiler-menu-open" style="display:block;" onclick="ci_profiler_bar.open(); return false;">' . $profiler_logo . '</a>';

$html .= '<div id="codeigniter-profiler" style="display:none;">';

	$html .= '<div id="ci-profiler-menu">';

		$html .= '<a>' . $profiler_logo . '</a>';
	
		if (isset($sections['console']) && !empty($sections['console'])) {
			$html .= '<a href="#ci-profiler-console" id="ci-profiler-menu-console" onclick="ci_profiler_bar.show(\'ci-profiler-console\', \'ci-profiler-menu-console\'); return false;">';
				$html .= 'Console <span>' . count($sections['console']) . '</span>';
			$html .= '</a>';
		}
		
		$html .= '<a href="#ci-profiler-loaded" id="ci-profiler-menu-loaded" onclick="ci_profiler_bar.show(\'ci-profiler-loaded\', \'ci-profiler-menu-loaded\'); return false;">';
			$html .= 'Loaded <span>' . round($this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end') * 1000) . ' ms</span>';
		$html .= '</a>';
		
		$html .= '<a href="#ci-profiler-variables" id="ci-profiler-menu-variables" onclick="ci_profiler_bar.show(\'ci-profiler-variables\', \'ci-profiler-menu-variables\'); return false;">';
			$html .= 'Variables <span>' . $sections['memory_usage'] . '</span>';
		$html .= '</a>';
		
		if (isset($sections['queries']) && !empty($sections['queries'])) {
			$html .= '<a href="#ci-profiler-databases" id="ci-profiler-menu-databases" onclick="ci_profiler_bar.show(\'ci-profiler-databases\', \'ci-profiler-menu-databases\'); return false;">';
				$html .= 'Databases <span>' . count($sections['queries']) . '</span>';
			$html .= '</a>';
		}
		
		if (isset($sections['smarty']) && !empty($sections['smarty'])) {
			$html .= '<a href="#ci-profiler-smarty" id="ci-profiler-menu-smarty" onclick="ci_profiler_bar.show(\'ci-profiler-smarty\', \'ci-profiler-menu-smarty\'); return false;">';
				$html .= 'Smarty <span>' . count($sections['smarty']) . '</span>';
			$html .= '</a>';
		}
		
		if (isset($sections['files'])) {
			$html .= '<a href="#ci-profiler-files" id="ci-profiler-menu-files" onclick="ci_profiler_bar.show(\'ci-profiler-files\', \'ci-profiler-menu-files\'); return false;">';
				$html .= 'Files <span>' . count($sections['files']) . '</span>';
			$html .= '</a>';
		}
		
        	$html .= '<a href="#ci-profiler-menu-open" id="ci-profiler-menu-exit" onclick="ci_profiler_bar.close(); return false;"></a>';

	$html .= '</div>';

	$html .= '<div id="profiler-panel">';

		if (isset($sections['console']) && !empty($sections['console'])) {
			$html .= '<div id="ci-profiler-console" class="ci-profiler-box">';
			foreach ($sections['console'] as $key => $log) {
				$id = 'console_' . md5($key . $log['file'] . ' on Line ' . $log['line']);
				$html .= '<a href="#' . $id . '_table" onclick="ci_profiler_bar.toggle_data_table(\'' . $id . '\'); return false;">';
					$html .= '<h2>' . $log['memory'] . ' in ' . $log['time'] . ' @ ' . $log['file'] . ' on Line ' . $log['line'] . '</h2>';
				$html .= '</a>';
				$html .= '<div id="' . $id . '_table" style="display:none;">' . $log['data'] . '</div>';
			}
			$html .= '</div>';
		}
		
		$html .= '<div id="ci-profiler-loaded" class="ci-profiler-box">';
			$html .= '<h2>' . lang('profiler_benchmarks') . '</h2>';
			$html .= '<table class="main">';
			foreach ($sections['benchmarks'] as $benchmark => $time) {
				$html .= '<tr>';
					$html .= '<td class="hilight">' . $time . '</td>';
					$html .= '<td>' . $benchmark . '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
		$html .= '</div>';
		
		$html .= '<div id="ci-profiler-variables" class="ci-profiler-box">';
			$html .= '<h2>Info</h2>';
			$html .= '<table class="main">';
			foreach (array('memory_usage'=>'Memory Usage', 'uri_string'=>'URI String', 'controller_info'=>'Controller Info') as $section => $header) {
				if (isset($sections[$section])) {
					$html .= '<tr>';
						$html .= '<td class="hilight">' . $header . '</td>';
						$html .= '<td>' . $sections[$section] . '</td>';
					$html .= '</tr>';
				}
			}
			$html .= '</table>';
			$variables = array('get'=>'$_GET Variables', 'session_data'=>'$_SESSION Variables', 'http_headers'=>'$_SERVER Variables', 'config'=>'CodeIgniter Config');
			if (isset($sections['post']['files'])) {
				$sections['post_files'] = $sections['post']['files'];
				$variables = array('post_files'=>'$_FILES Data') + $variables;
			}
			if (isset($sections['post']['vars'])) {
				$sections['post_vars'] = $sections['post']['vars'];
				$variables = array('post_vars'=>'$_POST Data') + $variables;
			}
			foreach ($variables as $section => $header) {
				if (isset($sections[$section]) && !empty($sections[$section])) {
					$html .= '<a href="#' . $section . '_table" onclick="ci_profiler_bar.toggle_data_table(\'' . $section . '\'); return false;"><h2>' . $header . '</h2></a>';
					$html .= '<table class="main" id="' . $section . '_table" style="display:none;">';
					foreach ($sections[$section] as $key => $val) {
						$html .= '<tr>';
							$html .= '<td class="hilight">' . $key . '</td>';
							$html .= '<td>' . $val . '</td>';
						$html .= '</tr>';
					}
					$html .= '</table>';
				}
			}
		$html .= '</div>';
		
		if (isset($sections['queries']) && !empty($sections['queries'])) {
			$html .= '<div id="ci-profiler-databases" class="ci-profiler-box">';
				foreach ($sections['queries'] as $database => $queries) {
					$id = 'database_' . md5($database);
					$name = array_shift($queries);
					$time = array_shift($queries);
					$count = count($queries);
					$html .= '<a href="#' . $id . '_table" onclick="ci_profiler_bar.toggle_data_table(\'' . $id . '\'); return false;">';
						$html .= '<h2>' . ($count == 1 ? '1 query' : $count . ' queries') . ' in ' . $time . ' @ ' . $database . '</h2>';
					$html .= '</a>';
					$html .= '<table class="main" id="' . $id . '_table" style="display:none;">';
					if ($count === 0) {
						$html .= '<tr><td colspan="2">' . lang('profiler_no_queries') . '</td></tr>';
					} else {
						foreach ($queries as $db) {
							$html .= '<tr>';
								$html .= '<td class="hilight">' . $db['time'] . '</td>';
								$html .= '<td>' . $db['query'] . '</td>';
							$html .= '</tr>';
						}
					}
					$html .= '</table>';
				}
			$html .= '</div>';
		}
		
		if (isset($sections['smarty']) && !empty($sections['smarty'])) {
			$html .= '<div id="ci-profiler-smarty" class="ci-profiler-box">';
			foreach ($sections['smarty'] as $info) {
				$id = 'smarty_' . md5($info['file']);
				$html .= '<a href="#' . $id . '_table" onclick="ci_profiler_bar.toggle_data_table(\'' . $id . '\'); return false;">';
					$html .= '<h2>' . $info['memory'] . ' in ' . $info['start'] . ' @ ' . $info['file'] . ' for ' . $info['time'] . '</h2>';
				$html .= '</a>';
				$html .= '<table class="main" id="' . $id . '_table" style="display:none;">';
				foreach ($info['vars'] as $key => $value) {
					$html .= '<tr>';
						$html .= '<td class="hilight">' . $key . '</td>';
						$html .= '<td>' . $value . '</td>';
					$html .= '</tr>';
				}
				$html .= '</table>';
			}
			$html .= '</div>';
		}
		
		if (isset($sections['files'])) {
			$html .= '<div id="ci-profiler-files" class="ci-profiler-box">';
				$html .= '<h2>Included Files</h2>';
				$html .= '<table class="main"><tr><td class="hilight"><ol>';
				foreach ($sections['files'] as $file) $html .= '<li>' . $file . '</li>';
				$html .= '</ol></td></tr></table>';
			$html .= '</div>';
		}
		
	$html .= '</div>';

$html .= '</div>';

echo $html;

?>
