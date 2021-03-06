<?php

declare(strict_types=1);

namespace site\handler\api\bookmark;

use lzx\exception\ErrorMessage;
use lzx\exception\Forbidden;
use site\Service;

class Handler extends Service
{
    const NODES_PER_PAGE = 20;
    /**
     * get bookmarks for a user
     * uri: /api/bookmark/<uid>
     *      /api/bookmark/<uid>?p=<pageNo>
     */
    public function get(): void
    {
        $this->validateUser();
        if (!$this->args || !is_numeric($this->args[0])) {
            throw new Forbidden();
        }

        $uid = (int) $this->args[0];

        if ($uid !== $this->user->id) {
            throw new Forbidden();
        }

        $nodeCount = $this->user->countBookmark();
        list($pageNo, $pageCount) = $this->getPagerInfo($nodeCount, self::NODES_PER_PAGE);

        $nodes = $nodeCount > 0 ? $this->user->listBookmark(self::NODES_PER_PAGE, ($pageNo - 1) * self::NODES_PER_PAGE) : [];

        $this->json(['nodes' => $nodes, 'pager' => ['pageNo' => $pageNo, 'pageCount' => $pageCount]]);
    }

    /**
     * add a node to user's bookmark list
     * uri: /api/bookmark[?action=post]
     * post: nid=<nid>
     */
    public function post(): void
    {
        if (!$this->request->data) {
            throw new Forbidden();
        }

        $nid = (int) $this->request->data['nid'];
        if ($nid <= 0) {
            throw new ErrorMessage('node does not exist');
        }

        $this->validateUser();

        $this->user->addBookmark($nid);

        $this->json();
    }

    /**
     * remove one node or multiple modes from user's bookmark list
     * uri: /api/bookmark/<nid>(,<nid>,...)?action=delete
     */
    public function delete(): void
    {
        $this->validateUser();
        if (!$this->args) {
            throw new Forbidden();
        }

        $nids = [];

        foreach (explode(',', $this->args[0]) as $nid) {
            if (is_numeric($nid) && intval($nid) > 0) {
                $nids[] = (int) $nid;
            }
        }

        foreach ($nids as $nid) {
            $this->user->deleteBookmark($nid);
        }

        $this->json();
    }
}
