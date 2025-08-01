<script type="text/javascript">
	$(document).ready( function () {

		$('#production_list_filter_date_range').daterangepicker(
        dateRangeSettings,
	        function (start, end) {
	            $('#production_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
	            productions_table.ajax.reload();
	        }
	    );
	    $('#production_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
	        $('#production_list_filter_date_range').val('');
	        productions_table.ajax.reload();
	    });
		//Purchase table
	    productions_table = $('#productions_table').DataTable({
	        processing: true,
	        serverSide: true,
	        aaSorting: [[0, 'desc']],
	        ajax: {
	            url: '{{action("\Modules\Manufacturing\Http\Controllers\ProductionController@index")}}',
	            "data": function ( d ) {
	                if($('#production_list_filter_date_range').val()) {
	                    var start = $('#production_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
	                    var end = $('#production_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
	                    d.start_date = start;
	                    d.end_date = end;
	                }
	                d.location_id = $('#productstion_list_filter_location_id').val();
	                if($('#production_list_is_final').is(':checked')) {
                        d.is_final = 1;
                    }
	                d = __datatable_ajax_callback(d);
	            },
	        },
	        columnDefs: [
	            {
	                targets: [6],
	                orderable: false,
	                searchable: false,
	            },
	        ],
	        columns: [
	            { data: 'transaction_date', name: 'transaction_date' },
	            { data: 'ref_no', name: 'ref_no' },
	            { data: 'location_name', name: 'bl.name' },
	            { data: 'product_name', name: 'product_name' },
	            { data: 'quantity', searchable: false },
	            { data: 'final_total', name: 'final_total' },
	            { data: 'action', name: 'action' },
	        ],
	        fnDrawCallback: function(oSettings) {
	            __currency_convert_recursively($('#productions_table'));
	        }
	    });

	    $(document).on('change', '#production_list_filter_date_range, #productstion_list_filter_location_id',  function() {
        	productions_table.ajax.reload();
    	});
    	$('#production_list_is_final').on('ifChanged', function(event){
            productions_table.ajax.reload();
        });

	    if ($('textarea#instructions').length > 0) {
            tinymce.init({
                selector: 'textarea#instructions',
            });
	    }

		if ($('#search_product').length) {
			initialize_search($('#search_product'));
	    }
	    if ($('.search_product').length) {
	    	$('.search_product').each( function(){
	    		initialize_search($(this));
	    	});
	    }

    	recipe_table = $('#recipe_table').DataTable({
	        processing: true,
	        serverSide: true,
	        ajax: '{{action("\Modules\Manufacturing\Http\Controllers\RecipeController@index")}}',
	        columnDefs: [
	            {
	                targets: [0, 5, 6, 7],
	                orderable: false,
	                searchable: false,
	            },
	        ],
	        "order": [[ 1, "desc" ]],
	        columns: [
	        	{ data: 'row_select' },
	       // 	{ data: 'created_at' },
	            { data: 'recipe_name', name: 'recipe_name' },
	            { data: 'category', name: 'c.name' },
	            { data: 'sub_category', name: 'sc.name' },
	            { data: 'total_quantity', name: 'total_quantity' },
	            { data: 'recipe_total' },
	            { data: 'unit_cost' },
	            { data: 'action', name: 'action' },
	        ],
	        fnDrawCallback: function(oSettings) {
	            __currency_convert_recursively($('#recipe_table'));
	        },
	    });
	});

	$(document).on('shown.bs.modal', '#recipe_modal', function(){
		initSelect2($(this).find('#variation_id'), $('#recipe_modal'));
        $(this).find('#copy_recipe_id').select2();
	});

	$(document).on('shown.bs.modal', '.view_modal', function(){
		__currency_convert_recursively($('.view_modal'));
	});

	$(document).on('change', '.row_sub_unit_id, #total_quantity, #extra_cost, #labor_cost, #sub_unit_id, #electricity_cost, #bags, #bag_cost', function(){
		calculateRecipeTotal();
	});

	/*$(document).on('change', '.initial, .premix_quota, .dustquantity, .gemquantity, .branquantity, .ungaquantity, .fortifyied_price', function(){
		calculateByProducts();
	});*/

    
	/*function calculateByProducts(){
		var dust =0;
		var gem = 0;
		var bran =0;
		var unga =0;

		var premix=0

		$('.ingredients_table tbody tr').each( function() {
		var quantity = __read_number($(this).find('.initial'));

		// var fortifying_unga = __read_number($(this).find('.dustquantity'));

		// cleaning and milling			
		var dustquantity = __read_number($(this).find('.dustquantity'));
		var gemquantity = __read_number($(this).find('.gemquantity'));
		var branquantity = __read_number($(this).find('.branquantity'));
// 		var ungaquantity = __read_number($(this).find('.ungaquantity'));
		var premix_quota = __read_number($(this).find('.premix_quota'));


		//fortifying
		var premix_amount = quantity*(premix_quota/1000);
		var final_quantity = quantity + (premix_amount/1000)

		dust = dustquantity/quantity*100;
		gem =gemquantity/quantity*100;
		bran = branquantity/quantity*100;
		
		ungaquantity = quantity - (dustquantity+gemquantity+branquantity);
		
		unga = ungaquantity/quantity*100;

		// cleaning and milling	
		$(this).find('input[class="dust_percent"]').val(`${(Math.round(dust * 100) / 100).toFixed(2)}`);
		$(this).find('input[class="gem_percent"]').val(`${(Math.round(gem * 100) / 100).toFixed(2)}`);
		$(this).find('input[class="bran_percent"]').val(`${(Math.round(bran * 100) / 100).toFixed(2)}`);
		$(this).find('input[class="unga_percent"]').val(`${(Math.round(unga * 100) / 100).toFixed(2)}`);
		$(this).find('input[class="unga_quantity"]').val(`${(Math.round(ungaquantity * 100) / 100).toFixed(2)}`);
		
// 		__write_number($('.unga_quantity'), ungaquantity);



		// fortifying
		$(this).find('input[class="premix_quantity"]').val(`${(Math.round(premix_amount * 100) / 100).toFixed(2)}`);
		$(this).find('input[class="fortified_unga"]').val(`${(Math.round(final_quantity * 100) / 100).toFixed(2)}`);

		
		calculateRecipeTotal();
		
		})
	}*/

    /*function calculateRecipeTotal() {
    	var total = 0;
		var quantity = 0;
		var ingredients_cost = 0;
		var total_extra_costs = 0;

    	// production_cost = __read_number($('#extra_cost'));
		
		var milling_quantity = __read_number($('#milling_input'));
		var fortifying_quantity = __read_number($('#fortifying_input'));
		var line_unit_price = __read_number($('#ingredient_price'));
		console.log('line_unit_price is :', line_unit_price);
		// var line_unit_price = $(this).find('.ingredient_price').val();
		

    	$('.ingredients_table tbody tr').each( function() {
			var unga_quantity = __read_number($(this).find('.ungaquantity'));

			var fortify_quantity = __read_number($(this).find('.fortified_unga'));

    		var multiplier = 1;
    		if ($(this).find('.row_sub_unit_id').length) {
    			multiplier = parseFloat(
		            $(this).find('.row_sub_unit_id')
		                .find(':selected')
		                .data('multiplier')
		        	);
    		}
			
    		var milling_total = line_unit_price * milling_quantity * multiplier;
    		console.log('milling_total is :', milling_total);

			var fortify_price = milling_total/fortifying_quantity;
 
    		var fortify_total = fortify_price * fortify_quantity * multiplier;
			ingredients_cost = fortify_total;
			
    		$(this).find('input[class="milling_cost"]').val(`${(Math.round(milling_total*100) / 100).toFixed(2)}`);
    		$(this).find('input[class="fortifying_cost"]').val(`${(Math.round(fortify_total*100) / 100).toFixed(2)}`);

			
			document.getElementById("totalquantity").value = `${(Math.round(fortify_quantity*100) / 100).toFixed(2)}`;
			
			$('span#ingredients_cost_text').text(`${(Math.round(fortify_total * 100) / 100).toFixed(2)}`);
			$('input[name="ingredients_cost"]').val(`${(Math.round(fortify_total * 100) / 100).toFixed(2)}`);

    	});

		var electricity_cost = 0;

    	var electricity_cost_per_sack = __read_number($('#electricity_cost'));
		electricity_cost = (electricity_cost_per_sack * milling_quantity) /90;
		$('input[name="total_electricity_cost"]').val(`${(Math.round(electricity_cost * 100) / 100).toFixed(2)}`);
		
		
		var labor_cost = 0;
		var labourers = __read_number($('#labourers_no'));
		var labourer_wage = __read_number($('#labor_cost'));
		labor_cost = labourers *labourer_wage;
		$('input[name="total_labour_bills"]').val(`${(Math.round(labor_cost * 100) / 100).toFixed(2)}`);
		
		
		var maize_offload_cost = 0;
		var maize_offload_quantity = __read_number($('#maize_offload_quantity'));
		var maize_offload_quantity_cost = __read_number($('#maize_offload_quantity_cost'));
		maize_offload_cost = maize_offload_quantity *maize_offload_quantity_cost;
		$('input[name="total_maize_offload_quantity_cost"]').val(`${(Math.round(maize_offload_cost * 100) / 100).toFixed(2)}`);
		
		
// 		var offloading_cost = 0;
// 		var offloaders_no = __read_number($('#offloaders_no'));
// 		var offloading_cost = __read_number($('#offloading_cost'));
// 		offloading_cost = offloaders_no *offloading_cost;
// 		$('input[name="total_offloading_bills"]').val(`${(Math.round(offloading_cost * 100) / 100).toFixed(2)}`);
		
		
		var flour_loading_cost = 0;
		var bales_no = __read_number($('#bales_no'));
		var bale_cost = __read_number($('#bale_cost'));
		flour_loading_cost = bales_no *bale_cost;
		$('input[name="total_bale_bills"]').val(`${(Math.round(flour_loading_cost * 100) / 100).toFixed(2)}`);

		var total_bale_pack_cost = 0;
		var packed_bales = __read_number($('#packed_bales'));
		var bale_pack_cost = __read_number($('#bale_pack_cost'));
		total_bale_pack_cost = packed_bales * bale_pack_cost;
		$('input[name="total_bale_pack_cost"]').val(`${(Math.round(total_bale_pack_cost * 100) / 100).toFixed(2)}`);
		
		var packaging_cost = 0;
		var kg1packets = __read_number($('#kg1packets'));
		var kg1packet_cost = __read_number($('#kg1packet_cost'));
		
		
		var kg2packets = __read_number($('#kg2packets'));
		var kg2packet_cost = __read_number($('#kg2packet_cost'));
		
		var bags = __read_number($('#bags'));
		var bag_cost = __read_number($('#bag_cost'));
		
		var balew = __read_number($('#balew'));
		var balew_cost = __read_number($('#balew_cost'));
		
		packaging_cost = ( kg1packets * kg1packet_cost ) + ( kg2packets * kg2packet_cost ) + ( bags * bag_cost ) + ( balew * balew_cost );
		
		$('input[name="total_packaging_bills"]').val(`${(Math.round(packaging_cost * 100) / 100).toFixed(2)}`);

		var transport_cost = 0;
		var transport_cost = __read_number($('#transport_cost'));
		$('input[name="transport_cost"]').val(`${(Math.round(transport_cost * 100) / 100).toFixed(2)}`);
		
    	var packet_cost = __read_number($('#packet_cost'));
    	var others = __read_number($('#others'));
    	
    	var total_extra_costs  = parseFloat(electricity_cost) +  parseFloat(labor_cost) + parseFloat(maize_offload_cost) + parseFloat(others) + 
    	parseFloat(flour_loading_cost) + parseFloat(packaging_cost) + parseFloat(transport_cost) + parseFloat(total_bale_pack_cost);
    	
		$('input[name="extra_cost"]').val(`${(Math.round(total_extra_costs * 100) / 100).toFixed(2)}`);
		
		total = total_extra_costs + parseFloat(ingredients_cost);
// 		console.log(`extra costs are ${total_extra_costs}`);
// 		console.log(`ingredients costs are ${ingredients_cost}`);
// 		console.log(`total costs are ${total}`);
// 		console.log(total);
    	__write_number($('#total'), total);
    }*/

	function calculateRecipeTotal() {
    	var total = 0;
    	$('.ingredients_table tbody tr').each( function() {
    		var line_unit_price = $(this).find('.ingredient_price').val();
    		var quantity = __read_number($(this).find('.quantity'));
    		var multiplier = 1;
    		if ($(this).find('.row_sub_unit_id').length) {
    			multiplier = parseFloat(
		            $(this).find('.row_sub_unit_id')
		                .find(':selected')
		                .data('multiplier')
		        	);
    		}

    		var line_total = line_unit_price * quantity * multiplier;
    		$(this).find('span.ingredient_price').text(__currency_trans_from_en(line_total, true));
    		total += line_total;
    	});
    	$('span#ingredients_cost_text').text(__currency_trans_from_en(total, true));
    	$('#ingredients_cost').val(total);
    	var production_cost = __read_number($('#extra_cost'));
    	
    	var labor_cost = __read_number($('#labor_cost'));
    	var bag_cost = __read_number($('#bag_cost'));
    	var electricity_cost = __read_number($('#electricity_cost'));
    	
        var production_cost_type = $('#production_cost_type').val();
        if (production_cost_type == 'percentage') {
    	   production_cost = __calculate_amount('percentage', production_cost, total);
        } else if(production_cost_type == 'per_unit') {
            var total_quantity = __read_number($('#total_quantity'));
            production_cost = total_quantity * production_cost;
        }

        $('span#total_production_cost').text(__currency_trans_from_en(production_cost, true));
        
		total += production_cost;
		
		total += labor_cost;
		total += bag_cost;
		total += electricity_cost;
		
    	__write_number($('#total'), total);
    }

	function addIngredientRow(variation_id, search_element) {
    	var row_index = parseInt($('#row_index').val());
    	var ingredient_group = search_element.closest('.box').find('.ingredient_group');
        var row_ig_index = ingredient_group.length ? ingredient_group.data('ig_index') : '';
        var sort_order = ++(search_element.closest('.box').find('.ingredient-row-sortable').children().length);

    	$.ajax({
            url: "/manufacturing/get-ingredient-row/" + variation_id + '?row_index=' + row_index + '&row_ig_index=' + row_ig_index + '&sort_order=' + sort_order,
            dataType: 'html',
            success: function(result) {
                search_element.closest('.box').find('table.ingredients_table tbody').append(result);
                calculateRecipeTotal();
                row_index++;
                $('#row_index').val(row_index);
            },
        });
    }

	function initSelect2(element, dropdownParent = $('body')) {
		element.select2({
	        ajax: {
	            url: '/products/list',
	            dataType: 'json',
	            delay: 250,
	            data: function(params) {
	                return {
	                    term: params.term, // search term
	                };
	            },
	            processResults: function(data) {
	            	return {
			            results: $.map(data, function (value, key) {
			            	var name = value.type == 'variable' ? value.name + ' - ' + value.variation : value.name;
			            	name += ' (' + value.sub_sku + ')';
			                return {
			                    id: value.variation_id,
			                    text: name
			                }
			            })
			        };
	            },
	        },
	        minimumInputLength: 1,
	        escapeMarkup: function(markup) {
	            return markup;
	        },
	        dropdownParent: dropdownParent
	    });
	}

	$(document).on('click', 'button.remove_ingredient', function() {
        
        element = $(this).closest('tbody.ingredient-row-sortable');

		$(this).closest('tr').remove();

        //set the order of ingredient
        $(element).children().each(function(index) {
            $(this).find('input.sort_order').val(++index)
        });

		calculateRecipeTotal();
	});
    
	$(document).on('submit', '#recipe_form', function (e) {
		var ingredients_length = $('.ingredients_table tbody .initial').length;
		if (ingredients_length < 1) {
			toastr.error('@lang("manufacturing::lang.please_add_ingredients")');
			e.preventDefault();
			return false;
		}
	});

	$(document).on('click', 'button#add_ingredient_group', function() {
		var ig_index = parseInt($('#ig_index').val());
    	$.ajax({
            url: "/manufacturing/ingredient-group-form" + '?ig_index=' + ig_index,
            dataType: 'html',
            success: function(result) {
            	var el = $(result);
                $('#box_group').append(el);
                initialize_search(el.find('.search_product'));
                el.find('.ingredient_group').focus();
                ig_index++;
                $('#ig_index').val(ig_index);
            },
        });
	});

	function initialize_search(element) {
		element.autocomplete({
            source: function(request, response) {
                $.getJSON(
                    '/products/list',
                    {
                        term: request.term,
                        product_types: ['single', 'variable']
                    },
                    response
                );
            },
            minLength: 2,
            response: function(event, ui) {
                if (ui.content.length == 0) {
                    toastr.error(LANG.no_products_found);
                    $('input#search_product').select();
                }
            },
            select: function(event, ui) {
                addIngredientRow(ui.item.variation_id, $(this));
            },
        }).autocomplete('instance')._renderItem = function(ul, item) {
	        var string = '<li>' + item.name;
            if (item.type == 'variable') {
                string += '-' + item.variation;
            }
            string +=
                ' (' +
                item.sub_sku +
                ')' +
                '</li>';
            return $(string).appendTo(ul);
        }
	}
	$(document).on('click', 'button.remove_ingredient_group', function() {
	$(this).closest('.box').remove();
	calculateRecipeTotal();
});

$(document).on('click', '#mass_update_product_price', function(e){
    e.preventDefault();
    var selected_rows = [];
    var unit_prices = [];
    var i = 0;
    $('.row-select:checked').each(function () {
    	var recipe_id = $(this).val();
        selected_rows[i++] = recipe_id;
        unit_prices[recipe_id] = $(this).closest('tr').find('span.unit_cost').data('unit_cost');
    });
    
    if(selected_rows.length > 0){
        swal({
            title: LANG.sure,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var data = {
                	recipe_ids: selected_rows,
                	unit_prices: unit_prices
                }
                $.ajax({
                    method: "post",
                    url: "/manufacturing/update-product-prices",
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            recipe_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    } else{
        swal('@lang("lang_v1.no_row_selected")');
    }    
});

$(document).on('click', 'button.delete_recipe', function() {
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then(willDelete => {
        if (willDelete) {
            var href = $(this).data('href');
            var data = $(this).serialize();
            $.ajax({
                method: 'DELETE',
                url: href,
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        recipe_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

$(document).on('click', '.delete-production', function(e) {
	e.preventDefault();
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then(willDelete => {
        if (willDelete) {
            var href = $(this).data('href');
            var data = $(this).serialize();
            $.ajax({
                method: 'DELETE',
                url: href,
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        productions_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

$(document).on('change', '#choose_product_form #variation_id', function() {
    var variation_id = $(this).val();
    if (variation_id) {
        $.ajax({
            method: 'get',
            url: "/manufacturing/is-recipe-exist/" + variation_id,
            dataType: 'json',
            success: function(result) {
                if (result == 1) {
                    $('#choose_product_form #recipe_selection').addClass('hide');
                } else {
                    $('#choose_product_form #recipe_selection').removeClass('hide');
                }
            },
        });
    } else {
        $('#choose_product_form #recipe_selection').removeClass('hide');
    }
})

// $(document).on('change', '#choose_product_form #variation_id', function() {
//     var variation_id = $(this).val();
//     if (variation_id) {
//         $.ajax({
//             method: 'get',
//             url: "/manufacturing/is-recipe-exist/" + variation_id,
//             dataType: 'json',
//             success: function(result) {
//                 if (result == 1) {
//                     $('#choose_product_form #recipe_selection').addClass('hide');
//                 } else {
//                     $('#choose_product_form #recipe_selection').removeClass('hide');
//                 }
//             },
//         });
//     } else {
//         $('#choose_product_form #recipe_selection').removeClass('hide');
//     }
// })
// $(document).on('change', '#step', function() {
//     var variation_id = $("#wastepercent").val();
// 	alert("The input value has changed. The new value is: " + variation_id);

    
// })

/*function handleChange(selectObject) {
	var value = selectObject.value; 
	// alert(value);
	$('.ingredients_table tr').each( function(e) {
	if(value=="Milling"){
	$(".dust").show();
	$(this).find(".gem").show();
	$(".bran").show();
	$(".unga").show();
	$(".premixquota").hide();
	$(".premix").hide();
	$(".final_quantity").hide();
	}
	
	
	else{
			// e.preventDefault();
			$(".ingredients_table.dust").hide();
			$(".gem").hide();
			$(".bran").hide();
			$(".unga").hide();
			$(".premixquota").show();
			$(".premix").show();
			$(".final_quantity").show();
		}
	})


	// document.getElementsByClassName('dust').style.display = "";
	// if (value == 'M'){
	// 	document.getElementById('dust').style.display = "";
	// 	document.getElementById('gem').style.display = "";
	// 	document.getElementById('bran').style.display = "";
		
	// 	document.getElementById('dustp').style.display = "";
	// 	document.getElementById('gemp').style.display = "";
	// 	document.getElementById('branp').style.display = "";
	// }
	// if (value == 'F'){
	// 	document.getElementById('dust').style.display = "none";
	// 	document.getElementById('gem').style.display = "none";
	// 	document.getElementById('bran').style.display = "none";
	// }
}*/

/*function myFunction(val) {
  alert("The input value has changed. The new value is: " + val);
}
*/
</script>