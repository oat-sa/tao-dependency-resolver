<?php
declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;

interface FinderInterface
{
    /**
     * Clears the result for a new traversal.
     * @retun $this
     */
    public function clear(): FinderInterface;

    /**
     * Returns found result.
     * @return mixed
     */
    public function getResult();
}
