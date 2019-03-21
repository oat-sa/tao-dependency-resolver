<?php
declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Manifest\Interfaces\FinderInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class DependencyNamesFinder extends NodeVisitorAbstract implements FinderInterface
{
    public const REQUIRES_AST_TOKEN_KEY = 'requires';

    /** @var array */
    private $dependencyNames = [];

    /**
     * Clears all extensions found for a new traversal.
     * @retun $this
     */
    public function clear(): FinderInterface
    {
        $this->dependencyNames = [];
        return $this;
    }

    /**
     * Returns all found extension names.
     *
     * @return array
     */
    public function getResult()
    {
        return $this->dependencyNames;
    }

    /**
     * Stores extension names found in "requires" sub-array.
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof ArrayItem
            && $node->key instanceof String_
            && $node->key->value == self::REQUIRES_AST_TOKEN_KEY
            && $node->value instanceof Array_
        ) {
            foreach ($node->value->items as $item) {
                $this->dependencyNames[] = $item->key->value;
            }
        }
    }
}
