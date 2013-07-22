pimcore.registerNS("pimcore.plugin.sphinxsearch");

pimcore.plugin.sphinxsearch = Class.create(pimcore.plugin.admin, {


    getClassName: function () {
        return "pimcore.plugin.sphinxsearch";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (viewport) {
        var user = pimcore.globalmanager.get("user");
        if(user.admin == true){

//            var toolbar = Ext.getCmp("pimcore_panel_toolbar");
            var toolbar = pimcore.globalmanager.get("layout_toolbar");
            var action = new Ext.Action({
                id:"sphinxsearch_setting_button",
                text: t('Sphinxsearch settings'),
                iconCls:"sphinxsearch_icon_root",
                handler: function(){
                    var gestion = new sphinxsearch.settings;
                }
            });
            toolbar.settingsMenu.addItem(action);
        }
    }



});
(function() {
    new pimcore.plugin.sphinxsearch();
})();

