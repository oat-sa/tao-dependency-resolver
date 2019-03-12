<?php

namespace OAT\DependencyResolver\TestHelpers;

use OAT\DependencyResolver\FileSystem\FileAccessException;
use OAT\DependencyResolver\FileSystem\FileAccessor;

class LocalFileAccessor extends FileAccessor
{
    /**
     * Wraps calls to distant urls to local test files.
     *
     * @param string $url
     *
     * @return string
     * @throws FileAccessException when an error occurs.
     */
    public function getContents(string $url): string
    {
        $localUrl = str_replace(['http://', 'https://'], __DIR__ . '/../resources/', $url);
        return parent::getContents($localUrl);
    }
}
