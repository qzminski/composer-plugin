<?php

/**
 * This file is part of contao-community-alliance/composer-plugin.
 *
 * (c) 2013 Contao Community Alliance
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/composer-plugin
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2013-2015 Contao Community Alliance
 * @license    https://github.com/contao-community-alliance/composer-plugin/blob/master/LICENSE LGPL-3.0+
 * @link       http://c-c-a.org
 * @filesource
 */

namespace ContaoCommunityAlliance\Composer\Plugin\Test;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use ContaoCommunityAlliance\Composer\Plugin\Installer\LegacyContaoModuleInstaller;
use ContaoCommunityAlliance\Composer\Plugin\RunonceManager;

/**
 * This tests the ContaoModuleInstaller.
 */
class LegacyContaoModuleInstallerTest extends TestCase
{
    /**
     * Tests that the installer supports packages of type "contao-module".
     *
     * @return void
     */
    public function testSupportsLegacyContaoModule()
    {
        $installer = $this->createInstaller();

        $this->assertFalse($installer->supports('contao-module'));
        $this->assertFalse($installer->supports('library'));
        $this->assertTrue($installer->supports('legacy-contao-module'));
    }

    /**
     * Tests that sources are symlinked when installing a package.
     *
     * @return void
     */
    public function testSourcesOnInstall()
    {
        $installer = $this->createInstaller();
        $repo      = $this->mockRepository();
        $package   = $this->mockPackage();

        $basePath = $installer->getInstallPath($package);

        $this->filesystem->ensureDirectoryExists($basePath . '/TL_ROOT/system/modules/foobar/config');
        touch($basePath . '/TL_ROOT/system/modules/foobar/config/config.php');

        $installer->install($repo, $package);

        $this->assertTrue(file_exists($basePath . '/../../../system/modules/foobar/config/config.php'));
        $this->assertTrue(is_link($basePath . '/../../../system/modules/foobar/config/config.php'));
        $this->assertEquals(
            $basePath . '/TL_ROOT/system/modules/foobar/config/config.php',
            realpath($basePath . '/../../../system/modules/foobar/config/config.php')
        );
    }

    /**
     * Tests that nothing happens if a symlink is already present and correct.
     *
     * @return void
     */
    public function testSourcesOnInstallIgnoresIfLinkIsAlreadyCorrect()
    {
        $installer = $this->createInstaller();
        $repo      = $this->mockRepository();
        $package   = $this->mockPackage();

        $basePath = $installer->getInstallPath($package);

        $this->filesystem->ensureDirectoryExists($basePath . '/TL_ROOT/system/modules/foobar/config');
        touch($basePath . '/TL_ROOT/system/modules/foobar/config/config.php');

        $this->filesystem->ensureDirectoryExists($basePath . '/../../../system/modules/foobar/config');
        symlink(
            $basePath . '/TL_ROOT/system/modules/foobar/config/config.php',
            $this->filesystem->normalizePath($basePath . '/../../../system/modules/foobar/config/config.php')
        );

        $installer->install($repo, $package);

        $this->assertTrue(file_exists($basePath . '/../../../system/modules/foobar/config/config.php'));
        $this->assertTrue(is_link($basePath . '/../../../system/modules/foobar/config/config.php'));
        $this->assertEquals(
            $basePath . '/TL_ROOT/system/modules/foobar/config/config.php',
            realpath($basePath . '/../../../system/modules/foobar/config/config.php')
        );
    }

    /**
     * Tests that an exception is thrown if a target already exists.
     *
     * @expectedException \RuntimeException
     *
     * @return void
     */
    public function testSourcesOnInstallThrowsExceptionIfFileExists()
    {
        $installer = $this->createInstaller();
        $repo      = $this->mockRepository();
        $package   = $this->mockPackage();

        $basePath = $installer->getInstallPath($package);

        $this->filesystem->ensureDirectoryExists($basePath . '/TL_ROOT/system/modules/foobar/config');
        touch($basePath . '/TL_ROOT/system/modules/foobar/config/config.php');

        $this->filesystem->ensureDirectoryExists($basePath . '/../../../system/modules/foobar/config');
        touch($basePath . '/../../../system/modules/foobar/config/config.php');

        $installer->install($repo, $package);
    }

    /**
     * Test that symlinks get removed on uninstall.
     *
     * @return void
     */
    public function testSourcesOnUninstall()
    {
        $installer = $this->createInstaller();
        $repo      = $this->mockRepository();
        $package   = $this->mockPackage();

        $basePath = $installer->getInstallPath($package);

        $this->filesystem->ensureDirectoryExists($basePath . '/TL_ROOT/system/modules/foobar/config');
        touch($basePath . '/TL_ROOT/system/modules/foobar/config/config.php');

        $this->filesystem->ensureDirectoryExists($basePath . '/../../../system/modules/foobar/config');
        symlink(
            $basePath . '/TL_ROOT/system/modules/foobar/config/config.php',
            $this->filesystem->normalizePath($basePath . '/../../../system/modules/foobar/config/config.php')
        );

        $installer->uninstall($repo, $package);

        $this->assertFalse(file_exists($basePath . '/../../../system/modules/foobar/config/config.php'));
        $this->assertFalse(is_dir($basePath . '/../../../system/modules/foobar'));
    }

    /**
     * Test that a missing target file is ignored when a package is uninstalled.
     *
     * @return void
     */
    public function testSourcesOnUninstallIgnoresMissingTarget()
    {
        $installer = $this->createInstaller();
        $repo      = $this->mockRepository();
        $package   = $this->mockPackage();

        $basePath = $installer->getInstallPath($package);

        $this->filesystem->ensureDirectoryExists($basePath . '/TL_ROOT/system/modules/foobar/config');
        touch($basePath . '/TL_ROOT/system/modules/foobar/config/config.php');

        $installer->uninstall($repo, $package);
    }

    /**
     * Test that an exception is thrown when uninstalling and the link target is not a link anymore.
     *
     * @expectedException \RuntimeException
     *
     * @return void
     */
    public function testSourcesOnUninstallThrowsExceptionIfTargetIsNotALink()
    {
        $installer = $this->createInstaller();
        $repo      = $this->mockRepository();
        $package   = $this->mockPackage();

        $basePath = $installer->getInstallPath($package);

        $this->filesystem->ensureDirectoryExists($basePath . '/TL_ROOT/system/modules/foobar/config');
        touch($basePath . '/TL_ROOT/system/modules/foobar/config/config.php');

        $this->filesystem->ensureDirectoryExists($basePath . '/../../../system/modules/foobar/config');
        touch($this->filesystem->normalizePath($basePath . '/../../../system/modules/foobar/config/config.php'));

        $installer->uninstall($repo, $package);
    }

    /**
     * Test that an exception is thrown when uninstalling and the link target is now a link to a different file.
     *
     * @expectedException \RuntimeException
     *
     * @return void
     */
    public function testSourcesOnUninstallThrowsExceptionIfTargetLinkIsDifferent()
    {
        $installer = $this->createInstaller();
        $repo      = $this->mockRepository();
        $package   = $this->mockPackage();

        $basePath = $installer->getInstallPath($package);

        $this->filesystem->ensureDirectoryExists($basePath . '/TL_ROOT/system/modules/foobar/config');
        touch($basePath . '/TL_ROOT/system/modules/foobar/config/config.php');

        $this->filesystem->ensureDirectoryExists($basePath . '/../../../system/modules/foobar/config');
        symlink(
            $basePath . '/TL_ROOT/system/modules/foobar/config',
            $this->filesystem->normalizePath($basePath . '/../../../system/modules/foobar/config/config.php')
        );

        $installer->uninstall($repo, $package);
    }

    /**
     * Create a mock of the runonce manager.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|RunonceManager
     */
    private function mockRunonce()
    {
        return $this->getMockBuilder('ContaoCommunityAlliance\\Composer\\Plugin\\RunonceManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock a composer instance.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Composer
     */
    private function mockComposer()
    {
        $tempdir         = $this->tempdir;
        $config          = $this->getMock('Composer\\Config');
        $downloadManager = $this->getMock('Composer\\Downloader\\DownloadManager', [], [], '', false);
        $composer        = $this->getMock('Composer\\Composer', ['getConfig', 'getDownloadManager']);

        $composer
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $composer
            ->expects($this->any())
            ->method('getDownloadManager')
            ->willReturn($downloadManager);

        $config
            ->expects($this->any())
            ->method('get')
            ->with($this->logicalOr(
                $this->equalTo('vendor-dir'),
                $this->equalTo('bin-dir'),
                $this->equalTo('bin-compat')
            ))
            ->willReturnCallback(
                function ($key) use ($tempdir) {
                    switch ($key) {
                        case 'vendor-dir':
                            return $tempdir . '/vendor';

                        case 'bin-dir':
                            return $tempdir . '/vendor/bin';

                        case 'bin-compat':
                            return 'auto';

                        default:
                    }

                    return null;
                }
            );

        return $composer;
    }

    /**
     * Mock a package.
     *
     * @return PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPackage()
    {
        $package = $this->getMock('Composer\\Package\\PackageInterface');

        $package
            ->expects($this->any())
            ->method('getTargetDir')
            ->willReturn('');

        $package
            ->expects($this->any())
            ->method('getName')
            ->willReturn('foo/bar');

        $package
            ->expects($this->any())
            ->method('getPrettyName')
            ->willReturn('foo/bar');

        return $package;
    }

    /**
     * Mock a repository which will always respond with true to calls of "hasPackage".
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|InstalledRepositoryInterface
     */
    private function mockRepository()
    {
        $repo = $this->getMock('Composer\\Repository\\InstalledRepositoryInterface');

        $repo
            ->expects($this->any())
            ->method('hasPackage')
            ->willReturn(true);

        return $repo;
    }

    /**
     * Mock an input/output instance which is very verbose and ensures that only writeError is used (not write()).
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|IOInterface
     */
    private function mockIO()
    {
        $ioMock = $this->getMock('Composer\\IO\\IOInterface');

        $ioMock
            ->expects($this->any())
            ->method('isVerbose')
            ->willReturn(true);

        $ioMock
            ->expects($this->any())
            ->method('isVeryVerbose')
            ->willReturn(true);

        // Should always use writeError() and not write()
        $ioMock
            ->expects($this->never())
            ->method('write');

        return $ioMock;
    }

    /**
     * Create a ContaoModuleInstaller with mocked instances.
     *
     * @return LegacyContaoModuleInstaller
     */
    private function createInstaller()
    {
        $installer = new LegacyContaoModuleInstaller(
            $this->mockRunonce(),
            $this->mockIO(),
            $this->mockComposer(),
            'legacy-contao-module',
            $this->filesystem
        );

        return $installer;
    }
}
