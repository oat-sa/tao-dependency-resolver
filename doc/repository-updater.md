# Repository lister and updater

To help maintaining the mapping of **extension name** to **repository name**, a tool has been developed in the same repository.


## Update repositories

Reads all oat-sa repositories on Github to populate a map (currently in `<project config dir>/repositoryMap.json`).

*/!\ Not needed at the time, it's up to date now and quite time consuming...*

```
$ php bin/console repositories:update [-b branch name] [-r] [-l limit]
```

- `branch name` : name of the branch we want to inspect first when updating repository list. Defaults to `develop`
- `-r` : reloads the list of oat-sa repositories in addition to analyzing every repository
- `limit` : number of repositories to analyze at a time


## Dump repositories

Dumps the repository map to a csv file for human reading and analysis.

```
$ php bin/console repositories:dump [-f filename]
```

- `filename` : CSV filename. Defaults to `<projet root dir>/repositories.csv`


## Current result

4 types of repositories currently exist:
- **Tao extensions**
- Tao packages (core + clients)
- Libraries
- Other repositories

Most of the **Tao extension** repositories are currently based on the same pattern:

- `develop` and `master` branches at least, `master` being the default branch,
- `composer.json` and `manifest.php` files present in both `develop` and `master` branches,
- equal **extension name** in `composer.json` and `manifest.php` in both `develop` and `master` branches,
- equal **repository name** in `composer.json` of both `develop` and `master` branches,
- public repositories are present on packagist.

33 extension repositories make exceptions:

| Repository name                    | Type        | Branches   | Default | Packagist | Missing files         | Repository name         | Extension names                             | 
|------------------------------------|-------------|------------|---------|-----------|-----------------------|-------------------------|---------------------------------------------| 
| extension-generis-hard-pg          | extension   | none       |         | Missing   | manifest and composer |                         | none                                        | 
| extension-tao-unisa                | extension   | no master  | develop |           | manifest and composer |                         |                                             | 
| extension-tao-authorization-server | extension   | no develop |         | Missing   | manifest and composer |                         | none                                        | 
| extension-tao-itemapip             | extension   | no develop |         | Missing   | manifest and composer |                         | none                                        | 
| extension-experimental-ekstera     | extension   | no develop |         | Missing   | manifest and composer |                         |                                             | 
| extension-experimental-kutimo      | extension   | no develop |         | Missing   | manifest and composer |                         |                                             | 
| extension-tao-randomcat            | extension   | no develop |         | Missing   | manifest and composer |                         |                                             | 
| extension-tao-act-authoring        | extension   | no develop |         |           | manifest and composer |                         |                                             | 
| extension-tao-iave                 | extension   |            | develop |           |                       |                         |                                             | 
| extension-tao-pfs                  | extension   |            | develop |           |                       |                         |                                             | 
| extension-tao-talk                 | extension   |            |         | Missing   |                       | taoTalk (composer.json) |                                             | 
| extension-lti-outcomeui            | extension   |            |         | Missing   | manifest              |                         |                                             | 
| extension-tao-item-restapi         | extension   |            |         | Missing   | manifest and composer |                         |                                             | 
| extension-tao-lti-consumer         | extension   |            |         | Missing   | manifest and composer |                         |                                             | 
| extension-tao-restful              | extension   |            |         | Missing   | manifest and composer |                         |                                             | 
| extension-lti-advantage            | extension   |            |         | Missing   |                       |                         |                                             | 
| extension-parcc-tei                | extension   |            |         | Missing   |                       |                         |                                             | 
| extension-tao-delivery-keyvalue    | extension   |            |         | Missing   |                       |                         |                                             | 
| extension-tao-foobar               | extension   |            |         | Missing   |                       |                         |                                             | 
| extension-tao-itemprint            | extension   |            |         | Missing   |                       |                         |                                             | 
| extension-tao-qtiitem-restapi      | extension   |            |         | Missing   |                       |                         |                                             | 
| extension-tao-restapi-docs         | extension   |            |         | Missing   |                       |                         |                                             | 
| extension-tao-static-deliveries    | extension   |            |         | Missing   |                       |                         |                                             | 
| extension-tao-test-runner-tools    | extension   |            |         | Missing   |                       |                         |                                             | 
| irt-test                           | extension ? |            |         | Missing   |                       |                         | irtTest                                     | 
| Ontology-gmdb                      | extension ? |            |         | Missing   |                       | taoGmdb (composer.json) | taoGmdb                                     | 
| training-branding                  | extension ? |            |         | Missing   |                       |                         | trainingBranding                            | 
| training-pci                       | extension ? |            |         | Missing   | manifest              |                         | trainingPci                                 | 
| extension-tao-frontoffice          | extension   |            |         |           | manifest              |                         |                                             | 
| extension-tao-marking              | extension   |            |         |           | manifest and composer |                         |                                             | 
| extension-tao-extrapic             | extension   |            |         |           |                       |                         | taoExtraPic (develop), taoTextHelp (master) | 
| generis                            | extension ? |            |         |           |                       |                         | generis                                     | 
| tao-core                           | extension ? |            |         |           |                       |                         | tao                                         | 


