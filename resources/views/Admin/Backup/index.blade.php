@extends('Admin.layout')
@section('title', 'Backup Dữ liệu')
@section('css')
<link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" />
<style>
    .backup-card { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: all 0.2s; }
    .backup-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.12); transform: translateY(-2px); }
    .backup-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .backup-name { font-weight: 600; font-size: 16px; color: #2c3e50; }
    .backup-name i { color: #3498db; margin-right: 8px; }
    .backup-meta { display: flex; gap: 20px; font-size: 13px; color: #7f8c8d; }
    .backup-meta span { display: flex; align-items: center; gap: 5px; }
    .backup-actions { display: flex; gap: 8px; }
    .btn-action { padding: 6px 12px; border-radius: 6px; font-size: 13px; }
    .empty-state { text-align: center; padding: 60px 20px; color: #95a5a6; }
    .empty-state i { font-size: 60px; margin-bottom: 20px; opacity: 0.5; }
    .info-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    .info-box h4 { margin: 0 0 10px; font-weight: 600; }
    .info-box p { margin: 0; opacity: 0.9; font-size: 14px; }
</style>
@endsection
@section('body')
<div class="card-box">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="m-0">
                    <a href="{{ env('APP_URL') }}admin" class="btn btn-primary btn-sm mr-2"><i class="fa fa-reply-all"></i></a>
                    <i class="fas fa-database text-primary"></i> Backup Dữ liệu
                </h3>
                <form action="{{ env('APP_URL') }}admin/backup/create" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="this.disabled=true; this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Đang backup...'; this.form.submit();">
                        <i class="fas fa-plus-circle"></i> Tạo Backup Mới
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Hướng dẫn</h4>
                <p>Backup lưu toàn bộ dữ liệu MongoDB vào file ZIP. Sử dụng <strong>"Khôi phục"</strong> để hoàn tác dữ liệu, <strong>"Tải về"</strong> để lưu trên máy. Hệ thống tự động giữ 10 bản backup gần nhất.</p>
            </div>
        </div>
    </div>

    {{-- Upload Form --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card" style="border: 2px dashed #ddd; border-radius: 10px;">
                <div class="card-body">
                    <form action="{{ env('APP_URL') }}admin/backup/upload" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-3">
                        @csrf
                        <div class="flex-grow-1">
                            <label class="mb-1" style="font-weight: 500;"><i class="fas fa-upload text-primary"></i> Tải lên Backup từ máy tính</label>
                            <input type="file" name="backup_file" accept=".zip" class="form-control" required style="max-width: 400px;">
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="this.disabled=true; this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Đang tải...'; this.form.submit();">
                            <i class="fas fa-cloud-upload-alt"></i> Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(count($backups) > 0)
        @foreach($backups as $backup)
        <div class="backup-card">
            <div class="backup-header">
                <div class="backup-name">
                    <i class="fas fa-archive"></i>{{ $backup['name'] }}
                </div>
                <div class="backup-actions">
                    <a href="{{ env('APP_URL') }}admin/backup/download/{{ $backup['name'] }}" class="btn btn-info btn-action">
                        <i class="fas fa-download"></i> Tải về
                    </a>
                    <form action="{{ env('APP_URL') }}admin/backup/restore/{{ $backup['name'] }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-action" onclick="return confirm('⚠️ CẢNH BÁO: Thao tác này sẽ GHI ĐÈ toàn bộ dữ liệu hiện tại!\n\nBạn chắc chắn muốn khôi phục từ backup này?');">
                            <i class="fas fa-undo"></i> Khôi phục
                        </button>
                    </form>
                    <form action="{{ env('APP_URL') }}admin/backup/delete/{{ $backup['name'] }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-action" onclick="return confirm('Xóa backup này?');">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="backup-meta">
                @if($backup['created_at'])
                <span><i class="fas fa-clock"></i> {{ $backup['created_at'] }}</span>
                @endif
                <span><i class="fas fa-hdd"></i> {{ $backup['size'] }}</span>
                <span><i class="fas fa-file-alt"></i> {{ number_format($backup['total_documents']) }} documents</span>
                <span><i class="fas fa-layer-group"></i> {{ $backup['collections_count'] }} collections</span>
                @if($backup['has_zip'])
                <span class="badge badge-success"><i class="fas fa-check"></i> ZIP</span>
                @endif
            </div>
        </div>
        @endforeach
    @else
        <div class="empty-state">
            <i class="fas fa-database"></i>
            <h4>Chưa có backup nào</h4>
            <p>Nhấn nút "Tạo Backup Mới" để bắt đầu bảo vệ dữ liệu của bạn.</p>
        </div>
    @endif
</div>
@endsection
@section('js')
<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
<script>
@if(Session::get('msg'))
$.toast({
    heading: "Thông báo",
    text: "{{ Session::get('msg') }}",
    loaderBg: "{{ Session::get('msg_type') == 'error' ? '#e74c3c' : '#27ae60' }}",
    icon: "{{ Session::get('msg_type') == 'error' ? 'error' : 'success' }}",
    hideAfter: 5000, stack: 1, position: "top-right"
});
@endif
</script>
@endsection
