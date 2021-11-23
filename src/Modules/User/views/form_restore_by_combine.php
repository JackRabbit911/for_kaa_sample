<?
    WN\Core\Validation\Response::$valid = 'is-valid';
    WN\Core\Validation\Response::$invalid = 'is-invalid';
?>

<form action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data" class="border rounded" style="padding: 10px;">
        <div class="form-group">
          <input type="text" class="form-control<?=$combine->class()?>" name="combine"
              placeholder="<?=__('Valid email or phone number')?>" value="<?=$combine->value?>">
            <small class="form-text text-muted"><?=__('Enter email or phone provided during registration')?></small>
          <div class="valid-feedback"><?=$combine->msg() ?? 'Looks good!'?></div>
          <div class="invalid-feedback"><?=$combine->msg()?></div>
        </div>
        
        <button type="submit" class="btn btn-primary"><?=__('Send')?></button>
        <button type="submit" class="btn btn-success" data-ajax="true"><?=__('Send')?></button>
</form>