<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository;

use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Entity\RepositoryBranch;
use OAT\DependencyResolver\Repository\Entity\RepositoryFile;

class RepositoryMapConverter
{
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
                Repository::CSV_TITLES,
                RepositoryBranch::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryBranch::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryBranch::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryFile::CSV_TITLES
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
