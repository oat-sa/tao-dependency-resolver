<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Factory;

use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExtensionMapFactory
{
    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function create(): array
    {
        if (!$this->parameterBag->has('extension.map.path')) {
            throw new LogicException(sprintf('Parameter "extension.map.path" cannot be empty.'));
        }

        return json_decode(file_get_contents($this->parameterBag->get('extension.map.path')), true);
    }
}
