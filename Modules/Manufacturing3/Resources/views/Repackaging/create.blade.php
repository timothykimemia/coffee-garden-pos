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
			<div class="col-md-3">
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
			</div>
			<div class="col-md-3">
				<div class="form-group">
					{!! Form::label('mfg_fortified_units','Fortified unga :') !!} @show_tooltip('Quantity of fortified unga produced')
					<div class="input-group">
						{!! Form::text('mfg_fortified_units', 0, ['id'=>'mfg_fortified_units', 'class' => 'form-control mfg_fortified_units input_number']); !!}
						<span class="input-group-addon" id="">Kilograms</span>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					{!! Form::label('carried_fortified_units','Previously unpacked unga :') !!} @show_tooltip('Quantity of unpacked unga produced in previous production.')
					<div class="input-group">
						{!! Form::text('carried_fortified_units', 0, ['id'=>'carried_fortified_units', 'class' => 'form-control carried_fortified_units input_number', 'readonly'=>'readonly']); !!}
						<span class="input-group-addon" id="">Kilograms</span>
					</div>
				</div>
			</div>
		</div>
		<div class="row mt-5">
				<div class="col-md-12 mb-5">
					<div class="">
						<h3>Specify packaging quantities for the fortified unga here.</h3>
					</div>
				</div>
				<!-- unpacked -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('unpacked_quantity','Unpacked Quantity :') !!} @show_tooltip('Enter packaging quantity in kilograms to package the fortified unga in packets.')
						<div class="input_inline">
							{!! Form::text('unpacked_quantity', 0, ['class' => 'form-control input_number', 'readonly'=>'readonly'] ); !!}
							<span class="input-group-addon" id="">Kilograms</span>
							<input name="total_unpacked_cost" type="hidden" style='border: #C0C0C0;' value="">
						</div>
					</div>
				</div>
				<!-- 1/2kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('halfkg_packaging_quantity','1/2kg Bales :') !!} @show_tooltip('Enter produced 1/2kg bales.')
						<div class="input_inline">
							{!! Form::text('halfkg_packaging_quantity', 0, ['class' => 'form-control halfkg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Bales</span>
						</div>
						<p><strong>
								Total 1/2kg Bales:
							</strong>
							<input name="total_half_kgs" id="total_half_kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>
					        <input name="total_half_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('halfkg_packets_quantity','1/2kg Packets :') !!} @show_tooltip('Enter produced 1/2kg packets.')
						<div class="input_inline">
							{!! Form::text('halfkg_packets_quantity', 0, ['class' => 'form-control halfkg_packets_quantity input_number']); !!}
							<span class="input-group-addon" id="">Packets</span>
						</div>
						<p><strong>
								Total 1/2kg Packets:
							</strong>
							<input name="total_halfkg_packets" id="total_half_kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>
					        <input name="total_half_packets_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<!-- 1kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('1kg_packaging_quantity','1kg Bales :') !!} @show_tooltip('Enter produced 1kg bales.')
						<div class="input_inline">
							{!! Form::text('1kg_packaging_quantity', 0, ['class' => 'form-control 1kg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Bales</span>
						</div>
						<p><strong>
						Total 1kg Bales:
							</strong>
							<input name="total_1kgs" id="total_1kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>						
					        <input name="total_1kg_cost" type="hidden" style='border: #C0C0C0;' value="">																		
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('1kg_packets_quantity','1kg Packets :') !!} @show_tooltip('Enter produced 1kg packets.')
						<div class="input_inline">
							{!! Form::text('1kg_packets_quantity', 0, ['class' => 'form-control 1kg_packets_quantity input_number']); !!}
							<span class="input-group-addon" id="">Packets</span>
						</div>
						<p><strong>
						Total 1kg Packets:
							</strong>
							<input name="total_1kg_packets" id="total_1kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>						
					        <input name="total_1kg_packets_cost" type="hidden" style='border: #C0C0C0;' value="">																		
					</div>
				</div>
				<!-- 2kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('2kg_packaging_quantity','2kg Bales :') !!} @show_tooltip('Enter produced 2kg bales.')
						<div class="input_inline">
							{!! Form::text('2kg_packaging_quantity', 0, ['class' => 'form-control 2kg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Bales</span>
						</div>
						<p><strong>
						Total 2kg Bales:
							</strong>
							<input name="total_2kgs" id="total_2kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>												
							<input name="total_2kg_cost" type="hidden" style='border: #C0C0C0;' value="">																		
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('2kg_packets_quantity','2kg Packets :') !!} @show_tooltip('Enter produced 2kg packets.')
						<div class="input_inline">
							{!! Form::text('2kg_packets_quantity', 0, ['class' => 'form-control 2kg_packets_quantity input_number']); !!}
							<span class="input-group-addon" id="">Packets</span>
						</div>
						<p><strong>
						Total 2kg Packets:
							</strong>
							<input name="total_2kg_packets" id="total_2kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>												
							<input name="total_2kg_packets_cost" type="hidden" style='border: #C0C0C0;' value="">																		
					</div>
				</div>
				<!-- 5kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('5kg_packaging_quantity','5kg Packaging :') !!} @show_tooltip('Enter produced 5kg bags.')
						<div class="input_inline">
							{!! Form::text('5kg_packaging_quantity', 0, ['class' => 'form-control 5kg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Bags</span>
						</div>
						<p><strong>
						Total 5kg bags:
							</strong>
							<input name="total_5kgs" id="total_5kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>												
					        <input name="total_5kg_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<!-- 10kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('10kg_packaging_quantity','10kg Packaging :') !!} @show_tooltip('Enter produced 10kg bags.')
						<div class="input_inline">
							{!! Form::text('10kg_packaging_quantity', 0, ['class' => 'form-control 10kg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Bags</span>
						</div>
						<p><strong>
						Total 10kg bags:
							</strong>
							<input name="total_10kgs" id="total_10kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>																		
					        <input name="total_10kg_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<!-- 25kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('25kg_packaging_quantity','25kg Packaging :') !!} @show_tooltip('Enter produced 25kg bags.')
						<div class="input_inline">
							{!! Form::text('25kg_packaging_quantity', 0, ['class' => 'form-control 25kg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Bags</span>
						</div>
						<p><strong>
						Total 25kg bags:
							</strong>
							<input name="total_25kgs" id="total_25kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>																		
					        <input name="total_25kg_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<!-- 45kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('45kg_packaging_quantity','45kg Packaging :') !!} @show_tooltip('Enter produced 45kg bags.')
						<div class="input_inline">
							{!! Form::text('45kg_packaging_quantity', 0, ['class' => 'form-control 45kg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Bags</span>
						</div>						
						<p><strong>
						Total 45kg bags:
							</strong>
							<input name="total_45kgs" id="total_45kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>																		
				            <input name="total_45kg_cost" type="hidden" style='border: #C0C0C0;' value="">
					</div>
				</div>
				<!-- 50kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('50kg_packaging_quantity','50kg Packaging :') !!} @show_tooltip('Enter produced 50kg bags.')
						<div class="input_inline">
							{!! Form::text('50kg_packaging_quantity', 0, ['class' => 'form-control 50kg_packaging_quantity input_number']); !!}
                            <span class="input-group-addon" id="">Bags</span>
						</div>
						<p><strong>
						Total 50kg bags:
						</strong>
						<input name="total_50kgs" id="total_90kgs" type="number" style='border: #C0C0C0; width:70px; ' value="" readonly>																		
						 <input name="total_50kg_cost" type="hidden" style='border: #C0C0C0;' value="">																	
					</div>
				</div>
				<!-- 90kg -->
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('90kg_packaging_quantity','90kg Packaging :') !!} @show_tooltip('EEnter produced 90kg bags.')
						<div class="input_inline">
							{!! Form::text('90kg_packaging_quantity', 0, ['class' => 'form-control 90kg_packaging_quantity input_number']); !!}
							<span class="input-group-addon" id="">Bags</span>							
						</div>
						<p><strong>
						Total 90kg bags:
						</strong>
						<input name="total_90kgs" id="total_90kgs" type="number" style='border: #C0C0C0; width:70px; ' value="{{!empty($ingredient->unga_percent) ? @num_format($ingredient->unga_percent) : 0}}" readonly>																		
					    <input name="total_90kg_cost" type="hidden" style='border: #C0C0C0;' value="">
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
						{!! Form::select('kg_units',$units, 2, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required'] ); !!}
							<span class="input-group-addon" id="">Units</span>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('packet_units','Packet Units :') !!} @show_tooltip('select packet units here.')
						<div class="input_inline">
						{!! Form::select('packet_units',$units, 4, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required'] ); !!}
							<span class="input-group-addon" id="">Units</span>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						{!! Form::label('bag_units','Bag units:') !!} @show_tooltip('select bag units here.')
						<div class="input_inline">
							{!! Form::select('bag_units',$units, 6, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required'] ); !!}
							<span class="input-group-addon" id="">Units</span>
						</div>
					</div>
				</div>
				
				<div class="col-md-4">
					<div class="form-group">
						{!! Form::label('bale_units','Bale units:') !!} @show_tooltip('select bale units here.')
						<div class="input_inline">
							{!! Form::select('bale_units',$units, 3, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required'] ); !!}
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

		<!--<div class="row">-->
		<!--	<div class="col-md-3 col-md-offset-9">-->
		<!--		{!! Form::hidden('final_total', 0, ['id' => 'final_total']); !!}-->
		<!--		<strong>-->
		<!--			{{__('manufacturing::lang.total_cost')}}:-->
		<!--		</strong>-->
		<!--		<span id="final_total_text" class="display_currency" data-currency_symbol="true">0</span>-->
		<!--	</div>-->
		<!--</div>-->
		<!--<div class="row">-->
		<!--	<div class="col-md-3 col-md-offset-9">-->
		<!--		<div class="form-group">-->
		<!--			<br>-->
		<!--			<div class="checkbox">-->
		<!--				<label>-->
		<!--				{!! Form::checkbox('finalize', 1, false, ['class' => 'input-icheck', 'id' => 'finalize']); !!} @lang('manufacturing::lang.finalize')-->
		<!--				</label> @show_tooltip(__('manufacturing::lang.finalize_tooltip'))-->
		<!--			</div>-->
		<!--        </div>-->
		<!--	</div>-->
		<!--</div>-->
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
