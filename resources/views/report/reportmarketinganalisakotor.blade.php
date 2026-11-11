@extends('report.masterreport2')

<!-- Chart.js v4 (di-bundle lokal: public/plugins/chart.js/chart.umd.min.js).
     BUKAN Chart.min.js di folder yang sama -- itu Chart.js v2 (2019), API-nya beda. -->
<script src="{!! URL::asset('plugins/chart.js/chart.umd.min.js') !!}?v={{ @filemtime(base_path('public/plugins/chart.js/chart.umd.min.js')) ?: '1' }}"></script>

<style>
  .tb-report .table-wrap { min-height: 10vh; }

  /* ── Chart section (di atas tabel) — pola sama seperti reportaccountinghutangumur ── */
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

@include('report.modalMarketingSO')

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Report Marketing - Analisa Laba Kotor</div>
      </div> --}}

      <!-- Periode (date range) -->
      <div class="filter-wrap">
        <label>Periode</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
        <span class="filter-sep">s/d</span>
        <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
      </div>

      {{-- Search --}}
      <div>
          <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
      </div>

      <!-- Actions: search + filter modal + tampilkan + export -->
      <div class="action-group">
        {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5).
             Halaman ini memuat dua Bootstrap; jQuery dimuat SESUDAH bundle BS5, jadi
             $.fn.modal dipegang BS5. JS di bawah menutup modal ini dengan
             $('#modalFilter').modal('hide'), jadi pembukanya harus API yang sama. --}}
        <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
          <i class="fas fa-filter"></i> Filter
        </button>
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

    {{-- CHARTS (dibangun sisi-klien dari baris yang sedang tampil).
         Disembunyikan sampai ada data -- render() yang mengatur tampil/tidaknya. --}}
    <div class="chart-grid" id="chartGrid" style="display:none">
      <div class="chart-box">
        <h3>Komposisi Nilai DPP</h3>
        <div class="chart-holder"><canvas id="komposisiChart"></canvas></div>
      </div>
      <div class="chart-box">
        <h3>Laba Kotor per Customer (Top 10)</h3>
        <div class="chart-holder"><canvas id="topCustomerChart"></canvas></div>
      </div>
    </div>

    <!-- Bar kolom tersembunyi + Order By (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>No Bukti</th></tr>
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

  <!-- TOAST -->
  <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>
</div><!-- /tb-report -->

{{-- Modal DILETAKKAN DI LUAR .tb-report supaya reset `.tb-report *{margin:0;padding:0}`
     di report-table.css tidak merusak padding/margin modal Bootstrap. --}}

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
        <button type="button" class="btn-close" aria-label="Close"
          data-dismiss="modal" data-bs-dismiss="modal"
          onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          {{-- <div class="rt-group-label">Pengaturan Laporan</div> --}}
          {{-- Tidak ada switcher "Tampilan": Report Mode (Detail/Rekap) dan varian Order By
               (Merk/PIC/Group Customer, plus cabang "Nomor Barang" yang bahkan tidak pernah
               jadi opsi di dropdown) sebelumnya mengganti gcart_header ke boilerplate generik
               (NetW/GrossW, NDPP/NPPN/TotalIDR/Vls/Kurs/...usd) yang sudah ditemukan salah di
               beberapa laporan lain -- tidak spesifik untuk laporan Laba Kotor ini, dihapus
               bukan dipindah. Hanya susunan kolom Detail + Customer (default) yang dipertahankan
               -- satu-satunya yang terlihat dibuat khusus untuk laporan ini (field laba/HPP/
               margin). Order By juga tidak ada di sini lagi: parameter nyata ini (inputOrd,
               dikirim ke proc, benar-benar dipakai controller) sudah dipindah ke switcher
               "Order By" di bar atas tabel (ReportTable.init views), lihat setOrderBy(). --}}
          <div class="rt-grid-2">
            <div>
              <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
              <select class="rt-native" id="modalOtorisasi">
                <option value="0">Semua</option>
                <option value="1">Otorisasi</option>
                <option value="2">Non Otorisasi</option>
              </select>
            </div>
            <div>
              <label class="rt-field-label" for="modalAgen">Agen</label>
              <select class="rt-native" id="modalAgen">
                <option value="2">Semua</option>
                <option value="0">Agen</option>
                <option value="1">Non-Agen</option>
              </select>
            </div>
          </div>
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Filter Data
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          <div class="rt-grid-2" id="pickFields"></div>

          {{-- Nilai sebenarnya (dibaca makeTable() & ditulis buttonPilih()). Berbeda dari
               laporan lain di seri ini: keenam filter entitas ini SUNGGUHAN -- doReport()
               benar-benar membaca & meneruskannya ke Sp_ReportInvoicePenjualanDet. Sebelumnya
               tombol "+" di versi lama memanggil buttonSelect() yang tidak pernah didefinisikan
               (halaman ini tidak meng-include modalMarketingSO) -- filter-nya nyata tapi
               picker-nya rusak. Diperbaiki dengan include modalMarketingSO + pola rt-combo yang
               sama seperti reportmarketinglaporanoutso.blade.php. --}}
          <input type="hidden" id="inputCustomer" value="-">
          <input type="hidden" id="inputGroup" value="-">
          <input type="hidden" id="inputPIC" value="-">
          <input type="hidden" id="inputKategori" value="-">
          <input type="hidden" id="inputSubKategori" value="-">
          <input type="hidden" id="inputMerk" value="-">
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
@endsection

@section('jsreport')

<script type="text/javascript">
  // Picker (#formSelect di modalMarketingSO.blade.php): baris langsung diklik, tanpa
  // kolom Actions/tombol Select — konsisten dengan Filter modal yang sudah diredesain.
  window.g_pickerV2 = true;

  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalDate2 = "{!! date('Y-m-d') !!}";
  // Enum Otorisasi & Agen SAMA PERSIS seperti versi lama halaman ini. Otorisasi netral = "0"
  // (Semua) -- BEDA dari konvensi "2"=Semua yang dipakai di halaman lain, dipertahankan apa
  // adanya karena di sini keduanya (Otorisasi & Agen) SUNGGUHAN dibaca controller.
  let globalOtorisasi = "0"; // default: Semua
  let globalAgen = "2";      // default: Semua
  let globalOrderBy = "H";   // default: Customer

  let lastRows = [];        // hasil fetch terakhir (dipakai render / export / search)

  // Order By (inputOrd) -> kolom pengelompokan subtotal + kolom nama untuk label barisnya.
  // key  = kolom kode yang dipakai memecah grup (nilai apa adanya dari proc)
  // name = kolom nama yang tampil di baris "Subtotal <nama>"; fallback ke kode kalau kosong.
  const ORDER_GROUP = {
    'H' : { key: 'KodeCustSupp', name: 'NAMACUSTSUPP' }, // Customer
    'HM': { key: 'KOdeMerk',     name: 'NAMAMERK'     }, // Merk
    'HP': { key: 'KodePIC',      name: 'namaPIC'      }, // PIC
    'GC': { key: 'KodeHDGrp',    name: 'NAMAHDGRP'    }, // Group Customer
  };
  let currentGroup = ORDER_GROUP['H']; // grup aktif untuk render ulang saat search, ikut default globalOrderBy

  const reportUrl = "{{ url('laporanmarketinganalisakotor_doReport') }}";

  // Satu-satunya mode: Detail + Customer (default). Rekap dihapus (lihat komentar di modal
  // Filter) -- tidak ada switcher "Tampilan" di halaman ini. Order By TETAP memengaruhi
  // tampilan: tidak menukar gcart_header, tapi memilih ORDER_GROUP (kolom & label subtotal)
  // lewat makeTable(). Tetap satu slot kolom tersimpan per ordering walau isinya sama, supaya
  // kustomisasi kolom user (DBSIMPANHEADER) tidak bleed antar ordering.
  var modereport_customer      = 0,
      modereport_merk          = 1,
      modereport_pic           = 2,
      modereport_groupcustomer = 3;
  g_modeReport = modereport_customer;

  // Order By: H (Customer) / HM (Merk) / HP (PIC) / GC (Group Customer) -- parameter nyata
  // (Ordr) yang dikirim ke proc & benar-benar dipakai controller (dikonfirmasi dari
  // ORDER_GROUP/komentar yang sudah ada, bukan ditebak).
  const ORDER_OPTIONS = [
    { value: 'H',  label: 'Customer', desc: 'Dikelompokkan per Customer' },
    { value: 'HM', label: 'Merk', desc: 'Dikelompokkan per Merk' },
    { value: 'HP', label: 'PIC', desc: 'Dikelompokkan per PIC' },
    { value: 'GC', label: 'Group Customer', desc: 'Dikelompokkan per Group Customer' },
  ];
  let viewsCfg = {
    label: 'Order By',
    options: ORDER_OPTIONS,
    get: function() { return globalOrderBy; },
    set: function(v) {
      setOrderBy(String(v));
      if (lastRows.length) { makeTable('REPORT'); } // re-fetch: inputOrd adalah parameter SP
    }
  };

  $(document).ready(function() {
    setOtorisasi(globalOtorisasi);
    setAgen(globalAgen);
    showPeriode();
    setDefaultHeader();
    setOrderBy(globalOrderBy);

    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render,
      views: viewsCfg
    });
  });

  // periode
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
  }

  // otorisasi
  function setOtorisasi(val) {
    globalOtorisasi = val;
  }

  // agen
  function setAgen(val) {
    globalAgen = val;
  }

  // order by: ikut menentukan slot kolom tersimpan (lewat g_modeReport), bukan susunan kolom
  // itu sendiri -- lihat setDefaultHeader().
  function setOrderBy(val) {
    globalOrderBy = val;
    if (val === 'HM') g_modeReport = modereport_merk;
    else if (val === 'HP') g_modeReport = modereport_pic;
    else if (val === 'GC') g_modeReport = modereport_groupcustomer;
    else g_modeReport = modereport_customer;
    doSetHeader(g_modeReport);
    doShowCustomize();
  }

  /* -- FILTER MODAL -- */

  // Enam field "Filter Data" (Customer/Group/PIC/Kategori/Sub-Kat/Merk): nilai
  // sebenarnya tetap di input hidden #inputXxx (dibaca makeTable(), ditulis
  // buttonPilih() di modalMarketingSO.blade.php) — kotak .rt-combo di bawah ini
  // hanyalah tampilan di atasnya.
  const PICK_FIELDS = [
    { id: 'inputCustomer', label: 'Customer', modal: 'selectCustomer' },
    { id: 'inputGroup', label: 'Group', modal: 'selectGroup' },
    { id: 'inputPIC', label: 'PIC', modal: 'selectPIC' },
    { id: 'inputKategori', label: 'Kategori', modal: 'selectKategori' },
    { id: 'inputSubKategori', label: 'Sub-Kat', modal: 'selectSubKategori' },
    { id: 'inputMerk', label: 'Merk', modal: 'selectMerk' },
  ];

  function renderPickFields() {
    let html = '';
    PICK_FIELDS.forEach(function(f) {
      const val = $('#' + f.id).val() || '-';
      const isSet = (val !== '-' && val !== '');
      html += '<div>';
      html += '<label class="rt-field-label">' + f.label + '</label>';
      html += '<div class="rt-combo">';
      html += '<div class="rt-combo-input" onclick="pickFromModal(\'' + f.modal + '\')">';
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

  // Otorisasi netral = '0' (Semua, lihat catatan di atas). Agen netral = '2'. Order By tidak
  // lagi di modal ini (sudah jadi switcher "Order By" di #rtBar), jadi tidak dihitung ke
  // badge filter modal.
  function updateFilterBadge() {
    let count = 0;
    PICK_FIELDS.forEach(function(f) {
      const val = $('#' + f.id).val();
      if (val && val !== '-') { count++; }
    });
    if ($('#modalOtorisasi').val() !== '0') { count++; }
    if ($('#modalAgen').val() !== '2') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    $('#modalOtorisasi').val('0');
    $('#modalAgen').val('2');
    PICK_FIELDS.forEach(function(f) { $('#' + f.id).val('-'); });
    renderPickFields();
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function() {
    $('#modalOtorisasi').val(globalOtorisasi);
    $('#modalAgen').val(globalAgen);
    renderPickFields();
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  function applyModalFilter() {
    setOtorisasi($('#modalOtorisasi').val());
    setAgen($('#modalAgen').val());

    $('#modalFilter').modal('hide');
  }

  // Jembatan ke modal pilih entitas (#formSelect di modalMarketingSO.blade.php):
  // sembunyikan modal Filter dulu (hindari Bootstrap stacked-modal), lalu buka lagi
  // setelah modal pilih ditutup (buttonPilih() di modalMarketingSO sudah hide #formSelect).
  let g_reopenFilter = false;
  function pickFromModal(idModal) {
    g_reopenFilter = true;
    $('#modalFilter').modal('hide');
    buttonSelect(idModal);
  }
  $(document).on('hidden.bs.modal', '#formSelect', function() {
    if (g_reopenFilter) {
      g_reopenFilter = false;
      $('#modalFilter').modal('show');
      renderPickFields();
      updateFilterBadge();
    }
  });

  /* -- EXPORT -- */
  function toggleExport() {
    document.getElementById('exportDrop').classList.toggle('open');
  }
  document.addEventListener('click', function(e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) {
      document.getElementById('exportDrop').classList.remove('open');
    }
  });
  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }
    exportDelimited(fmt);
  }
  function exportDelimited(fmt) {
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function(c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'AnalisaLabaKotor_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* -- TOAST -- */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  // Nilai grup ternormalisasi (case-insensitive lewat pickCI + trim) -- BUKAN r[key]:
  // casing kolom dari proc tidak konsisten (NoBukti/KOdeMerk/namaPIC).
  function groupKeyOf(r, key) {
    const v = pickCI(r, key);
    return (v == null ? '' : String(v).trim());
  }

  // Kelompokkan baris menurut kolom grup TANPA mengubah urutan relatif di dalam tiap grup:
  // urutan grup mengikuti kemunculan pertamanya. Kalau proc sudah ORDER BY kolom yang sama,
  // ini no-op; kalau tidak, subtotal tetap utuh (tidak pecah jadi beberapa potongan).
  function groupRows(rows, key) {
    const order = [], bucket = {};
    rows.forEach(function(r) {
      const g = groupKeyOf(r, key);
      if (!(g in bucket)) { bucket[g] = []; order.push(g); }
      bucket[g].push(r);
    });
    return order.reduce(function(acc, g) { return acc.concat(bucket[g]); }, []);
  }

  // Kolom dari mode Detail + Customer (satu-satunya yang dipertahankan). NAMAHDGRP dulu
  // berlabel "Kategorix" (sisa ketikan) -- dikoreksi jadi "Kategori". qnt dulu bertipe
  // 'varchar' (tidak konsisten dengan kolom angka lain di baris yang sama) -- dikoreksi ke
  // 'float', label "Qty" mengikuti konvensi.
  function setDefaultHeader() {
    gcart_header = [
      ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
      ['NAMACUSTSUPP', 'Nama Cust', 1, 'varchar', 0, 0],
      ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
      ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
      ['qnt', 'Qty', 1, 'float', 0, 0],
      ['HARGA', 'Harga', 1, 'float', 1, 0],
      ['DiscP', 'Disc', 1, 'float', 1, 0],
      ['NDPP', 'Nilai DPP', 1, 'float', 1, 0],
      ['HPP', 'HPP', 1, 'float', 1, 0],
      ['hpprp', 'Total HPP', 1, 'float', 1, 0],
      ['laba', 'Laba Kotor', 1, 'float', 1, 0],
      ['prs', '% Laba Kotor', 1, 'float', 1, 0],
      ['prsMrg', '% Margin', 1, 'float', 1, 0],
      ['NAMAHDGRP', 'Kategori', 1, 'varchar', 0, 0],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  function makeTable(_mode) {
    let _date1    = $("#inputDate1").val();
    let _date2    = $("#inputDate2").val();
    let inputOto = globalOtorisasi;
    let _inputAgen = globalAgen;
    let _inputCustomer = $("#inputCustomer").val();
    let _inputGroup = $("#inputGroup").val();
    let _inputPIC = $("#inputPIC").val();
    let _inputKategori = $("#inputKategori").val();
    let _inputSubKategori = $("#inputSubKategori").val();
    let _inputMerk = $("#inputMerk").val();
    let input_order = globalOrderBy;

    // kolom pengelompokan subtotal + kolom nama untuk labelnya, mengikuti Order By yang dipilih
    const grp = ORDER_GROUP[input_order] || ORDER_GROUP['H'];

    setDefaultHeader();
    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    let data = {
      date1    : _date1,
      date2    : _date2,
      inputOto : inputOto,
      inputAgen: _inputAgen,
      inputCustomer: _inputCustomer,
      inputGroup: _inputGroup,
      inputPIC: _inputPIC,
      inputKategori: _inputKategori,
      inputSubKategori: _inputSubKategori,
      inputMerk: _inputMerk,
      inputOrd : input_order,
    };

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl,
      type: 'get',
      data: data,
      success: function(res) {
        lastRows = res || [];
        currentGroup = grp;
        $('#searchBox2').val('');
        render();
      },
      error: function() {
        lastRows = [];
        currentGroup = grp;
        render();
      }
    });
  }

  // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
  // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat / item[2]===1,
  // sesuai urutan simpanan).
  function render() {
    const cols = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
    const keys = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom yang di-subtotal
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const showSub = (gsum_issubtotal === 1);
    const showGrand = (gsum_isgrandtotal === 1);

    const search = ($('#searchBox2').val() || '').trim().toLowerCase();
    let rows = !search ? (lastRows || []) : (lastRows || []).filter(function(r) {
      return rowSearchText(r, cols).indexOf(search) !== -1;
    });

    // Kelompokkan baris menurut kolom Order By yang aktif sebelum dirender, supaya subtotal
    // tidak pecah kalau proc tidak ORDER BY kolom yang sama (lihat groupRows()).
    if (showSub) { rows = groupRows(rows, currentGroup.key); }

    // HEADER dinamis dari gcart_header — dibangun report-table.js (ReportTable) supaya kolom
    // bisa diseret untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    // Juga menyegarkan #rtBar (daftar kolom tersembunyi).
    thead.innerHTML = ReportTable.headHtml(cols);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      document.getElementById('chartGrid').style.display = 'none';
      return;
    }

    let html = '', prev = null, prevLabel = '';
    let sub = {}, grand = {};
    keys.forEach(k => { sub[k] = 0; grand[k] = 0; });

    rows.forEach(function(r, i) {
      const now = groupKeyOf(r, currentGroup.key);

      // subtotal saat nilai grup berganti (kalau toggle Subtotal aktif)
      if (showSub && i !== 0 && prev !== now) {
        html += totalRowTotal('Subtotal ' + prevLabel, sub, cols, keys, 'subtotal-row');
        keys.forEach(k => { sub[k] = 0; });
      }

      keys.forEach(function(k) {
        const v = currencyNormalizer(pickCI(r, k));
        sub[k] += v;
        grand[k] += v;
      });

      // satu sel per kolom terlihat, format menurut tipe (item[3]) & desimal (item[5])
      html += '<tr class="data-row">' + cols.map(function(c) {
        const key = c[0], type = c[3];
        if (type === 'date') return '<td>' + format_date(pickCI(r, key)) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(pickCI(r, key)), c[5]) + '</td>';
        return '<td>' + nullToEmpty(pickCI(r, key)) + '</td>';
      }).join('') + '</tr>';

      prev = now;
      prevLabel = nullToEmpty(pickCI(r, currentGroup.name)) || now || '-';
    });

    // subtotal grup terakhir + grand total   mengikuti toggle di modal Customize Table
    if (showSub) html += totalRowTotal('Subtotal ' + prevLabel, sub, cols, keys, 'subtotal-row');
    if (showGrand) html += totalRowTotal('GRAND TOTAL', grand, cols, keys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';

    // charts mengikuti baris yang tampil (hasil pencarian), bukan seluruh lastRows
    document.getElementById('chartGrid').style.display = '';
    buildCharts(rows);
  }

  // Baris total: nilai di kolom yang di-subtotal (item[4]===1), label di kolom pertama non-total
  // yang masih terlihat, sel lain dikosongkan   mengikuti urutan kolom terlihat saat ini.
  function totalRowTotal(label, total, cols, keys, cls) {
    const labelIdx = cols.findIndex(c => keys.indexOf(c[0]) === -1);

    const tds = cols.map(function(c, idx) {
      if (keys.indexOf(c[0]) !== -1) {
        return '<td class="num">' + format_number(total[c[0]], c[5]) + '</td>';
      }
      if (idx === labelIdx) return '<td>' + label + '</td>';
      return '<td></td>';
    });

    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  // === PENCARIAN SISI-KLIEN ===
  function applyFilters() {
    if (!lastRows.length) return; // belum ada data dimuat
    render();
  }

  // Gabungan teks satu baris dari kolom terlihat (tanggal pakai format tampil dd/mm/yyyy)
  // supaya pencarian cocok dengan apa yang user lihat di tabel.
  function rowSearchText(r, cols) {
    return cols.map(function(c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

  /* ── CHARTS (Chart.js v4) ────────────────────────────────────────────────
     Kiri  : Komposisi Nilai DPP (doughnut) — Total HPP + Laba Kotor, dengan Nilai DPP
             sebagai total di lubang doughnut. SENGAJA bukan 3 irisan (NDPP/HPP/Laba):
             NDPP ≈ HPP + Laba, jadi memasukkan ketiganya menghitung totalnya dua kali dan
             persentasenya tidak lagi terbaca sebagai bagian dari omzet.
     Kanan : Laba Kotor per Customer Top 10 (bar horizontal) — laba dijumlah per
             NAMACUSTSUPP, urut menurun.
     Agregasi membaca field MENTAH (NDPP/hpprp/laba/NAMACUSTSUPP), jadi menyembunyikan
     kolom lewat menu roda gigi tidak mengubah angka chart. ── */
  const CHART_PALETTE = ['#4F46E5','#7C3AED','#DB2777','#2563eb','#16a34a','#ca8a04','#ea580c','#0891b2','#e11d48','#65a30d'];
  const COLOR_HPP = '#64748b', COLOR_LABA = '#16a34a', COLOR_LABA_NEG = '#dc2626';
  let _charts = {};

  // num() dipakai apa adanya dari report-table.js (window.num, dimuat masterreport2 sebelum
  // section ini) -- TIDAK dideklarasi ulang di sini: `function num(){}` akan menimpa global
  // itu dan melewati guard `if (typeof window.num !== 'function')` di sana.
  function str(v) { return (v == null ? '' : String(v)).trim(); }

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

      // agregasi satu lintasan
      let totNDPP = 0, totHPP = 0, totLaba = 0;
      const custOrder = [], custSum = {};
      (rows || []).forEach(function(r) {
        totNDPP += currencyNormalizer(pickCI(r, 'NDPP'));
        totHPP  += currencyNormalizer(pickCI(r, 'hpprp'));
        totLaba += currencyNormalizer(pickCI(r, 'laba'));

        const nm = str(pickCI(r, 'NAMACUSTSUPP')) || '(Tanpa Nama)';
        if (!(nm in custSum)) { custSum[nm] = 0; custOrder.push(nm); }
        custSum[nm] += currencyNormalizer(pickCI(r, 'laba'));
      });
      const top = custOrder.map(n => [n, custSum[n]]).sort((a, b) => b[1] - a[1]).slice(0, 10);

      // ── Komposisi Nilai DPP (doughnut) ──
      // Nilai busur di-clamp ke >= 0 (Chart.js menggambar busur negatif secara keliru);
      // tooltip & teks tengah selalu memakai nilai asli (bertanda).
      const realVals = [totHPP, totLaba];
      const arcVals  = realVals.map(v => Math.max(0, v));
      const denom    = totNDPP !== 0 ? Math.abs(totNDPP) : (Math.abs(totHPP) + Math.abs(totLaba));

      const centerTotalPlugin = {
        id: 'centerTotal',
        afterDraw: function(chart) {
          const ctx = chart.ctx, area = chart.chartArea;
          if (!area) return;
          const cx = (area.left + area.right) / 2, cy = (area.top + area.bottom) / 2;
          ctx.save();
          ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
          ctx.fillStyle = '#64748b';
          ctx.font = "11px 'Segoe UI', system-ui, sans-serif";
          ctx.fillText('NDPP', cx, cy - 12);
          ctx.fillStyle = '#1e293b';
          ctx.font = "bold 18px 'Segoe UI', system-ui, sans-serif";
          ctx.fillText(fmtShort(totNDPP), cx, cy + 8);
          ctx.restore();
        }
      };

      _destroyChart('komposisi');
      _charts.komposisi = new Chart(document.getElementById('komposisiChart'), {
        type: 'doughnut',
        data: {
          labels: ['Total HPP', 'Laba Kotor'],
          datasets: [{
            data: arcVals,
            backgroundColor: [COLOR_HPP, (totLaba < 0 ? COLOR_LABA_NEG : COLOR_LABA)],
            borderWidth: 2, borderColor: '#fff'
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          cutout: '62%',
          plugins: {
            legend: { position: 'right' },
            tooltip: {
              callbacks: {
                label: function(c) {
                  const real = realVals[c.dataIndex];
                  const pct = denom ? (real / denom * 100).toFixed(1) : '0.0';
                  return ' ' + c.label + ': ' + fmtShort(real) + ' (' + pct + '%)';
                }
              }
            }
          }
        },
        plugins: [centerTotalPlugin]
      });

      // ── Top 10 customer bar (horizontal) ──
      _destroyChart('topCustomer');
      _charts.topCustomer = new Chart(document.getElementById('topCustomerChart'), {
        type: 'bar',
        data: {
          labels: top.map(t => t[0]),
          datasets: [{
            label: 'Laba Kotor',
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
            tooltip: {
              callbacks: {
                title: (items) => (items.length ? items[0].label : ''),   // nama lengkap (sumbu Y dipotong)
                label: (c) => ' ' + fmtShort(c.parsed.x)
              }
            }
          },
          scales: {
            x: { ticks: { callback: (v) => fmtShort(v) } },
            y: { ticks: { callback: function(v) {
                   const s = String(this.getLabelForValue(v));
                   return s.length > 22 ? s.slice(0, 21) + '…' : s;
                 } } }
          }
        }
      });
    } catch (e) { console.error('buildCharts', e); }
  }

</script>

@endsection
