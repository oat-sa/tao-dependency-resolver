<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Resolver;

use Composer\Composer;

interface DependencyResolverInterface
{
    public function resolve(Composer $composer, string $packageRemoteUrl, string $packageBranch = null, string $dependenciesBranch = null): void;
}
