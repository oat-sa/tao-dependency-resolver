<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\FileSystem;

use OAT\DependencyResolver\FileSystem\Exception\FileAccessException;

class FileAccessor
{
    /**
     * Reads a local file contents.
     * Wraps file_get_contents into an object to ease testing.
     *
     * @param string $filePath file path and name
     *
     * @return string
     * @throws FileAccessException when an error occurs.
     */
    public function getContents(string $filePath): ?string
    {
        if (! is_readable($filePath)) {
            throw new FileAccessException('File "' . $filePath . '" does not exist or is not readable.');
        }

        return file_get_contents($filePath);
    }

    /**
     * Write a file contents to local path.
     * Wraps file_get_contents into an object to ease testing.
     *
     * @param string $filePath local file path
     * @param string $contents contents to write to file
     *
     * @return bool
     * @throws FileAccessException when an error occurs.
     */
    public function setContents(string $filePath, string $contents): bool
    {
        // Creates directory if necessary.
        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        try {
            return file_put_contents($filePath, $contents) !== false;
        } catch (\Exception $exception) {
            throw new FileAccessException($exception->getMessage());
        }
    }
}
