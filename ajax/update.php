<?php

/**
 * Update einer Variablen
 *
 * @param array $groups
 * @param array $data
 *
 * @throws QUI\Exception
 */
function package_quiqqer_translator_ajax_update($groups, $data)
{
    $data = json_decode($data, true);

    if (!isset($data['var'])) {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'package/tranlator',
                'exception.translation.not.found.update'
            ),
            404
        );
    }

    $result = QUI\Translator::get($groups, $data['var']);

    if (!isset($result[0])) {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'package/tranlator',
                'exception.translation.not.found.update'
            ),
            404
        );
    }

    // benutzer edit
    QUI\Translator::edit($groups, $data['var'], $data);

    QUI::getMessagesHandler()->addSuccess(
        QUI::getLocale()->get(
            'quiqqer/translator',
            'message.translation.update.successful'
        )
    );
}
QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_update',
    array('groups', 'data'),
    'Permission::checkAdminUser'
);
