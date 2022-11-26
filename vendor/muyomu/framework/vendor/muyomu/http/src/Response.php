<?php

namespace muyomu\http;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use muyomu\config\ConfigParser;
use muyomu\config\exception\FieldConfigException;
use muyomu\http\client\ResponseClient;
use muyomu\http\config\DefaultFileConfig;
use muyomu\http\config\DefaultHttpConfig;
use muyomu\http\exception\FileNotFoundException;
use muyomu\http\message\Message;
use muyomu\http\message\MessageToArray;

class Response implements ResponseClient
{
    private array $configData;

    private array $fileConfig;

    /**
     * @throws FieldConfigException
     */
    public function __construct()
    {
        $config = new ConfigParser();
        $this->configData = $config->getConfigData(DefaultHttpConfig::class);
        $this->fileConfig = $config->getConfigData(DefaultFileConfig::class);
    }


    public function setHeader(string $field, string $content): void
    {
        $this->configData[$field] = $content;
    }

    private function addAllHeaders(array $config):void{
        $keys = array_keys($config['response_headers']);
        foreach ($keys as $key){
            $value = $config['response_headers'][$key];
            $header = "$key: $value";
            header("$header");
        }
    }

    #[NoReturn] public function doDataResponse(mixed $data,int $code): void
    {
        http_response_code($code);
        $this->addAllHeaders($this->configData);

        $message = new Message();
        $message->setDataStatus("Success");
        $message->setDataType(gettype($data));
        $message->setData($data);

        $data = MessageToArray::messageToArray($message);

        die(json_encode($data));
    }

    #[NoReturn] public function doExceptionResponse(Exception $exception, int $code,): void
    {
        http_response_code($code);
        $this->addAllHeaders($this->configData);

        $message = new Message();
        $message->setDataStatus("Success");
        $message->setDataType(gettype("string"));
        $message->setData($exception->getMessage());

        $data = MessageToArray::messageToArray($message);

        die(json_encode($data));
    }

    #[NoReturn] public function doFileResponse(string $file): void
    {
        $file_location = $this->fileConfig['location'].$file;
        $resource = fopen($file_location,"r");
        if ($resource){
            $this->addAllHeaders($this->fileConfig);
            Header ( "Accept-Length: " . filesize ($file_location) );
            Header ( "Content-Disposition: attachment; filename=" . $file );
            $content = fread($resource,filesize($file));
            die($content);
        }else{
            $this->doExceptionResponse(new FileNotFoundException(),400);
        }
    }


    #[NoReturn] public function doCustomizeResponse(mixed $data, int $code, array $headerConfig = array()):void
    {
        http_response_code($code);
        $this->customizeHeaders($headerConfig);

        $message = new Message();
        $message->setDataStatus("Success");
        $message->setDataType(gettype("string"));
        $message->setData($data);

        $data = MessageToArray::messageToArray($message);

        die(json_encode($data));
    }

    private function customizeHeaders(array $config):void{
        $keys = array_keys($config);
        foreach ($keys as $key){
            $value = $config[$key];
            $header = "$key: $value";
            header("$header");
        }
    }
}