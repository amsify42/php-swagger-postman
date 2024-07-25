<?php

namespace Amsify42\PhpSwaggerPostman;

class Postman
{
	private $exampleValues = [];

	private $securitySchemes = [];

	private $postmanData = [
		'info' => [
			'_postman_id' 	=> '{{$guid}}',
			'name' 		=> 'Postman - Collection',
			'schema' 	=> 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
		],
		'item' => [
			// API Request Items
		],
		'event' => [
			// [
			// 	'listen' => 'prerequest',
			// 	'script' => [
			// 		'type' => 'text/javascript',
			// 		'exec' => [
			// 			''
			// 		]
			// 	]
			// ]
		]
	];

	function __construct($exampleValues=[])
	{
		$this->exampleValues = $exampleValues;
	}

	public function generate($swaggerData)
	{
		$postmanData = NULL;
		if($swaggerData && sizeof($swaggerData)> 0)
		{
			$this->postmanData['info']['name'] = $swaggerData['info']['title'];
			$paths = (isset($swaggerData['paths']) && sizeof($swaggerData['paths'])> 0)? $swaggerData['paths']: [];
			$this->securitySchemes = (isset($swaggerData['components']['securitySchemes']) && sizeof($swaggerData['components']['securitySchemes'])> 0)? $swaggerData['components']['securitySchemes']: [];
			foreach($paths as $route => $endpoints)
			{
				if($endpoints && sizeof($endpoints) > 0)
				{
					foreach($endpoints as $method => $endpoint)
					{
						$this->addItem($route, $method, $endpoint);
					}
				}
			}
			$postmanData = $this->postmanData;
		}
		return $postmanData;
	}

	public function generateEnv($baseURL)
	{
		$values = [
			[
				'key' 		=> 'baseURL',
				'value' 	=> ($baseURL? $baseURL: ''),
				'type' 		=> 'default',
				'enabled' 	=> true
			]
		];
		foreach($this->securitySchemes as $type => $securityScheme)
		{
			$values[] = [
				'key' 		=> $type,
				'value' 	=> '',
				'type' 		=> 'default',
				'enabled' 	=> true
			];
		}

		return [
			'id' => '{{$guid}}',
			'name' => $this->postmanData['info']['name'].' - Environment',
			'values' => $values
		];
	}

	private function addItem($route, $method, $endpoint)
	{
		$uri 		 = $route;
		$headers 	 = [];
		$routeParams = [];
		$queryParams = [];
		/**
		 * For route and query params
		 */
		$addIsTest 		= ($method == 'get')? true: false;
		$urlParameters 	= (isset($endpoint['parameters']) && sizeof($endpoint['parameters'])> 0)? $endpoint['parameters']: [];
		$securities 	= (isset($endpoint['security']) && sizeof($endpoint['security'])> 0)? $endpoint['security']: [];
		if(sizeof($urlParameters)> 0)
		{
			foreach($urlParameters as $upk => $urlParameter)
			{
				if($urlParameter['in'] == 'path')
				{
					$uri = str_replace("{".$urlParameter['name']."}", ":".$urlParameter['name'], $uri);
					$routeParams[] 	= [
						'key' 			=> $urlParameter['name'],
						'value' 		=> $this->getExampleValue($urlParameter, $urlParameter['name']),
						'description'  	=> isset($urlParameter['description'])? $urlParameter['description']: '',
						'disabled' 		=> false
					];
				}
				else if($urlParameter['in'] == 'query')
				{
					$description = isset($urlParameter['description'])? $urlParameter['description']: '';
					if($urlParameter['required'])
					{
						$description = '(Required) '.$description;
					}
					$queryParams[] 	= [
						'key' 			=> $urlParameter['name'],
						'value' 		=> $this->getExampleValue($urlParameter, $urlParameter['name']),
						'description' 	=> trim($description),
						'disabled' 		=> !$urlParameter['required']
					];
				}
				else if($urlParameter['in'] == 'header')
				{
					$description = isset($urlParameter['description'])? $urlParameter['description']: '';
					if($urlParameter['required'])
					{
						$description = '(Required) '.$description;
					}
					$headers[] 	= [
						'key' 			=> $urlParameter['name'],
						'value' 		=> $this->getExampleValue($urlParameter, $urlParameter['name']),
						'description' 	=> trim($description),
						'disabled' 		=> !$urlParameter['required']
					];
				}
			}
		}

		if(sizeof($securities) > 0)
		{
			foreach($securities as $security)
			{
				$securityName = key($security);
				$securityHeader = $this->checkHeaderSecurity($securityName);
				if($securityHeader)
				{
					$headers[] 	= [
						'key' 		=> $securityHeader['name'],
						'value' 	=> "{{".$securityName."}}",
						'description' 	=> $securityHeader['description'],
						'disabled' 	=> false
					];
				}
			}
		}

		$item = [
			'name' => (isset($endpoint['summary']) && $endpoint['summary'])? $endpoint['summary']: (isset($endpoint['description'])? $endpoint['description']: ''),
			'request' => [
				'method' => $method,
				'header' => $headers,
				'url' 	 => [
					'raw'  => '{{baseURL}}'.$uri,
					'host' => ['{{baseURL}}'],
					'path' => explode('/', trim($uri, '/'))
				],
			] 
		];

		if(sizeof($routeParams)> 0)
		{
			$item['request']['url']['variable'] = $routeParams;
		}
		if(sizeof($queryParams)> 0)
		{
			$this->sortParams($queryParams);
			$item['request']['url']['query'] = $queryParams;
		}

		/**
		 * For request body
		 */
		if($method == 'post' || $method == 'put')
		{
			$mode 		= isset($endpoint['requestBody']['content']['multipart/form-data'])? 'formdata': 'raw';
			$properties = [];
			$required 	= [];
			if($mode == 'formdata')
			{
				$properties = $endpoint['requestBody']['content']['multipart/form-data']['schema']['properties']?? [];
				$required 	= $endpoint['requestBody']['content']['multipart/form-data']['schema']['required']?? [];
			}
			else
			{
				$properties = $endpoint['requestBody']['content']['application/json']['schema']['properties']?? [];
				$required 	= $endpoint['requestBody']['content']['application/json']['schema']['required']?? [];
			}

			$params = [];
			$notReq = [];
			if(sizeof($properties) > 0)
			{
				foreach($properties as $field => $property)
				{
					$value = $this->getExampleValue($property, $field);
					
					$isRequired = in_array($field, $required);	
					if($mode == 'formdata')
					{
						$description = isset($property['description'])? $property['description']: '';
						if($isRequired)
						{
							$description = '(Required) '.$description;
						}
						$params[] = [
							'key' 			=> $field,
							'value' 		=> $value,
							'description' 	=> trim($description),
							'type' 			=> ($property['type'] == 'file')? 'file': 'text',
							'disabled' 		=> !$isRequired
						];
					}
					else
					{
						if($isRequired)
						{
							$params[$field] = $value;
						}
						else
						{
							$notReq[$field] = $field;
						}
					}
				}	
			}


			$item['request']['body'] = [
					'mode' => $mode
				];
			if($mode == 'raw')
			{
				$prettyJson = json_encode(array_merge($params, $notReq), JSON_PRETTY_PRINT);
				foreach($notReq as $field => $value)
				{
					$prettyJson = str_replace("\"{$field}\":", "//\"{$field}\":", $prettyJson);
				}
				$item['request']['body']['raw'] = $prettyJson;
				$item['request']['body']['options'] = [
					'raw' => ['language' => 'json']
				];
			}
			else 
			{
				$this->sortParams($params);
				$item['request']['body']['formdata'] = $params;
			}
		}

		$this->postmanData['item'][] = $item;
	}

	private function checkHeaderSecurity($name)
	{
		if(isset($this->securitySchemes[$name]) && $this->securitySchemes[$name]['in'] == 'header')
		{
			return [
				'name' => $this->securitySchemes[$name]['name'],
				'description' => isset($this->securitySchemes[$name]['description'])? $this->securitySchemes[$name]['description']: ''
			];
		}
		return NULL;
	}

	private function sortParams(&$params)
	{
		usort($params, function ($a, $b) {
            if($a['disabled'] === $b['disabled']) {
		        return 0;
		    }
		    return $a['disabled'] < $b['disabled'] ? -1 : 1;
        });
	}

	private function getExampleValue($property, $name=NULL)
	{
		if(isset($this->exampleValues[$name]))
		{
			return $this->exampleValues[$name];
		}
		$enum = isset($property['enum'])? $property['enum']: NULL;
		if($enum)
		{
			return $enum[array_rand($enum)];
		}
		$value 		= '';
		$type  		= (isset($property['schema']['type']) && $property['schema']['type'])? $property['schema']['type']: (isset($property['type'])? $property['type']: ''); 
		$example  	= (isset($property['schema']['example']) && $property['schema']['example'])? $property['schema']['example']: (isset($property['example'])? $property['example']: ''); 
		if($example)
		{
			if(is_array($example))
			{
				return $example;
			}
			else
			{
				$exampleArr = explode(':', $example);
				$value = (isset($exampleArr[1]) && $exampleArr[1])? trim($exampleArr[1]): trim($example);
			}
		}
		else if($type == 'integer' || $name == 'id' || ($name && strpos($name, 'Id') !== false))
		{
			$value = "42";
		}
		else if($type == 'string')
		{
			if($name)
			{
				$value = $this->getExampleByName($name);
			}
			else
			{
				$value = 'lorem_ipsum';
			}
		}
		else if($type == 'file')
		{
			$value = "";
		}
		return $value;
	}

	private function getExampleByName($name)
	{
		if(isset($this->exampleValues[$name]))
		{
			return $this->exampleValues[$name];
		}
		$value = 'example';
		if(stripos($name, 'is_') !== false)
		{
			$value = '1';
		}
		else if(stripos($name, 'email') !== false)
		{
			$value = 'example@email.com';
		}
		else if(stripos($name, 'currency') !== false)
		{
			$value = 'usd';
		}
		else if(stripos($name, 'amount') !== false)
		{
			$value = '100';
		}
		else if(stripos($name, 'date') !== false)
		{
			$value = '2022-04-02';
		}
		else if(stripos($name, 'url') !== false)
		{
			$value = 'http://site.com/page';
		}
		else if(stripos($name, 'description') !== false || stripos($name, 'overview') !== false)
		{
			$value = 'Some description about...';
		}
		else if(stripos($name, '_id') !== false)
		{
			$value = '42';
		}
		return $value;
	}
}
