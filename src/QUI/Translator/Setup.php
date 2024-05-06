<?php

/**
 * This file contains QUI\Translator\Setup
 */

namespace QUI\Translator;

use QUI;
use QUI\Database\Exception;
use QUI\Package\Package;

/**
 * Class Setup
 * @package QUI\Translator
 */
class Setup
{
    /**
     * @param Package $Package
     * @throws QUI\Exception
     */
    public static function onPackageSetup(Package $Package): void
    {
        if ($Package->getName() !== 'quiqqer/translator') {
            return;
        }

        $table = QUI\Translator::table();

        // id field
        $exists = QUI::getDataBase()->table()->getColumn($table, 'id');

        if (!empty($exists)) {
            QUI::getDataBase()->table()->setPrimaryKey($table, 'id');
            self::patchForEmptyLocales();

            return;
        }

        // create id column for old translation table
        QUI::getDataBase()->table()->addColumn($table, [
            'id' => 'INT(11) DEFAULT NULL'
        ]);

        $PDO = QUI::getDataBase()->getPDO();
        $PDO->query(
            "SET @count = 0;
            UPDATE `$table` SET `$table`.`id` = @count:= @count + 1;"
        );

        QUI::getDataBase()->table()->setPrimaryKey($table, 'id');
        QUI::getDataBase()->table()->setAutoIncrement($table, 'id');

        self::patchForEmptyLocales();
    }

    /**
     * packages empty package fields
     * @throws Exception
     */
    protected static function patchForEmptyLocales(): void
    {
        $table = QUI\Translator::table();

        // update empty package fields
        $emptyLocales = QUI::getDataBase()->fetch([
            'from' => $table,
            'where' => [
                'package' => null
            ]
        ]);

        foreach ($emptyLocales as $entry) {
            if (!isset($entry['id'])) {
                continue;
            }

            QUI::getDataBase()->update(
                $table,
                ['package' => $entry['groups']],
                ['id' => $entry['id']]
            );
        }
    }
}
