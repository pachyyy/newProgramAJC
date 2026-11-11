# Prompt: migrate report Excel/CSV export to the shared `ReportExport` library

> Copy everything below the line into Claude Code, opened at the root of the **other** codebase.
> Before sending: copy these two files from this repo into the other repo's `public/js/` (same names):
> - `public/js/report-export.js`  (~27 KB, our own code)
> - `public/js/exceljs.min.js`    (~948 KB, ExcelJS 4.4.0 browser bundle)

---

## Goal

Replace the old report export (HTML disguised as `.xls` through a `data:` URI, or CSV text saved under an `.xls` name) with a **real `.xlsx`** (styled, auto-fitted column widths) and a **proper CSV for Indonesian-locale Excel**, using one shared script for every report page. This was already done and click-tested in a sibling codebase; your job is to reproduce it here.

Follow this repo's `CLAUDE.md`. In particular: **do not change anything until you are 95% confident, ask me follow-up questions until then, and when finished tell me every file you changed.** The codebase is "the same" as the reference one, but it may have drifted, so verify every assumption below against the real files before editing.

## Background (what the final design is)

- `public/js/report-export.js` exposes `window.ReportExport` with three functions:
  - `toXlsx(tableId, opts)` / `toCsv(tableId, opts)` read the **rendered `<table>`** (so views that override the row/header builders export exactly what is on screen).
  - `fromRows(rows2D, fmt, opts)` takes a flat 2-D array (row 0 = header) and exports it. `fmt` is `'Excel'` or `'CSV'`. Used by pages that build their own data array.
  - `opts` = `{ baseName, customName, sheetName }`. `baseName` is a file prefix (normally `$akses['xlsfilename']`, a `_YYYYMMDD_HHMMSS` suffix is appended); `customName` is used as the file name as-is (the library adds the extension).
- Excel output: real numbers (not text), styled header, borders, frozen header, auto-filter, auto-fit widths (capped), merged colspans, landscape.
- CSV output: `;` delimiter, UTF-8 BOM, comma decimal, formula-injection guard (`'` prefix). Header + data + subtotal rows, no title lines. This is deliberate for Indonesian-locale Excel. Do not "fix" it to commas.
- Page data logic is **not** touched. Only the last step (building a CSV string / Blob / download link) is swapped for a library call.

## Step 0: preflight (read-only; report to me and ask questions before any edit)

1. Confirm `public/js/report-export.js` and `public/js/exceljs.min.js` exist. If not, stop and tell me.
2. List every layout under `resources/views/report/` named `masterreport*.blade.php`. In the reference repo the 8 live ones were: `masterreport`, `masterreport2`, `masterreport2x`, `masterreport3`, `masterreport4`, `masterreport5`, `masterreportGudang`, `masterreportNeraca`. Treat backup/legacy files (`masterreportBackup2310`, `masterreportGudangbackup`, `testes`, `reportTes*`, `newmaster*.blade.php`, anything with "copy" or "backup" in the name) as **out of scope** unless I say otherwise. Show me the list you plan to touch.
3. For each layout find the current `doExportTableToExcel` function and any other export helper. Note which `@extends` chain each report page uses.
4. **Survey the report pages.** Find every `resources/views/**/*.blade.php` that matches `exportDelimited|function doExport\(|Ekspor ke`. Classify each as:
   - **A. has a CSV/Blob tail** inside a function taking `fmt` (see "Page migration" below), the bulk-migratable group;
   - **B. defines its own `doExport`** with a different shape (Kas Harian style), needs hand editing;
   - **C. has an Export dropdown but no working `doExport`** (clicking raises a JS error);
   - **D. Print-only**.
   Give me a table: file, class, what builds its data (`rows`, `[header].concat(body)`, other variable), and what it uses for the file name.
5. Ask me anything unclear (for example: which backup views to include, whether pages in class C should get an export or only be reported).

## Step 1: wire the shared script into each live layout

For each layout in scope:

1. Add these two tags **immediately before** the `<script type="text/javascript">` block that declares `gxls_filename` (the block with `var g_href = ...`):
   ```blade
   <script src="{{ asset('js/exceljs.min.js') }}?v={{ filemtime(public_path('js/exceljs.min.js')) }}"></script>
   <script src="{{ asset('js/report-export.js') }}?v={{ filemtime(public_path('js/report-export.js')) }}"></script>
   ```
   (Use the layout's existing asset helper style if it differs, but keep the `?v=filemtime` cache-buster. The load order matters: ExcelJS first, then `report-export.js`, both before any page script that calls `ReportExport`.)
2. Replace the body of `doExportTableToExcel` and add a sibling `doExportTableToCsv`:
   ```js
   // Export Excel (.xlsx) & CSV: shared logic lives in public/js/report-export.js
   function doExportTableToExcel(tableID, filename = '') {
     ReportExport.toXlsx(tableID || 'tabel', { baseName: @json($akses['xlsfilename']), customName: filename || gxls_filename });
   }
   function doExportTableToCsv(tableID, filename = '') {
     ReportExport.toCsv(tableID || 'tabel', { baseName: @json($akses['xlsfilename']), customName: filename || gxls_filename });
   }
   ```
   - Keep the signature and the `gxls_filename` override semantics. Never mutate `gxls_filename`.
   - If the old function had a guard (for example `masterreport` and `masterreportGudang` return early when `#showTableReport` is `display:none`), **keep that guard** at the top of both new functions.
   - Delete the old data-URI / `<table>` HTML-to-xls code in that function.
3. Add an **"Export to CSV"** button right after the existing "Export to Excel" button in the same toolbar, copying its classes/inline style, with `onclick="doExportTableToCsv('tabel')"`.
4. If a layout uses a different table id than `tabel` (the newer pages use `mainTable`), pass the correct id from the call site; do not hard-code it in the library.

## Step 2: migrate the per-page exports (class A, bulk)

Typical old tail inside `function exportDelimited(fmt)` (names vary slightly):

```js
const csv = rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\n');
const ext = ...;
const blob = new Blob([ ... csv ... ], { type: ... });
const a = document.createElement('a');
a.href = URL.createObjectURL(blob);
a.download = 'SomeName_' + date1 + '_sd_' + date2 + '.' + ext;
document.body.appendChild(a); a.click(); document.body.removeChild(a);
```

Replace the whole tail (from `const csv = ...` through `document.body.removeChild(a);`) with:

```js
ReportExport.fromRows(rows, fmt, { customName: 'SomeName_' + date1 + '_sd_' + date2 });
```

Rules:
- The matrix expression is whatever the old code mapped over (`rows`; `[header].concat(body)`; `out`; `rowsCsv`...). Keep it verbatim. Row 0 must be the header row.
- `customName` is the old `a.download` expression **minus the extension** (`+ '.' + ext`, `+ '.xls'`, ternary `fmt === 'Excel' ? 'xls' : 'csv'`, etc. must go; the library adds `.xlsx` or `.csv`). Keep any date/period parts exactly as the page built them.
- The enclosing function must take `fmt`. If it does not, do not guess; list it for manual handling.
- Keep the page's existing `showToast(...)` / `alertify` message after the call.
- Automate it with a Node script over the classified files: one regex for the tail, a check that everything between `const csv` and `removeChild(a)` is only the known statements (`const ext`, `const blob`, `const a`, `a.href`, `a.download`, `appendChild`, `a.click`, comments), a dry run that prints every matrix expression and file name, and an `--apply` flag. Anything that does not match goes into a **manual** list, never into a partial edit. Read and write files losslessly as UTF-8 and keep CRLF/LF as they were. Keep the script outside the repo.
- Show me the dry-run output (distinct matrix expressions, final file names, manual list) and wait for my OK before `--apply`.

Class B (own `doExport`, e.g. Kas Harian): edit by hand. Delete the page's own delimited-text exporter and make `doExport(fmt)` call the library, keeping the Print branch and the "no data yet" toast:
```js
const opts = { baseName: 'KasHarian', customName: 'KasHarian_' + (g_date1 || '') + '_' + (g_date2 || ''), sheetName: 'Kas Harian' };
if (fmt === 'Excel') { ReportExport.toXlsx('mainTable', opts); } else { ReportExport.toCsv('mainTable', opts); }
```
Such a page loads the library through whichever layout it extends; make sure that layout is one of the wired ones from Step 1. Pages that define their own `doExport` shadow the layout's, so the fix has to be in the page.

Class C (dropdown but no `doExport`): **do not touch unless I tell you.** List them in your final report. If I say yes, give each one a `doExport(fmt)` that calls `ReportExport.toXlsx/toCsv` on its table id and keeps Print as `window.print()`.

Class D (Print only): leave alone.

## Step 3: toolbar dropdown z-index fix (CSS)

Symptom: on pages with **two** `.toolbar` rows (for example the type-filter row under the Export button), the Export dropdown is painted underneath row 2 and cannot be clicked. Cause: both rows have `position: relative; z-index: 50`, so the later row wins; the dropdown's own `z-index: 100` is trapped in row 1's stacking context.

First confirm the same CSS exists here (`.tb-report .toolbar { position: relative; z-index: 50; }` in `public/css/report-table.css`). If so, add directly after that rule:

```css
/* Pages with a second toolbar row: both rows share z-index 50, so the LATER row paints on top of the first row's dropdown. Drop every later row a notch; still above the sticky thead (z-index 20). */
.tb-report .toolbar + .toolbar {
  z-index: 49;
}
```
If the CSS differs, tell me and propose a fix instead of forcing this one.

## Step 4: verification (all of it, report the results)

1. **Blade compile check** of every edited layout/page with a PHP 8.x CLI (`php -r` that runs `Blade::compileString` or `php artisan view:cache` style check). Note: the machine's default PHP may be old; find the right binary (Laragon on Windows).
2. **Inline-JS syntax check** of every edited view against `git HEAD`: strip Blade directives (`{{ }}`, `{!! !!}`, `@json(...)`, `@php...@endphp`, `@if/@foreach/...` lines), extract each `<script>` without `src`, and compile with Node's `vm.Script`. Pass criterion: **no file has more syntax errors than it had at HEAD**.
3. **Leftover scan**: in migrated pages there must be no remaining `URL.createObjectURL(blob)`, `text/csv;charset=utf-8;` or `a.download =` in the export functions, and no `'.xls'` file names.
4. **Headless functional test** (scratchpad, not in the repo): jsdom + `exceljs.min.js` + `report-export.js`. Build a table with a title row, a header row, right-aligned numeric cells in both `1,234.56` and `1.234,56` formats, a leading-zero code (`00123`), a 16+ digit value, a colspan subtotal row, and a long text column. Check that the produced `.xlsx` has real numeric cells, a frozen header, merges, widths that grow with content, and that the CSV has the BOM, `;` delimiters and comma decimals. Also call `fromRows` with a small matrix (header + 2 rows, and a header-only matrix, which must show the "Tidak ada data" notice instead of a file).
5. Remind me to click-test in a browser: 4 or 5 pages from different families (a ledger report, a marketing SO report, a stock report, Kas Harian) in both Excel and CSV, and one large report, checking: file opens without a warning, numbers sum, columns are wide enough, file name is right.

## Guardrails

- Edit only what is listed above. No refactors, no formatting passes, no unrelated fixes.
- Do not commit. I will review `git diff` first.
- If something does not match the assumptions here (different function names, different layout chain, a page whose tail is not the pattern), **stop and ask** instead of improvising.
- Duplicate controllers under `app/Http/Controllers/Master/` are dead code (see `CLAUDE.md`); this task never touches PHP controllers.

## Final report format

1. What you did, per step.
2. The complete list of changed files (grouped: new, layouts, pages, CSS).
3. Pages deliberately left alone, with the reason (class C and D, backup views).
4. Verification results (counts and any failures).
5. What I still need to click-test.
