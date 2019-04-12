<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository;

use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Entity\RepositoryBranch;
use OAT\DependencyResolver\Repository\Entity\RepositoryFile;

class RepositoryMapConverter
{
    private const REPOSITORY_CSV_TITLES = [
        'repositoryName',
        'extensionName',
        'composerName',
        'privacy',
        'packagist',
        'defaultBranch',
    ];
    public const BRANCH_CSV_TITLES = ['branchName'];
    public const FILE_CSV_TITLES = ['filename', 'composerName', 'extensionName', 'requires'];

    /** @var RepositoryMapAccessor */
    private $repositoryMapAccessor;

    public function __construct(RepositoryMapAccessor $repositoryMapAccessor)
    {
        $this->repositoryMapAccessor = $repositoryMapAccessor;
    }

    /**
     * Converts repository map to csv.
     *
     * @return array
     * @throws \LogicException
     */
    public function toCsv(): array
    {
        $repositories = $this->repositoryMapAccessor->read();

        // Sets titles.
        $csv = [
            implode(',', array_merge(
                self::REPOSITORY_CSV_TITLES,
                self::BRANCH_CSV_TITLES,
                self::FILE_CSV_TITLES,
                self::FILE_CSV_TITLES,
                self::BRANCH_CSV_TITLES,
                self::FILE_CSV_TITLES,
                self::FILE_CSV_TITLES,
                self::BRANCH_CSV_TITLES,
                self::FILE_CSV_TITLES,
                self::FILE_CSV_TITLES
            )),
        ];

        // Builds Repository objects.
        /** @var Repository $repository */
        foreach ($repositories as $repository) {
            $csv[] = implode(',', $repository->toFlatArray());
        }

        return $csv;
    }
}
