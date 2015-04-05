<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form;

/**
 * Post presenter.
 */
class PostPresenter extends BasePresenter {

    private $database;

    /** @persistent */
    public $backlink = '';

    function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    public function renderShow($id,$word='') {

        $post = $this->database->table('posts')->get($id);
        $comments = $post->related('comments')->order('created_at');
        $this->template->post = $post;
        $this->template->comments = $comments;
        $this->template->word=$word;
    }

    public function getPerson($user_id) {
        $user = $this->database->table('users')->get($user_id);
        return $user;
    }

    public function createComponentCommentForm() {
        $form = new Form();
        $form->addTextArea('comment')
                ->setRequired('Napíšte komentár!');
        $form->addSubmit('send', 'Odoslať');
        $form->onSuccess[] = array($this, 'commentFormSucceeded');
        return $form;
    }

    public function handleDeleteComment($commentId) {
        if ($this->getUser()->isInRole('admin')) {
            $comment = $this->database->table('comments')->get($commentId);
            $prev = $comment->post_id;
            $comment->delete();
            $this->redrawControl('comments');
        }
    }

    public function commentFormSucceeded($form) {
        $values = $form->getValues();
        $postId = $this->getParameter('id');
        $this->database->table('comments')->insert(array(
            'post_id' => $postId,
            'user' => $this->getUser()->getId(),
            'content' => $values->comment
        ));
        $this->redirect('Post:show', $postId);
    }

    public function rendercreate($id) {
        if ($id == null) {
            $title = "Zadajte Nadpis";
            $photo = "images/posts/default.png";
            $description = "Vložte popis";
            $content = "Vložte text";
        } else {
            $post = $this->database->table('posts')->get($id);
            $title = $post->title;
            $photo = $post->photo;
            $description = $post->description;
            $content = $post->content;
        }
        $this->template->title = $title;
        $this->template->photo = $photo;
        $this->template->description = $description;
        $this->template->content = $content;
    }

    public function createComponentCreatePost() {
        $form = new Form();
        if ($this->getParameter('id') == null) {
            $description = "Vložte popis";
            $content = "Vložte text";
        } else {
            $post = $this->database->table('posts')->get($this->getParameter('id'));
            $description = $post->description;
            $content = $post->content;
        }
        $form->addText('title')
                ->setRequired('Napíšte komentár!');
        $form->addUpload('image', 'Odoslať');
        $form->addTextArea('description')
                ->setRequired('Napíšte popis!')
                ->setDefaultValue($description);
        $form->addTextArea('content')
                ->setRequired('Napíšte článok!')
                ->setDefaultValue($content);
        $form->addSubmit('send', 'Uložiť');
        $form->onSuccess[] = array($this, 'updatePost');
        return $form;
    }

    public function updatePost($form) {
        if ($this->getUser()->isInRole('admin')) {
            $values = $form->getValues();
            $image = $values['image'];
            if ($this->getParameter('id') == null) {
                $row = $this->database->table('posts')->insert(array(
                    'title' => $values->title,
                    'content' => $values->content,
                    'description' => $values->description,
                    'photo' => 'images/posts/default',
                    'author' => $this->getUser()->getId()
                ));
            } else {
                $row = $this->database->table('posts')->get($this->getParameter('id'));
                $row->update((array(
                    'title' => $values->title,
                    'content' => $values->content,
                    'description' => $values->description,
                    'author' => $this->getUser()->getId())));
            }
            if ($image->isOK()) {
                $oldImage = $row->photo;
                if ($oldImage != '/images/avatar/post1.png') {
                    @ unlink($oldImage);
                }
                $newname = 'post' . "$row->id" . '.' . pathinfo($image->name, PATHINFO_EXTENSION);
                $newPath = $this->context->parameters['wwwDir'] . '\images\posts\\' . $newname;
                $fileTarget = $this->context->parameters['wwwDir'] . '\images\posts\\' . $image->name;
                $image->move($fileTarget);
                rename($fileTarget, $newPath);
                $row->update(array('photo' => $newname));
            }
            $this->redirect("Post:show", $row->id);
        }
    }

    public function handlelike($id, $what) {
        if ($this->isAjax()) {
            $row = $this->database->table('likes')->where('post_id = ? AND user_id = ?', $id, $this->getUser()->getId())->fetch();
            if ($row == null) {
                $this->database->table('likes')->insert(array(
                    'post_id' => $id,
                    'user_id' => $this->getUser()->getId(),
                    'status' => $what
                ));
            } else {
                $this->database->table('likes')->where('post_id = ? AND user_id = ?', $id, $this->getUser()->getId())->update(array(
                    'status' => $what
                ));
            }
            $this->redrawControl('likes');
            $this->redrawControl('best');
            $this->redrawControl('counts');
        }
    }

    public function alreadyliked($id, $what) {
        $row = $this->database->table('likes')->where('post_id = ? AND user_id = ?', $id, $this->getUser()->getId())->fetch();
        if ($row!=null) {
            if ($row->status == $what) {
                return true;
            }
        }
        return false;
    }

    public function getLikes($id, $what) {
        return $this->database->table('likes')->where('post_id = ? AND status = ?', $id, $what)->count();
    }

}
