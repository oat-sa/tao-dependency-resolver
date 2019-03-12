<?php declare(strict_types=1);

namespace OAT\DependencyResolver\FileSystem;

class FileAccessor
{
    /**
     * Reads a file contents from url.
     * Wraps file_get_contents into an object to ease testing.
     *
     * @param string $url file url (local or distant)
     *
     * @return string
     * @throws FileAccessException when an error occurs.
     */
    public function getContents(string $url): ?string
    {
        try {
            return file_get_contents($url);
        } catch (\Exception $exception) {
            throw new FileAccessException($exception->getMessage());
        }
    }

    /**
     * Reads a file contents from url.
     * Wraps file_get_contents into an object to ease testing.
     *
     * @param string $url file url (local or distant)
     * @param string $contents contents to write to file
     *
     * @return bool
     * @throws FileAccessException when an error occurs.
     */
    public function setContents(string $url, string $contents): bool
    {
        try {
            return file_put_contents($url, $contents) !== false;
        } catch (\Exception $exception) {
            throw new FileAccessException($exception->getMessage());
        }
    }
}
