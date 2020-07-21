<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Command;

use ArgumentCountError;
use Github\Api\GitData;
use Github\Api\GitData\References;
use Github\Api\Organization;
use Github\Api\Repo;
use Github\Api\Repository\Contents;
use Github\Client;
use LogicException;
use OAT\DependencyResolver\Command\DependencyResolverCommand;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Kernel;
use OAT\DependencyResolver\Manifest\DependencyResolver;
use OAT\DependencyResolver\Repository\GithubClientProxy;
use OAT\DependencyResolver\Repository\GithubConnection;
use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use OAT\DependencyResolver\Tests\Helpers\ProtectedAccessorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for DependencyResolverCommand class.
 */
class DependencyResolverCommandTest extends KernelTestCase
{
    use ProtectedAccessorTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var DependencyResolverCommand */
    private $subject;

    /** @var RepositoryMapAccessor */
    private $repositoryMapAccessor;

    /** @var ExtensionFactory */
    private $extensionFactory;

    /** @var DependencyResolver */
    private $dependencyResolver;

    /** @var Client|MockObject */
    private $client;

    public static function getKernelClass()
    {
        return Kernel::class;
    }

    public function setUp()
    {
        $kernel = parent::bootKernel();
        $application = new Application($kernel);

        // Mocks the githubClient to avoid making distant calls.
        $this->client = $this->createMock(Client::class);
        $githubClient = new GithubClientProxy($this->client);

        /** @var GithubConnection $connectedGithubClient */
        $connectedGithubClient = self::$container->get(GithubConnection::class);
        $this->setPrivateProperty($connectedGithubClient, 'client', $githubClient);

        $this->commandTester = new CommandTester($application->find(DependencyResolverCommand::NAME));
    }

    public function testMissingArgument()
    {
        $this->expectException(ArgumentCountError::class);

        $this->commandTester->execute([]);
    }

    /**
     * @dataProvider workingCasesToTest
     */
    public function testWorkingCases($options, $expectedRequire, $expectedRepositories)
    {
        $this->configureClient();
        $output = $this->commandTester->execute($options);
        $this->assertEquals(0, $output);

        // Build composer.json
        $compose = [];
        if (!empty($expectedRepositories)) {
            $composeRepositories = [];
            foreach ($expectedRepositories as $repoFullName => $private) {
                $composeRepositories[] = [
                    'type' => 'vcs',
                    'url' => "https://github.com/${repoFullName}",
                    'no-api' => !$private
                ];
            }

            $compose['repositories'] = $composeRepositories;
        }

        $compose['require'] = $expectedRequire;

        $this->assertEquals(
            json_encode($compose, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            $this->commandTester->getDisplay()
        );
    }

    public function workingCasesToTest()
    {
        return [
            'repo name no branch' => [
                [
                    '--repository-name' => 'oat-sa/generis',
                ],
                ['oat-sa/generis' => 'dev-develop'],
                [],
            ],

            'ext name branch develop' => [
                [
                    '--extension-name' => 'tao',
                    '--main-branch' => 'develop',
                ],
                [
                    'oat-sa/tao-core' => 'dev-develop',
                    'oat-sa/generis' => 'dev-develop',
                ],
                [],
            ],

            'repo name custom branches' => [
                [
                    '--repository-name' => 'oat-sa/extension-tao-itemqti',
                    '--main-branch' => 'custom-branch',
                    '--dependency-branches' => implode(',', [
                        'taoBackOffice:fix-branch',
                        'taoItems:customBranch',
                        'generis:master',
                        'wrong-repo:develop',
                    ]),
                ],
                [
                    'oat-sa/extension-tao-itemqti' => 'dev-custom-branch',
                    'oat-sa/extension-tao-item' => 'dev-customBranch',
                    'oat-sa/extension-tao-backoffice' => 'dev-fix-branch',
                    'oat-sa/tao-core' => 'dev-develop',
                    'oat-sa/generis' => 'dev-master',
                ],
                [],
            ],

            'repo name repositories' => [
                [
                    '--repository-name' => 'oat-sa/extension-tao-itemqti',
                    '--repositories' => ''
                ],
                [
                    'oat-sa/extension-tao-itemqti' => 'dev-develop',
                    'oat-sa/extension-tao-item' => 'dev-develop',
                    'oat-sa/extension-tao-backoffice' => 'dev-develop',
                    'oat-sa/tao-core' => 'dev-develop',
                    'oat-sa/generis' => 'dev-develop',
                ],
                [
                    'oat-sa/extension-tao-itemqti' => false,
                    'oat-sa/extension-tao-item' => false,
                    'oat-sa/extension-tao-backoffice' => false,
                    'oat-sa/tao-core' => false,
                    'oat-sa/generis' => false,
                ]
            ]
        ];
    }

    /**
     * @dataProvider exceptionsToTest
     *
     * @param array  $options
     * @param string $exception
     * @param string $message
     */
    public function testNotExistingRepository(array $options, string $exception, string $message)
    {
        $this->configureClient();

        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        $output = $this->commandTester->execute($options);
        $this->assertEquals(1, $output);
    }

    public function exceptionsToTest()
    {
        return [
            [
                [],
                ArgumentCountError::class,
                'You must provide either a repository name or an extension name to resolve.',
            ],
            [
                ['--repository-name' => 'whatever', '--extension-name' => 'whatever'],
                ArgumentCountError::class,
                'You must provide either a repository name or an extension name to resolve.',
            ],
            [
                ['--repository-name' => 'not-existing'],
                NotMappedException::class,
                'Unknown repository "not-existing".',
            ],
            [
                ['--repository-name' => 'oat-sa/package-tao'],
                NotMappedException::class,
                'Repository "oat-sa/package-tao" has no extension name.',
            ],
            [
                ['--dependency-branches' => ':oat-sa/package-tao'],
                LogicException::class,
                'The extensions-branch option has a non-resolvable value: ":oat-sa/package-tao".',
            ],
        ];
    }

    public function configureClient()
    {
        /** @var Organization|MockObject $organizationApi */
        $organizationApi = $this->createConfiguredMock(Organization::class, ['show' => ['properties']]);
        $organizationApi->method('repositories')->willReturn(
            [
                [
                    'id' => 123456,
                    'name' => 'extension-tao-backoffice',
                    'private' => false,
                    'default_branch' => 'master'
                ],
                [
                    'id' => 234567,
                    'name' => 'extension-tao-item',
                    'private' => false,
                    'default_branch' => 'master'
                ],
                [
                    'id' => 345678,
                    'name' => 'extension-tao-itemqti',
                    'private' => false,
                    'default_branch' => 'master'
                ],
                [
                    'id' => 8720920,
                    'name' => 'extension-tao-private',
                    'private' => true,
                    'default_branch' => 'master'
                ],
                [
                    'id' => 456789,
                    'name' => 'generis',
                    'private' => false,
                    'default_branch' => 'master'
                ],
                [
                    'id' => 5678910,
                    'name' => 'tao-core',
                    'private' => false,
                    'default_branch' => 'master'
                ],
                [
                    'id' => 67891011,
                    'name' => 'wrong-repo',
                    'private' => false,
                    'default_branch' => 'master'
                ],
            ]
        );

        /** @var References|MockObject $gitDataApi */
        $referenceApi = $this->createMock(References::class);
        $referenceApi->method('show')->willReturnCallback(
            function ($owner, $repositoryName, $branchName) {
                return ['ref' => str_replace('heads/', '', $branchName)];
            }
        );
        /** @var GitData|MockObject $gitDataApi */
        $gitDataApi = $this->createConfiguredMock(GitData::class, ['references' => $referenceApi]);

        /** @var Contents|MockObject $contentsApi */
        $contentsApi = $this->createMock(Contents::class);
        $contentsApi->method('download')->willReturnCallback(
            function ($owner, $repositoryName, $filename, $branchName) {
                return file_get_contents(
                    __DIR__
                    . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . 'resources'
                    . DIRECTORY_SEPARATOR . 'raw.githubusercontent.com'
                    . DIRECTORY_SEPARATOR . $owner
                    . DIRECTORY_SEPARATOR . $repositoryName
                    . DIRECTORY_SEPARATOR . $branchName
                    . DIRECTORY_SEPARATOR . $filename
                );
            }
        );
        /** @var Repo|MockObject $repositoryApi */
        $repositoryApi = $this->createConfiguredMock(Repo::class, ['contents' => $contentsApi]);

        $this->client->method('api')->willReturnCallback(
            function ($apiName) use ($organizationApi, $repositoryApi, $gitDataApi) {
                $apis = [
                    GithubClientProxy::API_ORGANIZATION => $organizationApi,
                    GithubClientProxy::API_REPOSITORY => $repositoryApi,
                    GithubClientProxy::API_REFERENCE => $gitDataApi,
                ];

                return $apis[$apiName];
            }
        );
    }
}
