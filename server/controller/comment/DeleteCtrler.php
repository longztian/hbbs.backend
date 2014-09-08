<?php

namespace site\controller\comment;

use site\controller\Comment;
use site\dbobject\Tag;
use site\dbobject\Comment as CommentObject;
use site\dbobject\Node;
use site\dbobject\User;

class DeleteCtrler extends Comment
{

   public function run()
   {
      $comment = new CommentObject();
      $comment->id = $this->id;
      $comment->load( 'uid,nid' );

      if ( $this->request->uid != 1 && $this->request->uid != $comment->uid )
      {
         $this->logger->warn( 'wrong action : uid = ' . $this->request->uid );
         $this->pageForbidden();
      }

      $this->_getCacheEvent( 'NodeUpdate', $comment->nid )->trigger();

      $node = new Node( $comment->nid, 'tid' );
      if ( \in_array( $node->tid, ( new Tag( self::$_city->ForumRootID, NULL ) )->getLeafTIDs() ) ) // forum tag
      {
         $this->_getCacheEvent( 'ForumComment' )->trigger();
         $this->_getCacheEvent( 'ForumUpdate', $node->tid )->trigger();
      }

      if ( \in_array( $node->tid, ( new Tag( self::$_city->YPRootID, NULL ) )->getLeafTIDs() ) ) // yellow page tag
      {
         $this->_getCacheEvent( 'YellowPageComment', $node->tid )->trigger();
         /*
           $c = new CommentObject();
           $c->nid = $comment->nid;
           $c->uid = $comment->uid;
           if ( $c->getCount() == 0 )
           {
           $node = new Node();
           $node->deleteRating( $comment->nid, $comment->uid );
           } */
      }

      $user = new User( $comment->uid, 'points' );
      $user->points -= 1;
      $user->update( 'points' );

      $comment->delete();

      $this->pageRedirect( $this->request->referer );
   }

}

//__END_OF_FILE__