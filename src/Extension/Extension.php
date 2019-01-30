<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

class Extension
{
    const DEFAULT_BRANCH = 'develop';
    const BRANCH_PREFIX = 'dev';
    const GITHUB_RAW_BASE_URL = 'https://raw.githubusercontent.com';

    /** @var string */
    private $extensionName;

    /** @var string */
    private $repositoryName;

    /** @var string */
    private $branchName = self::DEFAULT_BRANCH;

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
        return sprintf('%s-%s', static::BRANCH_PREFIX, $this->branchName);
    }

    public function getRemoteComposerUrl(): string
    {
        return sprintf('%s/%s/%s/composer.json', static::GITHUB_RAW_BASE_URL, $this->repositoryName, $this->branchName);
    }

    public function getRemoteManifestUrl(): string
    {
        return sprintf('%s/%s/%s/manifest.php', static::GITHUB_RAW_BASE_URL, $this->repositoryName, $this->branchName);
    }
}

