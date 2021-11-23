<?
 WN\Core\Validation\Response::$valid = 'is-valid';
 WN\Core\Validation\Response::$invalid = 'is-invalid';
?>



<div class="col-lg-4 col-sm-6 col-xs-10">
    <form action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data">
        <div class="orm-group mb-3">
            <label for="InputEmail1" class="form-label">Email address</label>
            <input name="email" type="text" class="form-control<?=$email->class()?>" id="InputEmail1" aria-describedby="emailHelp">
            <div class="invalid-feedback"><?=$email->msg()?></div>
        </div>
        <div class="orm-group mb-3">
            <label for="InputPassword" class="form-label">Password</label>
            <input name="password" type="password" class="form-control<?=$password->class()?>" id="InputPassword">
            <div class="invalid-feedback"><?=$password->msg()?></div>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>