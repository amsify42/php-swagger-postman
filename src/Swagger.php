<?php

namespace Amsify42\PhpSwaggerPostman;

use OpenApi\Generator;
use Amsify42\PhpSwaggerPostman\Swagger\Annotation;
use Amsify42\PhpSwaggerPostman\Postman;
use Exception;

class Swagger
{
	private $docPrefix = 'doc-';

    public static function generateAnnotation()
    {
        $annotation = new Annotation();
        return $annotation->generate();
    }

	public function getGeneratedJson($scanPath, $saveTo, $fparams=[])
    {
        $filterTags = NULL;
        if(isset($fparams['filterTags']) && $fparams['filterTags'])
        {
            $filterTags = explode(',', $fparams['filterTags']);
            $filterTags = array_filter($filterTags);
        }

        $scanPath = is_array($scanPath)? $scanPath: [$scanPath];
        //var_dump($scanPath); die;
        $openapi  = Generator::scan($scanPath);
        $json     = $openapi->toJson();
        if($json)
        {
            $jsonData    = json_decode($json, true);
            $collections = [];
            if($jsonData && sizeof($jsonData)> 0)
            {
                if(isset($jsonData['paths']) && sizeof($jsonData['paths']) > 0)
                {
                    foreach($jsonData['paths'] as $route => $methods)
                    {
                        if(sizeof($methods) > 0)
                        {
                            foreach($methods as $method => $api)
                            {
                                if(sizeof($api['tags'])> 0)
                                {
                                    foreach($api['tags'] as $tk => $tag)
                                    {
                                        $sTag = trim(strtolower($tag));
                                        $sTag = str_replace(' ', '-', $sTag);
                                        $fTag = str_replace($this->docPrefix, '', $sTag);
                                        $isDoc= (strpos($sTag, $this->docPrefix) !== false);
                                        if($filterTags === NULL || ($isDoc && in_array($fTag, $filterTags)))
                                        {
                                            if($filterTags === NULL)
                                            {
                                                $fTag = 'swagger';
                                            }
                                            /**
                                             * If json not yet copied for collection
                                             */
                                            if(!isset($collections[$fTag]))
                                            {
                                                $collections[$fTag]          = $jsonData;
                                                $collections[$fTag]['paths'] = [];
                                            }

                                            $cAPI = $api;
                                            /**
                                             * Remove tager Tag from endpoint to let APIs not to appear duplicate
                                             */
                                            if($isDoc && sizeof($cAPI['tags']) > 0)
                                            {
                                                $ctKey = NULL;
                                                foreach($cAPI['tags'] as $ctk => $cTag)
                                                {
                                                    if($cTag == $tag)
                                                    {
                                                        $ctKey = $ctk;
                                                        break;
                                                    }   
                                                }
                                                if($ctKey !== NULL)
                                                {
                                                    unset($cAPI['tags'][$ctKey]);
                                                    $cAPI['tags'] = array_values($cAPI['tags']);
                                                }
                                            }

                                            $collections[$fTag]['paths'][$route][$method] = $cAPI;
                                        }
                                    }
                                }
                            }   
                        }   
                    }     
                }

                if(sizeof($collections)> 0)
                {
                    if(!is_dir($saveTo))
                    {
                        mkdir($saveTo);
                    }
                    $doneFullSwagger = false;
                    foreach($collections as $file => $jsonData)
                    {
                        $path = $saveTo.DIRECTORY_SEPARATOR.$file.'.json';
                        file_put_contents($path, json_encode($jsonData));
                        sleep(rand(0.5,1));

                        $postman 		= new Postman();
                    	$postmanData 	= $postman->generate($jsonData);
                    	if($postmanData)
                    	{
                    		$postmanData['info']['name'] = ucwords($file);
                    		file_put_contents($saveTo.DIRECTORY_SEPARATOR.$file.'.postman_collection.json', json_encode($postmanData));
                    	}
                        if($file == 'swagger')
                        {
                            $doneFullSwagger = true;
                        }
                    }
                    /**
                     * Generate postman collection if full swagger doc json is generated
                     */
                    if($doneFullSwagger)
                    {
                        //
                    }
                }
                else
                {
                    throw new Exception("No collection found to save");
                }
            }
            else
            {
                throw new Exception("Swagger json data not found");
            }  
        }
        else
        {
            throw new Exception('Swagger not generated any data');
        }
    }
}