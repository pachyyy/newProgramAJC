@extends('report.masterreportGudang')
{{-- @include('report.modalbrowsemaster') dihapus: halaman ini sekarang punya picker
     sendiri (.modal-picker / openPickMaster()) untuk Gudang & Merk, menggantikan popup
     shared #formBrowseMaster -- pola yang sama dipakai di reportstockmutasistock.blade.php. --}}

<style>
  /* Samakan dengan reportopname.blade.php: supaya area tabel tidak kempes jadi
     nyaris tak ada tinggi sebelum data pertama dimuat / saat kosong. */
  .tb-report .table-wrap {
    min-height: 10vh;
  }

  /* Popup "Pilih Data" (Gudang/Merk) dari dalam modal Filter Laporan.
     Sama seperti reportstockmutasistock.blade.php -- dibuat manual karena tidak
     ada style khusus untuk ini di report-table.css. */
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

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">

      <!-- Periode -->
      <div class="filter-wrap">
        <label>Periode</label>
        <select class="period-select" id="periodBulan" onchange="changePeriodParts()"></select>
        <select class="period-select" id="periodTahun" onchange="changePeriodParts()"></select>
        <input type="hidden" id="inputDate1" value="{!! date('Y-m') !!}">
      </div>

      <!-- Tampilan (Detail/Rekap) -->
      <div class="filter-wrap">
        <label>Tampilan</label>
        <select class="period-select" id="selectReportMode" onchange="setReportMode(this.value)">
          <option value="0" selected>Detail</option>
          <option value="1">Rekap</option>
        </select>
      </div>

      <!-- Search -->
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
      </div>

      <div class="action-group">
        <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
          <i class="fas fa-filter"></i> Filter
        </button>
        <button class="btn-load" type="button" onclick="doShowFormFilterData()" title="Filter Data">
          <i class="fas fa-magnifying-glass"></i> Filter Data
        </button>
        <button class="btn-load" type="button" onclick="makeTable('REPORT')" title="Tampilkan laporan">
          <i class="fas fa-check"></i> Tampilkan
        </button>
        <div class="export-wrap" id="exportWrap">
          <button class="export-btn" type="button" onclick="toggleExport()"><i class="bi bi-arrow-down"></i> Export <i class="bi bi-caret-down-fill"></i></button>
          <div class="export-drop" id="exportDrop">
            <div class="export-opt" onclick="doExport('Excel')"><i class="bi bi-journals text-success"></i> Ekspor ke <span class="ext">XLSX</span></div>
            <div class="export-opt" onclick="doExport('CSV')"><i class="bi bi-clipboard"></i> Ekspor ke <span class="ext">CSV</span></div>
            <div class="export-opt" onclick="doExport('Print')"><i class="bi bi-printer-fill text-warning"></i> Cetak Laporan</div>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI -->
    <div class="kpi-strip">
      <div class="kpi-card">
        <div class="kpi-dot" style="background:#1D4ED8;"></div>
        <div class="kpi-body">
          <div class="kpi-label">Total Item</div>
          <div class="kpi-val" id="kpiTotalItem">0</div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-dot" style="background:#15803D;"></div>
        <div class="kpi-body">
          <div class="kpi-label">Total Item Masuk</div>
          <div class="kpi-val" id="kpiTotalMasuk">0</div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-dot" style="background:#B45309;"></div>
        <div class="kpi-body">
          <div class="kpi-label">Total Item Keluar</div>
          <div class="kpi-val" id="kpiTotalKeluar">0</div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-dot" style="background:#7C3AED;"></div>
        <div class="kpi-body">
          <div class="kpi-label">Turn Over Stock</div>
          <div class="kpi-val" id="kpiTOS">0 %</div>
        </div>
      </div>
    </div>

    <!-- Bar kolom tersembunyi + Reset kolom (diisi report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <div class="table-outer">
      <div class="table-wrap" id="showTableReport">
        <table class="tb" id="tabel">
          <thead id="tabel_header"><tr><th>&nbsp;</th></tr></thead>
          <tbody id="tabel_data">
            <tr class="empty-row">
              <td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td>
            </tr>
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
        <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="rt-section">
          <div class="rt-group-label">Filter Data
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          <div class="rt-grid-2">
            <div>
              <label class="rt-field-label">Gudang</label>
              <div class="input-group input-group-sm">
                <input type="text" id="inputGudang" class="form-control" placeholder="-" value="-" readonly>
                <button type="button" class="btn btn-primary" onclick="openPickMaster('inputGudang', '{!! $gudang !!}', 'Pilih Gudang')"><i class="bi bi-search"></i></button>
              </div>
            </div>
            <div>
              <label class="rt-field-label">Merk</label>
              <div class="input-group input-group-sm">
                <input type="text" id="inputMerk" class="form-control" placeholder="-" value="-" readonly>
                <button type="button" class="btn btn-primary" onclick="openPickMaster('inputMerk', '{!! $merk !!}', 'Pilih Merk')"><i class="bi bi-search"></i></button>
              </div>
            </div>
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

<!-- modal pilih data master (Gudang/Merk) -->
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
<script type="text/javascript">
  var modereport_detail = 0, modereport_rekap = 1;
  g_modeReport = modereport_detail;

  let reportTitle = "LAPORAN STOK BULANAN QTY+RUPIAH";
  let lastRows = [];              // hasil fetch terakhir (dipakai render / search / export)
  let currentGroupby = 'pMERK';   // field yang perubahannya memicu band merk + subtotal (mode Detail)

  const reportUrl = "{{ url('laporanstockmutasistockpermerk_doReport') }}";

  // Jumlah kolom yang sedang tampil (untuk colspan baris band merk/ringkasan full-width).
  // Dihitung ulang tiap panggilan -- BUKAN konstanta -- supaya tetap benar setelah
  // kolom disembunyikan/ditampilkan lewat gear ReportTable.
  function getVisibleColCount() {
    return gcart_header.reduce((sum, c) => sum + (c[2] ? 1 : 0), 0);
  }

  // Jumlah kolom identitas (Kode/Nama/Part Number/Merk, atau Kode/Merk/Gdg di mode
  // Rekap) yang sedang tampil -- yaitu kolom sebelum "QntAwal" (kolom numerik pertama).
  // Dipakai untuk colspan sel label "Total Item" di buildMasukKeluarFooter().
  function getIdentityColspan() {
    let count = 0;
    for (const c of gcart_header) {
      if (c[0] === 'QntAwal') { break; }
      if (c[2]) { count++; }
    }
    return count || 1;
  }

  $(document).ready(function() {
    populatePeriodSelectors();

    ReportTable.init({
      table: '#tabel',
      bar: '#rtBar',
      onChange: render
    });
  });

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
  }

  // Ganti mode Detail/Rekap: muat ulang layout kolom untuk mode ini (tersimpan atau
  // default dari setDefaultHeader()). SP mengembalikan kolom yang berbeda per mode,
  // jadi data lama tidak dipakai lagi -- klik Tampilkan untuk mengambil ulang.
  function setReportMode(val) {
    g_modeReport = Number(val);
    doSetHeader(g_modeReport);
  }

  function setDefaultHeader() {
    if (g_modeReport == modereport_detail) {
      gcart_header = [
          ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
          ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
          ['partNumber', 'Part Number', 1, 'varchar', 0, 0],
          ['pMERK', 'Kode Merk', 0, 'varchar', 0, 0],
          ['pNAMAMERK', 'Nama Merk', 0, 'varchar', 0, 0],
          ['NAMAMERK', 'Merk', 1, 'varchar', 0, 0],
          ['QntAwal', 'So. Awal (Qty)', 1, 'float', 1, 2],
          ['HRGAWAL', 'So. Awal (Rp)', 1, 'float', 1, 0],
          ['QNTPBL', 'Pembelian (Qty)', 1, 'float', 1, 2],
          ['HRGPBL', 'Pembelian (Rp)', 1, 'float', 1, 0],
          ['QNTRPJ', 'Retur Jual (Qty)', 1, 'float', 1, 2],
          ['HRGRPJ', 'Retur Jual (Rp)', 1, 'float', 1, 0],
          ['QNTADI', 'Kor. Msk (Qty)', 1, 'float', 1, 2],
          ['HRGADI', 'Kor. Msk (Rp)', 1, 'float', 1, 0],
          ['QNTTRI', 'Trans. Msk (Qty)', 1, 'float', 1, 2],
          ['HRGTRI', 'Trans. Msk (Rp)', 1, 'float', 1, 0],
          ['QNTRPK', 'R. Pemakaian (Qty)', 1, 'float', 1, 2],
          ['HRGRPK', 'R. Pemakaian (Rp)', 1, 'float', 1, 0],
          ['QNTUKI', 'Ubah Kemasan In (Qty)', 1, 'float', 1, 2],
          ['HRGUKI', 'Ubah Kemasan In (Rp)', 1, 'float', 1, 0],
          ['qntrspb', 'Terima dr R.Sjln (Qty)', 1, 'float', 1, 2],
          ['hrgrspb', 'Terima dr R.Sjln (Rp)', 1, 'float', 1, 0],
          ['QntHPrd', 'Gd TC dr SJ (Qty)', 1, 'float', 1, 2],
          ['HRGHPrd', 'Gd TC dr SJ (Rp)', 1, 'float', 1, 0],
          ['QNTPNJ', 'S.Jalan (Qty)', 1, 'float', 1, 2],
          ['HRGPNJ', 'S.Jalan (Rp)', 1, 'float', 1, 0],
          ['qntrgtc', 'Retur Sjln dr GTC (Qty)', 1, 'float', 1, 2],
          ['hrgrgtc', 'Retur Sjln dr GTC (Rp)', 1, 'float', 1, 0],
          ['QNTPRJ', 'HPP (Qty)', 1, 'float', 1, 2],
          ['HRGPRJ', 'HPP (Rp)', 1, 'float', 1, 0],
          ['QNTRBP', 'Retur Beli (Qty)', 1, 'float', 1, 2],
          ['HRGRBP', 'Retur Beli (Rp)', 1, 'float', 1, 0],
          ['QNTADO', 'Kor. Klr (Qty)', 1, 'float', 1, 2],
          ['HRGADO', 'Kor. Klr (Rp)', 1, 'float', 1, 0],
          ['QNTTRO', 'Trans. Klr (Qty)', 1, 'float', 1, 2],
          ['HRGTRO', 'Trans. Klr (Rp)', 1, 'float', 1, 0],
          ['QNTUKO', 'Ubah Kemasan Out (Qty)', 1, 'float', 1, 2],
          ['HRGUKO', 'Ubah Kemasan Out (Rp)', 1, 'float', 1, 0],
          ['QNTPMK', 'Pemakaian (Qty)', 1, 'float', 1, 2],
          ['HRGPMK', 'Pemakaian (Rp)', 1, 'float', 1, 0],
          ['SALDOQNT', 'So. Akhir (Qty)', 1, 'float', 1, 2],
          ['SALDORP', 'So. Akhir (Rp)', 1, 'float', 1, 0]
      ];

      gsum_issubtotal = 1; gsum_isgrandtotal = 0;
    } else {
      gcart_header = [
          ['pMERK', 'Kode', 1, 'varchar', 0, 0],
          ['pNAMAMERK', 'Merk', 1, 'varchar', 0, 0],
          ['KodeGDG', 'Gdg', 1, 'varchar', 0, 0],
          ['QntAwal', 'So. Awal (Qty)', 1, 'float', 1, 2],
          ['HRGAWAL', 'So. Awal (Rp)', 1, 'float', 1, 0],
          ['QNTPBL', 'Pembelian (Qty)', 1, 'float', 1, 2],
          ['HRGPBL', 'Pembelian (Rp)', 1, 'float', 1, 0],
          ['QNTRPJ', 'Retur Jual (Qty)', 1, 'float', 1, 2],
          ['HRGRPJ', 'Retur Jual (Rp)', 1, 'float', 1, 0],
          ['QNTADI', 'Kor. Msk (Qty)', 1, 'float', 1, 2],
          ['HRGADI', 'Kor. Msk (Rp)', 1, 'float', 1, 0],
          ['QNTTRI', 'Trans. Msk (Qty)', 1, 'float', 1, 2],
          ['HRGTRI', 'Trans. Msk (Rp)', 1, 'float', 1, 0],
          ['QNTRPK', 'R. Pemakaian (Qty)', 1, 'float', 1, 2],
          ['HRGRPK', 'R. Pemakaian (Rp)', 1, 'float', 1, 0],
          ['QNTUKI', 'Ubah Kemasan In (Qty)', 1, 'float', 1, 2],
          ['HRGUKI', 'Ubah Kemasan In (Rp)', 1, 'float', 1, 0],
          ['qntrspb', 'Terima dr R.Sjln (Qty)', 1, 'float', 1, 2],
          ['hrgrspb', 'Terima dr R.Sjln (Rp)', 1, 'float', 1, 0],
          ['QntHPrd', 'Gd TC dr SJ (Qty)', 1, 'float', 1, 2],
          ['HRGHPrd', 'Gd TC dr SJ (Rp)', 1, 'float', 1, 0],
          ['QNTPNJ', 'S.Jalan (Qty)', 1, 'float', 1, 2],
          ['HRGPNJ', 'S.Jalan (Rp)', 1, 'float', 1, 0],
          ['qntrgtc', 'Retur Sjln dr GTC (Qty)', 1, 'float', 1, 2],
          ['hrgrgtc', 'Retur Sjln dr GTC (Rp)', 1, 'float', 1, 0],
          ['QNTPRJ', 'HPP (Qty)', 1, 'float', 1, 2],
          ['HRGPRJ', 'HPP (Rp)', 1, 'float', 1, 0],
          ['QNTRBP', 'Retur Beli (Qty)', 1, 'float', 1, 2],
          ['HRGRBP', 'Retur Beli (Rp)', 1, 'float', 1, 0],
          ['QNTADO', 'Kor. Klr (Qty)', 1, 'float', 1, 2],
          ['HRGADO', 'Kor. Klr (Rp)', 1, 'float', 1, 0],
          ['QNTTRO', 'Trans. Klr (Qty)', 1, 'float', 1, 2],
          ['HRGTRO', 'Trans. Klr (Rp)', 1, 'float', 1, 0],
          ['QNTUKO', 'Ubah Kemasan Out (Qty)', 1, 'float', 1, 2],
          ['HRGUKO', 'Ubah Kemasan Out (Rp)', 1, 'float', 1, 0],
          ['QNTPMK', 'Pemakaian (Qty)', 1, 'float', 1, 2],
          ['HRGPMK', 'Pemakaian (Rp)', 1, 'float', 1, 0],
          ['SALDOQNT', 'So. Akhir (Qty)', 1, 'float', 1, 2],
          ['SALDORP', 'So. Akhir (Rp)', 1, 'float', 1, 0]
      ];

      gsum_issubtotal = 0; gsum_isgrandtotal = 1;
    }
  }

  // ==================== RENDER (pola docs/report-table-guide.md, sama seperti
  // reportopname.blade.php) ====================
  // Kolom dibangun DINAMIS dari gcart_header (hanya yang terlihat, sesuai urutan
  // tersimpan). Header lewat ReportTable.headHtml() supaya drag & gear tetap jalan;
  // cols HARUS dari gcart_header.filter(...), bukan .map()/copy, supaya headHtml()
  // bisa memetakan tiap kolom balik ke index globalnya (drag & gear diam-diam tidak
  // berfungsi kalau di-copy).
  function render() {
    const cols = gcart_header.filter(c => c[2] === 1);
    const keys = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1).map(c => c[0]);
    const thead = document.querySelector('#tabel thead');
    const tbody = document.getElementById('tabel_data');

    // Band merk + subtotal per grup: mode Detail saja (gsum_issubtotal). Grand total
    // tunggal: mode Rekap saja (gsum_isgrandtotal). Lihat komentar di setDefaultHeader().
    const showGroupBand = (gsum_issubtotal === 1);
    const showSub   = keys.length > 0 && showGroupBand;
    const showGrand = keys.length > 0 && (gsum_isgrandtotal === 1);

    thead.innerHTML = ReportTable.headHtml(cols);

    const term = ($('#searchBox2').val() || '').trim().toLowerCase();
    const rows = !term ? (lastRows || []) : (lastRows || []).filter(r => rowSearchText(r, cols).indexOf(term) !== -1);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      // KPI mewakili SELURUH data (lastRows), bukan hasil pencarian -- kalau ini
      // cuma pencarian yang tidak match apa-apa, biarkan KPI apa adanya. Hanya
      // nolkan kalau memang belum/tidak ada data sama sekali.
      if (!lastRows.length) { try { updateKpiWidgets(0, 0, 0, 0); } catch (e) {} }
      return;
    }

    let html = '', prev = null, sub = {}, grand = {};
    keys.forEach(k => { sub[k] = 0; grand[k] = 0; });

    rows.forEach(function (r, i) {
      const now = r[currentGroupby];

      if (showGroupBand && (i === 0 || prev !== now)) {
        if (showSub && i !== 0) {
          html += totalRow('Subtotal', sub, cols, keys, 'subtotal-row');
          keys.forEach(k => sub[k] = 0);
        }
        html += groupBandRow(r);
      }

      keys.forEach(function (k) {
        const v = currencyNormalizer(r[k]);
        sub[k] += v; grand[k] += v;
      });

      html += '<tr class="data-row">' + cols.map(function (c) {
        const key = c[0], type = c[3];
        if (type === 'date') return '<td>' + format_date(r[key]) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(r[key]), c[5]) + '</td>';
        return '<td>' + nullToEmpty(r[key]) + '</td>';
      }).join('') + '</tr>';

      prev = now;
    });

    if (showSub)   html += totalRow('Subtotal', sub, cols, keys, 'subtotal-row');
    if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, keys, 'grand-total');

    // Ringkasan Total Item / Item Masuk / Item Keluar / Turn Over Stock -- khusus mode
    // Detail (Rekap tidak menghitung ini, lihat setDefaultHeader). Dihitung dari
    // SELURUH data yang dimuat (lastRows), bukan hasil pencarian, supaya angka ini
    // tetap mewakili satu bulan penuh walau user sedang mengetik di kotak cari.
    if (showGroupBand) { html += buildMasukKeluarFooter(lastRows); }
    else { try { updateKpiWidgets(lastRows.length, 0, 0, 0); } catch (e) {} }

    tbody.innerHTML = html;

    const footerMsg = term
      ? 'Menampilkan ' + rows.length + ' dari ' + lastRows.length + ' baris'
      : 'Menampilkan ' + rows.length + ' baris';
    document.getElementById('footerLabel').textContent = footerMsg;
  }

  // Band judul grup Merk (banner gelap) sebelum baris pertama tiap merk -- hanya mode
  // Detail (Rekap sudah menampilkan Merk sebagai kolom biasa, satu baris per merk).
  // Pakai class "group-row" (padding/uppercase-nya report-table.css) tapi warnanya
  // di-override manual karena grup "Merk" bukan salah satu kategori g-asset/g-liab/dst.
  function groupBandRow(item) {
    const label = nullToEmpty(item['pMERK']) + ' : ' + nullToEmpty(item['pNAMAMERK']);
    return '<tr class="group-row" style="">' +
           '<td colspan="' + getVisibleColCount() + '">' + label + '</td></tr>';
  }

  // Baris total: nilai di kolom yang di-subtotal ([4]===1), label di kolom identitas
  // pertama yang masih terlihat, sel lain dikosongkan. Sama seperti
  // docs/report-table-guide.md / reportopname.blade.php.
  function totalRow(label, sums, cols, keys, cls) {
    const labelIdx = cols.findIndex(c => keys.indexOf(c[0]) === -1);
    const tds = cols.map(function (c, idx) {
      if (keys.indexOf(c[0]) !== -1) return '<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>';
      if (idx === labelIdx) return '<td>' + label + '</td>';
      return '<td></td>';
    });
    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  function rowSearchText(r, cols) {
    return cols.map(function (c) {
      const v = r[c[0]];
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

  function applyFilters() { if (lastRows.length) { render(); } }

  function updateKpiWidgets(totalItem, totMasuk, totKeluar, tosPct) {
    $('#kpiTotalItem').text(format_number(totalItem, 0));
    $('#kpiTotalMasuk').text(format_number(totMasuk, 0));
    $('#kpiTotalKeluar').text(format_number(totKeluar, 0));
    $('#kpiTOS').text(tosPct + ' %');
  }

  // Kembalikan '' (bukan sel kosong) kalau kolomnya sedang disembunyikan lewat gear,
  // supaya jumlah <td> di baris ringkasan tetap sama dengan jumlah <th> yang tampil.
  function isColVisible(_col) {
    const row = gcart_header.find(r => r[0] === _col);
    return !row || row[2] === 1;
  }

  function getRowFooter1(_rows, _col) {
    if (!isColVisible(_col)) { return ''; }

    const sum = _rows.reduce((s, item) => s + currencyNormalizer(item[_col]), 0);
    const decimal = (gcart_header.find(row => row[0] === _col) || [])[5];

    return '<td class="num">' + format_number(sum, decimal) + '</td>';
  }

  // Catatan: sel ini punya colspan="2" tetap (mewakili pasangan Qty+Rp). Kalau user
  // menyembunyikan salah satu kolom pasangan itu saja (bukan keduanya), baris ini
  // akan sedikit tidak sejajar -- kasus tepi yang jarang terjadi dan sengaja tidak
  // ditangani penuh di sini. "sum" tetap dihitung dari seluruh data (dipakai juga
  // oleh KPI), terlepas dari status tampil/sembunyi.
  function getRowFooter2(_rows, _col) {
    const sum = _rows.filter(item => currencyNormalizer(item[_col]) !== 0).length;
    const str = isColVisible(_col) ? '<td colspan="2" class="num">' + sum + '</td>' : '';
    return { sum, str };
  }

  // Ringkasan Total Item / Item Masuk / Item Keluar / Turn Over Stock, dihitung dari
  // SELURUH data satu bulan (_rows = lastRows). Isinya sama seperti sebelumnya, hanya
  // dipindah ke dalam render() dan dipakaikan kelas report-table.css (grand-total)
  // alih-alih border hitam manual.
  function buildMasukKeluarFooter(_rows) {
    let rowFooter1 = '<tr class="grand-total">';
    rowFooter1 += '<td colspan="' + getIdentityColspan() + '">Total Item</td>';

    let rowFooter2 = '<tr class="grand-total">';
    rowFooter2 += '<td colspan="' + getIdentityColspan() + '"></td>';

    let tempFooter2;
    let tot_masuk = 0, tot_keluar = 0, tos = 0;

    // So. Awal
    rowFooter1 += getRowFooter1(_rows, "QntAwal");
    rowFooter1 += getRowFooter1(_rows, "HRGAWAL");
    tempFooter2 = getRowFooter2(_rows, "QntAwal");
    rowFooter2 += tempFooter2.str;
    tot_masuk  += tempFooter2.sum;

    // Pembelian
    rowFooter1 += getRowFooter1(_rows, "QNTPBL");
    rowFooter1 += getRowFooter1(_rows, "HRGPBL");
    tempFooter2 = getRowFooter2(_rows, "QNTPBL");
    rowFooter2 += tempFooter2.str;
    tot_masuk  += tempFooter2.sum;

    // Retur Jual
    rowFooter1 += getRowFooter1(_rows, "QNTRPJ");
    rowFooter1 += getRowFooter1(_rows, "HRGRPJ");
    tempFooter2 = getRowFooter2(_rows, "QNTRPJ");
    rowFooter2 += tempFooter2.str;
    tot_masuk  += tempFooter2.sum;

    // Kor. Msk
    rowFooter1 += getRowFooter1(_rows, "QNTADI");
    rowFooter1 += getRowFooter1(_rows, "HRGADI");
    tempFooter2 = getRowFooter2(_rows, "QNTADI");
    rowFooter2 += tempFooter2.str;
    tot_masuk  += tempFooter2.sum;

    // Trans. Msk
    rowFooter1 += getRowFooter1(_rows, "QNTTRI");
    rowFooter1 += getRowFooter1(_rows, "HRGTRI");
    tempFooter2 = getRowFooter2(_rows, "QNTTRI");
    rowFooter2 += tempFooter2.str;
    tot_masuk  += tempFooter2.sum;

    // R. Pemakaian
    rowFooter1 += getRowFooter1(_rows, "QNTRPK");
    rowFooter1 += getRowFooter1(_rows, "HRGRPK");
    tempFooter2 = getRowFooter2(_rows, "QNTRPK");
    rowFooter2 += tempFooter2.str;
    tot_masuk  += tempFooter2.sum;

    // Ubah Kemasan In
    rowFooter1 += getRowFooter1(_rows, "QNTUKI");
    rowFooter1 += getRowFooter1(_rows, "HRGUKI");
    tempFooter2 = getRowFooter2(_rows, "QNTUKI");
    rowFooter2 += tempFooter2.str;
    tot_masuk  += tempFooter2.sum;

    // Terima dr R.Sjln
    rowFooter1 += getRowFooter1(_rows, "qntrspb");
    rowFooter1 += getRowFooter1(_rows, "hrgrspb");
    tempFooter2 = getRowFooter2(_rows, "qntrspb");
    rowFooter2 += tempFooter2.str;
    // tot_masuk  += tempFooter2.sum;

    // Gd TC dr SJ
    rowFooter1 += getRowFooter1(_rows, "QntHPrd");
    rowFooter1 += getRowFooter1(_rows, "HRGHPrd");
    tempFooter2 = getRowFooter2(_rows, "QntHPrd");
    rowFooter2 += tempFooter2.str;
    tot_masuk  += tempFooter2.sum;

    // S.Jalan
    rowFooter1 += getRowFooter1(_rows, "QNTPNJ");
    rowFooter1 += getRowFooter1(_rows, "HRGPNJ");
    tempFooter2 = getRowFooter2(_rows, "QNTPNJ");
    rowFooter2 += tempFooter2.str;
    tot_keluar  += tempFooter2.sum;

    // Retur Sjln dr GTC
    rowFooter1 += getRowFooter1(_rows, "qntrgtc");
    rowFooter1 += getRowFooter1(_rows, "hrgrgtc");
    tempFooter2 = getRowFooter2(_rows, "qntrgtc");
    rowFooter2 += tempFooter2.str;
    tot_keluar  += tempFooter2.sum;

    // HPP
    rowFooter1 += getRowFooter1(_rows, "QNTPRJ");
    rowFooter1 += getRowFooter1(_rows, "HRGPRJ");
    tempFooter2 = getRowFooter2(_rows, "QNTPRJ");
    rowFooter2 += tempFooter2.str;
    // tot_keluar  += tempFooter2.sum;

    // Retur Beli
    rowFooter1 += getRowFooter1(_rows, "QNTRBP");
    rowFooter1 += getRowFooter1(_rows, "HRGRBP");
    tempFooter2 = getRowFooter2(_rows, "QNTRBP");
    rowFooter2 += tempFooter2.str;
    // tot_keluar  += tempFooter2.sum;

    // Kor. Klr
    rowFooter1 += getRowFooter1(_rows, "QNTADO");
    rowFooter1 += getRowFooter1(_rows, "HRGADO");
    tempFooter2 = getRowFooter2(_rows, "QNTADO");
    rowFooter2 += tempFooter2.str;
    tot_keluar  += tempFooter2.sum;

    // Trans. Klr
    rowFooter1 += getRowFooter1(_rows, "QNTTRO");
    rowFooter1 += getRowFooter1(_rows, "HRGTRO");
    tempFooter2 = getRowFooter2(_rows, "QNTTRO");
    rowFooter2 += tempFooter2.str;
    tot_keluar  += tempFooter2.sum;

    // Ubah Kemasan Out
    rowFooter1 += getRowFooter1(_rows, "QNTUKO");
    rowFooter1 += getRowFooter1(_rows, "HRGUKO");
    tempFooter2 = getRowFooter2(_rows, "QNTUKO");
    rowFooter2 += tempFooter2.str;
    tot_keluar  += tempFooter2.sum;

    // Pemakaian
    rowFooter1 += getRowFooter1(_rows, "QNTPMK");
    rowFooter1 += getRowFooter1(_rows, "HRGPMK");
    tempFooter2 = getRowFooter2(_rows, "QNTPMK");
    rowFooter2 += tempFooter2.str;
    tot_keluar  += tempFooter2.sum;

    // So. Akhir
    rowFooter1 += getRowFooter1(_rows, "SALDOQNT");
    rowFooter1 += getRowFooter1(_rows, "SALDORP");
    tempFooter2 = getRowFooter2(_rows, "SALDOQNT");
    rowFooter2 += tempFooter2.str;
    // tot_keluar  += tempFooter2.sum;

    rowFooter1 += '</tr>';
    rowFooter2 += '</tr>';

    const infoStyle = 'style="border:none !important;color:var(--muted);font-size:12.5px;font-style:italic;"';
    const visCols = getVisibleColCount();

    let totMasukKPI = tot_masuk;
    tot_masuk = (tot_masuk !== 0) ? tot_masuk : 1;
    tos = format_number((tot_keluar / tot_masuk) * 100, 2);

    let rowInfo = '';
    rowInfo += '<tr><td colspan="' + visCols + '" ' + infoStyle + '>Item masuk : ' + tot_masuk + '</td></tr>';
    rowInfo += '<tr><td colspan="' + visCols + '" ' + infoStyle + '>Item keluar : ' + tot_keluar + '</td></tr>';
    rowInfo += '<tr><td colspan="' + visCols + '" ' + infoStyle + '>Turn over stock : ' + tos + ' %</td></tr>';

    try { updateKpiWidgets(_rows.length, totMasukKPI, tot_keluar, tos); } catch (e) {}

    return rowFooter1 + rowFooter2 + rowInfo;
  }

  function makeTable(_mode, _callback) {
    // nilai groupby adalah nama kolom (sesuai database) untuk pengelompokan subtotal
    // mode report menentukan kolom yang dipakai
    let groupby = "pMERK";
    let _date1  = $("#inputDate1").val();
    let _date2  = $("#inputDate2").val();

    let data = {
      date1         : _date1,
      date2         : _date2,
      inputGudang   : $("#inputGudang").val(),
      inputMerk     : $("#inputMerk").val(),
      inputTampil   : g_modeReport,
    };

    if (_mode !== 'REPORT') {
      // Mode "FILTER": delegasikan ke engine masterreportGudang supaya modal
      // "Filter Data" (doShowFormFilterData/doShowFilter, gcart_filter) tetap jalan --
      // render() di atas tidak menggantikan jalur itu. doMakeTable() sudah async (lihat
      // catatan di masterreportGudang.blade.php), jadi teruskan _callback apa adanya.
      doMakeTable(_mode, groupby, data, reportTitle, _date1, null, _callback);
      return;
    }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl,
      type: 'get',
      data: data,
      success: function (res) {
        lastRows = res || [];
        render();
        alertify.success("Report ditampilkan");
      },
      error: function (xhr) {
        console.error('laporanstockmutasistockpermerk_doReport gagal:', xhr.status, xhr.responseText);
        lastRows = [];
        render();
      }
    });
  }

  function getKolomFilter() {
    // tentukan kolom (sesuai database & gcart_header) yang mau ditampilkan
    // mode report menentukan kolom yang dipakai
    // berapa pun bisa asal dalam bentuk array

    let data = [];
    data = ['pMERK', 'pNAMAMERK'];

    return data;
  }

  // ---- Filter Laporan (Gudang / Merk) ----
  const PICK_FIELD_IDS = ['inputGudang', 'inputMerk'];

  function updateFilterBadge() {
    let count = 0;
    PICK_FIELD_IDS.forEach(function (id) {
      const val = $('#' + id).val();
      if (val && val !== '-') { count++; }
    });
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    PICK_FIELD_IDS.forEach(function (id) { $('#' + id).val('-'); });
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function () { updateFilterBadge(); });

  // ---- modal "Pilih Data" (Gudang/Merk), menggantikan popup shared
  //      #formBrowseMaster (search + Submit) dengan Actions berisi tombol "+" per baris. ----
  let pickerTargetInput = "";

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
    updateFilterBadge();
  }

  // ---- Export ----
  function toggleExport() { document.getElementById('exportDrop').classList.toggle('open'); }
  document.addEventListener('click', function (e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) { document.getElementById('exportDrop').classList.remove('open'); }
  });

  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }
    if (!lastRows.length) { return; }

    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = lastRows.map(r => cols.map(function (c) {
      if (c[3] === 'date') return format_date(r[c[0]]);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(r[c[0]]);
      return (r[c[0]] == null ? '' : r[c[0]]);
    }));
    const rowsCsv = [header].concat(body);
    ReportExport.fromRows(rowsCsv, fmt, { customName: 'LaporanMutasiStokPerMerk_' + ($('#inputDate1').val() || '') });
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
</script>

@endsection
