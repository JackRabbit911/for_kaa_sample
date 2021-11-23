<?  if(empty($width) && empty($height)) { $width = '100%'; $height = '100%'; }
    if(empty($width)) $width = $height;
    if(empty($height)) $height = '100%';
    if(empty($bgcolor)) $bgcolor = '#777';
    if(empty($text)) $text = '';
    if(empty($color)) $color = 'rgba(255,255,255,.75)';
    if($height === '100%' && $width === '100%') $fontsize = '10'; else
    {
        if($width <= 100)
            $fontsize = $width/7;
        elseif($width <= 300) $fontsize = $width/20;
        else $fontsize = $width/25;
    }
?>

<svg width="<?=$width?>" height="<?=$height?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?=$width?> <?=$height?>" preserveAspectRatio="none">
    <defs>
        <style type="text/css">
            <!-- #holder_175bbaf45f5 text { fill:rgba(255,255,255,.75);font-weight:normal;font-family:Helvetica, monospace;font-size:20pt } -->
            text { fill:<?=$color?>;font-weight:normal;font-family:Helvetica, monospace;font-size:<?=$fontsize?>vmin; }
        </style>
    </defs>
    <g>
        <rect width="<?=$width?>" height="<?=$height?>" fill="<?=$bgcolor?>">
        </rect>
        <g>
            <!-- <text x="74.421875" y="104.5"> -->
            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle">
                <!-- 200x200 -->
                <?=$text?>
            </text>
        </g>
    </g>
</svg>