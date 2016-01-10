<?php

/**
 * Import the data from a file or a xml string
 *
 * @param \QUI\QDOM $File
 * @param integer $overwrite
 * @return mixed
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_file_import',
    function ($File, $overwrite) {
        $overwrite = (int)$overwrite;

        /* @var $File QUI\QDOM */
        if (!$File->getAttribute('filepath')) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.file.could.not.read'
                )
            );

            return array();
        }

        if ($overwrite == 1) {
            return QUI\Translator::import(
                $File->getAttribute('filepath'),
                false
            );
        }

        return QUI\Translator::import(
            $File->getAttribute('filepath'),
            true
        );
    },
    array('File', 'overwrite'),
    'Permission::checkAdminUser'
);
