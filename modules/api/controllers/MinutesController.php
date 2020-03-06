<?php

/**
 * 自动执行代码
 */
namespace app\modules\api\controllers;

use app\models\Cat;
use app\models\Goods;
use app\models\GoodsCat;
use app\models\GoodsPic;
use app\models\Order;
use app\models\OrderDetail;
use app\modules\mch\models\GoodsForm;
use app\modules\mch\models\CatForm;
use app\modules\mch\models\GoodsSearchForm;
use yii\data\Pagination;

class MinutesController extends Controller
{
    public $appkey = "93029013";
    public $secret = "a190791ef13f49a39be08b25255edb98";
    public $username = "13860493992";
    public $password = "123123";
	/**
     * 默认方法
     */
    public function actionIndex() {
      	$login = $this->access_token();
      	//$this->_cron_category(0, 0, $login);
        //$this->_cron_goodsList(1, $login);
        $this->_cron_order($login);
      	$this->_cron_order_list(1, $login);
    }
  	/* php 获取13位时间戳 */
    public function getMillisecond() { 
        list($t1, $t2) = explode(' ', microtime());     
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000); 
    }
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
	/*获取更新分类信息*/
	private function _cron_category($catId = 0,$parent_id = 0, $login){
      	$login = $this->access_token();
        $time = $this->getMillisecond();
		/*查询全部分类*/	
		$url = "http://api.shangaoan.com/api/v2/goods/getTopCategory";
		$params = array(
			'accountId' => $login['accountId'],
			'memberId' => $login['memberId'],
			'token' => $login['token'],
			'appkey' => $this->appkey,
			'timestamp' => $time,
			'catId' => $catId,
			'listShow' => 0,
		);
      	ksort($params);
		$source = utf8_encode(http_build_query($params));
		$signature = sha1($this->secret.$source.$this->secret);
		$params['topSign'] = strtoupper($signature);
		$paramstring = http_build_query($params);
		$content = $this->juhecurl($url, $paramstring);
		$result = json_decode($content, true);
      	if($result){
			if($result['success'] == true && $result['data']['totalCount'] > 0){
              	foreach($result['data']['result'] as $val){
                  	$cat = Cat::findOne(['catid' => $val['id']]);
                    if (!$cat) {
                       $cat = new Cat();
                    }
                  	$cat->store_id = 1;
                  	$cat->parent_id = $parent_id;
                  	$cat->name = $val['name'];
                  	$cat->pic_url = $val['image'];
                  	$cat->big_pic_url = $val['image'];
                  	$cat->sort = $val['catOrder'];
                  	$cat->addtime = time();
                  	$cat->is_delete = 0;
                  	$cat->catid = $val['id'];
                    echo $parent_id.$val['name'].$cat->saveCat()['msg'].'<br>';
                  	
                  	$this->_cron_category($val['id'], $cat->id, $login);
                  
                }
			}
		}
		return null;
	}
  	/*获取商品信息*/
	private function _cron_goodsList($page, $login){
        $time = $this->getMillisecond();
		/*查询全部分类*/	
		$url = "http://api.shangaoan.com/api/v2/goods/searchingGoods ";
		$params = array(
			'accountId' => $login['accountId'],
			'memberId' => $login['memberId'],
			'token' => $login['token'],
			'appkey' => $this->appkey,
			'timestamp' => $time,
          	'pageSize' => 50,
          	'pageNum' => $page,
          	'ignoreStock' => 0
		);
      	ksort($params);
		$source = utf8_encode(http_build_query($params));
		$signature = sha1($this->secret.$source.$this->secret);
		$params['topSign'] = strtoupper($signature);
		$paramstring = http_build_query($params);
		$content = $this->juhecurl($url, $paramstring);
      	//echo $content;
		$result = json_decode($content, true);
      	if($result){
			if($result['success'] == true && $result['data']['totalCount'] > 0){
              	foreach($result['data']['result'] as $val){
                  	//判断是否有分类
                  	$cat = Cat::findOne(['catid' => $val['catId']]);
                  	if ($cat) {
                  		//$this->_cron_goodsInfo($val['goodsId'], $cat->id, $login);
                        $goods = Goods::findOne(['goodsId' => $val['goodsId']]);
                        if (!$goods) {
                          	$goods = new Goods();
                        	$goods->store_id = 1;
                        	$goods->price = $val['price'];
                        	$goods->cost_price = $val['mktPrice'];
                        	$goods->detail = $val['name'];//商品描述
                            $goods->goods_num = $val['enableStore'];
                            $goods->status = $goods->goods_num>0?1:0;
                            $goods->use_attr = 0;
                            $goods->attr = '[{"attr_list":[{"attr_id":1,"attr_name":"默认"}],"num":'.$goods->goods_num.',"price":'.$goods->price.',"no":"","pic":"","share_commission_first":"","share_commission_second":"","share_commission_third":""}]';
                            $goods->full_cut = '{"pieces":"","forehead":""}';
                            $goods->integral = '{"give":"0","forehead":"","more":""}';
                        	$goods->goodsId = $val['goodsId'];
                        }
                        $goods->cat_id = $cat->id;
                        $goods->name = $val['name'];
                        $goods->original_price = $val['originalprice']==0?$val['price']:$val['originalprice'];
                        $goods->cover_pic = $val['thumbnail'];
                        $goods->sn = $val['sn'];
                        $goods->tradeType = $val['tradeType'];
                        echo $val['name'].'|'.$cat->id.$cat->name.'|'.$goods->saveGoods()['msg']."<br>";
                        //多分类设置
                        GoodsCat::updateAll(['is_delete' => 1], ['goods_id' => $goods->id]);
                      	$cats = GoodsCat::findOne(['goods_id' => $goods->id]);
                      	if(!$cats){
                        	$cats = new GoodsCat();
                        }
                        $cats->goods_id = $goods->id;
                        $cats->store_id = $goods->store_id;
                        $cats->addtime = time();
                        $cats->cat_id = $cat->id;
                        $cats->is_delete = 0;
                        echo $cats->saveGoodsCat()['msg']."<br>";
                    }else{
                      //echo $val['name']."分类不存在<br>";
                    }
                }
              	$page = $page + 1;
                if($page <= $result['data']['totalPageCount']){
                	$this->_cron_goodsList($page, $login);
                    echo $page.'|'.$result['data']['totalPageCount']."<br>";
                }
			}
		}
    }
	/*更新商品详细*/
	private function _cron_goodsInfo($goodsId, $catId = 0, $login){      
        $time = $this->getMillisecond();
		$url = 'http://api.shangaoan.com/api/v2/goods/getGoodsInfo';
		$params = array(
			'accountId' => $login['accountId'],
			'memberId' => $login['memberId'],
			'token' => $login['token'],
			'appkey' => $this->appkey,
			'timestamp' => $time,
          	'goodsId' => $goodsId
		);
      	ksort($params);
		$source = utf8_encode(http_build_query($params));
		$signature = sha1($this->secret.$source.$this->secret);
		$params['topSign'] = strtoupper($signature);
		$paramstring = http_build_query($params);
		$content = $this->juhecurl($url, $paramstring);echo $content."<br>";
		$result = json_decode($content, true);
      	if($result){
			if($result['success'] == true){
                echo $result['data'];
            }
        }
		
	}
	/*上传订单*/
	private function _cron_order($login){
      	$time = $this->getMillisecond();
      	
      	//发送已支付未发送的订单
        $query = Order::find()->where(['store_id' => 1, 'is_delete' => 0]);//'is_pay' =>1, 'is_send'=>0, 
        $url = 'http://api.shangaoan.com/api/v2/order/createOrders';
      	
      	$urls = 'http://img.pre.seatent.com/statics/json/regionsAll.json';
      	$regionsAll = $this->juhecurl($urls);
      	
      	$list = $query->orderBy('addtime DESC')->all();
		//查询未上传的订单信息
		foreach ($list as $order) {
          	$order_detail_list = OrderDetail::findAll(['order_id' => $order->id, 'is_delete' => 0]);
          	$goodsIds = "";
          	$nums = "";
          	$lifes = "";
          	//获取地区ID
          	$resultadd = json_decode($regionsAll, true);
          	$address_data = json_decode($order->address_data, true);
          	foreach($resultadd as $region){
              	if($address_data['province']==$region['regionName']){
                  	//echo  $address_data['province']. $region['regionId']."<br>";
                  	$area = $region['regionId'];
                  	foreach($region['children'] as $region1){
                		if($address_data['city']==$region1['regionName']){
                        	//echo  $address_data['city']. $region1['regionId']."<br>";
                  			$area = $region1['regionId'];
                            foreach($region1['children'] as $region2){
                                if($address_data['district']==$region2['regionName']){
                                    //echo  $address_data['district']. $region2['regionId']."<br>";
                  					$area = $region2['regionId'];
                                }
                            }
                        }
                    }
                }
            }
          	foreach ($order_detail_list as $order_detail) {
                $goods = Goods::findOne($order_detail->goods_id);
            	if (!$goods) {
                    continue;
                }
                $url_life = 'http://api.shangaoan.com/api/v2/goods/getGoodsPrice';
                $params_life = array(
                  'accountId' => $login['accountId'],
                  'memberId' => $login['memberId'],
                  'token' => $login['token'],
                  'appkey' => $this->appkey,
                  'timestamp' => $time,
                  'goodsId' => $goods->goodsId,
                  'num' => $order_detail->num,
                  'cityId' => $area
                );
                ksort($params_life);
                $source_life = utf8_encode(http_build_query($params_life));
                $signature_life = sha1($this->secret.$source_life.$this->secret);
                $params_life['topSign'] = strtoupper($signature_life);
                $paramstring_life = http_build_query($params_life);//	echo $paramstring_life."<br>";
              	$content_life = $this->juhecurl($url_life, $paramstring_life); //echo $content_life."<br>";
                $result_life = json_decode($content_life, true);
                if($result_life){
                    if($result_life['success']==true){
                        //修改订单信息，已经上传
                        //echo $result_life['data']['lifes']."<br>";
                      	$life = $result_life['data']['lifes'][0]['life'];
                      	
                    }
                }
              	if(!$lifes){
                	$lifes = $life;
                }else{
              		$lifes .= ",".$life;
                }
              	if(!$goodsIds){
                	$goodsIds = $goods->goodsId;
                }else{
              		$goodsIds .= ",".$goods->goodsId;
                }
              	//$this->_cron_goodsInfo($goods->goodsId,0,$login);
              	if(!$nums){
                	$nums = $order_detail->num;
                }else{
              		$nums .= ",".$order_detail->num;
                }
          	}
			$params = array(
              'accountId' => $login['accountId'],
              'memberId' => $login['memberId'],
              'token' => $login['token'],
              'appkey' => $this->appkey,
              'timestamp' => $time,
              'area' => $area,//区域编码
              'goodsIds' => $goodsIds,//商品货号SNs
              'nums' => $nums,//数量
              'name' => $order->name,//用户名称
              'productNums' => $nums,//规格数量
              'mobile' => $order->mobile,//手机
              'address' => $order->address,//地址
              'identification' => $order->code,//身份证号码
              'customOrder'=> $order->order_no,
              'lifes'=> $lifes
			);
            ksort($params);
            $source = utf8_encode(http_build_query($params));
            $signature = sha1($this->secret.$source.$this->secret);
            $params['topSign'] = strtoupper($signature);
            $paramstring = http_build_query($params); //echo $paramstring."<br>";
            $content = $this->juhecurl($url, $paramstring); echo $content."<br>";
            $result = json_decode($content, true);
			if($result){
				if($result['success']==true){
					//修改订单信息，已经上传
                  	echo "order:".$result['data'];
				}
			}
		}
      	return null;
	}
  	//获取全部订单信息
  	private function _cron_order_list($page, $login){
        $time = $this->getMillisecond();
      	//获取全部订单信息的url
        $url = 'http://api.shangaoan.com/api/v2/order/orderList';//获取订单信息
      	$params = array(
          'accountId' => $login['accountId'],
          'memberId' => $login['memberId'],
          'token' => $login['token'],
          'appkey' => $this->appkey,
          'timestamp' => $time,
          'pageSize' => 50,//每页条数
          'pageNum' => $page,//第几页
          'status' => 5 //全部 （不传），待付款-0，待发-2，待收货-5 完成-7; 已取消-8
        );
        ksort($params);
        $source = utf8_encode(http_build_query($params));
        $signature = sha1($this->secret.$source.$this->secret);
        $params['topSign'] = strtoupper($signature);
        $paramstring = http_build_query($params); echo $paramstring."<br>";
        $content = $this->juhecurl($url, $paramstring); echo $content."<br>";
        $result = json_decode($content, true);
        if($result){
          	if($result['success']==true){
            	//修改订单信息，已经上传
            	foreach($result['data']['result'] as $val){
                	echo $val['customOrder']."|".$val['sn']."|".$val['orderStatus']."<br>";
                  	//查询订单
                  	$order_detail = Order::find()->where(['order_no' =>  $val['customOrder'], 'mch_id' => 0])->one();
                  	//对比订单状态
                  	//发货
                  	if($val['status'] == 5 && $order_detail['is_send'] == 0){
                      $order_detail->is_send = 1;//是否发货
                      $order_detail->send_time = $result['data']['result']['saleCmplTime'];//发货时间
                      $order_detail->express = $result['data']['result']['dlyName'];//物流公司
                      $order_detail->express_no = $result['data']['result']['shipNo'];//物流单号
                    }
                  	//修改订单状态
                    if ($order_detail->save()) {
                        echo '操作成功';
                    } else {
                        echo '操作失败';
                    }
                  	
                  
                }
          	}
        }

        $page = $page + 1;
        if($page <= $result['data']['totalPageCount']){
          	$this->_cron_order_list($page, $login);
          	echo $page.'|'.$result['data']['totalPageCount']."<br>";
        }
      	return null;
    }
	/**
	 * 请求接口返回内容
	 * @param  string $url [请求的URL地址]
	 * @param  string $params [请求的参数]
	 * @param  int $ipost [是否采用POST形式]
	 * @return  string
	 */
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
}