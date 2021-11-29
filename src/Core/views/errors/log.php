<style type="text/css">
    table.erlog {
        width: 100%;
        border-collapse: collapse;
    }

    table.erlog caption {
        background-color: black;
        color: white;
        font-size: 1.2rem;
        font-weight: bolder;
    }

    table.erlog tr {
        border: 1px solid grey;
    }

    table.erlog caption a {
        float: right;
        color: deepskyblue;
        padding-right: 1rem;
    }
</style>
<table id="erlog" class="erlog">
    <caption>Errors log.txt<a href="/errorlog/delete">delete all</a></caption>
    
        <? foreach($errs as $err): ?>
            <tr>
                <td class="details"><?=$err['msg']?></td>
                <td><a href="/errorlog/delete/<?=$err['hash']?>">delete</a></td>
            </tr>
            <tr style="display:none">
                <td colspan="2" id="<?=$err['hash']?>"></td>
            </tr>
        <? endforeach; ?>   
</table>
<script type="text/javascript">
    var table = document.getElementById("erlog");
    var cells = table.getElementsByClassName("details");

    for (var i = 0; i < cells.length; i++) { 
        cells[i].onclick = function() {
            var row = this.closest("tr").nextSibling;

            while(row && row.nodeType != 1) {
                row = row.nextSibling
            }

            row.style.display = (row.style.display != 'none') ? 'none' : '';

            var cell = row.children[0];

            if(cell.innerHTML === "") {
                var addr = document.location.href+'/get/'+cell.id;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', addr);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send();
                xhr.onload = function() {
                    cell.innerHTML = xhr.response;
                };
            }
        };
    }
</script>