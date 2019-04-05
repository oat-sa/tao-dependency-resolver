<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository\Entity;

use OAT\DependencyResolver\Repository\GitHubRepositoryReader;

class RepositoryBranch implements \JsonSerializable
{
    public const CSV_BLANK = ['', '', '', '', '', '', '', '', ''];

    /** @var string */
    private $name;

    /** @var RepositoryFile[]|array */
    private $files;

    public function __construct(string $name = '', array $files = [])
    {
        $this
            ->setName($name)
            ->setFiles($files);
    }

    public static function createFromArray(array $properties): self
    {
        $files = [];
        $filesField = $properties['files'] ?? [];
        foreach ($filesField as $fileProperties) {
            $file = RepositoryFile::createFromArray($fileProperties);
            $files[$file->getName()] = $file;
        }

        return new self($properties['name'] ?? '', $files);
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

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): self
    {
        $this->files = $files;

        return $this;
    }

    public function getFile($fileName): ?RepositoryFile
    {
        return $this->files[$fileName] ?? null;
    }

    public function addFile(RepositoryFile $file): self
    {
        $this->files[$file->getName()] = $file;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'files' => $this->getFiles(),
        ];
    }

    public function toFlatArray()
    {
        $line = [
            $this->getName(),
        ];

        // Find files manifest and composer.
        $fileNames = [GitHubRepositoryReader::MANIFEST_FILENAME, GitHubRepositoryReader::COMPOSER_FILENAME];
        foreach ($fileNames as $fileName) {
            $file = $this->getFile($fileName);
            $csv = $file !== null
                ? $file->toFlatArray()
                : RepositoryFile::CSV_BLANK;
            $line = array_merge($line, $csv);
        }

        return $line;
    }
}
