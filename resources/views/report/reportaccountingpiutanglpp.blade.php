@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Piutang LPP (rekap saldo & umur piutang per pelanggan): styled .tb-report, satu baris per
     pelanggan (kode/nama = kolom data), tabel RATA (tanpa grup) + Grand Total. Kolom: Saldo Awal,
     Penjualan, Pelunasan, Retur, Saldo Akhir, Titipan, Saldo, + bucket umur <0/1-30/31-60/61-90/>90.
     Di atas tabel: chart Aging Piutang (doughnut) + Piutang per Pelanggan Top 10 (bar).
     Perkiraan / mode Valas (IDR/$) & Kurs Valas / Plgn Awal / Plgn Akhir semua ada di modal
     "Filter Laporan"; "Tampilan" di bar tabel interaktif mencerminkan mode Valas. Klik baris
     pelanggan → buka Kartu Piutang (ledger, SP Sp_ReportKartuPiutang) di panel kanan; kolom
     No Nota di ledger → buka Faktur Penjualan (INVC) di panel bawah. --}}

<!-- Chart.js v4 (di-bundle lokal: public/plugins/chart.js/chart.umd.min.js) -->
<script src="{!! URL::asset('plugins/chart.js/chart.umd.min.js') !!}?v={{ @filemtime(base_path('public/plugins/chart.js/chart.umd.min.js')) ?: '1' }}"></script>

<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }

  /* ── Chart section (di atas tabel) — tanpa kartu KPI ── */
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
        <div class="page-title">Analisa Piutang LPP</div>
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

      {{-- Mode Valas (IDR/$), Kurs Valas, Perkiraan & Plgn Awal/Akhir dipindah ke modal
           "Filter Laporan" (lihat di luar .tb-report). Nilai sebenarnya tetap di input hidden
           #valas_value / #inputPerkiraan / #inputSuppAwal / #inputSuppAkhir, dibaca makeTable(). --}}
      <input type="hidden" id="valas_value" value="IDR">
      <input type="hidden" id="inputPerkiraan" value="-">
      <input type="hidden" id="inputSuppAwal" value="-">
      <input type="hidden" id="inputSuppAkhir" value="-">

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

    <!-- Bar kolom tersembunyi + Tampilan (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- Petunjuk drill -->
    <div style="font-size:12px;color:var(--muted);margin:0 0 12px 2px">
      <i class="bi bi-lightbulb-fill text-warning"></i>
      Klik baris pelanggan untuk melihat Kartu Piutang, lalu klik No Nota untuk membuka Faktur Penjualan.
    </div>

    <!-- CHARTS (dibangun sisi-klien dari data yang dimuat) -->
    <div class="chart-grid" id="chartGrid">
      <div class="chart-box">
        <h3>Aging Piutang</h3>
        <div class="chart-holder"><canvas id="agingChart"></canvas></div>
      </div>
      <div class="chart-box">
        <h3>Piutang per Pelanggan (Top 10)</h3>
        <div class="chart-holder"><canvas id="topCustomerChart"></canvas></div>
      </div>
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

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk
      sembunyikan kolom atau atur desimal &amp; total.
    </div>

  </div><!-- /content -->

  <!-- DRILL OVERLAY + PANEL (Kartu Piutang per pelanggan, geser dari kanan) -->
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

<!-- modal select pelanggan awal -->
<div class="modal fade rt-picker-v2" id="formSelectSuppAwal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Pelanggan Awal</h5>
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

<!-- modal select pelanggan akhir -->
<div class="modal fade rt-picker-v2" id="formSelectSuppAkhir" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Pelanggan Akhir</h5>
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
             $.fn.modal milik BS4 (jQuery dimuat sesudah bundle BS5). data-bs-dismiss dibiarkan
             untuk jaga-jaga. Lihat new-design-all-report-guide.md §5.1. --}}
        <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Pengaturan Laporan</div>
          {{-- Perkiraan satu kolom penuh (bukan rt-grid-2 -- tak ada kelas "1 kolom" di
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
            {{-- Muncul di sebelah Valas hanya saat mode $ dipilih (lihat 'change' handler
                 #modalReportMode di jsreport). --}}
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
          {{-- Default rentang penuh (Awal = pelanggan pertama, Akhir = pelanggan terakhir utk
               Perkiraan aktif) diisi otomatis oleh loadSuppList() -- lihat catatan di jsreport. --}}
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
  let globalDate2 = "{!! date('Y-m-d') !!}";
  let globalReportMode = "IDR";   // default: IDR

  let g_reportTitle = "";
  let g_inputPerkiraan = "";

  let lastRows = [];    // hasil fetch terakhir (dipakai render / search / chart)
  let shownRows = [];   // baris yang sedang tampil (lolos search) — dipakai openLedger by index

  // Konteks filter SAAT tabel dimuat (dipakai openLedger agar kartu piutang konsisten dgn
  // tabel yang tampil — bukan nilai input yang mungkin diubah user tanpa klik Tampilkan).
  let g_loadedPerkiraan = '-', g_loadedValas = 'IDR';

  // Daftar pelanggan utk perkiraan aktif (urut KodeCustsupp dari SP) — dipakai utk auto-pilih
  // rentang penuh Plgn Awal/Akhir (lihat loadSuppList()) & mengisi modal pilih pelanggan.
  let g_suppList = [];

  let perkiraanList = [];   // daftar akun dari loadPerkiraanDropdown (dipakai resetAllFilters)

  // Mode NUMERIK: DBSIMPANHEADER.reportmode itu kolom integer, jadi mode string membuat
  // header (termasuk toggle Grand Total) TIDAK tersimpan. Int di sini scoped per href.
  var modereport_detail = 17, modereport_rekap = 18;
  g_modeReport = modereport_detail;
  var jenisreport = 0, DetOrRekap = 0;

  const reportUrl = "{{ url('reportaccountingpiutanglpp_doReport') }}";
  const kartuUrl  = "{{ url('reportaccountingpiutanglpp_doKartu') }}";   // ledger 1 pelanggan

  // Panel voucher bawah (report-table.js). No Nota → jenisFromNo → INVC → Faktur Penjualan (doInvoice).
  window.ReportTableConfig = {
    kasUrl    : "{{ url('reportaccountingpiutanglpp_doKasharian') }}",
    invoiceUrl: "{{ url('reportaccountingpiutanglpp_doInvoice') }}",
    lpbUrl    : "{{ url('reportaccountingpiutanglpp_doLpb') }}",
    bpUrl     : "{{ url('reportaccountingpiutanglpp_doBp') }}"
  };

  $(document).ready(function () {
    setReportMode(globalReportMode);   // set mode + muat gcart_header
    loadPerkiraanDropdown();           // isi dropdown Perkiraan (default akun PT pertama)

    // Header tabel interaktif (drag/gear/hide/decimal/total). "Tampilan" di sini hanya
    // MENCERMINKAN select "Valas" di modal Filter Laporan -- keduanya lewat setReportMode(),
    // tidak ada state baru.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render,
      views: {
        label: 'Tampilan',
        options: [
          { value: 'IDR', label: 'IDR',       desc: 'Rincian dalam Rupiah' },
          { value: '$',   label: 'Valas ($)', desc: 'Termasuk kolom $ & kurs' }
        ],
        get: function () { return globalReportMode; },
        set: function (v) {
          setReportMode(String(v));
          if (lastRows.length) { makeTable('REPORT'); }
        }
      }
    });

    // Tidak auto-load: tabel dimuat hanya saat user klik "Tampilkan" (makeTable).
  });

  /* ── kolom (gcart_header). Tabel styled DI-RENDER dari sini (Customize Table).
        Satu baris per pelanggan → kode & nama TETAP kolom data. Kolom nominal ditandai
        total (item[4]=1) → ikut Grand Total. Bucket umur (saldo0..saldo121) diwarnai.
        Kolom detail & rekap identik (SP hanya berganti mata uang lewat KodeVls). ── */
  function setDefaultHeader() {
    gcart_header = [
      ['kode', 'Kode', 1, 'varchar', 0, 0],
      ['nama', 'Nama', 1, 'varchar', 0, 0],
      ['awal', 'Saldo Awal', 1, 'float', 1, 2],
      ['Jumlah', 'Penjualan', 1, 'float', 1, 2],
      ['pelunasan', 'Pelunasan', 1, 'float', 1, 2],
      ['retur', 'Retur', 1, 'float', 1, 2],
      ['saldoakhir', 'Saldo Akhir', 1, 'float', 1, 2],
      ['titip', 'Titipan', 1, 'float', 1, 2],
      ['akhir', 'Saldo', 1, 'float', 1, 2],
      ['saldo0', '<0', 1, 'float', 1, 2],
      ['saldo30', '1 - 30', 1, 'float', 1, 2],
      ['saldo60', '31 - 60', 1, 'float', 1, 2],
      ['saldo90', '61 - 90', 1, 'float', 1, 2],
      ['saldo121', '>90', 1, 'float', 1, 2],
    ];
    gsum_issubtotal = 0; gsum_isgrandtotal = 1;
  }

  /* ── toolbar controls ── */
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
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
    doSetHeader(g_modeReport);   // muat susunan kolom (default / kustomisasi tersimpan)
    doShowCustomize();
  }

  /* ── FILTER MODAL (Perkiraan, Valas, Plgn Awal/Akhir) ──
     Nilai sebenarnya tetap di input hidden #inputPerkiraan / #valas_value / #inputSuppAwal /
     #inputSuppAkhir (dibaca makeTable(), ditulis di sini / buttonPilihSuppAwal /
     buttonPilihSuppAkhir / buttonPilihValas / loadSuppList). Kontrol di modal (#modalPerkiraan,
     #modalReportMode, .rt-combo) hanya tampilan pending di atasnya sampai "Terapkan" diklik --
     KECUALI Plgn Awal/Akhir yang tetap commit langsung (sama seperti perilaku lama: ganti
     Perkiraan langsung auto-pilih rentang penuh pelanggan lewat loadSuppList()). ── */
  const PICK_FIELDS = [
    { id: 'inputSuppAwal',  label: 'Plgn Awal',  open: 'suppAwal' },
    { id: 'inputSuppAkhir', label: 'Plgn Akhir', open: 'suppAkhir' },
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

  // Kotak Kurs Valas (di sebelah select Valas, hanya tampil saat mode $) -- pola sama dengan
  // renderPickFields(), tapi nilainya #valas_value (bukan salah satu PICK_FIELDS) dan langsung
  // ter-commit begitu dipilih (bukan menunggu Terapkan), sama seperti Plgn Awal/Akhir.
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

  // Perkiraan: pilihan wajib (tanpa opsi netral seperti "Semua") -- sengaja tidak dihitung.
  // Plgn Awal/Akhir: netralnya adalah "rentang penuh" (bukan '-'), auto-terisi oleh
  // loadSuppList() setiap ganti Perkiraan -- bukan filter yang "dinyalakan" user, jadi juga
  // tidak dihitung (beda dari PICK_FIELDS di halaman lain yang defaultnya '-'/kosong).
  // Valas: IDR = netral (default), jadi cuma dihitung saat pindah ke $.
  function updateFilterBadge() {
    let count = 0;
    if ($('#modalReportMode').val() !== 'IDR') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    if (perkiraanList.length) {
      $('#modalPerkiraan').val(perkiraanList[0].Perkiraan);
      loadSuppList(perkiraanList[0].Perkiraan);
    }
    $('#modalReportMode').val('IDR');
    $('#modalValasWrap').hide();
    $('#valas_value').val('IDR');
    renderPickFields();
    renderValasPick();
    updateFilterBadge();
  }

  // Saat modal Filter dibuka ulang otomatis sesudah picker Plgn Awal/Akhir/Valas ditutup (lihat
  // pickFromModal / hidden.bs.modal di bawah), JANGAN timpa ulang pilihan pending
  // (Perkiraan/Valas) dari nilai yang sudah di-Terapkan -- kalau tidak, pilihan Perkiraan yang
  // belum di-Terapkan hilang begitu user selesai memilih Plgn Awal/Akhir.
  // g_reopeningFilter ditandai true sesaat sebelum modal dibuka ulang di jalur itu saja.
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

  // Ganti Perkiraan (pending, belum Terapkan): muat ulang daftar pelanggan & auto-pilih
  // rentang penuh (Awal = kode pertama, Akhir = kode terakhir) ke #inputSuppAwal/#inputSuppAkhir
  // langsung -- sama seperti perilaku lama saat Perkiraan diganti di toolbar. "+" tetap bisa
  // dipakai sesudahnya utk mempersempit ke 1 pelanggan.
  $('#modalFilter').on('change', '#modalPerkiraan', function () {
    loadSuppList($(this).val());
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
    const ket  = $('#modalPerkiraan option:selected').data('ket') || '';
    setPerkiraan(kode, ket);
    if ($('#modalReportMode').length) { setReportMode($('#modalReportMode').val()); }
    $('#modalFilter').modal('hide');
  }

  // Jembatan ke modal pilih Plgn Awal/Akhir/Valas: sembunyikan modal Filter dulu (hindari
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
    ReportExport.fromRows(rows, fmt, { customName: 'PiutangLPP_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (sp_ReportSaldoHutang Tipe=1; doReport mengembalikan array biasa) ── */
  function makeTable(_mode) {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
    g_reportTitle = 'REPORT ACCOUNTING PIUTANG LPP';

    let _perk   = $('#inputPerkiraan').val() || '-';
    let _suppAw = $('#inputSuppAwal').val()  || '-';
    let _suppAk = $('#inputSuppAkhir').val() || '-';
    let _valas  = $('#valas_value').val();

    // simpan konteks yang dipakai untuk memuat tabel → dipakai openLedger (kartu piutang)
    g_loadedPerkiraan = _perk;
    g_loadedValas = _valas;

    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = {
      date1: globalDate1, date2: globalDate2,
      inputSuppAwal: _suppAw, inputSuppAkhir: _suppAk,
      inputPerkiraan: _perk, valas_value: _valas
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


  // Warna bucket umur — SATU sumber dipakai header+kolom tabel dan doughnut.
  // <0 (saldo0) tak ada di doughnut → abu slate; sisanya hijau→merah sesuai keparahan.
  const AGING_COLORS = {
    saldo0:   '#64748b',
    saldo30:  '#16a34a',
    saldo60:  '#ca8a04',
    saldo90:  '#ea580c',
    saldo121: '#dc2626',
  };
  function agingStyle(key) { return AGING_COLORS[key] ? ' style="color:' + AGING_COLORS[key] + '"' : ''; }

  /* ── LEDGER (Kartu Piutang) + VOUCHER ─────────────────────────────────────
     Klik baris pelanggan → ambil kartu piutang pelanggan itu (Sp_ReportKartuPiutang via
     doKartu) → tampil di panel kanan. Kolom No Nota bisa diklik → Faktur Penjualan (INVC). ── */
  function escapeJs(s) { return String(s == null ? '' : s).replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
  function isVoucherNo(v) {
    const s = str(v);
    if (!s || s.indexOf('/') === -1) return false;
    return s.toUpperCase().indexOf('SALDO AWAL') === -1;
  }
  // Sel No Nota: klik → panel voucher bawah (report-table.js). Hanya nomor voucher betulan.
  function voucherCell(v) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<td>' + nullToEmpty(v) + '</td>';
    const jn  = (typeof jenisFromNo === 'function') ? jenisFromNo(s) : '';
    const ttl = (typeof jenisTitle === 'function') ? jenisTitle(jn) : 'Voucher';
    return '<td class="kas-clickable" style="cursor:pointer;color:#0d6efd;text-decoration:underline" ' +
           'title="Klik untuk lihat ' + ttl + ' ' + s + '" ' +
           'onclick="openVoucher(\'' + escapeJs(s) + '\',\'' + escapeJs(jn) + '\')">' + nullToEmpty(v) + '</td>';
  }

  // Kolom ledger mengikuti mode valas (mirror Piutang Kartu). type: date/voucher/text/num; total=1 → ikut footer.
  function ledgerCols() {
    const base = [
      ['Tanggal', 'Tanggal', 'date', 0],
      ['NoFaktur', 'No Nota', 'voucher', 0],
      ['NoBukti', 'No Bukti', 'text', 0],
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
    const kode = str(pickCI(r, 'kode'));
    const nama = str(pickCI(r, 'nama')) || kode || '(Tanpa Nama)';

    document.getElementById('dpTitle').textContent = nama;
    document.getElementById('dpSub').textContent = 'Kode: ' + (kode || '-');

    // meta ringkas dari baris LPP (saldo + titipan + bucket umur)
    const metaDefs = [
      ['saldoakhir', 'Saldo Akhir'], ['titip', 'Titipan'], ['akhir', 'Saldo'],
      ['saldo0', '<0'], ['saldo30', '1 - 30'], ['saldo60', '31 - 60'], ['saldo90', '61 - 90'], ['saldo121', '>90'],
    ];
    document.getElementById('dpMeta').innerHTML = metaDefs.map(function (m, i) {
      const v = currencyNormalizer(pickCI(r, m[0]));
      const big = (i === 0) ? ' style="font-size:16px"' : '';
      return '<div class="dp-meta-item"><span class="dp-meta-label">' + m[1] + '</span>' +
             '<span class="dp-meta-val ' + (v < 0 ? 'neg' : '') + '"' + big + '>' + format_number(v, 0) + '</span></div>';
    }).join('');

    document.getElementById('dpBody').innerHTML = '<div class="dp-section-title">' + loadingHtml('Memuat kartu piutang...') + '</div>';
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
          '<div style="padding:12px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;color:#B91C1C;font-size:12.5px">Gagal memuat kartu piutang.</div>';
      }
    });
  }

  function renderKartuBody(nama, rows) {
    const cols = ledgerCols();
    const totals = {}; cols.forEach(c => { if (c[3] === 1) totals[c[0]] = 0; });

    // Saldo berjalan (Saldo Rp / Saldo $): kartu 1 pelanggan → satu urutan menerus (tanpa
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
    const tfoot = '<tr class="ledger-total"><td colspan="3" style="font-weight:800">Total</td>' +
      cols.slice(3).map(function (c) {
        if (isRunningSaldo(c[0])) return '<td class="num">' + format_number(endRun[c[0]] || 0, 0) + '</td>';
        return '<td class="num">' + (c[3] === 1 ? format_number(totals[c[0]], 0) : '') + '</td>';
      }).join('') + '</tr>';

    document.getElementById('dpBody').innerHTML =
      '<div class="dp-section-title">Kartu Piutang - ' + rows.length + ' transaksi</div>' +
      '<div style="overflow-x:auto"><table class="ledger-table">' +
      '<thead>' + thead + '</thead><tbody>' + body + '</tbody><tfoot>' + tfoot + '</tfoot></table></div>';
  }

  function closeDrill() {
    document.getElementById('drillOverlay').classList.remove('open');
    document.getElementById('drillPanel').classList.remove('open');
  }

  /* ── RENDER: tabel RATA (satu baris per pelanggan, tanpa grup) + Grand Total.
     Kolom dinamis dari gcart_header (item[2]===1). Bucket umur diwarnai (header + nilai). ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');

    const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
    const totalKeys = totalCols.map(c => c[0]);
    const hasTotal  = totalCols.length > 0;
    const showGrand = hasTotal && (gsum_isgrandtotal === 1);
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    // HEADER dinamis + interaktif (drag/gear/hide/desimal/total) lewat ReportTable.
    // headHtml() tak punya hook utk warna per-kolom, jadi warnai label bucket umur sesudahnya
    // dengan patch DOM (urutan <th> yang dihasilkan sama dgn urutan `cols`).
    thead.innerHTML = ReportTable.headHtml(cols);
    const ths = thead.querySelectorAll('th.rt-th');
    cols.forEach(function (c, i) {
      if (AGING_COLORS[c[0]] && ths[i]) {
        const lbl = ths[i].querySelector('.th-label');
        if (lbl) lbl.style.color = AGING_COLORS[c[0]];
      }
    });

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
      html += '<tr class="data-row" style="cursor:pointer" title="Klik untuk melihat Kartu Piutang" onclick="openLedger(' + idx + ')">' + cols.map(function (c) {
        const type = c[3];
        const v = pickCI(r, c[0]);
        if (type === 'date') return '<td>' + format_date(v) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num"' + agingStyle(c[0]) + '>' + format_number(currencyNormalizer(v), c[5]) + '</td>';
        if (c[0] === 'nama') return '<td style="white-space:nowrap">' + nullToEmpty(v) + '<span class="drill-hint"><i class="bi bi-arrow-right-short"></i> kartu</span></td>';
        return '<td>' + nullToEmpty(v) + '</td>';
      }).join('') + '</tr>';
    });

    if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';

    buildCharts(rows);
  }

  // Baris total: nilai di tiap kolom pada `sumKeys`; label di kolom pertama non-sum. Bucket diwarnai.
  function totalRow(label, sums, cols, sumKeys, cls) {
    const labelIdx = cols.findIndex(c => sumKeys.indexOf(c[0]) === -1);
    const tds = cols.map(function (c, idx) {
      if (sumKeys.indexOf(c[0]) !== -1) return '<td class="num"' + agingStyle(c[0]) + '>' + format_number(sums[c[0]], c[5]) + '</td>';
      if (idx === labelIdx) return '<td>' + label + '</td>';
      return '<td></td>';
    });
    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  /* ── CHARTS (Chart.js v4) ────────────────────────────────────────────────
     Kiri  : Aging Piutang (doughnut) — 4 bucket 1-30 / 31-60 / 61-90 / >90 (bucket <0 tak
             ditampilkan di chart; tetap ada di tabel).
     Kanan : Piutang per Pelanggan Top 10 (bar horizontal) — Saldo Akhir per pelanggan, desc. ── */
  const CHART_PALETTE = ['#4F46E5','#7C3AED','#DB2777','#2563eb','#16a34a','#ca8a04','#ea580c','#0891b2','#e11d48','#65a30d'];
  const AGING_DEFS = [
    ['1 - 30',  'saldo30',  AGING_COLORS.saldo30],
    ['31 - 60', 'saldo60',  AGING_COLORS.saldo60],
    ['61 - 90', 'saldo90',  AGING_COLORS.saldo90],
    ['>90',     'saldo121', AGING_COLORS.saldo121],
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
      const cust = [];
      (rows || []).forEach(r => {
        AGING_DEFS.forEach((d, i) => { aging[i] += currencyNormalizer(pickCI(r, d[1])); });
        cust.push([str(pickCI(r, 'nama')) || str(pickCI(r, 'kode')) || '(Tanpa Nama)', currencyNormalizer(pickCI(r, 'saldoakhir'))]);
      });
      const top = cust.sort((a, b) => b[1] - a[1]).slice(0, 10);

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

      // ── Top 10 pelanggan bar (horizontal) ──
      _destroyChart('topCustomer');
      _charts.topCustomer = new Chart(document.getElementById('topCustomerChart'), {
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

  function getKolomFilter() { return ['kode', 'nama']; }

  /* ── DROPDOWN PERKIRAAN (akun PT; default akun pertama) ── */
  function loadPerkiraanDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('reportaccountingpiutanglpp_loadperkiraan') !!}",
      type: "get", async: false,
      success: function (res) { list = res || []; }
    });

    perkiraanList = list;

    let html = '';
    list.forEach((item) => {
      const ket = (item.Keterangan != null ? String(item.Keterangan) : '');
      html += '<option value="' + item.Perkiraan + '" data-ket="' + ket.replace(/"/g, '&quot;') + '">' +
        item.Perkiraan + ket + '</option>';
    });
    $("#modalPerkiraan").html(html);

    if (list.length) {
      setPerkiraan(list[0].Perkiraan, list[0].Keterangan != null ? list[0].Keterangan : '');
      loadSuppList(list[0].Perkiraan);   // muat awal: auto-pilih rentang penuh pelanggan
    }
  }

  function setPerkiraan(kode, ket) {
    $("#inputPerkiraan").val(kode);
    $("#modalPerkiraan").val(kode);
    g_inputPerkiraan = kode + (ket ? ' - ' + ket : '');
  }

  // Daftar pelanggan utk perkiraan aktif (dipakai jg oleh modal pilih Plgn Awal/Akhir, tanpa
  // fetch ulang). Auto-pilih rentang penuh: Awal = kode pertama, Akhir = kode terakhir (list
  // sudah terurut KodeCustsupp dari SP) — "+" tetap bisa dipakai utk mempersempit ke 1 pelanggan.
  // Dipanggil saat load awal & tiap kali #modalPerkiraan berganti (pending, lihat FILTER MODAL).
  function loadSuppList(perkiraan) {
    g_suppList = [];
    $.ajax({
      url: "{!! url('reportaccountingpiutanglpp_loadsuppawal') !!}",
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

  /* ── MODAL PELANGGAN AWAL ── */
  function buttonSelectSuppAwal() { loadSelectSuppAwal(); $("#formSelectSuppAwal").modal('toggle'); }
  function buttonPilihSuppAwal(kode) { $("#inputSuppAwal").val(kode); $("#formSelectSuppAwal").modal('hide'); }

  function loadSelectSuppAwal() {
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAwal')) { $('#tabelSelectSuppAwal').DataTable().destroy(); }

    let rowTable = "";
    g_suppList.forEach((item) => {
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

  /* ── MODAL PELANGGAN AKHIR ── */
  function buttonSelectSuppAkhir() { loadSelectSuppAkhir(); $("#formSelectSuppAkhir").modal('toggle'); }
  function buttonPilihSuppAkhir(kode) { $("#inputSuppAkhir").val(kode); $("#formSelectSuppAkhir").modal('hide'); }

  function loadSelectSuppAkhir() {
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAkhir')) { $('#tabelSelectSuppAkhir').DataTable().destroy(); }

    let rowTable = "";
    g_suppList.forEach((item) => {
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
      url: "{!! url('reportaccountingpiutanglpp_loadvalas') !!}",
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
</script>
@endsection
