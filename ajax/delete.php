<?php

/**
 * Einen Eintrag löschen
 *
 * @param String $data - JSON Array
 */
function ajax_translater_delete($data)
{
    $data = json_decode($data, true);

    foreach ($data as $entry)
    {
        if (!isset($entry['groups'])) {
            continue;
        }

        if (!isset($entry['var'])) {
            continue;
        }

        QUI_Locale_Translater::delete($entry['groups'], $entry['var']);
    }
}
$ajax->register('ajax_translater_delete', array('data'));

?>