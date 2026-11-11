<div id="page2" style="display: none" class="mainpage container-fluid">

    <div class="row" style="margin-top: 0px" id="contentContainer">
        <div class="col-8 text-left">
            {{-- <h2>Form Bank</h2> --}}
        </div>
        <div class="col-4 text-right">
            <button type="button" class="btn btn-danger btn-action-danger btn-pill-primary"
                onclick="buttonCloseForm()">CLOSE</button>
        </div>
    </div>

    <div id= "formAdd" class="">

        <div id="formBsGrid" class="">
            <div class="">
                <!-- <h1>Tes Modal</h1> -->
                <div class="container-fluid">
                    <input type="hidden" name="noUrut" id="input_add_nourut" value="" />
                    <div class="row">
                        <div class="col-12 col-lg-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Transaksi</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <select id="input_add_transaksi" class="form-control form-select-lg mb-3"
                                        aria-label=".form-select-lg example" onChange="onChangeTransaksi()">
                                        <option value='BBK' selected>BBK</option>
                                        <option value='BBM'>BBM</option>
                                    </select>
                                </div>

                            </div>

                            <div class="row kas-row-tight">

                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Perkiraan</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group input-group">
                                        <input type="hidden" class="form-control" id="input_add_simbol" placeholder=""
                                            disabled>
                                        <input type="text" class="form-control" id="input_add_kodeperkiraan"
                                            placeholder="" disabled>
                                        <button class="btn btn-chip-biru text-right" id="buttonAddListPerkiraan"
                                            onclick="buttonAddListPerkiraan()"><i class="bi bi-search"></i></button>
                                    </div>
                                </div>

                                <div class="col-md-5">

                                </div>


                                <div class="col-md-6" style="margin-top:-10px">
                                    <div class="form-group">
                                        <input type="text" style="width: 100%; resize: none" rows=1 class="form-control" id="input_add_keteranganperkiraan" disabled/>
                                    </div>
                                </div>


                            </div>

                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">


                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>No Bukti</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control"
                                                            id="input_add_nobukti" placeholder="No Bukti" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="row">

                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Tgl</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <input type="date" class="form-control text-center"
                                                            id="input_add_tanggal" value="{!! date('Y-m-d') !!}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row kas-row-tight">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="partBBK showhidePart">Kepada</label>
                                                <label class="partBBM showhidePart">Terima</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <input type="text" class="form-control" id="input_add_kepadaterima"
                                                    placeholder="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row kas-row-tight">
                                <div class="col-md-12">
                                    <div class="row">
                                        <input type="hidden" class="form-control partBBK showhidePart"
                                            id="input_add_bon" placeholder="" disabled>
                                        <input type="hidden" class="form-control text-right partBBK showhidePart"
                                            id="input_add_nilaibon" value="0.00" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <hr />
                </div>

                <div class="container-fluid mt-4" style="overflow-x: auto; padding:0; margin:0;">
                    <div class="dph-table-outer">
                        <div class="dph-table-wrap">
                            <table id="addTable" class="dph-tb">
                                <thead>
                                    <tr>
                                        <th scope="col">Devisi</th>
                                        <th scope="col">Perk.</th>
                                        <th scope="col">Ket. Perk</th>
                                        <th scope="col">Lawan</th>
                                        <th scope="col">Ket. Lawan</th>
                                        <th scope="col" class="num">Jumlah</th>
                                        <th scope="col">Keterangan</th>
                                        <th scope="col">Costing</th>
                                        <th scope="col">Sub Cost</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>

                                <tbody id="addTableData" class="">
                                    <tr>
                                        <td colspan="9" class="text-center">Belum ada data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 text-right">

                </div>
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-12  text-right" style="padding: 1rem 0 ;">
                            <button id="buttonAddAddItem" type="button" class="btn btn-chip-biru"
                                onclick="buttonAddAddItem()">Tambah</button>
                        </div>
                    </div>
                </div>

                <div id="formAddAdd" class="container-fluid showhideitem">
                    <div class="col-12">
                        {{-- <hr /> --}}
                        <div class="row">
                            <div class="col-md-12">
                                {{-- <h4 id="labelAddAddItem">Add Item</h4>
                                <h4 id="labelAddEditItem">Edit Item</h4> --}}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-lg-4">
                                <div class="row">
                                    <div class="col-md-12">

                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Devisi</label>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {{-- Devisi & Valas: dropdown, bukan modal picker lagi. Kelasnya sama dengan
                                                         dropdown Transaksi di atas. Opsi diisi bankMuatDevisiValas() di
                                                         public/js/bank.js saat halaman dibuka; opsi di sini hanya cadangan
                                                         sampai daftarnya termuat. Nama devisi tetap disimpan di
                                                         #AddAddNamaDevisi (hidden) untuk kode yang membacanya. --}}
                                                    <select id="AddAddKodeDevisi" class="form-control form-select-lg"
                                                        onchange="onChangeDevisi()">
                                                        <option value="01" data-nama="Accounting">01 - Accounting</option>
                                                    </select>
                                                    <input id="AddAddNamaDevisi" type="hidden" value="Accounting">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row kas-row-tight">
                                            <div class="col-md-12">
                                                <div class="row">

                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Valas</label>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            {{-- Opsi "IDR - Rupiah - 1.00"; data-kurs mengisi Kurs saat dipilih
                                                                 (onChangeValas() di public/js/bank.js). --}}
                                                            <select id="AddAddValas" class="form-control form-select-lg"
                                                                onchange="onChangeValas()">
                                                                <option value="IDR" data-kurs="1">IDR</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Kurs</label>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="input-group form-group">
                                                            <input id="AddAddKurs" type="number" value="1.00"
                                                                class="text-right form-control" disabled>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row kas-row-tight">
                                            <div class="col-md-12">
                                                <div class="row">

                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Lawan</label>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-5">
                                                        {{-- Lawan bisa diketik lalu Enter: kode persis cocok -> langsung
                                                             dipilih, selain itu daftar Lawan dibuka dan disaring dengan teks
                                                             tsb - lihat onKeyPressLawan() di public/js/bank.js. --}}
                                                        <div class="input-group form-group">
                                                            <input id="AddAddLawan" type="text"
                                                                class="form-control" onkeypress="onKeyPressLawan(event)"
                                                                oninput="onInputLawan()">
                                                            <input id="AddAddKodeLawan" type="hidden"
                                                                class="form-control" disabled>
                                                            <button id="buttonAddListLawan" type="button"
                                                                onclick="buttonAddListLawan()"
                                                                class="btn btn-chip-biru"><i
                                                                    class="bi bi-search"></i></button>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-5">
                                                        <div class="input-group form-group">
                                                            <input id="AddAddKeteranganLawan" type="text"
                                                                class="form-control" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- No Titipan: hanya BBK + Lawan 113400. Bisa diketik lalu Enter, atau
                                             browse lewat tombol cari - lihat buttonAddListTitipan() di
                                             public/js/bank.js. Tampil/sembunyi lewat resetTitipan(). --}}
                                        <div class="row kas-row-tight" id="rowNoTitipan" style="display:none">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>No Titipan</label>
                                                </div>
                                            </div>

                                            <div class="col-md-9">
                                                <div class="input-group form-group">
                                                    <input id="AddAddNoTitipan" type="text" class="form-control"
                                                        onkeypress="onKeyPressNoTitipan(event)">
                                                    <input id="AddAddUrutTitipan" type="hidden">
                                                    <button id="buttonAddListTitipan" type="button"
                                                        onclick="buttonAddListTitipan()" class="btn btn-chip-biru"><i
                                                            class="bi bi-search"></i></button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row kas-row-tight" id="rowCustsupp">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Custsupp</label>
                                                </div>
                                            </div>

                                            <div class="col-md-5">
                                                <div class="input-group form-group">
                                                    <input id="AddAddKodeCustsupp" type="text"
                                                        class="form-control" disabled>
                                                    <button id="buttonAddListCustsupp" type="button"
                                                        onclick="buttonAddListCustsupp()" class="btn btn-chip-biru"><i
                                                            class="bi bi-search"></i></button>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="input-group form-group">
                                                    <input id="AddAddNamaCustsupp" type="text"
                                                        class="form-control" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-3">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Jumlah</label>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="input-group form-group">
                                                    <input id="AddAddJumlah" type="text" value="0.00"
                                                        class="text-right form-control"
                                                        oninput='formatAngkaKetik(this)'
                                                        onblur='formatAngkaInput(this)'>
                                                    {{-- Buka ulang Pelunasan Hutang (BBK + Lawan lokal/exim, mode tambah).
                                                         Tampil/sembunyi lewat aturTombolBukaTunai() di public/js/bank.js. --}}
                                                    <button id="buttonBukaTunai" type="button"
                                                        onclick="buttonBukaTunai()" class="btn btn-chip-biru"
                                                        title="Pelunasan Hutang" style="display:none"><i
                                                            class="bi bi-list-check"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-5">
                                <div class="row">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Departemen</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group form-group">
                                                <input id="AddAddKodeDepartemen" type="text" class="form-control"
                                                    disabled>
                                                <button id="buttonAddListDepartemen" type="button"
                                                    onclick="buttonAddListDepartemen()" class="btn btn-chip-biru"><i
                                                        class="bi bi-search"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group form-group">
                                                <input id="AddAddNamaDepartemen" type="text" class="form-control"
                                                    disabled>

                                            </div>
                                        </div>
                                    </div>

                                    <div class="row kas-row-tight" id="rowCosting">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Costing</label>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="input-group form-group">
                                                <input id="AddAddKodeCosting" type="text" class="form-control"
                                                    disabled>
                                                <button id="buttonAddListCosting" type="button"
                                                    onclick="buttonAddListCosting()"
                                                    class="btn btn-primary">+</button>

                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="input-group form-group">
                                                <input id="AddAddNamaCosting" type="text" class="form-control"
                                                    disabled>

                                            </div>
                                        </div>

                                    </div>

                                    <div class="row kas-row-tight" id="rowSubCosting">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>SubCosting</label>
                                            </div>
                                        </div>
                                        <!-- <div class="col-4 text-right">

                </div> -->
                                        <div class="col-md-5">
                                            <div class="input-group form-group">
                                                <input id="AddAddKodeSubCosting" type="text" class="form-control"
                                                    disabled>
                                                <button id="buttonAddListSubCosting" type="button"
                                                    onclick="buttonAddListSubCosting()"
                                                    class="btn btn-primary">+</button>

                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="input-group form-group">
                                                <input id="AddAddNamaSubCosting" type="text" class="form-control"
                                                    disabled>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>{{-- /.row Devisi..SubCosting - ditutup DI SINI (sama seperti kas/_form.blade.php),
                                  bukan setelah Keterangan: .row di dalam .row jadi flex item selebar isinya,
                                  sehingga Keterangan & Ket. Det menyempit di samping kolom lain. --}}

                        {{-- Keterangan & Ket. Det dipindah dari kolom tengah (col-lg-3) ke baris penuh
                             supaya inputnya panjang. ID tidak berubah. --}}
                        <div class="row kas-row-tight">
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>Keterangan</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group form-group">
                                    <input id="AddAddKeterangan" type="text" value=""
                                        class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row kas-row-tight">
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>Ket. Det</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group form-group">
                                    <input id="AddAddKeteranganDetail" type="text" value=""
                                        class="form-control">
                                </div>
                            </div>
                            {{-- Batal/Simpan sebaris dengan Ket. Det, rata kanan. id="contentContainer"
                                 wajib dipertahankan: gaya .btn-pill-* di tableMaster2.css hanya berlaku
                                 di dalam #contentContainer. --}}
                            <div class="col-md-5 text-right form-group" id="contentContainer">
                                <button type="button" class="btn btn-danger btn-action-danger btn-pill-primary"
                                    onclick="buttonAddBatal()">Batal</button>

                                <button id="buttonSubmitAddAdd" type="button" onclick="submitAddAdd()"
                                    class="btn btn-primary btn-action-primary btn-pill-primary">Simpan</button>

                                <button id="buttonSubmitAddEdit" type="button" onclick="submitAddEdit()"
                                    class="btn btn-primary btn-action-primary btn-pill-primary">Simpan</button>
                            </div>
                        </div>

                    </div>








                    <!-- <div class="line"></div> -->
                    <!-- <hr/> -->
                </div>
            </div>
            <!-- </div> -->


            <!-- ADD EDIT -->


            <!-- </div> -->



        </div>

        <!-- <div class="row "> -->

        <!-- </div> -->









    </div>










</div>
