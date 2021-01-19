# 命令记录日志


### 创建生成安全访问令牌
> 创建生成安全访问令牌时所需的加密密钥，同时，这条命令也会创建用于生成访问令牌的「个人访问」客户端和「密码授权」客户端
```
php artisan passport:install
```

### 创建模型
```
php artisan make:model Models/PlatformAdmin -m
php artisan make:model Models/Member -m
php artisan make:model Models/PermissionMenu -m
php artisan make:model Models/PlatformRole -m
php artisan make:model Models/PlatformRoleMenu -m

```

### 表单验证
Platform
```
php artisan make:request Platform/LoginRequest
php artisan make:request Platform/PermissionMenuRequest
php artisan make:request Platform/RoleRequest
php artisan make:request Platform/AdminRequest
```
Seller
```
php artisan make:request Seller/GoodsRequest

```
shop
```
php artisan make:request Shop/UserAccountRequest

```

### migrate
```
php artisan make:migration create_oauth_access_token_providers_table --create=oauth_access_token_providers
```

```
php artisan make:seeder PlatformAdminSeeder
php artisan db:seed --class=PlatformAdminSeeder

php artisan make:seeder MemberSeeder
php artisan db:seed --class=MemberSeeder
```

### 自动加载命令
```
composer dump-auto
```

### 自动加载正确的事件类  
```
php artisan event:generate
```

###
```
php artisan make:middleware PassportCustomProvider
php artisan make:middleware PassportCustomProviderAccessToken
```

### 生成任务类
```
php artisan make:job CloseTrade
```

### Test
```
php artisan make:test Platform/V1/LoginTest
php artisan make:test Platform/V1/PermissionMenuTest
php artisan make:test Platform/V1/RoleTest
php artisan make:test Platform/V1/AdminTest
```
