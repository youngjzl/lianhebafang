<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/14 0014
 * Time: 14:30
 */

namespace app\modules\api\models;


class CnxhCatsForm extends ApiModel
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

    public function search(){

    }

}