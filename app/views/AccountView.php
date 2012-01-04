<?php
class AccountView extends BaseView
{
	public function prepareResponse($vars = array())
	{
		return new UIKit\Framework\HTTPFoundation\Response\HTTPResponse($this->render->render2("/account/" . $this->template, $vars));
	}
}