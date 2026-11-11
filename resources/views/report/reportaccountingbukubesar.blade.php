@extends('report.masterreport2')

{{-- All .tb-report table + toolbar styling lives in public/css/report-table.css (loaded globally via newmaster2).
     This page is self-contained: it carries its own #formSelect modal (below) and loads Perkiraan from
     its own endpoint (laporanaccountingbukubesar_loadperkiraan), so it does NOT include modalAccountingJurnal. --}}

@section('header2')
    <!-- Modal -->
    <div class="modal fade" id="formSelect" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"
                        onclick="$('#formSelect').modal('hide')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="tabelSelect" class="table table-bordered table-striped">
                        <thead class="text-center" id="tabelHeader">
                            <tr>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody id="tabel_dataSelect" class="text-left">
                            <tr>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal"
                        onclick="$('#formSelect').modal('hide')">Batal</button>
                </div>
            </div>
        </div>
    </div>
    <div class="tb-report main">
        <div class="content">

            <!-- TOOLBAR -->
            <div class="toolbar">
                {{-- <div>
                    <div class="page-title">Buku Tambahan</div>
                </div> --}}

                <!-- Periode (date range) -->
                <div class="filter-wrap">
                    <label>Periode</label>
                    <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
                    <span class="filter-sep">s/d</span>
                    <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
                </div>

                {{-- Divisi & Perkiraan (awal/akhir) pindah ke modal "Filter Laporan" -- lihat
                     docs/new-filter-modal-ui-guide.md. Nilai sebenarnya: globalDivisi (var JS) +
                     #inputPerkiraan1 / #inputPerkiraan2 (hidden input di dalam modal). --}}
                <!-- Actions: second (row-level) search + load + export -->
                <div class="action-group">
                    <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..."
                        oninput="applyFilters()" style="width:180px">
                    {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5) --
                         lihat catatan di modal Filter di bawah. --}}
                    <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button class="btn-load" onclick="makeTable('REPORT')" title="Tampilkan laporan"><i
                            class="fas fa-check"></i> Tampilkan</button>
                    <div class="export-wrap" id="exportWrap">
                        <button class="export-btn" onclick="toggleExport()"><i class="bi bi-arrow-down"></i> Export <i
                                class="bi bi-caret-down-fill"></i></button>
                        <div class="export-drop" id="exportDrop">
                            <div class="export-opt" onclick="doExport('Excel')"><i
                                    class="bi bi-journals text-success"></i>
                                Ekspor ke <span class="ext">XLSX</span></div>
                            <div class="export-opt" onclick="doExport('CSV')"><i class="bi bi-clipboard"></i> Ekspor ke
                                <span class="ext">CSV</span>
                            </div>
                            <div class="export-opt" onclick="doExport('Print')"><i
                                    class="bi bi-printer-fill text-warning"></i> Cetak Laporan</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
            <div id="rtBar"></div>

            <!-- TABLE (header + rows rendered dynamically from gcart_header) -->
            <div class="table-outer">
                <div class="table-wrap">
                    <table class="tb" id="mainTable">
                        <thead>
                            <tr><th>Tanggal</th></tr>
                        </thead>
                        <tbody id="tableBody">
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
                Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan kolom atau atur desimal &amp; total.
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
                        <i class="fas fa-filter"></i> Filter Laporan
                        <span class="rt-active-badge" id="filterBadge">0 aktif</span>
                    </h5>
                    {{-- data-dismiss (BS4) = yang benar-benar menutup, karena modal ini dibuka lewat
                         $.fn.modal milik BS4 (jQuery baru dimuat SESUDAH bundle BS5 di masterreport2).
                         data-bs-dismiss dibiarkan untuk jaga-jaga. --}}
                    <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                            onclick="$('#modalFilter').modal('hide')"></button>
                </div>

                <div class="modal-body">

                    <div class="rt-section">
                        <div class="rt-group-label">Pengaturan Laporan</div>
                        <div class="rt-grid-1">
                            <div>
                                <label class="rt-field-label" for="modalDivisi">Divisi</label>
                                {{-- Diisi dari laporanaccountingbukubesar_loaddivisi (loadDivisiDropdown()).
                                     Selalu punya nilai (tidak ada opsi "Semua") -- pilihan wajib, bukan
                                     filter yang bisa dimatikan, jadi TIDAK dihitung di badge (lihat
                                     updateFilterBadge()), sama seperti pola Perkiraan di
                                     reportaccountingkasharian. --}}
                                <select class="rt-native" id="modalDivisi"></select>
                            </div>
                        </div>
                    </div>

                    <div class="rt-section">
                        <div class="rt-group-label">Perkiraan
                            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
                        </div>
                        <div class="rt-grid-2" id="pickFields"></div>

                        {{-- Nilai sebenarnya (dibaca makeTable() & ditulis buttonPilihPerkiraan()) --}}
                        <input type="hidden" id="inputPerkiraan1" value="-">
                        <input type="hidden" id="inputPerkiraan2" value="-">
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
    {{-- Reusable bottom voucher panel (Bukti Kas/Bank + Invoice). Reads window.ReportTableConfig
     below; report-table.css is already loaded globally via report/newmaster2.blade.php. --}}
    <script>
        window.ReportTableConfig = {
            kasUrl: "{{ url('laporanaccountingbukubesar_doKasharian') }}",
            invoiceUrl: "{{ url('laporanaccountingbukubesar_doInvoice') }}",
            // Voucher titles per Jenis. BBK/BBM/BKK/BKM/BMM/BJK/INVC are already covered by
            // report-table.js. Unmapped jenis (TDP/BPL/UMJ/R/L/OPN/RPB/BP/KRS/…) fall back to
            // "Bukti Kas". Add overrides here later, e.g.  TDP: 'BUKTI TITIPAN',
            jenisTitle: {
                // KODE: 'JUDUL VOUCHER',
            }
        };
    </script>
    <script src="{!! URL::asset('js/report-table.js') !!}?v={{ @filemtime(base_path('public/js/report-table.js')) ?: '1' }}"></script>

    <script type="text/javascript">
        // Endpoint for the styled table (Sp_ReportBukuTambahan over the perkiraan range)
        const reportUrl = "{{ url('laporanaccountingbukubesar_doReport') }}";

        // Raw rows from the last successful load — kept so search / export don't refetch.
        let lastRows = [];

        let globalDivisi = "-";  // diisi loadDivisiDropdown() saat page load (selalu wajib diisi)

        // Report satu mode saja (buku besar tidak punya switcher Detail/Rekap).
        g_modeReport = 0;

        /* ── kolom (gcart_header). Tabel styled DI-RENDER dari sini (lihat renderLedger()),
              jadi hasil drag/gear (show-hide + urutan kolom) langsung ikut tampil. Saldo
              adalah saldo BERJALAN (running balance), bukan angka yang dijumlah — makanya
              total-nya default MATI (item[4]=0); Debet & Kredit dijumlah (item[4]=1) untuk
              subtotal per akun & grand total. ── */
        function setDefaultHeader() {
            gcart_header = [
                ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                ['Nobukti', 'No. Bukti', 1, 'varchar', 0, 0],
                ['Keterangan', 'Keterangan', 1, 'varchar', 0, 0],
                ['Lawan', 'Lawan', 1, 'varchar', 0, 0],
                ['Debet', 'Debet', 1, 'float', 1, 2],
                ['kredit', 'Kredit', 1, 'float', 1, 2],
                ['SaldoAkhir', 'Saldo', 1, 'float', 0, 2]
            ];
            gsum_issubtotal = 1;
            gsum_isgrandtotal = 1;
        }

        $(document).ready(function() {
            // This report renders its own styled table; strip the shared engine's table markup.
            $("#showTableReport").empty().hide();

            var pt = document.getElementById('printTime');
            if (pt) pt.textContent = new Date().toLocaleString('id-ID');

            loadDivisiDropdown(); // isi dropdown Divisi (default: divisi pertama)

            doSetHeader(g_modeReport);   // muat susunan kolom (default / hasil kustomisasi user) + gsum flags

            // Header tabel interaktif: seret kolom, menu roda gigi (sembunyikan/desimal/total).
            // Tidak ada "Tampilan" switcher -- halaman ini cuma satu mode.
            ReportTable.init({
                table: '#mainTable',
                bar: '#rtBar',
                onChange: function () {
                    if (lastRows.length) { applyFilters(); } else { renderLedger(); }
                }
            });

            // NO auto-load: the table loads only when the "Tampilkan" (check) button is clicked.
        });

        // Setiap baris transaksi membuka voucher sumbernya di panel bawah (report-table.js):
        // INVC -> Invoice, B* -> Bukti Kas/Bank, sesuai kolom Jenis baris tsb. Delegasi event
        // (bukan onclick inline per baris) karena renderLedger() membangun tbody lewat innerHTML.
        $(document).on('click', '#tableBody tr.kas-clickable', function () {
            openVoucher($(this).data('nobukti'), $(this).data('jenis'));
        });

        /* ── SELECT DIVISI (modal Filter Laporan) ──
              Diisi sekali dari laporanaccountingbukubesar_loaddivisi saat page load. Memilih
              item hanya menyetel globalDivisi; laporan baru dimuat saat klik Tampilkan
              (konsisten dgn filter Periode/Perkiraan). ── */
        function loadDivisiDropdown() {
            let list = [];
            $.ajax({
                url: "{!! url('laporanaccountingbukubesar_loaddivisi') !!}",
                type: "get",
                async: false,
                success: function(res) {
                    list = res || [];
                }
            });

            let html = '';
            list.forEach((item) => {
                const nama = (item.NamaDevisi != null ? String(item.NamaDevisi) : '');
                html += '<option value="' + item.Devisi + '">' + item.Devisi + ' - ' + esc(nama) + '</option>';
            });
            $("#modalDivisi").html(html);

            // default: divisi pertama (tidak ada opsi "Semua")
            if (list.length) { setDivisi(list[0].Devisi); }
        }

        function setDivisi(kode) {
            globalDivisi = kode;
            $("#modalDivisi").val(kode);
        }

        /* ── FILTER MODAL ── */
        const PICK_FIELDS = [
            { id: 'inputPerkiraan1', label: 'Perkiraan Awal',  target: 1 },
            { id: 'inputPerkiraan2', label: 'Perkiraan Akhir', target: 2 },
        ];

        function renderPickFields() {
            let html = '';
            PICK_FIELDS.forEach(function (f) {
                const val = $('#' + f.id).val() || '-';
                const isSet = (val !== '-' && val !== '');
                html += '<div>';
                html += '<label class="rt-field-label">' + f.label + '</label>';
                html += '<div class="rt-combo">';
                html += '<div class="rt-combo-input" onclick="pickFromModal(' + f.target + ')">';
                if (isSet) {
                    html += '<span class="rt-combo-tag">' + esc(val) +
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

        // Perkiraan Awal/Akhir: '-' = tidak dibatasi -> punya nilai netral, jadi DIHITUNG di
        // badge saat diisi. Divisi TIDAK ada opsi "Semua" -> wajib, jadi TIDAK dihitung.
        function updateFilterBadge() {
            let count = 0;
            PICK_FIELDS.forEach(function (f) {
                const val = $('#' + f.id).val();
                if (val && val !== '-') { count++; }
            });
            $('#filterBadge').text(count + ' aktif');
        }

        function resetAllFilters() {
            if ($('#modalDivisi option').length) {
                $('#modalDivisi').prop('selectedIndex', 0);
            }
            PICK_FIELDS.forEach(function (f) { $('#' + f.id).val('-'); });
            renderPickFields();
            updateFilterBadge();
        }

        $('#modalFilter').on('show.bs.modal', function () {
            $('#modalDivisi').val(globalDivisi);
            renderPickFields();
            updateFilterBadge();
        });

        $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

        function applyModalFilter() {
            setDivisi($('#modalDivisi').val());
            $('#modalFilter').modal('hide');
        }

        // Buka picker Perkiraan dari dalam modal Filter: BS4/BS5 tidak menumpuk modal dengan
        // bersih, jadi Filter disembunyikan dulu & dibuka lagi setelah picker ditutup.
        let g_reopenFilter = false;

        function pickFromModal(target) {
            g_reopenFilter = true;
            $('#modalFilter').modal('hide');
            loadSelectPerkiraan(target);   // buka #formSelect
        }

        $(document).on('hidden.bs.modal', '#formSelect', function () {
            if (g_reopenFilter) {
                g_reopenFilter = false;
                $('#modalFilter').modal('show');
                renderPickFields();
                updateFilterBadge();
            }
        });

        /* ── HELPERS ── */
        // Sp_ReportBukuTambahan mixes column casing (Debet, kredit, Nobukti…); read lowercased.
        function lc(r) {
            const o = {};
            Object.keys(r).forEach(k => {
                o[k.toLowerCase()] = r[k];
            });
            return o;
        }

        function num(v) {
            if (v === null || v === undefined || v === '') return 0;
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        // Debet/Kredit cell value ('-' when zero). `col` = gcart_header entry, dipakai untuk
        // desimal (item[5]) supaya stepper desimal di menu roda gigi benar-benar berpengaruh.
        function money(v, col) {
            if (!v) return '-';
            const dec = col ? Number(col[5]) : 2;
            return (v < 0 ? '-' : '') + 'Rp ' + Math.abs(v).toLocaleString('id-ID', {
                minimumFractionDigits: dec, maximumFractionDigits: dec
            });
        }
        // Running saldo always shows a value (incl. 0)
        function saldo(v, col) {
            const dec = col ? Number(col[5]) : 2;
            return (v < 0 ? '-' : '') + 'Rp ' + Math.abs(v).toLocaleString('id-ID', {
                minimumFractionDigits: dec, maximumFractionDigits: dec
            });
        }

        // HTML-escape teks bebas (Keterangan/Lawan/No. Bukti/nama akun bisa diisi user).
        function esc(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Satu <tr> dibangun dari `cols` (gcart_header terlihat, urutan sesuai drag user).
        // `map` dikunci oleh nama field HURUF KECIL -> { text, style, cls, neg }; kolom yang
        // tidak ada di map dirender kosong (dipakai baris Subtotal/Grand Total untuk kolom Saldo).
        function cellsFor(cols, map) {
            return cols.map(function (c) {
                const key = String(c[0]).toLowerCase();
                const cell = map[key] || {};
                const classes = [];
                if (c[3] === 'float' || c[3] === 'int') classes.push('num');
                if (cell.cls) classes.push(cell.cls);
                if (cell.neg) classes.push('neg');
                const classAttr = classes.length ? ' class="' + classes.join(' ') + '"' : '';
                const styleAttr = cell.style ? ' style="' + cell.style + '"' : '';
                return '<td' + classAttr + styleAttr + '>' + (cell.text !== undefined ? cell.text : '') + '</td>';
            }).join('');
        }

        function setFooter(msg) {
            const el = document.getElementById('footerLabel');
            if (el) el.textContent = msg;
        }

        function isOpening(r) {
            return String(r.nobukti || '').trim().toUpperCase() === 'SALDO AWAL';
        }

        // Group rows by account (NoACC), preserving the order the proc returned them in.
        function buildGroups(rows) {
            const groups = [],
                idx = {};
            rows.forEach(r0 => {
                const r = lc(r0);
                const acc = (r.noacc != null ? r.noacc : '').toString().trim();
                if (!(acc in idx)) {
                    idx[acc] = groups.length;
                    groups.push({
                        acc: acc,
                        nama: (r.nama != null ? r.nama : '').toString().trim(),
                        rows: []
                    });
                }
                groups[idx[acc]].rows.push(r);
            });
            return groups;
        }

        /* ── LOAD (only on click) ── */
        function makeTable(_mode) {
            const data = {
                date1: $("#inputDate1").val(),
                date2: $("#inputDate2").val(),
                divisi: globalDivisi,
                inputPerkiraan1: $("#inputPerkiraan1").val(),
                inputPerkiraan2: $("#inputPerkiraan2").val()
            };

            document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');
            $.ajax({
                url: reportUrl,
                type: 'get',
                data: data,
                success: function(res) {
                    lastRows = res || [];
                    renderLedger();
                },
                error: function() {
                    lastRows = [];
                    renderLedger();
                    setFooter('Gagal memuat data laporan.');
                }
            });
        }

        /* ── RENDER (grouped general ledger) ── */
        // Both search boxes match the same fields (account code/name, no. bukti, keterangan)
        // and combine with OR: a transaction row is shown if it matches ANY non-empty term.
        // Filtering is row-level — non-matching rows are dropped, and subtotals / running
        // balance / grand total are recomputed from the rows that remain visible.
        function activeTerms() {
            return [$("#searchBox").val(), $("#searchBox2").val()]
                .map(s => (s || '').trim().toLowerCase())
                .filter(s => s !== '');
        }

        function rowMatches(g, r, terms) {
            if (!terms.length) return true;
            const hay = (g.acc + ' ' + g.nama + ' ' + (r.nobukti || '') + ' ' + (r.keterangan || '')).toLowerCase();
            return terms.some(t => hay.includes(t));
        }

        function renderLedger() {
            const cols  = gcart_header.filter(c => c[2] === 1);
            const thead = document.querySelector('#mainTable thead');
            const tbody = document.getElementById('tableBody');

            // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
            // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
            thead.innerHTML = ReportTable.headHtml(cols);

            // kolom numerik yang ikut ditotal (Debet & Kredit secara default — Saldo TIDAK,
            // karena isinya saldo berjalan, bukan angka yang dijumlah)
            const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
            const totalKeys = totalCols.map(c => String(c[0]).toLowerCase());
            const colByKey  = {};
            cols.forEach(c => { colByKey[String(c[0]).toLowerCase()] = c; });
            // kolom label = kolom pertama yang terlihat & bukan angka (tempat teks "Saldo Awal" /
            // "Subtotal ..." / "TOTAL KESELURUHAN" ditaruh)
            const labelCol = cols.find(c => c[3] !== 'float' && c[3] !== 'int') || cols[0];
            const labelKey = labelCol ? String(labelCol[0]).toLowerCase() : null;

            const terms = activeTerms();
            const groups = buildGroups(lastRows);

            let visible = 0, shownGroups = 0;
            const grand = {}; totalKeys.forEach(k => grand[k] = 0);

            let html = '';

            groups.forEach(g => {
                const opening = g.rows.find(isOpening);
                const txns = g.rows.filter(r => !isOpening(r));
                const shownTxns = txns.filter(r => rowMatches(g, r, terms));
                if (!shownTxns.length) return; // no matching rows → hide the whole account

                shownGroups++;

                // group header (divider bar, selalu penuh selebar kolom yang terlihat)
                html += '<tr class="group-row"><td colspan="' + cols.length + '">' + esc(g.acc) +
                    (g.nama ? ' &mdash; ' + esc(g.nama) : '') + '</td></tr>';

                let running = 0;
                const sub = {}; totalKeys.forEach(k => sub[k] = 0);

                // opening "Saldo Awal" seeds the running balance (when the account has one)
                if (opening) {
                    running += num(opening.saldoakhir);
                    const map = {};
                    if (labelKey) map[labelKey] = { text: '<span style="font-style:italic;color:var(--muted)">Saldo Awal</span>' };
                    if (colByKey['debet'])  map['debet']  = { text: '-' };
                    if (colByKey['kredit']) map['kredit'] = { text: '-' };
                    if (colByKey['saldoakhir']) map['saldoakhir'] = { text: saldo(running, colByKey['saldoakhir']), style: 'font-weight:600', neg: running < 0 };
                    html += '<tr class="data-row">' + cellsFor(cols, map) + '</tr>';
                }

                shownTxns.forEach(r => {
                    running += num(r.saldoakhir); // cumulative over the rows shown
                    totalKeys.forEach(key => {
                        const v = num(r[key]);
                        sub[key] += v; grand[key] += v;
                    });

                    const tgl = (typeof format_date === 'function') ? format_date(r.tanggal) : (r.tanggal || '');
                    // Each transaction row opens its source voucher in the shared bottom panel
                    // (report-table.js): INVC -> Invoice, B* -> Bukti Kas/Bank, by row Jenis.
                    const nb = (r.nobukti != null ? String(r.nobukti) : '');
                    const jn = (r.jenis != null ? String(r.jenis).trim() : '');

                    const map = {
                        tanggal:    { text: tgl, style: 'white-space:nowrap' },
                        nobukti:    { text: esc(nb), cls: 'code' },
                        keterangan: { text: esc(r.keterangan), cls: 'name' },
                        lawan:      { text: esc(r.lawan), cls: 'code' },
                        debet:      { text: money(num(r.debet), colByKey['debet']) },
                        kredit:     { text: money(num(r.kredit), colByKey['kredit']) },
                        saldoakhir: { text: saldo(running, colByKey['saldoakhir']), style: 'font-weight:600', neg: running < 0 }
                    };

                    const title = 'Klik untuk lihat ' + (typeof jenisTitle === 'function' ? jenisTitle(jn) : 'voucher') + ' ' + nb;
                    html += '<tr class="data-row kas-clickable" style="cursor:pointer" title="' + esc(title) +
                        '" data-nobukti="' + esc(nb) + '" data-jenis="' + esc(jn) + '">' + cellsFor(cols, map) + '</tr>';
                    visible++;
                });

                // per-account subtotal (totals of shown rows + closing balance)
                const subMap = {};
                if (labelKey) subMap[labelKey] = { text: 'Subtotal ' + esc(g.acc), style: 'font-weight:600' };
                totalKeys.forEach(key => { subMap[key] = { text: money(sub[key], colByKey[key]) }; });
                if (colByKey['saldoakhir']) subMap['saldoakhir'] = { neg: running < 0 };
                html += '<tr class="subtotal-row">' + cellsFor(cols, subMap) + '</tr>';
            });

            if (!shownGroups) {
                const msg = lastRows.length ? 'Tidak ada baris yang cocok dengan pencarian.' :
                    'Tidak ada data untuk filter ini.';
                tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">' + msg + '</td></tr>';
                setFooter(lastRows.length ? 'Tidak ada hasil pencarian' : 'Tidak ada data');
                return;
            }

            // grand total (movement totals across all shown rows)
            const grandMap = {};
            if (labelKey) grandMap[labelKey] = { text: 'TOTAL KESELURUHAN', style: 'font-weight:800' };
            totalKeys.forEach(key => { grandMap[key] = { text: money(grand[key], colByKey[key]) }; });
            html += '<tr class="grand-total">' + cellsFor(cols, grandMap) + '</tr>';

            tbody.innerHTML = html;
            setFooter(`Menampilkan ${visible} transaksi - ${shownGroups} akun`);
        }

        function applyFilters() {
            if (lastRows.length) renderLedger();
        }

        // The voucher drill (openVoucher / openKasharian / openInvoice / closeKasharian)
        // lives in public/js/report-table.js, configured via window.ReportTableConfig above.

        /* ── EXPORT ── */
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
            if (fmt === 'Print') {
                window.print();
                return;
            }
            exportDelimited(fmt);
        }

        function exportDelimited(fmt) {
            const out = [
                ['Perkiraan', 'Nama', 'Tanggal', 'No. Bukti', 'Keterangan', 'Lawan', 'Debet', 'Kredit', 'Saldo']
            ];
            buildGroups(lastRows).forEach(g => {
                let running = 0;
                g.rows.forEach(r => {
                    running += num(r.saldoakhir);
                    if (isOpening(r)) {
                        out.push([g.acc, g.nama, '', 'SALDO AWAL', 'Saldo Awal', '', '', '', running]);
                    } else {
                        out.push([g.acc, g.nama, r.tanggal || '', r.nobukti || '', r.keterangan || '', r
                            .lawan || '', num(r.debet), num(r.kredit), running
                        ]);
                    }
                });
            });

            ReportExport.fromRows(out, fmt, { customName: 'BukuBesar_' + ($("#inputDate1").val() || '') + '_sd_' + ($("#inputDate2").val() || '') });
            showToast('📄', 'Data diekspor sebagai ' + fmt);
        }

        /* ── TOAST ── */
        function showToast(icon, msg) {
            const t = document.getElementById('toast');
            document.getElementById('ti').textContent = icon;
            document.getElementById('tm').textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        // Picker gaya baru (new-cust-supp-modal-guide.md): TANPA kolom Actions / tombol "+",
        // baris langsung diklik. #formSelect di halaman ini bukan modal bersama (tidak ada
        // halaman lain yang menyertakannya) jadi ungated aman -- tidak ada halaman lain yang
        // ikut berubah.
        function pickerHeadHtml(cols) {
            return '<tr>' + cols.map(c => '<th>' + c + '</th>').join('') + '</tr>';
        }

        function pickerRowHtml(target, kode, cellsHtml) {
            return '<tr class="pick-row" onclick="buttonPilihPerkiraan(' + target + ',\'' + kode + '\')">' +
                cellsHtml + '</tr>';
        }

        // Buka modal pilih Perkiraan (rentang: target 1 = awal → #inputPerkiraan1,
        // target 2 = akhir → #inputPerkiraan2). Sumber khusus Buku Besar:
        // laporanaccountingbukubesar_loadperkiraan (parameter beda dari modal bersama).
        function loadSelectPerkiraan(target) {
            let _token = $("#_token").val();
            let dataRefresh = [];

            if ($.fn.DataTable.isDataTable('#tabelSelect')) {
                $('#tabelSelect').DataTable().destroy();
            }

            $.ajax({
                url: "{!! url('laporanaccountingbukubesar_loadperkiraan') !!}",
                type: "get",
                async: false,
                data: {
                    _token: _token,
                },
                success: function(res) {
                    dataRefresh = res || [];
                },
            });

            document.getElementById('exampleModalLabel').innerHTML = "Select Perkiraan";
            document.getElementById("tabelHeader").innerHTML = pickerHeadHtml(['Perkiraan', 'Keterangan']);

            let rowTable = "";
            dataRefresh.forEach((item) => {
                const kodeJs = String(item.Perkiraan).replace(/'/g, "\\'");
                rowTable += pickerRowHtml(target, kodeJs,
                    `<td>${esc(item.Perkiraan)}</td><td>${esc(item.Keterangan)}</td>`);
            });

            document.getElementById("tabel_dataSelect").innerHTML = rowTable;
            $("#formSelect").addClass('rt-picker-v2');
            $("#tabelSelect").DataTable({
                "lengthChange": false,
                "paging": true,
                // Sebelum konversi, kolom Actions (isinya tombol identik di tiap baris) ada di
                // index 0, jadi sort default DataTables ("order": [[0,'asc']]) adalah no-op
                // stabil -> baris tampil sesuai urutan API. Setelah Actions dihapus, Perkiraan
                // jadi kolom 0 dan sort default itu betulan mengurutkan -- matikan biar urutan
                // sama seperti sebelumnya (klik header masih tetap bisa sort manual).
                "order": [],
            });

            $("#formSelect").modal('toggle'); // <-- tampilkan modalnya
        }

        // Pilih satu Perkiraan → isi input awal/akhir lalu tutup modal.
        function buttonPilihPerkiraan(target, kode) {
            $(target == 2 ? "#inputPerkiraan2" : "#inputPerkiraan1").val(kode);
            $("#formSelect").modal('hide');
        }
    </script>
@endsection
