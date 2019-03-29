# Tao dependency resolver

Resolves the dependency requirement tree from manifest.php in each extension needed.

Default result is to displays a corresponding composer.json `require` array.

Now works with both **extension** names and **repository** names.

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

You need to provide a valid [GitHub token](https://github.com/settings/tokens) with "repo" access rights into `<project dir>/.env`, in the key `GITHUB_SECRET`.

## The tools

There are two tools in this repository:

### Dependency resolver

Read more about this tool [here](doc/dependency-resolver.md).

```
$ php bin/console dependencies:resolve <root extension or repository name> [--package-branch <root extension branch>] [--extensions-branch <dependency extensions branch>] [--dump-directory <directory>]
```

- `root extension or repository name`: "manifest name", not the repository name, e.g. "taoQtiTest", not "oat-sa/extension-tao-testqti".
- `root extension branch`: the branch of the extension to be tested
- `dependency extensions branch`: the branch to download for each dependency in the form of "extensionName1:branchName1,extensionName2:branchName2,...", e.g. "tao:develop,taoQtiItem:3.2.1,generis:10.12.14". Branches for all non given extensions will default to "develop".
- `directory`: the directory where you want to write the composer.json file. Defaults to the system temp dir (i.e. `/tmp` on linux systems).

### Repository lister

Read more about this tool [here](doc/repository-updater.md).

This tool reads every oat-sa repositories in Github and maintains the map of **extension name** to **repository name**.

**/!\ This is not needed each time, there is an up-to-date map currently provided in `<project config dir>/repositoryMap.json` and it is quite time consuming...**

#### Update repositories

Reads and analyzes repositories from Github.

```
$ php bin/console repositories:update [-b branch name] [-r] [-l limit]
```

- `branch name` : name of the branch we want to inspect first when updating repository list. Defaults to `develop`
- `-r` : reloads the list of oat-sa repositories in addition to analyzing every repository
- `limit` : number of repositories to analyze at a time

#### Dump repository list

Dumps the repository map to a CSV file for human reading and analysis.

```
$ php bin/console repositories:dump [-f filename]
```

- `filename` : CSV filename. Defaults to `<project root dir>/repositories.csv`
