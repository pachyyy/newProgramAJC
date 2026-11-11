@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Laporan SKB (buku tambahan SKB): styled .tb-report, daftar RATA baris ledger (No Bukti, Tanggal,
     Keterangan, Perkiraan, Lawan, Nilai). Filter: Periode (rentang tanggal). Klik di mana saja pada
     baris membuka voucher (CetakKasharian) di panel bawah — Jenis diambil dari No Bukti via
     jenisFromNo. Sumber: sp_ReportBukuTambahanSKB ?,?. Data hanya dimuat setelah klik "Tampilkan".
     Mode "Nomor Barang" lama dihapus (kolomnya tidak cocok dengan SP ini; Order By disembunyikan).

     Header interaktif (drag/gear + #rtBar), pola sama persis dengan reportaccountingbukubesar.blade.php
     (ledger RATA + klik-baris-buka-voucher yang sama), lihat docs/new-slider-table-guide.md.
     PERBAIKAN vs versi lama: doSetHeader(g_modeReport) sebelumnya TIDAK PERNAH dipanggil, sehingga
     gcart_header tidak pernah terisi dan kustomisasi kolom (sembunyikan/urutkan/desimal) tidak bisa
     tersimpan sama sekali. Sekarang dipanggil di $(document).ready() & makeTable() seperti halaman
     report lain. --}}
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
        <div class="page-title">Laporan SKB</div>
      </div> --}}

      <!-- Periode (rentang tanggal) -->
      <div class="filter-wrap">
        <label>Periode</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
        <span class="filter-sep">s/d</span>
        <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
      </div>

      <!-- Search -->
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
      </div>

      <!-- Actions: search + tampilkan + export -->
      <div class="action-group">
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

    <!-- TABLE (header + rows dirender dinamis dari gcart_header; klik baris membuka voucher) -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>Nomor Bukti</th></tr>
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
@endsection


@section('jsreport')
<script type="text/javascript">
  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalDate2 = "{!! date('Y-m-d') !!}";

  let g_reportTitle = "";
  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)

  // Report mode dipakai engine masterreport2 (doSetHeader) — cukup satu int.
  g_modeReport = 23;

  const reportUrl = "{{ url('laporanaccountingskb_doReport') }}";

  // Bottom voucher panel endpoints (report-table.js dimuat oleh masterreport2).
  // No Bukti buku tambahan → openVoucher(no, jenisFromNo(no)); Jenis diambil dari
  // nomor (segmen ke-2), lalu di-dispatch (Bukti Kas/Bank/Jurnal → CetakKasharian, dst).
  window.ReportTableConfig = {
    kasUrl    : "{{ url('laporanaccountingskb_doKasharian') }}",
    invoiceUrl: "{{ url('laporanaccountingskb_doInvoice') }}",
    lpbUrl    : "{{ url('laporanaccountingskb_doLpb') }}",
    bpUrl     : "{{ url('laporanaccountingskb_doBp') }}"
  };

  // Susunan kolom (mode "Nomor Bukti"). Kolom No Bukti dapat diklik (voucher).
  const COLS = [
    { key: 'NoBukti',    label: 'Nomor Bukti', type: 'str',  dec: 0, voucher: true },
    { key: 'Tanggal',    label: 'Tanggal',     type: 'date', dec: 0 },
    { key: 'Keterangan', label: 'Keterangan',  type: 'str',  dec: 0 },
    { key: 'Perkiraan',  label: 'Perkiraan',   type: 'str',  dec: 0 },
    { key: 'Lawan',      label: 'Lawan',       type: 'str',  dec: 0 },
    { key: 'Nilai',      label: 'Nilai',       type: 'num',  dec: 0 },
  ];

  $(document).ready(function () {
    doSetHeader(g_modeReport);   // muat susunan kolom (default / hasil kustomisasi user) tersimpan --
                                  // sebelumnya TIDAK PERNAH dipanggil, lihat catatan di atas file.

    // Header tabel interaktif: seret kolom, menu roda gigi (sembunyikan/desimal/total).
    // Tidak ada "Tampilan" switcher -- halaman ini cuma satu mode.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { if (lastRows.length) { applyFilters(); } else { render(); } }
    });

    // Sengaja TIDAK memuat data saat halaman dibuka — laporan hanya dimuat setelah
    // pengguna klik tombol "Tampilkan".
  });

  // gcart_header (dipakai render() untuk header interaktif + urutan/visibilitas kolom)
  // dibangun dari COLS. doSetHeader() memanggil ini bila belum ada header tersimpan.
  function setDefaultHeader() {
    gcart_header = COLS.map(c => [c.key, c.label, 1, (c.type === 'num' ? 'float' : c.type), 0, c.dec]);
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
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
    // Hanya kolom yang sedang terlihat (gcart_header[i][2]===1) yang ikut diekspor -- konsisten
    // dengan yang tampil di layar setelah kolom disembunyikan lewat gear.
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'SKB_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (sp_ReportBukuTambahanSKB; doReport mengembalikan array biasa) ── */
  function makeTable(_mode) {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
    g_reportTitle = 'REPORT SKB';

    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = { date1: globalDate1, date2: globalDate2 };

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

  // No Bukti clickable hanya untuk nomor voucher betulan (mengandung '/', bukan baris
  // "Saldo Awal"). Klik dilakukan pada SELURUH baris (lihat voucherRowOpen); Jenis
  // diambil dari nomor via jenisFromNo → openVoucher men-dispatch ke proc yang tepat.
  function isVoucherNo(v) {
    const s = str(v);
    if (!s || s.indexOf('/') === -1) return false;
    return s.toUpperCase().indexOf('SALDO AWAL') === -1;
  }

  // Kolom No Bukti hanya PETUNJUK visual (garis bawah biru); klik dilakukan pada
  // SELURUH baris karena hanya ada satu kolom yang bisa diklik.
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
    const esc = s.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    const jsc = String(jn).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    return '<tr class="' + cls + '" title="Klik untuk lihat ' + ttl + ' ' + s + '" ' +
           'onclick="openVoucher(\'' + esc + '\',\'' + jsc + '\')">';
  }

  /* ── RENDER: daftar RATA baris ledger (satu baris per transaksi). Kolom terlihat & urutan
     dari gcart_header (item[2]===1) supaya show/hide/seret lewat gear benar-benar berpengaruh;
     No Bukti dapat diklik (buka voucher lewat klik baris) — dicocokkan lewat nama field
     ('NoBukti'), bukan posisi, jadi tetap benar walau kolomnya digeser. ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
    // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    thead.innerHTML = ReportTable.headHtml(cols);

    const rows = (lastRows || []).filter(r => !search || rowSearchText(r, cols).indexOf(search) !== -1);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    let html = '';
    rows.forEach(r => {
      html += voucherRowOpen(pickCI(r, 'NoBukti'), 'data-row') + cols.map(function (c) {
        const v = pickCI(r, c[0]);
        if (c[0] === 'NoBukti') return voucherCell(v);
        if (c[3] === 'date') return '<td>' + format_date(v) + '</td>';
        if (c[3] === 'float' || c[3] === 'int') return '<td class="num">' + format_number(currencyNormalizer(v), c[5]) + '</td>';
        return '<td>' + nullToEmpty(v) + '</td>';
      }).join('') + '</tr>';
    });

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
  }

  /* ── PENCARIAN SISI-KLIEN ── */
  function applyFilters() { render(); }

  function rowSearchText(r, cols) {
    return cols.map(function (c) {
      const v = pickCI(r, c[0]);
      return (c[3] === 'date') ? format_date(v) : (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
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
</script>
@endsection
