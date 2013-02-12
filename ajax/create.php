<?php

/**
 * Übersetzungen erstellen
 */
function package_quiqqer_translator_ajax_create()
{
    \QUI\Translator::create();

    \QUI::getMessagesHandler()->addSuccess(
        'Übersetzungen wurden erfolgreich erstellt'
    );
}

\QUI::$Ajax->register(
	'package_quiqqer_translator_ajax_create',
    false,
    'Permission::checkAdminUser'
);

?>