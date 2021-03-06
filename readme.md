Validation
==========
Provides a base class to implement validation as a service.

BaseValidator
----------
Extend the BaseValidator to create your own validation classes.
```
use Iyoworks\Validation\BaseValidator;

class FieldValidator extends BaseValidator{

	protected $rules = [
	'name' => 'required',
	'handle' => 'required|unique:fields,handle',
	'fieldtype' => 'required',
	'id' => 'exists:fields,id'
	];
		
	//executed before validation
	protected function preValidate() {
       	if($fieldtype = $this->get('fieldtype'))
		{
			$this->runner->addDynamic('fieldtype', 'exists', function($value){
			  return app('fieldtypes')->exists($value);
			}, 'Invalid fieldtype given for :attribute attribute');
		}
	}

	//executed before validation on insert mode	
	protected function preValidateOnInsert() {}

	//executed before validation on update mode
	protected function preValidateOnUpdate() {
		if($this->get('handle'))
            $this->setUnique('handle'); // the id will be appended to the rule
	}

	//executed before validation on delete mode
	protected function preValidateOnDelete() {}
}

```
Next instantiate your validator
```
$validator = new FieldValidator;

$data = ['name' => 'Title', 'order' => 2];

//set the mode and pass your data object
if($validator->validForInsert($data)){
    //your logic
} else {
	$this->errors = $this->validator->errors();
    //more logic
}
```
