<?php

namespace App\Presenters;

use Nette,
    App\Model;

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter {

    private $database;
    private $offset=1;
    function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    public function renderDefault() {
        
        $posts=$this->database->table('posts')->order('created_at DESC')->limit(6, $this->offset);
        $this->template->latest=$this->database->table('posts')->get($posts->max('id'));
        $this->template->lastOffset=$this->database->table('posts')->count();
        $this->template->posts=$posts;    }   
        
    public function handledelete($id){
        if($this->getUser()->isInRole('admin')){
           
        $post=$this->database->table('posts')->get($id);
        $post->related('likes')->delete();
        $post->related('comments')->delete();
        $post->delete();
        $this->redrawControl('main');
        $this->redrawControl('best');
        }
    }
    public function getPerson($user_id){
        $user= $this->database->table('users')->get($user_id);
        return $user;
        
    }
    public function getOffset(){
        return $this->offset;
    }
    public function setOffset($offset){
        $this->offset=$offset;
    }
    
    public function handleload($offset){
        if($this->isAjax()){
                $this->setOffset($offset);       
            $this->redrawControl('posts');
        }
    }


}
