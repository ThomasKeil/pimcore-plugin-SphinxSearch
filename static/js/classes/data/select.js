/**
 * Created with JetBrains PhpStorm.
 * User: thomas
 * Date: 19.03.13
 * Time: 08:45
 * To change this template use File | Settings | File Templates.
 */

pimcore.object.classes.data.select = Class.create(pimcore.object.classes.data.select, {

    getLayout: function ($super) {
        $super();

        this.layout.add({
            xtype: "form",
            title: t("Sphinx settings"),
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            items: [
                {
                    xtype: "checkbox",
                    fieldLabel: t("index sphinx"),
                    name: "index_sphinx",
                    checked: this.datax.index_sphinx
                },
                {
                    xtype: "spinnerfield",
                    fieldLabel: t("sphinx weight"),
                    name: "weight_sphinx",
                    value: this.datax.weight_sphinx
                }
            ]
        });


        return this.layout;
    }
});

