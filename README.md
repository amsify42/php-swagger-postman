# PHP Swagger Postman

```
composer require amsify42/php-swagger-postman
```
### For generating enpoint specific Attribute, you can call this method
```php
$attribute = new \Amsify42\PhpSwaggerPostman\Swagger\Attribute();
echo $attribute->generate();
```

#### You can also pass parameter rules and route params also with key value pair to get it added to the attribute
```php
echo $attribute->generate(
  ['name' => 'required', 'address_id' => 'reqiured|integer'],
  ['userId' => 42]
);
```
#### For generating response data in attibute
```php
$attribute->setSuccessData(['id' => 42, 'name' => 'SomeUser']);
echo $attribute->generate();
```
#### You can also pass callback for checking rules and decide the param needs to have TinyInt or Enum Values or Custom Property Attribute
```php
$attribute->checkRules(
                function ($name, $rule) {
                    if ($rule == 'some_custom_rule') {
                        return [
                            'property' => // Pass some custom property attribute syntax
                        ];
                    } else if ($rule == 'enum') {
                        return  [
                            'enum' => ['paid', 'unpaid']
                        ];
                    } else if ($rule == 'boolean') {
                        return  [
                            'tinyint' => true
                        ];
                    }
                    return NULL;
                }
            );
```
By default `NULL` needs to be return if array of tinyint/enum/property is not return and if you want to generate property for a param which might be having simple or nested array
```php
$attribute->checkRules(
                function ($name, $rule) use($attribute) {
                    if ($rule == 'array') {
                        return [
                            'property' => $attribute->createObjectOrArrayProperty(
                                $_POST[$name], // or $_GET[$name] or value from body data
                                $name
                              )
                        ];
                    }
                    return NULL;
                }
            );
```

#### For adding security
```php
$attribute->setSecurity('XApiKey'); // The XApiKey will be from the security attribute already added
```
Example security attribute already added
```php
#[OA\SecurityScheme(
    securityScheme: "XApiKey",
    type: "apiKey",
    in: "header",
    name: "X-Api-Key"
)]
```

#### For adding header with static value
```php
$attribute->setHeader(['Auth-Key' => 'some_key']);
```

#### For adding response which already defined
```php
$attribute->setResponse([
                [
                    'code' => 422,
                    'ref' => '#/components/responses/Validation' // Validation here is the name of response which is already defined somewhere
                ]
            ]);
```
Example Validation already defined
```php
#[OA\Response(
    response: "Validation",
    description: "Validation Errors",
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: "message",
                example: "Please check the inputs provided"
            ),
            new OA\Property(
                property: "errors",
                type: "object"
            )
        ]
    )
)]
```

#### For generating _Annotation_ instead of _Attribute_
```php
$annotation = new \Amsify42\PhpSwaggerPostman\Swagger\Annotation();
echo $annotation->generate();
```
_Notes:_
1. You can use all the method with **Annotation** which is mentioned above for **Attribute** but _Attribute_ requires minimum php `8.2` version.
2. For using Annotation with latest `swagger-php` installed, you may have to separately install `doctrine/annotations`
```
composer require doctrine/annotations
```
Refer to this link for more details
https://github.com/zircote/swagger-php/blob/master/docs/guide/migrating-to-v4.md

### Scanning the directory and generating swagger json for API documentation and postman collection and postman environment json.
```php
$swagger = new \Amsify42\PhpSwaggerPostman\Swagger;
$swagger->getGeneratedJson(
    "path/to/scan-directory",
    "path/to/export-swagger-and-postman-json/"
);
```
**Note:** Make sure to have `OA/Info` and at least one API endpoint already added before running `$swagger->getGeneratedJson()` method.
```php
use OpenApi\Attributes as OA;

#[OA\Info(title: "My API", version: "1.0.0")]

#[OA\Get(path:"/api/users")]
#[OA\Response(response:"200", description:"An example endpoint")]
```
**Annotation Example**
```php
/**
 * @OA\Info(
 *   version="1.0.0",
 *   title="My API"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/users",
 *     @OA\Response(response="200", description="An example endpoint")
 * )
 */

```
If you want postman environment to have baseURL variable value, you can either set like this
```php
$swagger = new \Amsify42\PhpSwaggerPostman\Swagger('http://www.site.com');
$swagger->getGeneratedJson(
    "path/to/scan-directory",
    "path/to/export-swagger-and-postman-json/"
);
```
or define at least one server in Attribute
```php
#[OA\Server(
    url: 'http://www.site.com',
    description: 'some description about site'
)]
```
or in Annotation
```php
/**
 * @OA\Server(
 *     url="http://www.site.com",
 *     description="some description about site"
 * )
 */
```
for adding environment suffix in postman environment file name, you can pass it in second parameter of Swagger Constructor
```php
$swagger = new \Amsify42\PhpSwaggerPostman\Swagger('http://www.site.com', 'Local');
```
