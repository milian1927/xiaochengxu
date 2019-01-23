<?php

namespace app\admin\controller;

class index extends Controller
{
    public function index()
    {
		return $this->fetch('index/index');
    }
}
