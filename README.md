# ThinkUp Launcher

A one-click web page for simply booting your own private EC2 instance running ThinkUp.

Learn more about ThinkUp, an open-source web app for tracking and archiving Twitter
and Facebook, at http://thinkupapp.com/

**THIS IS ALPHA SOFTWARE.**

## Installation

Requires PHP 5.  

Drop in a directory on your webserver, and copy config.inc.php.sample to config.inc.php.
Edit config.inc.php to point to the full directory name.  The rest should just work.

## What It Does

Nothing is saved or stored on the server. Your AWS keys are used only for the EC2 API requests.

After submitting your keys, ThinkUp Launcher does the following:

* Creates a new "thinkup" security group for the user, if it doesn't
already exist.
* Creates a new "thinkup" key pair for the user, if it doesn't exist.
* Using a pre-built Ubuntu AMI with all of ThinkUp's prerequisites,
starts an EC2 micro instance in the us-east availability zone.
* Downloads and installs the latest ThinkUp beta from Github.
* Creates the new MySQL user and tables with a randomly-generated password.
* Gives the user the link to the new ThinkUp install with their MySQL credentials.

## Thanks

ThinkUp Launcher uses the following projects.

jQuery and jQuery UI:
http://jquery.com/

Amazon SDK for PHP:
http://aws.amazon.com/sdkforphp/

Mark Dotto's 3D Text: 
http://www.markdotto.com/2011/01/05/3d-text-using-just-css/

Ivan Vanderbyl's Pure CSS Progress Bar:
http://skunkworks.ivanvanderbyl.com/

Zurb's Reveal Modal Plugin for jQuery:
http://www.zurb.com/playground/reveal-modal-plugin

## Comments?

All pull requests and feedback appreciated.  

