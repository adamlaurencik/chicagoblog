<?php

namespace App\Presenters;

use Nette,
    App\Model;

/**
 * Description of SearchPresenter
 *
 * @author Adam
 */
class SearchPresenter extends \App\Presenters\BasePresenter {

    private $database;

    function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    public function renderdefault($word) {
        $posts = $this->database->table('posts')->where('(title LIKE ?) OR (description LIKE ?) OR (content LIKE ?)', "%$word%", "%$word%", "%$word%");
        $this->template->posts = $posts;
        $this->template->word = $word;
    }

    public function handledelete($id) {
        if ($this->isAjax()) {
            if ($this->getUser()->isInRole('admin')) {
                $post = $this->database->table('posts')->get($id);
                $post->related('likes')->delete();
                $post->related('comments')->delete();
                $post->delete();
                $this->redrawControl('posts');
                $this->redrawControl('best');
            }
        }
    }
    public function getPerson($user_id){
        $user= $this->database->table('users')->get($user_id);
        return $user;
    }

}
