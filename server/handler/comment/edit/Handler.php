<?php

declare(strict_types=1);

namespace site\handler\comment\edit;

use Exception;
use lzx\core\Response;
use lzx\exception\ErrorMessage;
use lzx\exception\Forbidden;
use lzx\exception\Redirect;
use site\dbobject\Comment as CommentObject;
use site\dbobject\Image;
use site\handler\comment\Comment;

class Handler extends Comment
{
    public function run(): void
    {
        $this->response->type = Response::JSON;

        // edit existing comment
        $cid = (int) $this->args[0];

        if (strlen($this->request->data['body']) < 5) {
            throw new ErrorMessage('Comment body is too short.');
        }

        $comment = new CommentObject($cid, 'nid,uid');
        if ($this->user->id !== self::UID_ADMIN && $this->user->id !== $comment->uid) {
            $this->logger->warning('wrong action : uid = ' . $this->user->id);
            throw new Forbidden();
        }
        $comment->body = $this->request->data['body'];
        $comment->lastModifiedTime = $this->request->timestamp;
        $comment->reportableUntil = $this->request->timestamp + self::ONE_DAY * 3;
        try {
            $this->dedup();

            $comment->update();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new ErrorMessage($e->getMessage());
        }

        // FORUM comments images
        if ($this->request->data['update_file']) {
            $files = $this->getFormFiles();

            $file = new Image();
            $file->cityId = self::$city->id;
            $file->updateFileList($files, $this->config->path['file'], $comment->nid, $cid);
            $this->getIndependentCache('imageSlider')->delete();
        }

        $this->getCacheEvent('NodeUpdate', $comment->nid)->trigger();

        throw new Redirect($this->request->referer);
    }
}
