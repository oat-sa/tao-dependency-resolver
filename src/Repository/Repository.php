<?php

namespace OAT\DependencyResolver\Repository;

class Repository implements \JsonSerializable
{
    const CSV_TITLES = ['repositoryName', 'extensionName', 'composerName', 'privacy', 'packagist', 'defaultBranch'];

    /** @var string */
    private $owner = '';

    /** @var string */
    private $name = '';

    /** @var bool */
    private $private = false;

    /** @var string */
    private $defaultBranch = '';

    /** @var string */
    private $extensionName = '';

    /** @var string */
    private $composerName = '';

    /** @var bool */
    private $onPackagist = false;

    /** @var array */
    private $branches = [
        'develop' => null,
        'master' => null,
        'other' => null,
    ];

    /**
     * Repository constructor.
     * @param string $owner
     * @param string $name
     * @param bool $private
     * @param string $defaultBranch
     * @param string $extensionName
     * @param string $composerName
     * @param bool $onPackagist
     * @param array $branches
     */
    public function __construct(
        string $owner = '',
        string $name = '',
        bool $private = false,
        string $defaultBranch = '',
        string $extensionName = '',
        string $composerName = '',
        bool $onPackagist = false,
        array $branches = []
    )
    {
        $this
            ->setOwner($owner)
            ->setName($name)
            ->setPrivate($private)
            ->setDefaultBranch($defaultBranch)
            ->setExtensionName($extensionName)
            ->setComposerName($composerName)
            ->setOnPackagist($onPackagist)
            ->setBranches($branches);
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function constructFromArray(array $properties): self
    {
        $branches = [];
        foreach ($properties['branches'] as $branchProperties) {
            $branch = (new RepositoryBranch())->constructFromArray($branchProperties);
            $branches[$branch->getName()] = $branch;
        }

        $this
            ->setOwner($properties['owner'])
            ->setName($properties['name'])
            ->setPrivate($properties['private'])
            ->setDefaultBranch($properties['defaultBranch'])
            ->setExtensionName($properties['extensionName'])
            ->setComposerName($properties['composerName'])
            ->setOnPackagist($properties['onPackagist'])
            ->setBranches($branches);

        return $this;
    }

    /**
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     * @return $this
     */
    public function setOwner(string $owner): self
    {
        $this->owner = $owner;
        return $this;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->private;
    }

    /**
     * @param bool $private
     * @return $this
     */
    public function setPrivate(bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultBranch(): string
    {
        return $this->defaultBranch;
    }

    /**
     * @param string $defaultBranch
     * @return $this
     */
    public function setDefaultBranch(string $defaultBranch): self
    {
        $this->defaultBranch = $defaultBranch;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    /**
     * @param string $extensionName
     * @return $this
     */
    public function setExtensionName(string $extensionName): self
    {
        $this->extensionName = $extensionName;
        return $this;
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
     * @return bool
     */
    public function isOnPackagist(): bool
    {
        return $this->onPackagist;
    }

    /**
     * @param bool $onPackagist
     * @return $this
     */
    public function setOnPackagist(bool $onPackagist): self
    {
        $this->onPackagist = $onPackagist;
        return $this;
    }

    /**
     * @return array
     */
    public function getBranches(): array
    {
        return $this->branches;
    }

    /**
     * @param array $branches
     * @return $this
     */
    public function setBranches(array $branches): self
    {
        $this->branches = $branches;
        return $this;
    }

    /**
     * @param string $branchName
     * @return RepositoryBranch|null
     */
    public function getBranch($branchName): ?RepositoryBranch
    {
        return $this->branches[$branchName] ?? null;
    }

    /**
     * @param RepositoryBranch $branch
     * @return $this
     */
    public function addBranch(RepositoryBranch $branch): self
    {
        $this->branches[$branch->getName()] = $branch;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'owner' => $this->getOwner(),
            'name' => $this->getName(),
            'private' => $this->isPrivate(),
            'defaultBranch' => $this->getDefaultBranch(),
            'extensionName' => $this->getExtensionName(),
            'composerName' => $this->getComposerName(),
            'onPackagist' => $this->isOnPackagist(),
            'branches' => $this->getBranches(),
        ];
    }

    /**
     * Converts to Csv line.
     *
     * @return array
     */
    public function toCsv()
    {
        $line = [
            $this->getName(),
            $this->getExtensionName(),
            $this->getComposerName(),
            $this->isPrivate() ? 'private' : 'public',
            $this->isOnPackagist() ? 'yes' : 'no',
            $this->getDefaultBranch(),
        ];

        // Find branches other than mandatory "develop" and "master" and put them at the end.
        $branchNames = ['develop', 'master'];
        foreach (array_keys($this->getBranches()) as $branchName) {
            if (!in_array($branchName, $branchNames)) {
                $branchNames[] = $branchName;
            }
        }

        // Adds csv from each branch.
        foreach ($branchNames as $branchName) {
            $branch = $this->getBranch($branchName);
            $csv = $branch !== null
                ? $branch->toCsv()
                : RepositoryBranch::CSV_BLANK;
            $line = array_merge($line, $csv);
        }

        return $line;
    }
}
