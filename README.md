# Tao dependency resolver

Resolves the requires tree from manifests.php in each extension needed.

/!\ Works on **extension** names, not repository names.

A more extensive explanation of the problematics and solutions is exposed in the [documentation](doc/dependency-resolver.md).

## Installation

$ composer install

php min version required: 7.1

php extensions required: php7.1-xml, php7.1-mbstring

## Authentification

You need to provide a valid GitHub token with "repo" autorizations into <project dir>/config/services.yaml, in the key parameters > github.token.

## Resolve dependencies

$ php bin/console dependencies:resolve <root extension name> [-b root extension branch] [--extension-branch dependency extensions branch] [-d directory] 

- *root extension name* is the "manifest name", not the repository name, e.g. "taoQtiTest", not "extension-tao-testqti".
- *root extension branch* is the branch of the extension to be tested
- *dependency extensions branch* is the branch to download for each dependency. This will be changed to provide a file name with a mapping 'extension'=>'branch to load'.
- *directory* is the directory where you want to install the whol package. Defaults to <project root dir>/tmp

## Update repositories

Reads all oat-sa repositories on github to populate a map (currently in <project config dir>/repositoryMap.json).

*/!\ Not needed at the time, it's up to date now and quite time consuming...*

$ php bin/console repositories:update [-b branch name] [-r] [-l limit]

- *branch name* is the name of the branch we want to inspect first when update repository list
- *-r* reloads the list in addition to analyzing every repository 
- *limit* is the number of repositories to analyze

## Dumps repositories

Dumps the repository map to a csv file for human reading and analysis.

$ php bin/console repositories:dump [-f filename]

- *filename* is the csv filename. Defaults to <projet root dir>/repositories.csv
