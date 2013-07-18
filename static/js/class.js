/**
 * Created with JetBrains PhpStorm.
 * User: thomas
 * Date: 22.03.13
 * Time: 09:25
 * To change this template use File | Settings | File Templates.
 */

pimcore.object.klass = Class.create(pimcore.object.klass, {

    openClass: function (id) {
        if(Ext.getCmp("pimcore_class_editor_panel_" + id)) {
            this.getEditPanel().activate(Ext.getCmp("pimcore_class_editor_panel_" + id));
            return;
        }

        if (id > 0) {
            Ext.Ajax.request({
                url: "/plugin/SphinxSearch/class/get",
                params: {
                    id: id
                },
                success: this.addClassPanel.bind(this)
            });
        }
    }

});