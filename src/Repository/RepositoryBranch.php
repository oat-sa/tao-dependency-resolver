<?php

namespace OAT\DependencyResolver\Repository;

class RepositoryBranch implements \JsonSerializable
{
    const CSV_TITLES = ['branchName'];
    const CSV_BLANK = ['', '', '', '', '', '', '', '', ''];

    /** @var string */
    private $name = '';

    /** @var array|RepositoryFile[] */
    private $files = [];

    /**
     * RepositoryBranch constructor.
     * @param string $name
     * @param array|RepositoryFile[]|null $files
     */
    public function __construct(string $name = '', array $files = [])
    {
        $this
            ->setName($name)
            ->setFiles($files);
    }

    /**
     * @param array $properties
     * @return $this
     */
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
     * @return array|RepositoryFile[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param array|RepositoryFile[] $files
     * @return $this
     */
    public function setFiles(array $files): self
    {
        $this->files = $files;
        return $this;
    }

    /**
     * @param string $fileName
     * @return RepositoryFile|null
     */
    public function getFile($fileName): ?RepositoryFile
    {
        return $this->files[$fileName] ?? null;
    }

    /**
     * @param RepositoryFile $file
     * @return $this
     */
    public function addFile(RepositoryFile $file): self
    {
        $this->files[$file->getName()] = $file;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'files' => $this->getFiles(),
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
        ];

        // Find files manifest and composer.
        $fileNames = [GitHubRepositoryReader::MANIFEST_FILENAME, GitHubRepositoryReader::COMPOSER_FILENAME];
        foreach ($fileNames as $fileName) {
            $file = $this->getFile($fileName);
            $csv = $file !== null
                ? $file->toCsv()
                : RepositoryFile::CSV_BLANK;
            $line = array_merge($line, $csv);
        }

        return $line;
    }
}
