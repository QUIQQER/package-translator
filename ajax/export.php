<?php

/**
 * Export a group, send the download header
 * Please call it in an iframe or new window
 * no quiqqer xml would be send
 *
 * @param String $group - translation group
 */

function package_quiqqer_translator_ajax_export($group, $langs, $type)
{
    $group = str_replace( '/', '!GROUPSEPERATOR!', $group );
    $group = \QUI\Utils\Security\Orthos::clear( $group );
    $group = str_replace( '!GROUPSEPERATOR!', '/', $group );

    $langs = \QUI\Utils\Security\Orthos::clearArray(
        json_decode( $langs, true )
    );
    $type = \QUI\Utils\Security\Orthos::clear( $type );

    \QUI\Utils\System\File::downloadHeader(
        \QUI\Translator::export( $group, $langs, $type )
    );
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_export',
    array( 'group', 'langs', 'type' ),
    'Permission::checkAdminUser'
);
