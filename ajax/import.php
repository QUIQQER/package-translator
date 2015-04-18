<?php

/**
 * Variablen importieren
 *
 * @param String $data - JSON Array
 */
function package_quiqqer_translator_ajax_import($overwriteOriginal, $File)
{
    $overwriteOriginal = \QUI\Utils\Security\Orthos::clear($overwriteOriginal);

    \QUI\Translator::import(
        $File->getAttribute('filepath'),
        $overwriteOriginal
    );
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_import',
    array('overwriteOriginal', 'File'),
    'Permission::checkAdminUser'
);
