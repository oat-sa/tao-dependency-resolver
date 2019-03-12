<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Repository;

use OAT\DependencyResolver\FileSystem\FileAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RepositoryMapAccessorTest extends TestCase
{
    const VALID_FILENAME = 'validFileName';
    const JSON_CONTENTS = '{
        "oat-sa\/generis":{
            "owner": "oat-sa",
            "name": "generis",
            "private": false,
            "defaultBranch": "master",
            "extensionName": "generis",
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
    const ARRAY_CONTENTS = ['some' => 'json'];

    /** @var RepositoryMapAccessor */
    private $subject;

    /** @var FileAccessor|MockObject */
    private $fileAccessor;

    public function testConstructor_WithNoExtensionMapPath_ThrowsException()
    {
        $parameterBag = new ParameterBag([]);
        $this->fileAccessor = $this->createMock(FileAccessor::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Parameter "' . RepositoryMapAccessor::REPOSITORY_MAP_PATH . '" not missing or empty.');

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);
    }

    public function testRead_WithEmptyExtensionMapPath_ThrowsException()
    {
        $parameterBag = new ParameterBag([RepositoryMapAccessor::REPOSITORY_MAP_PATH => '']);
        $this->fileAccessor = $this->createMock(FileAccessor::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Parameter "' . RepositoryMapAccessor::REPOSITORY_MAP_PATH . '" not missing or empty.');

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);
    }

    public function testRead_WithEmptyMapPath_ThrowsException()
    {
        $parameterBag = new ParameterBag([RepositoryMapAccessor::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createConfiguredMock(FileAccessor::class, ['getContents' => '']);

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Extension map is not valid Json.');
        $this->subject->read();
    }

    public function testRead_WithInvalidJson_ThrowsException()
    {
        $parameterBag = new ParameterBag([RepositoryMapAccessor::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createConfiguredMock(FileAccessor::class, ['getContents' => '{']);

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Extension map is not valid Json.');
        $this->subject->read();
    }

    public function testRead_WithValidExtensionMapPath_ReturnsArray()
    {
        $parameterBag = new ParameterBag([RepositoryMapAccessor::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createConfiguredMock(FileAccessor::class, ['getContents' => self::JSON_CONTENTS]);

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $expected = [
            'oat-sa/generis' => new Repository('oat-sa', 'generis', false, 'master', 'generis', true,
                [
                    'develop' => new RepositoryBranch(
                        'develop',
                        [
                            'manifest.php' => new RepositoryFile('manifest.php', '', 'generis', []),
                            'composer.json' => new RepositoryFile('composer.json', 'oat-sa/generis', 'generis', ['oat-sa/oatbox-extension-installer', 'oat-sa/lib-generis-search']),
                        ]
                    ),
                    'master' => new RepositoryBranch(
                        'master',
                        [
                            'manifest.php' => new RepositoryFile('manifest.php', '', 'generis', []),
                            'composer.json' => new RepositoryFile('composer.json', 'oat-sa/generis', 'generis', ['oat-sa/oatbox-extension-installer', 'oat-sa/lib-generis-search']),
                        ]
                    ),
                ]
            )
        ];

        $this->assertEquals($expected, $this->subject->read());
    }

    public function testWrite_WithValidExtensionMapPath_ReturnsArray()
    {
        $this->markTestIncomplete();
        $parameterBag = new ParameterBag([RepositoryMapAccessor::REPOSITORY_MAP_PATH => self::VALID_FILENAME]);
        $this->fileAccessor = $this->createMock(FileAccessor::class);
        $this->fileAccessor->expects($this->once())->method('setContents')->with(self::VALID_FILENAME, self::JSON_CONTENTS);

        $this->subject = new RepositoryMapAccessor($parameterBag, $this->fileAccessor);

        $this->subject->write(self::ARRAY_CONTENTS);
    }
}
