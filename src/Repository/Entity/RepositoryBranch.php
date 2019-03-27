<?php

namespace OAT\DependencyResolver\Repository\Entity;

use OAT\DependencyResolver\Repository\GitHubRepositoryReader;

class RepositoryBranch implements \JsonSerializable
{
    public const CSV_TITLES = ['branchName'];
    public const CSV_BLANK = ['', '', '', '', '', '', '', '', ''];

    /** @var string */
    private $name = '';

    /** @var array|RepositoryFile[] */
    private $files = [];

    public function __construct(string $name = '', array $files = [])
    {
        $this
            ->setName($name)
            ->setFiles($files);
    }

    public function constructFromArray(array $properties): self
    {
        $files = [];
        foreach ($properties['files'] as $fileProperties) {
            $file = (new RepositoryFile())->constructFromArray($fileProperties);
            $files[$file->getName()] = $file;
        }

        $this
            ->setName($properties['name'])
            ->setFiles($files);

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
