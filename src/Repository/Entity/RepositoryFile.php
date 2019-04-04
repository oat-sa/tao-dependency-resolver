<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository\Entity;

class RepositoryFile implements \JsonSerializable
{
    public const CSV_BLANK = ['', '', '', ''];

    /** @var string */
    private $name;

    /** @var string */
    private $composerName;

    /** @var string */
    private $extensionName;

    /** @var array */
    private $requires;

    public function __construct(
        string $name = '',
        string $composerName = '',
        string $extensionName = '',
        array $requires = []
    ) {
        $this
            ->setName($name)
            ->setComposerName($composerName)
            ->setExtensionName($extensionName)
            ->setRequires($requires);
    }

    public static function createFromArray(array $properties): self
    {
        return new self(
            $properties['name'] ?? '',
            $properties['composerName'] ?? '',
            $properties['extensionName'] ?? '',
            $properties['requires'] ?? []
        );
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

    public function getComposerName(): string
    {
        return $this->composerName;
    }

    public function setComposerName(string $composerName): self
    {
        $this->composerName = $composerName;

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

    public function getRequires(): array
    {
        return $this->requires;
    }

    public function setRequires(array $requires): self
    {
        $this->requires = $requires;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'composerName' => $this->getComposerName(),
            'extensionName' => $this->getExtensionName(),
            'requires' => $this->getRequires(),
        ];
    }

    public function toFlatArray()
    {
        return [
            $this->getName(),
            $this->getComposerName(),
            $this->getExtensionName(),
            implode('|', $this->getRequires()),
        ];
    }
}
