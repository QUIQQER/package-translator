<?php

/**
 * Add a translation var
 *
 * @param String $groups
 * @param String $var
 */
function package_quiqqer_translator_ajax_add($group, $var)
{
    $group = str_replace( '/', '!GROUPSEPERATOR!', $group );
    $group = \QUI\Utils\Security\Orthos::clear( $group );
    $group = str_replace( '!GROUPSEPERATOR!', '/', $group );

    $var    = \QUI\Utils\Security\Orthos::clear( $var );

    \QUI\Translator::add( $group, $var );

    \QUI::getMessagesHandler()->addSuccess(
        \QUI::getLocale()->get(
            'quiqqer/translator',
            'message.var.add.successful',
            array(
                'groups' => $group,
                'var'    => $var
            )
        )
    );
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_add',
    array( 'group', 'var' ),
    'Permission::checkAdminUser'
);
