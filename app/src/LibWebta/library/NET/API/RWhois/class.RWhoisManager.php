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
     * @subpackage RWhois
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

    /**
     * Rwhois config manager
     * 
     * @name       RWhoisManager
     * @category   LibWebta
     * @package    NET_API
     * @subpackage RWhois
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class RWhoisManager extends Core 
    {
        /**
         * Path to rwhois_indexer
         *
         * @var string
         * @access private
         */
        private $RWhoisIndexerPath;
        
        /**
         * Path to rwhois config directory
         *
         * @var string
         * @access private
         */
        private $RWHoisConfDir;
               
        /**
         * Network transport
         *
         * @var string
         * @access private
         */
        private $Transport;
        
        /**
         * Rwhois host
         *
         * @var string
         * @access private
         */
        private $Host;
        
        /**
         * Rwhois port
         *
         * @var string
         * @access private
         */
        private $Port;
        
        /**
         * Rwhois hostmaster
         *
         * @var string
         * @access private
         */
        private $Hostmaster;
        
        /**
         * Path to RWHOISD
         *
         * @var string
         */
        private $RWhoisdPath;
        
        /**
         * RWhois Manager construcotr
         *
         * @param string $rwhois_host
         * @param string $rwhois_port
         * @param string $rwhois_hostmaster
         * @param string $rwhois_indexer_path
         * @param string $rwhois_conf_dir
         * @param string $rwhois_net_tpl_dir
         * @param string $rwhois_domain_tpl_dir
         */
        function __construct($rwhois_host, $rwhois_port, $rwhois_hostmaster, $rwhois_indexer_path, $rwhoisd_path, $rwhois_conf_dir)
        {
            $this->RWhoisIndexerPath = $rwhois_indexer_path;
            $this->RWhoisdPath = $rwhoisd_path;
            $this->RWHoisConfDir = $rwhois_conf_dir;
            
            $this->Host = $rwhois_host;
            $this->Port = $rwhois_port;
            $this->Hostmaster = $rwhois_hostmaster;
        }
        
        /**
         * Set Transport
         *
         * @param string $transport_name
         * @param array $options
         */
        function SetTransport($transport_name, array $options = array())
        {
            array_splice($options, 0, 0, $transport_name);
            $method = new ReflectionMethod("TransportFactory", "GetTransport");
            $this->Transport = $method->invokeArgs(null, $options);
        }
        
        /**
         * Remove network from rwhois
         *
         * @param string $network_address
         * @param integer $CIDR
         * @param bool $restart
         * @return unknown
         */
        public function DeleteNetwork($network_address, $CIDR, $restart = true, $isupdate = false)
        {
            if ($isupdate)
                Log::Log("RWhoisManager::DeleteNetwork Deleting network {$network_address}/{$CIDR}", LOG_LEVEL_INFO);
            
            $network_conf_path = "{$this->RWHoisConfDir}/net-{$network_address}-{$CIDR}";
            
            // Remove Old config if exists
            $this->Transport->Remove($network_conf_path, true);
            
            //Remove network from auth_area
            $auth_area = "name: {$network_address}/{$CIDR}\n";
            $auth_area .= "data-dir: net-{$network_address}-{$CIDR}/data\n";
            $auth_area .= "schema-file: net-{$network_address}-{$CIDR}/schema\n";
            $auth_area .= "soa-file: net-{$network_address}-{$CIDR}/soa\n";
            
            $area = $this->Transport->Read("{$this->RWHoisConfDir}/rwhoisd.auth_area");
            
            $new_area = str_replace($auth_area, "", $area);            
            
            $this->Transport->Write("{$this->RWHoisConfDir}/rwhoisd.auth_area", $this->NormalizeAuthArea($new_area), true);
            
            if ($restart)
                return $this->Restart();
            else 
                return true;
        }
        
        /**
         * Add network to rwhois database
         *
         * @param string $network_address
         * @param integer $CIDR
         * @param array $netinfo
         * @param bool $reindex
         * @return bool
         */
        public function AddNetwork($network_address, $CIDR, $netinfo, $reindex = true)
        {
            Log::Log("RWhoisManager::AddNetwork Adding/Update network {$network_address}/{$CIDR}", LOG_LEVEL_INFO);
            
            $network_conf_path = "{$this->RWHoisConfDir}/net-{$network_address}-{$CIDR}";
            
            // Remove Old config if exists
            $this->DeleteNetwork($network_address, $CIDR, false, true);
            
            //Create network directory
            $this->Transport->MkDir($network_conf_path);
            
            //Copy templates
            if(!$this->Transport->Copy("{$this->RWHoisConfDir}/net-10.0.0.0-8/*", "{$network_conf_path}", true))
            {
                Core::RaiseWarning("Cannot copy NET template");
                return false;
            }
            
            //
            // Edit auth_area file for rwhoisd
            //
            $auth_area = $this->Transport->Read("{$this->RWHoisConfDir}/rwhoisd.auth_area");
            // Add new network to auth_area
            $auth_area .= "---\n";
            $auth_area .= "name: {$network_address}/{$CIDR}\n";
            $auth_area .= "data-dir: net-{$network_address}-{$CIDR}/data\n";
            $auth_area .= "schema-file: net-{$network_address}-{$CIDR}/schema\n";
            $auth_area .= "soa-file: net-{$network_address}-{$CIDR}/soa\n";
            
            $auth_area = $this->NormalizeAuthArea($auth_area);
                        
            // Save auth_area file
            $this->Transport->Write("{$this->RWHoisConfDir}/rwhoisd.auth_area", $auth_area, true);
            
            //
            // Edit SOA For network
            //
            $soa = $this->Transport->Read("{$network_conf_path}/soa");
            
            // Change serial
            $serial = date("Ymd")."000000000";
            $soa = preg_replace("/Serial-Number:([0-9]+)/", "Serial-Number:{$serial}", $soa);
            // Change Primary server
            $soa = preg_replace("/Primary-Server:(.*)\n/", "Primary-Server:{$this->Host}:{$this->Port}\n", $soa);
            //Change Hostmaster
            $soa = preg_replace("/Hostmaster:(.*)\n/", "Hostmaster:{$this->Hostmaster}\n", $soa);
            // Save soa
            $this->Transport->Write("{$network_conf_path}/soa", $soa, true);
            
            //
            // Edit Schema For network
            //
            //$schema = $this->Transport->Read("{$network_conf_path}/schema");
            
            // Create schema content
            $schema  = "name:network\n";
            $schema .= "attributedef:net-{$network_address}-{$CIDR}/attribute_defs/network.tmpl\n";
            $schema .= "dbdir:net-{$network_address}-{$CIDR}/data/network\n";
            $schema .= "Schema-Version: {$serial}\n";
            // Write schema
            $this->Transport->Write("{$network_conf_path}/schema", $schema, true);

            //
            // Edit network information
            //          
            $network  = "ID: {$netinfo["id"]}\n";
            $network .= "Auth-Area: {$network_address}/{$CIDR}\n";
            $network .= "Network-Name: {$netinfo["name"]}\n";
            $network .= "IP-Network: {$network_address}/{$CIDR}\n";
            $network .= "Organization: {$netinfo["org"]}\n";
            $network .= "Tech-Contact: {$netinfo["tech"]}\n";
            $network .= "Admin-Contact: {$netinfo["admin"]}\n";
            $network .= "Created: {$netinfo["dtcreated"]}\n";
            $network .= "Updated: {$netinfo["dtupdated"]}\n";
            $network .= "Updated-By: {$this->Hostmaster}\n";
            
            $this->Transport->Write("{$network_conf_path}/data/network/network.txt", $network, true);
            
            if ($reindex)
            {
                $this->Reindex();
                return $this->Restart();
            }
            else 
                return true;
        }
        
        private function NormalizeAuthArea($content)
        {
            $content = trim($content);
            
            if (substr($content, 0, 3) == "---")
                $content = substr($content, 3);
                
            if (substr($content, -3) == "---")
                $content = substr($content, 0, strlen($content)-3);
                
            $content = preg_replace("/---[\r\n]---/s", "---", $content);
            
            $content = preg_replace("/[\n]+/s", "\n", $content);
            
            if (trim($content) == '---')
                $content = "";
                
            return $content."\n";
        }
        
        /**
         * Reindex rwhois data
         *
         * @return bool
         */
        public function Reindex()
        {
            Log::Log("RWhoisManager::Reindex Executing reindex routine for RWhois", LOG_LEVEL_INFO);
            $res = $this->Transport->Execute("{$this->RWhoisIndexerPath} -c {$this->RWHoisConfDir}/rwhoisd.conf -i -v -s .txt");
            if (!stristr($res, "done."))
            {
                Log::Log("RWhoisManager::Reindex failed (".trim($res).")", LOG_LEVEL_ERROR);
                return false;
            }
            else
                return true;
        }
        
        /**
         * Restart rwhois server
         *
         * @return bool
         */
        public function Restart()
        {
            Log::Log("RWhoisManager::Restart Restarting RWhois server", LOG_LEVEL_INFO);
            $this->Transport->Execute("killall -9 rwhoisd");
            return $this->Transport->Execute("{$this->RWhoisdPath} -d -c {$this->RWHoisConfDir}/rwhoisd.conf 2>&1 &");
        }
    }
?>