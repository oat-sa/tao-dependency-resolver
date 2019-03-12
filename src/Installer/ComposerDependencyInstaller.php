<?php

namespace OAT\DependencyResolver\Installer;

use Composer\Composer;
use Composer\Installer\SuggestedPackagesReporter;
use Composer\Installer;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use OAT\DependencyResolver\Extension\ExtensionCollection;
use Composer\Semver\Constraint\Constraint;

class ComposerDependencyInstaller
{
    /** @var IOInterface */
    private $io;

    /** @var SuggestedPackagesReporter */
    private $suggestedPackagesReporter;

    public function __construct(IOInterface $io, SuggestedPackagesReporter $suggestedPackagesReporter)
    {
        $this->io = $io;
        $this->suggestedPackagesReporter = $suggestedPackagesReporter;
    }

    public function install(Composer $composer, ExtensionCollection $extensionCollection): int
    {
        $composer->getEventDispatcher()->addListener(
            InstallerEvents::PRE_DEPENDENCIES_SOLVING,
            function (InstallerEvent $event) use ($extensionCollection) {
                foreach ($extensionCollection->getIterator() as $extension) {
                    $composerName = $extension->getComposerName();
                    $branchName = $extension->getPrefixedBranchName();

                    echo 'Requiring manifest repository: ', $composerName . ':' . $branchName, '.', "\n";
                    $event->getRequest()->install($composerName, new Constraint('==', $branchName));
                }
            }
        );

        $install = Installer::create($this->io, $composer);

        // Status code.
        return $install->run();
    }
}
