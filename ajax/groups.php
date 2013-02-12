<?php

function package_quiqqer_translator_ajax_groups()
{
    return \QUI\Translator::getGroupList();
}

\QUI::$Ajax->register(
	'package_quiqqer_translator_ajax_groups',
    false,
    'Permission::checkAdminUser'
);

?>