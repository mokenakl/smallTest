<?php
namespace Home\Controller;
use Think\Controller;
class ChicnicoController extends Controller{
  protected $api_key = 'e8cf4a69bc4c0d583a26f00f2353206b';
  protected $secret  = 'e3586be915d1f96371621a6d85879a78';
  protected $token   = '4fdb6b9a55c5754125519ac1e9127d14';
  protected $shop    = "nanjing.myshopify.com";
  public function index(){
  	vendor("Others.ShopifyClient");
    $shopifyClient = new \ShopifyClient($this->shop, $this->token, $this->api_key, $this->secret);
    $db2 = M("db2","","DB_CONFIG_YVETTE");
    $src_data = $db2->table("yvette_chicnico")->limit("0,10")->select();
    if( is_array($src_data) ){
    	foreach ($src_data as $ko => $chicnicoSrcVal) {    	
		    $product_data = shell_exec("curl https://www.chicnico.com/".$chicnicoSrcVal['prod_href'].".js");
		    $decode_data  = json_decode($product_data,true);
		    $data = array();
		    if( is_array($decode_data) ){
				$data['title'] = str_replace('Chicnico','',$decode_data['title']);
				preg_match_all("#Size(.*)#is",$decode_data['description'],$matches);
				$matches_str="<div style='font-size:13pX;'>".$matches[0][0]."</div>";
				$data['body_html'] = $matches_str;
				$data['handle'] = $decode_data['handle'];
				$data['tags'] = $decode_data['tags'];
				if( is_array($decode_data['variants']) || is_array($decode_data['options']) ){
					$fileds = array(
						"variants" => array("title","option1","option2","option3","sku","requires_shipping","taxable","price","weight","compare_at_price","inventory_quantity","inventory_management","inventory_policy","barcode"),
						"options" => array("name","position","values"),
					);
					$handle = array();	
					foreach ($fileds as $filedname => $fileVal ) {
							foreach($decode_data[$filedname] as $key => $value){
								$variant_data = array();
								foreach($fileVal as $i => $item){
									if( isset($value[$item]) ){
										$variant_data[$item] = $value[$item];
									}						
								}
								$handle[$filedname][$key] = $variant_data;
							}
					}
					$data['variants'] = $handle['variants'];
					$data['options'] = $handle['options'];			
				}
				if( is_array($decode_data['images']) ){
					$data['images'] = array();
					foreach($decode_data['images'] as $img => $srcVal){
						$data['images'][$img]['src'] = "http:".$srcVal;
					}
				}
				$count  = $shopifyClient->call("POST","/admin/products.json",array("product" => $data));
				echo $count['id']."<br>";
		    }
		}
    }
    
  }

  public function redirectUrl(){
  	vendor("Others.ShopifyClient");
    $shopifyClient = new \ShopifyClient($this->shop, $this->token, $this->api_key, $this->secret);
    $authorizeUrl = $shopifyClient->getAuthorizeUrl("read_products, write_products","http://139.162.237.182/yvette_order/Home/Chicnico/index");
    header("Location:".$authorizeUrl);
  }


}