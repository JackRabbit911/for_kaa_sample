<?php use WN\Core\Exception\Debug;?>
<?php $error_id = uniqid('error');?>
<div id="wn_error">
	<? if($code) $code = ' [ '.$code.' ] '; ?>
	<h1><span class="type"><?php echo $class, $code ?>:</span> 
		<span class="message"><?php echo htmlspecialchars( (string) $message, ENT_QUOTES | ENT_IGNORE, 'UTF-8', TRUE); ?></span>
		<span class="uri">GMT: <?php echo $gmt?> uri: /<?php echo $uri?></span>
	</h1>
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