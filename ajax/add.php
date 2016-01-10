<?php

/**
 * Add a translation var
 *
 * @param String $group
 * @param String $var
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_add',
    function ($group, $var) {
        $group = str_replace('/', '!GROUPSEPERATOR!', $group);
        $group = QUI\Utils\Security\Orthos::clear($group);
        $group = str_replace('!GROUPSEPERATOR!', '/', $group);

        $var = str_replace('/', '!GROUPSEPERATOR!', $var);
        $var = QUI\Utils\Security\Orthos::clear($var);
        $var = str_replace('!GROUPSEPERATOR!', '/', $var);

        QUI\Translator::add($group, $var);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/translator',
                'message.var.add.successful',
                array(
                    'groups' => $group,
                    'var' => $var
                )
            )
        );
    },
    array('group', 'var'),
    'Permission::checkAdminUser'
);
