<?php

/**
 * This file contains \QUI\Translator\Console
 */

namespace QUI\Translator;

use QUI;
use QUI\Translator;

/**
 * Creat translation via console
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Console extends QUI\System\Console\Tool
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

        Translator::create();

        $this->write( ' [ok]' );
        $this->writeLn( '' );
    }
}