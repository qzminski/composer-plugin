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

namespace ContaoCommunityAlliance\Composer\Plugin\Test\CopyInstaller;

use Composer\Config;
use ContaoCommunityAlliance\Composer\Plugin\Installer\CopyInstaller;
use ContaoCommunityAlliance\Composer\Plugin\Test\InstallCodeBase;

class InstallCodeCopyTest
    extends InstallCodeBase
{
    /**
     * @return CopyInstaller
     */
    protected function mockInstaller()
    {
        $installer = new CopyInstaller($this->io, $this->composer, $this->environment);

        return $installer;
    }
}
