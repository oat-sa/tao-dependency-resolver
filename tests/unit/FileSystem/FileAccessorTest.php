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
        $filePath = __DIR__ . '/../../resources/raw.githubusercontent.com/oat-sa/generis/develop/manifest.php';
        $contents = file_get_contents($filePath);
        $this->assertEquals($contents, $this->subject->getContents($filePath));
    }

    public function testGetContents_WithInvalidLocalFilename_ThrowsException()
    {
        $filePath = __DIR__ . '/../../resources/raw.githubusercontent.com/oat-sa/unknown/develop/manifest.php';
        $this->expectException(FileAccessException::class);
        $this->subject->getContents($filePath);
    }

    public function testGetContents_WithInvalidDistantFilename_ThrowsException()
    {
        $url = 'http://example.com/not-existing-file.txt';
        $this->expectException(FileAccessException::class);
        $this->assertNull($this->subject->getContents($url));
    }

    public function testSetContents_WithValidFilename_CreatesNewFileAndReturnsTrue()
    {
        $tmpDir = __DIR__ . '/../../../resources/not-existing-directory/';
        $filePath = $tmpDir . 'php-unit-test.txt';
        $contents = 'foo';
        $this->assertTrue($this->subject->setContents($filePath, $contents));
        $this->assertEquals($contents, file_get_contents($filePath));
        unlink($filePath);
        rmdir($tmpDir);
    }

    public function testSetContents_WithValidFilenameAndEmptyContents_CreatesNewEmptyFileAndReturnsTrue()
    {
        $tmpDir = sys_get_temp_dir();
        $filePath = tempnam($tmpDir, 'php-unit-test');
        $contents = '';
        $this->assertTrue($this->subject->setContents($filePath, $contents));
        unlink($filePath);
    }

    public function testSetContents_WithNonAccessibleFile_ThrowsException()
    {
        // Makes a read-only file.
        $tmpDir = sys_get_temp_dir();
        $filePath = tempnam($tmpDir, 'php-unit-test');
        file_put_contents($filePath, 'foo');
        chmod($filePath, 0);

        $this->expectException(FileAccessException::class);
        $this->assertFalse($this->subject->setContents($filePath, 'bar'));

        chmod($filePath, 0777);
        unlink($filePath);
    }
}
