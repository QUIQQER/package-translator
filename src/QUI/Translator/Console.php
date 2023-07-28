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
     *
     * @throws QUI\Exception
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        if ($this->getArgument('--setup')) {
            $this->writeLn('Start translator setup... ');

            QUI::getPackageManager()
                ->getInstalledPackage('quiqqer/translator')
                ->setup();

            $this->write(' [ok]');
            $this->writeLn('');

            return;
        }

        if ($this->getArgument('--newLanguage')) {
            $language = $this->getArgument('--newLanguage');
            Translator::addLang($language);

            $this->writeLn('Add language to translator: ' . $language);
        }

        $this->writeLn('Start Translation ...');

        Translator::create();

        $this->write(' [ok]');
        $this->writeLn('');
    }
}
