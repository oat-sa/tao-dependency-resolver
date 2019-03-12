<?php
$repos = json_decode(file_get_contents('repos.json'), true);

foreach ($repos as $repo) {
    if (substr($repo['full_name'], 0, strlen('oat-sa/extension-tao')) !== 'oat-sa/extension-tao') {
        continue;
    }

    $url = 'https://raw.githubusercontent.com/'.$repo['full_name'].'/develop/composer.json';
    $composer = file_get_contents($url);
    $composer = json_decode($composer, true);
    $extensionName = $composer['extra']['tao-extension-name'];
    echo '\'', $extensionName, '\'=>\'', $repo['full_name'], '\'', "\n";
    exit;
}

