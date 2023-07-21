/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

// TODO: Blade Inventory, Chassis Capacity, Overall Blade Inventory, remove cap mgmt by chassis type

BRunner.ReportsGrid = Ext.extend(Ext.grid.GridPanel, {
    selectedReport: 'esxhypervisors',

    initComponent: function () {
        Ext.apply(this, arguments);

        this.reportsMeta = {
            storageutilbyarray: {
                title: 'Storage Utilization By Array',
                id: 'select-report-storageutilbyarray'
            },
            storageutilbysan: {
                title: 'Storage Utilization By SAN',
                id: 'select-report-storageutilbysan'
            },

            storagebsbysan: {
                title: 'Business Service By SAN',
                id: 'select-report-storagebsbysan'
            },
            storagebyhost: {
                title: 'Storage Report by Host',
                id: 'select-report-storagebyhost'
            },
            storagebyarray: {
                title: 'Storage Weekly Delta by Array',
                id: 'select-report-storagebyarray'
            },

            bladecountweekoverweek: {
                title: 'Blades Week Over Week',
                id: 'select-report-bladecountweekoverweek'
            },
            vmcountweekoverweek: {
                title: 'VMs Week Over Week',
                id: 'select-report-vmcountweekoverweek'
            },

            usage: {
                title: 'Chassis Usage',
                id: 'select-report-usage'
            },
            capacity: {
                title: 'Chassis Capacity',
                id: 'select-report-capacity'
            },
            bladeInventoryRollup: {
                title: 'Blade Inventory Roll-up',
                id: 'select-report-bladeInventoryRollup'
            },
            capmgmtsw: {
                //title: 'Chassis Cap Mgmt by Switch',
                title: 'Blade Inventory',
                id: 'select-report-capmgmtsw'
            },
            capmgmtchassistype: {
                title: 'Chassis Cap Mgmt by Chassis Type',
                id: 'select-report-capmgmtchassistype'
            },
            chassisfirmware: {
                title: 'Chassis Firmware Report',
                id: 'select-report-chassisfirmware'
            },
            xenhypervisors: {
                title: 'Xen Hypervisors Report',
                id: 'select-report-xenhypervisors'
            },
            esxhypervisors: {
                title: 'ESX Hypervisors Report',
                id: 'select-report-esxhypervisors'
            },
            vlansbychassis: {
                title: 'VLANs Report by Chassis',
                id: 'select-report-vlansbychassis'
            }
        };

        this.readers = [];
        this.readers['storageutilbyarray'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'sanName',            type: 'string'},
                {name: 'arrayName',          type: 'string'},
                {name: 'arrayModel',         type: 'string'},
                {name: 'tier',               type: 'string'},
                {name: 'status',             type: 'string'},
                {name: 'rawTb',              type: 'float'},
                {name: 'useableTb',          type: 'float'},
                {name: 'provisionedTb',      type: 'float'},
                {name: 'availableTb',        type: 'float'},
                {name: 'percentProvisioned', type: 'float'},
                {name: 'percentAvailable',   type: 'float'},
                {name: 'availableAt90',     type: 'float'},
                {name: 'divider',            type: 'bool'}
            ]);
        this.readers['storageutilbysan'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'sanOrTier',          type: 'string'},
                {name: 'useableTb',          type: 'float'},
                {name: 'provisionedTb',      type: 'float'},
                {name: 'availableAt100',     type: 'float'},
                {name: 'percentProvisioned', type: 'float'},
                {name: 'divider',            type: 'bool'}
            ]);
        this.readers['usage'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'type', type: 'string'},
                {name: 'site', type: 'string'},
                {name: 'distSw', type: 'string'},
                {name: 'chassisId', type: 'int'},
                {name: 'chassis', type: 'string'},

                {name: 'slotsTotal', type: 'string'},
                {name: 'slotsProv', type: 'string'},
                {name: 'slotsSpare', type: 'string'},
                {name: 'slotsRes', type: 'string'},
                {name: 'slotsInv', type: 'string'},
                {name: 'slotsEmpty', type: 'string'},
                {name: 'slotsAvail', type: 'string'},

                {name: 'bladesInst', type: 'string'},
                {name: 'bladesProv', type: 'string'},
                {name: 'bladesSpare', type: 'string'},
                {name: 'bladesRes', type: 'string'},
                {name: 'bladesInv', type: 'string'}
            ]);
        this.readers['capacity'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'type', type: 'string'},
                {name: 'site', type: 'string'},
                {name: 'distSw', type: 'string'},

                {name: 'chassisId', type: 'int'},
                {name: 'chassis', type: 'string'},
                {name: 'chassisType', type: 'string'},

                {name: 'bladeTypes', type: 'string'},

                {name: 'spare', type: 'string'},
                {name: 'empty', type: 'string'},
                {name: 'inventory', type: 'string'},
                {name: 'reserved', type: 'string'}
            ]);
        this.readers['capmgmtsw'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'site', type: 'string'},
                {name: 'distSwitch', type: 'string'},
                {name: 'chassis', type: 'string'},
                {name: 'chassisId', type: 'int'},
                {name: 'blade', type: 'string'},
                {name: 'bladeId', type: 'int'},
                {name: 'model', type: 'string'},
                {name: 'cpu', type: 'string'},
                {name: 'memGB', type: 'string'}
            ]);

        this.readers['bladeInventoryRollup'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'quantity', type: 'int'},
                {name: 'model', type: 'string'},
                {name: 'cpu', type: 'string'},
                {name: 'memGB', type: 'string'},
                {name: 'site', type: 'string'},
                {name: 'divider', type: 'boolean'},
                {name: 'modelCss', type: 'string'},
                {name: 'configCss', type: 'string'}
            ]);

        this.readers['bladecountweekoverweek'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'distSwitch', type: 'string'},
                {name: 'businessService', type: 'string'},
                {name: 'builds1', type: 'int'},
                {name: 'decoms1', type: 'int'},
                {name: 'builds2', type: 'int'},
                {name: 'decoms2', type: 'int'},
                {name: 'builds3', type: 'int'},
                {name: 'decoms3', type: 'int'},
                {name: 'builds4', type: 'int'},
                {name: 'decoms4', type: 'int'},
                {name: 'divider', type: 'boolean'}
            ]);

        this.readers['vmcountweekoverweek'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'distSwitch', type: 'string'},
                {name: 'businessService', type: 'string'},
                {name: 'builds1', type: 'int'},
                {name: 'decoms1', type: 'int'},
                {name: 'builds2', type: 'int'},
                {name: 'decoms2', type: 'int'},
                {name: 'builds3', type: 'int'},
                {name: 'decoms3', type: 'int'},
                {name: 'builds4', type: 'int'},
                {name: 'decoms4', type: 'int'},
                {name: 'divider', type: 'boolean'}
            ]);

        this.readers['capmgmtchassistype'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'quantity', type: 'int'},
                {name: 'distSwitch', type: 'string'},
                {name: 'chassisType', type: 'string'}
            ]);
        this.readers['storagebsbysan'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'type', type: 'string'},
                {name: 'sanName', type: 'string'},
                {name: 'tier', type: 'string'},
                {name: 'businessService', type: 'string'},
                {name: 'provisionedGb', type: 'string'}
            ]);
        this.readers['storagebyhost'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'hostName', type: 'string'},
                {name: 'gbThen', type: 'int'},
                {name: 'gbNow', type: 'int'},
                {name: 'allocatedGbDelta', type: 'int'},
                {name: 'arrayName', type: 'string'},
                {name: 'businessService', type: 'string'},
                {name: 'subsystem', type: 'string'}
            ]);
        this.readers['storagebyarray'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'sanName', type: 'string'},
                {name: 'tier', type: 'string'},
                {name: 'arrayName', type: 'string'},
                {name: 'arrayModel', type: 'string'},
                {name: 'gbThen', type: 'float'},
                {name: 'gbNow', type: 'float'},
                {name: 'gbDelta', type: 'float'},
                {name: 'divider', type: 'boolean'},
                {name: 'type', type: 'string'}
            ]);
        this.readers['chassisfirmware'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'distSwitch', type: 'string'},
                {name: 'chassisId', type: 'int'},
                {name: 'chassisName', type: 'string'},
                {name: 'businessService', type: 'string'},
                {name: 'mmFirmware', type: 'string'},
                {name: 'vcFirmware', type: 'string'},
                {name: 'divider', type: 'boolean'}
            ]);
        this.readers['xenhypervisors'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'distSwitch', type: 'string'},
                {name: 'chassisName', type: 'string'},
                {name: 'hyperName', type: 'string'},
                {name: 'hyperModel', type: 'string'},
                {name: 'businessService', type: 'string'},
                {name: 'hyperMemGBTotal', type: 'string'},
                {name: 'hyperMemGBFree', type: 'string'},
                {name: 'totalVMs', type: 'string'},
                {name: 'vms', type: 'string'},
                {name: 'chassisId', type: 'int'},
                {name: 'bladeId', type: 'int'},
                {name: 'divider', type: 'boolean'},
                {name: 'blankIntFields', type: 'boolean'}
            ]);
        this.readers['esxhypervisors'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'clusterName', type: 'string'},
                {name: 'distSwitch', type: 'string'},
                {name: 'chassisName', type: 'string'},
                {name: 'hyperName', type: 'string'},
                {name: 'hyperModel', type: 'string'},

                {name: 'memTotalGB', type: 'int'},
                {name: 'memProvGB', type: 'int'},
                {name: 'memAvailGB', type: 'int'},

                {name: 'memUtilizedGB', type: 'float'},
                {name: 'memUtilizedPct', type: 'float'},
                {name: 'memUnutilizedGB', type: 'float'},

                {name: 'vmsTotal', type: 'int'},
                {name: 'vmsCapacity', type: 'int'},
                {name: 'vmsAvailable', type: 'int'},

                {name: 'cpuCapacity', type: 'int'},
                {name: 'cpuProv', type: 'float'},
                {name: 'cpuAvail', type: 'float'},

                {name: 'cpuUtilized', type: 'float'},
                {name: 'cpuUtilizedPct', type: 'float'},
                {name: 'cpuUnutilized', type: 'float'},

                {name: 'slotsAvailable', type: 'int'},

                {name: 'chassisId', type: 'int'},
                {name: 'bladeId', type: 'int'},

                {name: 'divider', type: 'boolean'},
                {name: 'clusterSubtotal', type: 'boolean'},
                {name: 'blankIntFields', type: 'boolean'}
            ]);
        this.readers['vlansbychassis'] = new Ext.data.JsonReader(
            {
                root:          'grid',
                totalProperty: 'total'
            }, [
                {name: 'distSwitch', type: 'string'},
                {name: 'chassisId', type: 'int'},
                {name: 'chassisName', type: 'string'},
                {name: 'vlanName', type: 'string'},
                {name: 'vlanId', type: 'string'},
                {name: 'subnetMask', type: 'string'},
                {name: 'gateway', type: 'string'},
                {name: 'divider', type: 'boolean'}
            ]);


        this.stores = [];
        for (var report in this.reportsMeta) {
            this.stores[report] = new Ext.data.Store({
                proxy:      new Ext.data.HttpProxy({
                    url: 'php/get_report.php'
                }),
                baseParams: {
                    reportType: report
                },
                remoteSort: true,
                reader:     this.readers[report],
                listeners:  {
                    scope: this,
                    load:  this.updateHeader
                }
            });

        }

        // define the column model for the grid
        this.colModels = [];
        this.colModels['storageutilbyarray'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width:    90,
                align:    'left'
            },
            columns:  [
                {
                    header:    "SAN Name",
                    dataIndex: 'sanName',
                    width:     120,
                    renderer:  this.renderStorageUtilByArray
                }, {
                    header:    "Array Name",
                    dataIndex: 'arrayName',
                    width:     200,
                    renderer:  this.renderStorageUtilByArray
                }, {
                    header:    "Array Model",
                    dataIndex: 'arrayModel',
                    width:     140,
                    renderer:  this.renderStorageUtilByArray
                }, {
                    header:    "Tier",
                    dataIndex: 'tier',
                    renderer:  this.renderStorageUtilByArray,
                    width: 80
                }, {
                    header:    "Status",
                    dataIndex: 'status',
                    renderer:  this.renderStorageUtilByArray,
                    width: 80
                }, {
                    header:    "Raw TB",
                    dataIndex: 'rawTb',
                    align:     'right',
                    renderer:  this.renderStorageUtilByArray
                }, {
                    id:        'useableTb',
                    header:    "Useable TB",
                    dataIndex: 'useableTb',
                    align:     'right',
                    renderer:  this.renderStorageUtilByArray
                }, {
                    id:        'provisionedTb',
                    header:    "Provisioned TB",
                    dataIndex: 'provisionedTb',
                    align:     'right',
                    width:     100,
                    renderer:  this.renderStorageUtilByArray
                }, {
                    id:        'availableTb',
                    header:    "Available TB @ 100% Used",
                    dataIndex: 'availableTb',
                    align:     'right',
                    renderer:  this.renderStorageUtilByArray
                }, {
                    header:    "% Provisioned",
                    dataIndex: 'percentProvisioned',
                    align:     'right',
                    width:     100,
                    renderer:  this.renderStorageUtilByArray
                }, {
                    header:    "Available TB @ 90% Used",
                    dataIndex: 'availableAt90',
                    align:     'right',
                    width:     100,
                    renderer:  this.renderStorageUtilByArray
                }
            ]
        });
        this.colModels['storageutilbysan'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width:    120,
                align:    'left'
            },
            columns:  [
                {
                    header:    "SAN / Tier",
                    dataIndex: 'sanOrTier',
                    renderer:  this.renderStorageUtilBySan
                }, {
                    header:    "Useable TB",
                    dataIndex: 'useableTb',
                    align:     'right',
                    renderer:  this.renderStorageUtilBySan
                }, {
                    header:    "Provisioned TB",
                    dataIndex: 'provisionedTb',
                    align:     'right',
                    renderer:  this.renderStorageUtilBySan
                }, {
                    header:    "Available TB @ 100% Used",
                    dataIndex: 'availableAt100',
                    align:     'right',
                    renderer:  this.renderStorageUtilBySan
                }
            ]
        });
        this.colModels['usage'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width:    70,
                align:    'right'
            },
            columns:  [
                {
                    header:    "Site",
                    dataIndex: 'site',
                    align:     'left',
                    width:     60,
                    tooltip:   'Site name, Sterling or Charlotte'
                },
                {
                    header:    "Distribution Switch",
                    dataIndex: 'distSw',
                    align:     'left',
                    width:     120,
                    renderer:  this.renderer,
                    tooltip:   'Distribution Switch name'
                },
                {
                    header:    "Chassis",
                    dataIndex: 'chassis',
                    align:     'left',
                    width:     90,
                    renderer:  this.renderer,
                    tooltip:   'Chassis name'
                },
                {
                    header:    "Slots<br>Total",
                    dataIndex: 'slotsTotal',
                    renderer:  this.renderer,
                    tooltip:   'Total slots in chassis'
                },
                {
                    header:    "Slots<br>Prov",
                    dataIndex: 'slotsProv',
                    renderer:  this.renderer,
                    tooltip:   'Slots filled by provisioned blades: HP SIM and CMDB have matching FQDNs and the blade is powered on'
                },
                {
                    header:    "Slots<br>Spare",
                    dataIndex: 'slotsSpare',
                    renderer:  this.renderer,
                    tooltip:   'Slots filled by spare blades: CMDB has "spare" in its name'
                },
                {
                    header:    "Slots<br>Resrvd",
                    dataIndex: 'slotsRes',
                    renderer:  this.renderer,
                    tooltip:   'Slots filled by reserved blades: marked as reserved in BladeRunner'
                },
                {
                    header:    "Slots<br>Inven",
                    dataIndex: 'slotsInv',
                    renderer:  this.renderer,
                    tooltip:   'Slots filled by inventory blades: CMDB name has "inventory" in it'
                },
                {
                    header:    "Slots<br>Empty",
                    dataIndex: 'slotsEmpty',
                    renderer:  this.renderer,
                    tooltip:   'Empty slots'
                },
                {
                    header:    "Slots<br>Available",
                    dataIndex: 'slotsAvail',
                    renderer:  this.renderer,
                    tooltip:   'The sum of Inventory + Empty slots'
                },
                {
                    header:   " ",
                    width:    10,
                    renderer: function (value, metadata) {
                        metadata.css += ' br-grid-spacer-cell';
                        return '&nbsp;';
                    }
                },
                {
                    header:    "Blades<br>Installed",
                    dataIndex: 'bladesInst',
                    renderer:  this.renderer,
                    tooltip:   'Blades installed in the chassis'
                },
                {
                    header:    "Blades<br>Prov",
                    dataIndex: 'bladesProv',
                    renderer:  this.renderer,
                    tooltip:   'Provisioned blades: HP SIM and CMDB have matching FQDNs and the blade is power on'
                },
                {
                    header:    "Blades<br>Spare",
                    dataIndex: 'bladesSpare',
                    renderer:  this.renderer,
                    tooltip:   'Spare blades: CMDB has "spare" in it'
                },
                {
                    header:    "Blades<br>Resrvd",
                    dataIndex: 'bladesRes',
                    renderer:  this.renderer,
                    tooltip:   'Reserved blades: marked as reserved in BladeRunner'
                },
                {
                    header:    "Blades<br>Inv",
                    dataIndex: 'bladesInv',
                    renderer:  this.renderer,
                    tooltip:   'Inventory blades: CMDB name has "inventory" in it'
                }
            ]
        });
        this.colModels['capacity'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width:    70,
                align:    'right',
                renderer: this.capacityRenderer
            },
            columns:  [
                {
                    header:    "Distribution Switch",
                    dataIndex: 'distSw',
                    align:     'left',
                    width:     120,
                    tooltip:   'Distribution Switch name'
                },
                {
                    header:    "Chassis",
                    dataIndex: 'chassis',
                    align:     'left',
                    width:     100,
                    tooltip:   'Chassis name'
                },
                {
                    header:    "Chassis Type",
                    dataIndex: 'chassisType',
                    align:     'left',
                    width:     110,
                    tooltip:   'Chassis type'
                },
                {
                    header:    "Blade Types",
                    dataIndex: 'bladeTypes',
                    align:     'left',
                    width:     120,
                    tooltip:   'Blades types in the chassis'
                },
                {
                    header:    "Spares",
                    dataIndex: 'spare',
                    tooltip:   'Slots filled by spare blades: CMDB has "spare" in its name'
                },
                {
                    header:    "Empty<br>Slots",
                    dataIndex: 'empty',
                    tooltip:   'Empty slots'
                },
                {
                    header:    "Blade<br>Inventory",
                    dataIndex: 'inventory',
                    tooltip:   'Inventory blades: CMDB name has "inventory" in it'
                },
                {
                    header:    "Blades<br>Reserved",
                    dataIndex: 'reserved',
                    width:     90,
                    tooltip:   'Reserved blades: marked as reserved in BladeRunner'
                }
            ]
        });
        this.colModels['capmgmtsw'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    120,
                align:    'left'
            },
            columns:  [
                /*
                 header: "Site",
                 dataIndex: 'site'
                 */
                {
                    header:    "Distribution Switch",
                    dataIndex: 'distSwitch',
                    width:     200
                },
                {
                    header:    "Chassis",
                    dataIndex: 'chassis',
                    renderer:  this.capmgtswChassisRenderer,
                    width:     200
                },
                {
                    header:    "Blade",
                    dataIndex: 'blade',
                    renderer:  this.capmgtswBladeRenderer,
                    width:     200
                },
                {
                    header:    "Model",
                    dataIndex: 'model',
                    width:     200
                },
                {
                    header:    "CPU X Cores",
                    dataIndex: 'cpu',
                    align:     'center'
                },
                {
                    header:    "Mem",
                    dataIndex: 'memGB',
                    align:     'center'
                }
            ]
        });
        this.colModels['bladeInventoryRollup'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    120,
                align:    'left'
            },
            columns:  [
                {
                    header:    "Inv Qty",
                    dataIndex: 'quantity',
                    align:     'center',
                    width:     100,
                    renderer:  this.renderBladeInventoryRollup
                },
                {
                    header:    "Model",
                    dataIndex: 'model',
                    width:     200,
                    renderer:  this.renderBladeInventoryRollup
                },
                {
                    header:    "CPU X Cores",
                    dataIndex: 'cpu',
                    align:     'center',
                    renderer:  this.renderBladeInventoryRollup
                },
                {
                    header:    "Mem",
                    dataIndex: 'memGB',
                    align:     'center',
                    renderer:  this.renderBladeInventoryRollup
                },
                {
                    header:    "Site",
                    dataIndex: 'site',
                    renderer:  this.renderBladeInventoryRollup
                }
            ]
        });
        this.colModels['capmgmtchassistype'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    120,
                align:    'left'
            },
            columns:  [
                {
                    header:    "Inventory",
                    dataIndex: 'quantity',
                    align:     'center',
                    width:     100
                },
                {
                    header:    "Distribution Switch",
                    dataIndex: 'distSwitch',
                    width:     200
                },
                {
                    header:    "Chassis Type",
                    dataIndex: 'chassisType'
                }
            ]
        });
        this.colModels['storagebsbysan'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    220,
                align:    'left'
            },
            columns:  [
                {
                    header:    "SAN Name",
                    dataIndex: 'sanName',
                    align:     'left',
                    renderer:  this.renderer,
                    width:     100
                }, {
                    header:    "Tier",
                    dataIndex: 'tier',
                    align:     'left',
                    renderer:  this.renderer,
                    width: 80
                }, {
                    header: "Business Service",
                    dataIndex: "businessService",
                    renderer:  this.renderer
                }, {
                    header:    "Provisioned GB",
                    dataIndex: 'provisionedGb',
                    align:     'right',
                    renderer:  this.renderer,
                    width: 100
                }
            ]
        });
        this.colModels['storagebyhost'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    220,
                align:    'left'
            },
            columns:  [
                {
                    header:    "Host Name",
                    dataIndex: 'hostName',
                    align:     'left',
                    width:     200
                }, {
                    header:    "GB Then",
                    dataIndex: 'gbThen',
                    align:     'right',
                    width: 100
                }, {
                    header:    "GB Now",
                    dataIndex: 'gbNow',
                    align:     'right',
                    width: 100
                }, {
                    header:    "Change (GB)",
                    dataIndex: 'allocatedGbDelta',
                    align:     'right',
                    width: 100
                }, {
                    header:    "Array Name",
                    dataIndex: 'arrayName'
                }, {
                    header: "Business Service",
                    dataIndex: "businessService"
                }, {
                    header: "Subsystem",
                    dataIndex: "subsystem"
                }
            ]
        });
        this.colModels['storagebyarray'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width:    220,
                align:    'left'
            },
            columns:  [
                {
                    header:    "SAN Name",
                    dataIndex: 'sanName',
                    align:     'left',
                    renderer:  this.renderStorageByArray,
                    width:     100
                }, {
                    header:    "Tier",
                    dataIndex: 'tier',
                    align:     'left',
                    renderer:  this.renderStorageByArray,
                    width: 80
                }, {
                    header:    "Array Name",
                    dataIndex: 'arrayName',
                    renderer:  this.renderStorageByArray,
                    width: 100
                }, {
                    header:    "Array Model",
                    dataIndex: 'arrayModel',
                    renderer:  this.renderStorageByArray,
                    width: 100
                }, {
                    header:    "TB Provisioned Then",
                    dataIndex: 'gbThen',
                    align:     'right',
                    width: 100,
                    renderer:  this.renderStorageByArray
                }, {
                    header:    "TB Provisioned Now",
                    dataIndex: 'gbNow',
                    align:     'right',
                    width: 100,
                    renderer:  this.renderStorageByArray
                }, {
                    header:    "Change (TB)",
                    dataIndex: 'gbDelta',
                    align:     'right',
                    width: 100,
                    renderer:  this.renderStorageByArray
                }
            ]
        });

        var css = '';
        //var css = '';
        this.colModels['bladecountweekoverweek'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width:    75,
                align:    'right'
            },
            columns:  [
                {
                    header:    "Distribution Switch",
                    dataIndex: 'distSwitch',
                    align:     'left',
                    renderer: this.renderBladeCount,
                    css:       css,
                    width: 300
                }, {
                    header:    "Business Service",
                    dataIndex: 'businessService',
                    align:     'left',
                    renderer: this.renderBladeCount,
                    css:       css,
                    width: 300
                }, {
                    header:    "Builds",
                    dataIndex: 'builds1',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Decoms",
                    dataIndex: 'decoms1',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Builds",
                    dataIndex: 'builds2',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Decoms",
                    dataIndex: 'decoms2',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Builds",
                    dataIndex: 'builds3',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Decoms",
                    dataIndex: 'decoms3',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Builds",
                    dataIndex: 'builds4',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Decoms",
                    dataIndex: 'decoms4',
                    css:       css,
                    renderer: this.renderBladeCount
                }
            ]
        });
        this.colModels['vmcountweekoverweek'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width:    75,
                align:    'right'
            },
            columns:  [
                {
                    header:    "Distribution Switch",
                    dataIndex: 'distSwitch',
                    align:     'left',
                    renderer: this.renderBladeCount,
                    css:       css,
                    width: 300
                }, {
                    header:    "Business Service",
                    dataIndex: 'businessService',
                    align:     'left',
                    renderer: this.renderBladeCount,
                    css:       css,
                    width: 300
                }, {
                    header:    "Builds",
                    dataIndex: 'builds1',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Decoms",
                    dataIndex: 'decoms1',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Builds",
                    dataIndex: 'builds2',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Decoms",
                    dataIndex: 'decoms2',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Builds",
                    dataIndex: 'builds3',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Decoms",
                    dataIndex: 'decoms3',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Builds",
                    dataIndex: 'builds4',
                    css:       css,
                    renderer: this.renderBladeCount
                }, {
                    header:    "Decoms",
                    dataIndex: 'decoms4',
                    css:       css,
                    renderer: this.renderBladeCount
                }
            ]
        });

        this.colModels['chassisfirmware'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    220,
                align:    'left'
            },
            columns:  [
                {
                    header:    "Distribution Switch",
                    dataIndex: 'distSwitch',
                    align:     'left',
                    renderer: this.chassisFirmwareRenderer,
                    css:       css,
                    width: 300
                }, {
                    header:    "Chassis Name",
                    dataIndex: 'chassisName',
                    renderer: this.chassisFirmwareRenderer,
                    width: 250
                }, {
                    header: "Business Service",
                    dataIndex: 'businessService',
                    align: 'left',
                    renderer: this.chassisFirmwareRenderer,
                    css: css,
                    width: 300
                }, {
                    header:    "Mgmt Processor",
                    dataIndex: 'mmFirmware',
                    align:     'right',
                    renderer: this.chassisFirmwareRenderer,
                    width: 100
                }, {
                    header:    "Virtual Connect",
                    dataIndex: 'vcFirmware',
                    align:     'right',
                    renderer: this.chassisFirmwareRenderer,
                    width: 100
                }
            ]
        });
        this.colModels['xenhypervisors'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    220,
                align:    'left'
            },
            columns:  [
                {
                    header:    "Dist Switch Name",
                    dataIndex: 'distSwitch',
                    renderer:  this.renderXenHypers,
                    width: 200
                }, {
                    header:    "Chassis Name",
                    dataIndex: 'chassisName',
                    renderer:  this.renderXenHypers,
                    width: 135
                }, {
                    header:    "Hyper Name",
                    dataIndex: 'hyperName',
                    renderer:  this.renderXenHypers,
                    width: 135
                }, {
                    header: "Hyper Model",
                    dataIndex: 'hyperModel',
                    renderer:  this.renderXenHypers,
                    width: 140
                }, {
                    header: "Business Service",
                    dataIndex: 'businessService',
                    renderer:  this.renderXenHypers,
                    width: 200
                }, {
                    header:    "Total Mem (GB)",
                    dataIndex: 'hyperMemGBTotal',
                    align:     'right',
                    renderer:  this.renderXenHypers,
                    width: 100
                }, {
                    header:    "Avail Mem (GB)",
                    dataIndex: 'hyperMemGBFree',
                    align:     'right',
                    renderer:  this.renderXenHypers,
                    width: 100
                }, {
                    header:    "Total VMs",
                    dataIndex: 'totalVMs',
                    align:     'right',
                    renderer:  this.renderXenHypers,
                    width: 100
                }, {
                    header:    "VMs",
                    dataIndex: 'vms',
                    renderer:  this.renderXenHypers,
                    width: 1200
                }
            ]
        });
        this.colModels['esxhypervisors'] = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width:    100,
                align:    'left'
            },
            columns:  [
                {
                    header:    "Cluster Name",
                    dataIndex: 'clusterName',
                    renderer:  this.renderEsxHypers,
                    width: 125
                }, {
                    header: "Dist Switch Name",
                    dataIndex: 'distSwitch',
                    renderer: this.renderEsxHypers,
                    width: 200
                }, {
                    header: "Chassis Name",
                    dataIndex: 'chassisName',
                    renderer: this.renderEsxHypers,
                    width: 125
                }, {
                    header: "Hyper Name",
                    dataIndex: 'hyperName',
                    renderer:  this.renderEsxHypers,
                    width: 125
                }, {
                    header: "Hyper Model",
                    dataIndex: 'hyperModel',
                    renderer: this.renderEsxHypers
                }, {
                    header:    "Total Physical Mem Capacity(GB)",
                    dataIndex: 'memTotalGB',
                    align:     'right',
                    renderer:  this.renderEsxHypers
                }, {
                    header:    "Total Mem Provisioned(GB)",
                    dataIndex: 'memProvGB',
                    align:     'right',
                    renderer:  this.renderEsxHypers
                }, {
                    header:    "Avail Physical Mem",
                    dataIndex: 'memAvailGB',
                    align:     'right',
                    renderer:  this.renderEsxHypers
                }, {
                    header: "Total Mem Utilized(GB)",
                    dataIndex: 'memUtilizedGB',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total Mem Utilized(%)",
                    dataIndex: 'memUtilizedPct',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total Mem UnUtilized",
                    dataIndex: 'memUnutilizedGB',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total VM Capacity",
                    dataIndex: 'vmsCapacity',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total VMs Provisioned",
                    dataIndex: 'vmsTotal',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total VMs Available",
                    dataIndex: 'vmsAvailable',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total Physical CPU Capacity",
                    dataIndex: 'cpuCapacity',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total CPU Provisioned",
                    dataIndex: 'cpuProv',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Available Physical CPUs",
                    dataIndex: 'cpuAvail',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total CPU Utilized",
                    dataIndex: 'cpuUtilized',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total CPU Utilized(%)",
                    dataIndex: 'cpuUtilizedPct',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Total CPU UnUtilized",
                    dataIndex: 'cpuUnutilized',
                    align: 'right',
                    renderer: this.renderEsxHypers
                }, {
                    header: "Slots Available",
                    dataIndex: 'slotsAvailable',
                    align: 'right',
                    width: 60,
                    renderer: this.renderEsxHypers
                }
            ]
        });
        this.colModels['vlansbychassis'] = new Ext.grid.ColumnModel({
             defaults: {
                 sortable: true,
                 width:    220,
                 align:    'left'
             },
             columns:  [
                 {
                     header:    "Dist Switch Name",
                     dataIndex: 'distSwitch',
                     renderer:  this.renderVLANsByChassis,
                     width: 200
                 }, {
                     header:    "Chassis Name",
                     dataIndex: 'chassisName',
                     renderer:  this.renderVLANsByChassis,
                     width: 135
                 }, {
                     header:    "VLAN Name",
                     dataIndex: 'vlanName',
                     renderer:  this.renderVLANsByChassis,
                     width: 300
                 }, {
                     header:    "VLAN ID",
                     dataIndex: 'vlanId',
                     renderer:  this.renderVLANsByChassis,
                     align:     "right",
                     width: 80
                 }, {
                     header:    "Subnet Mask",
                     dataIndex: 'subnetMask',
                     renderer:  this.renderVLANsByChassis,
                     width: 120
                 }, {
                     header:    "Gateway",
                     dataIndex: 'gateway',
                     renderer:  this.renderVLANsByChassis,
                     width: 120
                 }
             ]
         });

        this.columnHeaderPluginRows = [];
        this.columnHeaderPluginRows['bladecountweekoverweek'] = [
            [
                {
                    header: '&nbsp;',
                    colspan: 2
                }, {
                    header: 'Date 1',
                    colspan: 2,
                    align: 'center'
                }, {
                    header: 'Date 2',
                    colspan: 2,
                    align: 'center'
                }, {
                    header: 'Date 3',
                    colspan: 2,
                    align: 'center'
                }, {
                    header: 'Date 4',
                    colspan: 2,
                    align: 'center'
                }
            ]
        ];
        this.columnHeaderPlugin = new Ext.ux.grid.ColumnHeaderGroup({
            rows: typeof this.columnHeaderPluginRows[this.selectedReport] !== 'undefined' ? this.columnHeaderPluginRows[this.selectedReport] : [[]]
        });

        this.toolbar = new Ext.Toolbar({
            items: [
                {
                    text: ' File &nbsp; ',
                    menu: {
                        xtype: 'menu',
                        items: [
                            {
                                text:    'Export',
                                tooltip: 'Export report to Excel',
                                iconCls: 'export',
                                handler: this.exportExcel,
                                scope:   this
                            }
                        ]
                    }
                },
                {
                    xtype: 'tbspacer'
                },
                {
                    text: ' Report &nbsp; ',
                    menu: {
                        xtype: 'menu',
                        plain: true,
                        items: [
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.capmgmtsw.title,
                                id:        this.reportsMeta.capmgmtsw.id,
                                checked:   !!(this.selectedReport === "capmgmtsw"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.bladeInventoryRollup.title,
                                id:        this.reportsMeta.bladeInventoryRollup.id,
                                checked:   !!(this.selectedReport === "bladeInventoryRollup"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.capmgmtchassistype.title,
                                id:        this.reportsMeta.capmgmtchassistype.id,
                                checked:   !!(this.selectedReport === "capmgmtchassistype"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.usage.title,
                                id:        this.reportsMeta.usage.id,
                                checked:   !!(this.selectedReport === "usage"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.capacity.title,
                                id:        this.reportsMeta.capacity.id,
                                checked:   !!(this.selectedReport === "capacity"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            '-',
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.chassisfirmware.title,
                                id:        this.reportsMeta.chassisfirmware.id,
                                checked:   !!(this.selectedReport === "chassisfirmware"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            '-',
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.xenhypervisors.title,
                                id:        this.reportsMeta.xenhypervisors.id,
                                checked:   !!(this.selectedReport === "xenhypervisors"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.esxhypervisors.title,
                                id:        this.reportsMeta.esxhypervisors.id,
                                checked:   !!(this.selectedReport === "esxhypervisors"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            '-',
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.vlansbychassis.title,
                                id:        this.reportsMeta.vlansbychassis.id,
                                checked:   !!(this.selectedReport === "vlansbychassis"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            '-',
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.storageutilbyarray.title,
                                id:        this.reportsMeta.storageutilbyarray.id,
                                checked:   !!(this.selectedReport === "storageutilbyarray"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.storageutilbysan.title,
                                id:        this.reportsMeta.storageutilbysan.id,
                                checked:   !!(this.selectedReport === "storageutilbysan"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.storagebsbysan.title,
                                id:        this.reportsMeta.storagebsbysan.id,
                                checked:   !!(this.selectedReport === "storagebsbysan"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.storagebyarray.title,
                                id:        this.reportsMeta.storagebyarray.id,
                                checked:   !!(this.selectedReport === "storagebyarray"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                            '-',
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.bladecountweekoverweek.title,
                                id:        this.reportsMeta.bladecountweekoverweek.id,
                                checked:   !!(this.selectedReport === "bladecountweekoverweek"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            }, {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.vmcountweekoverweek.title,
                                id:        this.reportsMeta.vmcountweekoverweek.id,
                                checked:   !!(this.selectedReport === "vmcountweekoverweek"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            }
                        ]
                    }
                },
                '->',
                {
                    xtype: 'tbtext',
                    id:    'report-title',
                    text:  this.reportsMeta[this.selectedReport].title,
                    cls:   'report-title'
                }
            ]
        });

        Ext.apply(this, {
            trackMouseOver: true,
            stripeRows:     true,
            autoScroll:     true,
            columnLines:    true,
            enableHdMenu:   false,
            //margins: '0 0 0 0',
            loadMask:       {
                msg: "Loading..."
            },
            tbar:           this.toolbar,
            colModel:       this.colModels[this.selectedReport],
            store:          this.stores[this.selectedReport],
            plugins:        this.columnHeaderPlugin
        });

        // call parent
        BRunner.ReportsGrid.superclass.initComponent.apply(this, arguments);
    },

    updateHeader: function() {
        var title = this.reportsMeta[this.selectedReport].title;

        if (this.selectedReport === 'storagebyhost') {
            var dateFrom = this.stores[this.selectedReport].reader.jsonData['dateFrom'];
            var dateTo = this.stores[this.selectedReport].reader.jsonData['dateTo'];
            this.colModels[this.selectedReport].setColumnHeader(2, dateFrom);
            this.colModels[this.selectedReport].setColumnHeader(3, dateTo);
        }
        else if (this.selectedReport === 'storagebyarray') {
            var dateFrom = this.stores[this.selectedReport].reader.jsonData['dateFrom'];
            var dateTo = this.stores[this.selectedReport].reader.jsonData['dateTo'];
            this.colModels[this.selectedReport].setColumnHeader(4, dateFrom + ' (Provisioned TB)');
            this.colModels[this.selectedReport].setColumnHeader(5, dateTo + ' (Provisioned TB)');
        }
        else if (this.selectedReport.search(/(blade|vm)countweekoverweek/) !== -1) {
            // since this is using the columnHeadGroup plugin, we can't just specify the header title
            // we have to redefine the header rows and then call reconfigure on the grid yuck
            this.columnHeaderPlugin.viewConfig.setHeaderRows([[
                    {
                        header: ' &nbsp; &nbsp; ',
                        colspan: 2
                    }, {
                        header: this.stores[this.selectedReport].reader.jsonData['dates'][0],
                        colspan: 2,
                        align: 'center'
                    }, {
                        header: this.stores[this.selectedReport].reader.jsonData['dates'][1],
                        colspan: 2,
                        align: 'center'
                    }, {
                        header: this.stores[this.selectedReport].reader.jsonData['dates'][2],
                        colspan: 2,
                        align: 'center'
                    }, {
                        header: this.stores[this.selectedReport].reader.jsonData['dates'][3],
                        colspan: 2,
                        align: 'center'
                    }
                ]]);
            this.reconfigure(this.stores[this.selectedReport], this.colModels[this.selectedReport]);
        }
        Ext.get('report-title').dom.innerHTML = title;
    },

    onReportCheck: function (item, e) {
        if (e !== null) e.stopEvent();
        var index = item.id.replace(/select-report-/, ''),
            title = this.reportsMeta[index].title;
        Ext.get('report-title').dom.innerHTML = title;

        // added setHeaderRows in the plugin so that the header rows can be changed after render. Yes, this is a hack
        this.columnHeaderPlugin.viewConfig.setHeaderRows(typeof this.columnHeaderPluginRows[index] !== 'undefined' ? this.columnHeaderPluginRows[index] : [[]]);

        this.reconfigure(this.stores[index], this.colModels[index]);
        this.stores[index].load();
        this.selectedReport = index;
    },

    exportExcel: function (item, e) {
        // create the form in HTML
        var win,
            task,
            html;

        e.stopEvent();

        html = "<div class='csv-message'>Generating MicroSquish Excel file. This may take a while depending on the formatting involved.</div>\n" +
            "<form name='exportForm' id='exportForm' method='post' action='php/export_report.php'>\n" +
            "<input type='hidden' name='reportType' value='" + this.selectedReport + "'>\n" +
            "</form>\n";

        // open a window and submit the form
        win = new Ext.Window({
            modal:      true,
            constrain:  true,
            height:     120,
            width:      230,
            autoScroll: true,
            items:      [
                {
                    html: html
                }
            ]
        });
        win.render(document.body);
        win.center();
        win.show();

        task = new Ext.util.DelayedTask();
        task.delay(3000, this.submitExportForm, this, [win]);
    },

    submitExportForm: function (win) {
        Ext.get('exportForm').dom.submit();
        win.close();
    },

    renderXenHypers: function(value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            divider = data.divider,
            blankIntFields = data.blankIntFields,
            fieldName = record.fields.items[colIndex].name,
            retValue;

        // add a line separator between the change in SAN names
        // a "divider" boolean field is defined by the php server code
        if (divider) {
            metadata.css.replace(/x-grid3-cell-inner/, " ");
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        // bold the dist switch
        if (fieldName === 'distSwitch') {
            metadata.css += " bold";
            retValue = value;
        } else if (fieldName === "chassisName" && value !== "") {
            retValue = "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis(" + data.chassisId + ");'>" + value + "</span>";
        } else if (fieldName === "hyperName" && value !== "") {
            retValue = "<span title='Click to go to blade' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToBlade(" + data.bladeId + ");'>" + value + "</span>";
        } else if (blankIntFields && fieldName.search(/hyperMemGBTotal|hyperMemGBFree/) !== -1) {
            retValue = "";
        } else if (fieldName === "hyperMemGBTotal" && parseInt(value) < 96) {
            metadata.css += 'br-grid-cell-error';
            retValue = value;
        } else {
            retValue = value;
        }
        return retValue;

    },

    renderEsxHypers: function(value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            divider = data.divider,
            blankIntFields = data.blankIntFields,
            fieldName = record.store.fields.items[colIndex].name,
            retValue;

        // add a line separator between the change Cluster Names
        // a "divider" boolean field is defined by the php server code
        if (divider) {
            metadata.css.replace(/x-grid3-cell-inner/, " ");
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        // let's add some color so we can read this.
        // VMs - Capacity, Provisioned & Available: light blue
        if (fieldName.search(/mem[TPA]/) !== -1) {
            metadata.css += " memory-capacity";
            if (record.data.clusterSubtotal == true) {
                metadata.css += " memory-capacity-subtotals";
            }
            retValue = value;
        // CPUs - Capacity, Provisioned & Available: brown
        } else if (fieldName.search(/cpu[CPA]/) !== -1) {
            metadata.css += " cpu-capacity";
            if (record.data.clusterSubtotal == true) {
                metadata.css += " cpu-capacity-subtotals";
            }
            retValue = value;
        // VMs - Capacity, Provisioned & Available: green
        } else if (fieldName.search(/vms[CTA]/) !== -1) {
            metadata.css += " vm-capacity";
            if (record.data.clusterSubtotal == true) {
                metadata.css += " vm-capacity-subtotals";
            }
            retValue = value;
        }

        // bold all cluster subtotals
        if (record.data.clusterSubtotal == true) {
            metadata.css += " bold";
            retValue = value;
            // add the text "Subtotals" in this column
            if (fieldName === "distSwitch") {
                retValue = "Subtotals";
            }
        } else if (blankIntFields && fieldName === "slotsAvailable" && parseInt(value) === 0) {
            retValue = "";
            // these columns need to be in bold
        } else if (fieldName === "memAvailGB" || fieldName === "cpuAvail") {
            metadata.css += " bold";
            retValue = value;
        } else if (blankIntFields && fieldName.search(/totalVMs/) !== -1 && parseInt(value) === 0) {
            retValue = "";
        } else if (fieldName === "chassisName" && value !== "") {
            retValue = "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis(" + data.chassisId + ");'>" + value + "</span>";
        } else if (fieldName === "hyperName" && value !== "") {
            retValue = "<span title='Click to go to blade' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToBlade(" + data.bladeId + ");'>" + value + "</span>";
        } else {
            retValue = value;
        }
        return retValue;

    },

    renderVLANsByChassis: function(value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            divider = data.divider,
            fieldName = record.fields.items[colIndex].name;

        // add a line separator between the change in SAN names
        // a "divider" boolean field is defined by the php server code
        if (divider) {
            metadata.css.replace(/x-grid3-cell-inner/, " ");
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        // bold the dist switch
        if (fieldName === 'distSwitch') {
            metadata.css += " bold";
            return value;
        } else if (fieldName === "chassisName") {
            return "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis(" + data.chassisId + ");'>" + value + "</span>";
        } else {
            return value;
        }

    },

    renderer: function (value, metadata, record, rowIndex, colIndex) {
        var data, dType, inv, inst, used, number;
        data = record.data;
        dType = data.type;
        inv = parseInt(data.bladesInv);
        inst = parseInt(data.bladesInst);
        used = parseInt(data.bladesProv) + parseInt(data.bladesSpare) + parseInt(data.bladesRes);

        if (dType === "switch") {
            if (colIndex === 1) {
                metadata.css += 'br-grid-cell-tl';
                return value;
            }
            else if (colIndex >= 2 && colIndex <= 15) {
                metadata.css += 'br-grid-cell-top';
            }
            else if (colIndex === 16) {
                metadata.css += 'br-grid-cell-tr';
            }
            return "<strong>" + value + "</strong>";
        }

        else if (dType === "total") {
            if (colIndex >= 3 && colIndex <= 16) {
                metadata.css += 'br-grid-cell-top';
            }
            return "<strong>" + value + "</strong>";
        }
        else if (dType === "storageTotal") {
            metadata.css += 'br-grid-cell-bottom';

            if (colIndex === 3) {
                return "<b>" + value + "</b>";
            } else {
                return value;
            }
        }
        else if (dType === "chassis" && colIndex === 2) {
            return "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis(" + data.chassisId + ");'>" + value + "</span>";
        }
        else if (dType === "chassis" && (colIndex >= 11 && colIndex <= 15) &&
            inv !== (inst - used)) {
            metadata.css = "br-grid-cell-warn";
            return value;
        } else {
            return value;
        }
    },

    capacityRenderer: function (value, metadata, record, rowIndex, colIndex) {
        var data, dType, inv, inst, used;
        data = record.data;
        dType = data.type;
        inv = parseInt(data.bladesInv);
        inst = parseInt(data.bladesInst);
        used = parseInt(data.bladesProv) + parseInt(data.bladesSpare) + parseInt(data.bladesRes);

        // bold the chassis name and totals values
        if (dType === "switch" || dType === "total") {
            metadata.css += 'bold';
            return value;
        }

        // create link to chassis
        else if (dType === "chassis" && colIndex === 1) {
            return "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis(" + data.chassisId + ");'>" + value + "</span>";
        }

        // highlight 0 spares in yellow

        else if (dType === "chassis" && colIndex === 4 && parseInt(value) === 0) {
            metadata.css = "br-grid-cell-warn";
            return value;
        }
        else {
            return value;
        }
    },

    capmgtswChassisRenderer: function(value, metadata, record, rowIndex, colIndex) {
        var data = record.data;
        if (colIndex === 1) {
            return "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis(" + data.chassisId + ");'>" + value + "</span>";
        } else {
            return value;
        }
    },

    capmgtswBladeRenderer: function(value, metadata, record, rowIndex, colIndex) {
        var data = record.data;
        if (colIndex === 2) {
            return "<span title='Click to go to blade' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToBlade(" + data.bladeId + ");'>" + value + "</span>";
        } else {
            return value;
        }
    },

    chassisFirmwareRenderer: function (value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            divider = data.divider,
            fieldName = record.fields.items[colIndex].name;

        // add a line separator between the change in SAN names
        // a "divider" boolean field is defined by the php server code
        if (divider) {
            metadata.css.replace(/x-grid3-cell-inner/, " ");
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        // bold the business serice
        if (fieldName === 'distSwitch') {
            metadata.css += " bold";
            return value;
        }

        if (colIndex === 1) {
            return "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis(" + data.chassisId + ");'>" + value + "</span>";
        }
        else {
            return value;
        }
    },

    renderVersion: function (value, metadata, record, rowIndex, colIndex) {
        if (value === 0 || isNaN(value)) {
            return "N/A";
        } else {
            return value;
        }
    },

    renderSubtotals: function (value, metadata, record, rowIndex, colIndex) {
        var data;
        data = record.data;

        if (data.businessService === "Array Subtotals" && colIndex > 0) {
            if (data.gbDelta !== 0) {
                metadata.css += 'br-grid-cell-warn';
            }
            return "<b>" + value + "</b>";
        }
        else if (data.gbDelta !== 0) {
            metadata.css += 'br-grid-cell-warn';
            return value;
        }
        else if (data.businessService === "blank" && colIndex > 0) {
            return "";
        }
        else {
            return value;
        }
    },

    renderStorageByArray: function (value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            divider = data.divider,
            fieldName = record.fields.items[colIndex].name;

        // add a line separator between the change in SAN names
        // a "divider" boolean field is defined by the php server code
        if (divider) {
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        // bold the san name
        if (fieldName === 'sanName') {
            metadata.css += " bold";
            return value;
        }

        if (fieldName === 'gbDelta' && value !== 0) {
            metadata.css += " br-grid-cell-warn";
        }

        // cols 4 thru 6 are floating point so format to show 2 digits after the decimal point
        if (colIndex >= 4) {
            return Ext.util.Format.number(value, '0.00');
        }

        // else just return the value
        return value;
    },

    renderBladeCount: function (value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            divider = data.divider,
            fieldName = record.fields.items[colIndex].name;

        // add a line separator between the change in SAN names
        // a "divider" boolean field is defined by the php server code
        if (divider) {
            metadata.css.replace(/x-grid3-cell-inner/, " ");
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        // bold the business serice
        if (fieldName === 'distSwitch' || fieldName === 'businessService') {
            metadata.css += " bold";
            return value;
        }

        else if (value > 0 && fieldName.search(/builds/) !== -1) {
            metadata.css += "green-background";
        }

        else if (value > 0 && fieldName.search(/decoms/) !== -1) {
            metadata.css += "red-background";
        }

        // else just return the value
        return value;
    },

    renderStorageUtilByArray: function (value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            divider = data.divider,
            fieldName = record.fields.items[colIndex].name;

        // bold the san name
        if (fieldName === 'sanName') {
            metadata.css += " bold";
        }

        // highlight percent provisioned amounts base upon thresholds
        if (fieldName === "percentProvisioned") {
            if (value >= 90) {
                metadata.css += " br-grid-cell-error";
            } else if (value >= 85) {
                metadata.css += " br-grid-cell-serious";
            } else if (value >= 80) {
                metadata.css += " br-grid-cell-warn";
            }
        }

        // add a line separator between the change in SAN names
        // a "divider" boolean field is defined by the php server code
        if (divider) {
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        // add percent symbol to the column names that start with percent
        if (colIndex === 9) {
            return Ext.util.Format.number(value, '0.00') + '%';
        }

        // cols 5 thru 8 & 11 are floating point so format to show 2 digits after the decimal point
        if ((colIndex >= 5 && colIndex <= 8) || colIndex === 10) {
            return Ext.util.Format.number(value, '0.00');
        }

        // else just return the value
        return value;
    },

    renderStorageUtilBySan: function (value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            divider = data.divider,
            fieldName = record.fields.items[colIndex].name;

        // highlight percent provisioned amounts base upon thresholds
        if (fieldName === "availableAt100" && value) {
            if (data.percentProvisioned >= 90) {
                metadata.css += " br-grid-cell-error";
            } else if (data.percentProvisioned >= 85) {
                metadata.css += " br-grid-cell-serious";
            } else if (data.percentProvisioned >= 80) {
                metadata.css += " br-grid-cell-warn";
            }
        }

        // add a line separator between the change in SAN names
        // a "divider" boolean field is defined by the php server code
        if (divider) {
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        if (colIndex === 0) {
            metadata.css += " bold";
        }
        else {
            // cols 1 thru 3 are floating point so format to show 2 digits after the decimal point
            if (value) {
                return Ext.util.Format.number(value, '0.00');
            } else {
                return "";
            }
        }

        if (value.search(/Tier/) !== -1) {
            return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" + value;
        }

        // else just return the value
        return value;
    },

    renderBladeInventoryRollup: function (value, metadata, record, rowIndex, colIndex) {
        var data = record.data,
            fieldName = record.fields.items[colIndex].name;

        // a "divider" boolean field is defined by the php server code
        if (data.divider) {
            metadata.css += " br-grid-cell-divider";
            return '';
        }

        // color code models
        if (fieldName === "model") {
            metadata.css += " " + data.modelCss;
        }

        // color code standard configs
        metadata.css += " " + data.configCss;

        return value;
    }
});
