# 编码规范

## 代码风格规范
代码风格 必须 严格遵循 PSR-2 规范。(查看：PSR-2: Coding Style Guide.md 文档)

### 项目目录结构
    ├── app # 应用程序核心代码
    │   ├── Console # 自定义的Artisan命令
    │   ├── Exceptions # 应用异常处理
    │   ├── Helpers # 助手函数
    │   ├── Http # 控制器、中间件以及表单请求目录
    │   │   ├── Controllers # 控制器
    │   │   │   ├── Platform # 平台控制器
    │   │   │   │   └── V1 # 版本
    │   │   │   ├── Seller # 商家管理控制器
    │   │   │   │   └── V1 # 版本
    │   │   │   └── Shop # 商城控制器
    │   │   │       └── V1 # 版本
    │   │   ├── Middleware # 中间件
    │   │   └── Requests # 表单请求
    │   ├── Models # 模型
    │   ├── Providers # 服务提供者 
    │   ├── Repositories # 数据库逻辑抽象层
    │   ├── Services # 应用逻辑抽象层
    │   └── Traits # 复用类库
    ├── bootstrap # 框架启动和自动加载设置
    ├── config # 应用程序的配置文件
    ├── database # 数据迁移及填充文件
    ├── guide # 文档目录
    ├── public # 项目的HTTP入口和前端资源文件
    ├── routes # 路由
    │   ├── Platform # 平台路由
    │   ├── Seller # 商家路由
    │   └── Shop # 商城路由
    ├── storage # 缓存日志目录
    └── tests # 测试目录
    
### Repository
> app/Repository 抽象数据层 [参考：](https://oomusou.io/laravel/repository/)
若将数据库逻辑都写在model，会造成model的肥大而难以维护，基于SOLID原则，我们应该使用Repository模式辅助model，将相关的数据库逻辑封装在不同的repository，方便中大型项目的维护LID原则，我们应该使用Repository模式辅助model，将相关的数据库逻辑封装在不同的repository，方便中大型项目的维护

### Services
> app/Services 应用逻辑抽象层 [参考：](https://oomusou.io/laravel/service/)
若将应用逻辑都写在controller，会造成controller肥大而难以维护，基于SOLID原则，我们应该使用Service模式辅助controller，将相关的应用逻辑封装在不同的service，方便中大型项目的维护

### 路由
> 项目使用 `RESTFul` 风格路由 统一查询数据使用get 提交、保存、更新使用post
路由URL示例：
get https://o2o.sdjchina.com/shop/v1/index/test
get https://o2o.sdjchina.com/seller/v1/index/test
post 需要在头部headers添加 Accept:application/json

### 数据库迁移文件规范
示例
```
Schema::create('articles', function (Blueprint $table) {
    $table->increments('id');
    $table->string('title', 200)->comment('标题');
    $table->unsignedInteger('cat_id')->index()->comment('分类id');
    $table->string('article_url', 100)->nullable()->default(null)->comment('相册名称');
    $table->unsignedTinyInteger('is_show')->nullable()->default(1)->comment('是否显示，0为否，1为是，默认为1');
    $table->text('content')->comment('内容');
    $table->unsignedTinyInteger('listorder')->nullable()->default(0)->comment('列表顺序');
    $table->timestamps();
});

DB::statement("ALTER TABLE `em_articles` comment'文章表'");
```
> 一定要给字段及表添加注释

### 使用统一的开发工具
工具的统一，是为了方便工作流的统一，还有工具使用经验的传承。

团队里的成员，经常需要互相使用对方电脑来讨论问题、查看某段代码、Debug 某个功能，工具统一起来后，你会发现，虽然是别人的电脑，工具使用起来是熟悉的，用起来就跟自己的电脑一样顺手，自然的工作效率就会提高。

- IDE: phpstorm
- 浏览器：Chrome
- MySQL 数据库查询工具: Navicat
- php开发环境：Docker [dnmp](https://github.com/kucode/dnmp)

### PHP注释规范
1. php头文件注释
```
/**
 * @Filename ${FILE_NAME}
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          ${USER}
 */
```
phpstorm 配置文件注释
PhpStorm > setting > Editor > File and Code Templates 中点击Includes 下的`PHP File Header` 将上述代码复制替换

2. php函数注释
```
/**
@Author ${USER}
${PARAM_DOC}
#if (${TYPE_HINT} != "void") * @return ${TYPE_HINT}
#end
${THROWS_DOC}
*/
```

phpstorm 配置文件注释
PhpStorm > setting > Editor > File and Code Templates 中点击Includes 下的`PHP Function Doc Comment` 将上述代码复制替换