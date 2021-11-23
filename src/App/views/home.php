<div class="col-lg-4 col-sm-6 col-xs-10 alert alert-success" role="alert">
    Hello тебе, <?=$user->name()?>
    <span class="float-end">
        <? if($user->id): ?>
            <a href="/user/logout" class="alert-link">Выйти</a>
        <? else: ?>
            <a href="/user/login" class="alert-link">Войти</a>
        <? endif ?>
    </span>
</div>
