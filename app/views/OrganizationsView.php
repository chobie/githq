<?php
class OrganizationsView extends BaseView
{
	public function prepareResponse($vars = array())
	{
		return new UIKit\Framework\HTTPFoundation\Response\HTTPResponse($this->render->render2("/organizations/" . $this->template, $vars));
	}	
}