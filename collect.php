<?php
require_once 'Medoo.php';
require_once 'ShopifyClient.php';
error_reporting(E_ALL^E_WARNING);
header('content-type:text/html;charset=utf-8');
class Collect{
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

    public function actionCollect(){
        set_time_limit(0);      
        $shopifyClient = new ShopifyClient($this->shop, $this->token, $this->api_key, $this->secret);
        $count = $shopifyClient->call("GET","/admin/collects/count.json");
        $page_count = $count > 0 ? ceil( $count / 50 ):1;
        echo $count;
        // for($i = 1; $i <= 100; $i++){
        //     $collectDt = $shopifyClient->call("GET","/admin/collects.json?page=".$i);
        //     $this->optionCollect( $collectDt );
        // }
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
                }
                var_dump($data_coll);
                if( !$searchRes )   $database->insert("collect",$data_coll);
            }
        }
    }
}

$obj = new Collect();
$obj->actionCollect();
?>
