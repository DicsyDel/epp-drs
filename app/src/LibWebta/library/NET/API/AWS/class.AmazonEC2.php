<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 

	Core::Load("NET/API/AWS/WSSESoapClient");
	
	class DescribeInstancesType
	{
		public $instancesSet;
		public $groupSet;
	};
	
	class DescribeImagesType
	{
		public $executableBySet = null;
		public $imagesSet = null;
		public $ownersSet = null;
	};
	
	class DescribeImagesOwnersType
	{
		public $owner;
	};
	
	class DescribeImageAttributeType
	{
	    public $imageId;
	    public $launchPermission;
	};
	
	class ModifyImageAttributeType
	{
	    public $imageId;
	    public $launchPermission;
	};
	
	class RunInstancesType
	{
	    public $imageId;
	    public $minCount;
	    public $maxCount;
	    public $keyName;
	    public $groupSet;
	    public $additionalInfo = "";
	    public $userData;
	    public $addressingType = "public";
	    public $instanceType = "";
	    
	    public function AddSecurityGroup($groupName)
	    {
	        if (!$this->groupSet)
	        {
	            $this->groupSet = new stdClass();
	            $this->groupSet->item = array();
	        }
	        
	        array_push($this->groupSet->item, array("groupId" => $groupName));
	    }
	    
	    public function SetUserData($data)
	    {
	        $this->userData = new stdClass();
	        $this->userData->version = "1.0";
	        $this->userData->encoding = "base64";
	        $this->userData->data = base64_encode($data);
	    }
	}
	
	/**
	 * IpPermissionSetType
	 *
	 * @todo $groups not supported yet
	 */
	class IpPermissionSetType
	{
	    public $item = array();
	    
	    public function AddItem($ipProtocol, $fromPort, $toPort, $groups, $ipRanges)
	    {
	        $stdClass = new stdClass();
	        $stdClass->ipProtocol = $ipProtocol;
	        $stdClass->fromPort = $fromPort;
	        $stdClass->toPort = $toPort;
	        $stdClass->groups = "";
	        $stdClass->ipRanges = new stdClass();
	        $stdClass->ipRanges->item = array();
	        foreach ($ipRanges as $ipRange)
	           array_push($stdClass->ipRanges->item, array("cidrIp" => $ipRange));
	           
	        array_push($this->item, $stdClass);
	    }
	}
	
    /**
     * @name AmazonEC2
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
	
	class AmazonEC2 
    {
	    const EC2WSDL = 'http://s3.amazonaws.com/ec2-downloads/2007-08-29.ec2.wsdl';
	    const KEY_PATH = '/etc/awskey.pem';
	    const CERT_PATH = '/etc/awscert.pem';
	    const USER_AGENT = 'Libwebta AWS Client (http://webta.net)';
	    
		private $EC2SoapClient = NULL;
	
		public function __construct($key_path = null, $cert_path = null) 
		{
			
			// Defaultize
			$key_path = $key_path == null ? self::KEY_PATH : $key_path;
			$cert_path = $cert_path == null ? self::CERT_PATH : $cert_path;
			
	      	$this->EC2SoapClient  = new WSSESoapClient(AmazonEC2::EC2WSDL, array('trace' => 1, 'exceptions'=> 0, 'user_agent' => AmazonEC2::USER_AGENT));
			/* Force location path - MUST INCLUDE trailing slash
			BUG in ext/soap that does not automatically add / if URL does not contain path. this causes POST header to be invalid 
			Seems like will be fixed in PHP 5.2 Release*/
			$this->EC2SoapClient->location = 'https://ec2.amazonaws.com/';
			
			$this->EC2SoapClient->KeyPath = $key_path;
			$this->EC2SoapClient->CertPath = $cert_path;
		}		
		
		/**
		 * The AuthorizeSecurityGroupIngress operation adds permissions to a security group.
         * Permissions are specified by the IP protocol (TCP, UDP or ICMP), the source of the request (by IP
         * range or an Amazon EC2 user-group pair), the source and destination port ranges (for TCP and UDP),
         * and the ICMP codes and types (for ICMP).
         * Permission changes are propagated to instances within the security group as quickly as possible.
         * However, depending on the number of instances, a small delay might occur.
		 *
		 * @param styring $userId
		 * @param string $groupName
		 * @param IpPermissionSetType $ipPermissions
		 * @return bool
		 */
		public function AuthorizeSecurityGroupIngress($userId, $groupName, IpPermissionSetType $ipPermissions)
		{
		    try 
			{
				$stdClass = new stdClass();
			    $stdClass->userId = $userId;
				$stdClass->groupName = $groupName;
				$stdClass->ipPermissions = $ipPermissions;

				$response = $this->EC2SoapClient->AuthorizeSecurityGroupIngress($stdClass);
				
				if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DeleteSecurityGroup operation deletes a security group.
		 *
		 * If you attempt to delete a security group that contains instances, a fault is returned.
		 * 
		 * @param string $groupName
		 * @return bool
		 */
		public function DeleteSecurityGroup($groupName)
		{
		    try 
			{
				$response = $this->EC2SoapClient->DeleteSecurityGroup(array("groupName" => $groupName));
				
				if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The CreateSecurityGroup operation creates a new security group.
         * Every instance is launched in a security group. If none is specified as part of the launch request then
         * instances are launched in the default security group. Instances within the same security group have
         * unrestricted network access to one another. Instances will reject network access attempts from other
         * instances in a different security group. As the owner of instances you may grant or revoke specific
         * permissions using the AuthorizeSecurityGroupIngress and RevokeSecurityGroupIngress operations.
		 *
		 * @param string $groupName
		 * @param string $groupDescription
		 * @return boolean
		 */
		public function CreateSecurityGroup($groupName, $groupDescription)
		{
		    try 
			{
				$stdClass = new stdClass();
			    $stdClass->groupName = $groupName;
				$stdClass->groupDescription = $groupDescription;

				$response = $this->EC2SoapClient->CreateSecurityGroup($stdClass);
				
				if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DeleteSecurityGroup operation deletes a security group.
		 * 
         * If an attempt is made to delete a security group and any instances exist that are members of that group a
         * fault is returned.
		 *
		 * @param securityGroupSet $securityGroupSet
		 * @return Object
		 */
		public function DescribeSecurityGroups($securityGroupSet = NULL)
		{
		    try 
			{
				$response = $this->EC2SoapClient->DescribeSecurityGroups($securityGroupSet);
				
				if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The ModifyImageAttribute operation modifies an attribute of an AMI.
		 *
		 * @param string $imageId AMI id
		 * @param string $operation 'add' OR 'delete'
		 * @param array $item (group => groupname) OR (userId => userId)
		 * @return boolean
		 */
		public function ModifyImageAttribute($imageId, $operation, $item)
		{
		    try 
			{
				$ModifyImageAttributeType = new ModifyImageAttributeType();
				$ModifyImageAttributeType->imageId = $imageId;
				$ModifyImageAttributeType->launchPermission = array( $operation => array('item' => $item ));
			    				
			    $response = $this->EC2SoapClient->ModifyImageAttribute($ModifyImageAttributeType);
			    
			    if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		// TODO: Discribe productCodes;
		public function DescribeImageAttribute($imageId)
		{
		    try 
			{
				$DescribeImageAttributeType = new DescribeImageAttributeType();
				$DescribeImageAttributeType->imageId = $imageId;
			    
			    $response = $this->EC2SoapClient->DescribeImageAttribute($DescribeImageAttributeType);
			    
			    if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		public function DescribeImages($DescribeImagesType = NULL) 
		{
			try 
			{
				$response = $this->EC2SoapClient->DescribeImages($DescribeImagesType);
				
				if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
				
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
	
		public function DescribeInstances($instanceId = NULL) 
		{
	
		    try 
		    {
				if(!empty($instanceId)) 
				{
				    $objInstances->instancesSet = array('item' => array('instanceId' => $instanceId) ); 
				};
				
				$response = $this->EC2SoapClient->DescribeInstances($objInstances);
				
				if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
				
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
	
	
	
		public function DescribeKeyPairs($keyName=NULL) 
		{
	
            try 
            {
            	$objkeyPairs = new keyPairs();
            	$response = $this->EC2SoapClient->DescribeKeyPairs($objkeyPairs);	
            	
            	if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
            } 
            catch (SoapFault $e) 
            {
                Core::RaiseError($e->getMessage(), E_ERROR);
            }
            
            return $response;
		}
	
		/**
		 * The TerminateInstances operation shuts down one or more instances. This operation is idempotent;
         * 
         * if you terminate an instance more than once, each call will succeed.
         * Terminated instances will remain visible after termination (approximately one hour).
		 *
		 * @param array $instances
		 * @return bool
		 */
		public function TerminateInstances($instances) 
		{
			try 
			{
                $instancesSet = new stdClass();
                $instancesSet->instancesSet->item = array();
                foreach ($instances as $instance)
                    array_push($instancesSet->instancesSet->item, array("instanceId" => $instance));
			    
                $response = $this->EC2SoapClient->TerminateInstances($instancesSet);
                
                if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR); 
			}
			
			return $response;
		}
		
   	 	public function RebootInstances($instances) 
		{
			try 
			{
                $instancesSet = new stdClass();
                $instancesSet->instancesSet->item = array();
                foreach ($instances as $instance)
                    array_push($instancesSet->instancesSet->item, array("instanceId" => $instance));
			    
                $response = $this->EC2SoapClient->RebootInstances($instancesSet);
                
                if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR); 
			}
			
			return $response;
		}
	
		/**
		 * If Amazon EC2 cannot launch the minimum number AMIs you request, no instances will be launched. If
         * there is insufficient capacity to launch the maximum number of AMIs you request, Amazon EC2
         * launches the minimum number specified for each AMI and allocate the remaining available instances
         * using round robin.
         * In the following example, Libby generates a request to launch two images (database and web_server):
         * 
         * 1. Libby runs the RunInstances operation to launch database instances (min. 10, max. 15) and
         * web_server instances (min. 30, max. 40).
         * Because there are currently 30 instances available and Libby needs a minimum of 40, no instances
         * are launched.
         * 
         * 2. Libby adjusts the number of instances she needs and runs the RunInstances operation to launch
         * database instances (min. 5, max. 10) and web_server instances (min. 20, max. 40).
         * Amazon EC2 launches the minimum number of instances for each AMI (5 database, 20 web_server).
         * The remaining 5 instances are allocated using round robin.
         * 
         * 3. Libby adjusts the number of instances she needs and runs the RunInstances operation again to
         * launch database instances (min. 5, max. 10) and web_server instances (min. 20, max. 40).
		 *
		 * @param RunInstancesType $RunInstancesType
		 * @return object
		 */
		public function RunInstances(RunInstancesType $RunInstancesType) 
		{
            try 
            {
                $response = $this->EC2SoapClient->RunInstances($RunInstancesType);
                
                if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
            } 
            catch (SoapFault $e) 
            {
                Core::RaiseError($e->getMessage(), E_ERROR); 
            }
            
            return $response;
		}
	
		public function DeleteKeyPair($keyName) 
		{
            try 
            {
                $response = $this->EC2SoapClient->DeleteKeyPair(array('keyName' => $keyName));
                
                if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
            } 
            catch (SoapFault $e) 
            {
                Core::RaiseError($e->getMessage(), E_ERROR);
            }
            
            return $response;
		}
	
		public function CreateKeyPair($keyName) 
		{
		    try 
		    {
				$response = $this->EC2SoapClient->CreateKeyPair(array('keyName' => $keyName));
				
				if ($response instanceof SoapFault)
					Core::RaiseError($response->faultstring, E_ERROR);
				
			} 
			catch (SoapFault $e) 
			{
			    Core::RaiseError($e->getMessage(), E_ERROR);	
			}
	
			return $response;
		}
    }
?>
