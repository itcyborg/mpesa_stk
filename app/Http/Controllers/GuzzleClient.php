<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class GuzzleClient extends Controller
{
    public static function request($method,$endpoint,$headers,$data=[])
    {
        $client=new Client([
            'headers'=>$headers,
            'verify'=>false
        ]);
        try {
            $response = $client->request($method, $endpoint,$data);
            return $response->getBody()->getContents();
        } catch (ClientException $exception) {
            throw new \Exception($exception->getResponse()->getReasonPhrase(),500);
        }catch (\Exception $e){
            throw new \Exception($e->getMessage(),500);
        }
    }

    public static function postJson($endpoint,$headers,$data)
    {
        $client=new Client([
            'headers'=>$headers,
            'verify'=>false
        ]);
        try {
            $response=$client->post($endpoint,[
                RequestOptions::JSON => $data
            ]);
            return $response->getBody()->getContents();
        } catch (ClientException $exception) {
            $responseBody = $exception->getResponse()->getBody(true)->getContents();
            throw new \Exception($responseBody,500);
        }catch (\Exception $e){
            throw new \Exception($e->getMessage(),500);
        }
    }
}
