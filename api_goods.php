<?php 
$appkey = "93029013";
$username = "13860493992";
$password = "i222x0uBUrdtf6AMj8idFVTIkGeak7lya";
$time = TIMESTAMP;


$url = "https://www.jtgloble.com/api.php";
/*获取产品信息*/
$goods_list = ;

/*循环产品信息*/
foreach ($result['date']['sale_list'] as $v) {
  /*发送产品信息到信息源，获取信息新信息*/
  
	/*对比产品信息*/
  if(){
  	/*不一致更新信息*/
  	
  }
}
		$url = "https://www.jtgloble.com/api.php";
		$params = array(
			'goods_id' => $up_start_time,
			'act' => $up_end_time
		);
		$paramstring = http_build_query($params);
		$content = $this->juhecurl($url, $paramstring);
		$result = json_decode($content, true);
		if($result){
			if($result['code']=='200'){
				if($result['date']['sale_list']){
					foreach ($result['date']['sale_list'] as $v) {
						$model_goods = Model('goods');
						$condition = array('goods_serial' => $v['sale_no']);
						$goods_count = $model_goods->getGoodsList($condition) {
						if($goods_count.count ==0){
							$goods = array();
							$goods['goods_serial']      = $v['sale_no'];
							$goods['goods_name']        = $v['sale_name'];
							$goods['goods_price']       = $v['market_price'];
							$goods['goods_marketprice'] = $v['web_sale_price'];
							$goods['store_id']			= 1;
							$goods['store_name']		= '平台自营';
							$goods['color_id']          = 0;
							$goods['goods_image']        = "";
							$goods['goods_body']        = "";
							//$common_id = $model_goods->addGoodsCommon($goods);
							$goods['goods_promotion_price']=$v['market_price'];
							$goods['common_id']          = $common_id;
							//$goods_id = $model_goods->addGoods($goods);
						}
					}
				}
			}
		}
	/*获取*/
	private function access_token(){
		$url = "http://api.pre.seatent.com/ssoapi/v2/login/login";
		$params = array(
			'appkey' => $appkey,
			'password' => $password,
			'timestamp' => $time,
			'username' => $username
		);
		$source = urldecode(http_build_query($params));
		$signature = md5($source);
		$params['topSign'] = $signature;
		$paramstring = http_build_query($params);
		$content = $this->juhecurl($url, $paramstring);
		$result = json_decode($content, true);
		if($result){
			if($result['success']==true){
				echo $result['date']['token'];exit;
			}
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
	function juhecurl($url, $params=false, $ispost=0){
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
?>