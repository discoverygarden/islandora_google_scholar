# islandora_google_scholar

## Introduction

Metatag additions and modifications for institutional repository content to provide Google Scholar metatags that are customized based on a node's research output 'type', as well as a 'first attached PDF' metatag as the PDF URL.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/Islandora/islandora)
* [Controlled Access Terms](https://github.com/Islandora/controlled_access_terms)
* [Metatag](https://www.drupal.org/project/metatag)

It is expected that [Metatag Google Scholar](https://www.drupal.org/project/metatag_google_scholar) will be installed in order to make use of the base citation-prefixed metatags, but it is not a strict requirement of the module.

## Installation

Install as usual, see
[this]( https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Usage

Islandora Google Scholar provides two capabilities to display metatags for
research output nodes based on the node's type:

### Metatag Alterer

A configuration piece designed to allow metatags to be altered in or out, or
changed, based on the name of the referenced taxonomy term in a configured
reference field. A configuration is attached to a node bundle, and expects the
following:

* `entity_bundle`: The bundle targeted by the configuration
* `reference_field`: The field on the bundle that references the 'type' to
search for when making alterations
* `reference_target`: Where to find the value in the reference field (e.g., the
'name' of a taxonomy term)
* `alterations`: A list of target types to perform alterations for. Each of
these target types contains a mapping of metatags to alter, and the new value
to use for that metatag.

Configuration can also accept a `purge_if_absent` as `true` or `false`; if true,
and the node's 'type' is not found in the `alterations` list,
`citation_`-prefixed metatags will be removed from the resultant metatag list.

An example configuration is included in this module's `config/install` folder
to get you started.

### Islandora Google Scholar Metatag Group

The Islandora Google Scholar metatag group includes, at present, a checkbox
allowing you to use the 'first attached PDF' to a node as the
`citation_pdf_url` metatag.

## Troubleshooting/Issues

Having problems or solved a problem? Contact [discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

This project has been sponsored by:

* [discoverygarden](http://wwww.discoverygarden.ca)

## Development

If you would like to contribute to this module, please check out our helpful
[Documentation for Developers](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers)
info, [Developers](http://islandora.ca/developers) section on Islandora.ca and
contact [discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
