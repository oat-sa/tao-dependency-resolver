<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension\Entity;

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
     * @param string $composerName
     * @param string $branchName
     */
    public function __construct(string $extensionName, string $repositoryName, string $composerName, string $branchName)
    {
        $this->extensionName = $extensionName;
        $this->repositoryName = $repositoryName;
        $this->composerName = $composerName;
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
