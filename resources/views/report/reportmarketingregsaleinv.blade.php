@extends('report.masterreport2')

<style>
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@include('report.modalMarketingSO')

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Report Marketing - Register Sale Invoice</div>
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

    <!-- Bar kolom tersembunyi + Order By (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>Sales</th></tr>
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
          <div class="rt-group-label">Pengaturan Laporan</div>
          {{-- Tidak ada switcher "Tampilan": mode Rekap yang dulu ada di halaman ini tidak
               pernah bisa diakses user (toggle-nya `hidden` di versi lama) dan kolomnya adalah
               copy-paste dari laporan lain (proc Sp_ReportInvoicePenjualanRek tidak punya
               pemanggil lain di repo untuk cross-check) -- dihapus, bukan dipindah. Order By
               (No Bukti/Barang/Customer) juga tidak ada di sini lagi: parameter nyata ini
               (dikirim ke proc, benar-benar dipakai controller) sudah dipindah ke switcher
               "Order By" di bar atas tabel (ReportTable.init views), lihat setOrderBy(). --}}
          <div class="rt-grid-2">
            <div>
              <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
              <select class="rt-native" id="modalOtorisasi">
                <option value="2">Semua</option>
                <option value="0">Non Otorisasi</option>
                <option value="1">Otorisasi</option>
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

          {{-- Nilai sebenarnya (dibaca makeTable() & ditulis buttonPilih()) --}}
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
  // Pakai gaya baru modal pemilih entitas (#formSelect di modalMarketingSO.blade.php):
  // baris tabel langsung diklik, tanpa kolom Actions/tombol Select.
  window.g_pickerV2 = true;

  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalDate2 = "{!! date('Y-m-d') !!}";
  // Enum Otorisasi & Agen SAMA PERSIS seperti versi lama halaman ini (bukan konvensi
  // 0=Sudah/1=Belum/2=Semua yang dipakai di halaman lain) -- di sini controller SUDAH
  // membaca inputOto/inputAgen sungguhan, jadi nilainya dipertahankan apa adanya, tidak ditebak.
  let globalOtorisasi = "2"; // default: Semua (0=Non Otorisasi, 1=Otorisasi, 2=Semua)
  let globalAgen = "2";      // default: Semua (0=Agen, 1=Non-Agen, 2=Semua)
  let globalOrderBy = "N";   // default: No Bukti

  let lastRows = [];        // hasil fetch terakhir (dipakai render / export / search)
  let currentGroupby = 'NoBukti'; // groupby aktif untuk render ulang saat search

  const reportUrl = "{{ url('laporanmarketingregsaleinv_doReport') }}";

  // Satu-satunya mode: Detail. Rekap dihapus (lihat komentar di modal Filter) -- tidak ada
  // switcher "Tampilan" untuk halaman ini. Order By (N/B/C) sebelumnya juga menukar seluruh
  // gcart_header ke set boilerplate NOBUKTI/KODEBRG/... yang sama seperti Rekap -- generik,
  // tidak spesifik ke proc ini -- jadi satu gcart_header dipakai untuk semua pilihan Order By;
  // bedanya hanya di groupby subtotal & parameter yang dikirim ke proc. Tetap satu slot kolom
  // tersimpan per ordering walau isinya sama, supaya kustomisasi kolom user (DBSIMPANHEADER)
  // tidak bleed antar ordering.
  var modereport_nobukti  = 0,
      modereport_barang   = 1,
      modereport_customer = 2;
  g_modeReport = modereport_nobukti;

  // Order By: N (No Bukti) / B (Barang) / C (Customer) -- parameter nyata (Ordr)
  // yang dikirim ke proc Sp_ReportInvoicePenjualanDet & dikonfirmasi diterima proc tsb. Kolom
  // yang ditampilkan TIDAK berubah per ordering (lihat setDefaultHeader()) -- hanya
  // groupby/subtotal & parameter Ordr yang berubah.
  const ORDER_OPTIONS = [
    { value: 'N', label: 'No Bukti', desc: 'Dikelompokkan per No Bukti' },
    { value: 'B', label: 'Barang', desc: 'Dikelompokkan per Kode Barang' },
    { value: 'C', label: 'Customer', desc: 'Dikelompokkan per Customer' },
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
    if (val === 'B') g_modeReport = modereport_barang;
    else if (val === 'C') g_modeReport = modereport_customer;
    else g_modeReport = modereport_nobukti;
    doSetHeader(g_modeReport);
    doShowCustomize();
  }

  /* -- FILTER MODAL -- */

  // Enam field "Filter Data" (Customer/Group/PIC/Kategori/Sub-Kategori/Merk): nilai
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

  // Order By tidak lagi di modal ini (sudah jadi switcher "Order By" di #rtBar), jadi tidak
  // dihitung ke badge filter modal.
  function updateFilterBadge() {
    let count = 0;
    PICK_FIELDS.forEach(function(f) {
      const val = $('#' + f.id).val();
      if (val && val !== '-') { count++; }
    });
    if ($('#modalOtorisasi').val() !== '2') { count++; }
    if ($('#modalAgen').val() !== '2') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    $('#modalOtorisasi').val('2');
    $('#modalAgen').val('2');
    PICK_FIELDS.forEach(function(f) {
      $('#' + f.id).val('-');
    });
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
    ReportExport.fromRows(rows, fmt, { customName: 'RegisterSaleInvoice_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
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

  // Field APA ADANYA dari proc lama (NAMASLS, pJasa, NoPajak, NDPP, NPPNRP, NNETRP,
  // NAMACUSTSUPP, NoPesanan). Label DIKOREKSI dari versi lama yang salah pasang
  // (NAMASLS sebelumnya berlabel "No. Bukti", NoPajak sebelumnya berlabel "Lokasi Penerima"):
  // NAMASLS -> "Sales" dan NoPajak -> "No. Pajak" mengikuti konvensi penamaan yang sama
  // dipakai di halaman report lain di repo ini. pJasa TIDAK ikut dikoreksi dengan yakin --
  // artinya field-nya belum tentu "Jasa" yang sebenarnya, label ini sekadar tebakan wajar
  // dari nama field, BUKAN hasil verifikasi ke definisi SP (SP tidak ada di repo).
  function setDefaultHeader() {
    gcart_header = [
      ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
      ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
    //   ['pJasa', 'Jasa', 1, 'varchar', 0, 0],
      ['NoPajak', 'No. Pajak', 1, 'varchar', 0, 0],
      ['tglfpj', 'Tgl Pajak', 1, 'date', 0, 0],
      ['NDPP', 'DPP', 1, 'float', 1, 0],
      ['NPPNRP', 'PPN', 1, 'float', 1, 0],
      ['NNETRP', 'Total', 1, 'float', 1, 0],
      ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
      ['NoPesanan', 'PO. Customer', 1, 'varchar', 0, 0],
      ['NAMAHDGRP', 'Group', 1, 'varchar', 0, 0],
      ['namaPIC', 'PIC', 1, 'varchar', 0, 0],
      ['NamaSubGrp', 'Kategori', 1, 'varchar', 0, 0],
      ['NamaSubkategori', 'Sub. Kategori', 1, 'varchar', 0, 0],
      ['NAMAMERK', 'Merk', 1, 'varchar', 0, 0],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  function makeTable(_mode) {
    // nilai groupby adalah nama kolom (sesuai database) untuk pengelompokan subtotal
    let groupby = '';
    let _date1    = $("#inputDate1").val();
    let _date2    = $("#inputDate2").val();
    let inputOto = globalOtorisasi;
    let inputAgen = globalAgen;
    let input_order = globalOrderBy;
    let _inputCustomer = $("#inputCustomer").val();
    let _inputGroup = $("#inputGroup").val();
    let _inputPIC = $("#inputPIC").val();
    let _inputKategori = $("#inputKategori").val();
    let _inputSubKategori = $("#inputSubKategori").val();
    let _inputMerk = $("#inputMerk").val();

    if (input_order == "B") {
      groupby = 'KodeBrg';
    } else if (input_order == "C") {
      groupby = 'KodeCustSupp';
    } else {
      groupby = 'NoBukti';
    }

    setDefaultHeader();
    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    let data = {
      date1    : _date1,
      date2    : _date2,
      inputOto : inputOto,
      inputAgen: inputAgen,
      inputOrd : input_order,
      inputCustomer   : _inputCustomer,
      inputGroup      : _inputGroup,
      inputPIC        : _inputPIC,
      inputKategori   : _inputKategori,
      inputSubKategori: _inputSubKategori,
      inputMerk       : _inputMerk,
    };

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl,
      type: 'get',
      data: data,
      success: function(res) {
        lastRows = res || [];
        currentGroupby = groupby;
        $('#searchBox2').val('');
        render();
      },
      error: function() {
        lastRows = [];
        currentGroupby = groupby;
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
    const rows = !search ? (lastRows || []) : (lastRows || []).filter(function(r) {
      return rowSearchText(r, cols).indexOf(search) !== -1;
    });

    // HEADER dinamis dari gcart_header — dibangun report-table.js (ReportTable) supaya kolom
    // bisa diseret untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    // Juga menyegarkan #rtBar (daftar kolom tersembunyi).
    thead.innerHTML = ReportTable.headHtml(cols);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    let html = '', prev = null;
    let sub = {}, grand = {};
    keys.forEach(k => { sub[k] = 0; grand[k] = 0; });

    rows.forEach(function(r, i) {
      const now = r[currentGroupby];

      // subtotal saat nilai grup berganti (kalau toggle Subtotal aktif)
      if (showSub && i !== 0 && prev !== now) {
        html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
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
    });

    // subtotal grup terakhir + grand total   mengikuti toggle di modal Customize Table
    if (showSub) html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
    if (showGrand) html += totalRowTotal('GRAND TOTAL', grand, cols, keys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
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

</script>

@endsection
