<!DOCTYPE html>
<html>
<head>
	<style type="text/css">
		body {
			color: #000000;
		}
	</style>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<!-- <link rel="stylesheet" href="style.css"> -->
	<title>{{ $heading }}</title>
</head>
<body>
	<div class="row">
		@if(!empty($heading))
			<h3 class="text-center">
				{{ $heading }}
			</h3>
		@endif
	</div>
</div>

<div class="row">
	<div class="col-xs-12">
		<br/>
		@php
			$p_width = 40;
		@endphp
		<table class="table table-responsive table-slim">
			<thead>
				<tr>
					<th width="{{$p_width}}%">{{__('Item')}}</th>
					<th class="text-right" width="15%">{{__('Quantity')}}</th>
				</tr>
			</thead>
			<tbody>
				@forelse($items as $line)
					<tr>
						<td>
                            {{$line['name']}}
                        </td>
						<td class="text-right">{{$line['quantity']}} {{ __('Portion(s)') }} </td>
					</tr>
					
				@empty
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

<div class="row">
	<div class="col-md-12"><hr/></div>
</div>	
</body>
</html>