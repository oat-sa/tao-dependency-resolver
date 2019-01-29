<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Resolver;

use Composer\Composer;
use Composer\Installer;
use Composer\IO\IOInterface;

class ComposerDependencyResolver implements DependencyResolverInterface
{
    /** @var IOInterface */
    private $IO;

    public function __construct(IOInterface $IO)
    {
        $this->IO = $IO;
    }

    public function resolve(Composer $composer, string $packageRemoteUrl, string $packageBranch = null, string $dependenciesBranch = null): void
    {
        $install = Installer::create($this->IO, $composer);

        $install->run();
    }
}
