@extends('report.masterreportGudang')
{{-- @include('report.modalbrowsemaster') dihapus: sudah tidak dipakai, halaman ini punya
     picker sendiri (.modal-picker / openPickMaster()) yang menggantikan popup #formBrowseMaster
     bawaan -- lihat komentar di dalam <script> jsreport. --}}

{{-- @section('reportname')
      <h3>Report Stock Fisik Gudang</h3>
@endsection --}}


@section('header2')
<style>
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

  /* Baris banner nama gudang di atas judul kolom (diisi setRowHeader()). Bukan kolom
     tabel, jadi tidak ikut .rt-th -- ReportTable mengabaikannya karena semua handler
     drag/gigi-nya digantung ke th.rt-th saja. */
  #tabel thead th.gdg-banner {
    text-align: left !important;
    background: #F8F9FF !important;
    color: var(--rt-ink, #1e293b) !important;
    font-size: 13.5px !important;
    letter-spacing: normal !important;
    text-transform: none !important;
    border-bottom: 1px solid #E2E8F4 !important;
  }

  /* Popup "Pilih Data" (Gudang/Grup) dari dalam modal Filter. Dibuat manual */
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

      <!-- Periode -->
      <div class="filter-wrap">
        <label>Periode</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
      </div>

      <!-- filter + filter data + tampilkan + export -->
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

    <!-- Bar kolom tersembunyi + Reset kolom (diisi report-table.js / ReportTable). -->
    <div id="rtBar"></div>

    <div class="table-outer">
      <div class="table-wrap" id="showTableReport">
        <table class="tb" id="tabel">
          <thead id="tabel_header">
            <tr>
              <th>KODE BRG</th>
              <th>NAMA BARANG</th>
              <th>ISI3</th>
              <th>SALDO SAT3</th>
              <th>ISI2</th>
              <th>SALDO SAT2</th>
              <th>SALDO SAT1</th>
              <th>FISIK</th>
              <th>SELISIH (+)</th>
              <th>SELISIH (-)</th>
            </tr>
          </thead>
          <tbody id="tabel_data">
            <tr class="empty-row">
              <td colspan="100%">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Tabel (Belum ada data dimuat) -->
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

          {{-- Combo diisi renderPickFields() dari PICK_FIELDS -- lihat
               docs/new-filter-modal-ui-guide.md #4. Combo hanya tampilan di atas input
               hidden di bawah; yang menyimpan nilai asli tetap input hidden-nya, jadi
               makeTable() dan pickMasterSelect() tidak perlu tahu combo ini ada. --}}
          <div class="rt-grid-2" id="pickFields"></div>

          <input type="hidden" id="inputGudang" value="-">
          <input type="hidden" id="inputGrup" value="-">
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

<!-- modal pilih data master (Gudang/Grup) -->
{{-- Picker gaya baru (docs/new-cust-supp-modal-guide.md): tanpa kolom Actions / tombol
     Select, seluruh baris bisa diklik. Skin-nya class .rt-picker-v2 -- class generik,
     bukan id-scoped (lihat catatan di public/css/report-table.css sekitar baris 1552),
     jadi id #modalPickMaster boleh tetap dipakai, sama seperti #formFilterData di
     masterreportGudang. Modal ini milik halaman ini sendiri, bukan blade shared, jadi
     tidak perlu digating window.g_pickerV2. --}}
<div class="modal-picker-backdrop" id="modalPickMasterBackdrop" onclick="closePickMaster()"></div>
<div class="modal-picker rt-picker-v2" id="modalPickMaster" tabindex="-1" role="dialog" aria-labelledby="modalPickMasterLabel" aria-hidden="true">
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
<script type="text/javascript">
  g_modeReport = 0;

  const reportTitle = "LAPORAN STOK FISIK GUDANG";
  const reportGroupBy = "KodeSupp";

  // Baris yang sedang ditampilkan saat pencarian aktif; null = tampil semua (gcart_res).
  let gRowsShown = null;

  function rowsShown() { return gRowsShown || gcart_res || []; }

  if (typeof loadingHtml !== 'function') {
    window.loadingHtml = function (msg) {
      return '<span style="display:inline-flex;align-items:center;gap:6px;color:#5A6A85;">' +
             '<i class="fas fa-spinner fa-spin"></i> ' + (msg || 'Memuat...') + '</span>';
    };
  }

  // Daftar field picker di modal Filter. Combo (.rt-combo) cuma tampilan; nilai aslinya
  // disimpan di input hidden ber-id sama -- lihat docs/new-filter-modal-ui-guide.md #4.
  const PICK_FIELDS = [
    { id: 'inputGudang', label: 'Gudang', url: '{!! $gudang !!}', title: 'Pilih Gudang' },
    { id: 'inputGrup',   label: 'Grup',   url: '{!! $grup !!}',   title: 'Pilih Grup' }
  ];

  function renderPickFields() {
    let html = '';
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val() || '-';
      const isSet = (val !== '-' && val !== '');
      html += '<div>';
      html += '<label class="rt-field-label">' + f.label + '</label>';
      html += '<div class="rt-combo">';
      html += '<div class="rt-combo-input" onclick="openPickMaster(\'' + f.id + '\')">';
      if (isSet) {
        html += '<span class="rt-combo-tag">' + htmlEscape(val) +
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

  /* modal "Pilih Data" (Gudang/Grup)
     Menggantikan popup shared #formBrowseMaster (search + Submit). Gaya picker baru:
     tanpa kolom Actions / tombol Select, seluruh baris diklik --
     lihat docs/new-cust-supp-modal-guide.md. */

  // Picker gaya baru: TANPA kolom Actions / tombol Select, baris langsung diklik.
  function pickerHeadHtml(cols) {
    return '<tr>' + cols.map(c => '<th>' + c + '</th>').join('') + '</tr>';
  }

  function pickerRowHtml(idPart, kode, cellsHtml) {
    return '<tr class="pick-row" onclick="pickMasterSelect(\'' + jsQuote(idPart) + '\',\'' + jsQuote(kode) + '\')">' +
      cellsHtml + '</tr>';
  }

  // kode master bisa mengandung tanda kutip / backslash. Nilainya masuk ke dalam string
  // JS yang ada DI DALAM atribut onclick="...", jadi harus di-escape dua kali: dulu
  // untuk JS (backslash + petik satu), lalu untuk atribut HTML (& dan petik dua).
  function jsQuote(_val) {
    return String(_val)
      .replace(/\\/g, '\\\\')
      .replace(/'/g, "\\'")
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;');
  }

  function htmlEscape(_val) {
    return String(_val)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  let pickerTargetInput = "";

  function openPickMaster(targetInputId) {
    const f = PICK_FIELDS.find(function (x) { return x.id === targetInputId; });
    if (!f) { return; }

    pickerTargetInput = targetInputId;
    const url = f.url;
    $("#modalPickMasterLabel").text(f.title);

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

  function renderPickMaster(res) {
    const kolom = (res && res.kolom) || [];
    const rows = (res && res.table) || [];

    $("#tabelPickMaster_header").html(pickerHeadHtml(kolom.map(function (k) { return k[1]; })));

    let bodyHtml = '';
    if (rows.length) {
      rows.forEach(function (item) {
        let cellsHtml = '';
        kolom.forEach(function (k) {
          let val;
          if (k[2] === 'date') { val = format_date(item[k[0]]); }
          else if (k[2] === 'float') { val = format_number(currencyNormalizer(item[k[0]]), k[3]); }
          else { val = nullToEmpty(item[k[0]]); }
          cellsHtml += '<td>' + val + '</td>';
        });
        const kode = kolom.length ? item[kolom[0][0]] : '';
        bodyHtml += pickerRowHtml(pickerTargetInput, kode, cellsHtml);
      });
    } else {
      bodyHtml = '<tr><td colspan="' + kolom.length + '" class="text-center">Tidak ada data ditemukan</td></tr>';
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

  // padanan buttonPilih(idPart, kode) di docs/new-cust-supp-modal-guide.md: menulis nilai
  // terpilih ke input hidden milik field itu, lalu menutup picker.
  function pickMasterSelect(idPart, kode) {
    if (idPart) { $('#' + idPart).val(kode); }
    closePickMaster();
    renderPickFields();
    updateFilterBadge();
  }

  // Badge "N aktif" di judul modal Filter. Field dengan nilai netral ("-") tidak
  // dihitung -- sama seperti aturan di new-filter-modal-ui-guide.md #5.
  function updateFilterBadge() {
    let count = 0;
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val();
      if (val && val !== '-') { count++; }
    });
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    PICK_FIELDS.forEach(function (f) { $('#' + f.id).val('-'); });
    renderPickFields();
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function () { renderPickFields(); updateFilterBadge(); });

  $(document).ready(function() {
    $("#gButtonCustomizeTable").hide();

    renderPickFields();

    $("#btnFilterData").on("click", function() {
      if (typeof doShowFormFilterData === "function") doShowFormFilterData();
      else alert(" Fungsi doShowFormFilterData belum tersedia.");
    });

    $("#btnCustomizeTable").on("click", function() {
      if (typeof doShowFormCustomizeTable === "function") doShowFormCustomizeTable();
      else alert(" Fungsi doShowFormCustomizeTable belum tersedia.");
    });

    $("#btnSubmitReport").on("click", function() {
      makeTable('REPORT');
    });

    // Header interaktif (drag / gigi / sembunyikan kolom) -- lihat
    // docs/new-slider-table-guide.md.
    ReportTable.init({
      table: '#tabel',
      bar: '#rtBar',
      onChange: renderCachedReport
    });
  });

  // Render ulang tabel dari data yang sudah dimuat, dipakai sebagai onChange oleh
  // ReportTable saat kolom di-drag/disembunyikan/direset. Hasil pencarian yang sedang
  // aktif ikut dipertahankan.
  function renderCachedReport() {
    if (!gcart_res || !gcart_res.length) { return; }
    doShowReport(rowsShown(), reportTitle, reportGroupBy, $("#inputDate1").val());
    alignNumericHeaders();
  }

  function setDefaultHeader() {
    gcart_header = [
      ['KodeBrg', 'Kode Brg', 1, 'varchar', 0, 0],
      ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
      ['KodeSupp', 'KodeSupp', 0, 'varchar', 0, 0],
      ['Isi3', 'Isi3', 1, 'float', 0, 0],
      ['SALDO3QNT', 'Saldo Sat3', 1, 'floatSat3', 0, 0],
      ['Isi2', 'Isi2', 1, 'float', 0, 0],
      ['SALDO2QNT', 'Saldo Sat2', 1, 'floatSat2', 0, 0],
      ['SALDO1QNT', 'Saldo Sat1', 1, 'floatSat1', 0, 0],
      ['Fisik', 'Fisik', 1, 'empty', 0, 0],
      ['Selisih (+)', 'Selisih (+)', 1, 'empty', 0, 0],
      ['Selisih (-)', 'Selisih (-)', 1, 'empty', 0, 0]
    ];

    gsum_issubtotal = 0; gsum_isgrandtotal = 0;
  }

  // Jumlah kolom yang sedang tampil. Semua colspan di setRowHeader/setRowFooter dihitung
  // dari sini, bukan angka 10 yang dulu di-hardcode -- kalau tidak, menyembunyikan satu
  // kolom lewat menu gigi bikin banner gudang & blok tanda tangan meleset saat dicetak.
  function visibleColCount() {
    return gcart_header.filter(c => c[2] === 1).length;
  }

  // Nama gudang untuk banner di atas judul kolom. Query-nya sinkron (async:false), jadi
  // hasilnya di-cache per kode gudang: tanpa ini setiap drag kolom (renderCachedReport)
  // ikut menembak ulang request yang mengunci UI.
  let gGudangBanner = { kode: null, teks: "" };

  function getGudangBannerText() {
    const _kode = $("#inputGudang").val();
    if (gGudangBanner.kode === _kode) { return gGudangBanner.teks; }

    let _teks = "";
    $.ajax({
      url     : "{!! url('functionbrowse_doLoadGudang') !!}",
      type    : "get",
      async   : false,
      data    : {
        kode : _kode
      },
      success: function(res) {
        if (res && res.length > 0) { _teks = res[0].KODEGDG + ' - ' + res[0].NAMA; }
      }
    })

    gGudangBanner = { kode: _kode, teks: _teks };
    return _teks;
  }

  function setRowHeader(_rowHeader) {
    const _n = visibleColCount();
    const _banner = getGudangBannerText();

    if (_banner !== "") {
      _rowHeader += '<tr>';
      _rowHeader += '  <th colspan="' + _n + '" class="gdg-banner">' + htmlEscape(_banner) + '</th>';
      _rowHeader += '</tr>';
    }

    // Header interaktif -- lihat docs/new-slider-table-guide.md.
    // cols HARUS berasal dari gcart_header.filter(...), bukan .map()/copy, supaya
    // ReportTable.headHtml() bisa memetakan tiap kolom balik ke index globalnya.
    const cols = gcart_header.filter(c => c[2] === 1);
    return _rowHeader + ReportTable.headHtml(cols);
  }

  // report-table.js hanya menandai kolom bertipe "float"/"int" sebagai .num (judul rata
  // kanan). Kolom Saldo di sini bertipe floatSat1/2/3 (float + nama satuan yang di-join),
  // jadi ditandai sendiri supaya judulnya ikut rata kanan seperti angkanya. Dipakai
  // data-gidx (index global kolom), yang tidak berubah walau kolom di-drag.
  function alignNumericHeaders() {
    gcart_header.forEach(function (c, i) {
      if (typeof c[3] === 'string' && c[3].startsWith('float')) {
        $('#tabel_header th.rt-th[data-gidx="' + i + '"]').addClass('num');
      }
    });
  }

  // Lebar 4 sel blok tanda tangan supaya totalnya selalu = jumlah kolom tampil (_n).
  // Sel dengan lebar 0 tidak dicetak. Di bawah 7 kolom spacer-nya dibuang dan sisanya
  // dibagi dua, supaya barisnya tetap pas walau kolom banyak disembunyikan.
  function ttdSpans(_n) {
    if (_n >= 7) { return [2, _n - 6, 3, 1]; }
    const _kiri = Math.max(1, Math.floor(_n / 2));
    return [_kiri, 0, _n - _kiri, 0];
  }

  function setRowFooter() {
    const _n = visibleColCount();
    const _style = 'style="border: none !important; outline: none !important;"';
    const _spans = ttdSpans(_n);

    // satu sel blok tanda tangan; dilewati kalau lebarnya 0
    function _ttdCell(_span, _isi) {
      if (_span < 1) { return ""; }
      return '  <td class="cellcompact-center" colspan="' + _span + '" ' + _style + '>' + _isi + '</td>';
    }

    function _spacerRow() {
      return '<tr><td colspan="' + _n + '" ' + _style + '></td></tr>';
    }

    let rowFooter = "";
    rowFooter += "<tr style='text-align: center'>";
    rowFooter += '  <td colspan="' + _n + '" class="cellcompact-center" style="border: 1px solid black; white-space:nowrap;">Total Item : ' + rowsShown().length + '</td>';
    rowFooter += '</tr>';

    let rowTTD = "";
    rowTTD += _spacerRow();
    rowTTD += _spacerRow();
    rowTTD += '<tr>';
    rowTTD += _ttdCell(_spans[0], '<b>Dibuat oleh,</b>');
    rowTTD += _ttdCell(_spans[1], '');
    rowTTD += _ttdCell(_spans[2], '<b>Mengetahui</b>');
    rowTTD += _ttdCell(_spans[3], '');
    rowTTD += '</tr>';
    rowTTD += _spacerRow();
    rowTTD += _spacerRow();
    rowTTD += _spacerRow();
    rowTTD += _spacerRow();
    rowTTD += '<tr>';
    rowTTD += _ttdCell(_spans[0], '<u><b>Ka. Gudang</b></u>');
    rowTTD += _ttdCell(_spans[1], '');
    rowTTD += _ttdCell(_spans[2], '<u><b>Supervisor</b></u>');
    rowTTD += _ttdCell(_spans[3], '');
    rowTTD += '</tr>';

    return rowFooter + rowTTD;
  }

  function makeTable(_mode, _callback) {
    // nilai groupby adalah nama kolom (sesuai database) untuk pengelompokan subtotal
    let groupby = reportGroupBy;
    let _date1  = $("#inputDate1").val();

    let data = {
      date1            : _date1,
      inputGudang      : $("#inputGudang").val(),
      inputGrup        : $("#inputGrup").val(),
    };

    // Mode FILTER dipanggil doShowFormFilterData() di layout, yang sekarang membaca
    // gcart_filter lewat _callback (doMakeTable() sudah async -- lihat catatan di
    // masterreportGudang.blade.php), jadi teruskan saja _callback apa adanya.
    if (_mode !== 'REPORT') {
      doMakeTable(_mode, groupby, data, reportTitle, _date1, null, _callback);
      return;
    }

    gRowsShown = null;          // data baru: batalkan hasil pencarian sebelumnya
    $('#searchBox2').val('');
    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    // doMakeTable() sekarang async (dulu async:false yang membekukan tab sampai response
    // datang tanpa sempat menggambar "Memuat data..." di atas) -- jadi bagian yang
    // membaca gcart_res harus jalan di callback ini, bukan lagi lewat trik nested rAF.
    doMakeTable(_mode, groupby, data, reportTitle, _date1, null, function () {
      alignNumericHeaders();

      let footerMsg = (gcart_res && gcart_res.length > 0)
        ? "Menampilkan " + gcart_res.length + " baris"
        : "Belum ada data dimuat";
      document.getElementById('footerLabel').textContent = footerMsg;

      if (typeof _callback === 'function') { _callback(); }
    });
  }

  function getKolomFilter() {
    // tentukan kolom (sesuai database & gcart_header) yang mau ditampilkan
    // berapa pun bisa asal dalam bentuk array
    return ['KodeBrg', 'NamaBrg'];
  }

  // Pencarian cepat di sisi client atas data yang sudah dimuat (gcart_res).
  function applyFilters() {
    if (typeof gcart_res === 'undefined' || !gcart_res.length) { return; }
    const term = ($('#searchBox2').val() || '').trim().toLowerCase();
    const cols = gcart_header.filter(c => c[2] === 1);

    gRowsShown = !term
      ? null
      : gcart_res.filter(r => cols.map(c => String(r[c[0]] || '')).join(' ').toLowerCase().indexOf(term) !== -1);

    doShowReport(rowsShown(), reportTitle, reportGroupBy, $("#inputDate1").val());
    alignNumericHeaders();

    document.getElementById('footerLabel').textContent =
      rowsShown().length > 0 ? "Menampilkan " + rowsShown().length + " baris" : "Tidak ada data ditemukan";
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
      const body = rowsShown().map(r => cols.map(c => c[3] === 'float' || c[3] === 'int' ? currencyNormalizer(r[c[0]]) : (r[c[0]] || '')));
      ReportExport.fromRows([header].concat(body), fmt, { customName: 'LaporanStokFisikGudang' });
    }
  }
</script>

@endsection
