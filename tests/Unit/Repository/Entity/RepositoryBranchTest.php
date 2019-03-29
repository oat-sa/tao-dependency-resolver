<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Repository\Entity;

use OAT\DependencyResolver\Repository\Entity\RepositoryBranch;
use OAT\DependencyResolver\Repository\Entity\RepositoryFile;
use OAT\DependencyResolver\Repository\GitHubRepositoryReader;
use PHPUnit\Framework\TestCase;

class RepositoryBranchTest extends TestCase
{
    /** @var RepositoryBranch */
    protected $subject;

    public function testConstructorWithDefaultValues()
    {
        $this->subject = new RepositoryBranch();

        $this->assertEquals('', $this->subject->getName());
        $this->assertEquals([], $this->subject->getFiles());
    }

    public function testConstructorAndAccessors()
    {
        $name = 'branch name';
        $file1 = $this->createConfiguredMock(RepositoryFile::class, []);
        $files = [$file1];

        $this->subject = new RepositoryBranch($name, $files);

        $this->assertEquals($name, $this->subject->getName());
        $this->assertEquals($files, $this->subject->getFiles());
    }

    public function testCreateFromArray()
    {
        $fileName = 'file name';
        $composerName = 'composer.json';
        $extensionName = 'nameOfTheExtension';
        $requires = ['extension2'];

        $branchName = 'name of the branch';
        $properties = [
            'name' => $branchName,
            'files' => [
                $fileName => [
                    'name' => $fileName,
                    'composerName' => $composerName,
                    'extensionName' => $extensionName,
                    'requires' => $requires,
                ],
            ],
        ];

        $file = new RepositoryFile($fileName, $composerName, $extensionName, $requires);

        $this->subject = RepositoryBranch::createFromArray($properties);

        $this->assertEquals($branchName, $this->subject->getName());
        $this->assertEquals([$fileName => $file], $this->subject->getFiles());
    }

    public function testGetFile()
    {
        $fileName = 'file name';
        $composerName = 'composer.json';
        $extensionName = 'nameOfTheExtension';
        $requires = ['extension2'];
        $file = new RepositoryFile($fileName, $composerName, $extensionName, $requires);

        $this->subject = new RepositoryBranch();
        $this->assertEquals(null, $this->subject->getFile($fileName));
        $this->subject->setFiles([$fileName => $file]);
        $this->assertEquals($file, $this->subject->getFile($fileName));
    }

    public function testAddFile()
    {
        $fileName = 'file name';
        $composerName = 'composer.json';
        $extensionName = 'nameOfTheExtension';
        $requires = ['extension2'];
        $file = new RepositoryFile($fileName, $composerName, $extensionName, $requires);

        $this->subject = new RepositoryBranch();
        $this->assertEquals([], $this->subject->getFiles());
        $this->subject->addFile($file);
        $this->assertEquals([$fileName => $file], $this->subject->getFiles());
    }

    public function testJsonSerialize()
    {
        $fileName = 'file name';
        $composerName = 'composer.json';
        $extensionName = 'nameOfTheExtension';
        $required = 'extension2';
        $requires = [$required];

        $branchName = 'branch name';

        $expected = '{
    "name": "' . $branchName . '",
    "files": {
        "' . $fileName . '": {
            "name": "' . $fileName . '",
            "composerName": "' . $composerName . '",
            "extensionName": "' . $extensionName . '",
            "requires": [
                "' . $required . '"
            ]
        }
    }
}';

        $file = new RepositoryFile($fileName, $composerName, $extensionName, $requires);
        $this->subject = new RepositoryBranch($branchName, [$fileName => $file]);
        $this->assertEquals($expected, json_encode($this->subject, JSON_PRETTY_PRINT));
    }

    public function testToFlatArray()
    {
        $fileName1 = GitHubRepositoryReader::MANIFEST_FILENAME;
        $composerName = 'composer.json';
        $extensionName1 = 'nameOfTheExtension';
        $required1 = 'extension1';
        $required2 = 'extension2';
        $requires1 = [$required1, $required2];
        $fileName2 = 'file name';
        $extensionName2 = 'nameOfTheExtension';
        $required3 = 'extension3';
        $required4 = 'extension4';
        $requires2 = [$required3, $required4];
        $branchName = 'branch name';

        $file1 = new RepositoryFile($fileName1, $composerName, $extensionName1, $requires1);
        $file2 = new RepositoryFile($fileName2, $composerName, $extensionName2, $requires2);
        $this->subject = new RepositoryBranch($branchName, [$fileName1 => $file1, $fileName2 => $file2]);

        $this->assertEquals([
            $branchName,
            $fileName1,
            $composerName,
            $extensionName1,
            $required1 . '|' . $required2,
            '',
            '',
            '',
            '',
        ], $this->subject->toFlatArray());
    }
}
