<?php

// Kevin O'Connor <kevino@arc90.com> / @gooeyblob

// Before you begin - check http://docs.amazonwebservices.com/AWSSDKforPHP/latest/#m=AmazonCloudWatch/get_metric_statistics
// for plenty of info on how to query statistics.  The comments really only make sense after you've read through the API reference.

// This example will take a given LB instance and graph its requests per minute

// Amazon works in UTC
date_default_timezone_set('UTC');

// Set your Key and Secret here, it should have at least Read Only access to CloudWatch
$key = '';
$secret = '';

// This should be something like AWS/RDS for RDS, AWS/ELB for ELB, etc.
// You can find more info in the CloudWatch section of the AWS Console
$namespace = 'AWS/ELB';

// Metric to retrieve, this should be something like RequestCount, FreeStorageSpace, etc.
$metric = 'RequestCount';

// Start & end times to retrieve statistics in, strtotime() compatible
$start_time = '-5 minutes';
$end_time = 'now';

// Interval for statistics, i.e. retrieve for every X seconds
// Must be at least 60 seconds and must be a multiple of 60
$interval = 60;

// Metric type, can be Average, Sum, Min, etc.
$metric_type = 'Sum';

// Unit to return, check the API reference for all the possibilities
$unit_type = 'Count';

// I'm not sure why this is called 'Dimensions' with Amazon, but basically these are
// Metric descriptors.  For instance, Name is LoadBalancerName and Value is MyLoadBalancer
// You can find out all the different options here through the AWS console and possibly querying
// the API directly.  I haven't been able to find a complete list - if someone knows of
// one please let me know!
$dimensions = array('Name' => 'LoadBalancerName', 'Value' => 'MyLoadBalancer');

// From here the script will retrieve the given metric and print the second latest value when requested.
// I choose the second as the first retrieved value is generally part of the current interval and as such
// really only represents a partial interval and doesn't reflect reality.  Obviously it's easily changed
// if you find you need it.

require_once('aws-sdk-for-php/sdk.class.php');

$cw = new AmazonCloudWatch(array('key' => $key,
				'secret' => $secret));

$count = $cw->get_metric_statistics($namespace, $metric, $start_time, $end_time, $interval, $metric_type, $unit_type, array('Dimensions' => array($dimensions)));

$d = array();

foreach($count->body->GetMetricStatisticsResult->Datapoints->member as $point) {
    // Create an array with all results with the timestamp as the key and the statistic as the value
	$time = strtotime($point->Timestamp);
	$d[$time] = (int)$point->Sum[0];
}


// Sort by key while maintaining association in order to have an oldest->newest sorted dataset
ksort($d);

// Knock off the newest value
array_pop($d);

// Return the now newest value for consumption by flot
print array_pop($d);
?>
