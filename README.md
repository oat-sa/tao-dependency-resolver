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

Create a `.env` file in the root directory with your github token and organization name (you can copy and populate the existing .env.dist template file).
You need to provide a valid [GitHub token](https://github.com/settings/tokens) with "repo" access rights.

## The tools

There are two tools in this repository:

### Dependency resolver

Read more about this tool [here](doc/dependency-resolver.md).

```
$ php bin/console oat:dependencies:resolve [--repository-name <repository name> | --extension-name <extension name>] [--main-branch <main repository branch>] [--dependency-branches <dependency branches>] [--repositories] [--file <path to composer.json>]
```

- `main repository name`: repository name, e.g. "oat-sa/extension-tao-testqti" of the repository to resolve.
- `main extension name`: "manifest name", e.g. "taoQtiTest" of the extension to resolve.
- `main repository branch`: the branch of the extension to be resolved.
- `dependency branches`: desired branches to download include for each dependency. In the form of "extensionName1:branchName1,extensionName2:branchName2,...", e.g. "tao:develop,taoQtiItem:fix/tao-1234,generis:10.12.14". Branches for all non given extensions will default to "develop".
- `repositories`: flag to indicate that composer repositories information must be included. In case of private repositories, ssh authentication must be set up to use the generated composer.json file.
- `file`: when given, the command will generate the composer.json into this file along the stdout. The target need to be writeable, and can be either a relative or absolute path. For instance, `--file output/composer.json` will generate the composer.json in the `output` directory (within the current working directory). 

Only one of the two options `repository-name` and `extension-name` must be provided.

#### Usage examples

Resolve dependencies for repository `oat-sa/extension-tao-items` with no branch specified (defaults to `develop`) and write the result to `/dest/dir/composer.json` with verbose output:

```
php bin/console oat:dependencies:resolve --repository-name oat-sa/extension-tao-items > /dest/dir/composer.json -vv
```

Will display the following in the console:

```
app.INFO: Resolving dependencies for repository "oat-sa/extension-tao-item".
app.INFO: Retrieving oat-sa/extension-tao-item/develop/manifest.php
app.INFO: Resolving dependencies for repository "oat-sa/extension-tao-backoffice".
app.INFO: Retrieving oat-sa/extension-tao-backoffice/develop/manifest.php
app.INFO: Resolving dependencies for repository "oat-sa/tao-core".
app.INFO: Retrieving oat-sa/tao-core/develop/manifest.php
app.INFO: Resolving dependencies for repository "oat-sa/generis".
app.INFO: Retrieving oat-sa/generis/develop/manifest.php
```

And write the following to `/dest/dir/composer.json`:

```
{
    "require": {
        "oat-sa/extension-tao-item": "dev-develop",
        "oat-sa/extension-tao-backoffice": "dev-develop",
        "oat-sa/tao-core": "dev-develop",
        "oat-sa/generis": "dev-develop"
    }
}
```

Resolve dependencies for extension `taoQtiTest` with main branch feature/tao-1234, branch feature/tao-1234 for tao, branch master for generis and display the result to console:

```
php bin/console oat:dependencies:resolve --extension-name taoQtiTest --main-branch feature/tao-1234 --dependency-branches tao:feature/tao-1234,generis:master
```

Will display the following in the console:

```
{
    "require": {
        "oat-sa/extension-tao-testqti": "dev-feature/TAO-7304-CSRF-timed-token-pool",
        "oat-sa/extension-tao-itemqti": "dev-develop",
        "oat-sa/extension-tao-item": "dev-develop",
        "oat-sa/extension-tao-backoffice": "dev-develop",
        "oat-sa/tao-core": "dev-feature/TAO-7304-CSRF-timed-token-pool",
        "oat-sa/generis": "dev-master",
        "oat-sa/extension-tao-test": "dev-develop",
        "oat-sa/extension-tao-delivery": "dev-develop",
        "oat-sa/extension-tao-outcome": "dev-develop"
    }
}
```

Requiring a non-existing branch will result in a exception both for main repository and dependencies:

```
php bin/console oat:dependencies:resolve --repository-name oat-sa/tao-core --main-branch foo
                                                          
  Unable to retrieve reference to "oat-sa/tao-core/foo".
```

```
php bin/console oat:dependencies:resolve --repository-name oat-sa/tao-core --dependency-branches generis:bar

  Unable to retrieve reference to "oat-sa/generis/bar".
```

Trying to resolve dependencies for unknown repository will result in a exception:

```
php bin/console oat:dependencies:resolve --repository-name oat-sa/tao-foo

  Unknown repository "oat-sa/tao-foo".  
```

Trying to resolve dependencies for unknown *extension* will also result in a exception:

```
php bin/console oat:dependencies:resolve --extension-name extension-tao-bar

  Extension "extension-tao-bar" not found in map.  
```

But if it is a newly added extension, it may just not be in the extension map. If this is the case, you can update the extension map with the second tool:

```
php bin/console oat:repositories:update --reload-list
```

### Repository lister

Read more about this tool [here](doc/repository-updater.md).

This tool reads every oat-sa repositories in Github and maintains the map of **extension name** to **repository name**.

**/!\ This is not needed each time, there is an up-to-date map currently provided in `<project config dir>/repositoryMap.json` and it is quite time consuming...**

#### Update repositories

Reads and analyzes repositories from Github.

```
$ php bin/console repositories:update [--reload-list] [--limit limit]
```

- `--reload-list` : reloads the list of oat-sa repositories in addition to analyzing every repository
- `limit` : number of repositories to analyze at a time

#### Dump repository list

Dumps the repository map to a CSV file for human reading and analysis.

```
$ php bin/console repositories:dump [-f filename]
```

- `filename` : CSV filename. Defaults to `<project root dir>/repositories.csv`
