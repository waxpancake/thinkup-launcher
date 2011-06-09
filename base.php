<?php

function createSecurityGroup($ec2) {
    $opt = array('GroupName' => 'thinkup');

    # $ec2->delete_security_group($opt);
    $response = $ec2->describe_security_groups($opt);
    
    if ($response->status != 200) {
        $ec2->create_security_group('thinkup', 'This is the default security group for ThinkUp.');
        $perms = array(
            'GroupName' => 'thinkup',
            'IpPermissions' => array(
                array( // Set 0
                    'IpProtocol' => 'tcp',
                    'FromPort' => '22',
                    'ToPort' => '22',
                    'IpRanges' => array(
                        array('CidrIp' => '0.0.0.0/0')
                    )
                ),
                array( // Set 1
                    'IpProtocol' => 'tcp',
                    'FromPort' => '80',
                    'ToPort' => '80',
                    'IpRanges' => array(
                        array('CidrIp' => '0.0.0.0/0')
                    )
                ),
                array( // Set 2
                    'IpProtocol' => 'tcp',
                    'FromPort' => '443',
                    'ToPort' => '443',
                    'IpRanges' => array(
                        array('CidrIp' => '0.0.0.0/0')
                    )
                ),
            )
        );
        $response = $ec2->authorize_security_group_ingress($perms);
        if ($response->status == 200) {
            return 'thinkup';
        }
    }

}


function createKeyPair($ec2) {
    # $response = $ec2->delete_key_pair('thinkup');

    $response = $ec2->describe_key_pairs(array(
        'KeyName' => 'thinkup'
    ));
    
    if ($response->status != 200) {
        $response = $ec2->create_key_pair('thinkup');
        
        # header('Content-type: application/pem');
        # header('Content-Disposition: attachment; filename="thinkup.pem"');
        
        return $response->body->keyMaterial;
    }
}



function createInstance($ec2, $ami, $userdata_add) {
    $password = randomPassword(12);
    $userdata = getUserdata($password);
    if (isset($userdata_add)) {
        $userdata .= $userdata_add;
    }
    
    $opt = array(
        'InstanceType' => 'm1.small',
        'SecurityGroup' => 'thinkup',
        'KeyName' => 'thinkup',
        'InstanceType' => 't1.micro',
        'UserData' => base64_encode($userdata)
    );
    
    $response = $ec2->run_instances($ami, 1, 1, $opt);
    if ($response->status == 200) {
        $instance_id = $response->body->instancesSet->item->instanceId;
        return array($instance_id, $password);
    }
}

function getServerName($ec2, $instance_id) {
    $response = $ec2->describe_instances(array(
        'InstanceId' => $instance_id
    ));
    if ($response->status == 200) {
        $hostname = $response->body->reservationSet->item->instancesSet->item->dnsName;
        return $hostname;
    }
}


function randomPassword($random_string_length) {
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $string = '';
    for ($i = 0; $i < $random_string_length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
    
}


function getUserdata($password) {
    $userdata = <<<EOD
#!/bin/bash -ex
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

wget https://github.com/downloads/ginatrapani/ThinkUp/thinkup_0.12.1.zip --no-check-certificate
sudo unzip -d /var/www/ thinkup_0.12.1.zip

# config thinkup installer
sudo ln -s /usr/sbin/sendmail /usr/bin/sendmail
sudo chown -R www-data /var/www/thinkup/_lib/view/compiled_view/
sudo touch /var/www/thinkup/config.inc.php
sudo chown www-data /var/www/thinkup/config.inc.php

# create database
mysqladmin -u root password $password 
mysqladmin -h localhost -u root -p$password create thinkup

# add apparmor exception for ThinkUp backup
sudo sed -i '
/\/var\/run\/mysqld\/mysqld.sock w,/ a\
  /var/www/thinkup/_lib/view/compiled_view/** rw,
' /etc/apparmor.d/usr.sbin.mysqld
sudo /etc/init.d/apparmor restart
EOD;
    return $userdata;
    
}

?>