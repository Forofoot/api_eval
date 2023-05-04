<?php 

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;

class UserValidator{

    public function checkUser($decoded)
    {
        if($decoded->roles != null && in_array('ROLE_ADMIN', $decoded->roles)){
            return true;
        }else{
            return new JsonResponse('User not allowed', 401);
        }
    }

}