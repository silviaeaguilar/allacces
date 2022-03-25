<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;

class GetExchangeRates
{
    private $httpclient;

    public function __construct(HttpClientInterface $httpclient)
    {
        $this->httpclient = $httpclient;
    }

    public function __invoke(string $currency):array
    {
        if ($currency == 'USD')
            $base='EUR';
        else
            $base = 'USD';
        $response = $this->httpclient->request(
            'GET',
            sprintf('http://api.exchangeratesapi.io/v1/latest?access_key=31e4c25225d9b1e06009a9c17e28a936&base=%s&symbols=%s',$base, $currency)
        );
        $content = $response->getContent();
        $json = json_decode($content, true);
        return $json['rates'];

    }

    public function convertList($rate, array $products, $currency):array
    {
        $productsArray = [];

        foreach ($products as $product)
        {

            if($currency == $product->getCurrency())
            {  $exchange = 1;
            }
            else{ $exchange = $rate ;
            }
            $productsArray[] = [
                'id' => $product->getId(),
                'name' =>$product->getName(),
                'price'=>$product->getPrice() * $exchange ,
                'currency'=>$product->getCurrency(),
                'category'=>$product->getCategory()->getName()
            ];

        }
        return $productsArray;
    }
}