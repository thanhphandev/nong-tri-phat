function addCart(path){
	$("#addCart").click(function(){
        var id_nhacungcap = $("#id_nhacungcap").val();
        var id_hanghoa = $("#id_hanghoa").val();
        var so_luong = $("#so_luong").val();
        if(id_nhacungcap && id_hanghoa && so_luong){
            var path_get = path + "admin/nhap-hang/get-add-cart?id_nhacungcap="+id_nhacungcap+"&id_hanghoa="+id_hanghoa+"&so_luong="+so_luong;
            $.get(path_get, function(hanghoa){
                if(jQuery.trim(hanghoa) == 'Số lượng tồn kho không đủ'){
                    alert('Số lượng tồn kho không đủ');
                } else {
                    $("#HangHoaList tbody").prepend(hanghoa);delete_cart();
                    tong_thanh_tien();
                    $("#id_nhacungcap").prop('disabled', true);
                    $("#updateCart").prop("disabled", false);
                    $("#id_nhacungcap_cart").val(id_nhacungcap);
                    change_so_luong();jQuery(".number").number(true, 0, ',', '.');
                }
            });
        } else {
            alert('Vui lòng chọn Nhà cung cấp, Hàng hóa và Số lượng');
        }
    });
}
function delete_cart(){
	$(".delete_cart").click(function(){
		var _this = $(this);
		_this.parents(".item").remove();
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

function currencyFormat(num) {
    return num.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.")
}

function tong_thanh_tien(){
    var tong_thanh_tien = 0; var tong_chiet_khau = 0; var tong_tien_chiet_khau = 0;
    $(".tong-thanh-tien").each(function(){
        tong_thanh_tien += parseFloat($(this).val());
    });

    $(".chiet-khau").each(function(){
        tong_chiet_khau += parseFloat($(this).val());
    });
    $(".tien-chiet-khau").each(function(){
        tong_tien_chiet_khau += parseFloat($(this).val());
    });
    var thanh_tien = tong_thanh_tien - tong_tien_chiet_khau;
    $("#tong-chiet-khau").val(tong_chiet_khau);
    $("#tong-chiet-khau-show").html(currencyFormat(tong_chiet_khau));
    $("#tong-tien-chiet-khau").val(tong_tien_chiet_khau);
    $("#tong-tien-chiet-khau-show").html(currencyFormat(tong_tien_chiet_khau));
    $("#tong-thanh-tien").val(tong_thanh_tien);
    $("#tong-thanh-tien-show").html(currencyFormat(tong_thanh_tien));
    $("#thanh-tien").val(thanh_tien);
    $("#thanh-tien-show").html(currencyFormat(thanh_tien));
}


function change_so_luong(){
    $(".cart-change").change(function(){
        var parent = $(this).parents(".item");
        var so_luong = parseFloat(parent.find(".so-luong").val());
        var don_gia = parseFloat(parent.find(".don-gia").val());
        var chiet_khau = parseInt(parent.find(".chiet-khau").val());
        if(so_luong == 0) {
            so_luong = 1; parent.find(".so-luong").val(1)
        }
        if(chiet_khau < 0){
            chiet_khau = 0; parent.find(".chiet-khau").val(0)
        }
        var ttt = don_gia * so_luong;
        var ck = (don_gia * so_luong * chiet_khau)/100;
        var tt = ttt - ck;
        parent.find(".tong-thanh-tien").val(ttt);
        parent.find(".tong-thanh-tien-show").html(currencyFormat(ttt));
        parent.find(".tien-chiet-khau").val(ck);
        parent.find(".tien-chiet-khau-show").html(currencyFormat(ck));
        parent.find(".thanh-tien").val(tt);
        parent.find(".thanh-tien-show").html(currencyFormat(tt));
        tong_thanh_tien();
    });
}
