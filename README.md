# Tao dependency resolver

Resolves the dependency requirement tree from manifest.php in each extension needed.

Default result is to displays a corresponding composer.json `require` array.
If you need to write this to a file, just redirect the standard output.

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

## Authentication

You need to provide a valid [GitHub token](https://github.com/settings/tokens) with "repo" access rights into `<project dir>/.env`, in the key `GITHUB_SECRET`.

## The tools

There are two tools in this repository:

### Dependency resolver

Read more about this tool [here](doc/dependency-resolver.md).

```
$ php bin/console oat:dependencies:resolve [--repository-name <repository name> | --extension-name <extension name>] [--main-branch <main repository branch>] [--dependency-branches <dependency branches>]
```

- `main repository name`: repository name, e.g. "oat-sa/extension-tao-testqti" of the repository to resolve.
- `main extension name`: "manifest name", e.g. "taoQtiTest" of the extension to resolve.
- `main repository branch`: the branch of the extension to be resolved.
- `dependency branches`: desired branches to download include for each dependency. In the form of "extensionName1:branchName1,extensionName2:branchName2,...", e.g. "tao:develop,taoQtiItem:fix/tao-1234,generis:10.12.14". Branches for all non given extensions will default to "develop".

Only one of the two options `repository-name` and `extension-name` must be provided.


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
