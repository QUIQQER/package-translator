<?php

/**
 * Return translation groups
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_groups',
    function () {
        return QUI\Translator::getGroupList();
    },
    false,
    'Permission::checkAdminUser'
);
