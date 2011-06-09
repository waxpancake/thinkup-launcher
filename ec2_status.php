<?php
require('./aws-sdk/sdk.class.php');
require('./base.php');

if (isset($_GET['aws_id']) && isset($_GET['aws_key']) && isset($_GET['instance_id'])) {
    $ec2 = new AmazonEC2($_GET['aws_id'], $_GET['aws_key']);
    $ec2->set_region(AmazonEC2::REGION_US_E1);

    $response = $ec2->describe_instances(array(
        'InstanceId' => $_GET['instance_id']
    ));
    $status = $response->body->reservationSet->item->instancesSet->item->instanceState->name;
    $dnsName = $response->body->reservationSet->item->instancesSet->item->dnsName; 
    
    $data['status'] = $status;
    $data['dnsName'] = $dnsName;

    echo json_encode($data);
}
?>