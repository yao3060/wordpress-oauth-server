# 静默登录测试案例

这个测试案例演示了如何使用iframe实现OAuth2的静默登录功能。

## 文件说明

- `client-pkce-1-iframe.html` - 主测试页面，包含静默登录功能
- `client-pkce-1-iframe-silent.html` - iframe回调页面，处理OAuth授权回调
- `js/PKCEClient.js` - 已修改支持prompt参数的PKCE客户端

## 功能特性

### 静默登录流程

1. **测试静默登录** - 点击"Test Silent Login"按钮开始测试
2. **iframe授权** - 在隐藏的iframe中加载OAuth授权URL（带`prompt=none`参数）
3. **自动处理** - 如果用户已登录，会自动返回授权码；如果未登录，会返回错误
4. **postMessage通信** - iframe通过postMessage将结果发送给父页面
5. **令牌交换** - 父页面接收到授权码后自动交换访问令牌
6. **用户信息** - 获取并显示用户信息

### 界面功能

- **状态指示器** - 实时显示静默登录状态（等待/成功/失败）
- **iframe可见性切换** - 可以显示/隐藏iframe来观察授权过程
- **结果展示** - 详细显示登录结果和用户信息
- **超时处理** - 10秒超时保护，避免无限等待

## 使用方法

### 1. 配置OAuth客户端

在WordPress OAuth2服务器中配置客户端：
- 设置为**公共客户端**（支持PKCE）
- 添加重定向URI：`http://localhost:8080/test/client-pkce-1-iframe-silent.html`

### 2. 配置测试页面

1. 填写WordPress基础URL
2. 填写客户端ID
3. 确保重定向URI正确

### 3. 测试静默登录

1. 首先使用常规方式登录一次（建立会话）
2. 点击"Test Silent Login"按钮
3. 观察状态指示器和结果显示

## 技术实现

### 关键参数

- `prompt=none` - 告诉OAuth服务器不要显示登录界面，如果用户未登录则直接返回错误
- `postMessage` - 用于iframe与父页面的跨域通信
- 超时机制 - 防止静默登录无限等待

### 安全考虑

- **Origin验证** - 验证postMessage的来源
- **State参数** - 防止CSRF攻击
- **PKCE** - 防止授权码拦截攻击

### 错误处理

常见错误类型：
- `login_required` - 用户未登录，需要交互式登录
- `consent_required` - 需要用户同意授权
- `interaction_required` - 需要用户交互
- 超时错误 - 网络或服务器响应超时

## 注意事项

1. **浏览器兼容性** - 需要支持postMessage和iframe的现代浏览器
2. **同源策略** - OAuth服务器需要正确配置CORS或使用同域
3. **会话状态** - 静默登录依赖于现有的用户会话
4. **隐私模式** - 在浏览器隐私模式下可能无法正常工作

## 调试技巧

1. 打开浏览器开发者工具查看控制台日志
2. 使用"Toggle iframe Visibility"查看iframe内容
3. 检查Network标签页查看OAuth请求和响应
4. 验证localStorage中的令牌存储情况
