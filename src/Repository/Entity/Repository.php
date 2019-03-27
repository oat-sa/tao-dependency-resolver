<?php

namespace OAT\DependencyResolver\Repository\Entity;

class Repository implements \JsonSerializable
{
    public const CSV_TITLES = ['repositoryName', 'extensionName', 'composerName', 'privacy', 'packagist', 'defaultBranch'];

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

    public function __construct(
        string $owner = '',
        string $name = '',
        bool $private = false,
        string $defaultBranch = '',
        string $extensionName = '',
        string $composerName = '',
        bool $onPackagist = false,
        array $branches = []
    ) {
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
     *
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

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): self
    {
        $this->private = $private;

        return $this;
    }

    public function getDefaultBranch(): string
    {
        return $this->defaultBranch;
    }

    public function setDefaultBranch(string $defaultBranch): self
    {
        $this->defaultBranch = $defaultBranch;

        return $this;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function setExtensionName(string $extensionName): self
    {
        $this->extensionName = $extensionName;

        return $this;
    }

    public function getComposerName(): string
    {
        return $this->composerName;
    }

    public function setComposerName(string $composerName): self
    {
        $this->composerName = $composerName;

        return $this;
    }

    public function isOnPackagist(): bool
    {
        return $this->onPackagist;
    }

    public function setOnPackagist(bool $onPackagist): self
    {
        $this->onPackagist = $onPackagist;

        return $this;
    }

    public function getBranches(): array
    {
        return $this->branches;
    }

    public function setBranches(array $branches): self
    {
        $this->branches = $branches;

        return $this;
    }

    public function getBranch($branchName): ?RepositoryBranch
    {
        return $this->branches[$branchName] ?? null;
    }

    public function addBranch(RepositoryBranch $branch): self
    {
        $this->branches[$branch->getName()] = $branch;

        return $this;
    }

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

    public function toFlatArray()
    {
        $line = [
            $this->getName(),
            $this->getExtensionName(),
            $this->getComposerName(),
            $this->isPrivate() ? 'private' : 'public',
            $this->isOnPackagist() ? 'yes' : 'no',
            $this->getDefaultBranch(),
        ];

        // Adds csv from each branch.
        $branchNames = ['develop', 'master'];
        foreach ($branchNames as $branchName) {
            $branch = $this->getBranch($branchName);
            $csv = $branch !== null
                ? $branch->toFlatArray()
                : RepositoryBranch::CSV_BLANK;
            $line = array_merge($line, $csv);
        }

        return $line;
    }
}
