<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository\Entity;

class RepositoryCollection implements \IteratorAggregate, \JsonSerializable
{
    /** @var Repository[] */
    private $repositories = [];

    public function add(Repository $repository): self
    {
        $this->repositories[$repository->getName()] = $repository;

        return $this;
    }

    public function has($index): bool
    {
        return array_key_exists($index, $this->repositories);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->repositories);
    }

    public function jsonSerialize()
    {
        return ['repositories' => $this->asArray()];
    }

    /**
     * @return Repository[]
     */
    public function asArray(): array
    {
        $repositories = [];

        foreach ($this->repositories as $repository) {
            $organization = $repository->getOwner();
            $repoName = $repository->getName();

            $repositories[] = [
                'type' => 'vcs',
                'url' => "https://github.com/${organization}/${repoName}",
                'no-api' => true
            ];
        }

        return $repositories;
    }
}
