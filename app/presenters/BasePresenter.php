<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form;

abstract class BasePresenter extends Nette\Application\UI\Presenter {

    private $database;

    public function injectDatabase(Nette\Database\Context $database) {
        $this->database = $database;
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->best = BasePresenter::getBestPosts();
    }

    public function handledelete($id) {
        if ($this->getUser()->isInRole('admin')) {

            $post = $this->database->table('posts')->get($id);
            $post->related('likes')->delete();
            $post->delete();
            $this->redrawControl('main');
            $this->redrawControl('best');
        }
    }
    public function actiondelete($id) {
        if ($this->getUser()->isInRole('admin')) {

            $post = $this->database->table('posts')->get($id);
            $post->related('likes')->delete();
            $post->delete();
            $this->redirect('Homepage:default');
        }
    }

    public function createComponentSignInForm() {
        $form = new Form();
        $form->addText('username')
                ->setRequired('Zadajte meno!')
                ->addRule(Form::MIN_LENGTH, 'Meno je krátke!', 3);
        $form->addPassword('password')
                ->setRequired('Zadajte heslo!')
                ->addRule(Form::MIN_LENGTH, 'Heslo je krátke!', 3);
        $form->addSubmit('send', 'Prihlásiť');
        $form->onSuccess[] = array($this, 'loginFormSucceeded');
        return $form;
    }

    public function loginFormSucceeded($form) {
        try {
            $values = $form->getValues();
            $this->getUser()->logout();
            $this->getUser()->setExpiration('60 minutes', FALSE);
            $this->getUser()->login($values['username'], $values['password']);
        } catch (Nette\Security\AuthenticationException $ex) {

            $form->addError($ex->getMessage());
        }
    }

    public function getBestPosts() {
        $posts = $this->database->table('posts');
        $results = array();
        foreach ($posts as $post) {
            $count = $this->database->table('likes')->where('status=? AND post_id=?', 'l', $post->id)->count();

            $results[$post->id] = $count;
        }
        arsort($results);
        return($results);
    }

    public function getTitleOfPost($id) {
        return $this->database->table('posts')->get($id)->title;
    }

}
