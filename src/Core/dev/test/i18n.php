<?php
use WN\Core\I18n;
echo I18n::langs(), '<br>';
echo I18n::lang(), '<br>';
echo I18n::gettext('Hello'), ' ', __('hello');
echo '<hr>';

foreach(I18n::get_href_array() as $lang => $uri)
    echo $lang, ' - ', $uri, '<br>';

echo '<hr>';

foreach(I18n::l10n() as $key => $value)
    echo $key, ' - ', $value, '<br>';