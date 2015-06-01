<?php
class AppInsights_Pay_Load {
	public $AggregateByDimension = array(); // optional
	public $MetricsToCalculate = array(); // required
	public $DimensionFilters = array(); // required At least one on 'context.data.eventTime' with a Start and End
	
	public function __construct() {
		$this->AggregateByDimension[] = new AggregateByDimension();
		$this->MetricsToCalculate[] = new MetricsToCalculate();
		$this->DimensionFilters[] = new DimensionFilters();
	}
}

class AggregateByDimension {
	public $Dimension; // required

	public function __construct() {
		$this->Dimension = new Dimension();
	}
}

class Dimension {
	public $Key; // required
	public $Grain; // optional
	
	public function __construct() {
		$this->Key = 'context.data.eventTime';
	}
}

class MetricsToCalculate {
	public $Metric; // required
	public $ApplyFunction; // required

	public function __construct() {
		$this->Metric = new Metric();
	}
}

class Metric {
	public $Key; // required

	public function __construct() {

	}
}

class DimensionFilters {
	public $Dimension; // required
	public $FilterExpression; // optional when Dimension.Key is 'context.data.eventTime', required otherwise
	public $Start; // required when Dimension.Key is 'context.data.eventTime'
	public $End; // required when Dimension.Key is 'context.data.eventTime'

	public function __construct() {
		$this->Dimension = new Dimension();
	}
}