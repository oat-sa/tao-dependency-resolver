<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extractor;

use OAT\DependencyResolver\Extension\Extension;

class RemoteExtensionComposerNameExtractor
{
    public function extractComposerName(Extension $extension): string
    {
        $composerContent = json_decode(file_get_contents($extension->getRemoteComposerUrl()), true);

        return $composerContent['name'];
    }
}
