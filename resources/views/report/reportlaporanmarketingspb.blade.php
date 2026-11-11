@extends('report.masterreport2')

<style>
    .tb-report .table-wrap {
        min-height: 10vh;
    }
</style>

@include('report.modalMarketingSO')

@section('header2')
    <div class="tb-report main">
        <div class="content">

            <!-- TOOLBAR -->
            <div class="toolbar">
                {{-- <div>
                    <div class="page-title">SPB</div>
                </div> --}}

                <!-- Jenis laporan: Non Outstanding (ke Sp_ReportSPBDet, dua tanggal) atau
                     Outstanding (ke Sp_ReportOutSpbDet, hanya satu tanggal -- diambil dari
                     #inputDate2, #inputDate1 disembunyikan & tidak dikirim;
                     LaporanMarketingOutSPPBController TIDAK diubah, jadi nilai #inputDate2
                     tetap dikirim sebagai request key `date1` apa adanya -- lihat makeTable()). -->
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
             BS5, $.fn.modal dipegang BS4. applyModalFilter() menutup modal ini dengan
             $('#modalFilter').modal('hide'), jadi pembukanya harus memakai API yang sama.
             JANGAN pasang data-toggle dan data-bs-toggle bersamaan. --}}
                    <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    {{-- <button class="btn-load" onclick="doShowFormCustomizeTable()" title="Customize Table"><i class="fas fa-cog"></i> Customize Table</button> --}}
                    <button class="btn-load" onclick="makeTable('REPORT')" title="Tampilkan laporan"><i
                            class="fas fa-check"></i> Tampilkan</button>
                    <div class="export-wrap" id="exportWrap">
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

            <!-- Bar kolom tersembunyi + Order By (diisi oleh report-table.js / ReportTable) -->
            <div id="rtBar"></div>

            <!-- TABLE -->
            <div class="table-outer">
                <div class="table-wrap">
                    <table class="tb" id="mainTable">
                        <thead>
                            <tr>
                                <th>No Bukti</th>
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
                    {{-- data-dismiss (BS4) = yang benar-benar menutup, karena modal ini dibuka lewat
             $.fn.modal milik BS4. data-bs-dismiss dibiarkan untuk jaga-jaga. --}}
                    <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                        onclick="$('#modalFilter').modal('hide')"></button>
                </div>

                <div class="modal-body">

                    <div class="rt-section">
                        <div class="rt-group-label">Pengaturan Laporan</div>
                        {{-- Report (Detail/Rekap) dihapus (dulu sudah pindah ke switcher "Tampilan"
               di bar atas tabel, sekarang switcher itu sendiri dibuang). Urutkan juga
               dipindah ke bar (switcher "Order By"), jadi tidak ada lagi di modal ini. --}}
                        <div class="rt-grid-2">
                            <div>
                                <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
                                {{-- Nilai = nilai kolom NeedOtorisasi apa adanya (dipakai SP maupun
                                     filter sisi-klien): 0 = semua level otorisasi sudah lengkap
                                     (Sudah), 1 = masih butuh otorisasi (Belum), 2 = semua. --}}
                                <select class="rt-native" id="modalOtorisasi">
                                    <option value="2">Semua</option>
                                    <option value="0">Sudah Otorisasi</option>
                                    <option value="1">Belum Otorisasi</option>
                                </select>
                            </div>
                            {{-- Status = kolom `outstanding` di VwreportSPB (qty yang sudah masuk
                                 invoice): > 0 Sudah, 0 Belum. Sp_ReportSPBDet TIDAK punya
                                 parameter untuk ini, jadi murni filter sisi-klien di render()
                                 lewat filterByStatus(). Sp_ReportOutSpbDet tidak mengembalikan
                                 kolomnya sama sekali -> field ini disembunyikan di mode
                                 Outstanding (lihat setMode()), sama seperti Tgl. Terima. --}}
                            <div id="wrapStatus">
                                <label class="rt-field-label" for="modalStatus">Status</label>
                                <select class="rt-native" id="modalStatus">
                                    <option value="ALL">Semua</option>
                                    <option value="BELUM">Belum</option>
                                    <option value="SUDAH">Sudah</option>
                                </select>
                            </div>
                        </div>
                        <div class="rt-grid-2">
                            {{-- Sp_ReportOutSpbDet tidak punya parameter ini -- hanya berlaku
                                 di mode Non Outstanding. --}}
                            <div id="wrapTerima">
                                <label class="rt-field-label" for="modalTerima">Tgl. Terima</label>
                                <select class="rt-native" id="modalTerima">
                                    <option value="2">Semua</option>
                                    <option value="0">Tgl. Terima</option>
                                    <option value="1">Non Tgl. Terima</option>
                                </select>
                            </div>
                        </div>
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
        let globalDate1 = "{!! date('Y-m-d') !!}";
        let globalDate2 = "{!! date('Y-m-d') !!}";
        let globalOtorisasi = "2"; // default: Semua
        let globalOrderBy = "N"; // default: Nomor Bukti
        let globalTerima = "2"; // default: Semua
        let globalStatus = "ALL"; // default: Semua ("SUDAH" / "BELUM" = kolom outstanding)
        let globalMode = "0"; // "0" = Non Outstanding (Sp_ReportSPBDet), "1" = Outstanding (Sp_ReportOutSpbDet)

        let lastRows = []; // hasil fetch terakhir (dipakai render / export / search)
        let currentGroupby = 'NOBUKTI'; // groupby aktif untuk render ulang saat search

        // Offset mode report Outstanding supaya kolom tersimpan (DBSIMPANHEADER, dikunci per
        // href+reportmode) tidak bentrok dengan mode Non Outstanding di href yang sama.
        const OUT_MODE_OFFSET = 20;

        const reportUrlSpb = "{{ url('laporanmarketingspb_doReport') }}";
        const reportUrlOut = "{{ url('laporanmarketingoutsppb_doReport') }}";

        // Urutkan: Non Outstanding punya 3 opsi (masing-masing mengubah susunan kolom lewat
        // setModeReport()); Outstanding punya 6 (Sp_ReportOutSpbDet mengembalikan field yang
        // sama apa pun Ordr -- lihat komentar di setHeaderOut() -- jadi Ordr di sana hanya
        // mengubah currentGroupby/subtotal, bukan kolom).
        const ORDER_OPTIONS_SPB = [{
                value: 'N',
                label: 'Nomor Bukti',
                desc: 'Dikelompokkan per No Bukti'
            },
            {
                value: 'B',
                label: 'Nomor Barang',
                desc: 'Dikelompokkan per Barang'
            },
            {
                value: 'C',
                label: 'Nomor Customer',
                desc: 'Dikelompokkan per Customer'
            },
        ];
        const ORDER_OPTIONS_OUT = ORDER_OPTIONS_SPB.concat([{
                value: 'S',
                label: 'Sales',
                desc: 'Dikelompokkan per Sales'
            },
            {
                value: 'HG',
                label: 'Head Group',
                desc: 'Dikelompokkan per Head Group'
            },
            {
                value: 'P',
                label: 'PIC',
                desc: 'Dikelompokkan per PIC'
            },
        ]);

        // Objek ini sendiri yang dikirim ke ReportTable.init({ views }) -- disimpan di variabel
        // supaya renderOrderOptions() bisa menukar .options lalu panggil ReportTable.refresh()
        // saat ganti mode, BUKAN init() ulang (report-table.js membaca cfg.views by reference).
        let viewsCfg = {
            label: 'Order By',
            options: ORDER_OPTIONS_SPB,
            get: function() {
                return globalOrderBy;
            },
            set: function(v) {
                setOrderBy(String(v));
                if (lastRows.length) { makeTable('REPORT'); } // re-fetch: inputOrd adalah parameter SP
            }
        };

        // Menukar opsi switcher "Order By" di #rtBar sesuai mode. Kalau nilai globalOrderBy
        // saat ini tidak ada di daftar mode baru (mis. pindah dari Outstanding 'S'/'HG'/'P' ke
        // Non Outstanding), jatuhkan ke 'N' -- SP_REPORTSPBDet tidak punya kolom untuk itu.
        function renderOrderOptions() {
            const opts = (globalMode === '1') ? ORDER_OPTIONS_OUT : ORDER_OPTIONS_SPB;
            const valid = opts.some(o => o.value === globalOrderBy);
            if (!valid) {
                globalOrderBy = 'N';
            }
            viewsCfg.options = opts;
            if (typeof ReportTable !== 'undefined' && ReportTable.refresh) {
                ReportTable.refresh();
            }
        }

        $(document).ready(function() {
            setOtorisasi(globalOtorisasi);
            setTerima(globalTerima);
            setStatus(globalStatus);
            renderOrderOptions();
            setOrderBy(globalOrderBy);
            showPeriode();

            // Menu lama boleh mengarahkan ke /laporanmarketingspb?mode=out supaya langsung
            // terbuka di mode Outstanding (lihat rencana retire halaman lama).
            if ("{{ request('mode') }}" === "out") {
                $('#inputMode').val('1');
                setMode('1');
            }

            setDefaultHeader();

            // Header tabel interaktif + switcher "Order By" di #rtBar (Detail/Rekap dihapus).
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

        // otorisasi / tgl. terima: filter query, dibaca langsung oleh makeTable()
        function setOtorisasi(val) {
            globalOtorisasi = val;
        }

        function setTerima(val) {
            globalTerima = val;
        }

        // Status (kolom outstanding): filter sisi-klien murni, dibaca render() lewat
        // filterByStatus() -- tidak ada parameternya di Sp_ReportSPBDet.
        function setStatus(val) {
            globalStatus = val;
        }

        // Jenis laporan: "0" Non Outstanding (Sp_ReportSPBDet, dua tanggal) atau "1" Outstanding
        // (Sp_ReportOutSpbDet, HANYA satu tanggal, diambil dari #inputDate2 -- lihat komentar
        // di toolbar dan makeTable()).
        function setMode(val) {
            globalMode = val;
            const isOut = (val === '1');

            // #inputDate1 disembunyikan di mode Outstanding -- yang dipakai & dikirim adalah
            // #inputDate2 (lihat makeTable()). LaporanMarketingOutSPPBController TIDAK diubah
            // (permintaan eksplisit): dia hanya membaca request key `date1`, jadi nilai
            // #inputDate2 tetap dikirim dengan key itu apa adanya.
            $('#inputDate1').toggle(!isOut);
            $('#dateSep').toggle(!isOut);
            $('#periodeLabel').text(isOut ? 'Per Tanggal' : 'Periode');

            // Tgl. Terima (@tglterima) tidak ada di Sp_ReportOutSpbDet -- lewati di Outstanding.
            // Status juga: proc itu tidak mengembalikan kolom `outstanding` sama sekali, jadi
            // filternya disembunyikan dan dikembalikan ke Semua supaya tidak ikut terhitung
            // di badge "N aktif" maupun terpakai diam-diam saat balik ke Non Outstanding.
            $('#wrapTerima').toggle(!isOut);
            $('#wrapStatus').toggle(!isOut);
            if (isOut) {
                $('#modalTerima').val('2');
                setTerima('2');
                $('#modalStatus').val('ALL');
                setStatus('ALL');
            }

            renderOrderOptions();

            // Ganti mode tidak langsung fetch ulang -- tabel dikosongkan, user tekan Tampilkan.
            lastRows = [];
            currentGroupby = 'NOBUKTI';
            $('#tableBody').html('<tr class="empty-row"><td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>');
            $('#footerLabel').text('Belum ada data dimuat');

            setModeReport();
            updateFilterBadge();
        }

        // order by: ikut menentukan groupby & susunan kolom (lewat setModeReport)
        function setOrderBy(val) {
            globalOrderBy = val;
            setModeReport();
        }

        /* -- FILTER MODAL -- */

        function updateFilterBadge() {
            let count = 0;
            if ($('#modalOtorisasi').val() !== '2') {
                count++;
            }
            if ($('#modalTerima').val() !== '2') {
                count++;
            }
            if ($('#modalStatus').val() !== 'ALL') {
                count++;
            }
            $('#filterBadge').text(count + ' aktif');
        }

        function resetAllFilters() {
            $('#modalOtorisasi').val('2');
            $('#modalTerima').val('2');
            $('#modalStatus').val('ALL');
            updateFilterBadge();
        }

        $('#modalFilter').on('show.bs.modal', function() {
            $('#modalOtorisasi').val(globalOtorisasi);
            $('#modalTerima').val(globalTerima);
            $('#modalStatus').val(globalStatus);
            updateFilterBadge();
        });

        $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

        function applyModalFilter() {
            // Dibandingkan SEBELUM setter dipanggil: Tgl. Terima (@tglterima) adalah parameter
            // SP -- baris di lastRows tidak bisa disesuaikan di sisi klien. Kalau berubah,
            // tabel dibiarkan apa adanya sampai user menekan Tampilkan. Otorisasi & Status
            // keduanya punya filter sisi-klien, jadi bisa langsung dirender.
            const needRefetch = ($('#modalTerima').val() !== globalTerima);

            setOtorisasi($('#modalOtorisasi').val());
            setTerima($('#modalTerima').val());
            setStatus($('#modalStatus').val());

            $('#modalFilter').modal('hide');

            if (!needRefetch && lastRows.length) {
                render();
            }
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
                if (isBadgeCol(c)) return badgeText(c[0], v);
                if (c[3] === 'date') return format_date(v);
                if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
                return (v == null ? '' : v);
            }));
            const rows = [header].concat(body);
            ReportExport.fromRows(rows, fmt, { customName: (globalMode === '1') ? 'OutstandingSPPB_' + (globalDate2 || '') : 'LaporanSPB_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
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

        // NeedOtorisasi (bit di VwReportSpB): 0 = semua level otorisasi lengkap -> SUDAH,
        // 1 = masih butuh otorisasi -> BELUM. Nilai bisa datang sebagai 0/1, "0"/"1" atau
        // true/false, jadi dinormalkan ke string dulu. null/undefined -> '' (tanpa badge).
        function otorisasiText(v) {
            if (v == null || v === '') return '';
            return (String(v) === '1' || v === true) ? 'Belum' : 'Sudah';
        }

        // VwreportSPB.outstanding = isnull(h.QNT,0) dari dbInvoicePLDet (join NoSPB+UrutSPB),
        // jadi isinya qty baris SPB yang SUDAH masuk invoice -- bukan sisa. > 0 = SUDAH
        // (hijau), 0/kosong = BELUM (merah). Selalu ada badge (view sudah isnull -> 0), beda
        // dengan NeedOtorisasi yang boleh kosong. Hanya dipakai di mode Non Outstanding:
        // Sp_ReportOutSpbDet tidak mengembalikan kolom ini.
        function outstandingText(v) {
            return (currencyNormalizer(v) > 0) ? 'Sudah' : 'Belum';
        }

        // Kolom badge (tipe 'bool') dipakai dua arti berbeda -> dispatch lewat nama kolom,
        // supaya render(), export, dan pencarian memakai teks yang sama persis.
        function badgeText(key, v) {
            return (String(key).toLowerCase() === 'outstanding') ? outstandingText(v) : otorisasiText(v);
        }

        function isBadgeCol(c) {
            return c[3] === 'bool' || c[0] === 'NeedOtorisasi';
        }

        // Satuan & QTY gabungan. Kedua proc sama-sama mengembalikan NOSAT + SAT_1/SAT_2 dan
        // sepasang kolom qty (SPB: QNT/QNT2, Outstanding: QntOut1/QntOut2), tapi TIDAK
        // mengembalikan kolom jadi -- jadi dirakit di sini sekali setelah fetch (bukan di
        // render()) supaya render, subtotal, pencarian, dan export membaca field yang sama.
        // NOSAT=1 -> satuan/qty pertama; NOSAT 2 atau 3 -> kedua, mengikuti VwreportSPB
        // (NBerat: "when nosat=3 then Qnt2") dan HAVING di Sp_ReportOutSpbDet ("NOSAT IN (2,3)").
        // Nilai lain (0/null) jatuh ke satuan pertama supaya qty tidak hilang dari total.
        function decorateRows(rows, isOut) {
            const q1 = isOut ? 'QntOut1' : 'QNT';
            const q2 = isOut ? 'QntOut2' : 'QNT2';

            (rows || []).forEach(function(r) {
                const nosat = String(nullToEmpty(pickCI(r, 'NOSAT')));
                const pakaiSat2 = (nosat === '2' || nosat === '3');

                r.Satuan = nullToEmpty(pickCI(r, pakaiSat2 ? 'SAT_2' : 'SAT_1'));
                r.QTY = pickCI(r, pakaiSat2 ? q2 : q1);
            });

            return rows || [];
        }

        function pickCI(r, key) {
            if (r[key] !== undefined) return r[key];
            const lk = String(key).toLowerCase();
            for (const k in r) {
                if (k.toLowerCase() === lk) return r[k];
            }
            return undefined;
        }

        // Detail/Rekap dihapus -- Rekap dulu punya kolom berbeda (real feature: mis. detail
        // No Bukti punya NoPOCustomer/namaGdg/TGLKIRIM/TGLTERIMA yang tidak ada di rekap), tapi
        // sengaja dibuang. Order By (N/B/C) tetap punya 3 susunan kolom NYATA berbeda di SPB
        // (lihat setHeaderSpb()) -- itu tidak disamakan, sesuai instruksi: hanya pindah UI-nya
        // ke switcher "Order By" di #rtBar, bukan menyatukan kolomnya.
        var modereport_nobukti = 0,
            modereport_barang = 1,
            modereport_customer = 2;
        g_modeReport = modereport_nobukti;

        // Dispatcher: kedua SP punya set kolom & penomoran mode yang berbeda total (SPB 0-2,
        // Outstanding tidak bercabang sama sekali -- lihat setHeaderOut()) -- tetap dipisah
        // jadi dua fungsi supaya g_modeReport (dengan offset) tidak salah dibaca.
        function setDefaultHeader() {
            if (globalMode === '1') {
                setHeaderOut();
            } else {
                setHeaderSpb(g_modeReport);
            }
        }

        function setHeaderSpb(base) {
            if (base == modereport_nobukti) {
                gcart_header = [
                    ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
                    ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                    ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
                    ['NoPOCustomer', 'No. PO Customer', 1, 'varchar', 0, 0],
                    ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
                    ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
                    ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
                    ['QTY', 'QTY', 1, 'float', 1, 0],
                    ['namaGdg', 'Nama Gudang', 1, 'varchar', 0, 0],
                    ['TGLKIRIM', 'Tanggal Kirim', 1, 'date', 0, 0],
                    ['TGLTERIMA', 'Tanggal Terima', 1, 'date', 0, 0],
                    // ['NBerat', 'Berat/Volume', 1, 'float', 1, 2],
                    ['outstanding', 'Status', 1, 'bool', 0, 0],
                    ['NeedOtorisasi', 'Otorisasi', 1, 'bool', 0, 0]
                ];
                gsum_issubtotal = 1;
                gsum_isgrandtotal = 1;

            } else if (base == modereport_barang) {
                gcart_header = [
                    ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
                    ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                    ['KodeCustSupp', 'Kode Customer', 1, 'varchar', 0, 0],
                    ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
                    ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
                    ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
                    ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
                    ['QTY', 'QTY', 1, 'float', 1, 0],
                    ['NetW', 'Net W', 1, 'float', 1, 2],
                    ['GrossW', 'Gross W', 1, 'float', 1, 2],
                    // ['HARGA', 'Harga', 1, 'float', 1, 2],
                    ['outstanding', 'Status', 1, 'bool', 0, 0],
                    ['NeedOtorisasi', 'Otorisasi', 1, 'bool', 0, 0]
                ];
                gsum_issubtotal = 1;
                gsum_isgrandtotal = 1;

            } else {
                gcart_header = [
                    ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
                    ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                    ['KodeCustSupp', 'Kode Customer', 1, 'varchar', 0, 0],
                    ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
                    ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
                    ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
                    ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
                    ['QTY', 'QTY', 1, 'float', 1, 0],
                    ['NetW', 'Net W', 1, 'float', 1, 2],
                    ['GrossW', 'Gross W', 1, 'float', 1, 2],
                    // ['HARGA', 'Harga', 1, 'float', 1, 2],
                    ['outstanding', 'Status', 1, 'bool', 0, 0],
                    ['NeedOtorisasi', 'Otorisasi', 1, 'bool', 0, 0]
                ];
                gsum_issubtotal = 1;
                gsum_isgrandtotal = 1;
            }
        }

        // Kolom Outstanding (Sp_ReportOutSpbDet) -- asalnya dari reportmarketingoutsppb.blade.php,
        // dengan Qty 1/Qty 2 diganti Satuan + QTY gabungan (lihat decorateRows(); proc ini juga
        // mengembalikan NOSAT/SAT_1/SAT_2). Kolom Outstanding (badge) TIDAK ada di sini -- itu
        // milik VwreportSPB, mode Non Outstanding. Proc ini mengembalikan field yang sama apa
        // pun Ordr (Detail/Rekap sudah dihapus juga) -- jadi satu susunan kolom saja, tidak
        // ada percabangan.
        function setHeaderOut() {
            gcart_header = [
                ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
                ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                ['kodeCustSupp', 'Kode', 1, 'varchar', 0, 0],
                ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
                ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
                ['Namabrg', 'Nama Barang', 1, 'varchar', 0, 0],
                ['NOPOCUstomer', 'No. PO. Cust', 1, 'varchar', 0, 0],
                ['NoSo', 'No. SO', 1, 'varchar', 0, 0],
                ['TanggalSO', 'Tgl. SO', 1, 'date', 0, 0],
                ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
                ['QTY', 'QTY', 1, 'float', 1, 0],
                // ['HARGA', 'Harga', 1, 'float', 1, 0],
                // ['NDPPRPZX', 'Total', 1, 'float', 1, 0],
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
            let input_order = globalOrderBy;
            let inputTerima = globalTerima;
            const isOut = (globalMode === '1');

            // Kolom yang dikembalikan dua proc ini beda casing (mis. NOBUKTI vs NoBukti,
            // KodeCustSupp vs kodeCustSupp) -- groupby (dibaca render() sebagai r[currentGroupby]
            // apa adanya) harus mengikuti casing masing-masing proc, bukan satu tabel bersama.
            if (isOut) {
                if (input_order == "N") {
                    groupby = 'NoBukti';
                } else if (input_order == "B") {
                    groupby = 'KodeBrg';
                } else if (input_order == "S") {
                    groupby = 'KodeSls';
                } else {
                    groupby = 'kodeCustSupp';
                }
            } else {
                if (input_order == "N") {
                    groupby = 'NOBUKTI';
                } else if (input_order == "B") {
                    groupby = 'KODEBRG';
                } else {
                    groupby = 'KodeCustSupp';
                }
            }

            setDefaultHeader();
            if (typeof doSetHeader === 'function') {
                doSetHeader(g_modeReport);
            }

            // Sp_ReportOutSpbDet tidak punya parameter @tglterima. Cuma satu tanggal yang
            // dipakai di mode Outstanding, dan itu diambil dari #inputDate2 (bukan #inputDate1
            // -- lihat setMode()). LaporanMarketingOutSPPBController TIDAK diubah: dia hanya
            // membaca request key `date1`, jadi _date2 (nilai #inputDate2) tetap dikirim
            // dengan key `date1` apa adanya -- JANGAN ganti key ini jadi `date2`.
            let url, data;
            if (isOut) {
                url = reportUrlOut;
                data = {
                    date1: _date2,
                    inputOto: inputOto,
                    inputOrd: input_order,
                };
            } else {
                url = reportUrlSpb;
                data = {
                    date1: _date1,
                    date2: _date2,
                    inputOto: inputOto,
                    inputOrd: input_order,
                    inputTerima: inputTerima
                };
            }

            document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

            $.ajax({
                url: url,
                type: 'get',
                data: data,
                success: function(res) {
                    lastRows = decorateRows(res || [], isOut);
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

        // Filter langsung di client (ga ke sp) berdasarkan pilihan #modalOtorisasi.
        // Nilainya sama dengan kolom NeedOtorisasi: 0 = Sudah Otorisasi, 1 = Belum Otorisasi,
        // 2/lainnya = Semua. Sp_ReportSPBDet juga sudah memfilter (NeedOtorisasi = @NeedOto,
        // 2 = semua), jadi filter ini hanya jaring kedua supaya tabel tetap konsisten dengan
        // pilihan filter walau data di lastRows dimuat sebelum filter diganti.
        function filterByOtorisasi(rows, filterVal) {
            switch (String(filterVal)) {
                case '0':
                    return rows.filter(r => otorisasiText(pickCI(r, 'NeedOtorisasi')) === 'Sudah');
                case '1':
                    return rows.filter(r => otorisasiText(pickCI(r, 'NeedOtorisasi')) === 'Belum');
                default:
                    return rows;
            }
        }

        // Status (kolom `outstanding`): TIDAK ada parameternya di Sp_ReportSPBDet, jadi ini
        // satu-satunya tempat filternya bekerja -- bukan jaring kedua seperti filterByOtorisasi.
        // Pakai outstandingText() supaya cocok persis dengan badge yang tampil ('Sudah'/'Belum').
        function filterByStatus(rows, filterVal) {
            switch (String(filterVal)) {
                case 'SUDAH':
                    return rows.filter(r => outstandingText(pickCI(r, 'outstanding')) === 'Sudah');
                case 'BELUM':
                    return rows.filter(r => outstandingText(pickCI(r, 'outstanding')) === 'Belum');
                default:
                    return rows;
            }
        }

        // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
        // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat / item[2]===1,
        // sesuai urutan simpanan) -> mode-agnostic, jadi tiap mode report (item[4]===1 menandai
        // kolom yang di-subtotal) langsung terpakai tanpa daftar kolom hardcode.
        function render() {
            const cols = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
            const keys = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom yang di-subtotal
            const thead = document.querySelector('#mainTable thead');
            const tbody = document.getElementById('tableBody');
            const showSub = (gsum_issubtotal === 1);
            const showGrand = (gsum_isgrandtotal === 1);

            const search = ($('#searchBox2').val() || '').trim().toLowerCase();
            const searched = !search ? (lastRows || []) : (lastRows || []).filter(function(r) {
                return rowSearchText(r, cols).indexOf(search) !== -1;
            });
            // pakai globalOtorisasi (nilai yang sudah di-Terapkan), BUKAN nilai select modal:
            // kalau user mengubah dropdown lalu menekan Batal, select tetap memegang nilai
            // yang dibatalkan itu dan akan ikut terpakai di render berikutnya (mis. saat cari).
            // Outstanding: lewati filter ini -- baris Sp_ReportOutSpbDet tidak punya kolom
            // NeedOtorisasi, jadi otorisasiText(undefined) selalu '' dan tidak cocok 'Sudah'
            // atau 'Belum' (proc sudah memfilter sendiri lewat parameter inputOto).
            // Status ikut dilewati di Outstanding untuk alasan yang sama: kolom `outstanding`
            // milik VwreportSPB, tidak ada di hasil Sp_ReportOutSpbDet.
            const rows = (globalMode === '1') ?
                searched :
                filterByStatus(filterByOtorisasi(searched, globalOtorisasi), globalStatus);

            // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
            // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
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
                    // NeedOtorisasi = 0 berarti sudah otorisasi (hijau), 1 = belum (merah);
                    // outstanding > 0 = sudah masuk invoice (hijau), 0 = belum (merah)
                    if (isBadgeCol(c)) {
                        const txt = badgeText(key, pickCI(r, key));
                        if (!txt) return '<td></td>';
                        const cls = (txt === 'Sudah') ? 'is-active' : 'is-inactive';
                        return '<td><span class="sp-badge ' + cls + '">' + txt + '</span></td>';
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
                // kolom badge dicari lewat teksnya ("sudah"/"belum"), bukan nilai mentah 0/1
                if (isBadgeCol(c)) return badgeText(c[0], v);
                if (c[3] === 'date') return format_date(v);
                return (v == null ? '' : String(v));
            }).join(' ').toLowerCase();
        }

        function setModeReport() {
            if (globalMode === '1') {
                // Sp_ReportOutSpbDet mengembalikan field yang sama apa pun Ordr -- satu slot
                // saja, digeser OUT_MODE_OFFSET supaya tidak bentrok dengan kolom tersimpan
                // mode SPB (DBSIMPANHEADER dikunci per href+reportmode, href-nya sama).
                g_modeReport = OUT_MODE_OFFSET;
            } else if (globalOrderBy == "N") {
                g_modeReport = modereport_nobukti;
            } else if (globalOrderBy == "B") {
                g_modeReport = modereport_barang;
            } else {
                g_modeReport = modereport_customer;
            }

            doSetHeader(g_modeReport);
            doShowCustomize();
        }
    </script>
@endsection
