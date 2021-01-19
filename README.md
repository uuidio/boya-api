# shopem base 安装说明

### clone project
`git clone https://git.dev.tencent.com/mocode/shopem_base.git`

### 安装依赖
`composer install`

### copy .env
`cp .env.example .env`

### 安装 Laravel 之后下一件应该做的事就是将应用程序的密钥设置为随机字符串
`php artisan key:generate`

### 配置 数据库 账号密码
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=数据库名
DB_USERNAME=账号
DB_PASSWORD=密码
```

### 运行数据库迁移文件
`php artisan migrate`

### 运行 passport:install 命令来创建生成安全访问令牌时所需的加密密钥
`php artisan passport:install`

### 配置.env passport 认证密钥
```
OAUTH_GRANT_TYPE=password
OAUTH_CLIENT_ID=2
OAUTH_CLIENT_SECRET=1XHTUfLbkugZpIiesobAIn5BOlvxsRawhc1ctAbC
OAUTH_SCOPE=*
```