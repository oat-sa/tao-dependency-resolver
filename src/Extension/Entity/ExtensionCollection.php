<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Extension\Entity;

class ExtensionCollection extends \ArrayObject implements \JsonSerializable
{
    /** @var Extension[] */
    private $extensions = [];

    public function add(Extension $extension): self
    {
        $this->offsetSet($extension->getExtensionName(), $extension);

        return $this;
    }

    public function offsetSet($index, $newval)
    {
        if (!$newval instanceof Extension) {
            throw new \TypeError('Extension provided is not an instance of ' . Extension::class . '.');
        }

        $this->extensions[$index] = $newval;
    }

    public function offsetGet($index): ?Extension
    {
        if (!$this->offsetExists($index)) {
            return null;
        }

        return $this->extensions[$index];
    }

    public function offsetExists($index): bool
    {
        return array_key_exists($index, $this->extensions);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->extensions);
    }

    public function jsonSerialize()
    {
        $requires = [];
        foreach ($this->extensions as $extension) {
            $requires[$extension->getRepositoryName()] = $extension->getPrefixedBranchName();
        }

        return ['require' => $requires];
    }
}
