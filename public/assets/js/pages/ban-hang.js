function addCart(path) {
    $("#addCart").click(function () {
        var id_khachhang = $("#id_khachhang").val();
        var id_hanghoa = $("#id_hanghoa").val();
        var so_luong = $("#so_luong").val();
        if (id_khachhang && id_hanghoa && so_luong) {
            var existingItem = $("input[name='id_hanghoa_cart[]'][value='" + id_hanghoa + "']");
            if (existingItem.length > 0) {
                var row = existingItem.closest('.item');
                var inputSoLuong = row.find('.so-luong');
                var currentSoLuong = parseFloat(inputSoLuong.val());
                var maxSoLuong = parseFloat(inputSoLuong.attr('max'));
                var newSoLuong = currentSoLuong + parseFloat(so_luong);
                if (newSoLuong <= maxSoLuong) {
                    inputSoLuong.val(newSoLuong);
                    inputSoLuong.trigger('change');
                } else {
                    alert('Số lượng tồn kho không đủ');
                }
            } else {
                var path_get = path + "admin/don-hang/get-add-cart?id_khachhang=" + id_khachhang + "&id_hanghoa=" + id_hanghoa + "&so_luong=" + so_luong;
                $.get(path_get, function (hanghoa) {
                    if (jQuery.trim(hanghoa) == 'Số lượng tồn kho không đủ') {
                        alert('Số lượng tồn kho không đủ');
                    } else {
                        $("#HangHoaList tbody").prepend(hanghoa); delete_cart();
                        tong_thanh_tien();
                        $("#id_khachhang").prop('disabled', true);
                        $("#updateCart").prop("disabled", false);
                        $("#id_khachhang_cart").val(id_khachhang);
                        change_so_luong(); jQuery(".number").number(true, 0, ',', '.');
                        update_prices_by_mode();
                    }
                });
            }
        } else {
            alert('Vui lòng chọn Khách hàng, Hàng hóa và Số lượng');
        }
    });
}

function update_prices_by_mode() {
    var hinh_thuc = $('input[name=hinh_thuc_thanh_toan]:checked').val();
    $(".don-gia").each(function () {

        var item = $(this);
        var original_val = parseFloat(item.val());
        // Only update if we have data attributes
        if (item.data('gia-si') !== undefined && item.data('gia-le') !== undefined) {
            var new_val = (hinh_thuc == 'tien_mat') ? parseFloat(item.data('gia-si')) : parseFloat(item.data('gia-le'));
            if (new_val !== undefined && new_val != original_val) {
                item.val(new_val);
                // Trigger change to recalculate row total
                item.trigger('change');
            }
        }
    });
}

function tong_thanh_tien() {
    var tong_thanh_tien = 0;
    $(".thanh-tien").each(function () {
        tong_thanh_tien += parseFloat($(this).val());
    });
    $("#tong-thanh-tien").val(tong_thanh_tien);
    $("#tong-thanh-tien-show").html(currencyFormat(tong_thanh_tien));

    // Logic cập nhật thanh toán
    var hinh_thuc = $('input[name=hinh_thuc_thanh_toan]:checked').val();
    if (hinh_thuc == 'tien_mat') {
        $("#thanh-toan").val(tong_thanh_tien);
    }
}

$(document).ready(function () {
    $('input[name=hinh_thuc_thanh_toan]').change(function () {
        var val = $(this).val();
        update_prices_by_mode();
        if (val == 'tien_mat') {
            $("#thanh-toan").prop('readonly', true);
            var total = $("#tong-thanh-tien").val();
            $("#thanh-toan").val(total);
        } else {
            $("#thanh-toan").prop('readonly', false);
            $("#thanh-toan").val(0); // Reset hoặc giữ nguyên tùy logic, ở đây reset về 0 để nhập
            $("#thanh-toan").select();
        }
    });
});

function delete_cart() {
    $(".delete_cart").click(function () {
        var _this = $(this);
        _this.parents(".item").remove();
        tong_thanh_tien();
    });
}

function currencyFormat(num) {
    return num.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.")
}

function change_so_luong() {
    $(".cart-change").off('change').change(function () {
        var parent = $(this).parents(".item");
        var so_luong = parseFloat(parent.find(".so-luong").val());
        var don_gia = parseFloat(parent.find(".don-gia").val());
        var chiet_khau = parseInt(parent.find(".chiet-khau").val());
        if (so_luong == 0) {
            so_luong = 1;
            parent.find(".so-luong").val(1)
        }
        if (chiet_khau < 0) {
            chiet_khau = 0;
            parent.find(".chiet-khau").val(0)
        }
        var tt = don_gia * so_luong;
        var ck = (don_gia * so_luong * chiet_khau) / 100;
        var thanh_tien = tt - ck;
        parent.find(".thanh-tien").val(thanh_tien);
        parent.find(".thanh-tien-show").html(currencyFormat(thanh_tien));
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
