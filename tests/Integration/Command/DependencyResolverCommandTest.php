<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Command;

use ArgumentCountError;
use Github\Api\GitData;
use Github\Api\GitData\References;
use Github\Api\Organization;
use Github\Api\Repo;
use Github\Api\Repository\Contents;
use LogicException;
use OAT\DependencyResolver\Command\DependencyResolverCommand;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Kernel;
use OAT\DependencyResolver\Manifest\DependencyResolver;
use OAT\DependencyResolver\Repository\ConnectedGithubClient;
use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use OAT\DependencyResolver\Tests\Helpers\ProtectedAccessorTrait;
use OAT\DependencyResolver\Tests\Unit\Repository\GithubClientProxyMock;
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

    /** @var GithubClientProxyMock */
    private $githubClient;

    public static function getKernelClass()
    {
        return Kernel::class;
    }

    public function setUp()
    {
        $kernel = parent::bootKernel();
        $application = new Application($kernel);

        // Mocks the githubClient to avoid making distant calls.
        $this->githubClient = new GithubClientProxyMock();

        /** @var ConnectedGithubClient $connectedGithubClient */
        $connectedGithubClient = self::$container->get(ConnectedGithubClient::class);
        $this->setPrivateProperty($connectedGithubClient, 'client', $this->githubClient);

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
    public function testWorkingCases($options, $expected)
    {
        $this->configureClient();
        $output = $this->commandTester->execute($options);
        $this->assertEquals(0, $output);
        $this->assertEquals(
            json_encode(['require' => $expected], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
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
            ],

            'ext name branch devlop' => [
                [
                    '--extension-name' => 'tao',
                    '--main-branch' => 'develop',
                ],
                [
                    'oat-sa/tao-core' => 'dev-develop',
                    'oat-sa/generis' => 'dev-develop',
                ],
            ],

            'repo name custom branches' => [
                [
                    '--repository-name' => 'oat-sa/extension-tao-itemqti',
                    '--main-branch' => 'custom-branch',
                    '--extension-branches' => implode(',', [
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
            ],
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
                ['--extension-branches' => ':oat-sa/package-tao'],
                LogicException::class,
                'The extensions-branch option has a non-resolvable value: ":oat-sa/package-tao".',
            ],
        ];
    }

    public function configureClient()
    {
        /** @var Organization|MockObject $organizationApi */
        $organizationApi = $this->createConfiguredMock(Organization::class, ['show' => ['properties']]);

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

        $this->githubClient
            ->setOrganizationApi($organizationApi)
            ->setGitDataApi($gitDataApi)
            ->setRepositoryApi($repositoryApi);
    }
}
