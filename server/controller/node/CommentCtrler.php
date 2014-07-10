<?php

namespace site\controller\node;

use site\controller\Node;
use site\dbobject\Node as NodeObject;
use site\dbobject\Comment;
use site\dbobject\Image;
use site\dbobject\User;

class CommentCtrler extends Node
{

   public function run()
   {
      $this->cache->setStatus( FALSE );
      
      list($nid, $type) = $this->_getNodeType();
      $method = '_comment' . $type;
      $this->$method( $nid );
   }

   private function _commentForumTopic( $nid )
   {
      // create new comment
      $node = new NodeObject( $nid, 'tid,status' );

      if ( !$node->exists() || $node->status == 0 )
      {
         $this->error( 'node does not exist.' );
      }

      if ( strlen( $this->request->post[ 'body' ] ) < 5 )
      {
         $this->error( '错误：评论正文字数太少。' );
      }

      $user = new User( $this->request->uid, 'createTime,points,status' );
      try
      {
         $user->validatePost( $this->request->ip, $this->request->timestamp, $this->request->post[ 'body' ] );
         $comment = new Comment();
         $comment->nid = $nid;
         $comment->uid = $this->request->uid;
         $comment->body = $this->request->post[ 'body' ];
         $comment->createTime = $this->request->timestamp;
         $comment->add();
      }
      catch ( \Exception $e )
      {
         // spammer found
         if ( $user->isSpammer() )
         {
            $this->logger->info( 'SPAMMER FOUND: uid=' . $user->id );
            $u = new User();
            $u->lastAccessIP = \ip2long( $this->request->ip );
            $users = $u->getList( 'createTime' );
            $deleteAll = TRUE;
            if ( \sizeof( $users ) > 0 )
            {
               // check if we have old users that from this ip
               foreach ( $users as $u )
               {
                  if ( $this->request->timestamp - $u[ 'createTime' ] > 2592000 )
                  {
                     $deleteAll = FALSE;
                     break;
                  }
               }

               if ( $deleteAll )
               {
                  $log = 'SPAMMER FROM IP ' . $this->request->ip . ': uid=';
                  foreach ( $users as $u )
                  {
                     $spammer = new User( $u[ 'id' ], NULL );
                     $spammer->delete();
                     $log = $log . $spammer->id . ' ';
                  }
                  $this->logger->info( $log );
               }
            }
            if ( $this->config->webmaster )
            {
               $mailer = new \lzx\core\Mailer();
               $mailer->subject = 'SPAMMER detected and deleted (' . \sizeof( $users ) . ($deleteAll ? ' deleted)' : ' not deleted)');
               $mailer->body = ' --node-- ' . $this->request->post[ 'title' ] . PHP_EOL . $this->request->post[ 'body' ];
               $mailer->to = $this->config->webmaster;
               $mailer->send();
            }
         }

         $this->logger->error( ' --comment-- ' . $this->request->post[ 'body' ] );
         $this->error( $e->getMessage(), TRUE );
      }

      if ( $this->request->post[ 'files' ] )
      {
         $file = new Image();
         $file->updateFileList( $this->request->post[ 'files' ], $this->config->path[ 'file' ], $nid, $comment->id );
         $this->cache->delete( 'imageSlider' );
      }

      $user->points += 1;
      $user->update( 'points' );

      $this->cache->delete( '/node/' . $nid );
      $this->cache->delete( '/forum/' . $node->tid );
      $this->cache->delete( 'latestForumTopicReplies' );
      if ( \in_array( $nid, $node->getHotForumTopicNIDs( 15, $this->request->timestamp - 604800 ) ) )
      {
         $this->cache->delete( 'hotForumTopics' );
      }

      //$pageNoLast = ceil(($node->commentCount + 1) / self::COMMENTS_PER_PAGE);
      $redirect_uri = '/node/' . $nid . '?page=last#comment' . $comment->id;
      $this->request->redirect( $redirect_uri );
   }

   private function _commentYellowPage( $nid )
   { 
      // create new comment
      $node = new NodeObject( $nid, 'status' );

      if ( !$node->exists() || $node->status == 0 )
      {
         $this->error( 'node does not exist.' );
      }

      if ( strlen( $this->request->post[ 'body' ] ) < 5 )
      {
         $this->error( '错误：评论正文字数太少。' );
      }

      $user = new User( $this->request->uid, 'createTime,points,status' );
      try
      {
         $user->validatePost( $this->request->ip, $this->request->timestamp, $this->request->post[ 'body' ] );
         $comment = new Comment();
         $comment->nid = $nid;
         $comment->uid = $this->request->uid;
         $comment->body = $this->request->post[ 'body' ];
         $comment->createTime = $this->request->timestamp;
         $comment->add();
      }
      catch ( \Exception $e )
      {
         // spammer found
         if ( $user->isSpammer() )
         {
            $this->logger->info( 'SPAMMER FOUND: uid=' . $user->id );
            $u = new User();
            $u->lastAccessIP = \ip2long( $this->request->ip );
            $users = $u->getList( 'createTime' );
            $deleteAll = TRUE;
            if ( \sizeof( $users ) > 0 )
            {
               // check if we have old users that from this ip
               foreach ( $users as $u )
               {
                  if ( $this->request->timestamp - $u[ 'createTime' ] > 2592000 )
                  {
                     $deleteAll = FALSE;
                     break;
                  }
               }

               if ( $deleteAll )
               {
                  $log = 'SPAMMER FROM IP ' . $this->request->ip . ': uid=';
                  foreach ( $users as $u )
                  {
                     $spammer = new User( $u[ 'id' ], NULL );
                     $spammer->delete();
                     $log = $log . $spammer->id . ' ';
                  }
                  $this->logger->info( $log );
               }
            }
            if ( $this->config->webmaster )
            {
               $mailer = new \lzx\core\Mailer();
               $mailer->subject = 'SPAMMER detected and deleted (' . \sizeof( $users ) . ($deleteAll ? ' deleted)' : ' not deleted)');
               $mailer->body = ' --node-- ' . $this->request->post[ 'title' ] . PHP_EOL . $this->request->post[ 'body' ];
               $mailer->to = $this->config->webmaster;
               $mailer->send();
            }
         }

         $this->logger->error( ' --comment-- ' . $this->request->post[ 'body' ] );
         $this->error( $e->getMessage(), TRUE );
      }

      if ( isset( $this->request->post[ 'star' ] ) && \is_numeric( $this->request->post[ 'star' ] ) )
      {
         $rating = (int) $this->request->post[ 'star' ];
         if ( $rating > 0 )
         {
            $node->updateRating( $nid, $this->request->uid, $rating, $this->request->timestamp );
         }
      }

      $user->points += 1;
      $user->update( 'points' );

      $this->cache->delete( '/node/' . $nid );
      $this->cache->delete( 'latestYellowPageReplies' );

      $redirect_uri = '/node/' . $nid . '?page=last#comment' . $comment->id;
      $this->request->redirect( $redirect_uri );
   }

}

//__END_OF_FILE__
