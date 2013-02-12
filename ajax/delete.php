<?php

/**
 * Deletes translations
 *
 * @param String $data - JSON Array
 */
function package_quiqqer_translator_ajax_delete($data)
{
    $data = json_decode( $data, true );

    if ( !is_array( $data ) ) {
        return;
    }

    foreach ( $data as $entry )
    {
        if ( !isset( $entry['groups'] ) ) {
            continue;
        }

        if ( !isset( $entry['var'] ) ) {
            continue;
        }

        \QUI\Translator::delete(
            $entry['groups'],
            $entry['var']
        );

        \QUI::getMessagesHandler()->addSuccess(
        	'Variable '. $entry['groups'] .' '. $entry['var'] .' wurde erfolgreich gelöscht'
        );
    }
}

\QUI::$Ajax->register(
	'package_quiqqer_translator_ajax_delete',
    array( 'data' ),
    'Permission::checkAdminUser'
);

?>