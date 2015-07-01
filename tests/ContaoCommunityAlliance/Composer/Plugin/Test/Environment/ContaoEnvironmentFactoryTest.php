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

namespace ContaoCommunityAlliance\Composer\Plugin\Test\Plugin\Environment;

use Composer\Package\RootPackage;
use Composer\Util\Filesystem;
use ContaoCommunityAlliance\Composer\Plugin\Environment\ContaoEnvironmentFactory;
use ContaoCommunityAlliance\Composer\Plugin\Environment\ContaoEnvironmentInterface;
use ContaoCommunityAlliance\Composer\Plugin\Test\TestCase;

class ContaoEnvironmentFactoryTest extends TestCase
{
    /**
     * Path to a temporary folder where to mimic an installation.
     *
     * @var string
     */
    protected $testRoot;

    /**
     * Current working dir.
     *
     * @var string
     */
    protected $curDir;

    /** @var Filesystem */
    protected $fs;

    protected function setUp()
    {
        $this->fs       = new Filesystem();
        $this->curDir   = getcwd();
        $this->testRoot = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'composer-test-submodule/composer';
    }

    protected function tearDown()
    {
        chdir($this->curDir);
        $this->fs->removeDirectory(dirname($this->testRoot));
    }

    /**
     * Prepare the test directory and the plugin.
     *
     * @param string $subDir
     *
     * @return ContaoEnvironmentInterface
     */
    protected function clearTest($subDir = '')
    {
        $this->ensureDirectoryExistsAndClear($this->testRoot . $subDir);
        if (!chdir($this->testRoot))
        {
            $this->markTestIncomplete('Could not change to temp dir. Test incomplete!');
        }
    }

    protected function determineRootFromPackage($package)
    {
        $factory    = new ContaoEnvironmentFactory();
        $reflection = new \ReflectionMethod($factory, 'findRoot');
        $reflection->setAccessible(true);

        return $reflection->invoke($factory, $package);
    }

    /**
     * Test that a locally installed contao can be found when overriding the path via composer.json in the root package.
     *
     * @return void
     */
    public function testOverrideViaExtra()
    {
        $this->clearTest('/tmp/path');

        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra(array('contao' => array('root' => 'tmp/path')));

        $this->assertEquals($this->testRoot . '/tmp/path', $this->determineRootFromPackage($package));
    }

    /**
     * Test that a contao installation can be found within composer/vendor/contao/core
     */
    public function testCoreAsSubModule()
    {
        $this->clearTest('/vendor/contao/core');

        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');

        $this->assertEquals($this->testRoot . '/vendor/contao/core', $this->determineRootFromPackage($package));
    }

    /**
     * Test that a contao installation can be found within current directory.
     */
    public function testCoreIsRoot()
    {
        $this->clearTest();

        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');

        $this->assertEquals(dirname($this->testRoot), $this->determineRootFromPackage($package));
    }

    /**
     * Test that a Contao installation can be found within current working directory.
     */
    public function testCoreIsCwd()
    {
        $this->clearTest();
        mkdir($this->testRoot . DIRECTORY_SEPARATOR . 'system/modules', 0777, true);

        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');

        $this->assertEquals($this->testRoot, $this->determineRootFromPackage($package));
    }
}
