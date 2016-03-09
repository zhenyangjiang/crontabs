## 安装

环境要求：PHP >= 5.3.0

1. 安装包文件
```shell
composer require "ulan/solusvm:1.0.*"
```

2. 添加 `ServiceProvider` 到您项目 `config/app.php` 中的 `providers` 部分
```php
'ULan\SolusVM\SolusvmServiceProvider',
```

3. 创建配置文件
```shell
php artisan vendor:publish
```

4. 请修改应用根目录下的 `config/soulsvm.php` 中对应的项即可.

5. （可选）添加外观到 `config/app.php` 中的 `aliases` 部分
```php
'SolusVM' => 'ULan\SolusVM\SolusvmFacade',
```
