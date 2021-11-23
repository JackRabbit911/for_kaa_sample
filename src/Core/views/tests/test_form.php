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
  <!-- <input type="hidden" name="form" value="testform"> -->
  <div class="row">
    <div class="col-sm-3 text-center">
      <figure class="figure">
        <img src="<?=$img?>" class="figure-img img-fluid rounded" alt="<?=$desc?>" style="max-height: 200px">
        <figcaption class="figure-caption"><?=$desc?></figcaption>
      </figure>
    </div>
    <div class="col-sm-9">      
      <div class="form-group">
          <label for="alt">Short description, alt tag</label>
          <input type="text" class="form-control<?=$alt->class()?>" 
            name="alt" id="alt" value="<?=$alt->value?>" 
            placeholder="Only lettes, numbers, spaces, dots, commas and round brackets">
          <div class="valid-feedback">Looks good!</div>
          <div class="invalid-feedback"><?=$alt->msg()?></div>
      </div>
      <div class="form-group">
          <div class="custom-file mb-3">
            <input type="file" name="file" class="custom-file-input<?=$file->class()?>" id="file">
            <label class="custom-file-label" for="file" data-browse=""><?=$file_label?></label>
            <div class="invalid-feedback"><?=$file->msg()?></div>
            <div class="valid-feedback"><?=$file->msg()?></div>
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


