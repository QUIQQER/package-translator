<?php

/**
 * This file contains QUI\Translater
 */

namespace QUI;

use QUI;
use QUI\Utils\XML;
use QUI\Utils\String as QUIString;
use QUI\Utils\System\File as QUIFile;

/**
 * QUIQQER Translater
 *
 * Manage all translations, for the system and the plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.locale
 *
 * mysql fix for old dev version
 *
     UPDATE translate
    SET groups = REPLACE(groups, '\'', '') WHERE 1;
    UPDATE translate
    SET var = REPLACE(var, '\'', '') WHERE 1
 */

class Translator
{
    /**
     * Return the real table name
     *
     * @return String
     */
    static function Table()
    {
        return \QUI::getDBTableName( 'translate' );
    }

    /**
     * Translator setup
     * it looks, which languages are exist and creat it
     */
    static function setup()
    {

    }

    /**
     * Add / create a new language
     *
     * @param String $lang - lang code, length must be 2 signs
     * @throws \QUI\Exception
     */
    static function addLang($lang)
    {
        if ( strlen( $lang ) !== 2 )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'package/translator',
                    'exception.lang.shortcut.not.allowed'
                )
            );
        }

        QUI::getDataBase()->Table()->appendFields(
            self::Table(),
            array(
                $lang          => 'text NOT NULL',
                $lang .'_edit' => 'text NULL'
            )
        );
    }

    /**
     * Export a locale group as xml
     *
     * @param String $group - which group should be exported?
     * @param Bool $edit    - Eport edit fields or original? standard=true
     * @return String
     */
    static function export($group, $edit=true)
    {
        $entries = self::get( $group );
        $pool    = array();

        foreach ( $entries as $entry )
        {
            $type   = 'php';
            $define = $group;

            if ( isset( $entry['datatype'] ) && !empty( $entry['datatype'] ) ) {
                $type = $entry['datatype'];
            }

            if ( isset( $entry['datadefine'] ) && !empty( $entry['datadefine'] ) ) {
                $define = $entry['datadefine'];
            }

            $pool[ $type ][ $define ][] = $entry;
        }

        $result = '';


        foreach ( $pool as $type => $groups )
        {
            foreach ( $groups as $define => $entries )
            {
                $result .= '<groups name="'. $group .'" datatype="'. $type .'"';

                if ( $define != $group ) {
                    $result .= ' define="'. $define .'"';
                }

                $result .= '>'."\n";

                foreach ( $entries as $entry )
                {
                    $result .= "\t".'<locale name="'. $entry['var'] .'">'."\n";

                    foreach ( $entry as $lang => $translation )
                    {
                        if ( strlen( $lang ) == 2 )
                        {
                            if ( $edit &&
                                 isset( $entry[ $lang .'_edit' ] ) &&
                                 !empty( $entry[ $lang .'_edit' ] ))
                            {
                                $result .= "\t\t".'<'. $lang .'>';
                                $result .= '<![CDATA['. $entry[ $lang. '_edit' ] .']]>';
                                $result .= '</'. $lang .'>'."\n";

                                continue;
                            }

                            $result .= "\t\t".'<'. $lang .'>';
                            $result .= '<![CDATA['. $translation .']]>';
                            $result .= '</'. $lang .'>'."\n";
                        }
                    }

                    $result .= "\t".'</locale>'."\n";
                }

                $result .= '</groups>'."\n";
            }
        }

        return $result;
    }

    /**
     * Import a locale xml file
     *
     * @param String $file     - path to the file
     * @param Bool $update_edit_fields - if true, the _edit fields would be updated
     * 									 if false, the original fields would be updated
     *
     * @return Array - List of imported vars
     * @throws \QUI\Exception
     */
    static function import($file, $update_edit_fields=true)
    {
        if ( !file_exists( $file ) )
        {
            throw new QUI\Exception(
                \QUI::getLocale()->get(
                    'package/translator',
                    'exception.lang.file.not.exist'
                )
            );
        }

        $result = array();
        $groups = XML::getLocaleGroupsFromDom(
            XML::getDomFromXml( $file )
        );

        foreach ( $groups as $locales )
        {
            $group    = $locales['group'];
            $datatype = '';

            if ( isset( $locales['datatype'] ) ) {
                $datatype = $locales['datatype'];
            }

            foreach ( $locales['locales'] as $locale )
            {
                $var = $locale['name'];
                unset( $locale['name'] );


                if ( !isset( $locale['html'] ) ) {
                    $locale['html'] = 0;
                }

                if ( $locale['html'] ) {
                    $locale['html'] = 1;
                }


                try
                {
                    self::add( $group, $var );

                } catch ( QUI\Exception $Exception )
                {

                }

                // update only in the _edit fields
                if ( $update_edit_fields )
                {
                    $_locale = array();

                    foreach ( $locale as $key => $entry ) {
                        $_locale[ $key.'_edit' ] = $entry;
                    }

                    $_locale['datatype'] = $datatype;
                    $_locale['html']     = $locale['html'];

                    self::edit( $group, $var, $_locale );

                } else
                {
                    // set the original fields
                    $locale['datatype'] = $datatype;

                    self::update( $group, $var, $locale );
                }

                $result[] = array(
                    'group'  => $group,
                    'var'    => $var,
                    'locale' => $locale,
                );
            }
        }

        return $result;
    }

    /**
     * Ordner in dem die Übersetzungen liegen
     * @return String
     */
    static function dir()
    {
        return QUI::getLocale()->dir();
    }

    /**
     * Übersetzungs Datei
     *
     * @param String $lang
     * @param String $group
     *
     * @return String
     */
    static function getTranslationFile($lang, $group)
    {
        return QUI::getLocale()->getTranslationFile($lang, $group);
    }

    /**
     * Return the list of the translation files for a languag
     *
     * @param String $lang - Language -> eq: "de" or "en" ... and so on
     * @return Array
     */
    static function getJSTranslationFiles($lang)
    {
        if ( strlen( $lang ) !== 2 ) {
            return array();
        }

        $jsdir  = self::dir() .'/bin/';
        $result = array();

        $dirs = QUIFile::readDir( $jsdir );

        foreach ( $dirs as $dir )
        {
            $package_dir  = $jsdir . $dir;
            $package_list = QUIFile::readDir( $package_dir );

            foreach ( $package_list as $package )
            {
                $lang_file = $package_dir . '/'. $package .'/'. $lang .'.js';

                if ( file_exists( $lang_file ) ) {
                    $result[ 'locale/'. $dir .'/'. $package ] = $lang_file;
                }
            }
        }

        return $result;
    }

    /**
     * remove all dublicate entres from the translation table
     *
     * because:
     * #1071 - Specified key was too long; max key length is 1000 bytes
     *
     * we cannot use unique keys :/
     */
    static function cleanup()
    {
        $PDO       = \QUI::getDataBase()->getPDO();
        $bad_table = self::Table();

        // check if dublicate entries exist
        $Statement = $PDO->prepare(
            'SELECT `groups`, `var`
            FROM '. $bad_table .'
            GROUP BY `groups`, `var`
            HAVING count( * ) > 1'
        );

        $Statement->execute();

        if ( !$Statement->fetch() ) {
            return;
        }

        $Statement = $PDO->prepare(
            'CREATE TEMPORARY TABLE bad_temp_translation AS
            SELECT DISTINCT * FROM '. $bad_table
        );
        $Statement->execute();

        $Statement = $PDO->prepare( 'DELETE FROM '. $bad_table );
        $Statement->execute();

        $Statement = $PDO->prepare( 'INSERT INTO '. $bad_table .' SELECT * FROM bad_temp_translation' );
        $Statement->execute();
    }

    /**
     * Create the locale files
     */
    static function create()
    {
        // first step, a cleanup
        // so we get no errors in gettext
        self::cleanup();

        $langs = self::langs();
        $dir   = self::dir();

        // Sprach Ordner erstellen
        $folders    = array();
        $exec_error = array();

        foreach ( $langs as $lang )
        {
            $folders[ $lang ] = $dir .'/'. QUIString::toLower( $lang ) .
                                      '_'. QUIString::toUpper( $lang ) .
                                      '/LC_MESSAGES/';

            QUIFile::unlink( $folders[ $lang ] );
            QUIFile::mkdir( $folders[ $lang ] );
        }

        $js_langs = array();

        // Sprachdateien erstellen
        foreach ( $langs as $lang )
        {
            if ( strlen( $lang ) !== 2 ) {
                continue;
            }

            $result = \QUI::getDataBase()->fetch(array(
                'select' => array(
                    $lang, $lang .'_edit', 'groups', 'var',
                    'datatype', 'datadefine', 'html'
                ),
                'from' => self::Table()
            ));

            foreach ( $result as $entry )
            {
                if ( $entry['datatype'] == 'js' )
                {
                    $js_langs[ $entry['groups'] ][ $lang ][] = $entry;
                    continue;
                }

                // if php,js
                if ( strpos( $entry['datatype'], 'js' ) !== false ) {
                    $js_langs[ $entry['groups'] ][ $lang ][] = $entry;
                }


                // locale/permissions must available in JS AND PHP
                /*
                if ( $entry['groups'] == 'locale/permissions' ) {
                    $js_langs[ $entry['groups'] ][ $lang ][] = $entry;
                }
                */

                $value = $entry[ $lang ];

                if ( isset( $entry[ $lang.'_edit' ] ) &&
                     !empty( $entry[ $lang.'_edit' ]) )
                {
                    $value = $entry[ $lang.'_edit' ]; // benutzer übersetzung
                }

                $value = str_replace( '\\', '\\\\', $value );
                $value = str_replace( '"', '\"', $value );
                //$value = nl2br( $value );
                $value = str_replace( "\n", '', $value );

                if ( $value !== '' && $value !== ' ' ) {
                    $value = trim( $value );
                }

                // ini Datei
                $iniVar = $entry['var'];

                // in php some keywords are not allowed, so we rewrite the key in `
                // its better than destroy the ini file
                switch ( $iniVar )
                {
                    case 'null':
                    case 'yes':
                    case 'no':
                    case 'true':
                    case 'false':
                    case 'on':
                    case 'off':
                    case 'none':
                        $iniVar = '`'. $iniVar .'`';
                    break;
                }

                $ini     = $folders[ $lang ] . str_replace( '/', '_', $entry['groups'] ) .'.ini.php';
                $ini_str = $iniVar .'= "'. $value .'"';

                QUIFile::mkfile( $ini );
                QUIFile::putLineToFile( $ini, $ini_str );

                // po (gettext) datei
                $po = $folders[ $lang ] . str_replace( '/', '_', $entry['groups'] ) .'.po';
                $mo = $folders[ $lang ] . str_replace( '/', '_', $entry['groups'] ) .'.mo';

                QUIFile::mkfile( $po );

                QUIFile::putLineToFile( $po, 'msgid "'. $entry['var'] .'"' );
                QUIFile::putLineToFile( $po, 'msgstr "'. $value .'"' );
                QUIFile::putLineToFile( $po, '' );
            }

            // create javascript lang files
            $jsdir = $dir .'/bin/';

            QUIFile::mkdir( $jsdir );

            foreach ( $js_langs as $group => $groupentry )
            {
                foreach ( $groupentry as $lang => $entries )
                {
                    $vars = array();

                    foreach ( $entries as $entry )
                    {
                        $value = $entry[ $lang ];

                        if ( isset( $entry[ $lang.'_edit' ] ) &&
                             !empty( $entry[ $lang.'_edit' ]) )
                        {
                            $value = $entry[ $lang.'_edit' ]; // benutzer übersetzung
                        }

                        $vars[ $entry['var'] ] = $value;
                    }

                    $js  = '';
                    $js .= "define('locale/". $group ."/". $lang ."', ['Locale'], function(Locale)";
                    $js .= '{';
                        $js .= 'Locale.set("'. $lang .'", "'. $group .'", ';
                        $js .= json_encode( $vars );
                        $js .= ')';
                    $js .= '});';

                    // create package dir
                    QUIFile::mkdir( $jsdir . $group );

                    if ( file_exists( $jsdir . $group .'/'. $lang .'.js' ) ) {
                        unlink( $jsdir . $group .'/'. $lang .'.js' );
                    }

                    file_put_contents( $jsdir . $group .'/'. $lang .'.js', $js );
                }
            }

            // \QUI\System\Log::writeRecursive( $js_langs, 'error' );

            // alle .po dateien einlesen und in mo umwandeln
            $po_files = QUIFile::readDir( $folders[ $lang ] );

            foreach ( $po_files as $po_file )
            {
                if ( substr( $po_file, -3 ) == '.po' )
                {
                    self::phpmoConvert( $folders[ $lang ] . $po_file );

                    //$exec = 'msgfmt '. $folders[ $lang ]. $po_file .' -o '. $folders[ $lang ] . substr( $po_file, 0,-3 ).'.mo' ;
                    //exec( \QUI\Utils\Security\Orthos::clearShell( $exec ) .' 2>&1', $exec_error );
                }
            }
        }

        if ( !empty( $exec_error ) ) {
            QUI::getMessagesHandler()->addError( $exec_error );
        }
    }

    /**
     * Übersetzung bekommen
     *
     * @param String $group - Gruppe
     * @param String|Bool $var   - Übersetzungsvariable, optional
     *
     * @return Array
     */
    static function get($group, $var=false)
    {
        if ( !$var )
        {
            return QUI::getDataBase()->fetch(array(
                'from' => self::Table(),
                'where' => array(
                    'groups' => $group
                )
            ));
        }

        return \QUI::getDataBase()->fetch(array(
            'from' => self::Table(),
            'where' => array(
                'groups' => $group,
                'var'    => $var
            )
        ));
    }

    /**
     * Daten für die Tabelle bekommen
     *
     * @param String $groups - Gruppe
     * @param Array $params  - optional array(limit => 10, page => 1)
     * @param Array|Bool $search  - optional array(search => '%str%', fields => '')
     *
     * @return Array
     */
    static function getData($groups, $params=array(), $search=false)
    {
        $table     = self::Table();
        $db_fields = self::langs();

        $max  = 10;
        $page = 1;

        if ( isset( $params['limit'] ) ) {
            $max = (int)$params['limit'];
        }

        if ( isset( $params['page'] ) ) {
            $page = (int)$params['page'];
        }

        $page  = ($page - 1) ? $page - 1 : 0;
        $limit = ($page * $max) .','. $max;


        // PDO search emptyTranslations
        if ( $search && isset( $search['emptyTranslations'] ) && $search['emptyTranslations'] )
        {
            $PDO = QUI::getPDO();

            // search empty translations
            $whereParts = array();

            foreach ( $db_fields as $field ) {
                $whereParts[] = "({$field} = '' AND {$field}_edit = '' )";
            }

            $where = implode( ' OR ', $whereParts );

            $querySelect = "
                SELECT *
                FROM {$table}
                WHERE {$where}
                LIMIT {$limit}
            ";

            $queryCount = "
                SELECT COUNT(*) as count
                FROM {$table}
                WHERE {$where}
            ";

            $Statement = $PDO->prepare( $querySelect );
            $Statement->execute();
            $result = $Statement->fetchAll( \PDO::FETCH_ASSOC );

            $Statement = $PDO->prepare( $queryCount );
            $Statement->execute();
            $count = $Statement->fetchAll( \PDO::FETCH_ASSOC );


            return array(
                'data'  => $result,
                'page'  => $page + 1,
                'count' => $count[0]['count'],
                'total' => $count[0]['count']
            );
        }


        if ( $search && isset( $search['search'] ) )
        {
            // search translations
            $where  = array();
            $search = array(
                'type'  => '%LIKE%',
                'value' => $search['search']
            );


            // default fields
            $default = array(
                'groups'     => $search,
                'var'        => $search,
                'datatype'   => $search,
                'datadefine' => $search
            );

            foreach ( $db_fields as $lang )
            {
                if ( strlen( $lang ) == 2 )
                {
                    $default[ $lang ] = $search;
                    $default[ $lang .'_edit' ] = $search;
                }
            }

            // search
            $fields = array();

            if ( isset( $search['fields'] ) && !empty( $search['fields'] ) ) {
                $fields = $search['fields'];
            }

            foreach ( $fields as $field )
            {
                if ( isset( $default[ $field ] ) ) {
                    $where[ $field ] = $search;
                }
            }

            if ( empty( $where ) ) {
                $where = $default;
            }
            
            $data = array(
                'from'     => $table,
                'where_or' => $where,
                'limit'    => $limit
            );

        } else
        {
            // search complete group
            $data = array(
                'from'  => $table,
                'where' => array(
                    'groups' => $groups
                ),
                'limit' => $limit
            );
        }

        // result mit limit
        $result = \QUI::getDataBase()->fetch( $data );

        // count
        $data['count'] = 'groups';

        if ( isset( $data['limit'] ) ) {
            unset( $data['limit'] );
        }

        $count = \QUI::getDataBase()->fetch( $data );

        return array(
            'data'  => $result,
            'page'  => $page + 1,
            'count' => $count[0]['groups'],
            'total' => $count[0]['groups']
        );
    }

    /**
     * Liste aller vorhandenen Gruppen
     *
     * @return Array
     */
    static function getGroupList()
    {
        $result = \QUI::getDataBase()->fetch(array(
            'select' => 'groups',
            'from'   => self::Table(),
            'group'  => 'groups'
        ));

        $list = array();

        foreach ( $result as $entry ) {
            $list[] = $entry['groups'];
        }

        return $list;
    }

    /**
     * Fügt eine Übersetzungsvariable hinzu
     *
     * @param String $group
     * @param String $var
     * @throws \QUI\Exception
     */
    static function add($group, $var)
    {
        if ( empty( $var ) || empty( $group ) )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'package/translator',
                    'exception.empty.var.group'
                )
            );
        }

        $result = self::get( $group, $var );

        if ( isset( $result[0] ) )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'package/translator',
                    'exception.var.exists'
                )
            );
        }

        QUI::getDataBase()->insert(
            self::Table(),
            array(
                'groups' => $group,
                'var'    => $var
            )
        );
    }

    /**
     * Eintrag aktualisieren
     *
     * @param String $group
     * @param String $var
     * @param Array $data
     */
    static function update($group, $var, $data)
    {
        $langs = self::langs();
        $_data = array();

        foreach ( $langs as $lang )
        {
            if ( !isset( $data[ $lang ] ) ) {
                continue;
            }

            $_data[ $lang ] = $data[ $lang ];
        }

        if ( isset( $data[ 'datatype' ] ) ) {
            $_data[ 'datatype' ] = $data[ 'datatype' ];
        }

        if ( isset( $data[ 'datadefine' ] ) ) {
            $_data[ 'datadefine' ] = $data[ 'datadefine' ];
        }

        $_data[ 'html' ] = 0;

        if ( isset( $data[ 'html' ] ) && $data[ 'html' ] ) {
            $_data[ 'html' ] = 1;
        }

        \QUI::getDataBase()->update(
            self::Table(),
            $_data,
            array(
                'groups' => $group,
                'var'    => $var
            )
        );
    }

    /**
     * User Edit
     *
     * @param String $group
     * @param String $var
     * @param String $data
     */
    static function edit($group, $var, $data)
    {
        $langs = self::langs();
        $_data = array();

        $development = \QUI::conf( 'globals', 'development' );

        foreach ( $langs as $lang )
        {
            if ( $development )
            {
                if ( isset( $data[ $lang ] ) ) {
                    $_data[ $lang ] = $data[ $lang ];
                }

                if ( isset( $data[ $lang .'_edit' ] ) ) {
                    $_data[ $lang .'_edit' ] = $data[ $lang .'_edit' ];
                }

                continue;
            }

            if ( !isset( $data[ $lang ] ) ) {
                continue;
            }

            $_data[ $lang .'_edit' ] = $data[ $lang ];
        }

        if ( isset( $data[ 'datatype' ] ) ) {
            $_data[ 'datatype' ] = $data[ 'datatype' ];
        }

        if ( isset( $data[ 'datadefine' ] ) ) {
            $_data[ 'datadefine' ] = $data[ 'datadefine' ];
        }

        $_data[ 'html' ] = 0;

        if ( isset( $data[ 'html' ] ) && $data[ 'html' ] ) {
            $_data[ 'html' ] = 1;
        }

        \QUI::getDataBase()->update(
            self::Table(),
            $_data,
            array(
                'groups' => $group,
                'var'    => $var
            )
        );
    }

    /**
     * Einen Übersetzungseintrag löschen
     *
     * @param String $group
     * @param String $var
     */
    static function delete($group, $var)
    {
        \QUI::getDataBase()->delete(
            self::Table(),
            array(
                'groups' => $group,
                'var'    => $var
            )
        );
    }

    /**
     * Welche Sprachen existieren
     *
     * @return Array
     */
    static function langs()
    {
        $fields = \QUI::getDataBase()->Table()->getFields(
            self::Table()
        );

        $langs = array();

        foreach ( $fields as $entry )
        {
            if ( $entry == 'groups' ) {
                continue;
            }

            if ( $entry == 'var' ) {
                continue;
            }

            if ( $entry == 'html' ) {
                continue;
            }

            if ( $entry == 'datatype' ) {
                continue;
            }

            if ( $entry == 'datadefine' ) {
                continue;
            }

            if ( strpos($entry, '_edit') !== false ) {
                continue;
            }

            $langs[] = $entry;
        }

        return $langs;
    }

    /**
     * Gibt die zu übersetzenden Variablen zurück
     *
     * @return Array
     */
    static function getNeedles()
    {
        $fields = \QUI::getDataBase()->Table()->getFields(
            self::Table()
        );

        $langs = array();

        foreach ( $fields as $entry )
        {
            if ( $entry == 'var' || $entry == 'groups' || $entry == 'html' ) {
                continue;
            }

            $langs[] = $entry;
        }

        $result = \QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'where' => implode( ' = "" OR ', $langs ) .' = ""'
        ));

        return $result;
    }

    /**
     * Parser Methoden
     */

    static $_tmp = array();

    /**
     * T Blöcke in einem String finden
     *
     * @param String $string
     * @return Array
     */
    static function getTBlocksFromString($string)
    {
        if ( strpos( $string, '{/t}' ) === false ) {
            return array();
        }

        self::$_tmp = array();

        preg_replace_callback(
            '/{t([^}]*)}([^[{]*){\/t}/im',
            function($params)
            {
                if ( isset( $params[1] ) && !empty( $params[1] ) )
                {
                    $_params = explode( ' ', trim( $params[1] ) );
                    $_params = str_replace( array('"', "'"), '', $_params );

                    $group = '';
                    $var   = '';

                    foreach ( $_params as $param )
                    {
                        $_param = explode( '=', $param );

                        if ( $_param[0] == 'groups' ) {
                            $group = $_param[1];
                        }

                        if ( $_param[0] == 'var' ) {
                            $var = $_param[1];
                        }
                    }

                    self::$_tmp[] = array(
                        'groups' => $group,
                        'var'    => $var
                    );

                    return;
                }

                $_param = explode( ' ', $params[2] );

                if ( strpos( $_param[0], '/') === false ||
                     strpos( $_param[1], ' ') !== false )
                {
                    self::$_tmp[] = array(
                        'var' => $params[2]
                    );
                }

                self::$_tmp[] = array(
                    'groups' => $_param[0],
                    'var'    => $_param[1],
                );
            },
            $string
        );

        return self::$_tmp;
    }

    /**
     * PHP Blöcke in einem String finden
     *
     * @param String $string
     * @return Array
     */
    static function getLBlocksFromString($string)
    {
        if ( strpos( $string, '$L->get(' ) === false &&
             strpos( $string, '$Locale->get(' ) === false)
        {
            return array();
        }

        self::$_tmp = array();

        preg_replace_callback(
            '/\$L(ocale)?->get\s*\(\s*\'([^)]*)\'\s*,\s*\'([^[)]*)\'\s*\)/im',
            function($params)
            {
                if ( isset( $params[2] ) &&
                     isset( $params[3] ) &&
                     !empty( $params[2] ) &&
                     !empty( $params[3] ) &&
                     strpos( $params[2], '/' ) === false )
                {
                    self::$_tmp[] = array(
                        'groups' => $params[2],
                        'var'    => $params[3],
                    );
                }
            },
            $string
        );

        return self::$_tmp;
    }

    /**
     * Deletes double group-var entries
     *
     * @param Array $array
     * @return Array
     */
    static function deleteDoubleEntries($array)
    {
        // Doppelte Einträge löschen
        $new_tmp = array();

        foreach( $array as $tmp )
        {
            if ( !isset( $new_tmp[ $tmp['groups'] . $tmp['var'] ] ) ) {
                $new_tmp[ $tmp['groups'] . $tmp['var'] ] = $tmp;
            }
        }

        $array = array();

        foreach ( $new_tmp as $tmp ) {
            $array[] = $tmp;
        }

        return $array;
    }


    /**
     * First fallback for gettext
     * based on php.mo 0.1 by Joss Crowcroft (http://www.josscrowcroft.com)
     */

    /**
     * The main .po to .mo function
     *
     * @param String $input
     * @param String|Bool $output
     * @return boolean
     */
    static function phpmoConvert($input, $output=false)
    {
        if ( !$output ) {
            $output = str_replace( '.po', '.mo', $input );
        }

        $hash = self::phpmoParsePoFile( $input );

        if ( $hash === false ) {
            return false;
        }

        self::phpmoWriteMoFile( $hash, $output );
        return true;
    }

    /**
     * Clean helper
     *
     * @param Array|String $x
     * @return mixed
     */
    static function phpmoCleanHelper($x)
    {
        if ( is_array( $x ) )
        {
            foreach ( $x as $k => $v ) {
                $x[$k] = self::phpmoCleanHelper( $v );
            }
        } else
        {
            if ( $x[0] == '"' ) {
                $x = substr($x, 1, -1);
            }

            $x = str_replace( "\"\n\"", '', $x );
            $x = str_replace( '$', '\\$', $x );
        }

        return $x;
    }

    /**
     * Parse gettext .po files.
     * @link http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files
     *
     * @param {String} $in
     * @return Bool|String
     */
    static function phpmoParsePoFile($in)
    {
        // read .po file
        $fh = fopen( $in, 'r' );

        if ( $fh === false )
        {
            // Could not open file resource
            return false;
        }

        // results array
        $hash = array();

        // temporary array
        $temp = array();

        // state
        $state = null;
        $fuzzy = false;

        // iterate over lines
        while( ( $line = fgets( $fh, 65536 ) ) !== false )
        {
            $line = trim( $line );

            if ( $line === '' ) {
                continue;
            }

            list ( $key, $data ) = preg_split( '/\s/', $line, 2 );

            switch ( $key )
            {
                case '#,' : // flag...
                    $fuzzy = in_array('fuzzy', preg_split('/,\s*/', $data));
                case '#' : // translator-comments
                case '#.' : // extracted-comments
                case '#:' : // reference...
                case '#|' : // msgid previous-untranslated-string
                    // start a new entry
                    if ( sizeof($temp) &&
                         array_key_exists('msgid', $temp) &&
                         array_key_exists('msgstr', $temp) )
                    {
                        if ( !$fuzzy ) {
                            $hash[] = $temp;
                        }

                        $temp  = array();
                        $state = null;
                        $fuzzy = false;
                    }
                break;

                case 'msgctxt' :
                    // context
                case 'msgid' :
                    // untranslated-string
                case 'msgid_plural' :
                    // untranslated-string-plural
                    $state = $key;
                    $temp[ $state ] = $data;
                break;
                case 'msgstr' :
                    // translated-string
                    $state = 'msgstr';
                    $temp[ $state ][] = $data;
                break;

                default:
                    if ( strpos($key, 'msgstr[') !== false )
                    {
                        // translated-string-case-n
                        $state = 'msgstr';
                        $temp[ $state ][] = $data;
                    } else
                    {
                        // continued lines
                        switch ($state)
                        {
                            case 'msgctxt' :
                            case 'msgid' :
                            case 'msgid_plural' :
                                $temp[$state] .= "\n" . $line;
                            break;
                            case 'msgstr' :
                                $temp[ $state ][ sizeof($temp[$state]) - 1 ] .= "\n" . $line;
                            break;
                            default :
                                // parse error
                                fclose($fh);
                                return false;
                        }
                    }
                break;
            }
        }

        fclose( $fh );

        // add final entry
        if ( $state == 'msgstr' ) {
            $hash[] = $temp;
        }

        // Cleanup data, merge multiline entries, reindex hash for ksort
        $temp = $hash;
        $hash = array();

        foreach ( $temp as $entry )
        {
            foreach ( $entry as $v )
            {
                $v = self::phpmoCleanHelper( $v );

                // parse error
                if ( $v === false ) {
                    return false;
                }
            }

            $hash[ $entry['msgid'] ] = $entry;
        }

        return $hash;
    }

    /**
     * Write a GNU gettext style machine object.
     *
     * @link http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
     *
     * @param Array $hash
     * @param String $out - file path
     */
    static function phpmoWriteMoFile($hash, $out)
    {
        // sort by msgid
        ksort( $hash, SORT_STRING );

        // our mo file data
        $mo = '';

        // header data
        $offsets = array ();
        $ids     = '';
        $strings = '';

        foreach ( $hash as $entry )
        {
            $id = $entry['msgid'];

            if ( isset( $entry['msgid_plural'] ) ) {
                $id .= "\x00" . $entry['msgid_plural'];
            }

            // context is merged into id, separated by EOT (\x04)
            if ( array_key_exists( 'msgctxt', $entry ) ) {
                $id = $entry['msgctxt'] . "\x04" . $id;
            }

            // plural msgstrs are NUL-separated
            $str = implode( "\x00", $entry['msgstr'] );

            // keep track of offsets
            $offsets[] = array(
                strlen( $ids ),
                strlen( $id ),
                strlen( $strings ),
                strlen( $str )
            );
            // plural msgids are not stored (?)
            $ids .= $id . "\x00";
            $strings .= $str . "\x00";
        }

        // keys start after the header (7 words) + index tables ($#hash * 4 words)
        $key_start = 7 * 4 + sizeof( $hash ) * 4 * 4;

        // values start right after the keys
        $value_start = $key_start + strlen( $ids );

        // first all key offsets, then all value offsets
        $key_offsets   = array();
        $value_offsets = array();

        // calculate
        foreach ($offsets as $v)
        {
            list ( $o1, $l1, $o2, $l2 ) = $v;

            $key_offsets[]   = $l1;
            $key_offsets[]   = $o1 + $key_start;
            $value_offsets[] = $l2;
            $value_offsets[] = $o2 + $value_start;
        }

        $offsets = array_merge( $key_offsets, $value_offsets );

        // write header
        $mo .= pack(
            'Iiiiiii', 0x950412de, // magic number
            0, // version
            sizeof($hash), // number of entries in the catalog
            7 * 4, // key index offset
            7 * 4 + sizeof($hash) * 8, // value index offset,
            0, // hashtable size (unused, thus 0)
            $key_start // hashtable offset
        );

        // offsets
        foreach ( $offsets as $offset ) {
            $mo .= pack( 'i', $offset );
        }

        // ids
        $mo .= $ids;

        // strings
        $mo .= $strings;

        file_put_contents( $out, $mo );
    }
}
