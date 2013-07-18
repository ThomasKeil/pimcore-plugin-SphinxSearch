pimcore.registerNS("pimcore.plugin.sphinxsearch");

pimcore.plugin.sphinxsearch = Class.create(pimcore.plugin.admin, {


    getClassName: function () {
        return "pimcore.plugin.sphinxsearch";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },


    uninstall: function() {
    
    },

    pimcoreReady: function (params,broker){


        var user = pimcore.globalmanager.get("user");
        if(user.admin == true){

            var toolbar = Ext.getCmp("pimcore_panel_toolbar");

            var action = new Ext.Action({
                id:"sphinxsearch_setting_button",
                text: t('Sphinxsearch settings'),
                iconCls:"sphinxsearch_icon_root",
                handler: function(){
                    var gestion = new sphinxsearch.settings;
                }
            });

            toolbar.items.items[2].menu.add(action);
        }
    }



});

new pimcore.plugin.sphinxsearch();
