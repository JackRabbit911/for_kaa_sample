<?
 WN\Core\Validation\Response::$valid = 'is-valid';
 WN\Core\Validation\Response::$invalid = 'is-invalid';
?>

<form action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data" class="border rounded" style="padding: 10px;">
        <!-- <input type="hidden" name="url" value="/~user/login"> -->
        <div class="form-group">
          <input type="text" class="form-control<?=$userdata->class()?>" name="userdata" 
              placeholder="<?=__('Email or phone number')?>" value="<?=$userdata->value?>"<?=$disabled?>>
          <div class="valid-feedback">Looks good!</div>
          <div class="invalid-feedback"><?=$userdata->msg()?></div>
        </div>
        <div class="form-group">
          <input type="password" class="form-control<?=$password->class()?>" name="password" placeholder="<?=__('Password')?>" value="<?=$password->value?>"<?=$disabled?>>
          <div class="valid-feedback">Looks good!</div>
          <div class="invalid-feedback"><?=$password->msg()?></div>
        </div>
        <div class="form-group">
          <div class="form-check">          
            <input class="form-check-input<?=$short->class()?>" type="checkbox" id="short" name="short"<?=$short->checked()?><?=$disabled?>>
            <label class="form-check-label" for="short">
              <?=__('other people PC')?> &nbsp;&nbsp;
            </label>
            <div class="float-right text-right">
              <a href="/~user/restore" data-request="#lala"><?=__('Forgot your password?')?></a>
              <br>
              <a href="/~user/register"><?=__('Register')?></a>&nbsp;
            </div>
            <div class="invalid-feedback"><?=$short->msg()?></div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary"<?=$disabled?>><?=__('Sign In')?></button>
        <button type="submit" class="btn btn-success" data-ajax="true"<?=$disabled?>><?=__('Sign In')?></button>
</form>