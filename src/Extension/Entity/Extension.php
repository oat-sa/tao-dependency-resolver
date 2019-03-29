<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Extension\Entity;

class Extension
{
    public const DEFAULT_BRANCH = 'develop';
    private const BRANCH_PREFIX = 'dev';

    /** @var string */
    private $extensionName;

    /** @var string */
    private $repositoryName;

    /** @var string */
    private $branchName;

    public function __construct(string $extensionName, string $repositoryName, string $branchName)
    {
        $this->extensionName = $extensionName;
        $this->repositoryName = $repositoryName;
        $this->branchName = $branchName;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getRepositoryName(): string
    {
        return $this->repositoryName;
    }

    public function getBranchName(): string
    {
        return $this->branchName;
    }

    public function getPrefixedBranchName(): string
    {
        return sprintf('%s-%s', self::BRANCH_PREFIX, $this->branchName);
    }
}
