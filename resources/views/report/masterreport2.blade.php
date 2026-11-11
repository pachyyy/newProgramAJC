@extends('report.newmaster2x')
<!-- Font Awesome CDN -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
rel="stylesheet">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap JS (include Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@section('buttons')
@endsection
@section('content')
@yield('header2')
<div class="container-fluid">
  <div class="row">
    <div class="col-6 text-left">
      @yield('reportname')
    </div>
  </div>
</div>

<div id="printContainer" style="display:none">

</div>
<div id="contentContainer" class="container-fluid" style="margin-top:-50px;">

        <div class="row">

        </div>
        {{-- <div class="row justify-content-center">
          <div class="card w-75">
            <div class="card-body">
              <div class="container-fluid">

                @yield('input')

                <br>
                <br>

                <div class="row pr-3" style="display: flex; justify-content: right;">
                  <button type="button" class="btn btn-primary" style="
                  height: 30px;
                  padding: 4px 12px;
                  border-radius: 20px;
                  font-size: 0.75rem;
                  margin-right: 8px;
                  font-weight: 600;
                  text-transform: uppercase;
                  transition: background-color 0.3s, box-shadow 0.3s;
                  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doShowFormFilterData()">Filter Data</button>
                  <button type="button" class="btn btn-primary" style="
                  height: 30px;
                  padding: 4px 12px;
                  border-radius: 20px;
                  font-size: 0.75rem;
                  font-weight: 600;
                  margin-right: 8px;
                  text-transform: uppercase;
                  transition: background-color 0.3s, box-shadow 0.3s;
                  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doShowFormCustomizeTable()">Customize Table</button>
                  <button type="button" class="btn btn-primary" style="
                  height: 30px;
                  padding: 4px 12px;
                  border-radius: 20px;
                  font-size: 0.75rem;
                  font-weight: 600;
                  margin-right: 8px;
                  text-transform: uppercase;
                  transition: background-color 0.3s, box-shadow 0.3s;
                  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="makeTable('REPORT')">Submit</button>
                </div>

              </div>
            </div>
          </div>
        </div> --}}

        <div class="container-fluid">
          <div id="showTableReport" style="display:none; background-color: white; padding: 10px" class="row mt-4 rounded">
            <div class="col-12 text-right">
              <button type="button" class="btn btn-success" style="
                height: 30px;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                transition: background-color 0.3s, box-shadow 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doExportTableToExcel('tabel')">Export to Excel</button>
              <button type="button" class="btn btn-success" style="
                height: 30px; 
                padding: 4px 12px; 
                border-radius: 20px; 
                font-size: 0.75rem; 
                font-weight: 600; 
                text-transform: uppercase; 
                transition: background-color 0.3s, box-shadow 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doExportTableToCsv('tabel')">Export to CSV</button>
              <button type="button" class="btn btn-danger" style="
                height: 30px;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                transition: background-color 0.3s, box-shadow 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doCloseTable()">Close Table</button>
            </div>
            <div class="col-12 mt-4" style="overflow:auto;">
              <div class="">
                <table id="tabel" class="table table-bordered">

                  <thead id="tabel_header" class="text-left" >
                  </thead>

                  <tbody id="tabel_data" class="text-center bg-dark text-white"  style="border: 1px solid black; text-align: center;">
                  </tbody>

                </table>
              </div>
            </div>
          </div>
        </div>


        <!-- start modal customize table -->
        <div class="modal fade ct-modal" id="formCustomizeTable" tabindex="-1" role="dialog" aria-labelledby="formCustomizeTableLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

              <div class="modal-header">
                <div>
                  <h5 class="modal-title" id="formCustomizeTableLabel">Atur Kolom</h5>
                  <p class="ct-sub">Seret baris untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada kolom angka untuk atur desimal &amp; total.</p>
                </div>
                <button type="button" class="ct-close" data-dismiss="modal" aria-label="Close" onclick="doCloseFormCustomizeTable()" title="Tutup (Esc)">&times;</button>
              </div>

              <div class="ct-preview">
                <div class="ct-preview-label">
                  <span class="ct-dot"></span>Urutan tabel saat ini
                  <span class="ct-count" id="ct_count">0 kolom</span>
                </div>
                <div class="ct-chips" id="ct_chips"></div>
              </div>

              <div class="ct-search">
                <div class="ct-search-box">
                  <i class="bi bi-search"></i>
                  <input type="text" id="ct_search" placeholder="Cari kolom..." autocomplete="off" oninput="doShowCustomize()">
                </div>
              </div>

              <div class="modal-body">
                <div id="tabelcustomize_data"></div>
              </div>

              <div class="ct-settings">
                <div class="ct-setting-item" id="buttonSubtotal"></div>
                <div class="ct-setting-item" id="buttonGrandtotal"></div>
              </div>

              <div class="modal-footer">
                <button type="button" class="ct-reset-link" onclick="doResetHeader()" title="Reset">Reset ke default</button>
                <button type="button" class="ct-btn ct-btn-ghost" data-dismiss="modal" onclick="doCloseFormCustomizeTable()" title="Tutup (Esc)">Tutup</button>
              </div>

            </div>
          </div>
        </div>
        <!-- End modal customize table -->


        {{-- Modal "Setting Total" dihapus: pengaturan desimal & total kolom numeric
             sekarang memakai panel inline di dalam modal Atur Kolom (.ct-panel). --}}


        <!-- start modal filter data -->
        {{-- Restyle-only pakai skin .rt-picker-v2 (docs/new-cust-supp-modal-guide.md) --
             #formFilterData TETAP multi-select (klik/shift-klik baris, tombol Submit),
             bukan diubah jadi picker klik-langsung-pilih seperti #formSelect. .rt-picker-v2
             adalah class generik (lihat catatan di public/css/report-table.css sekitar
             baris 1552), aman dipakai di id manapun selama modalnya cuma punya satu <table>.
             Disamakan dengan masterreportGudang baris 196. --}}
        <div class="modal fade rt-picker-v2"  id="formFilterData" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm modal-dialog-centered"  role="document" style="max-width: 50%">
            <div class="modal-content">
              <div class="modal-header text-right">
                <h5 class="modal-title" id="formFilterDataLabel">Filter Data</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="doCloseFormFilterData()">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="container-fluid">
                  <div class="row" style="display: flex; justify-content: right;">
                    <label id="tabelfilter_totalrow"></label>
                  </div>
                  <div class="row mt-3">
                    <div class="col-12" style="overflow:auto;">
                      <div class="">
                            <table id="tabelfilter" class="table table-bordered table-striped"  >
                              <thead id="tabelfilter_header" class="text-center">
                                <tr>
                                  <th>Nomor Bukti</th>
                                  <th>Tanggal</th>
                                </tr>
                              </thead>
                              <tbody id="tabelfilter_data" class="text-center" >
                              </tbody>
                            </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="doCloseFormFilterData()" title="Batal (Esc)">Batal</button>
                <button type="button" class="btn btn-primary" onclick="doShowReportFilter()" title="Submit (Ctrl+F)">Submit</button>
              </div>
            </div>
          </div>
        </div>
        <!-- End modal filter data -->
</div>
@endsection

@section('js')
{{-- Shared report helpers (loadingHtml, fmtRp/fmtN, invDate/fmtDMY, voucher drill) PLUS the
     interactive table header (drag kolom + menu roda gigi + bar "kolom tersembunyi"/"Tampilan",
     window.ReportTable) untuk SEMUA halaman report yang extend masterreport2 — jadi tiap child
     tak perlu memuat sendiri. File berupa dua IIFE, masing-masing dengan guard
     (`if (typeof window.X !== 'function')` / `if (window.ReportTable) return;`), aman bila
     child juga memuatnya (tidak menimpa). Dimuat sebelum @yield('jsreport') agar global-nya
     siap dipakai. public/js/report-table-v2.js masih ada di disk sebagai arsip, tapi TIDAK
     dimuat lagi — isinya sudah digabung ke sini. --}}
<script src="{!! URL::asset('js/report-table.js') !!}?v={{ @filemtime(base_path('public/js/report-table.js')) ?: '1' }}"></script>
@yield('jsreport')
<script src="{{ asset('js/exceljs.min.js') }}?v={{ filemtime(public_path('js/exceljs.min.js')) }}"></script>
<script src="{{ asset('js/report-export.js') }}?v={{ filemtime(public_path('js/report-export.js')) }}"></script>
<script type="text/javascript">
  var g_href = '{!! $akses['href'] !!}';

  var g_modeModal = "";
  var gmodal_customizetable = "customizetable";
  var gmodal_filterdata = "filterdata";

  // === GRAND TOTAL FINAL  ===
  var gsum_isfinalgrandtotal = 1;
  var gsum_colFinal = {};

  var g_modeReport;
  var gcart_header = [];
  var gsum_issubtotal = 0, gsum_isgrandtotal = 0;
  var gsum_colCart = {}, gsum_posArray = [], gsum_rowSubtotal = 0;

  // modal Atur Kolom: index baris yang panel setting-nya terbuka (-1 = tidak ada),
  // dan index baris yang sedang di-drag (-1 = tidak ada)
  var g_ct_expanded = -1;
  var g_ct_dragIdx = -1;

  var gcart_filter = [];
  var gcart_filterShow = [];
  var gfilter_lastrow = -1, gfilter_totalrow = 0;
  var gfilter_title, gfilter_groupby, gfilter_date1, gfilter_date2;
  // Kunci baris (nilai kolom pertama getKolomFilter(), mis. NoBukti/KODEBRG) yang
  // sedang dipilih di modal Filter Data. Beda dari gcart_filterShow (yang dibangun
  // ulang dari nol tiap modal dibuka) -- var ini TIDAK direset oleh doShowFilter()
  // atau doCloseFormFilterData(), jadi pilihan tetap ada saat modal dibuka lagi.
  var gfilter_selectedKeys = new Set();

  var gxls_filename = ""; // berikan nilai di Blade jika ingin custom file name excel



  $(document).ready(function(){
    doSetHeader(g_modeReport);   // doSetHeader() sudah memanggil doButtonSubtotal/doButtonGrandtotal sendiri

    $("#tabelfilter").DataTable({
      "lengthChange": false,
      "paging": false,
    });
  });

  document.onkeydown = function(e) {
    if(event.keyCode == 13 && e.ctrlKey){ return doCtrlEnter(); }
    if(event.keyCode == 38 && e.ctrlKey){ return doGodown(); }
    if(event.keyCode == 27){ return doEscButton(); }
  }

  function doCtrlEnter() {
    switch (g_modeModal) {
      case gmodal_filterdata :
                 doShowReportFilter();
                 break;

      default :
                 makeTable('REPORT');
                 break;
    }
  }

  function doGodown() {
    document.getElementById("showTableReport").scrollIntoView({ behavior : "smooth" });
  }

  function doEscButton() {
    switch (g_modeModal) {
      case gmodal_customizetable :
                 // Esc pertama menutup panel setting kolom, Esc berikutnya menutup modal
                 if (g_ct_expanded >= 0) { g_ct_expanded = -1; doShowCustomize(); return; }
                 doCloseFormCustomizeTable();
                 break;

      case gmodal_filterdata :
                 doCloseFormFilterData();
                 break;

      default :
                return;
    }
  }



  /* ============== START CUSTOMIZE HEADER  ============== */

  function doShowFormCustomizeTable() {
    g_modeModal = gmodal_customizetable;
    g_ct_expanded = -1;
    $("#ct_search").val("");
    doShowCustomize();
    $("#formCustomizeTable").modal('toggle');
  }

  function doCloseFormCustomizeTable() {
    g_modeModal = "";
    g_ct_expanded = -1;
    $("#formCustomizeTable").modal('toggle');
  }

  function doResetHeader() {
    alertify.confirm('Reset Customize Table', 'Apakah yakin ingin mengembalikan kolom tabel ke pengaturan awal?',
    function() {
        doSetHeader(g_modeReport, true);
        g_ct_expanded = -1;
        $("#ct_search").val("");
        doShowCustomize();
        doButtonSubtotal(gsum_issubtotal);
        doButtonGrandtotal(gsum_isgrandtotal);
        alertify.success("Kolom tabel sudah kembali ke pengaturan awal");
      }, function(){}
    );
  }

  function doSetHeader(_modereport, _isReset = false) {
    let _strHeader = (!_isReset) ? doLoadHeader('{!! $akses['href'] !!}', _modereport) : "";

    if (_strHeader != "") {
      gcart_header = doGetHeader(_strHeader);
    } else {
      // cek apakah function setDefaultHeader ada
      if ($.isFunction(window.setDefaultHeader)) {
        setDefaultHeader();
        doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
      }
    }
    doButtonSubtotal(gsum_issubtotal);
    doButtonGrandtotal(gsum_isgrandtotal);
  }

  function doLoadHeader(_href, _mode) {
    // window.g_headerStore (diisi newmaster2x dari $akses['simpanheader']) memuat
    // seluruh baris DBSIMPANHEADER milik user+href ini -- baca dari situ dulu
    // supaya tidak perlu AJAX sinkron (yang mengunci main thread) tiap kali
    // doSetHeader() dipanggil, termasuk panggilan kedua dari ready master layout.
    let _key = String(_mode);
    let _row = window.g_headerStore ? window.g_headerStore[_key] : undefined;

    if (_row === undefined) {   // undefined = belum pernah dicek -> baru ambil dari server
      $.ajax({
        url     : "{!! url('globalfunctions_doLoadHeader') !!}",
        type    : "get",
        async   : false,
        data    : {
          href : _href,
          mode : _mode
        },
        success: function(res) {
          // Number(), bukan toInteger(): kolom int dari DBSIMPANHEADER dikirim
          // sebagai angka di JSON, sedangkan toInteger() memanggil .replace().
          _row = (res.length > 0)
            ? { header: res[0].header, issubtotal: Number(res[0].issubtotal), isgrandtotal: Number(res[0].isgrandtotal) }
            : null;   // null = sudah dicek ke server, memang tidak ada baris tersimpan
        }
      })

      if (window.g_headerStore) { window.g_headerStore[_key] = _row; }
    }

    if (!_row) { return ""; }

    gsum_issubtotal = Number(_row.issubtotal);
    gsum_isgrandtotal = Number(_row.isgrandtotal);
    return _row.header;
  }

  function doGetHeader(_strHeader) {
    let _cart = [];

    _strHeader.split("||").forEach((item, i) => {
      let temp = [];
      temp.push(item.split(";;")[0]);             // nama kolom
      temp.push(item.split(";;")[1]);             // nama header
      temp.push(toInteger(item.split(";;")[2]));  // muncul / tidak muncul
      temp.push(item.split(";;")[3]);             // tipe data
      temp.push(toInteger(item.split(";;")[4]));  // 0 = tanpa total, 1 = pakai total (khusus numeric)
      temp.push(toInteger(item.split(";;")[5]));  // jumlah desimal di belakang koma
      _cart.push(temp);
    });

    return _cart;
  }

  function doSimpanHeader(_href, _mode, _cart, _issubtotal, _isgrandtotal) {
    let _strHeader = "";

    _cart.forEach((item, i) => {
      if (i != 0) { _strHeader += '||'; }
      _strHeader += item[0] + ';;' + item[1] + ';;' + item[2] + ';;' + item[3] + ';;' + item[4] + ';;' + item[5];
    });

    // Lewati request kalau isinya sama persis dengan yang terakhir diketahui
    // tersimpan (dari g_headerStore) -- doSetHeader() memanggil ini tiap page
    // load walau tidak ada perubahan sama sekali, jadi ini menghapus AJAX
    // sinkron yang percuma.
    let _key = String(_mode), _store = window.g_headerStore;
    let _prev = _store ? _store[_key] : undefined;

    if (_prev && _prev.header === _strHeader
        && Number(_prev.issubtotal) === Number(_issubtotal)
        && Number(_prev.isgrandtotal) === Number(_isgrandtotal)) {
      return;
    }

    $.ajax({
      url     : "{!! url('globalfunctions_doSimpanHeader') !!}",
      type    : "get",
      async   : false,
      data    : {
        href : _href,
        mode : _mode,
        header : _strHeader,
        issubtotal : _issubtotal,
        isgrandtotal : _isgrandtotal
      },
      success: function(res) {
        // nothing to do
      }
    })

    if (_store) {
      _store[_key] = { header: _strHeader, issubtotal: Number(_issubtotal), isgrandtotal: Number(_isgrandtotal) };
    }
  }

  // Dipanggil tanpa argumen dari banyak halaman report (mis. setelah doReportMode),
  // jadi kata kunci pencarian dibaca langsung dari input #ct_search.
  function doShowCustomize() {
    let _cari = String($("#ct_search").val() || "").toLowerCase();
    let str = "";

    gcart_header.forEach((item, i) => {
      let _nama = String(item[1]);
      if (_cari != "" && _nama.toLowerCase().indexOf(_cari) < 0) { return; }

      // NB: pakai Number(), bukan toInteger() — toInteger() memanggil .replace()
      // sehingga error bila diberi angka, dan isi gcart_header sudah berupa angka.
      let _numeric  = (item[3] == "float" || item[3] == "int");
      let _visible  = (Number(item[2]) === 1);
      let _expanded = (g_ct_expanded === i);

      str += '<div class="ct-block">';
      str += '  <div class="ct-row' + (_visible ? '' : ' is-hidden') + (_expanded ? ' is-expanded' : '') + '" draggable="true" data-idx="' + i + '">';
      str += '    <span class="ct-grip"><i></i><i></i><i></i><i></i><i></i><i></i></span>';
      str += '    <button type="button" class="ct-switch' + (_visible ? ' on' : '') + '" title="Tampilkan / sembunyikan kolom" onclick="doButtonVisibility(' + i + ')"></button>';
      str += '    <span class="ct-name" title="' + _nama + '">' + _nama + '</span>';

      if (_numeric) {
        str += '  <span class="ct-tag is-num">' + Number(item[5]) + ' desimal</span>';
        if (Number(item[4]) === 1) { str += '<span class="ct-tag is-total">total</span>'; }
        str += '  <button type="button" class="ct-icon' + (_expanded ? ' is-active' : '') + '" title="Setting kolom" onclick="doShowFormSettingTotal(' + i + ')"><i class="bi bi-gear"></i></button>';
      }

      str += '    <button type="button" class="ct-icon" title="Naikkan" onclick="doButtonUpDown(' + i + ', 0)"><i class="bi bi-chevron-up"></i></button>';
      str += '    <button type="button" class="ct-icon" title="Turunkan" onclick="doButtonUpDown(' + i + ', 1)"><i class="bi bi-chevron-down"></i></button>';
      str += '  </div>';

      if (_numeric && _expanded) { str += doPanelSettingTotal(i, item); }

      str += '</div>';
    });

    if (str == "") { str = '<div class="ct-empty">Kolom tidak ditemukan</div>'; }

    $("#tabelcustomize_data").html(str);
    doBindCustomizeDrag();
    doShowCustomizePreview();
  }

  // Strip chip di atas: kolom yang tampil, urut sesuai urutan tabel
  function doShowCustomizePreview() {
    let _str = "", _n = 0;

    gcart_header.forEach((item) => {
      if (Number(item[2]) !== 1) { return; }
      _n++;
      _str += '<span class="ct-chip"><span class="ct-chip-n">' + _n + '</span>' + item[1] + '</span>';
    });

    if (_n === 0) { _str = '<span class="ct-chip-empty">Tidak ada kolom yang ditampilkan</span>'; }

    $("#ct_chips").html(_str);
    $("#ct_count").text(_n + " kolom");
  }

  function doBindCustomizeDrag() {
    document.querySelectorAll("#tabelcustomize_data .ct-row").forEach(function(row) {
      row.addEventListener("dragstart", function(e) {
        g_ct_dragIdx = toInteger(row.getAttribute("data-idx"));
        row.classList.add("is-dragging");
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = "move";
          e.dataTransfer.setData("text/plain", row.getAttribute("data-idx"));
        }
      });

      row.addEventListener("dragend", function() {
        g_ct_dragIdx = -1;
        doClearDragOver();
      });

      row.addEventListener("dragover", function(e) {
        e.preventDefault();
        if (g_ct_dragIdx >= 0 && g_ct_dragIdx !== toInteger(row.getAttribute("data-idx"))) {
          row.classList.add("is-dragover");
        }
      });

      row.addEventListener("dragleave", function() { row.classList.remove("is-dragover"); });

      row.addEventListener("drop", function(e) {
        e.preventDefault();
        doClearDragOver();
        let _from = g_ct_dragIdx;
        g_ct_dragIdx = -1;
        doMoveHeader(_from, toInteger(row.getAttribute("data-idx")));
      });
    });
  }

  function doClearDragOver() {
    document.querySelectorAll("#tabelcustomize_data .ct-row").forEach(function(r) {
      r.classList.remove("is-dragging");
      r.classList.remove("is-dragover");
    });
  }

  // Pindahkan kolom dari index _from ke index _to (dipakai drag & drop)
  function doMoveHeader(_from, _to) {
    if (_from < 0 || _to < 0 || _from === _to) { return; }
    if (_from >= gcart_header.length || _to >= gcart_header.length) { return; }

    let _moved = gcart_header.splice(_from, 1)[0];
    gcart_header.splice(_to, 0, _moved);

    g_ct_expanded = -1;
    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
    doShowCustomize();
  }

  function doButtonVisibility(_id) {
    gcart_header[_id][2] = (Number(gcart_header[_id][2]) === 1) ? 0 : 1;

    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
    doShowCustomize();
  }

  function doButtonUpDown(_id, _mode) {
    // mode = 0 UP, 1 DOWN
    let temp = [];
    let _idx = (_mode == 0) ? _id-1 : _id+1; // idx adalah index tujuan

    let _isNotEdge = (_mode == 0) ? (_id > 0) : (_id < gcart_header.length-1);

    if (_isNotEdge) {
      // masukkan data yang sudah ada di index tujuan ke temp
      temp.push(gcart_header[_idx][0]);
      temp.push(gcart_header[_idx][1]);
      temp.push(gcart_header[_idx][2]);
      temp.push(gcart_header[_idx][3]);
      temp.push(gcart_header[_idx][4]);
      temp.push(gcart_header[_idx][5]);

      // masukkan data index asal ke index tujuan
      gcart_header[_idx][0] = gcart_header[_id][0];
      gcart_header[_idx][1] = gcart_header[_id][1];
      gcart_header[_idx][2] = gcart_header[_id][2];
      gcart_header[_idx][3] = gcart_header[_id][3];
      gcart_header[_idx][4] = gcart_header[_id][4];
      gcart_header[_idx][5] = gcart_header[_id][5];

      // masukkan data dari temp ke index asal
      gcart_header[_id] = temp;

      // panel setting yang terbuka ikut pindah bersama barisnya
      if (g_ct_expanded === _id)       { g_ct_expanded = _idx; }
      else if (g_ct_expanded === _idx) { g_ct_expanded = _id; }

      doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
      doShowCustomize();
    }
  }

  function doButtonSubtotal(_mode) {
    let _on = (Number(_mode) === 1);
    let _next = _on ? 0 : 1;
    let _str = '';

    _str += '<button type="button" class="ct-switch' + (_on ? ' on' : '') + '" onclick="doButtonSubtotal(' + _next + ')"></button>';
    _str += '<label onclick="doButtonSubtotal(' + _next + ')">Tampilkan subtotal</label>';

    $("#buttonSubtotal").html(_str);

    gsum_issubtotal = Number(_mode);

    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
  }

  function doButtonGrandtotal(_mode) {
    let _on = (Number(_mode) === 1);
    let _next = _on ? 0 : 1;
    let _str = '';

    _str += '<button type="button" class="ct-switch' + (_on ? ' on' : '') + '" onclick="doButtonGrandtotal(' + _next + ')"></button>';
    _str += '<label onclick="doButtonGrandtotal(' + _next + ')">Tampilkan grand total</label>';

    $("#buttonGrandtotal").html(_str);

    gsum_isgrandtotal = Number(_mode);

    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
  }

  /* ============== END OF CUSTOMIZE HEADER ============== */



  /* ==============   START SETTING TOTAL   ============== */
  /* Dulu modal terpisah (#formSettingTotal), sekarang panel inline di dalam
     modal Atur Kolom. Perubahan langsung tersimpan (tanpa tombol Simpan). */

  var gct_desimal_max = 4;

  // Buka / tutup panel setting kolom numeric. Nama fungsi dipertahankan karena
  // masih dipakai sebagai onclick di baris kolom.
  function doShowFormSettingTotal(_index) {
    g_ct_expanded = (g_ct_expanded === _index) ? -1 : _index;
    doShowCustomize();
  }

  function doPanelSettingTotal(_index, _item) {
    let _total = (Number(_item[4]) === 1);
    let _str = '';

    _str += '<div class="ct-panel">';
    _str += '  <div class="ct-panel-row">';
    _str += '    <div>';
    _str += '      <div class="ct-panel-label">Jumlah desimal di belakang koma</div>';
    _str += '      <div class="ct-panel-hint">Berlaku untuk semua nilai di kolom "' + _item[1] + '"</div>';
    _str += '    </div>';
    _str += '    <div class="ct-stepper">';
    _str += '      <button type="button" title="Kurangi" onclick="doSetDesimal(' + _index + ', -1)">&minus;</button>';
    _str += '      <span class="ct-stepper-val">' + Number(_item[5]) + '</span>';
    _str += '      <button type="button" title="Tambah" onclick="doSetDesimal(' + _index + ', 1)">+</button>';
    _str += '    </div>';
    _str += '  </div>';
    _str += '  <div class="ct-panel-row">';
    _str += '    <div>';
    _str += '      <div class="ct-panel-label">Tampilkan total kolom</div>';
    _str += '      <div class="ct-panel-hint">Jumlahkan nilai kolom ini di baris subtotal &amp; grand total</div>';
    _str += '    </div>';
    _str += '    <button type="button" class="ct-switch sm' + (_total ? ' on' : '') + '" onclick="doButtonTotal(' + _index + ')"></button>';
    _str += '  </div>';
    _str += '</div>';

    return _str;
  }

  function doSetDesimal(_index, _step) {
    let _next = Number(gcart_header[_index][5]) + _step;
    if (_next < 0 || _next > gct_desimal_max) { return; }

    gcart_header[_index][5] = _next;
    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
    doShowCustomize();
  }

  function doButtonTotal(_index) {
    gcart_header[_index][4] = (Number(gcart_header[_index][4]) === 1) ? 0 : 1;

    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
    doShowCustomize();
  }

  /* ==============  END OF SETTING TOTAL   ============== */



  /* ==============    START MAIN REPORT    ============== */

  function doCloseTable() {
    document.getElementById("showTableReport").style.display = "none"
  }

  function doReportMode(_mode) {
    if (g_modeReport != _mode) {
      let prev_mode = g_modeReport;
      g_modeReport = _mode;

      $("#buttonMode" + prev_mode).removeClass("btn-primary");
      $("#buttonMode" + prev_mode).addClass("btn-outline-primary");

      $("#buttonMode" + g_modeReport).removeClass("btn-outline-primary");
      $("#buttonMode" + g_modeReport).addClass("btn-primary");

      doSetHeader(g_modeReport);
      doShowCustomize();
    }
  }

  function doMakeTable(_mode, _groupby, _data, _title, _date1, _date2 = null, inputdetailorRekap) {
    let url = "{!! url('" + g_href + "_doReport') !!}";

    $.ajax({
      url     : url,
      type    : "get",
      async   : false,
      data    : _data,
      success: function(res) {
        console.log(res)
        if (_mode == "REPORT") {
          document.getElementById("showTableReport").style.display = "block";
          doShowReport(res, _title, _groupby, _date1, _date2);
          // alertify.success("Report ditampilkan");
        } else if (_mode == "FILTER") {
          gcart_filter = res;
          gfilter_title = _title;
          gfilter_groupby = _groupby;
          gfilter_date1 = _date1;
          gfilter_date2 = _date2;
        }
      }
    })
  }

  function getColumnType(col) {
    for (let i = 0; i < col.length; i++) {
        let v = (col[i] + "").toLowerCase();
        if (v === "float" || v === "double" || v === "decimal") return "float";
        if (v === "varchar" || v === "string" || v === "text") return "varchar";
    }
    return "varchar";
  }

  function doSetRowTableWithGrouping(data, tempcart) {
    let html = "";

    let isMultiGrouping =
      typeof g_groupingConfig !== "undefined" &&
      Array.isArray(g_groupingConfig) &&
      g_groupingConfig.length > 0;

    // ===== INIT FINAL GRAND TOTAL =====
    if (gsum_isfinalgrandtotal === 1) {
      tempcart.forEach(col => {
        if (col[4] === 1 && gsum_colFinal["ftot" + col[0]] === undefined) {
          gsum_colFinal["ftot" + col[0]] = 0;
        }
      });
    }

    // ================= MULTI GROUPING ========================
    if (isMultiGrouping) {

      const LEVEL_CUSTOMER = 0;
      const LEVEL_LOKASI   = 1;
      const LEVEL_BULAN    = 2;

      let prevRow = null;

      data.forEach((r, i) => {

        let changedLevel = -1;

        if (!prevRow) {
          changedLevel = 0;
        } else {
          g_groupingConfig.forEach((lvl, idx) => {
            if (changedLevel === -1 && r[lvl.field] !== prevRow[lvl.field]) {
              changedLevel = idx;
            }
          });
        }

        if (changedLevel !== -1 && i !== 0) {

          if (changedLevel <= LEVEL_BULAN) {
            html += doSetRowSubtotal(tempcart);
          }

          if (changedLevel <= LEVEL_LOKASI) {
            html += doSetRowGrandtotal(tempcart);

            Object.keys(gsum_colCart).forEach(k => {
              if (k.startsWith("gtot")) gsum_colCart[k] = 0;
            });
          }
        }

        if (changedLevel !== -1) {
          for (let x = changedLevel; x < g_groupingConfig.length; x++) {
            let lvl = g_groupingConfig[x];
            html += `
              <tr style="background:#eee;font-weight:bold">
                <td colspan="${tempcart.length}" style="text-align:left;">
                  ${lvl.header(r)}
                </td>
              </tr>`;
          }
        }

        // ===== DATA ROW =====
        let row = "<tr>";

        tempcart.forEach(col => {
          let kolom = col[0];
          let tipe  = getColumnType(col);
          let val   = r[kolom] ?? "";

          if (tipe === "float") {
            let num = currencyNormalizer(val);

            if (col[4] === 1) {
              gsum_colCart["stot" + kolom] += num;
              gsum_colCart["gtot" + kolom] += num;

              if (gsum_isfinalgrandtotal === 1) {
                gsum_colFinal["ftot" + kolom] += num;
              }
            }

            row += `<td style="text-align:right">${format_number(num, col[5])}</td>`;
          } else {
            row += `<td>${val}</td>`;
          }
        });

        row += "</tr>";
        html += row;

        prevRow = r;
      });

      if (data.length > 0) {
        html += doSetRowSubtotal(tempcart);
        html += doSetRowGrandtotal(tempcart);

        if (gsum_isfinalgrandtotal === 1) {
          html += doSetRowGrandTotalFinal(tempcart);
        }
      }

      return html;
    }

    // ================= SINGLE GROUPING =================

    let lastValue = null;
    let prevGroup = null;

    let needGrouping = (typeof g_needGrouping !== "undefined") ? g_needGrouping : false;

    let avgCol = getAverageColumn(tempcart);
    let needAverage = avgCol !== null;
    let avgSum = 0;
    let avgCnt = 0;

    data.forEach((r, i) => {

        let key = g_groupField;
        let groupVal = r[key] ?? "";

        if (needGrouping && i !== 0 && groupVal !== prevGroup) {
            html += doSetRowSubtotal(tempcart);
        }

        if (needGrouping && groupVal !== lastValue) {
            html += `
              <tr style="background:#eee; font-weight:bold;">
                <td colspan="${tempcart.length}" style="text-align:left;">
                  ${groupVal}
                </td>
              </tr>`;
            lastValue = groupVal;
        }

        let row = "<tr>";

        tempcart.forEach(col => {
            let kolom = col[0];
            let tipe  = getColumnType(col);
            let val   = r[kolom] ?? "";

            if (tipe === "float") {
                let num = currencyNormalizer(val);

                if (col[4] === 1) {
                    gsum_colCart["stot" + kolom] += num;
                }

                if (needAverage && kolom === avgCol[0]) {
                    avgSum += num;
                    avgCnt++;
                }

                row += `<td style="text-align:right">
                          ${format_number(num, col[5])}
                        </td>`;
            } else {
                row += `<td>${val}</td>`;
            }
        });

        row += "</tr>";
        html += row;

        prevGroup = groupVal;
    });

    if (needGrouping && data.length > 0) {
        html += doSetRowSubtotal(tempcart);
    }

    if (needAverage && avgCnt > 0) {
        html += doSetRowAverageStandalone(tempcart, avgCol, avgValue);
    }

    if (gsum_isgrandtotal === 1) {
        html += doSetRowGrandtotal(tempcart);
    }

    return html;
  }

  function doSetRowAverageStandalone(_tempcart) {
    let rowTable = '';

    if (!gsum_colCart.__AVG_VALUE__) return '';

    rowTable += '<tr id="avgrow" style="font-weight:bold;">';

    let _counter = 0;
    _tempcart.forEach((itemcart) => {
      if (itemcart[2]) {
        _counter++;

        // kolom nilai rata2
        if (itemcart[0] === gsum_colCart.__AVG_COL__) {
          let _decimal = itemcart[5];
          rowTable += `
            <td style="border-top:1px solid black; text-align:right;">
              ${format_number(gsum_colCart.__AVG_VALUE__, _decimal)}
            </td>
          `;

        // kolom tulisan rata2
        } else if (_counter === gsum_colCart.__AVG_LABEL_POS__) {
          rowTable += `
            <td style="border-top:1px solid black;">
              Rata-rata :
            </td>
          `;

        // kolom lain yg kosong
        } else {
          rowTable += '<td style="border-top:1px solid black;"></td>';
        }
      }
    });

    rowTable += '</tr>';
    return rowTable;
  }

  function getAverageColumn(tempcart) {
    for (let col of tempcart) {
        if (
            col.length >= 7 &&
            col[6] &&
            col[6].avg === true &&
            getColumnType(col) === 'float'
        ) {
            return col;
        }
    }
    return null;
  }

  function doSetRowAverage(tempcart, avgCol, avgValue) {
    let html = '<tr style="font-weight:bold;">';

    let avgIndex = tempcart.findIndex(col => col[0] === avgCol[0]);

    tempcart.forEach((col, idx) => {
        // sebelum kolom Selisih
        if (idx === avgIndex - 1) {
            html += `<td style="text-align:right">Rata-rata</td>`;
        }
        // kolom Selisih
        else if (idx === avgIndex) {
            html += `<td style="text-align:right">
                        ${format_number(avgValue, col[5])}
                     </td>`;
        }
        else {
            html += `<td></td>`;
        }
    });

    html += '</tr>';
    return html;
  }


  function formatAngka(value) {
    if (value === null || value === undefined || value === "" || value === ".00" || value === ".000000") {
        return "0";
    }

    let num = Number(value);

    if (isNaN(num)) {
        return "0";
    }

    // format rp
    return num.toLocaleString("id-ID", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
  }

  function doShowReport (_res, _reportTitle, _groupby, _date1, _date2 = null) {
    let tempcart = gcart_header;
    let _cellcount = 0;
    tempcart.forEach((item, i) => {
      _cellcount += item[2];
    });

    gsum_colFinal = {};
    let rowTable = "";
    gsum_rowSubtotal = 0;

    let needGrouping = (typeof g_needGrouping !== "undefined") ? g_needGrouping : false;

    // TABLE HEADER
    doSetColCart(gcart_header);
    $("#tabel_header").html(doSetRowHeader(_reportTitle, tempcart, _cellcount, _date1, _date2));

    // TABLE DATA
    rowTable = '';
    if (_res.length > 0) {
      if (needGrouping === true && typeof g_groupField !== "undefined" && g_groupField) {
          rowTable = doSetRowTableWithGrouping(_res, tempcart, _groupby);
      } else {
          rowTable = doSetRowTable(_res, tempcart, _groupby);
      }
    } else {
        rowTable += "<tr style='text-align: center'>";
        rowTable += '  <td colspan="' + _cellcount + '" style="border: 1px solid black;">Tidak ada data ditemukan</td>';
        for (let i = 0; i < (_cellcount-1); i++) {
          rowTable += '  <td style="display: none;"></td>';
        }
        rowTable += "</tr>";
    }

    $("#tabel_data").html(rowTable);

    // POSISI TULISAN TOTAL & GRAND TOTAL
    if (_res.length > 0) { doSetPosisiTulisanTotal(_cellcount); }

    doGodown();
  }

  function doSetColCart(_tempcart) {
    gsum_colCart = {};
    _tempcart.forEach((item, i) => {
      if ((item[2] === 1) && (item[4] === 1) && (item[3] == "float" || item[3] == "int")) {
        gsum_colCart["stot"+item[0]] = 0;
        gsum_colCart["gtot"+item[0]] = 0;
      }
    });
  }

  function doSetRowHeader (_reportTitle, _tempcart, _cellcount, _date1, _date2 = null) {
  if ($.isFunction(window.setRowHeader)) { return setRowHeader(); }

  let rowHeader = "";
  let posCount = 0;
  gsum_posArray = [];

  rowHeader += '<tr>';
  rowHeader += '  <th colspan="' + _cellcount + '" style="text-align: left; font-weight: bold;">{!! $akses['program'] !!}<br/> ' + _reportTitle + '</th>';

  rowHeader += '</tr>';

  if (_date1 && _date1 !== 'undefined' && _date1 !== '') {
    rowHeader += '<tr>';
    rowHeader += '  <th colspan="' + _cellcount + '" style="text-align: left; font-weight: bold;">PERIODE: ' +
      format_date(_date1, true) +
      ((_date2 == null || _date2 === '' || _date2 === 'undefined') ? '' : ' S.D ' + format_date(_date2, true)) +
      '</th>';
    rowHeader += '</tr>';
  }

  rowHeader += '<tr>';
  rowHeader += '  <th colspan="' + _cellcount + '"  style="text-align: left; font-weight: bold;">Dicetak Oleh :  ' + '  {!! $akses['user'] !!}  //  Tanggal : '+ getDateIndo() +' // Jam : ' + getTimeNow() + '</th>';
  rowHeader += '</tr>';

  rowHeader += '<tr><th colspan="' + _cellcount + '"></th></tr>';

  rowHeader += '<tr class="tabel_header_kolom">';
  _tempcart.forEach((item, i) => {
    if (item[2]) {
      posCount += 1;
      if (item[0] == "Nomor") {
        rowHeader += '  <th scope="col" style="border: 1px solid black;">No</th>';
      } else {
        rowHeader += '  <th scope="col" style="border: 1px solid black;">' + item[1] + '</th>';
        if ((item[4] === 1) && (item[3] == "float" || item[3] == "int")) {
          gsum_posArray.push(posCount);
        }
      }
    }
  });
  rowHeader += '</tr>';

  return rowHeader;
}

  function doSetRowTable(_res, _tempcart, _groupby) {
    // jika ada table custom, buatlah function setRowTable di Blade
    if ($.isFunction(window.setRowTable)) { return setRowTable(); }

    let _prevdata = "", _nowdata = "";
    let _countSubtotal = 0;
    let rowTable = "";

    _res.forEach((item, i) => {
      _nowdata = item[_groupby];

      // SUBTOTAL
      if (i != 0 && _prevdata != _nowdata) {
        rowTable += doSetRowSubtotal(_tempcart);
      }

      // ROW
      rowTable += "<tr style='text-align: center'>";
      _tempcart.forEach((itemcart, j) => {
        if (itemcart[2]) {
          if (itemcart[3] == "index") {
            rowTable += '  <td class="cellcompact-left" style="border: 1px solid black;">' + (i+1) + '</td>';
          } else if (itemcart[3] == "date") {
            rowTable += '  <td class="cellcompact-left" style="border: 1px solid black;">' + format_date(item[itemcart[0]]) + '</td>';
          } else if (itemcart[3] == "float") {
            let _value = currencyNormalizer(item[itemcart[0]]);
            let _decimal = itemcart[5];
            if (itemcart[4] === 1) { gsum_colCart["stot"+itemcart[0]] += _value; }
            rowTable += '  <td class="cellcompact-right text-right" style="border: 1px solid black; text-align: right;">' + format_number(_value,_decimal) + '</td>';
          } else {
            rowTable += '  <td class="cellcompact-left" style="border: 1px solid black; white-space:nowrap;">' + nullToEmpty(item[itemcart[0]]) + '</td>';
          }
        }
      });
      rowTable += "</tr>";

      _prevdata = item[_groupby];
    })

    // LAST ROW SUBTOTAL
    rowTable += doSetRowSubtotal(_tempcart);

    // ===== RATA-RATA =====
    if (typeof doSetRowAverageStandalone === 'function') {
      rowTable += doSetRowAverageStandalone(_tempcart);
    }

    // GRAND TOTAL
    if (gsum_isgrandtotal === 1) {
      rowTable += doSetRowGrandtotal(_tempcart);
    }

    return rowTable;
  }

  function doSetRowSubtotal(_tempcart) {
    let rowTable = '';

    if (Object.keys(gsum_colCart).length > 0) {

      let _counter = 0;
      gsum_rowSubtotal++;

      /* ================= TAMPILKAN SUBTOTAL ================= */
      if (gsum_issubtotal === 1) {

        rowTable += '<tr id="strow' + gsum_rowSubtotal + '" style="text-align: center">';

        _tempcart.forEach((itemcart, j) => {
          if (itemcart[2] === 1) {
            _counter++;

            if (itemcart[4] === 1) {
              let _value   = gsum_colCart["stot" + itemcart[0]];
              let _decimal = itemcart[5];

              if (itemcart[3] === "float") {
                rowTable +=
                  '  <td class="st' + _counter + ' cellcompact-left" ' +
                  'style="border-bottom: 1px solid black; border-right-style: hidden; ' +
                  'border-left-style: hidden; background-color:#dee2e6; ' +
                  'font-weight: bold; text-align: right;">' +
                  format_number(_value, _decimal) +
                  '</td>';

              } else if (itemcart[3] === "int") {
                rowTable +=
                  '  <td class="st' + _counter + ' cellcompact-left" ' +
                  'style="border-bottom: 1px solid black; background-color:#dee2e6; ' +
                  'border-right-style: hidden; border-left-style: hidden; ' +
                  'font-weight: bold; text-align: right;">' +
                  _value +
                  '</td>';
              }

            } else {
              rowTable +=
                '  <td class="st' + _counter + ' cellcompact-left" ' +
                'style="border-bottom: 1px solid black; border-right-style: hidden; ' +
                'background-color:#dee2e6; border-left-style: hidden; ' +
                'font-weight: bold; text-align: right;"></td>';
            }
          }
        });

        rowTable += "</tr>";
      }

      /* ================= GRAND TOTAL OTOMATIS ================= */
      // Jika multi grouping ada (lebih dari 1) maka jangan update gtot
      let isMultiGrouping = typeof g_groupingConfig !== "undefined" && Array.isArray(g_groupingConfig) && g_groupingConfig.length > 0;

      if (!isMultiGrouping) {
        // single grouping -> update grand total
        _tempcart.forEach((itemcart, j) => {
          if ((itemcart[4] === 1) && (itemcart[3] === "float" || itemcart[3] === "int")) {
            gsum_colCart["gtot"+itemcart[0]] += gsum_colCart["stot"+itemcart[0]];
          }
        });
      }

      /* ================= RESET SUBTOTAL ================= */
      _tempcart.forEach((itemcart, j) => {
        if ((itemcart[4] === 1) && (itemcart[3] === "float" || itemcart[3] === "int")) {
          gsum_colCart["stot"+itemcart[0]] = 0.00;
        }
      });
    }

    return rowTable;
  }

  function doSetRowGrandtotal(_tempcart) {
    let rowTable = '';
    if (Object.keys(gsum_colCart).length > 0) {

      rowTable += '<tr id="gtrow" class="gtrow" style="text-align: center">';

      let _counter = 0;
      _tempcart.forEach((itemcart, j) => {
        if ((itemcart[2])) {
          _counter++;
          if ((itemcart[4] === 1)) {
            let _value = gsum_colCart["gtot"+itemcart[0]];
            let _decimal = itemcart[5];

            if (itemcart[3] == "float") {
              rowTable += '  <td id="gt' + _counter + '" style="border-bottom: 1px solid black; border-right-style: hidden; border-left-style: hidden; font-weight: bold; text-align: right;">' + format_number(_value,_decimal) + '</td>';
            } else if (itemcart[3] == "int") {
              rowTable += '  <td id="gt' + _counter + '" style="border-bottom: 1px solid black; border-right-style: hidden; border-left-style: hidden; font-weight: bold; text-align: right;">' + _value + '</td>';
            }
          } else {
            rowTable += '  <td id="gt' + _counter + '" style="border-bottom: 1px solid black; border-right-style: hidden; border-left-style: hidden; font-weight: bold; text-align: right;"></td>';
          }
        }
      });
      rowTable += "</tr>";
    }

    return rowTable;
  }


  function doSetRowGrandTotalFinal(_tempcart) {
    let rowTable = '';
    if (Object.keys(gsum_colFinal).length === 0) return rowTable;

    rowTable += `
      <tr class="finalgtrow"
          style="text-align:right;background:#cfe2ff;font-weight:bold">
    `;

    let counter = 0;
    _tempcart.forEach(col => {
      if (col[2]) {
        counter++;

        if (col[4] === 1) {
          let val = gsum_colFinal["ftot" + col[0]];
          let dec = col[5];

          rowTable += `
            <td class="fg${counter}" style="text-align:right">
              ${col[3] === "float" ? format_number(val, dec) : val}
            </td>`;
        } else {
          rowTable += `<td class="fg${counter}"></td>`;
        }
      }
    });

    rowTable += "</tr>";
    return rowTable;
  }

  function doSetPosisiTulisanTotal(_cellcount) {

    let posStrTotal = (gsum_posArray.length > 0)
        ? Math.min(...gsum_posArray) - 2
        : 0;

    if (posStrTotal < 0) return;

    /* ================= SUBTOTAL ================= */
    if (gsum_issubtotal === 1) {

      if (posStrTotal === 0) {
        $(".st1").html("Total :");

      } else if (posStrTotal === (_cellcount - 1)) {
        $(".st" + posStrTotal).html("Total :");

      } else {
        for (let i = 0; i < gsum_rowSubtotal; i++) {
          let _row = document.getElementById("strow" + (i + 1));
          if (_row) _row.deleteCell(posStrTotal);
        }

        $(".st" + posStrTotal)
          .attr("colspan", 2)
          .html("Total :");
      }
    }

    /* ================= GRAND TOTAL ================= */
    if (gsum_isgrandtotal === 1) {

      if (posStrTotal === 0) {

        $(".gtrow").each(function () {
          $(this).find("#gt1").html("Grand Total :");
        });

      } else if (posStrTotal === (_cellcount - 1)) {

        $(".gtrow").each(function () {
          $(this)
            .find("#gt" + posStrTotal)
            .html("Grand Total :");
        });
      } else {

        $(".gtrow").each(function () {
          let _row = this;

          _row.deleteCell(posStrTotal);

          $(_row)
            .find("#gt" + posStrTotal)
            .attr("colspan", 2)
            .html("Grand Total :");
        });
      }
    }

    /* ================= FINAL GRAND TOTAL ================= */
    if (gsum_isfinalgrandtotal === 1) {

      if (posStrTotal === 0) {
        $(".finalgtrow").each(function () {
          $(this).find(".fg1").html("Grand Total Final :");
        });
      } else {
        $(".finalgtrow").each(function () {
          this.deleteCell(posStrTotal);
          $(this)
            .find(".fg" + posStrTotal)
            .attr("colspan", 2)
            .html("Grand Total Final :");
        });
      }
    }

  }

  /* ==============   END OF MAIN REPORT    ============== */



  /* ==============    START FILTER DATA    ============== */

  function doShowFormFilterData() {
    g_modeModal = gmodal_filterdata;

    makeTable("FILTER");
    doShowFilter();
    // gfilter_totalrow sudah dihitung ulang oleh doShowFilter() dari gfilter_selectedKeys
    // yang tersimpan -- tampilkan labelnya kalau ada baris yang masih terpilih.
    $("#tabelfilter_totalrow").html(gfilter_totalrow > 0 ? "Jumlah baris yang dipilih: " + gfilter_totalrow : "");

    $("#formFilterData").modal('toggle');
  }

  function doCloseFormFilterData() {
    g_modeModal = "";
    gfilter_lastrow = -1;
    gfilter_totalrow = 0;
    $("#formFilterData").modal('toggle');
  }

  function doShowFilter() {
    $('#tabelfilter').DataTable().destroy();

    // PERSIAPKAN KOLOM TABLE FILTER
    let _kolom = getKolomFilter();
    let cart_filterHeader = [];
    _kolom.forEach((item) => {
      let _match = gcart_header.find((itemcart) => item == itemcart[0]);
      if (_match) {
        cart_filterHeader.push(_match);
      }
    });

    // HEADER TABLE FILTER
    let _str = '<tr>';
    if (cart_filterHeader.length > 0) {
      cart_filterHeader.forEach((item, i) => {
        _str += '<th style="text-align: center;">' + item[1] + '</th>';
      });
    }
    _str += '</tr>';
    $("#tabelfilter_header").html(_str);

    // DATA TABLE FILTER
    _str = "";
    let _prevdata = "", _nowdata = "", _idx = -1;
    gcart_filterShow = [];
    gfilter_totalrow = 0;
    if (gcart_filter.length > 0) {
      gcart_filter.forEach((item, i) => {
        _nowdata = item[cart_filterHeader[0][0]];

        if (_prevdata != _nowdata) {
          _idx += 1;
          item._idx = _idx;

          // Pulihkan status terpilih dari gfilter_selectedKeys (bertahan lintas
          // buka-tutup modal), bukan selalu mulai dari false.
          let _isSelected = gfilter_selectedKeys.has(_nowdata);
          if (_isSelected) { gfilter_totalrow += 1; }

          _str += '<tr id="' + _idx + '-trrowfilter" class="pick-row' + (_isSelected ? ' is-selected' : '') +
                  '" draggable="true" onclick="doSelectrowfilter(' + _idx + ')">';
          cart_filterHeader.forEach((itemcart, j) => {
            if (itemcart[3] == "index") {
              _str += "  <td>" + (_idx+1) + "</td>";
            } else if (itemcart[3] == "date") {
              _str += '  <td>' + format_date(item[itemcart[0]]) + '</td>';
            } else if (itemcart[3] == "float") {
              let _value = currencyNormalizer(item[itemcart[0]]);
              let _decimal = itemcart[5];
              _str += '  <td>' + format_number(_value,_decimal) + '</td>';
            } else {
              _str += '  <td>' + item[itemcart[0]] + '</td>';
            }
          });
          _str += '</tr>';

          let temp = [];
          temp.push(_idx);        // index
          temp.push(_isSelected); // selected or not
          temp.push(_nowdata);    // kunci -- dipakai doSelectrowfilter() untuk update gfilter_selectedKeys
          gcart_filterShow.push(temp);
        } else {
          item._idx = _idx;
        }

        _prevdata = _nowdata;
      });
    } else {
        _str += '<tr>';
        _str += '  <td colspan="2" class="text-center">Tidak ada transaksi ditemukan.</td>';
        _str += '  <td style="display: none;"></td>';
        _str += '</tr>';
    }

    $("#tabelfilter_data").html(_str);

    $("#tabelfilter").DataTable({
      "lengthChange": false,
      "paging": false,
    });
  }

  function doSelectrowfilter(_row) {
    let _row_start, _row_end;

    if (!event.shiftKey) {
      _row_start = _row;
      _row_end = _row;
    } else {
      if (_row > gfilter_lastrow) {
        _row_start = gfilter_lastrow + 1;
        _row_end = _row;
      } else if (_row < gfilter_lastrow) {
        _row_start = _row;
        _row_end = gfilter_lastrow - 1;
      } else {
        _row_start = _row;
        _row_end = _row;
      }
    }

    while (_row_start <= _row_end) {
      let _key = gcart_filterShow[_row_start][2];

      if (gcart_filterShow[_row_start][1]) {
        // unselect
        $("#"+_row_start+"-trrowfilter").removeClass('is-selected');
        gfilter_selectedKeys.delete(_key);
        gfilter_totalrow -= 1;
      } else {
        // select
        $("#"+_row_start+"-trrowfilter").addClass('is-selected');
        gfilter_selectedKeys.add(_key);
        gfilter_totalrow += 1;
      }

      gcart_filterShow[_row_start][1] = !gcart_filterShow[_row_start][1];
      _row_start++;
    }

    gfilter_lastrow = _row;
    $("#tabelfilter_totalrow").html("Jumlah baris yang dipilih: " + gfilter_totalrow);
  }

  function doShowReportFilter() {
    let _res = [];
    gcart_filterShow.forEach((item) => {
      if (item[1]) {
        gcart_filter.filter(itemcart => itemcart._idx === item[0])
               .forEach(filteredItem => _res.push(filteredItem));
      }
    });

    doShowReport(_res, gfilter_title, gfilter_groupby, gfilter_date1, gfilter_date2);
    alertify.success("Report ditampilkan");

    doCloseFormFilterData();
  }

  /* ==============   END OF FILTER DATA    ============== */



  // Export Excel (.xlsx) & CSV — logika bersama ada di public/js/report-export.js
  function doExportTableToExcel(tableID, filename = '') {
    ReportExport.toXlsx(tableID || 'tabel', { baseName: @json($akses['xlsfilename']), customName: filename || gxls_filename });
  }

  function doExportTableToCsv(tableID, filename = '') {
    ReportExport.toCsv(tableID || 'tabel', { baseName: @json($akses['xlsfilename']), customName: filename || gxls_filename });
  }

</script>




@endsection
