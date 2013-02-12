<?php

/**
 * Add a translation var
 *
 * @param String $groups
 * @param String $var
 */
function package_quiqqer_translator_ajax_add($groups, $var)
{
    \QUI\Translator::add( $groups, $var );

    \QUI::getMessagesHandler()->addSuccess(
    	'Variable '. $groups .' '. $var .' wurde erfolgreich hinzugefügt'
    );
}

\QUI::$Ajax->register(
	'package_quiqqer_translator_ajax_add',
    array( 'groups', 'var' ),
    'Permission::checkAdminUser'
);

?>