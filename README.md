<h1>SphinxSearch Plugin</h1>
This plugin is intended to interface the <a href="http://sphinxsearch.com/">Sphinx full-text search engine</a>
with Pimcore.

It adds new configuration settings to object class definitions input fields to enable indexing and set weights,
adds a weight attribute to document input field definition and provides a general settings panel to configure
the search daemon and run the indexer manually.

It also provides classes and methods to query the index.

<h2>Get the plugin</h2>
You can find this plugin on github:
https://github.com/ThomasKeil/pimcore-plugin-SphinxSearch


<h2>Installation</h2>
Download the plugin and copy it to plugins/SphinxSearch, e.g.

        cd plugins
        git clone https://github.com/ThomasKeil/pimcore-plugin-SphinxSearch.git SphinxSearch

For indexing to work correctly you need to run searchd, this can be done in the plugin's settings.

If this won't work for you please do it on the command line as the webserver's user, e.g.

 su www-data - -c "searchd -c <path_to_your_htdocs>/website/var/plugins/SphinxSearch/sphinx.conf"

<h2>Usage</h2>
<h3>Submitting information to the index</h3>

For Objects, check the "Index" checkbox in the "Sphinx Settings" part of a classdefinition.
Documents are indexed automatically.

New sphinx.conf is written if a document or classdefinition is changed.

<h3>Querying the index</h3>

        $result_array = SphinxSearch_SphinxSearch::queryObjects($query, $class_name);

        // Suitable for Iterators or Paginators:
        $object_list = new SphinxSearch_ObjectList($query, $class_name);

        $document_list = new SphinxSearch_DocumentList($query);

<h2>Contact the author</h2>
This plugin is developed by <a href="http://www.weblizards.de/">Weblizards - Custom Internet Solutions</a>.
You can contact the author at thomas@weblizards.de
