<?php

/**
 * Domain transfer status constancts
 * @category EPP-DRS
 * @package Modules
 * @subpackage RegistryModules
 * @sdk
 */
final class TRANSFER_STATUS
{
	/**
	 * Transfer approved by holder or registry
	 *
	 */
	const APPROVED = 1;
	
	/**
	 * Transfer declined by holder or registry
	 *
	 */
    const DECLINED = 0;
	
    /**
     * Transfer is awaiting authorization 
     */
	const PENDING = 2;
	
	/**
	 * Transfer failed
	 */
	const FAILED = 3;
}

?>