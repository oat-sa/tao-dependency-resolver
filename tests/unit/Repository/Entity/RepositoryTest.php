<?php

namespace OAT\DependencyResolver\Repository\Entity;

use OAT\DependencyResolver\Repository\GitHubRepositoryReader;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /** @var Repository */
    protected $subject;

    public function testConstructorWithDefaultValues()
    {
        $this->subject = new Repository();

        $this->assertEquals('', $this->subject->getOwner());
        $this->assertEquals('', $this->subject->getName());
        $this->assertEquals(false, $this->subject->isPrivate());
        $this->assertEquals('', $this->subject->getDefaultBranch());
        $this->assertEquals('', $this->subject->getExtensionName());
        $this->assertEquals('', $this->subject->getComposerName());
        $this->assertEquals(false, $this->subject->isOnPackagist());
        $this->assertEquals([], $this->subject->getBranches());
    }

    public function testConstructorAndAccessors()
    {
        $owner = 'owner name';
        $repositoryName = 'repo name';
        $private = true;
        $defaultBranch = 'develop';
        $extensionName = 'extension name';
        $composerName = 'oat-sa/composer-name';
        $onPackagist = true;

        $branch1 = $this->createConfiguredMock(RepositoryBranch::class, []);
        $branches = [$branch1];

        $this->subject = new Repository(
            $owner,
            $repositoryName,
            $private,
            $defaultBranch,
            $extensionName,
            $composerName,
            $onPackagist,
            $branches
        );

        $this->assertEquals($owner, $this->subject->getOwner());
        $this->assertEquals($repositoryName, $this->subject->getName());
        $this->assertEquals($private, $this->subject->isPrivate());
        $this->assertEquals($defaultBranch, $this->subject->getDefaultBranch());
        $this->assertEquals($extensionName, $this->subject->getExtensionName());
        $this->assertEquals($composerName, $this->subject->getComposerName());
        $this->assertEquals($onPackagist, $this->subject->isOnPackagist());
        $this->assertEquals($branches, $this->subject->getBranches());
    }

    public function testConstructFromArray()
    {
        $fileName = 'file name';
        $fileComposerName = 'composer.json';
        $fileExtensionName = 'nameOfTheExtension';
        $requires = 'extension';

        $owner = 'owner name';
        $repositoryName = 'name of the repo';
        $private = true;
        $defaultBranch = 'develop';
        $extensionName = 'extension name';
        $composerName = 'oat-sa/composer-name';
        $onPackagist = true;
        $branchName = 'branch name';

        $properties = [
            'owner' => $owner,
            'name' => $repositoryName,
            'private' => $private,
            'defaultBranch' => $defaultBranch,
            'extensionName' => $extensionName,
            'composerName' => $composerName,
            'onPackagist' => $onPackagist,
            'branches' => [
                $branchName => [
                    'name' => $branchName,
                    'files' => [
                        $fileName => [
                            'name' => $fileName,
                            'composerName' => $fileComposerName,
                            'extensionName' => $fileExtensionName,
                            'requires' => [
                                $requires,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $file = new RepositoryFile($fileName, $fileComposerName, $fileExtensionName, [$requires]);
        $branch = new RepositoryBranch($branchName, [$fileName => $file]);

        $this->subject = new Repository();
        $this->subject->constructFromArray($properties);

        $this->assertEquals($owner, $this->subject->getOwner());
        $this->assertEquals($repositoryName, $this->subject->getName());
        $this->assertEquals($private, $this->subject->isPrivate());
        $this->assertEquals($defaultBranch, $this->subject->getDefaultBranch());
        $this->assertEquals($extensionName, $this->subject->getExtensionName());
        $this->assertEquals($composerName, $this->subject->getComposerName());
        $this->assertEquals($onPackagist, $this->subject->isOnPackagist());
        $this->assertEquals([$branchName => $branch], $this->subject->getBranches());
    }

    public function testGetFile()
    {
        $branchName = 'branch name';
        $branch = new RepositoryBranch($branchName);

        $this->subject = new Repository();
        $this->assertEquals(null, $this->subject->getBranch($branchName));
        $this->subject->setBranches([$branchName => $branch]);
        $this->assertEquals($branch, $this->subject->getBranch($branchName));
    }

    public function testAddBranch()
    {
        $branchName = 'branch name';
        $branch = new RepositoryBranch($branchName);

        $this->subject = new Repository();
        $this->assertEquals([], $this->subject->getBranches());
        $this->subject->addBranch($branch);
        $this->assertEquals([$branchName => $branch], $this->subject->getBranches());
    }

    public function testJsonSerialize()
    {
        $fileName = 'file name';
        $fileComposerName = 'composer.json';
        $fileExtensionName = 'nameOfTheExtension';
        $requires = 'extension';

        $owner = 'owner name';
        $repositoryName = 'name of the repo';
        $private = true;
        $defaultBranch = 'develop';
        $extensionName = 'extension name';
        $composerName = 'oat-sa/composer-name';
        $onPackagist = true;
        $branchName = 'branch name';

        $expected = '{
    "owner": "' . $owner . '",
    "name": "' . $repositoryName . '",
    "private": ' . ($private ? 'true' : 'false') . ',
    "defaultBranch": "' . $defaultBranch . '",
    "extensionName": "' . $extensionName . '",
    "composerName": "' . str_replace('/', '\/', $composerName) . '",
    "onPackagist": ' . ($onPackagist ? 'true' : 'false') . ',
    "branches": {
        "' . $branchName . '": {
            "name": "' . $branchName . '",
            "files": {
                "' . $fileName . '": {
                    "name": "' . $fileName . '",
                    "composerName": "' . $fileComposerName . '",
                    "extensionName": "' . $fileExtensionName . '",
                    "requires": [
                        "' . $requires . '"
                    ]
                }
            }
        }
    }
}';

        $file = new RepositoryFile($fileName, $fileComposerName, $fileExtensionName, [$requires]);
        $branch = new RepositoryBranch($branchName, [$fileName => $file]);
        $this->subject = new Repository(
            $owner,
            $repositoryName,
            $private,
            $defaultBranch,
            $extensionName,
            $composerName,
            $onPackagist,
            [$branchName => $branch]
        );

        $this->assertEquals($expected, json_encode($this->subject, JSON_PRETTY_PRINT));
    }

    public function testToCsv()
    {
        $fileName1 = GitHubRepositoryReader::MANIFEST_FILENAME;
        $fileComposerName = 'composer.json';
        $extensionName1 = 'nameOfTheExtension';
        $required1 = 'extension1';
        $required2 = 'extension2';
        $requires1 = [$required1, $required2];
        $fileName2 = 'file name';
        $extensionName2 = 'nameOfTheExtension';
        $required3 = 'extension3';
        $required4 = 'extension4';
        $requires2 = [$required3, $required4];
        $branchName = 'develop';

        $owner = 'owner name';
        $repositoryName = 'name of the repo';
        $private = true;
        $defaultBranch = 'develop';
        $extensionName = 'extension name';
        $composerName = 'oat-sa/composer-name';
        $onPackagist = true;

        $file1 = new RepositoryFile($fileName1, $fileComposerName, $extensionName1, $requires1);
        $file2 = new RepositoryFile($fileName2, $fileComposerName, $extensionName2, $requires2);
        $branch = new RepositoryBranch($branchName, [$fileName1 => $file1, $fileName2 => $file2]);
        $this->subject = new Repository(
            $owner,
            $repositoryName,
            $private,
            $defaultBranch,
            $extensionName,
            $composerName,
            $onPackagist,
            [$branchName => $branch]
        );

        $this->assertEquals([
            $repositoryName,
            $extensionName,
            $composerName,
            $private ? 'private' : 'public',
            $onPackagist ? 'yes' : 'no',
            $defaultBranch,
            $branchName,
            $fileName1,
            $fileComposerName,
            $extensionName1,
            $required1 . '|' . $required2,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ], $this->subject->toCsv());
    }
}
