<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository;

use OAT\DependencyResolver\Repository\Entity\Repository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RepositoryMapAccessor
{
    private const REPOSITORY_MAP_PATH = 'repository.map.path';

    /** @var string */
    private $extensionMapPath;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        if (!$parameterBag->has(self::REPOSITORY_MAP_PATH) || $parameterBag->get(self::REPOSITORY_MAP_PATH) === '') {
            throw new \LogicException('Parameter "' . self::REPOSITORY_MAP_PATH . '" missing or empty.');
        }
        $this->extensionMapPath = $parameterBag->get(self::REPOSITORY_MAP_PATH);
    }

    /**
     * Reads extension map from configured file.
     *
     * @throws \LogicException when the extension map is not valid json.
     */
    public function read(): array
    {
        if (!file_exists($this->extensionMapPath)) {
            return [];
        }

        $map = file_get_contents($this->extensionMapPath);

        $decodedMap = json_decode($map, true);
        if ($decodedMap === null) {
            throw new \LogicException('Extension map is not valid Json.');
        }

        // Builds Repository objects.
        foreach ($decodedMap as &$repository) {
            $repository = Repository::createFromArray($repository);
        }

        return $decodedMap;
    }

    /**
     * Writes extensionMap to configured file.
     *
     * @param array $map Extension maps extracted from updater.
     *
     * @return bool was the content well written?
     */
    public function write(array $map): bool
    {
        // Creates directory if necessary.
        $directory = dirname($this->extensionMapPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return (bool)file_put_contents($this->extensionMapPath, json_encode($map, JSON_PRETTY_PRINT));
    }
}
