<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Repository;

use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use OAT\DependencyResolver\FileSystem\Exception\FileAccessException;
use OAT\DependencyResolver\FileSystem\FileAccessor;
use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Entity\RepositoryBranch;
use OAT\DependencyResolver\Repository\Entity\RepositoryFile;
use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RepositoryMapAccessorTest extends TestCase
{
    private const REPOSITORY_MAP_PATH = 'repository.map.path';
    const VALID_FILENAME = 'validFileName';
    const INVALID_FILENAME = 'invalidFileName';
    const JSON_REPO_CONTENTS = '{
        "oat-sa\/generis":{
            "owner": "oat-sa",
            "name": "generis",
            "private": false,
            "defaultBranch": "master",
            "extensionName": "generis",
            "composerName": "oat-sa\/generis",
            "onPackagist": true,
            "branches": {
                "develop": {
                    "name": "develop",
                    "files": {
                        "manifest.php": {
                            "name": "manifest.php",
                            "composerName": "",
                            "extensionName": "generis",
                            "requires": []
                        },
                        "composer.json": {
                            "name": "composer.json",
                            "composerName": "oat-sa\/generis",
                            "extensionName": "generis",
                            "requires": [
                                "oat-sa\/oatbox-extension-installer",
                                "oat-sa\/lib-generis-search"
                            ]
                        }
                    }
                },
                "master": {
                    "name": "master",
                    "files": {
                        "manifest.php": {
                            "name": "manifest.php",
                            "composerName": "",
                            "extensionName": "generis",
                            "requires": []
                        },
                        "composer.json": {
                            "name": "composer.json",
                            "composerName": "oat-sa\/generis",
                            "extensionName": "generis",
                            "requires": [
                                "oat-sa\/oatbox-extension-installer",
                                "oat-sa\/lib-generis-search"
                            ]
                        }
                    }
                }
            }
        }
    }';
    const JSON_CONTENTS = '{' . "\n" . '    "some": "json"' . "\n" . '}';
    const ARRAY_CONTENTS = ['some' => 'json'];

    /** @var RepositoryMapAccessor */
    private $subject;

    /** @var FileAccessor|MockObject */
    private $fileAccessor;

    public function testConstructorWithNoExtensionMapPathThrowsException()
    {
        $parameterBag = new ParameterBag([]);
        $this->fileAccessor = $this->createMock(FileAccessor::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Parameter "' . self::REPOSITORY_MAP_PATH . '" missing or empty.'
        );

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);
    }

    public function testConstructorWithEmptyExtensionMapPathThrowsException()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => '']);
        $this->fileAccessor = $this->createMock(FileAccessor::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Parameter "' . self::REPOSITORY_MAP_PATH . '" missing or empty.'
        );

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);
    }

    public function testFindExtensionNameWithNotExistingMapFileThrowsException()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::INVALID_FILENAME]);
        $this->fileAccessor = $this->createMock(FileAccessor::class);
        $this->fileAccessor->method('getContents')->willThrowException(
            new FileAccessException('File "' . self::INVALID_FILENAME . '" does not exist or is not readable.')
        );

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Extension map does not exist.');
        $this->subject->findExtensionName('');
    }

    public function testFindExtensionNameWithInvalidJsonThrowsException()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createConfiguredMock(FileAccessor::class, ['getContents' => '{']);

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Extension map is not valid Json.');
        $this->subject->findExtensionName('');
    }

    public function testFindExtensionNameWithNotMappedReporitoryThrowsException()
    {
        $repositoryName = 'name of the repository';

        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createConfiguredMock(
            FileAccessor::class,
            ['getContents' => self::JSON_REPO_CONTENTS]
        );

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->expectException(NotMappedException::class);
        $this->expectExceptionMessage('Repository "' . $repositoryName . '" not found in map.');
        $this->subject->findExtensionName($repositoryName);
    }

    public function testFindExtensionNameWithMappedReporitoryReturnsExtensionName()
    {
        $repositoryName = 'oat-sa/generis';

        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createConfiguredMock(
            FileAccessor::class,
            ['getContents' => self::JSON_REPO_CONTENTS]
        );

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->assertEquals('generis', $this->subject->findExtensionName($repositoryName));
    }

    public function testReadWithNotExistingMapFileThrowsException()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::INVALID_FILENAME]);
        $this->fileAccessor = $this->createMock(FileAccessor::class);
        $this->fileAccessor->method('getContents')->willThrowException(
            new FileAccessException('File "' . self::INVALID_FILENAME . '" does not exist or is not readable.')
        );

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Extension map does not exist.');
        $this->subject->read();
    }

    public function testReadWithInvalidJsonThrowsException()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createConfiguredMock(FileAccessor::class, ['getContents' => '{']);

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Extension map is not valid Json.');
        $this->subject->read();
    }

    public function testReadWithValidExtensionMapPathReturnsArray()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createConfiguredMock(
            FileAccessor::class,
            ['getContents' => self::JSON_REPO_CONTENTS]
        );

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $expected = [
            'oat-sa/generis' => new Repository(
                'oat-sa',
                'generis',
                false,
                'master',
                'generis',
                'oat-sa/generis',
                true,
                [
                    'develop' => new RepositoryBranch(
                        'develop',
                        [
                            'manifest.php' => new RepositoryFile('manifest.php', '', 'generis', []),
                            'composer.json' => new RepositoryFile(
                                'composer.json',
                                'oat-sa/generis',
                                'generis',
                                ['oat-sa/oatbox-extension-installer', 'oat-sa/lib-generis-search']
                            ),
                        ]
                    ),
                    'master' => new RepositoryBranch(
                        'master',
                        [
                            'manifest.php' => new RepositoryFile('manifest.php', '', 'generis', []),
                            'composer.json' => new RepositoryFile(
                                'composer.json',
                                'oat-sa/generis',
                                'generis',
                                ['oat-sa/oatbox-extension-installer', 'oat-sa/lib-generis-search']
                            ),
                        ]
                    ),
                ]
            ),
        ];

        $this->assertEquals($expected, $this->subject->read());
    }

    public function testWriteWithValidExtensionMapPathReturnsTrue()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createMock(FileAccessor::class);
        $this->fileAccessor
            ->expects($this->once())
            ->method('setContents')
            ->with(self::VALID_FILENAME, self::JSON_CONTENTS)
            ->willReturn(true);

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->assertTrue($this->subject->write(self::ARRAY_CONTENTS));
    }
}
