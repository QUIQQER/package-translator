<?php

/**
 * Deletes translations
 *
 * @param string $data - JSON Array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_delete',
    function ($data) {
        $data = json_decode($data, true);

        if (!is_array($data)) {
            return;
        }

        foreach ($data as $entry) {
            if (!isset($entry['groups'])) {
                continue;
            }

            if (!isset($entry['var'])) {
                continue;
            }

            QUI\Translator::delete(
                $entry['groups'],
                $entry['var']
            );

            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'message.translation.delet.successful',
                    array(
                        'groups' => $entry['groups'],
                        'var' => $entry['var']
                    )
                )
            );
        }
    },
    array('data'),
    'Permission::checkAdminUser'
);
