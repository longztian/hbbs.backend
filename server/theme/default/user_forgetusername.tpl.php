<ul class="tabs">
   <li><a href="/user/login">登录</a></li>
   <li><a href="/user/register">创建新帐号</a></li>
   <li><a href="/user/password">重设密码</a></li>
   <li class="active"><a href="/user/username">忘记用户名</a></li>
</ul>
<form accept-charset="UTF-8" autocomplete="off" method="post" action="/user/username" id="user-pass">
   <div class="form_element">
      <div class="element_label"><label>注册电子邮箱地址</label><span class="element_required"> * </span><span class="element_help" title="输入您注册时使用的电子邮箱地址"> ? </span></div>
      <div class="element_input"><input size="22" name="email" type="email" required="required"></div>
   </div>
   <div class="form_element">
      <div class="element_input"><button type="submit">邮寄您的用户名</button></div>
   </div>
</form>