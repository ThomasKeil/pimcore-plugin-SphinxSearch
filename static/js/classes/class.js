/**
 * Created with JetBrains PhpStorm.
 * User: thomas
 * Date: 20.03.13
 * Time: 08:08
 * To change this template use File | Settings | File Templates.
 */


pimcore.object.classes.klass  = Class.create(pimcore.object.classes.klass, {

    saveOnComplete: function ($super, response) {
        $super(response);
        var configuration = Ext.encode(this.getData());
        var values = Ext.encode(this.data);

        Ext.Ajax.request({
            url: '/plugin/SphinxSearch/class/save',
            method: "post",
            params: {
                configuration: configuration,
                id: this.data.id
            },
            success: this.saveSphinxOnComplete.bind(this),
            failure: this.saveSphinxOnError.bind(this)
        });
    },

    saveSphinxOnComplete: function (response) {

        try {
            var res = Ext.decode(response.responseText);
            if(res.success) {
                console.log(t("sphinx_class_saved_successfully"));
            } else {
                throw "save of sphinx data was not successful, see debug.log";
            }
        } catch (e) {
            this.saveSphinxOnError();
        }

    },

    saveSphinxOnError: function () {
        pimcore.helpers.showNotification(t("error"), t("sphinx_class_save_error"), "error");
    }
});