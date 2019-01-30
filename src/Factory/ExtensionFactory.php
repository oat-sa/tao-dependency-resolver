<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Factory;

use OAT\DependencyResolver\Extension\Extension;

class ExtensionFactory
{
    private $extensionMap = [];

    public function __construct(array $extensionMap = [])
    {
        $extensionMap = [
            'taoQtiItem' => 'oat-sa/extension-tao-itemqti',
            'taoItems' => 'oat-sa/extension-tao-item',
            'taoBackOffice' => 'oat-sa/extension-tao-backoffice',
            'tao' => 'oat-sa/tao-core',
            'generis' => 'oat-sa/generis',
        ];
        $this->extensionMap = $extensionMap;
    }

    public function create(string $extensionName, string $branch = Extension::DEFAULT_BRANCH): Extension
    {
        return new Extension($extensionName, $this->extensionMap[$extensionName], $branch);
    }
}
