<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extractor;

use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Extension\ExtensionCollection;
use OAT\DependencyResolver\Factory\ExtensionFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;

class RemoteManifestExtensionsExtractor
{
    /** @var Parser */
    private $parser;

    /** @var ExtensionFactory */
    private $extensionFactory;

    public function __construct(Parser $parser, ExtensionFactory $extensionFactory)
    {
        $this->parser = $parser;
        $this->extensionFactory = $extensionFactory;
    }

    public function extractExtensionsRecursively(
        ExtensionCollection $extensionCollection,
        Extension $rootExtension,
        array $extensionToBranchMap
    ): ExtensionCollection
    {
        $manifestContent = file_get_contents($rootExtension->getRemoteManifestUrl());

        $ast = $this->parser->parse($manifestContent);

        $traverser = new NodeTraverser();

        $traverser->addVisitor(new class($extensionCollection, $this->extensionFactory, $this, $extensionToBranchMap) extends NodeVisitorAbstract
        {
            private const REQUIRES_AST_TOKEN_KEY = 'requires';

            /** @var ExtensionCollection */
            private $extensionCollection;

            /** @var ExtensionFactory */
            private $extensionFactory;

            /** @var RemoteManifestExtensionsExtractor */
            private $remoteManifestExtensionsExtractor;

            /** @var array */
            private $extensionToBranchMap;

            public function __construct(
                ExtensionCollection $extensionCollection,
                ExtensionFactory $extensionFactory,
                RemoteManifestExtensionsExtractor $remoteManifestExtensionsExtractor,
                array $extensionToBranchMap
            )
            {
                $this->extensionCollection = $extensionCollection;
                $this->extensionFactory = $extensionFactory;
                $this->remoteManifestExtensionsExtractor = $remoteManifestExtensionsExtractor;
                $this->extensionToBranchMap = $extensionToBranchMap;
            }

            public function enterNode(Node $node)
            {
                if (
                    $node instanceof ArrayItem
                    && $node->key instanceof String_
                    && $node->key->value == self::REQUIRES_AST_TOKEN_KEY
                    && $node->value instanceof Array_
                ) {
                    foreach ($node->value->items as $item) {
                        $extensionName = $item->key->value;

                        if (!$this->extensionCollection->has($extensionName)) {
                            $extension = $this->extensionFactory->create($extensionName, $this->extensionToBranchMap[$extensionName] ?? Extension::DEFAULT_BRANCH);

                            $this->extensionCollection->add($extension);

                            $this->remoteManifestExtensionsExtractor->extractExtensionsRecursively(
                                $this->extensionCollection,
                                $extension,
                                $this->extensionToBranchMap
                            );
                        }
                    }
                }
            }
        });

        $traverser->traverse($ast);

        return $extensionCollection;
    }
}
