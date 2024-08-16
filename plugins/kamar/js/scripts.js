jQuery().ready(function () {
    var var_tbl_kamar = $('#tbl_kamar').DataTable({
        'processing': true,
        'serverSide': true,
        'serverMethod': 'post',
        'dom': 'Bfrtip',
        'searching': false,
        'select': true,
        'colReorder': true,
        "bInfo" : false,
        "ajax": {
            "url": "{?=url(['kamar','data'])?}",
            "dataType": "json",
            "type": "POST",
            "data": function (data) {

                // Read values
                var search_field_kamar = $('#search_field_kamar').val();
                var search_text_kamar = $('#search_text_kamar').val();
                
                data.search_field_kamar = search_field_kamar;
                data.search_text_kamar = search_text_kamar;
                
            }
        },
        "fnDrawCallback": function () {
            $('#more_data_kamar').on('click', function(e) {
                e.preventDefault();
                var clientX = e.originalEvent.clientX;
                var clientY = e.originalEvent.clientY;
                $('#tbl_kamar tr').contextMenu({x: clientX, y: clientY});
            });          
        },        
        "columns": [
            { 'data': 'kd_kamar' },
            { 'data': 'kd_bangsal' },
            { 'data': 'nm_bangsal' },
            { 'data': 'trf_kamar' },
            { 'data': 'status' },
            { 'data': 'kelas' },
            { 'data': 'statusdata', 
                "render": function (data) {
                    if(data == '1') {
                        var status = 'Aktif';
                    } else {
                        var status = 'Tidak Aktif';
                    }
                    return status;
                }                
            }
        ],
        "columnDefs": [
            { 'targets': 0},
            { 'targets': 1},
            { 'targets': 2},
            { 'targets': 3},
            { 'targets': 4},
            { 'targets': 5},
            { 'targets': 6}
        ],
        order: [[1, 'DESC']], 
        buttons: [],
        "scrollCollapse": true,
        // "scrollY": '48vh', 
        // "pageLength":'25', 
        "lengthChange": true,
        "scrollX": true,
        dom: "<'row'<'col-sm-12'tr>><<'pmd-datatable-pagination' l i p>>"
    });


    $.contextMenu({
        selector: '#tbl_kamar tr', 
        trigger: 'right',
        callback: function(key, options) {
          var rowData = var_tbl_kamar.rows({ selected: true }).data()[0];
          if (rowData != null) {
            var kd_kamar = rowData['kd_kamar'];
            switch (key) {
                case 'detail' :
                    OpenModal(mlite.url + '/kamar/detail/' + kd_kamar + '?t=' + mlite.token);
                break;
                default :
                break
            } 
          } else {
            bootbox.alert("Silakan pilih data atau klik baris data.");            
          }          
        },
        items: {
            "detail": {name: "View Detail", "icon": "edit", disabled:  {$disabled_menu.read}}
        }
    });

    // ==============================================================
    // FORM VALIDASI
    // ==============================================================

    $("form[name='form_kamar']").validate({
        rules: {
            kd_kamar: 'required',
            kd_bangsal: 'required',
            trf_kamar: 'required',
            status: 'required',
            kelas: 'required',
            statusdata: 'required'
        },
        messages: {
            kd_kamar:'Kd Kamar tidak boleh kosong!',
            kd_bangsal:'Kd Bangsal tidak boleh kosong!',
            trf_kamar:'Trf Kamar tidak boleh kosong!',
            status:'Status tidak boleh kosong!',
            kelas:'Kelas tidak boleh kosong!',
            statusdata:'Statusdata tidak boleh kosong!'
        },
        submitHandler: function (form) {
            var kd_kamar= $('#kd_kamar').val();
            var kd_bangsal= $('#kd_bangsal').val();
            var trf_kamar= $('#trf_kamar').val();
            var status= $('#status').val();
            var kelas= $('#kelas').val();
            var statusdata= $('#statusdata').val();

            var typeact = $('#typeact').val();

            var formData = new FormData(form); // tambahan
            formData.append('typeact', typeact); // tambahan

            $.ajax({
                url: "{?=url(['kamar','aksi'])?}",
                method: "POST",
                contentType: false, // tambahan
                processData: false, // tambahan
                data: formData,
                success: function (data) {
                    data = JSON.parse(data);
                    var audio = new Audio('{?=url()?}/assets/sound/' + data.status + '.mp3');
                    audio.play();
                    if (typeact == "add") {
                        if(data.status === 'success') {
                            bootbox.alert('<span class="text-success">' + data.msg + '</span>');
                            $("#modal_kamar").modal('hide');
                        } else {
                            bootbox.alert('<span class="text-danger">' + data.msg + '</span>');
                        }    
                    }
                    else if (typeact == "edit") {
                        if(data.status === 'success') {
                            bootbox.alert('<span class="text-success">' + data.msg + '</span>');
                            $("#modal_kamar").modal('hide');
                        } else {
                            bootbox.alert('<span class="text-danger">' + data.msg + '</span>');
                        }    
                    }
                    if(typeof ws != 'undefined' && typeof ws.readyState != 'undefined' && ws.readyState == 1){
                        let payload = {
                            'action' : typeact
                        }
                        ws.send(JSON.stringify(payload));
                    } 
                    var_tbl_kamar.draw();
                }
            })
        }
    });


    if(typeof ws != 'undefined' && typeof ws.readyState != 'undefined' && ws.readyState == 1){
        ws.onmessage = function(response){
            try{
                output = JSON.parse(response.data);
                if(output['action'] == 'add'){
                    var_tbl_kamar.draw();
                }
                if(output['action'] == 'edit'){
                    var_tbl_kamar.draw();
                }
                if(output['action'] == 'del'){
                    var_tbl_kamar.draw();
                }
            }catch(e){
                console.log(e);
            }
        }
    }

    // ==============================================================
    // KETIKA TOMBOL SEARCH DITEKAN
    // ==============================================================
    $('#filter_search_kamar').click(function () {
        var_tbl_kamar.draw();
    });

    // ===========================================
    // KETIKA TOMBOL EDIT DITEKAN
    // ===========================================

    $("#edit_data_kamar").click(function () {
        var rowData = var_tbl_kamar.rows({ selected: true }).data()[0];
        if (rowData != null) {

            var kd_kamar = rowData['kd_kamar'];
            var kd_bangsal = rowData['kd_bangsal'];
            var trf_kamar = rowData['trf_kamar'];
            var status = rowData['status'];
            var kelas = rowData['kelas'];
            var statusdata = rowData['statusdata'];

            $("#typeact").val("edit");
  
            $('#kd_kamar').val(kd_kamar);
            $('#kd_bangsal').val(kd_bangsal).change();
            $('#trf_kamar').val(trf_kamar);
            $('#status').val(status).change();
            $('#kelas').val(kelas).change();
            $('#statusdata').val(statusdata).change();

            $("#kd_kamar").prop('readonly', true); // GA BISA DIEDIT KALI READONLY
            $('#modal-title').text("Edit Data Kamar");
            $("#modal_kamar").modal('show');
        }
        else {
            bootbox.alert("Silakan pilih data yang akan di edit.");
        }

    });

    // ==============================================================
    // TOMBOL  DELETE DI CLICK
    // ==============================================================
    jQuery("#hapus_data_kamar").click(function () {
        var rowData = var_tbl_kamar.rows({ selected: true }).data()[0];


        if (rowData) {
            var kd_kamar = rowData['kd_kamar'];
            bootbox.confirm('Anda yakin akan menghapus data dengan kd_kamar="' + kd_kamar, function(result) {
                if(result) {
                    $.ajax({
                        url: "{?=url(['kamar','aksi'])?}",
                        method: "POST",
                        data: {
                            kd_kamar: kd_kamar,
                            typeact: 'del'
                        },
                        success: function (data) {
                            data = JSON.parse(data);
                            var audio = new Audio('{?=url()?}/assets/sound/' + data.status + '.mp3');
                            audio.play();
                            if(data.status === 'success') {
                                bootbox.alert('<span class="text-success">' + data.msg + '</span>');
                            } else {
                                bootbox.alert('<span class="text-danger">' + data.msg + '</span>');
                            }    
                            var_tbl_kamar.draw();
                        }
                    })    
                }
            });

        }
        else {
            bootbox.alert("Pilih satu baris untuk dihapus");
        }
    });

    // ==============================================================
    // TOMBOL TAMBAH DATA DI CLICK
    // ==============================================================
    jQuery("#tambah_data_kamar").click(function () {

        $('#kd_kamar').val('');
        $('#kd_bangsal').val('').change();
        $('#trf_kamar').val('');
        $('#status').val('').change();
        $('#kelas').val('').change();
        $('#statusdata').val('').change();

        $("#typeact").val("add");
        $("#kd_kamar").prop('readonly', false);
        
        $('#modal-title').text("Tambah Data Kamar");
        $("#modal_kamar").modal('show');
    });

    // ===========================================
    // Ketika tombol lihat data di tekan
    // ===========================================
    $("#lihat_data_kamar").click(function () {

        var search_field_kamar = $('#search_field_kamar').val();
        var search_text_kamar = $('#search_text_kamar').val();

        $.ajax({
            url: "{?=url(['kamar','aksi'])?}",
            method: "POST",
            data: {
                typeact: 'lihat', 
                search_field_kamar: search_field_kamar, 
                search_text_kamar: search_text_kamar
            },
            dataType: 'json',
            success: function (res) {
                var eTable = "<div class='table-responsive'><table id='tbl_lihat_kamar' class='table display dataTable' style='width:100%'><thead><th>Kd Kamar</th><th>Kd Bangsal</th><th>Trf Kamar</th><th>Status</th><th>Kelas</th><th>Statusdata</th></thead>";
                for (var i = 0; i < res.length; i++) {
                    eTable += "<tr>";
                    eTable += '<td>' + res[i]['kd_kamar'] + '</td>';
                    eTable += '<td>' + res[i]['kd_bangsal'] + '</td>';
                    eTable += '<td>' + res[i]['trf_kamar'] + '</td>';
                    eTable += '<td>' + res[i]['status'] + '</td>';
                    eTable += '<td>' + res[i]['kelas'] + '</td>';
                    eTable += '<td>' + res[i]['statusdata'] + '</td>';
                    eTable += "</tr>";
                }
                eTable += "</tbody></table></div>";
                $('#forTable_kamar').html(eTable);
            }
        });

        $('#modal-title').text("Lihat Data");
        $("#modal_lihat_kamar").modal('show');
    });
        
    // ===========================================
    // Ketika tombol export pdf di tekan
    // ===========================================
    $("#export_pdf").click(function () {

        var doc = new jsPDF('p', 'pt', 'A4'); /* pilih 'l' atau 'p' */
        var img = "{?=base64_encode(file_get_contents(url($settings['logo'])))?}";
        doc.addImage(img, 'JPEG', 20, 10, 50, 50);
        doc.setFontSize(20);
        doc.text("{$settings.nama_instansi}", 80, 35, null, null, null);
        doc.setFontSize(10);
        doc.text("{$settings.alamat} - {$settings.kota} - {$settings.propinsi}", 80, 46, null, null, null);
        doc.text("Telepon: {$settings.nomor_telepon} - Email: {$settings.email}", 80, 56, null, null, null);
        doc.line(20,70,572,70,null); /* doc.line(20,70,820,70,null); --> Jika landscape */
        doc.line(20,72,572,72,null); /* doc.line(20,72,820,72,null); --> Jika landscape */
        doc.setFontSize(14);
        doc.text("Tabel Data Kamar", 20, 95, null, null, null);
        const totalPagesExp = "{total_pages_count_string}";        
        doc.autoTable({
            html: '#tbl_lihat_kamar',
            startY: 105,
            margin: {
                left: 20, 
                right: 20
            }, 
            styles: {
                fontSize: 10,
                cellPadding: 5
            }, 
            didDrawPage: data => {
                let footerStr = "Page " + doc.internal.getNumberOfPages();
                if (typeof doc.putTotalPages === 'function') {
                footerStr = footerStr + " of " + totalPagesExp;
                }
                doc.setFontSize(10);
                doc.text(`© ${new Date().getFullYear()} {$settings.nama_instansi}.`, data.settings.margin.left, doc.internal.pageSize.height - 10);                
                doc.text(footerStr, data.settings.margin.left + 480, doc.internal.pageSize.height - 10);
           }
        });
        if (typeof doc.putTotalPages === 'function') {
            doc.putTotalPages(totalPagesExp);
        }
        // doc.save('table_data_kamar.pdf')
        window.open(doc.output('bloburl'), '_blank',"toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes");  
              
    })

    // ===========================================
    // Ketika tombol export xlsx di tekan
    // ===========================================
    $("#export_xlsx").click(function () {
        let tbl1 = document.getElementById("tbl_lihat_kamar");
        let worksheet_tmp1 = XLSX.utils.table_to_sheet(tbl1);
        let a = XLSX.utils.sheet_to_json(worksheet_tmp1, { header: 1 });
        let worksheet1 = XLSX.utils.json_to_sheet(a, { skipHeader: true });
        const new_workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(new_workbook, worksheet1, "Data kamar");
        XLSX.writeFile(new_workbook, 'tmp_file.xls');
    })

    $("#view_chart").click(function () {
        window.open(mlite.url + '/kamar/chart?t=' + mlite.token, '_blank',"toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes");  
    })   

});