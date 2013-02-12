<?php

/**
 * Update einer Variablen
 *
 * @param unknown_type $groups
 * @param unknown_type $data
 */
function package_quiqqer_translator_ajax_update($groups, $data)
{
    $data = json_decode( $data, true );

    if ( !isset( $data['var'] ) )
    {
        throw new QException(
        	'Übersetzung wurde nicht gefunden und konnt nicht aktualisiert werden',
            404
        );
    }

    $result = \QUI\Translator::get( $groups, $data['var'] );

    if ( !isset( $result[0] ) )
    {
        throw new QException(
        	'Übersetzung wurde nicht gefunden und konnt nicht aktualisiert werden',
            404
        );
    }

    // benutzer edit
    \QUI\Translator::edit( $groups, $data['var'], $data );

    \QUI::getMessagesHandler()->addSuccess(
        'Übersetzung wurde erfolgreich gespeichert'
    );
}

\QUI::$Ajax->register(
	'package_quiqqer_translator_ajax_update',
    array( 'groups', 'data' ),
    'Permission::checkAdminUser'
);

?>