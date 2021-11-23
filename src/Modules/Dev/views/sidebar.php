<div class="accordion" id="accordion">
<?$i = 0?>  
<?foreach($modules as $module => $files):?>
<?if($module == $active['module']) $show = ' show'; else $show = null;?>
  <div class="card">

    <div class="card-header" id="heading<?=$i?>">
      <h5 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse<?=$i?>" aria-expanded="false" aria-controls="collapse<?=$i?>">
          <?=$module?>
        </button>
      </h5>
    </div>

    <div id="collapse<?=$i?>" class="collapse<?=$show?>" aria-labelledby="heading<?=$i?>" data-parent="#accordion">
      <div class="list-group list-group-flush">
        <!-- <div class="list-group"> -->
          <?foreach($files as $title => $uri):?>
            <?//if(stripos($active['item'], $title) !== false)
             if(strcasecmp($active['item'], $title) === 0) $active_item = ' list-group-item-primary'; else $active_item = null;?>
            <a href="<?=$uri?>" class="list-group-item list-group-item-action<?=$active_item?>"><?=$title?></a>
          <?endforeach;?>
        <!-- </div> -->
      </div>
    </div>
    
  </div>
<?$i++;?>
<?endforeach;?>

</div>