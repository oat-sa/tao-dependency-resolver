<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

class ExtensionCollection extends \ArrayObject
{
    /** @var Extension[] */
    private $extensions = [];

    /**
     * @param Extension $extension
     * @return $this
     */
    public function add(Extension $extension)
    {
        $this->offsetSet($extension->getExtensionName(), $extension);

        return $this;
    }

    /**
     * @param mixed $index
     * @param mixed $newval
     */
    public function offsetSet($index, $newval)
    {
        if (!$newval instanceof Extension) {
            throw new \TypeError('Extension provided is not an instance of ' . Extension::class . '.');
        }

        $this->extensions[$index] = $newval;
    }

    /**
     * @param string $index
     * @return Extension|null
     */
    public function offsetGet($index): ?Extension
    {
        if (!$this->offsetExists($index)) {
            return null;
        }

        return $this->extensions[$index];
    }

    /**
     * @param mixed $index
     * @return bool
     */
    public function offsetExists($index): bool
    {
        return array_key_exists($index, $this->extensions);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->extensions);
    }

    /**
     * Generates a composer.json contents from the extension collection
     *
     * @return false|string
     */
    public function generateComposerJson()
    {
        $requires = [];
        foreach ($this->extensions as $extension) {
            $requires[$extension->getRepositoryName()] = $extension->getPrefixedBranchName();
        }
        return json_encode(['require' => $requires], JSON_PRETTY_PRINT);
    }
}
