<?php declare(strict_types=1);

namespace site\handler\node\edit;

use site\handler\node\Node;
use lzx\html\Template;
use site\dbobject\Node as NodeObject;
use site\dbobject\NodeYellowPage;
use site\dbobject\Image;
use site\dbobject\Comment;

class Handler extends Node
{
    public function run()
    {
        list($nid, $type) = $this->getNodeType();
        $method = 'edit' . $type;
        $this->$method($nid);
    }

    private function editForumTopic($nid)
    {
        // edit existing comment
        $node = new NodeObject($nid, 'uid,status');

        if (!$node->exists() || $node->status == 0) {
            $this->error('node does not exist.');
        }

        if (strlen($this->request->post['body']) < 5 || strlen($this->request->post['title']) < 5) {
            $this->error('Topic title or body is too short.');
        }

        if ($this->request->uid != 1 && $this->request->uid != $node->uid) {
            $this->logger->warn('wrong action : uid = ' . $this->request->uid);
            $this->pageForbidden();
        }

        $node->title = $this->request->post['title'];
        $node->lastModifiedTime = $this->request->timestamp;

        try {
            $node->update();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->error($e->getMessage());
        }

        $comment = new Comment();
        $comment->nid = $nid;
        $arr = $comment->getList('id', 1);

        $comment = new Comment();
        $comment->id = $arr[0]['id'];
        $comment->body = $this->request->post['body'];
        $comment->lastModifiedTime = $this->request->timestamp;
        $comment->update();


        $files = is_array($this->request->post['files']) ? $this->request->post['files'] : [];
        $file = new Image();
        $file->cityID = self::$city->id;
        $file->updateFileList($files, $this->config->path['file'], $nid, $comment->id);

        $this->getCacheEvent('ImageUpdate')->trigger();
        $this->getCacheEvent('NodeUpdate', $nid)->trigger();

        $this->pageRedirect($this->request->referer);
    }

    private function editYellowPage($nid)
    {
        $node = new NodeObject($nid, 'uid,status');

        if (!$node->exists() || $node->status == 0) {
            $this->error('node does not exist.');
        }

        if ($this->request->uid != 1 && $this->request->uid != $node->uid) {
            $this->logger->warn('wrong action : uid = ' . $this->request->uid);
            $this->pageForbidden();
        }

        if (empty($this->request->post)) {
            // display edit interface
            $nodeObj = new NodeObject();
            $contents = $nodeObj->getYellowPageNode($nid);

            $comment = new Comment();
            $comment->nid = $nid;
            $arr = $comment->getList('id', 1);

            $comment = new Comment($arr[0]['id'], 'body');
            $contents['body'] = $comment->body;
            
            $image = new Image();
            $image->nid = $nid;
            $image->cid = $comment->id;
            $contents['files'] = $image->getList('id,name,path');

            $this->var['content'] = new Template('editor_bbcode_yp', $contents);
        } else {
            // save modification
            $node = new NodeObject($nid, 'tid');
            $node->title = $this->request->post['title'];
            $node->lastModifiedTime = $this->request->timestamp;
            $node->update();

            $node_yp = new NodeYellowPage($nid, 'nid');
            $keys = ['address', 'phone', 'email', 'website', 'fax'];
            foreach ($keys as $k) {
                $node_yp->$k = strlen($this->request->post[$k]) ? $this->request->post[$k] : null;
            }

            $node_yp->update();

            $comment = new Comment();
            $comment->nid = $nid;
            $arr = $comment->getList('id', 1);

            $comment = new Comment();
            $comment->id = $arr[0]['id'];
            $comment->body = $this->request->post['body'];
            $comment->lastModifiedTime = $this->request->timestamp;
            $comment->update();

            $files = is_array($this->request->post['files']) ? $this->request->post['files'] : [];
            $file = new Image();
            $file->cityID = self::$city->id;
            $file->updateFileList($files, $this->config->path['file'], $nid, $comment->id);

            $this->getCacheEvent('NodeUpdate', $nid)->trigger();

            $this->pageRedirect('/node/' . $nid);
        }
    }
}
