# PHP Swagger Postman

### Scanning the directory and generating swagger json for API documentation.
```php
$swagger = new \Amsify42\PhpSwaggerPostman\Swagger;
$swagger->getGeneratedJson(
    "path/to/directory",
    "path/to/export-swagger-json"
);
```
### For generating enpoint specific Annotation, you can call this method
```php
$annotation = new \Amsify42\PhpSwaggerPostman\Swagger\Annotation();
echo $annotation->generate();
```

#### You can also pass parameter rules and route params also with key value pair to get it added to the annotation
```php
echo $annotation->generate(
  ['name' => 'required', 'address_id' => 'reqiured|integer'],
  ['userId' => 42]
);
```
#### For generating response data in annotation
```php
$annotation->setSuccessData(['id' => 42, 'name' => 'SomeUser']);
echo $annotation->generate();
```
#### You can also pass callback for checking rules and decide the param needs to have TinyInt or Enum Values or Custom Property Annotation
```php
$annotation->checkRules(
                function ($name, $rule) {
                    if ($rule == 'some_custom_rule') {
                        return [
                            'property' => // Pass some custom property annotation syntax
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
Note: By default `NULL` needs to be passed

#### For adding security/header/response
```php
$annotation
            ->setSecurity('XApiKey') // The XApiKey will be from the security annotation you already added
            ->setHeader(['Auth-Key' => 'some_key'])
            ->setResponse([
                [
                    'code' => 422,
                    'ref' => '#/components/responses/Validation' // Validation here is the name of response annotation which is already defined somewhere in annotation
                ]
            ]);
```

#### For generating _Attribute_ instead of _Annotation_
```php
$attribute = new Attribute();
echo $attribute->generate();
```
_Note:_ You can use all the method with **Attribute** which is mentioned above for **Annotation** but _Attribute_ requires minimum php `8.2` version.


### Testing
```
./vendor/bin/phpunit tests
./vendor/bin/phpunit --filter AnnotationTest
```
