<?php declare(strict_types=1);

namespace Convo\Core;

interface IAdminUser
{
	public function isSystem();
	public function getId();
	public function getUsername();
	public function getEmail();
	public function getName();
}