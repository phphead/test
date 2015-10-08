<?php namespace Rj;

use Exception,
	Rj\EI\ValidationErrorInterface;

class ValidationException extends Exception implements ValidationErrorInterface {}
