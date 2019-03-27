# Repository lister and updater

To help maintaining the mapping of **extension name** to **repository name**, a tool has been developed in the same repository.
It will read the list of all oat-sa repositories on Github and for each repository, will inspect the following facts:

- privacy
- presence on packagist.org when the repository is public
- `develop` and `master` branch presence
- default branch
- on `develop` and `master` branch:
    - presence of `manifest.php` and `composer.json`
    - name of the repository in `composer.json`
    - name of the extension in `manifest.php` (key `name`) and `composer.json` (key `extra.tao-extension-name`)
- finally determine the **extension name** used by the [dependency resolver tool](dependency-resolver.md).

## Usage

Usage is given in the [main README](../README.md) file.

## Current result

4 types of repositories currently exist:
- **Tao extensions** (the ones interesting us here)
- Tao packages (core + clients)
- Libraries
- Other repositories

Most of the **Tao extensions** repositories are currently based on the same pattern:

- `develop` and `master` branches present at least, `master` being the default branch,
- `composer.json` and `manifest.php` files present in both `develop` and `master` branches,
- identical **extension name** in `composer.json` and `manifest.php` in both `develop` and `master` branches,
- identical **repository name** in `composer.json` of both `develop` and `master` branches,
- public repositories are present on packagist.

33 extension repositories make exceptions:

| Repository name                    | Branches   | Default | Packagist | Missing files      | Repo name (composer.json) | Extension name                              | 
|------------------------------------|------------|---------|-----------|--------------------|---------------------------|---------------------------------------------| 
| extension-experimental-ekstera     | no develop |         | Missing   | manifest, composer |                           |                                             | 
| extension-experimental-kutimo      | no develop |         | Missing   | manifest, composer |                           |                                             | 
| extension-lti-advantage            |            |         | Missing   |                    |                           |                                             | 
| extension-lti-outcomeui            |            |         | Missing   | manifest           |                           |                                             | 
| extension-parcc-tei                |            |         | Missing   |                    |                           |                                             | 
| extension-tao-delivery-keyvalue    |            |         | Missing   |                    |                           |                                             | 
| extension-tao-extrapic             |            |         |           |                    |                           | taoExtraPic (develop), taoTextHelp (master) | 
| extension-tao-foobar               |            |         | Missing   |                    |                           |                                             | 
| extension-tao-iave                 |            | develop |           |                    |                           |                                             | 
| extension-tao-itemprint            |            |         | Missing   |                    |                           |                                             | 
| extension-tao-lti-consumer         |            |         | Missing   | manifest, composer |                           |                                             | 
| extension-tao-marking              |            |         |           | manifest, composer |                           |                                             | 
| extension-tao-pfs                  |            | develop |           |                    |                           |                                             | 
| extension-tao-randomcat            | no develop |         | Missing   | manifest, composer |                           |                                             | 
| extension-tao-restapi-docs         |            |         | Missing   |                    |                           |                                             | 
| extension-tao-static-deliveries    |            |         | Missing   |                    |                           |                                             | 
| extension-tao-talk                 |            |         | Missing   |                    | taoTalk                   |                                             | 
| extension-tao-test-runner-tools    |            |         | Missing   |                    |                           |                                             | 
| extension-tao-unisa                | no master  | develop |           | manifest, composer |                           |                                             | 
| generis                            |            |         |           |                    |                           | generis                                     | 
| irt-test                           |            |         | Missing   |                    |                           | irtTest                                     | 
| Ontology-gmdb                      |            |         | Missing   |                    | taoGmdb                   | taoGmdb                                     | 
| tao-core                           |            |         |           |                    |                           | tao                                         | 
| training-branding                  |            |         | Missing   |                    |                           | trainingBranding                            | 
| training-pci                       |            |         | Missing   | manifest           |                           | trainingPci                                 | 
