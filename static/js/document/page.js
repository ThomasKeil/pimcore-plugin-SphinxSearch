/**
 * Created with JetBrains PhpStorm.
 * User: thomas
 * Date: 11.04.13
 * Time: 08:32
 * To change this template use File | Settings | File Templates.
 */



pimcore.document.page = Class.create(pimcore.document.page, {

    save : function ($super, task, only) {
        $super(task, only);

        if (!this.edit.frame || !this.edit.frame.editables) {
            throw "edit not available";
        }

        var values = {};
        var editables = this.edit.frame.editables;

        for (var i = 0; i < editables.length; i++) {
            try {
                var editable = editables[i];
                if (editable.getName() && !editable.getInherited() && editable.getType()) {
                    console.log(editable.getName() + " "+ editable.getType());
                    var editableName = editable.getName();
                    values[editableName] = {};
                    values[editableName].weight = editable.element && editable.element.sphinx_weight ? editable.element.sphinx_weight : 0;
                    values[editableName].type = editable.getType();
                }
            } catch (e) {
                console.log(e);
            }
        }

        try {
            Ext.Ajax.request({
                url: '/plugin/SphinxSearch/Document/save',
                method: "post",
                params: {
                    data: Ext.encode({
                            id: this.edit.frame.pimcore_document_id,
                            config: values
                    })
                },
                success: function (response) {
                    try{
                        var rdata = Ext.decode(response.responseText);
                        if (!(rdata && rdata.success)) {
                            pimcore.helpers.showNotification(t("error"), t("error_saving_document"), "error",t(rdata.message));
                        }
                    } catch (e) {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_document"), "error");
                    }
                }
            });
        }
        catch (e2) {
            console.log(e2);
        }

    }

});