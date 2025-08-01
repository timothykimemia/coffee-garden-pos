
@extends('layouts.app')
@section('title', __('manufacturing::lang.production'))

@section('content')
@include('manufacturing::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('manufacturing::lang.production') </h1>
</section>

<!-- Main content -->
<section class="content">

	{!! Form::open(['url' => action('\Modules\Manufacturing\Http\Controllers\ProductionController@store'), 'method' => 'post', 'id' => 'production_form', 'files' => true ]) !!}
	@component('components.widget', ['class' => 'box-solid'])
		<div class="row">
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('ref_no', __('purchase.ref_no').':') !!} @show_tooltip(__('manufacturing::lang.ref_no_tooltip'))
					{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('transaction_date', __('manufacturing::lang.mfg_date') . ':*') !!}
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</span>
						{!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']); !!}
					</div>
				</div>
			</div>
			
			@if(count($business_locations) == 1)
				@php 
					$default_location = current(array_keys($business_locations->toArray())) 
				@endphp
			@else
				@php $default_location = null; @endphp
			@endif
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('location_id', __('purchase.business_location').':*') !!}
					@show_tooltip(__('tooltip.purchase_location'))
					{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>

			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('variation_id', __('sale.product').':*') !!}
					{!! Form::select('variation_id', $recipe_dropdown, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>
			
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('production_process', 'Process:*') !!}
					{!! Form::select('production_process', [1=>'Production', 2=>'Repackaging'], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>
			
			<div class="col-sm-3">
				
				<div class="form-group">
					{!! Form::label('recipe_quantity', __('lang_v1.quantity').':*') !!}
					<div class="input-group" id="recipe_quantity_input">
						{!! Form::text('quantity', 1, ['class' => 'form-control input_number', 'id' => 'recipe_quantity', 'required', 'data-rule-notEmpty' => 'true', 'data-rule-notEqualToWastedQuantity' => 'true']); !!}
						<span class="input-group-addon" id="unit_html"></span>
					</div>
				</div>
			</div>
			<div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('upload_document', __('purchase.attach_document') . ':') !!}
                    {!! Form::file('documents[]', ['id' => 'upload_document', 'multiple', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                    <p class="help-block">
                    	@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                    	@includeIf('components.document_help_text')
                    </p>
                </div>
            </div>
		</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-solid', 'title' => __('manufacturing::lang.ingredients')])
		<div class="row">
			<div class="col-md-12">
				<div id="enter_ingredients_table" class="text-center">
					<i>@lang('manufacturing::lang.add_ingredients_tooltip')</i>
				</div>
			</div>
		</div>
		<br>
		<div class="row">
			@if(request()->session()->get('business.enable_lot_number') == 1)
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('lot_number', __('lang_v1.lot_number').':*') !!}
						{!! Form::text('lot_number', null, ['class' => 'form-control'], 'required'); !!}
					</div>
				</div>
			@endif
			@if(session('business.enable_product_expiry'))
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('exp_date', __('product.exp_date').':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('exp_date', null, ['class' => 'form-control', 'readonly']); !!}
						</div>
					</div>
				</div>
			@endif
			<!--<div class="col-md-3">
				<div class="form-group">
					{!! Form::label('mfg_dust_units','Dust :') !!} @show_tooltip('Quantity of dust produced')
					<div class="input-group">
						{!! Form::text('mfg_dust_units', 0, ['id'=>'mfg_dust_units', 'class' => 'form-control  input_number']); !!}
						<span class="input-group-addon" id="">Kilograms</span>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					{!! Form::label('mfg_gem_units','Gem :') !!} @show_tooltip('Quantity of gem produced')
					<div class="input-group">
						{!! Form::text('mfg_gem_units', 0, ['id'=>'mfg_gem_units', 'class' => 'form-control  input_number']); !!}
						<span class="input-group-addon" id="">Kilograms</span>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					{!! Form::label('mfg_bran_units','Bran :') !!} @show_tooltip('Quantity of bran produced')
					<div class="input-group">
						{!! Form::text('mfg_bran_units', 0, ['id'=>'mfg_bran_units', 'class' => 'form-control mfg_bran_units input_number']); !!}
						<span class="input-group-addon" id="">Kilograms</span>
					</div>
				</div>
			</div>-->
			<div class="col-md-3">
				<div class="form-group">
					{!! Form::label('mfg_product_units','Quantity of Product :') !!} @show_tooltip('Quantity of product produced')
					<div class="input-group">
						{!! Form::text('mfg_product_units', 0, ['id'=>'mfg_product_units', 'class' => 'form-control mfg_product_units input_number']); !!}
						<span class="input-group-addon" id="">Kilograms</span>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					{!! Form::label('carried_product_units','Previously unpacked product :') !!} @show_tooltip('Quantity of unpacked product produced in previous production.')
					<div class="input-group">
						{!! Form::text('carried_product_units', 0, ['id'=>'carried_product_units', 'class' => 'form-control carried_product_units input_number', 'readonly'=>'readonly']); !!}
						<span class="input-group-addon" id="">Kilograms</span>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					{!! Form::label('mfg_wasted_units', __('manufacturing::lang.waste_units').':') !!} @show_tooltip(__('manufacturing::lang.wastage_tooltip'))
					<div class="input-group">
						{!! Form::text('mfg_wasted_units', 0, ['class' => 'form-control input_number', 'id'=>'wasted_units']); !!}
						<span class="input-group-addon" id="wasted_units_text">Kilograms</span>
					</div>
				</div>
			</div>
		</div>
		<div class="row mt-5">
				<div class="col-md-12 mb-5">
					<div class="">
						<h3>Specify packaging quantities for the produce here.</h3>
					</div>
				</div>
				<!-- unpacked -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('unpacked_quantity','Unpacked Quantity :') !!} @show_tooltip('Enter packaging quantity in kilograms.')
						<div class="input_inline">
							{!! Form::text('unpacked_quantity', 0, ['id'=>'unpacked_quantity','class' => 'form-control input_number', 'readonly'=>'readonly'] ); !!}
							<span class="input-group-addon" id="">Kilograms</span>
							<input name="total_unpacked_cost" type="hidden" style='border: #C0C0C0;' value="">
						</div>
					</div>
				</div>
				<!-- 10kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('tenkg_packaging_quantity','10kg Bags :') !!} @show_tooltip('Enter produced 10kg bags.')
						<div class="input_inline">
							{!! Form::text('tenkg_packaging_quantity', 0, ['class' => 'form-control tenkg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Kilograms</span>
						</div>
						<p><strong>
								Total 10kg Bags:
							</strong>
							<input name="total_ten_kgs" id="total_ten_kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>
					        <input name="total_ten_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<!-- 20kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('twentykg_packaging_quantity','20kg Bags :') !!} @show_tooltip('Enter produced 20kg bags.')
						<div class="input_inline">
							{!! Form::text('twentykg_packaging_quantity', 0, ['class' => 'form-control twentykg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Kilograms</span>
						</div>
						<p><strong>
								Total 20kg Bags:
							</strong>
							<input name="total_twenty_kgs" id="total_twenty_kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>
					        <input name="total_twenty_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<!-- 50kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('fiftykg_packaging_quantity','50kg Bags :') !!} @show_tooltip('Enter produced 50kg bags.')
						<div class="input_inline">
							{!! Form::text('fiftykg_packaging_quantity', 0, ['class' => 'form-control fiftykg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Kilograms</span>
						</div>
						<p><strong>
								Total 50kg Bags:
							</strong>
							<input name="total_fifty_kgs" id="total_fifty_kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>
					        <input name="total_fifty_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<!-- 70kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('seventykg_packaging_quantity','70kg Bags :') !!} @show_tooltip('Enter produced 70kg bags.')
						<div class="input_inline">
							{!! Form::text('seventykg_packaging_quantity', 0, ['class' => 'form-control seventykg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Kilograms</span>
						</div>
						<p><strong>
								Total 70kg Bags:
							</strong>
							<input name="total_seventy_kgs" id="total_seventy_kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>
					        <input name="total_seventy_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
			</div>
		<div class="row mt-5">
				<div class="col-md-12 mb-5">
					<div class="">
						<h3>Packaging units</h3>
					</div>
				</div>
				<!-- unpacked -->
				<div class="col-md-4">
					<div class="form-group">
						{!! Form::label('kg_units','Kilograms Units :') !!} @show_tooltip('select Kilograms units here.')
						<div class="input_inline">
						{!! Form::select('kg_units',$units, 1, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required'] ); !!}
							<span class="input-group-addon" id="">Units</span>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('packet_units','Packet Units :') !!} @show_tooltip('select packet units here.')
						<div class="input_inline">
						{!! Form::select('packet_units',$units, 1, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required'] ); !!}
							<span class="input-group-addon" id="">Units</span>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						{!! Form::label('bag_units','Bag units:') !!} @show_tooltip('select bag units here.')
						<div class="input_inline">
							{!! Form::select('bag_units',$units, 1, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required'] ); !!}
							<span class="input-group-addon" id="">Units</span>
						</div>
					</div>
				</div>
				
				<div class="col-md-4">
					<div class="form-group">
						{!! Form::label('bale_units','Bale units:') !!} @show_tooltip('select bale units here.')
						<div class="input_inline">
							{!! Form::select('bale_units',$units, 2, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required'] ); !!}
							<span class="input-group-addon" id="">Units</span>
						</div>
					</div>
				</div>
								
			</div>
    	<div class="row mt-5">
				<div class="col-md-12 mb-5">
					<div class="text-start">
						<h3>Production costs.</h3>
					</div>
				</div>
				<div class="col-md-3">
							<div class="form-group">
								{!! Form::label('production_cost', __('manufacturing::lang.production_cost').':') !!} @show_tooltip(__('manufacturing::lang.production_cost_tooltip'))
								<div class="input_inline">
									{!! Form::text('production_cost', 0, ['class' => 'form-control input_number']); !!}
									<span>
										{!! Form::select('mfg_production_cost_type',['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage'), 'per_unit' => __('manufacturing::lang.per_unit')], 'fixed', ['class' => 'form-control', 'id' => 'mfg_production_cost_type']); !!}	
									</span>
								</div>
								<p><strong>
								{{__('manufacturing::lang.total_production_cost')}}:
							</strong>
							<span id="total_production_cost" class="display_currency" data-currency_symbol="true">0</span></p>
							</div>
						</div>
				<div class="col-md-3 col-md-offset-9">
						{!! Form::hidden('final_total', 0, ['id' => 'final_total']); !!}
						<strong>
							{{__('manufacturing::lang.total_cost')}}:
						</strong>
						<span id="final_total_text" class="display_currency" data-currency_symbol="true">0</span>
					</div>
				<div class="col-md-3 col-md-offset-9">
					<div class="form-group">
						<br>
						<div class="checkbox">
							<label>
							{!! Form::checkbox('finalize', 1, false, ['class' => 'input-icheck', 'id' => 'finalize']); !!} @lang('manufacturing::lang.finalize')
							</label> @show_tooltip(__('manufacturing::lang.finalize_tooltip'))
						</div>
					</div>
				</div>
		</div>
		
		<div class="row">
			<div class="col-md-12">
				<button type="submit" class="btn btn-primary pull-right">@lang('messages.submit')</button>
			</div>
		</div>
	@endcomponent

{!! Form::close() !!}
</section>
@endsection

@section('javascript')
	@include('manufacturing::production.production_script')
@endsection
