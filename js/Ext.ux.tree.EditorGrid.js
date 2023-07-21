/*******************************************************************************
 *
 * $Id: Ext.ux.tree.EditorGrid.js 75201 2013-05-13 15:07:20Z rcallaha $
 * $Date: 2013-05-13 11:07:20 -0400 (Mon, 13 May 2013) $
 * $Author: rcallaha $
 * $Revision: 75201 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/Ext.ux.tree.EditorGrid.js $
 *
 *******************************************************************************
 */

/*!
 * Ext JS Library 3.4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

/**
 * @class Ext.ux.tree.TreeGridNodeUI
 * @overrides Ext.ux.tree.TreeGridNodeUI
 *
 * Adds support function for new Ext.tree.TreeNode set function
 */
Ext.override(Ext.ux.tree.TreeGridNodeUI, {
    // private
    onSetText: function (node, text, oldText, field) {
        if (node.getOwnerTree()) {
            var tree = node.getOwnerTree(),
                columns = tree.getColumnModel(),
                el = node.ui.elNode,
                col = false, target;

            for (var i = 0; i < columns.length; i++) {
                if (columns[i].dataIndex == field) {
                    col = i;
                    target = el.childNodes[col];
                    break;
                }
            }

            if (col !== false) {
                switch (col) {
                    case 0:
                        var parentTarget = target;
                        for (var i = 0; i < parentTarget.childNodes.length; i++) {
                            if (parentTarget.childNodes[i].className == 'x-tree-node-anchor' && parentTarget.childNodes[i].localName == 'a') {
                                target = parentTarget.childNodes[i].childNodes[0];
                                break;
                            }
                        }
                        break;
                    default:
                        target = target.childNodes[0];
                        break;
                }

                if (this.rendered) {
                    target.innerHTML = text;
                }
            }
        }
    }

});

/**
 * @class Ext.tree.TreeNode
 * @overrides Ext.tree.TreeNode
 *
 * Adds support for get and set functions on tree nodes similar to record get and set functions
 */
Ext.override(Ext.tree.TreeNode, {
    /**
     * Get the value of the {@link Ext.data.Field#name named field}.
     * @param {String} name The {@link Ext.data.Field#name name of the field} to get the value of.
     * @return {Object} The value of the field.
     */
    get: function (name) {
        return this.attributes[name];
    },

    /**
     * Sets the text for specified field on this node
     * @param {String} field
     * @param {String} text
     */
    set: function (field, text) {
        var oldText = this.text;
        this.text = this.attributes[field] = text;
        if (this.rendered) { // event without subscribing
            this.ui.onSetText(this, text, oldText, field);
        }
        this.fireEvent('textchange', this, text, oldText, field);
    },

    /**
     * Toggles the node from a leaf to a branch
     * @return {Ext.tree.TreeNode} The newly created {@link Ext.tree.TreeNode TreeNode}
     */
    toggleLeaf: function () {
        if (this.isLeaf()) return this.setLeaf(false);
        else return this.setLeaf();
    },

    /**
     * Turns a leaf node into a branch node, optionally false will do the reverse
     * @param {Boolean} option
     * @return {Ext.tree.TreeNode} The newly created {@link Ext.tree.TreeNode TreeNode}
     */
    setLeaf: function (option) {
        if (option !== false) option = true;
        var nodeConf = {},
            parentNode = this.parentNode;

        if (!this.isLeaf()) this.removeAll(true);
        for (var p in this.attributes) {
            if (p != 'loader') nodeConf[p] = this.get(p);
        }
        nodeConf['leaf'] = option;

        var targetNode = new Ext.tree.TreeNode(nodeConf);
        if (parentNode) parentNode.replaceChild(targetNode, this);
        return targetNode;
    }

});

/**
 * @class Ext.tree.TreeDropZone
 * @overrides Ext.tree.TreeDropZone
 *
 * Alters onValidDrop event to supply full dragData, not just the node, to allow firing dragdrop
 * to support grids as well as trees.
 */
Ext.override(Ext.tree.TreeDropZone, {
    // private
    processDrop: function (target, data, point, dd, e, dropNode) {
        var dropEvent = {
            tree:       this.tree,
            target:     target,
            data:       data,
            point:      point,
            source:     dd,
            rawEvent:   e,
            dropNode:   dropNode,
            cancel:     !data,
            dropStatus: false
        };
        if (!this.tree.events['datadrop']) this.tree.addEvents('datadrop'); // add datadrop event to every tree
        if (!this.tree.events['beforedatadrop']) this.tree.addEvents('beforedatadrop'); // add beforedatadrop event to every tree
        if (dropEvent.dropNode) {
            var retval = this.tree.fireEvent("beforenodedrop", dropEvent);
            if (retval === false || dropEvent.cancel === true) {
                target.ui.endDrop();
                return dropEvent.dropStatus;
            }
        }
        else {
            var retval = this.tree.fireEvent("beforedatadrop", dropEvent);
            if (retval !== false && dropEvent.cancel !== true)
                dropEvent.dropStatus = (!(this.tree.fireEvent("datadrop", dropEvent) === false || dropEvent.cancel === true));

            target.ui.endDrop();
            return dropEvent.dropStatus;
        }

        target = dropEvent.target;
        if (point == 'append' && !target.isExpanded()) {
            target.expand(false, null, function () {
                this.completeDrop(dropEvent);
            }.createDelegate(this));
        }
        else {
            this.completeDrop(dropEvent);
        }
        return true;
    }

});

/**
 * @class Ext.ux.tree.EditorGrid
 * @extends Ext.ux.tree.TreeGrid
 *
 * @xtype editortreegrid
 */
Ext.ux.tree.EditorGrid = Ext.extend(Ext.ux.tree.TreeGrid, {
    /**
     * @cfg {String} cellSelector The selector used to find cells internally (defaults to <tt>'td.x-treegrid-col'</tt>)
     */
    cellSelector: 'td.x-treegrid-col',

    /**
     * @cfg {Number} cellSelectorDepth The number of levels to search for cells in event delegation (defaults to <tt>4</tt>)
     */
    cellSelectorDepth: 4,

    /**
     * @cfg {String} rowSelector The selector used to find rows internally (defaults to <tt>'div.x-grid3-row'</tt>)
     */
    rowSelector: 'tr.x-tree-node-el',

    /**
     * @cfg {Number} rowSelectorDepth The number of levels to search for rows in event delegation (defaults to <tt>10</tt>)
     */
    rowSelectorDepth: 10,

    /**
     * @cfg {Boolean} autoEncode
     * True to automatically HTML encode and decode values pre and post edit (defaults to false)
     */
    autoEncode: false,

    /**
     * @cfg {Boolean} forceValidation
     * True to force validation even if the value is unmodified (defaults to false)
     */
    forceValidation: false,

    /**
     * @cfg {Object} loadMask An {@link Ext.LoadMask} config or true to mask the grid while
     * loading. Defaults to <code>false</code>.
     */
    loadMask: false,

    /**
     * @cfg {Object} expandOnDblClick If true, nodes will expand when double clicked.  This is
     * the default behavior of a tree. Defaults to <code>false</code> to prevent expanding when
     * trying to edit.
     */
    expandOnDblClick: false,

    preloadChildren: true,
    cls:             'x-treegrid',

    constructor: function (config) {
        if (config.autoExpandColumn) {/*
         switch (typeof config.autoExpandColumn) {
         case 'string':
         for (var i = 0; i < config.columns.length; i++) {
         if (config.columns[i].dataIndex == config.autoExpandColumn)
         config.columns[i].width = 15;
         }
         break;
         case 'number':
         config.columns[config.autoExpandColumn].width = 15;
         break;
         }

         */
        }
        Ext.ux.tree.EditorGrid.superclass.constructor.call(this, config);
    },

    initComponent: function () {
        this.addEvents(
            // custom events
            /**
             * @event datadrop
             * Fires after non-node data has been dropped onto a tree node. The edit event object has the
             * following properties <br />
             * <ul style="padding:5px;padding-left:16px;">
             * <li>tree - This tree</li>
             * <li>target - The drop target</li>
             * <li>data - The data being dropped</li>
             * <li>point - The drop point</li>
             * <li>cancel - Set this to true to cancel the edit or return false from your handler.</li>
             * </ul>
             * @param {Object} e A drop event object (see above for description)
             */
            /**
             * @event beforedatadrop
             * Fires before non-node data has been dropped onto a tree node.  The edit event object has the
             * following properties <br />
             * <ul style="padding:5px;padding-left:16px;">
             * <li>tree - This tree</li>
             * <li>target - The drop target</li>
             * <li>data - The data being dropped</li>
             * <li>point - The drop point</li>
             * <li>cancel - Set this to true to cancel the edit or return false from your handler.</li>
             * </ul>
             * @param {Object} e A drop event object (see above for description)
             */
            /**
             * @event cellclick
             * Fires when a cell is clicked. The edit event object has the following properties <br />
             * <ul style="padding:5px;padding-left:16px;">
             * <li>tree - This tree</li>
             * <li>field - The field name being edited</li>
             * <li>value - The value for the field being edited.</li>
             * <li>row - The tree node index</li>
             * <li>column - The treegrid column index</li>
             * @param {Node} node
             * @param {Object} e An edit event (see above for description)
             */
            'cellclick',
            /**
             * @event celldblclick
             * Fires when a cell is double clicked. The edit event object has the following properties <br />
             * <ul style="padding:5px;padding-left:16px;">
             * <li>tree - This tree</li>
             * <li>field - The field name being edited</li>
             * <li>value - The value for the field being edited.</li>
             * <li>row - The tree node index</li>
             * <li>column - The treegrid column index</li>
             * @param {Node} node
             * @param {Object} e An edit event (see above for description)
             */
            'celldblclick',
            /**
             * @event beforeedit
             * Fires before cell editing is triggered. The edit event object has the following properties <br />
             * <ul style="padding:5px;padding-left:16px;">
             * <li>tree - This tree</li>
             * <li>node - The node being edited</li>
             * <li>field - The field name being edited</li>
             * <li>value - The value for the field being edited.</li>
             * <li>row - The grid row index</li>
             * <li>column - The grid column index</li>
             * <li>cancel - Set this to true to cancel the edit or return false from your handler.</li>
             * </ul>
             * @param {Object} e An edit event (see above for description)
             */
            "beforeedit",
            /**
             * @event afteredit
             * Fires after a cell is edited. The edit event object has the following properties <br />
             * <ul style="padding:5px;padding-left:16px;">
             * <li>tree - This tree</li>
             * <li>node - The node being edited</li>
             * <li>field - The field name being edited</li>
             * <li>value - The value being set</li>
             * <li>originalValue - The original value for the field, before the edit.</li>
             * <li>row - The grid row index</li>
             * <li>column - The grid column index</li>
             * </ul>
             *
             * <pre><code>
             tree.on('afteredit', afterEdit, this );

             function afterEdit(e) {
    // execute an XHR to send/commit data to the server, in callback do (if successful):
};
             * </code></pre>
             * @param {Object} e An edit event (see above for description)
             */
            "afteredit",
            /**
             * @event validateedit
             * Fires after a cell is edited, but before the value is set in the node. Return false
             * to cancel the change. The edit event object has the following properties <br />
             * <ul style="padding:5px;padding-left:16px;">
             * <li>tree - This tree</li>
             * <li>node - The node being edited</li>
             * <li>field - The field name being edited</li>
             * <li>value - The value being set</li>
             * <li>originalValue - The original value for the field, before the edit.</li>
             * <li>row - The grid row index</li>
             * <li>column - The grid column index</li>
             * <li>cancel - Set this to true to cancel the edit or return false from your handler.</li>
             * </ul>
             * Usage example showing how to remove the red triangle (dirty record indicator) from some
             * records (not all).  By observing the tree's validateedit event, it can be cancelled if
             * the edit occurs on a targeted row (for example) and then setting the field's new value
             * in the Record directly:
             * <pre><code>
             tree.on('validateedit', function(e) {
  var myTargetCol = 4;

  if (e.column == myTargetCol && (e.value != 'option1' && e.value != 'option2')) {
    e.cancel = true;
    var colName = e.tree.getColumnAt(e.column);
    alert(colName.header+' has been set with an invalid value, please enter \'option1\' or \'option2\'');
  }
});
             * </code></pre>
             * @param {Object} e An edit event (see above for description)
             */
            "validateedit"
        );

        Ext.ux.tree.EditorGrid.superclass.initComponent.call(this);

        if (Ext.tree.XmlTreeSerializer || Ext.tree.JsonTreeSerializer) {
            this.serializer = {};
            if (Ext.tree.XmlTreeSerializer) this.serializer.xml = new Ext.tree.XmlTreeSerializer(this);
            if (Ext.tree.JsonTreeSerializer) this.serializer.json = new Ext.tree.JsonTreeSerializer(this);

            this.toString = function (output) {
                if (this.serializer === false) return false;
                this.getRootNode().expand(true);
                if (output && this.serializer[output]) var serialized = this.serializer[output].toString();
                else {
                    if (this.serializer.json) var serialized = this.serializer.json.toString();
                    else var serialized = this.serializer.xml.toString();
                }
                this.getRootNode().collapse(true);
                return serialized;
            };

            this.getSerializer = function (output) {
                if (output && this.serializer[output]) return this.serializer[output];
                else {
                    if (this.serializer.json) return this.serializer.json;
                    else return this.serializer.xml;
                }
                return false;
            };
        }
        else {
            this.serializer = false;
            this.toString = function (output) {
                return false;
            };
            this.getSerializer = function (output) {
                return false;
            };
        }

        //this.on('beforeclick', function(node,e) { return false; }); // PREVENT TREE SCROLL BUG ON NODE CLICK
        if (!this.expandOnDblClick) this.on('beforedblclick', function (node, e) {
            return false;
        }); // PREVENT EXPAND ON DOUBLE CLICK
    },

    onRender: function () {
        Ext.ux.tree.EditorGrid.superclass.onRender.apply(this, arguments);

        this.colRe = new RegExp('x-treegrid-([^\\s]+)', '');
    },

    initEvents: function () {
        Ext.ux.tree.EditorGrid.superclass.initEvents.apply(this, arguments);

        var tree = this;

        if (this.loadMask) {
            var loader = this.getLoader();
            loader.on('beforeload', function (loader, node, response) {
                try {
                    tree.loadMask.show();
                }
                catch (e) {
                    tree.loadMask = new Ext.LoadMask(tree.getTreeEl(), tree.loadMask);
                    tree.loadMask.show();
                }
            });

            loader.on('load', function (loader, node, response) {
                tree.loadMask.hide();
            });
        }

        this.mon(this.innerCt, {
            scope:    this,
            click:    this.onClick,
            dblclick: this.onDblClick
        });
    },

    /**
     * @private
     * Each TreeGrid has its own private flyweight, accessed through this method
     */
    fly:          function (el) {
        if (!this._flyweight) {
            this._flyweight = new Ext.Element.Flyweight(document.body);
        }
        this._flyweight.dom = el;
        return this._flyweight;
    },

    // private
    processEvent: function (name, e) {
        var rtarget = e.event.getTarget(),
            htarget = e.event.getTarget('.x-treegrid-col'),
            tree = this,
            header = this.findHeaderIndex(htarget),
            row, cell, col, body;

        row = this.findRowIndex(rtarget);
        if (row !== false) {
            cell = this.findHeaderIndex(htarget);
            if (cell !== false) {
                col = this.getColumnAt(cell);
                var n = this.getNodeById(this.findRowId(rtarget));
                if (n) {
                    var field = this.getDataIndex(cell),
                        o = {
                            tree:   this,
                            field:  field,
                            value:  n.get(field),
                            row:    row,
                            column: cell,
                            cancel: false
                        };

                    if (name === 'dblclick') this.startEditing(row, cell, {col: col, event: e.event, ht: htarget, rt: rtarget});
                    if (this.fireEvent('cell' + name, n, o) !== false) {
                        //if (!col || (col.processEvent && (col.processEvent(name, e, this, row, cell) !== false))) {
                        //	this.fireEvent('row' + name, this, row, e);
                        //}
                    }
                }
            }
        }

    },

    // private
    onClick:      function (e, el, opt) {
        this.processEvent('click', {event: e, el: el, opt: opt});
    },

    // private
    onDblClick:   function (e, el, opt) {
        this.processEvent('dblclick', {event: e, el: el, opt: opt});
    },

    /**
     * Starts editing the specified for the specified row/column
     * @param {Number} rowIndex
     * @param {Number} colIndex
     */
    startEditing:   function (rIndex, cIndex, opt) {
        var nodeId = this.findRowId(opt.rt);
        switch (cIndex) {
            case 0:
                var parentTarget = (opt.rt.className != 'x-treegrid-col') ? opt.event.getTarget('.x-treegrid-col') : opt.rt;
                for (var i = 0; i < parentTarget.childNodes.length; i++) {
                    if (parentTarget.childNodes[i].className == 'x-tree-node-anchor' && parentTarget.childNodes[i].localName == 'a') {
                        opt.rt = parentTarget.childNodes[i];
                        break;
                    }
                }
                break;
            default:
                if (opt.rt.className == 'x-treegrid-col') opt.rt = opt.rt.childNodes[0];
                break;
        }
        this.stopEditing();
        if (this.isCellEditable(cIndex, rIndex)) {
            var n = this.getNodeById(nodeId),
                field = this.getDataIndex(cIndex),
                e = {
                    tree:   this,
                    node:   n,
                    field:  field,
                    value:  n.attributes[field],
                    row:    rIndex,
                    column: cIndex,
                    cancel: false
                };
            if (this.fireEvent("beforeedit", e) !== false && !e.cancel) {
                this.editing = true;
                var ed = this.getCellEditor(cIndex, rIndex);
                if (!ed) {
                    return;
                }
                if (!ed.rendered) {
                    ed.on({
                        scope:      this,
                        render:     {
                            fn:     function (c) {
                                c.field.focus(false, true);
                            },
                            single: true,
                            scope:  this
                        },
                        specialkey: function (field, e) {
                            this.onEditorKey(field, e, {
                                row:  rIndex,
                                col:  cIndex,
                                node: n
                            });
                        },
                        complete:   this.onEditComplete,
                        canceledit: this.stopEditing.createDelegate(this, [true])
                    });
                }
                Ext.apply(ed, {
                    row:    rIndex,
                    col:    cIndex,
                    target: opt.rt,
                    node:   n
                });
                this.lastEdit = { row: rIndex, col: cIndex };
                this.activeEditor = ed;
                // Set the selectSameEditor flag if we are reusing the same editor again and
                // need to prevent the editor from firing onBlur on itself.
                ed.selectSameEditor = false; //(this.activeEditor == this.lastActiveEditor);
                var v = this.preEditValue(n, field);
                ed.startEdit(opt.rt, Ext.isDefined(v) ? v : '');

                // Clear the selectSameEditor flag
                (function () {
                    delete ed.selectSameEditor;
                }).defer(50);
                //ed.startEdit(opt.rt);

            }
        }
    },

    // private
    onEditComplete: function (ed, value, startValue) {
        this.editing = false;
        this.lastActiveEditor = this.activeEditor;
        this.activeEditor = null;

        var n = ed.node,
            field = this.getDataIndex(ed.col);
        value = this.postEditValue(value, startValue, n, field);
        if (this.forceValidation === true || String(value) !== String(startValue)) {
            var e = {
                tree:          this,
                node:          n,
                field:         field,
                originalValue: startValue,
                value:         value,
                row:           ed.row,
                column:        ed.col,
                cancel:        false
            };
            if (this.fireEvent("validateedit", e) !== false && !e.cancel && String(value) !== String(startValue)) {
                n.set(field, e.value);
                delete e.cancel;
                this.markDirty(n, ed.col);
                this.fireEvent("afteredit", e);
            }
        }
    },

    /**
     * Added by Rob Callahan on 7/23/2012 to mark edited cells as dirty
     */
    markDirty: function (node, col) {
        var td, el;

        if (typeof node.el === "undefined") {
            td = node.ui.elNode.childNodes[col];
            el = new Ext.Element(td);
            node.ui.el = el;
        }
        node.ui.el.addClass('x-grid3-dirty-cell');
    },

    markClean: function (node, col) {
        var td, el;

        if (typeof node.el === "undefined") {
            td = node.ui.elNode.childNodes[col];
            el = new Ext.Element(td);
            node.ui.el = el;
        }
        node.ui.el.removeClass('x-grid3-dirty-cell');
    },


    /**
     * Returns the tree's column config object.
     * @return {Object} The column config object
     */
    getColumnModel: function () {
        return this.columns;
    },

    /**
     * Returns the editor defined for the cell/column.
     * @param {Number} colIndex The column index
     * @param {Number} rowIndex The row index
     * @return {Ext.Editor} The {@link Ext.Editor Editor} that was created to wrap
     * the {@link Ext.form.Field Field} used to edit the cell.
     */
    getCellEditor: function (colIndex, rowIndex) {
        var formField = this.columns[colIndex].editor.cloneConfig();
        var cfg = {
            shadow:          false,
            completeOnEnter: true,
            cancelOnEsc:     true,
            updateEl:        false,
            ignoreNoChange:  true
        };
        if (!formField) return false;
        return new Ext.grid.GridEditor(Ext.apply({ field: formField }, cfg));
    },

    /**
     * Sets if a column is editable.
     * @param {Number} col The column index
     * @param {Boolean} editable True if the column is editable
     */
    setEditable:   function (col, editable) {
        this.columns[col].editable = editable;
    },

    // private
    preEditValue:  function (n, field) {
        var value = n.attributes[field];
        return this.autoEncode && Ext.isString(value) ? Ext.util.Format.htmlDecode(value) : value;
    },

    // private
    postEditValue: function (value, originalValue, n, field) {
        return this.autoEncode && Ext.isString(value) ? Ext.util.Format.htmlEncode(value) : value;
    },

    /**
     * Stops any active editing
     * @param {Boolean} cancel (optional) True to cancel any changes
     */
    stopEditing: function (cancel) {
        if (this.editing) {
            // Store the lastActiveEditor to check if it is changing
            var ae = this.lastActiveEditor = this.activeEditor;
            if (ae) {
                ae[cancel === true ? 'cancelEdit' : 'completeEdit']();
            }
            this.activeEditor = null;
        }
        this.editing = false;
    },

    /**
     * Returns true if the cell is editable.
     * @param {Number} colIndex The column index
     * @param {Number} rowIndex The row index
     * @return {Boolean}
     */
    isCellEditable: function (colIndex, rowIndex) {
        var c = this.columns[colIndex],
            ed = c.editable;

        //force boolean
        return !!(ed || (!Ext.isDefined(ed) && c.editor));
    },

    /**
     * Returns the dataIndex for the specified column.
     <pre><code>
     // Get field name for the column
     var fieldName = tree.getDataIndex(columnIndex);
     </code></pre>
     * @param {Number} col The column index
     * @return {String} The column's dataIndex
     */
    getDataIndex: function (col) {
        return this.columns[col].dataIndex;
    },

    /**
     * Returns the index for a specified column id.
     * @param {String} id The column id
     * @return {Number} the index, or -1 if not found
     */
    getIndexById: function (id) {
        for (var i = 0, len = this.columns.length; i < len; i++) {
            if (this.columns[i].id == id) {
                return i;
            }
        }
        return -1;
    },

    /**
     * Returns the id of the column at the specified index.
     * @param {Number} index The column index
     * @return {String} the id
     */
    getColumnId: function (index) {
        return this.columns[index].id;
    },

    /**
     * Returns the column at the specified index.
     * @param {Number} index The column index
     * @return {Object} the column
     */
    getColumnAt:    function (index) {
        return this.columns[index];
    },

    // private
    findHeaderCell: function (el) {
        var cell = this.findCell(el);
        return cell && this.fly(cell).hasClass(this.hdCls) ? cell : null;
    },

    /**
     * Return the HtmlElement representing the tree node which contains the passed element.
     * @param {HTMLElement} el The target HTMLElement
     * @return {HTMLElement} The node element, or null if the target element is not within a node of this TreeGrid.
     */
    findRow: function (el) {
        if (!el) {
            return false;
        }
        return this.fly(el).findParent(this.rowSelector, this.rowSelectorDepth);
    },

    /**
     * Return the index of the tree node which contains the passed HTMLElement.
     * See also {@link #findCellIndex}
     * @param {HTMLElement} el The target HTMLElement
     * @return {Number} The node index, or <b>false</b> if the target element is not within a node of this TreeGrid.
     */
    findRowIndex: function (el) {
        var row = this.findRow(el);
        return row ? row.rowIndex : false;
    },

    /**
     * Return the index of the tree node which contains the passed HTMLElement.
     * See also {@link #findCellIndex}
     * @param {HTMLElement} el The target HTMLElement
     * @return {Number} The node index, or <b>false</b> if the target element is not within a node of this TreeGrid.
     */
    findRowId: function (el) {
        var row = this.findRow(el),
            foundId = false,
            attr = row.attributes;

        for (var p = 0; p < attr.length; p++) {
            if (attr[p].name == 'ext:tree-node-id') {
                foundId = attr[p].value;
                break;
            }
        }
        return foundId; //row.attributes[1] ? row.attributes[1].value : false;
    },

    // private
    findCell:  function (el) {
        if (!el) {
            return false;
        }
        return this.fly(el).findParent(this.cellSelector, this.cellSelectorDepth);
    },

    /**
     * <p>Return the index of the tree column which contains the passed HTMLElement.</p>
     * See also {@link #findRowIndex}
     * @param {HTMLElement} el The target element
     * @return {Number} The column index, or <b>false</b> if the target element is not within a node of this TreeGrid.
     */
    findCellIndex: function (el, requiredCls) {
        var cell = this.findCell(el),
            hasCls;

        if (cell) {
            hasCls = this.fly(cell).hasClass(requiredCls);
            if (!requiredCls || hasCls) {
                return this.getCellIndex(cell);
            }
        }
        return false;
    },

    // private
    getCellIndex:  function (el) {
        if (el) {
            var match = el.className.match(this.colRe);

            if (match && match[1]) {
                return this.getIndexById(match[1]);
            }
        }
        return false;
    },

    // private
    syncScroll:    function () {
        Ext.ux.tree.EditorGrid.superclass.syncScroll.call(this);
        this.stopEditing();
    },

    /** @ignore */
    onEditorKey: function (field, e, o) {
        if (!e.isNavKeyPress()) {
            return;
        }

        var next = o.node.nextSibling;
        if (!next) return;

        var target = next.ui.elNode.childNodes[o.col];

        var k = e.getKey(),
            tree = this,
            target = next.ui.elNode.childNodes[o.col],
            row = o.row + 1,
            cell = o.col,
            col = this.getColumnAt(cell);

        switch (k) {
            case e.ENTER:
                if (!tree.editing) {
                    tree.startEditing(row, cell, {col: col, event: e, ht: target, rt: target});
                    return;
                }
                break;
        }
    }/*,

     updateColumnWidths : function() {
     var cols = this.columns,
     colCount = cols.length,
     groups = this.outerCt.query('colgroup'),
     groupCount = groups.length,
     colWidth, autoColWidth, c, g, i, j;

     for(i = 0; i<colCount; i++) {
     colWidth += Number(cols[i]);
     }

     for(i = 0; i<colCount; i++) {
     c = cols[i];
     //if (c.dataIndex == this.autoExpandColumn) {
     //	autoColWidth = 15+(Number(colWidth) - Number(c.width));
     //	if (autoColWidth >= this.innerCt.offsetWidth) c.width = 15;
     //	else c.width = Number(this.innerCt.offsetWidth) - Number(autoColWidth);
     //}
     for(j = 0; j<groupCount; j++) {
     g = groups[j];
     g.childNodes[i].style.width = (c.hidden ? 0 : c.width) + 'px';
     }
     }

     for(i = 0, groups = this.innerHd.query('td'), len = groups.length; i<len; i++) {
     c = Ext.fly(groups[i]);
     if(cols[i] && cols[i].hidden) {
     c.addClass('x-treegrid-hd-hidden');
     }
     else {
     c.removeClass('x-treegrid-hd-hidden');
     }
     }

     var tcw = this.getTotalColumnWidth();
     Ext.fly(this.innerHd.dom.firstChild).setWidth(tcw + (this.scrollOffset || 0));
     this.outerCt.select('table').setWidth(tcw);
     this.syncHeaderScroll();
     }*/

});

Ext.reg('editortreegrid', Ext.ux.tree.EditorGrid);