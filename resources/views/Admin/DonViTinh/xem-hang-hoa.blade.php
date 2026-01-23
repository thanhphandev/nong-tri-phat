@if($danhsach && count($danhsach) > 0)
<table id="responsive-datatable" class="table table-bordered table-striped table-bordered table-sm" cellspacing="0" width="100%">
	<thead>
		<tr>
			<th>#</th>
            <th>Mã hàng</th>
			<th>Tên hàng hóa</th>
            <th>Giá vốn</th>
            <th>Giá tiền mặt</th>
            <th>Giá tiền nợ</th>
            <th>Tồn kho</th>
		</tr>
	</thead>
	<tbody>
	@if($danhsach)
		@foreach($danhsach as $key => $ds)
		<tr>
			<td>{{ $key+1 }}</td>
            <td>{{ $ds['ma'] }}</td>
			<td>{{ $ds['ten'] }}</td>
            <td class="text-right">{{ number_format($ds['gia_von'], 0,",",".") }}</td>
            <td class="text-right">{{ number_format($ds['gia_si'], 0,",",".") }}</td>
            <td class="text-right">{{ number_format($ds['gia_le'], 0,",",".") }}</td>
            <td class="text-right">{{ number_format($ds['so_luong_ton'],0,",",".") }}</td>
		</tr>
		@endforeach
	@endif
	</tbody>
</table>
@else 
<div class="alert alert-warning">
	<h3>Không có hàng hóa theo nhóm hàng này</h3>
</div>
@endif