<?
    WN\Core\Validation\Response::$valid = 'is-valid';
    WN\Core\Validation\Response::$invalid = 'is-invalid';
?>

<style>
.pincode input[type="number"] {
    text-align: center;
    font-weight: 700;
    width: 40px;
}
.pincode input[type="number"]::-webkit-outer-spin-button,
.pincode input[type="number"]::-webkit-inner-spin-button {
    display: none;
}

.pincode button[type="submit"] {
    width: 100%;
}
form.pincode {
    margin-top: 8px;
}

form.pincode div.is-invalid {
    padding: 5px;
    border: 1px solid #dc3545;
    border-radius: 4px;
}
</style>

<div class="row justify-content-center">
<form action="" method="post" class="pincode">
    <fieldset <?=$disabled?>>
    <input type="hidden" name="formdata" value='{"url":"~user/restore"}'>
        <div class="form-group<?=@$pincode->class()?>">

            <div class="form-row">
            
                <div class="col">
                <input class="form-control" type="number">
                </div>
                <div class="col">
                <input class="form-control" type="number">
                </div>
                <div class="col">
                <input class="form-control" type="number">
                </div>
                <div class="col">
                <input class="form-control" type="number">
                </div>

            </div>
        </div>
        <div class="invalid-feedback">
            <?=@$pincode->msg()?>
        </div>
        <div class="form-group">
            <input type="hidden" name="pincode">
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
    </fieldset>
</form>
<div>