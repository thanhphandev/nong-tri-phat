function addCart(path) {
    $("#addCart").click(function () {
        var id_nhacungcap = $("#id_nhacungcap").val();
        var id_hanghoa = $("#id_hanghoa").val();
        var so_luong = $("#so_luong").val();
        if (id_nhacungcap && id_hanghoa && so_luong) {
            var existingItem = $("input[name='id_hanghoa_cart[]'][value='" + id_hanghoa + "']");
            if (existingItem.length > 0) {
                var row = existingItem.closest('.item');
                var inputSoLuong = row.find('.so-luong');
                var currentSoLuong = parseFloat(inputSoLuong.val());
                var newSoLuong = currentSoLuong + parseFloat(so_luong);
                inputSoLuong.val(newSoLuong);
                inputSoLuong.trigger('change');
            } else {
                var path_get = path + "admin/nhap-hang/get-add-cart?id_nhacungcap=" + id_nhacungcap + "&id_hanghoa=" + id_hanghoa + "&so_luong=" + so_luong;
                $.get(path_get, function (hanghoa) {
                    $("#HangHoaList tbody").prepend(hanghoa); delete_cart();
                    tong_thanh_tien();
                    $("#id_nhacungcap").prop('disabled', true);
                    $("#updateCart").prop("disabled", false);
                    $("#id_nhacungcap_cart").val(id_nhacungcap);
                    change_so_luong(); jQuery(".number").number(true, 0, ',', '.');
                    jQuery(".datepicker").datepicker({ autoclose: !0, orientation: "bottom", todayHighlight: !0, format: "dd/mm/yyyy" });
                });
            }
        } else {
            alert('Vui lòng chọn Nhà cung cấp, Hàng hóa và Số lượng');
        }
    });
}
function delete_cart() {
    $(".delete_cart").click(function () {
        var _this = $(this);
        _this.parents(".item").remove();
        tong_thanh_tien();
    });
}

function tim_hang_hoa(path) {
    //$id_hanghoa, thongtinhanghoa
    $.getJSON(path, function (hh) {
        if (hh.id_hanghoa) {
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

function tong_thanh_tien() {
    var tong_thanh_tien = 0;
    $(".thanh-tien").each(function () {
        var val = parseFloat($(this).val());
        if (!isNaN(val)) tong_thanh_tien += val;
    });

    $("#thanh-tien").val(tong_thanh_tien);
    $("#thanh-tien-show").html(currencyFormat(tong_thanh_tien));
}


function change_so_luong() {
    $(".cart-change").off("change").change(function () {
        var parent = $(this).parents(".item");
        var so_luong = parseFloat(parent.find(".so-luong").val());
        var don_gia = parseFloat(parent.find(".don-gia").val());

        if (isNaN(so_luong) || so_luong == 0) {
            so_luong = 1; parent.find(".so-luong").val(1)
        }

        var tt = don_gia * so_luong;
        parent.find(".thanh-tien").val(tt);
        parent.find(".thanh-tien-show").html(currencyFormat(tt));

        if ($(this).hasClass('so-thang')) {
            var so_thang = parseInt($(this).val());
            if (!isNaN(so_thang) && so_thang > 0) {
                var today = new Date();
                var targetDate = new Date(today.setMonth(today.getMonth() + so_thang));
                var dd = targetDate.getDate();
                var mm = targetDate.getMonth() + 1;
                var yyyy = targetDate.getFullYear();
                if (dd < 10) { dd = '0' + dd }
                if (mm < 10) { mm = '0' + mm }
                var formattedDate = dd + '/' + mm + '/' + yyyy;
                parent.find(".ngay-het-han").val(formattedDate);
                parent.find(".ngay-het-han").datepicker('update', formattedDate);
            }
        }

        tong_thanh_tien();
    });
}
