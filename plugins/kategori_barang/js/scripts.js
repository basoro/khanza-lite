jQuery().ready(function () {
    var var_tbl_kategori_barang = $('#tbl_kategori_barang').DataTable({
        'processing': true,
        'serverSide': true,
        'serverMethod': 'post',
        'dom': 'Bfrtip',
        'searching': false,
        'select': true,
        'colReorder': true,
        "bInfo" : false,
        "ajax": {
            "url": "{?=url(['kategori_barang','data'])?}",
            "dataType": "json",
            "type": "POST",
            "data": function (data) {

                // Read values
                var search_field_kategori_barang = $('#search_field_kategori_barang').val();
                var search_text_kategori_barang = $('#search_text_kategori_barang').val();
                
                data.search_field_kategori_barang = search_field_kategori_barang;
                data.search_text_kategori_barang = search_text_kategori_barang;
                
            }
        },
        "fnDrawCallback": function () {
            $('#more_data_kategori_barang').on('click', function(e) {
                e.preventDefault();
                var clientX = e.originalEvent.clientX;
                var clientY = e.originalEvent.clientY;
                $('#tbl_kategori_barang tr').contextMenu({x: clientX, y: clientY});
            });          
        }, 
        "columns": [
{ 'data': 'kode' },
{ 'data': 'nama' }

        ],
        "columnDefs": [
{ 'targets': 0},
{ 'targets': 1}

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
        selector: '#tbl_kategori_barang tr', 
        trigger: 'right',
        callback: function(key, options) {
          var rowData = var_tbl_kategori_barang.rows({ selected: true }).data()[0];
          if (rowData != null) {
var kode = rowData['kode'];
            switch (key) {
                case 'detail' :
                    OpenModal(mlite.url + '/kategori_barang/detail/' + kode + '?t=' + mlite.token);
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

    $("form[name='form_kategori_barang']").validate({
        rules: {
kode: 'required',
nama: 'required'

        },
        messages: {
kode:'Kode tidak boleh kosong!',
nama:'Nama tidak boleh kosong!'

        },
        submitHandler: function (form) {
var kode= $('#kode').val();
var nama= $('#nama').val();

var typeact = $('#typeact').val();

var formData = new FormData(form); // tambahan
formData.append('typeact', typeact); // tambahan

            $.ajax({
                url: "{?=url(['kategori_barang','aksi'])?}",
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
                            $("#modal_kategori_barang").modal('hide');
                        } else {
                            bootbox.alert('<span class="text-danger">' + data.msg + '</span>');
                        }    
                    }
                    else if (typeact == "edit") {
                        if(data.status === 'success') {
                            bootbox.alert('<span class="text-success">' + data.msg + '</span>');
                            $("#modal_kategori_barang").modal('hide');
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
                    var_tbl_kategori_barang.draw();
                }
            })
        }
    });

    if(typeof ws != 'undefined' && typeof ws.readyState != 'undefined' && ws.readyState == 1){
        ws.onmessage = function(response){
            try{
                output = JSON.parse(response.data);
                if(output['action'] == 'add'){
                    var_tbl_kategori_barang.draw();
                }
                if(output['action'] == 'edit'){
                    var_tbl_kategori_barang.draw();
                }
                if(output['action'] == 'del'){
                    var_tbl_kategori_barang.draw();
                }
            }catch(e){
                console.log(e);
            }
        }
    }

    // ==============================================================
    // KETIKA TOMBOL SEARCH DITEKAN
    // ==============================================================
    $('#filter_search_kategori_barang').click(function () {
        var_tbl_kategori_barang.draw();
    });

    // ===========================================
    // KETIKA TOMBOL EDIT DITEKAN
    // ===========================================

    $("#edit_data_kategori_barang").click(function () {
        var rowData = var_tbl_kategori_barang.rows({ selected: true }).data()[0];
        if (rowData != null) {

            var kode = rowData['kode'];
var nama = rowData['nama'];

            $("#typeact").val("edit");
  
            $('#kode').val(kode);
$('#nama').val(nama);

            $("#kode").prop('readonly', true); // GA BISA DIEDIT KALI READONLY
            $('#modal-title').text("Edit Data Kategori Barang");
            $("#modal_kategori_barang").modal('show');
        }
        else {
            bootbox.alert("Silakan pilih data yang akan di edit.");
        }

    });

    // ==============================================================
    // TOMBOL  DELETE DI CLICK
    // ==============================================================
    jQuery("#hapus_data_kategori_barang").click(function () {
        var rowData = var_tbl_kategori_barang.rows({ selected: true }).data()[0];


        if (rowData) {
var kode = rowData['kode'];
            bootbox.confirm('Anda yakin akan menghapus data dengan kode="' + kode, function(result) {
                if(result) {
                    $.ajax({
                        url: "{?=url(['kategori_barang','aksi'])?}",
                        method: "POST",
                        data: {
                            kode: kode,
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
                            if(typeof ws != 'undefined' && typeof ws.readyState != 'undefined' && ws.readyState == 1){
                                let payload = {
                                    'action' : 'del'
                                }
                                ws.send(JSON.stringify(payload));
                            }
                            var_tbl_kategori_barang.draw();
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
    jQuery("#tambah_data_kategori_barang").click(function () {

        $('#kode').val('');
$('#nama').val('');

        $("#typeact").val("add");
        $("#kode").prop('readonly', false);
        
        $('#modal-title').text("Tambah Data Kategori Barang");
        $("#modal_kategori_barang").modal('show');
    });

    // ===========================================
    // Ketika tombol lihat data di tekan
    // ===========================================
    $("#lihat_data_kategori_barang").click(function () {

        var search_field_kategori_barang = $('#search_field_kategori_barang').val();
        var search_text_kategori_barang = $('#search_text_kategori_barang').val();

        $.ajax({
            url: "{?=url(['kategori_barang','aksi'])?}",
            method: "POST",
            data: {
                typeact: 'lihat', 
                search_field_kategori_barang: search_field_kategori_barang, 
                search_text_kategori_barang: search_text_kategori_barang
            },
            dataType: 'json',
            success: function (res) {
                var eTable = "<div class='table-responsive'><table id='tbl_lihat_kategori_barang' class='table display dataTable' style='width:100%'><thead><th>Kode</th><th>Nama</th></thead>";
                for (var i = 0; i < res.length; i++) {
                    eTable += "<tr>";
                    eTable += '<td>' + res[i]['kode'] + '</td>';
eTable += '<td>' + res[i]['nama'] + '</td>';
                    eTable += "</tr>";
                }
                eTable += "</tbody></table></div>";
                $('#forTable_kategori_barang').html(eTable);
            }
        });

        $('#modal-title').text("Lihat Data");
        $("#modal_lihat_kategori_barang").modal('show');
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
        doc.text("Tabel Data Kategori Barang", 20, 95, null, null, null);
        const totalPagesExp = "{total_pages_count_string}";        
        doc.autoTable({
            html: '#tbl_lihat_kategori_barang',
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
        // doc.save('table_data_kategori_barang.pdf');
        window.open(doc.output('bloburl'), '_blank',"toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes");  
              
    })

    // ===========================================
    // Ketika tombol export xlsx di tekan
    // ===========================================
    $("#export_xlsx").click(function () {
        let tbl1 = document.getElementById("tbl_lihat_kategori_barang");
        let worksheet_tmp1 = XLSX.utils.table_to_sheet(tbl1);
        let a = XLSX.utils.sheet_to_json(worksheet_tmp1, { header: 1 });
        let worksheet1 = XLSX.utils.json_to_sheet(a, { skipHeader: true });
        const new_workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(new_workbook, worksheet1, "Data kategori_barang");
        XLSX.writeFile(new_workbook, 'tmp_file.xls');
    })

    $("#view_chart").click(function () {
        window.open(mlite.url + '/kategori_barang/chart?t=' + mlite.token, '_blank',"toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes");  
    })   

});