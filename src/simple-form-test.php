<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST 请求成功！</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>简单表单测试</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .button { padding: 10px 20px; margin: 10px; cursor: pointer; }
        .button-primary { background: #0073aa; color: white; border: none; }
    </style>
</head>
<body>
    <h1>简单表单测试</h1>
    
    <form method="POST" action="">
        <input type="hidden" name="test" value="123">
        <input type="submit" name="approve" value="测试提交" class="button button-primary">
    </form>
    
    <script>
    console.log('页面加载完成');
    
    document.querySelector('form').addEventListener('submit', function(e) {
        console.log('表单提交事件触发');
        console.log('Action:', this.action);
        console.log('Method:', this.method);
        return true;
    });
    
    document.querySelector('input[name="approve"]').addEventListener('click', function(e) {
        console.log('按钮点击事件触发');
        return true;
    });
    </script>
</body>
</html>
