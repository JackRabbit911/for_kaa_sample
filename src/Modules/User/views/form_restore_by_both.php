<?
    WN\Core\Validation\Response::$valid = 'is-valid';
    WN\Core\Validation\Response::$invalid = 'is-invalid';
?>

<form action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data" class="border rounded" style="padding: 10px;">
<input type="hidden" name="formdata" value='{"url":"~user/restore", "view": "form_restore_by_both"}'>
<?if($mode === 'email' || $mode === 'both'):?>
        <div class="form-group">
          <input type="text" class="form-control<?=$email->class()?>" name="email"
              placeholder="<?=__('Valid email')?>" value="<?=$email->value?>">
            <small class="form-text text-muted"><?=__('Enter email provided during registration')?></small>
            <div class="invalid-feedback"><?=$email->msg()?></div>
        </div>
<?endif;?>
<?if($mode === 'both'):?>
        <small class="form-text text-muted" style="margin: -10px 0 10px 0"><?=__('OR')?></small>
<?endif;?>
<?if($mode === 'phone' || $mode === 'both'):?>
        <div class="form-group">
          <input type="text" class="form-control<?=$phone->class()?>" name="phone"
              placeholder="<?=__('Valid phone number')?>" value="<?=$phone->value?>">
          <small class="form-text text-muted"><?=__('Enter phone number provided during registration')?></small>
          <div class="invalid-feedback"><?=$phone->msg([':name' => __('phone')])?></div>
        </div>
<?endif;?>        
        <button type="submit" class="btn btn-primary"><?=__('Send')?></button>
        <button type="submit" class="btn btn-success" data-ajax="true"><?=__('Send')?></button>
</form>