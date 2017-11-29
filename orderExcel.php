<?php
require_once 'Medoo.php';
require_once './classes/PHPExcel.php';
require_once './classes/PHPExcel/Writer/Excel5.php';
// error_reporting(E_ALL^E_WARNING);
header('content-type:text/html;charset=utf-8');
class OrderExcel{
	protected $database = null;
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

    public function index(){
        $database = $this->database;
        $xlsName  = "Order";
        $xlsCell  = array(
            array('code','客户代码'),
            array('createtime','到货日期'),
            array('numberPeople','客户单号'),
            array('number','服务商单号'),
            array('saleproduce','销售产品'),
            array('country_code','目的国家'),
            array('product_num','件数'),
            array('weight','重量'),
            array('goodstype','货物类型'),
            array('serve','附加服务'),
            array('company','收件人公司名'),
            array('name','收件人姓名'),
            array('province','收件人省'),
            array('city','收件人城市'),
            array('zip','收件人邮编'),
            array('address','收件人地址'),
            array('doorplate','收件人门牌'),
            array('phone','收件人电话'),   
            array('faxes','收件人传真'),   
            array('toemail','收件人邮箱'),   
            array('paperstype','收件人证件类型'),   
            array('papersphone','收件人证件号码'),   
            array('papersbetween','收件人证件有效期间'),  
            array('english','英文申报品名'),   
            array('chinese','中文申报品名'),   
            array('declarenum','申报数量'),   
            array('declareprice','申报总价'),   
            array('fromcompany','发件人公司名'),   
            array('fromname','发件人姓名'),   
            array('fromcountry','发件人国家'),   
            array('fromprovince','发件人省'),   
            array('fromcity','发件人城市'),   
            array('fromemail','发件人邮编'),   
            array('fromaddr','发件人地址'),   
            array('fromphone','发件人电话'),   
            array('fromfaxes','发件人传真'),   
            array('remark','运单备注'),   
            array('goodsale','商品销售网'),   
            array('goodsnum','海关货物编号'),   
            array('servechannel','服务渠道'),   
            array('fromcompanyname','发件人中文公司名称'),   
            array('texture','材质'),   
            array('ruletype','规格型号'),   
        );
        $xlsData = $database->query("select od.name as number,addr.country_code,addr.name,addr.province,addr.city,addr.zip,addr.address1,addr.address2,addr.phone FROM `orders` as od inner join `orders_shipping_address` as addr on  od.ori_id = addr.relevance_id")->fetchAll();

        foreach ($xlsData as $k => $v){
            $joinaddr = empty($v['address2']) ? '':",".$v['address2'];
            $xlsData[$k]['address'] = $v['address1'].$joinaddr;
            $replacenum = str_replace("#","DN099",$v['number']);
            $xlsData[$k]['number'] = $replacenum;
            $xlsData[$k]['code'] = "NJ0108";
            $xlsData[$k]['createtime'] = date("Y/m/d");
            $xlsData[$k]['numberPeople'] = $replacenum;
            $xlsData[$k]['saleproduce'] = '华东-JCEX美国专线-(小货)DHL';
            $xlsData[$k]['product_num'] = 1;
            $xlsData[$k]['weight'] = '';
            $xlsData[$k]['goodstype'] = '袋子';
            $xlsData[$k]['serve'] = '';
            $xlsData[$k]['doorplate'] = '';
            $xlsData[$k]['toemail'] = '';
            $xlsData[$k]['paperstype'] = '';
            $xlsData[$k]['papersphone'] = '';
            $xlsData[$k]['papersbetween'] = '';            
            $xlsData[$k]['company'] = $v['name'];
            $xlsData[$k]['english'] = 'Ladies dress';
            $xlsData[$k]['chinese'] = '女裙';
            $xlsData[$k]['declarenum'] = 1;
            $xlsData[$k]['declareprice'] = 1;
            $xlsData[$k]['fromcompany'] = 'Nanjing cross trade Agel Ecommerce Ltd';
            $xlsData[$k]['fromname'] = 'ZHU JUN';
            $xlsData[$k]['fromcountry'] = 'CN';
            $xlsData[$k]['fromprovince'] = 'jiangsu';
            $xlsData[$k]['fromcity'] = 'nanjing';
            $xlsData[$k]['fromemail'] = '';
            $xlsData[$k]['fromaddr'] = 'Guanghua Road, Wanda village 1-102 Nanjing JIANG SU';
            $xlsData[$k]['fromphone'] = '18168006439';
            $xlsData[$k]['fromfaxes'] = '';
            $xlsData[$k]['remark'] = '';
            $xlsData[$k]['goodsale'] = '';
            $xlsData[$k]['goodsnum'] = '6202939000';
            $xlsData[$k]['servechannel'] = 'JC34-DHL-SHA01';
            $xlsData[$k]['fromcompanyname'] = '南京跨贸电子商务有限公司';
            $xlsData[$k]['texture'] = '80% nylon 20% spandex WOVEN';
            $xlsData[$k]['ruletype'] = 'w';

        }
        // var_dump($xlsData);
        $this->exportExcel($xlsName,$xlsCell,$xlsData);
    }

    public function exportExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = "order".date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
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
}

$obj = new OrderExcel();
$obj->index();
?>