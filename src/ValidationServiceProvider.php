<?php namespace Iyoworks\Validation;

use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider {
	
	public function boot()
	{	
		BaseValidator::$factory = $this->app['validator'];
	
		$this->app['validator']->resolver(function($translator, $data, $rules, $messages)
		{
			return new Validator($translator, $data, $rules, $messages);
		});
	}

	public function register()
	{
		
	}
}
