<?php

namespace Plugins\Vedika;

use Systems\AdminModule;
use Systems\Lib\BpjsService;

class Admin extends AdminModule
{
    private $_uploads = WEBAPPS_PATH.'/berkasrawat/pages/upload';

    public function navigation()
    {
        return [
            'Manage' => 'manage',
            'Index' => 'index',
            'Pengaturan' => 'settings',
        ];
    }

    public function getManage()
    {
      $sub_modules = [
        ['name' => 'Index', 'url' => url([ADMIN, 'vedika', 'index']), 'icon' => 'code', 'desc' => 'Index Vedika'],
        ['name' => 'Pengaturan', 'url' => url([ADMIN, 'vedika', 'settings']), 'icon' => 'code', 'desc' => 'Pengaturan Vedika'],
      ];
      return $this->draw('manage.html', ['sub_modules' => $sub_modules]);
    }

    public function getIndex($type = 'ralan', $page = 1)
    {
      $this->_addHeaderFiles();
      $start_date = date('Y-m-d');
      if(isset($_GET['start_date']) && $_GET['start_date'] !='')
        $start_date = $_GET['start_date'];
      $end_date = date('Y-m-d');
      if(isset($_GET['end_date']) && $_GET['end_date'] !='')
        $end_date = $_GET['end_date'];
      $perpage = '10';
      $phrase = '';
      if(isset($_GET['s']))
        $phrase = $_GET['s'];

      // pagination
      $totalRecords = $this->db()->pdo()->prepare("SELECT reg_periksa.no_rawat FROM reg_periksa, pasien WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis AND (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?) AND reg_periksa.tgl_registrasi BETWEEN '$start_date' AND '$end_date' AND reg_periksa.status_lanjut = 'Ralan'");
      $totalRecords->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
      $totalRecords = $totalRecords->fetchAll();

      $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), $perpage, url([ADMIN, 'vedika', 'index', $type, '%d?s='.$phrase.'&start_date='.$start_date.'&end_date='.$end_date]));
      $this->assign['pagination'] = $pagination->nav('pagination','5');
      $this->assign['totalRecords'] = $totalRecords;

      $offset = $pagination->offset();
      $query = $this->db()->pdo()->prepare("SELECT reg_periksa.*, pasien.*, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab FROM reg_periksa, pasien, dokter, poliklinik, penjab WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis AND reg_periksa.kd_dokter = dokter.kd_dokter AND reg_periksa.kd_poli = poliklinik.kd_poli AND reg_periksa.kd_pj = penjab.kd_pj AND (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?) AND reg_periksa.tgl_registrasi BETWEEN '$start_date' AND '$end_date' AND reg_periksa.status_lanjut = 'Ralan' LIMIT $perpage OFFSET $offset");
      $query->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
      $rows = $query->fetchAll();

      if($type == 'ranap') {
        // pagination
        $totalRecords = $this->db()->pdo()->prepare("SELECT reg_periksa.no_rawat FROM reg_periksa, pasien WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis AND (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?) AND reg_periksa.tgl_registrasi BETWEEN '$start_date' AND '$end_date' AND reg_periksa.status_lanjut = 'Ranap'");
        $totalRecords->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
        $totalRecords = $totalRecords->fetchAll();

        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), $perpage, url([ADMIN, 'vedika', 'index', $type, '%d?s='.$phrase.'&start_date='.$start_date.'&end_date='.$end_date]));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT reg_periksa.*, pasien.*, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab FROM reg_periksa, pasien, dokter, poliklinik, penjab WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis AND reg_periksa.kd_dokter = dokter.kd_dokter AND reg_periksa.kd_poli = poliklinik.kd_poli AND reg_periksa.kd_pj = penjab.kd_pj AND (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?) AND reg_periksa.tgl_registrasi BETWEEN '$start_date' AND '$end_date' AND reg_periksa.status_lanjut = 'Ranap' LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();
      }
      $this->assign['list'] = [];
      if (count($rows)) {
          foreach ($rows as $row) {
              $berkas_digital = $this->db('berkas_digital_perawatan')
                ->join('master_berkas_digital', 'master_berkas_digital.kode=berkas_digital_perawatan.kode')
                ->where('berkas_digital_perawatan.no_rawat', $row['no_rawat'])
                ->asc('master_berkas_digital.nama')
                ->toArray();
              $galleri_pasien = $this->db('mlite_pasien_galleries_items')
                ->join('mlite_pasien_galleries', 'mlite_pasien_galleries.id = mlite_pasien_galleries_items.gallery')
                ->where('mlite_pasien_galleries.slug', $row['no_rkm_medis'])
                ->toArray();

              $berkas_digital_pasien = array();
              if (count($galleri_pasien)) {
                  foreach ($galleri_pasien as $galleri) {
                      $galleri['src'] = unserialize($galleri['src']);

                      if (!isset($galleri['src']['sm'])) {
                          $galleri['src']['sm'] = isset($galleri['src']['xs']) ? $galleri['src']['xs'] : $galleri['src']['lg'];
                      }

                      $berkas_digital_pasien[] = $galleri;
                  }
              }

              $row = htmlspecialchars_array($row);
              $row['no_sep'] = $this->_getSEPInfo('no_sep', $row['no_rawat']);
              $row['no_peserta'] = $this->_getSEPInfo('no_kartu', $row['no_rawat']);
              $row['no_rujukan'] = $this->_getSEPInfo('no_rujukan', $row['no_rawat']);
              $row['kd_penyakit'] = $this->_getDiagnosa('kd_penyakit', $row['no_rawat'], $row['status_lanjut']);
              $row['nm_penyakit'] = $this->_getDiagnosa('nm_penyakit', $row['no_rawat'], $row['status_lanjut']);
              $row['berkas_digital'] = $berkas_digital;
              $row['berkas_digital_pasien'] = $berkas_digital_pasien;
              $row['sepURL'] = url([ADMIN, 'vedika', 'sep', $row['no_sep']]);
              $row['formSepURL'] = url([ADMIN, 'vedika', 'formsepvclaim', '?no_rawat='.$row['no_rawat']]);
              $row['pdfURL'] = url([ADMIN, 'vedika', 'pdf', $this->convertNorawat($row['no_rawat'])]);
              $row['resumeURL']  = url([ADMIN, 'vedika', 'resume', $this->convertNorawat($row['no_rawat'])]);
              $row['riwayatURL']  = url([ADMIN, 'vedika', 'riwayat', $this->convertNorawat($row['no_rawat'])]);
              $row['billingURL'] = url([ADMIN, 'vedika', 'billing', $this->convertNorawat($row['no_rawat'])]);
              $row['berkasPasien'] = url([ADMIN, 'vedika', 'berkaspasien', $this->getRegPeriksaInfo('no_rkm_medis', $row['no_rawat'])]);
              $row['berkasPerawatan'] = url([ADMIN, 'vedika', 'berkasperawatan', $this->convertNorawat($row['no_rawat'])]);
              $this->assign['list'][] = $row;
          }
      }

      $this->core->addCSS(url('assets/jscripts/lightbox/lightbox.min.css'));
      $this->core->addJS(url('assets/jscripts/lightbox/lightbox.min.js'));

      $this->assign['searchUrl'] =  url([ADMIN, 'vedika', 'index', $type, $page.'?s='.$phrase.'&start_date='.$start_date.'&end_date='.$end_date]);
      $this->assign['ralanUrl'] =  url([ADMIN, 'vedika', 'index', 'ralan', $page.'?s='.$phrase.'&start_date='.$start_date.'&end_date='.$end_date]);
      $this->assign['ranapUrl'] =  url([ADMIN, 'vedika', 'index', 'ranap', $page.'?s='.$phrase.'&start_date='.$start_date.'&end_date='.$end_date]);
      return $this->draw('index.html', ['tab' => $type, 'vedika' => $this->assign]);

    }

    public function getSEP($id)
    {
      $print_sep['bridging_sep'] = $this->db('bridging_sep')->where('no_sep', $id)->oneArray();
      $batas_rujukan = $this->db('bridging_sep')->select('DATE_ADD(tglrujukan , INTERVAL 85 DAY) AS batas_rujukan')->where('no_sep', $id)->oneArray();
      $print_sep['batas_rujukan'] = $batas_rujukan['batas_rujukan'];
      $print_sep['nama_instansi'] = $this->settings->get('settings.nama_instansi');
      $print_sep['logoURL'] = url(MODULES.'/vclaim/img/bpjslogo.png');
      $print_sep['qrCode'] = url(ADMIN.'/vedika/qrcode?no_sep='.$id);
      $this->tpl->set('print_sep', $print_sep);
      echo $this->tpl->draw(MODULES.'/vedika/view/admin/sep.html', true);
      exit();
    }

    public function getFormSEPVClaim()
    {
      $this->tpl->set('poliklinik', $this->core->db('poliklinik')->where('status', '1')->toArray());
      $this->tpl->set('dokter', $this->core->db('dokter')->where('status', '1')->toArray());
      echo $this->tpl->draw(MODULES.'/vedika/view/admin/form.sepvclaim.html', true);
      exit();
    }

    public function postSaveSEP()
    {
      header('Content-type: text/html');
      $date = date('Y-m-d');
      $url = $this->settings->get('settings.BpjsApiUrl').'SEP/'.$_POST['no_sep'];
      $consid = $this->settings->get('settings.BpjsConsID');
      $secretkey = $this->settings->get('settings.BpjsSecretKey');
      $output = BpjsService::get($url, NULL, $consid, $secretkey);
      $data = json_decode($output, true);
      //print_r($output);

      $url_rujukan = $this->settings->get('settings.BpjsApiUrl').'Rujukan/'.$data['response']['noRujukan'];
      if($_POST['asal_rujukan'] == 2) {
        $url_rujukan = $this->settings->get('settings.BpjsApiUrl').'Rujukan/RS/'.$data['response']['noRujukan'];
      }
      $rujukan = BpjsService::get($url_rujukan, NULL, $consid, $secretkey);
      $data_rujukan = json_decode($rujukan, true);
      //print_r($rujukan);

      $no_telp = $data_rujukan['response']['rujukan']['peserta']['mr']['noTelepon'];
      if(empty($data_rujukan['response']['rujukan']['peserta']['mr']['noTelepon'])){
        $no_telp = '00000000';
      }

      $jenis_pelayanan = '2';
      if($data['response']['jnsPelayanan'] == 'Rawat Inap') {
        $jenis_pelayanan = '1';
      }

      if($data_rujukan['metaData']['code'] == 201) {
        $data_rujukan['response']['rujukan']['tglKunjungan'] = $_POST['tgl_kunjungan'];
        $data_rujukan['response']['rujukan']['provPerujuk']['kode'] = $this->settings->get('settings.ppk_bpjs');
        $data_rujukan['response']['rujukan']['provPerujuk']['nama'] = $this->settings->get('settings.nama_instansi');
        $data_rujukan['response']['rujukan']['diagnosa']['kode'] = $_POST['kd_diagnosa'];
        $data_rujukan['response']['rujukan']['diagnosa']['nama'] = $data['response']['diagnosa'];
        $data_rujukan['response']['rujukan']['pelayanan']['kode'] = $jenis_pelayanan;
      }

      if($data['metaData']['code'] == 200)
      {
        $insert = $this->db('bridging_sep')
          ->save([
            'no_sep' => $data['response']['noSep'],
            'no_rawat' => $_POST['no_rawat'],
            'tglsep' => $data['response']['tglSep'],
            'tglrujukan' => $data_rujukan['response']['rujukan']['tglKunjungan'],
            'no_rujukan' => $data['response']['noRujukan'],
            'kdppkrujukan' => $data_rujukan['response']['rujukan']['provPerujuk']['kode'],
            'nmppkrujukan' => $data_rujukan['response']['rujukan']['provPerujuk']['nama'],
            'kdppkpelayanan' => $this->settings->get('settings.ppk_bpjs'),
            'nmppkpelayanan' => $this->settings->get('settings.nama_instansi'),
            'jnspelayanan' => $data_rujukan['response']['rujukan']['pelayanan']['kode'],
            'catatan' => $data['response']['catatan'],
            'diagawal' => $data_rujukan['response']['rujukan']['diagnosa']['kode'],
            'nmdiagnosaawal' => $data_rujukan['response']['rujukan']['diagnosa']['nama'],
            'kdpolitujuan' => $this->db('maping_poli_bpjs')->where('kd_poli_rs', $_POST['kd_poli'])->oneArray()['kd_poli_bpjs'],
            'nmpolitujuan' => $this->db('maping_poli_bpjs')->where('kd_poli_rs', $_POST['kd_poli'])->oneArray()['nm_poli_bpjs'],
            'klsrawat' =>  $data['response']['kelasRawat'],
            'lakalantas' => '0',
            'user' => $this->core->getUserInfo('username', null, true),
            'nomr' => $this->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']),
            'nama_pasien' => $data['response']['peserta']['nama'],
            'tanggal_lahir' => $data['response']['peserta']['tglLahir'],
            'peserta' => $data['response']['peserta']['jnsPeserta'],
            'jkel' => $data['response']['peserta']['kelamin'],
            'no_kartu' => $data['response']['peserta']['noKartu'],
            'tglpulang' => '1900-01-01 00:00:00',
            'asal_rujukan' => $_POST['asal_rujukan'],
            'eksekutif' => $data['response']['poliEksekutif'],
            'cob' => '0',
            'penjamin' => '-',
            'notelep' => $no_telp,
            'katarak' => '0',
            'tglkkl' => '1900-01-01',
            'keterangankkl' => '-',
            'suplesi' => '0',
            'no_sep_suplesi' => '-',
            'kdprop' => '-',
            'nmprop' => '-',
            'kdkab' => '-',
            'nmkab' => '-',
            'kdkec' => '-',
            'nmkec' => '-',
            'noskdp' => '0',
            'kddpjp' => $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $_POST['kd_dokter'])->oneArray()['kd_dokter_bpjs'],
            'nmdpdjp' => $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $_POST['kd_dokter'])->oneArray()['nm_dokter_bpjs']
          ]);
      }

      if ($insert) {
          $this->db('bpjs_prb')->save(['no_sep' => $data['response']['noSep'], 'prb' => $data_rujukan['response']['rujukan']['peserta']['informasi']['prolanisPRB']]);
          $this->notify('success', 'Simpan sukes');
      } else {
          $this->notify('failure', 'Simpan gagal');
      }
      //redirect(url([ADMIN, 'vedika', 'index']));
    }
    public function getPDF($id)
    {
      $berkas_digital = $this->db('berkas_digital_perawatan')
        ->join('master_berkas_digital', 'master_berkas_digital.kode=berkas_digital_perawatan.kode')
        ->where('berkas_digital_perawatan.no_rawat', $this->revertNorawat($id))
        ->asc('master_berkas_digital.nama')
        ->toArray();

      $galleri_pasien = $this->db('mlite_pasien_galleries_items')
        ->join('mlite_pasien_galleries', 'mlite_pasien_galleries.id = mlite_pasien_galleries_items.gallery')
        ->where('mlite_pasien_galleries.slug', $this->getRegPeriksaInfo('no_rkm_medis', $this->revertNorawat($id)))
        ->toArray();
      $berkas_digital_pasien = array();
      if (count($galleri_pasien)) {
          foreach ($galleri_pasien as $galleri) {
              $galleri['src'] = unserialize($galleri['src']);

              if (!isset($galleri['src']['sm'])) {
                  $galleri['src']['sm'] = isset($galleri['src']['xs']) ? $galleri['src']['xs'] : $galleri['src']['lg'];
              }

              $berkas_digital_pasien[] = $galleri;
          }
      }

      $no_rawat = $this->revertNorawat($id);
      $query = $this->db()->pdo()->prepare("select no,nm_perawatan,pemisah,if(biaya=0,'',biaya),if(jumlah=0,'',jumlah),if(tambahan=0,'',tambahan),if(totalbiaya=0,'',totalbiaya),totalbiaya from billing where no_rawat='$no_rawat'");
      $query->execute();
      $rows = $query->fetchAll();
      $total=0;
      foreach ($rows as $key => $value) {
        $total = $total+$value['7'];
      }
      $total = $total;
      $this->tpl->set('total', $total);

      $instansi['logo'] = $this->settings->get('settings.logo');
      $instansi['nama_instansi'] = $this->settings->get('settings.nama_instansi');
      $instansi['alamat_instansi'] = $this->settings->get('settings.alamat');
      $instansi['kabupaten'] = $this->settings->get('settings.kota');
      $instansi['propinsi'] = $this->settings->get('settings.propinsi');
      $instansi['kontak'] = $this->settings->get('settings.nomor_telepon');
      $instansi['email'] = $this->settings->get('settings.email');

      $this->tpl->set('billing', $rows);
      $this->tpl->set('instansi', $instansi);

      $print_sep = array();
      if(!empty($this->_getSEPInfo('no_sep', $no_rawat))) {
        $print_sep['bridging_sep'] = $this->db('bridging_sep')->where('no_sep', $this->_getSEPInfo('no_sep', $no_rawat))->oneArray();
        $print_sep['bpjs_prb'] = $this->db('bpjs_prb')->where('no_sep', $this->_getSEPInfo('no_sep', $no_rawat))->oneArray();
        $batas_rujukan = $this->db('bridging_sep')->select('DATE_ADD(tglrujukan , INTERVAL 85 DAY) AS batas_rujukan')->where('no_sep', $id)->oneArray();
        $print_sep['batas_rujukan'] = $batas_rujukan['batas_rujukan'];
      }
      $print_sep['nama_instansi'] = $this->settings->get('settings.nama_instansi');
      $print_sep['logoURL'] = url(MODULES.'/vclaim/img/bpjslogo.png');
      $this->tpl->set('print_sep', $print_sep);

      $resume_pasien = $this->db('resume_pasien')
        ->join('dokter', 'dokter.kd_dokter = resume_pasien.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->oneArray();
      $this->tpl->set('resume_pasien', $resume_pasien);

      $pasien = $this->db('pasien')
        ->join('kecamatan', 'kecamatan.kd_kec = pasien.kd_kec')
        ->join('kabupaten', 'kabupaten.kd_kab = pasien.kd_kab')
        ->where('no_rkm_medis', $this->getRegPeriksaInfo('no_rkm_medis', $this->revertNorawat($id)))
        ->oneArray();
      $reg_periksa = $this->db('reg_periksa')
        ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
        ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
        ->join('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
        ->where('stts', '<>', 'Batal')
        ->where('no_rawat', $this->revertNorawat($id))
        ->oneArray();
      $rows_dpjp_ranap = $this->db('dpjp_ranap')
        ->join('dokter', 'dokter.kd_dokter = dpjp_ranap.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $dpjp_i = 1;
      $dpjp_ranap = [];
      foreach ($rows_dpjp_ranap as $row) {
        $row['nomor'] = $dpjp_i++;
        $dpjp_ranap[] = $row;
      }
      $rujukan_internal = $this->db('rujukan_internal_poli')
        ->join('poliklinik', 'poliklinik.kd_poli = rujukan_internal_poli.kd_poli')
        ->join('dokter', 'dokter.kd_dokter = rujukan_internal_poli.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->oneArray();
      $diagnosa_pasien = $this->db('diagnosa_pasien')
        ->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $prosedur_pasien = $this->db('prosedur_pasien')
        ->join('icd9', 'icd9.kode = prosedur_pasien.kode')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')
        ->where('no_rawat', $this->revertNorawat($id))
        ->asc('tgl_perawatan')
        ->asc('jam_rawat')
        ->toArray();
      $pemeriksaan_ranap = $this->db('pemeriksaan_ranap')
        ->where('no_rawat', $this->revertNorawat($id))
        ->asc('tgl_perawatan')
        ->asc('jam_rawat')
        ->toArray();
      $rawat_jl_dr = $this->db('rawat_jl_dr')
        ->join('jns_perawatan', 'rawat_jl_dr.kd_jenis_prw=jns_perawatan.kd_jenis_prw')
        ->join('dokter', 'rawat_jl_dr.kd_dokter=dokter.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_jl_pr = $this->db('rawat_jl_pr')
        ->join('jns_perawatan', 'rawat_jl_pr.kd_jenis_prw=jns_perawatan.kd_jenis_prw')
        ->join('petugas', 'rawat_jl_pr.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_jl_drpr = $this->db('rawat_jl_drpr')
        ->join('jns_perawatan', 'rawat_jl_drpr.kd_jenis_prw=jns_perawatan.kd_jenis_prw')
        ->join('dokter', 'rawat_jl_drpr.kd_dokter=dokter.kd_dokter')
        ->join('petugas', 'rawat_jl_drpr.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_inap_dr = $this->db('rawat_inap_dr')
        ->join('jns_perawatan_inap', 'rawat_inap_dr.kd_jenis_prw=jns_perawatan_inap.kd_jenis_prw')
        ->join('dokter', 'rawat_inap_dr.kd_dokter=dokter.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_inap_pr = $this->db('rawat_inap_pr')
        ->join('jns_perawatan_inap', 'rawat_inap_pr.kd_jenis_prw=jns_perawatan_inap.kd_jenis_prw')
        ->join('petugas', 'rawat_inap_pr.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_inap_drpr = $this->db('rawat_inap_drpr')
        ->join('jns_perawatan_inap', 'rawat_inap_drpr.kd_jenis_prw=jns_perawatan_inap.kd_jenis_prw')
        ->join('dokter', 'rawat_inap_drpr.kd_dokter=dokter.kd_dokter')
        ->join('petugas', 'rawat_inap_drpr.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $kamar_inap = $this->db('kamar_inap')
        ->join('kamar', 'kamar_inap.kd_kamar=kamar.kd_kamar')
        ->join('bangsal', 'kamar.kd_bangsal=bangsal.kd_bangsal')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $operasi = $this->db('operasi')
        ->join('paket_operasi', 'operasi.kode_paket=paket_operasi.kode_paket')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $tindakan_radiologi = $this->db('periksa_radiologi')
        ->join('jns_perawatan_radiologi', 'periksa_radiologi.kd_jenis_prw=jns_perawatan_radiologi.kd_jenis_prw')
        ->join('dokter', 'periksa_radiologi.kd_dokter=dokter.kd_dokter')
        ->join('petugas', 'periksa_radiologi.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $hasil_radiologi = $this->db('hasil_radiologi')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $pemeriksaan_laboratorium = [];
      $rows_pemeriksaan_laboratorium = $this->db('periksa_lab')
        ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      foreach ($rows_pemeriksaan_laboratorium as $value) {
        $value['detail_periksa_lab'] = $this->db('detail_periksa_lab')
          ->join('template_laboratorium', 'template_laboratorium.id_template=detail_periksa_lab.id_template')
          ->where('detail_periksa_lab.no_rawat', $value['no_rawat'])
          ->where('detail_periksa_lab.kd_jenis_prw', $value['kd_jenis_prw'])
          ->toArray();
        $pemeriksaan_laboratorium[] = $value;
      }
      $pemberian_obat = $this->db('detail_pemberian_obat')
        ->join('databarang', 'detail_pemberian_obat.kode_brng=databarang.kode_brng')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $obat_operasi = $this->db('beri_obat_operasi')
        ->join('obatbhp_ok', 'beri_obat_operasi.kd_obat=obatbhp_ok.kd_obat')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $resep_pulang = $this->db('resep_pulang')
        ->join('databarang', 'resep_pulang.kode_brng=databarang.kode_brng')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $laporan_operasi = $this->db('laporan_operasi')
        ->where('no_rawat', $this->revertNorawat($id))
        ->oneArray();

      $this->tpl->set('pasien', $pasien);
      $this->tpl->set('reg_periksa', $reg_periksa);
      $this->tpl->set('rujukan_internal', $rujukan_internal);
      $this->tpl->set('dpjp_ranap', $dpjp_ranap);
      $this->tpl->set('diagnosa_pasien', $diagnosa_pasien);
      $this->tpl->set('prosedur_pasien', $prosedur_pasien);
      $this->tpl->set('pemeriksaan_ralan', $pemeriksaan_ralan);
      $this->tpl->set('pemeriksaan_ranap', $pemeriksaan_ranap);
      $this->tpl->set('rawat_jl_dr', $rawat_jl_dr);
      $this->tpl->set('rawat_jl_pr', $rawat_jl_pr);
      $this->tpl->set('rawat_jl_drpr', $rawat_jl_drpr);
      $this->tpl->set('rawat_inap_dr', $rawat_inap_dr);
      $this->tpl->set('rawat_inap_pr', $rawat_inap_pr);
      $this->tpl->set('rawat_inap_drpr', $rawat_inap_drpr);
      $this->tpl->set('kamar_inap', $kamar_inap);
      $this->tpl->set('operasi', $operasi);
      $this->tpl->set('tindakan_radiologi', $tindakan_radiologi);
      $this->tpl->set('hasil_radiologi', $hasil_radiologi);
      $this->tpl->set('pemeriksaan_laboratorium', $pemeriksaan_laboratorium);
      $this->tpl->set('pemberian_obat', $pemberian_obat);
      $this->tpl->set('obat_operasi', $obat_operasi);
      $this->tpl->set('resep_pulang', $resep_pulang);
      $this->tpl->set('laporan_operasi', $laporan_operasi);

      $this->tpl->set('berkas_digital', $berkas_digital);
      $this->tpl->set('berkas_digital_pasien', $berkas_digital_pasien);
      $this->tpl->set('hasil_radiologi', $this->db('hasil_radiologi')->where('no_rawat', $this->revertNorawat($id))->oneArray());
      $this->tpl->set('gambar_radiologi', $this->db('gambar_radiologi')->where('no_rawat', $this->revertNorawat($id))->toArray());
      $this->tpl->set('vedika', htmlspecialchars_array($this->settings('vedika')));
      echo $this->tpl->draw(MODULES.'/vedika/view/admin/pdf.html', true);
      exit();
    }

    public function getResume($id)
    {
      $resume_pasien = $this->db('resume_pasien')
        ->join('dokter', 'dokter.kd_dokter = resume_pasien.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->oneArray();
      $this->tpl->set('resume_pasien', $resume_pasien);
      echo $this->tpl->draw(MODULES.'/vedika/view/admin/resume.html', true);
      exit();
    }

    public function getRiwayat($id)
    {
      $pasien = $this->db('pasien')
        ->join('kecamatan', 'kecamatan.kd_kec = pasien.kd_kec')
        ->join('kabupaten', 'kabupaten.kd_kab = pasien.kd_kab')
        ->where('no_rkm_medis', $this->getRegPeriksaInfo('no_rkm_medis', $this->revertNorawat($id)))
        ->oneArray();
      $reg_periksa = $this->db('reg_periksa')
        ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
        ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
        ->join('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
        ->where('stts', '<>', 'Batal')
        ->where('no_rawat', $this->revertNorawat($id))
        ->oneArray();
      $rujukan_internal = $this->db('rujukan_internal_poli')
        ->join('poliklinik', 'poliklinik.kd_poli = rujukan_internal_poli.kd_poli')
        ->join('dokter', 'dokter.kd_dokter = rujukan_internal_poli.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->oneArray();
      $dpjp_ranap = $this->db('dpjp_ranap')
        ->join('dokter', 'dokter.kd_dokter = dpjp_ranap.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $diagnosa_pasien = $this->db('diagnosa_pasien')
        ->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $prosedur_pasien = $this->db('prosedur_pasien')
        ->join('icd9', 'icd9.kode = prosedur_pasien.kode')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')
        ->where('no_rawat', $this->revertNorawat($id))
        ->asc('tgl_perawatan')
        ->asc('jam_rawat')
        ->toArray();
      $pemeriksaan_ranap = $this->db('pemeriksaan_ranap')
        ->where('no_rawat', $this->revertNorawat($id))
        ->asc('tgl_perawatan')
        ->asc('jam_rawat')
        ->toArray();
      $rawat_jl_dr = $this->db('rawat_jl_dr')
        ->join('jns_perawatan', 'rawat_jl_dr.kd_jenis_prw=jns_perawatan.kd_jenis_prw')
        ->join('dokter', 'rawat_jl_dr.kd_dokter=dokter.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_jl_pr = $this->db('rawat_jl_pr')
        ->join('jns_perawatan', 'rawat_jl_pr.kd_jenis_prw=jns_perawatan.kd_jenis_prw')
        ->join('petugas', 'rawat_jl_pr.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_jl_drpr = $this->db('rawat_jl_drpr')
        ->join('jns_perawatan', 'rawat_jl_drpr.kd_jenis_prw=jns_perawatan.kd_jenis_prw')
        ->join('dokter', 'rawat_jl_drpr.kd_dokter=dokter.kd_dokter')
        ->join('petugas', 'rawat_jl_drpr.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_inap_dr = $this->db('rawat_inap_dr')
        ->join('jns_perawatan_inap', 'rawat_inap_dr.kd_jenis_prw=jns_perawatan_inap.kd_jenis_prw')
        ->join('dokter', 'rawat_inap_dr.kd_dokter=dokter.kd_dokter')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_inap_pr = $this->db('rawat_inap_pr')
        ->join('jns_perawatan_inap', 'rawat_inap_pr.kd_jenis_prw=jns_perawatan_inap.kd_jenis_prw')
        ->join('petugas', 'rawat_inap_pr.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $rawat_inap_drpr = $this->db('rawat_inap_drpr')
        ->join('jns_perawatan_inap', 'rawat_inap_drpr.kd_jenis_prw=jns_perawatan_inap.kd_jenis_prw')
        ->join('dokter', 'rawat_inap_drpr.kd_dokter=dokter.kd_dokter')
        ->join('petugas', 'rawat_inap_drpr.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $kamar_inap = $this->db('kamar_inap')
        ->join('kamar', 'kamar_inap.kd_kamar=kamar.kd_kamar')
        ->join('bangsal', 'kamar.kd_bangsal=bangsal.kd_bangsal')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $operasi = $this->db('operasi')
        ->join('paket_operasi', 'operasi.kode_paket=paket_operasi.kode_paket')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $tindakan_radiologi = $this->db('periksa_radiologi')
        ->join('jns_perawatan_radiologi', 'periksa_radiologi.kd_jenis_prw=jns_perawatan_radiologi.kd_jenis_prw')
        ->join('dokter', 'periksa_radiologi.kd_dokter=dokter.kd_dokter')
        ->join('petugas', 'periksa_radiologi.nip=petugas.nip')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $hasil_radiologi = $this->db('hasil_radiologi')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $pemeriksaan_laboratorium = [];
      $rows_pemeriksaan_laboratorium = $this->db('periksa_lab')
        ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      foreach ($rows_pemeriksaan_laboratorium as $value) {
        $value['detail_periksa_lab'] = $this->db('detail_periksa_lab')
          ->join('template_laboratorium', 'template_laboratorium.id_template=detail_periksa_lab.id_template')
          ->where('detail_periksa_lab.no_rawat', $value['no_rawat'])
          ->where('detail_periksa_lab.kd_jenis_prw', $value['kd_jenis_prw'])
          ->toArray();
        $pemeriksaan_laboratorium[] = $value;
      }
      $pemberian_obat = $this->db('detail_pemberian_obat')
        ->join('databarang', 'detail_pemberian_obat.kode_brng=databarang.kode_brng')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $obat_operasi = $this->db('beri_obat_operasi')
        ->join('obatbhp_ok', 'beri_obat_operasi.kd_obat=obatbhp_ok.kd_obat')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();
      $resep_pulang = $this->db('resep_pulang')
        ->join('databarang', 'resep_pulang.kode_brng=databarang.kode_brng')
        ->where('no_rawat', $this->revertNorawat($id))
        ->toArray();

      $this->tpl->set('pasien', $pasien);
      $this->tpl->set('reg_periksa', $reg_periksa);
      $this->tpl->set('rujukan_internal', $rujukan_internal);
      $this->tpl->set('dpjp_ranap', $dpjp_ranap);
      $this->tpl->set('diagnosa_pasien', $diagnosa_pasien);
      $this->tpl->set('prosedur_pasien', $prosedur_pasien);
      $this->tpl->set('pemeriksaan_ralan', $pemeriksaan_ralan);
      $this->tpl->set('pemeriksaan_ranap', $pemeriksaan_ranap);
      $this->tpl->set('rawat_jl_dr', $rawat_jl_dr);
      $this->tpl->set('rawat_jl_pr', $rawat_jl_pr);
      $this->tpl->set('rawat_jl_drpr', $rawat_jl_drpr);
      $this->tpl->set('rawat_inap_dr', $rawat_inap_dr);
      $this->tpl->set('rawat_inap_pr', $rawat_inap_pr);
      $this->tpl->set('rawat_inap_drpr', $rawat_inap_drpr);
      $this->tpl->set('kamar_inap', $kamar_inap);
      $this->tpl->set('operasi', $operasi);
      $this->tpl->set('tindakan_radiologi', $tindakan_radiologi);
      $this->tpl->set('hasil_radiologi', $hasil_radiologi);
      $this->tpl->set('pemeriksaan_laboratorium', $pemeriksaan_laboratorium);
      $this->tpl->set('pemberian_obat', $pemberian_obat);
      $this->tpl->set('obat_operasi', $obat_operasi);
      $this->tpl->set('resep_pulang', $resep_pulang);
      echo $this->tpl->draw(MODULES.'/vedika/view/admin/riwayat.html', true);
      exit();
    }

    public function getBilling($no_rawat)
    {
      $no_rawat = $this->revertNorawat($no_rawat);
      $query = $this->db()->pdo()->prepare("select no,nm_perawatan,pemisah,if(biaya=0,'',biaya),if(jumlah=0,'',jumlah),if(tambahan=0,'',tambahan),if(totalbiaya=0,'',totalbiaya),totalbiaya from billing where no_rawat='$no_rawat'");
      $query->execute();
      $rows = $query->fetchAll();
      $total=0;
      foreach ($rows as $key => $value) {
        $total = $total+$value['7'];
      }
      $total = $total;
      $this->tpl->set('total', $total);

      $instansi['logo'] = url().'/'.$this->settings->get('settings.logo');
      $instansi['nama_instansi'] = $this->settings->get('settings.nama_instansi');
      $instansi['alamat_instansi'] = $this->settings->get('settings.alamat');
      $instansi['kabupaten'] = $this->settings->get('settings.kota');
      $instansi['propinsi'] = $this->settings->get('settings.propinsi');
      $instansi['kontak'] = $this->settings->get('settings.nomor_telepon');
      $instansi['email'] = $this->settings->get('settings.email');

      $this->tpl->set('billing', $rows);
      $this->tpl->set('instansi', $instansi);

      echo $this->tpl->draw(MODULES.'/vedika/view/admin/billing.html', true);
      exit();
    }

    public function getBerkasPasien()
    {
      echo $this->tpl->draw(MODULES.'/vedika/view/admin/berkaspasien.html', true);
      exit();
    }

    public function anyBerkasPerawatan($no_rawat)
    {
      $this->assign['master_berkas_digital'] = $this->db('master_berkas_digital')->toArray();
      $this->assign['berkas_digital'] = $this->db('berkas_digital_perawatan')->where('no_rawat', $no_rawat)->toArray();
      $this->assign['no_rawat'] = $no_rawat;
      $this->tpl->set('berkasperawatan', $this->assign);

      echo $this->tpl->draw(MODULES.'/vedika/view/admin/berkasperawatan.html', true);
      exit();
    }

    public function postSaveBerkasDigital()
    {

      $dir    = $this->_uploads;
      $cntr   = 0;

      $image = $_FILES['files']['tmp_name'];
      $img = new \Systems\Lib\Image();
      $id = convertNorawat($_POST['no_rawat']);
      if ($img->load($image)) {
          $imgName = time().$cntr++;
          $imgPath = $dir.'/'.$id.'_'.$imgName.'.'.$img->getInfos('type');
          $lokasi_file = 'pages/upload/'.$id.'_'.$imgName.'.'.$img->getInfos('type');
          $img->save($imgPath);
          $query = $this->db('berkas_digital_perawatan')->save(['no_rawat' => $_POST['no_rawat'], 'kode' => $_POST['kode'], 'lokasi_file' => $lokasi_file]);
          if($query) {
            echo '<br><img src="'.WEBAPPS_URL.'/berkasrawat/'.$lokasi_file.'" width="150" />';
          }
      }

      exit();

    }

    private function _getSEPInfo($field, $no_rawat)
    {
        $row = $this->db('bridging_sep')->where('no_rawat', $no_rawat)->oneArray();
        return $row[$field];
    }

    private function _getDiagnosa($field, $no_rawat, $status_lanjut)
    {
        $row = $this->db('diagnosa_pasien')->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')->where('diagnosa_pasien.no_rawat', $no_rawat)->where('diagnosa_pasien.prioritas', 1)->where('diagnosa_pasien.status', $status_lanjut)->oneArray();
        return $row[$field];
    }

    public function getSettings()
    {
        $this->_addHeaderFiles();
        $this->assign['title'] = 'Pengaturan Modul Vedika';
        $this->assign['vedika'] = htmlspecialchars_array($this->settings('vedika'));
        $this->assign['master_berkas_digital'] = $this->db('master_berkas_digital')->toArray();
        return $this->draw('settings.html', ['settings' => $this->assign]);
    }

    public function postSaveSettings()
    {
        foreach ($_POST['vedika'] as $key => $val) {
            $this->settings('vedika', $key, $val);
        }
        $this->notify('success', 'Pengaturan telah disimpan');
        redirect(url([ADMIN, 'vedika', 'settings']));
    }

    public function getQrCode()
    {
      $data = $_GET['no_sep'];
      $sep = $this->db('bridging_sep')->where('no_sep', $_GET['no_sep'])->oneArray();
      $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $sep['no_rawat'])->oneArray();
      $pasien = $this->db('pasien')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->oneArray();
      $data = isset($_GET['data']) ? $_GET['data'] : 'Nama: '.$pasien['nm_pasien'].', Nomor RM: '.$pasien['no_rkm_medis'].', Nomor Rawat: '.$sep['no_rawat'].', Nomor SEP: '.$sep['no_sep'];
      $size = isset($_GET['size']) ? $_GET['size'] : '120x120';
      $logo = isset($_GET['logo']) ? $_GET['logo'] : 'https://www.rsaurasyifa.com/plugins/website_ausy/images/favicon.png';

      header('Content-type: image/png');
      // Get QR Code image from Google Chart API
      // http://code.google.com/apis/chart/infographics/docs/qr_codes.html
      $QR = imagecreatefrompng('https://chart.googleapis.com/chart?cht=qr&chld=H|1&chs='.$size.'&chl='.urlencode($data));
      if($logo !== FALSE){
      	$logo = imagecreatefromstring(file_get_contents($logo));

      	$QR_width = imagesx($QR);
      	$QR_height = imagesy($QR);

      	$logo_width = imagesx($logo);
      	$logo_height = imagesy($logo);

      	// Scale logo to fit in the QR Code
      	$logo_qr_width = $QR_width/3;
      	$scale = $logo_width/$logo_qr_width;
      	$logo_qr_height = $logo_height/$scale;

      	imagecopyresampled($QR, $logo, $QR_width/3, $QR_height/3, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
      }
      imagepng($QR);
      imagedestroy($QR);
      exit();

    }

    public function getQrCodeDokter()
    {
      $data = $_GET['kd_dokter'];
      $dokter = $this->db('dokter')->where('kd_dokter', $_GET['kd_dokter'])->oneArray();
      $data = isset($_GET['data']) ? $_GET['data'] : 'Nama: '.$dokter['nm_dokter'].', Kode: '.$dokter['kd_dokter'];
      $size = isset($_GET['size']) ? $_GET['size'] : '100x100';
      $logo = isset($_GET['logo']) ? $_GET['logo'] : 'https://www.rsaurasyifa.com/plugins/website_ausy/images/favicon.png';

      header('Content-type: image/png');
      // Get QR Code image from Google Chart API
      // http://code.google.com/apis/chart/infographics/docs/qr_codes.html
      $QR = imagecreatefrompng('https://chart.googleapis.com/chart?cht=qr&chld=H|1&chs='.$size.'&chl='.urlencode($data));
      if($logo !== FALSE){
      	$logo = imagecreatefromstring(file_get_contents($logo));

      	$QR_width = imagesx($QR);
      	$QR_height = imagesy($QR);

      	$logo_width = imagesx($logo);
      	$logo_height = imagesy($logo);

      	// Scale logo to fit in the QR Code
      	$logo_qr_width = $QR_width/3;
      	$scale = $logo_width/$logo_qr_width;
      	$logo_qr_height = $logo_height/$scale;

      	imagecopyresampled($QR, $logo, $QR_width/3, $QR_height/3, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
      }
      imagepng($QR);
      imagedestroy($QR);
      exit();

    }

    public function getPegawaiInfo($field, $nik)
    {
        $row = $this->db('pegawai')->where('nik', $nik)->oneArray();
        return $row[$field];
    }

    public function getPasienInfo($field, $no_rkm_medis)
    {
        $row = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
        return $row[$field];
    }

    public function getRegPeriksaInfo($field, $no_rawat)
    {
        $row = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        return $row[$field];
    }

    public function convertNorawat($text)
    {
        setlocale(LC_ALL, 'en_EN');
        $text = str_replace('/', '', trim($text));
        return $text;
    }

    public function revertNorawat($text)
    {
        setlocale(LC_ALL, 'en_EN');
        $tahun = substr($text, 0, 4);
        $bulan = substr($text, 4, 2);
        $tanggal = substr($text, 6, 2);
        $nomor = substr($text, 8, 6);
        $result = $tahun.'/'.$bulan.'/'.$tanggal.'/'.$nomor;
        return $result;
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/vedika/js/admin/scripts.js');
        exit();
    }

    public function getCss()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/vedika/css/admin/styles.css');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'vedika', 'css']));
        $this->core->addJS(url([ADMIN, 'vedika', 'javascript']), 'footer');
    }

}
