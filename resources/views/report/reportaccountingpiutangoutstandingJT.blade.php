@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Piutang Outstanding per Jatuh Tempo: styled .tb-report, dikelompokkan per customer (NamaCust)
     dengan subtotal + grand total (Jumlah/Bayar/Sisa Rp). Kolom (gcart_header) interaktif lewat
     ReportTable (seret/gear/Reset kolom). Perkiraan / Urut / Group By / Supp Awal-Akhir / PIC /
     Lokasi semua ada di modal "Filter Laporan" (halaman ini tidak punya mode Valas). Klik baris
     data (No Nota) buka voucher. --}}
<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Piutang Outstanding Per Jatuh Tempo</div>
        <div class="page-sub">Dicetak oleh: {{ $akses['user'] }} &nbsp;&middot;&nbsp; <span id="printTime"></span></div>
      </div> --}}

      <!-- Per Tanggal -->
      <div class="filter-wrap">
        <label>Per Tanggal</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
      </div>

      {{-- Perkiraan, Urut, Group By, Supp Awal/Akhir, PIC & Lokasi dipindah ke modal
           "Filter Laporan" (lihat di luar .tb-report). Nilai sebenarnya tetap di input hidden
           #inputPerkiraan / #inputOrd / #inputGroup / #inputSuppAwal / #inputSuppAkhir /
           #inputCust / #inputLokasi, dibaca makeTable(). Halaman ini tidak punya mode Valas —
           #valas_value tetap 'IDR' konstan (dikirim SP tapi tak pernah berubah). --}}
      <input type="hidden" id="valas_value" value="IDR">
      <input type="hidden" id="inputPerkiraan" value="-">
      <input type="hidden" id="inputOrd" value="0">
      <input type="hidden" id="inputGroup" value="0">
      <input type="hidden" id="inputSuppAwal" value="-">
      <input type="hidden" id="inputSuppAkhir" value="-">
      <input type="hidden" id="inputCust" value="-">
      <input type="hidden" id="inputLokasi" value="-">

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

    <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE (header + rows rendered dynamically from gcart_header; grouped per customer) -->
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

<!-- modal select PIC -->
<div class="modal fade rt-picker-v2" id="formSelectCustomer" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select PIC</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table id="tabelSelectCustomer">
          <thead>
            <tr><th>Kode Penerima</th><th>Nama Penerima</th></tr>
          </thead>
          <tbody id="tabel_dataSelectCustomer"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<!-- modal select lokasi -->
<div class="modal fade rt-picker-v2" id="formSelectLokasi" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Lokasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table id="tabelSelectLokasi">
          <thead>
            <tr><th>Kode Kebun</th><th>Nama Kebun</th></tr>
          </thead>
          <tbody id="tabel_dataSelectLokasi"></tbody>
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
             $.fn.modal milik BS4 (jQuery dimuat sesudah bundle BS5). data-bs-dismiss dibiarkan
             untuk jaga-jaga. Lihat new-design-all-report-guide.md §5.1. --}}
        <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#modalFilter').modal('hide')"></button>
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
              <label class="rt-field-label" for="modalOrder">Urut</label>
              <select class="rt-native" id="modalOrder">
                <option value="0">Tanggal</option>
                <option value="1">No.Nota</option>
              </select>
            </div>
            <div>
              <label class="rt-field-label" for="modalGroup">Group</label>
              <select class="rt-native" id="modalGroup">
                <option value="0">Default</option>
                <option value="1">Group</option>
              </select>
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
  let globalOrderBy = "0";   // default: Tanggal
  let globalGroupBy = "0";   // default

  let g_reportTitle = "";
  let g_inputPerkiraan = "";

  let lastRows = [];        // hasil fetch terakhir (dipakai render / search)
  let perkiraanList = [];   // daftar akun dari loadPerkiraanDropdown (dipakai resetAllFilters)

  // Report ini mode tunggal (tanpa Valas). Mode NUMERIK: DBSIMPANHEADER.reportmode itu
  // kolom integer, jadi mode string membuat header (termasuk toggle Subtotal/Grand Total)
  // TIDAK tersimpan.
  var modereport_detail = 13;
  g_modeReport = modereport_detail;
  var jenisreport = 0, DetOrRekap = 0;

  const reportUrl = "{{ url('reportaccountingpiutangoutstandingJT_doReport') }}";

  // Bottom voucher panel endpoints (report-table.js is loaded via masterreport2).
  // Clicking a row calls openVoucher(no, jenisFromNo(no)); for piutang the No Nota
  // is a sales Invoice (INVC) → CetakInvoicePenjualan.
  window.ReportTableConfig = {
    kasUrl    : "{{ url('reportaccountingpiutangoutstandingJT_doKasharian') }}",
    invoiceUrl: "{{ url('reportaccountingpiutangoutstandingJT_doInvoice') }}",
    lpbUrl    : "{{ url('reportaccountingpiutangoutstandingJT_doLpb') }}",
    bpUrl     : "{{ url('reportaccountingpiutangoutstandingJT_doBp') }}"
  };

  $(document).ready(function () {
    // #printTime hanya ada bila blok page-title/page-sub di toolbar aktif (saat ini
    // dikomentari) — lihat catatan yang sama di reportaccountinghutanglphto.
    setOrderBy(globalOrderBy);
    setGroupBy(globalGroupBy);
    setModeReport();                   // muat gcart_header + customize
    loadPerkiraanDropdown();           // isi dropdown Perkiraan (default akun pertama)

    // Header tabel interaktif (drag/gear/hide/decimal/total). Halaman ini tidak punya
    // mode Valas, jadi tanpa switcher "Tampilan".
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render
    });
  });

  /* ── kolom (gcart_header). Tabel styled DI-RENDER dari sini (Customize Table).
        Kolom uang (Jumlah/Bayar/Sisa Rp) ditandai total (item[4]=1) → ikut Subtotal &
        Grand Total. Kolom customer (NamaCust) jadi header grup, tidak ditampilkan lagi. ── */
  function setDefaultHeader() {
    gcart_header = [
      ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
      ['JatuhTempo', 'Jatuh Tempo', 1, 'date', 0, 0],
      ['Nofaktur', 'No Nota', 1, 'varchar', 0, 0],
      ['PONO', 'PO Cust', 1, 'varchar', 0, 0],
      ['Jumlah', 'Jumlah Rp', 1, 'float', 1, 2],
      ['Terbayar', 'Bayar Rp', 1, 'float', 1, 2],
      ['sisa', 'Sisa Rp', 1, 'float', 1, 2],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  function setModeReport() {
    g_modeReport = modereport_detail;
    doSetHeader(g_modeReport);   // muat susunan kolom (default / kustomisasi tersimpan)
    doShowCustomize();
  }

  /* ── toolbar controls ── */
  function showPeriode() { globalDate1 = $('#inputDate1').val(); }

  function setOrderBy(val) {
    globalOrderBy = val;
    $('#inputOrd').val(val);
  }

  function setGroupBy(val) {
    globalGroupBy = val;
    $('#inputGroup').val(val);
  }

  /* ── FILTER MODAL (Perkiraan, Urut, Group, Supp Awal/Akhir, PIC, Lokasi) ──
     Nilai sebenarnya tetap di input hidden #inputPerkiraan / #inputOrd / #inputGroup /
     #inputSuppAwal / #inputSuppAkhir / #inputCust / #inputLokasi (dibaca makeTable(), ditulis
     di sini / buttonPilihSuppAwal / buttonPilihSuppAkhir / buttonPilihCustomer /
     buttonPilihLokasi). Kontrol di modal (#modalPerkiraan, #modalOrder, #modalGroup) hanya
     tampilan pending di atasnya sampai "Terapkan" diklik; Supp Awal/Akhir/PIC/Lokasi ter-commit
     langsung begitu dipilih di picker masing-masing (sama seperti pola reportaccountinghutangkartu). ── */
  const PICK_FIELDS = [
    { id: 'inputSuppAwal',  label: 'Supp Awal',  open: 'suppAwal' },
    { id: 'inputSuppAkhir', label: 'Supp Akhir', open: 'suppAkhir' },
    { id: 'inputCust',      label: 'PIC',        open: 'pic' },
    { id: 'inputLokasi',    label: 'Lokasi',     open: 'lokasi' },
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
        html += '<span class="rt-combo-tag">' + esc(val) +
          '<button type="button" onclick="event.stopPropagation(); clearPickField(\'' + f.id + '\')">&times;</button></span>';
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

  // Perkiraan/Urut/Group: pilihan wajib (tanpa opsi netral) — sengaja tidak dihitung, sama
  // seperti halaman kartu. Supp Awal/Akhir/PIC/Lokasi: default '-' (belum dipilih) → dihitung
  // saat sudah diisi.
  function updateFilterBadge() {
    let count = 0;
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val();
      if (val && val !== '-') { count++; }
    });
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    $('#modalOrder').val('0');
    $('#modalGroup').val('0');
    if (perkiraanList.length) {
      $('#modalPerkiraan').val(perkiraanList[0].Perkiraan);
    }
    PICK_FIELDS.forEach(function (f) {
      $('#' + f.id).val('-');
    });
    renderPickFields();
    updateFilterBadge();
  }

  // Saat modal Filter dibuka ulang otomatis sesudah picker Supp Awal/Akhir/PIC/Lokasi ditutup
  // (lihat pickFromModal / hidden.bs.modal di bawah), JANGAN timpa ulang pilihan pending
  // (Perkiraan/Urut/Group) dari nilai yang sudah di-Terapkan — kalau tidak, pilihan Perkiraan
  // yang belum di-Terapkan hilang begitu user selesai memilih salah satu picker.
  // g_reopeningFilter ditandai true sesaat sebelum modal dibuka ulang di jalur itu saja.
  let g_reopeningFilter = false;

  $('#modalFilter').on('show.bs.modal', function () {
    if (!g_reopeningFilter) {
      $('#modalPerkiraan').val($('#inputPerkiraan').val());
      $('#modalOrder').val(globalOrderBy);
      $('#modalGroup').val(globalGroupBy);
    }
    g_reopeningFilter = false;
    renderPickFields();
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  // Ganti Perkiraan (pending, belum Terapkan) membatalkan pilihan Supp Awal/Akhir yang sedang
  // pending juga, sama seperti perilaku lama saat Perkiraan diganti di toolbar — daftar
  // supplier tergantung Perkiraan yang dipilih. PIC/Lokasi tidak tergantung Perkiraan.
  $('#modalFilter').on('change', '#modalPerkiraan', function () {
    $('#inputSuppAwal').val('-');
    $('#inputSuppAkhir').val('-');
    renderPickFields();
    updateFilterBadge();
  });

  function applyModalFilter() {
    const kode = $('#modalPerkiraan').val();
    const ket = $('#modalPerkiraan option:selected').data('ket') || '';
    setPerkiraan(kode, ket);
    if ($('#modalOrder').length) { setOrderBy($('#modalOrder').val()); }
    if ($('#modalGroup').length) { setGroupBy($('#modalGroup').val()); }
    $('#modalFilter').modal('hide');
  }

  // Jembatan ke modal pilih Supp Awal/Akhir/PIC/Lokasi: sembunyikan modal Filter dulu (hindari
  // Bootstrap stacked-modal), lalu buka lagi setelah modal pilih ditutup.
  let g_reopenFilter = false;

  function pickFromModal(which) {
    g_reopenFilter = true;
    $('#modalFilter').modal('hide');
    if (which === 'suppAwal') { buttonSelectSuppAwal(); }
    else if (which === 'suppAkhir') { buttonSelectSuppAkhir(); }
    else if (which === 'pic') { buttonSelectCustomer(); }
    else if (which === 'lokasi') { buttonSelectLokasi(); }
  }

  $(document).on('hidden.bs.modal', '#formSelectSuppAwal, #formSelectSuppAkhir, #formSelectCustomer, #formSelectLokasi', function () {
    if (g_reopenFilter) {
      g_reopenFilter = false;
      g_reopeningFilter = true;
      $('#modalFilter').modal('show');
      // 'show.bs.modal' juga memanggil ini, tapi panggil lagi di sini supaya kotak .rt-combo
      // langsung terupdate walau modal masih dalam proses transisi tampil.
      renderPickFields();
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
    const header = ['Customer'].concat(cols.map(c => c[1]));
    const body = (lastRows || []).map(r => [str(pickCI(r, 'NamaCust'))].concat(cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    })));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'PiutangOutstandingJT_' + (globalDate1 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (sp_ReportSisaPiutangN; doReport mengembalikan array biasa) ── */
  function makeTable(_mode) {
    globalDate1 = $('#inputDate1').val();
    g_reportTitle = 'REPORT ACCOUNTING PIUTANG OUTSTANDINGJT';

    let _perk   = $('#inputPerkiraan').val() || '-';
    let _suppAw = $('#inputSuppAwal').val()  || '-';
    let _suppAk = $('#inputSuppAkhir').val() || '-';
    let _cust   = $('#inputCust').val()      || '-';
    let _lokasi = $('#inputLokasi').val()    || '-';
    let _ord    = $('#inputOrd').val();
    let _group  = $('#inputGroup').val();
    let _valas  = $('#valas_value').val();

    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = {
      date1: globalDate1,
      inputSuppAwal: _suppAw, inputSuppAkhir: _suppAk,
      inputOrd: _ord, inputGroup: _group, inputPerkiraan: _perk,
      valas_value: _valas, inputCust: _cust, inputLokasi: _lokasi
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
  // HTML-escape teks bebas (nama customer/PIC/lokasi bisa diisi user).
  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // No Nota cell: clickable only for a real voucher number (has '/', and is not an
  // opening "Saldo Awal"/"AWL" row). Opens the bottom voucher panel (report-table.js),
  // dispatching by the Jenis parsed from the number itself.
  function isVoucherNo(v) {
    const s = str(v);
    if (!s || s.indexOf('/') === -1) return false;
    return s.toUpperCase().indexOf('SALDO AWAL') === -1;
  }
  // Kolom No Nota kini hanya PETUNJUK visual (garis bawah biru); klik dilakukan pada
  // SELURUH baris (lihat voucherRowOpen) karena hanya ada satu kolom yang bisa diklik.
  function voucherCell(v) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<td>' + nullToEmpty(v) + '</td>';
    return '<td class="kas-clickable" style="color:#0d6efd;text-decoration:underline">' + nullToEmpty(v) + '</td>';
  }

  // Tag <tr> pembuka baris data: bila memuat nomor voucher betulan, SELURUH baris
  // bisa diklik untuk membuka voucher (report-table.js).
  function voucherRowOpen(v, cls) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<tr class="' + cls + '">';
    const jn  = (typeof jenisFromNo === 'function') ? jenisFromNo(s) : '';
    const ttl = (typeof jenisTitle === 'function') ? jenisTitle(jn) : 'Voucher';
    const esc2 = s.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    const jsc = String(jn).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    return '<tr class="' + cls + '" title="Klik untuk lihat ' + ttl + ' ' + s + '" ' +
           'onclick="openVoucher(\'' + esc2 + '\',\'' + jsc + '\')">';
  }

  /* ── RENDER: dikelompokkan per customer (kolom `NamaCust`). Subtotal & Grand Total
     menjumlahkan semua kolom ber-total (item[4]=1). ── */
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

    // HEADER dinamis + interaktif (drag/gear/hide/desimal/total) lewat ReportTable
    thead.innerHTML = ReportTable.headHtml(cols);

    // kelompokkan per customer (NamaCust), pertahankan urutan kemunculan
    const order = [], buckets = {};
    (lastRows || []).forEach(r => {
      if (search && rowSearchText(r, cols).indexOf(search) === -1) return;
      const gkey = str(pickCI(r, 'NamaCust'));
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
        html += voucherRowOpen(pickCI(r, 'nofaktur'), 'data-row') + cols.map(function (c) {
          const key = c[0], type = c[3];
          const v = pickCI(r, key);
          if (type === 'date') return '<td>' + format_date(v) + '</td>';
          if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(v), c[5]) + '</td>';
          if (String(key).toLowerCase() === 'nofaktur') return voucherCell(v);
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

  // Baris total: nilai di tiap kolom pada `sumKeys`; label di kolom pertama non-sum.
  function totalRow(label, sums, cols, sumKeys, cls) {
    const labelIdx = cols.findIndex(c => sumKeys.indexOf(c[0]) === -1);
    const tds = cols.map(function (c, idx) {
      if (sumKeys.indexOf(c[0]) !== -1) return '<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>';
      if (idx === labelIdx) return '<td>' + label + '</td>';
      return '<td></td>';
    });
    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  /* ── PENCARIAN SISI-KLIEN ── */
  function applyFilters() { render(); }

  function rowSearchText(r, cols) {
    let s = str(pickCI(r, 'NamaCust'));   // ikutkan nama customer
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

  /* ── DROPDOWN PERKIRAAN (default akun pertama) ── */
  function loadPerkiraanDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('reportaccountingpiutangoutstandingJT_loadperkiraan') !!}",
      type: "get", async: false,
      success: function (res) { list = res || []; }
    });

    perkiraanList = list;

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
  }

  /* ── MODAL PIC ── */
  function buttonSelectCustomer() { loadSelectCustomer(); $("#formSelectCustomer").modal('toggle'); }
  function buttonPilihCustomer(kodePIC, namaPIC) {
    $("#inputCust").val(namaPIC); $("#formSelectCustomer").modal('hide');
    renderPickFields(); updateFilterBadge();
  }
  function loadSelectCustomer() {
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectCustomer')) { $('#tabelSelectCustomer').DataTable().destroy(); }
    $.ajax({
      url: "{!! url('reportaccountingpiutangoutstandingJT_loadcustomer') !!}",
      type: "get", async: false,
      success: function (res) { dataRefresh = res || []; }
    });
    let rowTable = "";
    dataRefresh.forEach((item) => {
      const kp = String(item.KodePIC).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
      const np = String(item.NamaPIC ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
      rowTable += `<tr class="pick-row" onclick="buttonPilihCustomer('${kp}','${np}')">
        <td>${esc(item.KodePIC)}</td>
        <td>${esc(item.NamaPIC)}</td>
      </tr>`;
    });
    document.getElementById("tabel_dataSelectCustomer").innerHTML = rowTable;
    $("#tabelSelectCustomer").DataTable({ "lengthChange": false, "paging": true, "order": [[1, "asc"]] });
  }

  /* ── MODAL LOKASI ── */
  function buttonSelectLokasi() { loadSelectLokasi(); $("#formSelectLokasi").modal('toggle'); }
  function buttonPilihLokasi(namaKebun) {
    $("#inputLokasi").val(namaKebun); $("#formSelectLokasi").modal('hide');
    renderPickFields(); updateFilterBadge();
  }
  function loadSelectLokasi() {
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectLokasi')) { $('#tabelSelectLokasi').DataTable().destroy(); }
    $.ajax({
      url: "{!! url('reportaccountingpiutangoutstandingJT_loadlokasi') !!}",
      type: "get", async: false,
      success: function (res) { dataRefresh = res || []; }
    });
    let rowTable = "";
    dataRefresh.forEach((item) => {
      const nk = String(item.namaKebun ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
      rowTable += `<tr class="pick-row" onclick="buttonPilihLokasi('${nk}')">
        <td>${esc(item.KodeKebun)}</td>
        <td>${esc(item.namaKebun)}</td>
      </tr>`;
    });
    document.getElementById("tabel_dataSelectLokasi").innerHTML = rowTable;
    $("#tabelSelectLokasi").DataTable({ "lengthChange": false, "paging": true });
  }

  /* ── MODAL SUPPLIER AWAL ── */
  function buttonSelectSuppAwal() { loadSelectSuppAwal(); $("#formSelectSuppAwal").modal('toggle'); }
  function buttonPilihSuppAwal(kode) {
    $("#inputSuppAwal").val(kode); $("#formSelectSuppAwal").modal('hide');
    renderPickFields(); updateFilterBadge();
  }
  function loadSelectSuppAwal() {
    // Baca dari #modalPerkiraan (pending, belum Terapkan), bukan #inputPerkiraan yang sudah
    // di-commit — picker dibuka dari dalam modal Filter sebelum Terapkan diklik.
    let perkiraan = $("#modalPerkiraan").length ? $("#modalPerkiraan").val() : $("#inputPerkiraan").val();
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAwal')) { $('#tabelSelectSuppAwal').DataTable().destroy(); }
    $.ajax({
      url: "{!! url('reportaccountingpiutangoutstandingJT_loadsuppawal') !!}",
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
    let perkiraan = $("#modalPerkiraan").length ? $("#modalPerkiraan").val() : $("#inputPerkiraan").val();
    let dataRefresh = [];
    if ($.fn.DataTable.isDataTable('#tabelSelectSuppAkhir')) { $('#tabelSelectSuppAkhir').DataTable().destroy(); }
    $.ajax({
      url: "{!! url('reportaccountingpiutangoutstandingJT_loadsuppawal') !!}",
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
</script>
@endsection
