<?php

/**
 * Contao Composer Installer
 *
 * Copyright (C) 2013 Contao Community Alliance
 *
 * @package contao-composer
 * @author  Dominik Zogg <dominik.zogg@gmail.com>
 * @author  Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author  Tristan Lins <tristan.lins@bit3.de>
 * @link    http://c-c-a.org
 * @license LGPL-3.0+
 */

namespace ContaoCommunityAlliance\Composer\Plugin\Test\SymlinkInstaller;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use ContaoCommunityAlliance\Composer\Plugin\RunonceManager;
use ContaoCommunityAlliance\Composer\Plugin\Test\TestCase;

class RunonceManagerTest
	extends TestCase
{
	/** @var string */
	protected $rootDir;

	/** @var Filesystem */
	protected $fs;

	/** @var IOInterface */
	protected $io;

	protected function setUp()
	{
		$this->rootDir = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'composer-test-contao';
		$this->fs = new Filesystem;
		$this->io = $this->getMock('Composer\IO\IOInterface');
		$this->fs->ensureDirectoryExists($this->rootDir . '/system');
	}

	protected function tearDown()
	{
		$this->fs->removeDirectory($this->rootDir);
	}

	public function testNothingToDo()
	{
		RunonceManager::createRunonce($this->io, $this->rootDir);
		$this->assertFileNotExists($this->rootDir . '/system/runonce.php');
	}

	public function testSingleRunOnce()
	{
		RunonceManager::addRunonce('composer/vendor/unit/test/runonce1.php');
		RunonceManager::createRunonce($this->io, $this->rootDir);

		$this->assertFileExists($this->rootDir . '/system/runonce.php');
		$file1 = file_get_contents($this->rootDir . '/system/runonce.php');
		$this->assertContains('composer/vendor/unit/test/runonce1.php', $file1, 'runonce1.php has not been added to runonce.php');
	}

	public function testRunonceCreateFiredTwice()
	{
		RunonceManager::addRunonce('composer/vendor/unit/test/runonce1.php');
		RunonceManager::createRunonce($this->io, $this->rootDir);

		RunonceManager::addRunonce('composer/vendor/unit/test/runonce2.php');
		RunonceManager::createRunonce($this->io, $this->rootDir);

		$this->assertFileExists($this->rootDir . '/system/runonce.php');
		$this->assertFileExists($this->rootDir . '/system/runonce_1.php');

		$file1 = file_get_contents($this->rootDir . '/system/runonce.php');
		$this->assertContains('system/runonce_1.php', $file1);
		$this->assertContains('composer/vendor/unit/test/runonce2.php', $file1);

		$file2 = file_get_contents($this->rootDir . '/system/runonce_1.php');
		$this->assertContains('composer/vendor/unit/test/runonce1.php', $file2);
		$this->assertNotContains('composer/vendor/unit/test/runonce2.php', $file2, 'runonce1 is also mentioned in new runonce.php');
	}

	public function testRunonceCreateFiredBeforeRunonceExecuted()
	{
		$array = var_export(array('composer/vendor/unit/test/runonce1.php'), true);
		$testData = <<<EOF
<?php

\$executor = new \ContaoCommunityAlliance\Composer\Plugin\RunonceExecutor();
\$executor->run($array);

EOF;
		file_put_contents($this->rootDir . '/system/runonce.php', $testData);
		RunonceManager::addRunonce('composer/vendor/unit/test/runonce1.php');
		RunonceManager::addRunonce('composer/vendor/unit/test/runonce2.php');
		RunonceManager::createRunonce($this->io, $this->rootDir);

		$this->assertFileExists($this->rootDir . '/system/runonce.php', 'Runonce does not exist.');
		$this->assertFileExists($this->rootDir . '/system/runonce_1.php', 'Secondary runonce does not exist.');
		$file1 = file_get_contents($this->rootDir . '/system/runonce.php');
		$file2 = file_get_contents($this->rootDir . '/system/runonce_1.php');

		$this->assertEquals($testData, $file2, 'Test runonce should have been moved to runonce_1.php');

		$this->assertContains('system/runonce_1.php', $file1, 'Previous runonce has not been added.');
		$this->assertContains('composer/vendor/unit/test/runonce2.php', $file1, 'runonce2 from module has not been added.');
		$this->assertNotContains('composer/vendor/unit/test/runonce1.php', $file1, 'runonce1 is also mentioned in new runonce.php');
	}
}
