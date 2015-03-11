<?php

/**
 * Übersetzungen erstellen
 */
function package_quiqqer_translator_ajax_create()
{
    \QUI\Translator::create();

    \QUI::getMessagesHandler()->addSuccess(
        \QUI::getLocale()->get(
            'quiqqer/translator',
            'message.translation.create.successful'
        )
    );
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_create',
    false,
    'Permission::checkAdminUser'
);
