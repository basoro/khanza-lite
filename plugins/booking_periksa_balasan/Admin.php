<?php
namespace Plugins\Booking_Periksa_Balasan;

use Systems\AdminModule;

class Admin extends AdminModule
{

    public function navigation()
    {
        return [
            'Kelola'   => 'manage',
        ];
    }

    public function getManage()
    {
        $this->_addHeaderFiles();
        $disabled_menu = $this->core->loadDisabledMenu('booking_periksa_balasan'); 
        foreach ($disabled_menu as &$row) { 
          if ($row == "true" ) $row = "disabled"; 
        } 
        unset($row);
        return $this->draw('manage.html', ['disabled_menu' => $disabled_menu]);
    }

    public function postData()
    {
        $column_name = isset_or($_POST['column_name'], 'no_booking');
        $column_order = isset_or($_POST['column_order'], 'asc');
        $draw = isset_or($_POST['draw'], '0');
        $row1 = isset_or($_POST['start'], '0');
        $rowperpage = isset_or($_POST['length'], '10'); // Rows display per page
        $columnIndex = isset_or($_POST['order'][0]['column']); // Column index
        $columnName = isset_or($_POST['columns'][$columnIndex]['data'], $column_name); // Column name
        $columnSortOrder = isset_or($_POST['order'][0]['dir'], $column_order); // asc or desc
        $searchValue = isset_or($_POST['search']['value']); // Search value

        ## Custom Field value
        $search_field_booking_periksa_balasan= isset_or($_POST['search_field_booking_periksa_balasan']);
        $search_text_booking_periksa_balasan = isset_or($_POST['search_text_booking_periksa_balasan']);

        if ($search_text_booking_periksa_balasan != '') {
          $where[$search_field_booking_periksa_balasan.'[~]'] = $search_text_booking_periksa_balasan;
          $where = ["AND" => $where];
        } else {
          $where = [];
        }

        ## Total number of records without filtering
        $totalRecords = $this->core->db->count('booking_periksa_balasan', '*');

        ## Total number of records with filtering
        $totalRecordwithFilter = $this->core->db->count('booking_periksa_balasan', '*', $where);

        ## Fetch records
        $where['ORDER'] = [$columnName => strtoupper($columnSortOrder)];
        $where['LIMIT'] = [$row1, $rowperpage];
        $result = $this->core->db->select('booking_periksa_balasan', '*', $where);

        $data = array();
        foreach($result as $row) {
            $data[] = array(
                'no_booking'=>$row['no_booking'],
'balasan'=>$row['balasan']

            );
        }

        ## Response
        http_response_code(200);
        $response = array(
            "draw" => intval($draw), 
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        if($this->settings('settings', 'logquery') == true) {
          $this->core->LogQuery('booking_periksa_balasan => postData');
        }

        echo json_encode($response);
        exit();
    }

    public function postAksi()
    {
        if(isset($_POST['typeact'])){ 
            $act = $_POST['typeact']; 
        }else{ 
            $act = ''; 
        }

        if ($act=='add') {

            if($this->core->loadDisabledMenu('booking_periksa_balasan')['create'] == 'true') {
              http_response_code(403);
              $data = array(
                'code' => '403', 
                'status' => 'error', 
                'msg' => 'Maaf, akses dibatasi!'
              );
              echo json_encode($data);    
              exit();
            }

        $no_booking = $_POST['no_booking'];
$balasan = $_POST['balasan'];

            
            $result = $this->core->db->insert('booking_periksa_balasan', [
'no_booking'=>$no_booking, 'balasan'=>$balasan
            ]);


            if (!empty($result)){
              http_response_code(200);
              $data = array(
                'code' => '200', 
                'status' => 'success', 
                'msg' => 'Data telah ditambah'
              );
            } else {
              http_response_code(201);
              $data = array(
                'code' => '201', 
                'status' => 'error', 
                'msg' => $this->core->db->errorInfo[2]
              );
            }

            if($this->settings('settings', 'logquery') == true) {
              $this->core->LogQuery('booking_periksa_balasan => postAksi => add');
            }

            echo json_encode($data);    
        }
        if ($act=="edit") {

            if($this->core->loadDisabledMenu('booking_periksa_balasan')['update'] == 'true') {
              http_response_code(403);
              $data = array(
                'code' => '403', 
                'status' => 'error', 
                'msg' => 'Maaf, akses dibatasi!'
              );
              echo json_encode($data);    
              exit();
            }

        $no_booking = $_POST['no_booking'];
$balasan = $_POST['balasan'];


        // BUANG FIELD PERTAMA

            $result = $this->core->db->update('booking_periksa_balasan', [
'no_booking'=>$no_booking, 'balasan'=>$balasan
            ], [
              'no_booking'=>$no_booking
            ]);


            if (!empty($result)){
              http_response_code(200);
              $data = array(
                'code' => '200', 
                'status' => 'success', 
                'msg' => 'Data telah diubah'
              );
            } else {
              http_response_code(201);
              $data = array(
                'code' => '201', 
                'status' => 'error', 
                'msg' => $this->core->db->errorInfo[2]
              );
            }

            if($this->settings('settings', 'logquery') == true) {
              $this->core->LogQuery('booking_periksa_balasan => postAksi => edit');
            }

            echo json_encode($data);             
        }

        if ($act=="del") {

            if($this->core->loadDisabledMenu('booking_periksa_balasan')['delete'] == 'true') {
              http_response_code(403);
              $data = array(
                'code' => '403', 
                'status' => 'error', 
                'msg' => 'Maaf, akses dibatasi!'
              );
              echo json_encode($data);    
              exit();
            }

            $no_booking= $_POST['no_booking'];
            $result = $this->core->db->delete('booking_periksa_balasan', [
              'AND' => [
                'no_booking'=>$no_booking
              ]
            ]);

            if (!empty($result)){
              http_response_code(200);
              $data = array(
                'code' => '200', 
                'status' => 'success', 
                'msg' => 'Data telah dihapus'
              );
            } else {
              http_response_code(201);
              $data = array(
                'code' => '201', 
                'status' => 'error', 
                'msg' => $this->core->db->errorInfo[2]
              );
            }

            if($this->settings('settings', 'logquery') == true) {
              $this->core->LogQuery('booking_periksa_balasan => postAksi => del');
            }

            echo json_encode($data);                    
        }

        if ($act=="lihat") {

            if($this->core->loadDisabledMenu('booking_periksa_balasan')['read'] == 'true') {
              http_response_code(403);
              $data = array(
                'code' => '403', 
                'status' => 'error', 
                'msg' => 'Maaf, akses dibatasi!'
              );
              echo json_encode($data);    
              exit();
            }

            $search_field_booking_periksa_balasan= $_POST['search_field_booking_periksa_balasan'];
            $search_text_booking_periksa_balasan = $_POST['search_text_booking_periksa_balasan'];

            if ($search_text_booking_periksa_balasan != '') {
              $where[$search_field_booking_periksa_balasan.'[~]'] = $search_text_booking_periksa_balasan;
              $where = ["AND" => $where];
            } else {
              $where = [];
            }

            ## Fetch records
            $result = $this->core->db->select('booking_periksa_balasan', '*', $where);

            $data = array();
            foreach($result as $row) {
                $data[] = array(
                    'no_booking'=>$row['no_booking'],
'balasan'=>$row['balasan']
                );
            }

            if($this->settings('settings', 'logquery') == true) {
              $this->core->LogQuery('booking_periksa_balasan => postAksi => lihat');
            }
            
            echo json_encode($data);
        }
        exit();
    }

    public function getRead($no_booking)
    {

        if($this->core->loadDisabledMenu('booking_periksa_balasan')['read'] == 'true') {
          http_response_code(403);
          $data = array(
            'code' => '403', 
            'status' => 'error', 
            'msg' => 'Maaf, akses dibatasi!'
          );
          echo json_encode($data);    
          exit();
        }

        $result =  $this->core->db->get('booking_periksa_balasan', '*', ['no_booking' => $no_booking]);

        if (!empty($result)){
          http_response_code(200);
          $data = array(
            'code' => '200', 
            'status' => 'success', 
            'msg' => $result
          );
        } else {
          http_response_code(201);
          $data = array(
            'code' => '201', 
            'status' => 'error', 
            'msg' => 'Data tidak ditemukan'
          );
        }

        if($this->settings('settings', 'logquery') == true) {
          $this->core->LogQuery('booking_periksa_balasan => getRead');
        }

        echo json_encode($data);        
        exit();
    }

    public function getDetail($no_booking)
    {

        if($this->core->loadDisabledMenu('booking_periksa_balasan')['read'] == 'true') {
          http_response_code(403);
          $data = array(
            'code' => '403', 
            'status' => 'error', 
            'msg' => 'Maaf, akses dibatasi!'
          );
          echo json_encode($data);    
          exit();
        }

        $settings =  $this->settings('settings');

        if($this->settings('settings', 'logquery') == true) {
          $this->core->LogQuery('booking_periksa_balasan => getDetail');
        }

        echo $this->draw('detail.html', ['settings' => $settings, 'no_booking' => $no_booking]);
        exit();
    }

    public function getChart($type = '', $column = '')
    {
      if($type == ''){
        $type = 'pie';
      }

      $labels = $this->core->db->select('booking_periksa_balasan', 'balasan', ['GROUP' => 'balasan']);
      $datasets = $this->core->db->select('booking_periksa_balasan', ['count' => \Medoo\Medoo::raw('COUNT(<balasan>)')], ['GROUP' => 'balasan']);

      if(isset_or($column)) {
        $labels = $this->core->db->select('booking_periksa_balasan', ''.$column.'', ['GROUP' => ''.$column.'']);
        $datasets = $this->core->db->select('booking_periksa_balasan', ['count' => \Medoo\Medoo::raw('COUNT(<'.$column.'>)')], ['GROUP' => ''.$column.'']);          
      }

      $database = DBNAME;
      $nama_table = 'booking_periksa_balasan';

      $get_table = $this->core->db->pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$database' AND TABLE_NAME='$nama_table'");
	    $get_table->execute();
	    $result = $get_table->fetchAll();

      if($this->settings('settings', 'logquery') == true) {
        $this->core->LogQuery('booking_periksa_balasan => getChart');
      }

      echo $this->draw('chart.html', ['type' => $type, 'column' => $result, 'labels' => json_encode($labels), 'datasets' => json_encode(array_column($datasets, 'count'))]);
      exit();
    }

    public function getCss()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/booking_periksa_balasan/css/styles.css');
        exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        $settings = $this->settings('settings');
        echo $this->draw(MODULES.'/booking_periksa_balasan/js/scripts.js', ['settings' => $settings, 'disabled_menu' => $this->core->loadDisabledMenu('booking_periksa_balasan')]);
        exit();
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/vendor/datatables/datatables.min.css'));
        $this->core->addCSS(url('assets/css/jquery.contextMenu.css'));
        $this->core->addJS(url('assets/js/jqueryvalidation.js'), 'footer');
        $this->core->addJS(url('assets/vendor/jspdf/xlsx.js'), 'footer');
        $this->core->addJS(url('assets/vendor/jspdf/jspdf.min.js'), 'footer');
        $this->core->addJS(url('assets/vendor/jspdf/jspdf.plugin.autotable.min.js'), 'footer');
        $this->core->addJS(url('assets/vendor/datatables/datatables.min.js'), 'footer');
        $this->core->addJS(url('assets/js/jquery.contextMenu.js'), 'footer');

        $this->core->addCSS(url([ 'booking_periksa_balasan', 'css']));
        $this->core->addJS(url([ 'booking_periksa_balasan', 'javascript']), 'footer');
    }

}
