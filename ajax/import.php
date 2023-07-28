<?php

/**
 * Variablen importieren
 *
 * @param string $data - JSON Array
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_import',
    function ($overwriteOriginal, $File) {
        $overwriteOriginal = QUI\Utils\Security\Orthos::clear($overwriteOriginal);

        /* @var $File QUI\QDOM */
        QUI\Translator::import(
            $File->getAttribute('filepath'),
            $overwriteOriginal
        );
    },
    ['overwriteOriginal', 'File'],
    'Permission::checkAdminUser'
);
