{extend name="public/container"}
{block name="content"}
<div class="ibox-content order-info">

    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    预约人信息
                </div>
                <div class="panel-body">
                    <div class="row show-grid">
                        <div class="col-xs-6" >姓名: {$orderInfo['real_name']}</div>
                        <div class="col-xs-6">联系号码: {$orderInfo['phone_number']}</div>
                        <div class="col-xs-6">性别: {$orderInfo['sex']}</div>
                        <div class="col-xs-6">婚否: {$orderInfo['married']}</div>
                        <div class="col-xs-6">证件类型: {$orderInfo['card_type']}</div>
                        <div class="col-xs-6">证件号码: {$orderInfo['card_number']}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    预约信息
                </div>
                <div class="panel-body">
                    <div class="row show-grid">
                        <div class="col-xs-6" >预约编号: {$orderInfo.order_id}</div>
                        <div class="col-xs-6">预约日期: {$orderInfo['sub_date']}</div>
                        <div class="col-xs-6" style="color: #8BC34A;">预约状态:
                            {if condition="$orderInfo['paid'] eq 0 && $orderInfo['status'] eq 0"}
                            待支付
                            {elseif condition="$orderInfo['paid'] eq 1 && $orderInfo['confirmed'] eq 0 && $orderInfo['status'] eq 0 && $orderInfo['refund_status'] eq 0"/}
                            待预约
                            {elseif condition="$orderInfo['paid'] eq 1 && $orderInfo['confirmed'] eq 1 && $orderInfo['status'] eq 0 && $orderInfo['refund_status'] eq 0"/}
                            待体检
                            {elseif condition="$orderInfo['paid'] eq 1 && $orderInfo['confirmed'] eq 1 && $orderInfo['status'] eq 1 && $orderInfo['refund_status'] eq 0"/}
                            待收报告单
                            {elseif condition="$orderInfo['paid'] eq 1 && $orderInfo['confirmed'] eq 1 && $orderInfo['status'] eq 2 && $orderInfo['refund_status'] eq 0"/}
                            待评价
                            {elseif condition="$orderInfo['paid'] eq 1 && $orderInfo['confirmed'] eq 1 && $orderInfo['status'] eq 3 && $orderInfo['refund_status'] eq 0"/}
                            交易完成
                            {elseif condition="$orderInfo['paid'] eq 1 && $orderInfo['refund_status'] eq 1"/}
                            申请退款<b style="color:#f124c7">{$orderInfo.refund_reason_wap}</b>
                            {elseif condition="$orderInfo['paid'] eq 1 && $orderInfo['refund_status'] eq 2"/}
                            已退款
                            {/if}
                        </div>
                        <div class="col-xs-6">套餐总数: {$orderInfo.total_num}</div>
                        <div class="col-xs-6">套餐总价: ￥{$orderInfo.total_price}</div>
                        <div class="col-xs-6">实际支付: ￥{$orderInfo.pay_price}</div>
                        {if condition="$orderInfo['refund_price'] GT 0"}
                        <div class="col-xs-6" style="color: #f1a417">退款金额: ￥{$orderInfo.refund_price}</div>
                        {/if}
                        {if condition="$orderInfo['deduction_price'] GT 0"}
                        <div class="col-xs-6" style="color: #f1a417">使用积分: {$orderInfo.use_integral}积分(抵扣了￥{$orderInfo.deduction_price})</div>
                        {/if}
                        {if condition="$orderInfo['back_integral'] GT 0"}
                        <div class="col-xs-6" style="color: #f1a417">退回积分: ￥{$orderInfo.back_integral}</div>
                        {/if}
                        <div class="col-xs-6">创建时间: {$orderInfo.add_time|date="Y/m/d H:i",###}</div>
                        <div class="col-xs-6">支付方式:
                            {if condition="$orderInfo['paid'] eq 1"}
                                           {if condition="$orderInfo['pay_type'] eq 'weixin'"}
                                           微信支付
                                           {elseif condition="$orderInfo['pay_type'] eq 'yue'"}
                                           余额支付
                                           {elseif condition="$orderInfo['pay_type'] eq 'offline'"}
                                           线下支付
                                           {else/}
                                           其他支付
                                           {/if}
                            {else/}
                            {if condition="$orderInfo['pay_type'] eq 'offline'"}
                            线下支付
                            {else/}
                            未支付
                            {/if}
                            {/if}
                        </div>
                        {notempty name="orderInfo.pay_time"}
                        <div class="col-xs-6">支付时间: {$orderInfo.pay_time|date="Y/m/d H:i",###}</div>
                        {/notempty}
                        <div class="col-xs-6" style="color: #ff0005">用户备注: {$orderInfo.mark?:'无'}</div>
                        <div class="col-xs-6" style="color: #733AF9">推广人: {if $spread}{$spread}{else}无{/if}</div>
                    </div>
                </div>
            </div>
        </div>
        {if condition="$orderInfo['delivery_type'] eq 'express'"}
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    物流信息
                </div>
                <div class="panel-body">
                    <div class="row show-grid">
                        <div class="col-xs-6" >快递公司: {$orderInfo.delivery_name}</div>
                        <div class="col-xs-6">快递单号: {$orderInfo.delivery_id} | <button class="btn btn-info btn-xs" type="button"  onclick="$eb.createModalFrame('物流查询','{:Url('express',array('oid'=>$orderInfo['id']))}',{w:322,h:568})">物流查询</button></div>
                    </div>
                </div>
            </div>
        </div>
        {elseif condition="$orderInfo['delivery_type'] eq 'send'"}
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    配送信息
                </div>
                <div class="panel-body">
                    <div class="row show-grid">
                        <div class="col-xs-6" >送货人姓名: {$orderInfo.delivery_name}</div>
                        <div class="col-xs-6">送货人电话: {$orderInfo.delivery_id}</div>
                    </div>
                </div>
            </div>
        </div>
        {/if}
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    备注信息
                </div>
                <div class="panel-body">
                    <div class="row show-grid">
                        <div class="col-xs-6" >{if $orderInfo.mark}{$orderInfo.mark}{else}暂无备注信息{/if}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="{__FRAME_PATH}js/content.min.js?v=1.0.0"></script>
{/block}
{block name="script"}

{/block}
