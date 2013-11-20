<?php

/**
 * Add a translation var
 *
 * @param String $groups
 * @param String $var
 */
function package_quiqqer_translator_ajax_add($groups, $var)
{
    \QUI\Translator::add( $groups, $var );

    \QUI::getMessagesHandler()->addSuccess(
        \QUI::getLocale()->get(
            'package/translator',
            'message.var.add.successful',
            array(
                'groups' => $groups,
                'var'    => $var
            )
        )
    );
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_add',
    array( 'groups', 'var' ),
    'Permission::checkAdminUser'
);
