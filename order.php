<?php
require_once 'Medoo.php';
require_once 'ShopifyClient.php';
error_reporting(E_ALL^E_WARNING);
header('content-type:text/html;charset=utf-8');
class Order{
    protected $database = null;
    protected $api_key = '48e702038776ebaa1f88a32ff7fb12cb';
    protected $secret = 'd6cf3d2d01aacefd987b86f582e57ac2';
    protected $token = "a364fe982e0511142f86a8b60e28fc88";
    protected $shop = "theeasylady.myshopify.com";
    public function __construct(){
        $database = new Medoo([
            'database_type' => 'mysql',
            'database_name' => 'free-shipping',
            'server' => 'localhost',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ]);
        $this->database = $database;
    }

    public function actionProduct(){
        $shopifyClient = new ShopifyClient($this->shop, $this->token, $this->api_key, $this->secret);
        $count  = $shopifyClient->call("GET","/admin/products/count.json");
        $page_count = $count > 0 ? ceil( $count / 50 ):1;
        for($i = 1; $i <= $page_count; $i++){
            $productDt  = $shopifyClient->call("GET","/admin/products.json?page=".$i);
            $this->optionProduct( $productDt );
        }
    }

    public function optionProduct( $productDt ){
        $database = $this->database;
        if( is_array($productDt) ){
            $productFiled = array("title","body_html","vendor","product_type","handle","updated_at","tags");
            $data_prod = array();
            foreach($productDt as $prodkey => $prodvalue){
                if( isset($prodvalue['variants']) || isset($prodvalue['images']) || isset($prodvalue['image']) ){
                    $prodvalue['image'] = array($prodvalue['image']);
                    $dbFieldDefine = array(
                        "variants" => array("product_id","title","price","sku","updated_at","barcode"," image_id"),
                        "images"   => array("product_id","src"),
                        "image"    => array("product_id","src"),
                    );
                    foreach($dbFieldDefine as $dbTable => $itemFields){
                        foreach($prodvalue[$dbTable] as $index => $fieldvalue){
                            $data_item = array();
                            $data_item['createtime'] = time();
                            $data_item['ori_id'] = $fieldvalue['id'];
                            $findRes = $database->select("products_".$dbTable,"*",["ori_id" => $fieldvalue['id'] ]);
                            foreach($itemFields as $i => $fieldVal){
                                if( isset($fieldvalue[$fieldVal]) ){
                                    $data_item[$fieldVal] = $fieldvalue[$fieldVal];
                                }                                  
                            }
                            if( !$findRes ){
                                $database->insert("products_".$dbTable,$data_item);
                            }
                        }
                    }
                }

                $data_prod['ori_id'] = $prodvalue['id'];
                $data_prod['createtime'] = time();
                foreach($productFiled as $index => $item){
                    $data_prod[$item] = $prodvalue[$item];                 
                }
                $searchRes = $database->select("products","*",["ori_id" => $prodvalue['id'] ]);
                if( !$searchRes )  $database->insert("products",$data_prod);
            }
        }
    }

    public function actionorders(){
        set_time_limit(0);      
        $shopifyClient = new ShopifyClient($this->shop, $this->token, $this->api_key, $this->secret);
        $count = $shopifyClient->call("GET","/admin/orders/count.json?status=any");
        $page_count = $count > 0 ? ceil( $count / 50 ):1;
        for($i = 1; $i <= $page_count; $i++){
            $ordersDt = $shopifyClient->call("GET","/admin/orders.json?status=any&page=".$i);
            $this->optionOrder( $ordersDt );
        }
    }

    public function optionOrder( $ordersDt ){
        $database = $this->database;
        if( is_array($ordersDt) ){
            foreach ($ordersDt as $orderkey => $ordervalue) {
                if( isset($ordervalue['line_items']) || isset($ordervalue['shipping_lines']) || isset($ordervalue['shipping_address']) ){
                    $ordervalue['shipping_address'] = array( $ordervalue['shipping_address'] );
                    $dbFieldDefine = array(
                        "line_items" => array('variant_id','title','quantity','price','grams','sku','variant_title','vendor','fulfillment_service','product_id','requires_shipping','taxable','gift_card','name','variant_inventory_management','product_exists','fulfillable_quantity','total_discount','fulfillment_status'),
                        "shipping_lines" => array('title','price','code','source','phone','requested_fulfillment_service_id','delivery_category','carrier_identifier','discounted_price'),
                        "shipping_address" => array('first_name','address1','phone','city','zip','province','country','last_name','address2','company','latitude','longitude','name','country_code','province_code'),
                    );
                    foreach($dbFieldDefine  as $dbTable => $itemFields){
                        foreach ($ordervalue[$dbTable] as $index => $fieldvalue) {
                            $data_item = array();
                            $data_item['createtime'] = time();
                            $data_item['relevance_id'] = isset($ordervalue['id']) ? $ordervalue['id']:'';
                            if( $dbTable != 'shipping_address' ){
                                $data_item['ori_id'] = isset($fieldvalue['id']) ? $fieldvalue['id']:'';
                                $findRes = $database->select("orders_".$dbTable,"*",["ori_id" => $fieldvalue['id']]);
                            }
                            else{
                                $findRes = $database->select("orders_".$dbTable,"*",["relevance_id" => $ordervalue['id'] ]);
                            }
                            foreach ($itemFields as $i => $fieldVal) {
                                if( isset($fieldvalue[$fieldVal]) ){
                                    $data_item[$fieldVal] = $fieldvalue[$fieldVal];
                                }
                            }
                            if( !$findRes ) $database->insert("orders_".$dbTable,$data_item);
                        }
                    }
                }
                $orderfile = array('email','closed_at','created_at','updated_at','number','note','token','gateway','test','total_price','subtotal_price','total_weight','total_tax','taxes_included','currency','financial_status','confirmed','total_discounts','total_line_items_price','cart_token','buyer_accepts_marketing','name','referring_site','landing_site','cancelled_at','cancel_reason','total_price_usd','checkout_token','reference','user_id','location_id','source_identifier','source_url','processed_at','device_id','phone','customer_locale','app_id','browser_ip','landing_site_ref','order_number','processing_method','checkout_id','source_name','fulfillment_status','contact_email','order_status_url');
                $data_ord = array();
                $data_ord['createtime'] = time();
                $data_ord['ori_id'] = isset($ordervalue['id']) ? $ordervalue['id']:'';
                $searchRes = $database->select("orders","*",["ori_id" => $ordervalue['id'] ]);
                foreach ($orderfile as $orderfilekey => $orderfilevalue) {
                    if( isset($ordervalue[$orderfilevalue]) ){
                        $data_ord[$orderfilevalue] = $ordervalue[$orderfilevalue];
                    }
                    $data_ord['created_at_time'] = strtotime($ordervalue['created_at']);
                }
                if( !$searchRes )   $database->insert("orders",$data_ord);
            }
        }
    }

    public function actionCollect(){
        set_time_limit(0);      
        $shopifyClient = new ShopifyClient($this->shop, $this->token, $this->api_key, $this->secret);
        $count = $shopifyClient->call("GET","/admin/collects/count.json");
        $page_count = $count > 0 ? ceil( $count / 50 ):1;
        for($i = 1; $i <= 100; $i++){
            $collectDt = $shopifyClient->call("GET","/admin/collects.json?page=".$i);
            $this->optionCollect( $collectDt );
        }
    }

    public function optionCollect( $collectDt ){
        $database = $this->database;
        if( is_array($collectDt) ){
            foreach ($collectDt as $collectkey => $collectvalue) {
                $collectfile = array('ori_id','collection_id','product_id','featured','created_at','updated_at','position','sort_value');
                $data_coll = array();
                $data_coll['createtime'] = time();
                $data_coll['ori_id'] = isset($collectvalue['id']) ? $collectvalue['id']:'';
                $searchRes = $database->select("collect","*",["ori_id" => $collectvalue['id'] ]);
                foreach ($collectfile as $collectfilekey => $collectfilevalue) {
                    if( isset($collectvalue[$collectfilevalue]) ){
                        $data_coll[$collectfilevalue] = $collectvalue[$collectfilevalue];
                    }
                    // $data_coll['created_at_time'] = strtotime($collectvalue['created_at']);
                }
                var_dump($data_coll);
                if( !$searchRes )   $database->insert("collect",$data_coll);
            }
        }
    }
}

$obj = new Order();
// $obj->actionorders();
// $obj->actionProduct();
$obj->actionCollect();
?>
