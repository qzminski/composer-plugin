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

use Composer\Util\Filesystem;
use ContaoCommunityAlliance\Composer\Plugin\Environment\Contao3Environment;
use ContaoCommunityAlliance\Composer\Plugin\Test\TestCase;

class Contao3EnvironmentTest extends TestCase
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

    /**
     * @var Filesystem
     */
    protected $fs;

    protected function setUp()
    {
        $this->fs       = new Filesystem();
        $this->curDir   = getcwd();
        $this->testRoot = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'composer-test-submodule';
    }

    protected function tearDown()
    {
        chdir($this->curDir);
        $this->fs->removeDirectory($this->testRoot);
    }

    /**
     * Prepare the test directory and the plugin.
     *
     * @param $configDir
     *
     * @param $version
     *
     * @param $build
     *
     * @return void
     */
    protected function createConstantsPhp($configDir, $version, $build)
    {
        $this->ensureDirectoryExistsAndClear($configDir);
        if (!chdir($this->testRoot))
        {
            $this->markTestIncomplete('Could not change to temp dir. Test incomplete!');
        }

        file_put_contents($configDir  . DIRECTORY_SEPARATOR . 'constants.php', '
<?php

/**
 * Contao Open Source CMS (Micro Mock)
 *
 * Copyright (c) 0000-9999 A. L. User
 *
 * @package Core
 * @link    https://example.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Core version
 */
define(\'VERSION\', \'' . $version . '\');
define(\'BUILD\', \'' . $build . '\');
define(\'LONG_TERM_SUPPORT\', true);

');
    }

    /**
     * @param string $expectVersion
     * @param string $expectBuild
     *
     * @dataProvider determineContao3VersionProvider
     */
    public function testDetermineContao3Version($expectVersion, $expectBuild)
    {
        $configDir = $this->testRoot . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $this->createConstantsPhp($configDir, $expectVersion, $expectBuild);
        $environment = new Contao3Environment($this->testRoot);

        $this->assertEquals($expectVersion, $environment->getVersion());
        $this->assertEquals($expectBuild, $environment->getBuild());
        $this->assertEquals($expectVersion . '.' . $expectBuild, $environment->getFullVersion());
    }

    /**
     * Provide all test values.
     */
    public function determineContao3VersionProvider()
    {
        return array(
            array('3.2', '99'),
            array('3.3', '99'),
            array('3.4', '99'),
            array('3.5', '99'),
            array('3.5', '99-RC1'),
        );
    }

    /**
     * Test the
     * @param string $fixturesDir
     *
     * @param string $configKey
     *
     * @param mixed  $expectResult
     *
     * @dataProvider readContao3ConfigValueProvider
     */
    public function testReadContao3ConfigValue($fixturesDir, $configKey, $expectResult)
    {
        $environment = new Contao3Environment($fixturesDir);

        $this->assertEquals($expectResult, $environment->getConfigKey($configKey));
    }

    public function readContao3ConfigValueProvider()
    {
        $fixtures = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;
        return array(
            array($fixtures . 'config1', 'characterSet', 'utf-8'),
            array($fixtures . 'config1', 'adminEmail', ''),
            array($fixtures . 'config1', 'enableSearch', true),
            array($fixtures . 'config1', 'indexProtected', false),
            array($fixtures . 'config1', 'dbPort', 3306),
            array($fixtures . 'config1', 'requestTokenWhitelist', array()),
            array($fixtures . 'config1', 'websiteTitle', 'Overridden'),
        );
    }
}
