<?php
/**
 * Created by PhpStorm.
 * User: DuXu
 * Date: 2017/7/21
 * Time: 16:04
 */
namespace Api\Controller;
use Think\Controller\RestController;
class FaultController extends BController {

    private $zsdb='mysqli://baojia_dc:Ba0j1a-Da0!@#*@rent2015.mysql.rds.aliyuncs.com:3306/dc'; //阿里云数据库
    private $box_config = 'mysqli://api-baojia:CSDV4smCSztRcvVb@10.1.11.14:3306/baojia_box';  //14盒子信息数据库
    public function index()
    {
      $this->display('index');
    }
    public function test(){
        echo phpinfo();
    }
    function curl_post($url,$data){ // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_COOKIEFILE, $GLOBALS['cookie_file']); // 读取上面所储存的Cookie信息
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $tmpInfo; // 返回数据
    }

    /**
     * 模拟提交参数，支持https提交 可用于各类api请求
     * @param string $url ： 提交的地址
     * @param array $data :POST数组
     * @param string $method : POST/GET，默认GET方式
     * @return mixed
     */
    function curl_get($url, $data='', $method='GET')
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            if ($data != '') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
            }
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }
    //分类规则
    public function sort_rules(){
        $return = [];
        $return[0]['name'] = "缺电";
        $return[0]['desc'] = "电量为35%以下";
        $return[0]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/quedian.png";
        $return[1]['name'] = "馈电下架";
        $return[1]['desc'] = "电量为20%以下不包含0%";
        $return[1]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/kuidian.png";
        $return[2]['name'] = "无电";
        $return[2]['desc'] = "电量为0%";
        $return[2]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/wudian.png";
        $return[3]['name'] = "无电离线";
        $return[3]['desc'] = "电量为0%的离线车辆，多为完全无电导致盒子离线";
        $return[3]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/wudianlixian.png";
        $return[4]['name'] = "离线下架";
        $return[4]['desc'] = "车辆盒子离线，并已下架";
        $return[4]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/lixianxiajia.png";
        $return[5]['name'] = "两日无单";
        $return[5]['desc'] = "当前时间48小时内无订单车辆";
        $return[5]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/liangriwudan.png";
        $return[6]['name'] = "故障车辆";
        $return[6]['desc'] = "连续三次无法使用并且移动小于300m";
        $return[6]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/youdanwucheng.png";
        $return[7]['name'] = "待小修";
        $return[7]['desc'] = "标记为需要小修的车辆，需要人员处理";
        $return[7]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/daixiaoxiu.png";
        $return[8]['name'] = "越界下架";
        $return[8]['desc'] = "超出服务区的下架车辆，需要调度拉回";
        $return[8]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/yuejiexiajia.png";
        $return[9]['name'] = "大修";
        $return[9]['desc'] = "标记为需要拉回维修的车辆";
        $return[9]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/daxiu.png";
        $return[10]['name'] = "待调动";
        $return[10]['desc'] = "标记为需要调度移动的车辆";
        $return[10]['image'] = "http://".$_SERVER['HTTP_HOST']."/Public/img/fenlei/daidiaodong.png";
        $this->response(["code" => 1, "data" => $return], 'json');
    }
    /*
            操作
            回收下架 sell_status = -10
            上架 sell_status = 1
            丢失 operate_status = 6  :
            电池丢失上报 operate_status = 3  :
            operation_logging 1=换电设防, 2=换电设防失败, 3=确认回收, 4=完成小修, 5=下架回收(car_status=1待维修 =2 待调度), 6=小修上报, 7=车辆丢失, 8=上架待租
    */
    public function repairOperation($uid = '', $iflogin = '',$rent_content_id = '',$plate_no = '',$gis_lng = '',$gis_lat = '',$operation_type = '', $car_status = '', $desc = "")
    {
        if( empty($rent_content_id) || empty($plate_no) || empty($uid) || empty($operation_type) ){
            $this->response(["code" => 0, "message" => "参数有误"], 'json');
        }
       $this->promossion($uid,$iflogin);
       $rent_info = M("rent_content")->where(["id"=>$rent_content_id,"sort_id"=>112])->find();
       if(!$rent_info){
          $this->response(["code" => -1001, "message" => "车辆不存在"], 'json');
       }
       if(!$operation_type){
          $this->response(["code" => -1002, "message" => "无操作类型"], 'json');
       }
       if($operation_type == 6){
        $data = ["operate_status" => 6];
        $record_data = [
                    "uid" => $uid,
                    "operate" => 7, //7=车辆丢失
                    "rent_content_id" => $rent_content_id,
                    "plate_no" => $plate_no,
                    "time" => time(),
                    "gis_lng" => $gis_lng,
                    "gis_lat" => $gis_lat

            ];
        $res = M("rent_sku_hour")->where(["rent_content_id"=>$rent_content_id])->save($data);
        if($res){
            $record_res = $this->add_record( $uid, $iflogin , $record_data);
            $location = M("operation_logging")->field("operate,gis_lat,gis_lng")->where("rent_content_id = {$rent_content_id} and uid = {$uid} and operate in (7,9,10)")->select();
            $user =  M("member")->where("uid = {$uid}")->field('first_name,last_name')->select();
            $data['title'] = "已标记丢失";
            $data['name'] = $user[0]['last_name'].$user[0]['first_name'];
            $data['time'] = date("Y-m-d H:i",$time);
             foreach ($location as $k => $v) {
                   if($v['operate'] == 9){
                    $location[$k]['operate'] = "疑失";
                    $location[$k]['gis_lat'] = (float)$v['gis_lat'];
                    $location[$k]['gis_lng'] = (float)$v['gis_lng'];
                   }
                   if($v['operate'] == 10){
                    $location[$k]['operate'] = "疑难";
                    $location[$k]['gis_lat'] = (float)$v['gis_lat'];
                    $location[$k]['gis_lng'] = (float)$v['gis_lng'];
                   }
                   if($v['operate'] == 7){
                    $location[$k]['operate'] = "丢失";
                    $location[$k]['gis_lat'] = (float)$v['gis_lat'];
                    $location[$k]['gis_lng'] = (float)$v['gis_lng'];
                   }
                } 
        $data['location'] = $location;
             $this->response(["code" => 61, "message" => "上报车辆丢失成功","data" => $data], 'json');
        }else{
             $this->response(["code" => -61, "message" => "操作无修改"], 'json');
        }
       }elseif($operation_type == 9){//遗失
            $time = time();
            $data = ["operate_status" => 5];
            $record_data = [
                    "uid" => $uid,
                    "operate" => 9, //9=遗失,
                    "rent_content_id" => $rent_content_id,
                    "plate_no" => $plate_no,
                    "time" => $time,
                    "gis_lng" => $gis_lng,
                    "gis_lat" => $gis_lat

            ];
            $info = M("operation_logging")->where(["rent_content_id"=>$rent_content_id,"operate"=>9,"uid"=>$uid])->getField("id");
            if($info){
                $this->response(["code" => -1000, "message" => "请勿重复上报疑失" ], 'json');
            }
                 $res = M("rent_sku_hour")->where(["rent_content_id"=>$rent_content_id])->save($data);
                 $record_res = $this->add_record( $uid, $iflogin , $record_data);
                 $user =  M("member")->where("uid = {$uid}")->field('first_name,last_name')->select();
                 $data['title'] = "已标记疑失";
                 $data['name'] = $user[0]['last_name'].$user[0]['first_name'];
                 $data['time'] = date("Y-m-d H:i",$time);
                 if($record_res){
                    $this->response(["code" => 91, "message" => "疑失上报成功" , "data" => $data], 'json');
                 }
                 else{
                    $this->response(["code" => -91, "message" => "疑失上报失败"], 'json');
                 }

       }elseif($operation_type == 10){//疑难
            $time = time();
            $data = ["operate_status" => 4];
            $record_data = [
                    "uid" => $uid,
                    "operate" => 10, // 10 =疑难,
                    "rent_content_id" => $rent_content_id,
                    "plate_no" => $plate_no,
                    "time" => $time,
                    "gis_lng" => $gis_lng,
                    "gis_lat" => $gis_lat

            ];
             $info = M("operation_logging")->where(["rent_content_id"=>$rent_content_id,"operate"=>10,"uid"=>$uid])->getField("id");
            if($info){
                $this->response(["code" => -1000, "message" => "请勿重复上报疑难" ], 'json');
            }
                 $res = M("rent_sku_hour")->where(["rent_content_id"=>$rent_content_id])->save($data);
                 $record_res = $this->add_record( $uid, $iflogin , $record_data);
                 $user =  M("member")->where("uid = {$uid}")->field('first_name,last_name')->select();
                 $data['title'] = "已标记疑难";
                 $data['name'] = $user[0]['last_name'].$user[0]['first_name'];
                 $data['time'] = date("Y-m-d H:i",$time);
                 if($record_res){
                    $this->response(["code" => 1001, "message" => "疑难上报成功" , "data" => $data], 'json');
                 }
                 else{
                    $this->response(["code" => -1001, "message" => "疑难上报失败"], 'json');
                 }

       }elseif($operation_type == -10){
            if( empty($car_status) ){
                 $this->response(["code" => -1001, "message" => "请选择下架回收类型"], 'json');
            }
            // $car_status1 = "";
            // if( $car_status == 10) {$car_status1.=$car_status; $desc = "";}//车辆线路损坏
            // if( $car_status == 11) {$car_status1.=$car_status; $desc = "";}//车辆丢失部件
            // if( $car_status == 12) {$car_status1.=$car_status; $desc = "";}//无法打开电子锁
            // if( $car_status == 13) {$car_status1.=$car_status; $desc = "";}//换电无法正常上线
            // if( $car_status == 14) {$car_status1.=$car_status; $desc = "";}//无法设防
            // if( $car_status == 15) {$car_status1.=$car_status; $desc = "";}//拧车把不走
            // if( $car_status == 16) {$car_status1.=$car_status; $desc = "";}//二维码损坏
            // if( $car_status == 17) {$car_status1.=$car_status; $desc = "";}//电池丢失并车辆被破坏
            // if( $car_status == 18) {$desc = "";}//车辆位置不易出租
            // if( in_array(18, explode(',', $car_status)) ) {
            //     if( empty($desc) ){
            //      $this->response(["code" => -1001, "message" => "请输入原因"], 'json');
            //     }
            //     $desc = $desc;}
            // if($cars == 1){
                $data = ["sell_status" => -8,"update_time" => time()];
            // }else{
            //     $data = ["sell_status" => -13];
            // }
            $record_data = [
                    "uid" => $uid,
                    "operate" => 5, //5=下架回收,
                    "car_status" => $car_status,
                    "desc" => $desc,
                    "rent_content_id" => $rent_content_id,
                    "plate_no" => $plate_no,
                    "time" => time(),
                    "status" => 0,
                    "gis_lng" => $gis_lng,
                    "gis_lat" => $gis_lat

            ];
            $res = M("rent_content")->where(["id"=>$rent_content_id])->save($data);
            if($res){
                 $this->add_record( $uid, $iflogin , $record_data);
                 $this->response(["code" => 101, "message" => "下架回收操作成功"], 'json');
            }else{
                 $this->response(["code" => -101, "message" => "操作无修改"], 'json');
            }

       }elseif($operation_type == 1){
            $data = ["sell_status" => $operation_type,"update_time" => time()];
            $record_data = [
                    "uid" => $uid,
                    "operate" => 8, //8=上架待租,
                    "rent_content_id" => $rent_content_id,
                    "plate_no" => $plate_no,
                    "time" => time(),
                    "gis_lng" => $gis_lng,
                    "gis_lat" => $gis_lat

            ];
            $res = M("rent_content")->where(["id"=>$rent_content_id])->save($data);
            if($res){
                 $operate_status = M("rent_sku_hour")->where(["rent_content_id"=>$rent_content_id])->getField('operate_status');
                 if($operate_status == 4||$operate_status == 5||$operate_status == 6) {
                      M("rent_sku_hour")->where(["rent_content_id"=>$rent_content_id])->save(["operate_status"=>8]);
                 }
                 $this->add_record( $uid, $iflogin , $record_data);
                 $this->response(["code" => 11, "message" => "上架待租操作成功"], 'json');
            }else{
                 $this->response(["code" => -11, "message" => "操作无修改"], 'json');
            }
       }elseif ($operation_type == 2) {//调度人员确认回收
            $data = ["sell_status" => -10,"update_time" => time()];
            $record_data = [
                    "uid" => $uid,
                    "operate" => 3, //3=确认回收,
                    "rent_content_id" => $rent_content_id,
                    "plate_no" => $plate_no,
                    "time" => time(),
                    "gis_lng" => $gis_lng,
                    "gis_lat" => $gis_lat

            ];
            $sell_status = M("rent_content")->where(["id"=>$rent_content_id])->getField('sell_status');
            if($sell_status==-8){
               $res1 = M("operation_logging")->where("rent_content_id = {$rent_content_id} and operate = 5 and status = 0")->save(['status'=>1]);
            }
            $res = M("rent_content")->where(["id"=>$rent_content_id])->save($data);
            if($res){
                    //mebike.repair_order  添加记录
                    $repair_data = [
                        "user_id" => $uid,
                        "repair_begin_time" => time(), 
                        "rent_content_id" => $rent_content_id,
                        "plate_no" => $plate_no,
                        "repair_success_time" => time(),
                        "repair_type" => 23,
                        "is_success" => 1
                    ];
                     M("baojia_mebike.repair_order")->add($repair_data);
                     $this->add_record( $uid, $iflogin , $record_data);
                     $return = D("FedGpsAdditional")->theDayCarNum($uid,2);
                     $this->response(["code" => 21, "message" => "确认回收成功" ,"data" => $return['prompt'] ], 'json');
                  }else{
                     $this->response(["code" => -21, "message" => "操作无修改"], 'json');
                  }
                 
       }elseif ($operation_type == 3) {//电池丢失上报
            if(isset($_FILES["pic1"])){
                $_FILES["uploadfile"]=$_FILES["pic1"];
                $root   =  "/opt/web/news/Public/img/pic";
                $root1="/" . date("Y") . "/" . date("md") . "/";
                
                $filename = md5(time().rand(100,100)) . ".jpg";
                $filepath = $root.$root1.$filename;
                $rr = $this->createdir($root.$root1, 0777);
                $pic1=move_uploaded_file($_FILES["uploadfile"]["tmp_name"], $filepath);
                if($pic1){
                    $pic1=['path'=>'xiaomi.baojia.com/Public/img/pic'.$root1.$filename,'short_path'=>'Public/img/pic'.$root1.$filename];
                }
           }
          if(isset($_FILES["pic2"])){
                $_FILES["uploadfile"]=$_FILES["pic2"];
                $root   =  "/opt/web/news/Public/img/pic";
                $root1="/" . date("Y") . "/" . date("md") . "/";
                
                $filename2 = md5(time().rand(200,200)) . ".jpg";
                $filepath2 = $root.$root1.$filename2;
                $rr = $this->createdir($root.$root1, 0777);
                $pic2=move_uploaded_file($_FILES["uploadfile"]["tmp_name"], $filepath2);
                if($pic2){
                    $pic2=['path'=>'xiaomi.baojia.com/Public/img/pic'.$root1.$filename2,'short_path'=>'Public/img/pic'.$root1.$filename2];
                }
          }
          if(empty($pic1['short_path']) || empty($pic2['short_path'])){
                $this->response(["code" => -1, "message" => "图片缺失，请重新上传"], 'json');
          };
              $data = [
                      "uid" => $uid,
                      "operate" => 11, //11 = 电池丢失,
                      "rent_content_id" => $rent_content_id,
                      "plate_no" => $plate_no,
                      "time" => time(),
                      "pic1" => $pic1['short_path'],
                      "pic2" => $pic2['short_path'],
                      "gis_lng" => $gis_lng,
                      "gis_lat" => $gis_lat
              ];
                 $res = $this->add_record( $uid, $iflogin , $data);
                 if($res){
                         $this->response(["code" => 1100, "message" => "电池丢失上报成功"], 'json');
                        
                 }else{
                         $this->response(["code" => -1100, "message" => "电池丢失上报失败"], 'json');
                 }
                 
          
       }
       
    }
    //查询车辆状态
    public function car_status($uid = '', $iflogin = '',$rent_content_id = ''){
        $this->promossion($uid,$iflogin);
        $rent_info = M("rent_content")->where(["id"=>$rent_content_id,"sort_id"=>112])->getField('sell_status');
        if(!$rent_info){
          $this->response(["code" => -1001, "message" => "车辆不存在"], 'json');
        }
        $this->response(["code" => 1001, "data" => $rent_info], 'json');
    }
    // 疑失疑难丢失
    public function lose_status($uid = '1', $iflogin = '',$rent_content_id = ''){
        $this->promossion($uid,$iflogin);
        $rent_info = M("rent_content")->where(["id"=>$rent_content_id,"sort_id"=>112])->find();
        if(!$rent_info){
          $this->response(["code" => -1001, "message" => "车辆不存在"], 'json');
        }
        $location = M("operation_logging")->field("operate,uid,time,gis_lat,gis_lng")->where("rent_content_id = {$rent_content_id} and operate in(7,9,10)")->select();
        foreach ($location as $k => $v) {
               if($v['operate'] == 9){
                $location[$k]['operate'] = (int)$v['operate'];
                $location[$k]['operate_type'] = "疑失";
                $location[$k]['title'] = "已标记疑失";
                $user =  M("member")->where("uid = ".$v['uid'])->field('first_name,last_name')->select();
                $location[$k]['name'] = $user[0]['last_name'].$user[0]['first_name'];
                $location[$k]['time'] = date("Y-m-d H:i",$v['time']);
                $location[$k]['gis_lat'] = (float)$v['gis_lat'];
                $location[$k]['gis_lng'] = (float)$v['gis_lng'];
               }
               if($v['operate'] == 10){
                $location[$k]['operate'] = (int)$v['operate'];
                $location[$k]['operate_type'] = "疑难";
                $location[$k]['title'] = "已标记疑难";
                $user =  M("member")->where("uid = ".$v['uid'])->field('first_name,last_name')->select();
                $location[$k]['name'] = $user[0]['last_name'].$user[0]['first_name'];
                $location[$k]['time'] = date("Y-m-d H:i",$v['time']);
                $location[$k]['gis_lat'] = (float)$v['gis_lat'];
                $location[$k]['gis_lng'] = (float)$v['gis_lng'];
               }
               if($v['operate'] == 7){
                $location[$k]['operate'] = (int)$v['operate'];
                $location[$k]['operate_type'] = "丢失";
                $location[$k]['title'] = "已标记丢失";
                $user =  M("member")->where("uid = ".$v['uid'])->field('first_name,last_name')->select();
                $location[$k]['name'] = $user[0]['last_name'].$user[0]['first_name'];
                $location[$k]['time'] = date("Y-m-d H:i",$v['time']);
                $location[$k]['gis_lat'] = (float)$v['gis_lat'];
                $location[$k]['gis_lng'] = (float)$v['gis_lng'];
               }
            } 
        $this->response(["code" => 1001, "data" => $location], 'json');
    }
    //添加写入操作工作记录
    public function add_record($uid = '', $iflogin = '',$data = ''){
        $this->promossion($uid,$iflogin);
        if(!is_array($data))
        {
             return false;
        }else{
             $res = M("operation_logging")->add($data);
             if($res){
                 return true;
            }
        }
    }

    //查询车辆回收时车辆位置
    public function find_location($uid = '', $iflogin = '',$id = ''){
        $this->promossion($uid,$iflogin);
        $this->CheckInt($id);
        $result = M("operation_logging")->field("rent_content_id,gis_lat,gis_lng")->where(["id"=>$id])->select();
        $imei = M("rent_content")->alias('rc')
        ->join("car_item_device cid on rc.car_item_id = cid.car_item_id","left")
        ->field(" cid.imei,rc.id,rc.car_item_id")->where(["rc.id"=>$result[0]['rent_content_id']])->find();
        // $pt = [$result[0]['gis_lng'],$result[0]['gis_lat']];
        $pt = [$result[0]['gis_lng'],$result[0]['gis_lat']];
        $location = $this->GetAmapAddress( $result[0]['gis_lng'], $result[0]['gis_lat']);
        $info = $this->isXiaomaInArea( $result[0]['rent_content_id'] , $pt, $imei['imei']);
        $result['is_Inarea'] = $info ? "界内" : "界外";
        $result['location'] = $location;
        if($result){
            $this->response(["code" => 1001, "message" => "成功", "data"=>$result], 'json');
        }else{
            $this->response(["code" => -1001, "message" => "失败"], 'json');
        }

    }
    //车辆详情接口
    public function car_details($plate_no = '', $uid = '' ,$iflogin = '',$gis_lng = '',$gis_lat = '',$is_scan = '',$page = '1'){
        $time1 = $this->microtime_float();
        if( empty($plate_no) || empty($uid) ){
            $this->response(["code" => 0, "message" => "参数有误"], 'json');
        }
        $this->promossion($uid,$iflogin);
        if( substr($plate_no,0,2) == 'dd' ){
            $plate_no = str_replace('dd', 'DD', $plate_no);
        }elseif( substr($plate_no,0,2) !== 'DD'){
            $plate_no = 'DD'.$plate_no;
        }
        //查询车辆是否被预约
        $order = M("baojia_mebike.have_order")->where("status=0 and plate_no='{$plate_no}'")->field('uid,create_time')->find();
        if( !empty($order['uid']) ){
            $resttime = time()-$order['create_time'];
            if($resttime>=1800){
                $over = M("baojia_mebike.have_order")->where("status=0 and plate_no='{$plate_no}'")->save(['status'=>1]);
            }else{
                if( $order['uid'] != $uid){
                $this->response(["code" => -100, "message" => "该车已被预约,暂不可查看详情"], 'json');
                } 
            }
        }
        
        $car_item_id = M("car_item_verify")->where(["plate_no" => $plate_no])->getField("car_item_id");
        $rent_content_id = M("rent_content")->where(["car_item_id" => $car_item_id])->getField("id");
        if(empty($rent_content_id)){
            $this->response(["code" => -1, "message" => "查询无此车辆" ], 'json');
        }
        $imei = M("car_item_device")->where(["car_item_id" => $car_item_id])->getField("imei");
        if( empty($imei) ){
               $this->response(["code" => -1, "message" => "未获取到盒子号,请重新请求" ], 'json');
          }  

        if($page==2){
          //用户最后用车
           $user_return = $this->trade_line($rent_content_id);
           $result['user_return'] = $user_return;
           $result['work_record'] = $this->car_operation_log($rent_content_id);
           $this->response(["code" => 1000, "message" => "请求成功" , "data" => $result, "is_stop" => false], 'json');
        }
        //计算扫码位置和盒子实际上报位置距离
        $gps = D('Gps');
        if( $is_scan==1 ){
            if( empty($gis_lng)||empty($gis_lat) ){
              $this->response(["code"=>-1001,"message"=>"未获取到当前位置,请重新请求"]);
            }
          $sql = "select ROUND(st_distance(point(longitude, latitude),point($gis_lng, $gis_lat))*111195,0) AS distance from gps_status where imei = {$imei}";
          $distance = M("",'',$this->box_config)->query($sql);
          // echo M("",'',$this->box_config)->getLastsql();die;
          if( $distance[0]['distance']<=1000 ){
            $url = $_SERVER['HTTP_HOST']."/index.php/Api/Operation/CorrectivePosition";
            $data['user_id'] = $uid;
            $data['rent_content_id'] = $rent_content_id;
            $data['longitude'] = $gis_lng;
            $data['latitude'] = $gis_lat;
            $data['type'] = 17;//自动校正位置
            // $data['distance'] = $distance[0]['distance'];
            // \Think\Log::write(var_export($data, true),'位置校正');
            $check_code = $this->curl_get($url,$data,'POST');
          }else{
            $this->response(["code" => -2000, "message"=>"当前距离车辆位置较远，你可以手动校正车辆位置后操作","rent_content_id"=>$rent_content_id ], 'json');
          
          }
        }      
        
        //定位时间
        // $res = M("rent_content")->alias("rc")
        //        ->join("rent_content_search rcs ON rc.id = rcs.rent_content_id","left")
        //        ->join("rent_content_ext rce ON rce.rent_content_id = rcs.rent_content_id","left")
        //        ->join("car_item_device cid ON cid.car_item_id = rc.car_item_id","left")
        //        ->join("car_info cn on cn.id=rc.car_info_id","left")
        //        ->join("car_item ci on ci.id = rc.car_item_id","left")
        //        ->join("car_model cm ON cm.id = cn.model_id","left")
        //        ->join("car_item_color cic on cic.id = ci.color","left")
        //        ->join("fed_gps_additional fga ON fga.imei = cid.imei","left")
        //        ->field("rc.sell_status,rcs.plate_no, rcs.picture_url, rcs.shop_brand, rce.battery_capacity, rcs.address, rcs.update_time, rcs.car_item_id, rc.id,rc.car_info_id, cid.`status`, cid.imei, rcs.gis_lat, rcs.gis_lng, cn.full_name, ci. `sort_name`,cic.color,cid.device_type, fga.residual_battery")
        //        ->where("rc.id = {$rent_content_id} and rcs.address_type = 99 AND rcs.sort_id = 112")
        //        ->select();
        $res = M("rent_content")->alias("rc")
               ->join("car_item_verify civ ON civ.car_item_id = rc.car_item_id","left")
               ->join("rent_content_ext rce ON rce.rent_content_id = rc.id","left")
               ->join("corporation cc on cc.id = rc.corporation_id","left")
               ->join("car_item_device cid ON cid.car_item_id = rc.car_item_id","left")
               ->join("rent_content_return_config rcrc ON rcrc.rent_content_id = rc.id","left")
               ->join("car_info cn on cn.id=rc.car_info_id","left")
               ->join("car_item ci on ci.id = rc.car_item_id","left")
               ->join("car_model cm ON cm.id = cn.model_id","left")
               ->join("car_item_color cic on cic.id = ci.color","left")
               ->join("fed_gps_additional fga ON fga.imei = cid.imei","left")
               ->join("fed_gps_status fgs ON fgs.imei = cid.imei","left")
               ->join("car_info_picture cip on cip.car_info_id=rc.car_info_id and cip.car_color_id=ci.color","left")
               ->field("cc.name, rcrc.return_mode, rc.sell_status, civ.plate_no, civ.vin, cip.url picture_url, rc.shop_brand, rc.car_item_id, rc.id, rc.car_info_id, rc.status, cid.imei, fgs.latitude gis_lat, fgs.longitude gis_lng, cn.full_name, ci.`sort_name`, cic.color, cid.device_type, fga.residual_battery, fga.datetime")
               ->where("rc.id = {$rent_content_id}  AND rc.sort_id = 112 AND cip.status=2")
               ->select(); 
        //限行区域
        $groupid = M("car_group_r","",$this->zsdb)->field("groupId")->where("plate_no='{$plate_no}'")->find();
        $driving_area = M("group","",$this->zsdb)->field("name")->where(["id"=>$groupid['groupid']])->find(); //查询行驶区域名字
        $userinfo = M("baojia_cloud.group_manager")->field("user_id,user_name")->where(["groupId"=>$groupid['groupid']]) ->find(); //查询区长ID名字
        $mobile = M("ucenter_member")->field("mobile")->where(["uid"=>$userinfo['user_id']])->find();  //查询区长手机号

        $battery_last = M("operation_logging")->where("operate in (-1,1,2) and rent_content_id=".$rent_content_id)->order('id desc')->getField('time');
        //添加运维端数据
        if( !empty($gis_lng) && !empty($gis_lat) ){
            $data_visit_log = [
                "uid" => $uid,
                "rent_content_id" => $rent_content_id,
                "plate_no" => $plate_no,
                "imei" => $res[0]['imei'],
                "user_lng" => $gis_lng,
                "user_lat" => $gis_lat,
                "record_time" => time(),
                "gis_lng" => $res[0]['gis_lng'],
                "gis_lat" => $res[0]['gis_lat'],
                "distance"=>$gps->distance($res[0]['gis_lat'],$res[0]['gis_lng'],$gis_lat,$gis_lng)
            ];
            $add_visit = M("xiaomi_visit_log")->add($data_visit_log);
         }
        //       echo "<pre/>";
        // print_r($res);die;
            //       Array
            // (
            //     [0] => Array
            //         (
            //             [plate_no] => DD912323
            //             [picture_url] => 2017/0316/2073761_201703169508.png
            //             [shop_brand] => 电动单车轻骑版JA
            //             [battery_capacity] => 49
            //             [address] => 北京市朝阳区民族园路2-9
            //             [update_time] => 2017-07-24 16:54:52
            //             [car_item_id] => 330421
            //             [id] => 282067
            //             [car_info_id] => 30059
            //             [status] => 1
            //             [imei] => 0358482040871657
            //             [gis_lat] => 39.980788
            //             [gis_lng] => 116.390237
            //             [full_name] => 九九一 轻骑版JA
            //             [sort_name] => 电动单车
            //             [color] => 黑色
            //         )

            // )
        $info = M("baojia_box.gps_status",null,$this->box_config)->where(["imei"=>$res[0]['imei']])->field("id,imei,latitude,longitude,datetime,lastonline")->find();
        $gd = $gps->gcj_encrypt($info["latitude"],$info["longitude"]);
        $info["gd_latitude"] = $gd["lat"];
        $info["gd_longitude"] = $gd["lon"];

        $bd = $gps->bd_encrypt($info["gd_latitude"],$info["gd_longitude"]);
        $info["bd_latitude"] = $bd["lat"];
        $info["bd_longitude"] = $bd["lon"];
        //       echo "<pre/>";
        // print_r($info);die;
        $info["datetime_diff"] = $this->timediff($info["datetime"],time())."前";
        $info["is_star"] = time() - $info['datetime'] > 600 ? "无定位无星" : "有定位有星"; 
        $info["is_online"] = time() - strtotime($info['lastonline']) > 1200 ? "离线无心跳" : "在线有心跳"; 
        $info["location"] = $this->GetAmapAddress( $info["gd_longitude"], $info["gd_latitude"]);
        // Array
        // (
        //     [id] => 300831
        //     [imei] => 0358482040871657
        //     [port] => 0
        //     [latitude] => 39.979388
        //     [longitude] => 116.383992
        //     [speed] => 0
        //     [basestation] => 
        //     [datetime] => 2017-07-19 11:13:10
        //     [lastonline] => 2017-07-19 11:13:10
        //     [alert_code] => 0
        //     [alert_time] => 0
        //     [alert_receive_time] => 0
        //     [course] => 0
        //     [gd_latitude] => 39.980787616111
        //     [gd_longitude] => 116.39023718636
        //     [bd_latitude] => 39.987097907665
        //     [bd_longitude] => 116.39664933304
        //     [datetime_diff] => 6天4时51分钟前
        //     [is_online] => 离线
        //     [location] => 北京市朝阳区亚运村街道中华民族园唐人街购物广场
        //     [is_inarea] => 界内
        // )
        $pt=[$info['longitude'],$info['latitude']];
        $in_area_result = $this->isXiaomaInArea($rent_content_id,$pt,$info['imei']);
        $info['is_inarea'] = empty($in_area_result) ? "界外" : "界内";
        $jizhan = M("baojia_box.gps_status_bs",'',$this->box_config)->where(["imei"=>$res[0]['imei']])->find();
        $jizhan["gd_latitude"] = $gd["lat"];
        $jizhan["gd_longitude"] = $gd["lon"];
        $jizhan['location'] = $this->GetAmapAddress( $gd["lon"], $gd["lat"]);
         // \Think\Log::write(var_export($info, true),'盒子信息');
        
        //车辆状态
        $is_rating = "待租中";
          if($res[0]['sell_status'] != 1){
            $is_rating = "不可租";
          }

          $sell_status_item = [];
          $sell_status_item[0]    = "人工停租";
          $sell_status_item[-5]   = "馈电停租";
          // $sell_status_item[-1]   = "馈电停租";
          $sell_status_item[-7]   = "越界停租";
          $sell_status_item[101]  = "报修下架";
          $sell_status_item[-8]   = "维修停租";
          $sell_status_item[-10]  = "收回下架";
          $sell_status_item[-100] = "离线停租";
          $sell_status_item[-13] = "待调度";

          isset($sell_status_item[$res[0]['sell_status']]) && $is_rating = $sell_status_item[$res[0]['sell_status']];
          $hasOrder = M("trade_order")->where(" rent_content_id={$rent_content_id} and  rent_type=3 and status>=10100 and status<80200 and status<>10301 ")->find();
          if($hasOrder){
            $is_rating = "出租中";
          }
        //车辆问题
        $car_questions = [];
        $car_questions['title'] = "车辆暂无问题";
        $car_questions['questions'] = [];
        $xiaoxiu = M("operation_logging")->field("pic1,pic2")->where("rent_content_id = {$rent_content_id} and operate = 6 and status = 0")->find();
        $i = 0;
        if( $res[0]['sell_status'] == -7 || $res[0]['sell_status'] == -8 || $res[0]['sell_status'] == -13){
            if($res[0]['sell_status'] == -7){
                $car_questions['questions'][$i]['reason'] = "该车已越界下架";
                $car_questions['questions'][$i]['desc'] = "需要\r\n回收";
                $car_questions['questions'][$i]['type'] = "1";
                $car_questions['title'] = "车辆存在以下问题,点击处理";
                $i++;  
            }
            if($res[0]['sell_status'] == -8){
                $recycle = M("operation_logging")->field("id,car_status,desc,pic1,pic2")->where("rent_content_id = {$rent_content_id} and operate = 5 and status = 0")->find();
                
                $recycle1 = explode(",",$recycle['car_status']);
                $car_questions['questions'][$i]['reason'] = "";
                foreach ($recycle1 as $k => $v) {
                    if( $v == 10) {$car_questions['questions'][$i]['reason'] .= "拧车把不走\r\n";      }
                    if( $v == 11) {$car_questions['questions'][$i]['reason'] .= "支架无法弹起\r\n";      }
                    if( $v == 12) {$car_questions['questions'][$i]['reason'] .= "app多项操作失败\r\n";    }
                    if( $v == 13) {$car_questions['questions'][$i]['reason'] .= "刹车完全失灵\r\n";  }
                    if( $v == 14) {$car_questions['questions'][$i]['reason'] .= "二维码无法扫描\r\n";          }
                    if( $v == 15) {$car_questions['questions'][$i]['reason'] .= "车辆被破坏\r\n";        }
                    if( $v == 16) {$car_questions['questions'][$i]['reason'] .= "车辆被政府部门扣押\r\n";}
                    // if( $v == 18) {$car_questions['questions'][$i]['reason'] .= $recycle['desc']."\r\n";        } 
                }  
                $car_questions['questions'][$i]['desc'] = "需要\r\n回收";
                $car_questions['questions'][$i]['type'] = "1";
                $car_questions['title'] = "车辆存在以下问题,点击处理";
                $i++;          
            }
            if($res[0]['sell_status'] == -13){
                $car_questions['questions'][$i]['reason'] = "该车待调度";
                $car_questions['questions'][$i]['desc'] = "需要\r\n回收";
                $car_questions['questions'][$i]['type'] = "1";
                $car_questions['title'] = "车辆存在以下问题,点击处理";
                $i++; 
            }
          
           }
        if( (float)$res[0]['residual_battery'] <= 36 ){
          $car_questions['questions'][$i]['desc'] = "需要\r\n换电";
          $car_questions['questions'][$i]['type'] = "2";
          $car_questions['title'] = "车辆存在以下问题,点击处理";
          $i++;
        }
        
        if( $xiaoxiu && empty($xiaoxiu['pic2']) ){
          $car_questions['questions'][$i]['desc'] = "需要\r\n小修";
          $car_questions['questions'][$i]['type'] = "3";
          $car_questions['title'] = "车辆存在以下问题,点击处理";
          }
        if( $res[0]['return_mode'] == 1){ //还车方式
            $result['return_way'] = "原点还";
        }
        if( $res[0]['return_mode'] == 2){ //还车方式
            $result['return_way'] = "网点还";
        }
        if( $res[0]['return_mode'] == 4){ //还车方式
            $result['return_way'] = "自由还";
        }
        if( $res[0]['return_mode'] == 32){ //还车方式
            $result['return_way'] = "区域还";
        }
        $time2 = $this->microtime_float();
        $result["id"] = $rent_content_id;
        $result["time"] = $time2-$time1;
        $result["picture_url"] = "http://pic.baojia.com/b/".$res[0]['picture_url'];
        $result["plate_no"] = $res[0]['plate_no'];
        $result["sell_status"] = $res[0]['sell_status'];
        $result["car_status"] = $is_rating;
        $result["check"] = $res[0]['status'] == 2 ? "已审" : "未审" ;
        $result["device_type"] = $res[0]['device_type'];
        $result["sort_name"] = $res[0]['sort_name'];
        $result["full_name"] = $res[0]['full_name'];
        $result["color"] = $res[0]['color'];
        $result["imei"] = $res[0]['imei'];
        $result["vin"] = $res[0]['vin']; //车架号
        $result["blong_station"] = $res[0]['name'];
        $result["battery_id"] = "";
        $result["battery_capacity"] = (float)$res[0]['residual_battery'] < 0 ? 0 : (float)$res[0]['residual_battery'];
        $result["battery_capacity_time"] = date("m月d日 H:i:s",$res[0]['datetime']);
        $result["battery_last"] = empty($battery_last) ? "暂无换电记录" : "上次更换".$this->timediff($battery_last,time())."前";
        $result["is_star"] = $info['is_star'];
        $result["datetime_diff"] = $info['datetime_diff'];  //定位
        $result["is_online"] = $info['is_online'];
        
        // $result['return_gdlng'] = $user_return_gd['lon'];
        // $result['return_gdlat'] = $user_return_gd['lat'];
        // $result['return_bdlng'] = $user_return_bd['lon'];
        // $result['return_bdlat'] = $user_return_bd['lat'];
        $result['return_location'] = $this->GetAmapAddress( $user_return['end']['lon'], $user_return['end']['lat']);
        $result["is_inarea"] = $info['is_inarea'];
        $result["car_location"] = $info['location'];
        $result["car_gd_latitude"] = $info["gd_latitude"];
        $result["car_gd_longitude"] = $info["gd_longitude"];
        $result["car_bd_latitude"] = $info["bd_latitude"];
        $result["car_bd_longitude"] = $info["bd_longitude"];
        $result["jizhan_location"] = $jizhan['location'];
        $result["driving_area"] = empty($driving_area['name']) ? "" : $driving_area['name'];
        $result["qu_manager"] = empty($userinfo['user_name']) ? "" : $userinfo['user_name'];
        $result["mobile"] = (float)$mobile['mobile'];
        $result["datetime"] = date("m月d日 H:i:s",$info['datetime']);
        $result["lastonline"] =  date("m月d日 H:i:s",strtotime($info['lastonline']));
        $result["lastonline_time"] = $this->timediff(strtotime($info['lastonline']),time())."前";  //心跳
        $result["yellow_map"] = time() - $info["datetime"]>86400 ? "该车辆位置长时间未上报，可能在室内\r\n请检查附近小区楼内或大型建筑下" : "";
        $result["car_questions"] = $car_questions;
        if(is_array($result)){
            $this->response(["code" => 1000, "message" => "请求成功" , "data" => $result , "is_stop" => true , "page"=>2], 'json');
        }else{
            $this->response(["code" => -1001, "message" => "失败" ], 'json');
        }
        // echo "<pre/>";
        // print_r($result);die;
        
    }
    //根据车辆ID查询最新的三条工作记录
    public function  car_operation_log($rent_content_id = ''){
         $model = M('operation_logging');
         $map['rent_content_id'] =  $rent_content_id;
         $map['operate'] = array('neq',0);
         $res = $model->field('id,uid,operate,time,source')->where($map)->order('time desc')->limit(3)->select();
         // var_dump(strpos($val['operate'],'5') == 0 );die;
         foreach ($res as &$val){
             //查询运维姓名
             if( $val['operate'] == 12 || $val['operate'] == 13 || $val['operate'] == 14 )
             {
                $val['is_circle'] = 0;
             }else{
                $val['is_circle'] = 1;
             }
             $umap['user_id'] = $val['uid'];
             $repair = M('baojia_mebike.repair_member')->where($umap)->getField('user_name');
             $val['user_name'] = $repair?$repair:"";
             if($val['operate'] == 1 || $val['operate'] == -1 || $val['operate'] == 2){
                 $val['operate'] = '换电';
             }else if($val['operate'] == 3){
                 $val['operate'] = '确认回收';
             }else if($val['operate'] == 4){
                 $val['operate'] = '完成小修';
             }else if($val['operate'] == 5){
                 $val['operate'] = '下架回收';
             }else if($val['operate'] == 6){
                 $val['operate'] = '待小修';
             }else if($val['operate'] == 7){
                 $val['operate'] = '车辆丢失';
             }else if($val['operate'] == 8){
                 if($val['source'] == 1){
                  $val['operate'] = 'H5上架待租';
                }else if($val['source'] == 2){
                  $val['operate'] = 'QY上架待租';
                }else if($val['source'] == 3){
                  $val['operate'] = 'ICSS上架待租';
                }else{
                  $val['operate'] = '上架待租';
                }
             }else if($val['operate'] == 9){
                 $val['operate'] = '疑失';
             }else if($val['operate'] == 10){
                 $val['operate'] = '疑难';
             }else if($val['operate'] == 12){
                $val['operate'] = '设防';
             }else if($val['operate'] == 13){
                $val['operate'] = '撤防';
             }else if($val['operate'] == 14){
                $val['operate'] = '启动';
             }else if($val['operate'] == 15){
                  if($val['source'] == 1){
                    $val['operate'] = 'H5人工停租';
                  }else if($val['source'] == 2){
                    $val['operate'] = 'QY人工停租';
                  }else{
                    $val['operate'] = 'ICSS人工停租';
                  }
             }else if($val['operate'] == 100){
                $val['operate'] = '备注信息';
             }else if($val['operate'] == 16){
                $val['operate'] = '手动校正';
             }else if($val['operate'] == 17){
                $val['operate'] = '自动校正';
             }else{
                $val['operate'] = '电池丢失';
             }
             $val['time'] = date("Y-m-d H:i:s",$val['time']);
             unset($val['uid']);
         }
        //  echo "<pre>";
        // print_r($res);
        return  $res;
    }
    public function GetAmapAddress($lng,$lat,$default=''){
    $res = file_get_contents('http://restapi.amap.com/v3/geocode/regeo?output=JSON&location='.$lng.','.$lat.'&key=7461a90fa3e005dda5195fae18ce521b&s=rsv3&radius=1000&extensions=base');
    $res = json_decode($res);
    if($res->info == 'OK'){
        $default=$res->regeocode->formatted_address;
    }
    return $default;
   }
    public function isInsidePolygon($pt, $poly){
        $l = count($poly);
        $j = $l - 1;
        $c = false;
        for ($i = -1; ++$i < $l;$j = $i)
            (($poly[$i][1] <= $pt[1] && $pt[1] < $poly[$j][1]) || ($poly[$j][1] <= $pt[1] && $pt[1] < $poly[$i][1]))
             &&  
            ($pt[0] < ($poly[$j][0] - $poly[$i][0]) * ($pt[1] - $poly[$i][1]) / ($poly[$j][1] - $poly[$i][1]) + $poly[$i][0]) 
            &&  
            ($c = !$c);  
        return $c; 
    
   }
   
   public function isXiaomaInArea($rent_id,$pt,$imei,$has_poly = 0){
        // $rent_info = M("baojia.rent_content",null)->where(["id"=>$rent_id])->find();
        // //非小马单车不判断，默认在区域内
        // if(!in_array($rent_info["sort_id"],[112])){
        //     return true;
        // }
        $map = ["car_item_id"=>$rent_info["car_item_id"]];
        $map["device_type"] = ["in",[12,14,16,18,9]];
        //$car_item_device = M("baojia.car_item_device",null)->where($map)->find();
        //$imei = $car_item_device["imei"];
        $no_zero_imei = ltrim($imei,"0");
        $aliyun_conn = "mysqli://baojia_dc:Ba0j1a-Da0!@#*@rent2015.mysql.rds.aliyuncs.com:3306/dc";
        
        $ga_list_sql = "SELECT
                            ga.lat,
                            ga.lng
                        FROM
                            car_group_r cgr
                        INNER JOIN `group` g ON g.id = cgr.groupId
                        INNER JOIN group_area ga ON ga.groupId = g.id
                        WHERE
                            cgr.carId = '{$no_zero_imei}'
                        AND cgr.`status` = 1
                        AND g.`status` = 1
                        ORDER BY
                            ga. NO;
                        ";
        $list = M("",null,$aliyun_conn)->query($ga_list_sql);
        M("baojia.action",null,$this->baojia_config)->find();
        if($list){
            $poly = [];
            foreach ($list as $key => $value) {
                # code...
                $poly[] = [$value["lng"],$value["lat"]];
            }
            if(count($poly) > 3 && $pt[0] > 0 && $pt[1] > 0){
                // var_dump($pt);
                // var_dump($poly);
                $result = $this->isInsidePolygon($pt,$poly);
                return $result;
            }
        }
        if($has_poly == 1){
            return empty($list) ? 0 : 1;
        }
        //此处判断是否在区域内
        return true;
   }
   //小修上报
   public function repair_report( $uid = '', $iflogin = '', $car_status = '', $rent_content_id = '', $plate_no = '',$gis_lng = '',$gis_lat = ''){
        $uid = $_POST['uid'];
        $car_status = $_POST['car_status'];
        $rent_content_id = $_POST['rent_content_id'];
        $plate_no = $_POST['plate_no'];
        $gis_lng = empty($_POST['gis_lng']) ? "" : $_POST['gis_lng'];
        $gis_lat =  empty($_POST['gis_lat']) ? "" : $_POST['gis_lat'];
        $this->promossion($uid,$iflogin);
        if( empty($car_status) ){
             $this->response(["code" => -100, "message" => "请选择一个待小修问题" ], 'json');
        }
        // $this->response(["code" => 111, "data" => $_FILES["picture"] ], 'json');die;
        if(isset($_FILES["picture"])){
            $_FILES["uploadfile"]=$_FILES["picture"];
            $root   =  "/opt/web/news/Public/img/pic";
            $root1="/" . date("Y") . "/" . date("md") . "/";
            
            $filename = md5(time().rand(100,100)) . ".jpg";
            $filepath = $root.$root1.$filename;
            $rr = $this->createdir($root.$root1, 0777);
            $pic=move_uploaded_file($_FILES["uploadfile"]["tmp_name"], $filepath);
            if($pic){
                // $this->picmark($filepath,$filepath);
                // $status=1;
                // $info="图片上传成功";
                $pic=['path'=>'xiaomi.baojia.com/Public/img/pic'.$root1.$filename,'short_path'=>'Public/img/pic'.$root1.$filename];
            }
        }
        $data = [
                    "uid" => $uid,
                    "operate" => 6, //6 = 待小修,
                    "car_status" => $car_status,
                    "rent_content_id" => $rent_content_id,
                    "plate_no" => $plate_no,
                    "time" => time(),
                    "pic1" => $pic['short_path'],
                    "gis_lng" => $gis_lng,
                    "gis_lat" => $gis_lat
            ];
            // var_dump($data);die;
        $find = M("operation_logging")->where(["rent_content_id" => $rent_content_id,"operate"=>6,"car_status"=>$car_status,"status"=>0])->getField("id");
        if(!empty($find)){
            $this->response(["code" => -1, "message" => "该问题已经上报" ], 'json');
        }
        $sell_status = M("rent_content")->where(["id"=>$rent_content_id])->getField('sell_status');
        if($sell_status == 1){
            $res = $this->add_record( $uid, $iflogin , $data);
            if($res){
                M("rent_sku_hour")->where(["rent_content_id"=>$rent_content_id])->save(["operate_status"=>11]);
                $this->response(["code" => 100, "message" => "小修上报成功" ], 'json');
            }else{
                $this->response(["code" => -100, "message" => "小修上报成功" ], 'json');
            }
        }else{
                $this->response(["code" => -1111, "message" => "非上架车辆不允许上报小修" ], 'json');
        }
        
        
        
   }
   //小修记录表
    public function repair_record($uid = '', $iflogin = '',$rent_content_id = ''){
        $this->promossion($uid,$iflogin);
        $rent_info = M("rent_content")->where(["id"=>$rent_content_id,"sort_id"=>112])->find();
        if(!$rent_info){
          $this->response(["code" => -1001, "message" => "车辆不存在"], 'json');
        }
        $location = M("operation_logging")->field("operate,rent_content_id,plate_no,car_status,id,pic1")->where("rent_content_id = {$rent_content_id} and operate = 6 and status = 0")->select();
       foreach ($location as $k => $v) {
          if( $v['car_status'] == 3 ){
            $location[$k]['status'] = "脚蹬子缺失";
          }if( $v['car_status'] == 4 ){
            $location[$k]['status'] = "车灯损坏";
          }if( $v['car_status'] == 5 ){
            $location[$k]['status'] = "车支子松动";
          }if( $v['car_status'] == 6 ){
            $location[$k]['status'] = "车灯松动";
          }if( $v['car_status'] == 7 ){
            $location[$k]['status'] = "车把松动";
          }if( $v['car_status'] == 8 ){
            $location[$k]['status'] = "鞍座丢失";
          }if( $v['car_status'] == 9 ){
            $location[$k]['status'] = "二维码丢失";
          }
       }
       $this->response(["code" => 100, "data" => $location ], 'json');
        // echo "<pre>";
        // print_r($location);
    }
   //小修完成
   public function repair_done($uid = '', $iflogin = '', $rent_content_id = '', $plate_no = '',$gis_lng = '',$gis_lat = '',$pic = '', $car_status = ''){
        $uid = $_POST['uid'];
        $rent_content_id = $_POST['rent_content_id'];
        $id = $_POST['id'];  //1 返回上次图片   3 提交表单
        $plate_no = $_POST['plate_no'];
        $car_status = $_POST['car_status'];
        $gis_lng = empty($_POST['gis_lng']) ? "" : $_POST['gis_lng'];
        $gis_lat =  empty($_POST['gis_lat']) ? "" : $_POST['gis_lat'];
        $this->promossion($uid,$iflogin);
        if( !empty($_FILES["picture"]["tmp_name"]) ){
            $_FILES["uploadfile"]=$_FILES["picture"];
            $root   =  "/opt/web/news/Public/img/pic";
            $root1="/" . date("Y") . "/" . date("md") . "/";
            
            $filename = md5(time().rand(100,100)) . ".png";
            $filepath = $root.$root1.$filename;
            $rr = $this->createdir($root.$root1, 0777);
            $pic=move_uploaded_file($_FILES["uploadfile"]["tmp_name"], $filepath);
            if($pic){
                // $this->picmark($filepath,$filepath);
                // $status=1;
                // $info="图片上传成功";
                $pic=['path'=>'xiaomi.baojia.com/Public/img/pic'.$root1.$filename,'short_path'=>'Public/img/pic'.$root1.$filename];
            }
           $data =[
                   "uid" => $uid,
                    "operate" => 4, //4 = 完成小修,
                    "rent_content_id" => $rent_content_id,
                    "car_status" => $car_status,
                    "plate_no" => $plate_no,
                    "time" => time(),
                    "pic2" => $pic['short_path'],
                    "gis_lng" => $gis_lng,
                    "gis_lat" => $gis_lat
           ];
           $info = $this->add_record( $uid, $iflogin , $data);
           //修改待小修记录状态
           $info1 = M("operation_logging")->where(["id" => $id])->save(['status'=>1,"pic2"=>$pic['short_path']]);
           if($info1 && $info){
            $unfinished =  M("operation_logging")->where(["rent_content_id"=>$rent_content_id,"operate" => 6,"status"=>0])->getField('id');
            if(empty($unfinished)){
                M("rent_sku_hour")->where(["rent_content_id"=>$rent_content_id])->save(["operate_status"=>8]);
            }
            $this->response(["code" => 1001, "message" => "完成小修" ], 'json');
           }else{
            $this->response(["code" => -1001, "message" => "小修失败" ], 'json');
           }
        }
   }
    protected function createdir($path, $mode) {
        if (is_dir($path)) {  //判断目录存在否，存在不创建
            //echo "目录'" . $path . "'已经存在";
            return true;
        } else { //不存在创建
            $re = mkdir($path, $mode, true); //第三个参数为true即可以创建多极目录
            if ($re) {
                //echo "目录创建成功";
                return true;
            } else {
                //echo "目录创建失败";
                return false;
            }
        }
    }
    private function timediff($ent_time, $start_time) {
       $time = abs($ent_time - $start_time);
       $start = 0;
       $y = floor($time / 31536000);
       if ($start || $y) {
           $start = 1;
           $time -= $y * 31536000;
           if ($y)
               $string .= $y . "年";
       }
       $m = floor($time / 2592000);
       if ($start || $m) {
           $start = 1;
           $time -= $m * 2592000;
           if ($m)
               $string .= $m . "月";
       }
       $d = floor($time / 86400);
       if ($start || $d) {
           $start = 1;
           $time -= $d * 86400;
           if ($d)
               $string .= $d . "天";
       }
       $h = floor($time / 3600);
       if ($start || $h) {
           $start = 1;
           $time -= $h * 3600;
           if ($h)
               $string .= $h . "时";
       }
       $s = floor($time / (60));
       if ($start || $s) {
           $start = 1;
           $time -= $s * 60;
           if ($s)
               $string .= $s . "分钟";
       }
       if (empty($string)) {
           return abs($ent_time - $start_time) . '秒';
       }
       return $string;
   }
   public function trade_line($rent_content_id=""){
    $return = [];
    $gps = D('Gps');
    $orderid = M("baojia_mebike.trade_order_return_log")->where(["rent_content_id"=>$rent_content_id])->order("id desc")->limit(1)->field("order_id")->find();
    if(empty($orderid)){
       $return=(object)[];
        return $return;
    }
    $order=M('trade_order')->field("car_item_id,end_time,begin_time")->where("id=".$orderid['order_id'])->find();
    $beginTime=$order['begin_time'];
    $endtime=$order['end_time'];
    $device=M('car_item_device')->where('car_item_id='.$order['car_item_id'])->field('imei')->find();
    // echo "<pre>";
    // print_r($order);
    $car_return_info=M('trade_order_car_return_info')->field("take_lng,take_lat,return_lng,return_lat")->where('order_id='.$orderid['order_id'])->find();
            if($car_return_info && $car_return_info['take_lng']>0 && $car_return_info['take_lat']>0){
                $liststart['lat']=(float)$car_return_info['take_lat'];
                $liststart['lon']=(float)$car_return_info['take_lng'];
                // $newpos = $gps->bd_encrypt($car_return_info['take_lat'],$car_return_info['take_lng']);
                // $liststart['id']=0;
                // if(compare_version(I('post.version'),"3.0.0") >= 0){
                //     $liststart['lat']=(float)$car_return_info['take_lat'];
                //     $liststart['lon']=(float)$car_return_info['take_lng'];
                // }else{
                //     $liststart['lat']=$newpos['lat'];
                //     $liststart['lon']=$newpos['lon'];   
                // }
                // $liststart['speed']=0;
                // $liststart['course']=0;
                // $liststart['accstatus']=0;
                // $liststart['datetime']=date('Y-m-d',$order['begin_time']);
            }
            if($car_return_info && $car_return_info['return_lng']>0 && $car_return_info['return_lat']>0){
                $listend['lat']=(float)$car_return_info['return_lat'];
                $listend['lon']=(float)$car_return_info['return_lng'];
                // $newpos = $gps->bd_encrypt($car_return_info['return_lat'],$car_return_info['return_lng']);
                // $listend['id']=0;
                // if(compare_version(I('post.version'),"3.0.0") >= 0){
                    // $listend['lat']=(float)$car_return_info['return_lat'];
                    // $listend['lon']=(float)$car_return_info['return_lng'];
                // }else{
                //     $listend['lat']=$newpos['lat'];
                //     $listend['lon']=$newpos['lon'];
                // }   
                // $listend['speed']=0;
                // $listend['course']=0;
                // $listend['accstatus']=0;
                $listend['datetime']=date('Y-m-d H:i:s',$order['end_time']);
            }
        $imei = $device['imei'];
        $condition="imei='$imei'";
        if($beginTime && $endtime)$condition.=" AND datetime between $beginTime AND $endtime";
        $PointArr1 = M('fed_gps_location')->field("latitude,longitude,datetime")->where("$condition")->order('datetime asc')->select();
        // $res = M('fed_gps_location')->getLastsql();
        //切换备份表进行查询
        $tb=M("",null,$this->box_config)->query("show tables like 'gps_location_%'");
        $bt=date('ymd',$beginTime);
        $et=date('ymd',$endtime);
        foreach($tb as $key=>$v){
            foreach($v as $k1=>$v1){
                if(substr($v1,-3)!="zid"){
                   if(trim($v1,'gps_location_')>=$bt && trim($v1,'gps_location_')>=$et){
                   $tbs1[]=$v1;
                   }
                }
            }
            
        }
        sort($tbs1);    
        if($tbs1[0]){
            $PointArr2=M("",null,$this->box_config)->query("select latitude,longitude from {$tbs1[0]} where  imei='{$imei}' and longitude>0 and latitude>0 and datetime>={$beginTime} and datetime<={$endtime} order by datetime asc");
        }
        if(count($PointArr1)>0 && count($PointArr2)>0){
            $PointArr2=array_merge($PointArr2,$PointArr1);
        }elseif(count($PointArr1)>1){
            $PointArr2=$PointArr1;
        }
        $return['start'] = $liststart;
        $return['end'] = $listend;
        foreach ($PointArr2 as $k => $v) {
            $gd = $gps->gcj_encrypt($v["latitude"],$v["longitude"]);
            $PointArr2[$k]['latitude'] = $gd['lat'];
            $PointArr2[$k]['longitude'] = $gd['lon'];
        }
        $return['guiji'] = $PointArr2;
        $return['use_time'] = "还车于:".$listend['datetime'];
        // $return['use_time_diff'] = "手机:".$this->timediff(strtotime($listend['datetime']),time())."前";
        return $return;
        // var_dump($PointArr2);

   }
   //根据车牌号查询信息
   public function plate($plate_no = ''){
             $car_item_id = M("car_item_verify")->where(["plate_no" => $plate_no])->getField("car_item_id");
             $car = M("rent_content")->where(["car_item_id" => $car_item_id])->field("id,sell_status")->find();
             $res = M("rent_content")->alias("rc")
                   ->join("car_item_device cid ON cid.car_item_id = rc.car_item_id","left")
                   ->join("car_item ci on ci.id = rc.car_item_id","left")
                   ->join("car_item_color cic on cic.id = ci.color","left")
                   ->join("fed_gps_additional fga ON fga.imei = cid.imei","left")
                   ->join("fed_gps_status fgs ON fgs.imei = cid.imei","left")
                   ->join("car_info_picture cip on cip.car_info_id=rc.car_info_id and cip.car_color_id=ci.color","left")
                   ->field("rc.id,rc.city_id,cip.url picture_url,cid.imei")
                   ->where("rc.id = ".$car['id']."  AND rc.sort_id = 112 AND cip.status=2")
                   ->find();
             $gps = D('Gps');
             $info = M("baojia_box.gps_status",null,$this->box_config)->where(["imei"=>$res['imei']])->field("id,imei,latitude,longitude,datetime,lastonline")->find();
             $gd = $gps->gcj_encrypt($info["latitude"],$info["longitude"]);
             $info["gd_latitude"] = $gd["lat"];
             $info["gd_longitude"] = $gd["lon"];

             $bd = $gps->bd_encrypt($info["gd_latitude"],$info["gd_longitude"]);
             $info["bd_latitude"] = $bd["lat"];
             $info["bd_longitude"] = $bd["lon"];
             $info["location"] = $this->GetAmapAddress( $info["gd_longitude"], $info["gd_latitude"]);
             $res['info'] = $info;
             // echo "<pre/>";
             // print_r($res);
             return $res;
   }
   //预约接口
       public function have_order($plate_no = '', $uid = '' ,$iflogin = '',$gis_lng = '',$gis_lat = '' ,$rent_status = "",$req_code= ""){
         $this->promossion($uid,$iflogin);
         if( !empty($plate_no) ){
            $res = $this->plate($plate_no);
            // var_dump($res);die;
         }
         if( $req_code == 1 ){ //点击图标查询信息
              // if( $rent_status == 9 )      {$data['status'] = -1;$status = "缺电";}//缺电
              // elseif( $rent_status == 3 )  {$data['status'] = -1;$status = "两日无单";}//两日无单
              // elseif( $rent_status == 4 )  {$data['status'] = -1;$status = "有单无程";}//有单无程
              // elseif( $rent_status == 5 )  {$data['status'] = -1;$status = "待小修";}//待小修
              // else{
              //   $data['status'] = 1;
              //  }
                $data['status'] = 1;  //预约按钮可点
                $data['type']=2;
                $data["picture_url"] = "http://pic.baojia.com/b/".$res['picture_url'];
                $data["car_location"] = $res['info']['location'];
                // $data["return_title"] = "该车为{$status}车辆，可能会被用户租用，暂不可预约，可直接前往换电";
                $data["return_title"] = "";
                $this->response(["code" => -1, "data"=>$data ], 'json');
             
         }
         
         if( $req_code == 0 ){  //首页加载调用查看有无预约
            $order = M("baojia_mebike.have_order")->where("uid = {$uid} and status=0")->field('id,create_time,rent_status,plate_no')->find();
            if( !empty($order) ){
                $res = $this->plate($order['plate_no']);
                $data['type'] = 1;
                $data['time'] = 1800;  //预约总时间
                $data['start_time'] = $order['create_time'];  //预约开始时间
                $data['rest_time'] = 1800-(time()-$order['create_time']);
                $data["picture_url"] = "http://pic.baojia.com/b/".$res['picture_url'];
                $data["rent_status"] = $order['rent_status'];
                $data["plate_no"] = $order['plate_no'];
                $data["gis_lat"] = $res['info']['gd_latitude'];
                $data["gis_lng"] = $res['info']['gd_longitude'];
                $data["bd_latitude"] = $res['info']['bd_latitude'];
                $data["bd_longitude"] = $res['info']['bd_longitude'];
                $data["city_id"] = $res['city_id'];
                $data["car_location"] = $res['info']['location'];
                $this->response(["code" => -1, "data"=>$data,"message" => "同一时间只能预约一辆车"], 'json');
           }else{
                $this->response(["code" => -1, "data" => ['type'=>0,'message'=>'没有预约订单']], 'json');
           }
         }
         if( $req_code == 2 ){ //点击立即预约按钮进行操作
                $car_item_id = M("car_item_verify")->where(["plate_no" => $plate_no])->getField("car_item_id");
                $rent_content_id = M("rent_content")->where(["car_item_id" => $car_item_id])->getField("id");
                $trade_order = M("trade_order")->where("rent_type = 3 AND STATUS >= 10100 AND STATUS < 80200 AND STATUS <> 10301 and rent_content_id={$rent_content_id}")->find();
                if( !empty($trade_order) ){
                    $this->response(["code" => -10, "message" => "该车有订单暂不可预约"], 'json');
                 }
                $order1 = M("baojia_mebike.have_order")->where("status=0 and plate_no='{$plate_no}'")->getField('id');
                 if( !empty($order1) ){
                    $this->response(["code" => -10, "message" => "该车已被预约"], 'json');
                 }
                $add = [
                       'uid' => $uid,
                       'plate_no' => $plate_no,
                       'create_time' => time(),
                       'status' => 0,
                       'gis_lng' => $gis_lng,
                       'gis_lat' => $gis_lat,
                       'rent_status' => $rent_status

                    ];
             $result = M("baojia_mebike.have_order")->add($add);
             if($result){
                 if($rent_status==3||$rent_status==4||$rent_status==5||$rent_status==9){
                  $up = M("rent_content")->where(["id"=>$res['id']])->save(['sell_status'=>-14,"update_time" => time()]);//修改车辆状态为-14小蜜预约换电下线
                 }
             } 
             $data['type'] = 1;
             $data["picture_url"] = "http://pic.baojia.com/b/".$res['picture_url'];
             $data["start_time"] = time();
             $data['rest_time'] = 1800;
             $data['time'] = 1800;  //预约总时间
             $data["car_location"] = $res['info']['location'];
             $data["return_title"] = "预约成功";
             $this->response(["code" => 1, "data" => $data], 'json');  
         }
         
    }
    //定时任务  运维端预约换电车辆 30分钟自动取消
    public function order_close($plate_no = '', $uid = '', $rent_status = ''){
      if( !empty($plate_no) && !empty($uid)  ){
        $result = M("baojia_mebike.have_order")->where(['status'=>0,'plate_no'=>$plate_no,'uid'=>$uid])->save(['status'=>1,'update_time'=>time()]);
        if($result){
            $res = $this->plate($plate_no);
            if($rent_status==3||$rent_status==4||$rent_status==5||$rent_status==9){
              $up = M("rent_content")->where(["id"=>$res['id']])->save(['sell_status'=>1,"update_time" => time()]);//修改车辆状态为1
            }
            $this->response(["code" => 1, "message" => "取消预约成功"], 'json');
        }else{
            $this->response(["code" => -1, "message" => "取消预约成功"], 'json');
        }
      }
        $res = M("baojia_mebike.have_order")->field('id,create_time')->where(['status'=>0])->select();
        foreach ($res as $val){
            $c_time = (time()-$val['create_time']);
            if($c_time >= 1800){
                M("baojia_mebike.have_order")->where(['id'=>$val['id']])->setField(['status'=>1,'update_time'=>time()]);
            }
        }
    }



}