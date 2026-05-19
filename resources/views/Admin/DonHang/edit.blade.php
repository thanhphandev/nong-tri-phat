@extends('Admin.layout')
@section('title', 'Chi tiết đơn hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <style>
        .cancelled-banner {
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            color: #fff;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(255, 68, 68, 0.3);
        }
        .cancelled-banner .badge-cancelled {
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-size: 14px;
            padding: 6px 14px;
            border-radius: 20px;
        }
        .cancelled-order-row {
            opacity: 0.6;
            text-decoration: line-through;
        }
        .huy-don-impact-table td, .huy-don-impact-table th {
            padding: 8px 12px !important;
        }
    </style>
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
            <h3 class="m-t-0">
                <a href="{{ url()->previous() }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-reply-all"></i> Trở về
                </a> 
                Chi tiết Đơn hàng: {{ $dh['ma_don_hang'] }}
            </h3>
            <hr>

            {{-- BANNER ĐƠN ĐÃ HỦY --}}
            @if($dh['tinh_trang'] == 3)
            <div class="cancelled-banner">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="badge-cancelled"><i class="fas fa-ban mr-1"></i> ĐƠN HÀNG ĐÃ HỦY</span>
                    </div>
                    <div style="font-size: 13px; opacity: 0.9;">
                        @if(isset($dh['huy_don']))
                            Hủy bởi: <strong>{{ $dh['huy_don']['user_huy'] ?? 'N/A' }}</strong>
                            — {{ \App\Http\Controllers\ObjectController::getDate($dh['huy_don']['ngay_huy'] ?? '', 'd/m/Y H:i') }}
                        @endif
                    </div>
                </div>
                @if(isset($dh['huy_don']))
                <div class="row mt-2" style="font-size: 13px;">
                    <div class="col-md-4">
                        <i class="fas fa-exclamation-circle mr-1"></i> <strong>Lý do:</strong> {{ $dh['huy_don']['ly_do'] ?? 'Không rõ' }}
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-money-bill-wave mr-1"></i> <strong>Đã TT trước hủy:</strong> {{ number_format($dh['huy_don']['da_thanh_toan_truoc_huy'] ?? 0, 0, ',', '.') }}đ
                    </div>
                    <div class="col-md-4">
                        @if(($dh['huy_don']['so_du_tao_ra'] ?? 0) > 0)
                            <i class="fas fa-wallet mr-1"></i> <strong>Số dư tạo ra:</strong> {{ number_format($dh['huy_don']['so_du_tao_ra'], 0, ',', '.') }}đ
                        @endif
                    </div>
                </div>
                @if(!empty($dh['huy_don']['ghi_chu']))
                <div class="mt-1" style="font-size: 12px; opacity: 0.85;">
                    <i class="fas fa-comment mr-1"></i> {{ $dh['huy_don']['ghi_chu'] }}
                </div>
                @endif
                @endif
            </div>
            @endif

            @if(Session::get('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-1"></i> {{ Session::get('error') }}
            </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <p><strong>Khách hàng:</strong> {{ $dh['ho_ten'] }}</p>
                    <p><strong>Điện thoại:</strong> {{ $dh['dien_thoai'] }}</p>
                    <p><strong>Địa chỉ:</strong> {{ $dh['dia_chi'] }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>Ngày bán:</strong> {{ \App\Http\Controllers\ObjectController::getDate($dh['ngay_ban'], "d/m/Y H:i") }}</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th>Mã hàng</th>
                            <th>Tên hàng</th>
                            <th class="text-center">ĐVT</th>
                            <th class="text-right">Số lượng</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Giảm giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dh['hanghoa'] as $k => $hh)
                        <tr>
                            <td class="text-center">{{ $k+1 }}</td>
                            <td>{{ $hh['ma'] ?? '' }}</td>
                            <td>
                                {{ $hh['ten'] }}
                                @if(!empty($hh['don_vi_le_info']))
                                    <br><small class="text-muted">{{ $hh['don_vi_le_info'] }}</small>
                                @endif
                                @if(!empty($hh['hang_chuong_trinh']))
                                    <br><small class="text-info">Hàng chương trình</small>
                                @endif
                                <div class="mt-2 consignment-status p-2 border rounded bg-white shadow-sm" style="font-size: 11px;">
                                    <div class="row no-gutters text-center">
                                        <div class="col-4 border-right">
                                            <div class="text-muted mb-0">Tổng mua</div>
                                            <div class="font-weight-bold text-dark">{{ number_format($hh['so_luong'], 2, ',', '.') }}</div>
                                        </div>
                                        <div class="col-4 border-right">
                                            <div class="text-success mb-0">Đã lấy</div>
                                            <div class="font-weight-bold">{{ number_format($hh['sl_da_lay'] ?? 0, 2, ',', '.') }}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-warning mb-0">Còn gửi</div>
                                            <div class="font-weight-bold sl-gui-kho-display">{{ number_format($hh['sl_gui_kho'] ?? 0, 2, ',', '.') }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mt-2 pt-2 border-top">
                                        @if(isset($hh['sl_gui_kho']) && $hh['sl_gui_kho'] > 0)
                                            <button type="button" class="btn btn-xs btn-primary mr-1 btn-lay-hang" 
                                                data-index="{{ $k }}" 
                                                data-ten="{{ $hh['ten'] }}" 
                                                data-con-lai="{{ $hh['sl_gui_kho'] }}">
                                                <i class="fas fa-truck-loading mr-1"></i> Lấy hàng
                                            </button>
                                        @endif
                                        @if(isset($hh['lich_su_lay_hang']) && count($hh['lich_su_lay_hang']) > 0)
                                            <button type="button" class="btn btn-xs btn-info btn-view-history mr-auto" 
                                                data-history='@json($hh['lich_su_lay_hang'])'
                                                title="Xem lịch sử lấy hàng">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        @endif
                                        
                                        <div class="custom-control custom-switch custom-switch-sm">
                                            <input type="checkbox" class="custom-control-input edit-gui-kho-checkbox" id="guiKhoSwitch{{ $k }}" data-id="{{ $dh['_id'] }}" data-index="{{ $k }}" data-so-luong="{{ $hh['so_luong'] }}" {{ (isset($hh['gui_kho']) && $hh['gui_kho'] == 1) ? 'checked' : '' }}>
                                            <label class="custom-control-label text-muted" for="guiKhoSwitch{{ $k }}" style="font-size: 10px;">Gửi kho</label>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">{{ $hh['don_vi_tinh'] }}</td>
                            <td class="text-right">{{ number_format($hh['so_luong'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($hh['don_gia'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($hh['chiet_khau'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($hh['thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">TỔNG THÀNH TIỀN:</td>
                            <td class="text-right font-weight-bold text-danger">{{ number_format($dh['tong_thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">
                                <a href="#paymentHistory" data-toggle="collapse" class="text-success" aria-expanded="false" aria-controls="paymentHistory" title="Bấm để xem chi tiết lịch sử thanh toán">
                                    ĐÃ THANH TOÁN <i class="fa fa-caret-down"></i>:
                                </a>
                            </td>
                            <td class="text-right font-weight-bold text-success">{{ number_format($dh->da_thanh_toan ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        @if(($dh->gia_tri_tra_hang ?? 0) > 0)
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold text-warning">GIÁ TRỊ TRẢ HÀNG KHÁCH:</td>
                            <td class="text-right font-weight-bold text-warning">{{ number_format($dh->gia_tri_tra_hang, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr class="collapse" id="paymentHistory">
                            <td colspan="8" class="p-0">
                                <div class="px-3 py-2" style="background-color: #f1f5f7;">
                                    <h5 class="font-14 mt-1 mb-2 text-info"><i class="fas fa-history"></i> LỊCH SỬ THANH TOÁN</h5>
                                    <table class="table table-sm table-bordered bg-white mb-2">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="text-center" width="20%">Thời gian</th>
                                                <th class="text-right" width="20%">Số tiền</th>
                                                <th>Ghi chú</th>
                                                <th width="20%">Người xử lý</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($lich_su_thanh_toan) && count($lich_su_thanh_toan) > 0)
                                                @foreach($lich_su_thanh_toan as $ls)
                                                    <tr>
                                                        <td class="text-center">{{ \App\Http\Controllers\ObjectController::getDate($ls['ngay_gio'] ?? '', "d/m/Y H:i") }}</td>
                                                        @if(isset($ls['id_trahangkhach']))
                                                            <td class="text-right text-warning font-weight-bold">-{{ number_format($ls['tong_thanh_tien'], 0, ',', '.') }}</td>
                                                        @else
                                                            <td class="text-right text-success font-weight-bold">+{{ number_format($ls['tong_thanh_tien'], 0, ',', '.') }}</td>
                                                        @endif
                                                        <td>{{ $ls['ghi_chu'] ?? '' }}</td>
                                                        <td>
                                                            @php
                                                                if(isset($ls['id_user']) && $ls['id_user']){
                                                                    $user = \App\Models\User::find($ls['id_user']);
                                                                    echo $user ? $user['fullname'] : '';
                                                                }
                                                            @endphp
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr><td colspan="4" class="text-center text-muted font-italic">Chưa có lịch sử thanh toán</td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">CÒN LẠI:</td>
                            <td class="text-right font-weight-bold {{ ($dh->con_no ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($dh->con_no ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="order-notes p-2 bg-light border rounded" style="font-size: 13px; min-width: 300px;">
                    <i class="fas fa-comment-alt text-primary mr-1"></i> <strong>Ghi chú đơn hàng:</strong>
                    <span class="text-dark">{{ $dh['ghi_chu'] ?: 'Không có ghi chú' }}</span>
                </div>
                <div>
                    @if($dh['tinh_trang'] != 3)
                        @if(($dh->con_no ?? 0) > 0)
                            <button class="btn btn-success mr-2 btn-tra-no" data-id="{{ $dh['_id'] }}" data-ma="{{ $dh['ma_don_hang'] }}" data-khach="{{ $dh['ho_ten'] }}" data-conno="{{ $dh->con_no ?? 0 }}" data-toggle="modal" data-target="#modalTraNo">
                                <i class="fas fa-money-bill-wave"></i> Thu nợ
                            </button>
                        @endif
                        <a href="{{ env('APP_URL') }}admin/don-hang/in-phieu-giao-hang/{{ $dh['_id'] }}" target="_blank" class="btn btn-warning mr-2"><i class="fa fa-print"></i> In Phiếu</a>
                        <button type="button" class="btn btn-danger btn-huy-don" data-id="{{ $dh['_id'] }}">
                            <i class="fas fa-ban"></i> Hủy đơn hàng
                        </button>
                    @else
                        <a href="{{ env('APP_URL') }}admin/don-hang/in-phieu-giao-hang/{{ $dh['_id'] }}" target="_blank" class="btn btn-secondary"><i class="fa fa-print"></i> In Phiếu (Đã hủy)</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Lấy Hàng --}}
<div class="modal fade" id="modalLayHang" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ env('APP_URL') }}admin/don-hang/da-lay-hang/{{ $dh['_id'] }}" method="GET">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="fas fa-warehouse mr-1"></i> Lấy hàng gửi kho</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="index" id="lay_hang_index">
                    <div class="form-group">
                        <label>Sản phẩm:</label>
                        <input type="text" class="form-control" id="lay_hang_ten" readonly>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Số lượng còn gửi:</label>
                                <input type="text" class="form-control" id="lay_hang_con_lai" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Số lượng lấy <span class="text-danger">*</span>:</label>
                                <input type="number" name="sl_lay" id="lay_hang_sl" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Ghi chú:</label>
                        <textarea name="ghi_chu" class="form-control" rows="2" placeholder="VD: Nhận hàng gửi kho ngày mua đơn hàng..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Xác nhận lấy hàng</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Lịch Sử --}}
<div class="modal fade" id="modalHistoryGuiKho" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white"><i class="fas fa-history mr-1"></i> Lịch sử lấy hàng</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center">Ngày lấy</th>
                            <th class="text-right">SL lấy</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody id="historyGuiKhoBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTraNo" tabindex="-1" role="dialog" aria-labelledby="modalTraNoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ env('APP_URL') }}admin/don-hang/tra-no" method="POST" id="TraNoForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTraNoLabel">Khách trả nợ đơn hàng - <span id="tra_no_ma"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_donhang" id="tra_no_id_donhang">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    
                    <div class="form-group">
                        <label>Khách hàng</label>
                        <input type="text" class="form-control" id="tra_no_khach" readonly value="" style="font-weight: bold;">
                    </div>

                    <div class="form-group">
                        <label>Số nợ hiện tại</label>
                        <input type="text" class="form-control" id="tra_no_con_no" readonly value="" style="font-weight: bold; color: #d9534f;">
                    </div>

                    <div class="form-group">
                        <label>Số tiền khách trả <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" id="so_tien_tra" class="form-control money" required placeholder="Nhập số tiền khách trả" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu" id="ghi_chu_tra_no" class="form-control" rows="3" placeholder="Ghi chú thanh toán"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                     <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Xác nhận thanh toán</button>
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Hủy Đơn Hàng --}}
<div class="modal fade" id="modalHuyDon" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ env('APP_URL') }}admin/don-hang/huy-don" method="POST" id="HuyDonForm">
                @csrf
                <div class="modal-header" style="background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%); color: #fff;">
                    <h5 class="modal-title text-white"><i class="fas fa-ban mr-1"></i> Hủy đơn hàng — <span id="huy_don_ma"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_donhang" id="huy_don_id">

                    {{-- Loading state --}}
                    <div id="huyDonLoading" class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2 text-muted">Đang tải thông tin đơn hàng...</p>
                    </div>

                    {{-- Error state --}}
                    <div id="huyDonError" class="alert alert-danger" style="display:none;"></div>

                    {{-- Content --}}
                    <div id="huyDonContent" style="display:none;">
                        {{-- Cảnh báo --}}
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Lưu ý:</strong> Hành động này không thể hoàn tác. Hệ thống sẽ tự động hoàn kho và xử lý công nợ.
                        </div>

                        {{-- Thông tin đơn hàng --}}
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fas fa-file-invoice mr-1"></i> Thông tin đơn hàng</strong>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0 huy-don-impact-table">
                                    <tr>
                                        <td class="font-weight-bold" width="40%">Khách hàng</td>
                                        <td id="huy_don_khach"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Tổng đơn hàng</td>
                                        <td id="huy_don_tong" class="text-primary font-weight-bold"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Đã thanh toán</td>
                                        <td id="huy_don_da_tt" class="text-success font-weight-bold"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- Thông báo số dư --}}
                        <div id="huy_don_credit_info" class="alert alert-info mb-3" style="display:none;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Đơn hàng này đã được thanh toán <strong id="huy_don_credit_amount"></strong>.
                            <br>Khi hủy đơn, số tiền này sẽ được chuyển thành <strong>số dư của khách hàng</strong> (công nợ âm).
                        </div>

                        {{-- Danh sách hàng hóa sẽ hoàn kho --}}
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fas fa-warehouse mr-1"></i> Hàng hóa sẽ được hoàn kho</strong>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-bordered mb-0 huy-don-impact-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-right">SL bán</th>
                                            <th class="text-right">SL hoàn kho</th>
                                        </tr>
                                    </thead>
                                    <tbody id="huy_don_hanghoa_list"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Lý do hủy --}}
                        <div class="form-group">
                            <label class="font-weight-bold">Lý do hủy <span class="text-danger">*</span></label>
                            <select name="ly_do" id="huy_don_ly_do" class="form-control" required>
                                <option value="">-- Chọn lý do --</option>
                                <option value="Nhập sai khách hàng">Nhập sai khách hàng</option>
                                <option value="Nhập sai hàng hóa">Nhập sai hàng hóa</option>
                                <option value="Nhập sai số lượng">Nhập sai số lượng</option>
                                <option value="Nhập sai giá">Nhập sai giá</option>
                                <option value="Khách hủy đơn">Khách hủy đơn</option>
                                <option value="Đơn trùng lặp">Đơn trùng lặp</option>
                                <option value="Lý do khác">Lý do khác</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Ghi chú thêm</label>
                            <textarea name="ghi_chu_huy" class="form-control" rows="2" placeholder="Ghi chú chi tiết về lý do hủy..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="huyDonFooter" style="display:none;">
                    <button type="submit" class="btn btn-danger" id="btnConfirmHuyDon">
                        <i class="fas fa-ban mr-1"></i> Xác nhận HỦY ĐƠN
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
<script>
    $(document).ready(function(){
        // Handle payment modal
        $(".btn-tra-no").click(function(){
            var _this = $(this);
            var id = _this.data("id");
            var ma = _this.data("ma");
            var khach = _this.data("khach");
            var conno = _this.data("conno");
            
            $("#tra_no_id_donhang").val(id);
            $("#tra_no_ma").text(ma);
            $("#tra_no_khach").val(khach);
            $("#tra_no_con_no").val(new Intl.NumberFormat('vi-VN').format(conno));
            
            // Reset và gán giá trị mặc định
            $("#so_tien_tra").val(new Intl.NumberFormat('vi-VN').format(conno));
            $("#so_tien_tra").attr('data-max', conno);
            $("#ghi_chu_tra_no").val('Khách trả nợ đơn ' + ma);
            
            $("#modalTraNo").modal("show");
        });

        // Định dạng tiền tệ khi nhập (Giống trang Nhập hàng)
        $('input.money').on('keyup', function() {
            var val = $(this).val().replace(/[^0-9]/g, '');
            if(val !== '') {
                val = new Intl.NumberFormat('vi-VN').format(parseInt(val));
                $(this).val(val);
            }
        });

        // Kiểm tra trước khi submit
        $("#TraNoForm").submit(function(e){
            var strSotien = $("#so_tien_tra").val().replace(/\./g, '');
            var soTien = parseFloat(strSotien);
            var maxAmount = parseFloat($("#so_tien_tra").attr('data-max'));
            
            if(isNaN(soTien) || soTien <= 0){
                alert("Vui lòng nhập số tiền hợp lệ!");
                return false;
            }
            
            if(soTien > maxAmount){
                if(!confirm("Số tiền trả đang lớn hơn số nợ. Bạn vẫn muốn tiếp tục?")) {
                    return false;
                }
            }
            
            return confirm('Xác nhận khách hàng đã thanh toán ' + $("#so_tien_tra").val() + ' VND?');
        });
        
        @if(Session::get('msg') && Session::get('msg'))
            $.toast({
                heading:"Thông báo",
                text:"{{ Session::get('msg') }}",
                loaderBg:"#3b98b5",icon:"info", hideAfter:3e3,stack:1,position:"top-right"
            });
        @endif
        $('.edit-gui-kho-checkbox').change(function(){
            var _this = $(this);
            var id = _this.data('id');
            var index = _this.data('index');
            var gui_kho = _this.is(':checked') ? 1 : 0;
            
            var sl_gui_kho = 0;
            if (gui_kho == 1) {
                var max_sl = parseFloat(_this.data('so-luong'));
                var current_val = _this.closest('.consignment-status').find('.sl-gui-kho-display').text().replace(/\./g, '').replace(',', '.');
                var prompt_val = prompt("Nhập số lượng gửi kho (Tối đa " + max_sl + "):", current_val);
                
                if (prompt_val === null) {
                    _this.prop('checked', false);
                    return;
                }
                
                sl_gui_kho = parseFloat(prompt_val);
                if (isNaN(sl_gui_kho) || sl_gui_kho <= 0) {
                    alert("Số lượng không hợp lệ!");
                    _this.prop('checked', false);
                    return;
                }

                if (sl_gui_kho > max_sl) {
                    alert("Số lượng gửi kho (" + sl_gui_kho + ") không được lớn hơn tổng số lượng mua (" + max_sl + ")!");
                    _this.prop('checked', false);
                    return;
                }
            }

            $.ajax({
                url: '{{ env('APP_URL') }}admin/don-hang/update-gui-kho',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_donhang: id,
                    index: index,
                    gui_kho: gui_kho,
                    sl_gui_kho: sl_gui_kho
                },
                success: function(res) {
                    if(res.error) {
                        alert(res.msg);
                        _this.prop('checked', !gui_kho);
                    } else {
                        location.reload(); // Reload to reflect changes easily
                    }
                },
                error: function() {
                    alert('Lỗi kết nối máy chủ!');
                    _this.prop('checked', !gui_kho);
                }
            });
        });

        $('#modalLayHang form').submit(function(e){
            var max = parseFloat($('#lay_hang_sl').attr('max'));
            var val = parseFloat($('#lay_hang_sl').val());
            if(val > max){
                alert('Số lượng lấy ('+val+') không được lớn hơn số lượng còn gửi ('+max+')');
                return false;
            }
            return confirm('Xác nhận lấy hàng?');
        });

        $('.btn-lay-hang').click(function(){
            var btn = $(this);
            $('#lay_hang_index').val(btn.data('index'));
            $('#lay_hang_ten').val(btn.data('ten'));
            $('#lay_hang_con_lai').val(btn.data('con-lai'));
            $('#lay_hang_sl').val(btn.data('con-lai')).attr('max', btn.data('con-lai'));
            
            var ngay_mua = '{{ \App\Http\Controllers\ObjectController::getDate($dh['ngay_ban'], "d/m/Y") }}';
            $('#modalLayHang textarea').val('Nhận hàng gửi kho từ đơn ngày ' + ngay_mua);
            
            $('#modalLayHang').modal('show');
        });

        $('.btn-view-history').click(function(){
            var history = $(this).data('history');
            var html = '';
            
            if (history && Array.isArray(history)) {
                history.forEach(function(item){
                    var dateStr = 'N/A';
                    try {
                        if (item.ngay_lay) {
                            var timestamp = 0;
                            if (typeof item.ngay_lay === 'object') {
                                if (item.ngay_lay.$date && item.ngay_lay.$date.$numberLong) {
                                    timestamp = item.ngay_lay.$date.$numberLong * 1;
                                } else if (item.ngay_lay.timestamp) { // custom fallback
                                    timestamp = item.ngay_lay.timestamp * 1000;
                                }
                            } else {
                                // Assume it's a string
                                timestamp = Date.parse(item.ngay_lay);
                            }
                            
                            if (timestamp > 0) {
                                var date = new Date(timestamp);
                                dateStr = date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
                            } else {
                                dateStr = item.ngay_lay; // Use raw if parsing fails
                            }
                        }
                    } catch(e) { console.error(e); }

                    html += '<tr>' +
                        '<td class="text-center">' + dateStr + '</td>' +
                        '<td class="text-right font-weight-bold text-primary">' + item.so_luong + '</td>' +
                        '<td>' + (item.ghi_chu || '<em class="text-muted">Không có ghi chú</em>') + '</td>' +
                        '</tr>';
                });
            } else {
                html = '<tr><td colspan="3" class="text-center text-muted">Chưa có lịch sử</td></tr>';
            }
            
            $('#historyGuiKhoBody').html(html);
            $('#modalHistoryGuiKho').modal('show');
        });

        // ==================== HỦY ĐƠN HÀNG ====================
        $('.btn-huy-don').click(function(){
            var id = $(this).data('id');
            $('#huy_don_id').val(id);
            $('#huyDonLoading').show();
            $('#huyDonContent').hide();
            $('#huyDonFooter').hide();
            $('#huyDonError').hide();
            $('#modalHuyDon').modal('show');

            // Load thông tin đơn hàng qua AJAX
            $.get('{{ env('APP_URL') }}admin/don-hang/get-huy-don-info/' + id, function(res){
                $('#huyDonLoading').hide();

                if(res.error){
                    $('#huyDonError').html('<i class="fas fa-exclamation-triangle mr-1"></i> ' + res.msg).show();
                    return;
                }

                // Hiển thị thông tin
                $('#huy_don_ma').text(res.ma_don_hang);
                $('#huy_don_khach').text(res.ho_ten);
                $('#huy_don_tong').text(formatMoney(res.tong_thanh_tien) + 'đ');
                $('#huy_don_da_tt').text(formatMoney(res.da_thanh_toan) + 'đ');

                // Hiển thị thông báo credit nếu đã thanh toán
                if(res.da_thanh_toan > 0){
                    $('#huy_don_credit_amount').text(formatMoney(res.da_thanh_toan) + 'đ');
                    $('#huy_don_credit_info').show();
                } else {
                    $('#huy_don_credit_info').hide();
                }

                // Danh sách hàng hóa
                var hhHtml = '';
                if(res.hang_hoa_hoan && res.hang_hoa_hoan.length > 0){
                    res.hang_hoa_hoan.forEach(function(hh){
                        hhHtml += '<tr>' +
                            '<td>' + (hh.ma ? '<small class="text-muted">' + hh.ma + '</small><br>' : '') + hh.ten + '</td>' +
                            '<td class="text-right">' + hh.so_luong + '</td>' +
                            '<td class="text-right text-success font-weight-bold">+' + hh.so_luong_hoan_kho + '</td>' +
                            '</tr>';
                    });
                }
                $('#huy_don_hanghoa_list').html(hhHtml);

                $('#huyDonContent').show();
                $('#huyDonFooter').show();
            }).fail(function(){
                $('#huyDonLoading').hide();
                $('#huyDonError').html('<i class="fas fa-exclamation-triangle mr-1"></i> Lỗi kết nối máy chủ.').show();
            });
        });

        // Submit form hủy đơn
        $('#HuyDonForm').submit(function(e){
            var lyDo = $('#huy_don_ly_do').val();
            if(!lyDo){
                alert('Vui lòng chọn lý do hủy đơn!');
                return false;
            }
            if(!confirm('⚠️ BẠN CHẮC CHẮN MUỐN HỦY ĐƠN HÀNG NÀY?\n\nHành động này sẽ:\n- Hoàn toàn bộ tồn kho\n- Điều chỉnh công nợ\n- Không thể hoàn tác\n\nBấm OK để xác nhận.')){
                return false;
            }
            $('#btnConfirmHuyDon').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
            return true;
        });

        function formatMoney(num){
            return new Intl.NumberFormat('vi-VN').format(num);
        }
    });
</script>
@endsection
