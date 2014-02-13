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
     * @package    IO
     * @subpackage PCNTL
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

    Core::Load("System/Independent/Shell/class.Getopt.php");
    
	/**
     * @name JobLauncher
     * @category   LibWebta
     * @package    IO
     * @subpackage PCNTL
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @example tests.php
     * @see tests.php
     */
    class JobLauncher extends Core
    {
        private $ProcessName;
        
        function __construct($process_classes_folder)
        {
            $processes = @glob("{$process_classes_folder}/class.*Process.php");
            
           $jobs = array();
            if (count($processes) > 0)
            {
                foreach ($processes as $process)
                {
                    $filename = basename($process);
                    $directory = dirname($process);
                    Core::Load($filename, $directory);
                    preg_match("/class.(.*)Process.php/s", $filename, $tmp);
                    $process_name = $tmp[1];
                    if (class_exists("{$process_name}Process"))
                    {
                        $reflect = new ReflectionClass("{$process_name}Process");
                        if ($reflect)
                        {
                            if ($reflect->implementsInterface("IProcess"))
                            {
                                $job = array(
                                                "name"          => $process_name,
                                                "description"   => $reflect->getProperty("ProcessDescription")->getValue($reflect->newInstance())
                                            );
                                array_push($jobs, $job);    
                            }
                            else 
                                Core::RaiseError("Class '{$process_name}Process' doesn't implement 'IProcess' interface.", E_ERROR);
                        }
                        else 
                            Core::RaiseError("Cannot use ReflectionAPI for class '{$process_name}Process'", E_ERROR);
                    }
                    else
                        Core::RaiseError("'{$process}' does not contain definition for '{$process_name}Process'", E_ERROR);
                }
            }
            else 
                Core::RaiseError(_("No job classes found in {$ProcessClassesFolder}"), E_ERROR);
             
            $options = array();
            foreach($jobs as $job)
                $options[$job["name"]] = $job["description"];

            $options["help"] = "Print this help";
                           
            $Getopt = new Getopt($options);
            $opts = $Getopt->getOptions();
            
            if (in_array("help", $opts) || count($opts) == 0 || !$options[$opts[0]])
            {
                print $Getopt->getUsageMessage();    
                exit();
            }
            else
            {                               
                $this->ProcessName = $opts[0];
            }
        }
        
        /**
         * Return Process name
         *
         * @return string
         */
        function GetProcessName()
        {
        	return $this->ProcessName;
        }
        
        function Launch($max_chinds = 5)
        {
            $proccess = new ReflectionClass("{$this->ProcessName}Process");
            $sig_handler = new ReflectionClass("SignalHandler");
            $PR = new ProcessManager($sig_handler->newInstance());
            $PR->SetMaxChilds($max_chinds);
            $PR->Run($proccess->newInstance());
        }
    }
?>
