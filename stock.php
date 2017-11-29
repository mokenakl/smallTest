<?php
require_once 'Medoo.php';
require_once 'ShopifyClient.php';
require_once './classes/PHPExcel.php';
require_once './classes/PHPExcel/Writer/Excel5.php';
// error_reporting(E_ALL^E_WARNING);
header('content-type:text/html;charset=utf-8');
class Stock{
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

    /***
    *初始化库存
    */
	public function actionindex(){
        $database = $this->database;
        $product_data = $database->debug()->select("products_variants",[
            "[><]products_images" => ["product_id" => "product_id"]
        ],[
            "products_variants.product_id","products_variants.barcode","products_images.src"
        ],[
            "GROUP" => "products_variants.barcode",
            "LIMIT" => 50
        ]);
        // if( is_array($product_data) ){
        //     foreach($product_data as $k => $prodVal){
        //         var_dump($prodVal);
        //         if( !empty($prodVal['barcode']) ){
        //             $proData = array();
        //             $proData['barcode'] = $prodVal['barcode'];
        //             $proData['product_image'] = $prodVal['src'];
        //             $database->insert('stock_init',$proData);
        //         }
        //         else{
        //             echo "产品ID=".$prodVal['product_id']."<br>";
        //         }
        //     }
        // }
    }

    /***
    *导出库存
    */
    public function actionToExcel(){
        $database = $this->database;
        $xlsName  = "Order";
        $xlsCell  = array(
            array('barcode','产品barcode'),
            array('product_image','产品图片'),
            array('qty','初始库存数量'),               
        );
        $xlsData = $database->select("stock_init",["barcode","product_image","qty"],[]);
        $this->exportExcel($xlsName,$xlsCell,$xlsData);
    }

    public function exportExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = "库存".date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
       
        $objPHPExcel = new PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]); 
        } 
        for($i=0;$i<$dataNum;$i++){
          for($j=0;$j<$cellNum;$j++){
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2), $expTableData[$i][$expCellName[$j][0]]);
          }             
        }  
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=".$fileName.".xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  
        $objWriter->save('php://output'); 
        exit;   
    }

    /***
    *导入库存
    */
    public function upExcel(){
        $database = $this->database;
        $files = $_FILES['myfile'];
        if( $files['error'] == 4 ){
            echo "<script>alert('您未选择表格');history.go(-1);</script>";
        }

        if( $files['type'] != 'application/vnd.ms-excel' ){
            echo "<script>alert('上传失败，只能上传excel2003的xls格式!');history.go(-1);</script>";
        }

        if( is_uploaded_file($files['tmp_name']) ){
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            //接收存在缓存中的excel表格
            $filename = $_FILES['myfile']['tmp_name'];
            $objPHPExcel = $objReader->load($filename); //$filename可以是上传的表格，或者是指定的表格
            $sheet = $objPHPExcel->getSheet(0); 
            $highestRow = $sheet->getHighestRow(); // 取得总行数 
            $highestColumn = $sheet->getHighestColumn(); // 取得总列数
            for($j=2;$j<=$highestRow;$j++){
                $a = $objPHPExcel->getActiveSheet()->getCell("A".$j)->getValue();//获取A列的值
                // $b = $objPHPExcel->getActiveSheet()->getCell("B".$j)->getValue();
                $c = $objPHPExcel->getActiveSheet()->getCell("C".$j)->getValue();//获取C列的值
                $searchRes = $database->select("stock_init","*",["barcode"=>$a]);
                if( $searchRes ) $database->update("stock_init",["qty"=> $c],["barcode"=>$a]);                
            }
        }
    }

    /***
    *库存订单
    */
    public function actionStockOrder(){
        $database = $this->database;
        $sql = "select ord.order_number as ordernumber,ord.created_at as date,item.quantity,va.barcode from orders as ord inner join orders_line_items as item on ord.ori_id = item.relevance_id inner join products_variants as va on item.variant_id = va.ori_id where ord.created_at like '%2017-11-01%'";
        $orderDt = $database->query( $sql )->fetchAll( \PDO::FETCH_ASSOC );
        if( is_array($orderDt) ){
            foreach($orderDt as $i => $item){
                $stockDt = array();
                foreach($item as $k => $val){
                    $stockDt[$k] = $val;
                }
                $stockDt['date'] = strtotime($item['date']);
                $searchRes = $database->select("stock_order","*",$stockDt);
                if( !$searchRes ) $database->insert('stock_order',$stockDt);
            }
        }
    } 
}

$obj = new Stock();
// $obj->actionindex();
// $obj->actionToExcel();
// $obj->actionStockOrder();
$obj->upExcel();
?>
