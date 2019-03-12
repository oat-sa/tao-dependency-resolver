<?php

namespace OAT\DependencyResolver\Repository;

use Github\Api\ApiInterface;
use Github\Api\GitData;
use Github\Api\Organization;
use Github\Api\Repo;
use Github\Client;
use Github\Exception\RuntimeException;

class ConnectedGithubClient extends Client
{
    const REPOSITORIES_PER_PAGE = 100;

    /** @var string */
    private $organization = '';

    /** @var string */
    private $token = '';

    /** @var bool */
    private $connected = false;

    /**
     * Stores credentials for github client.
     *
     * @param string $organization
     * @param string $token
     */
    public function storeCredentials(string $organization, string $token)
    {
        $this->organization = $organization;
        $this->token = $token;
    }

    /**
     * Always make authenticated requests to avoid limitations.
     * Returns organisation's number of private and public repositories.
     *
     * @todo: add some cache
     * @see https://github.com/KnpLabs/php-github-api#cache-usage
     *
     * @return array
     */
    public function authenticateAndCheck(): array
    {
        if ($this->connected) {
            return [];
        }

        $this->authenticate($this->token, null, self::AUTH_HTTP_TOKEN);

        $organisationApi = $this->getOrganizationApi();
        try {
            $organisationProps = $organisationApi->show($this->organization);
        } catch (\Exception $exception) {
            throw new \RuntimeException('A error occurred when trying to authenticate to Github API: ' . $exception->getMessage());
        }

        $this->connected = true;

        return $organisationProps;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryList(string $owner): array
    {
        $organisationProps = $this->authenticateAndCheck();

        $message = 'Connected to GitHub with http token.' . "\n"
            . 'Organisation "' . $this->organization . '" has:' . "\n"
            . '- ' . $organisationProps['public_repos'] . ' public repositories' . "\n"
            . '- ' . $organisationProps['total_private_repos'] . ' private repositories' . "\n";
        echo $message;

        $organisationApi = $this->getOrganizationApi();
        $organisationApi->setPerPage(self::REPOSITORIES_PER_PAGE);

        $repositories = [];
        $page = 0;
        do {
            $page++;
            $foundRepositories = $organisationApi->repositories($owner, 'all', $page);
            foreach ($foundRepositories as $repository) {
                $repositoryName = $repository['name'];
                $repositories[$owner . '/' . $repositoryName] = new Repository(
                    $owner,
                    $repositoryName,
                    $repository['private'],
                    $repository['default_branch']
                );
            }
        } while (count($foundRepositories) === self::REPOSITORIES_PER_PAGE);

        return $repositories;
    }

    /**
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchName
     * @param string $filename
     * @return string|null
     * @throws \Github\Exception\ErrorException
     */
    public function getContents(string $owner, string $repositoryName, string $branchName, string $filename): ?string
    {
        $this->authenticateAndCheck();

        // Gets a reference to the given branch of the repository if it exists.
        $branchReference = $this->getBranchReference($owner, $repositoryName, $branchName);

        try {
            return $this->getRepositoryApi()
                ->contents()
                ->download($owner, $repositoryName, $filename, $branchReference);
        } catch (RuntimeException $exception) {
            if ($exception->getCode() === 404) {
                // Throws an understandable exception.
                throw new FileNotFoundException('File "' . $filename . '" not found in branch "' . $branchName . '".');
            }
        }
    }

    /**
     * Checks existence of a branch.
     *
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchName
     *
     * @return string
     * @throws BranchNotFoundException when the branch does not exist.
     * @throws EmptyRepositoryException when the repository is empty.
     * @throws RuntimeException when another error occurs.
     */
    public function getBranchReference(string $owner, string $repositoryName, string $branchName)
    {
        $this->authenticateAndCheck();

        // Gets a reference to the given branch of the repository if it exists.
        try {
            $reference = $this->getGitDataApi()
                ->references()
                ->show($owner, $repositoryName, 'heads/' . $branchName);
        } catch (RuntimeException $exception) {
            switch ($exception->getCode()) {
                case 404:
                    // Throws an understandable exception.
                    throw new BranchNotFoundException('Unable to retrieve reference to "' . $owner . '/' . $repositoryName . '/' . $branchName . '".');

                case 409:
                    // Throws an understandable exception.
                    throw new EmptyRepositoryException($repositoryName);
            }

            throw $exception;
        }

        // Returns only the reference.
        if (isset($reference['ref'])) {
            return $reference['ref'];
        }

        // More than one reference returned: branchname was not found but a subpart of found references.
        $foundBranches = [];
        foreach ($reference as $foundBranch) {
            $foundBranches[] = $foundBranch['ref'];
        }

        throw new PartialBranchNamesFoundException(implode(',', $foundBranches));
    }

    /**
     * @return Organization|ApiInterface
     */
    public function getOrganizationApi(): Organization
    {
        return $this->api('organization');
    }

    /**
     * @return Repo|ApiInterface
     */
    public function getRepositoryApi(): Repo
    {
        return $this->api('repo');
    }

    /**
     * @return GitData|ApiInterface
     */
    public function getGitDataApi(): GitData
    {
        return $this->api('gitData');
    }
}