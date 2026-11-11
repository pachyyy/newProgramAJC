@extends('report.masterreportGudang')
{{-- @include('report.modalBrowseMaster') dihapus: sudah tidak dipakai, halaman ini punya
     picker sendiri (.modal-picker / openPickMaster()) yang menggantikan popup #formBrowseMaster
     bawaan -- lihat komentar di dalam <script> jsreport. --}}

<!-- Chart.js v4 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

@section('header2')
<style>
  /* Chart section (khusus report ini, bukan bagian dari report-table.css) */
  .chart-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 14px;
  }
  @media (max-width: 900px) { .chart-grid { grid-template-columns: 1fr; } }
  .chart-box {
    background: #fff; border: 1.5px solid #E2E8F4; border-radius: 12px;
    padding: 16px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06);
  }
  .chart-box h3 {
    font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 12px;
  }
  .chart-holder { position: relative; height: 260px; }
  .chart-holder canvas { max-height: 260px; }

  /* Periode Bulan/Tahun */
  .period-select {
    border: none; background: transparent; font-size: 13px; font-weight: 700;
    color: #4F46E5; outline: none; cursor: pointer;
  }

  #tabel thead th {
    font-size: 13px !important;
    font-weight: 700 !important;
    padding: 12px 14px !important;
    white-space: nowrap;
  }
  #tabel tbody td {
    font-size: 13.5px !important;
    padding: 11px 14px !important;
    line-height: 1.5 !important;
  }
  #tabel tbody tr td {
    border-bottom: 1px solid #EEF1F8 !important;
  }

  /* Samakan baris Subtotal & Grand Total dengan .tb-report .subtotal-row / .grand-total
     di report-table.css. */
  #tabel tr[id^="strow"] td {
    border: none !important;
    border-top: 1.5px solid #E2E8F4 !important;
    border-bottom: 2px solid #E2E8F4 !important;
    background: #F8F9FF !important;
    font-weight: 700 !important;
    font-size: 14px !important;
    padding: 11px 14px !important;
  }
  #tabel tr[id^="strow"] td.st {
    text-align: left !important;
  }
  #tabel #gtrow td {
    border: none !important;
    border-top: 2px solid #4F46E5 !important;
    background: linear-gradient(135deg, #EEF2FF, #F5F3FF) !important;
    font-weight: 800 !important;
    font-size: 14.5px !important;
    padding: 13px 14px !important;
  }
  #tabel #gtrow td.gt {
    text-align: left !important;
  }
  #tabel #gtrow td:not(.gt) {
    color: #4F46E5 !important;
  }

  /* Popup "Pilih Data" (Gudang/Grup/Kategori/SubKategori/Merk) dari dalam
     modal Filter. Dibuat manual */
  .modal-picker-backdrop {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0, 0, 0, .5);
    z-index: 1071;
  }
  .modal-picker-backdrop.show { display: block; }
  .modal-picker {
    display: none;
    position: fixed; inset: 0;
    z-index: 1072;
    overflow-x: hidden; overflow-y: auto;
    outline: 0;
  }
  .modal-picker.show { display: block; }
  .modal-picker .modal-dialog {
    margin: 1.75rem auto;
  }
</style>

<div class="tb-report main" style="font-family: 'Segoe UI', sans-serif;">
  <div class="content" style="padding: 20px 24px 0;">


    <div hidden>
      <button type="button" id="buttonMode0" onclick="doReportMode(0)"></button>
      <button type="button" id="buttonMode1" onclick="doReportMode(1)"></button>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">
          @if ($mode_menu == 'QTY')
           Stok Quantity
          @elseif ($mode_menu == 'QTYRP')
            Stok Quantity + Rupiah
          @else
            Stok per Periode
          @endif
        </div>
      </div> --}}

      <!-- Periode -->
      <div class="filter-wrap">
        <label>Periode</label>
        @if ($mode_menu == 'PERIODE')
          <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
          <span class="filter-sep">s/d</span>
          <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
        @else
          <!-- Bulan/Tahun -->
          <select class="period-select" id="periodBulan" onchange="changePeriodParts()"></select>
          <select class="period-select" id="periodTahun" onchange="changePeriodParts()"></select>
          <input type="hidden" id="inputDate1" value="{!! date('Y-m') !!}">
        @endif
      </div>

      {{-- Search --}}
      <div>
          <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
      </div>

      <!-- search + filter + filter data + customize + tampilkan + export -->
      <div class="action-group">
        <button
          class="btn-load"
          type="button"
          onclick="$('#modalFilter').modal('show')">
          <i class="fas fa-filter"></i> Filter
        </button>
        <button class="btn-load" onclick="doShowFormFilterData()" title="Filter Data"><i class="fas fa-magnifying-glass"></i> Filter Data</button>
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

    <!-- KPI STRIP (Dead/Slow/Fast/Stock/Kebutuhan dari sp_DEATFASTSLOW, lihat renderKpiStrip()) -->
    <div class="kpi-strip" id="kpiStrip"></div>

    <!-- CHARTS  -->
    <div class="chart-grid" id="chartGrid">
      <div class="chart-box">
        <h3>Penjualan Terbanyak</h3>
        <div class="chart-holder"><canvas id="topJualChart"></canvas></div>
      </div>
      <div class="chart-box">
        <h3>Perbandingan Pembelian vs Penjualan per Bulan</h3>
        <div class="chart-holder"><canvas id="beliJualChart"></canvas></div>
      </div>
    </div>

    <!-- Bar kolom tersembunyi + Reset kolom (diisi report-table.js / ReportTable).
         Hanya aktif untuk mode QTY -- mode QTYRP/PERIODE pakai header grouping
         rowspan/colspan manual yang tidak kompatibel, lihat ready() di jsreport. -->
    <div id="rtBar"></div>

    <div class="table-outer">
      <div class="table-wrap" id="showTableReport">
        <table class="tb" id="tabel">
          <thead id="tabel_header">
            <tr>
              <th>KODE BARANG</th>
              <th>NAMA BARANG</th>
              <th>SATUAN</th>
              <th>GDG</th>
              <th>AWAL</th>
              <th>BELI</th>
              <th>R.JUAL</th>
              <th>ADJ (+)</th>
              <th>TR (+)</th>
              <th>RPM (+)</th>
              <th>PRD</th>
              <th>JUAL</th>
              <th>R.BELI</th>
              <th>ADJ (-)</th>
              <th>TR (=)</th>
              <th>PMK (-)</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="tabel_data">
            <tr class="empty-row">
              <td colspan="100%">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Tabel (Belum ada data dimuat)  -->
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>

    </div>

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk
      sembunyikan kolom atau atur total.
    </div>

  </div>

  <!-- DRILL PANEL (klik kartu KPI Dead/Slow/Fast/Stock/Kebutuhan) -->
  <div class="drill-overlay" id="drillOverlay" onclick="closeDfsDrill()"></div>
  <div class="drill-panel" id="drillPanel">
    <div class="dp-header">
      <div>
        <div class="dp-title" id="dpTitle">-</div>
        <div class="dp-sub" id="dpSub">-</div>
      </div>
      <div class="dp-close" onclick="closeDfsDrill()"><i class="bi bi-x"></i></div>
    </div>
    <div class="dp-meta" id="dpMeta"></div>
    <div class="dp-body" id="dpBody"></div>
  </div>

</div>

<!-- modal filter -->
<div class="modal fade rt-filter" id="modalFilter">
  <div class="modal-dialog modal-md">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-filter"></i>
          Filter Laporan
          <span class="rt-active-badge" id="filterBadge">0 aktif</span>
        </h5>

        <button
          type="button"
          class="btn-close"
          aria-label="Close"
          data-bs-dismiss="modal">
        </button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Filter Data
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          <div class="rt-grid-2" id="pickFields"></div>

          {{-- Nilai sebenarnya (dibaca makeTable() & ditulis pickMasterSelect()) --}}
          <input type="hidden" id="inputGudang" value="-">
          <input type="hidden" id="inputGrup" value="-">
          <input type="hidden" id="inputKategori" value="-">
          <input type="hidden" id="inputSubKategori" value="-">
          <input type="hidden" id="inputMerk" value="-">
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Pengaturan Lain</div>
          <div class="rt-grid-2">
            <div>
              <label class="rt-field-label" for="inputAgenSelect">Status Agen</label>
              <select id="inputAgenSelect" class="rt-native" onchange="setAgen(this.value)">
                <option value="0">Agen</option>
                <option value="1">Non-Agen</option>
                <option value="2" selected>Semua</option>
              </select>
            </div>
            <div>
              <label class="rt-field-label" for="inputIsi">No Satuan</label>
              <input type="number" id="inputIsi" class="form-control" value="1">
            </div>
          </div>
          <div class="mb-3 d-flex align-items-center" style="gap:8px; margin-top:10px;">
            <input type="checkbox" id="inputStockMinus" style="width:18px;height:18px;cursor:pointer;">
            <label for="inputStockMinus" style="cursor:pointer;margin:0;">Stock Minus?</label>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="rt-reset-link" onclick="resetAllFilters()">Reset semua</button>
        <div class="rt-footer-buttons">
          <button type="button" class="rt-btn rt-btn-ghost" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="rt-btn rt-btn-primary" data-bs-dismiss="modal">Terapkan</button>
        </div>
      </div>

    </div>
  </div>
</div>
<!-- modal filter -->

<!-- modal pilih data master (Gudang/Grup/Kategori/SubKategori/Merk) -->
<div class="modal-picker-backdrop" id="modalPickMasterBackdrop" onclick="closePickMaster()"></div>
<div class="modal-picker" id="modalPickMaster" tabindex="-1" role="dialog" aria-labelledby="modalPickMasterLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 900px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPickMasterLabel">Pilih Data</h5>
        <button type="button" class="btn-close" aria-label="Close" onclick="closePickMaster()"></button>
      </div>
      <div class="modal-body">
        <table id="tabelPickMaster" class="table table-bordered table-striped">
          <thead id="tabelPickMaster_header" class="text-center"></thead>
          <tbody id="tabelPickMaster_data" class="text-left"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closePickMaster()">Batal</button>
      </div>
    </div>
  </div>
</div>
<!-- modal pilih data master -->

@endsection

@section('jsreport')
<script src="{!! URL::asset('public/js/ajc-browsemaster.js') !!}"></script>
<script type="text/javascript">

  var modereport_qty = 0, modereport_rp = 1, modereport_qtyrp = 2, modereport_periode = 3;
  g_modeReport = modereport_qty;

  let globalAgen = "2";

  var reportTitle = "";

  if (typeof loadingHtml !== 'function') {
    window.loadingHtml = function (msg) {
      return '<span style="display:inline-flex;align-items:center;gap:6px;color:#5A6A85;">' +
             '<i class="fas fa-spinner fa-spin"></i> ' + (msg || 'Memuat...') + '</span>';
    };
  }

  /* KPI strip (sp_DEATFASTSLOW) + grafik bulanan (doMonthlyGraphics) -- keduanya lepas dari
     filter Gudang/Grup/Kategori/dll, cuma bergantung pada periode Bulan/Tahun yang dipilih.
     Lihat currentDfsPeriod()/refreshPeriodWidgets() di bawah. */
  const DFS_URL = "{{ url('laporanstockmutasistock_doDeadFastSlow') }}";
  const MONTHLY_URL = "{{ url('laporanstockmutasistock_doMonthlyGraphics') }}";

  const DFS_CARDS = [
    { key: 'dead',  label: 'Dead Stock', qty: 'qtydead',      rp: 'rpdead', color: '#DC2626' },
    { key: 'slow',  label: 'Slow Stock', qty: 'qtyslow',      rp: 'rpslow', color: '#B45309' },
    { key: 'fast',  label: 'Fast Stock', qty: 'qtyfast',      rp: 'rpfast', color: '#15803D' },
    { key: 'stock', label: 'Stock',      qty: 'qtystock',     rp: null,     color: '#1D4ED8' },
    { key: 'keb',   label: 'Kebutuhan',  qty: 'qtykebutuhan', rp: null,     color: '#7C3AED' },
  ];
  let _dfsRows = [];
  let _monthlyRows = [];
  let _dfsSeq = 0;

  /* modal "Pilih Data" (Gudang/Grup/Kategori/SubKategori/Merk)
     Menggantikan popup shared #formBrowseMaster (search + Submit) dengan
     Actions berisi tombol "+" per baris. */
  let pickerTargetInput = "";

  // Lima field "Filter Data" (Gudang/Grup/Kategori/SubKategori/Merk): nilai sebenarnya
  // tetap di input hidden #inputXxx (dibaca makeTable(), ditulis pickMasterSelect()) --
  // kotak .rt-combo di bawah ini hanyalah tampilan di atasnya, mengikuti pola PICK_FIELDS
  // di new-filter-modal-ui-guide.md #4. Beda dari reference implementation (reportmarketingso):
  // openPickMaster() buka .modal-picker custom (bukan #formSelect Bootstrap), yang z-index-nya
  // (1071/1072) sudah di atas modalFilter -- jadi tidak perlu hide/reopen modalFilter seperti
  // pickFromModal() di guide.
  const PICK_FIELDS = [
    { id: 'inputGudang',      label: 'Gudang',        url: '{!! $gudang !!}',      title: 'Pilih Gudang' },
    { id: 'inputGrup',        label: 'Grup',          url: '{!! $grup !!}',        title: 'Pilih Grup' },
    { id: 'inputKategori',    label: 'Kategori',      url: '{!! $kategori !!}',    title: 'Pilih Kategori' },
    { id: 'inputSubKategori', label: 'Sub Kategori',  url: '{!! $subkategori !!}', title: 'Pilih Sub Kategori' },
    { id: 'inputMerk',        label: 'Merk',          url: '{!! $merk !!}',        title: 'Pilih Merk' },
  ];

  function renderPickFields() {
    let html = '';
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val() || '-';
      const isSet = (val !== '-' && val !== '');
      html += '<div>';
      html += '<label class="rt-field-label">' + f.label + '</label>';
      html += '<div class="rt-combo">';
      html += '<div class="rt-combo-input" onclick="openPickMaster(\'' + f.id + '\', \'' + f.url + '\', \'' + f.title + '\')">';
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

  function openPickMaster(targetInputId, url, title) {
    pickerTargetInput = targetInputId;
    $("#modalPickMasterLabel").text(title || "Pilih Data");

    try {
      if ($.fn.DataTable.isDataTable('#tabelPickMaster')) {
        $('#tabelPickMaster').DataTable().destroy();
      }
    } catch (e) {
      console.error('openPickMaster: gagal destroy DataTable sebelumnya', e);
    }

    $("#tabelPickMaster_header").html("");
    $("#tabelPickMaster_data").html('<tr><td>' + loadingHtml('Memuat data...') + '</td></tr>');

    $('#modalPickMasterBackdrop').addClass('show');
    $('#modalPickMaster').addClass('show').attr('aria-hidden', 'false');

    $.ajax({
      url: url,
      type: 'get',
      success: function (res) { renderPickMaster(res); },
      error: function () {
        $("#tabelPickMaster_data").html('<tr><td class="text-center">Gagal memuat data.</td></tr>');
      }
    });
  }

  function closePickMaster() {
    $('#modalPickMaster').removeClass('show').attr('aria-hidden', 'true');
    $('#modalPickMasterBackdrop').removeClass('show');
  }

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $('#modalPickMaster').hasClass('show')) {
      closePickMaster();
    }
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $('#drillPanel').hasClass('open')) {
      closeDfsDrill();
    }
  });

  // KPI strip dibangun ulang lewat innerHTML tiap refreshPeriodWidgets(), jadi klik kartu
  // didelegasikan (bukan onclick inline per kartu) -- sama seperti pola drill row di
  // reportaccountingneracalajur.blade.php.
  $(document).on('click', '#kpiStrip .kpi-card', function () {
    openDfsDrill($(this).attr('data-dfs-key'));
  });

  function renderPickMaster(res) {
    const kolom = (res && res.kolom) || [];
    const rows = (res && res.table) || [];

    let headHtml = '<tr>';
    kolom.forEach(function (k) { headHtml += '<th class="text-center">' + k[1] + '</th>'; });
    headHtml += '<th class="text-center">Actions</th></tr>';
    $("#tabelPickMaster_header").html(headHtml);

    let bodyHtml = '';
    if (rows.length) {
      rows.forEach(function (item) {
        bodyHtml += '<tr>';
        kolom.forEach(function (k) {
          let val;
          if (k[2] === 'date') { val = format_date(item[k[0]]); }
          else if (k[2] === 'float') { val = format_number(currencyNormalizer(item[k[0]]), k[3]); }
          else { val = nullToEmpty(item[k[0]]); }
          bodyHtml += '<td>' + val + '</td>';
        });
        const kode = kolom.length ? item[kolom[0][0]] : '';
        bodyHtml += '<td class="text-center">' +
          '<button type="button" class="btn btn-primary btn-sm" onclick="pickMasterSelect(\'' + String(kode).replace(/'/g, "\\'") + '\')"><i class="bi bi-plus-lg"></i></button>' +
          '</td></tr>';
      });
    } else {
      bodyHtml = '<tr><td colspan="' + (kolom.length + 1) + '" class="text-center">Tidak ada data ditemukan</td></tr>';
    }
    $("#tabelPickMaster_data").html(bodyHtml);

    try {
      $('#tabelPickMaster').DataTable({
        lengthChange: false,
        paging: rows.length > 10
      });
    } catch (e) {
      console.error('renderPickMaster: gagal inisialisasi DataTable', e);
    }
  }

  function pickMasterSelect(kode) {
    if (pickerTargetInput) { $('#' + pickerTargetInput).val(kode); }
    closePickMaster();
    renderPickFields();
    updateFilterBadge();
  }

  // Badge "N aktif" di judul modal Filter. Field dengan nilai netral ("-" untuk picker,
  // "2"/Semua untuk Status Agen, unchecked untuk Stock Minus) tidak dihitung -- sama
  // seperti aturan di new-filter-modal-ui-guide.md #5. No Satuan dianggap netral di "1"
  // (artinya tidak ada konversi satuan).
  function updateFilterBadge() {
    let count = 0;
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val();
      if (val && val !== '-') { count++; }
    });
    if ($('#inputAgenSelect').val() !== '2') { count++; }
    if (($('#inputIsi').val() || '1') !== '1') { count++; }
    if ($('#inputStockMinus').prop('checked')) { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    PICK_FIELDS.forEach(function (f) { $('#' + f.id).val('-'); });
    $('#inputAgenSelect').val('2');
    setAgen('2');
    $('#inputIsi').val('1');
    $('#inputStockMinus').prop('checked', false);
    renderPickFields();
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function () { renderPickFields(); updateFilterBadge(); });
  $('#modalFilter').on('change', 'select.rt-native, #inputStockMinus', updateFilterBadge);
  $('#modalFilter').on('input', '#inputIsi', updateFilterBadge);

  let defaultBulan = new Date().getMonth() + 1;
  let defaultTahun = new Date().getFullYear();
  const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

  function populatePeriodSelectors() {
    const selB = document.getElementById('periodBulan');
    const selT = document.getElementById('periodTahun');
    if (!selB || !selT) return;

    selB.innerHTML = NAMA_BULAN.map((nama, i) =>
      `<option value="${i + 1}" ${(i + 1) == defaultBulan ? 'selected' : ''}>${nama}</option>`).join('');
    const thisYear = new Date().getFullYear();
    let years = '';
    for (let y = thisYear; y >= thisYear - 6; y--) {
      years += `<option value="${y}" ${y == defaultTahun ? 'selected' : ''}>${y}</option>`;
    }
    selT.innerHTML = years;
    changePeriodParts();
  }

  // Bulan/Tahun -> gabung ke #inputDate1 format "YYYY-MM"
  function changePeriodParts() {
    const selB = document.getElementById('periodBulan');
    const selT = document.getElementById('periodTahun');
    if (!selB || !selT) return;
    defaultBulan = parseInt(selB.value, 10);
    defaultTahun = parseInt(selT.value, 10);
    const mm = String(defaultBulan).padStart(2, '0');
    $('#inputDate1').val(defaultTahun + '-' + mm);
    refreshPeriodWidgets();
  }

  $(document).ready(function() {

    // Mode RP tidak dipakai lagi -- URL lama /laporanstockmutasistockrp (mode_menu == "RP")
    // sengaja jatuh ke default QTY di bawah, BUKAN ke PERIODE: PERIODE membaca #inputDate2
    // yang cuma dirender @@if ($mode_menu == 'PERIODE') (lihat blok "Periode" di atas), jadi
    // andai fallback dibiarkan ke PERIODE, URL lama itu akan pecah cari input yang tidak ada.
    if ("{!! $mode_menu !!}" == "QTYRP") {
      g_modeReport = modereport_qtyrp;
      reportTitle = "LAPORAN STOK BULANAN QTY+RUPIAH";
    } else if ("{!! $mode_menu !!}" == "PERIODE") {
      g_modeReport = modereport_periode;
      reportTitle = "LAPORAN STOK BULANAN QTY+RUPIAH";
    } else {
      g_modeReport = modereport_qty;
      reportTitle = "LAPORAN STOK QUANTITY";
    }

    $("#gButtonCustomizeTable").hide();

    $("#btnCustomizeTable").on("click", function() {
      if (typeof doShowFormCustomizeTable === "function") doShowFormCustomizeTable();
      else alert(" Fungsi doShowFormCustomizeTable belum tersedia.");
    });

    $("#btnSubmitReport").on("click", function() {
      makeTable('REPORT');
    });

    populatePeriodSelectors();
    setAgen(globalAgen);
    setDefaultHeader();

    // Header interaktif (drag/gear/hide kolom) hanya untuk mode QTY -- header
    // QTYRP/PERIODE pakai grouping rowspan/colspan manual (setRowHeaderQtyRp) yang
    // tidak bisa direpresentasikan sebagai satu baris <th> per kolom, lihat
    // docs/new-slider-table-guide.md #1.
    if (g_modeReport == modereport_qty) {
      ReportTable.init({
        table: '#tabel',
        bar: '#rtBar',
        onChange: renderCachedReport
      });
    } else {
      $('#rtBar').hide();
      $('.rt-hint').hide();
    }

    // Mode PERIODE pakai input tanggal (#inputDate1/#inputDate2), bukan dropdown
    // Bulan/Tahun -- KPI strip & grafik bulanan ikut menyegarkan diri saat tanggal itu
    // berubah (lihat currentDfsPeriod()/refreshPeriodWidgets()).
    if (g_modeReport == modereport_periode) {
      $('#inputDate1, #inputDate2').on('change', refreshPeriodWidgets);
    }

    // setTimeout(() => {
    //   makeTable('REPORT');
    // }, 100);
  });

  // Render ulang tabel dari data yang sudah dimuat (gcart_res), dipakai sebagai
  // onChange oleh ReportTable saat kolom di-drag/disembunyikan/direset.
  // _rows opsional: kalau diisi (mis. hasil pencarian) baris itu yang dirender --
  // termasuk array kosong, supaya "Tidak ada data ditemukan" tetap muncul. Penjaga
  // di awal sengaja memeriksa gcart_res (sumbernya), bukan _rows.
  // ReportTable memanggil onChange() tanpa argumen; saat itu searchedRows() dipakai
  // supaya kata kunci yang sedang diketik tidak hilang begitu kolom di-drag.
  function renderCachedReport(_rows) {
    if (!gcart_res || !gcart_res.length) { return; }
    let rows = (_rows === undefined) ? searchedRows() : _rows;
    let _date1 = $("#inputDate1").val();
    let _date2 = (g_modeReport == modereport_periode) ? $("#inputDate2").val() : null;
    doShowReport(rows, reportTitle, "KODEBRG", _date1, _date2);
    relabelSubtotalRows();
  }

  function setAgen (val) {
    globalAgen = val;
    $('#inputAgenSelect').val(val);
  }

  function setDefaultHeader() {
    if (g_modeReport == modereport_qty) {
      gcart_header = [
        ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0], ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0], ['KODEGDG', 'Gdg', 1, 'varchar', 0, 0],
        ['QntAwal', 'Awal', 1, 'float', 1, 2], ['QNTPBL', 'Beli', 1, 'float', 1, 2],
        ['QNTRPJ', 'R. Jual', 1, 'float', 1, 2], ['QNTADI', 'Adj (+)', 1, 'float', 1, 2],
        ['QNTTRI', 'Tr (+)', 1, 'float', 1, 2], ['QNTRPK', 'RPM (+)', 1, 'float', 1, 2],
        ['QntHPrd', 'PRD (+)', 1, 'float', 1, 2], ['QNTPNJ', 'Jual', 1, 'float', 1, 2],
        ['QNTRBP', 'R.Beli', 1, 'float', 1, 2], ['QNTADO', 'Adj (-)', 1, 'float', 1, 2],
        ['QNTTRO', 'Tr (-)', 1, 'float', 1, 2], ['QNTPMK', 'PMK (-)', 1, 'float', 1, 2],
        ['SALDOQNT', 'Akhir', 1, 'float', 1, 2]
      ];
    } else if (g_modeReport == modereport_qtyrp || g_modeReport == modereport_periode) {
      gcart_header = [
          ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0], ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
          ['partNumber', 'Part Number', 1, 'varchar', 0, 0], ['NAMAMERK', 'Merk', 1, 'varchar', 0, 0],
          ['KODEGDG', 'GD', 1, 'varchar', 0, 0], ['Satuan', 'Sat', 1, 'varchar', 0, 0],
          ['QntAwal', 'So. Awal', 1, 'float', 1, 2], ['HRGAWAL', 'So. Awal', 1, 'float', 1, 0],
          ['QNTPBL', 'Pembelian', 1, 'float', 1, 2], ['HRGPBL', 'Pembelian', 1, 'float', 1, 0],
          ['QNTRPJ', 'Retur Jual', 1, 'float', 1, 2], ['HRGRPJ', 'Retur Jual', 1, 'float', 1, 0],
          ['QNTADI', 'Kor. Msk', 1, 'float', 1, 2], ['HRGADI', 'Kor. Msk', 1, 'float', 1, 0],
          ['QNTTRI', 'Trans. Msk', 1, 'float', 1, 2], ['HRGTRI', 'Trans. Msk', 1, 'float', 1, 0],
          ['QNTRPK', 'R. Pemakaian', 1, 'float', 1, 2], ['HRGRPK', 'R. Pemakaian', 1, 'float', 1, 0],
          ['QNTUKI', 'Ubah Kemasan In', 1, 'float', 1, 2], ['HRGUKI', 'Ubah Kemasan In', 1, 'float', 1, 0],
          ['qntrspb', 'Terima dr R.Sjln', 1, 'float', 1, 2], ['hrgrspb', 'Terima dr R.Sjln', 1, 'float', 1, 0],
          ['QntHPrd', 'Gd TC dr SJ', 1, 'float', 1, 2], ['HRGHPrd', 'Gd TC dr SJ', 1, 'float', 1, 0],
          ['QNTPNJ', 'S.Jalan', 1, 'float', 1, 2], ['HRGPNJ', 'S.Jalan', 1, 'float', 1, 0],
          ['qntrgtc', 'Retur Sjln dr GTC', 1, 'float', 1, 2], ['hrgrgtc', 'Retur Sjln dr GTC', 1, 'float', 1, 0],
          ['QNTPRJ', 'HPP', 1, 'float', 1, 2], ['HRGPRJ', 'HPP', 1, 'float', 1, 0],
          ['QNTRBP', 'Retur Beli', 1, 'float', 1, 2], ['HRGRBP', 'Retur  Beli', 1, 'float', 1, 0],
          ['QNTADO', 'Kor. Klr', 1, 'float', 1, 2], ['HRGADO', 'Kor. Klr', 1, 'float', 1, 0],
          ['QNTTRO', 'Trans. Klr', 1, 'float', 1, 2], ['HRGTRO', 'Trans. Klr', 1, 'float', 1, 0],
          ['QNTUKO', 'Ubah Kemasan Out', 1, 'float', 1, 2], ['HRGUKO', 'Ubah Kemasan Out', 1, 'float', 1, 0],
          ['QNTPMK', 'Pemakaian', 1, 'float', 1, 2], ['HRGPMK', 'Pemakaian', 1, 'float', 1, 0],
          ['SALDOQNT', 'So. Akhir', 1, 'float', 1, 2], ['SALDORP', 'So. Akhir', 1, 'float', 1, 0]
      ];
    }
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  function setRowHeader(_rowHeader) {
    if (g_modeReport == modereport_qty) {
      return setRowHeaderQtyOrRp(_rowHeader);
    } else {
      return setRowHeaderQtyRp(_rowHeader);
    }
  }

  function setRowHeaderQtyOrRp(_rowHeader) {
    // Header interaktif (drag/gear/hide) -- lihat docs/new-slider-table-guide.md.
    // cols HARUS berasal dari gcart_header.filter(...), bukan .map()/copy, supaya
    // ReportTable.headHtml() bisa memetakan tiap kolom balik ke index globalnya.
    const cols = gcart_header.filter(c => c[2] === 1);
    return _rowHeader + ReportTable.headHtml(cols);
  }

  function setRowHeaderQtyRp(_rowHeader) {
    let _thopen = "", _thclose = "</th>";
    _rowHeader += '<tr>';
    _thopen = '<th rowspan="3" scope="col" class="text-start">';
    _rowHeader += _thopen + 'Kode Barang' + _thclose;
    _rowHeader += _thopen + 'Nama Barang' + _thclose;
    _rowHeader += _thopen + 'Part Number' + _thclose;
    _rowHeader += _thopen + 'Merk' + _thclose;
    _rowHeader += _thopen + 'Gdg' + _thclose;
    _rowHeader += _thopen + 'Sat' + _thclose;
    _rowHeader += '<th colspan="2" rowspan="2" class="text-center">So. Awal</th>';
    _rowHeader += '<th colspan="16" class="text-center">Masuk</th>';
    _rowHeader += '<th colspan="16" class="text-center">Keluar</th>';
    _rowHeader += '<th colspan="2" rowspan="2" class="text-center">So. Akhir</th>';
    _rowHeader += '</tr>';

    _rowHeader += '<tr>';
    const masukKeluarLabels = [
        'Pembelian', 'Retur Jual', 'Kor. Msk', 'Trans. Msk',
        'R. Pemakaian', 'Ubah Kemasan In', 'Terima dr R.Sjln', 'Gd TC dr SJ',
        'S. Jalan', 'Retur Sjln dr GTC', 'HPP', 'Retur Beli',
        'Kor. Klr', 'Trans. Klr', 'Ubah Kemasan Out', 'Pemakaian'
    ];
    for (let i = 0; i < masukKeluarLabels.length; i++) {
        _rowHeader += `<th colspan="2" scope="col" class="text-center">${masukKeluarLabels[i]}</th>`;
    }
    _rowHeader += '</tr>';

    _rowHeader += '<tr>';
    let _qtyrp = '<th scope="col" class="text-end">Qty</th><th scope="col" class="text-end">Rp.</th>';
    _rowHeader += _qtyrp.repeat(1 + 8 + 8 + 1);
    _rowHeader += '</tr>';
    return _rowHeader;
  }

  function makeTable (_mode, _callback) {
    let groupby = "KODEBRG";
    let _date1  = $("#inputDate1").val();
    let _date2  = (g_modeReport == modereport_periode) ? $("#inputDate2").val() : null;

    let temp_href = g_href;
    g_href = 'laporanstockmutasistock';

    let data = {
      date1            : _date1,
      inputGudang      : $("#inputGudang").val(),
      inputIsi         : $("#inputIsi").val(),
      inputStockMinus  : $('#inputStockMinus').prop('checked') ? 1 : 0,
      inputGrup        : $("#inputGrup").val(),
      inputKategori    : $("#inputKategori").val(),
      inputSubKategori : $("#inputSubKategori").val(),
      inputMerk        : $("#inputMerk").val(),
      inputJenis       : globalAgen,
      date2            : _date2,
      modeMenu         : g_modeReport,
    };

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    // JANGAN buang baris <tr> dari #tabel_header di sini. Baris itu dulu dipakai untuk
    // membuang 4 baris info (judul/periode/dicetak-oleh) yang dibuat doSetRowHeaderInfo(),
    // tapi pemanggilnya sudah dikomentari di masterreportGudang.blade.php (doSetRowHeader,
    // sekitar baris 786). Yang tersisa di #tabel_header sekarang HANYA baris judul kolom
    // (1 baris dari ReportTable.headHtml untuk mode QTY/RP, 3 baris grouping untuk
    // QTYRP/PERIODE), jadi slice(0, 4).remove() ikut menghapus header aslinya --
    // dan karena makeTable('FILTER') juga lewat sini, header hilang begitu tombol
    // "Filter Data" ditekan walaupun doShowReport() belum sempat dipanggil.

    // doMakeTable() sekarang async (dulu async:false, yang membekukan tab sampai response
    // datang tanpa sempat menggambar spinner loadingHtml() di atas) -- jadi apa pun yang
    // membaca gcart_res harus jalan di callback ini, bukan lagi ditebak lewat setTimeout(500ms).
    doMakeTable(_mode, groupby, data, reportTitle, _date1, null, function () {
      let footerMsg = gcart_res && gcart_res.length > 0 ? "Menampilkan " + gcart_res.length + " baris" : "Belum ada data dimuat";
      document.getElementById('footerLabel').textContent = footerMsg;

      buildTopJualChart(gcart_res || []);
      relabelSubtotalRows();

      if (typeof _callback === 'function') { _callback(); }
    });

    // Tampilkan menyegarkan tabel DAN strip KPI/grafik bulanan bersamaan (keduanya lepas
    // dari filter Gudang/Grup/Kategori/dll -- sp_DEATFASTSLOW & doMonthlyGraphics cuma
    // menerima bulan+tahun).
    refreshPeriodWidgets();

    g_href = temp_href;
  }

  function relabelSubtotalRows() {
    $('#tabel tr[id^="strow"] td.st').each(function () {
      const $cell = $(this);
      $cell.html($cell.html().replace(/Total\s*:/i, 'Subtotal :'));
    });
  }

  function getKolomFilter() { return ['KODEBRG', 'NAMABRG']; }

  /* -- functS (Chart.js v4)
     Kiri : Penjualan Terbanyak (Qty) Top 10 barang berdasar kolom JUAL (buildTopJualChart)
     Kanan : Perbandingan Pembelian vs Penjualan per Bulan, Bulan 1 s.d. periode terpilih
             (buildMonthlyChart, dari doMonthlyGraphics) -- */
  const CHART_PALETTE = ['#4F46E5','#7C3AED','#DB2777','#2563eb','#16a34a','#ca8a04','#ea580c','#0891b2','#e11d48','#65a30d'];
  let _charts = {};

  function fmtShort(v) {
    v = Math.round(num(v)); const a = Math.abs(v);
    if (a >= 1e9) return (v / 1e9).toFixed(1).replace(/\.0$/, '') + ' M';
    if (a >= 1e6) return (v / 1e6).toFixed(1).replace(/\.0$/, '') + ' jt';
    if (a >= 1e3) return (v / 1e3).toFixed(0) + ' rb';
    return String(v);
  }
  function num(v) { if (v === null || v === undefined || v === '') return 0; const n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function _destroyChart(id) { if (_charts[id]) { _charts[id].destroy(); delete _charts[id]; } }

  function pickCIChart(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  function _chartMsg(canvasId, msg) {
    const cv = document.getElementById(canvasId);
    if (!cv) return;
    const holder = cv.parentElement;
    if (holder) holder.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:12.5px;text-align:center;padding:0 20px">' + msg + '</div>';
  }

  // Kiri: Penjualan Terbanyak (Qty) Top 10 barang berdasar kolom JUAL -- dari baris tabel
  // yang sedang tampil (gcart_res), jadi ikut Filter/Tampilkan seperti sebelumnya.
  function buildTopJualChart(rows) {
    const holder = document.querySelectorAll('#chartGrid .chart-holder')[0];
    if (holder && !document.getElementById('topJualChart')) holder.innerHTML = '<canvas id="topJualChart"></canvas>';

    if (typeof Chart === 'undefined') {
      _chartMsg('topJualChart', 'Chart.js gagal dimuat. Cek path <code>public/plugins/chart.js/chart.umd.min.js</code>.');
      return;
    }
    if (!rows || !rows.length) {
      _chartMsg('topJualChart', 'Belum ada data untuk grafik.');
      return;
    }
    try {
      Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
      Chart.defaults.font.size = 12;
      Chart.defaults.color = '#64748b';

      const order = [], jualByItem = {}, namaByItem = {};
      rows.forEach(r => {
        const kode = String(pickCIChart(r, 'KODEBRG') || '').trim();
        if (!kode) return;
        if (!(kode in jualByItem)) { jualByItem[kode] = 0; namaByItem[kode] = pickCIChart(r, 'NAMABRG') || kode; order.push(kode); }
        jualByItem[kode] += num(pickCIChart(r, 'QNTPNJ'));
      });

      // Top 10 barang dgn Qty Jual paling banyak
      const top = order.map(k => [k, jualByItem[k]]).sort((a, b) => b[1] - a[1]).slice(0, 10);
      const topLabels = top.map(t => namaByItem[t[0]]);

      const cvTop = document.getElementById('topJualChart');
      if (!top.length || !cvTop) {
        _chartMsg('topJualChart', 'Belum ada data penjualan (Qty) pada periode ini.');
        return;
      }
      _destroyChart('topJual');
      _charts.topJual = new Chart(cvTop, {
        type: 'bar',
        data: {
          labels: topLabels,
          datasets: [{
            label: 'Jual (Qty)',
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
    } catch (e) {
      console.error('buildTopJualChart', e);
      _chartMsg('topJualChart', 'Gagal menampilkan grafik (lihat console).');
    }
  }

  // Kanan: Perbandingan Pembelian vs Penjualan per Bulan -- SATU titik per bulan, dari
  // Bulan 1 sampai periode yang dipilih (doMonthlyGraphics / DBSTOCKBRG), lepas dari
  // baris tabel yang sedang tampil. Lihat refreshPeriodWidgets().
  function buildMonthlyChart() {
    const holder = document.querySelectorAll('#chartGrid .chart-holder')[1];
    if (holder && !document.getElementById('beliJualChart')) holder.innerHTML = '<canvas id="beliJualChart"></canvas>';

    if (typeof Chart === 'undefined') {
      _chartMsg('beliJualChart', 'Chart.js gagal dimuat. Cek path <code>public/plugins/chart.js/chart.umd.min.js</code>.');
      return;
    }
    const rows = _monthlyRows || [];
    if (!rows.length) {
      _chartMsg('beliJualChart', 'Belum ada data untuk grafik.');
      return;
    }
    try {
      Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
      Chart.defaults.font.size = 12;
      Chart.defaults.color = '#64748b';

      const labels = rows.map(r => {
        const b = num(pickCIChart(r, 'BULAN'));
        return NAMA_BULAN[b - 1] || ('Bulan ' + b);
      });
      const beliData = rows.map(r => num(pickCIChart(r, 'qntpbl')));
      const jualData = rows.map(r => num(pickCIChart(r, 'qntpnj')));

      const cvBJ = document.getElementById('beliJualChart');
      if (!cvBJ) {
        _chartMsg('beliJualChart', 'Belum ada data Beli/Jual pada periode ini.');
        return;
      }
      _destroyChart('beliJual');
      _charts.beliJual = new Chart(cvBJ, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Beli (Qty)',
              data: beliData,
              borderColor: '#16a34a', backgroundColor: '#16a34a',
              tension: .3, fill: false, pointRadius: 5
            },
            {
              label: 'Jual (Qty)',
              data: jualData,
              borderColor: '#DB2777', backgroundColor: '#DB2777',
              tension: .3, fill: false, pointRadius: 5
            }
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + fmtShort(c.parsed.y) } }
          },
          scales: {
            x: { ticks: { maxRotation: 40, minRotation: 0, autoSkip: false, font: { size: 10 } } },
            y: { ticks: { callback: (v) => fmtShort(v) } }
          }
        }
      });
    } catch (e) {
      console.error('buildMonthlyChart', e);
      _chartMsg('beliJualChart', 'Gagal menampilkan grafik (lihat console).');
    }
  }

  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // Bulan/Tahun aktif untuk sp_DEATFASTSLOW & doMonthlyGraphics. Mode PERIODE (input
  // tanggal, bukan dropdown) pakai bulan/tahun dari tanggal AWAL (#inputDate1).
  function currentDfsPeriod() {
    if (g_modeReport == modereport_periode) {
      const p = ($('#inputDate1').val() || '').split('-'); // format YYYY-MM-DD
      const tahun = parseInt(p[0], 10), bulan = parseInt(p[1], 10);
      return { tahun: isNaN(tahun) ? defaultTahun : tahun, bulan: isNaN(bulan) ? defaultBulan : bulan };
    }
    return { tahun: defaultTahun, bulan: defaultBulan };
  }

  // Menyegarkan KPI strip + grafik bulanan bersamaan. Dipanggil saat periode berganti
  // (changePeriodParts() / ganti tanggal PERIODE) DAN saat Tampilkan ditekan (makeTable()).
  // Token _dfsSeq mencegah response yang lambat dari periode lama menimpa periode baru.
  function refreshPeriodWidgets() {
    const period = currentDfsPeriod();
    if (!period.bulan || !period.tahun) return;
    const seq = ++_dfsSeq;

    document.getElementById('kpiStrip').innerHTML = '<div style="padding:12px">' + loadingHtml('Memuat KPI...') + '</div>';

    $.ajax({
      url: DFS_URL, type: 'get', data: { bulan: period.bulan, tahun: period.tahun },
      success: function (res) {
        if (seq !== _dfsSeq) return;
        _dfsRows = res || [];
        renderKpiStrip();
      },
      error: function () {
        if (seq !== _dfsSeq) return;
        _dfsRows = [];
        document.getElementById('kpiStrip').innerHTML =
          '<div style="padding:12px;color:#B91C1C;font-size:12.5px">Gagal memuat data KPI.</div>';
      }
    });

    $.ajax({
      url: MONTHLY_URL, type: 'get', data: { bulan: period.bulan, tahun: period.tahun },
      success: function (res) {
        if (seq !== _dfsSeq) return;
        _monthlyRows = res || [];
        buildMonthlyChart();
      },
      error: function () {
        if (seq !== _dfsSeq) return;
        _monthlyRows = [];
        _chartMsg('beliJualChart', 'Gagal memuat data grafik bulanan.');
      }
    });
  }

  // 5 kartu KPI dari sp_DEATFASTSLOW. Mode QTY: qty saja. Mode QTYRP/PERIODE: kartu
  // Dead/Slow/Fast juga menampilkan Rp (Stock & Kebutuhan tidak punya kolom Rp di SP).
  function renderKpiStrip() {
    const el = document.getElementById('kpiStrip');
    if (!el) return;

    if (!_dfsRows.length) {
      el.innerHTML = '<div style="padding:12px;color:#94a3b8;font-size:12.5px">Belum ada data untuk periode ini.</div>';
      return;
    }

    const showRp = (g_modeReport != modereport_qty);

    el.innerHTML = DFS_CARDS.map(function (card) {
      const rows = rowsForCard(card.key);
      const qtySum = _dfsRows.reduce((s, r) => s + num(pickCIChart(r, card.qty)), 0);

      let valHtml = format_number(qtySum, 2);
      if (showRp && card.rp) {
        const rpSum = _dfsRows.reduce((s, r) => s + num(pickCIChart(r, card.rp)), 0);
        valHtml += ' <span style="font-weight:600;color:#5A6A85;font-size:12px">/ Rp ' + format_number(rpSum, 0) + '</span>';
      }

      return '<div class="kpi-card" data-dfs-key="' + card.key + '" style="cursor:pointer;border-left:3px solid ' + card.color + '">' +
        '<div class="kpi-dot" style="background:' + card.color + '"></div>' +
        '<div class="kpi-body">' +
          '<div class="kpi-label">' + card.label + '</div>' +
          '<div class="kpi-val" style="color:' + card.color + '">' + valHtml + '</div>' +
          '<div class="kpi-count">' + rows.length + ' barang</div>' +
        '</div>' +
      '</div>';
    }).join('');
  }

  // Filter untuk drill per kartu. Dead/Slow/Fast saling eksklusif (qty & rp != 0 pada
  // kolomnya sendiri, dan qty = 0 pada dua kolom lain). Stock & Kebutuhan cuma cek
  // kolomnya sendiri != 0.
  function rowsForCard(key) {
    return _dfsRows.filter(function (r) {
      const dead = num(pickCIChart(r, 'qtydead'));
      const slow = num(pickCIChart(r, 'qtyslow'));
      const fast = num(pickCIChart(r, 'qtyfast'));
      const rpdead = num(pickCIChart(r, 'rpdead'));
      const rpslow = num(pickCIChart(r, 'rpslow'));
      const rpfast = num(pickCIChart(r, 'rpfast'));
      const stock = num(pickCIChart(r, 'qtystock'));
      const keb = num(pickCIChart(r, 'qtykebutuhan'));
      switch (key) {
        case 'dead':  return dead !== 0 && rpdead !== 0 && slow === 0 && fast === 0;
        case 'slow':  return slow !== 0 && rpslow !== 0 && dead === 0 && fast === 0;
        case 'fast':  return fast !== 0 && rpfast !== 0 && dead === 0 && slow === 0;
        case 'stock': return stock !== 0;
        case 'keb':   return keb !== 0;
        default:      return false;
      }
    });
  }

  // Ledger slide-over (drill panel) per kartu KPI -- pola sama dgn openDrill() di
  // reportaccountingneracalajur.blade.php, kolom dikuratori: Kode/Nama + metrik kartu (+ Rp
  // kalau ada).
  function openDfsDrill(key) {
    const card = DFS_CARDS.find(c => c.key === key);
    if (!card) return;

    const rows = rowsForCard(key);
    const period = currentDfsPeriod();
    const periodLabel = (NAMA_BULAN[period.bulan - 1] || period.bulan) + ' ' + period.tahun;

    document.getElementById('dpTitle').textContent = card.label;
    document.getElementById('dpSub').textContent = 'Periode ' + periodLabel + ' — ' + rows.length + ' barang';

    const qtySum = rows.reduce((s, r) => s + num(pickCIChart(r, card.qty)), 0);
    let metaHtml = '<div class="dp-meta-item"><span class="dp-meta-label">Total ' + esc(card.label) +
      '</span><span class="dp-meta-val">' + format_number(qtySum, 2) + '</span></div>';
    if (card.rp) {
      const rpSum = rows.reduce((s, r) => s + num(pickCIChart(r, card.rp)), 0);
      metaHtml += '<div class="dp-meta-item"><span class="dp-meta-label">Total Rp</span><span class="dp-meta-val">Rp ' +
        format_number(rpSum, 0) + '</span></div>';
    }
    metaHtml += '<div class="dp-meta-item"><span class="dp-meta-label">Jumlah Barang</span><span class="dp-meta-val">' +
      rows.length + '</span></div>';
    document.getElementById('dpMeta').innerHTML = metaHtml;

    let bodyHtml = '<div class="dp-section-title">Rincian Barang</div>';
    if (!rows.length) {
      bodyHtml += '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:12.5px">Tidak ada barang pada kategori ini.</div>';
    } else {
      bodyHtml += '<table class="ledger-table"><thead><tr><th>Kode Barang</th><th>Nama Barang</th><th class="num">' +
        esc(card.label) + '</th>' + (card.rp ? '<th class="num">Rp</th>' : '') + '</tr></thead><tbody>';
      rows.forEach(function (r) {
        const kode = pickCIChart(r, 'KODEBRG') ?? pickCIChart(r, 'kode') ?? '';
        const nama = pickCIChart(r, 'NAMABRG') ?? pickCIChart(r, 'nama') ?? '';
        bodyHtml += '<tr><td>' + esc(kode) + '</td><td>' + esc(nama) + '</td><td class="num">' +
          format_number(num(pickCIChart(r, card.qty)), 2) + '</td>';
        if (card.rp) { bodyHtml += '<td class="num">' + format_number(num(pickCIChart(r, card.rp)), 0) + '</td>'; }
        bodyHtml += '</tr>';
      });
      bodyHtml += '</tbody></table>';
    }
    document.getElementById('dpBody').innerHTML = bodyHtml;

    document.getElementById('drillOverlay').classList.add('open');
    document.getElementById('drillPanel').classList.add('open');
  }

  function closeDfsDrill() {
    document.getElementById('drillOverlay').classList.remove('open');
    document.getElementById('drillPanel').classList.remove('open');
  }

  // === PENCARIAN SISI-KLIEN ===
  // Dulu memanggil doRenderTable(), function yang tidak ada di mana pun (bukan di blade
  // mana pun, bukan di public/js/report-table.js), jadi tiap ketukan tombol melempar
  // ReferenceError dan kotak cari tidak berfungsi sama sekali. Sekarang lewat
  // renderCachedReport(), jalur render yang memang dipakai halaman ini.

  // Baris yang seharusnya tampil sekarang: gcart_res disaring kata kunci di kotak cari
  // (kosong -> semua baris). Hanya kolom yang terlihat yang ikut dicari, dan nilai 0
  // ikut dibandingkan -- String(v || '') yang lama mengubah 0 jadi '' sehingga angka 0
  // tidak pernah cocok.
  function searchedRows() {
    const term = ($('#searchBox2').val() || '').trim().toLowerCase();
    if (!term) { return gcart_res; }

    const cols = gcart_header.filter(c => c[2] === 1);
    return gcart_res.filter(function (r) {
      return cols.map(function (c) {
        let v = r[c[0]];
        return (v == null) ? '' : String(v);
      }).join(' ').toLowerCase().indexOf(term) !== -1;
    });
  }

  function applyFilters() {
    if (typeof gcart_res === 'undefined' || !gcart_res.length) return;

    const rows = searchedRows();

    // doShowReport() diakhiri doGodown() -> scrollIntoView({behavior:'smooth'}).
    // Kalau dibiarkan, halaman meloncat ke tabel tiap ketukan tombol dan kotak cari
    // ikut tergeser keluar layar. Dimatikan sementara khusus untuk pencarian; jalur
    // lain (Tampilkan, drag kolom) tetap memakai doGodown seperti biasa.
    const _godown = window.doGodown;
    window.doGodown = function () {};
    try {
      renderCachedReport(rows);
    } finally {
      window.doGodown = _godown;
    }

    document.getElementById('footerLabel').textContent =
      rows.length ? ('Menampilkan ' + rows.length + ' baris') : 'Tidak ada data';
  }

  function toggleExport() { document.getElementById('exportDrop').classList.toggle('open'); }
  document.addEventListener('click', function (e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) { document.getElementById('exportDrop').classList.remove('open'); }
  });
  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }
    if (typeof gcart_res !== 'undefined') {
      const cols = gcart_header.filter(c => c[2] === 1);
      const header = cols.map(c => c[1]);
      const body = gcart_res.map(r => cols.map(c => c[3] === 'float' || c[3] === 'int' ? currencyNormalizer(r[c[0]]) : (r[c[0]] || '')));
      ReportExport.fromRows([header].concat(body), fmt, { customName: 'LaporanMutasiStok' });
    }
  }
</script>
@endsection
