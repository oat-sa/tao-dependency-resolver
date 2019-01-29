<?php

namespace OAT\DependencyResolver\Downloader;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\Installer\InstallationManager;
use Composer\Installer\ProjectInstaller;
use Composer\Installer\SuggestedPackagesReporter;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\AliasPackage;
use Composer\Package\BasePackage;
use Composer\Package\Version\VersionParser;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\InstalledFilesystemRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Util\Filesystem;

class RootPackageDownloader
{
    /** @var IOInterface */
    private $IO;

    /** @var SuggestedPackagesReporter */
    private $suggestedPackagesReporter;

    public function __construct(IOInterface $IO, SuggestedPackagesReporter $suggestedPackagesReporter)
    {
        $this->IO = $IO;
        $this->suggestedPackagesReporter = $suggestedPackagesReporter;
    }

    public function download(Config $config, string $packageName, string $directory)
    {
        $sourceRepo = new CompositeRepository(RepositoryFactory::defaultRepos($this->IO, $config));

        $parser = new VersionParser();
        $requirements = $parser->parseNameVersionPairs(array($packageName));
        $name = strtolower($requirements[0]['name']);
        $packageVersion = null;

        if (isset($requirements[0]['version'])) {
            $packageVersion = $requirements[0]['version'];
        }

        if (preg_match('{^[^,\s]*?@(' . implode('|', array_keys(BasePackage::$stabilities)) . ')$}i', $packageVersion, $match)) {
            $stability = $match[1];
        } else {
            $stability = VersionParser::parseStability($packageVersion);
        }

        $stability = VersionParser::normalizeStability($stability);

        if (!isset(BasePackage::$stabilities[$stability])) {
            throw new \InvalidArgumentException('Invalid stability provided (' . $stability . '), must be one of: ' . implode(', ', array_keys(BasePackage::$stabilities)));
        }

        $pool = new Pool($stability);
        $pool->addRepository($sourceRepo);

        $phpVersion = null;
        $prettyPhpVersion = null;

        $platformOverrides = array();
        // initialize $this->repos as it is used by the parent InitCommand
        $platform = new PlatformRepository(array(), $platformOverrides);
        $phpPackage = $platform->findPackage('php', '*');
        $phpVersion = $phpPackage->getVersion();
        $prettyPhpVersion = $phpPackage->getPrettyVersion();

        // find the latest version if there are multiple
        $versionSelector = new VersionSelector($pool);
        $package = $versionSelector->findBestCandidate($name, $packageVersion, $phpVersion, $stability);

        if (!$package) {
            $errorMessage = "Could not find package $name with " . ($packageVersion ? "version $packageVersion" : "stability $stability");
            if ($phpVersion && $versionSelector->findBestCandidate($name, $packageVersion, null, $stability)) {
                throw new \InvalidArgumentException($errorMessage .' in a version installable using your PHP version '.$prettyPhpVersion.'.');
            }

            throw new \InvalidArgumentException($errorMessage .'.');
        }

        // handler Ctrl+C for unix-like systems
        if (function_exists('pcntl_async_signals')) {
            @mkdir($directory, 0777, true);
            if ($realDir = realpath($directory)) {
                pcntl_async_signals(true);
                pcntl_signal(SIGINT, function () use ($realDir) {
                    $fs = new Filesystem();
                    $fs->removeDirectory($realDir);
                    exit(130);
                });
            }
        }

        if ($package instanceof AliasPackage) {
            $package = $package->getAliasOf();
        }

        $dm = $this->createDownloadManager($this->IO, $config);

        $projectInstaller = new ProjectInstaller($directory, $dm);
        $im = $this->createInstallationManager();
        $im->addInstaller($projectInstaller);
        $im->install(new InstalledFilesystemRepository(new JsonFile('php://memory')), new InstallOperation($package));
        $im->notifyInstalls($this->IO);

        // collect suggestions
        $this->suggestedPackagesReporter->addSuggestionsFromPackage($package);

        chdir($directory);

        $_SERVER['COMPOSER_ROOT_VERSION'] = $package->getPrettyVersion();
        putenv('COMPOSER_ROOT_VERSION='.$_SERVER['COMPOSER_ROOT_VERSION']);
    }

    protected function createDownloadManager(IOInterface $io, Config $config)
    {
        $factory = new Factory();

        return $factory->createDownloadManager($io, $config);
    }

    protected function createInstallationManager()
    {
        return new InstallationManager();
    }
}
