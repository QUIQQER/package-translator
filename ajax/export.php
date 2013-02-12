<?php

/**
 * Export a group, send the download header
 * Please call it in an iframe or new window
 * no quiqqer xml would be send
 *
 * @param String $group - translation group
 */

function package_quiqqer_translator_ajax_export($group, $edit)
{
    $str = \QUI\Translator::export(
        $group,
        Utils_Bool::JSBool( $edit )
    );

    $file = str_replace( '/', '_', $group );

    header( 'Expires: '. gmdate( "D, d M Y H:i:s" ) . " GMT");
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: no-cache' );
	header( 'Content-type: text/xml' );
	header( 'Content-Disposition: attachment; filename="'. $file .'.xml"' );

	echo $str;
	exit;
}

\QUI::$Ajax->register(
	'package_quiqqer_translator_ajax_export',
    array( 'group', 'edit' ),
    'Permission::checkAdminUser'
);

?>