# Tao dependency resolver

Resolves the requires tree from manifests.php in each extension needed.

/!\ Works on **extension** names, not repository names.

A more extensive explanation of the problematics and solutions is exposed in the [documentation](doc/dependency-resolver.md).


## Installation

Clone this repository.

Install dependencies :

```
$ composer install
```

Minimal PHP version required: 7.1

PHP extensions required: php7.1-xml, php7.1-mbstring


## Authentification

You need to provide a valid GitHub token with "repo" access rights into `<project dir>/config/services.yaml`, in the key `parameters > github.token`.


## The tools

There are two tools in this repository:

### Dependency resolver

```
$ php bin/console dependencies:resolve <root extension name> [-b root extension branch] [--extension-branch dependency extensions branch] [-d directory] 
```

- *root extension name*: "manifest name", not the repository name, e.g. "taoQtiTest", not "oat-sa/extension-tao-testqti".
- *root extension branch*: the branch of the extension to be tested
- *dependency extensions branch*: the branch to download for each dependency. This will be changed to provide a file name with a mapping 'extension'=>'branch to load'.
- *directory*: the directory where you want to install the whole package. Defaults to `<project root dir>/tmp`


### Repository lister

This tool reads avery oat-sa repositories in Github and maintains the map of **extension name** to **repository name**.

Read more about this tool [here](doc/repository-updater.md).

#### Update repositories

Reads all oat-sa repositories on github to populate a map (currently in <project config dir>/repositoryMap.json).

*/!\ Not needed each time, there is an up-to-date map currently provided in this repository and quite time consuming...*

```
$ php bin/console repositories:update [-b branch name] [-r] [-l limit]
```

- *branch name*: name of the branch we want to inspect first when update repository list
- *-r*: reloads the list in addition to analyzing every repository 
- *limit*: number of repositories to analyze at a time

## Dumps repositories list

Dumps the repository map to a CSV file for human reading and analysis.

```
$ php bin/console repositories:dump [-f filename]
```

- *filename*: CSV filename. Defaults to <projet root dir>/repositories.csv
