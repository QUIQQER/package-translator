<?php

/**
 * This file contains QUI\Translator\Console
 */

namespace QUI\Translator;

/**
 *
 * @author hen
 *
 */

class Console extends \QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('package:translator')
             ->setDescription('Compile and publish the translations');
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn( 'Start Translation ...' );

        \QUI\Translator::create();

        $this->write( ' [ok]' );
        $this->writeLn( '' );
    }
}