<?php
namespace Api\Controller;

use Think\Controller\RestController;

class BController extends RestController
{
    protected function promossion($uid, $iflogin,$isCheckUid=true)
    {
        if ($isCheckUid) {
            $this->CheckInt($uid);
        }
        if (is_numeric($uid) && $uid > 0) {
            if (check_iflogin($uid, $iflogin) == false) {
                $result = array('status' => 0, 'info' => '用户验证失败,请重新登录');
                if(C("ISTEST") != 1){
                    $this->response($result, 'json');    
                }
            }
        }
    }
	

    protected function CheckInt($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            $result = array('status' => 0, 'info' => '参数错误');
            $this->response($result, 'json');
        } else {
            return intval($id);
        }
    }
	

}

?>