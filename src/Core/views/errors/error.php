<!DOCTYPE html>
<?php use WN\Core\Helper\Accept;
use WN\Core\Exception\Debug;
$lang = Accept::language();?>

<html lang="<?php echo $lang?>">

<head>
	<meta charset="utf-8">	
</head>

<body>
<?php $error_id = uniqid('error');?>
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
<div id="wn_error">
	<? if($code) $code = ' [ '.$code.' ] '; ?>
	<h1><span class="type"><?=$func?> <?php echo $class ?><?php echo $code ?>:</span> <span class="message"><?php echo htmlspecialchars( (string) $message, ENT_QUOTES | ENT_IGNORE, 'UTF-8', TRUE); ?></span></h1>
	<div id="<?php echo $error_id ?>" class="content">
		<p><span class="file"><?php echo Debug::path($file) ?> [ <?php echo $line ?> ]</span></p>
		<?php echo Debug::source($file, $line) ?>
		<ol class="trace">
			<?php foreach(Debug::trace($trace) as $i => $step): ?>
				<li>
					<p>
						<span class="file">
							<?php if ($step['file']): $source_id = $error_id.'source'.$i; ?>
								<a href="#<?php echo $source_id ?>" onclick="return koggle('<?php echo $source_id ?>')"><?php echo Debug::path($step['file']) ?> [ <?php echo $step['line'] ?> ]</a>
							<?php else: ?>
								{<?php echo 'PHP internal call' ?>}
							<?php endif ?>
						</span>
						&raquo;
						<?php echo $step['function'] ?>(<?php if ($step['args']): $args_id = $error_id.'args'.$i; ?><a href="#<?php echo $args_id ?>" onclick="return koggle('<?php echo $args_id ?>')"><?php echo 'arguments' ?></a><?php endif ?>)
					</p>
					<?php if (isset($args_id)): ?>
					<div id="<?php echo $args_id ?>" class="collapsed">
						<table cellspacing="0">
						<?php foreach ($step['args'] as $name => $arg): ?>
							<tr>
								<td><code><?php echo $name ?></code></td>
								<td><pre><?php echo Debug::dump($arg) ?></pre></td>
							</tr>
						<?php endforeach ?>
						</table>
					</div>
					<?php endif ?>
					<?php if (isset($source_id)): ?>
						<pre id="<?php echo $source_id ?>" class="source collapsed"><code><?php echo $step['source'] ?></code></pre>
					<?php endif ?>
				</li>
				<?php unset($args_id, $source_id); ?>
			<?php endforeach ?>
		</ol>
	</div>
	
	<h2><a href="#<?php echo $env_id = $error_id.'environment' ?>" onclick="return koggle('<?php echo $env_id ?>')"><?php echo 'Environment' ?></a></h2>
	<div id="<?php echo $env_id ?>" class="content collapsed">
		<?php $included = get_included_files() ?>
		<h3><a href="#<?php echo $env_id = $error_id.'environment_included' ?>" onclick="return koggle('<?php echo $env_id ?>')"><?php echo 'Included files' ?></a> (<?php echo count($included) ?>)</h3>
		<div id="<?php echo $env_id ?>" class="collapsed">
			<table cellspacing="0">
				<?php foreach ($included as $file): ?>
				<tr>
					<td><code><?php echo Debug::path($file) ?></code></td>
				</tr>
				<?php endforeach ?>
			</table>
		</div>
		<?php $included = get_loaded_extensions() ?>
		<h3><a href="#<?php echo $env_id = $error_id.'environment_loaded' ?>" onclick="return koggle('<?php echo $env_id ?>')"><?php echo 'Loaded extensions' ?></a> (<?php echo count($included) ?>)</h3>
		<div id="<?php echo $env_id ?>" class="collapsed">
			<table cellspacing="0">
				<?php foreach ($included as $file): ?>
				<tr>
					<td><code><?php echo Debug::path($file) ?></code></td>
				</tr>
				<?php endforeach ?>
			</table>
		</div>
		<?php foreach (array('_SESSION', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER') as $var): ?>
		<?php if (empty($GLOBALS[$var]) OR ! is_array($GLOBALS[$var])) continue ?>
		<h3><a href="#<?php echo $env_id = $error_id.'environment'.strtolower($var) ?>" onclick="return koggle('<?php echo $env_id ?>')">$<?php echo $var ?></a></h3>
		<div id="<?php echo $env_id ?>" class="collapsed">
			<table cellspacing="0">
				<?php foreach ($GLOBALS[$var] as $key => $value): ?>
				<tr>
					<td><code><?php echo htmlspecialchars( (string) $key, ENT_QUOTES, 'UTF-8', TRUE); ?></code></td>
					<td><pre><?php echo Debug::dump($value) ?></pre></td>
				</tr>
				<?php endforeach ?>
			</table>
		</div>
		<?php endforeach ?>
	</div>
</div>

</body>
</html>