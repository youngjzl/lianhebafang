<?php

namespace app\models;

use app\models\common\admin\log\CommonActionLog;
use Yii;

/**
 * This is the model class for table "{{%goods_pic}}".
 *
 * @property integer $id
 * @property integer $goods_id
 * @property string $pic_url
 * @property integer $is_delete
 */
class GoodsPic extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_pic}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id', 'is_delete'], 'integer'],
            [['pic_url'], 'required'],
            [['pic_url'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_id' => 'Goods ID',
            'pic_url' => 'Pic Url',
            'is_delete' => 'Is Delete',
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        $data = $insert ? json_encode($this->attributes) : json_encode($changedAttributes);
        CommonActionLog::storeActionLog('', $insert, $this->is_delete, $data, $this->id);
    }
    public function savePic()
      {
          if ($this->validate()) {
              if ($this->save()) {
                  return [
                      'code' => 0,
                      'msg' => '成功'
                  ];
              } else {
                  return [
                      'code' => 1,
                      'msg' => '失败'
                  ];
              }
          } else {
              return (new Model())->getErrorResponse($this);
          }
      }
}
