<?php

/**
 * Contao Composer Installer
 *
 * Copyright (C) 2013 Contao Community Alliance
 *
 * @package contao-composer
 * @author  Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @link    http://c-c-a.org
 * @license LGPL-3.0+
 */

namespace ContaoCommunityAlliance\Composer\Plugin\Test\SymlinkInstaller;

use ContaoCommunityAlliance\Composer\Plugin\Installer\SymlinkInstaller;
use ContaoCommunityAlliance\Composer\Plugin\Test\InstallCodeBase;

class InstallCodeSymlinkTest
    extends InstallCodeBase
{
    /**
     * @return SymlinkInstaller
     */
    protected function mockInstaller()
    {
        $installer = new SymlinkInstaller($this->io, $this->composer, $this->environment);

        return $installer;
    }
}
