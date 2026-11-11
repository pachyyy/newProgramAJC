@extends('report.masterreport2')

<style>
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Report Marketing - Sales Analisa</div>
      </div> --}}

      {{-- Bulan/Tahun (bukan rentang tanggal seperti laporan lain -- proc Sp_ReportAnalisaSales
           menerima bulan & tahun, bukan tgl1/tgl2). Dropdown Bulan/Tahun, bukan input teks bebas --
           pola sama seperti reportaccountingneracalajur.blade.php (populatePeriodSelectors()).
           BEDA dari halaman itu: di sini ganti dropdown TIDAK auto-reload -- cuma menyetel
           defaultBulan/defaultTahun, tetap perlu klik "Tampilkan" (perilaku lama dipertahankan,
           cuma gaya input yang diganti). --}}
      <div class="period-select-wrap">
        <label>Periode</label>
        <select class="period-select" id="periodBulan" onchange="changePeriodParts()"></select>
        <select class="period-select" id="periodTahun" onchange="changePeriodParts()"></select>
      </div>

      {{-- Search --}}
      <div>
          <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
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

    <!-- TABLE -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>NIK</th></tr>
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

@endsection

@section('jsreport')

<script type="text/javascript">
  let lastRows = [];        // hasil fetch terakhir (dipakai render / export / search)
  let currentGroupby = 'Nobukti'; // satu-satunya groupby yang ada di halaman ini (casing
                                   // mengikuti field asli di gcart_header, bukan 'NoBukti')

  let defaultBulan = new Date().getMonth() + 1;  // +1 karena getMonth() balikin 0-11
  let defaultTahun = new Date().getFullYear();

  const reportUrl = "{{ url('laporanmarketingsalesanalisa_doReport') }}";

  // Satu-satunya mode yang PERNAH bisa dicapai: Detail/NoBukti. Halaman versi lama punya 14
  // varian gcart_header (Report Mode x 6 Order By), tapi toolbar-nya sendiri TIDAK PUNYA
  // kontrol Report Mode maupun Order By -- hanya Bulan/Tahun. jenisreport & globalOrderBy jadi
  // tidak pernah bisa berubah dari default (0 / "N"), jadi 13 varian lainnya (plus Otorisasi/
  // Agen yang juga tidak py kontrol di UI) adalah dead code -- dihapus, bukan dipertahankan.
  g_modeReport = 0;

  $(document).ready(function() {
    setDefaultHeader();
    doSetHeader(g_modeReport);
    doShowCustomize();

    populatePeriodSelectors();

    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render
    });
  });

  /* -- PERIOD PICKER -- */
  const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

  // Isi dropdown Bulan (1-12) & Tahun (tahun berjalan dan 6 tahun ke belakang), pilih
  // defaultBulan/defaultTahun saat ini. Pola sama seperti reportaccountingneracalajur.blade.php.
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
  }

  // BEDA dari reportaccountingneracalajur.blade.php: di sana ganti dropdown langsung
  // makeTable('REPORT'). Di sini cuma menyimpan pilihan -- user tetap klik "Tampilkan" untuk
  // memuat ulang, sama seperti perilaku input Bulan/Tahun lama (tidak ada auto-fetch).
  function changePeriodParts() {
    defaultBulan = parseInt(document.getElementById('periodBulan').value, 10);
    defaultTahun = parseInt(document.getElementById('periodTahun').value, 10);
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
    ReportExport.fromRows(rows, fmt, { customName: 'SalesAnalisa_' + defaultBulan + '_' + defaultTahun });
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

  function setDefaultHeader() {
    gcart_header = [
      ['NIK', 'NIK', 1, 'varchar', 0, 0],
      ['Nama', 'Nama', 1, 'varchar', 0, 0],
      ['KodeCustSupp', 'Kode Supplier', 1, 'varchar', 0, 0],
      ['KODECUST', 'Kode Customer', 1, 'varchar', 0, 0],
      ['Nobukti', 'No Bukti', 1, 'varchar', 0, 0],
      ['tanggal', 'Tanggal', 1, 'date', 0, 0],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  function makeTable(_mode) {
    let bulan = defaultBulan;
    let tahun = defaultTahun;

    setDefaultHeader();
    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    // doReport() di controller membaca 'date1'/'date2' sebagai bulan & tahun (nama parameter
    // warisan). Sebelumnya payload dikirim dengan key 'bulan'/'tahun' yang TIDAK PERNAH dibaca
    // controller -- bulan/tahun yang diisi user tidak pernah sampai ke query, selalu string
    // kosong. Diperbaiki: kirim di bawah key yang sungguh-sungguh dibaca.
    let data = {
      date1: bulan,
      date2: tahun,
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
