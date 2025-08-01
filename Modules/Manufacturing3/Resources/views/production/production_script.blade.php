<script type="text/javascript">
	$(document).ready( function() {
        jQuery.validator.addMethod("notEmpty", function(value, element, param) {
            return __number_uf(value) > 0
        }, "{{__('manufacturing::lang.quantity_greater_than_zero')}}");

        jQuery.validator.addMethod("notEqualToWastedQuantity", function(value, element, param) {
            var waste_qty = __read_number($('#mfg_wasted_units'));
            var qty = __number_uf(value);
            return qty > waste_qty;
        }, "{{__('manufacturing::lang.waste_qty_less_than_qty')}}");

		$('#transaction_date').datetimepicker({
	        format: moment_date_format + ' ' + moment_time_format,
	        ignoreReadonly: true,
	    });

	    $('#exp_date').datepicker({
            autoclose: true,
            format: datepicker_date_format,
        });

	    production_form_validator = $('#production_form').validate();
	});
	$(document).on('change', '#production_form #variation_id, #production_form #location_id', function () {
		var variation_id = $("#variation_id").val();
		var location_id = $("#location_id").val();
		
		if(variation_id && location_id) {
			$.ajax({
	            url: "/manufacturing/get-recipe-details?variation_id=" + variation_id + "&location_id=" + location_id,
	            dataType: 'json',
	            success: function(result) {
	                $('#enter_ingredients_table').html(result.ingredient_table);
	                if (result.is_sub_unit) {
	                	$('#recipe_quantity_input').removeClass('input-group');
	                	$('#recipe_quantity_input').addClass('input_inline');
	                	$('#unit_html').removeClass('input-group-addon');
	                } else {
	                	$('#recipe_quantity_input').addClass('input-group');
	                	$('#recipe_quantity_input').removeClass('input_inline');
	                	$('#unit_html').addClass('input-group-addon');
	                }
	                __write_number($('#recipe_quantity'), result.recipe.total_quantity);
	                $('#unit_html').html(result.unit_html);
	                $('#wasted_units_text').text(result.unit_name);

                    var mfg_wasted_units = __calculate_amount('percentage', $('#waste_percent').val(), result.recipe.total_quantity);
                    __write_number($('#mfg_wasted_units'), mfg_wasted_units);
                    __write_number($('#production_cost'), result.recipe.extra_cost);
                    $('#mfg_production_cost_type').val(result.recipe.production_cost_type);

	                __currency_convert_recursively($('#enter_ingredients_table'));
                    calculateRecipeTotal();
	            },
	        });
		} else {
			$('#enter_ingredients_table').html('');
	        calculateRecipeTotal();
		}
	});

	$(document).on('change', '#production_cost, #production_process, select.sub_unit, input.mfg_waste_percent, #mfg_production_cost_type', function(){
		calculateRecipeTotal();
		calculateProductionPackaging();
	});

    $(document).on('change', '#recipe_quantity, #sub_unit_id', function(){
        var recipe_quantity = __read_number($('#recipe_quantity'));

        var mfg_wasted_units = __calculate_amount('percentage', $('#waste_percent').val(), recipe_quantity);
        __write_number($('#mfg_wasted_units'), mfg_wasted_units);

        var multiplier = 1;
        if ($('#sub_unit_id').length) {
            multiplier = parseFloat(
            $('#sub_unit_id')
                .find(':selected')
                .data('multiplier')
            );
            recipe_quantity = recipe_quantity * multiplier; 
        }
        $('#ingredients_for_unit_recipe_table tbody tr').each( function() {
            var line_unit_quantity = parseFloat($(this).find('.unit_quantity').val());
            var line_multiplier = __getUnitMultiplier($(this));
            var line_total_quantity = (recipe_quantity * line_unit_quantity) / line_multiplier;
            __write_number($(this).find('.total_quantities'), line_total_quantity);
            $(this).find('.total_quantities').change();
        });
    });

	$(document).on('change', '#sub_unit_id', function(){
		var unit_name = $(this)
                .find(':selected')
                .data('unit_name');
            $('#wasted_units_text').text(unit_name);
	});

	$(document).on('change', '.total_quantities', function(){
		if (production_form_validator) {
    		production_form_validator.element($(this));
		}
		calculateRecipeTotal();
	});

	function calculateRecipeTotal() {
		var recipe_quantity = __read_number($('#recipe_quantity'));

        var multiplier = 1;
        if ($('#sub_unit_id').length) {
            multiplier = parseFloat(
            $('#sub_unit_id')
                .find(':selected')
                .data('multiplier')
            );
            recipe_quantity = recipe_quantity * multiplier; 
        }
        var total_ingredients_cost = 0;

        var line_dust = 0;
        var line_gem = 0;
        var line_bran = 0; 
        var line_fortified = 0;
        
     
         var milling_quantity = __read_number($('#total_ingredient_price'));
                console.log(`ingredients_cost is ${milling_quantity}`);

        $('#ingredients_for_unit_recipe_table tbody tr').each( function() {
            if ($(this).find('.ingredient_price').length > 0) {
                var line_unit_price = parseFloat($(this).find('.ingredient_price').val());
                var line_unit_quantity = parseFloat($(this).find('.unit_quantity').val());
                var line_unit_total = line_unit_price * line_unit_quantity;
                var line_total_quantity = __read_number($(this).find('.total_quantities'));
                var line_multiplier = __getUnitMultiplier($(this));
                var line_waste_percent = __read_number($(this).find('.mfg_waste_percent'));

                var line_final_quantity = __substract_percent(line_total_quantity, line_waste_percent);

                var line_total = line_unit_price * line_total_quantity * line_multiplier;
                $(this).find('span.ingredient_total_price').text(__currency_trans_from_en(line_total, true));
                $(this).find('span.row_final_quantity').text(__currency_trans_from_en(line_final_quantity, false));

                var line_unit_name = '';

                if ($(this).find('.sub_unit').length) {
                    line_unit_name = $(this).find('.sub_unit')
                    .find('option:selected')
                    .text();
                } else {
                    line_unit_name = $(this).find('.line_unit_span').text();
                }
                $(this).find('.row_unit_text').text(line_unit_name);

                total_ingredients_cost += line_total;
            }
        });

        line_produced = document.getElementById("recipe_quantity").value;
        var process = $("#production_process").val();
        console.log('process is', process);
        
        if(process == 1){
        document.getElementById("mfg_product_units").value = line_produced ;
        }else{
        document.getElementById("mfg_product_units").value = 0;
        }

        var production_cost = __read_number($('#production_cost'));
        
        var previous_unpacked = 0; 
        previous_unpacked = __read_number($('#previous_unpacked'));
        console.log("Previous unpacked: " + previous_unpacked);

        document.getElementById("carried_product_units").value = previous_unpacked;
        
        if(process == 1){
        var total_unpacked_produce = previous_unpacked + line_produced; 
        }
        else{
            var total_unpacked_unga = previous_unpacked; 
        }
        
        
        document.getElementById("unpacked_quantity").value = total_unpacked_unga;
        
        $('#total_ingredient_price').text(__currency_trans_from_en(total_ingredients_cost, true));
        var production_cost = __read_number($('#production_cost'));
        var production_cost_type = $('#mfg_production_cost_type').val();
        if (production_cost_type == 'percentage') {
            production_cost = __calculate_amount('percentage', production_cost, total_ingredients_cost);
        } else if(production_cost_type == 'per_unit') {
            production_cost = production_cost * __read_number($('#recipe_quantity'));
        }
        $('span#total_production_cost').text(__currency_trans_from_en(production_cost, true));
        var final_price = total_ingredients_cost + production_cost;
        __write_number($('#final_total'), final_price);
        $('span#final_total_text').text(__currency_trans_from_en(final_price, true));
        __write_number($('#total'), total_ingredients_cost);
    }
    
    
    $(document).on('change', '#tenkg_packaging_quantity, #twentykg_packaging_quantity, #fiftykg_packaging_quantity, #seventykg_packaging_quantity', function(){
        // console.log('change occured');
        calculateProductionPackaging();
    });

    function calculateProductionPackaging(){
        var unpacked_quantity = 0;
       

        var mfg_product = __read_number($("#recipe_quantity"));
        var carried_product_units = __read_number($('#carried_product_units'));
        var total_product = mfg_product + carried_product_units;
        console.log('total_product is ' + total_product);
        console.log('carried_prod_units is ' + carried_product_units);
        
        var total_cost = __read_number($('#final_total'));
        console.log("Total Cost: " + total_cost);
        console.log("Mfg Product: " + mfg_product);
        var unit_cost = total_cost/mfg_product;
        console.log('unit cost here is ' + unit_cost);
        
        if(carried_product_units>0){
            var extra_cost = carried_product_units*unit_cost;
            total_cost +=extra_cost;
            unit_cost = total_cost/total_product;
            
        }
        console.log('unit_cost is ', unit_cost);

        
        
        
        var ten_kg_bag = __read_number($('#tenkg_packaging_quantity'));
        var twenty_kg_bag = __read_number($('#twentykg_packaging_quantity'));
        var fifty_kg_bag = __read_number($('#fiftykg_packaging_quantity'));
        var seventy_kg_bag = __read_number($('#seventykg_packaging_quantity'));
        
        console.log(fifty_kg_bag);
        console.log(seventy_kg_bag)
        
        var total_ten_kgs = ten_kg_bag*10;
        var total_twenty_kgs = twenty_kg_bag*20;
        var total_fifty_kgs = fifty_kg_bag*50;
        var total_seventy_kgs = seventy_kg_bag*70;

        // packetcosts
        var total_ten_kgs_cost = total_ten_kgs*unit_cost;
        var total_twenty_kgs_cost = total_twenty_kgs*unit_cost;
        var total_fifty_kgs_cost = total_fifty_kgs*unit_cost;
        var total_seventy_kgs_cost = total_seventy_kgs*unit_cost;
        
        // calculations
        unpacked_quantity = total_product - (total_ten_kgs + total_twenty_kgs + total_fifty_kgs + total_seventy_kgs);
        console.log("Unpacked: " + unpacked_quantity);
        
        var total_unpacked_cost = unpacked_quantity*unit_cost;
        console.log('total_unpacked_cost is', total_unpacked_cost);

        if(unpacked_quantity >=0){
            $('input[name="unpacked_quantity"]').val(`${(Math.round(unpacked_quantity * 100) / 100).toFixed(2)}`);
            

            $('input[name="total_ten_kgs"]').val(`${(Math.round(ten_kg_bag * 100) / 100).toFixed(1)}`);
            $('input[name="total_twenty_kgs"]').val(`${(Math.round(twenty_kg_bag * 100) / 100).toFixed(1)}`);
            $('input[name="total_fifty_kgs"]').val(`${(Math.round(fifty_kg_bag * 100) / 100).toFixed(1)}`);
            $('input[name="total_seventy_kgs"]').val(`${(Math.round(seventy_kg_bag * 100) / 100).toFixed(1)}`);
            
            $('input[name="total_ten_cost"]').val(`${(Math.round(total_ten_kgs_cost * 100) / 100).toFixed(3)}`);
            $('input[name="total_twenty_cost"]').val(`${(Math.round(total_twenty_kgs_cost * 100) / 100).toFixed(3)}`);
            $('input[name="total_fifty_cost"]').val(`${(Math.round(total_fifty_kgs_cost * 100) / 100).toFixed(3)}`);
            $('input[name="total_seventy_cost"]').val(`${(Math.round(total_seventy_kgs_cost * 100) / 100).toFixed(3)}`);
            
            $('input[name="total_unpacked_cost"]').val(`${(Math.round(total_unpacked_cost * 100) / 100).toFixed(3)}`);
            
        }else{
            alert("Error!! Current input exceeds remaning unpacked produce quantity!!")
            $('input[name="unpacked_quantity"]').val(`${(Math.round(unpacked_quantity * 100) / 100).toFixed(2)}`);

        }


        


    }

    $(document).on('change', 'select.sub_unit', function() {
        var tr = $(this).closest('tr');
        var selected_option = $(this).find(':selected');
        var multiplier = parseFloat(selected_option.data('multiplier'));
        var allow_decimal = parseInt(selected_option.data('allow_decimal'));

        var qty_element = tr.find('input.total_quantities');
        var base_max_avlbl = qty_element.data('qty_available');
        var error_msg_line = 'pos_max_qty_error';

        qty_element.attr('data-decimal', allow_decimal);
        var abs_digit = true;
        if (allow_decimal) {
            abs_digit = false;
        }
        qty_element.rules('add', {
            abs_digit: abs_digit,
        });

        if (base_max_avlbl) {
            var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
            var formated_max_avlbl = __number_f(max_avlbl);
            var unit_name = selected_option.data('unit_name');
            var max_err_msg = __translate(error_msg_line, {
                max_val: formated_max_avlbl,
                unit_name: unit_name,
            });
            qty_element.attr('data-rule-max-value', max_avlbl);
            qty_element.attr('data-msg-max-value', max_err_msg);
            qty_element.rules('add', {
                'max-value': max_avlbl,
                messages: {
                    'max-value': max_err_msg,
                },
            });
            qty_element.trigger('change');
        }
    });
</script>