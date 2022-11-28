<?php

namespace muyomu\auth\base;

use muyomu\auth\client\ModeClient;
use muyomu\auth\client\Realm;
use muyomu\auth\config\DefaultSecurityConfig;
use muyomu\auth\exception\NotCheckedUserException;
use muyomu\auth\utility\Jwt;
use muyomu\http\Request;
use muyomu\http\Response;
use ReflectionClass;
use ReflectionException;

class ObverseMode implements ModeClient
{
    /**
     * @throws ReflectionException
     */
    private static function getRealmInstance():Realm{

        $config = new DefaultSecurityConfig();

        $class  = $config->getOptions("realm");

        $reflectionClass = new ReflectionClass($class);

        return $reflectionClass->newInstance();
    }

    private DefaultSecurityConfig $defaultSecurityConfig;

    private Jwt $jwt;

    public function __construct()
    {
        $this->defaultSecurityConfig = new DefaultSecurityConfig();
        $this->jwt = new Jwt();
    }


    public function handle(Request $request, Response $response): void
    {
        $requestUrl = $request->getDbClient()->select("rule")->getData()->getRoute();

        $obverseUrls = array_keys($this->defaultSecurityConfig->getOptions("obverse"));

        if (!array_key_exists($requestUrl,$obverseUrls)){
            return;
        }

        $token = $request->getHeader($this->defaultSecurityConfig->getOptions("tokenName"));

        if (is_null($token)){
            $response->doExceptionResponse(new NotCheckedUserException(),403);
        }

        $result  = $this->jwt->verifyToken($token);

        if (!$result){
            $response->doExceptionResponse(new NotCheckedUserException(),403);
        }

        try {
            $authorizator = self::getRealmInstance();

            $payload = $this->jwt->getPayload($token);

            $principle = new Principle();

            $uid = $this->defaultSecurityConfig->getOptions("uid");

            $principle->setIdentifier($payload[$uid]);

            $authorizator = $authorizator->authorization($principle);

            $roles = $authorizator->getRoles();

            $privileges = $authorizator->getPrivileges();

            $dataRoles = $this->defaultSecurityConfig->getOptions("obverse.{$requestUrl}.roles");

            $dataPrivileges = $this->defaultSecurityConfig->getOptions("obverse.{$requestUrl}.privileges");

            if (in_array($roles,$dataRoles) && in_array($privileges,$dataPrivileges)){
                return;
            }else{
                $response->doExceptionResponse(new NotCheckedUserException(),403);
            }

        }catch (ReflectionException $exception){
            $response->doExceptionResponse(new NotCheckedUserException(),403);
        }
    }
}