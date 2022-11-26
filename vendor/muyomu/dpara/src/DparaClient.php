<?php

namespace muyomu\dpara;

use muyomu\database\DbClient;
use muyomu\database\exception\KeyNotFond;
use muyomu\database\exception\RepeatDefinition;
use muyomu\dpara\client\Dpara;
use muyomu\dpara\exception\UrlNotMatch;
use muyomu\dpara\utility\DparaHelper;
use muyomu\http\Request;

class DparaClient implements Dpara
{

    private DparaHelper $dparaHelper;

    public function __construct()
    {
        $this->dparaHelper = new DparaHelper();
    }

    /**
     * @throws UrlNotMatch
     * @throws RepeatDefinition
     */
    public function dpara(Request $request, DbClient $dbClient): void
    {

        /*
         * 静态路由转换
         */
        $static_routes_table = $this->routeResolver($dbClient->database);

        /*
         * 静态路由查询
         */
        $key_value = $this->dparaHelper->key_exits($request->getURL(),$static_routes_table,$request,$dbClient->database);

        /*
         * 将数据保存到request中的rule中
         */
        $request_db = null;
        try {
            $request_db = $request->getDbClient()->select("rule");
        }catch (KeyNotFond $e) {

        }
        $request_db->getData()->setPathpara($key_value['value']);
        $request_db->getData()->setPathkey($key_value['key']);
    }

    /*
     * 静态路由解析
     */
    private function routeResolver(array $database):array{
        $routes = array_keys($database);
        $routes_str = implode("|-|",$routes);
        $match = array();
        preg_match_all("/(\/[a-zA-Z]+)+/im",$routes_str,$match);

        //获取到所有的静态路由除开根目录
        $static_routes = $match[0];
        $static_routes = array_unique($static_routes);

        //获取到所有的静态路由对应的动态路由
        $list = array();
        foreach ($static_routes as $route){
            $ck = str_replace("/","\/",$route);
            preg_match_all("/{$ck}(\/:[a-zA-Z]*)*/im",$routes_str,$match);
            $list[$route] = $match[0];
        }
        return $list;
    }
}