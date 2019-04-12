<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Repository;

use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Entity\RepositoryBranch;
use OAT\DependencyResolver\Repository\Entity\RepositoryFile;
use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RepositoryMapAccessorTest extends TestCase
{
    private const REPOSITORY_MAP_PATH = 'repository.map.path';
    private const VALID_FILENAME = 'validFileName';
    private const INVALID_FILENAME = 'invalidFileName';
    private const JSON_REPO_CONTENTS = '{
        "oat-sa\/generis":{
            "analyzed": true,
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
    private const JSON_CONTENTS = '{' . "\n" . '    "some": "json"' . "\n" . '}';
    private const ARRAY_CONTENTS = ['some' => 'json'];
    private const VFS_ROOT = 'root/';

    /** @var RepositoryMapAccessor */
    private $subject;

    /** @var vfsStreamDirectory */
    private $testDir;

    public function setUp()
    {
        $this->testDir = vfsStream::setup(self::VFS_ROOT);
    }

    public function testConstructorWithNoExtensionMapPathThrowsException()
    {
        $parameterBag = new ParameterBag([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Parameter "' . self::REPOSITORY_MAP_PATH . '" missing or empty.'
        );

        $this->subject = new RepositoryMapAccessor($parameterBag);
    }

    public function testConstructorWithEmptyExtensionMapPathThrowsException()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => '']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Parameter "' . self::REPOSITORY_MAP_PATH . '" missing or empty.'
        );

        $this->subject = new RepositoryMapAccessor($parameterBag);
    }

    public function testReadWithNotExistingMapFileThrowsException()
    {
        $parameterBag = new ParameterBag([self::REPOSITORY_MAP_PATH => self::INVALID_FILENAME]);

        $this->subject = new RepositoryMapAccessor($parameterBag);

        $this->assertEquals([], $this->subject->read());
    }

    public function testReadWithInvalidJsonThrowsException()
    {
        vfsStream::create([self::VALID_FILENAME => '{'], $this->testDir);
        $parameterBag = new ParameterBag(
            [self::REPOSITORY_MAP_PATH => vfsStream::url(self::VFS_ROOT . self::VALID_FILENAME)]
        );

        $this->subject = new RepositoryMapAccessor($parameterBag);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Extension map is not valid Json.');
        $this->subject->read();
    }

    public function testReadWithValidExtensionMapPathReturnsArray()
    {
        vfsStream::create([self::VALID_FILENAME => self::JSON_REPO_CONTENTS], $this->testDir);
        $parameterBag = new ParameterBag(
            [self::REPOSITORY_MAP_PATH => vfsStream::url(self::VFS_ROOT . self::VALID_FILENAME)]
        );

        $this->subject = new RepositoryMapAccessor($parameterBag);

        $expected = [
            'oat-sa/generis' => new Repository(
                true,
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
        $path = 'path/to/';

        $parameterBag = new ParameterBag(
            [self::REPOSITORY_MAP_PATH => vfsStream::url(self::VFS_ROOT . $path . self::VALID_FILENAME)]
        );

        $this->subject = new RepositoryMapAccessor($parameterBag);

        $this->assertTrue($this->subject->write(self::ARRAY_CONTENTS));
        $this->assertTrue($this->testDir->hasChild($path . self::VALID_FILENAME));
        /** @var vfsStreamFile $file */
        $file = $this->testDir->getChild($path . self::VALID_FILENAME);
        $this->assertEquals(self::JSON_CONTENTS, $file->getContent());
    }
}
