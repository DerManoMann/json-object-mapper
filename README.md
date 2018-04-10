# (JSON) object mapper
A simple library to deserialize JSON into (nested) PHP  arrays / objects.

## Examples
````
class MyClass 
{
    protected $foo;
    
    public function getFoo()
    {
        return $this->foo;
    }
    
    public function setFoo($foo) 
    {
        $this->foo = $foo;
    }
}

    
$objectMapper = new \Radebatz\ObjectMapper\ObjectMapper();

$json = '{"foo":"bar"}';

/** @var \MyData $myData */
$myData = $objectMapper->map($json, \MyData::class);

echo $myData->getFoo(); // echo's 'bar'
````
