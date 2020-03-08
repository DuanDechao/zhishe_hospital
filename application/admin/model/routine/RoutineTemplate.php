<?php
namespace  app\admin\model\routine;

use app\routine\model\store\StoreOrder;
use app\routine\model\user\RoutineUser;
use app\routine\model\user\WechatUser;
use service\RoutineTemplateService;
use app\routine\model\store\StoreOrderCartInfo;
/**
 * 小程序模板消息
 * Class RoutineTemplate
 * @package app\routine\model\routine
 */
class RoutineTemplate{
    /**
     * 退款成功发送消息
     * @param array $order
     */
    public static function sendOrderRefundSuccess($order = array(), $postData = array()){
        $goodsName = StoreOrderCartInfo::getProductNameList($order['id']);
        $data['character_string1']['value'] =  $order['order_id'];
        $data['thing2']['value'] =  implode(",", $goodsName);
        $data['amount3']['value'] =  $postData['refund_price'];
        $data['thing4']['value'] = '已成功退款，如有疑问请联系客服';
        RoutineTemplateService::sendTemplate(WechatUser::getOpenId($order['uid']), RoutineTemplateService::ORDER_REFUND_SUCCESS,'',$data,'');
    }
    /**
     * 用户申请退款给管理员发送消息
     * @param array $order
     * @param string $refundReasonWap
     * @param array $adminList
     */
    public static function sendOrderRefundStatus($order = array(),$refundReasonWap = '',$adminList = array()){
        $data['keyword1']['value'] =  $order['order_id'];
        $data['keyword2']['value'] =  $refundReasonWap;
        $data['keyword3']['value'] =  date('Y-m-d H:i:s',time());
        $data['keyword4']['value'] =  $order['pay_price'];
        $data['keyword5']['value'] =  '原路返回';
        foreach ($adminList as $uid){
            $formId = RoutineFormId::getFormIdOne($order['uid']);
            if($formId){
                RoutineFormId::delFormIdOne($formId);
                RoutineTemplateService::sendTemplate(WechatUser::getOpenId($uid), RoutineTemplateService::ORDER_REFUND_STATUS,'',$data,$formId);
            }
        }
    }
    /**
     * 砍价成功通知
     * @param array $bargain
     * @param array $bargainUser
     * @param int $bargainUserId
     */
    public static function sendBargainSuccess($bargain = array(),$bargainUser  = array(),$bargainUserId = 0){
        $data['keyword1']['value'] =  $bargain['title'];
        $data['keyword2']['value'] =  $bargainUser['bargain_price'];
        $data['keyword3']['value'] =  $bargainUser['bargain_price_min'];
        $data['keyword4']['value'] =  $bargainUser['price'];
        $data['keyword5']['value'] =  $bargainUser['bargain_price_min'];
        $data['keyword6']['value'] =  '恭喜您，已经砍到最低价了';
        $formId = RoutineFormId::getFormIdOne($bargainUserId);
        if($formId){
            $dataFormId['formId'] = $formId;
            RoutineTemplateService::sendTemplate(WechatUser::getOpenId($bargainUser['uid']),RoutineTemplateService::BARGAIN_SUCCESS,'',$data,$formId);
        }
    }
    /**
     * 订单支付成功发送模板消息
     * @param string $formId
     * @param string $orderId
     */
    public static function sendOrderSuccess($formId = '',$orderId = ''){
        if($orderId == '') return ;
        $order = StoreOrder::where('order_id',$orderId)->find();
        if($formId == '') $formId = RoutineFormId::getFormIdOne($order['uid']);
        $goodsName = StoreOrderCartInfo::getProductNameList($order['id']);
        $data['thing4']['value'] =  "体检套餐购买";
        $data['thing1']['value'] =  implode(",", $goodsName);
       // $data['keyword3']['value'] =  '已支付';
        $data['amount2']['value'] =  $order['pay_price'];
        $data['thing3']['value'] = '套餐购买成功，点击查看购买详情';
       // if($order['pay_type'] == 'yue') $data['keyword5']['value'] =  '余额支付';
       // else if($order['pay_type'] == 'weixin') $data['keyword5']['value'] =  '微信支付';
//        else if($order['pay_type'] == 'offline') $data['keyword5']['value'] =  '线下支付';
        RoutineFormId::delFormIdOne($formId);
        RoutineTemplateService::sendTemplate(WechatUser::getOpenId($order['uid']), RoutineTemplateService::ORDER_PAY_SUCCESS,'/pages/orders-con/orders-con?order_id='.$orderId,$data,$formId);
    }

    /**
     * 订单支付成功发送模板消息
     * @param string $formId
     * @param string $orderId
     */
    public static function sendSubscribeSuccess($formId = '',$orderId = ''){
        if($orderId == '') return ;
        $order = StoreOrder::where('order_id',$orderId)->find();
        $goodsName = StoreOrderCartInfo::getProductNameList($order['id']);
        $data['thing2']['value'] =  implode(",", $goodsName);
        $data['date3']['value'] =  $order['sub_date'];
        $data['name6']['value'] =  $order['real_name'];
        $data['thing10']['value'] = '预约日期如果有变动，管理员会联系您哦～';
        RoutineTemplateService::sendTemplate(WechatUser::getOpenId($order['uid']), RoutineTemplateService::ORDER_SUB_SUCCESS,'/pages/orders-con/orders-con?order_id='.$orderId,$data,$formId);
    }

    /**
     * 订单支付成功发送模板消息
     * @param string $formId
     * @param string $orderId
     */
    public static function sendSubscribeModifySuccess($formId = '',$orderId = ''){
        if($orderId == '') return ;
        $order = StoreOrder::where('order_id',$orderId)->find();
        $goodsName = StoreOrderCartInfo::getProductNameList($order['id']);
        $data['character_string1']['value'] =  $orderId;
        $data['name2']['value'] =  $order['real_name'];
        $data['thing3']['value'] =  implode(",", $goodsName);
        $data['date4']['value'] = $order['sub_date'];
        RoutineTemplateService::sendTemplate(WechatUser::getOpenId($order['uid']), RoutineTemplateService::ORDER_SUB_MODIFY_SUCCESS,'/pages/orders-con/orders-con?order_id='.$orderId,$data,$formId);
    }

}
