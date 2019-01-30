<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

class Extension
{
    const DEFAULT_BRANCH = 'develop';
    const BRANCH_PREFIX = 'dev';

    /** @var string */
    private $extensionName; // taoItems

    /** @var string */
    private $repositoryName; // oat-sa/tao-core

    /** @var string */
    private $branch = self::DEFAULT_BRANCH;

    /**
     * Extension constructor.
     * @param string $extensionName
     * @param string $composerName
     * @param string $repositoryName
     * @param string $branch
     */
    public function __construct(string $extensionName, string $repositoryName, string $branch)
    {
        $this->extensionName = $extensionName;
        $this->repositoryName = $repositoryName;
        $this->branch = $branch;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getRepositoryName(): string
    {
        return $this->repositoryName;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function getPrefixedBranch(): string
    {
        return sprintf('%s-%s', static::BRANCH_PREFIX, $this->branch);
    }

    public function getRemoteComposerUrl(): string
    {
        return 'https://raw.githubusercontent.com/' .$this->repositoryName. '/' . $this->branch . '/composer.json';
    }

    public function getRemoteManifestUrl(): string
    {
        return 'https://raw.githubusercontent.com/' .$this->repositoryName. '/' . $this->branch . '/manifest.php';
    }
}
