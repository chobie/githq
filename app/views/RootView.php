<?php
class RootView extends BaseView
{	
	public function prepareResponse($vars = array())
	{
		$vars['user'] = $this->user;
		$organizations = null;
		if ($this->user) {
			$vars['user'] = $this->user;
		}
		return parent::prepareResponse($vars);
	}
}