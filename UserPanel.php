<?php
/**
 * UserPanel for Nette 2.0
 *
 * @author Mikuláš Dítě
 * @license MIT
 */

namespace Panel;
use Nette\Application\AppForm;
use Nette\Application\Control;
use Nette\Debug;
use Nette\Environment;
use Nette\IDebugPanel;
use Nette\Security\AuthenticationException;
use Nette\Templates\LatteFilter;


class UserPanel extends Control implements IDebugPanel
{

	/** @var \Nette\Web\User */
	private $user;

	/** @var array username => password */
	private $credentials = array();

	/** @var string */
	private $userColumn = 'username';



	public function __construct()
	{
		parent::__construct(Environment::getApplication()->presenter, $this->reflection->shortName);
		$this->user = Environment::getUser();
	}



	/**
	 * Renders HTML code for custom tab
	 * IDebugPanel
	 * @return void
	 */
	public function getTab()
	{
		$data = $this->getData();
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAnpJREFUeNqEU19IU1EY/927e52bWbaMQLbJwmgP0zIpffDFUClsyF56WJBQkv1RyJeo2IMPEghRQeAIoscegpBqTy6y3CDwrdzDwjCVkdqmzT+7u//O1jm3knkV/MF3z3e+8zu/7zv3O4crFotgaHC7jfHrwgKuBYPtVqt1BBx3SlNV5HK5KSmXu/N6fPxTKY+BMwvUNzY22cvFz6TIi0TXoWkaFEWBrkra+rrUtJLJTJcKCDCBZrqvyBaRCTMBnRCwKhRZFlVFuUspl0r5OwRUKXu+opxgsP8qfE4Bmk7wZV7Bg5FRqIR0m/m8OfA7K9n6bt1GvbeWlq2CKxCcPnEM1wf6sZknFXsKDF+c+dHgVKBmf4JoqmHMb/Va8OTK4vSeAhThpW9vwdsPociJ1ATD/zU7bqyZyVtdKMWHIXH0SJ3/RrWn05hn5t5jeeZN+OyQdtPMFbA77i1/f9dE7cy/+RS10G7EbRX4fL42OvQGAoFgT6uM2uPnjHhq9iNeTABjY2Mv6fR5IpGY2Cbg9XqPUr/PZrMNOJ1Oq65pfCQSwcPwK1TtE9F7OYCurgsQRbGQSqWUfD7/lPKfJZPJWc7j8ZzkeX7S5XLZHA6HIEkSqBCam5uxYqnDwf02WDeTiMVikGUZdrsdq6urOhWSCSGdFhoIud3ulrKyMiGbzRrXVqX9j8fj8Pu7UXO4EiPDIZYdNDN7F6DvhKf7+HQ6bRGoaju970bm/2CZmCXn0nAcyBn+xsbG1joTooJsbxv71LDNhUJh299lpPnFNaxt/hVjlZWCPTIar+YEQXhEzzxobk9HRyeWrC2oqhRRnplENBrd0UKa5PEfAQYAH6s95RSa3ooAAAAASUVORK5CYII=">' .
			($this->user->isLoggedIn() ? 'Logged as <span style="font-style: italic; margin: 0; padding: 0;">' . $this->getUsername() . '</span>' : 'Guest');
	}



	/**
	 * Renders HTML code for custom panel
	 * IDebugPanel
	 * @return void
	 */
	public function getPanel()
	{
		ob_start();
		$template = parent::getTemplate();

		$data = $this->getData();

		$form = $this->getComponent('login');
		if ($this->user->isLoggedIn()) {
			$form['user']->setDefaultValue($this->getUsername());
		} else {
			$form['user']->setDefaultValue('__guest');
		}

		$template->setFile(__DIR__ . '/bar.user.panel.phtml');

		$template->registerFilter(new LatteFilter());
		$template->user = $this->user;
		$template->data = $data;
		$template->userColumn = $this->userColumn;
		$template->username = $this->getUsername();
		$template->render();

		return ob_get_clean();
	}



	public function getUsername()
	{
		$data = $this->getData();
		return isset($data[$this->userColumn]) ? $data[$this->userColumn] : NULL;
	}



	/**
	 * IDebugPanel
	 * @return string
	 */
	public function getId()
	{
		return __CLASS__;
	}



	/**
	 * Registers panel to Debug bar
	 * @return \Panel\UserPanel;
	 */
	public static function register()
	{
		$panel = new self;
		Debug::addPanel($panel);
		return $panel;
	}



	private function getData()
	{
		if (method_exists($this->user->identity, 'getData')) {
			return $this->user->identity->data;
		}
		return array();
	}



	/**
	 * @param string $username default value
	 * @param string $password default value
	 * @return \Panel\UserPanel provides fluent interface
	 */
	public function addCredentials($username, $password)
	{
		$this->credentials[$username] = $password;
		return $this;
	}



	/**
	 * Sets which $user->identity->data column is supposed to be username
	 * @param string $column
	 * @return \Panel\UserPanel provides fluent interface
	 */
	public function setNameColumn($column)
	{
		$this->userColumn = $column;
		return $this;
	}



	public function getCredentialsRadioData()
	{
		$data = array();
		foreach ($this->credentials as $username => $passwor) {
			$data[$username] = \ucfirst($username);
		}
		$data['__guest'] = 'guest';
		return $data;
	}



	/**
	 * Sign in form component factory.
	 * @return Nette\Application\AppForm
	 */
	public function createComponentLogin($name)
	{
		$form = new AppForm($this, $name);

		$form->addRadioList('user', NULL, $this->getCredentialsRadioData())
			->setAttribute('class', 'onClickSubmit');

		/*
		$form->addText('username', 'Username:')
			->addRule(AppForm::FILLED, 'Please provide a username.');

		$form->addText('password', 'Password:')
			->addRule(AppForm::FILLED, 'Please provide a password.');
		/* */
		$form->addSubmit('send', 'Log in');

		$form->onSubmit[] = callback($this, 'onLoginSubmitted');
		return $form;
	}



	/**
	 * @param \Nette\Application\AppForm $form
	 */
	public function onLoginSubmitted(AppForm $form)
	{
		try {
			$values = $form->getValues();
			$username = $values['user'];
			if ($username == '__guest') {
				$this->user->logout(TRUE);
			} else {
				$password = $this->credentials[$username];
				Environment::getUser()->login($username, $password);
			}

			$this->redirect('this');
		} catch (AuthenticationException $e) {
			Environment::getApplication()->presenter->flashMessage($e->getMessage(), 'error');
			$this->redirect('this');
		}
	}
}
