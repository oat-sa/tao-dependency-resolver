<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extractor;

use PhpParser\Parser;

class RemoteManifestDependenciesExtractor
{
    /** @var Parser */
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function extractDependencies(string $manifestUrl)
    {
        $manifestContent = file_get_contents($manifestUrl);

        $ast = $this->parser->parse($manifestContent);

        var_dump($ast);exit;
    }
}
