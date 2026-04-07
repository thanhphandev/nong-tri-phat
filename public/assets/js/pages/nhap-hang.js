function addCart(path) {
    $("#addCart").click(function () {
        var id_nhacungcap = $("#id_nhacungcap").val();
        var id_hanghoa = $("#id_hanghoa").val();
        var so_luong = $("#so_luong").val();
        var ngay_san_xuat = $("#ngay_san_xuat_item").val();
        var so_thang = $("#so_thang_item").val();
        if (id_nhacungcap && id_hanghoa && so_luong && ngay_san_xuat) {
            // Validate NSX not in future
            var parts = ngay_san_xuat.split("/");
            if (parts.length === 3) {
                var nsxDate = new Date(parts[2], parts[1] - 1, parts[0]);
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                if (nsxDate > today) {
                    alert("Ngày sản xuất không được lớn hơn ngày hiện tại!");
                    return false;
                }
            } else {
                alert("Định dạng ngày không hợp lệ (dd/mm/yyyy)");
                return false;
            }

            var foundExactMatch = false;
            var existingItems = $("input[name='id_hanghoa_cart[]'][value='" + id_hanghoa + "']");

            if (existingItems.length > 0) {
                existingItems.each(function () {
                    var row = $(this).closest('.item');
                    var currentRowNsx = row.find('.ngay-san-xuat').val();

                    if (currentRowNsx === ngay_san_xuat) {
                        foundExactMatch = true;
                        var inputSoLuong = row.find('.so-luong');
                        var currentSoLuong = parseFloat(inputSoLuong.val());
                        var newSoLuong = currentSoLuong + parseFloat(so_luong);
                        inputSoLuong.val(newSoLuong);
                        inputSoLuong.trigger('change');
                        return false; // break loop
                    }
                });
            }

            if (!foundExactMatch) {
                var path_get = path + "admin/nhap-hang/get-add-cart?id_nhacungcap=" + id_nhacungcap + "&id_hanghoa=" + id_hanghoa + "&so_luong=" + so_luong + "&ngay_san_xuat=" + ngay_san_xuat + "&so_thang=" + so_thang;
                $.get(path_get, function (hanghoa) {
                    $("#HangHoaList tbody").prepend(hanghoa); delete_cart();
                    tong_thanh_tien();
                    // $("#id_nhacungcap").prop('disabled', true);
                    $("#updateCart").prop("disabled", false);
                    $("#id_nhacungcap_cart").val(id_nhacungcap);
                    change_so_luong(); jQuery(".number").number(true, 0, ',', '.');
                    jQuery(".datepicker").datepicker({ autoclose: !0, orientation: "bottom", todayHighlight: !0, format: "dd/mm/yyyy" });

                    // Reset input fields for next entry
                    $("#id_hanghoa").val(null).trigger('change');
                    $("#so_luong").val(1);
                    $("#thongtinhanghoa").html("Thông tin hàng hóa:");
                });
            } else {
                // Also reset after incrementing existing item
                $("#id_hanghoa").val(null).trigger('change');
                $("#so_luong").val(1);
                $("#thongtinhanghoa").html("Thông tin hàng hóa:");
            }
        } else {
            alert('Vui lòng chọn Nhà cung cấp, Hàng hóa, Số lượng và Ngày sản xuất');
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
        var stockText = repo.so_luong_ton > 0 ? new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 3 }).format(repo.so_luong_ton) : 'Hết hàng';

        var markup = "<div class='product-result'>" +
            "<div class='product-title'>" +
            "<span>" + repo.ten + "</span>" +
            "<span class='product-ma'>" + repo.ma + "</span>" +
            "</div>" +
            "<div class='product-info'>" +
            "<span><i class='fa fa-tag'></i> <span class='product-unit'>" + (repo.don_vi_tinh || 'N/A') + "</span></span>" +
            "<span><i class='fa fa-money-bill-wave'></i> Giá vốn: <span class='product-price'>" + currencyFormat(parseFloat(repo.gia_von || 0)) + "</span></span>" +
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

        $.getJSON(get_cart_path, function (hh) {
            $("#thongtinhanghoa").html(hh.thongtinhanghoa).show();
        });

        $("#so_luong").select().focus();
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

        // Handle unit change
        if ($(this).hasClass('don-vi-nhap')) {
            var unitSelect = $(this);
            var isRetail = unitSelect.val() === 'retail';
            var tyLe = parseFloat(unitSelect.data('ty-le')) || 1;
            var donGiaInput = parent.find(".don-gia");
            var currentDonGia = parseFloat(donGiaInput.val()) || 0;

            if (isRetail && tyLe > 0) {
                // If switched TO retail (smaller unit), divide price by conversion rate
                donGiaInput.val(currentDonGia / tyLe);
            } else if (!isRetail && tyLe > 0) {
                // If switched TO main (larger unit), multiply price by conversion rate
                donGiaInput.val(currentDonGia * tyLe);
            }
        }

        var so_luong = parseFloat(parent.find(".so-luong").val());
        var don_gia = parseFloat(parent.find(".don-gia").val());

        if (isNaN(so_luong) || so_luong <= 0) {
            so_luong = 1; parent.find(".so-luong").val(1)
        }

        var tt = don_gia * so_luong;
        parent.find(".thanh-tien").val(tt);
        parent.find(".thanh-tien-show").html(currencyFormat(tt));

        if ($(this).hasClass('so-thang') || $(this).hasClass('ngay-san-xuat')) {
            var so_thang = parseInt(parent.find(".so-thang").val());
            var ngay_san_xuat = parent.find(".ngay-san-xuat").val();

            if (ngay_san_xuat) {
                var parts = ngay_san_xuat.split('/');
                if (parts.length === 3) {
                    var nsxDate = new Date(parts[2], parts[1] - 1, parts[0]);
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (nsxDate > today) {
                        alert("Ngày sản xuất không được lớn hơn ngày hiện tại!");
                        parent.find(".ngay-san-xuat").val("");
                        parent.find(".ngay-het-han").val("");
                        return;
                    }
                }
            }

            if (!isNaN(so_thang) && so_thang > 0 && ngay_san_xuat) {
                var parts = ngay_san_xuat.split('/');
                if (parts.length === 3) {
                    var startDate = new Date(parts[2], parts[1] - 1, parts[0]); // yyyy, mm-1, dd
                    var targetDate = new Date(startDate.setMonth(startDate.getMonth() + so_thang));
                    var dd = targetDate.getDate();
                    var mm = targetDate.getMonth() + 1;
                    var yyyy = targetDate.getFullYear();
                    if (dd < 10) { dd = '0' + dd }
                    if (mm < 10) { mm = '0' + mm }
                    var formattedDate = dd + '/' + mm + '/' + yyyy;
                    parent.find(".ngay-het-han").val(formattedDate);
                    parent.find(".ngay-het-han").datepicker('update', formattedDate);
                }
            } else if (!isNaN(so_thang) && so_thang > 0 && !ngay_san_xuat) {
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

    // Sync id_nhacungcap with id_nhacungcap_cart
    $("#id_nhacungcap").change(function () {
        $("#id_nhacungcap_cart").val($(this).val());
    });
}
