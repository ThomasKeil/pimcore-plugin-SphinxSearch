For indexing to work correctly you need to run searchd as the webserver's user, e.g.

 su www-data - -c "searchd -c <path_to_site>/htdocs/website/var/plugins/SphinxSearch/sphinx.conf"

You can find this plugin on github:
https://github.com/ThomasKeil/pimcore-plugin-SphinxSearch