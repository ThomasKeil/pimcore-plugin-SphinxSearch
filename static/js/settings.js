pimcore.registerNS("sphinxsearch.settings");
sphinxsearch.settings = Class.create({

    initialize: function () {
        this.getData();
//        this.getTabPanel();
    },

    getData: function () {
        Ext.Ajax.request({
            url: "/plugin/SphinxSearch/settings/settings",
            success: function (response) {
                this.data = Ext.decode(response.responseText);
                this.getTabPanel();
            }.bind(this)
        });
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "sphinxsearch_settings",
                title: t("Sphinxsearch settings"),
                iconCls: "sitemap_icon_root",
                border: false,
                layout: "fit",
                closable:true
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("sphinxsearch_settings");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("sphinxsearch_settings");
            }.bind(this));

            this.layout = new Ext.FormPanel({
                bodyStyle:'padding:20px 5px 20px 5px;',
                border: false,
                autoScroll: true,
                forceLayout: true,
                defaults: {
                    forceLayout: true
                },
                layout: "pimcoreform",
                tbar: [
                    {
                        text: t("Save"),
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply"
                    },
                    {
                        text: t("Run Indexer"),
                        handler: this.runIndexer.bind(this),
                        iconCls: "pimcore_icon_start"
                    }

                ],
                bbar: ["<span>Developed by: <a href='http://www.weblizards.de' target='_blank'>Weblizards - Custom Internet Solutions</a></span>"],
                items: [
                    {
                        xtype:'fieldset',
                        title: t('searchd'),
                        collapsible: false,
                        collapsed: false,
                        autoHeight:true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t("path to pid file"),
                                xtype: "textfield",
                                name: "sphinxsearch.path_pid",
                                value: this.data.pid,
                                width: 350
                            },
                            {
                                fieldLabel: t("path to log file"),
                                xtype: "textfield",
                                name: "sphinxsearch.path_logfile",
                                value: this.data.logfile,
                                width: 350
                            },
                            {
                                fieldLabel: t("path to query log"),
                                xtype: "textfield",
                                name: "sphinxsearch.path_querylog",
                                value: this.data.querylog,
                                width: 350
                            }

                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('indexer'),
                        collapsible: false,
                        collapsed: false,
                        autoHeight:true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t("run_indexer_with_maintenance"),
                                xtype: "checkbox",
                                name: "sphinxsearch.indexer_maintenance",
                                checked: this.data.indexer_maintenance
                            },
                            {
                                fieldLabel: t("path to indexer"),
                                xtype: "textfield",
                                name: "sphinxsearch.path_indexer",
                                value: this.data.indexer,
                                width: 350
                            },
                            {
                                fieldLabel: t("indexer period"),
                                xtype: "textfield",
                                name: "sphinxsearch.indexer_period",
                                value: this.data.indexer_period,
                                width: 50
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('indexer_period_description'),
                                cls: "pimcore_extra_label_bottom"
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('documents'),
                        collapsible: false,
                        collapsed: false,
                        autoHeight:true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t("documents_use_languages"),
                                xtype: "checkbox",
                                name: "sphinxsearch.documents_i18n",
                                checked: this.data.documents_i18n
                            }
                        ]
                    }
                ]
            });

            this.panel.add(this.layout);

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("sphinxsearch_settings");
    },
    save: function () {
        var values = this.layout.getForm().getFieldValues();
        console.log(values);

        // check for mandatory fields
        if(empty(values["sphinxsearch.path_pid"])) {
            Ext.MessageBox.alert(t("error"), t("please enter path to pid file"));
            return;
        }
        if(empty(values["sphinxsearch.path_querylog"])) {
            Ext.MessageBox.alert(t("error"), t("please enter path to query logfile"));
            return;
        }
        if(empty(values["sphinxsearch.path_logfile"])) {
            Ext.MessageBox.alert(t("error"), t("please enter path to logfile"));
            return;
        }



        Ext.Ajax.request({
            url: "/plugin/SphinxSearch/settings/save",
            method: "post",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("sphinx settings saved"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("sphinx settings save error"), "error", t(res.message));
                    }
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("sphinx settings save error"), "error");
                }
            }
        });
    },

    runIndexer: function () {

        Ext.Ajax.request({
            url: "/plugin/SphinxSearch/admin/runindexer",
            method: "post",
            params: {

            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("Sphinx Indexer ran successfully"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("Sphinx Indexer had an error"), "error", t(res.message));
                    }
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("Error running the Sphinx Indexer"), "error");
                }
            }
        });
    }


});