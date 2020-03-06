<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/2
 * Time: 0:11
 */

namespace app\modules\api\models;

use app\hejiang\ApiResponse;
use app\models\Cat;
use app\models\Goods;
use app\models\GoodsCat;
use yii\data\Pagination;

class CatListForm extends ApiModel
{
    public $store_id;
    public $limit;
    public $goodspage;
    public $cat_id;

    public function rules()
    {
        return [
            [['store_id', 'limit','goodspage','cat_id'], 'integer'],
        ];
    }

    public function search()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $query = Cat::find()->where([
            'is_delete' => 0,
            'parent_id' => 0,
            'is_show' => 1,
        ]);
        if ($this->store_id) {
            $query->andWhere(['store_id' => $this->store_id]);
        }
        if ($this->limit) {
            $query->limit($this->limit);
        }
        $this->goodspage=($this->goodspage-1)*20;
        $query->orderBy('sort ASC');
        $list = $query->select('id,store_id,parent_id,name,pic_url,big_pic_url,advert_pic,advert_url')->asArray()->all();

        $recommendedlist=array();
        $recommended_list=array();
        foreach ($list as $i => $item) {
            $sub_list = Cat::find()->where([
                'is_delete' => 0,
                'parent_id' => $item['id'],
                'is_show' => 1,
            ])->orderBy('sort ASC')
                ->select('id,store_id,parent_id,name,pic_url,big_pic_url')->asArray()->all();
            $list[$i]['list'] = $sub_list ? $sub_list : [];
            if (!empty($item['big_pic_url'])){
                $recommendedlist['id']=$item['id'];
                $recommendedlist['name']=$item['name'];
                $recommendedlist['big_pic_url']=$item['big_pic_url'];
                $recommended_list[]=$recommendedlist;
            }
        }
        $this->cat_id=$list[0]['id'];
        $this->goodspage;
        $goodslist=$this->goods_list()['data']['goods_list'];
        $data = [
            'list'=>$list,
            'goods_list'=>$goodslist,
            'recommended_list'=>$recommended_list,
        ];
        return new ApiResponse(0, 'success', $data);
    }
    public function goods_list(){
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $this->goodspage=($this->goodspage-1)*20;
        $goodslist = GoodsCat::find()
            ->alias('gc')->where([
            'gc.is_delete' => 0,
            'gc.store_id' => $this->store_id,
            'g.is_delete'  => 0,
            'g.status' => 1,
        ])->andWhere(['gc.cat_id'=>$this->cat_id])
            ->leftJoin(['g' => Goods::tableName()], 'gc.goods_id=g.id')
            ->orderBy('gc.addtime asc')
            ->select('g.id,g.name,g.cover_pic')->limit(20)->offset($this->goodspage)->asArray()->all();
        $data = [
            'goods_list'=>$goodslist,
        ];
        return new ApiResponse(0, 'success', $data);
    }
}
