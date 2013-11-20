<?php

/**
 * Variablen importieren
 *
 * @param String $data - JSON Array
 */
function package_quiqqer_translator_ajax_import($data)
{
    $data = json_decode( $data, true );

    foreach ( $data as $entry )
    {
        if ( !isset( $entry['groups'] ) ) {
            continue;
        }

        if ( !isset( $entry['var'] ) ) {
            continue;
        }

        try
        {
            \QUI\Translator::add( $entry['groups'], $entry['var'] );
        } catch ( \QException $e )
        {
            // nothing
        }
    }
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_import',
    array( 'data' ),
    'Permission::checkAdminUser'
);
