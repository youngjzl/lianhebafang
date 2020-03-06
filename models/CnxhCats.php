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

class CnxhCats extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cnxh_cats}}';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id','cats_id', 'click_catsnum'], 'integer'],
        ];
    }

}