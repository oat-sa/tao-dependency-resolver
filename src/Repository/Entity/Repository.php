<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository\Entity;

class Repository implements \JsonSerializable
{
    public const CSV_TITLES = [
        'repositoryName',
        'extensionName',
        'composerName',
        'privacy',
        'packagist',
        'defaultBranch',
    ];

    /** @var bool */
    private $analyzed;

    /** @var string */
    private $owner;

    /** @var string */
    private $name;

    /** @var bool */
    private $private;

    /** @var string */
    private $defaultBranch;

    /** @var string */
    private $extensionName;

    /** @var string */
    private $composerName;

    /** @var bool */
    private $onPackagist;

    /** @var RepositoryBranch[]|array */
    private $branches;

    public function __construct(
        bool $analyzed,
        string $owner,
        string $name,
        bool $private,
        string $defaultBranch,
        string $extensionName,
        string $composerName,
        bool $onPackagist,
        array $branches
    ) {
        $this
            ->setAnalyzed($analyzed)
            ->setOwner($owner)
            ->setName($name)
            ->setPrivate($private)
            ->setDefaultBranch($defaultBranch)
            ->setExtensionName($extensionName)
            ->setComposerName($composerName)
            ->setOnPackagist($onPackagist)
            ->setBranches($branches);
    }

    public static function createFromArray(array $properties): self
    {
        $branches = [];
        $branchesField = $properties['branches'] ?? [];
        foreach ($branchesField as $branchProperties) {
            $branch = RepositoryBranch::createFromArray($branchProperties);
            $branches[$branch->getName()] = $branch;
        }

        return new self(
            $properties['analyzed'] ?? false,
            $properties['owner'] ?? '',
            $properties['name'] ?? '',
            $properties['private'] ?? false,
            $properties['defaultBranch'] ?? '',
            $properties['extensionName'] ?? '',
            $properties['composerName'] ?? '',
            $properties['onPackagist'] ?? false,
            $branches
        );
    }

    public function isAnalyzed(): bool
    {
        return $this->analyzed;
    }

    public function setAnalyzed(bool $analyzed): self
    {
        $this->analyzed = $analyzed;

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

    /**
     * @return RepositoryBranch[]|array
     */
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
            'analyzed' => $this->isAnalyzed(),
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
