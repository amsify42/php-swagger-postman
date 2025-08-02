<?php

namespace Amsify42\PhpSwaggerPostman\Swagger;

class Attribute
{
	private $method   = 'get';

	private $isLoaded = false;

	private $security = [];

	private $headers = [];

	private $responses = [
		[
			'code' => 200,
			'description' => 'success'
		]
	];

	private $bodyData = [];

	private $rulesCallback = NULL;

	public function setSecurity(array|string $security)
	{
		$this->security = is_array($security)? [...$this->security, ...$security]: [...$this->security, $security];
		return $this;
	}

	public function setHeader(array|string $header)
	{
		$this->headers = is_array($header)? [...$this->headers, ...$header]: [...$this->headers, $header];
		return $this;
	}

	public function checkRules($rulesCallback=NULL)
	{
		$this->rulesCallback = $rulesCallback;
	}

	public function setResponse($response)
	{
		$this->responses = is_array($response)? [...$this->responses, ...$response]: [...$this->responses, $response];
		return $this;
	}

	public function setSuccessData($data=NULL)
	{
		$data = json_decode(json_encode($data), true);
		$successDataContent = "";
		if($data)
		{
		    if(array_key_exists('jsonapi', $data)){
                	$successDataContent .= "new OA\MediaType(\n\t\tmediaType: \"application/vnd.api+json\",\n\t\tschema: new OA\Schema(\n";
			$successDataContent .= $this->createObjectOrArrayProperty($data, NULL, "\t\t\t");
			$successDataContent .= "\n\t\t)\n\t)";
	            } else {
	                $successDataContent .= "new OA\JsonContent(\n";
	                $successDataContent .= $this->createObjectOrArrayProperty($data);
	                $successDataContent .= "\n\t)";
	            }
		}
		if($successDataContent) {
			$this->responses[0]['content'] = $successDataContent;	
		}
	}

	public function createObjectOrArrayProperty($data, $name=NULL, $indent="\t\t")
	{
		$property = ($name)? "new OA\Property(\n{$indent}property: \"{$name}\",\n": "";

		if(empty($data)) $data = [1];

		$isArray = (isset($data[0]))? true: false;
		$isArrOfObject = false;

		if($isArray)
		{
			$property .= "{$indent}type: \"array\",\n{$indent}__EXAMPLE__\n{$indent}items: new OA\Items(";
		}
		else
		{
			$property .= "{$indent}type: \"object\",\n{$indent}__EXAMPLE__\n{$indent}properties: [";
		}

		foreach($data as $dataKey => $value)
		{
			if($isArray)
			{
				if(is_array($value))
				{
					$isArrOfObject = true;
					$property .= "\n{$indent}\tproperties:[";
					foreach($value as $propertyName => $propertyValue)
					{
						if(is_array($propertyValue))
						{
							$property .= "\n {$indent}\t\t".$this->createObjectOrArrayProperty($propertyValue, $propertyName, $indent."\t\t\t").",";
						}
						else
						{
							$propType = "";
							if(Data::isInt($propertyValue))
							{
								$propType = "\n{$indent}\t\t\ttype:\"integer\",\n{$indent}\t\t\tformat:\"int64\",";
							}
							else if(is_bool($propertyValue))
							{
								$propType = "\n{$indent}\t\t\ttype:\"boolean\",";
							}
							else
							{
								$propType = "\n{$indent}\t\t\ttype:\"string\",";
							}
							$property .= "\n{$indent}\t\tnew OA\Property(\n{$indent}\t\t\tproperty: \"{$propertyName}\",{$propType}\n{$indent}\t\t\texample:".(is_bool($propertyValue)? ($propertyValue === true? ' true': ' false'): "\"".$propertyValue."\"")."\n{$indent}\t\t),";
						}
					}
					$property = rtrim($property, ',');
					$property .= "\n{$indent}\t],";
				}
				else
				{
					if(is_array($value))
					{
						$property .= "\n{$indent}\t".$this->createObjectOrArrayProperty($value, $property, $indent."\t\t").",";
					}
					else
					{
						if(Data::isInt($value))
						{
							$property = str_replace('__EXAMPLE__', "example:[".implode(',', $data)."],", $property);
							$property .= "\n{$indent}\ttype:\"integer\",\n{$indent}\tformat:\"int64\"";
						}
						else
						{
							$property = str_replace('__EXAMPLE__', "example:[\"".implode('","', $data)."\"],", $property);
							$property .= "\n{$indent}\ttype:\"string\"";
						}
					}
				}
				break;
			}
			else
			{
				if(is_array($value))
				{
					$property .= "\n{$indent}\t".$this->createObjectOrArrayProperty($value, $dataKey, $indent."\t\t").",";
				}
				else
				{
					$propType = "";
					if(Data::isInt($value))
					{
						$propType = "\n{$indent}\t\ttype:\"integer\",\n{$indent}\t\tformat:\"int64\",";
					}
					else if(is_bool($value))
					{
						$propType = "\n{$indent}\t\ttype:\"boolean\",";
					}
					else
					{
						$propType = "\n{$indent}\t\ttype:\"string\",";
					}
					$property .= "\n{$indent}\tnew OA\Property(\n{$indent}\t\tproperty: \"{$dataKey}\",{$propType}\n{$indent}\t\texample:".(is_bool($value)? ($value === true? ' true': ' false'): "\"".$value."\"")."\n{$indent}\t),";
				}
			}
		}
		$property = str_replace("__EXAMPLE__\n{$indent}", '', $property);
		$property = rtrim($property, ',');
		$property .= "\n{$indent}".($isArray? ($isArrOfObject? ")": ""): "]");
		return $property.($isArray && $isArrOfObject === false? ")\n".preg_replace('/\t/', '', $indent, 1).")": ($name? "\n".substr($indent, 0, strlen($indent)-1).")": ""));
	}

	public function generate($rules=[], $routeParams=[])
	{
		$method  = isset($_SERVER['REQUEST_METHOD'])? $_SERVER['REQUEST_METHOD']: 'GET';
		$lMethod = strtolower($method);

		$this->method = $lMethod;

		$routeURI= isset($_SERVER['REQUEST_URI'])? strtok($_SERVER["REQUEST_URI"], '?'): '/';
		$qParams = ($lMethod == 'get')? $rules: [];

		$paramsStr = '';
		$bodyStr   = '';
		$hasRParams= false;

		if(!empty($this->headers))
		{
			foreach($this->headers as $headerKey => $headerValue)
			{
				$paramsStr .= $this->createParameter($headerKey, $headerValue, 'header', (Data::isInt($headerValue)? true: false), false, true);
			}
		}

		if(sizeof($routeParams)> 0)
		{
			$hasRParams = true;
			foreach($routeParams as $pName => $pValue)
			{
				$routeURI = str_replace('/'.$pValue, '/{'.$pName.'}', $routeURI);
				$paramsStr .= $this->createParameter($pName, $pValue, 'path', (Data::isInt($pValue)? true: false), false, true);
			}
		}
		if(sizeof($qParams) > 0)
		{
			foreach($qParams as $param => $rule)
			{
				$ruleArr = is_string($rule)? explode('|', $rule): $rule;
				$isReq   = false;
				$isInt   = false;
				$isTinyInt = false;
				$enumValues = NULL;
				foreach($ruleArr as $rk => $ru)
				{
					$this->checkCallback($param, $ru, $isTinyInt, $enumValues);
					if($ru == 'required')
					{
						$isReq = true;
					}
					if($ru == 'integer')
					{
						$isInt = true;
					}
				}
				$defVal = $this->has($param)? $this->get($param): '';
				$paramsStr .= $this->createParameter($param, $this->randomValue($param, $isInt, $isTinyInt, $defVal, $enumValues), 'query', $isInt, $isTinyInt, $isReq, $enumValues);
			}
		}

		if($lMethod != 'get' && sizeof($rules)> 0)
		{
			$mediaType = ($lMethod == 'post' && !empty($_POST))? 'multipart/form-data': ((isset($_SERVER["CONTENT_TYPE"]) && $_SERVER['CONTENT_TYPE'])? $_SERVER['CONTENT_TYPE']: 'application/json');

			$bodyStr .= "\n#[OA\RequestBody(\n\tcontent: new OA\MediaType(\n \t\tmediaType:\"".$mediaType."\",\n \t\tschema: new OA\Schema(\n";

			$reqArr = [];
            $properties = '';
			foreach($rules as $param => $rule)
			{
				$isFile       = false;
				$isInt        = false;
				$isTinyInt    = false;
				$enumValues   = NULL;
				$ruleArr      = is_string($rule)? explode('|', $rule): $rule;
				$propertyStr = NULL;
				foreach($ruleArr as $rk => $ru)
				{
					$propertyStr = $this->checkCallback($param, $ru, $isTinyInt, $enumValues);
					if($propertyStr !== NULL)
					{
						break;
					}
					if($ru == 'required')
					{
						$reqArr[] = '"'.$param.'"';
					}
					if($ru == 'integer')
					{
						$isInt = true;
					}
					if($ru == 'file' || $ru == 'image')
					{
						$isFile = true;
					}
				}
				if($propertyStr !== NULL)
				{
					$properties .= ($propertyStr? "\n \t\t\t\t".$propertyStr.",": "");
					continue;	
				}
				$defVal   = $this->has($param)? $this->input($param): '';
				$properties .= $this->createProperty($param, $this->randomValue($param, $isInt, $isTinyInt, $defVal, $enumValues), $isInt, $isTinyInt, $isFile, $enumValues);
			}
            $properties = rtrim($properties, ',');
			$bodyStr .= "\t\t\trequired:[".implode(',', $reqArr)."],\n\t\t\tproperties: [".$properties."\n\t\t\t]";
			$bodyStr .= "\n \t\t)\n \t)\n)]";
		}

		$operationId    = preg_replace('@\{.*?\}@', '', $routeURI);
		$operationId    = str_replace(['/', '-'], ' ', $operationId);
		$title          = trim(ucwords($operationId));
		$title          = trim(str_replace('  ', ' ', $title));
		$operationId    = trim(str_replace(' ', '', $title));

		$fName = $this->firstName($title);

		if($fName == $title)
		{
			$action = '';
			if($lMethod == 'post')
			{
				$action = ($hasRParams)? 'Update': 'Create';
			}
			else if($lMethod == 'put')
			{
				$action = 'Update';
			}
			else if($lMethod == 'get')
			{
				$action = ($hasRParams)? 'Details': 'List';
			}
			else if($lMethod == 'delete')
			{
				$action = 'Delete';
			}

			if($action)
			{
				$operationId   = strtolower($action).$title;
				$title         = $title.' - '.$action;
			}
		}

		$secuirtyStr = "";
		if(!empty($this->security))
		{
			$secuirtyStr .= ",\n\tsecurity: [[";
			foreach($this->security as $security)
			{
				$secuirtyStr .= "\n\t\t\"{$security}\" => [],";
			}
			$secuirtyStr = rtrim($secuirtyStr, ',');
			$secuirtyStr .= "\n\t]]\n";
		}

		$annotation = "#[OA\\".ucfirst($lMethod)."(\n \tpath:\"{$routeURI}\",\n \ttags:[\"{$fName}\"],\n \tsummary:\"{$title}\",\n \tdescription:\"{$title}\",\n \toperationId:\"{$operationId}\"{$secuirtyStr})]{$paramsStr}{$bodyStr}".$this->createResponses();

		return $annotation;
	}

	private function checkCallback($name, $rule, &$isTinyInt, &$enumValues)
	{
		if($this->rulesCallback && is_callable($this->rulesCallback))
		{
			$result = $this->rulesCallback->__invoke($name, $rule);
			if($result)
			{
				if(isset($result['enum']))
				{
					$enumValues = $result['enum'];
				}
				else if(isset($result['tinyint']))
				{
					$isTinyInt = true;
				}
				else if(isset($result['property']))
				{
					return $result['property'];
				}
			}
		}
		return NULL;
	}

	public function createParameter($name, $value, $in, $isInt=false, $isTinyInt=false, $required=false, $enumValues=NULL)
	{
		$reqStr= ($in == 'path' || $required)? 'true': 'false';
		$enum  = ($isTinyInt)? "\n \t\tenum:[\"0\",\"1\"],": "";
		$enum  = ($enumValues !== NULL)? "\n \t\tenum:[\"".implode('","', $enumValues)."\"],": $enum;

		$schema = "\n \tschema: new OA\Schema(";
		if(is_array($value))
		{
			// Check if it's an associative array (object-like) or indexed array
        	$isAssoc = array_keys($value) !== range(0, count($value) - 1);

			if($isAssoc) {
				$schema .= "\n".$this->createObjectOrArrayProperty($value, NULL, "\t\t")."\n\t)";
			} else {
				$isInt = isset($value[0]) && Data::isInt($value[0])? true: false;		
				$type = ($isInt)? "\n\t\t\ttype:\"integer\",\n\t\t\tformat:\"int32\"": "\n\t\t\ttype:\"string\"";
				$schema .= "\n\t\ttype: \"array\",\n\t\texample:[".($isInt? implode(",", $value): "\"".implode("\",\"", $value)."\"")."],\n\t\titems: new OA\Items({$type}\n\t\t)\n\t)";
			}
		}
		else
		{
			$schema = ($isTinyInt === false && $isInt)?
				"{$schema}\n \t\ttype:\"integer\",\n \t\tformat:\"int64\",\n \t\texample:\"".$value."\"\n \t)"
				: "{$schema}\n \t\ttype:\"string\",".$enum."\n \t\texample:\"".$value."\"\n \t)";
		}

		return "\n#[OA\Parameter(\n \tname:\"".$name."\",\n \tin:\"".$in."\",\n \tdescription:\"".$name."\",\n \trequired:".$reqStr.",".$schema."\n)]";
	}

	public function createProperty($name, $value, $isInt=false, $isTinyInt=false, $isFile=false, $enumValues=NULL)
	{
		if($value && (is_array($value) || is_object($value)))
		{
			return "\n \t\t\t\t".$this->createObjectOrArrayProperty($value, $name, "\t\t\t\t\t").",";
		}

		$type     = "string";
		$format   = '';
		if($isInt)
		{
			$type    = "integer";
			$format  = ",\n \t\t\t\t\tformat:\"int64\"";
		}

		if($isTinyInt)
		{
			$type    = "string";
			$format  = ",\n \t\t\t\t\tenum:[\"1\",\"0\"]";
		}

		if($isFile)
		{
			$type = 'file';
		}

		if($enumValues !== NULL)
		{
			$type    = "string";
			$format  = ",\n \t\t\t\t\tenum:[\"".implode('","', $enumValues)."\"]";
		}
		if(is_array($value) || is_object($value))
		{
			$value = '';
		}

		return "\n \t\t\t\tnew OA\Property(\n \t\t\t\t\tproperty:\"".$name."\",\n \t\t\t\t\tdescription:\"".$name."\",\n \t\t\t\t\ttype:\"".$type."\"".$format.",\n \t\t\t\t\texample:\"".$value."\"\n \t\t\t\t),";
	}

	public function randomValue($param, $isInt=false, $isTinyInt=false, $default='')
	{
		$param = trim(strtolower($param));

		if($isTinyInt)
		{
			return '1';
		}

		if($isInt)
		{
			return '10';
		}

		if(stripos($param, 'email') !== false)
		{
			return 'example@email.com';
		}

		if(stripos($param, 'currency') !== false)
		{
			return 'usd';
		}

		if(stripos($param, 'amount') !== false)
		{
			return '10';
		}

		if(stripos($param, 'date') !== false)
		{
			return '2022-04-02';
		}

		if(stripos($param, 'url') !== false)
		{
			return 'http://site.com/page';
		}

		if(stripos($param, 'description') !== false || stripos($param, 'overview') !== false)
		{
			return 'Some info about...';
		}

		return $default;
	}

	private function firstName($name='')
	{
		$firstName 	= '';
		$name 		= trim($name);
		if($name)
		{
			$nameArr = explode(' ', $name);
			if(sizeof($nameArr)> 0)
			{
				foreach($nameArr as $nk => $nArr)
				{
					$str = trim($nArr);
					if($str && strlen($str) > 2)
					{
						$firstName = $str;
						break;
					}
				}
				if(!$firstName)
				{
					$firstName = trim($nameArr[0]);
				}
			}
		}
		return $firstName;
	}

	private function has($key)
	{
		if($this->method == 'get')
		{
			return isset($_GET[$key])? true: false; 
		}
		else
		{
			$this->loadBody();
			if($this->method == 'post')
			{
				return isset($_POST[$key])? true: (isset($this->bodyData[$key])? true: false);
			}
			else
			{
				isset($this->bodyData[$key])? true: false;	
			}
		}
	}

	private function get($key)
	{
		return isset($_GET[$key])? $_GET[$key]: NULL;
	}

	private function input($key)
	{
		if($this->method == 'get')
		{
			return $this->get($key); 
		}
		else
		{
			$this->loadBody();
			if($this->method == 'post')
			{
				return isset($_POST[$key])? $_POST[$key]: (isset($this->bodyData[$key])? $this->bodyData[$key]: NULL);
			}
			else
			{
				isset($this->bodyData[$key])? $this->bodyData[$key]: NULL;
			}
		}
	}

	private function loadBody()
	{
		if($this->isLoaded === false)
		{
			$this->isLoaded = true;
			$this->bodyData = json_decode(file_get_contents("php://input"), true);
		}
	}

	private function createResponses()
	{
		$responsesStr = "";
		foreach($this->responses as $response)
		{
			$responsesStr .= "\n#[OA\Response(\n \tresponse:\"{$response['code']}\"";

			if(isset($response['ref']) && $response['ref'])
			{
				$responsesStr .= ",\n\tref:\"{$response['ref']}\"";
			}

			if(isset($response['description']) && $response['description'])
			{
				$responsesStr .= ",\n\tdescription:\"{$response['description']}\"";
			}

			if(isset($response['content']) && $response['content'])
			{
				$responsesStr .= ",\n\tcontent: {$response['content']}";
			}
			$responsesStr .= "\n)]";
		}
		return $responsesStr;
	}
}
