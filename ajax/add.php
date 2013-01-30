<?php

/**
 * Eine Übersetzungsvariable hinzufügen
 *
 * @param String $groups
 * @param String $var
 */
function ajax_translater_add($groups, $var)
{
    QUI_Locale_Translater::add($groups, $var);
}
$ajax->register('ajax_translater_add', array('groups', 'var'));

?>