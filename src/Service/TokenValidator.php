<?php 

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\JsonResponse;

class TokenValidator{

    /**
     * @var string
     */
    private $jwt_secret;

    public function __construct(string $jwt_secret){
        $this->jwt_secret = $jwt_secret;
    }


    public function checkToken($header)
    {
        if($header['token'] != null && !empty($header['token'])){
            $token = $header['token'];
            $jwt = current($token);
            $key = $this->jwt_secret;
            try{
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
                return array(true, $decoded);
            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 401);
            }
        }else{
            return new JsonResponse('Token not found', 404);
        }
    }

}