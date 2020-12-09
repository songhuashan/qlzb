<?php

/**
 * Eduline卡券领取控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class CardReceiptAction extends CommonAction
{
    /**
     * 卡券领取首页方法
     * @return void
     */
    public function index() {
        //选择模版
        $tab = t($_GET['tab']);
        //优惠券、打折卡、会员卡、充值卡、课程卡
        $tpls = ['coupon','discount_card','vip_card','recharge_card','course_card'];
        if(in_array($tab,$tpls)){
            $this->is_wap ? $size = 3 : $size = 6;
            $card_list = model('Coupon')->getCardReceiptList((array_search($tab,$tpls)+1),'ctime desc',$size,$this->mid);

            $this->assign('card_type',(array_search($tab,$tpls)+1));
            $this->assign('card_list',$card_list);
            if(t($_GET['is_wap'])){
                $html         = $this->fetch('ajax_card_receipt');
                $card_list['data'] = $html;
                echo json_encode($card_list);
                exit();
            }

            $this->display();
        }
    }

    /**
     *卡券领取
     *@return void
     */
    public function saveUSerCoupon() {
        $coupon_id = t($_POST['coupon_id']);
        if(!$coupon_id){
            echo json_encode(['status'=>0,'info'=>'请选择要领取的卡券']);
            exit;
        }
        $coupon = model('Coupon')->saveUSerCoupon($coupon_id,$this->mid);

        if($coupon === 1){
            echo json_encode(['status'=>0,'info'=>model('Coupon')->getError()]);
            exit;
        } else if($coupon) {
            echo json_encode(['status'=>1,'info'=>'领取成功，请及时使用']);
            exit;
        }
        echo json_encode(['status'=>0,'info'=>'领取失败']);
        exit;
    }



}
