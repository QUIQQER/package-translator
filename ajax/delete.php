<?php

/**
 * Deletes translations
 *
 * @param String $data - JSON Array
 */
function package_quiqqer_translator_ajax_delete($data)
{
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
                    'var'    => $entry['var']
                )
            )
        );
    }
}

QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_delete',
    array('data'),
    'Permission::checkAdminUser'
);
