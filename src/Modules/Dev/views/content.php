<div class="row">
    <div class="col">
        <h1 class="text-center display-3" style="font-size: 2em;">Tests</h1>
    </div>
</div>

<div class="row">
    
    <div class="col-3">
    <h4 class="display-4" style="font-size: 1.5em;">Modules:</h3>
        <?=$sidebar?>
    </div>

    <div class="col-9">
        <h4 class="display-4" style="font-size: 1.5em;">Code:</h3>
        <div class="card card-header">
            <pre><code><?=$code?></code></pre>
        </div>
        <h4 class="display-4" style="font-size: 1.5em;">Result:</h3>
        <div class="card card-header">
            <a name="re"></a>
            <samp>
                <?=$result?>
            </samp>
        </div>        
    </div>

</div>