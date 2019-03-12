<?php declare(strict_types=1);

namespace OAT\DependencyResolver\FileSystem;

use PHPUnit\Framework\TestCase;

class FileAccessorTest extends TestCase
{
    const VALID_FILENAME = 'validFileName';
    const INVALID_FILENAME = 'invalidFileName';
    const EXCEPTION_FILENAME = 'exceptionFileName';

    /** @var FileAccessor */
    private $subject;

    public function setUp()
    {
        $this->subject = new FileAccessor();
    }

    public function testGetContents_WithValidFilename_ReturnsContents()
    {
        $url = __DIR__ . '/../../resources/raw.githubusercontent.com/oat-sa/generis/develop/manifest.php';
        $contents = file_get_contents($url);
        $this->assertEquals($contents, $this->subject->getContents($url));
    }

    public function testGetContents_WithInvalidLocalFilename_ThrowsException()
    {
        $url = __DIR__ . '/../../resources/raw.githubusercontent.com/oat-sa/unknown/develop/manifest.php';
        $this->expectException(FileAccessException::class);
        $this->subject->getContents($url);
    }

    public function testGetContents_WithInvalidDistantFilename_ThrowsException()
    {
        $url = 'http://example.com/not-existing-file.txt';
        $this->expectException(FileAccessException::class);
        $this->assertNull($this->subject->getContents($url));
    }

    public function testSetContents_WithValidFilename_CreatesNewFileAndReturnsTrue()
    {
        $tmpDir = sys_get_temp_dir();
        $url = tempnam($tmpDir, 'php-unit-test');
        $contents = 'foo';
        $this->assertTrue($this->subject->setContents($url, $contents));
        $this->assertEquals($contents, file_get_contents($url));
        unlink($url);
    }

    public function testSetContents_WithValidFilenameAndEmptyContents_CreatesNewEmptyFileAndReturnsTrue()
    {
        $tmpDir = sys_get_temp_dir();
        $url = tempnam($tmpDir, 'php-unit-test');
        $contents = '';
        $this->assertTrue($this->subject->setContents($url, $contents));
        unlink($url);
    }

    public function testSetContents_WithNonAccessibleFile_ThrowsException()
    {
        // Makes a read-only file.
        $tmpDir = sys_get_temp_dir();
        $url = tempnam($tmpDir, 'php-unit-test');
        file_put_contents($url, 'foo');
        chmod($url, 0);

        $this->expectException(FileAccessException::class);
        $this->assertFalse($this->subject->setContents($url, 'bar'));

        chmod($url, 0777);
        unlink($url);
    }
}
