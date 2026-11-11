@extends('report.masterreport2')

  {{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
       Header dua tingkat (band Mutasi/Koreksi) dibangun manual per docs/new-slider-table-guide.md
       TANPA drag (ReportTable.headHtml() tidak bisa render rowspan/colspan) — tiap kolom tetap
       punya menu roda gigi (sembunyikan/desimal/total), lihat buildGroupedThead(). gcart_header
       (Detail mode) SENGAJA tetap menyertakan 2 baris penanda type:'group' ('' / Mutasi, '' /
       Koreksi) persis seperti sebelumnya — mengubah bentuknya akan membuat layout yang sudah
       tersimpan milik user lama (DBSIMPANHEADER) tidak nyambung index-nya lagi dengan kode baru
       ini. Mode Rekap (globalReportMode='1') tidak pernah dipakai — tidak ada kontrol UI apa pun
       yang mengubahnya dari default '0', jadi cabang itu dead code, dibiarkan apa adanya. --}}
<style>
  /* Tidak ada drag-reorder di halaman ini (header dua tingkat/grouped tidak bisa
     menoleransi kolom pindah band) -- timpa cursor:grab bawaan .th-inner supaya
     tidak menyiratkan kolom bisa diseret. Gear (hide/desimal/total) tetap aktif. */
  .tb-report .tb thead th.rt-th .th-inner { cursor: default; }
</style>

@include('report.modalAccountingJurnal')

@section('header2')
    <div class="tb-report main">
      <div class="content">

        <!-- TOOLBAR ROW 1 -->
        <div class="toolbar">
          {{-- <div>
            <div class="page-title">Report Trial Balance</div>
          </div> --}}

          <!-- Period selector (populated dynamically by populatePeriodSelectors) -->
          <div class="period-select-wrap">
            <label>Periode</label>
            <select class="period-select" id="periodBulan" onchange="changePeriodParts()"></select>
            <select class="period-select" id="periodTahun" onchange="changePeriodParts()"></select>
          </div>

          <!-- Search -->
          <input class="search-inp" type="text" placeholder="Cari akun..." oninput="applyFilters()">

          <!-- Export -->
          <div class="export-wrap" id="exportWrap">
            <button class="export-btn" onclick="toggleExport()"><i class="bi bi-arrow-down"></i> Export <i class="bi bi-caret-down-fill"></i></button>
            <div class="export-drop" id="exportDrop">
              <div class="export-opt" onclick="doExport('Excel')"><i class="bi bi-journals text-success"></i> Ekspor ke <span class="ext">XLSX</span></div>
              <div class="export-opt" onclick="doExport('PDF')"><i class="bi bi-file-earmark-pdf text-danger"></i> Ekspor ke <span class="ext">PDF</span></div>
              <div class="export-opt" onclick="doExport('CSV')"><i class="bi bi-clipboard"></i> Ekspor ke <span class="ext">CSV</span></div>
              <div class="export-opt" onclick="doExport('Print')"><i class="bi bi-printer-fill text-warning "></i> Cetak Laporan</div>
            </div>
          </div>
        </div>

        <!-- TOOLBAR ROW 2: type filters -->
        <div class="toolbar" style="margin-bottom:10px">
          <div class="type-filters">
            <button class="tf all on" onclick="setTypeFilter('all',this)">Semua</button>
            <button class="tf asset" onclick="setTypeFilter('asset',this)"><i class="bi bi-circle-fill text-primary"></i> Aset</button>
            <button class="tf liab" onclick="setTypeFilter('liab',this)"><i class="bi bi-circle-fill text-purple"></i> Kewajiban</button>
            <button class="tf equity" onclick="setTypeFilter('equity',this)"><i class="bi bi-circle-fill text-success"></i> Ekuitas</button>
            <button class="tf rev" onclick="setTypeFilter('rev',this)"><i class="bi bi-circle-fill text-teal"></i> Pendapatan</button>
            <button class="tf exp" onclick="setTypeFilter('exp',this)"><i class="bi bi-circle-fill text-warning"></i> Beban</button>
          </div>
          <span style="font-size:12px;color:var(--muted);margin-left:6px">
            <i class="bi bi-lightbulb-fill text-warning"></i> Klik baris untuk melihat detail transaksi
          </span>
        </div>

        <!-- KPI STRIP -->
        <div class="kpi-strip" id="kpiStrip"></div>

        <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
        <div id="rtBar"></div>

        <!-- TABLE — header dua tingkat (band Mutasi/Koreksi) dibangun oleh buildGroupedThead() -->
        <div class="table-outer">
          <div class="table-wrap">
            <table class="tb" id="mainTable">
              <thead>
                <tr>
                  <th rowspan="2" style="width:90px">Perkiraan</th>
                  <th rowspan="2">Keterangan</th>
                  <th rowspan="2" class="num" style="min-width:140px">Saldo Awal</th>
                  <th colspan="2" class="th-group" style="min-width:240px">Mutasi</th>
                  <th colspan="2" class="th-group" style="min-width:240px">Koreksi</th>
                  <th rowspan="2" class="num" style="min-width:140px">Saldo Akhir</th>
                </tr>
                <tr>
                  <th class="num" style="min-width:120px">Debet</th>
                  <th class="num" style="min-width:120px">Kredit</th>
                  <th class="num" style="min-width:120px">Debet</th>
                  <th class="num" style="min-width:120px">Kredit</th>
                </tr>
              </thead>
              <tbody id="tableBody"></tbody>
            </table>
          </div>
          <div class="table-footer">
            <span id="footerLabel">Menampilkan semua akun</span>
          </div>
        </div>

        <div class="rt-hint">
          <i class="bi bi-info-circle"></i>
          Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan kolom atau atur desimal &amp; total.
        </div>

      </div><!-- /content -->

    <!-- DRILL OVERLAY -->
  <div class="drill-overlay" id="drillOverlay" onclick="closeDrill()"></div>

  <!-- DRILL PANEL -->
  <div class="drill-panel" id="drillPanel">
    <div class="dp-header">
      <div>
        <div class="dp-title" id="dpTitle">-</div>
        <div class="dp-sub" id="dpSub">-</div>
      </div>
      <div class="dp-close" onclick="closeDrill()"><i class="bi bi-x"></i></div>
    </div>
    <div class="dp-meta" id="dpMeta"></div>
    <div class="dp-body" id="dpBody"></div>
  </div>

  <!-- TOAST -->
  <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>

  <!-- KAS HARIAN PANEL (slides up from the bottom, full width, 50vh) -->
  <div class="kas-panel" id="kasPanel">
    <div class="kas-head">
      <div class="kas-title" id="kasTitle">Bukti Kas</div>
      <div class="dp-close" onclick="closeKasharian()"><i class="bi bi-x"></i></div>
    </div>
    <div class="kas-body" id="kasBody"></div>
  </div>
  </div><!-- /tb-report -->


@endsection

@section('jsreport')

{{-- Shared number formatting (fmtN/fmtFull) + helpers from public/js/report-table.js --}}
<script src="{!! URL::asset('js/report-table.js') !!}?v={{ @filemtime(base_path('public/js/report-table.js')) ?: '1' }}"></script>

<script type="text/javascript">
  let globalReportMode = "0"; // default: Detail
  let globalOrderBy = "N"; // default: Detail
  let defaultBulan = new Date().getMonth() + 1;  // +1 because getMonth() returns 0-11
  let defaultTahun = new Date().getFullYear();

  // Endpoints for the styled "main table" (Sp_ReportMutasi) and its drill-down
  // ledger (Sp_ReportBukuTambahan) — same procs as Neraca Lajur.
  const reportUrl = "{{ url('laporanaccountingtrialbalance_doReport') }}";
  const ledgerUrl = "{{ url('laporanaccountingtrialbalance_doLedger') }}";

  // Voucher drill (Bukti Kas/Bank/Invoice) lives in public/js/report-table.js.
  // It reads its endpoints from this config; titles come from its default Jenis map.
  window.ReportTableConfig = {
    kasUrl    : "{{ url('laporanaccountingtrialbalance_doKasharian') }}",
    invoiceUrl: "{{ url('laporanaccountingtrialbalance_doInvoice') }}",
    lpbUrl    : "{{ url('laporanaccountingtrialbalance_doLpb') }}",
    bpUrl     : "{{ url('laporanaccountingtrialbalance_doBp') }}"
  };

  var jenisreport = 0; // ini untuk detail dan rekap

  $(document).ready(function() {
    // setReportMode() -> setModeReport() -> doSetHeader() already populates gcart_header
    // (saved layout, or setDefaultHeader() on first visit). NOTE: an explicit extra
    // setDefaultHeader() call used to sit right here — harmless before (render() never
    // read gcart_header), but it would have silently thrown away doSetHeader()'s loaded
    // layout on every single page load once the interactive header actually depends on
    // it, so it's removed rather than kept "for safety".
    setReportMode(globalReportMode);

    // The styled .tb-report table is the only table this report uses. Strip the
    // shared engine's old table markup from this page and keep it hidden (we
    // never call doMakeTable here). The shared masterreport4 template itself is
    // untouched, so other reports are unaffected.
    $("#showTableReport").empty().hide();

    populatePeriodSelectors();

    // Header tabel interaktif TANPA drag (lihat komentar di atas file): gear per kolom
    // untuk sembunyikan/desimal/total, + bar "Reset kolom"/kolom tersembunyi. Tidak ada
    // "Tampilan" switcher -- mode Rekap tidak pernah dipakai (lihat komentar di atas file).
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render
    });

    setTimeout(() => {
      makeTable('REPORT');
    }, 100);
  });

  function setReportMode(val) {
    globalReportMode = val;
    jenisreport = Number(val);   // 0 = Detail, 1 = Rekap
    DetOrRekap = Number(val);    // samakan dengan variabel yang ada di setModeReport

    // hapus centang dulu
    $('#dropdownReportMode .dropdown-item').each(function() {
      let itemText = $(this).text().replace(' ✔', '').trim();
      $(this).text(itemText);
    });

    // tambah centang di item terpilih
    $(`#dropdownReportMode .dropdown-item[data-value='${val}']`).each(function() {
      $(this).html(`${$(this).text()} <span class="checkmark-red">✔</span>`);
    });

    setModeReport();
  }

  var modereport_detailnobukti = 0, modereport_rekapnobukti = 1;
  g_modeReport = modereport_detailnobukti;

  function setDefaultHeader() {
    if (g_modeReport == modereport_detailnobukti) {
      gcart_header = [
        ['perkiraan', 'Perkiraan', 1, 'varchar', 0, 0, [1, 1, 2], false], // has data
        ['Keterangan', 'Keterangan', 1, 'varchar', 0, 0, [1, 1, 2], false], // has data
        ['SaldoAwal', 'Saldo Awal', 1, 'float', 1, 0, [1, 1, 2], false], // has data
        ['', 'Mutasi', 1, 'group', 0, 0, [1, 2, 1], true], // header only
        ['MD', 'Debet', 1, 'float', 1, 2, [2, 1, 1], false], // has data
        ['MK', 'Kredit', 1, 'float', 1, 2, [2, 1, 1], false], // has data
        ['', 'Koreksi', 1, 'group', 0, 0, [1, 2, 1], true], // header only
        ['JPD', 'Debet', 1, 'float', 1, 2, [2, 1, 1], false], // has data
        ['JPK', 'Kredit', 1, 'float', 1, 2, [2, 1, 1], false], // has data
        ['SaldoKAkhir', 'Saldo Akhir', 1, 'float', 1, 0, [1, 1, 2], false] // has data
      ];

      gsum_issubtotal = 1; gsum_isgrandtotal = 1;

    } else if (g_modeReport == modereport_rekapnobukti){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        ['NamaSls', 'Sales', 1, 'varchar', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NOPOCUstomer', 'No. PO. Customer', 1, 'varchar', 0, 0],
        ['NoSo', 'No. SO', 1, 'varchar', 0, 0],
        ['TanggalSO', 'Tanggal SO', 1, 'date', 0, 0],
        ['NDPPRPZX', 'Total', 1, 'float', 1, 0]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;
    }
  }

  function makeTable (_mode) {
    let inputBulan = defaultBulan;
    let inputTahun = defaultTahun;
    let divisi     = '-';

    currentPeriod = inputBulan + '/' + inputTahun;
    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url   : reportUrl,
      type  : 'get',
      data  : { inputBulan: inputBulan, inputTahun: inputTahun, divisi: divisi },
      success: function (res) {
        accountGroups = buildGroups(res || []);
        renderKpi();
        render();
      },
      error: function () {
        accountGroups = [];
        renderKpi();
        render();
        document.getElementById('footerLabel').textContent = 'Gagal memuat data laporan.';
      }
    });
  }

  function getKolomFilter() {
    let data = [];
    if ($("#inputOrder").val() == "N")
    {
      data = ['NoBukti', 'TanggalSO'];
    }

    return data;
  }

  function setModeReport () {
    if (globalOrderBy == "N") {
      if (jenisreport === 0) {
        g_modeReport = modereport_detailnobukti;
      } else {
        g_modeReport = modereport_rekapnobukti;
      }
    }

    doSetHeader(g_modeReport);
    doShowCustomize();
  }

  /* ── DATA (populated from Sp_ReportMutasi via makeTable) ── */
    let accountGroups = [];

    const GROUP_META = {
      asset:  { key: 'asset',  label: 'ASET',       cls: 'g-asset',  tcls: 't-asset',  color: '#1D4ED8' },
      liab:   { key: 'liab',   label: 'KEWAJIBAN',  cls: 'g-liab',   tcls: 't-liab',   color: '#7C3AED' },
      equity: { key: 'equity', label: 'EKUITAS',    cls: 'g-equity', tcls: 't-equity', color: '#0F766E' },
      rev:    { key: 'rev',    label: 'PENDAPATAN', cls: 'g-rev',    tcls: 't-rev',    color: '#15803D' },
      exp:    { key: 'exp',    label: 'BEBAN',      cls: 'g-exp',    tcls: 't-exp',    color: '#B45309' },
      other:  { key: 'other',  label: 'LAINNYA',    cls: '',         tcls: '',         color: '#5A6A85' }
    };
    const GROUP_ORDER = ['asset', 'liab', 'equity', 'rev', 'exp', 'other'];

    // Classify by the SP's Kelompok column (0=Aset, 1=Kewajiban, 2=Ekuitas,
    // 3=Pendapatan, 4=Beban). Compared as a trimmed string so the numeric 0 (falsy)
    // isn't dropped and it matches whether SQL Server returns it as int or string.
    function classify(kelompok) {
      switch (String(kelompok == null ? '' : kelompok).trim()) {
        case '0': return 'asset';
        case '1': return 'liab';
        case '2': return 'equity';
        case '3': return 'rev';
        case '4': return 'exp';
        default:  return 'other';
      }
    }

    function num(v) {
      if (v === null || v === undefined || v === '') return 0;
      const n = parseFloat(v);
      return isNaN(n) ? 0 : n;
    }

    // Read a property case-insensitively (the SP mixes cases).
    function pick(lc, key) {
      return lc[key.toLowerCase()];
    }

    // Map flat Sp_ReportMutasi rows into the grouped structure render() expects.
    // SP columns: Perkiraan, keterangan, SaldoAwal, MD, MK, JPD, JPK, SaldoSAldoAkhir
    // (trial balance has no Rugi/Laba pair, unlike Neraca Lajur).
    function buildGroups(rows) {
      const buckets = {};
      GROUP_ORDER.forEach(k => buckets[k] = []);

      rows.forEach(r => {
        // normalise keys to lowercase so casing differences don't matter
        const lc = {};
        Object.keys(r).forEach(k => { lc[k.toLowerCase()] = r[k]; });

        // Group by the SP's Kelompok column, but keep `code` = Perkiraan (account
        // number) — it drives the displayed code, the search box, and the drill.
        const code = (pick(lc, 'Perkiraan') != null ? pick(lc, 'Perkiraan') : '').toString().trim();
        buckets[classify(pick(lc, 'Kelompok'))].push({
          code: code,
          name: (pick(lc, 'keterangan') != null ? pick(lc, 'keterangan') : '').toString().trim(),
          SaldoAwal: num(pick(lc, 'SaldoAwal')),                       // Saldo Awal
          MD:  num(pick(lc, 'MD')),  MK:  num(pick(lc, 'MK')),  // Mutasi
          JPD: num(pick(lc, 'JPD')), JPK: num(pick(lc, 'JPK')), // Koreksi
          SAldoAkhir: num(pick(lc, 'SAldoAkhir'))                        // Saldo Akhir
        });
      });

      return GROUP_ORDER
        .filter(k => buckets[k].length)
        .map(k => Object.assign({}, GROUP_META[k], { accounts: buckets[k] }));
    }

    /* ── STATE ── */
    let typeFilter = 'all', searchStr = '', currentPeriod = '';

    /* ── FORMAT ── */
    // fmtN / fmtFull (full Rupiah, e.g. "Rp 3.500.000") live in
    // public/js/report-table.js so all report* pages share one formatter.
    function netAwal(a)  { return a.SaldoAwal; }
    function netAkhir(a) { return a.SAldoAkhir; }
    function isZero(a) {
      return a.SaldoAwal === 0 && a.MD === 0 && a.MK === 0 &&
             a.JPD === 0 && a.JPK === 0 && a.SAldoAkhir === 0;
    }

    /* ── KPI ── */
    function renderKpi() {
      const el = document.getElementById('kpiStrip');
      el.innerHTML = accountGroups.map(g => {
        // Signed sum of Saldo Akhir — identical to the group's subtotal row so the
        // KPI card and the table's "Subtotal <grup>" always agree (no Math.abs,
        // which double-counted contra-accounts of the opposite sign).
        const total = g.accounts.reduce((s, a) => s + netAkhir(a), 0);
        const count = g.accounts.length;
        return `<div class="kpi-card" style="border-left:3px solid ${g.color}">
      <div class="kpi-dot" style="background:${g.color}"></div>
      <div class="kpi-body">
        <div class="kpi-label">${g.label}</div>
        <div class="kpi-val" style="color:${g.color}">${fmtN(Math.abs(total))}</div>
        <div class="kpi-count">${count} akun</div>
      </div>
    </div>`;
      }).join('');
    }

  /* ── HEADER DUA TINGKAT (band Mutasi/Koreksi), TANPA drag ──
     ReportTable.headHtml() cuma bisa satu baris flat, jadi header dibangun manual di sini --
     tapi tiap kolom tetap dapat tombol roda gigi (data-rtgear) yang didengarkan oleh listener
     yang sama dipasang ReportTable.init() (event delegation di elemen <thead>, lihat
     docs/new-slider-table-guide.md). Kolom TIDAK bisa diseret (tidak ada .th-inner[draggable]/
     .th-grip di markup) supaya band Mutasi/Koreksi tidak pernah pecah. gcart_header (Detail
     mode) menandai band pakai baris type:'group' (field='', label='Mutasi'/'Koreksi') --
     baris itu sendiri TIDAK dapat gear (bukan kolom data sungguhan), band-nya mencakup semua
     baris non-group berikutnya sampai penanda group lain / akhir array. ── */
    function leafTh(idx, label, col, rowspan2) {
      const isNum = (col[3] === 'float' || col[3] === 'int');
      return '<th class="rt-th' + (isNum ? ' num' : '') + '"' + (rowspan2 ? ' rowspan="2"' : '') +
        ' data-gidx="' + idx + '">' +
        '<div class="th-inner">' +
        '<span class="th-label">' + label + '</span>' +
        '<button type="button" class="th-gear" data-rtgear="' + idx + '" title="Setting kolom"><i class="bi bi-gear"></i></button>' +
        '</div></th>';
    }

    function buildGroupedThead() {
      const all = gcart_header;
      let row1 = '', row2 = '';
      let i = 0;
      while (i < all.length) {
        const col = all[i];
        if (col[3] === 'group') {
          const label = col[1];
          let j = i + 1, count = 0;
          while (j < all.length && all[j][3] !== 'group') {
            if (Number(all[j][2]) === 1) { count++; }
            j++;
          }
          if (count > 0) {
            row1 += '<th colspan="' + count + '" class="th-group">' + label + '</th>';
            for (let k = i + 1; k < j; k++) {
              if (Number(all[k][2]) === 1) { row2 += leafTh(k, all[k][1], all[k], false); }
            }
          }
          i = j;
        } else {
          if (Number(col[2]) === 1) { row1 += leafTh(i, col[1], col, true); }
          i++;
        }
      }
      return '<tr>' + row1 + '</tr><tr>' + row2 + '</tr>';
    }

    // Baris total: nilai di tiap kolom numerik yang ditotal; label membentang seluruh kolom
    // non-total yang BERURUTAN mulai dari kolom non-total pertama (bukan cuma satu sel sempit)
    // supaya label yang panjang ("Subtotal PENDAPATAN") tidak wrap.
    function totalRow(label, sums, cols, totalKeys, cls) {
      const labelIdx = cols.findIndex(c => totalKeys.indexOf(c[0]) === -1);
      if (labelIdx === -1) {
        return '<tr class="' + cls + '">' + cols.map(c => '<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>').join('') + '</tr>';
      }
      let span = 1;
      while (labelIdx + span < cols.length && totalKeys.indexOf(cols[labelIdx + span][0]) === -1) { span++; }

      const tds = [];
      let idx = 0;
      while (idx < cols.length) {
        if (idx === labelIdx) {
          tds.push('<td colspan="' + span + '">' + label + '</td>');
          idx += span;
          continue;
        }
        const c = cols[idx];
        tds.push(totalKeys.indexOf(c[0]) !== -1 ? '<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>' : '<td></td>');
        idx++;
      }
      return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
    }

    // gcart_header field name -> nilai di object akun. Sebagian besar cocok langsung (SaldoAwal/
    // MD/MK/JPD/JPK), tapi 'perkiraan'/'Keterangan'/'SaldoKAkhir' TIDAK cocok dengan property
    // object akun (code/name/SAldoAkhir) -- perbedaan ejaan ini sudah ada sejak awal (lihat
    // buildGroups()) dan tidak pernah kelihatan karena render() dulu tidak pernah baca
    // gcart_header sama sekali. Alih-alih mengganti nama field di gcart_header (yang tidak akan
    // sampai ke layout tersimpan milik user lama, lihat catatan di atas file), cukup di-alias
    // di sini saja.
    function acctVal(a, field) {
      if (field === 'perkiraan') return a.code;
      if (field === 'Keterangan') return a.name;
      if (field === 'SaldoKAkhir') return a.SAldoAkhir;
      return a[field];
    }

    function esc(v) {
      return String(v == null ? '' : v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

  /* ── RENDER TABLE ──
     Kolom terlihat & urutan dari gcart_header (item[2]===1, kecuali baris penanda 'group')
     supaya show/hide lewat gear benar-benar berpengaruh; urutan itu sendiri tetap sama karena
     tidak ada drag di halaman ini. ── */
    function render() {
      const cols = gcart_header.filter(c => c[2] === 1 && c[3] !== 'group');
      const thead = document.querySelector('#mainTable thead');
      thead.innerHTML = buildGroupedThead();
      ReportTable.refresh();   // segarkan #rtBar (biasanya efek samping headHtml(), tapi di sini header dibangun manual)

      const tbody = document.getElementById('tableBody');
      let visibleCount = 0;

      const search = document.querySelector('.search-inp')?.value?.toLowerCase() || '';

      const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
      const totalKeys = totalCols.map(c => c[0]);

      const GROUP_DOT = {
        asset: '<i class="bi bi-circle-fill text-primary"></i>', liab: '<i class="bi bi-circle-fill text-purple"></i>',
        equity: '<i class="bi bi-circle-fill text-success"></i>', rev: '<i class="bi bi-circle-fill text-teal"></i>',
        exp: '<i class="bi bi-circle-fill text-warning"></i>'
      };

      let html = '';

      accountGroups.forEach(g => {
        if (typeFilter !== 'all' && typeFilter !== g.key) return;

        const filteredAccs = g.accounts.filter(a => {
          if (search && !a.code.includes(search) && !a.name.toLowerCase().includes(search)) return false;
          return true;
        });
        if (!filteredAccs.length) return;

        // group header row
        html += '<tr class="group-row ' + g.cls + '"><td colspan="' + cols.length + '">' +
          '<span style="margin-right:8px">' + (GROUP_DOT[g.key] || '') + '</span>' + g.label +
          ' <span style="font-size:11px;font-weight:600;opacity:.7;margin-left:8px">(' + filteredAccs.length + ' akun)</span></td></tr>';

        // running subtotals for the visible total columns
        const sub = {}; totalKeys.forEach(k => sub[k] = 0);

        filteredAccs.forEach(a => {
          const zero = isZero(a);
          totalKeys.forEach(k => { sub[k] += (acctVal(a, k) || 0); });

          const rowHtml = cols.map(function (c) {
            const key = c[0], type = c[3];
            const v = acctVal(a, key);
            if (key === 'perkiraan') { return '<td class="code">' + esc(v) + '</td>'; }
            if (key === 'Keterangan') { return '<td class="name">' + esc(v) + '<span class="drill-hint"><i class="bi bi-arrow-right-short"></i> detail</span></td>'; }
            if (type === 'float' || type === 'int') {
              const bold = (key === 'SaldoAwal' || key === 'SaldoKAkhir') ? ' style="font-weight:600"' : '';
              return '<td class="num"' + bold + '>' + format_number(v, c[5]) + '</td>';
            }
            return '<td>' + (v == null ? '' : esc(v)) + '</td>';
          }).join('');

          html += '<tr class="data-row ' + g.tcls + (zero ? ' zero-row' : '') + '" title="Klik untuk melihat detail transaksi ' +
            esc(a.code) + '" data-acc-code="' + esc(a.code) + '" data-acc-group="' + esc(g.key) + '">' + rowHtml + '</tr>';
          visibleCount++;
        });

        // subtotal
        html += totalRow('Subtotal ' + g.label, sub, cols, totalKeys, 'subtotal-row ' + g.cls);
      });

      // grand total
      if (typeFilter === 'all') {
        const allAccs = accountGroups.flatMap(g => g.accounts);
        const tot = {}; totalKeys.forEach(k => tot[k] = 0);
        allAccs.forEach(a => totalKeys.forEach(k => { tot[k] += (acctVal(a, k) || 0); }));
        html += totalRow('TOTAL KESELURUHAN', tot, cols, totalKeys, 'grand-total');
      }

      tbody.innerHTML = html;

      // footer
      document.getElementById('footerLabel').textContent = `Menampilkan ${visibleCount} akun`;
    }

    // Klik baris data untuk buka drill-down. Delegasi event (bukan onclick/closure per baris)
    // karena render() membangun tbody lewat innerHTML sekarang -- baris dicari lewat kode akun +
    // key grup (unik per baris) daripada nge-serialize seluruh objek akun ke sebuah atribut.
    //
    // WAJIB .attr(), BUKAN .data(): jQuery.data() mengonversi tipe nilai atribut -- string
    // yang bentuknya angka (kode Perkiraan SELALU angka, mis. "110101") dikembalikan sebagai
    // Number 110101. Padahal a.code disimpan sebagai string oleh buildGroups() (.toString()
    // .trim()), jadi find(x => x.code === code) tidak pernah ketemu dan handler diam-diam
    // keluar di `if (!a) return` -- panel tidak terbuka, tanpa error console maupun request.
    $(document).on('click', '#tableBody tr.data-row', function () {
      const code = $(this).attr('data-acc-code');
      const gkey = $(this).attr('data-acc-group');
      const g = accountGroups.find(x => x.key === gkey);
      if (!g) return;
      const a = g.accounts.find(x => x.code === code);
      if (!a) return;
      openDrill(a, g);
    });

    /* ── CONTROLS ── */
    function setTypeFilter(type, btn) {
      typeFilter = type;
      document.querySelectorAll('.tf').forEach(b => b.classList.remove('on'));
      if (btn) btn.classList.add('on');
      render();
    }
    function applyFilters() { render(); }

    /* ── PERIOD PICKER ── */
    const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // Build the Bulan (1–12) and Tahun (current year and 6 prior) dropdowns,
    // selecting the current defaultBulan/defaultTahun.
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

    function changePeriodParts() {
      defaultBulan = parseInt(document.getElementById('periodBulan').value, 10);
      defaultTahun = parseInt(document.getElementById('periodTahun').value, 10);
      makeTable('REPORT');
    }

    /* ── EXPORT ── */
    function toggleExport() {
      document.getElementById('exportDrop').classList.toggle('open');
    }
    document.addEventListener('click', function (e) {
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
    // Kolom terlihat + label yang diberi prefiks band (gcart_header sendiri cuma simpan
    // "Debet"/"Kredit" polos untuk kolom Mutasi & Koreksi -- band-nya cuma ada di baris
    // penanda 'group' -- jadi tanpa prefiks CSV-nya akan ambigu, dua kolom "Debet" berbeda
    // arti). Traversal-nya sama seperti buildGroupedThead() supaya kolom yang diekspor selalu
    // sinkron dengan yang tampil di layar.
    function visibleColsWithLabels() {
      const all = gcart_header;
      const out = [];
      let i = 0;
      while (i < all.length) {
        const col = all[i];
        if (col[3] === 'group') {
          const bandLabel = col[1];
          let j = i + 1;
          while (j < all.length && all[j][3] !== 'group') {
            if (Number(all[j][2]) === 1) { out.push({ col: all[j], label: bandLabel + ' ' + all[j][1] }); }
            j++;
          }
          i = j;
        } else {
          if (Number(col[2]) === 1) { out.push({ col: col, label: col[1] }); }
          i++;
        }
      }
      return out;
    }

    function exportDelimited(fmt) {
      const cols = visibleColsWithLabels();
      const rows = [cols.map(c => c.label)];
      accountGroups.forEach(g => {
        if (typeFilter !== 'all' && typeFilter !== g.key) return;
        g.accounts.forEach(a => {
          rows.push(cols.map(c => acctVal(a, c.col[0])));
        });
      });
      ReportExport.fromRows(rows, fmt, { customName: 'TrialBalance_' + currentPeriod.replace('/', '-') });
      showToast('📄', 'Data diekspor sebagai ' + fmt);
    }

    /* ── DRILL DOWN (real ledger via Sp_ReportBukuTambahan) ── */
    function openDrill(acc, group) {
      const awal = netAwal(acc);
      const sa = netAkhir(acc);
      document.getElementById('dpTitle').textContent = acc.name || acc.code;
      document.getElementById('dpSub').textContent = 'Kode: ' + acc.code + ' - ' + group.label;
      document.getElementById('dpMeta').innerHTML = `
    <div class="dp-meta-item"><span class="dp-meta-label">Saldo Awal</span><span class="dp-meta-val ${awal < 0 ? 'neg' : ''}">${fmtFull(awal)}</span></div>
    <div class="dp-meta-item"><span class="dp-meta-label">Mutasi Debet</span><span class="dp-meta-val" style="color:#1D4ED8">${fmtFull(acc.MD)}</span></div>
    <div class="dp-meta-item"><span class="dp-meta-label">Mutasi Kredit</span><span class="dp-meta-val" style="color:#7C3AED">${fmtFull(acc.MK)}</span></div>
    <div class="dp-meta-item"><span class="dp-meta-label">Saldo Akhir</span><span class="dp-meta-val ${sa < 0 ? 'neg' : ''}" style="font-size:16px">${fmtFull(sa)}</span></div>
  `;
      document.getElementById('dpBody').innerHTML = '<div class="dp-section-title">Memuat rincian transaksi...</div>';
      document.getElementById('drillOverlay').classList.add('open');
      document.getElementById('drillPanel').classList.add('open');

      $.ajax({
        url   : ledgerUrl,
        type  : 'get',
        data  : { perkiraan: acc.code, inputBulan: defaultBulan, inputTahun: defaultTahun, divisi: '-' },
        success: function (rows) { renderDrillBody(acc, rows || []); },
        error  : function () {
          document.getElementById('dpBody').innerHTML =
            '<div style="padding:12px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;color:#B91C1C;font-size:12.5px">Gagal memuat rincian transaksi.</div>';
        }
      });
    }

    function renderDrillBody(acc, rows) {
      const sa = netAkhir(acc);
      let running = netAwal(acc);
      let totD = 0, totK = 0;
      let body = `<tr>
      <td colspan="3" style="font-style:italic;color:var(--muted)">Saldo Awal</td>
      <td class="num">-</td><td class="num">-</td>
      <td class="num ${running < 0 ? 'neg' : ''}" style="font-weight:600">${fmtFull(running)}</td>
    </tr>`;

      rows.forEach(e0 => {
        // Sp_ReportBukuTambahan mixes column casing (Debet, kredit, Nobukti…),
        // so normalise keys to lowercase before reading them.
        const e = {};
        Object.keys(e0).forEach(k => { e[k.toLowerCase()] = e0[k]; });

        const d = num(e.debet), k = num(e.kredit);
        totD += d; totK += k;
        running += d - k;
        const tgl = (typeof format_date === 'function') ? format_date(e.tanggal) : (e.tanggal || '');
        const nb = (e.nobukti != null ? String(e.nobukti) : '');
        const jn = (e.jenis != null ? String(e.jenis).trim() : '');
        const nbJs = nb.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        const jnJs = jn.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        body += `<tr class="kas-clickable" title="Klik untuk lihat ${jenisTitle(jn)} ${nb}" onclick="openVoucher('${nbJs}','${jnJs}')">
      <td style="white-space:nowrap">${tgl}</td>
      <td><span class="ref-badge">${nb}</span></td>
      <td>${e.keterangan != null ? e.keterangan : ''}</td>
      <td class="num">${d ? fmtFull(d) : '-'}</td>
      <td class="num">${k ? fmtFull(k) : '-'}</td>
      <td class="num ${running < 0 ? 'neg' : ''}" style="font-weight:600">${fmtFull(running)}</td>
    </tr>`;
      });

      if (!rows.length) {
        body += `<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:14px">Tidak ada transaksi pada periode ini</td></tr>`;
      }

      document.getElementById('dpBody').innerHTML = `
    <div class="dp-section-title">Rincian Transaksi - Periode ${currentPeriod}</div>
    <table class="ledger-table">
      <thead>
        <tr>
          <th>Tanggal</th><th>No Bukti</th><th>Keterangan</th>
          <th class="num">Debet</th><th class="num">Kredit</th><th class="num">Saldo Berjalan</th>
        </tr>
      </thead>
      <tbody>${body}</tbody>
      <tfoot>
        <tr class="ledger-total">
          <td colspan="3" style="font-weight:800">Total Periode ${currentPeriod}</td>
          <td class="num">${fmtFull(totD)}</td>
          <td class="num">${fmtFull(totK)}</td>
          <td class="num ${sa < 0 ? 'neg' : ''}">${fmtFull(sa)}</td>
        </tr>
      </tfoot>
    </table>`;
    }

    function closeDrill() {
      document.getElementById('drillOverlay').classList.remove('open');
      document.getElementById('drillPanel').classList.remove('open');
    }

    /* ── TOAST ── */
    function showToast(icon, msg) {
      const t = document.getElementById('toast');
      document.getElementById('ti').textContent = icon;
      document.getElementById('tm').textContent = msg;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 3000);
    }


</script>

@endsection
