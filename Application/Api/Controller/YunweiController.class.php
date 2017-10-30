<?php
/**
 * Created by PhpStorm.
 * User: CHI
 * Date: 2017/7/21
 * Time: 16:04
 */
namespace Api\Controller;
use Think\Controller\RestController;
use Think\Upload;
class YunweiController extends RestController {
	
	private $PI = 3.14159265358979324;
	private $url = 'http://zykuaiche.com.cn:81/g/service';
    private $url2 = 'http://zykuaiche.com.cn:81/s/service';

    private $key = '987aa22ae48d48908edafda758ae82a8';

    private $box_config = 'mysqli://api-baojia:CSDV4smCSztRcvVb@10.1.11.14:3306/baojia_box';
    private $baojia_config = 'mysqli://api-baojia:CSDV4smCSztRcvVb@10.1.11.2:3306/baojia';
	private $ceshi_config = 'mysql://apitest-baojia:TKQqB5Gwachds8dv@10.1.11.110:3306/baojia_mebike#utf8';  //测试数据库
	
    public function index()
    {
		
       $this->display('yunwei');
    }
	
	//强制更新app
    public   function  forcedUpdate($version='1.3.0',$device_os = ''){
        // file_put_contents($_SERVER['DOCUMENT_ROOT']."canshu7878.txt",json_encode($_POST));
        if(empty($version)){
            $this->response(["code" => 0, "message" => "参数错误"], 'json');
        }
        $version = explode('.',$version);
        if($device_os == 'Android'){
           $newVersion = '1.3.1';
        }else{
           $newVersion = '1.3.3';
        }
        $version2 = explode('.',$newVersion);
        if(intval($version[0]) < intval($version2[0])){
           $data["isForced"] = 1;
        }else if(intval($version[0]) > intval($version2[0])){
           $data["isForced"] = 0;
        }else{
            if(intval($version[1]) < intval($version2[1])){
                $data["isForced"] = 1;
            }else if(intval($version[1]) > intval($version2[1])){
                $data["isForced"] = 0;
            }else{
                if(intval($version[2]) < intval($version2[2])){
                    $data["isForced"] = 1;
                }else if(intval($version[2]) > intval($version2[2])){
                    $data["isForced"] = 0;
                }else{
                    $data["isForced"] = 0;
                }
            }
        }
        $data['title'] = "更新提示";
        $data['version'] = $newVersion;
        $data['content'] = "1.修复换电闪退情况\r\n2.兼容部分安卓机型";
        if($device_os == 'Android'){
            $data['saveUrl'] = "http://xiaomi.baojia.com/Public/app/app-release.apk";
            $files_arr = get_headers("http://xiaomi.baojia.com/Public/app/app-release.apk",true);
            $data['contentLength'] = $files_arr['Content-Length']?$files_arr['Content-Length']:0;
        }else if($device_os == 'iOS'){
            $data['saveUrl'] = "https://www.pgyer.com/71gZ";
        }else{
            $data['saveUrl'] = "";
        }
        $this->response(["code" => 1, "message" => "数据接收成功","data"=>$data], 'json');
    }
    //检测是否换电接口
    public  function   detectionElectricity($rent_content_id=0){
//          $plate_no = "DD919130";
//          $map['rcs.plate_no'] = $plate_no;
//          $map['rc.id'] = I('post.rent_content_id');
          $map['rc.id'] = $rent_content_id;
          $map['rcs.address_type'] = 99;
          $map['rcs.sort_id'] = 112;
          $map['rcs.plate_no'] = array('neq','');
          $map['rc.car_info_id'] = array('neq',30150);
          $reslut = M('rent_content')->alias('rc')
                    ->field('rc.id,cid.imei')
                    ->join('rent_content_search rcs ON rc.id = rcs.rent_content_id','left')
                    ->join('car_item_device cid ON rc.car_item_id = cid.car_item_id')
                    ->where($map)->find();
          if($reslut){
              //查询电量
              $electricity = D('FedGpsAdditional')->electricity_info($reslut['imei']);
			 
              if($electricity['residual_battery1'] <= 35 && $electricity['residual_battery2'] <= 35){
                  $this->response(["code" => 1, "message" => "该车缺电请换电","data"=>["rent_content_id"=>$reslut['id'],"residual_battery"=>$electricity['residual_battery1']]], 'json');
              }else{
                  $this->response(["code" => 2, "message" => "无需换电","data"=>["rent_content_id"=>$reslut['id'],"residual_battery"=>$electricity['residual_battery1']]], 'json');
              }
          }else{
              $this->response(["code" => 0, "message" => "该车辆不存在"], 'json');
          }
    }
	
	//完成换电接口
    public  function  completeExchange($rent_content_id = 0, $residual_battery = 0,$uid=0,$operationId=0,$plate_no="",$gis_lng=0,$gis_lat=0,$power_per=100){
        $map['rc.id'] = $rent_content_id;
        $reslut = M('rent_content')->alias('rc')->field('rc.id,cid.imei,cid.device_type,rc.car_item_id')
            ->join('car_item_device cid ON rc.car_item_id = cid.car_item_id')
            ->where($map)->find();
        if($reslut){
            //判断操作记录是否存在
            $model = M('operation_logging');
            $ol_arr = $model->where(['id'=>$operationId])->getField('id');
            if(!$ol_arr){
                $this->response(["code" => 0, "message" => "参数错误"], 'json');
            }
            if(empty($uid) || empty($plate_no) || empty($gis_lng)){
                $this->response(["code" => 0, "message" => "参数错误"], 'json');
            }
           /* $hd_qian = D('FedGpsAdditional')->electricity_info($reslut['imei']);
            $residual_battery = $hd_qian['residual_battery2'];
            if($residual_battery <= 35){
                if($reslut['device_type'] == 16){
                    $this->url2 = $this->url = "http://yd.zykuaiche.com:81/s/service";
                    $imei = ltrim($reslut['imei'],"0");
                    $api_result_json = $this->setpower($imei,$power_per/100);
                    if($api_result_json["rtCode"] == 0){
                        $this->setDbPower($reslut['imei'],$power_per);
                    }
                }else{
                    $this->setDbPower($reslut['imei'],$power_per);
                }
            }else{
                $this->response(["code" => -4, "message" => "新电池检测不合格，请重新更换电池"], 'json');
            }
            $res = D('FedGpsAdditional')->electricity_info($reslut['imei']);
            if($res['residual_battery1'] > $residual_battery && $res['residual_battery2'] > $residual_battery){
                //换电完成上架待租
                M('rent_content','',$this->baojia_config)->where(["id"=>$reslut['id']])->setField('sell_status',1);
                //更新里程和电量
                $edata = array('battery_capacity'=>$power_per,'running_distance'=>60);
                M('rent_content_ext','',$this->baojia_config)->where(["rent_content_id"=>$reslut['id']])->setField($edata);
                //车牌号为空
                if(empty($plate_no) || $plate_no == "null"){
                    $plate_no = M("car_item_verify",null,$this->baojia_config)->where(["car_item_id"=>$reslut['car_item_id']])->getField("plate_no");
                }
                //查询我预约的车辆订单完成换电取消
                $hMap['plate_no'] = $plate_no;
                $hMap['uid'] = $uid;
                $hMap['status'] = 0;
                $have_order = M("baojia_mebike.have_order")->where($hMap)->getField('id');
                if($have_order){
                    M("baojia_mebike.have_order")->where(['id'=>$have_order])->setField(['status'=>1,'update_time'=>time()]);
                }
                //更新操作记录
                $olDdata['gis_lng'] = $gis_lng;
                $olDdata['gis_lat'] = $gis_lat;
                $olDdata['before_battery'] = $residual_battery;
                $olDdata['step'] = 3;
                $olDdata['time'] = time();
                $model->where(['id'=>$ol_arr])->save($olDdata);
                //清除redis历史电压
                if($reslut['imei']){
                    $redis = new \Redis();
                    $redis->pconnect('10.1.11.82',6379,0.5);
                    $key='prod:boxxan_analyzer'.$reslut['imei'];
                    $length = $redis->LLEN($key);
                    $redis->LTRIM($key,$length,-1);
                }
                $this->response(["code" => 1, "message" => "已完成换电，请上传照片","data"=>["operationId"=>$ol_arr],"residual_battery"=>$res['residual_battery1']], 'json');
            }else{
                $this->response(["code" => -4, "message" => "新电池检测不合格，请重新更换电池"], 'json');
            }*/

            $hd_qian = D('FedGpsAdditional')->electricity_info($reslut['imei']);
            $residual_battery = $hd_qian['residual_battery2'];
            $url = 'http://wg.baojia.com/simulate/service';
            //读取网关的key存到redis 1分钟失效
            $redis = new \Redis();
//            $redis->connect('127.0.0.1', 6379);
            $redis->pconnect('10.1.11.82',6379,0.5);
            $dyKey = ltrim($reslut['imei'],"0").":Voltage";
            $kValue = $redis->get($dyKey);
            if($kValue){
                $cData['carId'] = ltrim($reslut['imei'],"0");
                $cData['key']   = $kValue;
                $cData['cmd']   = 'resultQuery';
                $res2 = $this->VoltagePost($cData,$url);
                if($res2['rtCode'] == '0' && strlen($res2['rtCode']) > 0){
                    $electricity = $this->getDumpEle($res2['result']['voltage']);
                    $electricity = $electricity ? intval($electricity * 100) : 0;
                    if ($electricity > 25) {
                        //换电完成上架待租
                        M('rent_content','',$this->baojia_config)->where(["id"=>$reslut['id']])->setField('sell_status',1);
                        //车牌号为空
                        if(empty($plate_no) || $plate_no == "null"){
                            $plate_no = M("car_item_verify",null,$this->baojia_config)->where(["car_item_id"=>$reslut['car_item_id']])->getField("plate_no");
                        }
                        //查询我预约的车辆订单完成换电取消
                        $hMap['plate_no'] = $plate_no;
                        $hMap['uid'] = $uid;
                        $hMap['status'] = 0;
                        $have_order = M("baojia_mebike.have_order")->where($hMap)->getField('id');
                        if($have_order){
                            M("baojia_mebike.have_order")->where(['id'=>$have_order])->setField(['status'=>1,'update_time'=>time()]);
                        }
                        //更新操作记录
                        $olDdata['gis_lng'] = $gis_lng;
                        $olDdata['gis_lat'] = $gis_lat;
                        $olDdata['before_battery'] = $residual_battery;
                        if($electricity){
                            $date['desc'] = '换电时获取时时电压的电量:'.$electricity;
                        }
                        $olDdata['step'] = 3;
                        $olDdata['time'] = time();
                        $model->where(['id'=>$ol_arr])->save($olDdata);
                        //清除redis历史电压
                        if($reslut['imei']){
                            $redis = new \Redis();
                            $redis->pconnect('10.1.11.82',6379,0.5);
                            $key='prod:boxxan_analyzer'.$reslut['imei'];
                            $length = $redis->LLEN($key);
                            $redis->LTRIM($key,$length,-1);
                        }
                        $this->response(["code" => 1, "message" => "已完成换电，请上传照片", "data" => ["operationId" => $operationId, "residual_battery" => $electricity]], 'json');
                    } else {
                        $this->response(["code" => -4, "message" => "新电池检测不合格，请重新更换电池"], 'json');
                    }
                }else{
                    $this->response(["code" => 0, "message" => "再试一次"], 'json');
                }
            }else {
                $data['carId'] = ltrim($reslut['imei'], "0");
                $data['type'] = 34;
                $data['cmd'] = 'statusQuery';
//              $data['directRt'] = 'false';
                $res = $this->VoltagePost($data, $url);
                if ($res['rtCode'] == '0') {
                    if (!$kValue) {
                        $redis->set($dyKey, $res['msgkey']);
                        $redis->expire($dyKey, 20);
                    }
                    $cData['carId'] = ltrim($reslut['imei'], "0");
                    $cData['key'] = $res['msgkey'];
                    $cData['cmd'] = 'resultQuery';
                    $res2 = $this->VoltagePost($cData, $url);
                    if($res2['rtCode'] == '0' && strlen($res2['rtCode']) > 0){
                        $electricity = $this->getDumpEle($res2['result']['voltage']);
                        $electricity = $electricity ? intval($electricity * 100) : 0;
                        if ($electricity > 35) {
                            //换电完成上架待租
                            M('rent_content','',$this->baojia_config)->where(["id"=>$reslut['id']])->setField('sell_status',1);
                            //车牌号为空
                            if(empty($plate_no) || $plate_no == "null"){
                                $plate_no = M("car_item_verify",null,$this->baojia_config)->where(["car_item_id"=>$reslut['car_item_id']])->getField("plate_no");
                            }
                            //查询我预约的车辆订单完成换电取消
                            $hMap['plate_no'] = $plate_no;
                            $hMap['uid'] = $uid;
                            $hMap['status'] = 0;
                            $have_order = M("baojia_mebike.have_order")->where($hMap)->getField('id');
                            if($have_order){
                                M("baojia_mebike.have_order")->where(['id'=>$have_order])->setField(['status'=>1,'update_time'=>time()]);
                            }
                            //更新操作记录
                            $olDdata['gis_lng'] = $gis_lng;
                            $olDdata['gis_lat'] = $gis_lat;
                            $olDdata['before_battery'] = $residual_battery;
                            if($electricity){
                                $date['desc'] = '换电时获取时时电压的电量:'.$electricity;
                            }
                            $olDdata['step'] = 3;
                            $olDdata['time'] = time();
                            $model->where(['id'=>$ol_arr])->save($olDdata);
                            //清除redis历史电压
                            if($reslut['imei']){
                                $redis = new \Redis();
                                $redis->pconnect('10.1.11.82',6379,0.5);
                                $key='prod:boxxan_analyzer'.$reslut['imei'];
                                $length = $redis->LLEN($key);
                                $redis->LTRIM($key,$length,-1);
                            }
                            $this->response(["code" => 1, "message" => "已完成换电，请上传照片", "data" => ["operationId" => $operationId, "residual_battery" => $electricity]], 'json');
                        } else {
                            $this->response(["code" => -4, "message" => "新电池检测不合格，请重新更换电池"], 'json');
                        }
                    }else{
                        $this->response(["code" => 0, "message" => "再试一次"], 'json');
                    }
                }else if($res['rtCode'] == 1){
                    $this->response(["code" => 0, "message" => "设备接收命令并返回失败"], 'json');
                }else if($res['rtCode'] == 3){
                    $this->response(["code" => 0, "message" => "设备断开连接"], 'json');
                }else if($res['rtCode'] == 6){
                    $this->response(["code" => 0, "message" => "命令重复"], 'json');
                }else{
                    $this->response(["code" => 0, "message" => "请求失败"], 'json');
                }
            }
        }else{
            $this->response(["code" => 0, "message" => "该车辆不存在"], 'json');
        }
    }

    //换电接口
    public  function  electricity($rent_content_id = 0, $residual_battery = 100,$uid=0,$plate_no="",$gis_lng=0,$gis_lat=0,$power_per=100){
		//app参数
		$newLog ='log_time:'.date('Y-m-d H:i:s');
		file_put_contents($_SERVER['DOCUMENT_ROOT']."换电接口参数.txt",json_encode($_POST).$newLog.PHP_EOL, FILE_APPEND);
        $map['rc.id'] = $rent_content_id;
        $reslut = M('rent_content')->alias('rc')->field('rc.id,cid.imei,cid.device_type,rc.car_item_id')
            ->join('car_item_device cid ON rc.car_item_id = cid.car_item_id')
            ->where($map)->find();
        if($reslut){
            $hd_qian = D('FedGpsAdditional')->electricity_info($reslut['imei']);
            $residual_battery = $hd_qian['residual_battery2'];
            $url = 'http://wg.baojia.com/simulate/service';
            //读取网关的key存到redis 1分钟失效
            $redis = new \Redis();
//            $redis->connect('127.0.0.1', 6379);
            $redis->pconnect('10.1.11.82',6379,0.5);
            $dyKey = ltrim($reslut['imei'],"0").":Voltage";
            $kValue = $redis->get($dyKey);
            if($kValue){
                $cData['carId'] = ltrim($reslut['imei'],"0");
                $cData['key']   = $kValue;
                $cData['cmd']   = 'resultQuery';
                $res2 = $this->VoltagePost($cData,$url);
                if($res2['rtCode'] == '0' && strlen($res2['rtCode']) > 0){
                    $electricity = $this->getDumpEle($res2['result']['voltage']);
                    $electricity = $electricity ? intval($electricity * 100) : 0;
                    if ($electricity > 35) {
                        //换电完成上架待租
                        M('rent_content','',$this->baojia_config)->where(["id"=>$reslut['id']])->setField('sell_status',1);
                        //车牌号为空
                        if(empty($plate_no) || $plate_no == "null"){
                            $plate_no = M("car_item_verify",null,$this->baojia_config)->where(["car_item_id"=>$reslut['car_item_id']])->getField("plate_no");
                        }
                        //查询我预约的车辆订单完成换电取消
                        $hMap['plate_no'] = $plate_no;
                        $hMap['uid'] = $uid;
                        $hMap['status'] = 0;
                        $have_order = M("baojia_mebike.have_order")->where($hMap)->getField('id');
                        if($have_order){
                            M("baojia_mebike.have_order")->where(['id'=>$have_order])->setField(['status'=>1,'update_time'=>time()]);
                        }
                        $operationId = $this->operationLog($uid,$rent_content_id,$plate_no,$gis_lng,$gis_lat,$residual_battery,$electricity);
                        //清除redis历史电压
                        if($reslut['imei']){
                            $redis = new \Redis();
                            $redis->pconnect('10.1.11.82',6379,0.5);
                            $key='prod:boxxan_analyzer'.$reslut['imei'];
                            $length = $redis->LLEN($key);
                            $redis->LTRIM($key,$length,-1);
                        }
                        $this->response(["code" => 1, "message" => "已完成换电，请上传照片", "data" => ["operationId" => $operationId, "residual_battery" => $electricity]], 'json');
                    } else {
                        $this->response(["code" => 2, "message" => "请更换满电量电池"], 'json');
                    }
                }else{
                    $this->response(["code" => 0, "message" => "再试一次"], 'json');
                }
            }else {
                $data['carId'] = ltrim($reslut['imei'], "0");
                $data['type'] = 34;
                $data['cmd'] = 'statusQuery';
//              $data['directRt'] = 'false';
                $res = $this->VoltagePost($data, $url);
                if ($res['rtCode'] == '0') {
                    if (!$kValue) {
                        $redis->set($dyKey, $res['msgkey']);
                        $redis->expire($dyKey, 20);
                    }
                    $cData['carId'] = ltrim($reslut['imei'], "0");
                    $cData['key'] = $res['msgkey'];
                    $cData['cmd'] = 'resultQuery';
                    $res2 = $this->VoltagePost($cData, $url);
                    if($res2['rtCode'] == '0' && strlen($res2['rtCode']) > 0){
                        $electricity = $this->getDumpEle($res2['result']['voltage']);
                        $electricity = $electricity ? intval($electricity * 100) : 0;
                        if ($electricity > 35) {
                            //换电完成上架待租
                            M('rent_content','',$this->baojia_config)->where(["id"=>$reslut['id']])->setField('sell_status',1);
                            //车牌号为空
                            if(empty($plate_no) || $plate_no == "null"){
                                $plate_no = M("car_item_verify",null,$this->baojia_config)->where(["car_item_id"=>$reslut['car_item_id']])->getField("plate_no");
                            }
                            //查询我预约的车辆订单完成换电取消
                            $hMap['plate_no'] = $plate_no;
                            $hMap['uid'] = $uid;
                            $hMap['status'] = 0;
                            $have_order = M("baojia_mebike.have_order")->where($hMap)->getField('id');
                            if($have_order){
                                M("baojia_mebike.have_order")->where(['id'=>$have_order])->setField(['status'=>1,'update_time'=>time()]);
                            }
                            $operationId = $this->operationLog($uid,$rent_content_id,$plate_no,$gis_lng,$gis_lat,$residual_battery,$electricity);
                            //清除redis历史电压
                            if($reslut['imei']){
                                $redis = new \Redis();
                                $redis->pconnect('10.1.11.82',6379,0.5);
                                $key='prod:boxxan_analyzer'.$reslut['imei'];
                                $length = $redis->LLEN($key);
                                $redis->LTRIM($key,$length,-1);
                            }
                            $this->response(["code" => 1, "message" => "已完成换电，请上传照片", "data" => ["operationId" => $operationId, "residual_battery" => $electricity]], 'json');
                        } else {
                            $this->response(["code" => 2, "message" => "请更换满电量电池"], 'json');
                        }
                    }else{
                        $this->response(["code" => 0, "message" => "再试一次"], 'json');
                    }
                }else if($res['rtCode'] == 1){
                    $this->response(["code" => 0, "message" => "设备接收命令并返回失败"], 'json');
                }else if($res['rtCode'] == 3){
                    $this->response(["code" => 0, "message" => "设备断开连接"], 'json');
                }else if($res['rtCode'] == 6){
                    $this->response(["code" => 0, "message" => "命令重复"], 'json');
                }else{
                    $this->response(["code" => 0, "message" => "请求失败"], 'json');
                }
            }
            /*if($residual_battery <= 35){
                if($reslut['device_type'] == 16){
					$this->url2 = $this->url = "http://yd.zykuaiche.com:81/s/service";
                    $imei = ltrim($reslut['imei'],"0");
                    $api_result_json = $this->setpower($imei,$power_per/100);
                    
                    if($api_result_json["rtCode"] == 0){
                        $this->setDbPower($reslut['imei'],$power_per);
                    }
                }else{
                    $this->setDbPower($reslut['imei'],$power_per);
                }
            }
            $res = D('FedGpsAdditional')->electricity_info($reslut['imei']);
            if($res['residual_battery1'] > $residual_battery && $res['residual_battery2'] > $residual_battery){
				//换电完成上架待租
				file_put_contents($_SERVER['DOCUMENT_ROOT']."canshu66.txt",$reslut['id']);
				M('rent_content','',$this->baojia_config)->where(["id"=>$reslut['id']])->setField('sell_status',1);
				//更新里程和电量
				$edata = array('battery_capacity'=>$power_per,'running_distance'=>60);
				M('rent_content_ext','',$this->baojia_config)->where(["rent_content_id"=>$reslut['id']])->setField($edata);
                //车牌号为空
			    if(empty($plate_no) || $plate_no == "null"){
			        $plate_no = M("car_item_verify",null,$this->baojia_config)->where(["car_item_id"=>$reslut['car_item_id']])->getField("plate_no");
			    }
				//查询我预约的车辆订单完成换电取消
				$hMap['plate_no'] = $plate_no;
				$hMap['uid'] = $uid;
				$hMap['status'] = 0;
				$have_order = M("baojia_mebike.have_order")->where($hMap)->getField('id');
				if($have_order){
					M("baojia_mebike.have_order")->where(['id'=>$have_order])->setField(['status'=>1,'update_time'=>time()]);
				}
                $operationId = $this->operationLog($uid,$rent_content_id,$plate_no,$gis_lng,$gis_lat,$residual_battery);
				//清除redis历史电压
				if($reslut['imei']){
					$redis = new \Redis();
					$redis->pconnect('10.1.11.82',6379,0.5);
					$key='prod:boxxan_analyzer'.$reslut['imei'];
					$length = $redis->LLEN($key);
					$redis->LTRIM($key,$length,-1);
				}
                $this->response(["code" => 1, "message" => "已完成换电，请上传照片","data"=>["operationId"=>$operationId],"residual_battery"=>$res['residual_battery1']], 'json');
            }else{
                 $this->response(["code" => -2, "message" => "无需换电或换电没有完成"], 'json');
            }*/
        }else{
            $this->response(["code" => 0, "message" => "该车辆不存在"], 'json');
        }
    }
	
	 //设置数据库的电量
    public function setDbPower($imei = '',$power_per = 100){
        M('fed_gps_additional','',$this->baojia_config)->where(["imei"=>$imei])->setField('residual_battery',$power_per);
		// $data = array('residual_battery'=>$power_per,'update_battery_time'=>time());
		$data = array('residual_battery'=>$power_per);
        M('gps_additional','',$this->box_config)->where(["imei"=>$imei])->setField($data);
    }
	
	//新版完成换电接口
    public  function  complete_electricity($rent_content_id = 297671, $residual_battery = 100,$uid=0,$plate_no="",$gis_lng=0,$gis_lat=0){
        $map['rc.id'] = $rent_content_id;
        $reslut = M('rent_content')->alias('rc')->field('rc.id,cid.imei,cid.device_type')
            ->join('car_item_device cid ON rc.car_item_id = cid.car_item_id')
            ->where($map)->find();
        if($reslut){
            $url = 'http://47.95.32.191:8107/simulate/service';
//            $url = 'http://123.57.173.14:8107/simulate/service';
            $data['carId'] = ltrim($reslut['imei'],"0");

            $data['type'] = 34;
            $data['cmd'] = 'statusQuery';
            $data['directRt'] = 'false';
            $res = $this->VoltagePost($data,$url);
            if($res['rtCode'] == 0){
                $electricity = $this->getDumpEle($res['result']['voltage']);
                $electricity = $electricity?intval($electricity*100):0;
                if($electricity > 35){
                    $operationId = $this->operationLog($uid,$rent_content_id,$plate_no,$gis_lng,$gis_lat,$residual_battery);
                    $this->response(["code" => 1, "message" => "已完成换电，请上传照片","data"=>["operationId"=>$operationId,"residual_battery"=>$electricity]], 'json');
                }else{
                    $this->response(["code" => 2, "message" => "请更换满电量电池"], 'json');
                }
            }else if($res['rtCode'] == 1){
                $this->response(["code" => 0, "message" => "设备接收命令并返回失败"], 'json');
            }else if($res['rtCode'] == 3){
                $this->response(["code" => 0, "message" => "设备断开连接"], 'json');
            }else if($res['rtCode'] == 6){
                $this->response(["code" => 0, "message" => "命令重复"], 'json');
            }else{
                $this->response(["code" => 0, "message" => "请求超时"], 'json');
            }
        }else{
            $this->response(["code" => 0, "message" => "该车辆不存在"], 'json');
        }
    }
	
	//电压转换电量
    public function getDumpEle($voltage){
        $v = ($voltage - 43) / (0.12);
        $battery=0;
        if ($voltage > 53) { // 53 ~
            $battery=1;
        } else if ($voltage >= 43 && $voltage <= 55) { //43~55
            $battery= $v / 100;
        } else if ($voltage < 43 && $voltage > 10) { //43~10
            $battery= -1;
        }
        return sprintf('%.2f',$battery);
    }
	
	

    public  function  operationLog($uid,$rent_content_id,$plate_no,$gis_lng,$gis_lat,$residual_battery,$electricity=0){
        // $model = M('operation_logging','',C('BAOJIA_CS_LINK'));
        $model = M('operation_logging','',$this->baojia_config);
        $date['uid'] = $uid;
        $date['rent_content_id'] = $rent_content_id;
        $date['plate_no'] = $plate_no;
        $date['operate'] = -1;
		$date['status']  = 2;
        $date['gis_lng'] = $gis_lng;
        $date['gis_lat'] = $gis_lat;
        $date['before_battery'] = $residual_battery;
        if($electricity){
            $date['desc'] = '换电时获取时时电压的电量:'.$electricity;
        }
        $date['time'] = time();
        $res = $model->add($date);
        return $res;
    }

     //完成换电上传图片记录type 为 1 最新版本 否则以前版本
    public  function electricityLog($operationId=0,$uid=0,$type=0){
        // $model = M('operation_logging','',C('BAOJIA_CS_LINK'));
        $model = M('operation_logging','',$this->baojia_config);
        $pic = $this->upload();
        if($pic){
            $map['id'] = $operationId;
            $date['pic1'] = $pic;
            if($type == 1){
               $date['step'] = 5;
               $date['operate'] = 1;
            }else{
               $date['status'] = 1;
            }
            $date['time'] = time();
            $res = $model->where($map)->save($date);
            if($res){
				$reason = D('FedGpsAdditional')->theDayCarNum($uid,0);
                $this->response(["code" => 1, "message" => "上传完成",'data'=>$reason['prompt']], 'json');
            }else{
                $this->response(["code" => 0, "message" => "上传失败"], 'json');
            }
        }else{
            $this->response(["code" => 0, "message" => "上传失败"], 'json');
        }
    }


    public function upload(){
        if(isset($_FILES["picture"])){
            $_FILES["uploadfile"]=$_FILES["picture"];
            // $root   =  "E:\php\wamp\www\qiye-admin\Application\public\img";
            $root   =  "/opt/web/news/Public/img/pic";
            $root1="/" . date("Y") . "/" . date("md") . "/";

            $filename = md5(time().rand(100,100)) . ".png";
            $filepath = $root.$root1.$filename;
            $rr = $this->createdir($root.$root1, 0777);
            $pic=move_uploaded_file($_FILES["uploadfile"]["tmp_name"], $filepath);
            if($pic){
                return $pic_url='Public/img/pic'.$root1.$filename;
            }else{
                return $pic_url= '';
            }
        }
        return $pic_url= '';
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

   

    //车辆历史操作记录
    public   function  carHistoryRecord($rent_content_id=295806,$search="",$time=0,$operate=0,$page=1){
        // $model = M('operation_logging','',C('BAOJIA_CS_LINK'));
		$model = M('operation_logging','',$this->baojia_config);
        //搜索内容
        if($search){
			 $umap["user_name"] = array('like', '%' . $search . '%');
			 $umap["status"] = 1;
			 $repair = M('baojia_mebike.repair_member')->field('user_id')->where($umap)->select();
			 $repair = array_column($repair, 'user_id');
			 $lmap['uid'] = array('in', $repair);
        }

        if($time){
            $start_time = strtotime(date("Y-m-d",$time)." 0:0:0");
            $end_time   = strtotime(date("Y-m-d",$time)." 23:59:59");
            $lmap['time'] = array('between',array($start_time,$end_time));
        }
        if($operate){
            $operate = intval($operate);
            if($operate == 1){
                $lmap['operate'] = array('in',array(-1,1,2));
            }else{
                $lmap['operate'] = $operate;
            }
        }else{
            $lmap['operate'] = array('neq',0);
        }
        $lmap['rent_content_id'] = $rent_content_id;
		$page = $page?$page:1;
        $page_size = 15;
        $offset = $page_size * ($page - 1);
        $result = $model->field('id,uid,plate_no,operate,car_status,time,source')->where($lmap)->order('time desc')->limit($offset,$page_size)->select();
        if($result){
            foreach ($result as &$val){
				if($val['operate'] == 5){
					if($val['car_status'] == 1){
						$val['operate_id'] = 51;
					}else{
						$val['operate_id'] = 52;
					}
				}else if($val['operate'] == 100 && I('post.device_os') != 'Android'){
					$val['operate_id'] = 51;
				}else{
					$val['operate_id'] = $val['operate'];
				}
                //查询运维姓名
                $rmap['user_id'] = $val['uid'];
                $repair = M('baojia_mebike.repair_member')->where($rmap)->getField('user_name');
                $val['user_name'] = $repair?$repair:"";
				 //是否弹出浮层
				if($val['operate'] == 16 || $val['operate'] == 17 || $val['operate'] == 6 || $val['operate'] == 4 || $val['operate'] == 5 || $val['operate'] == 100 || $val['operate'] == 1 || $val['operate'] == 2 || $val['operate'] == 11){
					$val['is_spring'] = 1;
				}else if($val['operate'] == 7 || $val['operate'] == 9 || $val['operate'] == 10){
					$val['is_spring'] = 2;
				}else{
					$val['is_spring'] = 0;
				}
                
                if($val['operate'] == 1 || $val['operate'] == -1 || $val['operate'] == 2){
                    $val['operate'] = '换电';
                }else if($val['operate'] == 3){
                    $val['operate'] = '确认回收';
                }else if($val['operate'] == 4){
                    $val['operate'] = '完成小修';
                }else if($val['operate'] == 5 || $val['operate'] == 100){
					if($val['source'] == 1 && $val['operate'] == 100){
						$val['operate'] = '备注信息';   //微信h5运维端老数据
					}else if($val['source'] == 1){
						$val['operate'] = '下架回收,来自H5';
					}else{
						if($val['car_status'] == 2){
						  $val['operate'] = '下架回收,待调度';
						}else{
						  $val['operate'] = '下架回收,待维修';
						}
					}
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
                }else if($val['operate'] == 11){
                    $val['operate'] = '电池丢失';
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
                }else if($val['operate'] == 16){
                    $val['operate'] = '手动矫正';
                }else if($val['operate'] == 17){
                    $val['operate'] = '自动矫正';
                }else{
					$val['operate'] = '疑难';
				}
                
                $val['time1'] = date("H:i",$val['time']);
                $val['date'] = date("Y-m-d",$val['time']);
                unset($val['uid'],$val['time'],$val['car_status']);
            }
        }else{
            $result = [];
        }
        //操作类型
        $type_arr[0]['operate_id'] = 1;
        $type_arr[0]['operate']    = "换电";
        $type_arr[1]['operate_id'] = 8;
        $type_arr[1]['operate']    = "上架待租";
        $type_arr[2]['operate_id'] = 5;
        $type_arr[2]['operate']    = "下架回收";
        $type_arr[3]['operate_id'] = 6;
        $type_arr[3]['operate']    = "待小修";
        $type_arr[4]['operate_id'] = 9;
        $type_arr[4]['operate']    = "疑失";
        $type_arr[5]['operate_id'] = 10;
        $type_arr[5]['operate']    = "疑难";
        $type_arr[6]['operate_id'] = 7;
        $type_arr[6]['operate']    = "车辆丢失";
        $type_arr[7]['operate_id'] = 4;
        $type_arr[7]['operate']    = "完成小修";
        $type_arr[8]['operate_id'] = 3;
        $type_arr[8]['operate']    = "确认回收";
		$type_arr[9]['operate_id'] = 11;
        $type_arr[9]['operate']    = "电池丢失";
		$type_arr[10]['operate_id'] = 12;
        $type_arr[10]['operate']    = "设防";
		$type_arr[11]['operate_id'] = 13;
        $type_arr[11]['operate']    = "撤防";
		$type_arr[12]['operate_id'] = 14;
        $type_arr[12]['operate']    = "启动";
        $type_arr[13]['operate_id'] = 15;
        $type_arr[13]['operate']    = "人工停租";
        $type_arr[14]['operate_id'] = 16;
        $type_arr[14]['operate']    = "手动矫正";
        $type_arr[15]['operate_id'] = 17;
        $type_arr[15]['operate']    = "自动矫正";
//        echo "<pre>";
//        print_r($type_arr);

        $this->response(["code" => 1, "message" => "数据接收完成","data"=>['type_arr'=>$type_arr,"historyRecord"=>$result,"page"=>$page]], 'json');

    }

    //个人历史操作记录
    public   function  personalHistoryRecord($uid=0,$search="",$time=0,$operate=0,$page=1,$device_os='',$version='1.1.3'){
        // $model = M('operation_logging','',C('BAOJIA_CS_LINK'));
		$model = M('operation_logging','',$this->baojia_config);
        if($device_os == 'Android'){
            $pastVersion = '1.1.1';
        }else{
            $pastVersion = '1.1.2';
        }
        $compatible = $this->versionCompare($version,$pastVersion); //当前版本只要大于$pastVersion就显示换电未上传图片

        //搜索内容
        if($search){
            $map['plate_no'] = $search;
            $map['address_type'] = 99;
            $map['sort_id'] = 112;
            $res = M('rent_content_search')->where($map)->getField('rent_content_id');
            $lmap['rent_content_id'] = $res;
        }

        if($time){
            $start_time = strtotime(date("Y-m-d",$time)." 0:0:0");
            $end_time   = strtotime(date("Y-m-d",$time)." 23:59:59");
            $lmap['time'] = array('between',array($start_time,$end_time));
        }
        if($operate){
            $operate = intval($operate);
            if($operate == 1){
                $lmap['operate'] = array('in',array(1,2));
            }else{
                $lmap['operate'] = $operate;
            }
        }
		$lmap['uid'] = $uid;
        if($compatible == 0){
            $lmap['_string'] = " operate in(-1,1,2,3,4,5,6,11)";
        }else{
            $lmap['status'] = array('neq',2);
            $lmap['_string'] = " operate in(-1,1,2,3,4,5,6)";
        }
		$page = $page?$page:1;
        $page_size = 15;
        $offset = $page_size * ($page - 1);
        $result = $model->field('id,plate_no,pic1,pic2,gis_lng,gis_lat,operate,car_status,status,time,source')->where($lmap)->order('time desc')->limit($offset,$page_size)->select();

        if($result){
            foreach ($result as &$val){
				if($val['operate'] == 5){
					if($val['car_status'] == 1){
						$val['operate_id'] = 51;
					}else{
						$val['operate_id'] = 52;
					}
				}else{
				    if(($val['operate'] == -1 || $val['operate'] == 1 || $val['operate'] == 2) && empty($val['pic1']) && $compatible == 0){
                        $val['operate_id'] = -2;  //换电未上传图片状态
                    }else{
                        $val['operate_id'] = $val['operate'];
                    }
				}
                if($val['operate'] == 1){
                    $val['operate'] = '换电设防';
                }else if($val['operate'] == -1){
                    $val['operate'] = '换电未设防';
                }else if($val['operate'] == 2){
                    $val['operate'] = '换电设防失败';
                }else if($val['operate'] == 3){
                    $val['operate'] = '确认回收';
                }else if($val['operate'] == 4){
                    $val['operate'] = '完成小修';
                }else if($val['operate'] == 5){
					if($val['source'] == 1){
						$val['operate'] = '下架回收,来自H5';
					}else{
						if($val['car_status'] == 2){
						    $val['operate'] = '下架回收,待调度';
						}else{
							$val['operate'] = '下架回收,待维修';
						}
					}
                }else if($val['operate'] == 11){
                    $val['operate'] = '电池丢失';
                }else{
                    $val['operate'] = '待小修';
                }
				
				$val['is_spring'] = 1;
                $val['time1'] = date("H:i",$val['time']);
                $val['date'] = date("Y-m-d",$val['time']);
                unset($val['uid'],$val['car_status'],$val['time'],$val['pic1'],$val['pic2'],$val['gis_lng'],$val['gis_lat']);
            }
        }else{
            $result = [];
        }
        //操作类型
        $type_arr[0]['operate_id'] = 1;
        $type_arr[0]['operate']    = "换电设防";
        $type_arr[1]['operate_id'] = 2;
        $type_arr[1]['operate']    = "换电设防失败";
        $type_arr[2]['operate_id'] = 3;
        $type_arr[2]['operate']    = "确认回收";
        $type_arr[3]['operate_id'] = 4;
        $type_arr[3]['operate']    = "完成小修";
        $type_arr[4]['operate_id'] = 5;
        $type_arr[4]['operate']    = "下架回收";
        $type_arr[5]['operate_id'] = 6;
        $type_arr[5]['operate']    = "待小修";
		$type_arr[6]['operate_id'] = 11;
        $type_arr[6]['operate']    = "电池丢失";
//        echo "<pre>";
//        print_r($type_arr);

        $this->response(["code" => 1, "message" => "数据接收完成","data"=>['type_arr'=>$type_arr,"historyRecord"=>$result,'page'=>$page]], 'json');

    }

    //个人操作记录统计
    public   function  historyRecordCount($uid=0){
        // $model = M('operation_logging','',C('BAOJIA_CS_LINK'));
		$model = M('operation_logging','',$this->baojia_config);
        $start_time = strtotime(date("Y-m-d",time())." 0:0:0");
        $end_time   = strtotime(date("Y-m-d",time())." 23:59:59");
        $dq_month_one = date('Y-m-01', strtotime(date("Y-m-d")));
        $start_dq_month=strtotime(date('Y-m-01', strtotime(date("Y-m-d"))));
        $end_dq_month  = strtotime(date('Y-m-d', strtotime("$dq_month_one +1 month -1 day"))." 23:59:59");

        $lmap['time'] = array('between',array($start_time,$end_time));
        $lmap['uid']  = $uid;
		$lmap['status'] = array('neq',2);
        $lmap['operate'] = array('in',array(-1,1,2,3,4,5,6));
        $resday = $model->field('count(*) num,operate')->where($lmap)->group('operate')->select();
        $resday = array_column($resday, 'num','operate');
        $day_arr['total_car'] = array_sum($resday);
        $day_arr['hd_car_num'] = $resday[1] + $resday[2];
        $day_arr['repair_car_num'] = intval($resday[6]);
        $day_arr['recover_car_num'] = intval($resday[5]);
        $day_arr['complete_recover_car_num'] = intval($resday[4]);
        $day_arr['confirm_recovery_car_num'] = intval($resday[3]);
        //当前月的操作记录统计
        $mmap['time'] = array('between',array($start_dq_month,$end_dq_month));
        $mmap['uid']  = $uid;
		$mmap['status'] = array('neq',2);
        $mmap['operate'] = array('in',array(-1,1,2,3,4,5,6));
        $resmonth = $model->field('count(*) num,operate')->where($mmap)->group('operate')->select();
        $resmonth = array_column($resmonth, 'num','operate');
        $month_arr['total_car'] = array_sum($resmonth);
        $month_arr['hd_car_num'] = $resmonth[1] + $resmonth[2];
        $month_arr['repair_car_num'] = intval($resmonth[6]);
        $month_arr['recover_car_num'] = intval($resmonth[5]);
        $month_arr['complete_recover_car_num'] = intval($resmonth[4]);
        $month_arr['confirm_recovery_car_num'] = intval($resmonth[3]);
//        echo "<pre>";
//        print_r($month_arr);
        $this->response(["code" => 1, "message" => "数据接收完成","data"=>['day_count'=>$day_arr,"month_count"=>$month_arr]], 'json');
    }
	
	 //查询个人工作记录详情
    public  function   operationInfo($operationId="55638"){
		
        // $model = M('operation_logging','',C('BAOJIA_CS_LINK'));
		$model = M('operation_logging','',$this->baojia_config);
        $map['id'] = $operationId;
        $result = $model->field('rent_content_id,gis_lng,gis_lat,pic1,pic2,operate,car_status,desc')->where($map)->find();
		
        if($result){
			$address = $this->GetAmapAddress($result['gis_lng'],$result['gis_lat']);
			$result['address'] = $address?$address:"";
			if($result['operate'] == 1 || $result['operate'] == 2 || $result['operate'] == -1){
				$result['title'] = "换电附件";
				$result['car_status'] = "";
                $result['pic1'] = $result['pic1']?$_SERVER['HTTP_HOST']."/".$result['pic1']:"";
			}else if($result['operate'] == 11){
				$result['title'] = "电池丢失附件";
				$result['car_status'] = "";
				$pic_url = [];
				if($result['pic1']){
					$pic_url[]= $_SERVER['HTTP_HOST']."/".$result['pic1'];
				}
				if($result['pic2']){
					$pic_url[]= $_SERVER['HTTP_HOST']."/".$result['pic2'];
				}
				$pic_url = implode(',',$pic_url);
                $result['pic1'] = $pic_url?$pic_url:"";
			}else if($result['operate'] == 4){
				$result['title'] = "完成小修";
				if($result['car_status'] == 3){
					$result['car_status'] = "脚蹬子缺失";
				}else if($result['car_status'] == 4){
					$result['car_status'] = "车支子松动";
				}else if($result['car_status'] == 5){
					$result['car_status'] = "车灯损坏";
				}else if($result['car_status'] == 6){
					$result['car_status'] = "车灯松动";
				}else if($result['car_status'] == 7){
					$result['car_status'] = "车把松动";
				}else if($result['car_status'] == 8){
					$result['car_status'] = "鞍座丢失";
				}else if($result['car_status'] == 9){
					$result['car_status'] = "二维码丢失";
				}else{
					$result['car_status'] = "";
				}
                $result['pic1'] = $result['pic2']?$_SERVER['HTTP_HOST']."/".$result['pic2']:"";
			}else if($result['operate'] == 6){
				 $result['title'] = "待小修附件";
				 if($result['car_status'] == 3){
					$result['car_status'] = "脚蹬子缺失";
				 }else if($result['car_status'] == 4){
					$result['car_status'] = "车支子松动";
				 }else if($result['car_status'] == 5){
					$result['car_status'] = "车灯损坏";
				 }else if($result['car_status'] == 6){
					$result['car_status'] = "车灯松动";
				 }else if($result['car_status'] == 7){
					$result['car_status'] = "车把松动";
				 }else if($result['car_status'] == 8){
					$result['car_status'] = "鞍座丢失";
				 }else{
					$result['car_status'] = "二维码丢失";
				 }
				 $result['pic1'] = $result['pic1']?$_SERVER['HTTP_HOST']."/".$result['pic1']:"";
			}else if($result['operate'] == 5){
				
				 if($result['car_status'] == 2){
					$result['title'] = "待调度";
					$result['car_status'] = ""; 
				 }else{
					$car_str = "";
					if(strpos($result['car_status'],'10') !== false){
						$car_str .= "车辆线路损坏\r\n";
					}
					if(strpos($result['car_status'],'11') !== false){
						$car_str .= "车辆丢失部件\r\n";
					}
					if(strpos($result['car_status'],'12') !== false){
						$car_str .= "无法打开电子锁\r\n";
					}
					if(strpos($result['car_status'],'13') !== false){
						$car_str .= "换电无法正常上线\r\n";
					}
					if(strpos($result['car_status'],'14') !== false){
						$car_str .= "无法设防\r\n";
					}
					if(strpos($result['car_status'],'15') !== false){
						$car_str .= "拧车把不走\r\n";
					}
					if(strpos($result['car_status'],'16') !== false){
						$car_str .= "二维码损坏\r\n";
					}
					if(strpos($result['car_status'],'17') !== false){
						$car_str .= "电池丢失并车辆被破坏\r\n";
					}
					if(strpos($result['car_status'],'18') !== false){
						$car_str .= $result['desc'];
					}
					$result['title'] = "待维修";
					if($car_str){
					    $result['car_status'] = $car_str;	
					}else{
						$result['car_status'] = $result['desc'];
					}
				 }
                 $result['pic1'] = "";
			}else if($result['operate'] == 100){
				 $result['title'] = "待维修";
				 $result['car_status'] = $result['desc'];
                 $result['pic1'] = "";
			}else{
			     if($result['operate'] == 16 || $result['operate'] ==17){
                     $result['title'] = "位置矫正";
                 }else{
                     $result['title'] = "车辆回收位置";
                 }
				 $result['car_status'] = "";
                 $result['pic1'] = "";
			}
			$gps = D('Gps');
            $gps_arr = $gps->gcj_decrypt($result['gis_lat'],$result['gis_lng']);
            $pt = [$gps_arr['lon'],$gps_arr['lat']];
            $area = $this->isXiaomiInArea($result['rent_content_id'],$pt);
			if($result['operate'] == 100){
				$result['area'] = $result['desc'];
			}else{
				$result['area'] = $area?"界内":"界外";
			}
			
            unset($result['gis_lng'],$result['gis_lat'],$result['operate'],$result['pic2'],$result['rent_content_id'],$result['desc']);
			// file_put_contents($_SERVER['DOCUMENT_ROOT']."canshu66666.txt",json_encode($result));
            $this->response(["code" => 1, "message" => "数据接收完成","data"=>['operationInfo'=>$result]], 'json');
        }else{
            $this->response(["code" => 0, "message" => "该操作记录不存在"], 'json');
        }
    }

    public function GetAmapAddress($lng,$lat,$default=''){
        $res = file_get_contents('http://restapi.amap.com/v3/geocode/regeo?output=JSON&location='.$lng.','.$lat.'&key=7461a90fa3e005dda5195fae18ce521b&s=rsv3&radius=1000&extensions=base');
        $res = json_decode($res);
        if($res->info == 'OK'){
            $default=$res->regeocode->formatted_address;
        }
//        echo $default;
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
   
    //版本比较
    public  function  versionCompare($version='1.1',$pastVersion='1.1.1'){
        $version = explode('.',$version);
        $pastVersion = explode('.',$pastVersion);
        if(intval($version[0]) < intval($pastVersion[0])){
            $isForced = 1;
        }else if(intval($version[0]) > intval($pastVersion[0])){
            $isForced = 0;
        }else{
            if(intval($version[1]) < intval($pastVersion[1])){
                $isForced = 1;
            }else if(intval($version[1]) > intval($pastVersion[1])){
                $isForced = 0;
            }else{
                if(intval($version[2]) < intval($pastVersion[2])){
                    $isForced = 1;
                }else if(intval($version[2]) > intval($pastVersion[2])){
                    $isForced = 0;
                }else{
                    $isForced = 0;
                }
            }
        }
        return $isForced;
    }
   
   public function isXiaomiInArea($rent_id,$pt){
       
        $rent_info = M("baojia.rent_content",null)->where(["id"=>$rent_id])->find();
		
        //非小蜜单车不判断，默认在区域内
   
        if(!in_array($rent_info["sort_id"],[112])){
            return true;
        }
        

        $map = ["car_item_id"=>$rent_info["car_item_id"]];
        $map["device_type"] = ["in",[12,14,16,18,9]];
        $car_item_device = M("baojia.car_item_device",null)->where($map)->find();
		
        $imei = $car_item_device["imei"];
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

        return true;
   }

    private function VoltagePost($data, $url)
    {
        $json = json_encode($data);
//        echo  $json;die;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        curl_setopt($ch, CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json)
            ]
        );

        $output = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($output, true);
        return $data;
    }

	
	 //小蜜重置通知
    public function setpower($imei,$power = 1)
    {
        $data['carId'] = $imei;
        $data['cmd'] = 'powerSet';
        $data['power'] = $power;
        $r = $this->post($data);
//         echo(json_encode($r));
        return $r;
    }

    private function post($data, $nosign)
    {
        $postUrl = $this->url;
        if($nosign){
            $postUrl = $this->url2;
        }
        $sign = $this->getSign3($data, $this->key);
        $data['sign'] = $sign['sign'];
        $json = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        curl_setopt($ch, CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json)
            ]
        );

        /*if (is_array($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }*/
        $output = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($output, true);
        return $data;
    }

    /**
     * 对数据签名
     * @param array $params 需要参加签名的参数kv数组
     * @param string $secret 密钥
     * @param string $joiner
     * @param string $separator
     * @return array
     */
    private function getSign3($params, $secret, $joiner = '&', $separator = '=')
    {
        $preStr = '';
        ksort($params);
        //$first = true;
        foreach ($params as $k => $v) {
            $kName = strtolower($k);
            if (!in_array($kName, ['sign', 'msg', ''])) {
                $preStr = $preStr . $k . $separator . $v . $joiner;
            }
        }

        if (!empty($preStr)) {
            $preStr = substr($preStr, 0, -strlen($separator)) . $secret;
        }
        $sign = md5($preStr);
        return ['sign' => $sign, 'value' => $preStr];
    }
	
	 //操作 4=鸣笛
    public function OperationWhistle($rent_content_id=314889,$lat='39.981444',$lng='116.389832')
    {
        $map['rc.id'] = $rent_content_id;
        $reslut = M('rent_content')->alias('rc')->field('rc.id,cid.imei,cid.device_type')
            ->join('car_item_device cid ON rc.car_item_id = cid.car_item_id')
            ->where($map)->find();
        $imei = ltrim($reslut['imei'],"0");
        if (!empty($imei) && !empty($lat)) {
            $status_info = $this->gpsStatusInfo($reslut['imei']);
            //查看距离
            $distance = round($this->distance($status_info["gd_latitude"],$status_info["gd_longitude"],$lat,$lng));
            if($distance > 500){
                $this->ajaxReturn(["code" => -6, "message" => "请靠近车辆使用鸣笛功能","distance"=>$distance], 'json');
            }
            $data["carId"]=$imei;
            $data["cmd"]="carControl";
            $data["directRt"]="false";
            $data["type"]=5;
            $data["command"]=14;

            $result=$this->VoltagePost($data,"http://47.95.32.191:8107/simulate/service");

            if($result["rtCode"]==0){
                $this->ajaxReturn(["code" => 1, "message" => "操作成功","result"=>"success"], 'json');
            }else if($result["rtCode"]==4){
                $this->ajaxReturn(["code" => 4, "message" => "重试一次"], 'json');
            } else {
                $this->ajaxReturn(["code" => 0, "message" => "操作失败"], 'json');
            }
        }else{
            $this->ajaxReturn(["code" => -100, "message" => "参数不完整"], 'json');
        }
    }

    public function gpsStatusInfo($imei){
        $info = M("baojia_box.gps_status",null,$this->box_config)->field('latitude,longitude')->where(["imei"=>$imei])->find();
        $gps=D('Gps');
        $gd = $gps->gcj_encrypt($info["latitude"],$info["longitude"]);
        $info["gd_latitude"] = $gd["lat"];
        $info["gd_longitude"] = $gd["lon"];
        return $info;
    }

    public function distance($latA, $lonA, $latB, $lonB){
        $earthR = 6371000.;
        $x = cos($latA * $this->PI / 180.) * cos($latB * $this->PI / 180.) * cos(($lonA - $lonB) * $this->PI / 180);
        $y = sin($latA * $this->PI / 180.) * sin($latB * $this->PI / 180.);
        $s = $x + $y;
        if ($s > 1) $s = 1;
        if ($s < -1) $s = -1;
        $alpha = acos($s);
        $distance = $alpha * $earthR;
        return $distance;
    }
	
	public  function  aaaaa(){
		$model = M('baojia_mebike.repair_member','',$this->baojia_config);
        // $map['user_id'] = 2698464;
		// $model->where($map)->setField('user_name','刘阳');
		// file_put_contents($_SERVER['DOCUMENT_ROOT']."canshu.txt","ddddddddddd");
		
		$mm = D('FedGpsAdditional')->theDayCarNum(2715981,2);
	    var_dump($mm);
	}
	
	//定时任务  查询10分钟换电记录 判断换电完成后8~10分钟换电记录是否无效
	public   function   dianliang_log(){
        $model = M('operation_logging','',$this->baojia_config);
        $map['time'] = array('gt',time()-1260);
        $map['operate'] = array('in',array(-1,1,2));
        $res = $model->where($map)->field('id,rent_content_id,time')->select();
        foreach ($res as &$val){
            $rMap['rc.id'] = $val['rent_content_id'];
            $reslut = M('rent_content')->alias('rc')->field('rc.id,cid.imei')
                ->join('car_item_device cid ON rc.car_item_id = cid.car_item_id')
                ->where($rMap)->find();
            $gMap['imei'] = $reslut['imei']?$reslut['imei']:"";
            $res2 = M('gps_additional','',C('BAOJIA_LINK'))->where($gMap)->getField('residual_battery');
            $c_time = intval((time()-$val['time'])/60);
            if($c_time > 19 && $c_time < 21 && $res2 < 60){
                $remark = $res2."---".$c_time;
                $model->where(['id'=>$val['id']])->setField(['is_type'=>1,'remark'=>$remark]);
            }
//            $val['time'] = date("Y-m-d H:i:s",$val['time']);
        }
    }
	
	//人工停租记录
	public  function  StopRentLog($uid=0,$rent_content_id=0,$plate_no='',$gis_lng=0,$gis_lat=0,$source=0){
        if($uid && $rent_content_id && $plate_no && $source){
            $model = M('operation_logging','',$this->baojia_config);
            $date['uid'] = $uid;
            $date['rent_content_id'] = $rent_content_id;
            $date['plate_no'] = $plate_no;
            $date['operate'] = 15;
            $date['gis_lng'] = $gis_lng;
            $date['gis_lat'] = $gis_lat;
            $date['source'] = $source;
            $date['time'] = time();
            $res = $model->add($date);
            $this->ajaxReturn(["code" => 1, "message" => "数据接收成功",'data'=>["operationId"=> $res]], 'json');
        }else{
            $this->ajaxReturn(["code" => 0, "message" => "参数错误"], 'json');
        }
    }


    public   function   asasa(){
          $model = M('operation_logging');
          $map['uid'] = 2658265;
          $map['operate'] = 0;
          $res = $model->where($map)->select();
          echo "<pre>";
          print_r($res);
//          $model->where(['id'=>90306])->setField('step',3);
//         $model->where($map)->delete();
    }
	
	
	
}