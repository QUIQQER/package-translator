<?php

/**
 * Übersetzungen erstellen
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_create',
    function ($group) {
        if (isset($group) && !empty($group)) {
            QUI\Translator::publish($group);
        } else {
            QUI\Translator::create();
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/translator',
                'message.translation.create.successful'
            )
        );
    },
    array('group'),
    'Permission::checkAdminUser'
);
