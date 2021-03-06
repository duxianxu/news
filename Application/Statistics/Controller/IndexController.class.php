<?php
namespace Statistics\Controller;
use Think\Controller\RestController;

class IndexController extends RestController {
    public function index($auth=""){
        if(!empty($auth)) {
            if($auth==$this->getAuth()) {
                $today = strtotime(date('Y-m-d', time()));
                $this->assign('today', $today);
                $end = ($today + 24 * 60 * 60) - 1;
                $this->assign('end', $end);
                $cityArray = $this->getCitiyList();
                $this->assign('cityArray', $cityArray);
                $this->display('count');
            }else{
                echo "auth参数不正确";
            }
        }else{
            echo "auth参数不正确";
        }
    }

    public function getAuth($t=0){
        $year=date("Y");
        $month=date("m");
        $week=date('w'); //得到今天是星期几
        $hour=date("H"); //小时
        $date_now=date('j'); //得到今天是几号
        $we=ceil($date_now/7); //计算是第几个星期几
        if(($week==3&&$we==3&&$hour>=10)||$we>3||($week>3&&$we==3) ){
            $authpa=dechex(($year*$month*5));//.toString(16);
        }else{
            if($month==1){
                $month=12;
                $year=$year-1;
                $authpa=dechex(($year*$month*5));//.toString(16);
            }else{
                $authpa=dechex(($year*$month*5));
            }
        }
        if($t){
            echo "xmtest.baojia.com?auth=".$authpa;
        }
        return $authpa;
    }
	
	 public function test(){
        $today = strtotime(date('Y-m-d', time()));
        $this->assign('today',$today);
        $end = ($today + 24 * 60 * 60)-1;
        $this->assign('end',$end);
        $cityArray = $this->getCitiyList();
        $this->assign('cityArray',$cityArray);
        $this->display('index');
    }

    public  function  getCitiyList(){
        $Model = new \Think\Model();
        $cityArray = $Model->query('select id,name,gis_lat,gis_lng from baojia.area_city where status=1 and id in(select DISTINCT city_id from  baojia.rent_content where sort_id=112) and gis_lat is not null');
        return $cityArray;
    }
	
	public function heatmap(){
        $today = strtotime(date('Y-m-d', time()));
        $this->assign('today',$today);
        $end = ($today + 24 * 60 * 60)-1;
        $this->assign('end',$end);
        $cityArray = M('')->query("select id,name,gis_lat,gis_lng from baojia.area_city where status=1 and id in(select DISTINCT city_id from  baojia.rent_content where sort_id=112) and gis_lat is not null and name<>'等待'");
        $this->assign('cityArray',$cityArray);
        $this->display('heatmap');
    }

    //加载热力图
    public function LoadHeatmapData($pointMode,$city,$start,$end){
        if(empty($pointMode)||empty($city)||empty($start)||empty($end)){
            $this->response(["code" => -100, "message" => "参数不完整"], 'json');
        }
        $start= strtotime($start);
        $end = (strtotime($end) + 24 * 60 * 60)-1;
        if($start>$end)
        {
            $this->response(['code' => -1, 'message' => '开始日期大于截止日期'], 'json');
        }
        if((($end-$start)/86400)>60){
            $this->response(['code' => -2, 'message' => '日期范围不能超过60天'], 'json');
        }
        $strSql = "select rcrc.take_lng lng,rcrc.take_lat lat,count(0) count 
            from trade_order a
            left join trade_order_car_return_info rcrc ON a.id = rcrc.order_id
            left join rent_content rn on a.rent_content_id=rn.id
            LEFT JOIN car_item_verify civ on rn.car_item_id=civ.car_item_id
            where civ.plate_no like 'DD%' AND rn.sort_id =112 AND rn.city_id={$city} and IFNULL(rcrc.take_lng,0)>0
            and a.create_time BETWEEN {$start} and {$end}
            group by rcrc.take_lng,rcrc.take_lat";
        if($pointMode==2){
            $strSql = "select rcrc.return_lng lng,rcrc.return_lat lat,count(0) count 
            from trade_order a
            left join trade_order_car_return_info rcrc ON a.id = rcrc.order_id
            left join rent_content rn on a.rent_content_id=rn.id
            LEFT JOIN car_item_verify civ on rn.car_item_id=civ.car_item_id
            where civ.plate_no like 'DD%' AND rn.sort_id =112 AND rn.city_id={$city} and IFNULL(rcrc.return_lng,0)>0
            and a.end_time BETWEEN {$start} and {$end}
            group by rcrc.return_lng,rcrc.return_lat";
        }
        $heatmapData = M('',null,$this->baojia_config)->query($strSql);
        $this->response(["code" => 1, "message" => "查询成功","data" =>$heatmapData], 'json');
    }


    public function loadData()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $cityAndPattern="";
            $orderCityAndPattern="";
            $city1="";
            $t_city = "";//订单城市
            if($city!=0) {
                $cityAndPattern=" AND rn.city_id in({$city}) ";
                $orderCityAndPattern=" AND a.city_id in({$city}) ";
                $city1 = " AND rc.city_id in({$city}) ";
                $t_city = " AND a.city_id in({$city}) ";
            }
            if($pattern!=0){
                $cityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                $orderCityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
            }else{
                $cityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
                $orderCityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
            }
            $user  = $this->user_behavior($start,$end,$cityAndPattern,$city1,$t_city);
            $result = array('status' => 1, 'info' =>'获取数据成功','start'=>$start,'end'=>$end,'user'=>$user);
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    public function loadOrderData()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $cityAndPattern="";
            $orderCityAndPattern="";
            $city1="";
            $t_city = "";//订单城市
            if($city!=0) {
                $cityAndPattern=" AND rn.city_id in({$city}) ";
                $orderCityAndPattern=" AND a.city_id in({$city}) ";
                $city1 = " AND rc.city_id in({$city}) ";
                $t_city = " AND a.city_id in({$city}) ";
            }
            if($pattern!=0){
                $cityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                $orderCityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
            }else{
                $cityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
                $orderCityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
            }
            $order  = $this->order_statistics($start,$end,$cityAndPattern);
            $order_pay=$this->order_pay($start,$end,$cityAndPattern);
            $result = array('status' => 1, 'info' =>'获取数据成功','start'=>$start,'end'=>$end,'order'=>$order,'order_pay'=>$order_pay);
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    /**
     *统计用户使用次数和人数
     **/
    public function loadUseData()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $cityAndPattern="";
            $orderCityAndPattern="";
            $city1="";
            $t_city = "";//订单城市
            if($city!=0) {
                $cityAndPattern=" AND rn.city_id in({$city}) ";
                $orderCityAndPattern=" AND a.city_id in({$city}) ";
                $city1 = " AND rc.city_id in({$city}) ";
                $t_city = " AND a.city_id in({$city}) ";
            }
            if($pattern!=0){
                $cityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                $orderCityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
            }else{
                $cityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
                $orderCityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
            }
            $order  = $this->use_count_statistics($start,$end,$cityAndPattern);
            $result = array('status' => 1, 'info' =>'获取数据成功','start'=>$start,'end'=>$end,'order'=>$order);
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    //行驶里程和时长统计
    public function loadTravelData()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $cityAndPattern="";
            $orderCityAndPattern="";
            $city1="";
            $t_city = "";//订单城市
            if($city!=0) {
                $cityAndPattern=" AND rn.city_id in({$city}) ";
                $orderCityAndPattern=" AND a.city_id in({$city}) ";
                $city1 = " AND rc.city_id in({$city}) ";
                $t_city = " AND a.city_id in({$city}) ";
            }
            if($pattern!=0){
                $cityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                $orderCityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
            }else{
                $cityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
                $orderCityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
            }
            $travel=$this->travel($start,$end,$cityAndPattern);
            $result = array('status' => 1, 'info' =>'获取数据成功','start'=>$start,'end'=>$end,'travel'=>$travel);
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    public function loadRentCarCount()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $cityAndPattern="";
            $orderCityAndPattern="";
            if($city!=0) {
                $cityAndPattern=" AND rn.city_id in({$city}) ";
                $orderCityAndPattern=" AND a.city_id in({$city}) ";
            }
            if($pattern!=0){
                $cityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                $orderCityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
            }else{
                $orderCityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
                $cityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
            }
            $rent_car_count  = $this->rent_car_count($start,$end,$cityAndPattern);
            $result = array('status' => 1, 'info' =>'获取数据成功','start'=>$start,'end'=>$end,'rent_car_count'=>$rent_car_count);
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    public function loadCarData()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $today = date('Y-m-d', time());
            if($today==$startDate&&$today==$endDate) {
                $cityAndPattern="";
                if($city!=0) {
                    $cityAndPattern=" AND rn.city_id in({$city}) ";
                }
                if($pattern!=0){
                    $cityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                }
                $maintain = $this->car_maintain($cityAndPattern);
                $car_status = $this->car_status($cityAndPattern);
                $result = array('status' => 1, 'info' => '获取数据成功', 'start' => $start, 'end' => $end, 'car_status' => $car_status, 'maintain' => $maintain);
            }else{
                $result = array('status' => 0, 'info' => "不是今天的日期");
            }
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    public function loadCarOrderData()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $today = date('Y-m-d', time());
            if($today==$startDate&&$today==$endDate) {
				$cityAndPattern="";
                $orderCityAndPattern="";
                if($city!=0) {
                    $orderCityAndPattern=" AND a.city_id in({$city}) ";
                    $cityAndPattern=" AND rn.city_id in({$city}) ";
                }
                if($pattern!=0){
                    $orderCityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                    $cityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                }else{
                    $orderCityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
                    $cityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
                }
                $car_order = $this->car_order($cityAndPattern);
                $result = array('status' => 1, 'info' => '获取数据成功', 'start' => $start, 'end' => $end, 'car_order' => $car_order);
            }else{
                $result = array('status' => 0, 'info' => "不是今天的日期");
            }
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    public function loadOperationCarData()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $cityAndPattern="";
            if($city!=0) {
                $cityAndPattern=" and city_id in({$city}) ";
            }else{
				$cityAndPattern=" and city_id = 0 ";
			}
            if($pattern!=0){
                $cityAndPattern.=" and return_mode in ({$pattern}) ";
            }else{
				$cityAndPattern.=" and return_mode = 0 ";
			}
            //$aa = 'mysql://bjtest_dev1:yU23VFFFwfZ9y5YG@10.1.11.110:3306/testbaojia#utf8';
            $strSql="select sum(day_operation_car) day_operation_car from baojia_mebike.xmi_day_count where count_time between {$start} and {$end} ";
            //$day_operation_car = M('xmi_day_count','',$aa)->query($strSql.$cityAndPattern);
            $day_operation_car = M('')->query($strSql.$cityAndPattern);
			$sql = M('')->getlastsql();
            //echo $strSql.$cityAndPattern;
            $result = array('status' => 1, 'info' => '获取数据成功', 'start' => $start, 'end' => $end, 'day_operation_car' => $day_operation_car[0]['day_operation_car']);
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    public function loadCarOnlineData()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            $cars = $_POST['cars'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $today = date('Y-m-d', time());
            if($today==$startDate&&$today==$endDate) {
                $car_online = $this->car_online($cars);
                $result = array('status' => 1, 'info' => '获取数据成功', 'start' => $start, 'end' => $end, 'car_online' => $car_online);
            }else{
                $result = array('status' => 0, 'info' => "不是今天的日期");
            }
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    public function loadActiveUser()
    {
        if ($this->_method == 'post') {
            $startDate =$_POST['startDate'];
            $endDate = $_POST['endDate'];
            $city = $_POST['city'];
            $pattern = $_POST['pattern'];
            if(empty($startDate))
            {
                $result = array('status' => 0, 'info' => '开始日期不能为空');
                $this->response($result, 'json');
            }
            if (empty($endDate)) {
                $result = array('status' => 0, 'info' => '截止日期不能为空');
                $this->response($result, 'json');
            }
            $start= strtotime($startDate);
            $end = (strtotime($endDate) + 24 * 60 * 60)-1;
            if($start>$end)
            {
                $result = array('status' => 0, 'info' => '开始日期大于截止日期');
                $this->response($result, 'json');
            }
            $cityAndPattern="";
            $orderCityAndPattern="";
            $city1="";
            $t_city = "";//订单城市
            if($city!=0) {
                $cityAndPattern=" AND rn.city_id in({$city}) ";
                $orderCityAndPattern=" AND a.city_id in({$city}) ";
                $city1 = " AND rc.city_id in({$city}) ";
                $t_city = " AND a.city_id in({$city}) ";
            }
            if($pattern!=0){
                $cityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
                $orderCityAndPattern.=" and rcrc.return_mode in ({$pattern}) ";
            }else{
                $cityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
                $orderCityAndPattern.=" and rcrc.return_mode in (1,2,4,32) ";
            }
            $user  = $this->user_active($start,$end,$cityAndPattern,$city1,$t_city);
            $result = array('status' => 1, 'info' =>'获取数据成功','start'=>$start,'end'=>$end,'user'=>$user);
            $this->response($result, 'json');
        } else {
            $result = array('status' => 0, 'info' => "只支持POST请求");
            $this->response($result, 'json');
        }
    }

    /**
     *用户行为
     **/
    public function user_behavior($start=0, $end=0,$cityAndPattern,$city1,$t_city){
        $strWhere="u.`from` = 710130 and u.`regdate` between {$start} and {$end} ";
        //注册人数
        $register_count = M('ucenter_member')->alias('u')
            ->join('member m on m.uid=u.uid','LEFT')
            ->where($strWhere)
            ->count();
        $strWhere="u.`from` = 710130 AND mic.`verify_status` = 2 AND mic.`verify_idcard_result` = 1 AND mic.`validate_idcard` = 1 AND u.`regdate` between {$start} AND {$end} ";
        //实名认证
        $validate_count = M('ucenter_member')->alias('u')
            ->join("member_identity_card mic ON mic.user_id = u.uid",'left')
            ->join('member m on m.uid=u.uid','LEFT')
            ->where("u.`from` = 710130 AND mic.`verify_status` = 2 AND mic.verify_idcard_result = 1 and mic.update_time between {$start} and {$end}")
            ->count();
        //押金缴纳
        $pay_deposit = M('ucenter_member')->alias('u')
            ->join('trade_payment t ON t.user_id = u.uid','LEFT')
            ->where("u.`from`= 710130 AND t.busi_code = 20 AND t.refilled = 1 AND t.paid_time > {$start} AND t.paid_time<{$end}")
            ->Distinct(true)
            ->field('t.user_id')
            ->select();
        $pay_deposit_count = count($pay_deposit);
        //押金退款人数
        $deposit_return = M('ucenter_member')->alias('u')
            ->join('payable_return t ON t.user_id = u.uid','LEFT')
            ->where("u.`from`= 710130 AND t.create_time between {$start} and {$end}")
            ->Distinct(true)
            ->field('t.user_id')
            ->select();
        $deposit_return_count = count($deposit_return);
        //活跃用户
        /*$week_active = M('')->query("SELECT count(*) z FROM ( SELECT count(*) a, t.user_id FROM trade_order t LEFT JOIN ucenter_member u ON u.uid =
            t.user_id LEFT JOIN rent_content rc ON rc.id = t.rent_content_id WHERE u.`from` = 710130 AND t.begin_time BETWEEN UNIX_TIMESTAMP(current_date()) - 7 * 86400 
            AND UNIX_TIMESTAMP(current_date()) AND t.rent_type = 3 AND rc.sort_id = 112 AND t.`status` = 80200 AND t.`hand_over_state` >= 20100 {$city1} GROUP BY user_id HAVING a > 1 ) t1");
        $month_active = M('')->query("SELECT count(*) z FROM ( SELECT count(*) a, t.user_id FROM trade_order t LEFT JOIN ucenter_member u ON u.uid = 
            t.user_id LEFT JOIN rent_content rc ON rc.id = t.rent_content_id WHERE u.`from` = 710130 AND t.begin_time BETWEEN UNIX_TIMESTAMP(current_date()) - 30 * 86400 
            AND UNIX_TIMESTAMP(current_date()) AND t.rent_type = 3 AND rc.sort_id = 112 AND t.`status` = 80200 AND t.`hand_over_state` >= 20100 {$city1} GROUP BY user_id HAVING a > 1 ) t1");
        */
        //临时锁车次数
        // $lock = M('')->query("SELECT SUM(lock_count) lock_count, COUNT(user_count) user_count FROM ( 
        //             SELECT COUNT(1) lock_count, COUNT(log.user_id) user_count, log.order_id, log.op_command 
        //             FROM car_item_device_op_log log 
        //             LEFT JOIN rent_content rn ON rn.car_item_id = log.car_item_id 
        //             LEFT JOIN rent_content_return_config rcrc ON rcrc.rent_content_id = rn.id 
        //             WHERE log.order_id <> 0 AND rn.sort_id = 112 AND log.op_command='B'{$cityAndPattern}
        //             AND log.start_time BETWEEN {$start} AND {$end} GROUP BY log.order_id HAVING lock_count > 1 ) AS table1");
          $lock = M('')->query("SELECT SUM(lock_count) lock_count, COUNT(user_count) user_count FROM ( SELECT COUNT(1) lock_count, COUNT(log.user_id) user_count FROM car_item_device_op_log log
          LEFT JOIN rent_content rn ON rn.car_item_id = log.car_item_id LEFT JOIN trade_order a ON a.id = log.order_id  LEFT JOIN rent_content_return_config rcrc on rcrc.rent_content_id=rn.id WHERE log.order_id <> 0 AND rn.sort_id = 112 AND 
          log.op_command = 'B' AND log.op_result LIKE '%成功%' AND log.start_time BETWEEN {$start} AND {$end} {$cityAndPattern} GROUP BY log.order_id HAVING 
          lock_count > 1 ) AS table1");
        $lock_user_count = $lock[0]['user_count'];  //临时锁车人数
        $lock_count = $lock[0]['lock_count'];       //临时锁车次数
        $result['register_count'] = $register_count;                  //注册人数
        $result['validate_count'] = $validate_count;                  //实名认证人数
        $result['pay_deposit_count'] = $pay_deposit_count;            //押金缴纳人数
        $result['deposit_return_count'] = $deposit_return_count;      //押金退款人数
        $result['lock_user_count'] = $lock_user_count;                //临时锁车人数
        $result['lock_count'] = $lock_count;                          //临时锁车次数
        //$result['week_active'] = $week_active[0]['z'];      //周活跃用户
        //$result['month_active'] = $month_active[0]['z'];    //月活跃用户
        return $result;
    }

    /**
     *活跃用户统计
     **/
    public function user_active($start=0, $end=0,$cityAndPattern,$city1,$t_city){
        $week_active = M('')->query("SELECT count(*) z FROM ( SELECT count(*) a, t.user_id FROM trade_order t LEFT JOIN ucenter_member u ON u.uid = 
            t.user_id LEFT JOIN rent_content rc ON rc.id = t.rent_content_id WHERE u.`from` = 710130 AND t.begin_time BETWEEN UNIX_TIMESTAMP(current_date()) - 7 * 86400 
            AND UNIX_TIMESTAMP(current_date()) AND t.rent_type = 3 AND rc.sort_id = 112 AND t.`status` = 80200 AND t.`hand_over_state` >= 20100 {$city1} GROUP BY user_id HAVING a > 1 ) t1");
        $month_active = M('')->query("SELECT count(*) z FROM ( SELECT count(*) a, t.user_id FROM trade_order t LEFT JOIN ucenter_member u ON u.uid = 
            t.user_id LEFT JOIN rent_content rc ON rc.id = t.rent_content_id WHERE u.`from` = 710130 AND t.begin_time BETWEEN UNIX_TIMESTAMP(current_date()) - 30 * 86400 
            AND UNIX_TIMESTAMP(current_date()) AND t.rent_type = 3 AND rc.sort_id = 112 AND t.`status` = 80200 AND t.`hand_over_state` >= 20100 {$city1} GROUP BY user_id HAVING a > 1 ) t1");
        $result['week_active'] = $week_active[0]['z'];      //周活跃用户
        $result['month_active'] = $month_active[0]['z'];    //月活跃用户
        return $result;
    }


    /**
     *订单统计
     **/
    public function order_statistics($start=0, $end=0,$cityAndPattern){
        $order_model = M('Trade_order');
        $where = 'a.create_time>'.$start.' and  a.create_time<'.$end.' and ((a.rent_type=0 and a.earnest_pay_status=1) or (a.rent_type=3 and a.status<>0))';
        //预约次数 所有订单数 order_num
        $order_num =M('')->query("SELECT COUNT(a.id) AS order_num,count(distinct(a.user_id)) user_num FROM trade_order a 
        left JOIN rent_content rn on a.rent_content_id=rn.id 
        left JOIN rent_content_search rnt on a.rent_content_id = rnt.rent_content_id 
        left JOIN trade_order_car_return_info rcrc ON a.id = rcrc.order_id 
        WHERE ( a.create_time BETWEEN {$start} 
        AND {$end}
        and ((a.rent_type=0 and a.earnest_pay_status=1) 
        or (a.rent_type=3 and a.status<>0)) and rn.sort_id = 112 
        and rnt.address_type = 99 
        and rnt.plate_no <> '' 
        ) {$cityAndPattern} ");
        //下单方式统计
        $order_type =M('')->query("SELECT tot.order_type,COUNT(DISTINCT(a.id)) AS order_num FROM trade_order_type tot 
        left JOIN trade_order a  on a.id = tot.order_id
        left JOIN rent_content rn on a.rent_content_id=rn.id 
        left JOIN rent_content_search rnt on a.rent_content_id = rnt.rent_content_id 
        left JOIN trade_order_car_return_info rcrc ON a.id = rcrc.order_id 
        WHERE ( a.create_time BETWEEN {$start} 
        AND {$end}
        and ((a.rent_type=0 and a.earnest_pay_status=1) 
        or (a.rent_type=3 and a.status<>0)) and rn.sort_id = 112 
        and rnt.address_type = 99 
        and rnt.plate_no <> '' 
        ) {$cityAndPattern}  and  tot.order_type <> ''  GROUP BY tot.order_type order by tot.order_type");
        // $result['sql1'] = M('')->getlastsql();
        //echo "<pre>";
        //print_r($order_num);
        //0元车的使用统计
        $one_car_where = $where." and rn.sort_id = 112 and rnt.address_type = 99 and rnt.plate_no <> '' and tot.is_free = 1 ";
        $one_car_order = M('trade_order_type')->alias("tot")
            ->join('trade_order a  on a.id = tot.order_id','left')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rnt on a.rent_content_id = rnt.rent_content_id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id','left')
            ->where($one_car_where.$cityAndPattern)->count('a.id');
        //行驶里程大于0.3千米的0元车
        $one_car_where2 = $one_car_where." and tre.all_mile >0.3 ";
        $one_car_order2 = M('trade_order_type')->alias("tot")
            ->join('trade_order a  on a.id = tot.order_id','left')
            ->join('trade_order_ext tre on tre.order_id = a.id','left')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rnt on a.rent_content_id = rnt.rent_content_id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id','left')
            ->where($one_car_where2.$cityAndPattern)->count('a.id');
        $rcs_where = " and rcs.address_type = 99 AND rcs.plate_no <> '' AND rn.sort_id = 112  AND rn.car_info_id <> 30150";
        //使用次数
        /*$s_where = $where."  and (a.status=80200 and (a.billing_settlement_status=2 or (a.hand_over_state in(20200,20100) and a.billing_settlement_status<>2)) or ( a.status=50200 and a.hand_over_state=10200)) ".$rcs_where;
        $use_num = $order_model->alias("a")
            ->field('count(*) use_num,count(distinct(a.user_id)) user_num')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id','left')
            ->where($s_where.$cityAndPattern)->find();*/

        //还车次数
        $h_where = $where." and a.status=80200 and (a.billing_settlement_status=2 or (a.hand_over_state in(20200,20100) and a.billing_settlement_status<>2))  ".$rcs_where;
        $return_car_num = $order_model->alias("a")
            ->field('count(a.id) use_num,count(distinct(a.user_id)) user_num')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id', 'left')
            ->where($h_where.$cityAndPattern)->find();
        // $result['sql'] = $order_model->getlastsql();
        //支付次数
        $z_where = $where.' and a.status=80200 and a.billing_settlement_status=2 and p.pay_mode in(5,11) and p.pay_status = 1 and p.busi_code != 5'.$rcs_where;
        $pay_num = $order_model->alias("a")
            ->field('count(a.id) use_num,count(distinct(a.user_id)) user_num')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_payment t on a.id = t.order_id','right')
            ->join('trade_payment p on t.payment_id = p.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id', 'left')
            ->where($z_where.$cityAndPattern)->find();
        // $result['sql'] = $order_model->getlastsql();
        //取消预约次数
        $q_where = $where.' and a.status in(-1,10301) '.$rcs_where;
        $cancel_num = $order_model->alias("a")
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id', 'left')
            ->where($q_where.$cityAndPattern)->count('a.id');
        // $result['order_num'] =$order_num[0]['order_num'];                  //预约次数 所有订单数 order_num
        // $result['use_num'] =$use_num;                                      //使用次数
        // $result['return_car_num'] = $return_car_num;                       //还车次数
        // $result['pay_num'] = $pay_num;                                     //支付次数
        // $result['cancel_num'] = $cancel_num;                               //取消次数
        $result['order_num'] =$order_num[0]['order_num'];                  //预约次数 所有订单数 order_num
        $result['order_user_num'] =$order_num[0]['user_num'];              //预约人数
        //$result['use_num'] =$use_num['use_num'];                          //使用次数
        //$result['use_user_num'] =$use_num['user_num'];                    //使用人数
        $result['return_car_num'] = $return_car_num['use_num'];           //还车次数
        $result['return_car_user_num'] = $return_car_num['user_num'];     //还车人数
        $result['pay_num'] = $pay_num['use_num'];                          //支付次数
        $result['pay_user_num'] = $pay_num['user_num'];                    //支付人数
        $result['cancel_num'] = $cancel_num;                               //取消次数
        $result['reserve_num'] = $order_type[0]['order_num']?$order_type[0]['order_num']:0;     //预约下单数
        $result['scan_code_num'] = $order_type[1]['order_num']?$order_type[1]['order_num']:0;   //扫码下单数
        $result['plate_on_num'] = $order_type[2]['order_num']?$order_type[2]['order_num']:0;   //输入车牌号下单数
        $result['one_car_num'] = $one_car_order;                         //0元车的使用次数
        $result['real_one_car_num'] = $one_car_order2;                         //0元车的实际使用次数
        return $result;
    }

    /**
     *使用次数和人数统计
     **/
    public function use_count_statistics($start=0, $end=0,$cityAndPattern){
        $order_model = M('Trade_order');
        $where = 'a.create_time>'.$start.' and  a.create_time<'.$end.' and ((a.rent_type=0 and a.earnest_pay_status=1) or (a.rent_type=3 and a.status<>0))';
        $rcs_where = " and rcs.address_type = 99 AND rcs.plate_no <> '' AND rn.sort_id = 112  AND rn.car_info_id <> 30150";
        //使用次数
        $s_where = $where."  and (a.status=80200 and (a.billing_settlement_status=2 or (a.hand_over_state in(20200,20100) and a.billing_settlement_status<>2)) or ( a.status=50200 and a.hand_over_state=10200)) ".$rcs_where;
        $use_num = $order_model->alias("a")
		    ->field('count(*) use_num,count(distinct(a.user_id)) user_num')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id','left')
            ->where($s_where.$cityAndPattern)->find();
        $result['use_num'] =$use_num['use_num'];                          //使用次数
        $result['use_user_num'] =$use_num['user_num'];                    //使用人数
        return $result;
    }

    /**
     *统计有出租记录的车辆数
     **/
    public function rent_car_count($start=0, $end=0,$cityAndPattern){
        //出租车辆数
        $strSql="select sum(rent_car_count) rent_car_count from(
                select count(distinct car_item_id) rent_car_count,create_time from (
                select rn.car_item_id,from_unixtime(a.create_time, '%Y-%m-%d') create_time 
                from Trade_order a
                left join rent_content rn on a.rent_content_id=rn.id
                left join rent_content_search rcs on rcs.rent_content_id=rn.id
                left join trade_order_car_return_info rcrc ON a.id = rcrc.order_id
                where a.create_time between {$start} and {$end} 
                and((a.rent_type=0 and a.earnest_pay_status=1) or (a.rent_type=3 and a.status<>0))
                and (
                a.status=80200 and (a.billing_settlement_status=2  or (a.hand_over_state in(20200,20100)  and a.billing_settlement_status<>2)) 
                or ( a.status=50200 and a.hand_over_state=10200)
                ) 
                and rcs.address_type = 99 AND rcs.plate_no <> '' AND rn.sort_id = 112  AND rn.car_info_id <> 30150 {$cityAndPattern}
                ) b group by create_time
                ) c  ";
        //echo $strSql;
        $rent_car_count = M('')->query($strSql);
        //echo "<pre>";
        //print_r($rent_car_count);
        $result['rent_car_count'] = $rent_car_count[0]['rent_car_count']?$rent_car_count[0]['rent_car_count']:0;  //出租车辆数
        return $result['rent_car_count'];
    }

    /**
     *订单支付统计
     **/
    public  function  order_pay($start=0,$end=0,$cityAndPattern){
        $order_model = M('Trade_order');
        $where = 'a.create_time>'.$start.' and  a.create_time<'.$end.' and ((a.rent_type=0 and a.earnest_pay_status=1) or (a.rent_type=3 and a.status<>0))';
        $rcs_where = " and rcs.address_type = 99 AND rcs.plate_no <> '' AND rn.sort_id = 112 AND rn.car_info_id <> 30150";
        $h_where = $where.' and a.status=80200 and (a.billing_settlement_status=2 or (a.hand_over_state in(20200,20100) and a.billing_settlement_status<>2)) '.$rcs_where;
        $h_where2 = $where.' and a.status=80200 and a.billing_settlement_status=2 '.$rcs_where;
        $wx_where = $h_where2.' and p.pay_mode=5 and p.pay_status = 1 and p.busi_code != 5';
        $wxpay_arr = $order_model->alias("a")
            ->field('count(a.id) wx_num,sum(p.amount) wx_amount')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_payment t on a.id = t.order_id','right')
            ->join('trade_payment p on t.payment_id = p.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id', 'left')
            ->where($wx_where.$cityAndPattern)->find();
        $bb_where = $h_where2.' and p.pay_mode=11 and p.pay_status = 1 and p.busi_code != 5';
        $bbpay_arr = $order_model->alias("a")
            ->field('count(a.id) bb_num,sum(p.amount) bb_amount')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_payment t on a.id = t.order_id','right')
            ->join('trade_payment p on t.payment_id = p.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id', 'left')
            ->where($bb_where.$cityAndPattern)->find();
        $result['wx_num'] = $wxpay_arr['wx_num'];          //微信支付次数
        $result['wx_amount'] = $wxpay_arr['wx_amount']?$wxpay_arr['wx_amount']:0;  //微信支付金额
        $result['bb_num'] = $bbpay_arr['bb_num'];                                  //宝币支付次数
        $result['bb_amount'] = $bbpay_arr['bb_amount']?$bbpay_arr['bb_amount']:0;  //宝币支付金额
        //栏内栏外还车次数
        // $hc_where=$h_where.' and tor.version=1 ';
        $hc_where=$h_where.' and tor.resault>=0 ';
		
		$hc_strSql = "SELECT m.resault,count(DISTINCT m.id) return_num from (SELECT t.resault,t.id from (SELECT a.id,tor.resault FROM trade_order a
		LEFT JOIN rent_content rn ON a.rent_content_id = rn.id
		LEFT JOIN rent_content_search rcs ON rcs.rent_content_id = rn.id
		LEFT JOIN trade_order_car_return_info rcrc ON a.id = rcrc.order_id
		LEFT JOIN baojia_mebike.trade_order_return_log tor ON tor.order_id = a.id 
		where {$hc_where}{$cityAndPattern} ORDER BY tor.id DESC) t GROUP BY t.id) m GROUP BY m.resault=1";
        // $return_car = $order_model->alias("a")
            // ->field('tor.resault,count(DISTINCT a.id) return_num')
            // ->join('rent_content rn on a.rent_content_id=rn.id','left')
            // ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            // ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id', 'left')
            // ->join('baojia_mebike.trade_order_return_log tor on tor.order_id=a.id','left')
            // ->where($hc_where.$cityAndPattern)->group('tor.resault=1')->select();
		// $result['sql'] = $order_model->getlastsql();
		$return_car = M('')->query($hc_strSql);
        if($return_car){
            $result['column_num'] =0;
            $result['column_outside_num']=0;
            foreach ($return_car as $key=>$val){
                if($val['resault'] == 1){
                    $result['column_num'] = $val['return_num'];
                }
                if($val['resault'] == 0){
                    $result['column_outside_num'] = $val['return_num'];
                }
            }
        }else{
            $result['column_num'] = 0;          //栏内还车次数
            $result['column_outside_num'] = 0;  //栏外还车次数
        }
        //网点内/外还车次数
        // $phc_where=$h_where.' and tor.corporation_id>=0 and tor.resault=1 and tor.version=1 ';
        $phc_where=$h_where.' and tor.corporation_id>=0 and tor.resault=1 ';
        $return_car = $order_model->alias("a")
            ->field('tor.corporation_id,count(DISTINCT a.id) return_num')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id', 'left')
            ->join('baojia_mebike.trade_order_return_log tor on tor.order_id=a.id','left')
            ->where($phc_where.$cityAndPattern)->group('tor.corporation_id>0')->select();
        if($return_car){
            $result['point_num'] =0;
            $result['point_outside_num'] =0;
            foreach ($return_car as $key=>$val){
                if($val['corporation_id']>0){
                    $result['point_num'] = $val['return_num'];
                }
                if($val['corporation_id'] == 0){
                    $result['point_outside_num'] = $val['return_num'];
                }
            }
        }else{
            $result['point_num'] = 0;          //网点内还车次数
            $result['point_outside_num'] = 0;  //网点外还车次数
        }
        return $result;
    }

    /**
     *行驶统计
     **/
    public  function  travel($start=0, $end=0,$cityAndPattern){
        $order_model = M('Trade_order');
        $where = 'a.create_time>'.$start.' and  a.create_time<'.$end.' and ((a.rent_type=0 and a.earnest_pay_status=1) or (a.rent_type=3 and a.status<>0))';
        $rcs_where = " and rcs.address_type = 99 AND rcs.plate_no <> '' AND rn.sort_id = 112  AND rn.car_info_id <> 30150";
        $h_where = $where.' and a.status=80200 and (a.billing_settlement_status=2 or (a.hand_over_state in(20200,20100) and a.billing_settlement_status<>2))  and a.end_time > 0 and oe.all_mile > 0.3'.$rcs_where;
        $travel_arr = $order_model->alias("a")
            ->field('count(a.id) num,sum(oe.all_mile) all_mile,sum(a.end_time - a.begin_time) all_time')
            ->join('rent_content rn on a.rent_content_id=rn.id','left')
            ->join('rent_content_search rcs on rcs.rent_content_id=rn.id','left')
            ->join('trade_order_car_return_info rcrc ON a.id = rcrc.order_id', 'left')
            ->join('trade_order_ext oe on a.id=oe.order_id','left')
            ->where($h_where.$cityAndPattern)->find();
		// $result['sql'] = $order_model->getlastsql();
        if($travel_arr){
            $result['all_mile'] = $travel_arr['all_mile'];
            $result['all_time'] = $this->time_conversion($travel_arr['all_time']);
            $result['average_mileage'] = sprintf("%.2f", ($travel_arr['all_mile']/$travel_arr['num']));
            $result['average_time'] = $this->time_conversion(sprintf("%.2f", ($travel_arr['all_time']/$travel_arr['num'])));
        }else{
            $result['all_mile'] = 0;            //总里程
            $result['all_time'] = 0;            //总时间
            $result['average_mileage'] = 0;     //平均里程
            $result['average_time'] = 0;        //平均时间
        }
        return $result;
    }

    /**
     * 车辆现状
     **/
    public function car_status($cityAndPattern){
        $strSql="SELECT
                    rn.car_info_id,
                    rn.id rent_content_id,
                    rn.sort_id,
                    ifnull(ms.all_miles ,- 1) mi,
                    IFNULL(a.residual_battery, 0) residual_battery,
                    rn.car_item_id,
                    civ.plate_no,
                    rn.corporation_id m,
                    rn.city_id,
                    rn.update_time q,
                    rn. STATUS,
                    rn.sell_status,
                    IFNULL(t2.ordercount, 0) ba,
                    t.is_sell,
                    rsh.operate_status,
                    rn.create_time ct
                FROM
                    rent_content rn
                JOIN car_item_verify civ ON rn.car_item_id = civ.car_item_id
                LEFT JOIN car_item_device cid ON cid.car_item_id = rn.car_item_id
                LEFT JOIN fed_gps_additional a ON cid.imei = a.imei
                LEFT JOIN rent_content_return_config rnc ON rn.id = rnc.rent_content_id
                LEFT JOIN (
                    SELECT
                        rent_content_id,
                        1 AS is_sell
                    FROM
                        trade_order
                    WHERE
                        rent_type = 3
                    AND STATUS >= 10100
                    AND STATUS < 80200
                    AND STATUS <> 10301
                ) t ON rn.id = t.rent_content_id
                LEFT JOIN (
                    SELECT
                        rent_content_id,
                        count(1) AS ordercount
                    FROM
                        trade_order bto
                    WHERE
                        bto.rent_type = 3
                    AND bto.create_time > (
                        UNIX_TIMESTAMP(NOW()) - 86400 * 2
                    )
                    GROUP BY
                        bto.rent_content_id
                ) t2 ON rn.id = t2.rent_content_id
                LEFT JOIN mileage_statistics ms ON ms.rent_content_id = rn.id
                JOIN rent_sku_hour rsh ON rn.id = rsh.rent_content_id
                WHERE
                    civ.plate_no like  'DD%'
                AND rn.sort_id = 112
                AND rn. STATUS <>- 2 ".$cityAndPattern;
        $car_list = M('')->query($strSql);

        return $car_list;
    }

    /**
     * 车辆在线
     **/
    public function car_online($cars){
        $strSql="select fgu.cid,rn.status,rn.sell_status,rn.car_info_id,fgu.imei,fgs.latitude,fgs.longitude,fga.residual_battery,UNIX_TIMESTAMP(fgs.lastonline) lastonline,fgu.devicetype
        from fed_gps_usercar fgu 
        LEFT JOIN rent_content rn on fgu.cid = rn.car_item_id
        left join fed_gps_status fgs on fgu.imei=fgs.imei
        left join fed_gps_additional fga on fgu.imei=fga.imei 
        WHERE fgu.cid>0
        and fgu.cid in({$cars})";
        $car_online = M('')->query($strSql);
        return $car_online;
    }

    /**
     * 车辆订单
     **/
    public function car_order($cityAndPattern){
        $strSql="select rn.car_info_id,rnt.rent_content_id,rn.sort_id, IFNULL(a.ordercount,0) ordercount,
                ifnull(a.mile,0) mile,rnt.car_item_id,rnt.plate_no,rnt.gis_lat,rnt.gis_lng,rnt.corporation_id,
                rnt.city_id,rnt.province_id,rnt.update_time,rn.status,rn.sell_status,
                t.is_sell,rsh.operate_status from rent_content_search rnt 
                join rent_content rn on rn.id=rnt.rent_content_id 
                LEFT JOIN rent_content_return_config rcrc on rcrc.rent_content_id=rnt.rent_content_id
                left join (select rent_content_id,1 as is_sell from trade_order where rent_type=3 and status>=10100 and status<80200 and status<>10301
                ) t on rn.id=t.rent_content_id        
                LEFT JOIN(SELECT bto.rent_content_id,count(1) AS ordercount,MAX(e.all_mile)  mile,bto.city_id 
                FROM trade_order bto LEFT JOIN trade_order_ext e on bto.id=e.order_id 
                WHERE bto.rent_type = 3 AND bto.end_time >(UNIX_TIMESTAMP(NOW()) - 86400 * 2 ) GROUP BY bto.rent_content_id
                ) a ON rn.id = a.rent_content_id 
                join rent_sku_hour rsh on rn.id=rsh.rent_content_id where rnt.address_type=99
                and rnt.plate_no<>'' and rn.sort_id=112 ";
        $car_order = M('')->query($strSql.$cityAndPattern);
        //$car_order['sql'] = M('')->getlastsql();
        return $car_order;
    }

    /**
     * 车辆维修数量
     **/
    public function car_maintain($cityAndPattern){
        $strWhere="rnt.address_type = 99 AND rnt.plate_no <> '' AND rn.sort_id = 112  AND rn.business_flags = 1 AND rs.operate_status = 3 AND rn.car_info_id <> 30150 ";
        //维修车辆
        $maintain = M('rent_content_search')->alias('rnt')
            ->join('rent_content rn ON rn.id = rnt.rent_content_id', 'left')
            ->join('rent_sku_hour rs ON rs.rent_content_id=rnt.rent_content_id', 'left')
            ->join('rent_content_return_config rcrc on rcrc.rent_content_id=rnt.rent_content_id', 'left')
            ->where($strWhere.$cityAndPattern)
            ->count();
        return $maintain;
    }

    private function time_conversion($time) {
        $time = abs($time);
        $start = 0;
        $string='';
        /*$y = floor($time / 31536000);
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
                $string .= $h . "小时";
        }*/
        $s = floor($time / (60));
        if ($start || $s) {
            $start = 1;
            $time -= $s * 60;
            if ($s)
                $string .= $s . "分钟";
        }
        if (empty($string)) {
            return abs($time) . '秒';
        }
        return $string;
    }
	/***********************************小蜜运维操作记录导出*****************************************/
	public  function  ygongList(){
         $user_name = I('post.name')?I('post.name'):"空";
         $map['user_name'] = array('like',"%".$user_name."%");
         $map['status']    = 1;
         $res = M('baojia_mebike.repair_member')->field("user_id,user_name")->where($map)->select();
         // $sqlStr = "SELECT * from (SELECT user_id,concat(m.last_name,m.first_name) user_name from  baojia_mebike.repair_member rm  LEFT JOIN member m on rm.user_id = m.uid WHERE rm.status=1) u where u.user_name LIKE '%{$user_name}%'";
         // $res = M('')->query($sqlStr);
         $res = $res?$res:"";
         $this->ajaxReturn($res);
    }

    public  function   yunweiExport(){
		$map['b.status'] = 1;
        $map['c.city_id'] = array('neq','');
        $res = M('baojia_mebike.repair_member')->alias('b')->field('c.city_id,ac.name')
            ->join('corporation c on c.id=b.corporation_id','left')
            ->join('area_city ac on ac.id=c.city_id','left')
            ->where($map)
            ->group('c.city_id')->select();
        $this->assign('city',$res);
        $this->display('daochu');
    }

    public  function recordingExport($time=0){
        $model = M('operation_logging');

        if($time){
            $time = explode("|",$time);
            $start_time = strtotime($time[0]." 0:0:0");
            $end_time   = strtotime($time[1]." 23:59:59");
            $lmap['ol.time'] = array('between',array($start_time,$end_time));
        }else{
            $start_time = strtotime(date("Y-m-d",time())." 0:0:0");
            $end_time   = strtotime(date("Y-m-d",time())." 23:59:59");
            $lmap['ol.time'] = array('between',array($start_time,$end_time));
        }

        // if(!empty(I('get.user_name'))){
            // $lmap['b.user_name'] = I('get.user_name');
        // }
        if(!empty(I('get.uId'))){
            $lmap['ol.uid'] = I('get.uId');
        }
        	if(!empty(I('get.cityId'))){
            $lmap['c.city_id'] = I('get.cityId');
        }
		if(!empty(I('get.job_type'))){
            $lmap['b.job_type'] = I('get.job_type');
        }
        $lmap['_string'] = " ol.operate in(-1,1,2,3,4,5,6)";
		$result = $model->alias('ol')->field('b.user_name,um.mobile,ol.plate_no,ol.uid,ol.pic1,ol.pic2,ol.gis_lng,ol.gis_lat,ol.operate,ol.car_status,ol.time,ol.source,ol.before_battery,ol.remark')
                  ->join('baojia_mebike.repair_member b on ol.uid = b.user_id','left')
				  ->join('corporation c on c.id=b.corporation_id','left')
                  ->join('ucenter_member um on um.uid = b.user_id','left')
                  ->where($lmap)->order('ol.id desc')->select();
        // $result = $model->alias('ol')->field('concat(m.last_name,m.first_name) user_name,um.mobile,ol.plate_no,ol.uid,ol.pic1,ol.pic2,ol.gis_lng,ol.gis_lat,ol.operate,ol.car_status,ol.time')
                  // ->join('baojia_mebike.repair_member b on ol.uid = b.user_id','left')
                  // ->join('ucenter_member um on um.uid = b.user_id','left')
				  // ->join('member m on m.uid = um.uid','left')
                  // ->where($lmap)->order('time desc')->select();

        if($result){
            $res_arr = [];
            foreach ($result as $key=>&$val){
                if($val['operate'] == 1){
                    $val['operate'] = '换电设防';
                    $val['pic1']    = $val['pic1']?$_SERVER['HTTP_HOST']."/".$val['pic1']:"";
                }else if($val['operate'] == -1){
                    $val['operate'] = '换电未设防';
                    $val['pic1']    = $val['pic1']?$_SERVER['HTTP_HOST']."/".$val['pic1']:"";
                }else if($val['operate'] == 2){
                    $val['operate'] = '换电设防失败';
                    $val['pic1']    = $val['pic1']?$_SERVER['HTTP_HOST']."/".$val['pic1']:"";
                }else if($val['operate'] == 3){
                    $val['operate'] = '确认回收';
                    $val['pic1']    = "";
                }else if($val['operate'] == 4){
                    $val['operate'] = '完成小修';
                    $val['pic1']    = $val['pic2']?$_SERVER['HTTP_HOST']."/".$val['pic2']:"";
                }else if($val['operate'] == 5){
					if($val['source'] == 1){
						$val['operate'] = '下架回收/来自H5';
					}else{
						if($val['car_status'] == 1){
							$val['operate'] = '下架回收/待维修';
						}else{
							$val['operate'] = '下架回收/待调度';
						}
					}
                    $val['pic1']    = "";
                }else{
                    $val['operate'] = '待小修';
                    $val['pic1']    = $val['pic1']?$_SERVER['HTTP_HOST']."/".$val['pic1']:"";
                }
                // $address = $this->GetAmapAddress($val['gis_lng'],$val['gis_lat']);
                // $val['address'] = $address?$address:"";
                //$val['time'] = date("Y-m-d H:i",$val['time']);
                $res_arr[$key]['plate_no'] = $val['plate_no'];
                $res_arr[$key]['user_name'] = $val['user_name'];
                $res_arr[$key]['mobile']    = $val['mobile'];
                $res_arr[$key]['operate']   = $val['operate'];
                $res_arr[$key]['time']      = date("Y-m-d H:i",$val['time']);
                // $res_arr[$key]['address']   = $val['address'];
                $res_arr[$key]['pic1']      = $val['pic1'];
				$res_arr[$key]['before_battery'] = $val['before_battery'];
                $res_arr[$key]['remark']      = $val['remark'];
                unset($val['uid'],$val['car_status'],$val['pic2'],$val['gis_lng'],$val['gis_lat']);
            }
        }else{
            $res_arr = [];
        }
        // $title = array('车牌号','操作人','手机号','操作任务','操作时间','操作地址','附件');
        $title = array('车牌号','操作人','手机号','操作任务','操作时间','附件','换电前电量','换电后电量');
        $file = "员工工作记录";

        $this->exportexcels($res_arr,$title,$file);
    }
	
	public  function carCountExport($time=0){

        $model = M('operation_logging');

        if($time){
            $time = explode("|",$time);
            $start_time = strtotime($time[0]." 0:0:0");
            $end_time   = strtotime($time[1]." 23:59:59");
            $lmap['ol.time'] = array('between',array($start_time,$end_time));
        }else{
            $start_time = strtotime(date("Y-m-d",time())." 0:0:0");
            $end_time   = strtotime(date("Y-m-d",time())." 23:59:59");
            $lmap['ol.time'] = array('between',array($start_time,$end_time));
        }

        if(!empty(I('get.plate_no'))){
            if(strpos(I('get.plate_no'),"DD")){
                $lmap['ol.plate_no'] = I('get.plate_no');
            }else{
                $lmap['ol.plate_no'] = "DD".I('get.plate_no');
            }
        }

        if(!empty(I('get.car_status')) && !empty(I('get.x_type'))){
            $lmap['ol.car_status'] = array('in',array(I('get.car_status'),I('get.x_type')));
        }

        if(!empty(I('get.car_status'))){
            $lmap['ol.car_status'] = I('get.car_status');
        }

        if(!empty(I('get.x_type'))){
            $lmap['ol.car_status'] = I('get.x_type');
        }

        $lmap['_string'] = " ol.operate in(3,5,6)";
        $result = $model->alias('ol')->field('b.user_name,um.mobile,ol.plate_no,ol.uid,ol.pic1,ol.pic2,ol.gis_lng,ol.gis_lat,ol.operate,ol.car_status,ol.time,ol.source,ol.desc')
            ->join('baojia_mebike.repair_member b on ol.uid = b.user_id','left')
//            ->join('corporation c on c.id=b.corporation_id','left')
            ->join('ucenter_member um on um.uid = b.user_id','left')
            ->where($lmap)->order('ol.id desc')->select();

        if($result){
            $res_arr = [];
            foreach ($result as $key=>&$val){
                if($val['operate'] == 5){
                    $val['operate'] = '下架回收';
                    $val['pic1']    = '';
                }else if ($val['operate'] == 6){
                    $val['operate'] = '待小修';
                    $val['pic1']    = $val['pic1']?$_SERVER['HTTP_HOST']."/".$val['pic1']:"";
                }else if ($val['operate'] == 3){
                    $val['operate'] = '确认回收';
                    $val['pic1']    = '';
                }
                if($val['car_status'] == 3){
                    $val['car_status'] = '脚蹬子缺失';
                }else if($val['car_status'] == 4){
                    $val['car_status'] = '车灯损坏';
                }else if($val['car_status'] == 5){
                    $val['car_status'] = '车支子松动';
                }else if($val['car_status'] == 6){
                    $val['car_status'] = '车灯松动';
                }else if($val['car_status'] == 7){
                    $val['car_status'] = '车把松动';
                }else if($val['car_status'] == 8){
                    $val['car_status'] = '鞍座丢失';
                }else if($val['car_status'] == 9){
                    $val['car_status'] = '二维码丢失';
                }else if($val['car_status'] == 10){
                    $val['car_status'] = '车辆线路损坏';
                }else if($val['car_status'] == 11){
                    $val['car_status'] = '车辆丢失部件';
                }else if($val['car_status'] == 12){
                    $val['car_status'] = '无法打开电子锁';
                }else if($val['car_status'] == 13){
                    $val['car_status'] = '换电无法正常上线';
                }else if($val['car_status'] == 14){
                    $val['car_status'] = '无法设防';
                }else if($val['car_status'] == 15){
                    $val['car_status'] = '拧车把不走';
                }else if($val['car_status'] == 16){
                    $val['car_status'] = '二维码损坏';
                }else if($val['car_status'] == 17){
                    $val['car_status'] = '电池丢失并车辆被破坏';
                }else if($val['car_status'] == 18){
                    $val['car_status'] = '其他';
                }else{
                    $val['car_status'] = "d".$val['car_status'];
                }
                $res_arr[$key]['plate_no'] = $val['plate_no'];
                $res_arr[$key]['user_name'] = $val['user_name'];
                $res_arr[$key]['mobile']    = $val['mobile'];
                $res_arr[$key]['operate']   = $val['operate'];
                $res_arr[$key]['car_status']= $val['car_status'];
                $res_arr[$key]['time']      = date("Y-m-d H:i",$val['time']);
                $res_arr[$key]['pic1']      = $val['pic1'];
                $res_arr[$key]['desc']      = $val['desc'];
                unset($val['uid'],$val['car_status'],$val['pic2'],$val['gis_lng'],$val['gis_lat']);
            }
        }else{
            $res_arr = [];
        }
        $title = array('车牌号','操作人','手机号','操作任务','故障类型','操作时间','附件','备注');
        
        $file = "车辆统计工作记录";
        $this->exportexcels($res_arr,$title,$file);
    }

    public function exportexcels($data=array(),$title=array(),$filename='report'){
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        //导出xls 开始
        if (!empty($title)){
            foreach ($title as $k => $v) {
                $title[$k]=iconv("UTF-8", "GB2312",$v);
            }
            $title= implode("\t", $title);
            echo "$title\n";
        }
        if (!empty($data)){
            foreach($data as $key=>$val){
                foreach ($val as $ck => $cv) {
                    $data[$key][$ck]=iconv("UTF-8", "GB2312", $cv);
                }
                $data[$key]=implode("\t", $data[$key]);

            }
            echo implode("\n",$data);
        }
    }

    public function GetAmapAddress($lng,$lat,$default=''){
        $res = file_get_contents('http://restapi.amap.com/v3/geocode/regeo?output=JSON&location='.$lng.','.$lat.'&key=7461a90fa3e005dda5195fae18ce521b&s=rsv3&radius=1000&extensions=base');
        $res = json_decode($res);
        if($res->info == 'OK'){
            $default=$res->regeocode->formatted_address;
        }
        //echo $default;
        return $default;
    }

    /*public function theWeek(){
        //得到周几
        $today=time();
        $week = $today.getDay();
        var hour = today.getHours();
        var minite=today.getMinutes();
        var seconds=today.getSeconds();
        var minisecond=today.getMilliseconds();

        var we=getMonthWeek(today.getFullYear(), today.getMonth() + 1,today.getDate());
        var t='';

        var t1=new Number(t);
        console.log(t)
	console.log(week+" "+we+" "+hour);
     if((week==3&&we==3&&hour>=10)|| we>3||(week>3&&we==3) ){
         authpa=new  Number(today.getFullYear()*(today.getMonth()+1)*5).toString(16);
     }else{
         var year=today.getFullYear();
         var month=today.getMonth();
         if(month==0){
             month=12;
             year=year-1;
             authpa=new  Number(year*month*5).toString(16);
         }else{
             authpa=new  Number(today.getFullYear()*(today.getMonth())*5).toString(16);
         }

         if(today.getTime()/1000<1505268000){
             authpa="15de8ff423e";
         }

     }
	//var data = fs.readFileSync(__dirname+'/message.txt', 'utf8');
	return authpa
}
var getMonthWeek = function(a, b, c)
{
    //a = d = 当前日期
    //b = 6 - w = 当前周的还有几天过完(不算今天)
    //a + b 的和在除以7 就是当天是当前月份的第几周

    var date = new Date(a, parseInt(b) - 1, c),
            w = date.getDay(),
            d = date.getDate();
        return Math.ceil((d + 6 - w) / 7);
};*/
}