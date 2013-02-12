<?php

/**
 * Import the data from a file or a xml string
 *
 * @param $data
 */
function package_quiqqer_translator_ajax_file_import($File, $overwrite)
{
    $overwrite = (int) $overwrite;

    if ( !$File->getAttribute( 'filepath' ) )
    {
        \QUI::getMessagesHandler()->addError(
        	'Datei konnte nicht gelesen werden'
        );

        return;
    }

    if ( $overwrite == 1 )
    {
        return \QUI\Translator::import(
            $File->getAttribute( 'filepath' ),
            false
        );
    }

    return \QUI\Translator::import(
        $File->getAttribute( 'filepath' ),
        true
    );
}

\QUI::$Ajax->register(
	'package_quiqqer_translator_ajax_file_import',
    array( 'File', 'overwrite' ),
    'Permission::checkAdminUser'
);

?>