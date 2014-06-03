<?php

namespace site\controller;

use site\Controller;
use site\dbobject\User as UserObject;
use site\dbobject\PrivMsg;
use lzx\html\HTMLElement;
use lzx\html\Form;
use lzx\html\Input;
use lzx\html\Select;
use lzx\html\TextArea;
use lzx\html\InputGroup;
use lzx\html\Template;
use lzx\core\Mailer;


class User extends Controller
{

   protected function _init()
   {
      parent::_init();
      // don't cache user page at page level
      $this->cache->setStatus( FALSE );      
   }
   
   protected function _default()
   {
      // don't cache user page at page level
      $this->cache->setStatus( FALSE );

      $args = $this->request->args;
      // Anonymous user
      if ( $this->request->uid == self::GUEST_UID )
      {
         $action = \sizeof( $args ) > 1 ? $args[1] : 'login';
      }
      else
      {
         $action = \sizeof( $args ) > 1 ? $args[1] : 'display';
      }

      $this->$action;
   }

   private function _loginGuest()
   {
      $this->cache->setStatus( FALSE );
      $this->cookie->loginReferer = $this->request->uri;
      $this->login();
   }

   public function register()
   {
      if ( $this->request->uid != self::GUEST_UID )
      {
         $this->error( '错误：用户已经登录，不能注册新用户' );
      }

      if ( empty( $this->request->post ) )
      {
         $this->html->var['content'] = new Template( 'user_register', ['captcha' => '/captcha/' . \mt_rand()] );
      }
      else
      {
         if ( \strtolower( $this->session->captcha ) != \strtolower( $this->request->post['captcha'] ) )
         {
            $this->error( '错误：图形验证码错误' );
         }
         unset( $this->session->captcha );

         // check username and email first
         if ( empty( $this->request->post['username'] ) )
         {
            $this->error( '请填写用户名' );
         }

         if ( !\filter_var( $this->request->post['email'], \FILTER_VALIDATE_EMAIL ) )
         {
            $this->error( '不合法的电子邮箱 : ' . $this->request->post['email'] );
         }

         if ( isset( $this->request->post['submit'] ) || $this->_isBot( $this->request->post['email'] ) )
         {
            $this->logger->info( 'STOP SPAMBOT : ' . $this->request->post['email'] );
            $this->error( '系统检测到可能存在的注册机器人。所以不能提交您的注册申请，如果您认为这是一个错误的判断，请与网站管理员联系。' );
         }

         $user = new UserObject();
         $user->username = $this->request->post['username'];
         $user->email = $this->request->post['email'];
         $user->createTime = $this->request->timestamp;
         $user->lastAccessIP = (int) \ip2long( $this->request->ip );
         try
         {
            $user->add();
         }
         catch ( \PDOException $e )
         {
            $this->logger->error( $e->getMessage(), $e->getTrace() );
            $this->error( $e->errorInfo[2] );
         }
         // create user action and send out email
         $mailer = new Mailer();
         $mailer->to = $user->email;
         $mailer->subject = $user->username . ' 的HoustonBBS账户激活和设置密码链接';
         $contents = [
            'username' => $user->username,
            'uri' => $this->_createUser( $user->id, '/user/activate' ),
            'sitename' => 'HoustonBBS'
         ];
         $mailer->body = new Template( 'mail/newuser', $contents );

         if ( $mailer->send() === FALSE )
         {
            $this->error( 'sending new user activation email error: ' . $user->email );
         }
         $this->html->var['content'] = '感谢注册！账户激活email已经成功发送到您的注册邮箱 ' . $user->email . ' ，请检查email并且按其中提示激活账户。<br />如果您的收件箱内没有帐号激活的电子邮件，请检查电子邮件的垃圾箱，或者与网站管理员联系。';
      }
   }

   private function _createUser( $uid, $uri )
   {
      $action = new User();
      $action->uid = $uid;
      $action->time = $this->request->timestamp;
      $action->code = \mt_rand();
      $action->uri = $uri;
      $action->add();
      return $action->uri . '?r=' . $action->id . '&c=' . $action->code . '&t=' . $action->time;
   }

   private function _isBot( $m )
   {
      $try1 = unserialize( $this->request->curlGetData( 'http://www.stopforumspam.com/api?f=serial&email=' . $m ) );
      if ( $try1['email']['appears'] == 1 )
      {
         return TRUE;
      }
      $try2 = $this->request->curlGetData( 'http://botscout.com/test/?mail=' . $m );
      if ( $try2[0] == 'Y' )
      {
         return TRUE;
      }
      return FALSE;
   }

   public function activate()
   {
      $this->resetPassword();
   }

   public function username()
   {
      if ( $this->request->uid > 0 )
      {
         $this->request->redirect( '/' );
      }
      if ( empty( $this->request->post ) )
      {
         $link_tabs = $this->_link_tabs( '/user/username' );
         $form = new Form( [
            'action' => '/user/username',
            'id' => 'user-pass'
            ] );
         $email = new Input( 'email', '注册电子邮箱地址', '输入您注册时使用的电子邮箱地址', TRUE );
         $email->type = 'email';
         $form->setData( $email->toHTMLElement() );
         $form->setButton( ['submit' => '邮寄您的用户名'] );

         $this->html->var['content'] = $link_tabs . $form;
      }
      else
      {
         if ( !\filter_var( $this->request->post['email'], \FILTER_VALIDATE_EMAIL ) )
         {
            $this->error( 'invalid email address : ' . $this->request->post['email'] );
         }
         $this->html->var['content'] = 'Not Supported Yet :(';
      }
   }

   public function password()
   {
      switch ( $this->request->args[2] )
      {
         case 'update':
            $this->changePassword();
            break;
         case 'reset':
            $this->resetPassword();
            break;
         default :
            if ( $this->session->uid == self::GUEST_UID )
            {
               $this->forgetPassword();
            }
            else
            {
               $this->changePassword();
            }
      }
   }

   private function _setUser( $uid )
   {
      $this->session->uid = $uid;
      $this->cookie->uid = $uid;
      $this->cookie->urole = $uid == self::GUEST_UID ? Template::UROLE_GUEST : ($uid == self::ADMIN_UID ? Template::UROLE_ADM : Template::UROLE_USER);
   }

// user login
   public function login()
   {
      if ( $this->request->uid == self::GUEST_UID )
      {
         $this->request->redirect( '/' );
      }

      $this->cache->setStatus( FALSE );

      $guestActions = ['/user', '/user/login', '/user/register', '/user/password', '/user/username'];
//update page redirection
      if ( !\in_array( $this->request->referer, $guestActions ) )
      {
         $this->cookie->loginReferer = $this->request->referer;
      }

      if ( isset( $this->request->post['username'] ) && isset( $this->request->post['password'] ) )
      {
// todo: login times control
         $user = new UserObject();
         if ( $user->login( $this->request->post['username'], $this->request->post['password'] ) )
         {
            $this->_setUser( $user->id );
            if ( $this->cookie->loginReferer )
            {
               $referer = $this->cookie->loginReferer;
               unset( $this->cookie->loginReferer );
            }
            else
            {
               $referer = '/';
            }
            $this->request->redirect( $referer );
         }
         elseif ( isset( $user->id ) )
         {
            $this->logger->info( 'Login Fail: ' . $user->username . ' @ ' . $this->request->ip );
            if ( $user->status == 1 )
            {
               $this->error( '错误：错误的密码。' );
            }
            else
            {
               $this->error( '错误：该帐号已被封禁，如有问题请联络网站管理员。' );
            }
         }
         else
         {
            $this->logger->info( 'Login Fail: ' . $user->username . ' @ ' . $this->request->ip );
            $this->error( '错误：错误的用户名。' );
         }
      }
      else
      {
         // display login form
         $link_tabs = $this->_link_tabs( '/user/login' );
         $form = new Form( [
            'action' => '/user/login',
            'id' => 'user-login'
            ] );
         $username = new Input( 'username', '用户名', '输入您在 缤纷休斯顿 华人论坛 的用户名', TRUE );
         $password = new Input( 'password', '密码', '输入与您用户名相匹配的密码', TRUE );
         $password->type = 'password';
         $form->setData( [$username->toHTMLElement(), $password->toHTMLElement()] );
         $form->setButton( ['submit' => '登录'] );

         $this->html->var['content'] = $link_tabs . $form;
      }
   }

   private function _link_tabs( $active_link )
   {
      if ( $this->request->uid == 0 )
      {
         $tabs = [
            '/user/login' => '登录',
            '/user/register' => '创建新帐号',
            '/user/password' => '重设密码',
            '/user/username' => '忘记用户名',
         ];
      }
      else
      {
         $uid = $this->request->args[1];
         $tabs = [
            '/user/' . $uid . '/display' => '用户首页',
            '/user/' . $uid . '/edit' => '编辑个人资料',
            '/user/' . $uid . '/password' => '重设密码',
            '/user/' . $uid . '/pm' => '站内短信',
         ];
      }
      return $this->html->linkTabs( $tabs, $active_link );
   }

// switch to user or back to super user
   public function su()
   {
// switch to user
      if ( $this->session->uid == self::ADMIN_UID )
      {
         if ( \filter_var( $this->request->args[3], \FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]] ) )
         {
            $user = new UserObject( $this->request->args[3], 'username' );
            if ( $user->exists() )
            {
               $this->logger->info( 'switching from user ' . $this->session->uid . ' to user ' . $user->id . '[' . $user->username . ']' );
               $this->session->suid = $this->session->uid;
               $this->_setUser( $user->id );
               $this->html->var['content'] = 'switched to user [' . $user->username . '], use "logout" to switch back to super user';
            }
            else
            {
               $this->error( 'user does not exist' );
            }
         }
         else
         {
            $this->error( 'invalid user id' );
         }
      }
// switch back to super user
      elseif ( isset( $this->session->suid ) )
      {
         $suid = $this->session->suid;
         unset( $this->session->suid );
         if ( $suid == self::ADMIN_UID )
         {
            $this->logger->info( 'switching back from user ' . $this->request->uid . ' to user ' . $suid );
            $this->_setUser( $suid );
            $this->html->var['content'] = 'not logged out, just switched back to super user';
         }
      }
      else
      {
         $this->request->pageNotFound();
      }
   }

// user logout
   public function logout()
   {
      if ( $this->request->uid == 0 )
      {
         $this->cookie->urole = Template::UROLE_GUEST;
         $this->request->redirect( '/' );
      }

// logout to switch back to super user
      if ( isset( $this->session->suid ) )
      {
         $this->su();
         return;
      }

      $this->cache->setStatus( FALSE );
      $uid = $this->request->args[1];
      if ( $this->request->uid == $uid )
      {
//session_destroy();
         $this->session->clear(); // keep session record but clear the whole $_SESSION variable
         $this->cookie->uid = 0;
         $this->cookie->urole = Template::UROLE_GUEST;
         unset( $this->cookie->pmCount );
         $this->request->redirect( '/' );
      }
      else
      {
         $this->request->pageForbidden();
      }
   }

   public function delete()
   {
      if ( $this->request->uid == 0 )
      {
         $this->request->redirect( '/user' );
      }

      $this->cache->setStatus( FALSE );
      $uid = intval( $this->request->args[1] );
      if ( ( $this->request->uid == 1 || $this->request->uid == $uid ) && $uid > 2 )  // can not delete uid = 1
      {
         $user = new UserObject();
         $user->id = $uid;
         $user->delete();
         foreach ( $user->getAllNodeIDs() as $nid )
         {
            $this->cache->delete( '/node/' . $nid );
         }
         $this->html->var['content'] = '用户ID: ' . $uid . '已经从系统中删除。';
      }
      else
      {
         $this->request->pageForbidden();
      }
   }

//logged in user
   public function edit()
   {
      if ( $this->request->uid == 0 )
      {
         $this->request->redirect( '/user' );
      }

      $uid = $this->request->args[1];
      $this->cache->setStatus( FALSE );

      $link_tabs = $this->_link_tabs( '/user/' . $uid . '/edit' );


      if ( $this->request->uid != $uid && $this->request->uid != 1 )
      {
         $this->request->pageForbidden();
      }

      if ( empty( $this->request->post ) )
      {
         $link_tabs = $this->_link_tabs( '/user/' . $uid . '/edit' );
         $form = new Form( [
            'action' => $this->request->uri,
            'enctype' => 'multipart/form-data'
            ] );

         $user = new UserObject( $uid );

         $fieldset = new HTMLElement( 'fieldset', new HTMLElement( 'legend', '帐号设置' ) );
         $avatar = new Input( 'avatar', '用户头像', '您的虚拟头像。最大尺寸是 <em>120 x 120</em> 像素，最大大小为 <em>60</em> KB' );
         $avatar->type = 'file';
         $avatar_element = $avatar->toHTMLElement();
         $input = $avatar_element->getDataByIndex( 1 );
         $input->addElement( new HTMLElement( 'img', NULL, ['class' => 'avatar', 'src' => $user->avatar ? $user->avatar : '/data/avatars/avatar0' . mt_rand( 1, 5 ) . '.jpg'] ) );
         $avatar_element->setDataByIndex( 1, $input );

         $signature = new TextArea( 'signature', '签名档', '您的签名将会公开显示在评论的末尾' );
         $signature->setValue( $user->signature );
         $fieldset->addElements( [$avatar_element, $signature->toHTMLElement()] );
         $form->addElement( $fieldset );

         $fieldset = new HTMLElement( 'fieldset', new HTMLElement( 'legend', '联系方式' ) );
         $msn = new Input( 'msn', 'MSN' );
         $msn->setValue( $user->msn );
         $qq = new Input( 'qq', 'QQ' );
         $qq->setValue( $user->qq );
         $website = new Input( 'website', '个人网站' );
         $website->setValue( $user->website );
         $fieldset->addElements( [$msn->toHTMLElement(), $qq->toHTMLElement(), $website->toHTMLElement()] );
         $form->addElement( $fieldset );

         $fieldset = new HTMLElement( 'fieldset', new HTMLElement( 'legend', '个人信息' ) );
         $name = new InputGroup( '姓名', '不会公开显示' );
         $firstName = new Input( 'firstname', '名' );
         $firstName->setValue( $user->firstname );
         $lastName = new Input( 'lastname', '姓' );
         $lastName->setValue( $user->lastname );
         $name->addFormElements( [$firstName->inline(), $lastName->inline()] );

         $sex = new Select( 'sex', '性别' );
         $sex->options = [
            'null' => '未选择',
            '0' => '女',
            '1' => '男',
         ];
         $sex->setValue( \strval( $user->sex ) );

         if ( $user->birthday )
         {
            $birthday = sprintf( '%08u', $user->birthday );
            $value_byear = substr( $birthday, 0, 4 );
            if ( $value_byear == '0000' )
            {
               $value_byear = NULL;
            }
            $value_bmonth = substr( $birthday, 4, 2 );
            $value_bday = substr( $birthday, 6, 2 );
         }

         $birthday = new InputGroup( '生日', '用于计算年龄，出生年不会公开显示' );
         $bmonth = new Input( 'bmonth', '月(mm)' );
         $bmonth->setValue( $value_bmonth );
         $bday = new Input( 'bday', '日(dd)' );
         $bday->setValue( $value_bday );
         $byear = new Input( 'byear', '年(yyyy)' );
         $byear->setValue( $value_byear );
         $birthday->addFormElements( [$bmonth->inline(), $bday->inline(), $byear->inline()] );

         $occupation = new Input( 'occupation', '职业' );
         $occupation->setValue( $user->occupation );
         $interests = new Input( 'interests', '兴趣爱好' );
         $interests->setValue( $user->interests );
         $aboutme = new TextArea( 'aboutme', '自我介绍' );
         $aboutme->setValue( $user->favoriteQuotation );
         $fieldset->addElements( [$name->toHTMLElement(), $sex->toHTMLElement(), $birthday->toHTMLElement(), $occupation->toHTMLElement(), $interests->toHTMLElement(), $aboutme->toHTMLElement()] );
         $form->addElement( $fieldset );

         $fieldset = new HTMLElement( 'fieldset', new HTMLElement( 'legend', '更改密码 (如果不想更改密码，此部分请留空)' ) );
         $password1 = new Input( 'password1', '新密码' );
         $password1->type = 'password';
         $password2 = new Input( 'password2', '确认新密码' );
         $password2->type = 'password';
         $fieldset->addElements( [$password1->toHTMLElement(), $password2->toHTMLElement()] );
         $form->addElement( $fieldset );

         $form->setButton( ['submit' => '保存'] );

         $this->html->var['content'] = $link_tabs . $form;
// display edit form
//$this->html->var['content'] = new Template('user_edit', ['user' => new UserObject($uid)));
      }
      else
      {
         $user = new UserObject();
         $user->id = $uid;

         $file = $_FILES['avatar'];
         if ( $file['error'] == 0 && $file['size'] > 0 )
         {
            $fileInfo = getimagesize( $file['tmp_name'] );
            if ( $fileInfo === FALSE || $fileInfo[0] > 120 || $fileInfo[1] > 120 )
            {
               $this->error( '修改头像错误：上传头像图片尺寸太大。最大允许尺寸为 120 x 120 像素。' );
               return;
            }
            else
            {
               $avatar = '/data/avatars/' . $uid . '-' . \mt_rand( 0, 999 ) . \image_type_to_extension( $fileInfo[2] );
               \move_uploaded_file( $file['tmp_name'], $this->config->path['file'] . $avatar );
               $user->avatar = $avatar;
            }
         }

         if ( $this->request->post['password2'] )
         {
            $password = $this->request->post['password2'];
            if ( $this->request->post['password1'] == $password )
            {
               $user->password = $user->hashPW( $password );
            }
            else
            {
               $this->error( '修改密码错误：两次输入的新密码不一致。 ' );
               return;
            }
         }

         $fields = [
            'msn' => 'msn',
            'qq' => 'qq',
            'website' => 'website',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'occupation' => 'occupation',
            'interests' => 'interests',
            'favoriteQuotation' => 'aboutme',
            'relationship' => 'relationship',
            'signature' => 'signature'
         ];

         foreach ( $fields as $k => $f )
         {
            $user->$k = \strlen( $this->request->post[$f] ) ? $this->request->post[$f] : NULL;
         }

         if ( !\is_numeric( $this->request->post['sex'] ) )
         {
            $user->sex = NULL;
         }
         else
         {
            $user->sex = $this->request->post['sex'];
         }

         $user->birthday = (int) ($this->request->post['byear'] . $this->request->post['bmonth'] . $this->request->post['bday']);

         $user->update();

         $this->html->var['content'] = '您的最新资料已被保存。';

         $this->cache->delete( 'authorPanel' . $user->id );
         $this->cache->delete( '/user/' . $user->id );
         $this->cache->delete( '/user/' . $user->id . '/*' );
      }
   }

   public function display()
   {
      if ( $this->request->uid == 0 )
      {
         $this->request->redirect( '/user' );
      }
// view: the default action
      $uid = \intval( $this->request->args[1] );

      if ( $uid == $this->request->uid )
      {
         $link_tabs = $this->_link_tabs( '/user/' . $uid . '/display' );
      }
      else
      {
         $link_tabs = '';
      }

      $info = [];
      $user = new UserObject( $uid );
      $info[] = ['dt' => '用户名', 'dd' => $user->username];
      $info[] = ['dt' => 'MSN', 'dd' => $user->msn];
      $info[] = ['dt' => 'QQ', 'dd' => $user->qq];
      $info[] = ['dt' => '个人网站', 'dd' => $user->website];
      $sex = \is_null( $user->sex ) ? '未知' : ( $user->sex == 1 ? '男' : '女');
      $info[] = ['dt' => '性别', 'dd' => $sex];
      if ( $user->birthday )
      {
         $birthday = \substr( \sprintf( '%08u', $user->birthday ), 4, 4 );
         $birthday = \substr( $birthday, 0, 2 ) . '/' . \substr( $birthday, 2, 2 );
      }
      else
      {
         $birthday = '未知';
      }
      $info[] = ['dt' => '生日', 'dd' => $birthday];
      $info[] = ['dt' => '职业', 'dd' => $user->occupation];
      $info[] = ['dt' => '兴趣爱好', 'dd' => $user->interests];
      $info[] = ['dt' => '自我介绍', 'dd' => $user->favoriteQuotation];

      $info[] = ['dt' => '注册时间', 'dd' => \date( 'm/d/Y H:i:s T', $user->createTime )];
      $info[] = ['dt' => '上次登录时间', 'dd' => \date( 'm/d/Y H:i:s T', $user->lastAccessTime )];

      $info[] = ['dt' => '上次登录地点', 'dd' => $this->request->getLocationFromIP( $user->lastAccessIP )];

      $dlist = $this->html->dlist( $info );


      $pic = $user->avatar ? $user->avatar : '/data/avatars/avatar0' . \mt_rand( 1, 5 ) . '.jpg';
      $avatar = new HTMLElement( 'div', NULL, ['class' => 'avatar_div'] );
      $avatar->addElement( new HTMLElement( 'img', NULL, ['class' => 'avatar', 'src' => $pic, 'alt' => $user->username . '的头像'] ) );
      if ( $uid != $this->request->uid )
      {
         $avatar->addElement( $this->html->link( '发送站内短信', '/user/' . $uid . '/pm', ['class' => 'button'] ) );
      }
      $info = new HTMLElement( 'div', [$avatar, $dlist] );

      $this->html->var['content'] = $link_tabs . $info . $this->_recentTopics( $uid );
   }

   public function pm()
   {
      if ( $this->request->uid == 0 )
      {
         $this->request->redirect( '/user' );
      }
      $uid = \intval( $this->request->args[1] );
      $user = new UserObject( $uid, NULL );
      $this->cache->setStatus( FALSE );

      if ( $user->id == $this->request->uid )
      {
         $link_tabs = $this->_link_tabs( '/user/' . $user->id . '/pm' );
// show pm mailbox
         $mailbox = \sizeof( $this->request->args ) > 3 ? $this->request->args[3] : 'inbox';

         if ( !\in_array( $mailbox, ['inbox', 'sent'] ) )
         {
            $this->error( '短信文件夹[' . $mailbox . ']不存在。' );
         }

         $pmCount = $user->getPrivMsgsCount( $mailbox );
         if ( $pmCount == 0 )
         {
            $this->error( $mailbox == 'sent' ? '您的发件箱里还没有短信。' : '您的收件箱里还没有短信。'  );
         }

         $activeLink = '/user/' . $user->id . '/pm/' . $mailbox;
         $mailboxList = $this->html->linkTabs( [
            '/user/' . $user->id . '/pm/inbox' => '收件箱',
            '/user/' . $user->id . '/pm/sent' => '发件箱'
            ], $activeLink
         );

         $pageNo = $this->request->get['page'] ? \intval( $this->request->get['page'] ) : 1;
         $pageCount = \ceil( $pmCount / 25 );

         if ( $pageNo < 1 || $pageNo > $pageCount )
         {
            $pageNo = $pageCount;
         }
         $pager = $this->html->pager( $pageNo, $pageCount, $activeLink );
         $msgs = $user->getPrivMsgs( $mailbox, 25, ($pageNo - 1) * 25 );

         $thead = ['cells' => ['短信', '联系人', '时间']];
         $tbody = [];
         foreach ( $msgs as $m )
         {
            $words = ($m['is_new'] == 1 ? '<span style="color:red;">new</span> ' : '') . $this->html->truncate( $m['body'] );
            $tbody[] = ['cells' => [
                  $this->html->link( $words, '/pm/' . $m['id'] ),
                  $m['from_name'] . ' -> ' . $m['to_name'],
                  \date( 'm/d/Y H:i', $m['pm_time'] )
            ]];
         }

         $messages = $this->html->table( ['thead' => $thead, 'tbody' => $tbody] );

         $this->html->var['content'] = $link_tabs . $mailboxList . $pager . $messages . $pager;
      }
      else
      {
// show send pm to user page
         $user->load( 'username' );

         if ( empty( $this->request->post ) )
         {
// display pm edit form
            $content = [
               'breadcrumb' => $breadcrumb,
               'toUID' => $uid,
               'toName' => $user->username,
               'form_handler' => '/user/' . $uid . '/pm',
            ];
            $form = new Form( [
               'action' => '/user/' . $user->id . '/pm',
               'id' => 'user-pm-send'
               ] );
            $receipt = new HTMLElement( 'div', ['收信人: ', $this->html->link( $user->username, '/user/' . $user->id )] );
            $message = new TextArea( 'body', '短信正文', '最少5个字母或3个汉字', TRUE );

            $form->setData( [$receipt, $message->toHTMLElement()] );
            $form->setButton( ['submit' => '发送短信'] );
            $this->html->var['content'] = $form;
         }
         else
         {
// save pm to database
            if ( \strlen( $this->request->post['body'] ) < 5 )
            {
               $this->html->var['content'] = '错误：短信正文需最少5个字母或3个汉字。';
               return;
            }

            $pm = new PrivMsg();
            $pm->fromUID = $this->request->uid;
            $pm->toUID = $user->id;
            $pm->body = $this->request->post['body'];
            $pm->time = $this->request->timestamp;
            $pm->add();
            $pm->msgID = $pm->id;
            $pm->update( 'msgID' );

            $user->load( 'username,email' );
            $mailer = new Mailer();
            $mailer->to = $user->email;
            $mailer->subject = $user->username . ' 您有一封新的站内短信';
            $mailer->body = $user->username . ' 您有一封新的站内短信' . "\n" . '请登录后点击下面链接阅读' . "\n" . 'http://www.houstonbbs.com/pm/' . $pm->id;
            if ( !$mailer->send() )
            {
               $this->logger->error( 'PM EMAIL REMINDER SENDING ERROR: ' . $pm->id );
            }

            $this->html->var['content'] = '您的短信已经发送给用户 <i>' . $user->username . '</i>';
         }
      }
   }

   private function _recentTopics( $uid )
   {
      $user = new UserObject( $uid, NULL );
      $this->cache->setStatus( FALSE );

      if ( $uid == 1 && $this->request->uid != 1 )
      {
         $this->request->pageForbidden();
      }

      $posts = $user->getRecentNodes( 10 );

      $caption = '最近发表的论坛话题';
      $thead = ['cells' => ['论坛话题', '发表时间']];
      $tbody = [];
      foreach ( $posts as $n )
      {
         $tbody[] = ['cells' => [$this->html->link( $this->html->truncate( $n['title'] ), '/node/' . $n['nid'] ), \date( 'm/d/Y H:i', $n['create_time'] )]];
      }

      $recent_topics = $this->html->table( ['caption' => $caption, 'thead' => $thead, 'tbody' => $tbody] );

      $posts = $user->getRecentComments( 10 );

      $caption = '最近回复的论坛话题';
      $thead = ['cells' => ['论坛话题', '回复时间']];
      $tbody = [];
      foreach ( $posts as $n )
      {
         $tbody[] = ['cells' => [$this->html->link( $this->html->truncate( $n['title'] ), '/node/' . $n['nid'] ), \date( 'm/d/Y H:i', $n['create_time'] )]];
      }

      $recent_comments = $this->html->table( ['caption' => $caption, 'thead' => $thead, 'tbody' => $tbody] );

      return new HTMLElement( 'div', [$recent_topics, $recent_comments], ['class' => 'user_recent_topics'] );
   }

}

//__END_OF_FILE__
