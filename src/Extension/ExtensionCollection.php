<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

class ExtensionCollection
{
    /** @var Extension[] */
    private $extensions;

    public function __construct(array $extensions)
    {
        foreach ($extensions as $extension) {
            $this->add($extension);
        }
    }

    public function add(Extension $extension): self
    {
        $this->extensions[$extension->getExtensionName()] = $extension;
    }

    public function all(): array
    {
        return array_values($this->extensions);
    }

    public function get(string $extensionName): ?Extension
    {
        return $this->extensions[$extensionName] ?? null;
    }

    public function has(string $extensionName): bool
    {
        return array_key_exists($extensionName, $this->extensions);
    }
}
