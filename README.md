<h1>SphinxSearch Plugin</h1>
This plugin is intended to interface the <a href="http://sphinxsearch.com/">Sphinx full-text search engine</a>
with Pimcore.

It adds new configuration settings to object class definitions input fields to enable indexing and set weights,
adds a weight attribute to document input field definition and provides a general settings panel to configure
the search daemon and run the indexer manually.

It also provides classes and methods to query the index.

<h2>Installation</h2>
For indexing to work correctly you need to run searchd as the webserver's user, e.g.

 su www-data - -c "searchd -c <path_to_site>/htdocs/website/var/plugins/SphinxSearch/sphinx.conf"

<h2>Get the plugin</h2>
You can find this plugin on github:
https://github.com/ThomasKeil/pimcore-plugin-SphinxSearch

<h2>Contact the author</h2>
You can contact the author at thomas@weblizards.de