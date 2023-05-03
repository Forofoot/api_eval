<?php 

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator{

    private $v;

    public function __construct(ValidatorInterface $v){
        $this->v = $v;
    }

    public function isValid($obj){
        $errors = $this->v->validate($obj);

        if (count($errors) > 0) {
            $e_list = [];
            foreach ($errors as $e) {
                $e_list[] = $e->getMessage();
            }

            return $e_list;
        }else{
            return true;
        }
    }
}