<html>
<head>
<title>ThinkUp Launcher</title>
<link href="css/styles.css" media="all" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="css/reveal.css">
<link rel="stylesheet" href="css/ui.progress-bar.css">
<link media="only screen and (max-device-width: 480px)" href="css/ios.css" type="text/css" rel="stylesheet" />
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script> 
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js"></script> 

<meta name="viewport" content="width=device-width">  

<?php
include('config.inc.php');
require('aws-sdk/sdk.class.php');
require('base.php');

$ami = 'ami-75a9651c';

if (isset($_POST['aws_id']) && isset($_POST['aws_key'])) {
    $ec2 = new AmazonEC2($_POST['aws_id'], $_POST['aws_key']);
    $response = $ec2->describe_instances();
    if ($response->isOK() == true) {
        $ec2->set_region(AmazonEC2::REGION_US_E1);        
        $keypair = createKeyPair($ec2);
        $securitygroup = createSecurityGroup($ec2);
        list($instance_id, $password) = createInstance($ec2, $ami);
        
    } else {
        ?>
        <script>
            $(document).ready(function() {
        		$("#modal-message").html("Your AWS Access Key ID and/or Secret Key don't appear to be valid. Please verify that you have <a href='http://aws.amazon.com/ec2' target='_blank'>access to EC2</a> &mdash; not just Amazon Web Services &mdash; and retry.");
                $('#myModal').reveal();
            });
        </script>
        <?
    }
}
?>
</head>
<body>
<div id="main">
    <div id="myModal" class="reveal-modal">
         <div id="modal-message"></div>
         <a class="close-reveal-modal">&#215;</a>
    </div>
    
    <div id="title">ThinkUp Launcher</div>
    <div id="subhead">Create Your Own Private <a href="http://thinkupapp.com/">ThinkUp</a> Server on Amazon EC2</div>
    <div id="description">
        Free for the first year for new Amazon EC2 users; everyone else pays about 
        $14.50 per month. <a href="http://aws.amazon.com/ec2/pricing/">Learn more</a>.
    </div>
    
    <h3>To get started, enter your Amazon Web Services keys.</h3>

    <div id="instructions">
        No Amazon Web Services account? <a href="http://aws.amazon.com/ec2">Sign up for EC2 now</a>.<br /> 
        If you already have an EC2 account, here are your <a href="https://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key#access_credentials" target="_blank">your Access Keys</a>.
    </div>
        
    <form action="" method="post">
        <div class="input">
            <label for="aws_id">Access Key ID</label>
            <input type="text" id="aws_id" name="aws_id" class="text" value="<?= $_POST['aws_id'] ?>" />
            <span id="aws_id_error" class="error" style="display:none">Access Key ID is required. <a href="https://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key#access_credentials">Find it here</a>.</span>
        </div>
        <div class="input">
            <label for="aws_key">Secret Access Key</label>
            <input type="text" id="aws_key" name="aws_key" class="text" value="<?= $_POST['aws_key'] ?>"/>
            <span id="aws_key_error" class="error" style="display:none">Secret Access Key is required. <a href="https://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key#access_credentials">Find it here</a>.</span>
        </div>
        
        <input type="submit" value="Start it up &raquo;" id="submit" class="button large" />
    </form>

    <?php if (isset($instance_id)) { ?>
        <div id="container">
            <div id="status">Starting up (about 45-60 seconds)...</div>

            <div id="progress_bar" class="ui-progress-bar ui-container">
              <div class="ui-progress" style="width: 10%;">
                <span class="ui-label" style="display:none;">Booting <b class="value">10%</b></span>
              </div>
            </div>
        
            <div id="serverinfo" style="display: none">
                <p><strong>Your ThinkUp server is now running!</strong></p>
                
                <p>Finish your ThinkUp installation here:</p>
                <p><span id="thinkup_link"></span></p>
                
                <p>
                <strong>Your Database Info</strong> (needed to complete ThinkUp installation):<br />
                <blockquote>
                    Database Host: localhost<br />
                    Database Name: thinkup<br />
                    User Name: root<br />
                    Password: <?= $password ?>
                </blockquote>
                </p>
                
                <p><strong>Important!</strong> If you ever want to shut down your server, terminate it 
                using the <a href="https://console.aws.amazon.com/ec2/home?region=us-east-1#s=Instances">AWS Dashboard</a>.  
                Otherwise, you'll continue to get charged for your server every month.</p>
            
            <?php if (isset($keypair)) { ?>
                <p><strong>Private Key</strong></p>
                    
                <p>Save the following private key in a file called <strong>thinkup.pem</strong>. You'll 
                need it if you ever want to SSH into your server.</p>
                
                <blockquote><pre><?= $keypair ?></pre></blockquote>            
            <?php } ?>
            
                <p>Thanks for using ThinkUp Launcher! Send any feedback/issues to andy@waxy.org.</p>
            </div>
        </div>        
    <? } ?>

    <div id="footer">
        Learn more about <a href="http://thinkupapp.com/">ThinkUp</a> |
        Help improve <a href="https://github.com/waxpancake/thinkup-launcher">ThinkUp Launcher on Github</a>
    </div>


</div>


<script type="text/javascript">
    $(function() {
        // form validation on submit
        $("#submit").click(function() {  
            $(".error").hide();
            var aws_id = $("input#aws_id").val();
            var aws_key = $("input#aws_key").val(); 
            if (aws_id == "") {
                $("#aws_id_error").show();
                $("input#aws_id").focus();
            }
            
            if (aws_key == "") {
                $("#aws_key_error").show();
                $("input#aws_key").focus();
            }
            
            if (aws_id == "" || aws_key == "") {
                return false;
            }
        });  
    });
    
<?php if (isset($instance_id)) { ?>
    var percentProgress = 10;

    var checkStatus = function() {                
        $.getJSON('<?= $_GLOBAL['path'] ?>/ec2_status.php', { aws_id: "<?= $_POST['aws_id'] ?>", aws_key: "<?= $_POST['aws_key'] ?>", instance_id: "<?= $instance_id ?>" }, function(data) {
            if (data.status[0] == 'terminated') {
                $("#status").text("Instance was terminated. Please try again.");
            } else if (data.status[0] == 'running') {
                $('#progress_bar .ui-progress').animateProgress(87);

                var dnsName = data.dnsName[0]
                var server_url = 'http://' + dnsName;
                var thinkup_url = 'http://' + dnsName + '/thinkup/install';
                
                var server_link = '<a href="' + server_url + '">' + server_url + '/</a>';
                var thinkup_link = '<a href="' + thinkup_url + '">' + thinkup_url + '</a>';

                setTimeout(function() {
                    $('#progress_bar .ui-progress').animateProgress(100);
                                    
                    $("#server_link").append(server_link);
                    $("#thinkup_link").append(thinkup_link);
                    $('#serverinfo').slideDown();                
                }, 16000);
            } else {
                percentProgress += 2;
                $('#progress_bar .ui-progress').animateProgress(percentProgress);
                setTimeout(checkStatus, 1000);
            }
        });
    };
    $(document).ready(function() {
        $('#progress_bar .ui-progress .ui-label').hide();
        $('#progress_bar .ui-progress').css('width', '10%'); 
        checkStatus(); 
    });

<?php } ?>

</script>
<script src="js/jquery.reveal.js" type="text/javascript"></script>
<script src="js/progress.js" type="text/javascript" charset="utf-8"></script>
</body>
</html>
