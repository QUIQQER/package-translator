<?php

/**
 * Übersetzungen erstellen
 */
function ajax_translater_create()
{
    QUI_Locale_Translater::create();
}
$ajax->register('ajax_translater_create');

?>