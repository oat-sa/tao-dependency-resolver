<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;

class ExtensionNameFinder extends NodeVisitorAbstract implements FinderInterface
{
    public const NAME_AST_TOKEN_KEY = 'name';

    /** @var string */
    private $extensionName = '';

    /**
     * Clears all extensions found for a new traversal.
     * @retun $this
     */
    public function clear(): FinderInterface
    {
        $this->extensionName = '';
        return $this;
    }

    /**
     * Returns all found extension names.
     * @return string
     */
    public function getResult()
    {
        return $this->extensionName;
    }

    /**
     * Stores extension names found in "requires" sub-array.
     * @param Node $node
     * @return int|null
     */
    public function enterNode(Node $node)
    {
        if (
            $node instanceof ArrayItem
            && $node->key instanceof String_
            && $node->key->value == self::NAME_AST_TOKEN_KEY
            && $node->value instanceof String_
        ) {
            $this->extensionName = $node->value->value;
            return NodeTraverser::STOP_TRAVERSAL;
        }
    }
}