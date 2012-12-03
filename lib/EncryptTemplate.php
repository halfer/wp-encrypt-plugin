<?php

class EncryptTemplate
{
	protected $root;

	public function __construct($root)
	{
		$this->root = $root;
	}

	public function renderTemplate($template, array $params = array())
	{
		extract($params);
		require_once "{$this->root}/templates/{$template}.php";
	}

	public function renderComponent($class, $template)
	{
		require_once $this->root . '/components/' . $class . '.php';
		$obj = new $class($this->root);
		$templateVars = $obj->execute();
		$this->renderTemplate('_' . $template, $templateVars);
	}
}