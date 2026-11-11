@extends('report.masterreport2')

<style>
  .tb-report .table-wrap { min-height: 10vh; }
</style>

{{-- Tidak ada picker (Customer/Group/PIC/dst) di halaman ini: ReportEvaluasiSoLunas
     hanya menerima date1/date2, jadi @include modal select tidak diperlukan. --}}

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Evaluasi SO Lunas</div>
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

      <!-- Actions: search + customize + tampilkan + export -->
      <div class="action-group">
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

    <!-- TABLE -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>Nama Cust</th></tr>
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
  let lastRows = [];             // hasil fetch terakhir (dipakai render / export / search)
  let currentGroupby = 'NAMACUSTSUPP'; // groupby aktif (satu-satunya mode di halaman ini)

  const reportUrl = "{{ url('laporanmarketingevalsolunas_doReport') }}";

  // ReportEvaluasiSoLunas hanya menerima date1/date2 (verifikasi di controller) — tak ada
  // UI Report/Otorisasi/Agen/Order By di halaman ini, jadi g_modeReport tetap (tidak ada
  // mode lain yang bisa dipilih).
  g_modeReport = 0;

  // Panel voucher bawah (report-table.js sudah dimuat lewat masterreport2) — No. Invoice
  // adalah satu-satunya kolom nomor voucher di halaman ini, dispatch via jenisFromNo
  // (format standar ".../INVC/...") -> Sales Invoice (CetakInvoicePenjualan).
  window.ReportTableConfig = {
    kasUrl    : "{{ url('laporanmarketingevalsolunas_doKasharian') }}",
    invoiceUrl: "{{ url('laporanmarketingevalsolunas_doInvoice') }}",
    lpbUrl    : "{{ url('laporanmarketingevalsolunas_doLpb') }}",
    bpUrl     : "{{ url('laporanmarketingevalsolunas_doBp') }}"
  };

  // No. Invoice clickable hanya untuk nomor voucher betulan (ada '/', bukan '-'/kosong
  // atau baris "SALDO AWAL"). Satu-satunya kolom voucher di halaman ini -> seluruh
  // baris data yang bisa dibuka (bukan cuma sel), lebih mudah diklik.
  function isVoucherNo(v) {
    const s = str(v);
    if (!s || s.indexOf('/') === -1) return false;
    return s.toUpperCase().indexOf('SALDO AWAL') === -1;
  }
  function str(v) { return (v == null ? '' : String(v)).trim(); }
  function escapeJs(s) { return String(s == null ? '' : s).replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }

  // Sel No. Invoice: tanda visual saja (underline biru) — onclick ada di <tr>-nya
  // (voucherRowOpen), bukan di sel ini, karena ini satu-satunya kolom voucher.
  function voucherCell(v) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<td>' + nullToEmpty(v) + '</td>';
    return '<td class="kas-clickable" style="cursor:pointer;color:#0d6efd;text-decoration:underline">' + nullToEmpty(v) + '</td>';
  }

  // Tag pembuka <tr> baris data: bawa onclick="openVoucher(...)" bila No. Invoice-nya
  // nomor voucher betulan, supaya klik di mana saja pada baris membuka panel voucher.
  function voucherRowOpen(v, cls) {
    const s = str(v);
    if (!isVoucherNo(s)) return '<tr class="' + cls + '">';
    const jn = (typeof jenisFromNo === 'function') ? jenisFromNo(s) : '';
    const ttl = (typeof jenisTitle === 'function') ? jenisTitle(jn) : 'Voucher';
    const esc = escapeJs(s), jsc = escapeJs(jn);
    return '<tr class="' + cls + '" style="cursor:pointer" title="Klik untuk lihat ' + ttl + ' ' + s + '" ' +
           'onclick="openVoucher(\'' + esc + '\',\'' + jsc + '\')">';
  }

  $(document).ready(function() {
    showPeriode();
    setDefaultHeader();

    // Header tabel interaktif (drag urutan, gear sembunyikan/desimal/total, bar
    // kolom tersembunyi). Halaman ini tidak punya mode Detail/Rekap, jadi tidak
    // ada opsi "Tampilan".
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render
    });

  });

  // periode
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
  }

  function setDefaultHeader() {
    gcart_header = [
      ['NAMACUSTSUPP', 'Nama Cust', 1, 'varchar', 0, 0],
      ['TGLSO', 'Tgl. SO', 1, 'date', 0, 0],
      ['TGLAWALSPB', 'Awal SPB', 1, 'date', 0, 0],
      ['TGLAKHIRSPB', 'Akhir SPB', 1, 'date', 0, 0],
      ['NOINVOICE', 'No. Invoice', 1, 'varchar', 0, 0],
      ['TGLINV', 'Tgl. Invoice', 1, 'date', 0, 0],
      ['TGLLUNAS', 'Tgl. Lunas', 1, 'date', 0, 0],
      ['HARISPBINV', 'SPB-INV', 1, 'float', 1, 0],
      ['HARIINVLUNAS', 'Inv-Lunas', 1, 'float', 1, 0],
      ['HARISOLUNAS', 'SO-Lunas', 1, 'float', 1, 2],
      ['NILAIDPP', 'Total DPP', 1, 'float', 1, 2],
      ['NILAIHPP', 'Nilai HPP', 1, 'float', 1, 2],
      ['LABAKOTOR', 'Laba Kotor', 1, 'float', 1, 2],
      ['PERSENLABAKOTOR', '% Laba', 1, 'float', 1, 2],
      ['prsMrg', '% Margin', 1, 'float', 1, 0],
      ['TglKirim', 'Tgl. Kirim', 1, 'date', 0, 0],
      ['TglTerima', 'Tgl. Terima', 1, 'date', 0, 0],
    ];

    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

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
    ReportExport.fromRows(rows, fmt, { customName: 'EvaluasiSOLunas_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
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

  function makeTable(_mode) {
    let _date1 = $("#inputDate1").val();
    let _date2 = $("#inputDate2").val();

    setDefaultHeader();
    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    let data = {
      date1 : _date1,
      date2 : _date2,
    };

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl,
      type: 'get',
      data: data,
      success: function(res) {
        lastRows = res || [];
        $('#searchBox2').val('');
        render();
      },
      error: function() {
        lastRows = [];
        render();
      }
    });
  }

  // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
  // Dikelompokkan per Nama Cust (NAMACUSTSUPP) dengan Subtotal + Grand Total mengikuti
  // toggle Customize Table. Kolom dibangun dinamis dari gcart_header (item[2]===1).
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

    // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa
    // diseret untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
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
      const now = pickCI(r, currentGroupby);

      // subtotal saat nilai Nama Cust berganti (kalau toggle Subtotal aktif)
      if (showSub && i !== 0 && prev !== now) {
        html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
        keys.forEach(k => { sub[k] = 0; });
      }

      keys.forEach(function(k) {
        const v = currencyNormalizer(pickCI(r, k));
        sub[k] += v;
        grand[k] += v;
      });

      // satu sel per kolom terlihat, format menurut tipe (item[3]) & desimal (item[5]).
      // Baris dibuka via voucherRowOpen (klik di mana saja pada baris membuka voucher
      // No. Invoice-nya, bila ada) — satu-satunya kolom voucher di halaman ini.
      html += voucherRowOpen(pickCI(r, 'NOINVOICE'), 'data-row') + cols.map(function(c) {
        const key = c[0], type = c[3];
        if (String(key).toLowerCase() === 'noinvoice') return voucherCell(pickCI(r, key));
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

  // Baris total: nilai di kolom yang di-subtotal (item[4]===1), label di kolom pertama
  // non-total yang masih terlihat, sel lain dikosongkan.
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

  // Gabungan teks satu baris dari kolom terlihat (tanggal pakai format tampil
  // dd/mm/yyyy) supaya pencarian cocok dengan apa yang user lihat di tabel.
  function rowSearchText(r, cols) {
    return cols.map(function(c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

</script>

@endsection
