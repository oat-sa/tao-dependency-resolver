<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest\Interfaces;

interface ResultStoreInterface
{
    /**
     * Clears the result for a new traversal.
     *
     * @retun $this
     */
    public function clear(): ResultStoreInterface;

    /**
     * Returns found result.
     *
     * @return mixed
     */
    public function getResult();
}
