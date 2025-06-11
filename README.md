# 说明

Crmeb单商户系统-微信同城配送&闪送

## 安装

`composer require ledc/crmeb-intra-city`

## 使用说明

1. 安装完之后，请执行以下命令，安装插件的数据库迁移文件 `php think install:migrate:crmeb-intra-city`

2. 执行数据库迁移 `php think migrate:run`

## 订单状态回调地址

1. 闪送 `https://您的域名/intra_city_callback/shansong`
2. 微信 `https://您的域名/intra_city_callback/wechat`

## 修改过的文件

1. `\app\adminapi\controller\v1\order\StoreOrder::lst`
2. `\app\api\controller\v1\admin\StoreOrderController::lst`
3. `\app\api\controller\v1\order\StoreOrderController::lst`
4. `\app\dao\order\StoreOrderDao::search`
5. `\app\kefuapi\controller\Order::getUserOrderList`
6. `\app\model\order\StoreOrder`

## 捐赠

![reward](reward.png)