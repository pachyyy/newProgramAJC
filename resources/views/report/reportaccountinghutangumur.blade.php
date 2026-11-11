@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Hutang Umur (Analisa Umur Hutang): styled .tb-report, per-nota rows dikelompokkan per supplier
     (Nama) dengan subtotal + grand total (Saldo Akhir & bucket umur <0/0-30/31-60/61-90/91-120/>120).
     Di atas tabel: chart Aging Hutang (doughnut) + Hutang per Supplier Top 10 (bar). Perkiraan,
     mode Valas (IDR/$) & Kurs Valas, Supp Awal/Akhir semua ada di modal "Filter Laporan"; tak ada
     "Tampilan" di bar tabel karena kolom sama persis utk IDR/$ (cuma nilainya beda).
     Kolom LPB (NoFaktur) → klik baris membuka Faktur Pembelian (BPL) di panel bawah (report-table.js). --}}

<!-- Chart.js v4 (di-bundle lokal: public/plugins/chart.js/chart.umd.min.js) -->
<script src="{!! URL::asset('plugins/chart.js/chart.umd.min.js') !!}?v={{ @filemtime(base_path('public/plugins/chart.js/chart.umd.min.js')) ?: '1' }}"></script>

<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }

  /* ── Chart section (di atas tabel) — kartu KPI sengaja TIDAK dipakai (tak ada deadline utk hutang) ── */
  .tb-report .chart-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;
  }
  @media (max-width: 900px) { .tb-report .chart-grid { grid-template-columns: 1fr; } }
  .tb-report .chart-box {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 16px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06);
  }
  .tb-report .chart-box h3 {
    font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 12px;
  }
  .tb-report .chart-holder { position: relative; height: 260px; }
  .tb-report .chart-holder canvas { max-height: 260px; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Analisa Umur Hutang</div>
      </div> --}}

      <!-- Periode (per tanggal) -->
      <div class="filter-wrap">
        <label>Per Tanggal</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
      </div>

      {{-- Mode Valas (IDR/$), Kurs Valas, Perkiraan & Supp Awal/Akhir dipindah ke modal
           "Filter Laporan" (lihat di luar .tb-report). Nilai sebenarnya tetap di input hidden
           #valas_value / #inputPerkiraan / #inputSuppAwal / #inputSuppAkhir, dibaca makeTable(). --}}
      <input type="hidden" id="valas_value" value="IDR">
      <input type="hidden" id="inputPerkiraan" value="-">
      <input type="hidden" id="inputSuppAwal" value="-">
      <input type="hidden" id="inputSuppAkhir" value="-">

      <!-- Order By dihapus — selalu default 0 (urut tanggal) -->
      <input type="hidden" id="inputOrd" value="0">

      <!-- Actions: search + filter modal + customize + tampilkan + export -->
      <div class="action-group">
        {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5) —
             lihat aturan dua-Bootstrap di new-design-all-report-guide.md §5.1. --}}
        <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
          <i class="fas fa-filter"></i> Filter
        </button>
        {{-- <button class="btn-load" onclick="doShowFormCustomizeTable()" title="Customize Table"><i class="fas fa-cog"></i> Customize Table</button> --}}
        <button class="btn-load" onclick="makeTable('REPORT')" title="Tampilkan laporan"><i class="fas fa-check"></i> Tampilkan</button>
        <div class="export-wrap" id="exportWrap">
          <button class="export-btn" onclick="toggleExport()"><i class="bi bi-arrow-down"></i> Export <i class="bi bi-caret-down-fill"></i></button>
          <div class="export-drop" id="exportDrop">
            <div class="export-opt" onclick="doExport('Excel')"><i class="bi bi-journals text-success"></i> Ekspor ke <span class="ext">XLSX</span></div>
            <div class="export-opt" onclick="doExport('CSV')"><i class="bi bi-clipboard"></i> Ekspor ke <span class="ext">CSV</span></div>
            <div class="export-opt" onclick="doExport('Print')"><i class="bi bi-printer-fill text-warning"></i> Cetak Laporan</div>
          </div>
        </div>
      </div>
    </div>

    <!-- CHARTS (dibangun sisi-klien dari data yang dimuat) -->
    <div class="chart-grid" id="chartGrid">
      <div class="chart-box">
        <h3>Aging Hutang</h3>
        <div class="chart-holder"><canvas id="agingChart"></canvas></div>
      </div>
      <div class="chart-box">
        <h3>Hutang per Supplier (Top 10)</h3>
        <div class="chart-holder"><canvas id="topSupplierChart"></canvas></div>
      </div>
    </div>

    <!-- Petunjuk drill -->
    <div style="font-size:12px;color:var(--muted);margin:-6px 0 10px 2px">
      <i class="bi bi-lightbulb-fill text-warning"></i>
      Klik baris supplier untuk melihat rincian nota, lalu klik nota untuk membuka voucher.
    </div>

    <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE (1 baris per supplier + subtotal; rincian nota di panel kanan saat baris diklik) -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>LPB</th></tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>
    </div>

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk
      sembunyikan kolom atau atur desimal &amp; total.
    </div>

  </div><!-- /content -->

  <!-- DRILL OVERLAY + PANEL (rincian nota per supplier, geser dari kanan) -->
  <div class="drill-overlay" id="drillOverlay" onclick="closeDrill()"></div>
  <div class="drill-panel" id="drillPanel">
    <div class="dp-header">
      <div>
        <div class="dp-title" id="dpTitle">-</div>
        <div class="dp-sub" id="dpSub">-</div>
      </div>
      <div class="dp-close" onclick="closeDrill()"><i class="bi bi-x"></i></div>
    </div>
    <div class="dp-meta" id="dpMeta"></div>
    <div class="dp-body" id="dpBody"></div>
  </div>

  <!-- TOAST -->
  <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>

  <!-- KAS/VOUCHER PANEL (muncul dari bawah; diisi report-table.js saat openVoucher) -->
  <div class="kas-panel" id="kasPanel">
    <div class="kas-head">
      <div class="kas-title" id="kasTitle">Voucher</div>
      <div class="dp-close" onclick="closeKasharian()"><i class="bi bi-x"></i></div>
    </div>
    <div class="kas-body" id="kasBody"></div>
  </div>

</div><!-- /tb-report -->

{{-- Modal-modal DILETAKKAN DI LUAR .tb-report supaya reset `.tb-report *{margin:0;padding:0}`
     di report-table.css tidak merusak padding/margin modal Bootstrap. --}}

<!-- modal select valas -->
<div class="modal fade rt-picker-v2" id="formSelectValas" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1000px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Valas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table id="tabelSelectValas">
          <thead>
            <tr><th>Valas</th><th>Keterangan</th><th>Kurs</th></tr>
          </thead>
          <tbody id="tabel_dataSelectValas"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<!-- modal select supplier awal -->
<div class="modal fade rt-picker-v2" id="formSelectSuppAwal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Supplier Awal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table id="tabelSelectSuppAwal">
          <thead>
            <tr><th>Kode</th><th>Nama</th><th>Alamat</th><th>Telpon</th></tr>
          </thead>
          <tbody id="tabel_dataSelectSuppAwal"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<!-- modal select supplier akhir -->
<div class="modal fade rt-picker-v2" id="formSelectSuppAkhir" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Supplier Akhir</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table id="tabelSelectSuppAkhir">
          <thead>
            <tr><th>Kode</th><th>Nama</th><th>Alamat</th><th>Telpon</th></tr>
          </thead>
          <tbody id="tabel_dataSelectSuppAkhir"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<!-- modal filter -->
<div class="modal fade rt-filter" id="modalFilter">
  <div class="modal-dialog modal-md">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-filter"></i> Filter Laporan
          <span class="rt-active-badge" id="filterBadge">0 aktif</span>
        </h5>
        {{-- data-dismiss (BS4) = yang benar-benar menutup, karena modal ini dibuka lewat
             $.fn.modal milik BS4 (jQuery dimuat sesudah bundle BS5). data-bs-dismiss
             dibiarkan untuk jaga-jaga. Lihat new-design-all-report-guide.md §5.1. --}}
        <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal"
          data-bs-dismiss="modal" onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Pengaturan Laporan</div>
          {{-- Perkiraan satu kolom penuh (bukan rt-grid-2 — tak ada kelas "1 kolom" di
               report-table.css, jadi child tunggal di grid 2-kolom cuma mengisi setengah). --}}
          <div style="margin-bottom:10px">
            <label class="rt-field-label" for="modalPerkiraan">Perkiraan</label>
            <select class="rt-native" id="modalPerkiraan"></select>
          </div>
          <div class="rt-grid-2">
            <div>
              <label class="rt-field-label" for="modalReportMode">Valas</label>
              <select class="rt-native" id="modalReportMode">
                <option value="IDR">IDR</option>
                <option value="$">$ (Valas)</option>
              </select>
            </div>
            {{-- Muncul di sebelah Valas hanya saat mode $ dipilih (lihat 'change'
                 handler #modalReportMode di jsreport). --}}
            <div id="modalValasWrap" style="display:none;">
              <label class="rt-field-label">Kurs Valas</label>
              <div id="modalValasCombo"></div>
            </div>
          </div>
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Filter Data
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          <div class="rt-grid-2" id="pickFields"></div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="rt-reset-link" onclick="resetAllFilters()">Reset semua</button>
        <div class="rt-footer-buttons">
          <button type="button" class="rt-btn rt-btn-ghost" data-dismiss="modal" data-bs-dismiss="modal"
            onclick="$('#modalFilter').modal('hide')">Batal</button>
          <button type="button" class="rt-btn rt-btn-primary" onclick="applyModalFilter()">Terapkan</button>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection


@section('jsreport')
<script type="text/javascript">
  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalOrderBy = "0";        // default: Tanggal
  let globalReportMode = "IDR";   // default: IDR

  let g_reportTitle = "";
  let g_inputPerkiraan = "";

  let lastRows = [];   // hasil fetch terakhir (dipakai render / search / chart)

  // Konteks filter SAAT tabel dimuat (dipakai openDrill utk kartu hutang agar konsisten).
  let g_loadedPerkiraan = '-', g_loadedValas = 'IDR';

  // Mode NUMERIK (bukan 'IDR'/'$'): DBSIMPANHEADER.reportmode itu kolom integer, jadi
  // mode string membuat header (termasuk toggle Subtotal/Grand Total) TIDAK tersimpan.
  var modereport_detail = 13, modereport_rekap = 14;
  g_modeReport = modereport_detail;
  var jenisreport = 0, DetOrRekap = 0;

  const reportUrl = "{{ url('reportaccountinghutangumur_doReport') }}";
  const kartuUrl  = "{{ url('reportaccountinghutangumur_doKartu') }}";   // ledger 1 supplier (kartu hutang)

  // Bottom voucher panel endpoints (report-table.js is loaded via masterreport2).
  // LPB (NoFaktur) → jenisFromNo memetakan LPB→BPL → Faktur Pembelian (doLpb).
  window.ReportTableConfig = {
    kasUrl    : "{{ url('reportaccountinghutangumur_doKasharian') }}",
    invoiceUrl: "{{ url('reportaccountinghutangumur_doInvoice') }}",
    lpbUrl    : "{{ url('reportaccountinghutangumur_doLpb') }}",
    bpUrl     : "{{ url('reportaccountinghutangumur_doBp') }}"
  };

  $(document).ready(function () {
    setReportMode(globalReportMode);   // set mode + muat gcart_header
    loadPerkiraanDropdown();           // isi dropdown Perkiraan (default akun HT pertama)

    // Header tabel interaktif (drag/gear/hide/decimal/total). Tanpa "Tampilan": IDR/$ di sini
    // TIDAK mengubah susunan kolom (lihat setDefaultHeader), cuma nilai — mode dipilih lewat
    // select Valas di modal Filter saja.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render
    });

    // setTimeout(() => { makeTable('REPORT'); }, 100);
  });

  /* ── kolom (gcart_header) = kolom UANG yang tampil di tabel induk (1 baris/supplier).
        Semuanya ditandai total (item[4]=1) → jadi subtotal per supplier + Grand Total, dan
        bisa di-toggle lewat Customize Table. Kolom nota per-nota (LPB/Tgl/Umur) TIDAK di sini —
        muncul di panel rincian (drill) yang membaca langsung dari baris mentah. Sama utk IDR/$. ── */
  function setDefaultHeader() {
    // item[2]=visible (0 = tersembunyi default, bisa di-toggle via Customize Table),
    // item[4]=isTotal (1 = kolom uang yang di-subtotal). Kolom per-nota (LPB/Tgl) tak
    // punya nilai tunggal per supplier → tampil kosong (—) di baris supplier bila di-on-kan.
    gcart_header = [
      ['NoFaktur', 'LPB', 0, 'varchar', 0, 0],
      ['TglLPB', 'Tgl LPB', 0, 'date', 0, 0],
      ['tanggal', 'Tanggal', 0, 'date', 0, 0],
      ['Saldo', 'Saldo Akhir', 1, 'float', 1, 2],
      ['Saldo0', '<0', 1, 'float', 1, 2],
      ['Saldo30', '0 - 30', 1, 'float', 1, 2],
      ['Saldo60', '31 - 60', 1, 'float', 1, 2],
      ['Saldo90', '61 - 90', 1, 'float', 1, 2],
      ['Saldo120', '91 - 120', 1, 'float', 1, 2],
      ['Saldo121', '>120', 1, 'float', 1, 2],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  /* ── toolbar controls ── */
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
  }

  function setReportMode(val) {
    globalReportMode = val;

    if (val === 'IDR') {
      jenisreport = 0; DetOrRekap = 0;
      $('#valas_value').val('IDR');
    } else {
      jenisreport = 1; DetOrRekap = 1;
      // kosongkan supaya user wajib pilih ulang mata uang lewat modal Filter setiap kali
      // pindah ke mode $ (perilaku lama, dipertahankan)
      $('#valas_value').val('');
    }

    setModeReport();
  }

  function setModeReport() {
    g_modeReport = (globalReportMode === 'IDR') ? modereport_detail : modereport_rekap;
    doSetHeader(g_modeReport);   // muat susunan kolom mode ini (default / kustomisasi tersimpan)
    doShowCustomize();
  }

  /* ── EXPORT ── */
  function toggleExport() { document.getElementById('exportDrop').classList.toggle('open'); }
  document.addEventListener('click', function (e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) { document.getElementById('exportDrop').classList.remove('open'); }
  });
  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }
    exportDelimited(fmt);
  }
  function exportDelimited(fmt) {
    // Ekspor rincian per-nota (bukan hanya subtotal): Kode + Supplier + kolom DRILL_COLS.
    const header = ['Kode', 'Supplier'].concat(DRILL_COLS.map(c => c[1]));
    const body = (lastRows || []).map(r => [str(pickCI(r, 'Kode')), str(pickCI(r, 'Nama'))].concat(DRILL_COLS.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[2] === 'date') return format_date(v);
      if (c[2] === 'num') return currencyNormalizer(v);
      return (v == null ? '' : v);
    })));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'HutangUmur_' + (globalDate1 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (sp_ReportUmurHutang; doReport mengembalikan array biasa) ── */
  function makeTable(_mode) {
    globalDate1 = $('#inputDate1').val();
    g_reportTitle = 'REPORT ACCOUNTING HUTANG UMUR';

    let _perk   = $('#inputPerkiraan').val() || '-';
    let _suppAw = $('#inputSuppAwal').val()  || '-';
    let _suppAk = $('#inputSuppAkhir').val() || '-';
    let _ord    = $('#inputOrd').val();
    let _valas  = $('#valas_value').val();

    // simpan konteks yang dipakai memuat tabel → dipakai openDrill (kartu hutang)
    g_loadedPerkiraan = _perk;
    g_loadedValas = _valas;

    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = {
      date1: globalDate1,
      inputSuppAwal: _suppAw, inputSuppAkhir: _suppAk,
      inputOrd: _ord, inputPerkiraan: _perk, valas_value: _valas
    };

    $.ajax({
      url: reportUrl, type: 'get', data: data,
      success: function (res) {
        lastRows = Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : []);
        $('#searchBox2').val('');
        render();
      },
      error: function () { lastRows = []; render(); }
    });
  }

  /* ── helpers ── */
  function num(v) { if (v === null || v === undefined || v === '') return 0; const n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function str(v) { return (v == null ? '' : String(v)).trim(); }
  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  // LPB (No Nota) clickable hanya untuk nomor voucher betulan (ada '/', bukan baris
  // pembuka "Saldo Awal"). Panel voucher dibuka via report-table.js (dispatch by Jenis).
  function isVoucherNo(v) {
    const s = str(v);
    if (!s || s.indexOf('/') === -1) return false;
    return s.toUpperCase().indexOf('SALDO AWAL') === -1;
  }
  function escapeJs(s) { return String(s == null ? '' : s).replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }

  // Kolom rincian nota (drill) — selalu tetap; dibaca langsung dari baris mentah SP.
  const DRILL_COLS = [
    ['NoFaktur', 'LPB',        'voucher'],
    ['TglLPB',   'Tgl LPB',    'date'],
    ['tanggal',  'Tanggal',    'date'],
    ['Umur',     'Umur',       'text'],
    ['Saldo',    'Saldo Akhir','num'],
    ['Saldo0',   '<0',         'num'],
    ['Saldo30',  '0 - 30',     'num'],
    ['Saldo60',  '31 - 60',    'num'],
    ['Saldo90',  '61 - 90',    'num'],
    ['Saldo120', '91 - 120',   'num'],
    ['Saldo121', '>120',       'num'],
  ];

  // Warna bucket umur — SATU sumber dipakai header+kolom tabel induk, tabel drill, dan doughnut.
  // <0 (Saldo0) tak ada di doughnut → diberi abu slate; sisanya hijau→merah sesuai keparahan.
  const AGING_COLORS = {
    Saldo0:   '#64748b',
    Saldo30:  '#16a34a',
    Saldo60:  '#ca8a04',
    Saldo90:  '#ea580c',
    Saldo120: '#dc2626',
    Saldo121: '#991b1b',
  };
  function agingStyle(key) { return AGING_COLORS[key] ? ' style="color:' + AGING_COLORS[key] + '"' : ''; }

  // Grup per supplier hasil render terakhir (dipakai openDrill by index).
  let supplierGroups = [];

  /* ── RENDER: 1 BARIS per supplier (subtotal kolom uang) + Grand Total.
     Rincian nota tidak tampil inline — dibuka di panel kanan saat baris diklik. ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);   // kolom terlihat (Customize Table): uang + opsional per-nota
    const keys  = cols.filter(c => c[4] === 1).map(c => c[0]);   // hanya kolom uang yang di-subtotal
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    // HEADER: Supplier (kolom tetap, bukan bagian gcart_header — tak ikut drag/gear) + kolom
    // interaktif dari ReportTable.headHtml(). Bucket umur tetap diwarnai lewat post-pass, karena
    // headHtml() tidak tahu soal AGING_COLORS.
    thead.innerHTML = ReportTable.headHtml(cols).replace('<tr>', '<tr><th>Supplier</th>');
    cols.forEach(c => {
      const color = AGING_COLORS[c[0]];
      if (!color) return;
      const th = thead.querySelector('th[data-gidx="' + gcart_header.indexOf(c) + '"]');
      if (th) th.style.color = color;
    });

    // kelompokkan per supplier (Nama), pertahankan urutan kemunculan + jumlahkan kolom uang
    const order = [], buckets = {};
    (lastRows || []).forEach(r => {
      const gkey = str(pickCI(r, 'Nama'));
      if (!(gkey in buckets)) { buckets[gkey] = []; order.push(gkey); }
      buckets[gkey].push(r);
    });

    supplierGroups = order.map(gkey => {
      const rows = buckets[gkey];
      const sums = {}; keys.forEach(k => sums[k] = 0);
      rows.forEach(r => keys.forEach(k => { sums[k] += currencyNormalizer(pickCI(r, k)); }));
      return { code: str(pickCI(rows[0], 'Kode')), name: (gkey !== '' ? gkey : '(Tanpa Nama)'), rows: rows, sums: sums };
    }).filter(g => !search || g.code.toLowerCase().indexOf(search) !== -1 || g.name.toLowerCase().indexOf(search) !== -1);

    if (!supplierGroups.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + (cols.length + 1) + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      buildCharts([]);
      return;
    }

    let html = '';
    const grand = {}; keys.forEach(k => grand[k] = 0);

    supplierGroups.forEach((g, idx) => {
      keys.forEach(k => grand[k] += g.sums[k]);
      const label = (g.code ? g.code + ' - ' : '') + g.name;
      html += '<tr class="data-row" title="Klik untuk melihat rincian nota ' + g.name + '" onclick="openDrill(' + idx + ')">' +
        '<td class="name">' + label +
        ' <span style="font-size:11px;font-weight:600;opacity:.6">(' + g.rows.length + ' nota)</span>' +
        '<span class="drill-hint"><i class="bi bi-arrow-right-short"></i> detail</span></td>' +
        cols.map(c => c[4] === 1
          ? '<td class="num"' + agingStyle(c[0]) + '>' + format_number(g.sums[c[0]], c[5]) + '</td>'
          : '<td style="color:var(--muted);text-align:center">—</td>').join('') +
        '</tr>';
    });

    html += '<tr class="grand-total"><td style="font-weight:800">GRAND TOTAL</td>' +
      cols.map(c => c[4] === 1
        ? '<td class="num"' + agingStyle(c[0]) + '>' + format_number(grand[c[0]], c[5]) + '</td>'
        : '<td></td>').join('') + '</tr>';

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + supplierGroups.length + ' supplier';

    // charts dari seluruh nota supplier yang tampil
    const shownRows = [];
    supplierGroups.forEach(g => g.rows.forEach(r => shownRows.push(r)));
    buildCharts(shownRows);
  }

  /* ── DRILL: rincian nota per supplier (panel kanan). Data dari baris yang sudah dimuat
     (tanpa panggilan server tambahan). Klik baris nota → panel voucher (Faktur Pembelian). ── */
  // Kolom ledger Kartu Hutang (Sp_ReportKartuHutang). 'No Bukti' = LPB (NoFaktur) → klik → BPL;
  // 'Hari' diambil dari kolom Umur data umur (dicocokkan per-nota). total=1 → ikut footer.
  const KARTU_COLS = [
    ['Tanggal',     'Tanggal',      'date',    0],
    ['NoFaktur',    'No Bukti',     'voucher', 0],
    ['debet1',      'Jumlah Rp',    'num',     1],
    ['kredit1',     'Bayar Rp',     'num',     1],
    ['SelisihKurs', 'Selisih Kurs', 'num',     1],
    ['SaldoRp',     'Saldo Rp',     'num',     0],
    ['__hari',      'Hari',         'hari',    0],
  ];

  function openDrill(idx) {
    const g = supplierGroups[idx];
    if (!g) return;

    document.getElementById('dpTitle').textContent = g.name;
    document.getElementById('dpSub').textContent = 'Kode: ' + (g.code || '-') + ' - ' + g.rows.length + ' nota';

    const metaDefs = [
      ['Saldo', 'Saldo Akhir'], ['Saldo0', '<0'], ['Saldo30', '0 - 30'], ['Saldo60', '31 - 60'],
      ['Saldo90', '61 - 90'], ['Saldo120', '91 - 120'], ['Saldo121', '>120'],
    ];
    document.getElementById('dpMeta').innerHTML = metaDefs.map(function (m, i) {
      const v = g.sums[m[0]] || 0;
      const big = (i === 0) ? ' style="font-size:16px"' : '';
      return '<div class="dp-meta-item"><span class="dp-meta-label">' + m[1] + '</span>' +
             '<span class="dp-meta-val ' + (v < 0 ? 'neg' : '') + '"' + big + '>' + format_number(v, 0) + '</span></div>';
    }).join('');

    // peta Nota(LPB) → Umur dari data umur (untuk kolom Hari + FILTER ledger). Key di-UPPERCASE.
    const umurByNota = {};
    g.rows.forEach(r => { const k = str(pickCI(r, 'NoFaktur')).toUpperCase(); if (k) umurByNota[k] = pickCI(r, 'Umur'); });

    document.getElementById('dpBody').innerHTML = '<div class="dp-section-title">' + loadingHtml('Memuat kartu hutang...') + '</div>';
    document.getElementById('drillOverlay').classList.add('open');
    document.getElementById('drillPanel').classList.add('open');

    // Ambil kartu hutang supplier: rentang penuh (2000-01-01) s/d tanggal 'Per Tanggal' umur.
    $.ajax({
      url: kartuUrl, type: 'get',
      data: {
        date1: '2000-01-01', date2: globalDate1, kode: g.code,
        inputPerkiraan: g_loadedPerkiraan, valas_value: g_loadedValas
      },
      success: function (res) { renderKartuBody(g, Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : []), umurByNota); },
      error: function () {
        document.getElementById('dpBody').innerHTML =
          '<div style="padding:12px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;color:#B91C1C;font-size:12.5px">Gagal memuat kartu hutang.</div>';
      }
    });
  }

  function renderKartuBody(g, rows, umurByNota) {
    const cols = KARTU_COLS;
    const totals = {}; cols.forEach(c => { if (c[3] === 1) totals[c[0]] = 0; });

    // Hanya baris yang No Bukti (LPB/NoFaktur)-nya cocok dgn nota di data umur (Saldo Awal ikut terbuang).
    const shown = (rows || []).filter(r => Object.prototype.hasOwnProperty.call(umurByNota, str(pickCI(r, 'NoFaktur')).toUpperCase()));

    let body = '';
    shown.forEach(r => {
      const nof = str(pickCI(r, 'NoFaktur'));
      const clickable = isVoucherNo(nof);
      const jn = clickable && (typeof jenisFromNo === 'function') ? jenisFromNo(nof) : '';
      const ttl = clickable && (typeof jenisTitle === 'function') ? jenisTitle(jn) : 'Voucher';
      const tr = clickable
        ? '<tr class="kas-clickable" title="Klik untuk lihat ' + ttl + ' ' + nof + '" ' +
          'onclick="openVoucher(\'' + escapeJs(nof) + '\',\'' + escapeJs(jn) + '\')">'
        : '<tr>';

      body += tr + cols.map(function (c) {
        if (c[2] === 'hari') { const h = umurByNota[nof.toUpperCase()]; return '<td>' + (h == null || h === '' ? '-' : nullToEmpty(h)) + '</td>'; }
        const v = pickCI(r, c[0]);
        if (c[2] === 'num') { const n = currencyNormalizer(v); if (c[3] === 1) totals[c[0]] += n; return '<td class="num">' + (n ? format_number(n, 0) : '-') + '</td>'; }
        if (c[2] === 'date') return '<td style="white-space:nowrap">' + format_date(v) + '</td>';
        if (c[2] === 'voucher') return '<td><span class="ref-badge">' + nullToEmpty(v) + '</span></td>';
        return '<td>' + nullToEmpty(v) + '</td>';
      }).join('') + '</tr>';
    });

    if (!shown.length) body = '<tr><td colspan="' + cols.length + '" style="text-align:center;color:var(--muted);padding:14px">Tidak ada transaksi cocok</td></tr>';

    const thead = '<tr>' + cols.map(c => '<th' + (c[2] === 'num' ? ' class="num"' : '') + '>' + c[1] + '</th>').join('') + '</tr>';
    const tfoot = '<tr class="ledger-total"><td colspan="2" style="font-weight:800">Total ' + g.name + '</td>' +
      cols.slice(2).map(c => '<td class="num">' + (c[3] === 1 ? format_number(totals[c[0]], 0) : '') + '</td>').join('') + '</tr>';

    document.getElementById('dpBody').innerHTML =
      '<div class="dp-section-title">Kartu Hutang - ' + shown.length + ' transaksi</div>' +
      '<div style="overflow-x:auto"><table class="ledger-table">' +
      '<thead>' + thead + '</thead><tbody>' + body + '</tbody><tfoot>' + tfoot + '</tfoot></table></div>';
  }

  function closeDrill() {
    document.getElementById('drillOverlay').classList.remove('open');
    document.getElementById('drillPanel').classList.remove('open');
  }

  /* ── CHARTS (Chart.js v4) ────────────────────────────────────────────────
     Kiri  : Aging Hutang (doughnut) — 5 bucket 0-30 / 31-60 / 61-90 / 91-120 / >120
             (bucket <0 sengaja tidak ditampilkan di chart; tetap ada di tabel).
     Kanan : Hutang per Supplier Top 10 (bar horizontal) — Saldo Akhir per supplier, desc. ── */
  const CHART_PALETTE = ['#4F46E5','#7C3AED','#DB2777','#2563eb','#16a34a','#ca8a04','#ea580c','#0891b2','#e11d48','#65a30d'];
  const AGING_DEFS = [
    ['0 - 30',   'Saldo30',  AGING_COLORS.Saldo30],
    ['31 - 60',  'Saldo60',  AGING_COLORS.Saldo60],
    ['61 - 90',  'Saldo90',  AGING_COLORS.Saldo90],
    ['91 - 120', 'Saldo120', AGING_COLORS.Saldo120],
    ['>120',     'Saldo121', AGING_COLORS.Saldo121],
  ];
  let _charts = {};

  function fmtShort(v) {
    v = Math.round(num(v)); const a = Math.abs(v);
    if (a >= 1e9) return (v / 1e9).toFixed(1).replace(/\.0$/, '') + ' M';
    if (a >= 1e6) return (v / 1e6).toFixed(1).replace(/\.0$/, '') + ' jt';
    if (a >= 1e3) return (v / 1e3).toFixed(0) + ' rb';
    return String(v);
  }
  function _destroyChart(id) { if (_charts[id]) { _charts[id].destroy(); delete _charts[id]; } }

  function buildCharts(rows) {
    if (typeof Chart === 'undefined') return;   // gagal muat Chart.js → lewati (tabel tetap jalan)
    try {
      Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
      Chart.defaults.font.size = 12;
      Chart.defaults.color = '#64748b';

      // agregasi
      const aging = AGING_DEFS.map(() => 0);
      const suppOrder = [], suppSum = {};
      (rows || []).forEach(r => {
        AGING_DEFS.forEach((d, i) => { aging[i] += currencyNormalizer(pickCI(r, d[1])); });
        const nm = str(pickCI(r, 'Nama')) || '(Tanpa Nama)';
        if (!(nm in suppSum)) { suppSum[nm] = 0; suppOrder.push(nm); }
        suppSum[nm] += currencyNormalizer(pickCI(r, 'Saldo'));
      });
      const top = suppOrder.map(n => [n, suppSum[n]]).sort((a, b) => b[1] - a[1]).slice(0, 10);

      // ── Aging doughnut ──
      _destroyChart('aging');
      _charts.aging = new Chart(document.getElementById('agingChart'), {
        type: 'doughnut',
        data: {
          labels: AGING_DEFS.map(d => d[0]),
          datasets: [{
            data: aging,
            backgroundColor: AGING_DEFS.map(d => d[2]),
            borderWidth: 2, borderColor: '#fff'
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { position: 'right' },
            tooltip: { callbacks: { label: (c) => ' ' + c.label + ': ' + fmtShort(c.parsed) } }
          }
        }
      });

      // ── Top 10 supplier bar (horizontal) ──
      _destroyChart('topSupplier');
      _charts.topSupplier = new Chart(document.getElementById('topSupplierChart'), {
        type: 'bar',
        data: {
          labels: top.map(t => t[0]),
          datasets: [{
            label: 'Saldo Akhir',
            data: top.map(t => t[1]),
            backgroundColor: top.map((t, i) => CHART_PALETTE[i % CHART_PALETTE.length]),
            borderRadius: 6
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          indexAxis: 'y',
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (c) => ' ' + fmtShort(c.parsed.x) } }
          },
          scales: { x: { ticks: { callback: (v) => fmtShort(v) } } }
        }
      });
    } catch (e) { console.error('buildCharts', e); }
  }

  /* ── PENCARIAN SISI-KLIEN (cocokkan kode / nama supplier) ── */
  function applyFilters() { render(); }

  /* ── TOAST ── */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function getKolomFilter() { return ['Tanggal', 'NoFaktur']; }

  /* ── DROPDOWN PERKIRAAN (akun HT; default akun pertama) ── */
  let perkiraanList = [];   // daftar akun HT (dipakai resetAllFilters)

  function loadPerkiraanDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('reportaccountinghutangumur_loadperkiraan') !!}",
      type: "get", async: false,
      success: function (res) { list = res || []; }
    });

    perkiraanList = list;

    let html = '';
    list.forEach((item) => {
      const ket = (item.Keterangan != null ? String(item.Keterangan) : '');
      html += '<option value="' + item.Perkiraan + '" data-ket="' + ket.replace(/"/g, '&quot;') + '">' +
        item.Perkiraan + ' - ' + ket + '</option>';
    });
    $("#modalPerkiraan").html(html);

    if (list.length) {
      setPerkiraan(list[0].Perkiraan, list[0].Keterangan != null ? list[0].Keterangan : '');
      autoSelectSuppRange();   // isi rentang Supp Awal/Akhir awal (perilaku lama)
    }
  }

  function setPerkiraan(kode, ket) {
    $("#inputPerkiraan").val(kode);
    $("#modalPerkiraan").val(kode);
    g_inputPerkiraan = kode + (ket ? ' - ' + ket : '');
  }

  /* ── AUTO-PILIH RENTANG SUPPLIER ──
     Isi Supp Awal = supplier pertama, Supp Akhir = supplier terakhir dari list akun
     (perkiraan) terpilih. List diurut per KodeCustsupp di endpoint, jadi pertama = kode
     terendah, terakhir = kode tertinggi. Dipanggil saat load & setiap ganti Perkiraan
     (pending, di modal Filter — lihat 'change' handler #modalPerkiraan di bawah). ── */
  function autoSelectSuppRange() {
    let perkiraan = $("#modalPerkiraan").length ? $("#modalPerkiraan").val() : $("#inputPerkiraan").val();
    let list = [];
    $.ajax({
      url: "{!! url('reportaccountinghutangumur_loadsuppawal') !!}",
      type: "get", async: false, data: { perkiraan: perkiraan },
      success: function (res) { list = res || []; }
    });

    if (list.length) {
      $('#inputSuppAwal').val(list[0].KodeCustsupp);
      $('#inputSuppAkhir').val(list[list.length - 1].KodeCustsupp);
    } else {
      $('#inputSuppAwal').val('-');
      $('#inputSuppAkhir').val('-');
    }
  }

  /* ── MODAL SUPPLIER AWAL ── */
  function buttonSelectSuppAwal() { loadSelectSuppAwal(); $("#formSelectSuppAwal").modal('toggle'); }
  function buttonPilihSuppAwal(kode) { $("#inputSuppAwal").val(kode); $("#formSelectSuppAwal").modal('hide'); }

  function loadSelectSuppAwal() {
    // Baca dari #modalPerkiraan (pending, belum Terapkan), bukan #inputPerkiraan yang sudah
    // di-commit — picker dibuka dari dalam modal Filter sebelum Terapkan diklik.
    let perkiraan = $("#modalPerkiraan").length ? $("#modalPerkiraan").val() : $("#inputPerkiraan").val();
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAwal')) { $('#tabelSelectSuppAwal').DataTable().destroy(); }

    $.ajax({
      url: "{!! url('reportaccountinghutangumur_loadsuppawal') !!}",
      type: "get", async: false, data: { perkiraan: perkiraan },
      success: function (res) { dataRefresh = res || []; }
    });

    let rowTable = "";
    dataRefresh.forEach((item) => {
      rowTable += `<tr class="pick-row" onclick="buttonPilihSuppAwal('${item.KodeCustsupp}')">
        <td>${item.KodeCustsupp}</td>
        <td>${item.NamaCust}</td>
        <td>${item.Alamat ?? ''}</td>
        <td>${item.Telpon ?? ''}</td>
      </tr>`;
    });
    document.getElementById("tabel_dataSelectSuppAwal").innerHTML = rowTable;
    $("#tabelSelectSuppAwal").DataTable({ "lengthChange": false, "paging": true });
  }

  /* ── MODAL SUPPLIER AKHIR ── */
  function buttonSelectSuppAkhir() { loadSelectSuppAkhir(); $("#formSelectSuppAkhir").modal('toggle'); }
  function buttonPilihSuppAkhir(kode) { $("#inputSuppAkhir").val(kode); $("#formSelectSuppAkhir").modal('hide'); }

  function loadSelectSuppAkhir() {
    // Baca dari #modalPerkiraan (pending, belum Terapkan), bukan #inputPerkiraan yang sudah
    // di-commit — picker dibuka dari dalam modal Filter sebelum Terapkan diklik.
    let perkiraan = $("#modalPerkiraan").length ? $("#modalPerkiraan").val() : $("#inputPerkiraan").val();
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAkhir')) { $('#tabelSelectSuppAkhir').DataTable().destroy(); }

    $.ajax({
      url: "{!! url('reportaccountinghutangumur_loadsuppawal') !!}",
      type: "get", async: false, data: { perkiraan: perkiraan },
      success: function (res) { dataRefresh = res || []; }
    });

    let rowTable = "";
    dataRefresh.forEach((item) => {
      rowTable += `<tr class="pick-row" onclick="buttonPilihSuppAkhir('${item.KodeCustsupp}')">
        <td>${item.KodeCustsupp}</td>
        <td>${item.NamaCust}</td>
        <td>${item.Alamat ?? ''}</td>
        <td>${item.Telpon ?? ''}</td>
      </tr>`;
    });
    document.getElementById("tabel_dataSelectSuppAkhir").innerHTML = rowTable;
    $("#tabelSelectSuppAkhir").DataTable({ "lengthChange": false, "paging": true });
  }

  /* ── MODAL VALAS ── */
  function buttonSelectValas() { loadSelectValas(); $("#formSelectValas").modal('toggle'); }
  function buttonPilihValas(kode) { $('#valas_value').val(kode); $('#formSelectValas').modal('hide'); }

  function loadSelectValas() {
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectValas')) { $('#tabelSelectValas').DataTable().destroy(); }

    $.ajax({
      url: "{!! url('reportaccountinghutangumur_loadvalas') !!}",
      type: "get", async: false,
      success: function (res) { dataRefresh = res || []; }
    });

    let rowTable = "";
    dataRefresh.forEach((item) => {
      rowTable += `<tr class="pick-row" onclick="buttonPilihValas('${item.Kodevls}')">
        <td>${item.Kodevls}</td>
        <td>${item.NamaVls}</td>
        <td>${item.Kurs}</td>
      </tr>`;
    });
    document.getElementById("tabel_dataSelectValas").innerHTML = rowTable;
    $("#tabelSelectValas").DataTable({ "lengthChange": false, "paging": true });
  }

  /* ── FILTER MODAL (Perkiraan, Valas, Supp Awal/Akhir) ──
     Nilai sebenarnya tetap di input hidden #inputPerkiraan / #valas_value / #inputSuppAwal /
     #inputSuppAkhir (dibaca makeTable(), ditulis di sini / buttonPilihSuppAwal /
     buttonPilihSuppAkhir / buttonPilihValas). Kontrol di modal (#modalPerkiraan,
     #modalReportMode, .rt-combo) hanya tampilan pending di atasnya sampai "Terapkan" diklik —
     kecuali Supp Awal/Akhir & Kurs Valas, yang (seperti sebelumnya) langsung ter-commit begitu
     dipilih dari picker-nya masing-masing. ── */
  const PICK_FIELDS = [
    { id: 'inputSuppAwal', label: 'Supp Awal', open: 'suppAwal' },
    { id: 'inputSuppAkhir', label: 'Supp Akhir', open: 'suppAkhir' },
  ];

  function renderPickFields() {
    let html = '';
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val() || '-';
      const isSet = (val !== '-' && val !== '');
      html += '<div>';
      html += '<label class="rt-field-label">' + f.label + '</label>';
      html += '<div class="rt-combo">';
      html += '<div class="rt-combo-input" onclick="pickFromModal(\'' + f.open + '\')">';
      if (isSet) {
        html += '<span class="rt-combo-tag">' + val +
          '<button type="button" onclick="event.stopPropagation(); clearPickField(\'' + f.id +
          '\')">&times;</button></span>';
      } else {
        html += '<span class="rt-combo-placeholder">Pilih ' + f.label.toLowerCase() + '...</span>';
      }
      html += '<span class="rt-combo-chevron">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
        '</span>';
      html += '</div></div></div>';
    });
    $('#pickFields').html(html);
  }

  function clearPickField(id) {
    $('#' + id).val('-');
    renderPickFields();
    updateFilterBadge();
  }

  // Kotak Kurs Valas (di sebelah select Valas, hanya tampil saat mode $) — pola sama dengan
  // renderPickFields(), tapi nilainya #valas_value dan langsung ter-commit begitu dipilih.
  function renderValasPick() {
    const val = $('#valas_value').val() || '';
    const isSet = (val !== '' && val !== '-' && val !== 'IDR');
    let html = '<div class="rt-combo">';
    html += '<div class="rt-combo-input" onclick="pickFromModal(\'valas\')">';
    if (isSet) {
      html += '<span class="rt-combo-tag">' + val +
        '<button type="button" onclick="event.stopPropagation(); clearValasPick()">&times;</button></span>';
    } else {
      html += '<span class="rt-combo-placeholder">Pilih kurs valas...</span>';
    }
    html += '<span class="rt-combo-chevron">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
      '</span>';
    html += '</div></div>';
    $('#modalValasCombo').html(html);
  }

  function clearValasPick() {
    $('#valas_value').val('');
    renderValasPick();
    updateFilterBadge();
  }

  // Perkiraan & Supp Awal/Akhir: selalu terisi otomatis (auto-select rentang), tak ada status
  // "belum pilih" yang berarti — sengaja tidak dihitung. Valas: IDR = netral (default), jadi
  // cuma dihitung saat pindah ke $.
  function updateFilterBadge() {
    let count = 0;
    if ($('#modalReportMode').val() !== 'IDR') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    if (perkiraanList.length) { $('#modalPerkiraan').val(perkiraanList[0].Perkiraan); }
    $('#modalReportMode').val('IDR');
    $('#modalValasWrap').hide();
    $('#valas_value').val('IDR');
    autoSelectSuppRange();
    renderPickFields();
    renderValasPick();
    updateFilterBadge();
  }

  // Saat modal Filter dibuka ulang otomatis sesudah picker Supp Awal/Akhir/Valas ditutup (lihat
  // pickFromModal / hidden.bs.modal di bawah), JANGAN timpa ulang pilihan pending
  // (Perkiraan/Valas) dari nilai yang sudah di-Terapkan — kalau tidak, pilihan Perkiraan yang
  // belum di-Terapkan hilang begitu user selesai memilih Supp Awal/Akhir. g_reopeningFilter
  // ditandai true sesaat sebelum modal dibuka ulang di jalur itu saja.
  let g_reopeningFilter = false;

  $('#modalFilter').on('show.bs.modal', function () {
    if (!g_reopeningFilter) {
      $('#modalPerkiraan').val($('#inputPerkiraan').val());
      $('#modalReportMode').val(globalReportMode);
      $('#modalValasWrap').toggle(globalReportMode !== 'IDR');
    }
    g_reopeningFilter = false;
    renderPickFields();
    renderValasPick();
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  // Ganti Perkiraan (pending, belum Terapkan) → auto-pilih rentang Supp Awal/Akhir (perilaku
  // lama) memakai daftar supplier utk Perkiraan yang BARU dipilih (pending).
  $('#modalFilter').on('change', '#modalPerkiraan', function () {
    autoSelectSuppRange();
    renderPickFields();
    updateFilterBadge();
  });

  // Ganti mode Valas (pending, belum Terapkan): tampilkan/sembunyikan kotak Kurs Valas di
  // sebelahnya secara langsung.
  $('#modalFilter').on('change', '#modalReportMode', function () {
    $('#modalValasWrap').toggle($(this).val() !== 'IDR');
    renderValasPick();
  });

  function applyModalFilter() {
    const kode = $('#modalPerkiraan').val();
    const ket = $('#modalPerkiraan option:selected').data('ket') || '';
    setPerkiraan(kode, ket);
    if ($('#modalReportMode').length) { setReportMode($('#modalReportMode').val()); }
    $('#modalFilter').modal('hide');
  }

  // Jembatan ke modal pilih Supp Awal/Akhir/Valas: sembunyikan modal Filter dulu (hindari
  // Bootstrap stacked-modal), lalu buka lagi setelah modal pilih ditutup.
  let g_reopenFilter = false;

  function pickFromModal(which) {
    g_reopenFilter = true;
    $('#modalFilter').modal('hide');
    if (which === 'suppAwal') { buttonSelectSuppAwal(); }
    else if (which === 'suppAkhir') { buttonSelectSuppAkhir(); }
    else if (which === 'valas') { buttonSelectValas(); }
  }

  $(document).on('hidden.bs.modal', '#formSelectSuppAwal, #formSelectSuppAkhir, #formSelectValas', function () {
    if (g_reopenFilter) {
      g_reopenFilter = false;
      g_reopeningFilter = true;
      $('#modalFilter').modal('show');
      // 'show.bs.modal' juga memanggil ini, tapi panggil lagi di sini supaya kotak .rt-combo
      // langsung terupdate walau modal masih dalam proses transisi tampil.
      renderPickFields();
      renderValasPick();
      updateFilterBadge();
    }
  });
</script>
@endsection
