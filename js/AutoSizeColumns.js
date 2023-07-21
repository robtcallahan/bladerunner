/*******************************************************************************
 *
 * $Id: AutoSizeColumns.js 73204 2013-03-14 18:03:31Z rcallaha $
 * $Date: 2013-03-14 14:03:31 -0400 (Thu, 14 Mar 2013) $
 * $Author: rcallaha $
 * $Revision: 73204 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/AutoSizeColumns.js $
 *
 *******************************************************************************
 */

Ext.ns('Ext.ux.grid');
Ext.ux.grid.AutoSizeColumns = function (config) {
    Ext.apply(this, config);
};
Ext.extend(Ext.ux.grid.AutoSizeColumns, Object, {
    cellPadding:   8,
    init:          function (grid) {
        grid.getView().onHeaderClick = this.onHeaderClick;
        grid.on('headerdblclick', function (grid, colIndex, e) {
            var h = grid.getView().getHeaderCell(colIndex);
            if (h.style.cursor != 'col-resize') {
                return;
            }
            var xy = Ext.lib.Dom.getXY(h);
            if (e.getXY()[0] - xy[0] <= 5) {
                colIndex--;
                h = grid.getView().getHeaderCell(colIndex);
            }
            if (grid.getColumnModel().isFixed(colIndex) || grid.getColumnModel().isHidden(colIndex)) {
                return;
            }
            var hi = h.firstChild;
            hi.style.width = '0px';
            var w = hi.scrollWidth;
            hi.style.width = 'auto';
            for (var r = 0, len = grid.getStore().getCount(); r < len; r++) {
                var ci = grid.getView().getCell(r, colIndex).firstChild;
                ci.style.width = '0px';
                w = Math.max(w, ci.scrollWidth);
                ci.style.width = 'auto';
            }
            w += this.cellPadding;
            grid.getView().onColumnSplitterMoved(colIndex, w);
        }, this);
    },
    onHeaderClick: function (g, index) {
        if (this.headersDisabled || !this.cm.isSortable(index)) {
            return;
        }
        var h = this.getHeaderCell(index);
        if (h.style.cursor == 'col-resize') {
            return;
        }
        g.stopEditing(true);
        g.store.sort(this.cm.getDataIndex(index));
    }
});

