<?php

/**
 * Add a translation var
 *
 * @param string $group
 * @param string $var
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_add',
    function ($group, $var, $pkg, $datatype, $html) {
        $group = str_replace('/', '!GROUPSEPARATOR!', $group);
        $group = QUI\Utils\Security\Orthos::clear($group);
        $group = str_replace('!GROUPSEPARATOR!', '/', $group);

        $var = str_replace('/', '!GROUPSEPARATOR!', $var);
        $var = QUI\Utils\Security\Orthos::clear($var);
        $var = str_replace('!GROUPSEPARATOR!', '/', $var);

        QUI\Translator::add(
            $group,
            $var,
            $pkg,
            $datatype,
            $html
        );

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/translator',
                'message.var.add.successful',
                array(
                    'groups'  => $group,
                    'var'     => $var,
                    'package' => $pkg
                )
            )
        );
    },
    array('group', 'var', 'pkg', 'datatype', 'html'),
    'Permission::checkAdminUser'
);
