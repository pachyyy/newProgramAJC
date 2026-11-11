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
<div id="contentContainer" class="container-fluid" style="margin-top:-150px;">

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
        <div class="modal fade"  id="formCustomizeTable" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm modal-dialog-centered"  role="document" style="max-width: 40%">
            <div class="modal-content">
              <div class="modal-header text-right">
                <h5 class="modal-title" id="formCustomizeTableLabel">Customize Table</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="doCloseFormCustomizeTable()">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="container-fluid">
                  <div class="row mt-3">
                    <div class="col-12" id="tabelcustomize_data" style="overflow:auto;">

                    </div>
                  </div>

                  <div id="buttonSubtotal" class="row mt-3" style="display: flex; justify-content: center;">
                  </div>
                  <div id="buttonGrandtotal" class="row mt-3" style="display: flex; justify-content: center;">
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="doResetHeader()" title="Reset">Reset</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="doCloseFormCustomizeTable()" title="Tutup (Esc)">Tutup</button>
              </div>
            </div>
          </div>
        </div>
        <!-- End modal customize table -->


        <!-- start modal setting total -->
        <div class="modal fade"  id="formSettingTotal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm modal-dialog-centered"  role="document" style="max-width: 40%">
            <div class="modal-content">
              <div class="modal-header text-right">
                <h5 class="modal-title" id="formSettingTotalLabel">Setting Total</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="doCloseFormSettingTotal()">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="container-fluid">
                  <div class="row">
                    <div class="col-7 text-left">
                      <div class="form-group text-left">
                        <label class="text-left">Jumlah desimal di belakang koma</label>
                      </div>
                    </div>
                    <div class="col-2">
                      <div class="form-group">
                        <input type="number" id="setTotalDesimal" class="form-control" name="setTotalDesimal">
                      </div>
                    </div>
                  </div>

                  <div id="buttonSetTotal" class="row mt-3" style="display: flex; justify-content: center;">
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="doCloseFormSettingTotal()" title="Batal (Esc)">Batal</button>
                <button type="button" class="btn btn-secondary" onclick="doSimpanFormSettingTotal()" title="Simpan">Simpan</button>
              </div>
            </div>
          </div>
        </div>
        <!-- End modal setting total -->


        <!-- start modal filter data -->
        {{-- Restyle-only pakai skin .rt-picker-v2 (docs/new-cust-supp-modal-guide.md) --
             #formFilterData TETAP multi-select (klik/shift-klik baris, tombol Submit),
             bukan diubah jadi picker klik-langsung-pilih seperti #formSelect. .rt-picker-v2
             adalah class generik (lihat catatan di public/css/report-table.css sekitar
             baris 1552), aman dipakai di id manapun selama modalnya cuma punya satu <table>. --}}
        <div class="modal fade rt-picker-v2" id="formFilterData" tabindex="-1" role="dialog" aria-labelledby="formFilterDataLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm modal-dialog-centered"  role="document" style="max-width: 50%">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="formFilterDataLabel">Filter Data</h5>
                <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="modal" onclick="doCloseFormFilterData()"></button>
              </div>
              <div class="modal-body">
                <div class="d-flex justify-content-end mb-2">
                  <label id="tabelfilter_totalrow"></label>
                </div>
                <table id="tabelfilter" class="table table-bordered table-striped">
                  <thead id="tabelfilter_header" class="text-center">
                    <tr>
                      <th>Nomor Bukti</th>
                      <th>Tanggal</th>
                    </tr>
                  </thead>
                  <tbody id="tabelfilter_data" class="text-center"></tbody>
                </table>
              </div>
              <div class="modal-footer ">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" onclick="doCloseFormFilterData()" title="Batal (Esc)">Batal</button>
                <button type="button" class="btn btn-primary" onclick="doShowReportFilter()" title="Submit (Ctrl+F)">Submit</button>
              </div>
            </div>
          </div>
        </div>
        <!-- End modal filter data -->
</div>
@endsection

@section('js')
{{-- window.ReportTable (drag kolom + menu roda gigi + bar kolom tersembunyi/Reset kolom),
     dipasangkan dengan doMoveHeader/doSetDesimal/doButtonTotal di bawah -- lihat
     docs/new-slider-table-guide.md. Dimuat sebelum @yield('jsreport') supaya globalnya
     siap dipakai halaman anak. --}}
<script src="{!! URL::asset('js/report-table.js') !!}?v={{ @filemtime(base_path('public/js/report-table.js')) ?: '1' }}"></script>
@yield('jsreport')
<script src="{{ asset('js/exceljs.min.js') }}?v={{ filemtime(public_path('js/exceljs.min.js')) }}"></script>
<script src="{{ asset('js/report-export.js') }}?v={{ filemtime(public_path('js/report-export.js')) }}"></script>
<script type="text/javascript">
  var g_href = '{!! $akses['href'] !!}';

  const gmodal_none = "none";
  const gmodal_customizetable = "customizetable", gmodal_settingtotal = "settingtotal";
  const gmodal_filterdata = "filterdata";
  var   g_modeModal = gmodal_none;

  var gcart_res = [];

  var g_modeReport;
  var gcart_header = [];
  var gsum_issubtotal = 0, gsum_isgrandtotal = 0;
  var gsum_colCart = {}, gsum_posArray = [], gsum_rowSubtotal = 0;
  var gsum_movingSum = {};

  const galign_left = "left", galign_center = "center", galign_right = "right";

  const gmodeTrans_tambah = "tambah", gmodeTrans_koreksi = "koreksi", gmodeTrans_view = "view"
      , gmodeTrans_hapus = "hapus";

  var gsettotal_index = 0;
  var gsettotal_nowtotal = 0;

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

  document.addEventListener('keydown', function(event) {
    if(event.keyCode == 13 && event.ctrlKey){ return doCtrlEnter(); }
    if(event.keyCode == 38 && event.ctrlKey){ return doGodown(); }
    if(event.keyCode == 27){ return doEscButton(); }

    if(event.ctrlKey && event.shiftKey && event.altKey && event.keyCode === 82) {
      // Ctrl + Shift + Alt + R
      event.preventDefault(); // Prevent any default action
      doResetHeader();
    }

    if(event.ctrlKey && event.shiftKey && event.keyCode === 69) {
      // Ctrl + Shift + E
      event.preventDefault(); // Prevent any default action
      doExportTableToExcel('tabel');
    }
  });

  function doCtrlEnter() {
    switch (g_modeModal) {
      case gmodal_none :
                 makeTable('REPORT');
                 break;

      case gmodal_settingtotal :
                 doSimpanFormSettingTotal();
                 break;

      case gmodal_filterdata :
                 doShowReportFilter();
                 break;

      default :
                 return;
    }
  }

  function doGodown() {
    document.getElementById("showTableReport").scrollIntoView({ behavior : "smooth" });
  }

  function doEscButton() {
    switch (g_modeModal) {
      case gmodal_customizetable :
                 doCloseFormCustomizeTable();
                 break;

      case gmodal_settingtotal :
                 doCloseFormSettingTotal();
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
    doShowCustomize();
    $("#formCustomizeTable").modal('toggle');
  }

  function doCloseFormCustomizeTable() {
    g_modeModal = gmodal_none;
    $("#formCustomizeTable").modal('toggle');
  }

  function doResetHeader() {
    alertify.confirm('Reset Customize Table', 'Apakah yakin ingin mengembalikan kolom tabel ke pengaturan awal?',
    function() {
        doSetHeader(g_modeReport, true);
        doShowCustomize();
        doButtonSubtotal(gsum_issubtotal);
        doButtonGrandtotal(gsum_isgrandtotal);
        alertify.success("Kolom tabel sudah kembali ke pengaturan awal");
      }, function(){}
    );
  }

  function doSetHeader(_modereport, _isReset = false) {
    let _strHeader = (!_isReset) ? doLoadHeader(g_href, _modereport) : "";

    if (_strHeader != "") {
      gcart_header = doGetHeader(_strHeader);
    } else {
      // cek apakah function setDefaultHeader ada
      if ($.isFunction(window.setDefaultHeader)) {
        setDefaultHeader();
        doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
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
        url     : "{!! url('functionglobal_doLoadHeader') !!}",
        type    : "get",
        async   : false,
        data    : {
          href : _href,
          mode : _mode
        },
        success: function(res) {
          let _h = (res.length > 0) ? res[0].header : "";
          _row = (_h != "")
            ? { header: _h, issubtotal: toInteger(res[0].issubtotal), isgrandtotal: toInteger(res[0].isgrandtotal) }
            : null;   // null = sudah dicek ke server, memang tidak ada header tersimpan
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
      url     : "{!! url('functionglobal_doSimpanHeader') !!}",
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

  function doShowCustomize() {
    let str = "";
    let tempcart = gcart_header;

    tempcart.forEach((item, i) => {
      let _checked = (item[2]) ? 'btn-success' : 'btn-outline-danger';
      let _icon_eye = (item[2]) ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      str += '<div class="row justify-content-center text-center">';
      str += '  <div class="col-2 ' + _checked + ' text-center header-toggle" id="buttonHeader' + i + '" onclick="doButtonVisibility(' + i + ')">' + _icon_eye + '</div>';

      if (item[3] == "float" || item[3] == "int" || item[3].startsWith("float")) {
        str += '  <div class="col-5 btn-outline-success text-center header-toggle" draggable="true" onclick="doShowFormSettingTotal(' + i + ')">' + item[1] + '</div>';
      } else {
        str += '  <div class="col-5 btn-outline-dark text-center header-toggle disabled" draggable="true">' + item[1] + '</div>';
      }
      str += '  <div class="col-2 btn-primary text-center header-toggle" id="buttonUp' + i + '" onclick="doButtonUpDown(' + i + ', 0)"><i class="bi bi-arrow-up"></i></div>';
      str += '  <div class="col-2 btn-primary text-center header-toggle" id="buttonDown' + i + '" onclick="doButtonUpDown(' + i + ', 1)"><i class="bi bi-arrow-down"></i></div>';
      str += '</div>';
    });

    $("#tabelcustomize_data").html(str);
  }

  function doButtonVisibility(_id) {
    if (gcart_header[_id][2] == 1) {
      $("#buttonHeader" + _id).removeClass("btn-success");
      $("#buttonHeader" + _id).addClass("btn-outline-danger");
      $("#buttonHeader" + _id).html('<i class="bi bi-eye-slash"></i>');
      gcart_header[_id][2] = 0;
    } else {
      $("#buttonHeader" + _id).removeClass("btn-outline-danger");
      $("#buttonHeader" + _id).addClass("btn-success");
      $("#buttonHeader" + _id).html('<i class="bi bi-eye"></i>');
      gcart_header[_id][2] = 1;
    }

    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
  }

  // Pindahkan kolom dari index _from ke index _to (dipakai drag & drop header
  // interaktif dari report-table.js / window.ReportTable).
  function doMoveHeader(_from, _to) {
    if (_from < 0 || _to < 0 || _from === _to) { return; }
    if (_from >= gcart_header.length || _to >= gcart_header.length) { return; }

    let _moved = gcart_header.splice(_from, 1)[0];
    gcart_header.splice(_to, 0, _moved);

    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
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

      doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
      doShowCustomize();
    }
  }

  function doButtonSubtotal(_mode) {
    let _str = '';
    if (_mode === 0) {
      // TANPA SUBTOTAL
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonSubtotal(0)">Tanpa Subtotal</div>';
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonSubtotal(1)">Pakai Subtotal</div>';
    } else {
      // PAKAI SUBTOTAL
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonSubtotal(0)">Tanpa Subtotal</div>';
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonSubtotal(1)">Pakai Subtotal</div>';
    }
    $("#buttonSubtotal").html(_str);

    gsum_issubtotal = _mode;

    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
  }

  function doButtonGrandtotal(_mode) {
    let _str = '';
    if (_mode === 0) {
      // TANPA GRANDTOTAL
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonGrandtotal(0)">Tanpa Grand Total</div>';
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonGrandtotal(1)">Pakai Grand Total</div>';
    } else {
      // PAKAI GRANDTOTAL
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonGrandtotal(0)">Tanpa Grand Total</div>';
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonGrandtotal(1)">Pakai Grand Total</div>';
    }
    $("#buttonGrandtotal").html(_str);

    gsum_isgrandtotal = _mode;

    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
  }

  /* ============== END OF CUSTOMIZE HEADER ============== */



  /* ==============   START SETTING TOTAL   ============== */

  function doShowFormSettingTotal(_index) {
    g_modeModal = gmodal_settingtotal;

    gsettotal_index = _index;
    if (gcart_header[_index][3] == "float" || gcart_header[_index][3].startsWith("float")) {
      $("#setTotalDesimalPanel").show();
    } else {
      $("#setTotalDesimalPanel").hide();
    }
    $("#setTotalDesimal").val(gcart_header[_index][5]);
    doButtonSetTotalMode(gcart_header[_index][4]);

    $("#formCustomizeTable").css('opacity', '0.6');
    $("#formSettingTotalLabel").html("Setting Kolom " + gcart_header[_index][1]);
    $("#formSettingTotal").modal('toggle');
  }

  function doCloseFormSettingTotal() {
    g_modeModal = gmodal_customizetable;
    $("#formCustomizeTable").css('opacity', '1');
    $("#formSettingTotal").modal('toggle');
  }

  // Staging (belum disimpan) untuk toggle "Pakai Total" di modal Setting Total --
  // beda dari doButtonTotal(_index) di bawah, yang dipakai menu roda gigi header
  // interaktif dan langsung menyimpan. Nama lama fungsi ini ("doButtonTotal")
  // sekarang dipakai kontrak report-table.js, jadi dipindah ke nama ini.
  function doButtonSetTotalMode(_mode) {
    let _str = '';
    if (_mode === 0) {
      // TANPA TOTAL
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonSetTotalMode(0)">Tanpa Total</div>';
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonSetTotalMode(1)">Pakai Total</div>';
    } else {
      // PAKAI TOTAL
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonSetTotalMode(0)">Tanpa Total</div>';
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonSetTotalMode(1)">Pakai Total</div>';
    }
    $("#buttonSetTotal").html(_str);

    gsettotal_nowtotal = _mode;
  }

  // Toggle "tampilkan total" kolom _index langsung tersimpan -- dipakai menu roda
  // gigi header interaktif (report-table.js / window.ReportTable), kontraknya sama
  // dengan doButtonTotal di masterreport2.blade.php.
  var gct_desimal_max = 4;

  function doButtonTotal(_index) {
    gcart_header[_index][4] = (Number(gcart_header[_index][4]) === 1) ? 0 : 1;

    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
    doShowCustomize();
  }

  // Ubah jumlah desimal kolom _index sebesar _step (+1/-1), lalu simpan -- dipakai
  // menu roda gigi header interaktif.
  function doSetDesimal(_index, _step) {
    let _next = Number(gcart_header[_index][5]) + _step;
    if (_next < 0 || _next > gct_desimal_max) { return; }

    gcart_header[_index][5] = _next;
    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
    doShowCustomize();
  }

  function doSimpanFormSettingTotal() {
    if ($("#setTotalDesimal").val() == "") { $("#setTotalDesimal").val(0); }
    if (toInteger($("#setTotalDesimal").val()) < 0) {
      alertify.warning("Jumlah desimal tidak boleh lebih kecil dari nol");
      return;
    }
    gcart_header[gsettotal_index][4] = gsettotal_nowtotal;
    gcart_header[gsettotal_index][5] = toInteger($("#setTotalDesimal").val());
    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);

    doCloseFormSettingTotal();
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

  // async:false dulu (sengaja diganti dari default true) -- efeknya request ini MEMBLOKIR
  // main thread sampai response datang, jadi browser tidak sempat repaint apa pun (termasuk
  // spinner loadingHtml() yang dipasang sebelum manggil ini) sampai datanya baru muncul
  // sekaligus. Sekarang async:true, jadi kode yang tadinya membaca gcart_res/gcart_filter
  // atau state lain LANGSUNG setelah manggil doMakeTable() (dengan asumsi itu sudah sinkron)
  // harus pindah ke _callback -- lihat pemanggil doMakeTable() di tiap halaman
  // reportstock*.blade.php & doShowFormFilterData() di bawah.
  function doMakeTable(_mode, _groupby, _data, _title, _date1, _date2 = null, _callback) {
    let url = "{!! url('" + g_href + "_doReport') !!}";


    $.ajax({
      url     : url,
      type    : "get",
      async   : true,
      data    : _data,
      success: function(res) {
        if (_mode == "REPORT") {
          gcart_res = res;
          document.getElementById("showTableReport").style.display = "block";
          doShowReport(res, _title, _groupby, _date1, _date2);
          alertify.success("Report ditampilkan");
        } else if (_mode == "FILTER") {
          gcart_filter = res;
          gfilter_title = _title;
          gfilter_groupby = _groupby;
          gfilter_date1 = _date1;
          gfilter_date2 = _date2;
        }
        if (typeof _callback === "function") { _callback(res); }
      }
    })
  }

  function doShowReport(_res, _reportTitle, _groupby, _date1, _date2 = null) {
    let tempcart = gcart_header;
    let _cellcount = 0;
    tempcart.forEach((item, i) => {
      _cellcount += item[2];
    });

    let rowTable = "";
    gsum_rowSubtotal = 0;

    // TABLE HEADER
    doSetColCart(gcart_header);
    $("#tabel_header").html(doSetRowHeader(_reportTitle, tempcart, _cellcount, _date1, _date2));

    // TABLE DATA
    rowTable = '';
    if (_res.length > 0) {
      rowTable = doSetRowTable(_res, tempcart, _groupby);
    } else {
        rowTable += "<tr style='text-align: center'>";
        rowTable += '  <td colspan="' + _cellcount + '" style="border: 1px solid black;">Tidak ada data ditemukan</td>';
        rowTable += '  <td style="display: none;"></td>'.repeat(_cellcount-1);
        rowTable += "</tr>";
    }

    // TABLE FOOTER
    rowTable += ($.isFunction(window.setRowFooter)) ? setRowFooter() : "";

    $("#tabel_data").html(rowTable);


    // POSISI TULISAN TOTAL & GRAND TOTAL
    if (_res.length > 0) { doSetPosisiTulisanTotal(_cellcount); }

    doGodown();
  }

  function doSetColCart(_tempcart) {
    gsum_colCart = {};
    _tempcart.forEach((item, i) => {
      if ((item[2] === 1) && (item[4] === 1) && (item[3] == "float" || item[3] == "int" || item[3].startsWith("float"))) {
        gsum_colCart["stot"+item[0]] = 0;
        gsum_colCart["gtot"+item[0]] = 0;
      }
    });
  }

  function doSetRowHeader(_reportTitle, _tempcart, _cellcount, _date1, _date2 = null) {
    let rowHeader = "";

    // rowHeader += doSetRowHeaderInfo(_reportTitle, _cellcount, _date1, _date2);

    // jika butuh header custom, buatlah function setRowHeader(rowHeader) di Blade
    if ($.isFunction(window.setRowHeader)) { return setRowHeader(rowHeader); }

    rowHeader += doSetRowHeaderDefault(_tempcart);

    return rowHeader;
  }

  function doSetRowHeaderInfo(_reportTitle, _cellcount, _date1, _date2) {
    let rowHeader = "";

    if (_date1 != null) {
      _date1 = (_date1.length == 7) ? format_date(_date1 + "-01", true, "-", "/", false) : format_date(_date1, true);
    }
    if (_date2 != null) {
      _date2 = (_date2.length == 7) ? format_date(_date2 + "-01", true, "-", "/", false) : format_date(_date2, true);
    }

    rowHeader += '<tr>';
    // rowHeader += '  <th colspan="' + _cellcount + '" style="text-align: left; font-weight: bold;">{!! $akses['program'] !!}<br/> ' + _reportTitle + '</th>';
    rowHeader += '</tr>';
    rowHeader += '<tr>';
    if (_date1 == null && _date2 == null) {
      // nothing happen
    } else if (_date1 == null || _date2 == null) {
      let _date = (_date1 != null) ? _date1 : _date2;
      rowHeader += '  <th colspan="' + _cellcount + '"  style="text-align: left; font-weight: bold;">PERIODE: S.D ' + _date + '</th>';
    } else {
      rowHeader += '  <th colspan="' + _cellcount + '"  style="text-align: left; font-weight: bold;">PERIODE: ' + _date1 + ' S.D ' + _date2 + '</th>';
    }
    rowHeader += '</tr>';

    rowHeader += '<tr>';
    // rowHeader += '  <th colspan="' + _cellcount + '"  style="text-align: left; font-weight: bold;">Dicetak Oleh :  ' + '  {!! $akses['user'] !!}  //  Tanggal : '+ getDateIndo() +' // Jam : ' + getTimeNow() + '</th>';
    rowHeader += '</tr>';

    rowHeader += '<tr>';
    rowHeader += '  <th colspan="' + _cellcount + '"></th>';
    rowHeader += '</tr>';

    return rowHeader;
  }

  function doSetRowHeaderDefault(_tempcart) {
    let rowHeader = "";
    let posCount = 0;
    gsum_posArray = [];

    rowHeader += '<tr style="height: 45px; padding: 20px; " class="text-center bg-dark text-light">';
    _tempcart.forEach((item, i) => {
      if (item[2]) {
        posCount += 1;
        if (item[0] == "Nomor") {
          rowHeader += '  <th scope="col" style="border: 1px solid black;">No</th>';
        } else {
          rowHeader += '  <th scope="col" style="border: 1px solid black;">' + item[1] + '</th>';
          if ((item[4] === 1) && (item[3] == "float" || item[3] == "int" || item[3].startsWith("float"))) {
            gsum_posArray.push(posCount);
          }
        }
      }
    });
    rowHeader += '</tr>';

    return rowHeader;
  }

  function doSetRowTable(_res, _tempcart, _groupby) {

    console.log('doSetRowTable' , _res, _tempcart, _groupby)
    // jika ada table custom, buatlah function setRowTable di Blade
    if ($.isFunction(window.setRowTable)) { return setRowTable(); }

    let _prevdata = "", _nowdata = "";
    let _countSubtotal = 0;
    let rowTable = "";

    _res.forEach((item, i) => {
      console.log('item' , item)

      console.log('countsubtotal' , _countSubtotal)
      _nowdata = item[_groupby];

      // SUBTOTAL
      if (i != 0 && _prevdata != _nowdata) {
        rowTable += doSetRowSubtotal(_tempcart);
      }

      // SUBHEADER
      if (i == 0 || _prevdata != _nowdata) {
        rowTable += ($.isFunction(window.setRowSubheader)) ? setRowSubheader(item) : "";
      }

      // ROW
      rowTable += "<tr style='text-align: center'>";
      // gsum_movingSum[_tempcart[0][0]] = 0
      _tempcart.forEach((itemcart, j) => {
        console.log('itemcart',itemcart)
        if (itemcart[2]) {
          if (itemcart[3] == "index") {
            rowTable += '  <td class="cellcompact-center" style="border: 1px solid black;">' + (i+1) + '</td>';
          } else if (itemcart[3] == "date") {
            rowTable += '  <td class="cellcompact-center" style="border: 1px solid black;">' + format_date(item[itemcart[0]]) + '</td>';
          } else if (itemcart[3].startsWith("float")) {
            let _joinCol = itemcart[3].slice(5); // kolom join (cth: satuan)
            if (_joinCol != "") { _joinCol = " " + nullToEmpty(item[_joinCol]); }

            let _value = currencyNormalizer(item[itemcart[0]]);
            let _decimal = itemcart[5];
            if (itemcart[4] === 1) { gsum_colCart["stot"+itemcart[0]] += _value; }
            rowTable += '  <td class="cellcompact-right" style="border: 1px solid black; text-align: right;">' + format_number(_value,_decimal) + _joinCol + '</td>';
          } else if (itemcart[3] == "sumfloat") {

            console.log("masuk sumfloat")
            console.log('-----')
            console.log(gsum_colCart["stot"+itemcart[0]])
            doAddTempMovingSum(itemcart[0], currencyNormalizer(item[itemcart[0]]));
            let _value = gsum_movingSum[itemcart[0]];
            console.log(_value)
            let _decimal = itemcart[5];
            if (itemcart[4] === 1) { gsum_colCart["stot"+itemcart[0]] += _value; }
            rowTable += '  <td class="cellcompact-right" style="border: 1px solid black; text-align: right;">' + format_number(_value,_decimal) + '</td>';
          } else if (itemcart[3] == "empty") {
            rowTable += '  <td class="cellcompact-left" style="border: 1px solid black; white-space:nowrap;"></td>';
          } else if (itemcart[3].startsWith("koreksi")) {
            let _nobukti = nullToEmpty(item[itemcart[0]]);
            rowTable += doSetTransaksiCell(gmodeTrans_koreksi, _nobukti, itemcart[3]);
          } else if (itemcart[3].startsWith("view")) {
            let _nobukti = nullToEmpty(item[itemcart[0]]);
            rowTable += doSetTransaksiCell(gmodeTrans_view, _nobukti, itemcart[3]);
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

      if (gsum_issubtotal === 1) {
        rowTable += '<tr id="strow' + gsum_rowSubtotal + '" style="text-align: center">';
        _tempcart.forEach((itemcart, j) => {
          if (itemcart[2] === 1) {
            _counter++;
            if ((itemcart[4] === 1)) {
              let _value = gsum_colCart["stot"+itemcart[0]];
              let _decimal = itemcart[5];

              if (itemcart[3] == "float" || itemcart[3].startsWith("float")) {
                rowTable += '  <td class="st' + _counter + ' cellcompact-right" style="border: 1px solid black; font-weight: bold; text-align: right;">' + format_number(_value,_decimal) + '</td>';
              } else if (itemcart[3] == "int") {
                rowTable += '  <td class="st' + _counter + ' cellcompact-right" style="border: 1px solid black; font-weight: bold; text-align: right;">' + _value + '</td>';
              }
            } else {
              rowTable += '  <td class="st' + _counter + ' cellcompact-right" style="border: 1px solid black; font-weight: bold; text-align: right;"></td>';
            }
          }
        });
        rowTable += "</tr>";
      }

      // nilai stot ditambahkan ke gtot
      _tempcart.forEach((itemcart, j) => {
        if ((itemcart[4] === 1) && (itemcart[3] == "float" || itemcart[3] == "int" || itemcart[3].startsWith("float"))) {
          gsum_colCart["gtot"+itemcart[0]] += gsum_colCart["stot"+itemcart[0]];
        }
      });

      // stot direset menjadi nol
      _tempcart.forEach((itemcart, j) => {
        if ((itemcart[4] === 1) && (itemcart[3] == "float" || itemcart[3] == "int" || itemcart[3].startsWith("float"))) {
          gsum_colCart["stot"+itemcart[0]] = 0.00;
        }
      });
    }

    return rowTable;
  }

  function doSetRowGrandtotal(_tempcart) {
    let rowTable = '';
    if (Object.keys(gsum_colCart).length > 0) {
      rowTable += '<tr id="gtrow" style="text-align: center">';

      let _counter = 0;
      _tempcart.forEach((itemcart, j) => {
        if ((itemcart[2])) {
          _counter++;
          if ((itemcart[4] === 1)) {
            let _value = gsum_colCart["gtot"+itemcart[0]];
            let _decimal = itemcart[5];

            if (itemcart[3] == "float" || itemcart[3].startsWith("float")) {
              rowTable += '  <td id="gt' + _counter + ' cellcompact-right" style="border: 1px solid black; font-weight: bold; text-align: right;">' + format_number(_value,_decimal) + '</td>';
            } else if (itemcart[3] == "int") {
              rowTable += '  <td id="gt' + _counter + ' cellcompact-right" style="border: 1px solid black; font-weight: bold; text-align: right;">' + _value + '</td>';
            }
          } else {
            rowTable += '  <td id="gt' + _counter + '" class="cellcompact-right" style="border: 1px solid black; font-weight: bold; text-align: right;"></td>';
          }
        }
      });
      rowTable += "</tr>";
    }

    return rowTable;
  }

  function doSetPosisiTulisanTotal(_cellcount) {
    if (gsum_issubtotal === 1) {
      for (let i = 0; i < gsum_rowSubtotal; i++) {
        doEmptyCellMerger("strow" + (i+1), "class", "st", "Total :");
      }
    }
    if (gsum_isgrandtotal === 1) {
      doEmptyCellMerger("gtrow", "id", "gt", "Grand Total :");
    }
  }

  function doEmptyCellMerger(_id, _firstType, _firstID, _firstStr) {
      let row = $("#" + _id);
      if (row.length === 0) return; // Exit if the row doesn't exist

      let emptyCount = 0;
      let tdElements = row.find("td");
      let firstCellMerged = false; // Track if the first cell in the row is merged

      tdElements.each(function () {
          let $td = $(this);
          if ($td.text().trim() === "") {
              emptyCount++;
              $td.addClass("to-remove"); // Mark empty cells for removal
          } else {
              if (emptyCount > 0) {
                  // Insert a new merged <td> before this non-empty one
                  let newTd = $("<td>")
                      .attr("colspan", emptyCount)
                      .attr("style", "border: 1px solid black; font-weight: bold; text-align: right;")
                      .addClass("cellcompact-right");

                  if (!firstCellMerged) {
                      // If this is the first merged cell, assign id/class and insert _firstStr
                      if (_firstType === "id") {
                          newTd.attr("id", _firstID + "1");
                      } else if (_firstType === "class") {
                          newTd.addClass(_firstID + "1");
                      }
                      newTd.text(_firstStr);
                      newTd.addClass(_firstID);
                      firstCellMerged = true;
                  }

                  newTd.insertBefore($td);
                  emptyCount = 0; // Reset counter
              } else {
                firstCellMerged = true; // If first item is non-empty cell
              }
          }
      });

      // Merge remaining empty cells at the end of the row
      if (emptyCount > 0) {
          let newTd = $("<td>")
              .attr("colspan", emptyCount)
              .attr("style", "border: 1px solid black; font-weight: bold; text-align: right;")
              .addClass("cellcompact-right");

          if (!firstCellMerged) {
              if (_firstType === "id") {
                  newTd.attr("id", _firstID + "1");
              } else if (_firstType === "class") {
                  newTd.addClass(_firstID + "1");
              }
              newTd.text(_firstStr);
              newTd.addClass(_firstID);
              firstCellMerged = true;
          }

          newTd.appendTo(row);
      }

      // Remove original empty <td> elements
      $(".to-remove").remove();
  }

  function doAddTempMovingSum(_key, _value) {
      if (!gsum_movingSum[_key]) {
          gsum_movingSum[_key] = 0;
      }
      gsum_movingSum[_key] += _value;
  }

  function doRenameSubTotal(_str = "Total : ", _newalign = galign_center) {
    $(".st1").html(_str);
    if (_newalign != "right") {
      $(".st1").removeClass('cellcompact-right').addClass('cellcompact-'+_newalign);
    }
  }

  function doRenameGrandTotal(_str = "Grand Total : ", _newalign = galign_center) {
    $("#gt1").html(_str);
    if (_newalign != "right") {
      $("#gt1").removeClass('cellcompact-right').addClass('cellcompact-'+_newalign);
    }
  }

  function doSetTransaksiCell(_modetrans, _nobukti, _item) {
    let _regex = new RegExp("^" + _modetrans + "(.*)");
    let _match = _item.match(_regex);
    let _trans = _match ? _match[1] : null;

    return '<td class="cellcompact-left" style="border: 1px solid black; white-space:nowrap; cursor:pointer;" onclick="doOpenTransaksi(\'' + _nobukti + '\', \'' + _trans + '\', \'' + _modetrans + '\')" onmouseover="this.style.backgroundColor=\'#0069d9\'; this.style.color=\'white\'" onmouseout="this.style.backgroundColor=\'\'; this.style.color=\'\'">' + _nobukti + '</td>';
}

  function doOpenTransaksi(_nobukti, _trans, _modetrans) {
    let encodedNoBukti = encodeURIComponent(_nobukti);
    let url = `{!! url('${_trans}?nobukti=${encodedNoBukti}&modereport=${_modetrans}') !!}`;
    window.open(url, '_blank');
  }

  /* ==============   END OF MAIN REPORT    ============== */



  /* ==============    START FILTER DATA    ============== */

  function doShowFormFilterData() {
    g_modeModal = gmodal_filterdata;

    // doMakeTable() kini async -- gcart_filter baru terisi setelah response datang, jadi
    // doShowFilter() (yang membaca gcart_filter) HARUS jalan sebagai callback, bukan
    // langsung sesudah makeTable("FILTER") seperti dulu. Modal-nya juga dipindah ke sini
    // (bukan dibuka duluan) supaya tidak sempat kelihatan kosong/isi lama sebelum terisi.
    makeTable("FILTER", function () {
      doShowFilter();
      // gfilter_totalrow sudah dihitung ulang oleh doShowFilter() dari gfilter_selectedKeys
      // yang tersimpan -- tampilkan labelnya kalau ada baris yang masih terpilih.
      $("#tabelfilter_totalrow").html(gfilter_totalrow > 0 ? "Jumlah baris yang dipilih: " + gfilter_totalrow : "");

      $("#formFilterData").modal('toggle');
    });
  }

  function doCloseFormFilterData() {
    g_modeModal = gmodal_none;
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
            } else if (itemcart[3] == "float" || itemcart[3].startsWith("float")) {
              let _value = currencyNormalizer(item[itemcart[0]]);
              let _decimal = itemcart[5];
              _str += '  <td>' + format_number(_value,_decimal) + '</td>';
            } else {
              _str += '  <td>' + nullToEmpty(item[itemcart[0]]) + '</td>';
            }
          });
          _str += '</tr>';

          let temp = [];
          temp.push(_idx);       // index
          temp.push(_isSelected); // selected or not
          temp.push(_nowdata);   // kunci -- dipakai doSelectrowfilter() untuk update gfilter_selectedKeys
          gcart_filterShow.push(temp);
        } else {
          item._idx = _idx;
        }

        _prevdata = _nowdata;
      });
    } else {
        _str += '<tr>';
        if (cart_filterHeader.length > 0) {
          cart_filterHeader.forEach((item, i) => {
            if (i === 0) {
              _str += '  <td colspan="' + cart_filterHeader.length + '" class="text-center">Tidak ada transaksi ditemukan.</td>';
            } else {
              _str += '  <td style="display: none;"></td>';
            }
          });
        }
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
    if (document.getElementById("showTableReport").style.display === "none") { return; }
    ReportExport.toXlsx(tableID || 'tabel', { baseName: @json($akses['xlsfilename']), customName: filename || gxls_filename });
  }

  function doExportTableToCsv(tableID, filename = '') {
    if (document.getElementById("showTableReport").style.display === "none") { return; }
    ReportExport.toCsv(tableID || 'tabel', { baseName: @json($akses['xlsfilename']), customName: filename || gxls_filename });
  }

</script>




@endsection
