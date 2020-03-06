<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/12 0012
 * Time: 14:46
 */

namespace app\models;

use app\models\common\admin\log\CommonActionLog;
use Yii;

class CnxhKeywords extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cnxh_data_keywords}}';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [[ 'history_keywords'], 'string'],
        ];
    }

}