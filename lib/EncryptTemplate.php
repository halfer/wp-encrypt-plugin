<?php

class EncryptTemplate
{
	protected $root;

	public function __construct($root)
	{
		$this->root = $root;

		if (method_exists($this, 'preExecute'))
		{
			$this->preExecute();
		}
	}

	public function renderTemplate($template, array $params = array())
	{
		extract($params);
		require_once "{$this->root}/templates/{$template}.php";
	}

	public function renderPartial($template, array $params = array())
	{
		return $this->renderTemplate('_' . $template, $params);
	}

	public function renderComponent($class, $template)
	{
		require_once $this->root . '/components/' . $class . '.php';
		$obj = new $class($this->root);
		$templateVars = $obj->execute();
		$this->renderTemplate('_' . $template, $templateVars);
	}

	protected function getInput($key)
	{
		return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
	}
}