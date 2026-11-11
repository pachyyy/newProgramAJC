@extends('report.masterreport2')

<style>
    .tb-report .table-wrap {
        min-height: 10vh;
    }
</style>

@include('report.modalMarketingHistorySO')

@section('header2')
    <div class="tb-report main">
        <div class="content">

            <!-- TOOLBAR -->
            <div class="toolbar">
                {{-- <div>
                    <div class="page-title">History SO</div>
                </div> --}}

                <!-- Jenis laporan: Non Outstanding (dua tanggal, ke SP_REPORTHISSO) atau
                     Outstanding (tgl1 dipatok di controller, hanya tgl2 dikirim, ke
                     SP_REPORTHISSOOUTweb). -->
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

            <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
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
                        <div class="rt-group-label">Pengaturan Laporan</div>
                        <div class="rt-grid-1">
                            {{-- SP_REPORTHISSOOUTweb tidak punya parameter ini -- hanya berlaku
                                 di mode Non Outstanding. --}}
                            <div id="wrapOtorisasi">
                                <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
                                <select class="rt-native" id="modalOtorisasi">
                                    <option value="0">Outstanding</option>
                                    <option value="1">Terpenuhi</option>
                                    <option value="2">Semua</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="rt-section">
                        <div class="rt-group-label">Filter Data
                            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
                        </div>
                        <div class="rt-grid-2" id="pickFields"></div>

                        {{-- Nilai sebenarnya (dibaca makeTable() & ditulis buttonPilih()) --}}
                        <input type="hidden" id="inputCustomer" value="-">
                        <input type="hidden" id="inputLokasi" value="-">
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
        window.g_pickerV2 = true;
        let globalDate1 = "{!! date('Y-m-d') !!}";
        let globalDate2 = "{!! date('Y-m-d') !!}";
        let globalOtorisasi = "2"; // default: Semua
        let globalOrderBy = "N"; // default: Nomor Bukti (SP_REPORTHISSO tidak punya parameter order)
        let globalMode = "0"; // "0" = Non Outstanding (SP_REPORTHISSO), "1" = Outstanding (SP_REPORTHISSOOUTweb)

        let lastRows = []; // hasil fetch terakhir (dipakai render / export / search)
        let currentGroupby = 'NOBUKTI'; // groupby aktif untuk render ulang saat search

        // Offset mode report Outstanding supaya kolom tersimpan (DBSIMPANHEADER, dikunci per
        // href+reportmode) tidak bentrok dengan mode Non Outstanding di href yang sama.
        const OUT_MODE_OFFSET = 20;

        const reportUrlHis = "{{ url('laporanmarketinghistoryso_doReport') }}";
        const reportUrlOut = "{{ url('laporanmarketinghistoryoutso_doReport') }}";

        $(document).ready(function() {
            setOtorisasi(globalOtorisasi);
            setOrderBy(globalOrderBy);
            showPeriode();

            // Menu lama boleh mengarahkan ke /laporanmarketinghistoryso?mode=out supaya
            // langsung terbuka di mode Outstanding (lihat rencana retire halaman lama).
            if ("{{ request('mode') }}" === "out") {
                $('#inputMode').val('1');
                setMode('1');
            }

            setDefaultHeader();

            // Tidak ada switcher (Detail/Rekap dihapus, Order By tidak punya UI di halaman
            // ini) -- #rtBar cuma menampilkan kolom tersembunyi + Reset kolom.
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

        // otorisasi (0 = Outstanding, 1 = Terpenuhi, 2 = Semua) -> parameter @penuh SP_REPORTHISSO
        function setOtorisasi(val) {
            globalOtorisasi = val;
        }

        // Jenis laporan: "0" Non Outstanding (SP_REPORTHISSO, dua tanggal) atau
        // "1" Outstanding (SP_REPORTHISSOOUTweb, tgl1 dipatok di controller).
        function setMode(val) {
            globalMode = val;
            const isOut = (val === '1');

            // tgl1 tidak dikirim di mode Outstanding -- controller sudah mematoknya sendiri
            // (fallback ke 2019-01-01, lihat LaporanMarketingHistoryOutSOController@doReport).
            $('#inputDate1').toggle(!isOut);
            $('#dateSep').toggle(!isOut);
            $('#periodeLabel').text(isOut ? 'Per Tanggal' : 'Periode');

            // Otorisasi (@penuh) tidak ada di SP_REPORTHISSOOUTweb -- lewati di Outstanding.
            // Filter Data (Customer/Lokasi) TETAP tampil: SP_REPORTHISSOOUTweb menerimanya.
            $('#wrapOtorisasi').toggle(!isOut);

            if (isOut) {
                $('#modalOtorisasi').val('2');
                setOtorisasi('2');
                // Dropdown atas menggantikan select "Outstanding" bawaan OutSO (isout) --
                // paksa isout=0 di makeTable().
            }

            // Ganti mode tidak langsung fetch ulang -- tabel dikosongkan, user tekan Tampilkan.
            lastRows = [];
            currentGroupby = 'NOBUKTI';
            $('#tableBody').html('<tr class="empty-row"><td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>');
            $('#footerLabel').text('Belum ada data dimuat');

            setModeReport();
            updateFilterBadge();
        }

        // order by
        function setOrderBy(val) {
            globalOrderBy = val;
            setModeReport();
        }

        /* -- FILTER MODAL -- */

        // Dua field "Filter Data" (Customer/Lokasi): nilai sebenarnya tetap di input hidden
        // #inputXxx (dibaca makeTable(), ditulis buttonPilih() di modalMarketingHistorySO.blade.php)
        // — kotak .rt-combo di bawah ini hanyalah tampilan di atasnya.
        const PICK_FIELDS = [{
                id: 'inputCustomer',
                label: 'Cust/Supp',
                modal: 'selectCustomer'
            },
            {
                id: 'inputLokasi',
                label: 'Lokasi',
                modal: 'selectLokasi'
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

        // Otorisasi ikut dihitung (punya nilai netral "Semua" = '2', beda dengan Outstanding
        // di reportmarketinghistoryoutso yang wajib pilih salah satu tanpa opsi netral).
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
            $('#filterBadge').text(count + ' aktif');
        }

        function resetAllFilters() {
            $('#modalOtorisasi').val('2');
            PICK_FIELDS.forEach(function(f) {
                $('#' + f.id).val('-');
            });
            renderPickFields();
            updateFilterBadge();
        }

        $('#modalFilter').on('show.bs.modal', function() {
            $("#modalOtorisasi").val(globalOtorisasi);
            renderPickFields();
            updateFilterBadge();
        });

        $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

        function applyModalFilter() {
            setOtorisasi($("#modalOtorisasi").val());

            $('#modalFilter').modal('hide');
        }

        // Jembatan ke modal pilih entitas (#formSelect di modalMarketingHistorySO.blade.php):
        // sembunyikan modal Filter dulu (hindari Bootstrap stacked-modal), lalu buka lagi
        // setelah modal pilih ditutup (buttonPilih() di include tsb sudah hide #formSelect).
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
            const osMap = (globalMode === '1') ? buildOutstandingMap(lastRows) : null;
            const body = (lastRows || []).map(r => cols.map(function(c) {
                if (c[0] === 'OUTSTANDING') return osMap ? outstandingStatus(osMap, r).label : '';
                const v = pickCI(r, c[0]);
                if (c[3] === 'date') return format_date(v);
                if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
                return (v == null ? '' : v);
            }));
            const rows = [header].concat(body);
            ReportExport.fromRows(rows, fmt, { customName: (globalMode === '1') ? 'OutstandingHistorySO_' + (globalDate2 || '') : 'HistorySO_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
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

        // Kolom OUTSTANDING (badge Belum/Sebagian/Terkirim) hanya ada di mode Outstanding dan
        // dihitung sisi-klien -- diambil apa adanya dari reportmarketinghistoryoutso.blade.php.

        // Kunci grup NOBUKTI dinormalkan: kolom CHAR(n) SQL Server sering ada padding spasi.
        function osKey(v) {
            return String(v == null ? '' : v).trim();
        }

        // Peta NOBUKTI -> { sum: total QNTSPB se-NOBUKTI, qntSO: QNTSO baris PERTAMA grup itu }.
        // Dihitung dari SELURUH data termuat (lastRows), bukan hasil search/filter, supaya
        // status satu SO tidak berubah hanya karena sebagian barisnya tersembunyi saat dicari.
        function buildOutstandingMap(rows) {
            const m = {};
            (rows || []).forEach(function(r) {
                const k = osKey(pickCI(r, 'NOBUKTI'));
                if (!k) return;
                if (!(k in m)) m[k] = {
                    sum: 0,
                    qntSO: currencyNormalizer(pickCI(r, 'QNTSO'))
                };
                m[k].sum += currencyNormalizer(pickCI(r, 'QNTSPB'));
            });
            return m;
        }

        // { label, cls } untuk satu baris, berdasarkan peta di atas.
        // sum <= 0 -> Belum; sum >= QNTSO -> Terkirim (>= dipakai, bukan ==, supaya
        // over-delivery dan pembulatan float tidak salah jadi "Sebagian").
        function outstandingStatus(map, r) {
            const g = map[osKey(pickCI(r, 'NOBUKTI'))];
            if (!g || g.sum <= 0) return {
                label: 'Belum',
                cls: 'is-inactive'
            };
            if (g.sum >= g.qntSO) return {
                label: 'Terkirim',
                cls: 'is-active'
            };
            return {
                label: 'Sebagian',
                cls: 'is-supervisor'
            };
        }

        // Satu slot -- Detail/Rekap dihapus (Rekap dulu punya kolom DPP/PPN/Total ringkasan,
        // real feature, tapi sengaja dibuang) dan Order By tidak punya UI di halaman ini
        // (globalOrderBy selalu "N", lihat setModeReport()).
        var modereport_nobukti = 0;
        g_modeReport = modereport_nobukti;

        // Dispatcher: Detail No Bukti beda antara Non Outstanding dan Outstanding (kolom
        // OUTSTANDING dkk.), jadi tetap dua fungsi.
        function setDefaultHeader() {
            if (globalMode === '1') {
                setHeaderOut();
            } else {
                setHeaderHis();
            }
        }

        function setHeaderHis() {
            gcart_header = [
                ['NOBUKTI', 'No. Bukti', 1, 'varchar', 0, 0],
                ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
                ['NoPesanan', 'No. PO. Cust', 1, 'varchar', 0, 0],
                ['NamaSLS', 'Sales', 1, 'varchar', 0, 0],
                ['NAMACUSTSUPP', 'Nama Cust', 1, 'varchar', 0, 0],
                ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
                ['QNT', 'Qty SO', 1, 'float', 1, 0],
                ['NOBUKTIPO', 'No. PO', 1, 'varchar', 0, 0],
                ['TGLPO', 'Tgl. PO', 1, 'date', 0, 0],
                ['NOBUKTISPB', 'No. SPB', 1, 'varchar', 0, 0],
                ['TGLSPB', 'Tgl. SPB', 1, 'date', 0, 0],
                ['QNTSPB', 'Qty SPB', 1, 'float', 1, 0],
                ['TGLKIRIM', 'Tgl. Kirim', 1, 'date', 0, 0],
                ['LeadTime', 'L. Time', 1, 'date', 0, 0],
                ['NOBINV', 'No. Inv', 1, 'varchar', 0, 0],
                ['TGINV', 'Tgl. Inv', 1, 'date', 0, 0],
                ['QNTINV', 'Qty Inv', 1, 'float', 1, 2],
                ['NORSPB', 'No. RSPB', 1, 'varchar', 0, 0],
                ['TGLRSPB', 'Tgl. RSPB', 1, 'date', 0, 0],
                ['QNTRSPB', 'Qty RSPB', 1, 'float', 1, 0],
                ['SLISIHPOSPB', 'Sls PO-SPB', 1, 'float', 1, 2],
                ['NNETRP', 'SLS-SPB', 1, 'float', 1, 2]
            ];
            gsum_issubtotal = 1;
            gsum_isgrandtotal = 1;
        }

        // Kolom Outstanding (SP_REPORTHISSOOUTweb) -- diambil apa adanya dari
        // reportmarketinghistoryoutso.blade.php.
        function setHeaderOut() {
            gcart_header = [
                ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
                ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
                ['NoPesanan', 'No. PO Cust', 1, 'varchar', 0, 0],
                ['NAMACUSTSUPP', 'Nama Cust', 1, 'varchar', 0, 0],
                ['NAMAGROUPCUSTSUPP', 'Nama Group', 1, 'varchar', 0, 0],
                ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
                ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
                ['HARGA', 'Harga', 1, 'float', 0, 0],
                ['QNTSO', 'Qty SO', 1, 'float', 1, 0],
                ['NOBUKTISPB', 'No. SPB', 1, 'varchar', 0, 0],
                ['TGLSPB', 'Tgl. SPB', 1, 'date', 0, 0],
                ['QNTSPB', 'Qty SPB', 1, 'float', 1, 0],
                ['NOBINV', 'No. Inv', 1, 'varchar', 0, 0],
                ['TGLINV', 'Tgl. Inv', 1, 'date', 0, 0],
                ['QNTINV', 'Qty Inv', 1, 'float', 1, 0],
                ['NORSPB', 'No. RSPB', 1, 'varchar', 0, 0],
                ['TGLRSPB', 'Tgl. RSPB', 1, 'date', 0, 0],
                ['QNTRSPB', 'Qty RSPB', 1, 'float', 1, 0],
                ['sTOCK', 'Stock Tgl. SO', 1, 'float', 1, 0],
                ['QNTBATAL', 'Batal SO', 1, 'float', 1, 0],
                ['OSSO', 'Sisa SO-SPB', 1, 'float', 1, 0],
                ['OUTSTANDING', 'Outstanding', 1, 'varchar', 0, 0],
            ];
            gsum_issubtotal = 1;
            gsum_isgrandtotal = 1;
        }

        function makeTable(_mode) {
            let _date1 = $("#inputDate1").val();
            let _date2 = $("#inputDate2").val();
            let _inputCustomer = $("#inputCustomer").val();
            let _inputLokasi = $("#inputLokasi").val();
            let inputOto = globalOtorisasi;
            let input_order = globalOrderBy;

            // Satu-satunya groupby yang pernah kepakai -- Order By tidak punya UI di halaman
            // ini (globalOrderBy selalu "N", lihat setModeReport()).
            let groupby = 'NOBUKTI';

            setDefaultHeader();
            if (typeof doSetHeader === 'function') {
                doSetHeader(g_modeReport);
            }

            const isOut = (globalMode === '1');

            // SP_REPORTHISSOOUTweb tidak punya parameter @penuh -- LaporanMarketingHistoryOutSOController
            // sudah mematok tgl1 sendiri saat date1 tidak dikirim (lihat @doReport). Dropdown atas
            // menggantikan select "Outstanding" bawaan halaman lama, jadi isout selalu dipaksa '0'.
            let url, data;
            if (isOut) {
                url = reportUrlOut;
                data = {
                    date2: _date2,
                    inputCustomer: _inputCustomer,
                    inputLokasi: _inputLokasi,
                    isout: '0',
                };
            } else {
                url = reportUrlHis;
                data = {
                    date1: _date1,
                    date2: _date2,
                    inputCustomer: _inputCustomer,
                    inputLokasi: _inputLokasi,
                    inputOto,
                    inputOrd: input_order,
                };
            }

            document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

            $.ajax({
                url: url,
                type: 'get',
                data: data,
                success: function(res) {
                    lastRows = res || [];
                    currentGroupby = groupby;
                    $('#searchBox2').val('');
                    render();
                },
                error: function(xhr) {
                    console.error((isOut ? 'laporanmarketinghistoryoutso_doReport' : 'laporanmarketinghistoryso_doReport') + ' gagal:', xhr.status, xhr.responseText);
                    showToast('⚠️', 'Gagal memuat data (' + xhr.status + ')');
                    lastRows = [];
                    currentGroupby = groupby;
                    render();
                }
            });
        }

        // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
        // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat / item[2]===1,
        // sesuai urutan simpanan) -> mode-agnostic, jadi tiap mode report (item[4]===1
        // menandai kolom yang di-subtotal) langsung terpakai tanpa daftar kolom hardcode.
        function render() {
            const cols = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
            const keys = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom yang di-subtotal
            const thead = document.querySelector('#mainTable thead');
            const tbody = document.getElementById('tableBody');
            const showSub = (gsum_issubtotal === 1);
            const showGrand = (gsum_isgrandtotal === 1);

            // Kolom OUTSTANDING hanya ada di mode Outstanding -- peta dihitung dari SELURUH data
            // termuat (lastRows), bukan hasil search, supaya status tidak berubah saat dicari.
            const osMap = (globalMode === '1') ? buildOutstandingMap(lastRows) : null;

            const search = ($('#searchBox2').val() || '').trim().toLowerCase();
            const rows = !search ? (lastRows || []) : (lastRows || []).filter(function(r) {
                return rowSearchText(r, cols, osMap).indexOf(search) !== -1;
            });

            // HEADER dinamis dari gcart_header — dibangun report-table.js (ReportTable) supaya
            // kolom bisa diseret untuk diurutkan & punya menu roda gigi (sembunyikan / desimal
            // / total). Juga menyegarkan #rtBar (daftar kolom tersembunyi).
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
                    if (key === 'OUTSTANDING') {
                        const st = outstandingStatus(osMap, r);
                        return '<td><span class="sp-badge ' + st.cls + '">' + st.label + '</span></td>';
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
        // osMap opsional: kalau ada, kolom OUTSTANDING ikut dicocokkan dengan label statusnya.
        function rowSearchText(r, cols, osMap) {
            return cols.map(function(c) {
                if (c[0] === 'OUTSTANDING') return osMap ? outstandingStatus(osMap, r).label : '';
                const v = pickCI(r, c[0]);
                if (c[3] === 'date') return format_date(v);
                return (v == null ? '' : String(v));
            }).join(' ').toLowerCase();
        }

        // Order By tidak ditampilkan di halaman ini: SP_REPORTHISSO tak punya parameter
        // urutan, jadi globalOrderBy selalu 'N'. Detail/Rekap juga sudah dihapus -- hanya
        // satu mode (Detail No Bukti) yang pernah dipakai.
        function setModeReport() {
            g_modeReport = modereport_nobukti;

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
