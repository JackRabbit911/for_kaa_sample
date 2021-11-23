<?
WN\Core\Validation\Response::$valid = 'is-valid';
WN\Core\Validation\Response::$invalid = 'is-invalid';
?>

<style>
  .custom-file-input ~ .custom-file-label[data-browse]::after {
  content: none;
}
</style>

<form action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data">
  <div class="row">
    <div class="col-sm-12">      
      <div class="form-group">
          <div class="custom-file mb-3">
            <input type="file" name="file[]" class="custom-file-input<?=$file->class()?>" id="file" multiple>
            <label class="custom-file-label" for="file" data-browse=""><?=$file_label?></label>
            <div class="invalid-feedback"><?=$file->msg()?></div>
            <div class="valid-feedback"><?=nl2br($file->msg())?></div>
          </div>
      </div>
      <div class="form-group">
          <button type="submit" class="btn btn-primary">Submit & Upload</button>
          <button type="button" class="btn btn-primary" id="ajax" data-ajax="true">Ajax post</button>
          <button type="reset" class="btn btn-secondary">Reset</button>
          <a href="/<?=WN\Core\Helper\HTTP::detect_uri()?>" class="btn btn-info" role="button">Reload</a>
      </div>      
    </div> 
  </div>
</form>


