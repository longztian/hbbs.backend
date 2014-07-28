<?php

namespace site\controller\single;

use site\controller\Single;
use site\dbobject\FFAttendee;
use site\dbobject\FFComment;
use lzx\html\Template;
use lzx\core\Mailer;

/**
 * @property \lzx\db\DB $db database object
 */
class AJAXCtrler extends Single
{

   // attend activity
   public function run()
   {
      // uri = /single/ajax/attend
      if ( !$this->args )
      {
         $this->error( '错误: 错误的请求' );
      }
      $method = '_' . $this->args[ 0 ];
      if ( !\method_exists( $this, $method ) )
      {
         $this->error( '错误: 错误的请求' );
      }
      $this->$method();
   }

   protected function _attend()
   {
      if ( \file_exists( $this->config->path[ 'file' ] . '/single.msg' ) )
      {
         $this->error( '错误: ' . \file_get_contents( $this->config->path[ 'file' ] . '/single.msg' ) );
      }

      if ( empty( $this->request->post[ 'name' ] ) || \strlen( $this->request->post[ 'sex' ] ) < 1 || empty( $this->request->post[ 'age' ] ) || empty( $this->request->post[ 'email' ] ) )
      {
         $this->error( '错误: 带星号(*)选项为必填选项' );
      }

      $a = \array_pop( $this->db->query( 'CALL get_latest_single_activity()' ) );
      if ( $this->request->post[ 'aid' ] != $a[ 'id' ] )
      {
         $this->error( '错误: 错误的活动' );
      }

      $attendee = new FFAttendee();

      $attendee->aid = $a[ 'id' ];
      $attendee->name = $this->request->post[ 'name' ];
      $attendee->sex = $this->request->post[ 'sex' ];
      $attendee->age = $this->request->post[ 'age' ];
      $attendee->email = $this->request->post[ 'email' ];
      $attendee->phone = $this->request->post[ 'phone' ];

      if ( $this->request->post[ 'comment' ] )
      {

         $comment = new FFComment();
         $comment->aid = $a[ 'id' ];
         $comment->name = $this->request->post[ 'anonymous' ] ? $this->request->ip : $this->request->post[ 'name' ];
         $comment->body = $this->request->post[ 'comment' ];
         $comment->time = $this->request->timestamp;
         $comment->add();
         $attendee->cid = $comment->id;

         $comments = (string) $this->_getComments( $a[ 'id' ] );
      }

      $attendee->time = $this->request->timestamp;
      $attendee->add();

      $chart = (string) $this->_getChart( $a );

      $mailer = new Mailer();

      $mailer->to = $attendee->email;
      $mailer->subject = $attendee->name . '，您的单身聚会报名已经收到';
      $mailer->body = new Template( 'mail/attendee', [ 'name' => $attendee->name ] );
      $mailer->signature = '';

      if ( !$mailer->send() )
      {
         $this->error( '错误: 报名确认邮件发送失败' );
      }

      $this->ajax( [
         'message' => '报名成功，确认邮件已发送。<br /><a href="/single">返回首页</a>',
         'chart' => $chart,
         'comments' => $comments
      ] );

      $this->_getIndependentCache( '/single' )->delete();
   }

   protected function error( $msg )
   {
      $this->ajax( ['error' => $msg ] );
      exit( (string) $this->html );
   }

}

//__END_OF_FILE__