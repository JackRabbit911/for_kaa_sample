<?
    use WN\Core\Helper\HTML;
    WN\Core\Validation\Response::$valid = 'is-valid';
    WN\Core\Validation\Response::$invalid = 'is-invalid';
?>

<form action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data" class="border rounded" style="padding: 10px; min-width: 50%;">
    <div class="form-group row">
        <label for="nickname" class="col-sm-4 col-form-label"><?=__('Nickname')?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control<?=$nickname->class()?>" name="nickname" 
                id="nickname" value="<?=$nickname->value?>">
            <div class="invalid-feedback"><?=$nickname->msg()?></div>
        </div>
    </div>
    <div class="form-group row">
        <label for="firstname" class="col-sm-4 col-form-label"><?=__('Firstname')?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control<?=$firstname->class()?>" name="firstname" 
                id="firstname" value="<?=$firstname->value?>">
            <div class="invalid-feedback"><?=$firstname->msg()?></div>
        </div>
    </div>
    <div class="form-group row">
        <label for="lastname" class="col-sm-4 col-form-label"><?=__('Lastname')?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control<?=$lastname->class()?>" name="lastname" 
                id="firstname" value="<?=$lastname->value?>">
            <div class="invalid-feedback"><?=$lastname->msg()?></div>
        </div>
    </div>
    <div class="form-group row">
        <label for="email" class="col-sm-4 col-form-label"><?=__('Email')?></label>
        <div class="col-sm-8">
            <input type="email" class="form-control<?=$email->class()?>" name="email" 
                id="email" value="<?=$email->value?>">
            <div class="invalid-feedback"><?=$email->msg()?></div>
        </div>
    </div>
    <div class="form-group row">
        <label for="phone" class="col-sm-4 col-form-label"><?=__('Phone')?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control<?=$phone->class()?>" name="phone" 
                id="phone" value="<?=$phone->value?>">
            <div class="invalid-feedback"><?=$phone->msg()?></div>
        </div>
    </div>
    <div class="form-group row">
        <label for="dob" class="col-sm-4 col-form-label"><?=__('Date of Birth')?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control<?=$dob->class()?>" name="dob" 
                id="phone" value="<?=$dob->value?>">
            <div class="invalid-feedback"><?=$dob->msg()?></div>
        </div>
    </div>
    <div class="form-group row">
        <label for="sex" class="col-sm-4 col-form-label"><?=__('Sex')?></label>
        <div class="col-sm-8" style="padding: 7px 15px;">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="sex" id="sex-male" value="1"<?=$sex->checked("1")?>>
                <label class="form-check-label" for="sex-male"><?=__('male')?></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="sex" id="sex-female" value="0"<?=$sex->checked("0")?>>
                <label class="form-check-label" for="sex-female"><?=__('female')?></label>
            </div>
        </div>
    </div>
    <div class="form-group row">
        <label for="password" class="col-sm-4 col-form-label"><?=__('Password')?></label>
        <div class="col-sm-8">
            <input type="password" class="form-control<?=$password->class()?>" name="password" 
                id="password" value="<?=$password->value?>">
            <div class="invalid-feedback"><?=$password->msg()?></div>
        </div>
    </div>
    <div class="form-group row">
        <label for="password" class="col-sm-4 col-form-label"><?=__('Confirm')?></label>
        <div class="col-sm-8">
            <input type="password" class="form-control<?=$confirm->class()?>" name="confirm" 
                id="confirm" value="<?=$confirm->value?>">
            <div class="invalid-feedback"><?=$confirm->msg()?></div>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-sm-8 offset-4">
            <div class="form-check">
                <input class="form-check-input<?=$agree->class()?>" type="checkbox" name="agree" id="agree"<?=$agree->checked()?>>
                <label class="form-check-label" for="agree">
                    <?=__('I agree to the processing of personal data ')?>
                </label>
                <div class="invalid-feedback"><?=$agree->msg()?></div>
            </div>
            <?=HTML::anchor('/info/personal-data', __('More details'), ['target' => '_blank', 'style' => 'margin-left: 17px;'])?>
        </div>
    </div>

    <div class="col-sm-9 offset-4">
        <button type="submit" class="btn btn-primary"><?=__('Send')?></button>
        <button type="submit" class="btn btn-success" data-ajax="true"><?=__('Send')?></button>
    </div>
</form>