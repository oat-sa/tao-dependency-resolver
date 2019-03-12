<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

class Extension
{
    const DEFAULT_BRANCH = 'develop';
    const BRANCH_PREFIX = 'dev';

    /** @var string */
    private $extensionName;

    /** @var string */
    private $repositoryName;

    /** @var string */
    private $composerName = '';

    /** @var string */
    private $branchName = self::DEFAULT_BRANCH;

    /**
     * Extension constructor.
     * @param string $extensionName
     * @param string $repositoryName
     * @param string $branchName
     */
    public function __construct(string $extensionName, string $repositoryName, string $branchName)
    {
        $this->extensionName = $extensionName;
        $this->repositoryName = $repositoryName;
        $this->branchName = $branchName;
    }

    /**
     * @return string
     */
    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    /**
     * @return string
     */
    public function getRepositoryName(): string
    {
        return $this->repositoryName;
    }

    /**
     * @return string
     */
    public function getComposerName(): string
    {
        return $this->composerName;
    }

    /**
     * @param string $composerName
     * @return $this
     */
    public function setComposerName(string $composerName): self
    {
        $this->composerName = $composerName;
        return $this;
    }

    /**
     * @return string
     */
    public function getBranchName(): string
    {
        return $this->branchName;
    }

    /**
     * @return string
     */
    public function getPrefixedBranchName(): string
    {
        return sprintf('%s-%s', static::BRANCH_PREFIX, $this->branchName);
    }
}

