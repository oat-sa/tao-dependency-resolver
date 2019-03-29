<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Manifest\Interfaces\ResultStoreInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ExtensionNameNodeVisitor extends NodeVisitorAbstract implements ResultStoreInterface
{
    public const NAME_AST_TOKEN_KEY = 'name';

    /** @var string */
    private $extensionName = '';

    /**
     * Clears all extensions found for a new traversal.
     */
    public function clear(): ResultStoreInterface
    {
        $this->extensionName = '';

        return $this;
    }

    /**
     * Returns all found extension names.
     */
    public function getResult(): string
    {
        return $this->extensionName;
    }

    /**
     * Stores extension names found in "requires" sub-array.
     *
     * @param Node $node
     *
     * @return int|null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof ArrayItem
            && $node->key instanceof String_
            && $node->key->value == self::NAME_AST_TOKEN_KEY
            && $node->value instanceof String_
        ) {
            $this->extensionName = $node->value->value;

            return NodeTraverser::STOP_TRAVERSAL;
        }

        return null;
    }
}
