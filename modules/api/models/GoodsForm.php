<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/8/15
 * Time: 9:56
 */

namespace app\modules\api\models;

use app\models\Attr;
use app\models\common\CommonGoods;
use app\models\Level;
use app\models\Option;
use app\modules\mch\models\LevelListForm;
use app\utils\GetInfo;
use app\hejiang\ApiResponse;
use app\models\Favorite;
use app\models\Goods;
use app\models\GoodsPic;
use app\models\Mch;
use app\models\MiaoshaGoods;
use app\modules\api\models\mch\ShopDataForm;

class GoodsForm extends ApiModel
{
    public $id;
    public $user_id;
    public $store_id;

    public function rules()
    {
        return [
            [['id'], 'required'],
            [['user_id'], 'safe'],
        ];
    }

    /**
     * 排序类型$sort   1--综合排序 2--销量排序
     */
    public function search()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $goods = Goods::findOne([
            'id' => $this->id,
            'is_delete' => 0,
            'status' => 1,
            'store_id' => $this->store_id,
            'type' => get_plugin_type()
        ]);
        if (!$goods) {
            return new ApiResponse(1, '商品不存在或已下架');
        }
      	 $pic_list = GoodsPic::find()->select('pic_url')->where(['goods_id' => $goods->id, 'is_delete' => 0])->asArray()->all();
      	//没有更新则获取更新信息
		if($goods->name==$goods->detail || !is_array($pic_list)){
            $login = $this->access_token();
            $time = $this->getMillisecond();
            //获取商品详细
            $url = 'http://api.shangaoan.com/api/v2/goods/getGoodsInfo';
            $params = array(
                'accountId' => $login['accountId'],
                'memberId' => $login['memberId'],
                'token' => $login['token'],
                'appkey' => $this->appkey,
                'timestamp' => $time,
                'goodsId' => $goods->goodsId
            );
            ksort($params);
            $source = utf8_encode(http_build_query($params));
            $signature = sha1($this->secret.$source.$this->secret);
            $params['topSign'] = strtoupper($signature);
            $paramstring = http_build_query($params);
            $content = $this->juhecurl($url, $paramstring);
            $result = json_decode($content, true);
          	if($result['success'] == true){
            	$goods->detail = $result['data']['intro'] != null ? $result['data']['intro'] : "无";//商品描述
              	
              	$new_attr = [];
              	if (empty($result['data']['specs']) || !is_array($result['data']['specs'])) {
                  	$goods->use_attr = 0;
                	$goods->attr = '[{"attr_list":[{"attr_id":1,"attr_name":"默认"}],"num":'.$goods->goods_num.',"price":'.$goods->price.',"no":"'.$result['data']['sn'].'","pic":"","share_commission_first":"","share_commission_second":"","share_commission_third":""}]';
        		}else{
                  	$goods->use_attr = 1;
                    foreach ($result['data']['specs'] as $a => $item) {
                        $new_attr_item = [
                            'attr_list' => [],
                            'num' => intval($item['num']),
                            'price' => doubleval($item['price']),
                            'no' => $item['no'] ? $item['no'] : '',
                            'pic' => '',
                            'share_commission_first' => '',
                            'share_commission_second' => '',
                            'share_commission_third' => '',
                        ];
                        $attr_model = Attr::findOne(['attr_group_id' => 1, 'attr_name' => $item['specValue'], 'is_delete' => 0]);
                        if (!$attr_model) {
                          $attr_model = new Attr();
                          $attr_model->attr_name = $item['specValue'];
                          $attr_model->attr_group_id = 1;
                          $attr_model->is_delete = 0;
                          $attr_model->save();
                        }
                        $new_attr_item['attr_list'][] = [
                          'attr_id' => $attr_model->id,
                          'attr_name' => $attr_model->attr_name,
                        ];
                        $new_attr[] = $new_attr_item;
                      	//$attr .= '{"attr_list":[{"attr_id":1,"attr_name":"'.$specs['specValue'].'"}],"num":'.$specs['num'].',"price":'.$specs['price'].',"no":"","pic":"","share_commission_first":"","share_commission_second":"","share_commission_third":""},';
                    }
              		$goods->attr = \Yii::$app->serializer->encode($new_attr);
                }
              	if ($goods->save()) {
              		//商品图片保存
                  GoodsPic::updateAll(['is_delete' => 1], ['goods_id' => $goods->id]);
                  foreach ($result['data']['images'] as $pic_url) {
                      $goods_pic = new GoodsPic();
                      $goods_pic->goods_id = $goods->id;
                      $goods_pic->pic_url = $pic_url['original'];
                      $goods_pic->is_delete = 0;
                      $goods_pic->savePic()['msg'];
                  }
                  $pic_list = GoodsPic::find()->select('pic_url')->where(['goods_id' => $goods->id, 'is_delete' => 0])->asArray()->all();
                }
            }
        }
      
        $mch = null;
        if ($goods->mch_id) {
            $mch = $this->getMch($goods);
            if (!$mch) {
                return new ApiResponse(1, '店铺已经打烊了哦~');
            }
        }
        //$pic_list = GoodsPic::find()->select('pic_url')->where(['goods_id' => $goods->id, 'is_delete' => 0])->asArray()->all();
        $is_favorite = 0;
        if ($this->user_id) {
            $exist_favorite = Favorite::find()->where(['user_id' => $this->user_id, 'goods_id' => $goods->id, 'is_delete' => 0])->exists();
            if ($exist_favorite) {
                $is_favorite = 1;
            }
        }
        $service_list = explode(',', $goods->service);
        // 默认商品服务
        if (!$goods->service) {
            $option = Option::get('good_services', $this->store->id, 'admin', []);
            foreach ($option as $item) {
                if ($item['is_default'] == 1) {
                    $service_list = explode(',', $item['service']);
                    break;
                }
            }
        }
        $new_service_list = [];
        if (is_array($service_list)) {
            foreach ($service_list as $item) {
                $item = trim($item);
                if ($item) {
                    $new_service_list[] = $item;
                }
            }
        }
        $price = [];
        foreach (json_decode($goods->attr) as $v) {
            if ($v->price > 0) {
                $price[] = $v->price;
            } else {
                $price[] = floatval($goods->price);
            }
        }

        $res_url = GetInfo::getVideoInfo($goods->video_url);
        $goods->video_url = $res_url['url'];

        if ($goods->is_negotiable) {
            $min_price = Goods::GOODS_NEGOTIABLE;
        } else {
            $min_price = sprintf('%.2f', min($price));
        }

        $res = CommonGoods::getMMPrice([
            'attr' => $goods['attr'],
            'attr_setting_type' => $goods['attr_setting_type'],
            'share_type' => $goods['share_type'],
            'share_commission_first' => $goods['share_commission_first'],
            'price' => $goods['price'],
            'individual_share' => $goods['individual_share'],
            'mch_id' => $goods['mch_id'],
            'is_level' => $goods['is_level'],
        ]);

        $attr = json_decode($goods->attr, true);
        $goodsPrice = $goods->price;
        $isMemberPrice = false;
        if ($res['user_is_member'] === true && count($attr) === 1 && $attr[0]['attr_list'][0]['attr_name'] == '默认') {
            $goodsPrice = $res['min_member_price'] ? $res['min_member_price'] : $goods->price;
            $isMemberPrice = true;
        }

        // 多商户商品无会员价
        if ($res['is_mch_goods'] === true) {
            $isMemberPrice = false;
        }
      
      
        $data = [
            'id' => $goods->id,
            'pic_list' => $pic_list,
            'attr' => $goods->attr,
            'is_negotiable' => $goods->is_negotiable,
            'max_price' => sprintf('%.2f', max($price)),
            'min_price' => $min_price,
            'name' => $goods->name,
            'cat_id' => $goods->cat_id,
            'price' => sprintf('%.2f', $goodsPrice),
            'detail' => $goods->detail,
            'sales' => $goods->getSalesVolume() + $goods->virtual_sales,
            'attr_group_list' => $goods->getAttrGroupList(),
            'num' => $goods->getNum(),
            'is_favorite' => $is_favorite,
            'service_list' => $new_service_list,
            'original_price' => sprintf('%.2f', $goods->original_price),
            'video_url' => $goods->video_url,
            'unit' => $goods->unit,
            'use_attr' => intval($goods->use_attr),
            'mch' => $mch,
            'max_share_price' => sprintf('%.2f', $res['max_share_price']),
            'min_member_price' => sprintf('%.2f', $res['min_member_price']),
            'is_share' => $res['is_share'],
            'is_level' => $res['is_level'],
            'is_member_price' => $isMemberPrice,
        ];

        return new ApiResponse(0, 'success', $data);
    }
  	/* php 获取13位时间戳 */
    public function getMillisecond() { 
        list($t1, $t2) = explode(' ', microtime());     
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000); 
    }
    public $appkey = "93029013";
    public $secret = "a190791ef13f49a39be08b25255edb98";
    public $username = "13860493992";
    public $password = "123123";
	private function access_token(){
        $time = $this->getMillisecond();
		$url = "http://api.shangaoan.com/ssoapi/v2/login/login";
		$params = array(
			'appkey' => $this->appkey,
			'password' => md5($this->password),
			'timestamp' => $time,
			'username' => $this->username
		);
		$source = $this->secret.utf8_encode(http_build_query($params)).$this->secret;
		$signature = sha1($source);
		$params['topSign'] = strtoupper($signature);
		$paramstring = http_build_query($params);
		$content = $this->juhecurl($url, $paramstring);
		$result = json_decode($content, true);
		if($result){
			if($result['success']==true){
				return $result['data'];
			}
		}
		return null;
	}
	private function juhecurl($url, $params=false, $ispost=0){
		$httpInfo = array();
		$ch = curl_init();
	 
		curl_setopt($ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
		curl_setopt($ch, CURLOPT_USERAGENT , 'JuheData' );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 60 );
		curl_setopt($ch, CURLOPT_TIMEOUT , 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION , true);
		if($ispost)
		{
			curl_setopt($ch , CURLOPT_POST , true );
			curl_setopt($ch , CURLOPT_POSTFIELDS , $params );
			curl_setopt($ch , CURLOPT_URL , $url );
		}
		else
		{
			if($params){
				curl_setopt($ch , CURLOPT_URL , $url.'?'.$params );
			}else{
				curl_setopt($ch , CURLOPT_URL , $url);
			}
		}
		$response = curl_exec($ch );
		if ($response === FALSE) {
			//echo "cURL Error: " . curl_error($ch);
			return false;
		}
		$httpCode = curl_getinfo($ch , CURLINFO_HTTP_CODE );
		$httpInfo = array_merge($httpInfo , curl_getinfo( $ch ) );
		curl_close( $ch );
		return $response;
	}
    //获取商品秒杀数据
    public function getMiaoshaData($goods_id)
    {
        $miaosha_goods = MiaoshaGoods::findOne([
            'goods_id' => $goods_id,
            'is_delete' => 0,
            'start_time' => intval(date('H')),
            'open_date' => date('Y-m-d'),
        ]);
        if (!$miaosha_goods) {
            return null;
        }
        $attr_data = json_decode($miaosha_goods->attr, true);
        $total_miaosha_num = 0;
        $total_sell_num = 0;
        $miaosha_price = 0.00;
        foreach ($attr_data as $i => $attr_data_item) {
            $total_miaosha_num += $attr_data_item['miaosha_num'];
            $total_sell_num += $attr_data_item['sell_num'];
            if ($miaosha_price == 0) {
                $miaosha_price = $attr_data_item['miaosha_price'];
            } else {
                $miaosha_price = min($miaosha_price, $attr_data_item['miaosha_price']);
            }
        }
        return [
            'miaosha_num' => $total_miaosha_num,
            'sell_num' => $total_sell_num,
            'miaosha_price' => (float)$miaosha_price,
            'begin_time' => strtotime($miaosha_goods->open_date . ' ' . $miaosha_goods->start_time . ':00:00'),
            'end_time' => strtotime($miaosha_goods->open_date . ' ' . $miaosha_goods->start_time . ':59:59'),
            'now_time' => time(),
        ];
    }


    // 快速给购买商品
    public function quickGoods($twocatid)
    {
        $goods = Goods::find()
            ->where([
                'store_id' => $this->store_id,
                'is_delete' => 0,
                'status' => 1,
                'quick_purchase' => 1
            ])
            ->andWhere([

                'in', 'cat_id', $twocatid
            ])->asArray()
            ->all();
        foreach ($goods as $key => &$value) {
            $value['attr'] = json_decode($value['attr']);
            foreach ($value['attr'] as $key2 => $value2) {
                foreach ($value2->attr_list as $key3 => $value3) {
                    $value['attr_name'] = $value3->attr_name;
                }
                // $value['attr_num'][] = $value2->num;
                // $value['attr_price'][] = $value2->price;
                // $value['attr_no'][] = $value2->no;
                // $value['attr_pic'][] = $value2->pic;
                $value['num'] = 0;
            }
            // unset($value['attr']);
        }
        return [
            'code' => 0,
            'data' => [
                'list' => $goods,
            ],
        ];
    }

    /**
     * @param Goods $goods
     */
    public function getMch($goods)
    {
        $f = new ShopDataForm();
        $f->mch_id = $goods->mch_id;
        $shop = $f->getShop();
        if (isset($shop['code']) && $shop['code'] == 1) {
            return null;
        }
        return $shop;
    }
}
