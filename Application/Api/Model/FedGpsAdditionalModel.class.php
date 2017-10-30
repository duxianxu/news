<?php
namespace Api\Model;
use Think\Model;

class FedGpsAdditionalModel extends Model{
    //根据设备号查询电量
    public  function  electricity_info($iemi){
        $result = [];
        $map['imei'] = $iemi;
        $res = M('fed_gps_additional')->field('residual_battery')->where($map)->find();
        if($res){
            $result['residual_battery1'] = $res['residual_battery'];
        }
        $res2 = M('gps_additional','',C('BAOJIA_LINK'))->field('residual_battery')->where($map)->find();
        if($res2){
            $result['residual_battery2'] = $res2['residual_battery'];
        }
        if($result){
            return $result;
        }else{
            return "";
        }
    }
	
	//统计当天完成的车辆数
	public  function   theDayCarNum($uid,$type){
		$model = M('operation_logging');
        $start_time = strtotime(date("Y-m-d",time())." 0:0:0");
        $end_time   = strtotime(date("Y-m-d",time())." 23:59:59");
        
        $lmap['time'] = array('between',array($start_time,$end_time));
        $lmap['uid']  = $uid?$uid:"";
		if($type == 2){
			$lmap['operate'] = 3;
		}else{
			$lmap['status'] = array('neq',2);
            $lmap['operate'] = array('in',array(-1,1,2));
		}
        $resday = $model->where($lmap)->count();
		if($resday){
			if($type==2){
				$prompt = "完成了回收，工作记录+1\r\n这是今天的第".$resday."辆车，继续加油";
			}else{
				$prompt = "完成换电，工作记录+1\r\n这是今天的第".$resday."辆车，继续加油";
			}
			return ['CarNum'=>$resday,"prompt"=>$prompt];
		}else{
			return $resday;
		}
	}
	
	/**
	 *操作记录
	 *uid   用户ID
	 *rent_content_id  车辆ID
	 *plate_no         车牌号
	 *gis_lng          经度
	 *gis_lat          纬度
	 *operate          日志类型      12 设防  13 撤防 14 启动 16 手动矫正 17 自动矫正 34=开舱锁
	*/
	public  function  operation_log($uid,$rent_content_id,$plate_no,$gis_lng,$gis_lat,$operate){
		if($uid && $rent_content_id && $operate){
			$model = M('operation_logging');
			if($operate == 34){
				$date['operate'] = 0;
                $date['step'] = 1;
			}else{
				$date['operate'] = $operate;
			}
			$date['uid'] = $uid;
			$date['rent_content_id'] = $rent_content_id;
			$date['plate_no'] = $plate_no;
			$date['gis_lng'] = $gis_lng;
			$date['gis_lat'] = $gis_lat;
			$date['time'] = time();
			$res = $model->add($date);
			return $res;
		}else{
			return  false;
		}
    }
	


}
