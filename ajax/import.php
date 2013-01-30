<?php

/**
 * Variablen importieren
 *
 * @param String $data - JSON Array
 */
function ajax_translater_import($data)
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

        try
        {
            QUI_Locale_Translater::add($entry['groups'], $entry['var']);
        } catch (QException $e)
        {
            // nothing
        }
    }
}
$ajax->register('ajax_translater_import', array('data'));

?>