<?php


namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\NewMenu;
use App\Model\NewAksesMenu;
use App\Model\DBFLMENU;
use App\Model\NewPeriode;
use App\Model\NewUsers;
use Illuminate\Support\Facades\DB;
use App\Services\OtorisasiService;

class BankController extends Controller


{

  // Satu rentang tanggal dipakai baik oleh index() (first load) maupun loadAll() (AJAX reload
  // dari date picker Periode di toolbar). A.Tanggal dipakai dengan rentang setengah-terbuka
  // [date1, date2+1hari) supaya baris yang timestamp-nya persis di tanggal akhir tidak ikut
  // terbuang, sama seperti PengajuanDPHController::fetchList().
  private function fetchList(string $date1, string $date2)
  {
    return DB::connection("SML")->select("
select  A.NoUrut, A.NoBukti, A.Tanggal, A.Note, '' Devisi, A.PerkiraanHd Perkiraan, A.TipeTransHd,
        sum(case when B.Valas='IDR' then 0.00 else B.Debet+B.Kredit end) TotalD,
        sum((B.Debet+B.Kredit)*B.Kurs) TotalRp,
	A.IsOtorisasi1, A.OtoUser1, A.TglOto1, A.IsOtorisasi2, A.OtoUser2, A.TglOto2,
	A.IsOtorisasi3, A.OtoUser3, A.TglOto3, A.IsOtorisasi4, A.OtoUser4, A.TglOto4,
	A.IsOtorisasi5, A.OtoUser5, A.TglOto5
from dbTrans A
left outer join dbTransaksi B on B.NoBukti=A.NoBukti
where A.Tanggal >= :date1 and A.Tanggal < :date2
and (a.NoBukti like '%BBM%' or a.NoBukti like '%BBK%')




group by A.NoUrut, A.NoBukti, A.Tanggal, A.Note, A.TipeTransHd, A.PerkiraanHd,
	A.IsOtorisasi1, A.OtoUser1, A.TglOto1, A.IsOtorisasi2, A.OtoUser2, A.TglOto2,
	A.IsOtorisasi3, A.OtoUser3, A.TglOto3, A.IsOtorisasi4, A.OtoUser4, A.TglOto4,
	A.IsOtorisasi5, A.OtoUser5, A.TglOto5
Order by A.Nobukti



",[
      "date1" => $date1,
      "date2" => date('Y-m-d', strtotime($date2 . ' +1 day')),
    ]);
  }

  public function index(Request $req) {
    $kodemenu = '02012';

    $akses = app('App\Http\Controllers\GlobalController')->getAkses($kodemenu, $req->path());
    // $akses = DBFLMENU::where('USERID', \Auth::user()->username)-> where('L1', $kodemenu)->first();
    if(!$akses || !$akses->HASACCESS) {
       return redirect('/home');
    }


    $periode = app('App\Http\Controllers\GlobalController')->getPeriode();






    $menul0 = app('App\Http\Controllers\NewMenuController')->getMenuL0(5);

    $date1 = date('Y-m-01', mktime(0, 0, 0, $periode->bulan, 1, $periode->tahun));
    $date2 = date('Y-m-d');

    $tempOutstanding = $this->fetchList($date1, $date2);


    return view('accounting.bank' , [
      "menul0" => $menul0,
      "periode" => $periode,
      "date1" => $date1,
      "date2" => $date2,
      "tempOutstanding" => $tempOutstanding,
      "listCustSuppX" =>[],
      "akses" => $akses
    ]);

  }


    public function spAddAktiva (Request $req) {

      $username = \Auth::user()->username;
      $periode = DB::connection("SML")->select('select TOP 1 * from DBPERIODE where user_id = :username ' , ["username" => $username]);


        $jmlrecord = $req->jmlrecord;
        if ($jmlrecord == 0 ) {
          $check = DB::connection('SML')->select('select * from DBTRANS where Nobukti = :nobukti',["nobukti" => $req->nobukti]);
            if ($check) {
              return 2;
          }
        }
        $plok = DB::connection("SML")->select("Select ISnull(IsLokalOrExim,0) IsLokalOrExim from dbPostHutpiut where perkiraan= :lawan
      " , ["lawan" => $req->lawanx]);



          DB::connection('SML')->statement('exec sp_TransaksiKasBankDPH ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?', [
            $req->choice,
            $req->nobukti,
            $req->nourut,
            $req->tanggal,
            $req->note ? $req->note : '',
            $req->lampiran ? $req->lampiran : '',
            $req->kodedevisi,
            $req->perkiraanx,
            $req->lawanx,
            $req->keterangan ? $req->keterangan : '', // 10
            $req->keterangan2 ? $req->keterangan2 : '',
            $req->jumlah,
            $req->kredit,
            $req->valas,
            $req->kurs,
            $req->jumlahrp,
            $req->kreditrp,
            $req->transaksi,
            $req->tphc,
            $req->custsuppP ? $req->custsuppP : '' , // 20
            $req->custsuppL ? $req->custsuppL : '' ,
            $req->urut,
            $req->noaktivaP ? $req->noaktivaP : '' ,
            $req->noaktivaL ? $req->noaktivaL : '' ,
            $req->statusaktivaP ? $req->statusaktivaP : '',
            $req->statusaktivaL ? $req->statusaktivaL : '',
            $req->nobon ? $req->nobon : '',
            $req->kodedepartemen,
            $req->kodeP ? $req->kodeP : '',
            $req->kodeL ? $req->kodeL : '', // 30
            $req->statusgiro ? $req->statusgiro : '',
            $req->simbol,
            $req->kodeperkiraan,
            $req->flagsimbol ? $req->flagsimbol : '',
            $req->kodecost ? $req->kodecost : '',
            $req->kodesubcost ? $req->kodesubcost : '',
            $req->nodph ?   $req->nodph : '',
            $req->urutdph,
            $req->dppdph ? $req->dppdph : '', // 40
            $username,
            $req->tp ? $req->tp : '',
            $req->ppklx ? $req->ppklx : '',
            $req->nofaktur ? $req->nofaktur : '',
            $plok[0]->IsLokalOrExim,
            $req->nobons ? $req->nobons : '',
            $req->jmlrecord,
            $req->notitipan ? $req->notitipan : '',
            $req->uruttitipan,
            $req->keterangandetail ? $req->keterangandetail : '', // 50
            $req->pSKB,

          ]);

          $check = DB::connection('SML')->select("
            select Perkiraan ,  Bulan, Tahun , Devisi , md from dbaktivadet where Perkiraan = :perkiraan and Bulan = :bulan and Tahun = :tahun
          " , ["perkiraan" => $req->xaktivagroupperkiraan , "bulan" => $periode[0]->bulan , "tahun" => $periode[0]->tahun]);
          if ($check) {

      //       $update = DB::connection('SML')->update("
      //
      // update DBAKTIVADET set MD = :md
      // where Perkiraan = :perkiraan and Bulan = :bulan and Tahun = :tahun " ,
      // ["md" => (int)$req->jumlah + (int)$check[0]->md ,  "perkiraan" => $req->xaktivagroupperkiraan , "bulan" => $periode[0]->bulan , "tahun" => $periode[0]->tahun]
      // );

          } else {


            $add = DB::connection('SML')->statement("
            insert into DBAKTIVADET (Perkiraan , Bulan, Tahun , Devisi , Valas , Kurs , Awal , AwalSusut , MD , DMD , MK , DMK , SD , DSD , SK , DSK )
      values ( :perkiraan , :bulan , :tahun , :devisi  , :valas , :kurs , 0 , 0 , :md , 0 , 0 , 0 , 0 , 0 , 0 , 0 )
            ",
          [
            "perkiraan" => $req->xaktivagroupperkiraan ,
            "bulan" => $periode[0]->bulan ,
            "tahun" => $periode[0]->tahun ,
            "devisi" => $req->devisi ,
            "valas" => $req->valas ,
            "kurs" => $req->kurs ,
            "md" => $req->jumlah ,
          ]);

          }

        return 1;

    }



      public function listAktiva (Request $req) {

        $listData = DB::connection('SML')->select("Select A.*,B.Keterangan NamaGroupAktiva,
           C.Keterangan NamaAkumulasi,
           Case when A.Tipe='L' then '[L]urus'
                when A.Tipe='M' then '[M]enurun'
                when A.Tipe='P' then '[P]ajak'
                else ''''
           end Metode,d.Namabag,e.NamaDevisi
    from dbaktiva a
    left Outer join dbPerkiraan b on b.perkiraan=a.Nomuka
    left Outer join dbperkiraan c on c.perkiraan=a.Akumulasi
    Left Outer Join dbBagian d on d.kodebag=a.kodebag
    left outer join dbDevisi e on e.devisi=a.devisi
    where
     a.Nomuka= :perkiraan and a.Devisi= :devisi ", ["perkiraan" => $req->perkiraan , "devisi" => $req->devisi]);
        return $listData;
      }

      public function listDetailAktiva (Request $req) {

        $listData = DB::connection('SML')->select("Select A.*,B.Keterangan NamaGroupAktiva,
           C.Keterangan NamaAkumulasi,
           Case when A.Tipe='L' then '[L]urus'
                when A.Tipe='M' then '[M]enurun'
                when A.Tipe='P' then '[P]ajak'
                else ''''
           end Metode,d.Namabag,e.NamaDevisi
    from dbaktiva a
    left Outer join dbPerkiraan b on b.perkiraan=a.Nomuka
    left Outer join dbperkiraan c on c.perkiraan=a.Akumulasi
    Left Outer Join dbBagian d on d.kodebag=a.kodebag
    left outer join dbDevisi e on e.devisi=a.devisi
    where a.Perkiraan= :groupaktiva   ", ["groupaktiva" => $req->groupaktiva ]);
        return $listData;
      }

      // kalo keluar dia masuk P
      // kalo masuk masuk ke L
      public function updateDBAktivaDet (Request $req) {
        $check = DB::connection('SML')->select("
          select Perkiraan ,  Bulan, Tahun , Devisi , md from dbaktivadet where Perkiraan = :perkiraan and Bulan = :bulan and Tahun = :tahun
        " , ["perkiraan" => $req->perkiraan , "bulan" => $req->bulan , "tahun" => $req->tahun]);
        if ($check) {

          $update = DB::connection('SML')->update("

    update DBAKTIVADET set MD = :md
    where Perkiraan = :perkiraan and Bulan = :bulan and Tahun = :tahun " ,
    ["MD" => $req->md + $check[0]['MD'] ,  "perkiraan" => $req->perkiraan , "bulan" => $req->bulan , "tahun" => $req->tahun]
    );

        } else {
          $add = DB::connection('SML')->statement("
          insert into DBAKTIVADET (Perkiraan , Bulan, Tahun , Devisi , Valas , Kurs , Awal , AwalSusut , MD , DMD , MK , DMK , SD , DSD , SK , DSK )
    values ( :perkiraan , :bulan , :tahun , :devisi  , :valas , :kurs , 0 , 0 , :md , 0 , 0 , 0 , 0 , 0 , 0 , 0 )
          ",
        [
          "perkiraan" => $req->perkiraan ,
          "bulan" => $req->bulan ,
          "tahun" => $req->tahun ,
          "devisi" => $req->devisi ,
          "valas" => $req->valas ,
          "kurs" => $req->kurs ,
          "MD" => $req->md ,
        ]);

        }

      }

  public function loadAll (Request $req) {


    $periode = app('App\Http\Controllers\GlobalController')->getPeriode();

    $date1 = $req->input('date1');
    $date2 = $req->input('date2');
    if (!$date1 || !$date2) {
        $date1 = date('Y-m-01', mktime(0, 0, 0, $periode->bulan, 1, $periode->tahun));
        $date2 = date('Y-m-d');
    }

    return ["tempOutstanding" => $this->fetchList($date1, $date2)];
  }


  public function getDetail (Request $req ) {



        $tempOutstanding = DB::connection("SML")->select("

                declare @NoBukti varchar(30)

                select @NoBukti= :nobukti

                select z.nilaibon nilaibon, a.NoBukti , w.NAMACUSTSUPP namacustsuppL  , u.NAMACUSTSUPP namacustsuppP
                      , a.Tanggal
                      , a.Devisi
                      , a.Note
                      , a.Lampiran
                      , a.Perkiraan
                      , a.Lawan
                      , a.Keterangan
                      , a.Keterangan2
                      , a.Debet
                      , a.Kredit
                      , a.Valas
                      , a.Kurs
                      , a.DebetRp
                      , a.KreditRp
                      , a.TipeTrans
                      , a.TPHC
                      , a.CustSuppP
                      , a.CustSuppL
                      , a.Urut
                      , a.KodeP
                      , a.KodeL
                      , a.NoAktivaP
                      , a.NoAktivaL
                      , a.StatusAktivaP
                      , a.StatusAktivaL
                      , a.Nobon
                      , a.KodeBag , e.NMDEP
                      , a.StatusGiro
                      , a.FlagSimbol
                      ,a.KODECOST
                      ,a.KODESUBCOST
                      ,a.NODPH
                      ,a.urutDPH
                      ,a.nobons
                      ,a.DBASAL
                      ,a.NOTITIPAN
                      ,a.URUTTITIPAN
                      ,a.KetDetail
                      ,a.pSKB, b.keterangan NamaPerkiraan, d.Keterangan NamaLawan,
                	case when a.valas<>'IDR' then a.debet+ a.kredit else 0 end Jumlah,
                	a.DebetRp+a.KreditRp JumlahRp, c.NamaDevisi,
                	Case when a.TPHC='C' then '[C]Cash'
                		When a.TPHC='T' then '[T]Transfer'
                		when a.TPHC='H' then '[H]Hutang Giro'
                		when a.TPHC='P' then '[P]Piutang Giro'
                                when a.TPHC='R' then '[R]Tukar Giro'
                		else ''
                	end MyTPHC, e.NMDEP NamaBag, f.NOURUT,
                	Case when a.TipeTrans IN ('BBK','BKK') then a.Lawan
                		when a.TipeTrans IN ('BBM','BKM') then a.Perkiraan
                		else ''
                	end mPerkiraan,
                	Case when a.TipeTrans IN ('BBK','BKK') then d.Keterangan
                		when a.TipeTrans IN ('BBM','BKM') then b.Keterangan
                		else ''
                	end mNamaPerkiraan,
                	Case when a.TipeTrans IN ('BBK','BKK') then a.Lawan+char(13)+d.Keterangan
                		when a.TipeTrans IN ('BBM','BKM') then a.Perkiraan+char(13)+b.Keterangan
                		else ''
                	end MyPerkiraan,
                	Case when a.TipeTrans IN ('BBK','BKK') then a.Perkiraan
                		when a.TipeTrans IN ('BBM','BKM') then a.Lawan
                		else ''
                	end MyLawan,
                	Case when a.TipeTrans IN ('BBK','BKK') then b.Keterangan
                		when a.TipeTrans IN ('BBM','BKM') then d.Keterangan
                		else ''
                	end MyNamaLawan,
                	Case when a.Debet<>0 then A.Debet
                		when a.DebetRp<>0 then A.DebetRp
                		else 0
                	end MyDebet,
                	Case when a.Debet+a.Kredit<>0 then a.Debet+a.Kredit
                		when a.DebetRp+a.KreditRp<>0 then a.DebetRp+a.KreditRp
                		else 0
                	end MyJumlah, v.NamaVls, f.Simbol, f.TipeTransHD,
                	ISNULL(GB.NilaiBuka,ISNULL(GC.NilaiCair,0)) JumlahGiro,
                	ISNULL(GB.NilaiBukaRp,ISNULL(GC.NilaiCairRp,0)) JumlahGiroRp,
                        Cs.NamaCost, SCs.NamaSubCost,a.notitipan,a.urutTitipan,
                        a.NoBukti+right('0000000000'+cast(a.Urut as varchar(10)),10)+isnull(a.FlagSimbol,'') KeyUrut,
                        f.PerkiraanHd, phd.Keterangan NamaPerkiraanHd,A.NobonS, case when Bn.NoBukti is null then 'T' else 'Y' end BonKembaliUang
                from dbTransaksi a
                left outer join dbperkiraan b on a.perkiraan=b.perkiraan
                left outer join dbdevisi c on c.Devisi=a.Devisi
                left outer join dbPerkiraan d on d.Perkiraan=a.Lawan
                left outer join dbDepart e on e.KDDEP=a.kodebag
                left outer join dbTrans f on f.nobukti=a.nobukti
                left Outer join dbValas v on v.Kodevls=a.Valas
                left outer join DBCUSTSUPP  w on a.CustSuppL = w.KODECUSTSUPP
                left outer join DBCUSTSUPP u on a.CustSuppP = u.KODECUSTSUPP

                left outer join
                	(select  nobukti,SUM(debet) - SUM(kredit) nilaibon

                	from	dbbon
                	group by nobukti
                	) z on z.nobukti = A.nobons
                left outer join dbCost Cs on Cs.KodeCost=a.KodeCost
                left outer join vwSubCost SCs on SCs.KodeCost=a.KodeCost and SCs.KodeSubCost=a.KodeSubCost
                left outer join dbPerkiraan phd on phd.Perkiraan=f.PerkiraanHd
                left outer join
                	(select BuktiBuka, UrutBuktiBuka, SUM(case when Tipe='PT' then Debet else Kredit end) NilaiBuka,
                		SUM(case when Tipe='PT' then DebetRp else KreditRp end) NilaiBukaRp
                	from	DBGIRO where BuktiBuka=@NoBukti
                	group by BuktiBuka, UrutBuktiBuka
                	) GB on GB.BuktiBuka=A.NoBukti and GB.UrutBuktiBuka=A.Urut
                left outer join
                	(select BuktiCair, UrutBuktiCair, SUM(case when Tipe='PT' then Kredit else Debet end) NilaiCair,
                		SUM(case when Tipe='PT' then KreditRp else DebetRp end) NilaiCairRp
                	from	DBGIRO where BuktiCair=@NoBukti
                	group by BuktiCair, UrutBuktiCair
                	) GC on GC.Bukticair=A.NoBukti and GC.UrutBuktiCair=A.Urut
                left outer join dbBon Bn on Bn.BuktiKas=a.NoBukti and Bn.UrutKas=0
                where a.NoBukti=@NoBukti
                Order by a.Nobukti, a.Urut



" , ["nobukti" => $req->nobukti]);
    return $tempOutstanding;
  }


  public function getNoBukti (Request $req) {
    // return 1;
    $username = \Auth::user()->username;
    $periode = app('App\Http\Controllers\GlobalController')->getPeriode();

    $kode = $req->kode;
    $inisial = DB::connection("SML")->select('select ' . $kode . ' from DBNOMOR');
    // $inisialx = 'KN';
    // return $inisialx;
    $values = [
        $inisial[0]->$kode,
        $periode[0]->bulan,
        $periode[0]->tahun,
        $username,
        $req->simbol
    ];

    $noBukti = DB::connection('SML')->select('exec SP_IsiNobuktiSimbol ?,?,?,?,?',$values);

    return $noBukti;
  }


  public function listKasHeader (Request $req) {

    // $listData = DB::connection('SML')->select("select Perkiraan, Keterangan , Simbol from DBPERKIRAAN where Perkiraan like '1111%' and Tipe = 1");
    $listData = DB::connection('SML')->select("select a.Perkiraan, a.Keterangan , a.Simbol from DBPERKIRAAN a
left outer join DBPOSTHUTPIUT b on a.Perkiraan=b.Perkiraan
where b.Kode='BANK'");


    return $listData;
  }

  public function listCosting (Request $req) {

    // $listData = DB::connection('SML')->select("select Perkiraan, Keterangan , Simbol from DBPERKIRAAN where Perkiraan like '1111%' and Tipe = 1");
    $listData = DB::connection('SML')->select("select a.* , b.Urut, b.Perkiraan  from DBCOST a left outer join DBPERKCOST b on a.KodeCost = b.KodeCost
where b.Perkiraan = :lawan
" , ["lawan" => $req->kodelawan]);


    return $listData;
  }

  public function listSubCosting (Request $req) {

    // $listData = DB::connection('SML')->select("select Perkiraan, Keterangan , Simbol from DBPERKIRAAN where Perkiraan like '1111%' and Tipe = 1");
    $listData = DB::connection('SML')->select("select * from vwSubCost where KodeCost = :kodecosting

" , ["kodecosting" => $req->kodecosting]);


    return $listData;
  }





  public function listValas (Request $req) {

    $listData = DB::connection('SML')->select("select * from DBVALAS");
    return $listData;
  }



  public function listDevisi (Request $req) {

    $listData = DB::connection('SML')->select("select Devisi, NamaDevisi from dbdevisi");
    return $listData;
  }


  public function listPTHT (Request $req) {

    $listData = DB::connection('SML')->select("select D.Nobukti,e.NOBUKTI NOUM,A.KODECUSTSUPP,C.NAMACUSTSUPP
                          ,D.Valas,Sum(A.DIBAYAR) DIBAYAR ,X.KL,SUM(LB) LB
                               from DBDPHDET A
                           Left Outer Join dbTransaksi B on A.NoBukti=B.NODPH and A.KodeCUstSupp=B.CustSUppP
                           Left OUter Join DBCUSTSUPP C on A.KODECUSTSUPP=C.KODECUSTSUPP
                           Left Outer JOin DbDPH D on A.Nobukti=D.Nobukti
                           Left Outer Join (select nobukti,KodeCustSupp,Sum(NilaiK) KL from DBKurangBayar group by  nobukti,KodeCustSupp)  X on A.nobukti=X.Nobukti and A.KodeCustSupp=X.kodeCustSupp
                         left outer join (select nobukti,noso from dbUMjual group by nobukti,noso) E on a.nofaktur=e.nobukti
                         left outer join (select nopohd from dbbeli group by NOPOHD) F on e.noso=f.nopohd
                           where B.NoBukti Is null       and f.nopohd is null
                         And D.Tipe= :tipe   and  D.Valas= :valas
                           and Cast(Case when Case when D.IsOtorisasi1=1 then 1 else 0 end+
                           Case when D.IsOtorisasi2=1 then 1 else 0 end+
                           Case when D.IsOtorisasi3=1 then 1 else 0 end+
                          Case when D.IsOtorisasi4=1 then 1 else 0 end+
                                            Case when D.IsOtorisasi5=1 then 1 else 0 end=D.MaxOL then 0
                                          else 1
                                     end As Bit)=0   --and E.nobukti is null
                            group by D.Nobukti,A.KODECUSTSUPP,C.NAMACUSTSUPP,E.nobukti
                          ,D.Valas,X.kl" , ["tipe" => $req->tipe , "valas" => $req->valas ]);
    return $listData;
  }

  public function listDPH (Request $req) {

    $listData = DB::connection('SML')->select("select D.Nobukti,e.NOBUKTI NOUM,A.KODECUSTSUPP,C.NAMACUSTSUPP
                          ,D.Valas,Sum(A.DIBAYAR) DIBAYAR ,X.KL,SUM(LB) LB
                               from DBDPHDET A
                           Left Outer Join dbTransaksi B on A.NoBukti=B.NODPH and A.KodeCUstSupp=B.CustSUppP
                           Left OUter Join DBCUSTSUPP C on A.KODECUSTSUPP=C.KODECUSTSUPP
                           Left Outer JOin DbDPH D on A.Nobukti=D.Nobukti
                           Left Outer Join (select nobukti,KodeCustSupp,Sum(NilaiK) KL from DBKurangBayar group by  nobukti,KodeCustSupp)  X on A.nobukti=X.Nobukti and A.KodeCustSupp=X.kodeCustSupp
                         left outer join (select nobukti,noso from dbUMjual group by nobukti,noso) E on a.nofaktur=e.nobukti
                         left outer join (select nopohd from dbbeli group by NOPOHD) F on e.noso=f.nopohd
                           where B.NoBukti Is null       and f.nopohd is null
                         And D.Tipe= 'DPH'   and  D.Valas= :valas
                           and Cast(Case when Case when D.IsOtorisasi1=1 then 1 else 0 end+
                           Case when D.IsOtorisasi2=1 then 1 else 0 end+
                           Case when D.IsOtorisasi3=1 then 1 else 0 end+
                          Case when D.IsOtorisasi4=1 then 1 else 0 end+
                                            Case when D.IsOtorisasi5=1 then 1 else 0 end=D.MaxOL then 0
                                          else 1
                                     end As Bit)=0   --and E.nobukti is not null
                            group by D.Nobukti,A.KODECUSTSUPP,C.NAMACUSTSUPP,E.nobukti
                          ,D.Valas,X.kl" , ["valas" => $req->valas ]);
    return $listData;
  }

  public function listDPHUHT (Request $req) {

    $listData = DB::connection('SML')->select("select D.Nobukti,e.NOBUKTI NOUM,A.KODECUSTSUPP,C.NAMACUSTSUPP
                          ,D.Valas,Sum(A.DIBAYAR) DIBAYAR ,X.KL,SUM(LB) LB
                               from DBDPHDET A
                           Left Outer Join dbTransaksi B on A.NoBukti=B.NODPH and A.KodeCUstSupp=B.CustSUppP
                           Left OUter Join DBCUSTSUPP C on A.KODECUSTSUPP=C.KODECUSTSUPP
                           Left Outer JOin DbDPH D on A.Nobukti=D.Nobukti
                           Left Outer Join (select nobukti,KodeCustSupp,Sum(NilaiK) KL from DBKurangBayar group by  nobukti,KodeCustSupp)  X on A.nobukti=X.Nobukti and A.KodeCustSupp=X.kodeCustSupp
                         left outer join (select nobukti,noso from dbUMjual group by nobukti,noso) E on a.nofaktur=e.nobukti
                         left outer join (select nopohd from dbbeli group by NOPOHD) F on e.noso=f.nopohd
                           where B.NoBukti Is null       and f.nopohd is null
                         And D.Tipe= 'DPH'   and  D.Valas= :valas
                           and Cast(Case when Case when D.IsOtorisasi1=1 then 1 else 0 end+
                           Case when D.IsOtorisasi2=1 then 1 else 0 end+
                           Case when D.IsOtorisasi3=1 then 1 else 0 end+
                          Case when D.IsOtorisasi4=1 then 1 else 0 end+
                                            Case when D.IsOtorisasi5=1 then 1 else 0 end=D.MaxOL then 0
                                          else 1
                                     end As Bit)=0   --and E.nobukti is not null
                            group by D.Nobukti,A.KODECUSTSUPP,C.NAMACUSTSUPP,E.nobukti
                          ,D.Valas,X.kl             " , ["valas" => $req->valas ]);
    return $listData;
  }


  public function listDPP (Request $req) {

    $listData = DB::connection('SML')->select("select D.Nobukti,e.NOBUKTI NOUM,A.KODECUSTSUPP,C.NAMACUSTSUPP
                          ,D.Valas,Sum(A.DIBAYAR) DIBAYAR ,X.KL,SUM(LB) LB
                               from DBDPHDET A
                           Left Outer Join dbTransaksi B on A.NoBukti=B.NODPH and A.KodeCUstSupp=B.CustSUppP
                           Left OUter Join DBCUSTSUPP C on A.KODECUSTSUPP=C.KODECUSTSUPP
                           Left Outer JOin DbDPH D on A.Nobukti=D.Nobukti
                           Left Outer Join (select nobukti,KodeCustSupp,Sum(NilaiK) KL from DBKurangBayar group by  nobukti,KodeCustSupp)  X on A.nobukti=X.Nobukti and A.KodeCustSupp=X.kodeCustSupp
                         left outer join (select nobukti,noso from dbUMjual group by nobukti,noso) E on a.nofaktur=e.nobukti
                         left outer join (select nopohd from dbbeli group by NOPOHD) F on e.noso=f.nopohd
                           where B.NoBukti Is null       and f.nopohd is null
                         And D.Tipe= 'DPP'   and  D.Valas= :valas
                           and Cast(Case when Case when D.IsOtorisasi1=1 then 1 else 0 end+
                           Case when D.IsOtorisasi2=1 then 1 else 0 end+
                           Case when D.IsOtorisasi3=1 then 1 else 0 end+
                          Case when D.IsOtorisasi4=1 then 1 else 0 end+
                                            Case when D.IsOtorisasi5=1 then 1 else 0 end=D.MaxOL then 0
                                          else 1
                                     end As Bit)=0   -- and E.nobukti is null
                            group by D.Nobukti,A.KODECUSTSUPP,C.NAMACUSTSUPP,E.nobukti
                          ,D.Valas,X.kl" , ["valas" => $req->valas ]);
    return $listData;
  }




  public function listDepartemen (Request $req) {

    $listData = DB::connection('SML')->select("select * from DBDEPART");
    return $listData;
  }


    public function listAkumulasi (Request $req) {

      $listData = DB::connection('SML')->select("Select A.Devisi,b.Namabag,A.Perkiraan, A.Keterangan,A.Tanggal, A.Tipe,
   Case when A.Tipe='L' then '[L]urus'
     when A.Tipe='M' then '[M]enurun'
     when A.Tipe='P' then '[P]ajak'
     else ''
   end Metode,A.Persen,A.Quantity,A.Kodebag,
   A.Akumulasi, D.Keterangan NamaAkumulasi,
   A.NoMuka,C.Keterangan NamaGroupAktiva,A.NoBelakang,A.NoBelakang2, A.Biaya,a.Biaya2,a.PersenBiaya1,a.PersenBiaya2, a.biaya3, a.persenbiaya3,
   E.NamaDevisi,a.TipeAktiva,a.Kelompok
   From DBAktiva A
   left outer join dbBagian b on b.kodebag=a.kodebag
   left outer join dbperkiraan c on c.perkiraan=a.Nomuka and c.tipe=1
   left outer join dbperkiraan d on d.perkiraan=a.Akumulasi and d.Tipe=1
   left outer join dbDevisi e on e.Devisi=a.Devisi
   Where A.NoMuka= :perkiraan or
   A.Akumulasi= :perkiraan2 ", ["perkiraan" => $req->perkiraan , "perkiraan2" => $req->perkiraan ]);
      return $listData;
    }



      public function listAkumulasiInput () {
        $listData = DB::connection('SML')->select("     select a.Perkiraan,b.Keterangan from dbposthutpiut a
    left outer join dbperkiraan b on b.perkiraan=a.perkiraan
    where a.Kode='AKM' order by a.Perkiraan
    ");
          return $listData;

      }

      public function listBiayaInput () {

        $username = \Auth::user()->username;
        $listData = DB::connection('SML')->select("
    select Perkiraan,Keterangan from dbPerkiraan where  tipe=1
     and perkiraan in (select perkiraan from DBAKSESPERKIRAANR where userid= :username )

    " , ["username" => $username ]);
          return $listData;


      }


      // Bentuk baris (faktur dikelompokkan + pelunasan bukti ini) sama dengan Kas - lihat
      // KasController::tunaiRows().
      public function listTunaiX (Request $req) {
        return app('App\Http\Controllers\Accounting\KasController')->tunaiRows($req->nobukti ?? '');
      }


        public function listTunai (Request $req) {

      $username = \Auth::user()->username;
      DB::connection('SML')->statement("delete from dbTempHutPiut where IDUSER = :username",["username"=>$username] );
      DB::connection('SML')->statement("
      declare @NoBukti varchar(30), @KodeCustSupp varchar(30)  , @username varchar(30) , @agent varchar (50) , @lawan varchar(30) -- dr dbcustsupp

      select @NoBukti = :nobukti , @KodeCustSupp = :kodecustsupp , @username = :username, @agent = :agent, @lawan = :lawan




      insert into dbTempHutPiut (NoFaktur, NoRetur, TipeTrans, KodeCustSupp, NoBukti, NoMsk, Urut, Tanggal, JatuhTempo,
      Debet, Kredit, Valas, Kurs, DebetD, KreditD, KodeSales, Tipe, Perkiraan, Catatan, IDUser, TipeDK,
      NoInvoice, Valas_, Kurs_, FlagSimbol)


      select Y.NoFaktur, Y.NoRetur, Y.TipeTrans, Y.KodeCustSupp, case when y.KodeCustSupp= 'S_TUNAI' and TipeTrans='T' then xx.NAMACUSTSUPP else Y.NoBukti end NoBukti,
      Y.NoMsk, Y.Urut, Y.Tanggal, Y.JatuhTempo,
      Y.Debet, Y.Kredit, Y.Valas, Y.Kurs, Y.DebetD, Y.KreditD, Y.KodeSales, Y.Tipe, Y.Perkiraan, Y.Catatan, @username IDUser, 'D',
      Y.NoInvoice, Y.KodeVls_, Y.Kurs_, Y.FlagSimbol
      from
        (select NoFaktur, KodeCustSupp, Perkiraan
        from vwHutPiut
        where KodeCustSupp in (select KodeCustSupp from dbCustSupp where KodeCustSupp= @KodeCustSupp or KodeCustSupp= @agent or ISnull(Agent,'')= @agent)
        and Perkiraan= @lawan
        and NoBukti+right('0000'+cast(NoMsk as varchar(4)),4)<> @NoBukti
        group by NoFaktur, KodeCustSupp, Perkiraan
        having sum(Kredit-Debet)<>0
        ) X
      left outer join vwHutPiut Y on Y.NoFaktur=X.NoFaktur and Y.KodeCustSupp=X.KodeCustSupp and Y.Perkiraan=X.Perkiraan
      left outer join DBBELI yy on y.NoFaktur=yy.NOBUKTI
      left outer join DBCUSTSUPP xx on yy.KODESUPP=xx.KODECUSTSUPP
      where Y.KodeCustSupp in (select KodeCustSupp from dbCustSupp where KodeCustSupp= @KodeCustSupp or KodeCustSupp= @agent or ISnull(Agent,'')= @agent)
      and Y.NoBukti+right('0000'+cast(Y.NoMsk as varchar(4)),4)<> @NoBukti
      and y.KodeCustSupp=@KodeCustSupp",
        // agent kosong ('') diubah Laravel jadi NULL; dengan @agent NULL query ini 0 baris.
        // Lihat catatan yang sama di KasController::listTunai().
        ["nobukti" => $req->nobukti , "kodecustsupp" => $req->kodecustsupp , "username" => $username , "agent" => $req->agent ?? '' , "lawan" => $req->lawan]
      );


        // nobukti di atas = NoBukti + NoUrut; nobuktiview = NoBukti saja (lihat listTunaiX()).
        return app('App\Http\Controllers\Accounting\KasController')->tunaiRows($req->nobuktiview ?? '');

        }



        public function listCustSuppTunai (Request $req) {

          $listCustSuppX = DB::connection('SML')->select("select A.KODECUSTSUPP, A.NAMACUSTSUPP, A.ALAMAT, A.NAMAKOTA, A.ALAMATKOTA, A.PPN, A.Hari, ISNULL(a.agent, '') Agent
          from vwBrowsCustSupp A
          where A.Perkiraan= :lawan
          order by A.KodeCustSupp" , ["lawan" => $req->lawan]);

          return $listCustSuppX;
        }


          public function spAddNewAktiva (Request $req) {


              $username = \Auth::user()->username;

              // $jmlrecord = $req->jmlrecord;
              // if ($jmlrecord == 0 ) {
                $check = DB::connection('SML')->select('select * from dbaktiva where Perkiraan = :noaktiva',["noaktiva" => $req->noaktiva]);
                  if ($check) {
                    return 2;
                }
              // }


                DB::connection('SML')->statement('exec SP_AktivaTetap ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?', [
                  $req->choice ,// @Choice char(1),
                  $req->devisi ,// @Devisi varchar(5)='',
                  $req->noaktiva ,// @Perkiraan varchar(30)='',
                  $req->keterangan ,// @Keterangan varchar(50)='',
                  $req->kuantum ,// @Quantity Numeric(18,2)=0,
                  $req->persen ,// @Persen Numeric(18,2)=0,
                  $req->tglpemakaian ,// @Tanggal datetime=null,
                  $req->metodepenyusutan ,// @Tipe varchar(1)='',
                  $req->akumulasi ,// @Akumulasi varchar(15)='',
                  $req->biaya1 ? $req->biaya1 : '',// @Biaya varchar(15)='',
                  $req->groupaktiva ,// @NoMuka varchar(25)='',
                  $req->nobelakang ,// @NoBelakang varchar(20)='',
                  $req->biaya2 ? $req->biaya2 : '',// @biaya2 varchar(15),
                  $req->persen1 ? $req->persen1 : '0.00' ,// @persen1 numeric(18,2),
                  $req->persen2 ? $req->persen2 : '0.00' ,// @persen2 numeric(18,2),
                  $req->biaya3 ? $req->biaya3 : '' ,// @biaya3 varchar(15)='',
                  $req->persen3 ? $req->persen3 : '0.00' ,// @persenbiaya3 numeric(18,2)=0,
                  '' ,// @biaya4 varchar(15)='',
                  0 ,// @persenbiaya4 numeric(18,2)=0,
                  $req->tipeaktiva ,// @TipeAktiva tinyint,
                  '' ,// @Bagian varchar(15),
                  '' ,// @NoBelakang2 varchar(20),
                  0 ,// @IsHeader tinyint,
                  '' ,// @NoAktivaHd varchar(30),
                  $req->tglperolehan ,// @TglPeroleh DateTime=Null

                ]);

              return 1;

          }




            public function getNoUrutAktiva (Request $req) {
              $nourut = DB::connection('SML')->select("SELECT RIGHT('00000' + (CAST(ISNULL(MAX(CONVERT(int, NoBelakang)), 0) + 1 AS VARCHAR(10))), 5) AS NoUrut
          FROM dbAktiva where nomuka = :nomuka " , ["nomuka" => $req->nomuka]);

            return $nourut;
            }



  public function listLawan (Request $req) {

        $username = \Auth::user()->username;
        $listData = [];

        // if ($req->transaksi == 'BBK') {
        //   $listData = DB::connection('SML')->select("
        //   select a.Perkiraan, a.Keterangan,a.Simbol,C.Kode, C.IsLokalOrExim from dbPerkiraan a
        //               left Outer join dbAksesPerkiraan b on b.Perkiraan=a.Perkiraan
        //                Left Outer Join (select perkiraan,kode,IsLokalOrExim from dbPOSTHUTPIUT group by perkiraan,kode,IsLokalOrExim)  C on A.Perkiraan=C.Perkiraan
        //               where a.Tipe=1 and a.Perkiraan <> :perkiraan and b.UserID = :username
        //               and a.perkiraan not in (select Perkiraan from DBPOSTHUTPIUT where Kode='PT')
        //
        //               order by a.Perkiraan" , ["perkiraan" => $req->perkiraan , "username" => $username ]);
        //
        // } else {
        //   $listData = DB::connection('SML')->select("
        //   select a.Perkiraan, a.Keterangan, a.Simbol,C.Kode, C.IsLokalOrExim from dbPerkiraan a
        //         left Outer join dbAksesPerkiraan b on b.Perkiraan=a.Perkiraan
        //          Left Outer Join (select perkiraan,kode,IsLokalOrExim from dbPOSTHUTPIUT group by perkiraan,kode,IsLokalOrExim)  C on A.Perkiraan=C.Perkiraan
        //         where a.Tipe=1 and a.Perkiraan <> :perkiraan and b.UserID = :username
        //         and a.perkiraan not in (select Perkiraan from DBPOSTHUTPIUT where Kode='HT' and Perkiraan not in ('116100','21203') )
        //
        //         order by a.Perkiraan     " , ["perkiraan" => $req->perkiraan , "username" => $username ]);
        //
        //
        //
        //
        //
        // }
        // if ($req->transaksi == 'BBK') {
        //   $listData = DB::connection('SML')->select("
        //   select a.Perkiraan, a.Keterangan,a.Simbol,C.Kode, isnull(C.IsLokalOrExim, 0) IsLokalOrExim ,  isnull(d.iscost , 0) iscost from dbPerkiraan a
        //               left Outer join dbAksesPerkiraan b on b.Perkiraan=a.Perkiraan
        //                Left Outer Join (select perkiraan,kode,IsLokalOrExim from dbPOSTHUTPIUT group by perkiraan,kode,IsLokalOrExim)  C on A.Perkiraan=C.Perkiraan

        //                left outer join (select COUNT(kodecost) iscost , perkiraan from DBPERKCOST group by Perkiraan) D on a.Perkiraan = D.perkiraan
        //               where a.Tipe=1 and a.Perkiraan <> :perkiraan and b.UserID = :username
        //               and a.perkiraan not in (select Perkiraan from DBPOSTHUTPIUT where Kode='PT')

        //               order by a.Perkiraan" , ["perkiraan" => $req->perkiraan , "username" => $username ]);

        // } else {
        //   $listData = DB::connection('SML')->select("
        //   select a.Perkiraan, a.Keterangan, a.Simbol,C.Kode, isnull(C.IsLokalOrExim, 0) IsLokalOrExim, isnull(d.iscost , 0) iscost from dbPerkiraan a
        //         left Outer join dbAksesPerkiraan b on b.Perkiraan=a.Perkiraan
        //          Left Outer Join (select perkiraan,kode,IsLokalOrExim from dbPOSTHUTPIUT group by perkiraan,kode,IsLokalOrExim)  C on A.Perkiraan=C.Perkiraan

        //                left outer join (select COUNT(kodecost) iscost , perkiraan from DBPERKCOST group by Perkiraan) D on a.Perkiraan = D.perkiraan
        //         where a.Tipe=1 and a.Perkiraan <> :perkiraan and b.UserID = :username
        //         and a.perkiraan not in (select Perkiraan from DBPOSTHUTPIUT where Kode='HT' and Perkiraan not in ('116100','21203') )

        //         order by a.Perkiraan     " , ["perkiraan" => $req->perkiraan , "username" => $username ]);
        // }

        $listData = DB::connection('SML')->select("
          select a.Perkiraan, a.Keterangan,a.Simbol,C.Kode, isnull(C.IsLokalOrExim, 0) IsLokalOrExim ,  isnull(d.iscost , 0) iscost from dbPerkiraan a
                      left Outer join dbAksesPerkiraan b on b.Perkiraan=a.Perkiraan
                       Left Outer Join (select perkiraan,kode,IsLokalOrExim from dbPOSTHUTPIUT group by perkiraan,kode,IsLokalOrExim)  C on A.Perkiraan=C.Perkiraan

                       left outer join (select COUNT(kodecost) iscost , perkiraan from DBPERKCOST group by Perkiraan) D on a.Perkiraan = D.perkiraan
                      where a.Tipe=1 and a.Perkiraan <> :perkiraan and b.UserID = :username

                      order by a.Perkiraan" , ["perkiraan" => $req->perkiraan , "username" => $username ]);


    return $listData;
  }

  public function listInvoice (Request $req) {

    $listData = DB::connection('SML')->select("declare @awal Varchar(15)
    Select @Awal= :kodecustsupp
    select Cast(1 as Bit) Pilih,Convert(Numeric(18,2),0) Nilai, a.Valas KodeVls, a.Kurs, a.NoFaktur,Min(a.Tanggal) Tanggal, Min(a.JatuhTempo) JatuhTempo,
           SUM(a.Saldo) Saldo, SUM(A.SaldoD) SaldoD
    from dbo.vwHutPiut a
         left outer join (select NoInv from dbkreditNoteDet) b on b.NoInv=a.NoFaktur
    where a.KodeCustSupp=@Awal and B.NoInv is null and a.Tipe='PT'
    Group by a.NoFaktur,a.Valas, a.Kurs
    Having SUM(a.Saldo)<>0 or SUM(A.SaldoD)<>0
    Order by a.Nofaktur
" , ["kodecustsupp" => $req->kodecustsupp]);
    return $listData;

  }

  public function listCustsupp (Request $req) {

    $listData = DB::connection('SML')->select("select kodecustsupp , namacustsupp , alamat1 from DBCUSTSUPP where JENIS = 1 and isaktif = 1");
    return $listData;
  }

  public function listCustSuppX (Request $req) {


    $listData = DB::connection('SML')->select("select A.KODECUSTSUPP, A.NAMACUSTSUPP, A.ALAMAT, A.NAMAKOTA, A.ALAMATKOTA, A.PPN, A.Hari
 from vwBrowsCustSupp A
 where A.Perkiraan= :lawan
order by A.KodeCustSupp" , ["lawan" => $req->lawan]);
  return $listData;
  }


  public function listCustsuppUMB (Request $req) {

    $listData = DB::connection('SML')->select("Select c.KODESUPP,B.NAMACUSTSUPP
 from DBUMJUAL A
 LEFT OUTER JOIN DBPO C ON A.NOSO=C.NOBUKTI
 LEFT OUTER JOIN DBCUSTSUPP B ON c.KODESUPP=B.KODECUSTSUPP
where  c.KODESUPP is not null


GROUP BY c.KODESUPP,B.NAMACUSTSUPP
ORDER BY c.KODESUPP,B.NAMACUSTSUPP");
    return $listData;
  }

  public function sumBBMUHT (Request $req) {




    $listData = DB::connection('SML')->select("select sum(DPP) SubTotal from tempRUMJual where IDUser= :username and Isnull(Noretur,'')<>''
      and Isnull(Notrans,'')<>''", ["username" =>  \Auth::user()->username]);
    return $listData;
  }


  public function prosesUMB (Request $req) {

    $resx = DB::connection('SML')->update("delete TEMPRUMJUAL where IDUSER = :username", ["username" => \Auth::user()->username]);





    $res = DB::connection('SML')->update("declare @nilaippn numeric(18,2)
set @nilaippn= (select top 1 nilaippn/100.00 from dbnilaippn where GETDATE() between tglawal and tglakhir)
insert into tempRUMJual (NOBUKTI,NORETUR,TANGGAL,NOSO,VALAS,KURS,DPP,PPN,IDUSER,KODESUPP,SUBTOTAL)
select NoFaktur,'' NOretur,B.TANGGAL,b.NOSO,a.Valas,a.Kurs,
SUM(CASE WHEN E.PPN<>2 THEN A.debet- isnull(c.nuangmuka,0) ELSE (A.Debet / (1.0 + @nilaippn)) -
CASE WHEN E.PPN<>2 THEN isnull(c.nuangmuka,0) else (isnull(c.nuangmuka,0) / (1.0 + @nilaippn)) end END) debet,SUM(b.ppnum) ppnum, :username , :custsupp1 ,SUM(A.debet - isnull(c.nuangmuka,0)) SUBTOTAL
from DBHUTPIUT A
left outer join (select NOBUKTI,NOSO,SUM(DPP) dppum,SUM(ppn) ppnum,SUM(SUBTOTAL) subtotalum ,TANGGAL
                                from DBUMJUAL
                                where NoRetur=''
                                group by NOBUKTI,NOSO,TANGGAL) b on a.NoFaktur=b.NOBUKTI
 left outer join (select sum(isnull(nuangmuka,0)) nuangmuka,nopohd,PPN from dbbeli group by nopohd,PPN ) c on b.noso=c.nopohd
 Left outer join dbumjual D on B.nobukti=D.nobukti and isnull(D.NOretur,'')<>''
 left outer join dbpo E on b.NOSO=E.NOBUKTI
where A.Perkiraan IN ('116100','21201')  and a.nofaktur like '%UMB%' and A.tipetrans='L'
 AND a.KODECUSTSUPP= :custsupp2  and D.nobukti is null
group by NoFaktur,b.NOSO,a.Valas,a.Kurs,B.TANGGAL
HAVING SUM(A.debet - isnull(c.nuangmuka,0))>0   ", ["username" => \Auth::user()->username , "custsupp1" => $req->custsupp, "custsupp2" => $req->custsupp]);



$listData = DB::connection('SML')->select("Select *,Nobukti+Noretur KeyBukti,
(select top 1 nilaippn/100.00 from dbnilaippn where GETDATE() between tglawal and tglakhir) ppnx,
(select sum(DPP) SubTotal from tempRUMJual where IDUser='SA' ) totalqntx
from tempRUMJual
WHERE KODESUPP= :custsupp and IDuser= :username", ["custsupp" => $req->custsupp , "username" =>  \Auth::user()->username]);

  return $listData;


  }

 public function getDetailCetak(Request $req)
  {
      $noBukti = $req->input('NOBUKTI');

      $cetak = DB::connection("SML")->select(
          "EXEC dbo.CetakKasharian ?",
          [$noBukti]
      );

      $tempCetak1 = [];
      foreach ($cetak as $p) {
          array_push($tempCetak1, $p);
      }

      return $tempCetak1;
  }

  public function listUMB (Request $req) {


    $listData = DB::connection('SML')->select("Select *,Nobukti+Noretur KeyBukti
    from tempRUMJual
    WHERE KODESUPP= :custsupp and IDuser= :username", ["custsupp" => $req->custsupp , "username" =>  \Auth::user()->username]);

      return $listData;



  }



  // Sebelumnya tanpa cek hak di server - kini wajib DBFLMENUWEB.IsOtorisasi1 untuk menu 'bank'.
  // UPDATE-nya pindah ke App\Otorisasi\BankOtorisasi supaya sama dengan yang dipakai halaman
  // approve mobile (/otorisasi/{id}). Pola yang sama dengan KasController::spOtorisasi.
  public function spOtorisasi (Request $req, OtorisasiService $oto) {
    $username = \Auth::user()->username;
    if (!$oto->canApprove($username, 'bank')) {
      return response('Anda tidak berhak otorisasi Bank', 403);
    }
    $res = $oto->handler('bank')->approve($req->nobukti, $username);
    $oto->closePending('bank', $req->nobukti, $username);
    return $res;
  }
  public function spBatalOtorisasi (Request $req, OtorisasiService $oto) {
    if (!$oto->canApprove(\Auth::user()->username, 'bank')) {
      return response('Anda tidak berhak batal otorisasi Bank', 403);
    }
    $tanggal = date('Y-m-d H:i:s');
    $res = DB::connection('SML')->update("update dbtrans set isOtorisasi1 = 0, maxol = -1 , OtoUser1= '' , TglOto1 = NULL where nobukti = :nobukti", [ "nobukti" => $req->nobukti]);
    app('App\Http\Controllers\GlobalController')->LoggingData('btloto', 'BANK', $req->nobukti, $req->pket, 0, 'dbtrans');
    return $res;
  }

  public function spAdd (Request $req) {


    $username = \Auth::user()->username;

    $jmlrecord = $req->jmlrecord;
    if ($jmlrecord == 0 ) {
      $check = DB::connection('SML')->select('select * from DBTRANS where Nobukti = :nobukti',["nobukti" => $req->nobukti]);
        if ($check) {
          return 2;
      }
    }

      // foreach ($tempData as $d)  {
        // code...
        $plok = DB::connection("SML")->select("Select ISnull(IsLokalOrExim,0) IsLokalOrExim from dbPostHutpiut where perkiraan= :lawan
      " , ["lawan" => $req->lawanx]);

      $xplok = 0;
      // return ['a' => $plok[0]['IsLokalOrExim']];
      if ($plok) {
        $xplok = $plok[0]->IsLokalOrExim;
      }
      if($req->xislocalorexim == 1) {
        $xplok = 1;
        $uruttunai = DB::connection('SML')->select('Select isnull(MAX(urut) , 0) + 1 URUT from dbTransaksi where NoBukti = :nobukti', ["nobukti" => $req->nobukti ]);
        // return ['a'=> $uruttunai[0]->URUT];
        DB::connection('SML')->statement('update dbtemphutpiut set nomsk = :urut where iduser = :username', ["urut" => $uruttunai[0]->URUT , "username" => $username]);

        // Pelunasan Piutang (Lawan 113100): NoRetur pelunasan dibukukan kosong - sama seperti
        // KasController::spAdd().
        if ($req->lawan == '113100') {
          DB::connection('SML')->statement("update dbTempHutPiut set NoRetur = '' where IDUSER = :username and NoBukti = :nobukti and StatusUID = 'I'",
            ["username" => $username, "nobukti" => $req->nobukti]);
        }

      }
      // return $xplok;

        DB::connection('SML')->statement('exec sp_TransaksiKasBankDPH ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?', [
          $req->choice,
          $req->nobukti,
          $req->nourut,
          $req->tanggal,
          $req->note ? $req->note : '',
          $req->lampiran ? $req->lampiran : '',
          $req->kodedevisi,
          $req->perkiraanx,
          $req->lawanx,
          $req->keterangan ? $req->keterangan : '', // 10
          $req->keterangan2 ? $req->keterangan2 : '',
          $req->jumlah,
          $req->kredit,
          $req->valas,
          $req->kurs,
          $req->jumlahrp,
          $req->kreditrp,
          $req->transaksi,
          $req->tphc,
          $req->custsuppP ? $req->custsuppP : '' , // 20
          $req->custsuppL ? $req->custsuppL : '' ,
          $req->urut,
          $req->noaktivaP ? $req->noaktivaP : '' ,
          $req->noaktivaL ? $req->noaktivaL : '' ,
          $req->statusaktivaP ? $req->statusaktivaP : '',
          $req->statusaktivaL ? $req->statusaktivaL : '',
          $req->nobon ? $req->nobon : '',
          $req->kodedepartemen,
          $req->kodeP ? $req->kodeP : '',
          $req->kodeL ? $req->kodeL : '', // 30
          $req->statusgiro ? $req->statusgiro : '',
          $req->simbol,
          $req->kodeperkiraan,
          $req->flagsimbol ? $req->flagsimbol : '',
          $req->kodecost ? $req->kodecost : '',
          $req->kodesubcost ? $req->kodesubcost : '',
          $req->nodph ?   $req->nodph : '',
          $req->urutdph,
          $req->dppdph ? $req->dppdph : '', // 40
          $username,
          $req->tp ? $req->tp : '',
          $req->ppklx ? $req->ppklx : '',
          $req->nofaktur ? $req->nofaktur : '',
          $xplok,
          $req->nobons ? $req->nobons : '',
          $req->jmlrecord,
          $req->notitipan ? $req->notitipan : '',
          $req->uruttitipan,
          $req->keterangandetail ? $req->keterangandetail : '', // 50
          0, // untuk input pskb biar parameternya sama jumlahnya

        ]);
      // }
      DB::connection('SML')->statement("delete from dbTempHutPiut where IDUSER = :username",["username"=>$username] );

      return 1;

  }


  public function spAddDPPDPH (Request $req) {


      $username = \Auth::user()->username;

      $jmlrecord = $req->jmlrecord;


      if ($jmlrecord == 0) {
        $check = DB::connection('SML')->select('select * from DBTRANSAKSI where Nobukti = :nobukti',["nobukti" => $req->nobukti]);
          if ($check) {
            return 2;
        }
      }

      $tempData = DB::connection('SML')->select('exec SP_ViewDPH ?,?,?,?,?' , [
        $req->dppdph,
        $req->valas,
        $req->nodph,
        $req->perkiraan,
        $req->custsupp

      ]);

      // return $tempData;

      // $outstandingReguler = DB::connection("SML")->select('exec sp_sosiapkirim ?,?,?',[ $periode->bulan , $periode->tahun ,\Auth::user()->username ]);
      $plok = DB::connection("SML")->select("Select ISnull(IsLokalOrExim,0) IsLokalOrExim from dbPostHutpiut where perkiraan= :lawan
    " , ["lawan" => $req->lawanx]);

      // Jumlah yang diketik user dialokasikan ke baris dokumen sesuai urutan SP_ViewDPH: tiap baris
      // dibukukan penuh sampai jumlah habis, baris terakhir dipotong sisanya, sisanya dilewati.
      // Jumlah sama dengan total dokumen (atau kosong) = perilaku lama, semua baris dibukukan penuh.
      $totalDibayar = 0.0;
      foreach ($tempData as $d) {
        $totalDibayar += (float) $d->DIBAYAR;
      }
      $totalDibayar = round($totalDibayar, 2);
      $jumlahInput = $req->jumlah === null ? 0.0 : round((float) $req->jumlah, 2);

      $baris = [];
      if ($jumlahInput <= 0 || abs($jumlahInput - $totalDibayar) < 0.005) {
        foreach ($tempData as $d) {
          $baris[] = [$d, (float) $d->DIBAYAR];
        }
      } elseif ($jumlahInput > $totalDibayar) {
        return 3; // melebihi total dokumen
      } else {
        $sisa = $jumlahInput;
        foreach ($tempData as $d) {
          $nilai = round(min((float) $d->DIBAYAR, $sisa), 2);
          if ($nilai <= 0) {
            continue;
          }
          $baris[] = [$d, $nilai];
          $sisa = round($sisa - $nilai, 2);
        }
        if (!count($baris)) {
          return 3;
        }
      }

      foreach ($baris as [$d, $nilaiDibayar])  {
        // return [
        //   'a' => $d->DIBAYAR,
        //   'b' => $req->kurs
        // ];

        DB::connection('SML')->statement('exec sp_TransaksiKasBankDPH ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?', [
          $req->choice,
          $req->nobukti,
          $req->nourut,
          $req->tanggal,
          $req->note ? $req->note : '',
          $req->lampiran ? $req->lampiran : '',
          $req->kodedevisi,
          $req->perkiraanx,
          $req->lawanx,
          $req->keterangan ? $req->keterangan : '', // 10
          $req->keterangan2 ? $req->keterangan2 : '',
          $nilaiDibayar,
          $req->kredit,
          $req->valas,
          $req->kurs,
          $nilaiDibayar * (float) $req->kurs,
          $req->kreditrp,
          $req->transaksi,
          $req->tphc,
          $req->custsuppP ? $req->custsuppP : '' , // 20
          $req->custsuppL ? $req->custsuppL : '' ,
          $req->urut,
          $req->noaktivaP ? $req->noaktivaP : '' ,
          $req->noaktivaL ? $req->noaktivaL : '' ,
          $req->statusaktivaP ? $req->statusaktivaP : '',
          $req->statusaktivaL ? $req->statusaktivaL : '',
          $req->nobon ? $req->nobon : '',
          $req->kodedepartemen,
          $req->kodeP ? $req->kodeP : '',
          $req->kodeL ? $req->kodeL : '', // 30
          $req->statusgiro ? $req->statusgiro : '',
          $req->simbol,
          $req->kodeperkiraan,
          $req->flagsimbol ? $req->flagsimbol : '',
          $req->kodecost ? $req->kodecost : '',
          $req->kodesubcost ? $req->kodesubcost : '',
          $req->nodph ?   $req->nodph : '',
          $d->Urut,
          $req->dppdph ? $req->dppdph : '', // 40
          $username,
          $d->Tp ? $d->Tp : '',
          $req->ppklx ? $req->ppklx : '',
          $d->NOFAKTUR ? $d->NOFAKTUR  : '',
          $plok[0]->IsLokalOrExim,
          $req->nobons ? $req->nobons : '',
          $jmlrecord,
          $req->notitipan ? $req->notitipan : '',
          $req->uruttitipan,
          $req->keterangandetail ? $req->keterangandetail : '', // 50
          $req->pSKB,

        ]);

        $jmlrecord = 1;
      }

      return 1;

  }



  public function spAddTempRUMJUAL (Request $req) {
    $username = \Auth::user()->username;
    $periode = DB::connection("SML")->select('select TOP 1 * from DBPERIODE where user_id = :username ' , ["username" => $username]);
    $kode = 'RUM';
    $inisial = DB::connection("SML")->select('select ' . $kode . ' from DBNOMOR');
    // $inisialx = 'KN';
    // return $inisialx;
    $values = [
        $inisial[0]->$kode,
        $periode[0]->bulan,
        $periode[0]->tahun,
        $username
    ];

    $noBukti = DB::connection('SML')->select('exec SP_IsiNobuktiSimbol ?,?,?,?',$values);

    $res = DB::connection('SML')->update("insert into tempRUMJual (NOBUKTI,NORETUR,TANGGAL,NOSO,VALAS,KURS,DPP,PPN,IDUSER,KODESUPP,SUBTOTAL,Notrans,uruttrans)
    VALUES ( :noumb , :noretur , :tanggal , :noso , :valas , :kurs , :dpp , :ppn , :username , :kodesupp , :subtotal , :nobukti , :urut) " , [
      "noumb" => $req->noumb,
      "noretur" => $noBukti[0]->Nobukti,
      "tanggal" => $req->tanggal,
      "noso" => $req->noso,
      "valas" => $req->valas,
      "kurs" => $req->kurs,
      "dpp" => $req->dpp,
      "ppn" => $req->ppn,
      "username" => $username,
      "kodesupp" => $req->kodesupp,
      "subtotal" => $req->subtotal,
      "nobukti" => $req->nobukti,
      "urut" => $req->urut,

    ]);


    $listData = DB::connection('SML')->select("Select *,Nobukti+Noretur KeyBukti,
    (select top 1 nilaippn/100.00 from dbnilaippn where GETDATE() between tglawal and tglakhir) ppnx
    , (select sum(DPP) SubTotal from tempRUMJual where IDUser='SA' ) totalqntx
    from tempRUMJual
    WHERE KODESUPP= :custsupp and IDuser= :username order by nobukti , noretur", ["custsupp" => $req->kodesupp , "username" =>  \Auth::user()->username]);

    return $listData;








  }

  public function spDeleteTempRUMJUAL (Request $req) {

    // delete TempRumJual where nobukti=:0 and Noretur=:1
    $res = DB::connection('SML')->update("delete TempRumJual where nobukti= :nobukti and Noretur= :noretur and IDUSER = :username", ["nobukti" => $req->noumb ,"noretur" => $req->noretur,  "username" =>  \Auth::user()->username]);




    $listData = DB::connection('SML')->select("Select *,Nobukti+Noretur KeyBukti,
    (select top 1 nilaippn/100.00 from dbnilaippn where GETDATE() between tglawal and tglakhir) ppnx
    , (select sum(DPP) SubTotal from tempRUMJual where IDUser='SA' ) totalqntx
    from tempRUMJual
    WHERE KODESUPP= :custsupp and IDuser= :username order by nobukti , noretur", ["custsupp" => $req->kodesupp , "username" =>  \Auth::user()->username]);

    return $listData;
  }

  public function spUpdateTempRUMJUAL (Request $req) {
    $res = DB::connection('SML')->update("update TEMPRUMJUAL set DPP = :dpp , PPN = :ppn , subtotal = :subtotal where nobukti= :nobukti and Noretur= :noretur and IDUSER = :username",
    [
      "dpp" => $req->dpp,
      "ppn" => $req->ppn,
      "subtotal" => $req->subtotal,
      "nobukti" => $req->noumb ,
      "noretur" => $req->noretur ? $req->noretur : '' ,
      "username" =>  \Auth::user()->username
    ]);

    $listData = DB::connection('SML')->select("Select *,Nobukti+Noretur KeyBukti,
    (select top 1 nilaippn/100.00 from dbnilaippn where GETDATE() between tglawal and tglakhir) ppnx,
    (select sum(DPP) SubTotal from tempRUMJual where IDUser='SA' ) totalqntx
    from tempRUMJual
    WHERE KODESUPP= :custsupp and IDuser= :username order by nobukti , noretur", ["custsupp" => $req->kodesupp , "username" =>  \Auth::user()->username]);

    return $listData;
  }

    // Browse No Titipan untuk item BBK ber-Lawan 113400 (lihat buttonAddListTitipan() di bank.js).
  // Pakai tanda kutip biasa, BUKAN backtick: di PHP backtick menjalankan isinya sebagai
  // perintah shell.
  public function getNoTitipan () {

  $data = DB::connection('SML')->select("select 	A.NOBUKTI,  A.TANGGAL,A.Valas ,C.namaCustSupp,
                        (A.Debet+A.Kredit)*A.Kurs JumlahRp ,A.Keterangan  ,
                       A.debet - (isnull(d.Dibayar,0)+isnull(D.LB,0))-ISNULL(E.DEBET,0) Sisa,A.URUT,A.Debet,C.KodeCustSupp
                       from dbTransaksi A
                       LEFT OUTER JOIN DBTRANS B ON A.NoBukti=B.NoBukti
                       LEFT OUTER JOIN DBCUSTSUPP C ON A.CustSuppL=C.KODECUSTSUPP
                       LEFT OUTER JOIN (select UrutDPP,NODPP,sum(dibayar) Dibayar,sum(LB) LB
                                        from DBTerimaDPPDET group by UrutDPP,NODPP) D ON A.NObukti=D.NoDPP AND A.urut=D.UrutDPP
                       LEFT OUTER JOIN (SELECT NOTITIPAN,URUTTITIPAN,SUM(Debet) DEBET
                                        FROM dbTransaksi GROUP BY NOTITIPAN,URUTTITIPAN) E ON A.NoBukti=E.NOTITIPAN AND A.Urut=E.URUTTITIPAN
                       where A.Lawan=  '113400'AND A.CustSuppL<> ''     and A.TANGGAL>  '03/28/2016'
                       and    A.debet - (isnull(d.Dibayar,0)+isnull(D.LB,0))-ISNULL(E.DEBET,0) >0 and a.tipetrans in ('BBM')");

    return $data;
  }

}
