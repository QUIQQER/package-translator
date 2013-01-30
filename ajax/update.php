<?php

/**
 * Update einer Variablen
 *
 * @param unknown_type $groups
 * @param unknown_type $data
 */
function ajax_translater_update($groups, $data)
{
    $data = json_decode($data, true);

    if (!isset($data['var'])) {
        throw new QException('Übersetzung wurde nicht gefunden und konnt nicht aktualisiert werden');
    }

    $result = QUI_Locale_Translater::get($groups, $data['var']);

    if (!isset($result[0])) {
        throw new QException('Übersetzung wurde nicht gefunden und konnt nicht aktualisiert werden');
    }

    // benutzer edit
    QUI_Locale_Translater::edit($groups, $data['var'], $data);
}
$ajax->register('ajax_translater_update', array('groups', 'data'))


?>