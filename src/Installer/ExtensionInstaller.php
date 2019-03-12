<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Installer;

use Composer\Factory;

class ExtensionInstaller
{
    /** @var RootPackageInstaller */
    private $rootPackageInstaller;

    /** @var ComposerDependencyInstaller */
    private $composerDependencyInstaller;

    /** @var Factory */
    private $factory;

    public function __construct(
        Factory $factory,
        RootPackageInstaller $rootPackageInstaller,
        ComposerDependencyInstaller $composerDependencyInstaller
    )
    {
        $this->factory = $factory;
        $this->rootPackageInstaller = $rootPackageInstaller;
        $this->composerDependencyInstaller = $composerDependencyInstaller;
    }

    // Install root and dependency repositories.
    public function install($rootExtension, $extensionCollection, $directory, $consoleIo)
    {
        // Download root package.
        $composerConfig = $this->factory::createConfig($consoleIo);
        $statusCode = $this->rootPackageInstaller->install($composerConfig, $rootExtension, $directory);
        if($statusCode) {
            return $statusCode;
        }

        // Install all extension.
        $composer = $this->factory->createComposer($consoleIo, $directory . DIRECTORY_SEPARATOR . 'composer.json', false, $directory);
        return $this->composerDependencyInstaller->install($composer, $extensionCollection);
    }
}