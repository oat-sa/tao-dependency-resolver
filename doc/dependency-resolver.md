# Tao dependency resolver

Resolves the inclusion tree from information in both `manifests.php` and `composer.json`.

Sample 


## Problematic

- Tao extensions inclusion currently rely both on:
    - `manifest.php` for inclusion of other OAT extensions,
    - `composer.json` for foreign libraries.
- Some repositories include OAT libraries or extension in `composer.json`.
- Some repositories include transitive dependencies in the root composer.json or manifest.php, which potentially leads to version conflicts.
- `manifest.php` are written in... PHP, more difficult to parse than JSON, especially when including class names.
- `manifest.php` use **extension names** whereas `composer.json` use **repository names**.
This last point leads to the need to maintain a mapping of extensions names to repository names.
A [tool](repository-updater.md) has been developed to list all GitHub repositories and extract the extension name when it exists.
- `manifest.php` also contains information about:
    - routing,
    - dependency injection,
    - service configuration,
    - install and update instructions,
    - ...

As an example, see [tao-core's manifest.php](tao-manifest.php), where the only parts used for dependency inclusion are:
```
return array(
    'name' => 'tao',
    'version' => '28.2.0',
    'requires' => array(
        'generis' => '>=9.0.0',
    ),
);
```

## Method

### Find the list of required extensions.

Resolving the dependency tree is performed recursively:

    1. Load the `manifest.php` of the main repository,
    2. Extract the extension names from the `manifest.php`.
    3. For each extension name, load the `manifest.php` and go to 2.

### Install the main and required repository.

The list of included repositories is injected in a local composer execution:

    1. Install the main repository.
    2. Install each of the dependent repository.


## Result

Currently, the result is the installation of all required extensions in a specific directory, in order to be able to independently test an extension with all its dependencies.
This could also be:
- returning a list of all extensions found,
- removing the extension names from the `manifest.php` files and adding the corresponding repository names into the `composer.json` files.

### An example?

The dependency tree of extension `taoQtiItem` in `manifest.php` files is the following:

    taoQtiItem
    |-- generis
    |-- tao
    |   `-- generis
    `-- taoItems
        |-- generis
        |-- tao
        |   `-- generis
        `-- taoBackOffice
            |-- generis
            `-- tao
                `-- generis


Resolving it with this tool will return the following list:

    generis       => oat-sa/generis
    tao           => oat-sa/tao-core
    taoBackOffice => oat-sa/extension-tao-backoffice  (transitive dependency)
    taoItems      => oat-sa/extension-tao-item
    taoQtiItem    => oat-sa/extension-tao-itemqti (main repository)


This list is then injected in a local composer execution, which adds all the dependencies required in `composer.json` file from each repository:

    oat-sa/generis
    |-- clearFw/clearFw (oat-sa/clearfw)
    |-- oat-sa/oatbox-extension-installer 
    `-- oat-sa/lib-generis-search

    oat-sa/tao-core
    |-- oat-sa/oatbox-extension-installer
    |-- oat-sa/jig
    `-- imsglobal/lti (IMSGlobal/LTI-Tool-Provider-Library-PHP)

    oat-sa/extension-tao-backoffice
    `-- oat-sa/oatbox-extension-installer

    oat-sa/extension-tao-item
    `-- oat-sa/oatbox-extension-installer
    
    oat-sa/extension-tao-itemqti
    |-- oat-sa/oatbox-extension-installer
    `-- oat-sa/lib-tao-qti
        |-- qtism/qtism (oat-sa/qti-sdk)
        `-- oat-sa/lib-beeme
    

Composer will finally install the following repositories:

    clearFw/clearFw
    imsglobal/lti
    oat-sa/extension-tao-backoffice
    oat-sa/extension-tao-item
    oat-sa/extension-tao-itemqti
    oat-sa/generis
    oat-sa/jig
    oat-sa/lib-beeme
    oat-sa/lib-generis-search
    oat-sa/lib-tao-qti
    oat-sa/oatbox-extension-installer
    oat-sa/tao-core
    qtism/qtism


## Using the tools

Installation instructions and usage of the tools are to be found in the [README.md](../README.md) of the `tao-dependency-resolver` repository.
