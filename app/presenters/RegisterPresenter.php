<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RegisterPresenter
 *
 * @author Adam
 */
class RegisterPresenter extends BasePresenter {

    private $database;
    public $avatar;

    function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    function renderPhoto() {
        
    }

    function createComponentUploadImage() {
        $form = new Form();
        $form->addUpload('avatar', 'Fotka:')
                ->addRule(Form::IMAGE, 'Súbor musí být JPEG, PNG nebo GIF.');
        $form->addSubmit('send', 'Odoslať fotku');
        $form->onSuccess[] = array($this, 'sendPhoto');
        return $form;
    }

    function createComponentRegister() {
        $form = new Form();
        $form->addText('username', 'Meno: ')
                ->setRequired('Zadajte meno!');
        $form->addPassword('password', 'Heslo:')
                ->setRequired('Zadajte heslo!')
                ->addRule(Form::MIN_LENGTH, 'Heslo musi mať aspoň %d znaky', 4);
        $form->addPassword('contr', 'Kontrola hesla:')
                ->setRequired('Zadajte heslo!')
                ->addRule(Form::EQUAL, 'Heslá sa nezhodujú', $form['password']);
        $form->addSubmit('send', 'Registrovať');
        $form->onSuccess[] = array($this, 'register');
        return $form;
    }

    function register($form) {
        $values = $form->getValues();
        if ($values['password'] == $values['contr']) {
            if (!$this->database->table('users')->where('username', $values['username'])->fetch()) {
                $hash = sha1($values['password']);
                $this->database->table('users')->insert(array(
                    'username' => $values['username'],
                    'password' => $hash,
                    'role' => 'user',
                    'avatar' => '/images/avatar/default.png'
                ));
                $this->user->login($values['username'], $values['password']);
                $this->redirect('Register:photo');
            } else {
                $form->addError('Používateľ už existuje!');
            }
        }
    }

    function sendPhoto($form) {
        $values = $form->getValues();
        $image = $values['avatar'];
        if ($image->isOK()) {
            $oldImage = $this->user->getIdentity()->avatar;
            if ($oldImage != '/images/avatar/default.png') {
               @ unlink($oldImage);
            }
            
            $newname = $this->user->getIdentity()->data['username'] . '.' . pathinfo($image->name, PATHINFO_EXTENSION);
            
            $newPath = $this->context->parameters['wwwDir'] . '\images\avatar\\' . $newname;
            $fileTarget = $this->context->parameters['wwwDir'] . '\images\avatar\\' . $image->name;
            $image->move($fileTarget);
            rename($fileTarget, $newPath);
            $this->database->table('users')->where('id', $this->user->getId())->update(array('avatar' => "images/avatar/$newname"));
            $this->user->getIdentity()->avatar = "images/avatar/$newname";
        }
    }

}
