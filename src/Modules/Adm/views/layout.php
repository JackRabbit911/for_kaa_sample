<!doctype html>
<html lang="<?=_lang()?>"class="h-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css">
		<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css"> -->

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.6.1/font/bootstrap-icons.css">

        <link href="/media/css/adm.css" rel="stylesheet">

        <title><?=DOMAIN?> - Admin panel</title>
    </head>
    <body class="h-100">
        <?//=_include('navbar')?>
        <?//=_include('sidebar')?>

        <?//=_include('vmenu')?>

    <nav class="navbar navbar-expand fixed-top navbar-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <? $site_link = WN\Core\Helper\HTTP::scheme().'://'.DOMAIN; ?>
                
                <button class="btn" id="sidebarCollapse" type="button" data-bs-toggle="collapse">
                    <!-- <span class="navbar-toggler-icon"></span> -->
                    <!-- <i class="bi bi-list align-top" style="font-size: 1.5rem;"></i> -->
                    <img src="/media/img/list.svg" alt="" width="32" height="32">
                </button>
                <a href="<?=$site_link?>" class="ms-3"><?=$site_link?></a>
            </div>

            <button id="toggler" class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bd-docs-nav" 
                aria-controls="bd-docs-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="" data-json="true"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Alarms">
                            <i class="bi bi-bell"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="" data-json="true"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Messages">
                            <i class="bi bi-envelope-open"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="" data-json="true"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?=$user->name()?>">
                            <i class="bi bi-person-circle"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <aside id="bd-docs-nav" class="bg-light collapse d-flex flex-column h-100">
        <nav class="bd-links" aria-label="Docs navigation">
            <ul class="list-unstyled mb-0 py-3 pt-md-1">
                <li class="mb-1 d-grid gap-2 px-2">
                    <button class="btn" type="button"
                        data-bs-toggle="tooltip" data-bs-placement="right" title="Bootstrap wizard">
                        <i class="bi bi-magic"></i>
                        <span class="vmenu">Bootstrap wizard</span>
                    </button>
                </li>
                <li class="mb-1 d-grid gap-1 px-2">
                    <button class="btn" type="button"
                        data-bs-toggle="tooltip" data-bs-placement="right" title="Core Settings">
                        <i class="bi bi-gear"></i>
                        <span class="vmenu">Core Settings</span>
                    </button>
                </li>
                
            </ul>
        </nav>

        <!-- <div class="mt-auto"></div> -->

        <footer class="footer mt-auto py-3 border-top">
            <div class="container">
                <span class="text-muted">Place sticky footer content here.</span>
            </div>
        </footer>

    </aside>

    <div class="container-fluid">
        <main>
            <?=$main?>
        </main>
    </div>
   
     

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>

    

        <script>
           $(function() {
                // уровень контроллера (Бога), никаких кишков, почти на русском...
                init();
                onClick();
            });

            function init() {
                // подняли все
                $.each($('[data-bs-toggle]'), up);

                // и проверили:
                // загадка №1 тебе - и чёё здесь произойдеёёт?
                control();

                function up(_, item) {
                    bootstrap
                        .Tooltip
                        .getOrCreateInstance(item)
                        .enable();
                }
            }

            function onClick() {
                $('#sidebarCollapse')
                    .on('click', toggle);

                function toggle() {
                    $('.navbar-brand, aside, main')
                        .toggleClass('collapsed');

                    // и проверили, где собака порылась...
                    control();
                }
            }

            function control() {
                // подняли/опустили, зависит от чего-то там...
                let method = $('aside')
                    .hasClass('collapsed')
                    ? 'enable'
                    : 'disable';

                $.each($('button[data-bs-toggle]'), onOff);

                function onOff(_, item) {
                    // загадка №2 тебе: что это?
                    // (не трогай это!!! Оно работает как часы!)
                    bootstrap
                        .Tooltip
                        .getOrCreateInstance(item)[method](); // вот это!!!...
                }
            }
        </script>

    </body>
</html>