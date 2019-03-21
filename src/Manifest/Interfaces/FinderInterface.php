<?php
declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest\Interfaces;

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
