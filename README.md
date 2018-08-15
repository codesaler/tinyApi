# tinyApi

极其精简的 Api 脚手架。

一些特性：

- 使用 composer
- namespace 默认 `tiny\api\`
- DB：[catfan/Medoo](https://github.com/catfan/Medoo)
- Redis：[nrk/predis](https://github.com/nrk/predis)
- Log：[Seldaek/monolog](https://github.com/Seldaek/monolog)
- 没有容器/注解/ORM，不支持 RESTful/自定义路由
- 请求参数强制使用 Json（即使是 GET 参数）
- 强制使用 AES 加密
- 强制进行 timestamp hash 头校验

### introduce

没什么好介绍的。

![](https://raw.githubusercontent.com/LemonLone/tinyApi/master/screenshot.png)