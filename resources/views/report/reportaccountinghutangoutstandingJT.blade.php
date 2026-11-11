@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Hutang Outstanding JT (sisa hutang per jatuh tempo): styled .tb-report, dikelompokkan per
     supplier (namasupp) dengan subtotal per supplier + grand total. Kolom (gcart_header) kini
     interaktif lewat ReportTable (seret/gear/Reset kolom). Mode IDR / $ (valas), Perkiraan,
     Supplier Awal/Akhir & Urut pindah ke modal "Filter Laporan". --}}
<style>
  #inputReportBtn {
    border: 0; background: none; padding: 0; box-shadow: none;
    color: #495057; font-weight: 600;
  }
  #inputReportBtn:hover, #inputReportBtn:focus { color: #0d6efd; box-shadow: none; }

  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title" id="reportTitleLabel">Outstanding Jatuh Tempo</div>
      </div> --}}

      <!-- Laporan (DROPDOWN; pilih salah satu dari 3 laporan hutang) -->
      <div class="filter-wrap">
        <label>Laporan</label>
        <input type="hidden" id="inputReport" value="jt">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="inputReportBtn"
                data-bs-toggle="dropdown" aria-expanded="false"><span id="reportLabel">Outstanding Jatuh Tempo</span></button>
        <ul class="dropdown-menu" id="dropdownReport" aria-labelledby="inputReportBtn"
            style="max-height:320px; overflow:auto;"></ul>
      </div>

      <!-- Per Tanggal (JT/Nota) / Periode rentang (LHPJT — #inputDate2 tampil saat dateRange) -->
      <div class="filter-wrap">
        <label id="dateLabel">Per Tanggal</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
        <span class="filter-sep" id="dateSep" style="display:none">s/d</span>
        <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}" style="display:none">
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
      </div>

      {{-- Mode Valas, Kurs Valas, Perkiraan, Supplier Awal/Akhir & Urut pindah ke modal
           "Filter Laporan" -- lihat docs/new-filter-modal-ui-guide.md. Nilai sebenarnya tetap
           di variabel/hidden input yang sama: globalReportMode, #valas_value, #inputPerkiraan,
           #inputSuppAwal/#inputSuppAkhir, #inputOrd (semua di dalam modal sekarang). --}}

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

    <!-- TABLE (header + rows rendered dynamically from gcart_header; grouped per supplier) -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>Tanggal</th></tr>
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
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan kolom atau atur desimal &amp; total.
    </div>

  </div><!-- /content -->

  <!-- TOAST -->
  <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>

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
          <div class="rt-group-label">Mode &amp; Urutan</div>
          <div class="rt-grid-2">
            <div>
              <label class="rt-field-label" for="modalReportMode">Mode Valas</label>
              {{-- Selalu punya nilai (IDR = default) -- pilihan wajib, bukan filter yang bisa
                   dimatikan, jadi TIDAK dihitung di badge. Ganti langsung memuat ulang susunan
                   kolom (gcart_header) mode ini, sama seperti perilaku dropdown lama. --}}
              <select class="rt-native" id="modalReportMode" onchange="setReportMode(this.value)">
                <option value="IDR">IDR</option>
                <option value="$">$ (Valas)</option>
              </select>
            </div>
            <div>
              <label class="rt-field-label" for="modalOrder">Urut</label>
              <select class="rt-native" id="modalOrder" onchange="setOrderBy(this.value)">
                <option value="0">Tanggal</option>
                <option value="1">No.Nota</option>
              </select>
            </div>
          </div>

          <!-- Kurs Valas (hanya tampil saat Mode Valas = $) -->
          <div class="rt-grid-1" id="modalValasWrap" style="display:none; margin-top:10px">
            <label class="rt-field-label">Kurs Valas</label>
            <div class="rt-combo">
              <div class="rt-combo-input" onclick="pickValas()" id="valasPickField"></div>
            </div>
          </div>
          <input type="hidden" id="valas_value" value="IDR">
          <input type="hidden" id="inputOrd" value="0">
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Perkiraan</div>
          <div class="rt-grid-1">
            {{-- Diisi dari *_loadperkiraan (loadPerkiraanDropdown()). Selalu punya nilai
                 (default akun HT pertama) -- wajib, jadi TIDAK dihitung di badge. Memilih akun
                 lain otomatis menyusun ulang rentang Supplier Awal/Akhir (autoSelectSuppRange). --}}
            <select class="rt-native" id="modalPerkiraan" onchange="setPerkiraan(this.value, $(this).find(':selected').data('ket'))"></select>
          </div>
          <input type="hidden" id="inputPerkiraan" value="-">
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Supplier
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          {{-- Rentang otomatis (supplier pertama/terakhir dari akun terpilih) -- wajib punya
               nilai, jadi TIDAK dihitung di badge, sama seperti Perkiraan/Divisi. --}}
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
  let globalOrderBy = "0";        // default: Tanggal
  let globalReportMode = "IDR";   // default: IDR

  let g_reportTitle = "";
  let g_inputPerkiraan = "";

  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)

  // Mode string: 'IDR' (detail) / '$' (valas)
  // Mode NUMERIK (bukan 'IDR'/'$'): DBSIMPANHEADER.reportmode itu kolom integer, jadi
  // mode string membuat header (termasuk toggle Subtotal/Grand Total) TIDAK tersimpan —
  // saat Tampilkan ia jatuh ke setDefaultHeader() sehingga subtotal/grand total nyala lagi.
  var modereport_detail = 5, modereport_rekap = 6;
  g_modeReport = modereport_detail;
  var jenisreport = 0, DetOrRekap = 0;

  // ── PETA 3 LAPORAN HUTANG (JT = basis) ──────────────────────────────────────
  // Satu halaman; dropdown "Laporan" berpindah antar laporan. reportUrl + 4 URL voucher
  // diturunkan dari `slug` + BASE. Backend TIDAK berubah: tiap laporan tetap memakai
  // stored proc & endpoint _doReport masing-masing. loadPerkiraan/loadSuppAwal/loadValas
  // identik di 3 controller → tetap dipanggil dari endpoint JT (tidak ikut berpindah).
  const BASE = "{{ url('/') }}";
  const REPORTS = {
    jt   : { slug:'reportaccountinghutangoutstandingJT',   title:'Outstanding Jatuh Tempo',
             reportTitle:'REPORT ACCOUNTING HUTANG OUTSTANDING JT',
             file:'HutangOutstandingJT',   modeDetail:5,  modeRekap:6,  voucher:false, dateRange:false },
    nota : { slug:'reportaccountinghutangoutstandingnota', title:'Outstanding Nota',
             reportTitle:'REPORT ACCOUNTING HUTANG OUTSTANDING NOTA',
             file:'HutangOutstandingNota', modeDetail:9,  modeRekap:10, voucher:true,  dateRange:false },
    lhpjt: { slug:'reportaccountinghutanglhpjt',           title:'Hutang Per Jatuh Tempo',
             reportTitle:'REPORT ACCOUNTING HUTANG LAPORAN HUTANG PER JATUH TEMPO',
             file:'HutangJatuhTempo',      modeDetail:11, modeRekap:12, voucher:true,  dateRange:true }
  };

  // reportUrl berpindah saat laporan diganti → `let`. Di-set applyReport().
  let reportUrl    = "";
  let g_reportKey  = "jt";
  let g_hasVoucher = false;   // JT: baris tak bisa diklik; Nota/LHPJT: bisa (voucher panel)
  let g_dateRange  = false;   // LHPJT: rentang tanggal (pakai date2)
  let g_exportBase = "HutangOutstandingJT";

  // report-table.js membaca window.ReportTableConfig lazily (cfg()) saat baris diklik,
  // jadi aman menukar/mengosongkannya tiap ganti laporan (applyReport).
  window.ReportTableConfig = {};

  $(document).ready(function () {
    buildReportDropdown();             // isi dropdown Laporan dari peta REPORTS
    setOrderBy(globalOrderBy);
    applyReport(g_reportKey);          // set url/voucher/date + ID mode + setReportMode (muat gcart_header)
    loadPerkiraanDropdown();           // isi dropdown Perkiraan (default akun HT pertama)

    // Header tabel interaktif: seret kolom, menu roda gigi (sembunyikan/desimal/total).
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { applyFilters(); }
    });

    // setTimeout(() => { makeTable('REPORT'); }, 100);
  });

  /* ── kolom (gcart_header) per mode. Tabel styled DI-RENDER dari sini (Customize Table).
        Kolom nominal ditandai total (item[4]=1); Subtotal per supplier & Grand Total ikut
        toggle Customize Table. Kolom `namasupp` dipakai untuk header grup (bukan kolom). ── */
  function setDefaultHeader() {
    if (g_modeReport == modereport_detail) {
      gcart_header = [
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        ['JatuhTempo', 'Jatuh Tempo', 1, 'date', 0, 0],
        ['Nofaktur', 'No Nota', 1, 'varchar', 0, 0],
        ['Jumlah', 'Jumlah Rp', 1, 'float', 1, 2],
        ['Terbayar', 'Bayar Rp', 1, 'float', 1, 2],
        ['sisa', 'Sisa Rp', 1, 'float', 1, 2],
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;
    } else {
      gcart_header = [
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        ['JatuhTempo', 'Jatuh Tempo', 1, 'date', 0, 0],
        ['Nofaktur', 'No Nota', 1, 'varchar', 0, 0],
        ['Jumlah', 'Jumlah Rp', 1, 'float', 1, 2],
        ['Terbayar', 'Bayar Rp', 1, 'float', 1, 2],
        ['sisa', 'Sisa Rp', 1, 'float', 1, 2],
        ['JumlahD', 'Jumlah $', 1, 'float', 1, 2],
        ['TerbayarD', 'Bayar $', 1, 'float', 1, 2],
        ['sisaD', 'Sisa $', 1, 'float', 1, 2],
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;
    }
  }

  /* ── toolbar controls ── */
  function showPeriode() { globalDate1 = $('#inputDate1').val(); }

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
    doSetHeader(g_modeReport);   // muat susunan kolom mode ini (default / kustomisasi tersimpan)
    doShowCustomize();
  }

  // val: '0' (Tanggal) / '1' (No.Nota). Dipanggil oleh <select id="modalOrder">.
  function setOrderBy(val) {
    globalOrderBy = val;
    $('#inputOrd').val(val);
    $('#modalOrder').val(val);
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
    const header = ['Supplier'].concat(cols.map(c => c[1]));
    const body = (lastRows || []).map(r => [str(pickCI(r, 'namasupp'))].concat(cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    })));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: g_exportBase + '_' + (globalDate1 || '') + (g_dateRange ? '_' + (globalDate2 || '') : '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (sp_ReportSisaHutang; doReport mengembalikan array biasa) ── */
  function makeTable(_mode) {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
    // g_reportTitle di-set oleh applyReport() sesuai laporan aktif.

    let _perk   = $('#inputPerkiraan').val() || '-';
    let _suppAw = $('#inputSuppAwal').val()  || '-';
    let _suppAk = $('#inputSuppAkhir').val() || '-';
    let _ord    = $('#inputOrd').val();
    let _valas  = $('#valas_value').val();

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

  /* ── VOUCHER (hanya laporan dengan g_hasVoucher = Nota/LHPJT) ──
     No Nota clickable hanya untuk nomor voucher betulan (mengandung '/', bukan baris
     "Saldo Awal"). Membuka panel voucher bawah (report-table.js); Jenis diambil dari
     nomor via jenisFromNo. JT (g_hasVoucher=false) → baris statis, helper ini tak dipakai. ── */
  function isVoucherNo(v) {
    const s = str(v);
    if (!s || s.indexOf('/') === -1) return false;
    return s.toUpperCase().indexOf('SALDO AWAL') === -1;
  }
  function voucherCell(v) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<td>' + nullToEmpty(v) + '</td>';
    return '<td class="kas-clickable" style="color:#0d6efd;text-decoration:underline">' + nullToEmpty(v) + '</td>';
  }
  function voucherRowOpen(v, cls) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<tr class="' + cls + '">';
    const jn  = (typeof jenisFromNo === 'function') ? jenisFromNo(s) : '';
    const ttl = (typeof jenisTitle === 'function') ? jenisTitle(jn) : 'Voucher';
    const esc = s.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    const jsc = String(jn).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    return '<tr class="' + cls + '" title="Klik untuk lihat ' + ttl + ' ' + s + '" ' +
           'onclick="openVoucher(\'' + esc + '\',\'' + jsc + '\')">';
  }

  /* ── RENDER: dikelompokkan per supplier (kolom `namasupp`) ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');

    const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
    const totalKeys = totalCols.map(c => c[0]);
    const hasTotal  = totalCols.length > 0;
    const showSub   = hasTotal && (gsum_issubtotal === 1);
    const showGrand = hasTotal && (gsum_isgrandtotal === 1);
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
    // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    thead.innerHTML = ReportTable.headHtml(cols);

    // kelompokkan per supplier (namasupp), pertahankan urutan kemunculan
    const order = [], buckets = {};
    (lastRows || []).forEach(r => {
      if (search && rowSearchText(r, cols).indexOf(search) === -1) return;
      const gkey = str(pickCI(r, 'namasupp'));
      if (!(gkey in buckets)) { buckets[gkey] = []; order.push(gkey); }
      buckets[gkey].push(r);
    });

    if (!order.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    let html = '';
    const grand = {}; totalKeys.forEach(k => grand[k] = 0);
    let visible = 0;

    order.forEach(gkey => {
      const rows = buckets[gkey];
      const label = (gkey !== '' ? gkey : '(Tanpa Nama)');

      html += '<tr class="group-row"><td colspan="' + cols.length + '">' + esc(label) +
        ' <span style="font-size:11px;font-weight:600;opacity:.7;margin-left:8px">(' + rows.length + ' transaksi)</span></td></tr>';

      const sub = {}; totalKeys.forEach(k => sub[k] = 0);
      rows.forEach(r => {
        totalKeys.forEach(k => { const v = currencyNormalizer(pickCI(r, k)); sub[k] += v; grand[k] += v; });
        const openTag = g_hasVoucher ? voucherRowOpen(pickCI(r, 'nofaktur'), 'data-row') : '<tr class="data-row">';
        html += openTag + cols.map(function (c) {
          const key = c[0], type = c[3];
          const v = pickCI(r, key);
          if (type === 'date') return '<td>' + format_date(v) + '</td>';
          if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(v), c[5]) + '</td>';
          if (g_hasVoucher && String(key).toLowerCase() === 'nofaktur') return voucherCell(v);
          return '<td>' + nullToEmpty(v) + '</td>';
        }).join('') + '</tr>';
        visible++;
      });

      if (showSub) html += totalRow('Subtotal', sub, cols, totalKeys, 'subtotal-row');
    });

    if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + visible + ' baris';
  }

  // Label baris total menempati SELURUH kolom non-total yang berurutan mulai dari kolom
  // non-total pertama (bukan cuma satu sel sempit), supaya tidak wrap.
  function totalRow(label, sums, cols, totalKeys, cls) {
    const labelIdx = cols.findIndex(c => totalKeys.indexOf(c[0]) === -1);
    let span = 0;
    for (let i = labelIdx; i < cols.length && totalKeys.indexOf(cols[i][0]) === -1; i++) { span++; }

    const tds = [];
    for (let idx = 0; idx < cols.length; idx++) {
      const c = cols[idx];
      if (totalKeys.indexOf(c[0]) !== -1) { tds.push('<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>'); continue; }
      if (idx === labelIdx) { tds.push('<td colspan="' + span + '">' + label + '</td>'); idx += span - 1; continue; }
      tds.push('<td></td>');
    }
    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  /* ── PENCARIAN SISI-KLIEN ── */
  function applyFilters() { render(); }

  function rowSearchText(r, cols) {
    let s = str(pickCI(r, 'namasupp'));   // ikutkan nama supplier
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

  function getKolomFilter() { return ['Tanggal', 'Nofaktur']; }

  /* ── PERKIRAAN (akun HT; default akun pertama) — modal Filter Laporan, <select id="modalPerkiraan"> ── */
  function loadPerkiraanDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('reportaccountinghutangoutstandingJT_loadperkiraan') !!}",
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

    // supplier difilter per perkiraan → auto-pilih rentang: Awal = supplier pertama, Akhir = terakhir
    autoSelectSuppRange();
    renderPickFields();
    updateFilterBadge();
  }

  /* ── DROPDOWN LAPORAN (peta REPORTS; JT = basis) ── */
  function buildReportDropdown() {
    let html = '';
    Object.keys(REPORTS).forEach((key) => {
      html += '<li><a class="dropdown-item report-item" style="cursor:pointer" data-key="' + key + '">' +
        REPORTS[key].title + ' <span class="checkmark-red" style="display:none">&#10003;</span></a></li>';
    });
    $('#dropdownReport').html(html);
  }

  // Set url report + config voucher + judul + basis export + UI tanggal + ID mode sesuai
  // laporan terpilih, lalu muat ulang header (setReportMode). TIDAK memuat data sendiri.
  function applyReport(key) {
    const r = REPORTS[key];
    if (!r) return;
    g_reportKey       = key;
    reportUrl         = BASE + '/' + r.slug + '_doReport';
    modereport_detail = r.modeDetail;
    modereport_rekap  = r.modeRekap;
    g_hasVoucher      = !!r.voucher;
    g_dateRange       = !!r.dateRange;
    g_exportBase      = r.file;
    g_reportTitle     = r.reportTitle;

    // voucher panel: hanya Nota/LHPJT (JT tidak bisa diklik → config dikosongkan)
    if (g_hasVoucher) {
      window.ReportTableConfig = {
        kasUrl    : BASE + '/' + r.slug + '_doKasharian',
        invoiceUrl: BASE + '/' + r.slug + '_doInvoice',
        lpbUrl    : BASE + '/' + r.slug + '_doLpb',
        bpUrl     : BASE + '/' + r.slug + '_doBp'
      };
    } else {
      window.ReportTableConfig = {};
    }

    // UI tanggal: rentang (Periode) untuk LHPJT, tunggal (Per Tanggal) untuk lainnya
    $('#dateLabel').text(g_dateRange ? 'Periode' : 'Per Tanggal');
    $('#dateSep').toggle(g_dateRange);
    $('#inputDate2').toggle(g_dateRange);

    // label + centang dropdown
    $('#inputReport').val(key);
    $('#reportTitleLabel').text(r.title);
    $('#reportLabel').text(r.title);
    $('#inputReportBtn').attr('title', r.title);
    $('#dropdownReport .checkmark-red').hide();
    $(`#dropdownReport .report-item[data-key='${key}'] .checkmark-red`).show();

    // muat susunan kolom untuk ID mode laporan ini (Customize Table per laporan)
    setReportMode(globalReportMode);
  }

  // Ganti laporan hanya menyetel konfigurasi/kolom — TIDAK memuat data. Data dimuat
  // saat user klik "Tampilkan" (makeTable). Auto-load awal tetap di $(document).ready.
  function setReport(key) {
    applyReport(key);
  }

  $(document).on('click', '#dropdownReport .report-item', function () {
    setReport($(this).data('key'));
  });

  /* ── AUTO-PILIH RENTANG SUPPLIER ──
     Isi Supp Awal = supplier pertama, Supp Akhir = supplier terakhir dari list akun
     (perkiraan) terpilih. List diurut per KodeCustsupp di endpoint, jadi pertama = kode
     terendah, terakhir = kode tertinggi. Dipanggil saat load & setiap ganti Perkiraan. ── */
  function autoSelectSuppRange() {
    let perkiraan = $("#inputPerkiraan").val();
    let list = [];
    $.ajax({
      url: "{!! url('reportaccountinghutangoutstandingJT_loadsuppawal') !!}",
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
  function buttonPilihSuppAwal(kode) {
    $("#inputSuppAwal").val(kode); $("#formSelectSuppAwal").modal('hide');
    renderPickFields(); updateFilterBadge();
  }

  function loadSelectSuppAwal() {
    let perkiraan = $("#inputPerkiraan").val();
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAwal')) { $('#tabelSelectSuppAwal').DataTable().destroy(); }

    $.ajax({
      url: "{!! url('reportaccountinghutangoutstandingJT_loadsuppawal') !!}",
      type: "get", async: false, data: { perkiraan: perkiraan },
      success: function (res) { dataRefresh = res || []; }
    });

    let rowTable = "";
    dataRefresh.forEach((item) => {
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
    let perkiraan = $("#inputPerkiraan").val();
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAkhir')) { $('#tabelSelectSuppAkhir').DataTable().destroy(); }

    $.ajax({
      url: "{!! url('reportaccountinghutangoutstandingJT_loadsuppawal') !!}",
      type: "get", async: false, data: { perkiraan: perkiraan },
      success: function (res) { dataRefresh = res || []; }
    });

    let rowTable = "";
    dataRefresh.forEach((item) => {
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
      url: "{!! url('reportaccountinghutangoutstandingJT_loadvalas') !!}",
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
        (formSelectSuppAwal/Akhir/Valas) -- BUKAN modal bersama seperti modalAccountingJurnal.
        Membuka salah satunya menyembunyikan modal Filter dulu (BS4/BS5 tidak menumpuk modal
        dengan bersih), lalu dibuka lagi begitu picker ditutup. ── */
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
  // tidak ada opsi "Semua", jadi TIDAK dihitung di badge (sama seperti Divisi di halaman lain).
  // Kurs Valas ('-' = belum dipilih) PUNYA nilai netral -> dihitung saat mode $ & sudah dipilih.
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
    setOrderBy('0');
    setReportMode('IDR');   // juga menyembunyikan & mengosongkan Kurs Valas
    if ($('#modalPerkiraan option').length) {
      setPerkiraan($('#modalPerkiraan option').eq(0).val(), $('#modalPerkiraan option').eq(0).data('ket'));
    }
  }

  $('#modalFilter').on('show.bs.modal', function () {
    renderPickFields();
    updateFilterBadge();
  });

  // Mode Valas / Urut / Perkiraan sudah menerapkan diri sendiri langsung lewat onchange
  // (sama seperti perilaku dropdown lama) -- Terapkan hanya menutup modal.
  function applyModalFilter() {
    $('#modalFilter').modal('hide');
  }
</script>
@endsection
