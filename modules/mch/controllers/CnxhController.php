<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/15 0015
 * Time: 11:39
 */

namespace app\modules\mch\controllers;

use app\models\CnxhCats;
use app\models\User;
use app\models\Cat;
use yii\data\Pagination;


class CnxhController extends Controller
{
    public function actionCnxhdatalist(){
        $query = CnxhCats::find()->alias('ct')->where(['ct.store_id' => $this->store->id]);
        $count = $query->count();

        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => 20]);
        $list = $query
            ->leftJoin(['u' => User::tableName()], 'ct.user_id=u.id')
            ->leftJoin(['c' => Cat::tableName()], 'ct.cats_id=c.id')
            ->select('ct.id as user_id,u.nickname,c.name as card_name,ct.time')
            ->orderBy('ct.time DESC')->limit($pagination->limit)->offset($pagination->offset)->asArray()->all();

        return $this->render('cnxhdatalist', [
            'list'       => $list,
            'pagination' => $list['pagination'],
            'row_count'  => $list['row_count'],
        ]);
    }

    public function actionCnxhgoodslist()
    {
        
        return $this->render('cnxhdatalist');
    }
}