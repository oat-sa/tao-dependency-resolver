<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Extension\Entity;

class ExtensionCollection implements \IteratorAggregate, \JsonSerializable
{
    /** @var Extension[]|array */
    private $extensions = [];

    public function add(Extension $extension): self
    {
        $this->extensions[$extension->getExtensionName()] = $extension;

        return $this;
    }

    public function has($index): bool
    {
        return array_key_exists($index, $this->extensions);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->extensions);
    }

    public function jsonSerialize()
    {
        return ['require' => $this->asArray()];
    }

    public function asArray(): array
    {
        $requires = [];
        foreach ($this->extensions as $extension) {
            $requires[$extension->getRepositoryName()] = $extension->getPrefixedBranchName();
        }

        return $requires;
    }
}
