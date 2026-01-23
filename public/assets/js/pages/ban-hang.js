function addCart(path){
	$("#addCart").click(function(){
        var id_khachhang = $("#id_khachhang").val();
        var id_hanghoa = $("#id_hanghoa").val();
        var so_luong = $("#so_luong").val();
        if(id_khachhang && id_hanghoa && so_luong){
            var path_get = path + "admin/don-hang/get-add-cart?id_khachhang="+id_khachhang+"&id_hanghoa="+id_hanghoa+"&so_luong="+so_luong;
            $.get(path_get, function(hanghoa){
                if(jQuery.trim(hanghoa) == 'Số lượng tồn kho không đủ'){
                    alert('Số lượng tồn kho không đủ');
                } else {
                    $("#HangHoaList tbody").prepend(hanghoa);delete_cart();
                    tong_thanh_tien();
                    $("#id_khachhang").prop('disabled', true);
                    $("#updateCart").prop("disabled", false);
                    $("#id_khachhang_cart").val(id_khachhang);
                    change_so_luong();jQuery(".number").number(true, 0, ',', '.');
                }
            });
        } else {
            alert('Vui lòng chọn Khách hàng, Hàng hóa và Số lượng');
        }
    });
}

function tong_thanh_tien(){
    var tong_thanh_tien = 0;
    $(".thanh-tien").each(function(){
        tong_thanh_tien += parseFloat($(this).val());
    });
    $("#tong-thanh-tien").val(tong_thanh_tien);
    $("#tong-thanh-tien-show").html(currencyFormat(tong_thanh_tien));
}

function delete_cart(){
	$(".delete_cart").click(function(){
		var _this = $(this);
		_this.parents(".item").remove();
        tong_thanh_tien();
	});
}

function currencyFormat(num) {
    return num.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.")
}

function change_so_luong(){
    $(".cart-change").change(function(){
        var parent = $(this).parents(".item");
        var so_luong = parseFloat(parent.find(".so-luong").val());
        var don_gia = parseFloat(parent.find(".don-gia").val());
        var chiet_khau = parseInt(parent.find(".chiet-khau").val());
        if(so_luong == 0) {
            so_luong = 1;
            parent.find(".so-luong").val(1)
        }
        if(chiet_khau < 0){
            chiet_khau = 0;
            parent.find(".chiet-khau").val(0)
        }
        var tt = don_gia * so_luong;
        var ck = (don_gia * so_luong * chiet_khau)/100;
        var thanh_tien = tt - ck;
        parent.find(".thanh-tien").val(thanh_tien);
        parent.find(".thanh-tien-show").html(currencyFormat(thanh_tien));
        tong_thanh_tien();
    });
}

function tim_hang_hoa(path){
    //$id_hanghoa, thongtinhanghoa
    $.getJSON(path, function(hh){
        if(hh.id_hanghoa){
            $("#id_hanghoa").val(hh.id_hanghoa);
            $("#thongtinhanghoa").html(hh.thongtinhanghoa);
        } else {
            $("#id_hanghoa").val("");
            $("#thongtinhanghoa").html("Không tim thấy mặt hàng.");
        }
        $("#thongtinhanghoa").show();
    });
}

function autocomplete_mahang(path) {
    $('#mahanghoa').autocomplete({
        serviceUrl: path,
        dataType: 'json',
        paramName: 'search',
        type: "GET",
        onSelect: function (suggestion) {
            $(this).val(suggestion.data);
        }
    });
}
