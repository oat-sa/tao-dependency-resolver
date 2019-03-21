<?php

namespace OAT\DependencyResolver\Repository\Entity;

class RepositoryFile implements \JsonSerializable
{
    const CSV_TITLES = ['filename', 'composerName', 'extensionName', 'requires'];
    const CSV_BLANK = ['', '', '', ''];

    /** @var string */
    private $name = '';

    /** @var string */
    private $composerName = '';

    /** @var string */
    private $extensionName = '';

    /** @var array */
    private $requires = [];

    /**
     * RepositoryFile constructor.
     * @param string $name
     * @param string $composerName
     * @param string $extensionName
     * @param array $requires
     */
    public function __construct(string $name = '', string $composerName = '', string $extensionName = '', array $requires = [])
    {
        $this->name = $name;
        $this->composerName = $composerName;
        $this->extensionName = $extensionName;
        $this->requires = $requires;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function constructFromArray(array $properties): self
    {
        $this
            ->setName($properties['name'])
            ->setComposerName($properties['composerName'])
            ->setExtensionName($properties['extensionName'])
            ->setRequires($properties['requires']);

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
     * @return array
     */
    public function getRequires(): array
    {
        return $this->requires;
    }

    /**
     * @param array $requires
     * @return $this
     */
    public function setRequires(array $requires): self
    {
        $this->requires = $requires;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'composerName' => $this->getComposerName(),
            'extensionName' => $this->getExtensionName(),
            'requires' => $this->getRequires(),
        ];
    }

    /**
     * Converts to Csv line.
     *
     * @return array
     */
    public function toCsv()
    {
        return [
            $this->getName(),
            $this->getComposerName(),
            $this->getExtensionName(),
            implode('|', $this->getRequires()),
        ];
    }
}