<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Manifest\Interfaces\ResultStoreInterface;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor;
use PhpParser\Parser as PhpParser;

class Parser
{
    /** @var PhpParser */
    private $phpParser;

    /** @var ResultStoreInterface */
    private $extensionNameNodeVisitor;

    /** @var ResultStoreInterface */
    private $dependencyNamesNodeVisitor;

    /** @var NodeTraverserInterface */
    private $nodeTraverser;

    public function __construct(
        PhpParser $phpParser,
        ResultStoreInterface $extensionNameNodeVisitor,
        ResultStoreInterface $dependencyNamesNodeVisitor,
        NodeTraverserInterface $traverser
    ) {
        $this->phpParser = $phpParser;
        $this->extensionNameNodeVisitor = $extensionNameNodeVisitor;
        $this->dependencyNamesNodeVisitor = $dependencyNamesNodeVisitor;
        $this->nodeTraverser = $traverser;
    }

    // Retrieves extension name from manifest contents.
    public function getExtensionName(string $manifestContents)
    {
        return $this->parse($manifestContents, $this->extensionNameNodeVisitor);
    }

    // Finds required dependency names in manifest contents.
    public function getDependencyNames(string $manifestContents)
    {
        return $this->parse($manifestContents, $this->dependencyNamesNodeVisitor);
    }

    /**
     * Parses the given manifest contents and populates the extensionNames and dependencyNames properties.
     *
     * @param string                           $manifestContents
     * @param NodeVisitor|ResultStoreInterface $nodeVisitor
     *
     * @return mixed
     */
    private function parse(string $manifestContents, NodeVisitor $nodeVisitor)
    {
        // Parses manifest php ast.
        $ast = $this->phpParser->parse($manifestContents);

        $nodeVisitor->clear();

        $this->nodeTraverser->addVisitor($nodeVisitor);
        $this->nodeTraverser->traverse($ast);
        $this->nodeTraverser->removeVisitor($nodeVisitor);

        return $nodeVisitor->getResult();
    }
}
