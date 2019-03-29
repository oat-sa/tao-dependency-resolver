<?php

namespace OAT\DependencyResolver\Repository;

use Github\Exception\ErrorException;
use Github\Exception\RuntimeException;
use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Exception\BranchNotFoundException;
use OAT\DependencyResolver\Repository\Exception\EmptyRepositoryException;
use OAT\DependencyResolver\Repository\Exception\FileNotFoundException;

class ConnectedGithubClient
{
    /** @var GithubClientProxy */
    protected $client;

    /** @var string */
    protected $organization = '';

    /** @var string */
    protected $token = '';

    /**
     * Is the client authenticated?
     *
     * @var bool
     */
    protected $authenticated = false;

    /**
     * Stores the numbers of public and private repositories.
     *
     * @var array
     */
    protected $organisationProperties = [];

    public function __construct(GithubClientProxy $client)
    {
        $this->client = $client;
    }

    /**
     * Stores token for github client.
     */
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    public function getOrganizationProperties(string $owner): array
    {
        $this->authenticateAndCheck($owner);

        return $this->organisationProperties;
    }

    /**
     * Retrieves organization's repositories.
     *
     * @param string $owner
     * @param int    $perPage Number of repositories to load per client call.
     *
     * @return array
     */
    public function getRepositoryList(string $owner, int $perPage = 100): array
    {
        $this->authenticateAndCheck($owner);

        $repositories = [];
        $page = 0;
        do {
            $page++;
            $foundRepositories = $this->client->getRepositoryList($owner, $page, $perPage);
            foreach ($foundRepositories as $repository) {
                $repositoryName = $repository['name'];
                $repositories[$owner . '/' . $repositoryName] = new Repository(
                    false, // repository not yet analyzed.
                    $owner,
                    $repositoryName,
                    $repository['private'],
                    $repository['default_branch']
                );
            }
        } while (count($foundRepositories) === $perPage);

        return $repositories;
    }

    /**
     * @throws FileNotFoundException when the file does not exist
     * @throws ErrorException when another error occurs
     */
    public function getContents(string $owner, string $repositoryName, string $branchName, string $fileName): ?string
    {
        $this->authenticateAndCheck($owner);

        // Gets a reference to the given branch of the repository if it exists.
        $branchReference = $this->getBranchReference($owner, $repositoryName, $branchName);

        try {
            return $this->client->getFileContents($owner, $repositoryName, $branchReference, $fileName);
        } catch (RuntimeException $exception) {
            if ($exception->getCode() === 404) {
                // Throws an understandable exception.
                throw new FileNotFoundException(
                    sprintf(
                        'File "%s" not found in branch "%s" of repository "%s/%s".',
                        $fileName,
                        $branchName,
                        $owner,
                        $repositoryName
                    )
                );
            }

            // Transmits any other exception.
            throw $exception;
        }
    }

    /**
     * Checks existence of a branch.
     *
     * @throws BranchNotFoundException when the branch does not exist.
     * @throws EmptyRepositoryException when the repository is empty.
     * @throws RuntimeException when another error occurs.
     */
    public function getBranchReference(string $owner, string $repositoryName, string $branchName)
    {
        $this->authenticateAndCheck($owner);

        // Gets a reference to the given branch of the repository if it exists.
        try {
            $reference = $this->client->getReference($owner, $repositoryName, $branchName);
        } catch (RuntimeException $exception) {
            switch ($exception->getCode()) {
                case 404:
                    // Throws an understandable exception.
                    throw new BranchNotFoundException(
                        sprintf('Unable to retrieve reference to "%s/%s/%s".', $owner, $repositoryName, $branchName)
                    );

                case 409:
                    // Throws an understandable exception.
                    throw new EmptyRepositoryException($repositoryName);
            }

            // Transmits any other exception.
            throw $exception;
        }

        // More than one reference returned: branchName was not found but other branches containing the name where.
        // We just ignore them.
        if (! isset($reference['ref'])) {
            throw new BranchNotFoundException(
                sprintf(
                    'Unable to retrieve reference to "%s/%s/%s".',
                    $owner,
                    $repositoryName,
                    $branchName
                )
            );
        }

        // One reference found: returns only the reference.
        return $reference['ref'];
    }

    /**
     * Always make authenticated requests to avoid limitations.
     * Sets organisation's number of private and public repositories.
     *
     * @todo: add some cache
     * @see https://github.com/KnpLabs/php-github-api#cache-usage
     */
    protected function authenticateAndCheck(string $owner)
    {
        if ($this->authenticated) {
            return;
        }

        $this->client->authenticate($this->token, null, $this->client::AUTH_HTTP_TOKEN);

        try {
            // This is performed just to ensure we have a proper authentication.
            $this->organisationProperties = $this->client->getOrganizationProperties($owner);
        } catch (\Exception $exception) {
            throw new \RuntimeException(
                'A error occurred when trying to authenticate to Github API: ' . $exception->getMessage()
            );
        }

        $this->authenticated = true;
    }
}
