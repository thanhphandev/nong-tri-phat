function addCart(path) {
    $("#addCart").click(function () {
        var id_khachhang = $("#id_khachhang").val();
        var id_hanghoa = $("#id_hanghoa").val();
        var so_luong = $("#so_luong").val();
        if (id_khachhang && id_hanghoa && so_luong) {
            var existingItem = $("input[name='id_hanghoa_cart[]'][value='" + id_hanghoa + "']");
            if (existingItem.length > 0) {
                // Sản phẩm đã tồn tại, cộng dồn số lượng (cho phép vượt tồn kho)
                var row = existingItem.closest('.item');
                var inputSoLuong = row.find('.so-luong');
                var currentSoLuong = parseFloat(inputSoLuong.val());
                var newSoLuong = currentSoLuong + parseFloat(so_luong);
                inputSoLuong.val(newSoLuong);
                inputSoLuong.trigger('change');
                // Reset input fields for next entry
                $("#id_hanghoa").val(null).trigger('change');
                $("#so_luong").val(1);
                $("#thongtinhanghoa").html("Thông tin hàng hóa:");
            } else {
                var path_get = path + "admin/don-hang/get-add-cart?id_khachhang=" + id_khachhang + "&id_hanghoa=" + id_hanghoa + "&so_luong=" + so_luong;
                $.get(path_get, function (hanghoa) {
                    $("#HangHoaList tbody").prepend(hanghoa); delete_cart();
                    tong_thanh_tien();
                    $("#id_khachhang").prop('disabled', true);
                    $("#updateCart").prop("disabled", false);

                    $("#id_khachhang_cart").val(id_khachhang);
                    change_so_luong(path); jQuery(".number").number(true, 0, ',', '.');
                    update_prices_by_mode();
                    // Reset input fields for next entry
                    $("#id_hanghoa").val(null).trigger('change');
                    $("#so_luong").val(1);
                    $("#thongtinhanghoa").html("Thông tin hàng hóa:");
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
    var tong_gia_von = 0;

    $(".thanh-tien").each(function () {
        tong_thanh_tien += parseFloat($(this).val());
    });

    // Tính tổng giá vốn từ các item
    $(".gia-von-thuc-te").each(function () {
        tong_gia_von += parseFloat($(this).val()) || 0;
    });

    var tong_loi_nhuan = tong_thanh_tien - tong_gia_von;

    $("#tong-thanh-tien").val(tong_thanh_tien);
    $("#tong-thanh-tien-show").html(currencyFormat(tong_thanh_tien));

    // Cập nhật hiển thị tổng giá vốn và lợi nhuận
    $("#tong-gia-von").val(tong_gia_von);
    $("#tong-gia-von-show").html(currencyFormat(tong_gia_von));

    $("#tong-loi-nhuan").val(tong_loi_nhuan);
    $("#tong-loi-nhuan-show").html(currencyFormat(tong_loi_nhuan));

    // Cập nhật màu sắc cho lợi nhuận
    var loiNhuanContainer = $("#loi-nhuan-container");
    loiNhuanContainer.removeClass('text-success text-danger');
    if (tong_loi_nhuan >= 0) {
        loiNhuanContainer.addClass('text-success');
    } else {
        loiNhuanContainer.addClass('text-danger');
    }

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
            $("#thanh-toan").prop('readonly', false);
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

function change_so_luong(path) {
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
        if (isNaN(chiet_khau)) {
            chiet_khau = 0;
        }
        var tt = don_gia * so_luong;
        var ck = (don_gia * so_luong * chiet_khau) / 100;
        var thanh_tien = tt - ck;
        parent.find(".thanh-tien").val(thanh_tien);
        parent.find(".thanh-tien-show").html(currencyFormat(thanh_tien));

        // Kiểm tra và hiển thị cảnh báo tồn kho
        var maxTon = parseFloat(parent.find(".so-luong").data('max-ton')) || 0;
        var stockWarning = parent.find("td:eq(1) .alert-danger");
        if (so_luong > maxTon) {
            var tru_am = so_luong - maxTon;
            var warningHtml = '<i class="fas fa-exclamation-triangle"></i> <strong>Cảnh báo:</strong> Tồn kho chỉ còn ' + currencyFormat(maxTon) + ', sẽ trừ âm ' + currencyFormat(tru_am);
            if (stockWarning.length > 0) {
                stockWarning.html(warningHtml);
            } else {
                parent.find("td:eq(1)").prepend('<div class="alert alert-danger p-1 m-1" style="font-size: 11px;">' + warningHtml + '</div>');
            }
            parent.addClass('table-warning');
        } else {
            if (stockWarning.length > 0) {
                stockWarning.remove();
            }
            parent.removeClass('table-warning');
        }

        // Check Batch Usage & Update Profit
        var id_hanghoa = parent.find("input[name='id_hanghoa_cart[]']").val();
        if (path && id_hanghoa) {
            $.get(path + "admin/don-hang/check-batch-usage", {
                id_hanghoa: id_hanghoa,
                so_luong: so_luong
            }, function (resp) {
                var warningName = parent.find("td:eq(1) .alert-warning");
                if (resp.warning_info) {
                    if (warningName.length > 0) {
                        warningName.html(resp.warning_info);
                    } else {
                        parent.find("td:eq(1) .profit-info").before('<div class="alert alert-warning p-1 m-1" style="font-size: 11px;">' + resp.warning_info + '</div>');
                    }
                } else {
                    if (warningName.length > 0) {
                        warningName.remove();
                    }
                }

                // Cập nhật giá vốn thực tế và lợi nhuận
                if (resp.gia_von_thuc_te !== undefined) {
                    var gia_von_thuc_te = parseFloat(resp.gia_von_thuc_te);
                    // Lấy lại thanh_tien hiện tại (có thể đã thay đổi)
                    var current_thanh_tien = parseFloat(parent.find(".thanh-tien").val()) || 0;
                    var loi_nhuan = current_thanh_tien - gia_von_thuc_te;

                    // Update hidden input
                    parent.find(".gia-von-thuc-te").val(gia_von_thuc_te);

                    // Update display
                    parent.find(".gia-von-show").html(currencyFormat(gia_von_thuc_te));
                    parent.find(".loi-nhuan-show").html(currencyFormat(loi_nhuan));

                    // Update badge color
                    var loiNhuanBadge = parent.find(".loi-nhuan-badge");
                    loiNhuanBadge.removeClass('badge-success badge-danger');
                    if (loi_nhuan >= 0) {
                        loiNhuanBadge.addClass('badge-success');
                    } else {
                        loiNhuanBadge.addClass('badge-danger');
                    }

                    // Cập nhật lại tổng sau khi có giá vốn mới
                    tong_thanh_tien();
                }
            });
        } else {
            // Tính lợi nhuận từ giá vốn hiện tại (khi không call API)
            var gia_von_thuc_te = parseFloat(parent.find(".gia-von-thuc-te").val()) || 0;
            var loi_nhuan = thanh_tien - gia_von_thuc_te;
            parent.find(".loi-nhuan-show").html(currencyFormat(loi_nhuan));

            var loiNhuanBadge = parent.find(".loi-nhuan-badge");
            loiNhuanBadge.removeClass('badge-success badge-danger');
            if (loi_nhuan >= 0) {
                loiNhuanBadge.addClass('badge-success');
            } else {
                loiNhuanBadge.addClass('badge-danger');
            }

            // Cập nhật tổng
            tong_thanh_tien();
        }
    });
}

function initializeProductSearch(path) {
    if (path.length > 0 && path.substr(-1) !== '/') {
        path += '/';
    }
    $('#id_hanghoa').select2({
        ajax: {
            url: path + 'admin/hang-hoa/autocomplete',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        placeholder: 'Tìm mặt hàng (Phím tắt F3, Mã, Tên, Mã vạch...)',
        minimumInputLength: 1,
        templateResult: formatRepo,
        templateSelection: formatRepoSelection,
        escapeMarkup: function (markup) { return markup; }
    });

    function formatRepo(repo) {
        if (repo.loading) return repo.text;

        var stockClass = repo.so_luong_ton > 0 ? 'stock-in' : 'stock-out';
        var stockText = repo.so_luong_ton > 0 ? repo.so_luong_ton : 'Hết hàng';

        var markup = "<div class='product-result'>" +
            "<div class='product-title'>" +
            "<span>" + repo.ten + "</span>" +
            "<span class='product-ma'>" + repo.ma + "</span>" +
            "</div>" +
            "<div class='product-info'>" +
            "<span><i class='fa fa-tag'></i> <span class='product-unit'>" + (repo.don_vi_tinh || 'N/A') + "</span></span>" +
            "<span><i class='fa fa-money-bill-wave'></i> Bán tiền mặt: <span class='product-price'>" + currencyFormat(parseFloat(repo.gia_si)) + "</span></span>" +
            "<span><i class='fa fa-hand-holding-usd'></i> Bán bán nợ: <span class='product-price'>" + currencyFormat(parseFloat(repo.gia_le)) + "</span></span>" +
            "<span><i class='fa fa-boxes'></i> Tồn: <span class='product-stock " + stockClass + "'>" + stockText + "</span></span>" +
            "</div>" +
            "</div>";

        return markup;
    }

    function formatRepoSelection(repo) {
        return repo.ma ? (repo.ma + " - " + repo.ten) : repo.text;
    }

    $('#id_hanghoa').on('select2:select', function (e) {
        var data = e.params.data;
        var mahanghoa = data.ma;
        var get_cart_path = path + "admin/hang-hoa/get-cart/" + mahanghoa;

        // Cập nhật thông tin chi tiết (bao gồm cả HSD từ get-cart)
        $.getJSON(get_cart_path, function (hh) {
            $("#thongtinhanghoa").html(hh.thongtinhanghoa).show();
        });

        // Focus vào ô số lượng sau khi chọn
        $("#so_luong").select().focus();
    });
}