<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\BusinessLocation;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\PurchaseLine;
use App\Product;
use App\Unit;
use App\ProductVariation;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Entities\MfgRecipe;
use Modules\Manufacturing\Utils\ManufacturingUtil;
use Yajra\DataTables\Facades\DataTables;
use Modules\Manufacturing\Entities\MfgIngredientGroup;
use App\Media;

class ProductionController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $mfgUtil;
    protected $businessUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil, ManufacturingUtil $mfgUtil, BusinessUtil $businessUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->mfgUtil = $mfgUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $productions = Transaction::join(
                'business_locations AS bl',
                'transactions.location_id',
                '=',
                'bl.id'
                )->join('purchase_lines as pl', 'pl.transaction_id', '=', 'transactions.id')
                ->leftJoin('units as su', 'pl.sub_unit_id', '=', 'su.id')
                ->join('variations as v', 'v.id', '=', 'pl.variation_id')
                ->join('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->join('units as u', 'p.unit_id', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'production_purchase')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'ref_no',
                    'bl.name as location_name',
                    DB::raw('IF(p.type="variable", 
                            CONCAT(p.name, " - ", pv.name, " - ", v.name, " (", v.sub_sku, ")"), 
                            CONCAT(p.name, " (", v.sub_sku, ")") 
                            ) as product_name'),
                    'pl.quantity',
                    'final_total',
                    'su.short_name as sub_unit_name',
                    'su.base_unit_multiplier',
                    'u.short_name as unit_name',
                    'mfg_is_final'
                )->groupBy('transactions.id');

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $productions->whereDate('transactions.transaction_date', '>=', $start)
                            ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $productions->where('transactions.location_id', $location_id);
                }
            }

            if (request()->has('is_final')) {
                $productions->where('transactions.mfg_is_final', 1);
            }

            return Datatables::of($productions)
                ->addColumn('action', function ($row) {
                    $html = '<button data-href="' .  action('\Modules\Manufacturing\Http\Controllers\ProductionController@show', $row->id) . '" class="btn btn-info btn-xs btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __('messages.view') . '</button>';
                    $html .= ' <button data-href="' . action('\Modules\Manufacturing\Http\Controllers\ProductionController@destroy', [$row->id]) . '" class="delete-production btn btn-xs btn-danger"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</button>';
                    if ($row->mfg_is_final == 0) {
                        $html .= ' <a href="' .  action('\Modules\Manufacturing\Http\Controllers\ProductionController@edit', $row->id) . '" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> ' . __('messages.edit') . '</a>';

                    }

                    return $html;
                })
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn(
                    'quantity',
                    function ($row) {
                        $qty = empty($row->base_unit_multiplier) ? $row->quantity : $row->quantity / $row->base_unit_multiplier ;
                        $unit = empty($row->sub_unit_name) ? $row->unit_name : $row->sub_unit_name;
                        return "<span class='display_currency' data-currency_symbol='false' data-orig-value='$qty' data-is_quantity='true'>$qty</span> $unit";
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['final_total', 'action', 'quantity'])
                ->filterColumn('product_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(p.name, ' - ', pv.name, ' - ', v.name, ' (', v.sub_sku, ')') like ?", ["%{$keyword}%"]);
                })
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('manufacturing::production.index')->with(compact('business_locations'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $recipe_dropdown = MfgRecipe::forDropdown($business_id, false);
        $units = Unit::forDropdown($business_id);


        return view('manufacturing::production.create')
                ->with(compact('business_locations', 'recipe_dropdown', 'units'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
       public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }
        // dd($request);
        try {
            $request->validate([
                'transaction_date' => 'required',
                'location_id' => 'required',
                'final_total' => 'required'
            ]);

            //Create Production purchase
            $manufacturing_settings = $this->mfgUtil->getSettings($business_id);
            $user_id = $request->session()->get('user.id');
            $v_id = $request->input('variation_id');
            $mfg = MfgRecipe::where('id', $v_id)->first();

            // $variation_id = $request->input('variation_id');
            $variation_id = $mfg->variation_id;
            $ingredient = Variation::with('product', 'product_variation', 'product.unit')
                            ->findOrFail($variation_id);
            $final_product_name = $ingredient->product->name;
            $final_product_id = $ingredient->product->id;

            $kgs_packaging_unit = $request->input('kg_units');
            $packets_packaging_unit = $request->input('packet_units');
            $bag_packaging_unit = $request->input('bag_units');
            $bale_packaging_unit = $request->input('bale_units');
            

            
            $transaction_data = $request->only([ 'ref_no', 'transaction_date', 'location_id', 'final_total']);
            $location_id = $transaction_data['location_id'];
            $business_id = $request->session()->get('user.business_id');

            /*$dust_units = $this->productUtil->num_uf($request->input('mfg_dust_units'));
            $gem_units = $this->productUtil->num_uf($request->input('mfg_gem_units'));
            $bran_units = $this->productUtil->num_uf($request->input('mfg_bran_units'));
            $unga_units = $this->productUtil->num_uf($request->input('mfg_unga_units'));
            $fortified_units = $this->productUtil->num_uf($request->input('mfg_fortified_units'));*/
            
            $product_units = $this->productUtil->num_uf($request->input('mfg_product_units'));
            
            // packagings
            $total_ten_kgs = $this->productUtil->num_uf($request->input('total_ten_kgs'));
            $total_twenty_kgs = $this->productUtil->num_uf($request->input('total_twenty_kgs'));
            $total_fifty_kgs = $this->productUtil->num_uf($request->input('total_fifty_kgs'));
            $total_seventy_kgs = $this->productUtil->num_uf($request->input('total_seventy_kgs'));
            
            
            /*// input amount
            $input_units = $this->productUtil->num_uf($request->input('milling_input'));
            $input_product_id = $this->productUtil->num_uf($request->input('milling_input_id'));*/

            // unpacked
            $unpacked_amount = $this->productUtil->num_uf($request->input('unpacked_quantity'));
            $total_unpacked_cost = $this->productUtil->num_uf($request->input('total_unpacked_cost'));
            
            $carried_product_units = $this->productUtil->num_uf($request->input('carried_product_units'));
            
            
            // costs
            $total_ten_cost = $this->productUtil->num_uf($request->input('total_ten_cost'));
            $total_twenty_cost = $this->productUtil->num_uf($request->input('total_twenty_cost'));
            $total_fifty_cost = $this->productUtil->num_uf($request->input('total_fifty_cost'));
            $total_seventy_cost = $this->productUtil->num_uf($request->input('total_seventy_cost'));
            
            $single_product_type = "single";
            $combo_product_type = "combo";
            
    
            // main
            $production_process = $request->input('production_process');
            
            
            $variation = Variation::where('id', $v_id)
            ->with(['product'])
            ->first();
            $input_name = $variation->product->name;
            
            $data =  $this->productUtil->getCurrentStock($variation->id, $location_id);
            // dd($data);
            
            /*if($production_process == 1){
            
                if($input_units<=$data){        
                
                    DB::beginTransaction();  
                    
                    
                    // packagings
                    if($unpacked_amount >=0){
                        $this->productUtil->decreaseProductQuantity($final_product_id, $variation_id, $location_id, $carried_fortified_units, 0, null, false);
                        self::savedata($request, $unpacked_amount, $final_product_name, 1, '', $total_unpacked_cost,0, $kgs_packaging_unit);
                    }
                    
                    
                    if($total_half_packets >0){
                        self::savedata($request, $total_half_packets, '1/2KG '.$final_product_name.' PACKET', 0, '', $total_half_packets_cost, 1, $packets_packaging_unit);
                    }
                    if($total_1kgs_packets >0){
                        self::savedata($request, $total_1kgs_packets, '1KG '.$final_product_name.' PACKET', 0, '', $total_1kg_packets_cost,1, $packets_packaging_unit);
                    }
                    if($total_2kgs_packets >0){
                        self::savedata($request, $total_2kgs_packets, '2KG '.$final_product_name.' PACKET', 0, '', $total_2kg_packets_cost,1, $packets_packaging_unit);
                    }
                    
                    if($total_half_kgs >0){
                        self::savedata($request, $total_half_kgs, '1/2KG '.$final_product_name, 0, '', $total_half_cost,1, $bale_packaging_unit);
                    }
                  
                  
                    DB::commit();
                
                    $output = ['success' => 1,
                                    'msg' => __('lang_v1.added_success')
                                ];    
                } else {
                        $output = ['success' => 0,
                        'msg' => __('Error! Your input amount exceeds current stock. Please update stock')
                    ];
                }
            }*/
            
            // REPACKAGING
           /*if($production_process == 2){        

            DB::beginTransaction();  

            // packagings
            if($unpacked_amount >=0){

                $this->productUtil->decreaseProductQuantity($final_product_id, $variation_id, $location_id, $carried_fortified_units, 0, null, false);
                self::savedata($request, $unpacked_amount, $final_product_name, 1, '', $total_unpacked_cost,0, $kgs_packaging_unit);
            }
            if($total_half_packets >0){
                self::savedata($request, $total_half_packets, '1/2KG '.$final_product_name.' PACKET', 0, '', $total_half_packets_cost, 1, $packets_packaging_unit);
            }
            if($total_1kgs_packets >0){
                self::savedata($request, $total_1kgs_packets, '1KG '.$final_product_name.' PACKET', 0, '', $total_1kg_packets_cost,1, $packets_packaging_unit);
            }
            if($total_2kgs_packets >0){
                self::savedata($request, $total_2kgs_packets, '2KG '.$final_product_name.' PACKET', 0, '', $total_2kg_packets_cost,1, $packets_packaging_unit);
            }
            
            if($total_half_kgs >0){
                self::savedata($request, $total_half_kgs, '1/2KG '.$final_product_name, 0, '', $total_half_cost,1, $bale_packaging_unit);
            }
            
            if($total_1kgs >0){
                self::savedata($request, $total_1kgs, '1KG '.$final_product_name, 0, '', $total_1kg_cost, 1, $bale_packaging_unit);
            }
            if($total_2kgs >0){
                self::savedata($request, $total_2kgs, '2KG '.$final_product_name, 0, '', $total_2kg_cost, 1, $bale_packaging_unit);
            }
            if($total_5kgs >0){
                self::savedata($request, $total_5kgs, '5KG '.$final_product_name, 0, '', $total_5kg_cost, 1, $bag_packaging_unit);
            }
            if($total_10kgs >0){
                self::savedata($request, $total_10kgs, '10KG '.$final_product_name, 0, '', $total_10kg_cost, 1, $bag_packaging_unit);
            }
            if($total_225kgs >0){
                self::savedata($request, $total_225kgs, '22.5KG '.$final_product_name, 0, '', $total_225kg_cost, 1, $bag_packaging_unit);
            }
            if($total_25kgs >0){
                self::savedata($request, $total_25kgs, '25KG '.$final_product_name, 0, '', $total_25kg_cost, 1, $bag_packaging_unit);
            }
            if($total_45kgs >0){
                self::savedata($request, $total_45kgs, '45KG '.$final_product_name, 0, '', $total_45kg_cost, 1, $bag_packaging_unit);
            }
            if($total_50kgs >0){
                self::savedata($request, $total_50kgs, '50KG '.$final_product_name, 0, '', $total_50kg_cost ,1, $bag_packaging_unit);
            }
            if($total_90kgs >0){
                self::savedata($request, $total_90kgs, '90KG '.$final_product_name, 0, '', $total_90kg_cost, 1, $bag_packaging_unit);
            }
          
            DB::commit();
        
            $output = ['success' => 1,
                            'msg' => __('lang_v1.added_success')
                        ];    
            }*/
            DB::beginTransaction();  

            // packagings
            if($unpacked_amount >=0){

                $this->productUtil->decreaseProductQuantity($final_product_id, $variation_id, $location_id, $carried_product_units, 0, null, false);
                self::savedata($request, $unpacked_amount, $final_product_name, 1, '', $total_unpacked_cost,0, $kgs_packaging_unit);
            }
            if($total_ten_kgs >0){
                self::savedata($request, $total_ten_kgs, $final_product_name.' 10kg', 0, '', $total_ten_cost, 1, $packets_packaging_unit);
            }
            if($total_twenty_kgs >0){
                self::savedata($request, $total_twenty_kgs, $final_product_name. ' 20kg', 0, '', $total_twenty_cost,1, $packets_packaging_unit);
            }
            if($total_fifty_kgs >0){
                self::savedata($request, $total_fifty_kgs, $final_product_name.' 50kg', 0, '', $total_fifty_cost,1, $packets_packaging_unit);
            }
            
            if($total_seventy_kgs >0){
                self::savedata($request, $total_seventy_kgs, $final_product_name.' 70kg', 0, '', $total_seventy_cost,1, $packets_packaging_unit);
            }
            
            DB::commit();
        
            $output = ['success' => 1,
                            'msg' => __('lang_v1.added_success')
                        ];
       
        } catch (Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        

        }
     return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
    }

    public function savedata ($request, $quantity, $name, $selling, $process, $total, $final_product, $packaging_unit)
    {  


        $business_id = $request->session()->get('user.business_id');
        $manufacturing_settings = $this->mfgUtil->getSettings($business_id);
        $user_id = $request->session()->get('user.id');
        $transaction_data = $request->only([ 'ref_no', 'transaction_date', 'location_id', 'final_total']);
        
        $product_locations = array(
        '0' => "1",
        '1' => "2"
      );

        
        $product = Product::updateOrCreate(
        [
            'name' => $name
        ],
        [
            'type' =>'single',
            'created_by' =>$user_id,
            'process' =>$process,
            'alert_quantity' =>0,
            'enable_stock' =>1,
            'unit_id' =>$packaging_unit,
            'not_for_selling' =>$selling,
            'tax_type' =>'exclusive',                                
            ]                           
            
        );
        $combo_variations = [];
        // if ($product_type == 'combo'){
        //     $combo_variations[] = [
        //                             'variation_id' => $value,
        //                             'quantity' => $this->productUtil->num_uf($quantity[$key]),
        //                             'unit_id' => $unit[$key]
        //                         ];
                
        // }
                            
        $sku = $this->productUtil->generateProductSku($product->id);

        $product->sku = $sku;
        // $product->product_locations()->sync($transaction_data['location_id']);
        $product->save();
        
        // add locations
        // $product->product_locations()->sync($product_locations);
        

        $variation = Variation::where('product_id', $product->id)
                                ->with(['product'])
                                ->first();
        if($variation == null){
        $this->productUtil->createSingleProductVariation($product->id, $product->sku, 0, 0, 0, 0, 0, $combo_variations);
        }
        
                            
                            
        $is_final = !empty($request->input('finalize')) ? 1 : 0;
        $transaction_data['business_id'] = $business_id;
        $transaction_data['created_by'] = $user_id;
        $transaction_data['type'] = 'production_purchase';
        $transaction_data['status'] = $is_final ? 'received' : 'pending';
        $transaction_data['payment_status'] = 'due';
        $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);
        $transaction_data['final_total'] = $this->productUtil->num_uf($total);

        //Update reference count
        $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
        //Generate reference number
        if (empty($transaction_data['ref_no'])) {
            $prefix = !empty($manufacturing_settings['ref_no_prefix']) ? $manufacturing_settings['ref_no_prefix'] : null;
            $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count, null, $prefix);
        }      

        $variation = Variation::where('product_id', $product->id)
                                ->with(['product'])
                                ->first();
        $final_total = $request->input('final_total');
        // $quantity = $request->input('quantity');
        $waste_units = $this->productUtil->num_uf($request->input('mfg_wasted_units'));
        
        // by_products
       

        $uf_qty = $this->productUtil->num_uf($quantity);
        if (!empty($waste_units)) {
            $new_qty = $uf_qty - $waste_units;
            $uf_qty = $new_qty;
            $quantity = $this->productUtil->num_f($new_qty);
        }

        $final_total_uf = $this->productUtil->num_uf($total);

        $unit_purchase_line_total = 0;
        if ($final_product == 1){
            $unit_purchase_line_total = $final_total_uf / $uf_qty;

        }


        $unit_purchase_line_total_f = $this->productUtil->num_f($unit_purchase_line_total);

        $transaction_data['mfg_wasted_units'] = $waste_units;
        $transaction_data['mfg_production_cost'] = $this->productUtil->num_uf($request->input('production_cost'));
        $transaction_data['mfg_production_cost_type'] = $request->input('mfg_production_cost_type');
        $transaction_data['mfg_is_final'] = $is_final;

        $purchase_line_data = [
            'variation_id' => $variation->id,
            'quantity' => $quantity,
            'product_id' => $variation->product_id,
            'product_unit_id' => $packaging_unit,
            'pp_without_discount' => $unit_purchase_line_total_f,
            'discount_percent' => 0,
            'purchase_price' => $unit_purchase_line_total_f,
            'purchase_price_inc_tax' => $unit_purchase_line_total_f,
            'item_tax' => 0,
            'purchase_line_tax_id' => null,
            'mfg_date' => $this->transactionUtil->format_date($transaction_data['transaction_date'])
        ];
        if (request()->session()->get('business.enable_lot_number') == 1) {
            $purchase_line_data['lot_number'] = $request->input('lot_number');
        }

        if (request()->session()->get('business.enable_product_expiry') == 1) {
            $purchase_line_data['exp_date'] = $request->input('exp_date');
        }

        if (!empty($request->input('sub_unit_id'))) {
            $purchase_line_data['sub_unit_id'] = $packaging_unit;
        }
     

        $transaction = Transaction::create($transaction_data);

        Media::uploadMedia($business_id, $transaction, $request, 'documents', false);

        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

        $update_product_price = !empty($manufacturing_settings['enable_updating_product_price']) && $is_final ? true : false;

        $this->productUtil->createOrUpdatePurchaseLines($transaction, [$purchase_line_data], $currency_details, $update_product_price);

        //Adjust stock over selling if found
        $this->productUtil->adjustStockOverSelling($transaction);

        //Create production sell
        $transaction_sell_data = [
            'business_id' => $business_id,
            'location_id' => $transaction->location_id,
            'transaction_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'status' => $is_final ? 'final' : 'draft',
            'type' => 'production_sell',
            'mfg_parent_production_purchase_id' => $transaction->id,
            'payment_status' => 'due',
            'final_total' => $transaction->final_total
        ];

        $sell_lines = [];
        $ingredient_quantities = !empty($request->input('ingredients')) ? $request->input('ingredients') : [];

        //Get ingredient details to create sell lines
        $variation_id = $request->input('variation_id');

        $recipe = MfgRecipe::where('id', $variation_id)->first();

        $all_variation_details = $this->mfgUtil->getIngredientDetails($recipe, $business_id);

        foreach ($all_variation_details as $variation_details) {
            $variation = $variation_details['variation'];
            
            $line_sub_unit_id = !empty($ingredient_quantities[$variation_details['id']]['sub_unit_id']) ?
            $ingredient_quantities[$variation_details['id']]['sub_unit_id'] : null;

            $line_multiplier = !empty($line_sub_unit_id) ? $variation_details['sub_units'][$line_sub_unit_id]['multiplier'] : 1;

            $mfg_waste_percent = !empty($ingredient_quantities[$variation_details['id']]['mfg_waste_percent']) ? $this->productUtil->num_uf($ingredient_quantities[$variation_details['id']]['mfg_waste_percent']) : 0;

            $mfg_ingredient_group_id = !empty($ingredient_quantities[$variation_details['id']]['mfg_ingredient_group_id']) ? $ingredient_quantities[$variation_details['id']]['mfg_ingredient_group_id'] : null;

            $sell_lines[] = [
                    'product_id' => $variation->product_id,
                    'variation_id' => $variation->id,
                    'quantity' => $this->productUtil->num_uf($ingredient_quantities[$variation_details['id']]['quantity']),
                    'item_tax' => 0,
                    'tax_id' => null,
                    'unit_price' => $variation->dpp_inc_tax * $line_multiplier,
                    'unit_price_inc_tax' => $variation->dpp_inc_tax * $line_multiplier,
                    'enable_stock' => $variation_details['enable_stock'],
                    'product_unit_id' => $packaging_unit,
                    'sub_unit_id' => $packaging_unit,
                    'base_unit_multiplier' => $line_multiplier,
                    'mfg_waste_percent' => $mfg_waste_percent,
                    'mfg_ingredient_group_id' => $mfg_ingredient_group_id
                ];
        }

        //Create Sell Transfer transaction
        $production_sell = Transaction::create($transaction_sell_data);
        // dd($production_sell);

        // if (!empty($sell_lines)) {
        //     $this->transactionUtil->createOrUpdateSellLines($production_sell, $sell_lines, $transaction_sell_data['location_id'], 
        //     null, null, ['mfg_waste_percent' => 'mfg_waste_percent', 'mfg_ingredient_group_id' => 'mfg_ingredient_group_id']);
        // }

        if ($production_sell->status == 'final') {
            foreach ($sell_lines as $sell_line) {
                if ($sell_line['enable_stock']) {
                    $line_qty = $sell_line['quantity'] * $sell_line['base_unit_multiplier'];
                    // $this->productUtil->decreaseProductQuantity(
                    //     $sell_line['product_id'],
                    //     $sell_line['variation_id'],
                    //     $production_sell->location_id,
                    //     $line_qty
                    // );
                }
            }

            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            //Map sell lines with purchase lines
            $business = ['id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => $production_sell->location_id,
                        'pos_settings' => $pos_settings
                    ];
            $this->transactionUtil->mapPurchaseSell($business, $production_sell->sell_lines, 'production_purchase');
        }

        
    }

     
    // public function store(Request $request)
    // {
    //     $business_id = $request->session()->get('user.business_id');
    //     if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
    //         abort(403, 'Unauthorized action.');
    //     }
       
    //     try {
    //         $request->validate([
    //             'transaction_date' => 'required',
    //             'location_id' => 'required',
    //             'final_total' => 'required'
    //         ]);

    //         //Create Production purchase
    //         $manufacturing_settings = $this->mfgUtil->getSettings($business_id);
    //         $user_id = $request->session()->get('user.id');
    //         $variation_id = $request->input('variation_id');
    //         $ingredient = Variation::with('product', 'product_variation', 'product.unit')
    //                         ->findOrFail($variation_id);
            
    //         $final_product_name = $ingredient->product->name;
    //         $packaging_unit = $ingredient->product->unit_id;


    //         $transaction_data = $request->only([ 'ref_no', 'transaction_date', 'location_id', 'final_total']);
    //         $location_id = $transaction_data['location_id'];
    //         $business_id = $request->session()->get('user.business_id');

    //         $dust_units = $this->productUtil->num_uf($request->input('mfg_dust_units'));
    //         $gem_units = $this->productUtil->num_uf($request->input('mfg_gem_units'));
    //         $bran_units = $this->productUtil->num_uf($request->input('mfg_bran_units'));
    //         $unga_units = $this->productUtil->num_uf($request->input('mfg_unga_units'));
    //         $fortified_units = $this->productUtil->num_uf($request->input('mfg_fortified_units'));
            
    //         // input amount
    //         $input_units = $this->productUtil->num_uf($request->input('milling_input'));
    //         $input_product_id = $this->productUtil->num_uf($request->input('milling_input_id'));

    //         // production_total
    //         $production_total_cost = $transaction_data['final_total'];
            
    //         // packaging amount
    //         $packaging_amount = $this->productUtil->num_uf($request->input('packaging_quantity'));

            
    //         $variation = Variation::where('product_id', $input_product_id)
    //         ->with(['product'])
    //         ->first();
    //         $data =  $this->productUtil->getCurrentStock($variation->id, $location_id);
            
    //         if($data >= $input_units){              
            
    //         DB::beginTransaction();  
    //         if($input_units > 0){
    //             $this->productUtil->decreaseProductQuantity($input_product_id, $variation->id, $location_id, $input_units, 0, null, false);
    //         }

    //         if($dust_units >0){
    //             self::savedata($request, $dust_units, 'MAIZE DUST', 2, 'Default', 0, 0, 0, 2);
    //         }
    //         if($gem_units >0){
    //             self::savedata($request, $gem_units, 'MAIZE GERM', 2, 'Default', 0, 0, 0, 2);
    //         }
    //         if($bran_units >0){
    //             self::savedata($request, $bran_units, 'MAIZE BRAN', 2, 'Default', 0, 0, 0, 2);
    //         }
    //         if($fortified_units >0){
    //             self::savedata($request, $fortified_units, $final_product_name, 2, '', $production_total_cost, $packaging_amount,1, $packaging_unit);
    //         }
    //         DB::commit();
        
    //         $output = ['success' => 1, 
    //                         'msg' => __('lang_v1.added_success')
    //                     ];    
    //         }else{
    //             $output = ['success' => 0,
    //             'msg' => __('Error! You have less or 0 stock for this production. Please add stock.')
    //         ];   
    //         }
       
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
    //         $output = ['success' => 0,
    //                         'msg' => __('messages.something_went_wrong')
    //                     ];
        

    //     }
    //  return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
    // }

    // public function savedata ($request, $quantity, $name, $selling, $process, $total, $packaging_amt, $final_product, $packaging_unit)
    // {
    //   if($packaging_amt> 0){
    //     $packets = $quantity/$packaging_amt;
    //     $quantity = $packets;
    //   }

    //     $business_id = $request->session()->get('user.business_id');
    //     $manufacturing_settings = $this->mfgUtil->getSettings($business_id);
    //     $user_id = $request->session()->get('user.id');
    //     $transaction_data = $request->only([ 'ref_no', 'transaction_date', 'location_id', 'final_total']);

        
    //     $product = Product::updateOrCreate(
    //         [
    //         'name' => $name
    //     ],
    //     [
    //         'type' =>'single',
    //         'created_by' =>$user_id,
    //         'business_id' =>$business_id,
    //         'process' =>$process,
    //         'alert_quantity' =>0,
    //         'enable_stock' =>1,
    //         'unit_id' =>$packaging_unit,
    //         'not_for_selling' =>$selling,
    //         'tax_type' =>'exclusive',                                
    //         ]                           
            
    //     );
    //     $sku = $this->productUtil->generateProductSku($product->id);

    //     $product->sku = $sku;
    //     $product->save();

    //     $variation = Variation::where('product_id', $product->id)
    //                             ->with(['product'])
    //                             ->first();
    //     if($variation == null){
    //     $this->productUtil->createSingleProductVariation($product->id, $product->sku, 0, 0, 0, 0, 0);
    //     }
        
       
    //     $is_final = !empty($request->input('finalize')) ? 1 : 0;
    //     $transaction_data['business_id'] = $business_id;
    //     $transaction_data['created_by'] = $user_id;
    //     $transaction_data['type'] = 'production_purchase';
    //     $transaction_data['status'] = $is_final ? 'received' : 'pending';
    //     $transaction_data['payment_status'] = 'due';
    //     $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);
    //     $transaction_data['final_total'] = $this->productUtil->num_uf($total);

    //     //Update reference count
    //     $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
    //     //Generate reference number
    //     if (empty($transaction_data['ref_no'])) {
    //         $prefix = !empty($manufacturing_settings['ref_no_prefix']) ? $manufacturing_settings['ref_no_prefix'] : null;
    //         $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count, null, $prefix);
    //     }      

    //     $variation = Variation::where('product_id', $product->id)
    //                             ->with(['product'])
    //                             ->first();
    //     $final_total = $request->input('final_total');
    //     // $quantity = $request->input('quantity');
    //     $waste_units = $this->productUtil->num_uf($request->input('mfg_wasted_units'));
        
    //     // by_products
       

    //     $uf_qty = $this->productUtil->num_uf($quantity);
    //     if (!empty($waste_units)) {
    //         $new_qty = $uf_qty - $waste_units;
    //         $uf_qty = $new_qty;
    //         $quantity = $this->productUtil->num_f($new_qty);
    //     }

    //     $final_total_uf = $this->productUtil->num_uf($total);

    //     $unit_purchase_line_total = 0;
    //     if ($name == 'fortified_unga'){
    //         $unit_purchase_line_total = $final_total_uf / $uf_qty;

    //     }


    //     $unit_purchase_line_total_f = $this->productUtil->num_f($unit_purchase_line_total);

    //     $transaction_data['mfg_wasted_units'] = $waste_units;
    //     $transaction_data['mfg_production_cost'] = $this->productUtil->num_uf($request->input('production_cost'));
    //     $transaction_data['mfg_production_cost_type'] = $request->input('mfg_production_cost_type');
    //     $transaction_data['mfg_is_final'] = $is_final;

    //     $purchase_line_data = [
    //         'variation_id' => $variation->id,
    //         'quantity' => $quantity,
    //         'product_id' => $variation->product_id,
    //         'product_unit_id' => $variation->product->unit_id,
    //         'pp_without_discount' => $unit_purchase_line_total_f,
    //         'discount_percent' => 0,
    //         'purchase_price' => $unit_purchase_line_total_f,
    //         'purchase_price_inc_tax' => $unit_purchase_line_total_f,
    //         'item_tax' => 0,
    //         'purchase_line_tax_id' => null,
    //         'mfg_date' => $this->transactionUtil->format_date($transaction_data['transaction_date'])
    //     ];
    //     if (request()->session()->get('business.enable_lot_number') == 1) {
    //         $purchase_line_data['lot_number'] = $request->input('lot_number');
    //     }

    //     if (request()->session()->get('business.enable_product_expiry') == 1) {
    //         $purchase_line_data['exp_date'] = $request->input('exp_date');
    //     }

    //     if (!empty($request->input('sub_unit_id'))) {
    //         $purchase_line_data['sub_unit_id'] = $packaging_unit;
    //         // $purchase_line_data['sub_unit_id'] = $request->input('sub_unit_id');
    //     }
     

    //     $transaction = Transaction::create($transaction_data);

    //     Media::uploadMedia($business_id, $transaction, $request, 'documents', false);

    //     $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

    //     $update_product_price = !empty($manufacturing_settings['enable_updating_product_price']) && $is_final ? true : false;

    //     $this->productUtil->createOrUpdatePurchaseLines($transaction, [$purchase_line_data], $currency_details, $update_product_price);

    //     //Adjust stock over selling if found
    //     $this->productUtil->adjustStockOverSelling($transaction);

    //     //Create production sell
    //     $transaction_sell_data = [
    //         'business_id' => $business_id,
    //         'location_id' => $transaction->location_id,
    //         'transaction_date' => $transaction->transaction_date,
    //         'created_by' => $transaction->created_by,
    //         'status' => 'draft',
    //         'type' => 'production_sell',
    //         'mfg_parent_production_purchase_id' => $transaction->id,
    //         'payment_status' => 'due',
    //         'final_total' => $transaction->final_total
    //     ];

    //     $sell_lines = [];
    //     $ingredient_quantities = !empty($request->input('ingredients')) ? $request->input('ingredients') : [];

    //     //Get ingredient details to create sell lines
    //     $variation_id = $request->input('variation_id');

    //     $recipe = MfgRecipe::where('variation_id', $variation_id)->first();

    //     $all_variation_details = $this->mfgUtil->getIngredientDetails($recipe, $business_id);

    //     foreach ($all_variation_details as $variation_details) {
    //         $variation = $variation_details['variation'];

    //         $line_sub_unit_id = !empty($ingredient_quantities[$variation_details['id']]['sub_unit_id']) ?
    //                         $ingredient_quantities[$variation_details['id']]['sub_unit_id'] : null;
    //         $line_multiplier = !empty($line_sub_unit_id) ? $variation_details['sub_units'][$line_sub_unit_id]['multiplier'] : 1;

    //         $mfg_waste_percent = !empty($ingredient_quantities[$variation_details['id']]['mfg_waste_percent']) ? $this->productUtil->num_uf($ingredient_quantities[$variation_details['id']]['mfg_waste_percent']) : 0;

    //         $mfg_ingredient_group_id = !empty($ingredient_quantities[$variation_details['id']]['mfg_ingredient_group_id']) ? $ingredient_quantities[$variation_details['id']]['mfg_ingredient_group_id'] : null;

    //         $sell_lines[] = [
    //                 'product_id' => $variation->product_id,
    //                 'variation_id' => $variation->id,
    //                 'quantity' => $this->productUtil->num_uf($ingredient_quantities[$variation_details['id']]['quantity']),
    //                 'item_tax' => 0,
    //                 'tax_id' => null,
    //                 'unit_price' => $variation->dpp_inc_tax * $line_multiplier,
    //                 'unit_price_inc_tax' => $variation->dpp_inc_tax * $line_multiplier,
    //                 'enable_stock' => $variation_details['enable_stock'],
    //                 'product_unit_id' => $packaging_unit,
    //                 'sub_unit_id' => $packaging_unit,
    //                 'base_unit_multiplier' => $line_multiplier,
    //                 'mfg_waste_percent' => $mfg_waste_percent,
    //                 'mfg_ingredient_group_id' => $mfg_ingredient_group_id
    //             ];
    //     }

    //     //Create Sell Transfer transaction
    //     $production_sell = Transaction::create($transaction_sell_data);

    //     if (!empty($sell_lines)) {
    //         $this->transactionUtil->createOrUpdateSellLines($production_sell, $sell_lines, $transaction_sell_data['location_id'], 
    //         null, null, ['mfg_waste_percent' => 'mfg_waste_percent', 'mfg_ingredient_group_id' => 'mfg_ingredient_group_id']);
    //     }

    //     if ($production_sell->status == 'final') {
    //         foreach ($sell_lines as $sell_line) {
    //             if ($sell_line['enable_stock']) {
    //                 $line_qty = $sell_line['quantity'] * $sell_line['base_unit_multiplier'];
    //                 $this->productUtil->decreaseProductQuantity(
    //                     $sell_line['product_id'],
    //                     $sell_line['variation_id'],
    //                     $production_sell->location_id,
    //                     $line_qty
    //                 );
    //             }
    //         }

    //         $business_details = $this->businessUtil->getDetails($business_id);
    //         $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

    //         //Map sell lines with purchase lines
    //         $business = ['id' => $business_id,
    //                     'accounting_method' => $request->session()->get('business.accounting_method'),
    //                     'location_id' => $production_sell->location_id,
    //                     'pos_settings' => $pos_settings
    //                 ];
    //         $this->transactionUtil->mapPurchaseSell($business, $production_sell->sell_lines, 'production_purchase');
    //     }

    //     // DB::commit();
        
    //     // $output = ['success' => 1,
    //     //                 'msg' => __('lang_v1.added_success')
    //     //             ];        
    //  }

     /**
     * Show the specified resource.
     * @return Response
     */
     
      public function undo($id)
    {
       if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $purchase = Transaction::where('business_id', $business_id)
                        ->where('type', 'purchase')
                        ->with(['purchase_lines', 'contact', 'tax', 'return_parent', 'purchase_lines.sub_unit', 'purchase_lines.product', 'purchase_lines.product.unit'])
                        ->find($id);
        
        dd($purchase);

        // foreach ($purchase->purchase_lines as $key => $value) {
        //     if (!empty($value->sub_unit_id)) {
        //         $formated_purchase_line = $this->productUtil->changePurchaseLineUnit($value, $business_id);
        //         $purchase->purchase_lines[$key] = $formated_purchase_line;
        //     }
        // }

        // foreach ($purchase->purchase_lines as $key => $value) {
        //     $qty_available = $value->quantity - $value->quantity_sold - $value->quantity_adjusted;

        //     $purchase->purchase_lines[$key]->formatted_qty_available = $this->transactionUtil->num_f($qty_available);
        // }

        return view('purchase_return.add')
                    ->with(compact('purchase'));
    }

 
    
     public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        $production_purchase = Transaction::where('business_id', $business_id)
                                    ->where('type', 'production_purchase')
                                    ->with(['purchase_lines', 'purchase_lines.variations', 'purchase_lines.variations.product_variation', 'purchase_lines.variations.product',
                                        'purchase_lines.sub_unit', 'purchase_lines.variations.product.unit', 'media'])
                                    ->findOrFail($id);

        $production_sell = Transaction::where('business_id', $business_id)
                                    ->where('type', 'production_sell')
                                    ->where('mfg_parent_production_purchase_id', $production_purchase->id)
                                    ->with([
                                        'sell_lines', 
                                        'sell_lines.variations', 
                                        'sell_lines.variations.product_variation', 
                                        'sell_lines.variations.product', 
                                        'sell_lines.sub_unit',
                                        'sell_lines.sell_line_purchase_lines',
                                        'sell_lines.sell_line_purchase_lines.purchase_line'
                                    ])
                                    ->first();
                                    
        $purchase_line = $production_purchase->purchase_lines[0];
        // dd($production_sell);
        
        
        $base_unit_multiplier = !empty($purchase_line->sub_unit) ? $purchase_line->sub_unit->base_unit_multiplier : 1;
        $quantity = $purchase_line->quantity / $base_unit_multiplier;
        $quantity_wasted = 0;
        $unit_name = !empty($purchase_line->sub_unit) ?  $purchase_line->sub_unit->short_name : $purchase_line->variations->product->unit->short_name;
        if (!empty($production_purchase->mfg_wasted_units)) {
            $quantity_wasted = $production_purchase->mfg_wasted_units;
            $quantity += $quantity_wasted;
        }

        $actual_quantity = $quantity * $base_unit_multiplier;

        $ingredients = [];
        $ingredient_groups = [];
        $total_ingredients_price = 0;
        //Format sell lines
        foreach ($production_sell->sell_lines as $sell_line) {
            $variation = $sell_line->variations;
            $sell_line_qty = empty($sell_line->sub_unit) ? $sell_line->quantity : $sell_line->quantity / $sell_line->sub_unit->base_unit_multiplier;
            $unit = empty($sell_line->sub_unit) ? $variation->product->unit->short_name : $sell_line->sub_unit->short_name;

            $line_total_price = $variation->dpp_inc_tax * $sell_line->quantity;
            $total_ingredients_price += $line_total_price;

            $waste_percent = !empty($sell_line->mfg_waste_percent) ? $sell_line->mfg_waste_percent : 0;
            $wasted_qty = $this->moduleUtil->calc_percentage($sell_line_qty, $waste_percent);
            $final_quantity = $sell_line_qty - $wasted_qty;


            $lot_numbers = [];

            foreach ($sell_line->sell_line_purchase_lines as $slpl) {
                $lot_number = !empty($slpl->purchase_line->lot_number) ? $slpl->purchase_line->lot_number : '';
                if (!empty($slpl->purchase_line->exp_date) ) {
                    $lot_number .= ' - ' . $this->moduleUtil->format_date($slpl->purchase_line->exp_date);
                }

                if ($lot_number != '') {
                    $lot_numbers[] = $lot_number;
                }
            }

            if (empty($sell_line->mfg_ingredient_group_id)) {
                $ingredients[] = [
                    'dpp_inc_tax' => $variation->dpp_inc_tax,
                    'quantity' => $sell_line_qty,
                    'full_name' => $variation->full_name,
                    'id' => $variation->id,
                    'unit' => $unit,
                    'process'=>'yes',
                    'allow_decimal' => $variation->product->unit->allow_decimal,
                    'variation' => $variation,
                    'enable_stock' => $variation->product->enable_stock,
                    'total_price' => $line_total_price,
                    'waste_percent' =>  $waste_percent,
                    'final_quantity' => $final_quantity,
                    'lot_numbers' => implode(', ', $lot_numbers)
               ];
            } else {
                if (!isset($ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_name'])) {
                    $i_group = MfgIngredientGroup::find($sell_line->mfg_ingredient_group_id);
                    $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_name'] = $i_group->name;
                    $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_description'] = $i_group->description;
                }
                $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_ingredients'][] = [
                    'dpp_inc_tax' => $variation->dpp_inc_tax,
                    'quantity' => $sell_line_qty,
                    'full_name' => $variation->full_name,
                    'id' => $variation->id,
                    'unit' => $unit,
                    'process'=>'yes',
                    'allow_decimal' => $variation->product->unit->allow_decimal,
                    'variation' => $variation,
                    'enable_stock' => $variation->product->enable_stock,
                    'total_price' => $line_total_price,
                    'waste_percent' =>  $waste_percent,
                    'final_quantity' => $final_quantity,
                    'lot_numbers' => implode(', ', $lot_numbers)
                ];
            }
        }
        
        $total_production_cost = 0;
        if (!empty($production_purchase->mfg_production_cost)) {
            $total_production_cost = $production_purchase->mfg_production_cost;
            if ($production_purchase->mfg_production_cost_type == 'percentage') {
                $total_production_cost = $this->transactionUtil->calc_percentage($total_ingredients_price, $production_purchase->mfg_production_cost);
            } elseif ($production_purchase->mfg_production_cost_type == 'per_unit') {
                $total_production_cost = $production_purchase->mfg_production_cost * $quantity;
            }
            
        }

        return view('manufacturing::production.show')->with(compact('production_purchase', 'production_sell', 'purchase_line', 'ingredients', 'unit_name', 'quantity', 'quantity_wasted', 'actual_quantity', 'total_production_cost', 'ingredient_groups'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        $production_purchase = Transaction::where('business_id', $business_id)
                                    ->where('type', 'production_purchase')
                                    ->with(['purchase_lines', 'purchase_lines.variations', 'purchase_lines.variations.product_variation', 'purchase_lines.variations.product', 'media'])
                                    ->findOrFail($id);

        //Finalized production should not be editable
        if ($production_purchase->mfg_is_final == 1) {
            $output = ['success' => 0,
                        'msg' => __('finalized products not editable')
                    ];
            return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
        }

        $production_sell = Transaction::where('business_id', $business_id)
                                    ->where('type', 'production_sell')
                                    ->where('mfg_parent_production_purchase_id', $production_purchase->id)
                                    ->with(['sell_lines', 'sell_lines.variations', 'sell_lines.variations.product_variation', 'sell_lines.variations.product', 'sell_lines.variations.product.unit'])
                                    ->first();
        $purchase_line = $production_purchase->purchase_lines[0];

        $recipe = MfgRecipe::where('variation_id', $purchase_line->variation_id)
                        ->first();

        $base_unit_multiplier = !empty($purchase_line->sub_unit) ? $purchase_line->sub_unit->base_unit_multiplier : 1;
        $quantity = $purchase_line->quantity / $base_unit_multiplier;
        $quantity_wasted = 0;
        
        if (!empty($production_purchase->mfg_wasted_units)) {
            $quantity_wasted = $production_purchase->mfg_wasted_units;
            $quantity += $quantity_wasted;
        }

        $actual_quantity = $quantity * $base_unit_multiplier;

        $sub_units = $this->moduleUtil->getSubUnits($business_id, $purchase_line->variations->product->unit->id);
        $unit_name = $purchase_line->variations->product->unit->short_name;
        $sub_unit_id = $purchase_line->sub_unit_id;

        $ingredients = [];
        $total_ingredients_price = 0;
        foreach ($production_sell->sell_lines as $sell_line) {
            $variation = $sell_line->variations;

            $line_sub_units = $this->moduleUtil->getSubUnits($business_id, $variation->product->unit->id);
            $is_line_sub_unit = false;
            $line_sub_unit_id = null;
            $multiplier = 1;
            $line_unit_name = $variation->product->unit->short_name;
            $allow_decimal = $variation->product->unit->allow_decimal;
            if (!empty($line_sub_units)) {
                foreach ($line_sub_units as $key => $value) {
                    if (!empty($sell_line->sub_unit_id) && $sell_line->sub_unit_id == $key) {
                        $line_sub_unit_id = $sell_line->sub_unit_id;
                        $multiplier = $value['multiplier'];
                        $allow_decimal = $value['allow_decimal'];
                        $line_unit_name = $value['name'];
                    }
                }
                $is_line_sub_unit = true;
            }

            $unit_quantity = $sell_line->quantity / $actual_quantity;

            $line_total_price = $variation->dpp_inc_tax * $sell_line->quantity;
            $total_ingredients_price += $line_total_price;

            $waste_percent = !empty($sell_line->mfg_waste_percent) ? $sell_line->mfg_waste_percent : 0;
            $wasted_qty = $this->moduleUtil->calc_percentage($sell_line->quantity, $waste_percent);
            $final_quantity = ($sell_line->quantity - $wasted_qty) / $multiplier;

            $dust_percent = !empty($ingredient_variation->dust_percent) ? $ingredient_variation->dust_percent : 0;
            $dust = !empty($ingredient_variation->dust) ? $ingredient_variation->dust : 0;

            $bran_percent = !empty($ingredient_variation->bran_percent) ? $ingredient_variation->bran_percent : 0;
            $bran = !empty($ingredient_variation->bran) ? $ingredient_variation->bran : 0;

            $gem_percent = !empty($ingredient_variation->gem_percent) ? $ingredient_variation->gem_percent : 0;
            $gem = !empty($ingredient_variation->gem) ? $ingredient_variation->gem : 0;

            $unga = !empty($ingredient_variation->unga) ? $ingredient_variation->unga : 0;

            $fortified_unga = !empty($ingredient_variation->fortified_unga) ? $ingredient_variation->fortified_unga : 0;

            $milling_quantity = !empty($ingredient_variation->milling_quantity) ? $ingredient_variation->milling_quantity : 0;
            $fortify_quantity = !empty($ingredient_variation->fortify_quantity) ? $ingredient_variation->fortify_quantity : 0;



            $ig_name = '';
            if (!empty($sell_line->mfg_ingredient_group_id)) {
                $i_group = MfgIngredientGroup::find($sell_line->mfg_ingredient_group_id);
                $ig_name = !empty($i_group->name) ? $i_group->name : '';
            }
            $ingredients[] = [
                'dpp_inc_tax' => $variation->dpp_inc_tax,
                'quantity' => $sell_line->quantity / $multiplier,
                'full_name' => $variation->full_name,
                'variation_id' => $variation->id,
                'unit' => $line_unit_name,
                'process'=>'yes',
                'allow_decimal' => $allow_decimal,
                'variation' => $variation,
                'enable_stock' => $variation->product->enable_stock,
                'is_sub_unit' => $is_line_sub_unit,
                'sub_units' => $line_sub_units,
                'sub_unit_id' => $line_sub_unit_id,
                'multiplier' => $multiplier,
                'unit_quantity' => $unit_quantity,
                'total_price' => $line_total_price,
                'waste_percent' =>  $waste_percent,
                'final_quantity' => $final_quantity,
                'id' => $sell_line->id,
                'mfg_ingredient_group_id' => $sell_line->mfg_ingredient_group_id,
                'ingredient_group_name' => $ig_name,
                'milling_quantity' => $milling_quantity,
                'dust_percent' =>  $dust_percent,
                'dust' =>  $dust,
                'gem_percent' =>  $gem_percent,
                'gem' =>  $gem,
                'bran_percent' =>  $bran_percent,
                'bran' =>  $bran,
                'unga' =>  $unga,
                'fortified_unga' =>  $fortified_unga,
                'fortify_quantity' => $fortify_quantity,
                
           ];
        }

        $total_production_cost = 0;
        if (!empty($recipe->extra_cost)) {
            $total_production_cost = $this->transactionUtil->calc_percentage($total_ingredients_price, $recipe->extra_cost);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $variation_name = $purchase_line->variations->product->name;
        if ($purchase_line->variations->product->type == 'variable') {
            $variation_name .= ' - ' .
            $purchase_line->variations->product_variation->name .
            ' - ' . $purchase_line->variations->name;
        }
        $variation_name .= ' (' . $purchase_line->variations->sub_sku . ')';
        $recipe_dropdown = [$purchase_line->variation_id => $variation_name];

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $manufacturing_settings = $this->mfgUtil->getSettings($business_id);

        return view('manufacturing::production.edit')->with(compact('production_purchase', 'production_sell', 'business_locations', 'recipe_dropdown', 'ingredients', 'business_details', 'pos_settings', 'sub_units', 'quantity', 'quantity_wasted', 'actual_quantity', 'recipe', 'unit_name', 'sub_unit_id', 'total_production_cost', 'manufacturing_settings'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'transaction_date' => 'required',
                'location_id' => 'required',
                'final_total' => 'required'
            ]);

            //Create Production purchase
            $transaction_data = $request->only([ 'ref_no', 'transaction_date', 'location_id', 'final_total']);

            $is_final = !empty($request->input('finalize')) ? 1 : 0;

            $manufacturing_settings = $this->mfgUtil->getSettings($business_id);

            $transaction_data['status'] = $is_final ? 'received' : 'pending';
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);
            $transaction_data['final_total'] = $this->productUtil->num_uf($transaction_data['final_total']);

            $variation_id = $request->input('variation_id');
            $variation = Variation::where('id', $variation_id)
                                ->with(['product'])
                                ->first();
            $final_total = $request->input('final_total');
            $quantity = $request->input('quantity');
            $waste_units = $this->productUtil->num_uf($request->input('mfg_wasted_units'));
            $uf_qty = $this->productUtil->num_uf($quantity);
            if (!empty($waste_units)) {
                $new_qty = $uf_qty - $waste_units;
                $uf_qty = $new_qty;
                $quantity = $this->productUtil->num_f($new_qty);
            }

            $final_total_uf = $this->productUtil->num_uf($final_total);

            $unit_purchase_line_total = $final_total_uf / $uf_qty;

            $unit_purchase_line_total_f = $this->productUtil->num_f($unit_purchase_line_total);

            $transaction_data['mfg_wasted_units'] = $waste_units;
            $transaction_data['mfg_production_cost'] = $this->productUtil->num_uf($request->input('production_cost'));
            $transaction_data['mfg_production_cost_type'] = $request->input('mfg_production_cost_type');
            $transaction_data['mfg_is_final'] = $is_final;
            $purchase_line_data = [
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'product_id' => $variation->product_id,
                'product_unit_id' => $variation->product->unit_id,
                'pp_without_discount' => $unit_purchase_line_total_f,
                'discount_percent' => 0,
                'purchase_price' => $unit_purchase_line_total_f,
                'purchase_price_inc_tax' => $unit_purchase_line_total_f,
                'item_tax' => 0,
                'purchase_line_tax_id' => null,
                'mfg_date' => $this->transactionUtil->format_date($transaction_data['transaction_date'])
            ];

            if (request()->session()->get('business.enable_lot_number') == 1) {
                $purchase_line_data['lot_number'] = $request->input('lot_number');
            }

            if (request()->session()->get('business.enable_product_expiry') == 1) {
                $purchase_line_data['exp_date'] = $request->input('exp_date');
            }

            if (!empty($request->input('sub_unit_id'))) {
                $purchase_line_data['sub_unit_id'] = $request->input('sub_unit_id');
            }

            $transaction = Transaction::where('business_id', $business_id)
                                    ->where('type', 'production_purchase')
                                    ->findOrFail($id);

            //Finalized production should not be editable
            if ($transaction->mfg_is_final == 1) {
                $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
                return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
            }
            DB::beginTransaction();

            $transaction->update($transaction_data);

            Media::uploadMedia($business_id, $transaction, $request, 'documents', false);

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

            $update_product_price = !empty($manufacturing_settings['enable_updating_product_price']) && $is_final ? true : false;

            $this->productUtil->createOrUpdatePurchaseLines($transaction, [$purchase_line_data], $currency_details, $update_product_price);

            //Adjust stock over selling if found
            $this->productUtil->adjustStockOverSelling($transaction);

            $transaction_sell_data = [
                'transaction_date' => $transaction->transaction_date,
                'status' => $is_final ? 'final' : 'draft',
                'payment_status' => 'due',
                'final_total' => $transaction->final_total
            ];

            //Create Sell Transfer transaction
            $production_sell = Transaction::where('business_id', $business_id)
                                    ->where('type', 'production_sell')
                                    ->with('sell_lines', 'sell_lines.product', 'sell_lines.variations')
                                    ->where('mfg_parent_production_purchase_id', $transaction->id)
                                    ->first();

            $production_sell->update($transaction_sell_data);

            $sell_lines = [];
            $ingredient_quantities = $request->input('ingredients');

            foreach ($production_sell->sell_lines as $sell_line) {
                $variation = $sell_line->variations;

                $line_sub_unit_id = !empty($ingredient_quantities[$sell_line->id]['sub_unit_id']) ?
                                $ingredient_quantities[$sell_line->id]['sub_unit_id'] : null;
                $line_multiplier = 1;
                if (!empty($line_sub_unit_id)) {
                    $sub_units = $this->productUtil->getSubUnits($business_id, $sell_line->product->unit_id);
                    $line_multiplier = !empty($sub_units[$line_sub_unit_id]['multiplier']) ? $sub_units[$line_sub_unit_id]['multiplier'] : 1;
                }

                $mfg_waste_percent = !empty($ingredient_quantities[$sell_line->id]['mfg_waste_percent']) ? $this->productUtil->num_uf($ingredient_quantities[$sell_line->id]['mfg_waste_percent']) : 0;

                $mfg_ingredient_group_id = !empty($ingredient_quantities[$sell_line->id]['mfg_ingredient_group_id']) ? $ingredient_quantities[$sell_line->id]['mfg_ingredient_group_id'] : null;

                $sell_lines[] = [
                    'product_id' => $variation->product_id,
                    'variation_id' => $variation->id,
                    'quantity' => $this->productUtil->num_uf($ingredient_quantities[$sell_line->id]['quantity']),
                    'item_tax' => 0,
                    'tax_id' => null,
                    'unit_price' => $variation->dpp_inc_tax * $line_multiplier,
                    'unit_price_inc_tax' => $variation->dpp_inc_tax * $line_multiplier,
                    'enable_stock' => $sell_line->product->enable_stock,
                    'product_unit_id' => $variation->product->unit_id,
                    'sub_unit_id' => $line_sub_unit_id,
                    'base_unit_multiplier' => $line_multiplier,
                    'mfg_waste_percent' => $mfg_waste_percent,
                    'mfg_ingredient_group_id' => $mfg_ingredient_group_id
                ];
            }

            if (!empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($production_sell, $sell_lines, $transaction->location_id, false, 'draft', ['mfg_waste_percent' => 'mfg_waste_percent', 'mfg_ingredient_group_id' => 'mfg_ingredient_group_id']);
            }

            if ($transaction_sell_data['status'] == 'final') {
                foreach ($sell_lines as $sell_line) {
                    if ($sell_line['enable_stock']) {
                        $line_qty = $sell_line['quantity'] * $sell_line['base_unit_multiplier'];
                        $this->productUtil->decreaseProductQuantity(
                            $sell_line['product_id'],
                            $sell_line['variation_id'],
                            $production_sell->location_id,
                            $line_qty
                        );
                    }
                }

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                            'accounting_method' => $request->session()->get('business.accounting_method'),
                            'location_id' => $production_sell->location_id,
                            'pos_settings' => $request->session()->get('business.pos_settings')
                        ];
                $this->transactionUtil->mapPurchaseSell($business, $production_sell->sell_lines, 'production_purchase');
            }
            
            DB::commit();
            
            $output = ['success' => 1,
                            'msg' => __('lang_v1.updated_success')
                        ];
        } catch (Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }

        return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $transaction = Transaction::where('id', $id)
                            ->where('business_id', $business_id)
                            ->where('type', 'production_purchase')
                            // ->where('mfg_is_final', 0)
                            ->delete();
                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.deleted_success')
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }

            return $output;
        }
    }

    /**
     * Retrives data for manufacturing report.
     * @return Response
     */
    public function getManufacturingReport()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            $location_id = request()->get('location_id');

            $production_totals = $this->mfgUtil->getProductionTotals($business_id, $location_id, $start_date, $end_date);

            $total_sold = $this->mfgUtil->getTotalSold($business_id, $location_id, $start_date, $end_date);
            

            $output['total_production'] = $production_totals['total_production'];
            $output['total_production_cost'] = $production_totals['total_production_cost'];
            $output['total_sold'] = $total_sold;

            return $output;
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('manufacturing::production.report')->with(compact('business_locations'));
    }
}
