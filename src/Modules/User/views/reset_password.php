<?
 WN\Core\Validation\Response::$valid = 'is-valid';
 WN\Core\Validation\Response::$invalid = 'is-invalid';
?>

<form action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data" class="border rounded" style="padding: 10px;">
        <div class="form-group">
            <input type="password" class="form-control<?=$password->class()?>" name="password" placeholder="<?=__('New Password')?>" value="<?=$password->value?>">
            <small class="form-text text-muted"><?=__('Enter a new password')?></small>
            <div class="valid-feedback">Looks good!</div>
            <div class="invalid-feedback"><?=$password->msg()?></div>
        </div>
        <div class="form-group">
            <input type="password" class="form-control<?=$confirm->class()?>" name="confirm" placeholder="<?=__('Password confirmation')?>" value="<?=$confirm->value?>">
            <small class="form-text text-muted"><?=__('Confirm a new password')?></small>
            <div class="valid-feedback">Looks good!</div>
            <div class="invalid-feedback"><?=$confirm->msg()?></div>
        </div>
        <button type="submit" class="btn btn-primary"><?=__('Send')?></button>
        <button type="submit" class="btn btn-success" data-ajax="true"><?=__('Send')?></button>
</form>