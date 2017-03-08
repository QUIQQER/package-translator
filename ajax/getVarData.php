<?php

/**
 * Update einer Variablen
 *
 * @param array $groups
 * @param array $data
 *
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_getVarData',
    function ($group, $var, $pkg) {
        return QUI\Translator::getVarData($group, $var, $pkg);
    },
    array('group', 'var', 'pkg'),
    'Permission::checkAdminUser'
);
