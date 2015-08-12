<?php

/**
 * Get all translator languages
 *
 * @returns string -json
 */
function package_quiqqer_translator_ajax_getAvailableLanguages()
{
    return json_encode( \QUI::availableLanguages() );
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_getAvailableLanguages',
    array()
);
