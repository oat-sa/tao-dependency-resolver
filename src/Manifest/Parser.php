<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Extension\Extension;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor;
use PhpParser\Parser as PhpParser;

class Parser
{
    /** @var PhpParser */
    private $phpParser;

    /** @var ExtensionNameFinder */
    private $extensionNameFinder;

    /** @var DependencyNamesFinder */
    private $dependencyNamesFinder;

    /** @var NodeTraverserInterface */
    private $nodeTraverser;

    /**
     * Parser constructor.
     * @param PhpParser $phpParser
     * @param ExtensionNameFinder $extensionNameFinder
     * @param DependencyNamesFinder $dependencyNamesFinder
     * @param NodeTraverserInterface $traverser
     */
    public function __construct(
        PhpParser $phpParser,
        ExtensionNameFinder $extensionNameFinder,
        DependencyNamesFinder $dependencyNamesFinder,
        NodeTraverserInterface $traverser
    )
    {
        $this->phpParser = $phpParser;
        $this->extensionNameFinder = $extensionNameFinder;
        $this->dependencyNamesFinder = $dependencyNamesFinder;
        $this->nodeTraverser = $traverser;
    }

    /**
     * Retrieves extension name from manifest contents.
     * @param string $manifestContents
     *
     * @return string
     */
    public function getExtensionName(string $manifestContents)
    {
        return $this->parse($manifestContents, $this->extensionNameFinder);
    }

    /**
     * Finds required dependency names in manifest contents.
     * @param string|null $manifestContents
     *
     * @return array
     */
    public function getDependencyNames(string $manifestContents)
    {
        return $this->parse($manifestContents, $this->dependencyNamesFinder);
    }

    /**
     * Parses the given manifest contents and populates the extensionNames and dependencyNames properties.
     * @param string $manifestContents
     * @param NodeVisitor|FinderInterface $finder
     * @return mixed
     */
    private function parse(string $manifestContents, NodeVisitor $finder)
    {
        // Parses manifest php ast.
        $ast = $this->phpParser->parse($manifestContents);

        $finder->clear();

        $this->nodeTraverser->addVisitor($finder);
        $this->nodeTraverser->traverse($ast);
        $this->nodeTraverser->removeVisitor($finder);

        return $finder->getResult();
    }
}
