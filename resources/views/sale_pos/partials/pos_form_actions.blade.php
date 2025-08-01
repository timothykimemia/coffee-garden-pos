@php
	$is_mobile = isMobile();
@endphp
<div class="row">
	<div class="pos-form-actions">
		<div class="col-md-12">
			@if($is_mobile)
				<div class="col-md-12 text-right">
					<b>@lang('sale.total_payable'):</b>
					<input type="hidden" name="final_total" 
												id="final_total_input" value=0>
					<span id="total_payable" class="text-success lead text-bold text-right">0</span>
				</div>
			@endif
			<button type="button" class="@if($is_mobile) col-xs-6 @endif btn bg-info text-white btn-default btn-flat @if($pos_settings['disable_draft'] != 0) hide @endif" id="pos-draft"><i class="fas fa-edit"></i> @lang('sale.draft')</button>
			<button type="button" class="btn btn-default bg-yellow btn-flat @if($is_mobile) col-xs-6 @endif" id="pos-quotation"><i class="fas fa-edit"></i> @lang('lang_v1.quotation')</button>

			@if(empty($pos_settings['disable_suspend']))
				<button type="button" 
				class="@if($is_mobile) col-xs-6 @endif btn bg-red btn-default btn-flat no-print pos-express-finalize" 
				data-pay_method="suspend"
				title="@lang('lang_v1.tooltip_suspend')" >
				<i class="fas fa-pause" aria-hidden="true"></i>
				@lang('lang_v1.suspend')
				</button>
			@endif

            <button type="button" id="print-order" data-url="{{ route('print.order.details') }}" class="btn btn-info">Print Order</button>
            
			@if(empty($pos_settings['disable_credit_sale_button']))
				<input type="hidden" name="is_credit_sale" value="0" id="is_credit_sale">
				<button type="button" 
				class="btn bg-purple btn-default btn-flat no-print pos-express-finalize @if($is_mobile) col-xs-6 @endif" 
				data-pay_method="credit_sale"
				title="@lang('lang_v1.tooltip_credit_sale')" >
					<i class="fas fa-check" aria-hidden="true"></i> @lang('lang_v1.credit_sale')
				</button>
			@endif
			<button type="button" 
				class="btn bg-maroon btn-default btn-flat no-print @if(!empty($pos_settings['disable_suspend'])) @endif pos-express-finalize @if(!array_key_exists('card', $payment_types)) hide @endif @if($is_mobile) col-xs-6 @endif" 
				data-pay_method="card"
				title="@lang('lang_v1.tooltip_express_checkout_card')" >
				<i class="fas fa-credit-card" aria-hidden="true"></i> @lang('lang_v1.express_checkout_card')
			</button>

			<button type="button" class="btn bg-navy btn-default @if(!$is_mobile) @endif btn-flat no-print @if($pos_settings['disable_pay_checkout'] != 0) hide @endif @if($is_mobile) col-xs-6 @endif" id="pos-finalize" title="@lang('lang_v1.tooltip_checkout_multi_pay')"><i class="fas fa-money-check-alt" aria-hidden="true"></i> @lang('lang_v1.checkout_multi_pay') </button>

			<button type="button" class="btn btn-success @if(!$is_mobile) @endif btn-flat no-print @if($pos_settings['disable_express_checkout'] != 0 || !array_key_exists('cash', $payment_types)) hide @endif pos-express-finalize @if($is_mobile) col-xs-6 @endif" data-pay_method="cash" title="@lang('tooltip.express_checkout')"> <i class="fas fa-money-bill-alt" aria-hidden="true"></i> @lang('lang_v1.express_checkout_cash')</button>

			@if(empty($edit))
				<button type="button" class="btn btn-danger btn-flat @if($is_mobile) col-xs-6 @else btn-xs @endif" id="pos-cancel"> <i class="fas fa-window-close"></i> @lang('sale.cancel')</button>
			@else
				<button type="button" class="btn btn-danger btn-flat hide @if($is_mobile) col-xs-6 @else btn-xs @endif" id="pos-delete"> <i class="fas fa-trash-alt"></i> @lang('messages.delete')</button>
			@endif

			@if(!$is_mobile)
			<div class="bg-navy pos-total text-white">
			<span class="text">@lang('sale.total_payable')</span>
			<input type="hidden" name="final_total" 
										id="final_total_input" value=0>
			<span id="total_payable" class="number">0</span>
			</div>
			@endif

			@if(!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
			<button type="button" class="pull-right btn btn-primary btn-flat @if($is_mobile) col-xs-6 @endif" data-toggle="modal" data-target="#recent_transactions_modal" id="recent-transactions"> <i class="fas fa-clock"></i> @lang('lang_v1.recent_transactions')</button>
			@endif

			
			
		</div>
	</div>
</div>
@if(isset($transaction))
	@include('sale_pos.partials.edit_discount_modal', ['sales_discount' => $transaction->discount_amount, 'discount_type' => $transaction->discount_type, 'rp_redeemed' => $transaction->rp_redeemed, 'rp_redeemed_amount' => $transaction->rp_redeemed_amount, 'max_available' => !empty($redeem_details['points']) ? $redeem_details['points'] : 0])
@else
	@include('sale_pos.partials.edit_discount_modal', ['sales_discount' => $business_details->default_sales_discount, 'discount_type' => 'percentage', 'rp_redeemed' => 0, 'rp_redeemed_amount' => 0, 'max_available' => 0])
@endif

@if(isset($transaction))
	@include('sale_pos.partials.edit_order_tax_modal', ['selected_tax' => $transaction->tax_id])
@else
	@include('sale_pos.partials.edit_order_tax_modal', ['selected_tax' => $business_details->default_sales_tax])
@endif

@include('sale_pos.partials.edit_shipping_modal')

<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
<script type="module">
    $(document).ready(function() {
        var cnf = true;
    
    	document.getElementById('print-order').addEventListener('click', function(){
    		printFunction('print-order', cnf)
    	});
    });
    
    function printFunction(id, cnf) {
      	if (cnf) {
    		const items = [];
    		
    		var url = $('#' + id).data('url');
    		const tbody = document.getElementById('pbody');
    		const tdElements = tbody.querySelectorAll('tr');
    		const rows = Array.from(tbody.querySelectorAll('tr'));
    		
    		for (let r = 0; r < rows.length; r++) {
    			const tds = rows[r].querySelectorAll('td')
    			const tdata = Array.from(tds);
    			const name = tdata[0].innerText
    			const cldrn = Array.from(tdata[1].children)
    			const cld = Array.from(tdata[1].children).find(e => e.matches('div.input-group.input-number'))
    			const quantity = cld.querySelector('input.input_quantity').value;
    			const item = {name, quantity};
    			items.push(item);
    		}
    		
    		$.ajax({
    			method: 'POST',
    			url: url,
    			contentType: 'application/json',
    			data: JSON.stringify({ items: items }),
    			success: function(result) {
    				if (result.is_enabled) {
    					pos_print(result);
    				} else {
    					console.log("Error here");
    				}
    			},
    			error: function(xhr, status, error) {
    				console.log("AJAX Error: ", error);
    			}
    		});
      	}
    }
    
    function initialize_printer() {
        initializeSocket();
    }
    
    function pos_print(receipt) {
    
        if (receipt.print_type == 'printer') {
            var content = receipt;
            content.type = 'print-receipt';
    
            if (socket.readyState != 1) {
                initializeSocket();
                setTimeout(function() {
                    socket.send(JSON.stringify(content));
                }, 700);
            } else {
                socket.send(JSON.stringify(content));
            }
        } else if (receipt.html_content != '') {
            var title = document.title;
            if (typeof receipt.print_title != 'undefined') {
                document.title = receipt.print_title;
            }
    
            $('#receipt_section').html(receipt.html_content);
            __currency_convert_recursively($('#receipt_section'));
            setTimeout(function() {
                window.print();
                document.title = title;
            }, 1000);
        }
    }
</script>