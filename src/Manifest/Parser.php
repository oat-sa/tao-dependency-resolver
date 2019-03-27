<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Manifest\Interfaces\FinderInterface;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor;
use PhpParser\Parser as PhpParser;

class Parser
{
    /** @var PhpParser */
    private $phpParser;

    /** @var FinderInterface */
    private $extensionNameFinder;

    /** @var FinderInterface */
    private $dependencyNamesFinder;

    /** @var NodeTraverserInterface */
    private $nodeTraverser;

    public function __construct(
        PhpParser $phpParser,
        FinderInterface $extensionNameFinder,
        FinderInterface $dependencyNamesFinder,
        NodeTraverserInterface $traverser
    ) {
        $this->phpParser = $phpParser;
        $this->extensionNameFinder = $extensionNameFinder;
        $this->dependencyNamesFinder = $dependencyNamesFinder;
        $this->nodeTraverser = $traverser;
    }

    /**
     * Retrieves extension name from manifest contents.
     */
    public function getExtensionName(string $manifestContents)
    {
        return $this->parse($manifestContents, $this->extensionNameFinder);
    }

    /**
     * Finds required dependency names in manifest contents.
     */
    public function getDependencyNames(string $manifestContents)
    {
        return $this->parse($manifestContents, $this->dependencyNamesFinder);
    }

    /**
     * Parses the given manifest contents and populates the extensionNames and dependencyNames properties.
     *
     * @param string                      $manifestContents
     * @param NodeVisitor|FinderInterface $finder
     *
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
