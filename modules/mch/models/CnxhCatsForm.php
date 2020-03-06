<?php
namespace app\modules\mch\models;

use app\models\CnxhCats;
use yii\data\Pagination;

class CnxhCatsForm extends MchModel
{
    public $store_id;
    public $page;
    public $limit;

    public function rules()
    {
        return [
            [['page'],'default','value'=>1],
            [['limit'],'default','value'=>20]
        ];
    }
    /**
     * @param $store_id
     * @return array
     * 获取列表数据
     */
    public function getList()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $query = CnxhCats::find()->where(['store_id'=>$this->store_id]);



        $count = $query->count();

        $p = new Pagination(['totalCount'=>$count,'pageSize'=>$this->limit]);
        $list=$query->offset($p->offset)->limit($p->limit)->orderBy(['time'=>SORT_DESC])->asArray()->all();
        return [
            'list'=>$list,
            'row_count'=>$count,
            'pagination'=>$p
        ];
    }
}