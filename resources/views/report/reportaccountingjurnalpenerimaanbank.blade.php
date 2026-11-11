@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Jurnal Penerimaan Bank (BBM): styled .tb-report dengan header dua tingkat (Debet → Perk./Jumlah,
     Kredit → Perk./Jumlah). Baris dikelompokkan per No. Bukti dengan Subtotal per voucher + Grand
     Total. Filter: Periode (rentang tanggal) + Divisi (DROPDOWN, sumber loadDivisi). Klik di mana saja
     pada baris membuka voucher Bukti Bank (CetakKasharian) di panel bawah (report-table.js). --}}
<style>
  .checkmark-red { color: red !important; font-weight: bold; margin-left: 6px; }

  #inputDivisiBtn {
    border: 0; background: none; padding: 0; box-shadow: none;
    color: #495057; font-weight: 600;
  }
  #inputDivisiBtn:hover, #inputDivisiBtn:focus { color: #0d6efd; box-shadow: none; }

  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      <div>
        <div class="page-title">Jurnal Penerimaan Bank</div>
        <div class="page-sub">Dicetak oleh: {{ $akses['user'] }} &nbsp;&middot;&nbsp; <span id="printTime"></span></div>
      </div>

      <!-- Periode (rentang tanggal) -->
      <div class="filter-wrap">
        <label>Periode</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
        <span class="filter-sep">s/d</span>
        <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
      </div>

      <!-- Divisi (DROPDOWN; sumber loadDivisi) -->
      <div class="filter-wrap">
        <label>Divisi</label>
        <input type="hidden" id="inputDivisi" value="-">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="inputDivisiBtn"
                data-bs-toggle="dropdown" aria-expanded="false"><span id="divisiLabel">-</span></button>
        <ul class="dropdown-menu" id="dropdownDivisi" aria-labelledby="inputDivisiBtn"
            style="max-height:320px; overflow:auto;"></ul>
      </div>

      <!-- Actions: search + tampilkan + export -->
      <div class="action-group">
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
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

    <!-- TABLE — header dua tingkat (Debet / Kredit), baris di-render dari makeTable() -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr>
              <th rowspan="2" style="min-width:90px">Tanggal</th>
              <th rowspan="2" style="min-width:130px">No. Bukti</th>
              <th rowspan="2">Keterangan</th>
              <th colspan="2" class="th-group">Debet</th>
              <th colspan="2" class="th-group">Kredit</th>
            </tr>
            <tr>
              <th style="min-width:90px">Perk.</th>
              <th class="num" style="min-width:120px">Jumlah</th>
              <th style="min-width:90px">Perk.</th>
              <th class="num" style="min-width:120px">Jumlah</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td colspan="7">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>
    </div>

  </div><!-- /content -->

  <!-- TOAST -->
  <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>

</div><!-- /tb-report -->
@endsection


@section('jsreport')
<script type="text/javascript">
  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalDate2 = "{!! date('Y-m-d') !!}";

  let g_reportTitle = "";
  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)

  // Report mode dipakai engine masterreport2 (doSetHeader) — cukup satu int.
  g_modeReport = 0;

  const reportUrl = "{{ url('laporanaccountingjurnalpenerimaanbank_doReport') }}";

  // Bottom voucher panel endpoints (report-table.js dimuat oleh masterreport2).
  // No. Bukti di jurnal ini adalah BBM (Bukti Bank Masuk) → openVoucher memakai
  // jenisFromNo() yang memetakan BBM ke CetakKasharian (kasUrl).
  window.ReportTableConfig = {
    kasUrl    : "{{ url('laporanaccountingjurnalpenerimaanbank_doKasharian') }}",
    invoiceUrl: "{{ url('laporanaccountingjurnalpenerimaanbank_doInvoice') }}",
    lpbUrl    : "{{ url('laporanaccountingjurnalpenerimaanbank_doLpb') }}",
    bpUrl     : "{{ url('laporanaccountingjurnalpenerimaanbank_doBp') }}"
  };

  $(document).ready(function () {
    document.getElementById('printTime').textContent = new Date().toLocaleString('id-ID');
    loadDivisiDropdown();   // isi dropdown Divisi (default: divisi pertama)

    setTimeout(() => { makeTable('REPORT'); }, 100);
  });

  // Header sederhana untuk menjaga engine masterreport2 tetap terinisialisasi
  // (doSetHeader memanggil ini bila belum ada header tersimpan). Tabel styled
  // di-render sendiri oleh render(), jadi gcart_header di sini tidak dipakai untuk layout.
  function setDefaultHeader() {
    gcart_header = [
      ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
      ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
      ['Keterangan', 'Keterangan', 1, 'varchar', 0, 0],
      ['Perkiraan', 'Perk. Debet', 1, 'varchar', 0, 0],
      ['Debet', 'Debet', 1, 'float', 1, 2],
      ['Lawan', 'Perk. Kredit', 1, 'varchar', 0, 0],
      ['Debet2', 'Kredit', 1, 'float', 1, 2],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  /* ── toolbar controls ── */
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
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
    const header = ['Tanggal', 'No. Bukti', 'Keterangan', 'Debet Perk.', 'Debet Jumlah', 'Kredit Perk.', 'Kredit Jumlah'];
    const body = (lastRows || []).map(r => [
      format_date(pickCI(r, 'Tanggal')),
      str(pickCI(r, 'NoBukti')),
      str(pickCI(r, 'Keterangan')),
      str(pickCI(r, 'Perkiraan')),
      currencyNormalizer(pickCI(r, 'Debet')),
      str(pickCI(r, 'Lawan')),
      currencyNormalizer(pickCI(r, 'Debet2'))
    ]);
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'JurnalPenerimaanBank_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (Sp_LapJurnal 'BBM', divisi, date1, date2; array biasa) ── */
  function makeTable(_mode) {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
    g_reportTitle = 'REPORT JURNAL PENERIMAAN BANK';

    let _divisi = $('#inputDivisi').val() || '-';

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = { date1: globalDate1, date2: globalDate2, divisi: _divisi };

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

  // No. Bukti clickable hanya untuk nomor voucher betulan (mengandung '/', bukan baris
  // "Saldo Awal"). Klik dilakukan pada SELURUH baris (lihat voucherRowOpen); Jenis (BBM)
  // diambil dari nomor via jenisFromNo → CetakKasharian.
  function isVoucherNo(v) {
    const s = str(v);
    if (!s || s.indexOf('/') === -1) return false;
    return s.toUpperCase().indexOf('SALDO AWAL') === -1;
  }

  // Kolom No. Bukti kini hanya PETUNJUK visual (garis bawah biru); klik dilakukan pada
  // SELURUH baris karena hanya ada satu kolom yang bisa diklik.
  function voucherCell(v) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<td>' + nullToEmpty(v) + '</td>';
    return '<td class="kas-clickable" style="color:#0d6efd;text-decoration:underline">' + nullToEmpty(v) + '</td>';
  }

  // Tag <tr> pembuka baris data: bila memuat nomor voucher betulan, SELURUH baris
  // bisa diklik untuk membuka voucher Bukti Bank (report-table.js).
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

  /* ── RENDER: baris jurnal dikelompokkan per No. Bukti. Tiap voucher ditutup baris
     Subtotal (jumlah Debet & Kredit voucher); di paling bawah Grand Total. Header dua
     tingkat sudah statis di markup; render() hanya mengisi <tbody>. ── */
  function render() {
    const tbody = document.getElementById('tableBody');
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    const rows = (lastRows || []).filter(r => !search || rowSearchText(r).indexOf(search) !== -1);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="7">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    // kelompokkan per No. Bukti, pertahankan urutan kemunculan
    const order = [], buckets = {};
    rows.forEach(r => {
      const gkey = str(pickCI(r, 'NoBukti'));
      if (!(gkey in buckets)) { buckets[gkey] = []; order.push(gkey); }
      buckets[gkey].push(r);
    });

    let html = '';
    let grandD = 0, grandK = 0, visible = 0;

    order.forEach(gkey => {
      const grp = buckets[gkey];
      let subD = 0, subK = 0;

      grp.forEach(r => {
        const d = currencyNormalizer(pickCI(r, 'Debet'));
        const k = currencyNormalizer(pickCI(r, 'Debet2'));
        subD += d; subK += k; grandD += d; grandK += k;

        html += voucherRowOpen(pickCI(r, 'NoBukti'), 'data-row') +
          '<td>' + format_date(pickCI(r, 'Tanggal')) + '</td>' +
          voucherCell(pickCI(r, 'NoBukti')) +
          '<td>' + nullToEmpty(pickCI(r, 'Keterangan')) + '</td>' +
          '<td>' + nullToEmpty(pickCI(r, 'Perkiraan')) + '</td>' +
          '<td class="num">' + format_number(d, 2) + '</td>' +
          '<td>' + nullToEmpty(pickCI(r, 'Lawan')) + '</td>' +
          '<td class="num">' + format_number(k, 2) + '</td>' +
        '</tr>';
        visible++;
      });

      // Subtotal per voucher
      html += '<tr class="subtotal-row">' +
        '<td colspan="4">Subtotal ' + (gkey || '') + '</td>' +
        '<td class="num">' + format_number(subD, 2) + '</td>' +
        '<td></td>' +
        '<td class="num">' + format_number(subK, 2) + '</td>' +
      '</tr>';
    });

    // Grand Total
    html += '<tr class="grand-total">' +
      '<td colspan="4">GRAND TOTAL</td>' +
      '<td class="num">' + format_number(grandD, 2) + '</td>' +
      '<td></td>' +
      '<td class="num">' + format_number(grandK, 2) + '</td>' +
    '</tr>';

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + visible + ' baris';
  }

  /* ── PENCARIAN SISI-KLIEN ── */
  function applyFilters() { render(); }

  function rowSearchText(r) {
    return [
      format_date(pickCI(r, 'Tanggal')),
      str(pickCI(r, 'NoBukti')),
      str(pickCI(r, 'Keterangan')),
      str(pickCI(r, 'Perkiraan')),
      str(pickCI(r, 'Debet')),
      str(pickCI(r, 'Lawan')),
      str(pickCI(r, 'Debet2'))
    ].join(' ').toLowerCase();
  }

  /* ── TOAST ── */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function getKolomFilter() { return ['NoBukti', 'Tanggal']; }

  /* ── DROPDOWN DIVISI (sumber loadDivisi; default: divisi pertama) ── */
  function loadDivisiDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('laporanaccountingjurnal_loaddivisi') !!}",
      type: "get", async: false,
      success: function (res) { list = res || []; }
    });

    let html = '';
    list.forEach((item) => {
      const nama = (item.NamaDevisi != null ? String(item.NamaDevisi) : '').replace(/"/g, '&quot;');
      html += '<li><a class="dropdown-item divisi-item" style="cursor:pointer" ' +
        'data-value="' + item.Devisi + '" data-nama="' + nama + '">' +
        item.Devisi + ' - ' + (item.NamaDevisi != null ? item.NamaDevisi : '') +
        ' <span class="checkmark-red" style="display:none">&#10003;</span></a></li>';
    });
    $("#dropdownDivisi").html(html);

    // default: divisi pertama (tanpa memuat ulang — ready sudah memanggil makeTable)
    if (list.length) { applyDivisi(list[0].Devisi, list[0].NamaDevisi != null ? list[0].NamaDevisi : ''); }
  }

  function applyDivisi(kode, nama) {
    $("#inputDivisi").val(kode);
    $("#divisiLabel").text(nama || kode);
    $("#inputDivisiBtn").attr('title', nama || kode);
    $('#dropdownDivisi .checkmark-red').hide();
    $(`#dropdownDivisi .divisi-item[data-value='${kode}'] .checkmark-red`).show();
  }

  function setDivisi(kode, nama) {
    applyDivisi(kode, nama);
    makeTable('REPORT');
  }

  $(document).on('click', '#dropdownDivisi .divisi-item', function () {
    setDivisi($(this).data('value'), $(this).data('nama'));
  });
</script>
@endsection
