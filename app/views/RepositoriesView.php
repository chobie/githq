<?php
class RepositoriesView extends BaseView
{
	public function prepareResponse($vars = array())
	{
		return new UIKit\Framework\HTTPFoundation\Response\HTTPResponse($this->render->render2("/repositories/" . $this->template, $vars));
	}
	
}