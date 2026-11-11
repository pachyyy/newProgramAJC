/*!
 * report-export.js — shared Excel (.xlsx) and CSV export for the report pages
 * (masterreport*.blade.php). Replaces the per-view "HTML disguised as .xls" hack.
 *
 * Source of truth is the RENDERED <table> (so custom layouts that override
 * setRowTable()/setRowHeader() export exactly what is on screen), but:
 *   - right-aligned numeric cells are converted back into REAL numbers
 *     (handles both "1,234.56" from format_number() and "1.234,56" from
 *     toLocaleString('id-ID'); codes / leading-zero values stay text),
 *   - every column is auto-fitted to its content (capped at MAX_COL_WIDTH),
 *   - colspan/rowspan become merged cells,
 *   - header row is styled, borders applied, header row frozen.
 *
 * Needs: public/js/exceljs.min.js (global ExcelJS) for .xlsx. CSV has no dependency.
 *
 * API:
 *   ReportExport.toXlsx(tableId, { baseName, customName, sheetName })
 *   ReportExport.toCsv (tableId, { baseName, customName })
 *     baseName   — file name prefix, normally $akses['xlsfilename']; "_YYYYMMDD_HHMMSS" is appended
 *     customName — if set, used as the file name as-is (replaces baseName + timestamp)
 */
(function (window, document) {
  'use strict';

  if (window.ReportExport) { return; }

  var MAX_COL_WIDTH = 60;
  var MIN_COL_WIDTH = 6;
  var MAX_SPAN      = 200;

  var SKIP_SELECTOR = 'button, script, style, select, input, textarea, [data-noexport], .rt-colmenu, .rt-drop-menu';

  /* ------------------------------------------------------------------ */
  /* small helpers                                                       */
  /* ------------------------------------------------------------------ */

  function notify(msg) {
    if (window.alertify && typeof window.alertify.alert === 'function') {
      try { window.alertify.alert(msg); return; } catch (e) { /* fall through */ }
    }
    window.alert(msg);
  }

  function pad2(n) { return ('0' + n).slice(-2); }

  function stamp() {
    var d = new Date();
    var date = (typeof window.getDateNow === 'function')
      ? window.getDateNow('')
      : d.getFullYear() + pad2(d.getMonth() + 1) + pad2(d.getDate());
    var time = (typeof window.getTimeNow === 'function')
      ? window.getTimeNow('')
      : pad2(d.getHours()) + pad2(d.getMinutes()) + pad2(d.getSeconds());
    return date + '_' + time;
  }

  function safeFileName(name) {
    return String(name).replace(/[\\\/:*?"<>|]+/g, '_').trim();
  }

  function buildFileName(opts, ext) {
    var name;
    if (opts.customName) {
      name = opts.customName;
    } else {
      name = (opts.baseName || 'report') + '_' + stamp();
    }
    return safeFileName(name) + '.' + ext;
  }

  function download(blob, fileName) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    setTimeout(function () {
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }, 1500);
  }

  // run fn after the browser had a chance to paint the "busy" cursor
  function withBusy(fn) {
    document.body.style.cursor = 'progress';
    setTimeout(function () {
      var done = function () { document.body.style.cursor = ''; };
      try {
        var p = fn();
        if (p && typeof p.then === 'function') {
          p.then(done, function (err) { done(); fail(err); });
        } else {
          done();
        }
      } catch (err) {
        done();
        fail(err);
      }
    }, 30);
  }

  function fail(err) {
    if (window.console) { console.error(err); }
    notify('Export gagal: ' + (err && err.message ? err.message : err));
  }

  /* ------------------------------------------------------------------ */
  /* reading the DOM table                                               */
  /* ------------------------------------------------------------------ */

  function isHidden(el) {
    return !!(el.style && el.style.display === 'none');
  }

  function collectText(node, out) {
    if (node.nodeType === 3) { out.push(node.nodeValue); return; }
    if (node.nodeType !== 1) { return; }
    if (node.tagName === 'BR') { out.push('\n'); return; }
    if (node.matches(SKIP_SELECTOR) || isHidden(node)) { return; }
    var kids = node.childNodes;
    for (var i = 0; i < kids.length; i++) { collectText(kids[i], out); }
  }

  function cellText(cell) {
    var out = [];
    collectText(cell, out);
    return out.join('')
      .replace(/ /g, ' ')
      .replace(/[ \t\r\f\v]+/g, ' ')
      .replace(/ ?\n ?/g, '\n')
      .trim();
  }

  function isRightAligned(cell) {
    var ta = cell.style.textAlign;
    if (ta === 'right' || ta === 'end') { return true; }
    var cl = cell.classList;
    return cl.contains('text-right') || cl.contains('text-end') ||
           cl.contains('cellcompact-right') || cl.contains('num');
  }

  function horizontalAlign(cell, isTh) {
    var ta = cell.style.textAlign;
    if (ta === 'center' || ta === 'left' || ta === 'right') { return ta; }
    var cl = cell.classList;
    if (cl.contains('text-center')) { return 'center'; }
    if (cl.contains('text-right') || cl.contains('cellcompact-right') || cl.contains('num')) { return 'right'; }
    return isTh ? 'center' : 'left';
  }

  function isBoldStyle(el) {
    var fw = el.style && el.style.fontWeight;
    return fw === 'bold' || fw === 'bolder' || /^[6-9]00$/.test(fw || '');
  }

  // "rgb(222, 226, 230)" -> "FFDEE2E6"; null for transparent / unknown
  function toArgb(css) {
    if (!css) { return null; }
    var m = /^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)$/.exec(css);
    if (!m) { return null; }
    if (m[4] !== undefined && parseFloat(m[4]) === 0) { return null; }
    var hex = function (v) { return ('0' + parseInt(v, 10).toString(16)).slice(-2); };
    return ('FF' + hex(m[1]) + hex(m[2]) + hex(m[3])).toUpperCase();
  }

  /**
   * Flatten the table into cell records, honouring colspan/rowspan.
   * Returns { cells:[], rows:[], colCount }
   */
  function readTable(table) {
    var trs = table.rows;
    var occupied = [];          // occupied[outRow][col] = true (covered by an earlier rowspan/colspan)
    var cells = [];
    var rows = [];
    var colCount = 0;
    var outRow = 0;

    for (var r = 0; r < trs.length; r++) {
      var tr = trs[r];
      if (isHidden(tr)) { continue; }

      var section = tr.parentNode && tr.parentNode.tagName;
      var domCells = tr.cells;
      var allTh = domCells.length > 0;
      for (var k = 0; k < domCells.length; k++) {
        if (domCells[k].tagName !== 'TH') { allTh = false; break; }
      }

      var info = {
        index: outRow,
        inThead: section === 'THEAD',
        isHeader: tr.classList.contains('tabel_header_kolom') || (allTh && domCells.length >= 2),
        isTitle: false,
        rowBold: isBoldStyle(tr),
        rowBg: toArgb(tr.style.backgroundColor)
      };
      rows[outRow] = info;

      var col = 0;
      for (var i = 0; i < domCells.length; i++) {
        var cell = domCells[i];
        if (isHidden(cell)) { continue; }

        occupied[outRow] = occupied[outRow] || [];
        while (occupied[outRow][col]) { col++; }

        var cs = Math.min(Math.max(1, cell.colSpan || 1), MAX_SPAN);
        var rs = Math.min(Math.max(1, cell.rowSpan || 1), MAX_SPAN);
        var isTh = cell.tagName === 'TH';

        for (var rr = 0; rr < rs; rr++) {
          occupied[outRow + rr] = occupied[outRow + rr] || [];
          for (var cc = 0; cc < cs; cc++) { occupied[outRow + rr][col + cc] = true; }
        }

        var bold = info.rowBold || isBoldStyle(cell) || isTh ||
                   (cell.children.length === 1 && /^(B|STRONG)$/.test(cell.firstElementChild.tagName));

        cells.push({
          r: outRow, c: col, cs: cs, rs: rs,
          text: cellText(cell),
          isTh: isTh,
          right: isRightAligned(cell),
          align: horizontalAlign(cell, isTh),
          bold: bold,
          bg: toArgb(cell.style.backgroundColor) || info.rowBg,
          num: null,              // filled by markNumbers()
          decimals: 0
        });

        if (col + cs > colCount) { colCount = col + cs; }
        col += cs;
      }
      outRow++;
    }

    // title rows = everything before the first column-header row
    var firstHeader = -1;
    for (var x = 0; x < rows.length; x++) {
      if (rows[x] && rows[x].isHeader) { firstHeader = x; break; }
    }
    for (var y = 0; y < firstHeader; y++) { if (rows[y]) { rows[y].isTitle = true; } }

    // last row of the first contiguous run of header rows (-> freeze panes)
    var lastHeader = -1;
    if (firstHeader >= 0) {
      lastHeader = firstHeader;
      while (rows[lastHeader + 1] && rows[lastHeader + 1].isHeader) { lastHeader++; }
    }

    return { cells: cells, rows: rows, colCount: colCount, firstHeader: firstHeader, lastHeader: lastHeader };
  }

  /* ------------------------------------------------------------------ */
  /* number detection                                                    */
  /* ------------------------------------------------------------------ */

  function count(str, ch) {
    var n = 0;
    for (var i = 0; i < str.length; i++) { if (str.charAt(i) === ch) { n++; } }
    return n;
  }

  function validGroups(str, sep) {
    var parts = str.split(sep);
    if (!/^\d{1,3}$/.test(parts[0])) { return false; }
    for (var i = 1; i < parts.length; i++) {
      if (!/^\d{3}$/.test(parts[i])) { return false; }
    }
    return true;
  }

  // Strip sign/parentheses. Returns { neg, body } or null when text is not number-shaped.
  function splitSign(text) {
    var s = text.replace(/\s+/g, '');
    var neg = false;
    if (/^\(.*\)$/.test(s)) { neg = true; s = s.slice(1, -1); }
    if (s.charAt(0) === '-') { neg = true; s = s.slice(1); }
    else if (s.charAt(0) === '+') { s = s.slice(1); }
    if (!/^\d[\d.,]*$/.test(s) || /[.,]$/.test(s)) { return null; }
    return { neg: neg, body: s };
  }

  /**
   * decimalChar: '.' | ',' | null — what the table as a whole uses as decimal
   * separator (null when it can't be told).
   */
  function parseNumber(text, decimalChar) {
    var sp = splitSign(text);
    if (!sp) { return null; }
    var s = sp.body;
    var dots = count(s, '.'), commas = count(s, ',');
    var intPart = s, fracPart = '';

    if (dots && commas) {
      var last = Math.max(s.lastIndexOf('.'), s.lastIndexOf(','));
      var decCh = s.charAt(last);
      var thouCh = decCh === '.' ? ',' : '.';
      if (count(s, decCh) !== 1) { return null; }
      var head = s.slice(0, last);
      if (!validGroups(head, thouCh)) { return null; }
      intPart = head.split(thouCh).join('');
      fracPart = s.slice(last + 1);
    } else if (dots || commas) {
      var ch = dots ? '.' : ',';
      var n = dots || commas;
      var idx = s.lastIndexOf(ch);
      var before = s.slice(0, idx);
      var after = s.length - idx - 1;
      var asThousands;

      if (n > 1) {
        asThousands = true;
      } else if (decimalChar) {
        asThousands = (ch !== decimalChar);
      } else {
        asThousands = (after === 3 && /^[1-9]\d{0,2}$/.test(before));
      }

      // A page can mix formats (e.g. data rows "1,234.50" + id-ID summary "0,00"). If the
      // table-wide guess says "thousands" but the grouping is invalid, read it as a decimal.
      if (asThousands && n === 1 && !validGroups(s, ch)) { asThousands = false; }

      if (asThousands) {
        if (!validGroups(s, ch)) { return null; }
        intPart = s.split(ch).join('');
      } else {
        intPart = before;
        fracPart = s.slice(idx + 1);
      }
    }

    // codes like 007 / 0123 are text, not numbers
    if (intPart.length > 1 && intPart.charAt(0) === '0') { return null; }
    // beyond double precision (barcodes, NPWP, account numbers...) stay text
    if (intPart.length + fracPart.length > 15) { return null; }

    var num = parseFloat(intPart + (fracPart ? '.' + fracPart : ''));
    if (!isFinite(num)) { return null; }
    return { num: sp.neg ? -num : num, decimals: fracPart.length };
  }

  // Vote for the table's decimal separator using unambiguous numbers only.
  function detectDecimalChar(cells) {
    var dotVotes = 0, commaVotes = 0, seen = 0;
    for (var i = 0; i < cells.length && seen < 3000; i++) {
      var cell = cells[i];
      if (!cell.right || !cell.text) { continue; }
      var sp = splitSign(cell.text);
      if (!sp) { continue; }
      seen++;
      var s = sp.body;
      var dots = count(s, '.'), commas = count(s, ',');
      if (dots && commas) {
        if (s.lastIndexOf('.') > s.lastIndexOf(',')) { dotVotes++; } else { commaVotes++; }
      } else if (dots === 1 || commas === 1) {
        var ch = dots ? '.' : ',';
        var idx = s.indexOf(ch);
        var after = s.length - idx - 1;
        var before = s.slice(0, idx);
        if (after !== 3 || before === '0') {
          if (ch === '.') { dotVotes++; } else { commaVotes++; }
        }
      }
    }
    if (dotVotes > commaVotes) { return '.'; }
    if (commaVotes > dotVotes) { return ','; }
    return null;
  }

  function markNumbers(cells) {
    var decimalChar = detectDecimalChar(cells);
    for (var i = 0; i < cells.length; i++) {
      var cell = cells[i];
      if (!cell.right || !cell.text || cell.isTh) { continue; }
      var parsed = parseNumber(cell.text, decimalChar);
      if (parsed) { cell.num = parsed.num; cell.decimals = parsed.decimals; }
    }
  }

  /* ------------------------------------------------------------------ */
  /* column widths                                                       */
  /* ------------------------------------------------------------------ */

  function computeWidths(model) {
    var widths = [];
    for (var c = 0; c < model.colCount; c++) { widths[c] = MIN_COL_WIDTH; }

    model.cells.forEach(function (cell) {
      if (cell.cs !== 1 || model.rows[cell.r].isTitle || !cell.text) { return; }
      var longest = 0;
      cell.text.split('\n').forEach(function (line) {
        if (line.length > longest) { longest = line.length; }
      });
      var w = longest * (cell.bold ? 1.15 : 1.05) + 2;
      if (w > widths[cell.c]) { widths[cell.c] = w; }
    });

    return widths.map(function (w) { return Math.min(Math.max(w, MIN_COL_WIDTH), MAX_COL_WIDTH); });
  }

  /* ------------------------------------------------------------------ */
  /* .xlsx                                                               */
  /* ------------------------------------------------------------------ */

  var THIN = { style: 'thin', color: { argb: 'FF000000' } };
  var BORDER = { top: THIN, left: THIN, bottom: THIN, right: THIN };
  var FILL_HEADER = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD9D9D9' } };
  var FONT_PLAIN = { name: 'Calibri', size: 11 };
  var FONT_BOLD  = { name: 'Calibri', size: 11, bold: true };

  function numFmt(decimals) {
    return decimals > 0 ? '#,##0.' + new Array(decimals + 1).join('0') : '#,##0';
  }

  function sheetName(opts) {
    var n = String(opts.sheetName || opts.baseName || 'Report').replace(/[\\\/?*\[\]:]+/g, ' ').trim();
    return (n || 'Report').slice(0, 31);
  }

  function toXlsx(tableId, opts) {
    opts = opts || {};

    if (typeof window.ExcelJS === 'undefined') {
      notify('Library ExcelJS belum termuat (public/js/exceljs.min.js). Export Excel dibatalkan.');
      return;
    }

    var table = document.getElementById(tableId);
    if (!table || !table.rows.length) { notify('Tidak ada data untuk di-export.'); return; }

    withBusy(function () {
      var model = readTable(table);
      if (!model.cells.length) { notify('Tidak ada data untuk di-export.'); return; }
      markNumbers(model.cells);
      var widths = computeWidths(model);

      var wb = new window.ExcelJS.Workbook();
      wb.creator = 'AJC';
      wb.created = new Date();
      var ws = wb.addWorksheet(sheetName(opts));

      ws.columns = widths.map(function (w) { return { width: w }; });

      var alignCache = {};
      function alignment(horizontal, wrap) {
        var key = horizontal + '|' + (wrap ? 1 : 0);
        return alignCache[key] || (alignCache[key] = { horizontal: horizontal, vertical: 'middle', wrapText: !!wrap });
      }
      var fillCache = {};
      function fill(argb) {
        return fillCache[argb] || (fillCache[argb] = { type: 'pattern', pattern: 'solid', fgColor: { argb: argb } });
      }

      var titleRowLines = {};

      model.cells.forEach(function (cell) {
        var info = model.rows[cell.r];
        var x = ws.getCell(cell.r + 1, cell.c + 1);

        if (cell.num !== null) {
          x.value = cell.num;
          x.numFmt = numFmt(cell.decimals);
        } else {
          x.value = cell.text;      // plain string: never interpreted as a formula by ExcelJS
        }

        x.font = cell.bold ? FONT_BOLD : FONT_PLAIN;

        var lines = cell.text.split('\n').length;
        var wrap = lines > 1 || cell.text.length > MAX_COL_WIDTH || info.isHeader;

        if (info.isTitle) {
          x.alignment = alignment('left', lines > 1);
          if (lines > 1) { titleRowLines[cell.r] = Math.max(titleRowLines[cell.r] || 1, lines); }
        } else if (info.isHeader) {
          x.alignment = alignment('center', true);
          x.border = BORDER;
          x.fill = FILL_HEADER;
        } else {
          x.alignment = alignment(cell.num !== null ? 'right' : cell.align, wrap);
          x.border = BORDER;
          if (cell.bg) { x.fill = fill(cell.bg); }
        }
      });

      // merges (colspan / rowspan) — applied after values so the master cell keeps its value
      model.cells.forEach(function (cell) {
        if (cell.cs > 1 || cell.rs > 1) {
          ws.mergeCells(cell.r + 1, cell.c + 1, cell.r + cell.rs, cell.c + cell.cs);
          if (!model.rows[cell.r].isTitle) {
            // keep borders on the whole merged block
            for (var rr = 0; rr < cell.rs; rr++) {
              for (var cc = 0; cc < cell.cs; cc++) {
                ws.getCell(cell.r + 1 + rr, cell.c + 1 + cc).border = BORDER;
              }
            }
          }
        }
      });

      // merged title cells do not auto-grow their row, so set the height explicitly
      Object.keys(titleRowLines).forEach(function (r) {
        ws.getRow(parseInt(r, 10) + 1).height = 15 * titleRowLines[r];
      });

      if (model.lastHeader >= 0) {
        ws.views = [{ state: 'frozen', ySplit: model.lastHeader + 1 }];
      }

      ws.pageSetup = { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 };

      return wb.xlsx.writeBuffer().then(function (buffer) {
        var blob = new Blob([buffer], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        download(blob, buildFileName(opts, 'xlsx'));
      });
    });
  }

  /* ------------------------------------------------------------------ */
  /* .csv  (Excel, Indonesian locale: ';' delimiter, ',' decimal, BOM)   */
  /* ------------------------------------------------------------------ */

  function csvField(value) {
    var s = String(value);
    if (/[;"\r\n]/.test(s) || /^\s|\s$/.test(s)) {
      return '"' + s.replace(/"/g, '""') + '"';
    }
    return s;
  }

  // neutralise spreadsheet formula injection in text cells (=, @, +x, -x ...)
  function csvSafeText(text) {
    if (/^[=+\-@\t\r]/.test(text) && !/^[+\-]?[\d.,\s]*$/.test(text)) { return "'" + text; }
    return text;
  }

  function toCsv(tableId, opts) {
    opts = opts || {};

    var table = document.getElementById(tableId);
    if (!table || !table.rows.length) { notify('Tidak ada data untuk di-export.'); return; }

    withBusy(function () {
      var model = readTable(table);
      if (!model.cells.length) { notify('Tidak ada data untuk di-export.'); return; }
      markNumbers(model.cells);

      // CSV starts at the column-header row (title / period / printed-by lines are dropped)
      var startRow = model.firstHeader;
      if (startRow < 0) {
        startRow = 0;
        for (var i = 0; i < model.cells.length; i++) {
          var rowCells = model.cells.filter(function (cc) { return cc.r === model.cells[i].r; });
          if (rowCells.length >= 2) { startRow = model.cells[i].r; break; }
        }
      }

      var byRow = {};
      model.cells.forEach(function (cell) {
        if (cell.r < startRow) { return; }
        (byRow[cell.r] = byRow[cell.r] || []).push(cell);
      });

      var lines = [];
      Object.keys(byRow).map(Number).sort(function (a, b) { return a - b; }).forEach(function (r) {
        var out = [];
        for (var c = 0; c < model.colCount; c++) { out[c] = ''; }
        byRow[r].forEach(function (cell) {
          out[cell.c] = (cell.num !== null)
            ? cell.num.toFixed(cell.decimals).replace('.', ',')
            : csvSafeText(cell.text.replace(/\n/g, ' '));
        });
        lines.push(out.map(csvField).join(';'));
      });

      var blob = new Blob(['﻿', lines.join('\r\n'), '\r\n'], { type: 'text/csv;charset=utf-8' });
      download(blob, buildFileName(opts, 'csv'));
    });
  }

  /* ------------------------------------------------------------------ */
  /* fromRows — export a plain 2D array (row 0 = header)                 */
  /*                                                                     */
  /* For pages that build their own flat data array (instead of reading  */
  /* the on-screen table). JS numbers become real Excel numbers; strings */
  /* stay text. Same look as toXlsx: styled header, borders, frozen      */
  /* header + filter, auto-fit widths. CSV: ';' + BOM + comma decimals.  */
  /*   fmt: 'Excel' | 'CSV'                                              */
  /* ------------------------------------------------------------------ */

  function isNum(v) { return typeof v === 'number' && isFinite(v); }

  // decimals per column: 0 when every value is an integer, else max seen (min 2, max 4)
  function columnDecimals(rows, colCount) {
    var decs = [];
    for (var c = 0; c < colCount; c++) { decs[c] = 0; }
    for (var r = 1; r < rows.length; r++) {
      var row = rows[r];
      for (var c2 = 0; c2 < colCount; c2++) {
        var v = row[c2];
        if (!isNum(v) || Math.floor(v) === v) { continue; }
        var s = String(Math.round(v * 10000) / 10000);
        var idx = s.indexOf('.');
        var d = idx < 0 ? 0 : s.length - idx - 1;
        if (d > decs[c2]) { decs[c2] = d; }
      }
    }
    return decs.map(function (d) { return d > 0 ? Math.min(Math.max(d, 2), 4) : 0; });
  }

  function textOf(v) { return (v === null || v === undefined) ? '' : String(v); }

  function derivedSheetName(opts) {
    if (opts.sheetName) { return opts.sheetName; }
    var n = String(opts.customName || opts.baseName || '').replace(/[_\-\s]*\d.*$/, '').replace(/_/g, ' ').trim();
    return n || 'Report';
  }

  function fromRows(rows, fmt, opts) {
    opts = opts || {};
    if (!rows || rows.length < 2) { notify('Tidak ada data untuk di-export.'); return; }

    var colCount = 0;
    rows.forEach(function (row) { if (row.length > colCount) { colCount = row.length; } });
    var decs = columnDecimals(rows, colCount);
    var isXlsx = (fmt === 'Excel' || fmt === 'xlsx');

    if (!isXlsx) {
      withBusy(function () {
        var lines = rows.map(function (row, r) {
          var out = [];
          for (var c = 0; c < colCount; c++) {
            var v = row[c];
            if (r > 0 && isNum(v)) {
              out.push(v.toFixed(decs[c]).replace('.', ','));
            } else {
              out.push(csvSafeText(textOf(v).replace(/\r?\n/g, ' ')));
            }
          }
          return out.map(csvField).join(';');
        });
        var blob = new Blob(['﻿', lines.join('\r\n'), '\r\n'], { type: 'text/csv;charset=utf-8' });
        download(blob, buildFileName(opts, 'csv'));
      });
      return;
    }

    if (typeof window.ExcelJS === 'undefined') {
      notify('Library ExcelJS belum termuat (public/js/exceljs.min.js). Export Excel dibatalkan.');
      return;
    }

    withBusy(function () {
      // widths from content (header counts a little wider because it is bold)
      var widths = [];
      for (var c = 0; c < colCount; c++) { widths[c] = MIN_COL_WIDTH; }
      rows.forEach(function (row, r) {
        for (var c2 = 0; c2 < colCount; c2++) {
          var v = row[c2], text;
          if (r > 0 && isNum(v)) {
            text = v.toLocaleString('en-US', { minimumFractionDigits: decs[c2], maximumFractionDigits: decs[c2] });
          } else {
            text = textOf(v);
          }
          var longest = 0;
          text.split('\n').forEach(function (line) { if (line.length > longest) { longest = line.length; } });
          var w = longest * (r === 0 ? 1.15 : 1.05) + 2;
          if (w > widths[c2]) { widths[c2] = w; }
        }
      });
      widths = widths.map(function (w) { return Math.min(Math.max(w, MIN_COL_WIDTH), MAX_COL_WIDTH); });

      var wb = new window.ExcelJS.Workbook();
      wb.creator = 'AJC';
      wb.created = new Date();
      var ws = wb.addWorksheet(String(derivedSheetName(opts)).replace(/[\\\/?*\[\]:]+/g, ' ').trim().slice(0, 31) || 'Report');
      ws.columns = widths.map(function (w) { return { width: w }; });

      var ALIGN_HEAD  = { horizontal: 'center', vertical: 'middle', wrapText: true };
      var ALIGN_NUM   = { horizontal: 'right',  vertical: 'middle' };
      var ALIGN_TEXT  = { horizontal: 'left',   vertical: 'middle' };
      var ALIGN_WRAP  = { horizontal: 'left',   vertical: 'middle', wrapText: true };
      var fmts = decs.map(numFmt);

      rows.forEach(function (row, r) {
        for (var c3 = 0; c3 < colCount; c3++) {
          var v = row[c3];
          var x = ws.getCell(r + 1, c3 + 1);
          x.border = BORDER;

          if (r === 0) {
            x.value = textOf(v);
            x.font = FONT_BOLD;
            x.fill = FILL_HEADER;
            x.alignment = ALIGN_HEAD;
          } else if (isNum(v)) {
            x.value = v;
            x.numFmt = fmts[c3];
            x.font = FONT_PLAIN;
            x.alignment = ALIGN_NUM;
          } else {
            var t = textOf(v);
            x.value = t;               // plain string: never a formula
            x.font = FONT_PLAIN;
            x.alignment = (t.indexOf('\n') >= 0 || t.length > MAX_COL_WIDTH) ? ALIGN_WRAP : ALIGN_TEXT;
          }
        }
      });

      ws.views = [{ state: 'frozen', ySplit: 1 }];
      ws.autoFilter = { from: { row: 1, column: 1 }, to: { row: 1, column: colCount } };
      ws.pageSetup = { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 };

      return wb.xlsx.writeBuffer().then(function (buffer) {
        var blob = new Blob([buffer], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        download(blob, buildFileName(opts, 'xlsx'));
      });
    });
  }

  window.ReportExport = { toXlsx: toXlsx, toCsv: toCsv, fromRows: fromRows };

})(window, document);
