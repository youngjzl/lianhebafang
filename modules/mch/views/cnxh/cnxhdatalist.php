<?php
defined('YII_ENV') or exit('Access Denied');
use yii\widgets\LinkPager;

$urlManager = Yii::$app->urlManager;
$this->title = '数据列表';
$this->params['active_nav_group'] = 12;
?>
<style>
    table {
        table-layout: fixed;
    }

    th {
        text-align: center;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    td {
        text-align: center;
        line-height: 30px;
    }

    .ellipsis {
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    td.nowrap {
        white-space: nowrap;
        overflow: hidden;
    }

    .goods-pic {
        margin: 0 auto;
        width: 3rem;
        height: 3rem;
        background-color: #ddd;
        background-size: cover;
        background-position: center;
    }
</style>

<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <div class="mb-3 clearfix">
            <div class="float-left">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        分类点击量
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton"
                         style="max-height: 200px;overflow-y: auto">
                        <a href="<?= $urlManager->createUrl(['mch/cnxh/cnxhgoodslist']) ?>"
                           class="btn btn-secondary batch dropdown-item"
                           data-content="商品点击量">商品点击量</a>
                        <a href="javascript:void(0)"
                           class="btn btn-warning batch dropdown-item"
                           data-url="<?= $urlManager->createUrl(['mch/miaosha/goods/batch']) ?>" data-content="商品收藏量"
                           data-type="1">商品收藏量</a>
                        <a href="javascript:void(0)" class="btn btn-danger batch dropdown-item"
                           data-url="<?= $urlManager->createUrl(['mch/miaosha/goods/batch']) ?>" data-content="商品下单量"
                           data-type="2">商品下单量</a>
                        <a href="javascript:void(0)" class="btn btn-danger batch dropdown-item"
                           data-url="<?= $urlManager->createUrl(['mch/miaosha/goods/batch']) ?>" data-content="关键词搜索量"
                           data-type="3">关键词搜索量</a>
                    </div>
                </div>
            </div>
            <div class="float-right">
                <form method="get">
                    <?php $_s = ['keyword'] ?>
                    <?php foreach ($_GET as $_gi => $_gv) :
                        if (in_array($_gi, $_s)) {
                            continue;
                        } ?>
                        <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                    <?php endforeach; ?>

                    <div class="input-group">
                        <input class="form-control" placeholder="用户名" name="keyword"
                               value="<?= isset($_GET['keyword']) ? trim($_GET['keyword']) : null ?>">
                        <span class="input-group-btn">
                    <button class="btn btn-primary">搜索</button>
                </span>
                    </div>
                </form>
            </div>
        </div>
        <table class="table table-bordered bg-white table-hover">
            <thead>
            <tr>
                <th style="text-align: center">
                    <label class="checkbox-label" style="margin-right: 0px;">
                        <input type="checkbox" class="goods-all">
                        <span class="label-icon"></span>
                    </label>
                </th>
                <th><span class="label-text">ID</span></th>
                <th>用户名</th>
                <th>分类名</th>
                <th>点击时间</th>
            </tr>
            </thead>
            <col style="width: 3%">
            <col style="width: 5%">
            <col style="width: 15%">
            <col style="width: 18%">
            <col style="width: 10%">
            <tbody>
            <?php foreach ($list as $index => $goods) : ?>
                <tr>
                    <td class="nowrap" style="text-align: center">
                        <label class="checkbox-label" style="margin-right: 0px;">
                            <input data-num="<?= $goods['user_id']; ?>" type="checkbox"
                                   class="goods-one"
                                   value="<?= $goods['user_id']; ?>">
                            <span class="label-icon"></span>
                        </label>
                    </td>
                    <td data-toggle="tooltip"
                        data-placement="top" title="<?= $goods['user_id']; ?>">
                        <span class="label-text"><?= $goods['user_id']; ?></span></td>
                    <td class="text-left ellipsis" data-toggle="tooltip"
                        data-placement="top" title="<?= $goods['nickname']; ?>">
                        <?= $goods['nickname']; ?></td>
                    <td class="text-left ellipsis" data-toggle="tooltip"
                        data-placement="top" title="<?= $goods['card_name']; ?>">
                        <?= $goods['card_name']; ?></td>
                    <td class="text-left ellipsis" data-toggle="tooltip"
                        data-placement="top" title="<?= date('Y-m-d H:i:s', $goods['time']); ?>">
                        <?= date('Y-m-d H:i:s', $goods['time']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
