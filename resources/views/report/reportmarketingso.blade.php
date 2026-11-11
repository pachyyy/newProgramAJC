@extends('report.masterreport2')

<style>
    #inputReportMode {
        border: 0;
        background: none;
        padding: 0;
        box-shadow: none;
        color: #495057;
        font-weight: 600;
    }

    #inputReportMode:hover,
    #inputReportMode:focus {
        color: #0d6efd;
        box-shadow: none;
    }

    .tb-report .table-wrap {
        min-height: 15vh;
    }

    /* ── KPI strip ── */
    .tb-report .kpi-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    @media (max-width: 900px) {
        .tb-report .kpi-strip {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 560px) {
        .tb-report .kpi-strip {
            grid-template-columns: 1fr;
        }
    }

    .tb-report .kpi-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .tb-report .kpi-ic {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 18px;
    }

    .tb-report .kpi-label {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .tb-report .kpi-val {
        font-size: 19px;
        font-weight: 700;
        color: #1e293b;
    }
</style>

@include('report.modalMarketingSO')

@section('header2')
    <div class="tb-report main">
        <div class="content">

            <!-- TOOLBAR -->
            <div class="toolbar">
                {{-- <div>
                    <div class="page-title">SO</div>
                </div> --}}

                <!-- Jenis laporan: Non Outstanding (dua tanggal, ke Sp_ReportSODet) atau
                     Outstanding (tgl1 sudah dipatok di controller, hanya tgl2 dikirim). -->
                <div class="filter-wrap">
                    <label>Jenis</label>
                    <select class="filter-inp" id="inputMode" onchange="setMode(this.value)">
                        <option value="0">Semua</option>
                        <option value="1">Outstanding</option>
                    </select>
                </div>

                <!-- Periode (date range) -->
                <div class="filter-wrap">
                    <label id="periodeLabel">Periode</label>
                    <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
                    <span class="filter-sep" id="dateSep">s/d</span>
                    <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
                </div>

                {{-- Search --}}
                <div>
                    <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..."
                        oninput="applyFilters()" style="width:180px">
                </div>

                <!-- Actions: search + filter modal + customize + tampilkan + export -->
                <div class="action-group">
                    {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5).
                         Halaman ini memuat dua Bootstrap; karena jQuery baru dimuat SESUDAH bundle
                         BS5, $.fn.modal dipegang BS4. applyModalFilter() & pickFromModal() menutup
                         modal ini dengan $('#modalFilter').modal('hide'), jadi pembukanya harus
                         memakai API yang sama — kalau dibuka BS5, hide() versi BS4 tidak berefek.
                         JANGAN pasang data-toggle dan data-bs-toggle bersamaan: kedua data-api
                         akan jalan dan membuat dua instance + dobel backdrop. --}}
                    <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    {{-- <button class="btn-load" onclick="doShowFormCustomizeTable()" title="Customize Table"><i
                            class="fas fa-cog"></i> Customize Table</button> --}}
                    <button class="btn-load" onclick="makeTable('REPORT')" title="Tampilkan laporan"><i
                            class="fas fa-check"></i> Tampilkan</button>
                    <div class="export-wrap" id="exportW rap">
                        <button class="export-btn" onclick="toggleExport()"><i class="bi bi-arrow-down"></i> Export <i
                                class="bi bi-caret-down-fill"></i></button>
                        <div class="export-drop" id="exportDrop">
                            <div class="export-opt" onclick="doExport('Excel')"><i class="bi bi-journals text-success"></i>
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

            <!-- KPI STRIP (dibangun sisi-klien dari baris yang sedang tampil di tabel) -->
            <div class="kpi-strip" id="kpiStrip"></div>

            <!-- Bar kolom tersembunyi + Order By (diisi oleh report-table.js / ReportTable) -->
            <div id="rtBar"></div>

            <!-- TABLE -->
            <div class="table-outer">
                <div class="table-wrap">
                    <table class="tb" id="mainTable">
                        <thead>
                            <tr>
                                <th>No. Bukti</th>
                            </tr>
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
                Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan
                kolom atau atur desimal &amp; total.
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
                    {{-- data-dismiss (BS4) = yang benar-benar menutup, karena modal ini dibuka
                         lewat $.fn.modal milik BS4. data-bs-dismiss dibiarkan untuk jaga-jaga. --}}
                    <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                        onclick="$('#modalFilter').modal('hide')"></button>
                </div>

                <div class="modal-body">

                    <div class="rt-section">
                        {{-- <div class="rt-group-label">Pengaturan Laporan</div> --}}
                        <div class="rt-grid-2">
                            {{-- Report Detail/Rekap dihapus -- Rekap dulu punya kolom berbeda (real
                                 feature), tapi sekarang sengaja dibuang; halaman ini hanya Detail. --}}
                            {{-- Filter langsung, ga ke sp -- hanya berlaku di mode Non Outstanding
                                 (Sp_ReportOutStandingSoDet tidak menerima parameter ini). --}}
                            <div id="wrapOtorisasi">
                                <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
                                <select class="rt-native" id="modalOtorisasi">
                                    <option value="2">Semua</option>
                                    <option value="0">Belum</option>
                                    <option value="1">Sudah</option>
                                </select>
                            </div>
                            {{-- Outstanding --}}
                            <div id="wrapStatus">
                                <label class="rt-field-label" for="modalOutstanding">Status</label>
                                <select class="rt-native" id="modalOutstanding">
                                    <option value="2">Semua</option>
                                    <option value="0">Terkirim</option>
                                    <option value="1">Belum</option>
                                    <option value="3">Sebagian</option>
                                </select>
                            </div>
                        </div>
                        <div class="rt-grid-2">
                            <div id="wrapAgen">
                                <label class="rt-field-label" for="modalAgen">Agen</label>
                                <select class="rt-native" id="modalAgen">
                                    <option value="0">Agen</option>
                                    <option value="1">Non-Agen</option>
                                    <option value="2">Semua</option>
                                </select>
                            </div>
                        </div>

                        {{-- Order By dipindah ke switcher "Order By" di atas tabel (#rtBar, lewat
                             ReportTable.init views) -- baik mode Semua maupun Outstanding, jadi
                             tidak lagi di modal ini. --}}
                    </div>

                    <div class="rt-section" id="wrapPickFields">
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
                        <button type="button" class="rt-btn rt-btn-primary"
                            onclick="applyModalFilter()">Terapkan</button>
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
        // baris tabel langsung diklik, tanpa kolom Actions/tombol Select. Modal itu dipakai
        // ~34 halaman report lain juga, tapi mereka tidak menyalakan flag ini sehingga
        // tampilan & perilakunya tetap seperti semula.
        window.g_pickerV2 = true;

        let globalDate1 = "{!! date('Y-m-d') !!}";
        let globalDate2 = "{!! date('Y-m-d') !!}";
        let globalOtorisasi = "2"; // default: Semua
        let globalOutstanding = "2"; // default: Semua
        let globalOrderBy = "N"; // default: Nomor Bukti
        let globalAgen = "2"; // default: Agen
        let globalMode = "0"; // "0" = Non Outstanding (Sp_ReportSODet), "1" = Outstanding (Sp_ReportOutStandingSoDet)

        let lastRows = []; // hasil fetch terakhir (dipakai render / export / search)
        let currentGroupby = 'NoBukti'; // groupby aktif untuk render ulang saat search

        // Offset mode report Outstanding supaya kolom tersimpan (DBSIMPANHEADER, dikunci per
        // href+reportmode) tidak bentrok dengan mode Non Outstanding di href yang sama.
        const OUT_MODE_OFFSET = 20;

        // Order By: opsi beda per mode -- Sp_ReportOutStandingSoDet tidak menerima HG/CB/L.
        // Kedua SP sudah menerima parameter Ordr dengan nilai-nilai ini (dikonfirmasi manual
        // terhadap stored procedure, bukan ditebak -- lihat docs/handoff-remove-detail-rekap-order-by.md §B0).
        const ORDERBY_SEMUA = [
            { value: 'N', label: 'Nomor Bukti', desc: 'Dikelompokkan per No Bukti' },
            { value: 'B', label: 'Nomor Barang', desc: 'Dikelompokkan per Barang' },
            { value: 'C', label: 'Nomor Customer', desc: 'Dikelompokkan per Customer' },
            { value: 'S', label: 'Sales', desc: 'Dikelompokkan per Sales' },
            { value: 'HG', label: 'Head Group', desc: 'Dikelompokkan per Head Group' },
            { value: 'CB', label: 'CB', desc: 'Dikelompokkan per CB' },
            { value: 'P', label: 'PIC', desc: 'Dikelompokkan per PIC' },
            { value: 'L', label: 'L', desc: 'Dikelompokkan per L' }
        ];
        const ORDERBY_OUT = [
            { value: 'N', label: 'Nomor Bukti', desc: 'Dikelompokkan per No Bukti' },
            { value: 'B', label: 'Nomor Barang', desc: 'Dikelompokkan per Barang' },
            { value: 'C', label: 'Nomor Customer', desc: 'Dikelompokkan per Customer' },
            { value: 'P', label: 'PIC', desc: 'Dikelompokkan per PIC' },
            { value: 'S', label: 'Sales', desc: 'Dikelompokkan per Sales' }
        ];

        // Objek ini sendiri yang dikirim ke ReportTable.init({ views }) -- disimpan di variabel
        // supaya setMode() bisa menukar .options lalu panggil ReportTable.refresh() saat ganti
        // mode (report-table.js membaca cfg.views by reference, jadi mutasi di sini langsung
        // kepakai tanpa perlu init() ulang).
        let viewsCfg = {
            label: 'Order By',
            options: ORDERBY_SEMUA,
            get: function() {
                return globalOrderBy;
            },
            set: function(v) {
                setOrderBy(String(v));
                if (lastRows.length) { makeTable('REPORT'); } // re-fetch: inputOrd adalah parameter SP
            }
        };

        const reportUrlSO = "{{ url('laporanmarketingso_doReport') }}";
        const reportUrlOut = "{{ url('laporanmarketinglaporanoutso_doReport') }}";

        $(document).ready(function() {
            setOtorisasi(globalOtorisasi);
            setOutstanding(globalOutstanding);
            setOrderBy(globalOrderBy);
            setAgen(globalAgen);
            showPeriode();

            // Menu lama boleh mengarahkan ke /laporanmarketingso?mode=out supaya langsung
            // terbuka di mode Outstanding (lihat rencana retire halaman lama).
            if ("{{ request('mode') }}" === "out") {
                $('#inputMode').val('1');
                setMode('1');
            }

            setDefaultHeader();
            buildKpis([]);

            // Header tabel interaktif + switcher "Order By" di #rtBar (bukan lagi Detail/Rekap).
            ReportTable.init({
                table: '#mainTable',
                bar: '#rtBar',
                onChange: render,
                views: viewsCfg
            });

            // setTimeout(() => {
            //   makeTable('REPORT');
            // }, 100);
        });

        // periode
        function showPeriode() {
            globalDate1 = $('#inputDate1').val();
            globalDate2 = $('#inputDate2').val();
            // alertify.success(`Periode: ${globalDate1} s/d ${globalDate2}`);
        }

        // otorisasi
        function setOtorisasi(val) {
            globalOtorisasi = val;
        }

        // outstanding
        function setOutstanding(val) {
            globalOutstanding = val;
        }

        function setAgen(val) {
            globalAgen = val;
        }

        // Jenis laporan: "0" Non Outstanding (Sp_ReportSODet, dua tanggal) atau
        // "1" Outstanding (Sp_ReportOutStandingSoDet, tgl1 dipatok di controller).
        function setMode(val) {
            globalMode = val;
            const isOut = (val === '1');

            // tgl1 tidak dikirim di mode Outstanding -- controller sudah mematoknya sendiri.
            $('#inputDate1').toggle(!isOut);
            $('#dateSep').toggle(!isOut);
            $('#periodeLabel').text(isOut ? 'Per Tanggal' : 'Periode');

            // KPI & filter Otorisasi/Status/Agen/Filter Data bergantung pada kolom
            // (NeedOtorisasi, outstanding, dst.) yang tidak ada di hasil Sp_ReportOutStandingSoDet.
            $('#kpiStrip').toggle(!isOut);
            $('#wrapOtorisasi').toggle(!isOut);
            $('#wrapStatus').toggle(!isOut);
            $('#wrapAgen').toggle(!isOut);
            $('#wrapPickFields').toggle(!isOut);

            if (isOut) {
                // reset supaya tidak diam-diam menyaring baris saat baliknya nanti
                $('#modalOtorisasi').val('2');
                $('#modalOutstanding').val('2');
                $('#modalAgen').val('2');
                setOtorisasi('2');
                setOutstanding('2');
                setAgen('2');
                PICK_FIELDS.forEach(function(f) {
                    $('#' + f.id).val('-');
                });
            }

            // Opsi Order By beda per mode (Outstanding tidak punya HG/CB/L) -- reset ke "N"
            // (ada di kedua daftar) supaya tidak nyangkut di opsi yang tidak valid di mode baru.
            viewsCfg.options = isOut ? ORDERBY_OUT : ORDERBY_SEMUA;
            setOrderBy('N');
            ReportTable.refresh();

            // Ganti mode tidak langsung fetch ulang -- tabel dikosongkan, user tekan Tampilkan.
            lastRows = [];
            currentGroupby = 'NoBukti';
            $('#tableBody').html('<tr class="empty-row"><td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>');
            $('#footerLabel').text('Belum ada data dimuat');

            updateFilterBadge();
        }

        // order by
        function setOrderBy(val) {
            globalOrderBy = val;
            setModeReport();
        }

        /* -- FILTER MODAL -- */

        // Enam field "Filter Data" (Customer/Group/PIC/Kategori/Sub-Kat/Merk): nilai
        // sebenarnya tetap di input hidden #inputXxx (dibaca makeTable(), ditulis
        // buttonPilih() di modalMarketingSO.blade.php) — kotak .rt-combo di bawah ini
        // hanyalah tampilan di atasnya.
        const PICK_FIELDS = [{
                id: 'inputCustomer',
                label: 'Customer',
                modal: 'selectCustomer'
            },
            {
                id: 'inputGroup',
                label: 'Group',
                modal: 'selectGroup'
            },
            {
                id: 'inputPIC',
                label: 'PIC',
                modal: 'selectPIC'
            },
            {
                id: 'inputKategori',
                label: 'Kategori',
                modal: 'selectKategori'
            },
            {
                id: 'inputSubKategori',
                label: 'Sub-Kat',
                modal: 'selectSubKategori'
            },
            {
                id: 'inputMerk',
                label: 'Merk',
                modal: 'selectMerk'
            },
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

        function updateFilterBadge() {
            let count = 0;
            PICK_FIELDS.forEach(function(f) {
                const val = $('#' + f.id).val();
                if (val && val !== '-') {
                    count++;
                }
            });
            if ($('#modalOtorisasi').val() !== '2') {
                count++;
            }
            if ($('#modalOutstanding').val() !== '2') {
                count++;
            }
            if ($('#modalAgen').val() !== '2') {
                count++;
            }
            // Report (Detail/Rekap) dan Order By tidak lagi di modal ini -- sudah pindah ke
            // switcher "Order By" di #rtBar, jadi tidak dihitung ke badge filter modal.
            $('#filterBadge').text(count + ' aktif');
        }

        function resetAllFilters() {
            $('#modalOtorisasi').val('2');
            $('#modalOutstanding').val('2');
            $('#modalAgen').val('2');
            PICK_FIELDS.forEach(function(f) {
                $('#' + f.id).val('-');
            });
            renderPickFields();
            updateFilterBadge();
        }

        $('#modalFilter').on('show.bs.modal', function() {
            $("#modalOtorisasi").val(globalOtorisasi);
            $("#modalOutstanding").val(globalOutstanding);
            $("#modalAgen").val(globalAgen);
            renderPickFields();
            updateFilterBadge();
        });

        $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

        function applyModalFilter() {
            setOtorisasi($("#modalOtorisasi").val());
            setOutstanding($("#modalOutstanding").val());
            setAgen($("#modalAgen").val());

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
                // #modalFilter 'show.bs.modal' juga memanggil ini, tapi panggil lagi di sini
                // supaya kotak .rt-combo langsung terupdate walau show.bs.modal tidak sempat
                // fire ulang (mis. modal masih dalam proses transisi tampil).
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
            if (fmt === 'Print') {
                window.print();
                return;
            }
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
            ReportExport.fromRows(rows, fmt, { customName: (globalMode === '1') ? 'OutstandingSO_' + (globalDate2 || '') : 'MarketingSO_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
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
            for (const k in r) {
                if (k.toLowerCase() === lk) return r[k];
            }
            return undefined;
        }

        // Hanya untuk mode Outstanding: VwReportOutStandingSO mengembalikan sepasang qty
        // (QNT/QNT2, QntSisa/Qnt2Sisa) + NOSAT, tapi tidak ada kolom jadinya. NOSAT=1 -> pasangan
        // pertama, 2 atau 3 -> kedua (ikut CASE di view-nya sendiri). QntSisa sudah dinormalkan ke
        // satuan-1 di view (QNT2*ISI2 untuk NOSAT=2), Qnt2Sisa ada di satuan-2, jadi tiap pasangan
        // memang sudah sesuai satuannya masing-masing. Satuan/SatuanRoll digabung dengan aturan
        // yang SAMA (nosat=1 -> Satuan, 2/3 -> SatuanRoll) supaya unitnya selalu cocok dengan
        // angka QTY di atas -- QNT2 itu dalam satuan roll, bukan satuan biasa. Dihitung sekali
        // di sini (bukan di render()) supaya render, subtotal, pencarian, dan export semua
        // membaca field yang sama.
        function decorateRows(rows) {
            (rows || []).forEach(function(r) {
                const nosat = String(pickCI(r, 'NOSAT'));
                const pakaiKedua = (nosat === '2' || nosat === '3');
                r.QTY = pickCI(r, pakaiKedua ? 'QNT2' : 'QNT');
                r.QTYSisa = pickCI(r, pakaiKedua ? 'Qnt2Sisa' : 'QntSisa');
                r.Satuan = pickCI(r, pakaiKedua ? 'SatuanRoll' : 'Satuan');
            });
            return rows || [];
        }

        /* -- KPI STRIP -- */
        function fmtShort(v) {
            v = Math.round(currencyNormalizer(v));
            const a = Math.abs(v);
            if (a >= 1e9) return (v / 1e9).toFixed(1).replace(/\.0$/, '') + ' M';
            if (a >= 1e6) return (v / 1e6).toFixed(1).replace(/\.0$/, '') + ' jt';
            if (a >= 1e3) return (v / 1e3).toFixed(0) + ' rb';
            return String(v);
        }

        // Dihitung dari baris yang SEDANG TAMPIL (setelah search + filter Otorisasi/Status),
        // dipanggil dari render() supaya selalu ikut baris yang terlihat di tabel.
        function buildKpis(rows) {
            rows = rows || [];

            let totalDpp = 0;
            const custSet = new Set();
            const soSet = new Set();
            const belumSet = new Set();

            rows.forEach(function(r) {
                totalDpp += currencyNormalizer(pickCI(r, 'NDPPRPZX'));

                const cust = pickCI(r, 'KodeCustSupp');
                if (cust != null && String(cust).trim() !== '') custSet.add(cust);

                const noBukti = pickCI(r, 'NoBukti');
                if (noBukti != null && String(noBukti).trim() !== '') {
                    soSet.add(noBukti);
                    if (pickCI(r, 'outstanding') == null) belumSet.add(noBukti);
                }
            });

            const cards = [
                ['Total DPP', 'Rp ' + fmtShort(totalDpp), '#4F46E5', '#ede9fe', 'bi-currency-dollar'],
                ['Total Customer', format_number(custSet.size, 0), '#2563eb', '#dbeafe', 'bi-people-fill'],
                ['Total SO', format_number(soSet.size, 0), '#16a34a', '#dcfce7', 'bi-receipt'],
                ['Belum', format_number(belumSet.size, 0), '#dc2626', '#fee2e2', 'bi-clock-history'],
            ];

            document.getElementById('kpiStrip').innerHTML = cards.map(c =>
                '<div class="kpi-card"><div class="kpi-ic" style="background:' + c[3] + ';color:' + c[2] +
                '"><i class="bi ' + c[4] + '"></i></div>' +
                '<div><div class="kpi-label">' + c[0] + '</div><div class="kpi-val">' + c[1] + '</div></div></div>'
            ).join('');
        }

        // Satu slot per ordering (bukan per kolom -- semua ordering pakai kolom yang sama sejak
        // Detail/Rekap dihapus) supaya kolom tersimpan tiap user (DBSIMPANHEADER, doSetHeader())
        // tidak bleed antar ordering biarpun isinya identik sekarang.
        var modereport_nobukti = 0,
            modereport_barang = 1,
            modereport_customer = 2,
            modereport_sales = 3,
            modereport_hg = 4,
            modereport_cb = 5,
            modereport_pic = 6,
            modereport_l = 7;
        g_modeReport = modereport_nobukti;

        // Dispatcher: kolom Non Outstanding dan Outstanding tetap beda SP/view (mis. Outstanding
        // tidak punya NDPPRPZX/NPPNRPX/NNETRPZX/TglPO, lihat catatan di setHeaderOut()), jadi
        // tetap dua fungsi -- tapi di dalam tiap mode, semua ordering sekarang pakai kolom yang
        // sama (dulu beda-beda per Detail/Rekap x ordering, sekarang disamakan seperti mode "N").
        function setDefaultHeader() {
            if (globalMode === '1') {
                setHeaderOut();
            } else {
                setHeaderSO();
            }
        }

        // Semua ordering (N/B/C/S/HG/CB/P/L) pakai kolom yang sama -- dulu tiap ordering x
        // Detail/Rekap punya array sendiri, sekarang disamakan ke kolom mode "N" per instruksi.
        function setHeaderSO() {
            gcart_header = [
                // field sp, name on header, idk, data type, etc
                ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
                ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
                ['NoSPB', 'PO. Cust', 1, 'varchar', 0, 0],
                ['NamaKebun', 'Lokasi Penerima', 1, 'varchar', 0, 0],
                ['NamaBoffice', 'PIC', 1, 'varchar', 0, 0],
                ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
                ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
                ['Satuan', 'Sat', 1, 'varchar', 0, 0],
                ['QNT', 'QTY', 1, 'float', 0, 0],
                ['HARGA', 'Harga', 1, 'float', 0, 0],
                ['NDPPRPZX', 'DPP', 1, 'float', 1, 0],
                ['NPPNRPX', 'PPN', 1, 'float', 1, 0],
                ['NNETRPZX', 'Total', 1, 'float', 1, 0],
                ['TglPO', 'Tgl. PO', 1, 'date', 0, 0],
                ['NeedOtorisasi', 'Otorisasi', 1, 'bool', 0, 0],
                ['outstanding', 'Dikirim', 1, 'varchar', 0, 0]
            ];

            gsum_issubtotal = 1;
            gsum_isgrandtotal = 1;
        }

        // Kolom Outstanding (Sp_ReportOutStandingSoDet) -- proc ini SELALU `select * from
        // VwReportOutStandingSO` apa pun Ordr-nya. View itu TIDAK punya NDPPRPZX/NPPNRPX/
        // NNETRPZX/TglPO/NoSPB/NamaKebun/DiscTot/KODEVLS/KURS/DPPVLSZX/NPPNRPVLSX/NNETRPVLSZX
        // (header lama mengacu ke kolom itu semua -> tampil kosong/0). Yang benar-benar
        // dikembalikan: NOSAT, QNT/QNT2, Satuan/SatuanRoll, QtySisa/Qty2Sisa, harga, RPOUt,
        // NoPesanan. QTY, QTY Sisa, DAN Satuan digabung dari pasangan itu oleh decorateRows()
        // (nosat=1 -> QNT/QtySisa/Satuan, 2 atau 3 -> QNT2/Qty2Sisa/SatuanRoll) sebelum sampai
        // ke sini, sama seperti di reportlaporanmarketingspb.blade.php -- kolom 'Satuan' di
        // header bawah ini karena itu sudah berisi hasil gabungan, BUKAN kolom Satuan mentah
        // dari view. QTY tidak di-subtotal (satuan beda-beda per baris, sama seperti QNT di
        // setHeaderSO) -- hanya RPOUt (uang) yang dijumlahkan.
        // Semua ordering (N/B/C/P/S) di mode Outstanding pakai kolom yang sama, mengikuti kolom
        // mode "N" per instruksi.
        function setHeaderOut() {
            gcart_header = [
                ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
                ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
                ['NoPesanan', 'PO. Cust', 1, 'varchar', 0, 0],
                ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
                ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
                ['Satuan', 'Sat', 1, 'varchar', 0, 0],
                ['QTY', 'QTY', 1, 'float', 0, 0],
                ['QTYSisa', 'QTY Sisa', 1, 'float', 0, 0],
                ['harga', 'Harga', 1, 'float', 0, 0],
                ['RPOUt', 'Total Harga Out', 1, 'float', 1, 0],
            ];

            gsum_issubtotal = 1;
            gsum_isgrandtotal = 1;
        }

        function makeTable(_mode) {
            // nilai groupby adalah nama kolom (sesuai database) untuk pengelompokan subtotal
            // mode report menentukan kolom yang dipakai
            let groupby = '';
            let _date1 = $("#inputDate1").val();
            let _date2 = $("#inputDate2").val();
            let inputOto = globalOtorisasi;
            let _inputAgen = globalAgen;
            let _inputCustomer = $("#inputCustomer").val();
            let _inputGroup = $("#inputGroup").val();
            let _inputPIC = $("#inputPIC").val();
            let _inputKategori = $("#inputKategori").val();
            let _inputSubKategori = $("#inputSubKategori").val();
            let _inputMerk = $("#inputMerk").val();
            let input_order = globalOrderBy;

            if (input_order == "N") {
                groupby = 'NoBukti';
            } else if (input_order == "B") {
                groupby = 'KODEBRG';
            } else if (input_order == "C") {
                groupby = 'KodeCustSupp';
            } else if (input_order == "S") {
                groupby = 'KodeSls';
            } else if (input_order == "HG") {
                groupby = 'KODECUSTSUPP';
            } else if (input_order == "CB") {
                groupby = 'KODECUSTSUPP';
            } else if (input_order == "P") {
                groupby = 'KODECUSTSUPP';
            } else if (input_order == "L") {
                groupby = 'KODECUSTSUPP';
            }

            setDefaultHeader();
            if (typeof doSetHeader === 'function') {
                doSetHeader(g_modeReport);
            }

            const isOut = (globalMode === '1');

            // Sp_ReportOutStandingSoDet tidak punya slot Customer/PIC/Otorisasi/Agen sama
            // sekali -- LaporanMarketingLaporanOutSoController@doReport mematoknya sendiri ke
            // '' dan tgl1 ke 2017-08-01. Kirim hanya apa yang controller itu benar-benar baca.
            let url, data;
            if (isOut) {
                url = reportUrlOut;
                data = {
                    date2: _date2,
                    inputOrd: input_order,
                };
            } else {
                url = reportUrlSO;
                data = {
                    date1: _date1,
                    date2: _date2,
                    inputOto: inputOto,
                    inputAgen: _inputAgen,
                    inputCustomer: _inputCustomer,
                    inputGroup: _inputGroup,
                    inputPIC: _inputPIC,
                    inputKategori: _inputKategori,
                    inputSubKategori: _inputSubKategori,
                    inputMerk: _inputMerk,
                    inputOrd: input_order,
                };
            }

            document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

            $.ajax({
                url: url,
                type: 'get',
                data: data,
                success: function(res) {
                    lastRows = isOut ? decorateRows(res || []) : (res || []);
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

        // Filter langsung di client (ga ke sp) berdasarkan pilihan
        // #modalOtorisasi:
        // 0 = Non Otorisasi, 1 = Otorisasi, 3 = Non Outstanding, 4 = Outstanding, 2/lainnya = Semua
        function filterByOtorisasi(rows, filterVal) {
            switch (filterVal) {
                case '0':
                    return rows.filter(r => r.NeedOtorisasi == 0);
                case '1':
                    return rows.filter(r => r.NeedOtorisasi == 1);
                default:
                    return rows;
            }
        }
        // #modalOutstanding:
        // 0 = Non Outstanding, 1 = Outstanding, 2/lainnya = Semua
        function filterByOutstanding(rows, filterVal) {
            switch (filterVal) {
                case '0':
                    return rows.filter(r => r.outstanding >= r.QNT);
                case '1':
                    return rows.filter(r => r.outstanding == null);
                case '3':
                    return rows.filter(r => r.outstanding < r.QNT && r.outstanding > 0);
                default:
                    return rows;
            }
        }

        // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
        // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat / item[2]===1,
        // sesuai urutan simpanan) -> mode-agnostic, jadi tiap ordering (item[4]===1 menandai
        // kolom uang yang di-subtotal) langsung terpakai tanpa daftar kolom hardcode.
        function render() {
            const cols = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
            const keys = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom uang yang di-subtotal
            const thead = document.querySelector('#mainTable thead');
            const tbody = document.getElementById('tableBody');
            const showSub = (gsum_issubtotal === 1);
            const showGrand = (gsum_isgrandtotal === 1);

            const search = ($('#searchBox2').val() || '').trim().toLowerCase();
            const searched = !search ? (lastRows || []) : (lastRows || []).filter(function(r) {
                return rowSearchText(r, cols).indexOf(search) !== -1;
            });
            // Filter Otorisasi/Status & KPI bergantung pada kolom NeedOtorisasi/outstanding,
            // yang tidak ada di hasil Sp_ReportOutStandingSoDet -- lewati di mode Outstanding.
            let rows;
            if (globalMode === '1') {
                rows = searched;
            } else {
                const filterOto = ($('#modalOtorisasi').val());
                const filterOut = ($('#modalOutstanding').val());
                rows = filterByOutstanding(filterByOtorisasi(searched, filterOto), filterOut);
                buildKpis(rows);
            }

            // HEADER dinamis dari gcart_header — dibangun report-table.js (ReportTable) supaya
            // kolom bisa diseret untuk diurutkan & punya menu roda gigi.
            thead.innerHTML = ReportTable.headHtml(cols);

            if (!rows.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length +
                    '">Tidak ada data ditemukan.</td></tr>';
                document.getElementById('footerLabel').textContent = 'Tidak ada data';
                return;
            }

            let html = '',
                prev = null;
            let sub = {},
                grand = {};
            keys.forEach(k => {
                sub[k] = 0;
                grand[k] = 0;
            });

            rows.forEach(function(r, i) {
                const now = r[currentGroupby];

                // subtotal saat nilai grup berganti (kalau toggle Subtotal aktif)
                if (showSub && i !== 0 && prev !== now) {
                    html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
                    keys.forEach(k => {
                        sub[k] = 0;
                    });
                }

                keys.forEach(function(k) {
                    const v = currencyNormalizer(pickCI(r, k));
                    sub[k] += v;
                    grand[k] += v;
                });

                // satu sel per kolom terlihat, format menurut tipe (item[3]) & desimal (item[5])
                html += '<tr class="data-row">' + cols.map(function(c) {
                    const key = c[0],
                        type = c[3];
                    // Need Otorisasi = 0 berarti sudah otorisasi (hijau)
                    if (key === 'NeedOtorisasi') {
                        return `<td> ${r.NeedOtorisasi == 1 ? '<span class="sp-badge is-inactive">Belum</span>' : '<span class="sp-badge is-active">Sudah</span>'} </td>`;
                    }
                    // Outstanding
                    if (key === 'outstanding') {
                        // return `<td> ${r.outstanding == null ? '<span class="sp-badge is-inactive">Belum</span>' : '<span class="sp-badge is-active">Sudah</span>'} </td>`;
                        if (r.outstanding == null) {
                            return '<td><span class="sp-badge is-inactive">Belum</span></td>';
                        } else if (r.outstanding >= r.QNT) {
                            return '<td><span class="sp-badge is-active">Terkirim</span></td>';
                        } else {
                            return '<td><span class="sp-badge is-supervisor">Sebagian</span></td>';
                        }
                    }
                    if (type === 'date') return '<td>' + format_date(pickCI(r, key)) + '</td>';
                    if (type === 'float' || type === 'int') return '<td class="num">' + format_number(
                        currencyNormalizer(pickCI(r, key)), c[5]) + '</td>';
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

        // Baris total: nilai di kolom uang (item[4]===1), label di kolom pertama non-uang
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

        const selectFilter = document.getElementById('modalOtorisasi');

        selectFilter.addEventListener('change', (event) => {
            const selectedFilter = event.target.value;
            render();
        })

        const selectFilterOutstanding = document.getElementById('modalOutstanding');

        selectFilterOutstanding.addEventListener('change', (event) => {
            render();
        })

        // Gabungan teks satu baris dari kolom terlihat (tanggal pakai format tampil
        // dd/mm/yyyy) supaya pencarian cocok dengan apa yang user lihat di tabel.
        function rowSearchText(r, cols) {
            return cols.map(function(c) {
                const v = pickCI(r, c[0]);
                if (c[3] === 'date') return format_date(v);
                return (v == null ? '' : String(v));
            }).join(' ').toLowerCase();
        }

        function setModeReport() {
            if (globalOrderBy == "N") {
                g_modeReport = modereport_nobukti;
            } else if (globalOrderBy == "B") {
                g_modeReport = modereport_barang;
            } else if (globalOrderBy == "C") {
                g_modeReport = modereport_customer;
            } else if (globalOrderBy == "S") {
                g_modeReport = modereport_sales;
            } else if (globalOrderBy == "HG") {
                g_modeReport = modereport_hg;
            } else if (globalOrderBy == "CB") {
                g_modeReport = modereport_cb;
            } else if (globalOrderBy == "P") {
                g_modeReport = modereport_pic;
            } else if (globalOrderBy == "L") {
                g_modeReport = modereport_l;
            }

            // Kolom tersimpan (DBSIMPANHEADER) dikunci per href+reportmode -- offset mode
            // Outstanding supaya tidak bentrok/menimpa layout tersimpan mode Non Outstanding
            // di href yang sama (lihat OUT_MODE_OFFSET).
            if (globalMode === '1') {
                g_modeReport += OUT_MODE_OFFSET;
            }

            doSetHeader(g_modeReport);
            doShowCustomize();
        }
    </script>
@endsection
