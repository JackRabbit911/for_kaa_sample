<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">	
	</head>

	<body>
		
		<style type="text/css">
		#wn_error { background: #e2e3e5; font-size: 1em; font-family:sans-serif; text-align: left; color: #383d41; }
		#wn_error h1,
		#wn_error h2 { margin: 0; padding: 1em; font-size: 1em; font-weight: bolder; background: #f8d7da ; color: #721c24; border-bottom: solid 1px #f5c6cb;}
			#wn_error h1 a,
			#wn_error h2 a { color: #1b1e21; }
		#wn_error h2 { background: #d6d8d9; border-top: solid 1px #c6c8ca; border-bottom: none;}
		#wn_error h3 { margin: 0; padding: 0.4em 0 0; font-size: 1em; font-weight: normal; }
		#wn_error p { margin: 0; padding: 0.2em 0; }
		#wn_error a { color: #1b323b; }
		#wn_error pre { overflow: auto; white-space: pre-wrap; }
		#wn_error table { width: 100%; display: block; margin: 0 0 0.4em; padding: 0; border-collapse: collapse; background: #fff; }
			#wn_error table td { border: solid 1px #ddd; text-align: left; vertical-align: top; padding: 0.4em; }
		#wn_error div.content { padding: 0.4em 1em 1em; overflow: hidden; }
		#wn_error pre.source { margin: 0 0 1em; padding: 0.4em; background: #fff; border: dotted 1px #b7c680; line-height: 1.2em; }
			#wn_error pre.source span.line { display: block; }
			#wn_error pre.source span.highlight { background: #fff3cd; }
				#wn_error pre.source span.line span.number { color: #666; }
		#wn_error ol.trace { display: block; margin: 0 0 0 2em; padding: 0; list-style: decimal; }
			#wn_error ol.trace li { margin: 0; padding: 0; }

		span.uri { float:right; font-weight:normal!important; }
		.js .collapsed { display: none; }
		</style>
		<script type="text/javascript">
		document.documentElement.className = document.documentElement.className + ' js';
		function koggle(elem)
		{
			elem = document.getElementById(elem);

			if (elem.style && elem.style['display'])
				// Only works with the "style" attr
				var disp = elem.style['display'];
			else if (elem.currentStyle)
				// For MSIE, naturally
				var disp = elem.currentStyle['display'];
			else if (window.getComputedStyle)
				// For most other browsers
				var disp = document.defaultView.getComputedStyle(elem, null).getPropertyValue('display');

			// Toggle the state of the "display" style
			elem.style.display = disp == 'block' ? 'none' : 'block';
			return false;
		}
		</script>
		<?php echo $content;?>
	</body>
</html>