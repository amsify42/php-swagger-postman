<?php

namespace Amsify42\PhpSwaggerPostman\Swagger;

class Annotation
{
	private $method   = 'get';

	private $isLoaded = false;

	private $bodyData = [];

	public function generate($rules=[], $routeParams=[])
	{
		$method  = isset($_SERVER['REQUEST_METHOD'])? $_SERVER['REQUEST_METHOD']: 'GET';
		$lMethod = strtolower($method);

		$this->method = $lMethod;

		$routeURI= isset($_SERVER['REQUEST_URI'])? $_SERVER['REQUEST_URI']: '/';
		$qParams = ($lMethod == 'get')? $rules: [];

		$paramsStr = '';
		$bodyStr   = '';
		$hasRParams= false;
		if(sizeof($routeParams)> 0)
		{
			$hasRParams = true;
			foreach($routeParams as $pName => $pValue)
			{
				$routeURI = str_replace($pValue, '{'.$pName.'}', $routeURI);
				$paramsStr .= $this->createParameter($pName, $pValue, true, false, false, true);
			}
		}
		if(sizeof($qParams) > 0)
		{
			foreach($qParams as $param => $rule)
			{
				$ruleArr = is_string($rule)? explode(',', $rule): $rule;
				$isReq   = false;
				$isInt   = false;
				$isTinyInt = false;
				$enumValues = NULL;
				foreach($ruleArr as $rk => $ru)
				{
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
				$paramsStr .= $this->createParameter($param, $this->randomValue($param, $isInt, $isTinyInt, $defVal, $enumValues), false, $isInt, $isTinyInt, $isReq, $enumValues);
			}
		}

		if($lMethod != 'get' && sizeof($rules)> 0)
		{
			$mediaType = ($lMethod == 'post')? 'multipart/form-data': 'application/json';

			$bodyStr .= "\n *\t@OA\RequestBody(\n *\t\t@OA\MediaType(\n *\t\t\tmediaType=\"".$mediaType."\",\n *\t\t\t@OA\Schema(";

			$reqArr = [];
			foreach($rules as $param => $rule)
			{
				$isFile       = false;
				$isInt        = false;
				$isTinyInt    = false;
				$enumValues   = NULL;
				$ruleArr      = is_string($rule)? explode('|', $rule): $rule;
				foreach($ruleArr as $rk => $ru)
				{
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
				$defVal   = $this->has($param)? $this->input($param): '';
				$bodyStr .= $this->createProperty($param, $this->randomValue($param, $isInt, $isTinyInt, $defVal, $enumValues), $isInt, $isTinyInt, $isFile, $enumValues);
			}
			$bodyStr .= "\n *\t\t\t\trequired={".implode(',', $reqArr)."}";
			$bodyStr .= "\n *\t\t\t)\n *\t\t)\n *\t),";
		}

		$operationId    = preg_replace('@\{.*?\}@', '', $routeURI);
		$operationId    = str_replace(['/', '-'], ' ', $operationId);
		$title          = trim(ucwords($operationId));
		$title          = trim(str_replace('  ', ' ', $title));
		$operationId    = trim(str_replace(' ', '', $title));
		$operationId 	= isset($operationId[0])? strtolower($operationId[0]): '';

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

		$annotation = "/**\n * @OA\\".ucfirst($lMethod)."(\n *\tpath=\"{$routeURI}\",\n *\ttags={\"{$fName}\"},\n *\tsummary=\"{$title}\",\n *\tdescription=\"{$title}\",\n *\toperationId=\"{$operationId}\",\t{$paramsStr}{$bodyStr}\n *\t@OA\Response(\n *\t\tresponse=\"200\",\n *\t\tdescription=\"source code indicates successful operation\"\n *\t),\n *\t@OA\Response(\n *\t\tresponse=\"400\",\n *\t\tdescription=\"source code indicates Validation errors\"\n *\t)";

		$annotation .= "\n * )\n **/";

		return $annotation;
	}

	public function createParameter($name, $value, $inPath=true, $isInt=false, $isTinyInt=false, $required=false, $enumValues=NULL)
	{
		$inStr = ($inPath)? 'path': 'query';
		$reqStr= ($inPath || $required)? 'true': 'false';
		$enum  = ($isTinyInt)? "\n *\t\t\tenum={\"0\",\"1\"},": "";
		$enum  = ($enumValues !== NULL)? "\n *\t\t\tenum={\"".implode('","', $enumValues)."\"},": "";

		$schema = ($isTinyInt === false && $isInt)?
		"\n *\t\t@OA\Schema(\n *\t\t\ttype=\"integer\",\n *\t\t\tformat=\"int64\",\n *\t\t\texample=\"".$value."\"\n *\t\t)"
		:      "\n *\t\t@OA\Schema(\n *\t\t\ttype=\"string\",".$enum."\n *\t\t\texample=\"".$value."\"\n *\t\t)";

		return "\n *\t@OA\Parameter(\n *\t\tname=\"".$name."\",\n *\t\tin=\"".$inStr."\",\n *\t\tdescription=\"".$name."\",\n *\t\trequired=".$reqStr.",".$schema."\n *\t),";
	}

	public function createProperty($name, $value, $isInt=false, $isTinyInt=false, $isFile=false, $enumValues=NULL)
	{
		$type     = "string";
		$format   = '';
		if($isInt)
		{
			$type    = "integer";
			$format  = ",\n *\t\t\t\t\tformat=\"int64\"";
		}

		if($isTinyInt)
		{
			$type    = "string";
			$format  = ",\n *\t\t\t\t\tenum={\"1\",\"0\"}";
		}

		if($isFile)
		{
			$type = 'file';
		}

		if($enumValues !== NULL)
		{
			$type    = "string";
			$format  = ",\n *\t\t\t\t\tenum={\"".implode('","', $enumValues)."\"}";
		}
		if(is_array($value) || is_object($value))
		{
			$value = '';
		}

		return "\n *\t\t\t\t@OA\Property(\n *\t\t\t\t\tproperty=\"".$name."\",\n *\t\t\t\t\tdescription=\"".$name."\",\n *\t\t\t\t\ttype=\"".$type."\"".$format.",\n *\t\t\t\t\texample=\"".$value."\",\n *\t\t\t\t),";
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
			return '110011';
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
			return '100';
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

	private function input($key)
	{
		if($this->method == 'get')
		{
			return isset($_GET[$key])? $_GET[$key]: NULL; 
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
}