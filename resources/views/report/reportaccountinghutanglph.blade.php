@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Hutang LPH (rekap saldo hutang per supplier): styled .tb-report, satu baris per supplier
     (kode/nama = kolom data), tabel RATA (tanpa grup) + Grand Total. Kolom: Saldo Awal, Pembelian,
     Pelunasan, Retur, Saldo Akhir (+ kolom $ saat mode Valas). Tidak ada bucket umur/tanggal di SP
     (Tipe=0) — di atas tabel hanya chart Hutang per Supplier Top 10 (bar), tanpa doughnut aging.
     Kolom (gcart_header) interaktif lewat ReportTable (seret/gear/Reset kolom). Mode IDR / $ (valas)
     & Perkiraan/Supplier Awal/Akhir pindah ke modal "Filter Laporan". Klik baris supplier → buka
     Kartu Hutang (ledger, SP Sp_ReportKartuHutang) di panel kanan; kolom No Bukti di ledger →
     buka Faktur Pembelian (BPL) di panel bawah. --}}

<!-- Chart.js v4 (di-bundle lokal: public/plugins/chart.js/chart.umd.min.js) -->
<script src="{!! URL::asset('plugins/chart.js/chart.umd.min.js') !!}?v={{ @filemtime(base_path('public/plugins/chart.js/chart.umd.min.js')) ?: '1' }}"></script>

<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }

  /* ── Chart section (di atas tabel) — hanya 1 chart (tanpa aging), full width ── */
  .tb-report .chart-grid {
    display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;
  }
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
        <div class="page-title">Analisa Hutang LPH</div>
        <div class="page-sub">Dicetak oleh: {{ $akses['user'] }} &nbsp;&middot;&nbsp; <span id="printTime"></span></div>
      </div> --}}

      <!-- Periode (rentang tanggal) -->
      <div class="filter-wrap">
        <label>Periode</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
        <span class="filter-sep">s/d</span>
        <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
      </div>

      {{-- Mode Valas, Kurs Valas, Perkiraan & Supplier Awal/Akhir pindah ke modal
           "Filter Laporan" -- lihat docs/new-filter-modal-ui-guide.md. Nilai sebenarnya tetap
           di variabel/hidden input yang sama: globalReportMode, #valas_value, #inputPerkiraan,
           #inputSuppAwal/#inputSuppAkhir (semua di dalam modal sekarang). Order By dihapus — SP
           mengembalikan rekap per supplier, urutan tak relevan. --}}
      <input type="hidden" id="inputOrd" value="0">

      <!-- Actions: search + filter + tampilkan + export -->
      <div class="action-group">
        {{-- Dibuka lewat plugin jQuery, BUKAN data-bs-toggle -- lihat catatan di modal Filter. --}}
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

    <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- CHARTS (dibangun sisi-klien dari data yang dimuat) -->
    <div class="chart-grid" id="chartGrid">
      <div class="chart-box">
        <h3>Hutang per Supplier (Top 10)</h3>
        <div class="chart-holder"><canvas id="topSupplierChart"></canvas></div>
      </div>
    </div>

    <!-- Petunjuk drill -->
    <div style="font-size:12px;color:var(--muted);margin:0 0 12px 2px">
      <i class="bi bi-lightbulb-fill text-warning"></i>
      Klik baris supplier untuk melihat Kartu Hutang, lalu klik No Bukti untuk membuka Faktur Pembelian.
    </div>

    <!-- TABLE (header + rows rendered dynamically from gcart_header; tabel rata + grand total) -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>Kode</th></tr>
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

  </div><!-- /content -->

  <!-- DRILL OVERLAY + PANEL (Kartu Hutang per supplier, geser dari kanan) -->
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
             $.fn.modal milik BS4 (jQuery baru dimuat SESUDAH bundle BS5 di masterreport2).
             data-bs-dismiss dibiarkan untuk jaga-jaga. --}}
        <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Mode Valas</div>
          <div class="rt-grid-1">
            {{-- Selalu punya nilai (IDR = default) -- pilihan wajib, TIDAK dihitung di badge.
                 Ganti langsung memuat ulang susunan kolom (gcart_header) mode ini, sama seperti
                 perilaku dropdown lama. --}}
            <select class="rt-native" id="modalReportMode" onchange="setReportMode(this.value)">
              <option value="IDR">IDR</option>
              <option value="$">$ (Valas)</option>
            </select>
          </div>

          <!-- Kurs Valas (hanya tampil saat Mode Valas = $) -->
          <div class="rt-grid-1" id="modalValasWrap" style="display:none; margin-top:10px">
            <label class="rt-field-label">Kurs Valas</label>
            <div class="rt-combo">
              <div class="rt-combo-input" onclick="pickValas()" id="valasPickField"></div>
            </div>
          </div>
          <input type="hidden" id="valas_value" value="IDR">
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Perkiraan</div>
          <div class="rt-grid-1">
            {{-- Diisi dari *_loadperkiraan (loadPerkiraanDropdown()). Selalu punya nilai
                 (default akun HT pertama) -- wajib, TIDAK dihitung di badge. Memilih akun lain
                 otomatis menyusun ulang rentang Supplier Awal/Akhir (loadSuppList). --}}
            <select class="rt-native" id="modalPerkiraan" onchange="setPerkiraan(this.value, $(this).find(':selected').data('ket'))"></select>
          </div>
          <input type="hidden" id="inputPerkiraan" value="-">
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Supplier
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          {{-- Rentang otomatis (supplier pertama/terakhir dari akun terpilih) -- wajib punya
               nilai, TIDAK dihitung di badge, sama seperti Perkiraan. --}}
          <div class="rt-grid-2" id="pickFields"></div>
          <input type="hidden" id="inputSuppAwal" value="-">
          <input type="hidden" id="inputSuppAkhir" value="-">
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
<!-- modal filter -->

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
@endsection


@section('jsreport')
<script type="text/javascript">
  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalDate2 = "{!! date('Y-m-d') !!}";
  let globalReportMode = "IDR";   // default: IDR

  let g_reportTitle = "";
  let g_inputPerkiraan = "";

  let lastRows = [];    // hasil fetch terakhir (dipakai render / search / chart)
  let shownRows = [];   // baris yang sedang tampil (lolos search) — dipakai openLedger by index

  // Konteks filter SAAT tabel dimuat (dipakai openLedger agar kartu hutang konsisten dgn
  // tabel yang tampil — bukan nilai input yang mungkin diubah user tanpa klik Tampilkan).
  let g_loadedPerkiraan = '-', g_loadedValas = 'IDR';

  // Daftar supplier utk perkiraan aktif (urut KodeCustsupp dari SP) — dipakai utk auto-pilih
  // rentang penuh Supp Awal/Akhir (lihat loadSuppList()) & mengisi modal pilih supplier.
  let g_suppList = [];

  // Mode NUMERIK: DBSIMPANHEADER.reportmode itu kolom integer, jadi mode string membuat
  // header (termasuk toggle Grand Total) TIDAK tersimpan. Int di sini scoped per href.
  var modereport_detail = 17, modereport_rekap = 18;
  g_modeReport = modereport_detail;
  var jenisreport = 0, DetOrRekap = 0;

  const reportUrl = "{{ url('reportaccountinghutanglph_doReport') }}";
  const kartuUrl  = "{{ url('reportaccountinghutanglph_doKartu') }}";   // ledger 1 supplier

  // Panel voucher bawah (report-table.js). No Bukti → jenisFromNo (LPB/PBJ→BPL) → Faktur Pembelian (doLpb).
  window.ReportTableConfig = {
    kasUrl    : "{{ url('reportaccountinghutanglph_doKasharian') }}",
    invoiceUrl: "{{ url('reportaccountinghutanglph_doInvoice') }}",
    lpbUrl    : "{{ url('reportaccountinghutanglph_doLpb') }}",
    bpUrl     : "{{ url('reportaccountinghutanglph_doBp') }}"
  };

  $(document).ready(function () {
    // #printTime tidak ada di markup halaman ini (blok page-title/page-sub dikomentari) --
    // dijaga null supaya TIDAK melempar exception yang membatalkan sisa ready() (setReportMode
    // & loadPerkiraanDropdown di bawah tidak akan jalan kalau baris ini error).
    var pt = document.getElementById('printTime');
    if (pt) pt.textContent = new Date().toLocaleString('id-ID');
    setReportMode(globalReportMode);   // set mode + muat gcart_header
    loadPerkiraanDropdown();           // isi dropdown Perkiraan (default akun HT pertama)

    // Header tabel interaktif: seret kolom, menu roda gigi (sembunyikan/desimal/total).
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { applyFilters(); }
    });

    // Tidak auto-load: tabel dimuat hanya saat user klik "Tampilkan" (makeTable).
  });

  /* ── kolom (gcart_header). Tabel styled DI-RENDER dari sini (Customize Table).
        Satu baris per supplier → kode & nama TETAP kolom data. Kolom nominal ditandai
        total (item[4]=1) → ikut Grand Total. Tidak ada bucket umur (SP Tipe=0 tak
        mengembalikannya). Mode $ menambahkan kolom nominal dalam $ (…D). ── */
  function setDefaultHeader() {
    if (g_modeReport == modereport_detail) {
      gcart_header = [
        ['Kode', 'Kode', 1, 'varchar', 0, 0],
        ['Nama', 'Nama', 1, 'varchar', 0, 0],
        ['Awal', 'Saldo Awal', 1, 'float', 1, 2],
        ['Jumlah', 'Pembelian', 1, 'float', 1, 2],
        ['Pelunasan', 'Pelunasan', 1, 'float', 1, 2],
        ['Retur', 'Retur', 1, 'float', 1, 2],
        ['SaldoAkhir', 'Saldo Akhir', 1, 'float', 1, 2],
      ];
    } else {
      gcart_header = [
        ['Kode', 'Kode', 1, 'varchar', 0, 0],
        ['Nama', 'Nama', 1, 'varchar', 0, 0],
        ['Awal', 'Saldo Awal', 1, 'float', 1, 2],
        ['Jumlah', 'Pembelian', 1, 'float', 1, 2],
        ['Pelunasan', 'Pelunasan', 1, 'float', 1, 2],
        ['Retur', 'Retur', 1, 'float', 1, 2],
        ['SaldoAkhir', 'Saldo Akhir', 1, 'float', 1, 2],
        ['AwalD', 'Saldo Awal $', 1, 'float', 1, 2],
        ['JumlahD', 'Pembelian $', 1, 'float', 1, 2],
        ['PelunasanD', 'Pelunasan $', 1, 'float', 1, 2],
        ['ReturD', 'Retur $', 1, 'float', 1, 2],
        ['SaldoAkhirD', 'Saldo Akhir $', 1, 'float', 1, 2],
      ];
    }
    gsum_issubtotal = 0; gsum_isgrandtotal = 1;
  }

  /* ── toolbar controls ── */
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
  }

  // val: 'IDR' / '$'. Dipanggil oleh <select id="modalReportMode"> (modal Filter Laporan) —
  // ganti langsung memuat ulang gcart_header mode ini (sama seperti perilaku dropdown lama).
  function setReportMode(val) {
    globalReportMode = val;
    $('#modalReportMode').val(val);

    if (val === 'IDR') {
      jenisreport = 0; DetOrRekap = 0;
      $('#valas_value').val('IDR');
      $('#modalValasWrap').hide();
    } else {
      jenisreport = 1; DetOrRekap = 1;
      $('#valas_value').val('-');   // '-' = belum dipilih (Kurs Valas wajib diisi mode $)
      $('#modalValasWrap').show();
    }
    renderPickFields();
    updateFilterBadge();

    setModeReport();
  }

  function setModeReport() {
    g_modeReport = (globalReportMode === 'IDR') ? modereport_detail : modereport_rekap;
    doSetHeader(g_modeReport);   // muat susunan kolom (default / kustomisasi tersimpan)
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
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'HutangLPH_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (sp_ReportSaldoHutang Tipe=0; doReport mengembalikan array biasa) ── */
  function makeTable(_mode) {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
    g_reportTitle = 'REPORT ACCOUNTING HUTANG LPH';

    let _perk   = $('#inputPerkiraan').val() || '-';
    let _suppAw = $('#inputSuppAwal').val()  || '-';
    let _suppAk = $('#inputSuppAkhir').val() || '-';
    let _ord    = $('#inputOrd').val()       || '0';
    let _valas  = $('#valas_value').val();

    // simpan konteks yang dipakai untuk memuat tabel → dipakai openLedger (kartu hutang)
    g_loadedPerkiraan = _perk;
    g_loadedValas = _valas;

    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = {
      date1: globalDate1, date2: globalDate2,
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
  // HTML-escape teks bebas (nama supplier bisa diisi user).
  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }


  /* ── LEDGER (Kartu Hutang) + VOUCHER ─────────────────────────────────────
     Klik baris supplier → ambil kartu hutang supplier itu (Sp_ReportKartuHutang via
     doKartu) → tampil di panel kanan. Kolom No Bukti bisa diklik → Faktur Pembelian (BPL). ── */
  function escapeJs(s) { return String(s == null ? '' : s).replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
  function isVoucherNo(v) {
    const s = str(v);
    if (!s || s.indexOf('/') === -1) return false;
    return s.toUpperCase().indexOf('SALDO AWAL') === -1;
  }
  // Sel No Bukti: klik → panel voucher bawah (report-table.js). Hanya nomor voucher betulan.
  function voucherCell(v) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<td>' + nullToEmpty(v) + '</td>';
    const jn  = (typeof jenisFromNo === 'function') ? jenisFromNo(s) : '';
    const ttl = (typeof jenisTitle === 'function') ? jenisTitle(jn) : 'Voucher';
    return '<td class="kas-clickable" style="cursor:pointer;color:#0d6efd;text-decoration:underline" ' +
           'title="Klik untuk lihat ' + ttl + ' ' + s + '" ' +
           'onclick="openVoucher(\'' + escapeJs(s) + '\',\'' + escapeJs(jn) + '\')">' + nullToEmpty(v) + '</td>';
  }

  // Kolom ledger mengikuti mode valas (Sp_ReportKartuHutang). type: date/voucher/text/num; total=1 → ikut footer.
  function ledgerCols() {
    const base = [
      ['Tanggal', 'Tanggal', 'date', 0],
      ['NoFaktur', 'No Bukti', 'voucher', 0],
      ['debet1', 'Jumlah Rp', 'num', 1],
      ['kredit1', 'Bayar Rp', 'num', 1],
      ['SelisihKurs', 'Selisih Kurs', 'num', 1],
      ['SaldoRp', 'Saldo Rp', 'num', 0],
    ];
    if (g_loadedValas === 'IDR') {
      return base.concat([['hari', 'Hari', 'text', 0]]);
    }
    return base.concat([
      ['debetd1', 'Jumlah $', 'num', 1],
      ['kreditd1', 'Bayar $', 'num', 1],
      ['SaldoD', 'Saldo $', 'num', 0],
      ['kurs', 'Kurs', 'num', 0],
    ]);
  }

  function openLedger(idx) {
    const r = shownRows[idx];
    if (!r) return;
    const kode = str(pickCI(r, 'Kode'));
    const nama = str(pickCI(r, 'Nama')) || kode || '(Tanpa Nama)';

    document.getElementById('dpTitle').textContent = nama;
    document.getElementById('dpSub').textContent = 'Kode: ' + (kode || '-');

    // meta ringkas dari baris LPH (tidak ada bucket umur di SP Tipe=0)
    const metaDefs = [
      ['SaldoAkhir', 'Saldo Akhir'], ['Awal', 'Saldo Awal'], ['Jumlah', 'Pembelian'],
      ['Pelunasan', 'Pelunasan'], ['Retur', 'Retur'],
    ];
    document.getElementById('dpMeta').innerHTML = metaDefs.map(function (m, i) {
      const v = currencyNormalizer(pickCI(r, m[0]));
      const big = (i === 0) ? ' style="font-size:16px"' : '';
      return '<div class="dp-meta-item"><span class="dp-meta-label">' + m[1] + '</span>' +
             '<span class="dp-meta-val ' + (v < 0 ? 'neg' : '') + '"' + big + '>' + format_number(v, 0) + '</span></div>';
    }).join('');

    document.getElementById('dpBody').innerHTML = '<div class="dp-section-title">' + loadingHtml('Memuat kartu hutang...') + '</div>';
    document.getElementById('drillOverlay').classList.add('open');
    document.getElementById('drillPanel').classList.add('open');

    $.ajax({
      url: kartuUrl, type: 'get',
      data: {
        date1: globalDate1, date2: globalDate2,
        kode: kode, inputPerkiraan: g_loadedPerkiraan, valas_value: g_loadedValas
      },
      success: function (res) { renderKartuBody(nama, Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : [])); },
      error: function () {
        document.getElementById('dpBody').innerHTML =
          '<div style="padding:12px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;color:#B91C1C;font-size:12.5px">Gagal memuat kartu hutang.</div>';
      }
    });
  }

  function renderKartuBody(nama, rows) {
    const cols = ledgerCols();
    const totals = {}; cols.forEach(c => { if (c[3] === 1) totals[c[0]] = 0; });

    // Saldo berjalan (Saldo Rp / Saldo $): kartu 1 supplier → satu urutan menerus (tanpa
    // grup). Baris pertama = Saldo Awal dari SP, berikutnya + debet - kredit (Selisih Kurs
    // diabaikan). Fungsi bersama assignRunningSaldo / isRunningSaldo di report-table.js.
    assignRunningSaldo(rows, { groupKey: null });
    const endRun = {};   // saldo akhir (running terakhir) per kolom saldo → dipakai di footer

    let body = '';
    rows.forEach(r => {
      body += '<tr>' + cols.map(function (c) {
        const v = pickCI(r, c[0]);
        if (c[2] === 'num') {
          if (isRunningSaldo(c[0])) {
            const rb = (r._run && r._run[c[0]] != null) ? r._run[c[0]] : 0;
            endRun[c[0]] = rb;
            return '<td class="num">' + format_number(rb, 0) + '</td>';   // saldo: selalu tampil angka (termasuk 0)
          }
          const n = currencyNormalizer(v); if (c[3] === 1) totals[c[0]] += n;
          return '<td class="num">' + (n ? format_number(n, 0) : '-') + '</td>';
        }
        if (c[2] === 'date') return '<td style="white-space:nowrap">' + format_date(v) + '</td>';
        if (c[2] === 'voucher') return voucherCell(v);
        return '<td>' + nullToEmpty(v) + '</td>';
      }).join('') + '</tr>';
    });

    if (!rows.length) body = '<tr><td colspan="' + cols.length + '" style="text-align:center;color:var(--muted);padding:14px">Tidak ada transaksi pada periode ini</td></tr>';

    const thead = '<tr>' + cols.map(c => '<th' + (c[2] === 'num' ? ' class="num"' : '') + '>' + c[1] + '</th>').join('') + '</tr>';
    // Footer: kolom saldo berjalan → saldo akhir; kolom total → jumlah; lainnya kosong.
    const tfoot = '<tr class="ledger-total"><td colspan="2" style="font-weight:800">Total</td>' +
      cols.slice(2).map(function (c) {
        if (isRunningSaldo(c[0])) return '<td class="num">' + format_number(endRun[c[0]] || 0, 0) + '</td>';
        return '<td class="num">' + (c[3] === 1 ? format_number(totals[c[0]], 0) : '') + '</td>';
      }).join('') + '</tr>';

    document.getElementById('dpBody').innerHTML =
      '<div class="dp-section-title">Kartu Hutang - ' + rows.length + ' transaksi</div>' +
      '<div style="overflow-x:auto"><table class="ledger-table">' +
      '<thead>' + thead + '</thead><tbody>' + body + '</tbody><tfoot>' + tfoot + '</tfoot></table></div>';
  }

  function closeDrill() {
    document.getElementById('drillOverlay').classList.remove('open');
    document.getElementById('drillPanel').classList.remove('open');
  }

  /* ── RENDER: tabel RATA (satu baris per supplier, tanpa grup) + Grand Total.
     Kolom dinamis dari gcart_header (item[2]===1). ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');

    const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
    const totalKeys = totalCols.map(c => c[0]);
    const hasTotal  = totalCols.length > 0;
    const showGrand = hasTotal && (gsum_isgrandtotal === 1);
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
    // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    thead.innerHTML = ReportTable.headHtml(cols);

    // saring baris (search), lalu render rata
    const rows = (lastRows || []).filter(r => !search || rowSearchText(r, cols).indexOf(search) !== -1);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      buildCharts([]);
      return;
    }

    shownRows = rows;   // simpan utk openLedger(idx)
    let html = '';
    const grand = {}; totalKeys.forEach(k => grand[k] = 0);

    rows.forEach((r, idx) => {
      totalKeys.forEach(k => { grand[k] += currencyNormalizer(pickCI(r, k)); });
      html += '<tr class="data-row" style="cursor:pointer" title="Klik untuk melihat Kartu Hutang" onclick="openLedger(' + idx + ')">' + cols.map(function (c) {
        const type = c[3];
        const v = pickCI(r, c[0]);
        if (type === 'date') return '<td>' + format_date(v) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(v), c[5]) + '</td>';
        if (c[0] === 'Nama') return '<td style="white-space:nowrap">' + nullToEmpty(v) + '<span class="drill-hint"><i class="bi bi-arrow-right-short"></i> kartu</span></td>';
        return '<td>' + nullToEmpty(v) + '</td>';
      }).join('') + '</tr>';
    });

    if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';

    buildCharts(rows);
  }

  // Baris total: nilai di tiap kolom pada `sumKeys`; label menempati SELURUH kolom non-sum
  // yang berurutan mulai dari kolom non-sum pertama (bukan cuma satu sel sempit), supaya
  // tidak wrap.
  function totalRow(label, sums, cols, sumKeys, cls) {
    const labelIdx = cols.findIndex(c => sumKeys.indexOf(c[0]) === -1);
    let span = 0;
    for (let i = labelIdx; i < cols.length && sumKeys.indexOf(cols[i][0]) === -1; i++) { span++; }

    const tds = [];
    for (let idx = 0; idx < cols.length; idx++) {
      const c = cols[idx];
      if (sumKeys.indexOf(c[0]) !== -1) { tds.push('<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>'); continue; }
      if (idx === labelIdx) { tds.push('<td colspan="' + span + '">' + label + '</td>'); idx += span - 1; continue; }
      tds.push('<td></td>');
    }
    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  /* ── CHART (Chart.js v4) ─────────────────────────────────────────────────
     Hutang per Supplier Top 10 (bar horizontal) — Saldo Akhir per supplier, desc.
     SP Tipe=0 tak mengembalikan bucket umur/tanggal → tidak ada doughnut aging. ── */
  const CHART_PALETTE = ['#4F46E5','#7C3AED','#DB2777','#2563eb','#16a34a','#ca8a04','#ea580c','#0891b2','#e11d48','#65a30d'];
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

      // agregasi: Saldo Akhir per supplier (mode Rp — stabil walau mode $ aktif)
      const supp = [];
      (rows || []).forEach(r => {
        supp.push([str(pickCI(r, 'Nama')) || str(pickCI(r, 'Kode')) || '(Tanpa Nama)', currencyNormalizer(pickCI(r, 'SaldoAkhir'))]);
      });
      const top = supp.sort((a, b) => b[1] - a[1]).slice(0, 10);

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

  /* ── PENCARIAN SISI-KLIEN ── */
  function applyFilters() { render(); }

  function rowSearchText(r, cols) {
    let s = '';
    cols.forEach(function (c) {
      const v = pickCI(r, c[0]);
      s += ' ' + (c[3] === 'date' ? format_date(v) : (v == null ? '' : String(v)));
    });
    return s.toLowerCase();
  }

  /* ── TOAST ── */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function getKolomFilter() { return ['Kode', 'Nama']; }

  /* ── PERKIRAAN (akun HT; default akun pertama) — modal Filter Laporan, <select id="modalPerkiraan"> ── */
  function loadPerkiraanDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('reportaccountinghutanglph_loadperkiraan') !!}",
      type: "get", async: false,
      success: function (res) { list = res || []; }
    });

    let html = '';
    list.forEach((item) => {
      const ket = (item.Keterangan != null ? String(item.Keterangan) : '');
      html += '<option value="' + item.Perkiraan + '" data-ket="' + esc(ket) + '">' +
        item.Perkiraan + ' - ' + esc(ket) + '</option>';
    });
    $("#modalPerkiraan").html(html);

    if (list.length) { setPerkiraan(list[0].Perkiraan, list[0].Keterangan != null ? list[0].Keterangan : ''); }
  }

  function setPerkiraan(kode, ket) {
    $("#inputPerkiraan").val(kode);
    $("#modalPerkiraan").val(kode);
    g_inputPerkiraan = kode + (ket ? ' - ' + ket : '');

    // supplier difilter per perkiraan → muat ulang daftar & auto-pilih rentang penuh
    loadSuppList(kode);
    renderPickFields();
    updateFilterBadge();
  }

  // Daftar supplier utk perkiraan aktif (dipakai jg oleh modal pilih Supp Awal/Akhir, tanpa
  // fetch ulang). Auto-pilih rentang penuh: Awal = kode pertama, Akhir = kode terakhir (list
  // sudah terurut KodeCustsupp dari SP) — "+" tetap bisa dipakai utk mempersempit ke 1 supplier.
  function loadSuppList(perkiraan) {
    g_suppList = [];
    $.ajax({
      url: "{!! url('reportaccountinghutanglph_loadsuppawal') !!}",
      type: "get", async: false, data: { perkiraan: perkiraan },
      success: function (res) { g_suppList = res || []; }
    });

    if (g_suppList.length) {
      $('#inputSuppAwal').val(g_suppList[0].KodeCustsupp);
      $('#inputSuppAkhir').val(g_suppList[g_suppList.length - 1].KodeCustsupp);
    } else {
      $('#inputSuppAwal').val('-');
      $('#inputSuppAkhir').val('-');
    }
  }

  /* ── MODAL SUPPLIER AWAL ── */
  function buttonSelectSuppAwal() { loadSelectSuppAwal(); $("#formSelectSuppAwal").modal('toggle'); }
  function buttonPilihSuppAwal(kode) {
    $("#inputSuppAwal").val(kode); $("#formSelectSuppAwal").modal('hide');
    renderPickFields(); updateFilterBadge();
  }

  function loadSelectSuppAwal() {
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAwal')) { $('#tabelSelectSuppAwal').DataTable().destroy(); }

    let rowTable = "";
    g_suppList.forEach((item) => {
      rowTable += `<tr class="pick-row" onclick="buttonPilihSuppAwal('${item.KodeCustsupp}')">
        <td>${esc(item.KodeCustsupp)}</td>
        <td>${esc(item.NamaCust)}</td>
        <td>${esc(item.Alamat ?? '')}</td>
        <td>${esc(item.Telpon ?? '')}</td>
      </tr>`;
    });
    document.getElementById("tabel_dataSelectSuppAwal").innerHTML = rowTable;
    $("#tabelSelectSuppAwal").DataTable({ "lengthChange": false, "paging": true });
  }

  /* ── MODAL SUPPLIER AKHIR ── */
  function buttonSelectSuppAkhir() { loadSelectSuppAkhir(); $("#formSelectSuppAkhir").modal('toggle'); }
  function buttonPilihSuppAkhir(kode) {
    $("#inputSuppAkhir").val(kode); $("#formSelectSuppAkhir").modal('hide');
    renderPickFields(); updateFilterBadge();
  }

  function loadSelectSuppAkhir() {
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAkhir')) { $('#tabelSelectSuppAkhir').DataTable().destroy(); }

    let rowTable = "";
    g_suppList.forEach((item) => {
      rowTable += `<tr class="pick-row" onclick="buttonPilihSuppAkhir('${item.KodeCustsupp}')">
        <td>${esc(item.KodeCustsupp)}</td>
        <td>${esc(item.NamaCust)}</td>
        <td>${esc(item.Alamat ?? '')}</td>
        <td>${esc(item.Telpon ?? '')}</td>
      </tr>`;
    });
    document.getElementById("tabel_dataSelectSuppAkhir").innerHTML = rowTable;
    $("#tabelSelectSuppAkhir").DataTable({ "lengthChange": false, "paging": true });
  }

  /* ── MODAL VALAS ── */
  function buttonSelectValas() { loadSelectValas(); $("#formSelectValas").modal('toggle'); }
  function buttonPilihValas(kode) {
    $('#valas_value').val(kode); $('#formSelectValas').modal('hide');
    renderPickFields(); updateFilterBadge();
  }

  function loadSelectValas() {
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectValas')) { $('#tabelSelectValas').DataTable().destroy(); }

    $.ajax({
      url: "{!! url('reportaccountinghutanglph_loadvalas') !!}",
      type: "get", async: false,
      success: function (res) { dataRefresh = res || []; }
    });

    let rowTable = "";
    dataRefresh.forEach((item) => {
      rowTable += `<tr class="pick-row" onclick="buttonPilihValas('${item.Kodevls}')">
        <td>${esc(item.Kodevls)}</td>
        <td>${esc(item.NamaVls)}</td>
        <td>${esc(item.Kurs)}</td>
      </tr>`;
    });
    document.getElementById("tabel_dataSelectValas").innerHTML = rowTable;
    $("#tabelSelectValas").DataTable({ "lengthChange": false, "paging": true });
  }

  /* ── MODAL FILTER LAPORAN ──
        Supplier Awal/Akhir & Kurs Valas dipilih lewat modal picker halaman ini sendiri
        (formSelectSuppAwal/Akhir/Valas) -- BUKAN modal bersama. Membuka salah satunya
        menyembunyikan modal Filter dulu (BS4/BS5 tidak menumpuk modal dengan bersih), lalu
        dibuka lagi begitu picker ditutup. ── */
  let g_reopenFilter = false;

  function pickSuppAwal()  { g_reopenFilter = true; $('#modalFilter').modal('hide'); buttonSelectSuppAwal(); }
  function pickSuppAkhir() { g_reopenFilter = true; $('#modalFilter').modal('hide'); buttonSelectSuppAkhir(); }
  function pickValas()     { g_reopenFilter = true; $('#modalFilter').modal('hide'); buttonSelectValas(); }

  $(document).on('hidden.bs.modal', '#formSelectSuppAwal, #formSelectSuppAkhir, #formSelectValas', function () {
    if (g_reopenFilter) {
      g_reopenFilter = false;
      $('#modalFilter').modal('show');
      renderPickFields();
      updateFilterBadge();
    }
  });

  // Supplier Awal/Akhir & Perkiraan SELALU punya nilai (auto-pilih / default akun pertama) --
  // tidak ada opsi "Semua", jadi TIDAK dihitung di badge. Kurs Valas ('-' = belum dipilih)
  // PUNYA nilai netral -> dihitung saat mode $ & sudah dipilih.
  function renderPickFields() {
    let html = '';

    html += pickFieldHtml('Supplier Awal',  $('#inputSuppAwal').val(),  'pickSuppAwal');
    html += pickFieldHtml('Supplier Akhir', $('#inputSuppAkhir').val(), 'pickSuppAkhir');

    $('#pickFields').html(html);

    // Kurs Valas: tampil & diisi hanya saat Mode Valas = $
    const valasVal = $('#valas_value').val() || '-';
    const valasSet = (globalReportMode === '$' && valasVal !== '-' && valasVal !== '' && valasVal !== 'IDR');
    let vhtml = '';
    if (valasSet) {
      vhtml += '<span class="rt-combo-tag">' + esc(valasVal) +
        '<button type="button" onclick="event.stopPropagation(); clearValasField()">&times;</button></span>';
    } else {
      vhtml += '<span class="rt-combo-placeholder">Pilih valas...</span>';
    }
    vhtml += '<span class="rt-combo-chevron">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
      '</span>';
    $('#valasPickField').html(vhtml);
  }

  function pickFieldHtml(label, val, pickFn) {
    const display = (val && val !== '-') ? val : '-';
    let html = '<div>';
    html += '<label class="rt-field-label">' + label + '</label>';
    html += '<div class="rt-combo">';
    html += '<div class="rt-combo-input" onclick="' + pickFn + '()">';
    html += '<span class="rt-combo-tag">' + esc(display) + '</span>';
    html += '<span class="rt-combo-chevron">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
      '</span>';
    html += '</div></div></div>';
    return html;
  }

  function clearValasField() {
    $('#valas_value').val('-');
    renderPickFields();
    updateFilterBadge();
  }

  function updateFilterBadge() {
    let count = 0;
    const valasVal = $('#valas_value').val() || '-';
    if (globalReportMode === '$' && valasVal !== '-' && valasVal !== '' && valasVal !== 'IDR') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    setReportMode('IDR');   // juga menyembunyikan & mengosongkan Kurs Valas
    if ($('#modalPerkiraan option').length) {
      setPerkiraan($('#modalPerkiraan option').eq(0).val(), $('#modalPerkiraan option').eq(0).data('ket'));
    }
  }

  $('#modalFilter').on('show.bs.modal', function () {
    renderPickFields();
    updateFilterBadge();
  });

  // Mode Valas / Perkiraan sudah menerapkan diri sendiri langsung lewat onchange (sama seperti
  // perilaku dropdown lama) -- Terapkan hanya menutup modal.
  function applyModalFilter() {
    $('#modalFilter').modal('hide');
  }
</script>
@endsection
