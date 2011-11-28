<?php
class RootController extends UIkit\Framework\UIAppController
{
	public function onDefault()
	{
		$this->render("index.htm",array());
	}
}
