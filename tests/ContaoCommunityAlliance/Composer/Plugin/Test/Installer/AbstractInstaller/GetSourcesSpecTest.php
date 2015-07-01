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

namespace ContaoCommunityAlliance\Composer\Plugin\Test\Installer\AbstractInstaller;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use ContaoCommunityAlliance\Composer\Plugin\Environment\Contao3Environment;
use ContaoCommunityAlliance\Composer\Plugin\Test\TestCase;
use ContaoCommunityAlliance\Composer\Plugin\Installer\AbstractInstaller;

class GetSourcesSpecTest extends TestCase
{
    /**
     * @var AbstractInstaller
     */
    protected $installerStub;

    /**
     * @var Composer
     */
    protected $composer;

    public function setUp()
    {
        $package = new RootPackage('test/me', '0.8.15', '0.8.15.0');
        $package->setType(AbstractInstaller::MODULE_TYPE);

        $this->composer = new Composer();
        $this->composer->setConfig(new Config());
        $this->composer->setPackage($package);

        $this->installerStub = $this->getMockForAbstractClass(
            '\ContaoCommunityAlliance\Composer\Plugin\Installer\AbstractInstaller',
            array(new NullIO(), $this->composer, new Contao3Environment('CONTAO_ROOT'))
        );
    }

    protected function runWith($expected, $extra)
    {
        $mapSources = new \ReflectionMethod($this->installerStub, 'getSourcesSpec');
        $mapSources->setAccessible(true);

        /** @var RootPackage $package */
        $package = clone $this->composer->getPackage();

        $package->setExtra($extra);

        $this->assertEquals(
            $expected,
            $mapSources->invokeArgs(
                $this->installerStub,
                array($package)
            ));
    }


    public function test()
    {
        $this->runWith(
            array(),
            array()
        );

        $this->runWith(
            array(
            ),
            array(
                'contao' => array(
                    'sources' => array()
                )
            )
        );

        $this->runWith(
            array(
                'src' => 'system/modules/some-extension'
            ),
            array(
                'contao' => array(
                    'sources' => array(
                        'src' => 'system/modules/some-extension'
                    )
                )
            )
        );

        $this->runWith(
            array(
                'src' => 'system/modules/some-extension',
                'deprecated-symlinking' => 'system/modules/some-other-extension'
            ),
            array(
                'contao' => array(
                    'sources' => array(
                        'src' => 'system/modules/some-extension'
                    ),
                    'symlinks' => array(
                        'deprecated-symlinking' => 'system/modules/some-other-extension'
                    )
                )
            )
        );
    }
}
