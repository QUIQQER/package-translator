<?php

/**
 * Update einer Variablen
 *
 * @param array $groups
 * @param array $data
 *
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_update',
    function ($groups, $data) {
        $data = json_decode($data, true);

        if (!isset($data['var'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.translation.not.found.update'
                ),
                404
            );
        }

        $result = QUI\Translator::get($groups, $data['var']);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.translation.not.found.update'
                ),
                404
            );
        }

        // benutzer edit
        if (isset($data['id'])) {
            QUI\Translator::editById($data['id'], $data);
        } else {
            if (!isset($data['package'])) {
                $data['package'] = '';
            }

            QUI\Translator::edit($groups, $data['var'], $data['package'], $data);
        }


        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/translator',
                'message.translation.update.successful'
            )
        );
    },
    array('groups', 'data'),
    'Permission::checkAdminUser'
);
