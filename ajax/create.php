<?php

/**
 * Übersetzungen erstellen
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_create',
    function () {
        QUI\Translator::create();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/translator',
                'message.translation.create.successful'
            )
        );
    },
    false,
    'Permission::checkAdminUser'
);
