<?php
class AdminView extends BaseView
{
	public function prepareResponse($vars = array())
	{
		return new UIKit\Framework\HTTPFoundation\Response\HTTPResponse($this->render->render2("/admin/" . $this->template, $vars));
	}
}